<?php
/**
 * Product Assignment Debug Tool for Product Hunt Plugin
 *
 * This file provides debugging functionality for product assignment issues
 * in the Product Hunt plugin.
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add the Product Debug submenu page
 */
function ph_add_product_debug_page() {
    add_submenu_page(
        'edit.php?post_type=product-quizzes',
        'Product Debug',
        'Product Debug',
        'manage_options',
        'ph-product-debug',
        'ph_product_debug_page'
    );
}
add_action('admin_menu', 'ph_add_product_debug_page');

/**
 * Render the Product Debug admin page
 */
function ph_product_debug_page() {
    // Check if a quiz ID is provided
    $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
    
    // Get available quizzes for dropdown
    global $wpdb;
    $quizzes = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}ph_quizzes ORDER BY id DESC");
    
    // Handle product search test
    $product_search_results = array();
    if (isset($_POST['test_product_search']) && isset($_POST['search_term'])) {
        $search_term = sanitize_text_field($_POST['search_term']);
        $product_search_results = ph_test_product_search($search_term);
    }
    
    // Handle product assignment test
    $assignment_results = null;
    if (isset($_POST['test_product_assignment']) && isset($_POST['answer_id']) && isset($_POST['product_id'])) {
        $answer_id = intval($_POST['answer_id']);
        $product_id = intval($_POST['product_id']);
        $assignment_results = ph_test_product_assignment($answer_id, $product_id);
    }
    
    ?>
    <div class="wrap">
        <h1>Product Hunt - Product Assignment Debug Tool</h1>
        
        <h2>Select Quiz to Debug</h2>
        <form method="get">
            <input type="hidden" name="post_type" value="product-quizzes">
            <input type="hidden" name="page" value="ph-product-debug">
            
            <select name="quiz_id">
                <option value="0">-- Select a Quiz --</option>
                <?php foreach ($quizzes as $quiz) : ?>
                    <option value="<?php echo esc_attr($quiz->id); ?>" <?php selected($quiz_id, $quiz->id); ?>>
                        <?php echo esc_html("#" . $quiz->id . " - " . $quiz->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="submit" class="button" value="Load Quiz Data">
        </form>
        
        <?php if ($quiz_id) : ?>
            <?php
            // Get quiz data
            $quiz = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
                $quiz_id
            ));
            
            if (!$quiz) {
                echo '<div class="error"><p>Quiz not found!</p></div>';
                return;
            }
            
            // Get questions
            $questions = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d ORDER BY question_order",
                $quiz_id
            ));
            ?>
            
            <h2>Quiz Details</h2>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>ID</th>
                    <td><?php echo esc_html($quiz->id); ?></td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td><?php echo esc_html($quiz->title); ?></td>
                </tr>
                <tr>
                    <th>Questions</th>
                    <td><?php echo count($questions); ?></td>
                </tr>
            </table>
            
            <h2>Questions and Answers</h2>
            <?php foreach ($questions as $question) : ?>
                <div class="ph-debug-question">
                    <h3>Question #<?php echo esc_html($question->id); ?> - <?php echo esc_html($question->question_text); ?></h3>
                    
                    <?php
                    // Get answers for this question
                    $answers = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ph_answers WHERE question_id = %d ORDER BY answer_order",
                        $question->id
                    ));
                    ?>
                    
                    <?php if (empty($answers)) : ?>
                        <p>No answers found for this question.</p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Answer ID</th>
                                    <th>Text</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($answers as $answer) : ?>
                                    <tr>
                                        <td><?php echo esc_html($answer->id); ?></td>
                                        <td><?php echo esc_html($answer->answer_text); ?></td>
                                        <td>
                                            <?php
                                            // Get product recommendations for this answer
                                            $products = $wpdb->get_results($wpdb->prepare(
                                                "SELECT pr.*, p.post_title 
                                                FROM {$wpdb->prefix}ph_product_recommendations pr
                                                LEFT JOIN {$wpdb->posts} p ON pr.product_id = p.ID
                                                WHERE pr.answer_id = %d",
                                                $answer->id
                                            ));
                                            
                                            if (empty($products)) {
                                                echo '<span class="ph-no-products">No products assigned</span>';
                                            } else {
                                                foreach ($products as $product) {
                                                    echo '<div class="ph-product">';
                                                    echo '<span class="ph-product-id">#' . esc_html($product->product_id) . '</span> ';
                                                    echo '<span class="ph-product-title">' . esc_html($product->post_title) . '</span> ';
                                                    echo '<span class="ph-product-weight">(' . esc_html($product->weight) . ')</span>';
                                                    echo '</div>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <!-- Test Product Assignment Form -->
                                            <button type="button" class="button toggle-product-test" 
                                                    data-answer-id="<?php echo esc_attr($answer->id); ?>">
                                                Test Product Assignment
                                            </button>
                                            
                                            <div class="product-test-form" id="product-test-<?php echo esc_attr($answer->id); ?>" style="display:none;">
                                                <form method="post">
                                                    <input type="hidden" name="answer_id" value="<?php echo esc_attr($answer->id); ?>">
                                                    
                                                    <div class="product-search-container">
                                                        <label>Search Product:</label>
                                                        <input type="text" class="widefat product-search-debug" 
                                                               placeholder="Type to search products...">
                                                        <div class="product-search-results-debug"></div>
                                                    </div>
                                                    
                                                    <div class="selected-product-container" style="display:none;">
                                                        <p>Selected Product: <span class="selected-product-name">None</span></p>
                                                        <input type="hidden" name="product_id" value="">
                                                    </div>
                                                    
                                                    <input type="submit" name="test_product_assignment" class="button button-primary" 
                                                           value="Test Assignment" style="margin-top:10px;">
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <h2>Product Search Test</h2>
            <form method="post">
                <label for="search_term">Search Term:</label>
                <input type="text" name="search_term" id="search_term" value="<?php echo isset($_POST['search_term']) ? esc_attr($_POST['search_term']) : ''; ?>" class="regular-text">
                <input type="submit" name="test_product_search" class="button button-primary" value="Test Product Search">
            </form>
            
            <?php if (!empty($product_search_results)) : ?>
                <h3>Product Search Results</h3>
                <?php if (isset($product_search_results['success']) && !$product_search_results['success']) : ?>
                    <div class="error"><p><?php echo esc_html($product_search_results['message']); ?></p></div>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product_search_results['products'] as $product) : ?>
                                <tr>
                                    <td><?php echo esc_html($product['id']); ?></td>
                                    <td>
                                        <?php if (!empty($product['image'])) : ?>
                                            <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['title']); ?>" width="50">
                                        <?php else : ?>
                                            No image
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($product['title']); ?></td>
                                    <td><?php echo isset($product['price']) ? esc_html($product['price']) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($assignment_results) : ?>
                <h3>Product Assignment Test Results</h3>
                <?php if ($assignment_results['success']) : ?>
                    <div class="updated"><p><?php echo esc_html($assignment_results['message']); ?></p></div>
                <?php else : ?>
                    <div class="error"><p><?php echo esc_html($assignment_results['message']); ?></p></div>
                <?php endif; ?>
                
                <?php if (!empty($assignment_results['debug_info'])) : ?>
                    <h4>Debug Information</h4>
                    <pre><?php print_r($assignment_results['debug_info']); ?></pre>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <style>
        .ph-debug-question {
            margin: 20px 0;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
        }
        .ph-product {
            margin-bottom: 5px;
        }
        .ph-product-id {
            font-weight: bold;
        }
        .ph-no-products {
            color: #999;
            font-style: italic;
        }
        .product-search-results-debug {
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 5px;
            display: none;
        }
        .product-search-results-debug .product-item {
            padding: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        .product-search-results-debug .product-item:hover {
            background: #f9f9f9;
        }
        .product-search-results-debug img {
            width: 30px;
            margin-right: 10px;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Toggle product test form
        $('.toggle-product-test').on('click', function() {
            var answerId = $(this).data('answer-id');
            $('#product-test-' + answerId).toggle();
        });
        
        // Product search in debug mode
        $('.product-search-debug').on('input', function() {
            var $input = $(this);
            var $results = $input.closest('.product-search-container').find('.product-search-results-debug');
            var searchTerm = $input.val();
            
            if (searchTerm.length < 3) {
                $results.html('').hide();
                return;
            }
            
            // Show loading message
            $results.html('<p>Searching...</p>').show();
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'product_hunt_search_products',
                    nonce: '<?php echo wp_create_nonce("product_search_nonce"); ?>',
                    search_term: searchTerm
                },
                success: function(response) {
                    console.log('Product search response:', response);
                    
                    if (response.success && response.data.products.length > 0) {
                        var html = '';
                        $.each(response.data.products, function(i, product) {
                            html += '<div class="product-item" data-product-id="' + product.id + '">' +
                                '<img src="' + product.image + '" alt="' + product.title + '">' +
                                '<span>' + product.title + '</span>' +
                            '</div>';
                        });
                        $results.html(html).show();
                        
                        // Handle product selection
                        $results.find('.product-item').on('click', function() {
                            var productId = $(this).data('product-id');
                            var productName = $(this).find('span').text();
                            
                            // Update hidden field and display selected product
                            $input.closest('form').find('input[name="product_id"]').val(productId);
                            $input.closest('form').find('.selected-product-name').text(productName);
                            $input.closest('form').find('.selected-product-container').show();
                            
                            // Hide results
                            $results.hide();
                        });
                    } else {
                        $results.html('<p>No products found</p>').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Product search failed:', error);
                    $results.html('<p>Search failed. Please try again.</p>').show();
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Test product search functionality
 */
function ph_test_product_search($search_term) {
    // Check if WooCommerce is active
    if (!function_exists('wc_get_products')) {
        return array(
            'success' => false,
            'message' => 'WooCommerce functions not available'
        );
    }
    
    try {
        // Search for products
        $args = array(
            'status' => 'publish',
            'limit' => 10,
            'paginate' => false,
            's' => $search_term
        );
        
        $products = wc_get_products($args);
        
        if (empty($products)) {
            return array(
                'success' => false,
                'message' => 'No products found matching "' . $search_term . '"'
            );
        }
        
        // Format products for display
        $formatted_products = array();
        foreach ($products as $product) {
            $formatted_products[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => $product->get_price_html(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')
            );
        }
        
        return array(
            'success' => true,
            'message' => count($formatted_products) . ' products found',
            'products' => $formatted_products
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        );
    }
}

/**
 * Test product assignment functionality
 */
function ph_test_product_assignment($answer_id, $product_id) {
    global $wpdb;
    
    try {
        // Verify answer exists
        $answer_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ph_answers WHERE id = %d",
            $answer_id
        ));
        
        if (!$answer_exists) {
            return array(
                'success' => false,
                'message' => "Answer ID $answer_id does not exist"
            );
        }
        
        // Check if product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            return array(
                'success' => false,
                'message' => "Product ID $product_id does not exist"
            );
        }
        
        // Try to insert a temporary record
        $result = $wpdb->insert(
            $wpdb->prefix . 'ph_product_recommendations',
            array(
                'answer_id' => $answer_id,
                'product_id' => $product_id,
                'weight' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%d', '%f', '%s', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => "Failed to insert product recommendation: " . $wpdb->last_error,
                'debug_info' => array(
                    'wpdb_error' => $wpdb->last_error,
                    'wpdb_queries' => $wpdb->queries
                )
            );
        }
        
        // Get the inserted ID
        $inserted_id = $wpdb->insert_id;
        
        // Delete the test record
        $wpdb->delete(
            $wpdb->prefix . 'ph_product_recommendations',
            array('id' => $inserted_id),
            array('%d')
        );
        
        return array(
            'success' => true,
            'message' => "Successfully tested product assignment. Product {$product->get_name()} can be assigned to answer #{$answer_id}."
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'debug_info' => array(
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            )
        );
    }
}
