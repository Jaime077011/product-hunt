<?php
/**
 * Debug Quiz Edit Functionality
 *
 * This file contains functions to help debug issues with quiz editing and updating.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add debugging for quiz edit process
 */
function ph_debug_quiz_edit_process() {
    // Only run on admin pages
    if (!is_admin()) {
        return;
    }
    
    // Check if we're on the quiz edit page
    $current_screen = get_current_screen();
    if (!$current_screen || !isset($_GET['page']) || $_GET['page'] !== 'product-hunt-quiz') {
        return;
    }
    
    // Debug mode - set to true to enable detailed logging
    $debug_mode = true;
    
    // Log edit/update process
    if ($debug_mode) {
        // Create a log file in the plugin directory
        $log_file = plugin_dir_path(dirname(__FILE__)) . 'debug-quiz-edit.log';
        
        // Log request information
        $log_data = "=== Quiz Edit Debug Log: " . date('Y-m-d H:i:s') . " ===\n";
        $log_data .= "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
        $log_data .= "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
        
        // Log GET parameters
        $log_data .= "GET Parameters:\n";
        foreach ($_GET as $key => $value) {
            $log_data .= "  $key: " . print_r($value, true) . "\n";
        }
        
        // Log POST data (sanitized for security)
        $log_data .= "POST Data:\n";
        foreach ($_POST as $key => $value) {
            // Skip logging sensitive data
            if (in_array($key, ['password', 'pass', 'pwd', 'nonce'])) {
                $log_data .= "  $key: [REDACTED]\n";
            } else if (is_array($value)) {
                $log_data .= "  $key: " . json_encode($value) . "\n";
            } else {
                $log_data .= "  $key: " . sanitize_text_field($value) . "\n";
            }
        }
        
        // Log current user
        $current_user = wp_get_current_user();
        $log_data .= "Current User: " . $current_user->user_login . " (ID: " . $current_user->ID . ")\n";
        
        // Append to log file
        file_put_contents($log_file, $log_data . "\n\n", FILE_APPEND);
    }
}
add_action('admin_init', 'ph_debug_quiz_edit_process');

/**
 * Hook into quiz data loading process to debug
 */
function ph_debug_quiz_data_loading($quiz_id) {
    // Log the quiz data being loaded
    $log_file = plugin_dir_path(dirname(__FILE__)) . 'debug-quiz-edit.log';
    $log_data = "=== Quiz Data Loading for ID: $quiz_id ===\n";
    
    // Get quiz data
    $quiz_data = get_post_meta($quiz_id, '_quiz_data', true);
    if (empty($quiz_data)) {
        $log_data .= "ERROR: No quiz data found for ID: $quiz_id\n";
    } else {
        $log_data .= "Quiz data found: " . json_encode($quiz_data) . "\n";
    }
    
    // Log other meta fields
    $all_meta = get_post_meta($quiz_id);
    $log_data .= "All meta data keys for quiz ID $quiz_id:\n";
    foreach ($all_meta as $key => $value) {
        $log_data .= "  $key\n";
    }
    
    file_put_contents($log_file, $log_data . "\n\n", FILE_APPEND);
    
    return $quiz_data;
}

/**
 * Test quiz edit form population
 */
function ph_test_quiz_edit_form_population() {
    // Only on admin pages with our quiz parameter
    if (!is_admin() || !isset($_GET['quiz_id']) || !isset($_GET['page']) || $_GET['page'] !== 'product-hunt-quiz') {
        return;
    }
    
    $quiz_id = intval($_GET['quiz_id']);
    
    // Add inline script to verify form population
    add_action('admin_footer', function() use ($quiz_id) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.group('Quiz Edit Form Debug');
            console.log('Verifying form population for Quiz ID: <?php echo $quiz_id; ?>');
            
            // Check if all form fields are properly populated
            const formFields = [
                { name: 'quiz_title', label: 'Quiz Title' },
                { name: 'quiz_description', label: 'Quiz Description' },
                { name: 'quiz_status', label: 'Quiz Status' },
                // Add other fields as needed
            ];
            
            let formPopulated = true;
            
            formFields.forEach(field => {
                const $field = $(`[name="${field.name}"]`);
                if ($field.length === 0) {
                    console.error(`Field ${field.label} (${field.name}) not found in the form`);
                    formPopulated = false;
                } else {
                    console.log(`Field ${field.label}: ${$field.val()}`);
                }
            });
            
            console.log(`Form population ${formPopulated ? 'successful' : 'has issues'}`);
            console.groupEnd();
        });
        </script>
        <?php
    });
}
add_action('admin_init', 'ph_test_quiz_edit_form_population');

/**
 * Fix common edit/update quiz issues
 */
function ph_fix_quiz_edit_update_issues() {
    // This function will implement fixes for common issues
    
    // 1. Fix form submission handler to properly detect edit mode
    add_filter('product_hunt_quiz_is_edit_mode', function($is_edit_mode, $quiz_id) {
        if (!empty($quiz_id) && get_post_status($quiz_id) !== false) {
            return true;
        }
        return $is_edit_mode;
    }, 10, 2);
    
    // 2. Fix nonce verification
    add_action('admin_notices', function() {
        // Check if we're coming from a failed quiz update
        if (isset($_GET['quiz-update-error']) && $_GET['quiz-update-error'] === 'nonce') {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>Quiz update failed: Security verification failed. Please try again.</p>
            </div>
            <?php
        }
    });
}
add_action('init', 'ph_fix_quiz_edit_update_issues');

/**
 * Function to manually verify a quiz can be loaded for editing
 */
function ph_verify_quiz_edit_capability($quiz_id) {
    if (empty($quiz_id)) {
        return [
            'success' => false,
            'message' => 'No quiz ID provided'
        ];
    }
    
    // Check if quiz exists
    $quiz = get_post($quiz_id);
    if (!$quiz) {
        return [
            'success' => false,
            'message' => 'Quiz not found'
        ];
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $quiz_id)) {
        return [
            'success' => false,
            'message' => 'You do not have permission to edit this quiz'
        ];
    }
    
    // Check if quiz data exists
    $quiz_data = get_post_meta($quiz_id, '_quiz_data', true);
    if (empty($quiz_data)) {
        return [
            'success' => false,
            'message' => 'Quiz data is missing or corrupted'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Quiz can be edited',
        'data' => $quiz_data
    ];
}

/**
 * Generate and analyze the proper quiz edit URL
 * 
 * @param int $quiz_id The ID of the quiz to edit
 * @return string The proper URL for editing a quiz
 */
function ph_get_quiz_edit_url($quiz_id) {
    if (empty($quiz_id)) {
        return '';
    }
    
    // Generate the proper URL for editing a quiz
    $edit_url = admin_url('admin.php?page=product-hunt-quiz&action=edit&quiz_id=' . intval($quiz_id));
    
    // Add a nonce for security
    $edit_url = add_query_arg('_wpnonce', wp_create_nonce('edit_quiz_' . $quiz_id), $edit_url);
    
    // Log the URL generation
    $log_file = plugin_dir_path(dirname(__FILE__)) . 'debug-quiz-edit.log';
    $log_data = "=== Quiz Edit URL Generation for ID: $quiz_id ===\n";
    $log_data .= "Generated URL: $edit_url\n";
    
    // Check if current URL matches the pattern needed
    $current_url = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $log_data .= "Current URL: $current_url\n";
    
    // Extract the quiz_id from the current URL if present
    $current_quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
    $log_data .= "Current quiz_id in URL: $current_quiz_id\n";
    
    // Check if the action parameter is correct
    $current_action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
    $log_data .= "Current action in URL: $current_action\n";
    
    // Check if the nonce is present and valid
    $current_nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
    $nonce_valid = $current_nonce ? wp_verify_nonce($current_nonce, 'edit_quiz_' . $current_quiz_id) : false;
    $log_data .= "Current nonce in URL: $current_nonce (Valid: " . ($nonce_valid ? 'Yes' : 'No') . ")\n";
    
    // Log what the correct URL structure should be
    $log_data .= "\nExpected URL pattern: admin.php?page=product-hunt-quiz&action=edit&quiz_id=ID&_wpnonce=VALID_NONCE\n";
    
    // Check for common URL issues
    if ($current_quiz_id != $quiz_id) {
        $log_data .= "WARNING: Current quiz_id in URL doesn't match the expected quiz_id\n";
    }
    
    if ($current_action !== 'edit') {
        $log_data .= "WARNING: 'action=edit' parameter is missing or incorrect in the URL\n";
    }
    
    if (!$nonce_valid) {
        $log_data .= "WARNING: The nonce in the URL is missing or invalid\n";
    }
    
    file_put_contents($log_file, $log_data . "\n\n", FILE_APPEND);
    
    return $edit_url;
}

/**
 * Fix quiz edit URL issues
 * 
 * This function ensures that the quiz edit URL is properly formatted
 * and contains all necessary parameters for editing to work correctly.
 */
function ph_fix_quiz_edit_urls() {
    // Only run on the quiz list page
    if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'product-hunt-quiz' || 
        (isset($_GET['action']) && $_GET['action'] === 'edit')) {
        return;
    }
    
    // Fix the edit links in the quiz list
    add_filter('post_row_actions', function($actions, $post) {
        if ($post->post_type === 'product_hunt_quiz' && isset($actions['edit'])) {
            // Get the correct edit URL
            $correct_edit_url = ph_get_quiz_edit_url($post->ID);
            
            // Replace the edit URL with the correct one
            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($correct_edit_url),
                esc_html__('Edit', 'product-hunt')
            );
        }
        return $actions;
    }, 10, 2);
    
    // Add a notice if we're redirecting to a corrected URL
    if (isset($_GET['quiz_id']) && !isset($_GET['action'])) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>The quiz edit URL was missing parameters. You've been redirected to the correct URL format.</p>
            </div>
            <?php
        });
        
        // Redirect to the correct URL format
        $corrected_url = ph_get_quiz_edit_url(intval($_GET['quiz_id']));
        wp_redirect($corrected_url);
        exit;
    }
}
add_action('admin_init', 'ph_fix_quiz_edit_urls');

/**
 * Fix the quiz update handler to properly process form submissions
 */
function ph_fix_quiz_update_handler() {
    // Only run when submitting the quiz form
    if (!is_admin() || !isset($_POST['quiz_save']) || !isset($_POST['quiz_id'])) {
        return;
    }
    
    $quiz_id = intval($_POST['quiz_id']);
    
    // Log the update attempt
    $log_file = plugin_dir_path(dirname(__FILE__)) . 'debug-quiz-edit.log';
    $log_data = "=== Quiz Update Handler for ID: $quiz_id ===\n";
    
    // Verify the nonce
    $nonce_action = empty($quiz_id) ? 'create_quiz_nonce' : 'update_quiz_' . $quiz_id;
    $nonce_field = 'quiz_nonce';
    
    if (!isset($_POST[$nonce_field]) || !wp_verify_nonce($_POST[$nonce_field], $nonce_action)) {
        $log_data .= "ERROR: Nonce verification failed. Using action: $nonce_action\n";
        $log_data .= "Submitted nonce: " . (isset($_POST[$nonce_field]) ? $_POST[$nonce_field] : 'MISSING') . "\n";
        
        file_put_contents($log_file, $log_data . "\n\n", FILE_APPEND);
        
        // Add an error parameter to the redirect URL
        $redirect_url = add_query_arg('quiz-update-error', 'nonce', admin_url('admin.php?page=product-hunt-quiz'));
        wp_redirect($redirect_url);
        exit;
    }
    
    $log_data .= "Nonce verification passed\n";
    file_put_contents($log_file, $log_data . "\n\n", FILE_APPEND);
}
add_action('admin_init', 'ph_fix_quiz_update_handler');

/**
 * Provide debug info in admin footer
 */
function ph_debug_quiz_admin_footer() {
    $screen = get_current_screen();
    
    // Only run on quiz admin pages
    if (!$screen || !isset($_GET['page']) || $_GET['page'] !== 'product-hunt-quiz') {
        return;
    }
    
    // Add debug info in the admin footer
    add_filter('admin_footer_text', function($text) {
        $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
        
        if ($quiz_id) {
            $correct_url = ph_get_quiz_edit_url($quiz_id);
            $debug_info = '<div class="quiz-debug-info" style="margin-top:20px;padding:10px;background:#f8f8f8;border:1px solid #ddd;">';
            $debug_info .= '<h3>Quiz Edit URL Debug Info</h3>';
            $debug_info .= '<p><strong>Correct Edit URL:</strong> <a href="' . esc_url($correct_url) . '">' . esc_html($correct_url) . '</a></p>';
            $debug_info .= '<p><strong>Current URL:</strong> ' . esc_html(admin_url(basename($_SERVER['REQUEST_URI']))) . '</p>';
            $debug_info .= '<p>If the edit functionality is not working, try using the correct URL above.</p>';
            $debug_info .= '</div>';
            
            return $text . $debug_info;
        }
        
        return $text;
    });
}
add_action('admin_init', 'ph_debug_quiz_admin_footer');
