<?php
/**
 * Discounts List Table Class
 *
 * @package WC_Discount_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WCDM_Discounted_Products_Table extends WP_List_Table {

    function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'title'         => __('Product Name', 'wc-discount-manager'),
            'sku'           => __('SKU', 'wc-discount-manager'),
            'category'      => __('Category', 'wc-discount-manager'),
            'discount'      => __('Discount', 'wc-discount-manager'),
            'stock_status'  => __('Stock status', 'wc-discount-manager'),
            'schedule'      => __('Discount Schedule', 'wc-discount-manager'),
        );
        return $columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'bulk_delete_discounts' => __('Remove Discounts', 'wc-discount-manager')
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="product[]" value="%s" />', $item['ID']
        );
    }

    function prepare_items() {
        $this->_column_headers = array($this->get_columns(), array(), array());
        $current_page = isset($_REQUEST['paged']) ? max(1, intval($_REQUEST['paged'])) : 1;
        
        // Base args
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 20,
            'paged' => $current_page,
            'meta_query' => array(
                array(
                    'key'     => '_sale_price',
                    'value'   => '',
                    'compare' => '!=',
                ),
            ),            
        );

        // Add category filter if selected
        if (!empty($_GET['filter_category']) && $_GET['filter_category'] != '0') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => intval($_GET['filter_category']),
                    'include_children' => true,
                ),
            );
        }

        $query = new WP_Query($args);
        $products = $query->get_posts();
        $product_list = array();
        
        foreach ($products as $product) {
            $categories = get_the_terms($product->ID, 'product_cat');
            $category_names = array();
            if ($categories) {
                foreach ($categories as $category) {
                    $category_names[] = $category->name;
                }
            }

            $regular_price = get_post_meta($product->ID, '_regular_price', true);
            $sale_price = get_post_meta($product->ID, '_sale_price', true);
            $discount = get_post_meta($product->ID, '_nuolaida_pr', true);
            $stock_statuses = wc_get_product_stock_status_options();
            $stock_status = get_post_meta($product->ID, '_stock_status', true);
            $stock_status_name = isset($stock_statuses[$stock_status]) ? $stock_statuses[$stock_status] : '';

            // Get discount schedule
            $_product = wc_get_product($product->ID);
            $date_from = $_product->get_date_on_sale_from();
            $date_to = $_product->get_date_on_sale_to();
            
            $schedule = '';
            if ($date_from || $date_to) {
                $from_date = $date_from ? $date_from->date('Y-m-d') : '—';
                $to_date = $date_to ? $date_to->date('Y-m-d') : '—';
                $schedule = $from_date . ' to ' . $to_date;
            } else {
                $schedule = '—';
            }

            // Add regular and sale prices to the 'discount' field
            $discount_display = '<strong>'.$discount.'%</strong>&nbsp;-&nbsp;<del>' . wc_price($regular_price) . '</del> ' . wc_price($sale_price);

            $product_list[] = array(
                'ID'            => $product->ID,
                'title'         => $product->post_title,
                'sku'           => get_post_meta($product->ID, '_sku', true),
                'stock_status'  => $stock_status_name,
                'category'      => implode(', ', $category_names),
                'discount'      => $discount_display,
                'schedule'      => $schedule,
            );
        }
        $this->items = $product_list;
    
        // Set the pagination
        $this->set_pagination_args(array(
            'total_items' => $query->found_posts,
            'per_page'    => 20,
            'total_pages' => ceil($query->found_posts/20)
        ));
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'title':
                return '<a href="' . get_edit_post_link($item['ID']) . '">' . esc_html($item[$column_name]) . '</a>';
            case 'sku':
            case 'stock_status':
            case 'category':
            case 'discount':
            case 'schedule':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    function extra_tablenav($which) {
        if ($which == "top") {
            echo '<div class="alignleft actions">';
            echo '<select name="filter_category" id="filter_category">';
            echo '<option value="0">' . __('All Categories', 'wc-discount-manager') . '</option>';
            
            $categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
            ));
            
            $selected_category = isset($_GET['filter_category']) ? $_GET['filter_category'] : '0';
            
            foreach($categories as $category) {
                $selected = ($selected_category == $category->term_id) ? ' selected="selected"' : '';
                echo '<option value="' . esc_attr($category->term_id) . '"' . $selected . '>' . esc_html($category->name) . '</option>';
            }
            
            echo '</select>';
            submit_button(__('Filter', 'wc-discount-manager'), 'button', 'filter_action', false);
            echo '</div>';
        }
    }
}

class WCDM_Discounts_List_Table {

    /**
     * Render the page
     */
    public static function render_page() {
        $productsTable = new WCDM_Discounted_Products_Table();

        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] == 'bulk_delete_discounts') {
            if (!empty($_POST['product'])) {
                foreach ($_POST['product'] as $product_id) {
                    self::clear_discount_fields(intval($product_id));
                }
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     __('Discounts removed from selected products!', 'wc-discount-manager') . '</p></div>';
            }
        }

        if (isset($_POST['action2']) && $_POST['action2'] == 'bulk_delete_discounts') {
            if (!empty($_POST['product'])) {
                foreach ($_POST['product'] as $product_id) {
                    self::clear_discount_fields(intval($product_id));
                }
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     __('Discounts removed from selected products!', 'wc-discount-manager') . '</p></div>';
            }
        }

        echo '<div class="wrap"><h2>' . WCDM_PRODUCTS_WITH_DISCOUNT_NAME . '</h2>';
        echo '<form method="post">';
        $productsTable->prepare_items();
        $productsTable->display();
        echo '</form>';
        echo '</div>';
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
