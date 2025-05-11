<?php
/**
 * Enqueue admin styles for product search
 */
function ph_enqueue_product_search_styles() {
    // Only enqueue on our plugin pages
    $screen = get_current_screen();
    if (!$screen || !isset($_GET['page']) || $_GET['page'] !== 'product-hunt-quiz') {
        return;
    }
    
    wp_enqueue_style(
        'product-hunt-product-search',
        plugins_url('/assets/css/admin-product-search.css', dirname(__FILE__)),
        array(),
        time() // Use time() to avoid caching during development
    );
}
add_action('admin_enqueue_scripts', 'ph_enqueue_product_search_styles');
?>