<?php
/**
 * WPML Helper Class
 * Handles WPML multilingual product synchronization
 *
 * @package WC_Discount_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCDM_WPML_Helper {

    /**
     * Check if WPML is active
     *
     * @return bool
     */
    public static function is_wpml_active() {
        return class_exists('SitePress') && function_exists('icl_object_id');
    }

    /**
     * Get the original/default language product ID
     *
     * @param int $product_id Product ID
     * @return int Original product ID
     */
    public static function get_original_product_id($product_id) {
        if (!self::is_wpml_active()) {
            return $product_id;
        }

        global $sitepress;
        
        // Get the default language
        $default_language = $sitepress->get_default_language();
        
        // Get the original product ID in default language
        $original_id = apply_filters('wpml_object_id', $product_id, 'product', false, $default_language);
        
        return $original_id ? $original_id : $product_id;
    }

    /**
     * Get all translation IDs for a product (including the original)
     *
     * @param int $product_id Product ID
     * @return array Array of product IDs for all languages
     */
    public static function get_all_translation_ids($product_id) {
        if (!self::is_wpml_active()) {
            return array($product_id);
        }

        global $sitepress, $wpdb;
        
        // Get the original product ID first
        $original_id = self::get_original_product_id($product_id);
        
        // Get the trid (translation group ID)
        $trid = $sitepress->get_element_trid($original_id, 'post_product');
        
        if (!$trid) {
            return array($product_id);
        }

        // Get all translations
        $translations = $sitepress->get_element_translations($trid, 'post_product');
        
        $product_ids = array();
        
        if ($translations) {
            foreach ($translations as $translation) {
                if (isset($translation->element_id)) {
                    $product_ids[] = $translation->element_id;
                }
            }
        }

        // If no translations found, return the original product
        if (empty($product_ids)) {
            $product_ids[] = $original_id;
        }

        return $product_ids;
    }

    /**
     * Get all languages
     *
     * @return array Array of language codes
     */
    public static function get_active_languages() {
        if (!self::is_wpml_active()) {
            return array();
        }

        global $sitepress;
        return $sitepress->get_active_languages();
    }

    /**
     * Apply discount to product and all its translations
     *
     * @param int $product_id Product ID
     * @param float $discount Discount percentage
     * @param string $start_date Start date
     * @param string $end_date End date
     * @param bool $skip_existing_discounts Skip products with existing discounts
     * @return array Array of processed product IDs
     */
    public static function apply_discount_to_all_translations($product_id, $discount, $start_date, $end_date, $skip_existing_discounts) {
        // Get all translation IDs
        $translation_ids = self::get_all_translation_ids($product_id);
        
        $processed_ids = array();
        
        foreach ($translation_ids as $trans_id) {
            // Apply discount to each translation
            self::apply_discount_to_single_product($trans_id, $discount, $start_date, $end_date, $skip_existing_discounts);
            $processed_ids[] = $trans_id;
        }
        
        return $processed_ids;
    }

    /**
     * Apply discount to a single product (used internally)
     *
     * @param int $product_id Product ID
     * @param float $discount Discount percentage
     * @param string $start_date Start date
     * @param string $end_date End date
     * @param bool $skip_existing_discounts Skip if already has discount
     */
    private static function apply_discount_to_single_product($product_id, $discount, $start_date, $end_date, $skip_existing_discounts) {
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

        // Get the regular price
        $active_price = (float) $product->get_regular_price();

        if ($active_price <= 0) {
            return; // Skip if no valid regular price
        }

        // Calculate new price
        $new_price = $active_price - ($active_price * $discount / 100);

        // Update sale price
        if ($new_price !== $active_price) {
            $product->set_sale_price($new_price);
        }

        // Set sale dates
        $product->set_date_on_sale_from($start_date);
        $product->set_date_on_sale_to($end_date);

        // Save the product
        $product->save();
    }

    /**
     * Clear discount from product and all its translations
     *
     * @param int $product_id Product ID
     * @return array Array of cleared product IDs
     */
    public static function clear_discount_from_all_translations($product_id) {
        // Get all translation IDs
        $translation_ids = self::get_all_translation_ids($product_id);
        
        $cleared_ids = array();
        
        foreach ($translation_ids as $trans_id) {
            self::clear_discount_from_single_product($trans_id);
            $cleared_ids[] = $trans_id;
        }
        
        return $cleared_ids;
    }

    /**
     * Clear discount from a single product (used internally)
     *
     * @param int $product_id Product ID
     */
    private static function clear_discount_from_single_product($product_id) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return;
        }

        // Clear discount percentage meta
        delete_post_meta($product_id, '_nuolaida_pr');

        // Clear sale dates
        $product->set_date_on_sale_from('');
        $product->set_date_on_sale_to('');

        // Clear sale price
        $product->set_sale_price('');

        // Save the product
        $product->save();
    }

    /**
     * Get WPML status message for admin
     *
     * @return string Status message
     */
    public static function get_status_message() {
        if (self::is_wpml_active()) {
            $languages = self::get_active_languages();
            $lang_codes = array();
            foreach ($languages as $lang) {
                $lang_codes[] = $lang['code'];
            }
            return sprintf(
                __('WPML is active. Discounts will be applied to all language versions: %s', 'wc-discount-manager'),
                implode(', ', $lang_codes)
            );
        }
        return '';
    }
}
