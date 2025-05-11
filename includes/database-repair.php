<?php
/**
 * Database Repair Tool for Product Hunt Plugin
 *
 * Provides utilities to repair database issues that may cause quiz editing
 * and product assignment problems.
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add submenu page for Database Repair
 */
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
add_action('admin_menu', 'ph_add_repair_page');

/**
 * Render the Database Repair admin page
 */
function ph_database_repair_page() {
    $message = '';
    $error = '';
    
    // Handle repair database action
    if (isset($_POST['repair_database']) && wp_verify_nonce($_POST['ph_repair_nonce'], 'ph_repair_database')) {
        $results = ph_repair_database_tables();
        $message = '<div class="updated"><p>Database repair completed. Results:</p><pre>' . print_r($results, true) . '</pre></div>';
    }
    
    // Handle fix quiz data action
    if (isset($_POST['fix_quiz_data']) && wp_verify_nonce($_POST['ph_repair_nonce'], 'ph_repair_database')) {
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        if ($quiz_id > 0) {
            $result = ph_fix_quiz_data($quiz_id);
            if ($result['success']) {
                $message = '<div class="updated"><p>' . $result['message'] . '</p></div>';
            } else {
                $error = '<div class="error"><p>' . $result['message'] . '</p></div>';
            }
        } else {
            $error = '<div class="error"><p>Please select a quiz to fix.</p></div>';
        }
    }
    
    // Handle orphaned records cleanup
    if (isset($_POST['clean_orphans']) && wp_verify_nonce($_POST['ph_repair_nonce'], 'ph_repair_database')) {
        $result = ph_clean_orphaned_records();
        $message = '<div class="updated"><p>' . $result['message'] . '</p>';
        if (!empty($result['details'])) {
            $message .= '<ul>';
            foreach ($result['details'] as $detail) {
                $message .= '<li>' . $detail . '</li>';
            }
            $message .= '</ul>';
        }
        $message .= '</div>';
    }
    
    // Get all quizzes for the dropdown
    global $wpdb;
    $quizzes = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}ph_quizzes ORDER BY id DESC");
    
    // Count records in each table
    $tables_data = ph_get_tables_info();
    
    ?>
    <div class="wrap">
        <h1>Product Hunt - Database Repair Tool</h1>
        
        <?php echo $message; ?>
        <?php echo $error; ?>
        
        <div class="card">
            <h2>Database Tables Information</h2>
            <p>Current state of the database tables:</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Records</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables_data as $table => $info) : ?>
                        <tr>
                            <td><?php echo esc_html($table); ?></td>
                            <td><?php echo esc_html($info['count']); ?></td>
                            <td>
                                <?php if ($info['exists']) : ?>
                                    <span style="color:green;">✓ Exists</span>
                                <?php else : ?>
                                    <span style="color:red;">✗ Missing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card" style="margin-top:20px;">
            <h2>Repair Database Tables</h2>
            <p>This tool will attempt to repair the Product Hunt database tables if they are corrupted.</p>
            
            <form method="post">
                <?php wp_nonce_field('ph_repair_database', 'ph_repair_nonce'); ?>
                <p class="submit">
                    <input type="submit" name="repair_database" class="button button-primary" value="Repair Database Tables">
                </p>
            </form>
        </div>
        
        <div class="card" style="margin-top:20px;">
            <h2>Fix Quiz Data</h2>
            <p>This tool will attempt to fix corrupted data for a specific quiz, including questions, answers, and product mappings.</p>
            
            <form method="post">
                <?php wp_nonce_field('ph_repair_database', 'ph_repair_nonce'); ?>
                
                <select name="quiz_id">
                    <option value="0">-- Select a Quiz --</option>
                    <?php foreach ($quizzes as $quiz) : ?>
                        <option value="<?php echo esc_attr($quiz->id); ?>">
                            <?php echo esc_html("#" . $quiz->id . " - " . $quiz->title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <p class="submit">
                    <input type="submit" name="fix_quiz_data" class="button button-primary" value="Fix Quiz Data">
                </p>
            </form>
        </div>
        
        <div class="card" style="margin-top:20px;">
            <h2>Clean Orphaned Records</h2>
            <p>This tool will remove orphaned records from the database, such as answers without questions or product mappings without answers.</p>
            
            <form method="post">
                <?php wp_nonce_field('ph_repair_database', 'ph_repair_nonce'); ?>
                <p class="submit">
                    <input type="submit" name="clean_orphans" class="button button-primary" value="Clean Orphaned Records">
                </p>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Repair database tables
 * 
 * @return array Results of the repair operations
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
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        if ($table_exists) {
            // Try to repair the table
            $repair_result = $wpdb->get_results("REPAIR TABLE $table");
            $results[$table] = $repair_result;
            
            // Also optimize the table
            $wpdb->query("OPTIMIZE TABLE $table");
        } else {
            // Table doesn't exist, try to recreate it
            $results[$table] = array('status' => 'Table does not exist');
            
            // Add table recreation logic here if needed
            // This would require knowing the table schema
        }
    }
    
    return $results;
}

/**
 * Get information about database tables
 * 
 * @return array Table information
 */
function ph_get_tables_info() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'ph_quizzes',
        $wpdb->prefix . 'ph_questions',
        $wpdb->prefix . 'ph_answers',
        $wpdb->prefix . 'ph_product_recommendations',
        $wpdb->prefix . 'ph_quiz_results'
    );
    
    $info = array();
    
    foreach ($tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0;
        
        $info[$table] = array(
            'exists' => $exists,
            'count' => $count
        );
    }
    
    return $info;
}

/**
 * Fix quiz data for a specific quiz
 * 
 * @param int $quiz_id The quiz ID to fix
 * @return array Status and message
 */
function ph_fix_quiz_data($quiz_id) {
    global $wpdb;
    
    // Check if quiz exists
    $quiz = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
        $quiz_id
    ));
    
    if (!$quiz) {
        return array(
            'success' => false,
            'message' => "Quiz with ID $quiz_id not found."
        );
    }
    
    // Create a backup of the quiz data
    $backup = ph_backup_quiz_data($quiz_id);
    
    if (!$backup['success']) {
        return array(
            'success' => false,
            'message' => "Could not back up quiz data. Fix aborted."
        );
    }
    
    // Fix question ordering
    $questions = $wpdb->get_results($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d ORDER BY question_order",
        $quiz_id
    ));
    
    foreach ($questions as $index => $question) {
        $wpdb->update(
            $wpdb->prefix . 'ph_questions',
            array('question_order' => $index + 1),
            array('id' => $question->id),
            array('%d'),
            array('%d')
        );
    }
    
    // Fix answer ordering for each question
    foreach ($questions as $question) {
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ph_answers WHERE question_id = %d ORDER BY answer_order",
            $question->id
        ));
        
        foreach ($answers as $index => $answer) {
            $wpdb->update(
                $wpdb->prefix . 'ph_answers',
                array('answer_order' => $index + 1),
                array('id' => $answer->id),
                array('%d'),
                array('%d')
            );
        }
    }
    
    // Check for duplicate product entries
    $removed_duplicates = 0;
    $questions_fixed = count($questions);
    $answers_fixed = 0;
    
    foreach ($questions as $question) {
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ph_answers WHERE question_id = %d",
            $question->id
        ));
        
        $answers_fixed += count($answers);
        
        foreach ($answers as $answer) {
            // Find duplicate product mappings
            $mappings = $wpdb->get_results($wpdb->prepare(
                "SELECT product_id, COUNT(*) as count, MIN(id) as keep_id 
                FROM {$wpdb->prefix}ph_product_recommendations 
                WHERE answer_id = %d 
                GROUP BY product_id 
                HAVING COUNT(*) > 1",
                $answer->id
            ));
            
            foreach ($mappings as $mapping) {
                // Delete duplicate entries, keeping only one
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}ph_product_recommendations 
                    WHERE answer_id = %d AND product_id = %d AND id != %d",
                    $answer->id,
                    $mapping->product_id,
                    $mapping->keep_id
                ));
                
                $removed_duplicates += $mapping->count - 1;
            }
        }
    }
    
    return array(
        'success' => true,
        'message' => sprintf(
            "Quiz data fixed successfully. Fixed %d questions, %d answers, and removed %d duplicate product mappings.",
            $questions_fixed,
            $answers_fixed,
            $removed_duplicates
        )
    );
}

/**
 * Backup quiz data
 * 
 * @param int $quiz_id The quiz ID to back up
 * @return array Status and backup data
 */
function ph_backup_quiz_data($quiz_id) {
    global $wpdb;
    
    try {
        // Get quiz data
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
            $quiz_id
        ), ARRAY_A);
        
        if (!$quiz) {
            return array(
                'success' => false,
                'message' => "Quiz not found"
            );
        }
        
        // Get questions
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d",
            $quiz_id
        ), ARRAY_A);
        
        $question_ids = wp_list_pluck($questions, 'id');
        $answers = array();
        $product_mappings = array();
        
        // Get answers for each question
        if (!empty($question_ids)) {
            $question_ids_str = implode(',', array_map('intval', $question_ids));
            
            $answers = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}ph_answers WHERE question_id IN ($question_ids_str)"
            , ARRAY_A);
            
            // Get product mappings for each answer
            $answer_ids = wp_list_pluck($answers, 'id');
            
            if (!empty($answer_ids)) {
                $answer_ids_str = implode(',', array_map('intval', $answer_ids));
                
                $product_mappings = $wpdb->get_results(
                    "SELECT * FROM {$wpdb->prefix}ph_product_recommendations WHERE answer_id IN ($answer_ids_str)"
                , ARRAY_A);
            }
        }
        
        // Store backup data
        $backup = array(
            'timestamp' => current_time('mysql'),
            'quiz' => $quiz,
            'questions' => $questions,
            'answers' => $answers,
            'product_mappings' => $product_mappings
        );
        
        // Save backup to file
        $backup_dir = WP_CONTENT_DIR . '/product-hunt-backups';
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_file = $backup_dir . '/quiz-' . $quiz_id . '-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents($backup_file, json_encode($backup));
        
        // Also store in transient for short-term access
        set_transient('ph_quiz_backup_' . $quiz_id, $backup, 24 * HOUR_IN_SECONDS);
        
        return array(
            'success' => true,
            'message' => "Backup created successfully",
            'backup' => $backup
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => "Failed to create backup: " . $e->getMessage()
        );
    }
}

/**
 * Clean orphaned records in the database
 * 
 * @return array Status and details
 */
function ph_clean_orphaned_records() {
    global $wpdb;
    
    $details = array();
    
    // 1. Remove questions without a valid quiz
    $result = $wpdb->query("
        DELETE q FROM {$wpdb->prefix}ph_questions q
        LEFT JOIN {$wpdb->prefix}ph_quizzes qz ON q.quiz_id = qz.id
        WHERE qz.id IS NULL
    ");
    $details[] = "Removed $result orphaned questions without a valid quiz.";
    
    // 2. Remove answers without a valid question
    $result = $wpdb->query("
        DELETE a FROM {$wpdb->prefix}ph_answers a
        LEFT JOIN {$wpdb->prefix}ph_questions q ON a.question_id = q.id
        WHERE q.id IS NULL
    ");
    $details[] = "Removed $result orphaned answers without a valid question.";
    
    // 3. Remove product recommendations without a valid answer
    $result = $wpdb->query("
        DELETE pr FROM {$wpdb->prefix}ph_product_recommendations pr
        LEFT JOIN {$wpdb->prefix}ph_answers a ON pr.answer_id = a.id
        WHERE a.id IS NULL
    ");
    $details[] = "Removed $result orphaned product recommendations without a valid answer.";
    
    // 4. Remove product recommendations with invalid products
    $result = $wpdb->query("
        DELETE pr FROM {$wpdb->prefix}ph_product_recommendations pr
        LEFT JOIN {$wpdb->posts} p ON pr.product_id = p.ID
        WHERE p.ID IS NULL OR p.post_type != 'product'
    ");
    $details[] = "Removed $result product recommendations with invalid products.";
    
    return array(
        'success' => true,
        'message' => "Orphaned records cleanup completed successfully.",
        'details' => $details
    );
}
