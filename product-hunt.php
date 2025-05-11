<?php
/**
 * Plugin Name: Product Hunt - Quiz-Based Product Recommendations
 * Description: A WooCommerce plugin that allows users to take quizzes and receive personalized product recommendations
 * Version: 1.0.0
 * Author: Product Hunt Team
 * Text Domain: product-hunt
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 9.8.4
 */

// Ensure WordPress environment is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Set higher memory limit for the plugin
@ini_set('memory_limit', '256M');

// Define plugin constants
define('PRODUCT_HUNT_VERSION', '1.0.0');
define('PRODUCT_HUNT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRODUCT_HUNT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Enable better error handling
if (WP_DEBUG) {
    @ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Declare WooCommerce HPOS & Blocks compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Declare WooCommerce support
add_action('after_setup_theme', function() {
    add_theme_support('woocommerce');
});

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . __('Product Hunt requires WooCommerce to be installed and active.', 'product-hunt') . '</p></div>';
    });
    return;
}

/**
 * The code that runs during plugin activation.
 */
function activate_product_hunt() {
    require_once PRODUCT_HUNT_PLUGIN_DIR . 'includes/class-product-hunt-activator.php';
    Product_Hunt_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_product_hunt() {
    require_once PRODUCT_HUNT_PLUGIN_DIR . 'includes/class-product-hunt-deactivator.php';
    Product_Hunt_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_product_hunt');
register_deactivation_hook(__FILE__, 'deactivate_product_hunt');

/**
 * Register the JavaScript for the admin area.
 */
function enqueue_admin_scripts($hook) {
    // Add analytics-specific scripts only on the analytics page
    if ($hook === 'product-quizzes_page_product-hunt-analytics') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-datepicker-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }
}

/**
 * Enqueue scripts and styles
 */
function product_hunt_enqueue_scripts() {
    // Localize the script with the AJAX URL
    wp_localize_script('product-hunt-quiz', 'product_hunt_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('product_hunt_quiz_nonce'),
        'is_user_logged_in' => is_user_logged_in()
    ));
}

/**
 * Debug function to log AJAX issues
 */
function product_hunt_debug_info() {
    if (WP_DEBUG && current_user_can('manage_options')) {
        echo '<!-- Product Hunt Debug Info: 
            AJAX URL: ' . admin_url('admin-ajax.php') . '
            Site URL: ' . site_url() . '
            Home URL: ' . home_url() . '
        -->';
    }
}
add_action('wp_footer', 'product_hunt_debug_info');

// Load debugging tools in development environments
if (WP_DEBUG === true) {
    require_once plugin_dir_path(__FILE__) . 'includes/quiz-edit-debug.php';
    require_once plugin_dir_path(__FILE__) . 'includes/templates/ajax-test.php';
    require_once plugin_dir_path(__FILE__) . 'includes/debug-utils.php';
}

// Include ajax handlers
require_once plugin_dir_path(__FILE__) . 'includes/ajax/product-search.php';

// Include database repair and debug tools
require_once plugin_dir_path(__FILE__) . 'includes/database-repair.php';
require_once plugin_dir_path(__FILE__) . 'includes/product-debug.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-product-hunt.php';

/**
 * Begins execution of the plugin.
 */
function run_product_hunt() {
    $plugin = new Product_Hunt();
    $plugin->run();
}
run_product_hunt();