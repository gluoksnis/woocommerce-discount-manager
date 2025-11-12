<?php
/**
 * Discounts Add Class
 *
 * @package WC_Discount_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCDM_Discounts_Add {

    /**
     * Render the page
     */
    public static function render_page() {
        echo '<div class="wrap">';
        echo '<h1>' . WCDM_CAMPAIGNS_NAME . '</h1>';
        echo '<div id="poststuff">';
        echo '<div id="post-body" class="metabox-holder columns-2">';
        echo '<div id="post-body-content">';
        echo '<div class="meta-box-sortables ui-sortable">';

        // Accordion section for the upload form
        echo '<div class="postbox">';
        echo '<h2 class="hndle">' . __('Įkelti CSV arba pasirinkti kategoriją kuriai norite pritaikyti nuolaidą', 'wc-discount-manager') . '</h2>';
        echo '<div class="inside">';

        // Handle the file upload
        if (isset($_FILES['csv']) && ($_FILES['csv']['error'] == UPLOAD_ERR_OK)) {
            self::process_csv_upload();
        } elseif (!empty($_POST['category'])) {
            self::process_category_discount();
        } else {
            self::render_form();
        }

        echo '</div>';  // Close '.inside'
        echo '</div>';  // Close '.postbox'
        echo '</div>';  // Close '.meta-box-sortables .ui-sortable'
        echo '</div>';  // Close '#post-body-content'
        echo '</div>';  // Close '#post-body .metabox-holder .columns-2'
        echo '</div>';  // Close '#poststuff'
        echo '</div>';  // Close '.wrap'
    }

    /**
     * Process CSV upload
     */
    private static function process_csv_upload() {
        $csv = array_map('str_getcsv', file($_FILES['csv']['tmp_name']));
        $discount = floatval($_POST['discount']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $skip_existing_discounts = isset($_POST['skip_discounted']) && $_POST['skip_discounted'] == 'on';
        
        // Collect product IDs
        $product_ids = array();
        $skipped_skus = array();

        foreach ($csv as $row) {
            $sku = trim($row[0]);
            if (empty($sku)) {
                continue;
            }
            
            $product_id = wc_get_product_id_by_sku($sku);
            
            if ($product_id) {
                // Get original product ID if WPML is active
                if (class_exists('WCDM_WPML_Helper')) {
                    $product_id = WCDM_WPML_Helper::get_original_product_id($product_id);
                }
                $product_ids[] = $product_id;
            } else {
                $skipped_skus[] = $sku;
            }
        }

        if (empty($product_ids)) {
            echo '<span class="alert alert-warning">' . 
                 __('No valid products found in CSV.', 'wc-discount-manager') . '</span>';
            return;
        }

        // Show batch processing info if needed
        if (class_exists('WCDM_Batch_Processor')) {
            echo WCDM_Batch_Processor::get_batch_info_message(count($product_ids));
        }

        // Process products using batch processor
        $params = array(
            'discount' => $discount,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'skip_existing_discounts' => $skip_existing_discounts
        );

        $result = WCDM_Batch_Processor::process_batch($product_ids, 'apply_discount', $params);

        // Show results
        if (!empty($skipped_skus)) {
            echo '<span class="alert alert-warning">' . 
                 __('Produktai su šiais SKU kodais neegzistuoja:', 'wc-discount-manager') . 
                 '<strong> ' . implode(', ', $skipped_skus) . '</strong></span>';
        }

        if (!empty($result['errors'])) {
            echo '<span class="alert alert-warning">' . 
                 __('Some products had errors:', 'wc-discount-manager') . 
                 '<br>' . implode('<br>', $result['errors']) . '</span>';
        }

        // Auto-purge Cloudflare cache if available
        self::maybe_purge_cloudflare_cache();
        
        $success_msg = sprintf(
            __('Nuolaidos pritaikytos sėkmingai! Processed %d products.', 'wc-discount-manager'),
            $result['processed']
        );
        
        // Add WPML info if active
        if (class_exists('WCDM_WPML_Helper') && WCDM_WPML_Helper::is_wpml_active()) {
            $success_msg .= ' ' . __('(Applied to all language versions)', 'wc-discount-manager');
        }
        
        echo '<span class="alert alert-success">' . $success_msg . '</span>';
    }

    /**
     * Process category discount
     */
    private static function process_category_discount() {
        $discount = floatval($_POST['discount']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $skip_existing_discounts = isset($_POST['skip_discounted']) && $_POST['skip_discounted'] == 'on';

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids', // Only get IDs for better performance
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => intval($_POST['category']),
                    'include_children' => true,
                ),
            ),
        );
        
        $query = new WP_Query($args);
        $product_ids = $query->posts;
        
        if (empty($product_ids)) {
            echo '<span class="alert alert-warning">' . 
                 __('No products found in selected category.', 'wc-discount-manager') . '</span>';
            return;
        }

        // Remove duplicates and get original language products for WPML
        if (class_exists('WCDM_WPML_Helper') && WCDM_WPML_Helper::is_wpml_active()) {
            $unique_ids = array();
            foreach ($product_ids as $id) {
                $original_id = WCDM_WPML_Helper::get_original_product_id($id);
                $unique_ids[$original_id] = $original_id;
            }
            $product_ids = array_values($unique_ids);
        }

        // Show batch processing info
        if (class_exists('WCDM_Batch_Processor')) {
            echo WCDM_Batch_Processor::get_batch_info_message(count($product_ids));
        }

        // Process using batch processor
        $params = array(
            'discount' => $discount,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'skip_existing_discounts' => $skip_existing_discounts
        );

        $result = WCDM_Batch_Processor::process_batch($product_ids, 'apply_discount', $params);

        if (!empty($result['errors'])) {
            echo '<span class="alert alert-warning">' . 
                 __('Some products had errors:', 'wc-discount-manager') . 
                 '<br>' . implode('<br>', $result['errors']) . '</span>';
        }

        // Auto-purge Cloudflare cache if available
        self::maybe_purge_cloudflare_cache();
        
        $success_msg = sprintf(
            __('Nuolaidos pritaikytos sėkmingai! Processed %d products.', 'wc-discount-manager'),
            $result['processed']
        );
        
        // Add WPML info if active
        if (class_exists('WCDM_WPML_Helper') && WCDM_WPML_Helper::is_wpml_active()) {
            $success_msg .= ' ' . __('(Applied to all language versions)', 'wc-discount-manager');
        }
        
        echo '<span class="alert alert-success">' . $success_msg . '</span>';
    }

    /**
     * Render the form
     */
    private static function render_form() {
        // Show WPML status if active
        if (class_exists('WCDM_WPML_Helper')) {
            $wpml_message = WCDM_WPML_Helper::get_status_message();
            if (!empty($wpml_message)) {
                echo '<div class="notice notice-info"><p><strong>' . $wpml_message . '</strong></p></div>';
            }
        }

        echo '<form class="cform" method="post" enctype="multipart/form-data">';
        wp_nonce_field('wcdm_add_discount', 'wcdm_nonce');
        
        echo '<label for="csv">' . __('CSV Filas:', 'wc-discount-manager') . '</label>';
        echo '<input type="file" name="csv" id="csv-file">';
        
        // Add a dropdown for product categories
        echo '<label for="category">' . __('Kategorija:', 'wc-discount-manager') . '</label>';
        echo '<select name="category" id="category-dropdown">';
        echo '<option value="">' . __('Pasirinkti produkto kategoriją', 'wc-discount-manager') . '</option>';
        
        $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        foreach($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
        
        echo '<label for="discount">' . __('Nuolaidos dydis procentais %', 'wc-discount-manager') . '</label>';
        echo '<input type="number" name="discount" step="0.01" placeholder="1-100%">';
        
        echo '<label for="start_date">' . __('Pradžios data:', 'wc-discount-manager') . '</label>';
        echo '<input type="date" name="start_date" placeholder="Pradžios data:">';
        
        echo '<label for="end_date">' . __('Pabaigos data:', 'wc-discount-manager') . '</label>';
        echo '<input type="date" name="end_date" placeholder="Pabaigos data:">';
        
        // Add a checkbox to skip products with existing discounts
        echo '<label for="skip_discounted"><input type="checkbox" id="skip_discounted" name="skip_discounted"> ' . 
             __('Praleisti produktus su jau taikyta nuolaida (galioja tik pasirinkus kategoriją)', 'wc-discount-manager') . '</label><br>';
        
        echo '<button class="btn btn-primary" type="submit">' . __('Atnaujinti kainas', 'wc-discount-manager') . '</button>';
        echo '</form>';
    }

    /**
     * Maybe purge Cloudflare cache if plugin is available
     * Safely checks for Cloudflare plugin and purge function
     */
    private static function maybe_purge_cloudflare_cache() {
        // Check if function exists (from any Cloudflare plugin)
        if (function_exists('flush_cloudflare_cache')) {
            try {
                flush_cloudflare_cache();
            } catch (Exception $e) {
                // Silently fail - don't break the discount operation
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('WCDM: Cloudflare cache purge failed - ' . $e->getMessage());
                }
            }
        }
        
        // Alternative: WP Cloudflare Super Page Cache plugin
        if (function_exists('wp_cloudflare_purge_cache')) {
            try {
                wp_cloudflare_purge_cache();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('WCDM: WP Cloudflare cache purge failed - ' . $e->getMessage());
                }
            }
        }
        
        // Alternative: Cloudflare plugin by Cloudflare
        if (class_exists('CF\WordPress\Hooks')) {
            try {
                do_action('cloudflare_purge_everything');
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('WCDM: Cloudflare action purge failed - ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Update price by SKU
     */
    private static function update_price_by_sku($sku, $discount, $start_date, $end_date, $skip_existing_discounts) {
        $product_id = wc_get_product_id_by_sku($sku);
        
        if ($product_id) {
            self::apply_discount_to_product($product_id, $discount, $start_date, $end_date, $skip_existing_discounts);
            return true;
        }
        return false;
    }

    /**
     * Apply discount to product
     */
    private static function apply_discount_to_product($product_id, $discount, $start_date, $end_date, $skip_existing_discounts) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        // Check if '_sale_price' is already set and skip if needed
        if ($skip_existing_discounts) {
            $existing_sale_price = $product->get_sale_price();
            if (!empty($existing_sale_price)) {
                return;
            }
        }

        // Set the discount percentage
        update_post_meta($product_id, '_nuolaida_pr', $discount);

        // Catch the pricing data
        $active_price = (float) $product->get_regular_price();

        // Calculating new price
        $new_price = $active_price - $active_price * $discount / 100;

        // If the active price is different from the calculated new price
        if ($new_price !== $active_price) {
            $product->set_sale_price($new_price);
        }

        $product->set_date_on_sale_from($start_date);
        $product->set_date_on_sale_to($end_date);

        $product->save();
    }
}