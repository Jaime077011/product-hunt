<?php
/**
 * Handles AJAX requests for quiz submission in Product Hunt plugin.
 */

// Add proper error suppression in production mode
@ini_set('display_errors', 0);
error_reporting(0); // Only in production - remove in development

function product_hunt_submit_quiz() {
    // Enable detailed error reporting for debugging
    error_log('Quiz submission started');
    
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'product_hunt_quiz_nonce')) {
            error_log('Quiz submission failed: Invalid nonce');
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Get quiz data
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        
        if (!$quiz_id) {
            error_log('Quiz submission failed: Invalid quiz ID');
            wp_send_json_error('Invalid quiz ID');
            return;
        }
        
        error_log('Processing quiz ID: ' . $quiz_id);
        
        // Process answers
        // Add your answer processing logic here
        
        // Send success response
        wp_send_json_success(array(
            'message' => 'Quiz submitted successfully!',
            'redirect' => get_permalink(get_option('product_hunt_results_page', 0))
        ));
    } catch (Exception $e) {
        error_log('Quiz submission exception: ' . $e->getMessage());
        wp_send_json_error('An error occurred: ' . $e->getMessage());
    }
    
    // Fallback response in case something went wrong
    error_log('Quiz submission reached unexpected end');
    wp_send_json_error('An unknown error occurred');
    
    wp_die(); // Required to terminate AJAX request properly
}