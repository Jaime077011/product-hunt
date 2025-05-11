<?php
/**
 * Template for testing AJAX functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Output a test tool for product search AJAX
 */
function ph_output_ajax_test_tool() {
    // Only add the test tool if we're in debug mode
    if (!WP_DEBUG) {
        return;
    }
    
    // Only show on our plugin page
    if (!isset($_GET['page']) || $_GET['page'] !== 'product-hunt-quiz') {
        return;
    }
    
    ?>
    <div class="ajax-test-tool" style="margin: 20px 0; padding: 15px; background: #f1f1f1; border: 1px solid #ccc;">
        <h3>Product Search AJAX Test Tool</h3>
        <p>Use this tool to test if product search AJAX is working properly.</p>
        
        <div class="test-search">
            <label for="test-product-search">Search for a product:</label>
            <input type="text" id="test-product-search" style="width: 300px;" placeholder="Start typing a product name...">
        </div>
        
        <div class="test-results" style="margin-top: 15px; padding: 10px; background: #fff; border: 1px solid #ddd; display: none;">
            <h4>AJAX Response</h4>
            <pre id="test-results-data" style="max-height: 200px; overflow: auto;"></pre>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-product-search').on('keyup', function() {
                var term = $(this).val();
                
                if (term.length < 2) {
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'ph_product_search',
                        term: term,
                        nonce: '<?php echo wp_create_nonce('product_search_nonce'); ?>'
                    },
                    beforeSend: function() {
                        $('#test-product-search').addClass('loading');
                    },
                    success: function(response) {
                        $('#test-product-search').removeClass('loading');
                        $('#test-results-data').html(JSON.stringify(response, null, 2));
                        $('.test-results').show();
                    },
                    error: function(xhr, status, error) {
                        $('#test-product-search').removeClass('loading');
                        $('#test-results-data').html('Error: ' + status + ' - ' + error);
                        $('.test-results').show();
                    }
                });
            });
        });
        </script>
    </div>
    <?php
}

// Only add in admin
if (is_admin()) {
    add_action('admin_footer', 'ph_output_ajax_test_tool');
}
