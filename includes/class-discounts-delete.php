<?php
/**
 * Discounts Delete Class
 *
 * @package WC_Discount_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCDM_Discounts_Delete {

    /**
     * Render the page
     */
    public static function render_page() {
        echo '<div class="wrap">';
        echo '<h1>' . WCDM_DELETE_DISCOUNTS_NAME . '</h1>';
        echo '<div id="poststuff">';
        echo '<div id="post-body" class="metabox-holder columns-2">';
        echo '<div id="post-body-content">';
        echo '<div class="meta-box-sortables ui-sortable">';
        echo '<div class="postbox">';
        echo '<h2 class="hndle">' . __('Ištrinti nuolaidas', 'wc-discount-manager') . '</h2>';
        echo '<div class="inside">';

        // Execute the script to delete the discounts from selected option
        if (isset($_FILES['csv_clear']) && $_FILES['csv_clear']['error'] == UPLOAD_ERR_OK) {
            self::process_csv_clear();
        } else if (isset($_POST['clear_all'])) {
            self::process_clear_all();
        } else if (isset($_POST['clear_category']) && $_POST['clear_category'] != "0") {
            self::process_clear_category();
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
     * Process CSV clear
     */
    private static function process_csv_clear() {
        $csv = array_map('str_getcsv', file($_FILES['csv_clear']['tmp_name']));

        // Collect product IDs
        $product_ids = array();
        
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
            }
        }

        if (empty($product_ids)) {
            echo '<span class="alert alert-warning">' . 
                 __('No valid products found in CSV.', 'wc-discount-manager') . '</span>';
            return;
        }

        // Show batch processing info
        if (class_exists('WCDM_Batch_Processor')) {
            echo WCDM_Batch_Processor::get_batch_info_message(count($product_ids));
        }

        // Process using batch processor
        $result = WCDM_Batch_Processor::process_batch($product_ids, 'clear_discount');

        // Auto-purge Cloudflare cache if available
        self::maybe_purge_cloudflare_cache();
        
        $success_msg = sprintf(
            __('Discounts cleared for %d products!', 'wc-discount-manager'),
            $result['processed']
        );
        
        // Add WPML info if active
        if (class_exists('WCDM_WPML_Helper') && WCDM_WPML_Helper::is_wpml_active()) {
            $success_msg .= ' ' . __('(Cleared from all language versions)', 'wc-discount-manager');
        }
        
        echo '<span class="alert alert-success">' . $success_msg . '</span>';
    }

    /**
     * Process clear all - Using WooCommerce API instead of direct SQL
     * This ensures hooks fire for Typesense and other plugins
     */
    private static function process_clear_all() {
        global $wpdb;
        
        // Get all product IDs that have sale prices
        $product_ids = $wpdb->get_col("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_sale_price' 
            AND meta_value != ''
        ");

        if (empty($product_ids)) {
            echo '<span class="alert alert-warning">' . 
                 __('No products with discounts found.', 'wc-discount-manager') . '</span>';
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

        // Process using batch processor - this uses WooCommerce API
        $result = WCDM_Batch_Processor::process_batch($product_ids, 'clear_discount');

        // Auto-purge Cloudflare cache if available
        self::maybe_purge_cloudflare_cache();
        
        $success_msg = sprintf(
            __('All discounts cleared! Processed %d products and original prices restored.', 'wc-discount-manager'),
            $result['processed']
        );
        
        // Add WPML info if active
        if (class_exists('WCDM_WPML_Helper') && WCDM_WPML_Helper::is_wpml_active()) {
            $success_msg .= ' ' . __('(Cleared from all language versions)', 'wc-discount-manager');
        }
        
        echo '<span class="alert alert-success">' . $success_msg . '</span>';
    }

    /**
     * Process clear category
     */
    private static function process_clear_category() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => intval($_POST['clear_category']),
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
        $result = WCDM_Batch_Processor::process_batch($product_ids, 'clear_discount');

        // Auto-purge Cloudflare cache if available
        self::maybe_purge_cloudflare_cache();
        
        $success_msg = sprintf(
            __('Discounts cleared for %d products in selected category!', 'wc-discount-manager'),
            $result['processed']
        );
        
        // Add WPML info if active
        if (class_exists('WCDM_WPML_Helper') && WCDM_WPML_Helper::is_wpml_active()) {
            $success_msg .= ' ' . __('(Cleared from all language versions)', 'wc-discount-manager');
        }
        
        echo '<span class="alert alert-success">' . $success_msg . '</span>';
    }

    /**
     * Render the form
     */
    private static function render_form() {
        echo '<form class="cform" method="post" enctype="multipart/form-data" onsubmit="return confirm(\'' . 
             esc_js(__('Ar tikrai norite išvalyti visų produktų nuolaidų laukelius?', 'wc-discount-manager')) . '\');">';
        wp_nonce_field('wcdm_delete_discount', 'wcdm_nonce');
        
        echo '<label for="csv_clear">' . __('Įkelkite CSV failą su produktais:', 'wc-discount-manager') . '</label>';
        echo '<input type="file" id="csv_clear" name="csv_clear"><br>';
        
        echo '<label for="category">' . __('arba pasirinkite kategoriją:', 'wc-discount-manager') . '</label>';
        echo '<select name="clear_category" id="clear_category">';
        echo '<option value="">' . __('Pasirinkti produkto kategoriją', 'wc-discount-manager') . '</option>';
        
        $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        foreach($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
        
        echo '<label for="clear_all"><input type="checkbox" id="clear_all" name="clear_all"> ' . 
             __('Ištrinti nuolaidas iš visų produktų', 'wc-discount-manager') . '</label><br>';
        
        echo '<button class="btn btn-danger" type="submit">' . __('Atnaujinti kainas', 'wc-discount-manager') . '</button>';
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
     * Clear discount fields from product
     */
    private static function clear_discount_fields($product_id) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return;
        }

        // Clear "_nuolaida_pr" meta_value
        delete_post_meta($product_id, '_nuolaida_pr');

        // Clear product sale dates
        $product->set_date_on_sale_from('');
        $product->set_date_on_sale_to('');

        // Clear product sale price
        $product->set_sale_price('');

        // Save the product
        $product->save();
    }
}