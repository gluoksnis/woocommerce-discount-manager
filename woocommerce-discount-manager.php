<?php
/**
 * Plugin Name: WooCommerce Discount Manager
 * Plugin URI: https://github.com/gluoksnis/woocommerce-discount-manager
 * Description: Manage WooCommerce product discounts via CSV upload or category selection with bulk operations support
 * Version: 1.2.0
 * Author: Vytautas Gluoksnis, SOUR advertising
 * Author URI: https://sour.lt
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-discount-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WC_DISCOUNT_MANAGER_VERSION', '1.2.0');
define('WC_DISCOUNT_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_DISCOUNT_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_DISCOUNT_MANAGER_PLUGIN_FILE', __FILE__);

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'wcdm_woocommerce_missing_notice');
    return;
}

/**
 * Display admin notice if WooCommerce is not active
 */
function wcdm_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('WooCommerce Discount Manager requires WooCommerce to be installed and active.', 'wc-discount-manager'); ?></p>
    </div>
    <?php
}

/**
 * Main plugin class
 */
class WC_Discount_Manager {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        define('WCDM_CAMPAIGNS_NAME', __('Nuolaidos', 'wc-discount-manager'));
        define('WCDM_DELETE_DISCOUNTS_NAME', __('Ištrinti nuolaidas', 'wc-discount-manager'));
        define('WCDM_PRODUCTS_WITH_DISCOUNT_NAME', __('Prekės su nuolaida', 'wc-discount-manager'));
        define('WCDM_SKU_TAG_ASSIGNMENT', __('Priskirti TAG', 'wc-discount-manager'));
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once WC_DISCOUNT_MANAGER_PLUGIN_DIR . 'includes/class-wpml-helper.php';
        require_once WC_DISCOUNT_MANAGER_PLUGIN_DIR . 'includes/class-batch-processor.php';
        require_once WC_DISCOUNT_MANAGER_PLUGIN_DIR . 'includes/class-discounts-add.php';
        require_once WC_DISCOUNT_MANAGER_PLUGIN_DIR . 'includes/class-discounts-delete.php';
        require_once WC_DISCOUNT_MANAGER_PLUGIN_DIR . 'includes/class-discounts-list-table.php';
        require_once WC_DISCOUNT_MANAGER_PLUGIN_DIR . 'includes/class-sku-tag-assignment.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Declare HPOS (High-Performance Order Storage) compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                WC_DISCOUNT_MANAGER_PLUGIN_FILE,
                true
            );
        }
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Only for administrators
        if (!current_user_can('manage_options')) {
            return;
        }

        add_menu_page(
            WCDM_CAMPAIGNS_NAME,
            WCDM_CAMPAIGNS_NAME,
            'manage_options',
            'wcdm-manage-discounts',
            array('WCDM_Discounts_Add', 'render_page'),
            'dashicons-tag',
            56
        );

        add_submenu_page(
            'wcdm-manage-discounts',
            WCDM_DELETE_DISCOUNTS_NAME,
            WCDM_DELETE_DISCOUNTS_NAME,
            'manage_options',
            'wcdm-delete-discounts',
            array('WCDM_Discounts_Delete', 'render_page')
        );

        add_submenu_page(
            'wcdm-manage-discounts',
            WCDM_PRODUCTS_WITH_DISCOUNT_NAME,
            WCDM_PRODUCTS_WITH_DISCOUNT_NAME,
            'manage_options',
            'wcdm-products-with-discounts',
            array('WCDM_Discounts_List_Table', 'render_page')
        );

        add_submenu_page(
            'wcdm-manage-discounts',
            WCDM_SKU_TAG_ASSIGNMENT,
            WCDM_SKU_TAG_ASSIGNMENT,
            'manage_options',
            'wcdm-sku-tag-assignment',
            array('WCDM_SKU_Tag_Assignment', 'render_page')
        );
    }

    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'wcdm-') === false) {
            return;
        }

        wp_enqueue_style(
            'wcdm-admin-style',
            WC_DISCOUNT_MANAGER_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            WC_DISCOUNT_MANAGER_VERSION
        );

        wp_enqueue_script(
            'wcdm-admin-script',
            WC_DISCOUNT_MANAGER_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            WC_DISCOUNT_MANAGER_VERSION,
            true
        );
    }
}

/**
 * Initialize the plugin
 */
function wcdm_init() {
    return WC_Discount_Manager::instance();
}

// Start the plugin
add_action('plugins_loaded', 'wcdm_init');
