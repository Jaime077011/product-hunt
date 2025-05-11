<?php
/**
 * AJAX handlers for product search 
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle product search AJAX requests
 */
function ph_handle_product_search() {
    // Check nonce for security
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'product_search_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }

    // Get search term
    $term = isset($_REQUEST['term']) ? sanitize_text_field($_REQUEST['term']) : '';
    
    if (empty($term)) {
        wp_send_json_error('No search term provided');
        return;
    }

    // Debug log
    if (WP_DEBUG) {
        $log_file = plugin_dir_path(dirname(dirname(__FILE__))) . 'debug-product-search.log';
        $log_data = "=== Product Search: " . date('Y-m-d H:i:s') . " ===\n";
        $log_data .= "Search term: $term\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce is not active');
        return;
    }

    // Search products
    $products = ph_search_products($term);
    
    // Debug log results
    if (WP_DEBUG) {
        $log_data = "Found " . count($products) . " products\n\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);
    }

    wp_send_json_success($products);
}
add_action('wp_ajax_ph_product_search', 'ph_handle_product_search');

/**
 * Search for products by term
 * 
 * @param string $term Search term
 * @return array Array of matching products
 */
function ph_search_products($term) {
    $products_array = array();
    
    // Search args
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 15,
        's'              => $term,
    );
    
    // Also search by SKU
    if (is_numeric($term) || preg_match('/^[a-zA-Z0-9-]+$/', $term)) {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sku',
                'value'   => $term,
                'compare' => 'LIKE'
            )
        );
    }
    
    $products = new WP_Query($args);
    
    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            
            if ($product) {
                $products_array[] = array(
                    'id'    => $product_id,
                    'label' => $product->get_name() . ' (#' . $product_id . ' - ' . $product->get_price_html() . ')',
                    'value' => $product->get_name(),
                    'price' => $product->get_price(),
                    'image' => wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'thumbnail')[0] ?? '',
                );
            }
        }
    }
    
    wp_reset_postdata();
    
    return $products_array;
}
