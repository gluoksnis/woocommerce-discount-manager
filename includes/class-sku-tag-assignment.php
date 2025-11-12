<?php
/**
 * SKU Tag Assignment Class
 *
 * @package WC_Discount_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCDM_SKU_Tag_Assignment {

    /**
     * Render the page
     */
    public static function render_page() {
        echo '<div class="wrap">';
        echo '<h1>' . WCDM_SKU_TAG_ASSIGNMENT . '</h1>';
        echo '<div id="poststuff">';
        echo '<div id="post-body" class="metabox-holder columns-2">';
        echo '<div id="post-body-content">';
        echo '<div class="meta-box-sortables ui-sortable">';

        // Accordion section for the upload form
        echo '<div class="postbox">';
        echo '<h2 class="hndle">' . __('Įkelti CSV ir priskirti žymas pagal SKU arba pasirinktą kategoriją', 'wc-discount-manager') . '</h2>';
        echo '<div class="inside">';

        // Handle the file upload
        if (isset($_FILES['csv']) && ($_FILES['csv']['error'] == UPLOAD_ERR_OK)) {
            self::process_csv_tag_assignment();
        } elseif (!empty($_POST['category'])) {
            self::process_category_tag_assignment();
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
     * Process CSV tag assignment
     */
    private static function process_csv_tag_assignment() {
        $csv = array_map('str_getcsv', file($_FILES['csv']['tmp_name']));
        $selected_tag = sanitize_text_field($_POST['selected_tag']);
        
        echo '<p>' . __('Selected Tag:', 'wc-discount-manager') . ' ' . esc_html($selected_tag) . '</p>';
        
        foreach ($csv as $row) {
            $product_id = wc_get_product_id_by_sku(trim($row[0]));

            if ($product_id) {
                // Assign selected product tag to the product
                wp_set_object_terms($product_id, array($selected_tag), 'product_tag', true);
            }
        }

        echo '<span class="alert alert-success">' . __('Product tags assigned successfully!', 'wc-discount-manager') . '</span>';
    }

    /**
     * Process category tag assignment
     */
    private static function process_category_tag_assignment() {
        $selected_tag = sanitize_text_field($_POST['selected_tag']);
        
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
                // Assign selected product tag to the product
                wp_set_object_terms($product_id, array($selected_tag), 'product_tag', true);
            }
        endwhile;
        wp_reset_query();
        
        echo '<span class="alert alert-success">' . __('Product tags assigned successfully!', 'wc-discount-manager') . '</span>';
    }

    /**
     * Render the form
     */
    private static function render_form() {
        echo '<form class="cform" method="post" enctype="multipart/form-data">';
        wp_nonce_field('wcdm_tag_assignment', 'wcdm_nonce');
        
        echo '<label for="csv">' . __('CSV Filas (SKU only):', 'wc-discount-manager') . '</label>';
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
        
        // Add a dropdown for product tags
        echo '<label for="selected_tag">' . __('Pasirinkite tag kurį norite priskirti:', 'wc-discount-manager') . '</label>';
        echo '<select name="selected_tag" id="selected-tag">';
        echo '<option value="">' . __('Pasirinkite tag kurį norite priskirti', 'wc-discount-manager') . '</option>';
        
        $terms = get_terms(array('taxonomy' => 'product_tag', 'orderby' => 'name', 'hide_empty' => false));
        foreach($terms as $tag) {
            echo '<option value="' . esc_attr($tag->name) . '">' . esc_html($tag->name) . '</option>';
        }
        echo '</select>';

        // Add a button to assign product tags
        echo '<button class="btn btn-primary" type="submit">' . __('Assign Product Tags', 'wc-discount-manager') . '</button>';
        echo '</form>';
    }
}
