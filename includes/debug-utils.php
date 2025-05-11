<?php
/**
 * Debugging utilities for Product Hunt plugin
 */

// Don't allow direct access
if (!defined('ABSPATH')) exit;

/**
 * Log AJAX errors to a custom file for easier debugging
 */
function product_hunt_log_error($message, $data = null) {
    if (WP_DEBUG) {
        $log_file = PRODUCT_HUNT_PLUGIN_DIR . 'logs/debug.log';
        $log_dir = dirname($log_file);
        
        // Create logs directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Format the log entry
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            $log_entry .= " | Data: " . print_r($data, true);
        }
        
        // Append to log file
        error_log($log_entry . "\n", 3, $log_file);
    }
}

/**
 * Output AJAX diagnostic information
 */
function product_hunt_ajax_diagnostics() {
    // Only run for logged-in users with admin privileges
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return;
    }
    
    // Check if we should run diagnostics
    if (!isset($_GET['ph_diagnostics'])) {
        return;
    }
    
    echo '<div class="product-hunt-diagnostics" style="background: #f8f9fa; border: 1px solid #ddd; padding: 15px; margin: 15px;">';
    echo '<h3>Product Hunt Plugin Diagnostics</h3>';
    
    echo '<h4>Server Information</h4>';
    echo '<ul>';
    echo '<li>PHP Version: ' . phpversion() . '</li>';
    echo '<li>WordPress Version: ' . get_bloginfo('version') . '</li>';
    echo '<li>Memory Limit: ' . ini_get('memory_limit') . '</li>';
    echo '<li>Max Execution Time: ' . ini_get('max_execution_time') . '</li>';
    echo '</ul>';
    
    echo '<h4>AJAX Configuration</h4>';
    echo '<ul>';
    echo '<li>AJAX URL: ' . admin_url('admin-ajax.php') . '</li>';
    echo '<li>Site URL: ' . site_url() . '</li>';
    echo '<li>Home URL: ' . home_url() . '</li>';
    echo '</ul>';
    
    echo '<h4>Test AJAX Connection</h4>';
    echo '<button id="test-ajax-connection">Test AJAX</button>';
    echo '<div id="ajax-test-result"></div>';
    
    echo '<script>
    document.getElementById("test-ajax-connection").addEventListener("click", function() {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "' . admin_url('admin-ajax.php') . '");
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            document.getElementById("ajax-test-result").innerHTML = 
                "<p>Status: " + this.status + "</p>" +
                "<p>Response: " + this.responseText + "</p>";
        };
        xhr.onerror = function() {
            document.getElementById("ajax-test-result").innerHTML = 
                "<p>Error: Connection failed</p>";
        };
        xhr.send("action=product_hunt_test_ajax");
    });
    </script>';
    
    echo '</div>';
}
add_action('wp_footer', 'product_hunt_ajax_diagnostics');

/**
 * Test AJAX endpoint
 */
function product_hunt_test_ajax() {
    wp_send_json_success(array(
        'message' => 'AJAX is working correctly',
        'time' => current_time('mysql')
    ));
}
add_action('wp_ajax_product_hunt_test_ajax', 'product_hunt_test_ajax');
add_action('wp_ajax_nopriv_product_hunt_test_ajax', 'product_hunt_test_ajax');
