<?php
/**
 * Enqueue required scripts for product search
 */
function ph_enqueue_product_search_scripts() {
    // Check if we're on the quiz edit page
    $screen = get_current_screen();
    if (!$screen || !isset($_GET['page']) || ($_GET['page'] !== 'product-hunt-quiz' && $_GET['page'] !== 'product-hunt-add-quiz')) {
        return;
    }

    // Enqueue jQuery UI and our product search script
    wp_enqueue_script('jquery-ui-autocomplete');
    
    wp_enqueue_script(
        'product-hunt-product-search',
        plugins_url('/assets/js/product-search.js', dirname(dirname(__FILE__))),
        array('jquery', 'jquery-ui-autocomplete'),
        filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/js/product-search.js'),
        true
    );

    // Add the ajax URL as a JavaScript variable
    wp_localize_script(
        'product-hunt-product-search',
        'phProductSearch',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('product_search_nonce'),
            'searching' => __('Searching products...', 'product-hunt'),
            'noResults' => __('No products found', 'product-hunt'),
            'debug' => WP_DEBUG
        )
    );
    
    // Ensure jQuery UI CSS is loaded
    wp_enqueue_style(
        'jquery-ui-style',
        '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css'
    );
}
add_action('admin_enqueue_scripts', 'ph_enqueue_product_search_scripts');

/**
 * Ajax handler for product search
 */
function ph_ajax_product_search() {
    // Check nonce for security
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'product_search_nonce')) {
        wp_send_json_error('Invalid security token');
        die();
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
        die();
    }

    // Get search term
    $term = isset($_REQUEST['term']) ? sanitize_text_field($_REQUEST['term']) : '';
    
    if (empty($term)) {
        wp_send_json_error('No search term provided');
        die();
    }

    // Debug log
    if (WP_DEBUG) {
        $debug_log = plugin_dir_path(dirname(dirname(__FILE__))) . 'debug-product-search.log';
        $log_data = "=== Product Search: " . date('Y-m-d H:i:s') . " ===\n";
        $log_data .= "Search term: $term\n";
        file_put_contents($debug_log, $log_data, FILE_APPEND);
    }

    // Search products
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 15,
        's' => $term,
    );

    // Also search by SKU
    $args['meta_query'] = array(
        'relation' => 'OR',
        array(
            'key' => '_sku',
            'value' => $term,
            'compare' => 'LIKE'
        )
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            
            if ($product) {
                // Get product image
                $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
                if (!$image_url) {
                    $image_url = wc_placeholder_img_src('thumbnail');
                }
                
                $products[] = array(
                    'id' => $product_id,
                    'label' => $product->get_name() . ' (#' . $product_id . ' - ' . $product->get_price_html() . ')',
                    'value' => $product->get_name(),
                    'price' => $product->get_price(),
                    'image' => $image_url
                );
            }
        }
        wp_reset_postdata();
    }

    // Log results count
    if (WP_DEBUG) {
        $log_data = "Found " . count($products) . " products\n";
        if (count($products) > 0) {
            $log_data .= "First result: " . $products[0]['label'] . "\n";
        }
        file_put_contents($debug_log, $log_data . "\n\n", FILE_APPEND);
    }

    wp_send_json_success($products);
    die();
}

// This is critical - make sure we're hooking the AJAX handler correctly
remove_all_actions('wp_ajax_ph_product_search'); // Remove any previous handlers
add_action('wp_ajax_ph_product_search', 'ph_ajax_product_search');
?>