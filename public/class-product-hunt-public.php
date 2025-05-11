<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 */
class Product_Hunt_Public {

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($version) {
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('product-hunt-public', plugin_dir_url(__FILE__) . 'css/product-hunt-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script('product-hunt-public', plugin_dir_url(__FILE__) . 'js/product-hunt-public.js', array('jquery'), $this->version, false);
        
        // Pass data to script
        wp_localize_script('product-hunt-public', 'product_hunt_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('product_hunt_public_nonce'),
            'placeholder_img' => plugin_dir_url(dirname(__FILE__)) . 'public/images/product-placeholder.png',
            'i18n' => array(
                'error_required' => __('This question requires an answer.', 'product-hunt'),
                'error_email' => __('Please enter a valid email address.', 'product-hunt'),
                'no_recommendations' => __('Based on your answers, we don\'t have specific product recommendations at this time. Please contact us for personalized assistance.', 'product-hunt'),
                'view_product' => __('View Product', 'product-hunt'),
                'loading' => __('Finding the perfect products for you...', 'product-hunt'),
                'connection_error_title' => __('Connection Error', 'product-hunt'),
                'connection_error_message' => __('Unable to connect to the server. This could be due to your internet connection or a temporary server issue.', 'product-hunt'),
                'retry_button' => __('Try Again', 'product-hunt'),
                'restart_button' => __('Restart Quiz', 'product-hunt')
            )
        ));
    }
    
    /**
     * Register shortcodes for the plugin
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('product_quiz', array($this, 'product_quiz_shortcode'));
    }
    
    /**
     * Process shortcode to display a product quiz
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            HTML output for the shortcode.
     */
    public function product_quiz_shortcode($atts) {
        // Extract attributes
        $atts = shortcode_atts(
            array(
                'id' => 0,
                'title' => 'yes',
                'description' => 'yes',
            ),
            $atts,
            'product_quiz'
        );
        
        // Get quiz ID
        $quiz_id = intval($atts['id']);
        
        // If no quiz ID is provided, return an error message
        if ($quiz_id <= 0) {
            return '<p class="product-hunt-error">' . __('Please provide a valid quiz ID.', 'product-hunt') . '</p>';
        }
        
        // Start output buffering to capture HTML
        ob_start();
        
        // Include the quiz template
        include(plugin_dir_path(__FILE__) . 'partials/product-hunt-quiz-display.php');
        
        // Return the buffered HTML
        return ob_get_clean();
    }
    
    /**
     * AJAX handler to process quiz submission
     *
     * @since    1.0.0
     */
    public function process_quiz_submission() {
        try {
            // Increase memory limit and execution time for localhost environments
            if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                ini_set('memory_limit', '256M');
                ini_set('max_execution_time', 120);
                if (WP_DEBUG) {
                    error_log('Increasing memory limit and execution time for localhost environment');
                }
            }
            
            // Check nonce
            check_ajax_referer('product_hunt_public_nonce', 'security');
            
            // Debug information for AJAX requests
            if (WP_DEBUG) {
                error_log('AJAX request received: product_hunt_submit_quiz');
                error_log('POST data: ' . print_r($_POST, true));
            }
            
            // Get data
            $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
            $user_answers = isset($_POST['answers']) ? $_POST['answers'] : array();
            $user_data = isset($_POST['user_data']) ? $_POST['user_data'] : array();
            
            // Validate data
            if ($quiz_id <= 0) {
                wp_send_json_error(array('message' => __('Invalid quiz ID.', 'product-hunt')));
            }
            
            if (empty($user_answers)) {
                wp_send_json_error(array('message' => __('No answers provided.', 'product-hunt')));
            }
            
            // Log incoming data for debugging
            if (WP_DEBUG) {
                error_log('Quiz submission received - Quiz ID: ' . $quiz_id);
                error_log('User answers: ' . json_encode($user_answers));
            }
            
            // Save user data if provided
            $user_data_id = $this->save_user_data($user_data);
            
            // Save user answers
            $this->save_user_answers($user_data_id, $quiz_id, $user_answers);
            
            // Get product recommendations based on answers
            $recommended_products = $this->get_product_recommendations($quiz_id, $user_answers);
            
            // Debug logging
            if (WP_DEBUG) {
                error_log('Quiz submission processed successfully');
                error_log('Recommended products count: ' . count($recommended_products));
                if (empty($recommended_products)) {
                    error_log('No products found - check your product mappings');
                }
            }
            
            // Increment quiz completion count
            $this->increment_quiz_completion($quiz_id);
            
            // Return success response with recommended products
            wp_send_json_success(array(
                'products' => $recommended_products,
                'message' => __('Thank you for completing the quiz!', 'product-hunt')
            ));
            
        } catch (Exception $e) {
            // Log the error
            if (WP_DEBUG) {
                error_log('Quiz submission error: ' . $e->getMessage());
                error_log('Error trace: ' . $e->getTraceAsString());
            }
            
            // Return error response
            wp_send_json_error(array(
                'message' => __('An error occurred while processing your quiz. Please try again.', 'product-hunt'),
                'error' => $e->getMessage(),
                'debug' => WP_DEBUG ? $e->getTraceAsString() : null
            ));
        }
    }
    
    /**
     * Save user data to database
     *
     * @since    1.0.0
     * @param    array     $user_data    User data from form submission.
     * @return   int                     User data ID.
     */
    private function save_user_data($user_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ph_user_data';
        
        $data = array(
            'email' => isset($user_data['email']) ? sanitize_email($user_data['email']) : '',
            'first_name' => isset($user_data['first_name']) ? sanitize_text_field($user_data['first_name']) : '',
            'last_name' => isset($user_data['last_name']) ? sanitize_text_field($user_data['last_name']) : '',
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
            'created_at' => current_time('mysql')
        );
        
        // If user is logged in, associate with user ID
        if (is_user_logged_in()) {
            $data['user_id'] = get_current_user_id();
        }
        
        $wpdb->insert($table_name, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Save user answers to database
     *
     * @since    1.0.0
     * @param    int       $user_data_id    User data ID.
     * @param    int       $quiz_id         Quiz ID.
     * @param    array     $user_answers    User answers data.
     */
    private function save_user_answers($user_data_id, $quiz_id, $user_answers) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ph_user_responses';
        
        foreach ($user_answers as $question_id => $answer) {
            $data = array(
                'user_data_id' => $user_data_id,
                'quiz_id' => $quiz_id,
                'question_id' => intval($question_id),
                'created_at' => current_time('mysql')
            );
            
            if (is_array($answer)) {
                // Multiple answers for a question (checkboxes)
                foreach ($answer as $answer_id) {
                    $data['answer_id'] = intval($answer_id);
                    $wpdb->insert($table_name, $data);
                }
            } else if (is_numeric($answer)) {
                // Single answer (radio button)
                $data['answer_id'] = intval($answer);
                $wpdb->insert($table_name, $data);
            } else {
                // Text input
                $data['custom_answer'] = sanitize_text_field($answer);
                $wpdb->insert($table_name, $data);
            }
        }
    }
    
    /**
     * Get product recommendations based on quiz answers
     *
     * @since    1.0.0
     * @param    int       $quiz_id         Quiz ID.
     * @param    array     $user_answers    User answers data.
     * @return   array                      Array of recommended WooCommerce products.
     */
    private function get_product_recommendations($quiz_id, $user_answers) {
        global $wpdb;
        
        $product_points = array();
        $product_cat_points = array();
        
        // Get all answer IDs from user submission
        $answer_ids = array();
        foreach ($user_answers as $question_id => $answer) {
            if (is_array($answer)) {
                // Multiple answers (checkboxes)
                $answer_ids = array_merge($answer_ids, array_map('intval', $answer));
            } else if (is_numeric($answer)) {
                // Single answer (radio button)
                $answer_ids[] = intval($answer);
            }
        }
        
        // If no valid answer IDs, return empty array
        if (empty($answer_ids)) {
            if (WP_DEBUG) {
                error_log('No valid answer IDs found from user submission');
            }
            return array();
        }
        
        // Prepare placeholder for IN clause
        $placeholders = implode(',', array_fill(0, count($answer_ids), '%d'));
        
        // Get product recommendations for these answers
        $query = $wpdb->prepare(
            "SELECT answer_id, product_id, product_cat_id, weight
            FROM {$wpdb->prefix}ph_product_recommendations
            WHERE answer_id IN ($placeholders)",
            $answer_ids
        );
        
        $recommendations = $wpdb->get_results($query);
        
        if (WP_DEBUG) {
            error_log('SQL Query: ' . $wpdb->last_query);
            error_log('Found ' . count($recommendations) . ' recommendation records');
        }
        
        // Process recommendations
        foreach ($recommendations as $rec) {
            // Process product recommendations
            if (!empty($rec->product_id)) {
                if (!isset($product_points[$rec->product_id])) {
                    $product_points[$rec->product_id] = 0;
                }
                $product_points[$rec->product_id] += floatval($rec->weight);
            }
            
            // Process category recommendations
            if (!empty($rec->product_cat_id)) {
                if (!isset($product_cat_points[$rec->product_cat_id])) {
                    $product_cat_points[$rec->product_cat_id] = 0;
                }
                $product_cat_points[$rec->product_cat_id] += floatval($rec->weight);
            }
        }
        
        // No products found, return empty array
        if (empty($product_points) && empty($product_cat_points)) {
            if (WP_DEBUG) {
                error_log('No product or category points calculated');
            }
            return array();
        }
        
        // Sort products by points (highest first)
        arsort($product_points);
        
        // Limit to top 6 products
        $product_points = array_slice($product_points, 0, 6, true);
        
        // For debug
        if (WP_DEBUG) {
            error_log('Product points: ' . print_r($product_points, true));
        }
        
        // Get WooCommerce products
        $products = array();
        if (!empty($product_points)) {
            foreach ($product_points as $product_id => $score) {
                // Get product data using WooCommerce API
                $product = wc_get_product($product_id);
                
                // Add to results if product exists and is visible
                if ($product && $product->is_visible()) {
                    $image_id = $product->get_image_id();
                    $image_url = $image_id ? wp_get_attachment_url($image_id) : wc_placeholder_img_src();
                    
                    $products[] = array(
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'price' => $product->get_price_html(),
                        'permalink' => get_permalink($product_id),
                        'image' => $image_url,
                        'score' => $score
                    );
                }
            }
        }
        
        // Debug the final product list
        if (WP_DEBUG) {
            error_log('Final product recommendations: ' . print_r($products, true));
        }
        
        return $products;
    }
    
    /**
     * Increment quiz completion count in analytics
     *
     * @since    1.0.0
     * @param    int       $quiz_id    Quiz ID.
     */
    private function increment_quiz_completion($quiz_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ph_quiz_performance';
        
        // Check if record exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE quiz_id = %d",
            $quiz_id
        ));
        
        if ($exists) {
            // Update existing record
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table_name} SET completions = completions + 1, last_updated = %s WHERE quiz_id = %d",
                current_time('mysql'),
                $quiz_id
            ));
        } else {
            // Create new record
            $wpdb->insert(
                $table_name,
                array(
                    'quiz_id' => $quiz_id,
                    'starts' => 1,
                    'completions' => 1,
                    'last_updated' => current_time('mysql')
                )
            );
        }
    }
}