<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 */
class Product_Hunt_Admin {

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
        
        // Register form submission handlers
        add_action('admin_init', array($this, 'register_form_handlers'));
        
        // Register plugin settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Define admin hooks
        $this->define_admin_hooks();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('product-hunt-admin', plugin_dir_url(__FILE__) . 'css/product-hunt-admin.css', array(), $this->version, 'all');
        
        // Add quiz editor styles on the quiz editor page
        $screen = get_current_screen();
        if (isset($screen->id) && $screen->id === 'product-quizzes_page_product-hunt-add-quiz') {
            wp_enqueue_style('product-hunt-quiz-editor', plugin_dir_url(__FILE__) . 'css/quiz-editor.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only load scripts on plugin pages
        if (!$this->is_plugin_page()) {
            return;
        }
        
        // WP color picker
        wp_enqueue_script('wp-color-picker');
        
        // jQuery UI for sortable elements
        wp_enqueue_script('jquery-ui-sortable');
        
        // Plugin admin scripts
        wp_enqueue_script('product-hunt-admin', plugin_dir_url(__FILE__) . 'js/product-hunt-admin.js', array('jquery', 'wp-color-picker', 'jquery-ui-sortable'), $this->version, false);
        
        // Quiz editor script
        if (isset($_GET['page']) && ($_GET['page'] === 'product-hunt-add-quiz')) {
            wp_enqueue_script('product-hunt-quiz-editor', plugin_dir_url(__FILE__) . 'js/quiz-editor.js', array('jquery', 'jquery-ui-sortable'), $this->version, false);
        }
        
        // Localize script data
        wp_localize_script('product-hunt-admin', 'product_hunt_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('product_hunt_admin_nonce'),
            'placeholder_img' => plugin_dir_url(__FILE__) . 'images/product-placeholder.png',
            'strings' => array(
                'confirm_delete_quiz' => __('Are you sure you want to delete this quiz? This action cannot be undone.', 'product-hunt'),
                'confirm_delete_question' => __('Are you sure you want to delete this question? This will also remove any conditional logic associated with it.', 'product-hunt'),
                'confirm_delete_answer' => __('Are you sure you want to delete this answer? This will also remove any product mappings associated with it.', 'product-hunt'),
                'no_products_found' => __('No products found matching your search.', 'product-hunt'),
                'searching' => __('Searching...', 'product-hunt'),
                'search_error' => __('Error searching for products.', 'product-hunt'),
                'search_request_failed' => __('Request failed. Please try again.', 'product-hunt'),
                'remove_product' => __('Remove', 'product-hunt')
            )
        ));
    }
    
    /**
     * Add menu items to the WordPress admin dashboard
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu
        add_menu_page(
            __('Product Hunt Quizzes', 'product-hunt'),
            __('Product Quizzes', 'product-hunt'),
            'manage_options',
            'product-hunt-dashboard',
            array($this, 'display_dashboard_page'),
            'dashicons-list-view',
            30
        );
        
        // Submenu - Dashboard
        add_submenu_page(
            'product-hunt-dashboard',
            __('Dashboard', 'product-hunt'),
            __('Dashboard', 'product-hunt'),
            'manage_options',
            'product-hunt-dashboard',
            array($this, 'display_dashboard_page')
        );
        
        // Submenu - All quizzes
        add_submenu_page(
            'product-hunt-dashboard',
            __('All Quizzes', 'product-hunt'),
            __('All Quizzes', 'product-hunt'),
            'manage_options',
            'product-hunt-quizzes',
            array($this, 'display_quizzes_page')
        );
        
        // Submenu - Add new quiz
        add_submenu_page(
            'product-hunt-dashboard',
            __('Add New Quiz', 'product-hunt'),
            __('Add New Quiz', 'product-hunt'),
            'manage_options',
            'product-hunt-add-quiz',
            array($this, 'display_add_quiz_page')
        );
        
        // Submenu - Analytics
        add_submenu_page(
            'product-hunt-dashboard',
            __('Quiz Analytics', 'product-hunt'),
            __('Analytics', 'product-hunt'),
            'manage_options',
            'product-hunt-analytics',
            array($this, 'display_analytics_page')
        );
        
        // Submenu - Settings
        add_submenu_page(
            'product-hunt-dashboard',
            __('Quiz Settings', 'product-hunt'),
            __('Settings', 'product-hunt'),
            'manage_options',
            'product-hunt-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Display the dashboard overview page
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        include_once(plugin_dir_path(__FILE__) . 'partials/product-hunt-admin-dashboard.php');
    }
    
    /**
     * Display the quizzes listing page
     *
     * @since    1.0.0
     */
    public function display_quizzes_page() {
        include_once(plugin_dir_path(__FILE__) . 'partials/product-hunt-admin-quizzes.php');
    }
    
    /**
     * Display the add/edit quiz page
     *
     * @since    1.0.0
     */
    public function display_add_quiz_page() {
        include_once(plugin_dir_path(__FILE__) . 'partials/product-hunt-admin-add-quiz.php');
    }
    
    /**
     * Display the analytics page
     *
     * @since    1.0.0
     */
    public function display_analytics_page() {
        // Enqueue Chart.js
        wp_enqueue_script(
            'product-hunt-chartjs', 
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js', 
            array('jquery'), 
            '3.7.0', 
            true
        );
        
        include_once(plugin_dir_path(__FILE__) . 'partials/product-hunt-admin-analytics.php');
    }
    
    /**
     * Display the settings page
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Enqueue styles
        wp_enqueue_style('product-hunt-admin-settings', plugin_dir_url(__FILE__) . 'css/product-hunt-admin-settings.css', array(), $this->version);
        
        // Enqueue color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        $settings_file = plugin_dir_path(__FILE__) . 'partials/product-hunt-admin-settings.php';
        if (file_exists($settings_file)) {
            include_once $settings_file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Quiz Settings', 'product-hunt') . '</h1>';
            echo '<div class="notice notice-error"><p>' . esc_html__('Settings page template file is missing: product-hunt-admin-settings.php. Please ensure the file exists in the plugin\'s admin/partials/ directory.', 'product-hunt') . '</p></div>';
            echo '</div>';
        }
    }

    /**
     * Register plugin settings
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'product_hunt_settings_group',
            'product_hunt_settings',
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * Sanitize settings before saving
     *
     * @since    1.0.0
     * @param    array    $input    The settings array to sanitize.
     * @return   array              The sanitized settings array.
     */
    public function sanitize_settings($input) {
        $sanitized_input = array();
        
        // Style defaults
        if (isset($input['default_primary_color'])) {
            $sanitized_input['default_primary_color'] = sanitize_hex_color($input['default_primary_color']);
        }
        
        if (isset($input['default_secondary_color'])) {
            $sanitized_input['default_secondary_color'] = sanitize_hex_color($input['default_secondary_color']);
        }
        
        if (isset($input['default_button_style']) && in_array($input['default_button_style'], array('rounded', 'square', 'rounded-corners'))) {
            $sanitized_input['default_button_style'] = $input['default_button_style'];
        }
        
        if (isset($input['default_font_family'])) {
            $sanitized_input['default_font_family'] = sanitize_text_field($input['default_font_family']);
        }
        
        // Email integration
        if (isset($input['email_integration']) && in_array($input['email_integration'], array('none', 'mailchimp', 'custom'))) {
            $sanitized_input['email_integration'] = $input['email_integration'];
        }
        
        if (isset($input['mailchimp_api_key'])) {
            $sanitized_input['mailchimp_api_key'] = sanitize_text_field($input['mailchimp_api_key']);
        }
        
        if (isset($input['mailchimp_list_id'])) {
            $sanitized_input['mailchimp_list_id'] = sanitize_text_field($input['mailchimp_list_id']);
        }
        
        // Tracking
        $sanitized_input['enable_ga_tracking'] = isset($input['enable_ga_tracking']) ? 1 : 0;
        
        if (isset($input['ga_event_category'])) {
            $sanitized_input['ga_event_category'] = sanitize_text_field($input['ga_event_category']);
        }
        
        // GDPR
        $sanitized_input['enable_gdpr'] = isset($input['enable_gdpr']) ? 1 : 0;
        
        if (isset($input['gdpr_message'])) {
            $sanitized_input['gdpr_message'] = wp_kses_post($input['gdpr_message']);
        }
        
        if (isset($input['privacy_policy_page'])) {
            $sanitized_input['privacy_policy_page'] = intval($input['privacy_policy_page']);
        }
        
        // Performance
        $sanitized_input['cache_results'] = isset($input['cache_results']) ? 1 : 0;
        
        if (isset($input['cache_duration'])) {
            $sanitized_input['cache_duration'] = intval($input['cache_duration']);
            
            // Ensure valid range
            if ($sanitized_input['cache_duration'] < 1) {
                $sanitized_input['cache_duration'] = 1;
            } elseif ($sanitized_input['cache_duration'] > 168) {
                $sanitized_input['cache_duration'] = 168;
            }
        }
        
        // Permissions
        $sanitized_input['editor_access'] = isset($input['editor_access']) ? 1 : 0;
        
        return $sanitized_input;
    }
    
    /**
     * Test Mailchimp API connection via AJAX
     *
     * @since    1.0.0
     */
    public function test_mailchimp_connection() {
        check_ajax_referer('product_hunt_admin_nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'product-hunt')));
        }
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $list_id = isset($_POST['list_id']) ? sanitize_text_field($_POST['list_id']) : '';
        
        if (empty($api_key) || empty($list_id)) {
            wp_send_json_error(array('message' => __('API Key and List ID are required.', 'product-hunt')));
        }
        
        // Get the data center from API key
        $data_center = substr(strstr($api_key, '-'), 1);
        if (empty($data_center)) {
            wp_send_json_error(array('message' => __('Invalid API Key format.', 'product-hunt')));
        }
        
        // Build request URL for testing list validity
        $url = "https://{$data_center}.api.mailchimp.com/3.0/lists/{$list_id}";
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('anystring:' . $api_key),
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = isset($body['detail']) ? $body['detail'] : __('Unknown error. Please check your API Key and List ID.', 'product-hunt');
            wp_send_json_error(array('message' => $error_message));
        }
        
        wp_send_json_success();
    }

    /**
     * Register form handlers for admin forms
     *
     * @since    1.0.0
     */
    public function register_form_handlers() {
        // Quiz form handler
        add_action('admin_post_save_product_hunt_quiz', array($this, 'save_quiz_form'));
        
        // Quiz management actions
        add_action('admin_init', array($this, 'handle_quiz_actions'));
        
        // Quiz preview handler
        add_action('template_redirect', array($this, 'handle_quiz_preview'));
    }
    
    /**
     * Handle quiz actions like duplicate and delete
     *
     * @since    1.0.0
     */
    public function handle_quiz_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'product-hunt-quizzes') {
            return;
        }
        
        // Duplicate quiz
        if (isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id'])) {
            // Verify nonce
            check_admin_referer('duplicate_quiz');
            
            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'product-hunt'));
            }
            
            $quiz_id = intval($_GET['id']);
            $this->duplicate_quiz($quiz_id);
            
            // Redirect with success message
            wp_redirect(add_query_arg(array(
                'page' => 'product-hunt-quizzes',
                'message' => '3' // Quiz duplicated
            ), admin_url('admin.php')));
            exit;
        }
        
        // Delete quiz
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            // Verify nonce
            check_admin_referer('delete_quiz');
            
            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'product-hunt'));
            }
            
            $quiz_id = intval($_GET['id']);
            $this->delete_quiz($quiz_id);
            
            // Redirect with success message
            wp_redirect(add_query_arg(array(
                'page' => 'product-hunt-quizzes',
                'message' => '2' // Quiz deleted
            ), admin_url('admin.php')));
            exit;
        }
    }
    
    /**
     * Handle quiz preview in the frontend
     *
     * @since    1.0.0
     */
    public function handle_quiz_preview() {
        if (!isset($_GET['preview_quiz'])) {
            return;
        }
        
        // Only allow admin users to preview
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get quiz ID
        $quiz_id = intval($_GET['preview_quiz']);
        
        // Enqueue necessary styles and scripts
        wp_enqueue_style('product-hunt-public');
        wp_enqueue_script('product-hunt-public');
        
        // Display preview template
        include_once(plugin_dir_path(dirname(__FILE__)) . 'public/partials/product-hunt-quiz-preview.php');
        exit;
    }
    
    /**
     * Duplicate a quiz and all its related data
     *
     * @since    1.0.0
     * @param    int    $quiz_id    Quiz ID to duplicate
     */
    private function duplicate_quiz($quiz_id) {
        global $wpdb;
        
        // Get original quiz
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
            $quiz_id
        ));
        
        if (!$quiz) {
            return false;
        }
        
        // Create duplicate quiz
        $wpdb->insert(
            $wpdb->prefix . 'ph_quizzes',
            array(
                'title' => sprintf(__('%s (Copy)', 'product-hunt'), $quiz->title),
                'description' => $quiz->description,
                'category' => $quiz->category,
                'status' => 'draft', // Always set as draft
                'settings' => $quiz->settings,
                'author_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        
        $new_quiz_id = $wpdb->insert_id;
        
        // Get original quiz questions
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d ORDER BY question_order ASC",
            $quiz_id
        ));
        
        // Create duplicate questions
        $question_id_map = array(); // Original ID => New ID
        
        foreach ($questions as $question) {
            $wpdb->insert(
                $wpdb->prefix . 'ph_questions',
                array(
                    'quiz_id' => $new_quiz_id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'question_order' => $question->question_order,
                    'is_required' => $question->is_required,
                    'settings' => $question->settings,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                )
            );
            
            $new_question_id = $wpdb->insert_id;
            $question_id_map[$question->id] = $new_question_id;
            
            // Get answers for this question
            $answers = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ph_answers WHERE question_id = %d ORDER BY answer_order ASC",
                $question->id
            ));
            
            // Create duplicate answers
            $answer_id_map = array(); // Original ID => New ID
            
            foreach ($answers as $answer) {
                $wpdb->insert(
                    $wpdb->prefix . 'ph_answers',
                    array(
                        'question_id' => $new_question_id,
                        'answer_text' => $answer->answer_text,
                        'answer_order' => $answer->answer_order,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    )
                );
                
                $new_answer_id = $wpdb->insert_id;
                $answer_id_map[$answer->id] = $new_answer_id;
                
                // Get product recommendations for this answer
                $recommendations = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ph_product_recommendations WHERE answer_id = %d",
                    $answer->id
                ));
                
                // Create duplicate product recommendations
                foreach ($recommendations as $recommendation) {
                    $wpdb->insert(
                        $wpdb->prefix . 'ph_product_recommendations',
                        array(
                            'answer_id' => $new_answer_id,
                            'product_id' => $recommendation->product_id,
                            'weight' => $recommendation->weight,
                            'created_at' => current_time('mysql')
                        )
                    );
                }
            }
        }
        
        // Duplicate conditional logic
        $logic_rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ph_conditional_logic WHERE quiz_id = %d",
            $quiz_id
        ));
        
        foreach ($logic_rules as $rule) {
            if (isset($question_id_map[$rule->if_question_id]) && isset($answer_id_map[$rule->if_answer_id]) && isset($question_id_map[$rule->then_question_id])) {
                $wpdb->insert(
                    $wpdb->prefix . 'ph_conditional_logic',
                    array(
                        'quiz_id' => $new_quiz_id,
                        'if_question_id' => $question_id_map[$rule->if_question_id],
                        'if_answer_id' => $answer_id_map[$rule->if_answer_id],
                        'then_question_id' => $question_id_map[$rule->then_question_id],
                        'comparison' => $rule->comparison,
                        'created_at' => current_time('mysql')
                    )
                );
            }
        }
        
        return $new_quiz_id;
    }
    
    /**
     * Delete a quiz and all its related data
     *
     * @since    1.0.0
     * @param    int    $quiz_id    Quiz ID to delete
     */
    private function delete_quiz($quiz_id) {
        global $wpdb;
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Get questions IDs
            $question_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d",
                $quiz_id
            ));
            
            if (!empty($question_ids)) {
                // Convert IDs to a comma-separated list for IN clause
                $question_ids_string = implode(',', array_map('intval', $question_ids));
                
                // Get answers IDs
                $answer_ids = $wpdb->get_col(
                    "SELECT id FROM {$wpdb->prefix}ph_answers WHERE question_id IN ($question_ids_string)"
                );
                
                if (!empty($answer_ids)) {
                    // Convert IDs to a comma-separated list for IN clause
                    $answer_ids_string = implode(',', array_map('intval', $answer_ids));
                    
                    // Delete product recommendations
                    $wpdb->query(
                        "DELETE FROM {$wpdb->prefix}ph_product_recommendations WHERE answer_id IN ($answer_ids_string)"
                    );
                    
                    // Delete answers
                    $wpdb->query(
                        "DELETE FROM {$wpdb->prefix}ph_answers WHERE id IN ($answer_ids_string)"
                    );
                }
                
                // Delete questions
                $wpdb->query(
                    "DELETE FROM {$wpdb->prefix}ph_questions WHERE id IN ($question_ids_string)"
                );
            }
            
            // Delete conditional logic
            $wpdb->delete(
                $wpdb->prefix . 'ph_conditional_logic',
                array('quiz_id' => $quiz_id)
            );
            
            // Delete quiz performance data
            $wpdb->delete(
                $wpdb->prefix . 'ph_quiz_performance',
                array('quiz_id' => $quiz_id)
            );
            
            // Delete user responses for this quiz
            $wpdb->delete(
                $wpdb->prefix . 'ph_user_responses',
                array('quiz_id' => $quiz_id)
            );
            
            // Delete the quiz
            $wpdb->delete(
                $wpdb->prefix . 'ph_quizzes',
                array('id' => $quiz_id)
            );
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            error_log('Product Hunt Quiz delete error: ' . $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Process and save quiz form submission
     *
     * @since    1.0.0
     */
    public function save_quiz_form() {
        // Verify nonce
        if (!isset($_POST['product_hunt_quiz_nonce']) || !wp_verify_nonce($_POST['product_hunt_quiz_nonce'], 'save_product_hunt_quiz')) {
            wp_die(__('Security check failed. Please try again.', 'product-hunt'));
        }
        
        // Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'product-hunt'));
        }
        
        global $wpdb;
        
        // Get form data
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        $edit_mode = $quiz_id > 0; // Determine edit mode based on quiz_id, not just the flag
        
        // Log for debugging
        $this->debug_log("Processing quiz form - Quiz ID: {$quiz_id}, Edit mode: " . ($edit_mode ? 'true' : 'false'));
        
        $quiz_title = isset($_POST['quiz_title']) ? sanitize_text_field($_POST['quiz_title']) : '';
        $quiz_description = isset($_POST['quiz_description']) ? wp_kses_post($_POST['quiz_description']) : '';
        $quiz_category = isset($_POST['quiz_category']) ? sanitize_text_field($_POST['quiz_category']) : '';
        $quiz_status = isset($_POST['quiz_status']) && in_array($_POST['quiz_status'], array('draft', 'published')) ? $_POST['quiz_status'] : 'draft';
        
        // Validate quiz title
        if (empty($quiz_title)) {
            wp_redirect(add_query_arg(array('page' => 'product-hunt-add-quiz', 'error' => 'title_required', 'id' => $quiz_id), admin_url('admin.php')));
            exit;
        }
        
        // Prepare quiz settings
        $quiz_settings = array();
        if (isset($_POST['quiz_settings']) && is_array($_POST['quiz_settings'])) {
            foreach ($_POST['quiz_settings'] as $key => $value) {
                // Special handling for different setting types
                if (in_array($key, array('email_capture', 'email_required', 'show_progress'))) {
                    $quiz_settings[$key] = isset($value) && $value == '1' ? true : false;
                } elseif (in_array($key, array('time_limit', 'max_responses'))) {
                    $quiz_settings[$key] = intval($value);
                } else {
                    $quiz_settings[$key] = sanitize_text_field($value);
                }
            }
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            $current_time = current_time('mysql');
            
            if ($edit_mode) {
                // CRITICAL: Check if quiz exists before updating
                $existing_quiz = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
                    $quiz_id
                ));
                
                if (!$existing_quiz) {
                    throw new Exception(__('Quiz not found for editing.', 'product-hunt'));
                }
                
                $this->debug_log("Updating existing quiz: {$quiz_id}");
                
                // Create backup before editing
                $this->backup_quiz_data($quiz_id);
                
                // Update existing quiz
                $result = $wpdb->update(
                    $wpdb->prefix . 'ph_quizzes',
                    array(
                        'title' => $quiz_title,
                        'description' => $quiz_description,
                        'category' => $quiz_category,
                        'status' => $quiz_status,
                        'settings' => maybe_serialize($quiz_settings),
                        'updated_at' => $current_time
                    ),
                    array('id' => $quiz_id),
                    array('%s', '%s', '%s', '%s', '%s', '%s'),
                    array('%d')
                );
                
                if ($result === false) {
                    throw new Exception(__('Failed to update quiz in database.', 'product-hunt'));
                }
                
                // Important: Clean up existing data before adding new data
                // Delete existing questions, answers, and product recommendations
                $this->clean_up_quiz_data($quiz_id);
            } else {
                // Create new quiz
                $inserted = $wpdb->insert(
                    $wpdb->prefix . 'ph_quizzes',
                    array(
                        'title' => $quiz_title,
                        'description' => $quiz_description,
                        'category' => $quiz_category,
                        'status' => $quiz_status,
                        'settings' => maybe_serialize($quiz_settings),
                        'author_id' => get_current_user_id(),
                        'created_at' => $current_time,
                        'updated_at' => $current_time
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
                );
                
                if ($inserted === false) {
                    throw new Exception(__('Failed to create quiz in database.', 'product-hunt'));
                }
                
                $quiz_id = $wpdb->insert_id;
            }
            
            // Process questions, answers, and other data
            // (This will be the same for both new and existing quizzes)
            // ... existing code ...
            
            // Save product recommendations
            $answer_products = isset($_POST['answer_products']) ? $_POST['answer_products'] : array();
            $product_weights = isset($_POST['product_weights']) ? $_POST['product_weights'] : array();
            $this->save_product_recommendations($answer_products, $product_weights);
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Redirect to appropriate page
            $redirect_args = array(
                'page' => 'product-hunt-add-quiz',
                'message' => '1' // Quiz saved successfully
            );
            
            if ($edit_mode) {
                $redirect_args['id'] = $quiz_id;
            }
            
            wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            
            $this->debug_log('Error saving quiz: ' . $e->getMessage());
            
            // Attempt to restore from backup
            if ($edit_mode) {
                $this->restore_quiz_data($quiz_id);
            }
            
            // Redirect back with error message
            wp_redirect(add_query_arg(array(
                'page' => 'product-hunt-add-quiz',
                'id' => $quiz_id,
                'error' => 'database_error',
                'error_message' => urlencode($e->getMessage())
            ), admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Clean up existing quiz data when updating a quiz
     * 
     * @param int $quiz_id The quiz ID to clean up
     */
    private function clean_up_quiz_data($quiz_id) {
        global $wpdb;
        
        // Get all questions for this quiz
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d",
            $quiz_id
        ));
        
        if ($questions) {
            foreach ($questions as $question) {
                $question_id = $question->id;
                
                // Get all answers for this question
                $answers = $wpdb->get_results($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ph_answers WHERE question_id = %d",
                    $question_id
                ));
                
                // Get default weight or user-specified weight
                $weight = 1.0; // Default weight
                if (isset($product_weights[$answer_id][$product_id])) {
                    $weight = floatval($product_weights[$answer_id][$product_id]);
                    // Ensure weight is between 0.1 and 10
                    $weight = max(0.1, min(10, $weight));
                }
                
                // Check if mapping already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ph_product_recommendations 
                     WHERE answer_id = %d AND product_id = %d",
                    $answer_numeric_id,
                    $product_id
                ));
                
                // Check if mapping already exists for this answer
                if ($existing) {
                    // Update existing mapping
                    $wpdb->update(
                        $wpdb->prefix . 'ph_product_recommendations',
                        array(
                            'weight' => $weight,
                            'updated_at' => current_time('mysql')
                        ),
                        array(
                            'answer_id' => $answer_numeric_id,
                            'product_id' => $product_id
                        ),
                        array('%f', '%s'),
                        array('%d', '%d')
                    );
                } else {
                    // Insert new mapping
                    $wpdb->insert(
                        $wpdb->prefix . 'ph_product_recommendations',
                        array(
                            'answer_id' => $answer_numeric_id,
                            'product_id' => $product_id,
                            'weight' => $weight,
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%d', '%f', '%s')
                    );
                }
            }
        }
    }

    /**
     * Debug helper function
     */
    private function debug_log($message) {
        if (WP_DEBUG) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log($message);
            }
        }
    }

    /**
     * Search products via AJAX
     *
     * @since    1.0.0
     */
    public function search_products() {
        // Verify nonce
        check_ajax_referer('product_hunt_admin_nonce', 'security');
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'product-hunt')));
            return;
        }
        
        // Get search term
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Validate search term
        if (empty($search_term)) {
            wp_send_json_error(array('message' => __('Please provide a search term.', 'product-hunt')));
            return;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array('message' => __('WooCommerce is required for product search.', 'product-hunt')));
            return;
        }
        
        // Search for products
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 15,
            's' => $search_term
        );
        
        // Also search by SKU
        add_filter('posts_where', function($where, $wp_query) use ($search_term) {
            global $wpdb;
            if ($search_term && is_main_query()) {
                $where .= " OR {$wpdb->posts}.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key = '_sku'
                    AND meta_value LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%'
                )";
            }
            return $where;
        }, 10, 2);
        
        $products_query = new WP_Query($args);
        
        // Remove the filter after query
        remove_all_filters('posts_where');
        
        // Format results
        $products = array();
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);
                
                if (!$product) {
                    continue;
                }
                
                $image_id = $product->get_image_id();
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                
                $products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'sku' => $product->get_sku(),
                    'image' => $image_url
                );
            }
            wp_reset_postdata();
        }
        
        // Send response
        wp_send_json_success(array(
            'products' => $products,
            'count' => count($products),
            'search_term' => $search_term
        ));
    }

    /**
     * Save product recommendations for answers
     * 
     * @param array $answer_products Product mappings from form submission
     * @param array $product_weights Weight values for each product
     */
    private function save_product_recommendations($answer_products, $product_weights) {
        global $wpdb;
        
        if (empty($answer_products) || !is_array($answer_products)) {
            return;
        }
        
        foreach ($answer_products as $answer_id => $product_ids) {
            // Extract the numeric answer ID from the answer_id string
            $answer_numeric_id = preg_replace('/[^0-9]/', '', $answer_id);
            
            // Skip if the answer ID isn't valid
            if (empty($answer_numeric_id)) {
                continue;
            }
            
            // Get answer record to confirm it exists
            $answer = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ph_answers WHERE id = %d",
                $answer_numeric_id
            ));
            
            if (!$answer) {
                continue; // Skip if answer doesn't exist
            }
            
            // Process each product for this answer
            foreach ($product_ids as $product_id) {
                $product_id = absint($product_id);
                
                // Skip if product ID isn't valid
                if (empty($product_id)) {
                    continue;
                }
                
                // Get default weight or user-specified weight
                $weight = 1.0; // Default weight
                if (isset($product_weights[$answer_id][$product_id])) {
                    $weight = floatval($product_weights[$answer_id][$product_id]);
                    // Ensure weight is between 0.1 and 10
                    $weight = max(0.1, min(10, $weight));
                }
                
                // Check if mapping already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ph_product_recommendations 
                     WHERE answer_id = %d AND product_id = %d",
                    $answer_numeric_id,
                    $product_id
                ));
                
                if ($existing) {
                    // Update existing mapping
                    $wpdb->update(
                        $wpdb->prefix . 'ph_product_recommendations',
                        array(
                            'weight' => $weight,
                            'updated_at' => current_time('mysql')
                        ),
                        array(
                            'answer_id' => $answer_numeric_id,
                            'product_id' => $product_id
                        ),
                        array('%f', '%s'),
                        array('%d', '%d')
                    );
                } else {
                    // Insert new mapping
                    $wpdb->insert(
                        $wpdb->prefix . 'ph_product_recommendations',
                        array(
                            'answer_id' => $answer_numeric_id,
                            'product_id' => $product_id,
                            'weight' => $weight,
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%d', '%f', '%s')
                    );
                }
            }
        }
    }

    /**
     * Create backup of quiz data before editing
     * 
     * @param int $quiz_id The quiz ID to backup
     * @return array Backup data
     */
    private function backup_quiz_data($quiz_id) {
        global $wpdb;
        
        $this->debug_log("Creating backup for quiz: $quiz_id");
        
        // Get quiz data
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
            $quiz_id
        ), ARRAY_A);
        
        // Get questions
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d",
            $quiz_id
        ), ARRAY_A);
        
        $question_ids = wp_list_pluck($questions, 'id');
        $answers = array();
        $product_mappings = array();
        
        if (!empty($question_ids)) {
            $question_ids_str = implode(',', array_map('intval', $question_ids));
            
            $answers = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}ph_answers WHERE question_id IN ($question_ids_str)"
            , ARRAY_A);
            
            $answer_ids = wp_list_pluck($answers, 'id');
            
            if (!empty($answer_ids)) {
                $answer_ids_str = implode(',', array_map('intval', $answer_ids));
                
                $product_mappings = $wpdb->get_results(
                    "SELECT * FROM {$wpdb->prefix}ph_product_recommendations WHERE answer_id IN ($answer_ids_str)"
                , ARRAY_A);
            }
        }
        
        // Store backup in transient
        $backup = array(
            'quiz' => $quiz,
            'questions' => $questions,
            'answers' => $answers,
            'product_mappings' => $product_mappings
        );
        
        set_transient('ph_quiz_backup_' . $quiz_id, $backup, 3600); // 1 hour expiration
        
        $this->debug_log('Backup created for quiz ' . $quiz_id);
        return $backup;
    }
    
    /**
     * Restore quiz data from backup
     * 
     * @param int $quiz_id The quiz ID to restore
     * @return bool Success or failure
     */
    private function restore_quiz_data($quiz_id) {
        global $wpdb;
        
        $this->debug_log("Attempting to restore quiz: $quiz_id from backup");
        
        $backup = get_transient('ph_quiz_backup_' . $quiz_id);
        
        if (!$backup) {
            $this->debug_log('No backup found for quiz ' . $quiz_id);
            return false;
        }
        
        try {
            // Begin transaction
            $wpdb->query('START TRANSACTION');
            
            // First clean up any potentially corrupted data
            $this->clean_up_quiz_data_no_log($quiz_id);
            
            // Restore quiz data
            if (!empty($backup['quiz'])) {
                $wpdb->update(
                    $wpdb->prefix . 'ph_quizzes',
                    $backup['quiz'],
                    array('id' => $quiz_id)
                );
            }
            
            // Restore questions
            foreach ($backup['questions'] as $question) {
                $question_id = $question['id'];
                unset($question['id']);
                $wpdb->insert($wpdb->prefix . 'ph_questions', $question);
            }
            
            // Restore answers
            foreach ($backup['answers'] as $answer) {
                $answer_id = $answer['id'];
                unset($answer['id']);
                $wpdb->insert($wpdb->prefix . 'ph_answers', $answer);
            }
            
            // Restore product mappings
            foreach ($backup['product_mappings'] as $mapping) {
                unset($mapping['id']);
                $wpdb->insert($wpdb->prefix . 'ph_product_recommendations', $mapping);
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            $this->debug_log('Successfully restored quiz ' . $quiz_id . ' from backup');
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->debug_log('Failed to restore quiz from backup: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up quiz data without logging (used by restore function)
     */
    private function clean_up_quiz_data_no_log($quiz_id) {
        global $wpdb;
        
        // Get all questions for this quiz
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d",
            $quiz_id
        ));
        
        if ($questions) {
            foreach ($questions as $question) {
                $question_id = $question->id;
                
                // Get all answers for this question
                $answers = $wpdb->get_results($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ph_answers WHERE question_id = %d",
                    $question_id
                ));
                
                if ($answers) {
                    foreach ($answers as $answer) {
                        $answer_id = $answer->id;
                        
                        // Delete product recommendations for this answer
                        $wpdb->delete(
                            $wpdb->prefix . 'ph_product_recommendations',
                            array('answer_id' => $answer_id),
                            array('%d')
                        );
                    }
                    
                    // Delete all answers for this question
                    $wpdb->delete(
                        $wpdb->prefix . 'ph_answers',
                        array('question_id' => $question_id),
                        array('%d')
                    );
                }
            }
            
            // Delete all questions for this quiz
            $wpdb->delete(
                $wpdb->prefix . 'ph_questions',
                array('quiz_id' => $quiz_id),
                array('%d')
            );
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality
     *
     * @since    1.0.0
     */
    private function define_admin_hooks() {
        // AJAX handlers
        add_action('wp_ajax_product_hunt_search_products', array($this, 'search_products'));
        add_action('wp_ajax_product_hunt_test_mailchimp', array($this, 'test_mailchimp_connection'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Check if the current page is a plugin page
     *
     * @since    1.0.0
     * @return   bool    True if the current page is a plugin page, false otherwise.
     */
    private function is_plugin_page() {
        $screen = get_current_screen();
        return isset($screen->id) && strpos($screen->id, 'product-hunt') !== false;
    }
}