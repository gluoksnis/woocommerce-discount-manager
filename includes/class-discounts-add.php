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
        $purge_cache = isset($_POST['purge_cloudflare']) && $_POST['purge_cloudflare'] == 'on';
        $skipped_skus = array();

        foreach ($csv as $row) {
            $discountApplied = self::update_price_by_sku(trim($row[0]), $discount, $start_date, $end_date, $skip_existing_discounts);

            if (!$discountApplied) {
                $skipped_skus[] = trim($row[0]);
            }
        }

        if (!empty($skipped_skus)) {
            echo '<span class="alert alert-warning">' . 
                 __('Produktai su šiais SKU kodais neegzistuoja:', 'wc-discount-manager') . 
                 '<strong> ' . implode(', ', $skipped_skus) . '</strong></span>';
        }

        if ($purge_cache && function_exists('flush_cloudflare_cache')) {
            flush_cloudflare_cache();
        }
        
        echo '<span class="alert alert-success">' . __('Nuolaidos pritaikytos sėkmingai!', 'wc-discount-manager') . '</span>';
    }

    /**
     * Process category discount
     */
    private static function process_category_discount() {
        $discount = floatval($_POST['discount']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $skip_existing_discounts = isset($_POST['skip_discounted']) && $_POST['skip_discounted'] == 'on';
        $purge_cache = isset($_POST['purge_cloudflare']) && $_POST['purge_cloudflare'] == 'on';

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => intval($_POST['category']),
                    'include_children' => true,
                ),
            ),
        );
        
        $loop = new WP_Query($args);
        while ($loop->have_posts()) : $loop->the_post();
            global $product;
            $product_id = $product->get_id();
            if ($product_id) {
                self::apply_discount_to_product($product_id, $discount, $start_date, $end_date, $skip_existing_discounts);
            }
        endwhile;
        wp_reset_query();

        if ($purge_cache && function_exists('flush_cloudflare_cache')) {
            flush_cloudflare_cache();
        }
        
        echo '<span class="alert alert-success">' . __('Nuolaidos pritaikytos sėkmingai!', 'wc-discount-manager') . '</span>';
    }

    /**
     * Render the form
     */
    private static function render_form() {
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
        
        // Add a checkbox to purge Cloudflare cache
        echo '<label for="purge_cloudflare"><input type="checkbox" id="purge_cloudflare" name="purge_cloudflare"> ' . 
             __('Išvalyti Cloudflare cache', 'wc-discount-manager') . '</label><br>';
        
        echo '<button class="btn btn-primary" type="submit">' . __('Atnaujinti kainas', 'wc-discount-manager') . '</button>';
        echo '</form>';
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
