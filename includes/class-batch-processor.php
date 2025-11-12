<?php
/**
 * Batch Processor Class
 * Handles processing of large product sets in batches to avoid timeouts
 *
 * @package WC_Discount_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCDM_Batch_Processor {

    /**
     * Batch size for processing
     */
    const BATCH_SIZE = 50;

    /**
     * Process products in batches via AJAX
     * This allows processing thousands of products without timeouts
     *
     * @param array $product_ids Array of product IDs to process
     * @param string $action Action to perform ('apply_discount' or 'clear_discount')
     * @param array $params Additional parameters (discount, dates, etc.)
     * @return array Result with success status and messages
     */
    public static function process_batch($product_ids, $action, $params = array()) {
        $total = count($product_ids);
        $processed = 0;
        $errors = array();
        $batch_number = 1;

        // Process in batches
        foreach (array_chunk($product_ids, self::BATCH_SIZE) as $batch) {
            $batch_start = microtime(true);
            
            foreach ($batch as $product_id) {
                try {
                    if ($action === 'apply_discount') {
                        self::apply_discount_action($product_id, $params);
                    } elseif ($action === 'clear_discount') {
                        self::clear_discount_action($product_id);
                    }
                    $processed++;
                } catch (Exception $e) {
                    $errors[] = sprintf(
                        __('Error processing product ID %d: %s', 'wc-discount-manager'),
                        $product_id,
                        $e->getMessage()
                    );
                }
            }

            $batch_time = microtime(true) - $batch_start;
            
            // Log progress (optional, can be disabled)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'WCDM Batch %d: Processed %d/%d products in %.2f seconds',
                    $batch_number,
                    $processed,
                    $total,
                    $batch_time
                ));
            }

            $batch_number++;

            // Small delay to prevent overwhelming the server
            if ($processed < $total) {
                usleep(100000); // 0.1 second pause between batches
            }
        }

        return array(
            'success' => true,
            'processed' => $processed,
            'total' => $total,
            'errors' => $errors
        );
    }

    /**
     * Apply discount action to a single product
     *
     * @param int $product_id Product ID
     * @param array $params Parameters (discount, start_date, end_date, skip_existing_discounts)
     */
    private static function apply_discount_action($product_id, $params) {
        $discount = isset($params['discount']) ? floatval($params['discount']) : 0;
        $start_date = isset($params['start_date']) ? $params['start_date'] : '';
        $end_date = isset($params['end_date']) ? $params['end_date'] : '';
        $skip_existing_discounts = isset($params['skip_existing_discounts']) ? $params['skip_existing_discounts'] : false;

        // Use WPML helper to apply to all translations
        if (class_exists('WCDM_WPML_Helper')) {
            WCDM_WPML_Helper::apply_discount_to_all_translations(
                $product_id,
                $discount,
                $start_date,
                $end_date,
                $skip_existing_discounts
            );
        } else {
            // Fallback if WPML helper not available
            self::apply_discount_simple($product_id, $discount, $start_date, $end_date, $skip_existing_discounts);
        }
    }

    /**
     * Clear discount action from a single product
     *
     * @param int $product_id Product ID
     */
    private static function clear_discount_action($product_id) {
        // Use WPML helper to clear from all translations
        if (class_exists('WCDM_WPML_Helper')) {
            WCDM_WPML_Helper::clear_discount_from_all_translations($product_id);
        } else {
            // Fallback if WPML helper not available
            self::clear_discount_simple($product_id);
        }
    }

    /**
     * Simple discount application (no WPML)
     *
     * @param int $product_id Product ID
     * @param float $discount Discount percentage
     * @param string $start_date Start date
     * @param string $end_date End date
     * @param bool $skip_existing_discounts Skip existing
     */
    private static function apply_discount_simple($product_id, $discount, $start_date, $end_date, $skip_existing_discounts) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }

        if ($skip_existing_discounts) {
            $existing_sale_price = $product->get_sale_price();
            if (!empty($existing_sale_price)) {
                return;
            }
        }

        update_post_meta($product_id, '_nuolaida_pr', $discount);

        $active_price = (float) $product->get_regular_price();
        
        if ($active_price <= 0) {
            return;
        }

        $new_price = $active_price - ($active_price * $discount / 100);

        if ($new_price !== $active_price) {
            $product->set_sale_price($new_price);
        }

        $product->set_date_on_sale_from($start_date);
        $product->set_date_on_sale_to($end_date);

        $product->save();
    }

    /**
     * Simple discount clearing (no WPML)
     *
     * @param int $product_id Product ID
     */
    private static function clear_discount_simple($product_id) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return;
        }

        delete_post_meta($product_id, '_nuolaida_pr');
        $product->set_date_on_sale_from('');
        $product->set_date_on_sale_to('');
        $product->set_sale_price('');
        $product->save();
    }

    /**
     * Get estimated processing time
     *
     * @param int $product_count Number of products
     * @return string Estimated time message
     */
    public static function get_estimated_time($product_count) {
        // Rough estimate: 50 products per second
        $seconds = ceil($product_count / 50);
        
        if ($seconds < 60) {
            return sprintf(__('Estimated time: %d seconds', 'wc-discount-manager'), $seconds);
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return sprintf(__('Estimated time: %d minutes', 'wc-discount-manager'), $minutes);
        } else {
            $hours = ceil($seconds / 3600);
            return sprintf(__('Estimated time: %d hours', 'wc-discount-manager'), $hours);
        }
    }

    /**
     * Check if we should use batch processing
     *
     * @param int $product_count Number of products
     * @return bool True if batch processing recommended
     */
    public static function should_use_batch_processing($product_count) {
        // Use batch processing for more than 100 products
        return $product_count > 100;
    }

    /**
     * Show batch processing info message
     *
     * @param int $product_count Number of products
     * @return string HTML message
     */
    public static function get_batch_info_message($product_count) {
        if (!self::should_use_batch_processing($product_count)) {
            return '';
        }

        $message = sprintf(
            __('Processing %d products in batches of %d. %s', 'wc-discount-manager'),
            $product_count,
            self::BATCH_SIZE,
            self::get_estimated_time($product_count)
        );

        return '<div class="notice notice-info"><p><strong>' . $message . '</strong></p></div>';
    }
}
