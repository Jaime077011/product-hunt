<?php
<?php
/**
 * Product Hunt Plugin - Issues & Fixes Documentation
 * 
 * This file documents all identified issues with the Product Hunt plugin
 * and provides solutions for fixing them.
 */

/**
 * ISSUE 1: QUIZ SUBMISSION PROBLEMS
 * 
 * Problem: After submitting a quiz, errors occur and the submission fails.
 * Root causes:
 * - PHP errors mixing with JSON responses (common in XAMPP environments)
 * - Possible AJAX handler issues
 * - Missing error handling
 */

// SOLUTION 1A: Add proper error suppression in AJAX handlers
// Add to the top of your AJAX handler files
@ini_set('display_errors', 0); 
error_reporting(0); // Only in production

// SOLUTION 1B: Improve AJAX error handling in JavaScript
/*
function submitQuizData() {
    $.ajax({
        url: product_hunt_vars.ajax_url,
        type: 'POST',
        data: {
            action: 'submit_product_hunt_quiz',
            nonce: product_hunt_vars.nonce,
            quiz_id: quizId,
            answers: userAnswers
        },
        success: function(response) {
            console.log('Quiz submission response:', response);
            if (response.success) {
                // Success handling
                displayResults(response.data);
            } else {
                // Error handling
                console.error('Server returned error:', response.data);
                alert('There was a problem submitting your quiz. Please try again.');
            }
        },
        error: function(xhr, status, error) {
            // Log comprehensive error information
            console.error('AJAX Error Status:', status);
            console.error('Error:', error);
            console.error('Response Text:', xhr.responseText);
            
            // Special handling for localhost XAMPP issues
            if (xhr.responseText && xhr.responseText.includes('<?php')) {
                console.log("Detected PHP parsing issue - trying to extract JSON from response");
                tryParseLocalXamppResponse(xhr.responseText);
            }
            
            alert('There was a problem connecting to the server. Please try again later.');
        }
    });
}

// Helper function to extract JSON from problematic XAMPP responses
function tryParseLocalXamppResponse(responseText) {
    try {
        const jsonStart = responseText.indexOf('{');
        const jsonEnd = responseText.lastIndexOf('}') + 1;
        if (jsonStart >= 0 && jsonEnd > jsonStart) {
            const jsonStr = responseText.substring(jsonStart, jsonEnd);
            const data = JSON.parse(jsonStr);
            console.log("Successfully extracted JSON:", data);
            if (data.success) {
                displayResults(data.data);
                return;
            }
        }
    } catch (e) {
        console.error("Failed to extract valid JSON:", e);
    }
}
*/

// SOLUTION 1C: Add detailed error logging on the server side
/*
add_action('wp_ajax_submit_product_hunt_quiz', 'handle_quiz_submission');
add_action('wp_ajax_nopriv_submit_product_hunt_quiz', 'handle_quiz_submission');

function handle_quiz_submission() {
    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'product_hunt_quiz_nonce')) {
            throw new Exception('Security check failed');
        }
        
        // Process quiz submission
        // ...existing code...
        
        wp_send_json_success($result_data);
    } catch (Exception $e) {
        error_log('Quiz submission error: ' . $e->getMessage());
        error_log('Submission data: ' . print_r($_POST, true));
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
*/

/**
 * ISSUE 2: QUIZ EDITING PROBLEMS
 * 
 * Problem: Unable to edit existing quizzes properly
 * Root causes:
 * - JavaScript initialization conflicts
 * - Data loss during edit operations
 * - Form handling issues
 */

// SOLUTION 2A: Fix JavaScript initialization conflicts
/*
jQuery(document).ready(function($) {
    // Check if we're in edit mode vs create mode
    const quizId = $('#product-hunt-quiz-form input[name="quiz_id"]').val();
    const isEditMode = quizId && parseInt(quizId) > 0;
    
    // Set global flag to prevent initialization conflicts
    window.adminQuizBuilderDisabled = true;
    
    // Initialize quiz editor with correct state
    initQuizEditor({
        quizId: parseInt(quizId, 10) || 0,
        isEditing: isEditMode,
        currentStep: 1
    });
    
    // Debug info
    if (isEditMode) {
        console.log('Edit mode initialized for quiz ID:', quizId);
    }
});
*/

// SOLUTION 2B: Safe data handling during edit operations
/*
private function handle_quiz_form_submission() {
    global $wpdb;
    
    $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
    $edit_mode = $quiz_id > 0;
    
    try {
        if ($edit_mode) {
            // CRITICAL: Check if quiz exists before updating
            $existing_quiz = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
                $quiz_id
            ));
            
            if (!$existing_quiz) {
                throw new Exception(__('Quiz not found for editing.', 'product-hunt'));
            }
            
            // Create backup of existing data before modifying
            $this->backup_quiz_data($quiz_id);
            
            // Only then clean up existing data before adding new data
            $this->clean_up_quiz_data($quiz_id);
        }
        
        // Continue with quiz creation/update
        // ...existing code...
        
    } catch (Exception $e) {
        // If we're in edit mode and an error occurred after cleanup,
        // try to restore from backup
        if ($edit_mode) {
            $this->restore_quiz_data($quiz_id);
        }
        
        wp_die($e->getMessage());
    }
}

private function backup_quiz_data($quiz_id) {
    global $wpdb;
    
    // Get all questions, answers and product mappings
    $quiz = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
        $quiz_id
    ), ARRAY_A);
    
    $questions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d",
        $quiz_id
    ), ARRAY_A);
    
    $question_ids = wp_list_pluck($questions, 'id');
    $question_ids_str = implode(',', array_map('intval', $question_ids));
    
    $answers = array();
    $product_mappings = array();
    
    if (!empty($question_ids_str)) {
        $answers = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ph_answers WHERE question_id IN ($question_ids_str)"
        , ARRAY_A);
        
        $answer_ids = wp_list_pluck($answers, 'id');
        $answer_ids_str = implode(',', array_map('intval', $answer_ids));
        
        if (!empty($answer_ids_str)) {
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
    
    // Also log the backup operation
    error_log('Backup created for quiz ' . $quiz_id . ' before edit operation');
}

private function restore_quiz_data($quiz_id) {
    global $wpdb;
    
    $backup = get_transient('ph_quiz_backup_' . $quiz_id);
    
    if (!$backup) {
        error_log('No backup found for quiz ' . $quiz_id);
        return false;
    }
    
    try {
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        // First delete any potentially corrupted data
        $this->clean_up_quiz_data($quiz_id, false); // false = don't log
        
        // Restore quiz data
        $wpdb->update(
            $wpdb->prefix . 'ph_quizzes',
            $backup['quiz'],
            array('id' => $quiz_id)
        );
        
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
        
        error_log('Successfully restored quiz ' . $quiz_id . ' from backup');
        return true;
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Failed to restore quiz from backup: ' . $e->getMessage());
        return false;
    }
}
*/

/**
 * ISSUE 3: PRODUCT ASSIGNMENT ISSUES
 * 
 * Problem: Can't assign products to quiz answers properly
 * Root causes:
 * - UI flickering during product selection
 * - AJAX issues with product search and selection
 * - Data saving problems
 */

// SOLUTION 3A: Fix product selection UI
/*
// Better product mapping toggle without flickering
$(document).on('click', '.toggle-product-mapping', function(e) {
    e.preventDefault();
    e.stopPropagation(); // Prevent event bubbling
    
    const $button = $(this);
    const $container = $button.closest('.product-hunt-answer-container');
    const $mapping = $container.find('.product-hunt-product-mapping');
    
    // Close all other open mappings
    $('.product-hunt-answer-container').not($container).find('.product-hunt-product-mapping').hide();
    
    // Toggle this specific mapping with a clean show/hide rather than animation
    $mapping.toggle();
    
    // Update button text accordingly
    if ($mapping.is(':visible')) {
        $button.text('Hide Products');
    } else {
        $button.text('Manage Products');
    }
});
*/

// SOLUTION 3B: Fix product search functionality
/*
// Improved product search with clear error handling
$(document).on('input', '.product-search-input', function() {
    const $input = $(this);
    const $results = $input.closest('.product-mapping-section').find('.product-search-results');
    const searchTerm = $input.val();
    
    if (searchTerm.length < 3) {
        $results.html('').hide();
        return;
    }
    
    // Show loading indicator
    $results.html('<p>Searching...</p>').show();
    
    // Clear previous timeout
    if (window.productSearchTimeout) {
        clearTimeout(window.productSearchTimeout);
    }
    
    // Set new timeout to prevent too many requests
    window.productSearchTimeout = setTimeout(function() {
        $.ajax({
            url: product_hunt_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'product_hunt_search_products',
                nonce: product_hunt_vars.nonce,
                search_term: searchTerm
            },
            success: function(response) {
                console.log('Search response:', response);
                
                if (response.success && response.data.products.length > 0) {
                    let html = '<ul class="product-list">';
                    response.data.products.forEach(function(product) {
                        html += '<li class="product-item" data-product-id="' + product.id + '">' +
                            '<img src="' + product.image + '" alt="' + product.title + '">' +
                            '<span class="product-title">' + product.title + '</span>' +
                            '<button type="button" class="add-product">Add</button>' +
                            '</li>';
                    });
                    html += '</ul>';
                    $results.html(html);
                } else {
                    $results.html('<p>No products found.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Product search failed:', error);
                console.error('Response:', xhr.responseText);
                $results.html('<p class="error">Search failed. Please try again.</p>');
            }
        });
    }, 500);
});
*/

// SOLUTION 3C: Fix product data saving
/*
// In PHP handler for quiz form submission:

/**
 * Save product recommendations with proper validation
 *
 * @param array $answer_products An array of answer_id => [product_ids]
 * @param array $product_weights An array of answer_id => product_id => weight
 */
/*
private function save_product_recommendations($answer_products, $product_weights) {
    global $wpdb;
    
    if (!is_array($answer_products)) {
        error_log('Invalid answer_products data: ' . print_r($answer_products, true));
        return false;
    }
    
    // Begin database transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        $inserted = 0;
        
        foreach ($answer_products as $answer_id => $product_ids) {
            $answer_id = intval($answer_id);
            
            // Verify answer exists
            $answer_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ph_answers WHERE id = %d",
                $answer_id
            ));
            
            if (!$answer_exists) {
                error_log("Answer ID $answer_id does not exist");
                continue;
            }
            
            // Process each product for this answer
            foreach ($product_ids as $product_id) {
                $product_id = intval($product_id);
                
                // Check if product exists
                $product_exists = wc_get_product($product_id);
                if (!$product_exists) {
                    error_log("Product ID $product_id does not exist");
                    continue;
                }
                
                // Get weight for this product (default to 1 if not specified)
                $weight = 1;
                if (isset($product_weights[$answer_id][$product_id])) {
                    $weight = floatval($product_weights[$answer_id][$product_id]);
                }
                
                // Insert product recommendation
                $result = $wpdb->insert(
                    $wpdb->prefix . 'ph_product_recommendations',
                    array(
                        'answer_id' => $answer_id,
                        'product_id' => $product_id,
                        'weight' => $weight
                    ),
                    array('%d', '%d', '%f')
                );
                
                if ($result === false) {
                    throw new Exception("Failed to insert product recommendation: " . $wpdb->last_error);
                }
                
                $inserted++;
            }
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        error_log("Successfully inserted $inserted product recommendations");
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $wpdb->query('ROLLBACK');
        error_log("Failed to save product recommendations: " . $e->getMessage());
        return false;
    }
}
*/

/**
 * TROUBLESHOOTING GUIDE
 * 
 * For immediate debugging:
 * 
 * 1. Enable WP_DEBUG in wp-config.php:
 *    define('WP_DEBUG', true);
 *    define('WP_DEBUG_LOG', true);
 *    define('WP_DEBUG_DISPLAY', false);
 * 
 * 2. Check the debug.log file for errors:
 *    /wp-content/debug.log
 * 
 * 3. Use browser console to debug JavaScript issues:
 *    - Press F12 to open Developer Tools
 *    - Go to Console tab
 *    - Look for JavaScript errors
 *    - Check Network tab for AJAX request issues
 * 
 * 4. Test direct database access in these tables:
 *    - wp_ph_quizzes
 *    - wp_ph_questions
 *    - wp_ph_answers
 *    - wp_ph_product_recommendations
 */

/**
 * DATABASE REPAIR TOOL
 * 
 * This can help recover from database corruption issues:
 */
function ph_repair_database_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'ph_quizzes',
        $wpdb->prefix . 'ph_questions',
        $wpdb->prefix . 'ph_answers',
        $wpdb->prefix . 'ph_product_recommendations',
        $wpdb->prefix . 'ph_quiz_results'
    );
    
    $results = array();
    
    foreach ($tables as $table) {
        $repair_result = $wpdb->get_results("REPAIR TABLE $table");
        $results[$table] = $repair_result;
    }
    
    return $results;
}

// Add an admin page to run the repair tool
function ph_add_repair_page() {
    add_submenu_page(
        'edit.php?post_type=product-quizzes',
        'Database Repair',
        'Database Repair',
        'manage_options',
        'ph-database-repair',
        'ph_database_repair_page'
    );
}

function ph_database_repair_page() {
    $message = '';
    
    if (isset($_POST['repair_database']) && wp_verify_nonce($_POST['ph_repair_nonce'], 'ph_repair_database')) {
        $results = ph_repair_database_tables();
        $message = '<div class="updated"><p>Database repair completed. Results:</p><pre>' . print_r($results, true) . '</pre></div>';
    }
    
    echo '<div class="wrap">';
    echo '<h1>Product Hunt - Database Repair Tool</h1>';
    
    echo $message;
    
    echo '<form method="post">';
    wp_nonce_field('ph_repair_database', 'ph_repair_nonce');
    echo '<p>This tool will attempt to repair the Product Hunt database tables if they are corrupted.</p>';
    echo '<p class="submit"><input type="submit" name="repair_database" class="button button-primary" value="Repair Database"></p>';
    echo '</form>';
    echo '</div>';
}

// Add this line at the end of your main plugin file
if (is_admin()) {
    add_action('admin_menu', 'ph_add_repair_page');
}

/**
 * End of file
 */