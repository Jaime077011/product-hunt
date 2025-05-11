<?php
/**
 * Quiz Debug Admin Tool
 *
 * Provides admin tools to debug quiz editing issues
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add debug submenu to Product Hunt Quiz admin menu
 */
function ph_add_debug_menu() {
    add_submenu_page(
        'product-hunt-quiz',
        'Quiz Debug Tools',
        'Debug Tools',
        'manage_options',
        'product-hunt-quiz-debug',
        'ph_quiz_debug_page'
    );
}
add_action('admin_menu', 'ph_add_debug_menu');

/**
 * Display the quiz debug tools page
 */
function ph_quiz_debug_page() {
    // Security check
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $message = '';
    $quiz_data = null;
    
    // Handle form submission
    if (isset($_POST['debug_quiz'])) {
        // Verify nonce
        if (!isset($_POST['quiz_debug_nonce']) || !wp_verify_nonce($_POST['quiz_debug_nonce'], 'quiz_debug_action')) {
            $message = '<div class="error"><p>Security verification failed. Please try again.</p></div>';
        } else {
            // Get quiz ID
            $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
            
            if (!$quiz_id) {
                $message = '<div class="error"><p>Please enter a valid Quiz ID.</p></div>';
            } else {
                // Get quiz data
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/debug-quiz-edit.php';
                $result = ph_verify_quiz_edit_capability($quiz_id);
                
                if ($result['success']) {
                    $quiz_data = $result['data'];
                    $message = '<div class="updated"><p>' . $result['message'] . '</p></div>';
                } else {
                    $message = '<div class="error"><p>' . $result['message'] . '</p></div>';
                }
            }
        }
    }
    
    // Handle repair action
    if (isset($_POST['repair_quiz'])) {
        // Verify nonce
        if (!isset($_POST['quiz_debug_nonce']) || !wp_verify_nonce($_POST['quiz_debug_nonce'], 'quiz_debug_action')) {
            $message = '<div class="error"><p>Security verification failed. Please try again.</p></div>';
        } else {
            // Get quiz ID
            $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
            
            if (!$quiz_id) {
                $message = '<div class="error"><p>Please enter a valid Quiz ID.</p></div>';
            } else {
                // Attempt to repair common issues
                $repaired = ph_repair_quiz_edit_issues($quiz_id);
                
                if ($repaired) {
                    $message = '<div class="updated"><p>Quiz repair completed. Please try editing the quiz again.</p></div>';
                } else {
                    $message = '<div class="error"><p>Could not repair quiz. Please check the error logs.</p></div>';
                }
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Quiz Debug Tools</h1>
        <?php echo $message; ?>
        
        <div class="card">
            <h2>Quiz Edit Debugger</h2>
            <p>Use this tool to diagnose issues with quiz editing functionality.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('quiz_debug_action', 'quiz_debug_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="quiz_id">Quiz ID</label></th>
                        <td>
                            <input type="number" id="quiz_id" name="quiz_id" value="" class="regular-text">
                            <p class="description">Enter the ID of the quiz you're having trouble editing.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="debug_quiz" class="button button-primary" value="Debug Quiz">
                    <input type="submit" name="repair_quiz" class="button" value="Attempt Repair">
                </p>
            </form>
        </div>
        
        <?php if ($quiz_data): ?>
        <div class="card">
            <h2>Quiz Data</h2>
            <pre style="background:#f8f8f8;padding:10px;overflow:auto;max-height:400px;"><?php print_r($quiz_data); ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Common Edit Issues</h2>
            <ul>
                <li><strong>Issue:</strong> Form resets when trying to save edits<br>
                <strong>Solution:</strong> Ensure proper nonce verification and form handling</li>
                
                <li><strong>Issue:</strong> Quiz data not loading in edit form<br>
                <strong>Solution:</strong> Check how quiz data is retrieved and populated in form fields</li>
                
                <li><strong>Issue:</strong> Unable to save quiz edits<br>
                <strong>Solution:</strong> Verify correct handling of POST data and database updates</li>
                
                <li><strong>Issue:</strong> Error messages not showing<br>
                <strong>Solution:</strong> Implement proper error handling and display</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Debug Log</h2>
            <?php
            $log_file = plugin_dir_path(dirname(__FILE__)) . 'debug-quiz-edit.log';
            if (file_exists($log_file)) {
                $log_content = file_get_contents($log_file);
                echo '<pre style="background:#f8f8f8;padding:10px;overflow:auto;max-height:300px;">' . esc_html($log_content) . '</pre>';
                echo '<p><a href="' . esc_url(add_query_arg('clear_log', '1')) . '" class="button">Clear Log</a></p>';
            } else {
                echo '<p>No debug log available yet. Try editing a quiz to generate log data.</p>';
            }
            
            // Clear log if requested
            if (isset($_GET['clear_log']) && current_user_can('manage_options')) {
                @unlink($log_file);
                echo '<div class="updated"><p>Log file cleared.</p></div>';
                echo '<p><a href="' . remove_query_arg('clear_log') . '">Refresh page</a></p>';
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Function to repair common issues with quiz editing
 */
function ph_repair_quiz_edit_issues($quiz_id) {
    if (empty($quiz_id)) {
        return false;
    }
    
    // Log repair attempt
    $log_file = plugin_dir_path(dirname(__FILE__)) . 'debug-quiz-edit.log';
    $log_data = "=== Quiz Repair Attempt for ID: $quiz_id at " . date('Y-m-d H:i:s') . " ===\n";
    
    try {
        // 1. Check quiz post exists
        $quiz = get_post($quiz_id);
        if (!$quiz) {
            $log_data .= "Error: Quiz post not found\n";
            file_put_contents($log_file, $log_data, FILE_APPEND);
            return false;
        }
        
        // 2. Check and repair quiz meta data
        $quiz_data = get_post_meta($quiz_id, '_quiz_data', true);
        if (empty($quiz_data)) {
            // Try to recreate quiz data from other meta fields
            $title = get_the_title($quiz_id);
            $description = get_post_meta($quiz_id, '_quiz_description', true);
            $status = get_post_status($quiz_id);
            
            // Create basic quiz data structure
            $new_quiz_data = array(
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'questions' => array(),
                'settings' => array(
                    'primary_color' => '#3498db',
                    'secondary_color' => '#2ecc71',
                    'button_style' => 'rounded',
                    'font_family' => 'inherit',
                )
            );
            
            // Save the rebuilt quiz data
            update_post_meta($quiz_id, '_quiz_data', $new_quiz_data);
            $log_data .= "Quiz data rebuilt with basic structure\n";
        } else {
            $log_data .= "Quiz data exists, validating structure...\n";
            
            // Ensure quiz data has all required properties
            $required_props = ['title', 'description', 'settings', 'questions'];
            $updated = false;
            
            foreach ($required_props as $prop) {
                if (!isset($quiz_data[$prop])) {
                    $quiz_data[$prop] = ($prop === 'settings' || $prop === 'questions') ? array() : '';
                    $updated = true;
                    $log_data .= "Added missing property: $prop\n";
                }
            }
            
            if ($updated) {
                update_post_meta($quiz_id, '_quiz_data', $quiz_data);
                $log_data .= "Updated quiz data with missing properties\n";
            }
        }
        
        // 3. Verify and repair post type
        if ($quiz->post_type !== 'product_hunt_quiz') {
            $log_data .= "Warning: Quiz has incorrect post type: {$quiz->post_type}\n";
            
            // Update the post type
            wp_update_post(array(
                'ID' => $quiz_id,
                'post_type' => 'product_hunt_quiz'
            ));
            
            $log_data .= "Post type updated to product_hunt_quiz\n";
        }
        
        // 4. Clear any transients that might be causing issues
        $transient_key = 'quiz_edit_' . $quiz_id;
        delete_transient($transient_key);
        $log_data .= "Cleared potential transients for this quiz\n";
        
        $log_data .= "Repair process completed successfully\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);
        return true;
        
    } catch (Exception $e) {
        $log_data .= "Error during repair: " . $e->getMessage() . "\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);
        return false;
    }
}
