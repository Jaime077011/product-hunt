<?php
/**
 * Admin template for adding or editing a quiz
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if we're editing an existing quiz
$quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing = $quiz_id > 0;

// Debug output
if (WP_DEBUG) {
    error_log('Loading quiz form: Quiz ID = ' . $quiz_id . ', Editing = ' . ($editing ? 'true' : 'false'));
}

// Get quiz data if editing
$quiz = null;
$questions = array();
if ($editing) {
    global $wpdb;
    
    // Get quiz data
    $quiz = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
        $quiz_id
    ));
    
    if (!$quiz) {
        wp_die(__('Quiz not found.', 'product-hunt'));
    }
    
    // Get quiz questions
    $questions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d ORDER BY question_order ASC",
        $quiz_id
    ));
    
    foreach ($questions as $question) {
        // Get answers for each question
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ph_answers WHERE question_id = %d ORDER BY answer_order ASC",
            $question->id
        ));
        
        foreach ($answers as $answer) {
            // Get mapped products for each answer
            $answer->products = $wpdb->get_results($wpdb->prepare(
                "SELECT pr.*, p.post_title as product_name, pm.meta_value as product_price, 
                 img.meta_value as product_image
                 FROM {$wpdb->prefix}ph_product_recommendations pr
                 JOIN {$wpdb->posts} p ON p.ID = pr.product_id
                 LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = pr.product_id AND pm.meta_key = '_price'
                 LEFT JOIN {$wpdb->postmeta} img ON img.post_id = pr.product_id AND img.meta_key = '_thumbnail_id'
                 WHERE pr.answer_id = %d",
                $answer->id
            ));
        }
        
        $question->answers = $answers;
    }
    
    if (WP_DEBUG) {
        error_log('Loaded quiz data: Questions = ' . count($questions));
    }
}

// Default values for new quiz
$quiz_title = $editing ? $quiz->title : '';
$quiz_description = $editing ? $quiz->description : '';
$quiz_category = $editing ? $quiz->category : '';
$quiz_status = $editing ? $quiz->status : 'draft';
$quiz_settings = $editing ? maybe_unserialize($quiz->settings) : array();

// Default style settings
$primary_color = isset($quiz_settings['primary_color']) ? $quiz_settings['primary_color'] : '#3498db';
$secondary_color = isset($quiz_settings['secondary_color']) ? $quiz_settings['secondary_color'] : '#2ecc71';
$button_style = isset($quiz_settings['button_style']) ? $quiz_settings['button_style'] : 'rounded';
$font_family = isset($quiz_settings['font_family']) ? $quiz_settings['font_family'] : '';

// Email capture settings
$email_capture = isset($quiz_settings['email_capture']) ? (bool)$quiz_settings['email_capture'] : true;
$email_required = isset($quiz_settings['email_required']) ? (bool)$quiz_settings['email_required'] : true;
$email_title = isset($quiz_settings['email_title']) ? $quiz_settings['email_title'] : __('Almost there!', 'product-hunt');
$email_description = isset($quiz_settings['email_description']) ? $quiz_settings['email_description'] : __('Enter your email to see your personalized product recommendations.', 'product-hunt');
$privacy_text = isset($quiz_settings['privacy_text']) ? $quiz_settings['privacy_text'] : __('We respect your privacy and will never share your email address.', 'product-hunt');

// Results settings
$results_title = isset($quiz_settings['results_title']) ? $quiz_settings['results_title'] : __('Your Personalized Recommendations', 'product-hunt');
$results_description = isset($quiz_settings['results_description']) ? $quiz_settings['results_description'] : __('Based on your answers, we recommend these products for you:', 'product-hunt');

// Advanced quiz settings
$time_limit = isset($quiz_settings['time_limit']) ? intval($quiz_settings['time_limit']) : 0;
$max_responses = isset($quiz_settings['max_responses']) ? intval($quiz_settings['max_responses']) : 0;
$show_progress = isset($quiz_settings['show_progress']) ? (bool)$quiz_settings['show_progress'] : true;

// Check for error messages
$error_type = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$error_message = '';

switch ($error_type) {
    case 'title_required':
        $error_message = __('Quiz title is required.', 'product-hunt');
        break;
    case 'database_error':
        $error_message = __('An error occurred while saving the quiz. Please try again.', 'product-hunt');
        break;
}
?>

<div class="wrap product-hunt-admin-wrap">
    <div class="product-hunt-admin-header">
        <h1><?php echo $editing ? __('Edit Quiz', 'product-hunt') : __('Add New Quiz', 'product-hunt'); ?></h1>
    </div>
    
    <?php if (!empty($error_message)) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == '1') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Quiz saved successfully.', 'product-hunt'); ?></p>
        </div>
    <?php endif; ?>
    
    <form id="product-hunt-quiz-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('save_product_hunt_quiz', 'product_hunt_quiz_nonce'); ?>
        <input type="hidden" name="action" value="save_product_hunt_quiz">
        <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id); ?>">
        <input type="hidden" name="edit_mode" value="<?php echo $editing ? '1' : '0'; ?>">
        
        <div class="product-hunt-quiz-builder">
            <div class="product-hunt-quiz-settings">
                <h2><?php _e('Quiz Settings', 'product-hunt'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="quiz-title"><?php _e('Quiz Title', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="quiz-title" name="quiz_title" value="<?php echo esc_attr($quiz_title); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="quiz-description"><?php _e('Quiz Description', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <textarea id="quiz-description" name="quiz_description" class="large-text" rows="3"><?php echo esc_textarea($quiz_description); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="quiz-category"><?php _e('Category', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="quiz-category" name="quiz_category" value="<?php echo esc_attr($quiz_category); ?>" class="regular-text">
                            <p class="description"><?php _e('Optional category for organizing your quizzes.', 'product-hunt'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="quiz-status"><?php _e('Status', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <select id="quiz-status" name="quiz_status">
                                <option value="draft" <?php selected($quiz_status, 'draft'); ?>><?php _e('Draft', 'product-hunt'); ?></option>
                                <option value="published" <?php selected($quiz_status, 'published'); ?>><?php _e('Published', 'product-hunt'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <hr>
                
                <h3><?php _e('Email Capture Settings', 'product-hunt'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email-capture"><?php _e('Enable Email Capture', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="email-capture" name="quiz_settings[email_capture]" value="1" <?php checked($email_capture); ?>>
                            <label for="email-capture"><?php _e('Ask for email before showing results', 'product-hunt'); ?></label>
                        </td>
                    </tr>
                    <tr class="email-settings <?php echo !$email_capture ? 'hidden' : ''; ?>">
                        <th scope="row">
                            <label for="email-required"><?php _e('Email Required', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="email-required" name="quiz_settings[email_required]" value="1" <?php checked($email_required); ?>>
                            <label for="email-required"><?php _e('Require email to show results', 'product-hunt'); ?></label>
                        </td>
                    </tr>
                    <tr class="email-settings <?php echo !$email_capture ? 'hidden' : ''; ?>">
                        <th scope="row">
                            <label for="email-title"><?php _e('Email Form Title', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="email-title" name="quiz_settings[email_title]" value="<?php echo esc_attr($email_title); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr class="email-settings <?php echo !$email_capture ? 'hidden' : ''; ?>">
                        <th scope="row">
                            <label for="email-description"><?php _e('Email Form Description', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <textarea id="email-description" name="quiz_settings[email_description]" class="large-text" rows="2"><?php echo esc_textarea($email_description); ?></textarea>
                        </td>
                    </tr>
                    <tr class="email-settings <?php echo !$email_capture ? 'hidden' : ''; ?>">
                        <th scope="row">
                            <label for="privacy-text"><?php _e('Privacy Notice', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <textarea id="privacy-text" name="quiz_settings[privacy_text]" class="large-text" rows="2"><?php echo esc_textarea($privacy_text); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <hr>
                
                <h3><?php _e('Results Display Settings', 'product-hunt'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="results-title"><?php _e('Results Title', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="results-title" name="quiz_settings[results_title]" value="<?php echo esc_attr($results_title); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="results-description"><?php _e('Results Description', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <textarea id="results-description" name="quiz_settings[results_description]" class="large-text" rows="2"><?php echo esc_textarea($results_description); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <hr>
                
                <h3><?php _e('Advanced Settings', 'product-hunt'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="time-limit"><?php _e('Time Limit (minutes)', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="time-limit" name="quiz_settings[time_limit]" value="<?php echo esc_attr($time_limit); ?>" class="small-text" min="0" step="1">
                            <p class="description"><?php _e('Set to 0 for no time limit.', 'product-hunt'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max-responses"><?php _e('Maximum Responses', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max-responses" name="quiz_settings[max_responses]" value="<?php echo esc_attr($max_responses); ?>" class="small-text" min="0" step="1">
                            <p class="description"><?php _e('Maximum number of responses allowed. Set to 0 for unlimited.', 'product-hunt'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="show-progress"><?php _e('Show Progress Bar', 'product-hunt'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="show-progress" name="quiz_settings[show_progress]" value="1" <?php checked($show_progress); ?>>
                            <label for="show-progress"><?php _e('Show progress bar during quiz', 'product-hunt'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <hr>
                
                <h2><?php _e('Questions', 'product-hunt'); ?></h2>
                
                <div id="quiz-questions-container">
                    <?php 
                    if (!empty($questions)) {
                        foreach ($questions as $question) {
                            // Include the question template with data
                            include(plugin_dir_path(__FILE__) . 'product-hunt-admin-question-template.php');
                        }
                    }
                    ?>
                </div>
                
                <div class="product-hunt-add-question">
                    <button type="button" id="add-new-question" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span> <?php _e('Add New Question', 'product-hunt'); ?>
                    </button>
                </div>
            </div>
            
            <div class="product-hunt-form-actions">
                <button type="submit" class="button button-primary button-large">
                    <?php echo $editing ? __('Update Quiz', 'product-hunt') : __('Create Quiz', 'product-hunt'); ?>
                </button>
                
                <a href="<?php echo admin_url('admin.php?page=product-hunt-quizzes'); ?>" class="button button-large">
                    <?php _e('Cancel', 'product-hunt'); ?>
                </a>
                
                <?php if ($editing && $quiz_status === 'published'): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('preview' => 'true', 'quiz_id' => $quiz_id), get_home_url())); ?>" target="_blank" class="button button-secondary button-large">
                        <?php _e('Preview Quiz', 'product-hunt'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Question template for cloning -->
<script type="text/html" id="question-template">
    <div id="{question_id}" class="product-hunt-question-container">
        <div class="product-hunt-question-handle">
            <span class="dashicons dashicons-menu"></span>
            <span class="question-title"><?php _e('New Question', 'product-hunt'); ?></span>
            <div class="product-hunt-question-actions">
                <button type="button" class="button toggle-conditional-logic" data-question-id="{question_id}">
                    <span class="dashicons dashicons-randomize"></span> <?php _e('Logic', 'product-hunt'); ?>
                </button>
                <button type="button" class="button delete-question">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        
        <div class="product-hunt-question-content">
            <input type="hidden" name="question[{question_id}][id]" value="">
            <input type="hidden" name="question[{question_id}][order]" class="question-order" value="0">
            
            <div class="product-hunt-question-row">
                <label><?php _e('Question Text', 'product-hunt'); ?></label>
                <input type="text" name="question[{question_id}][text]" class="large-text question-text" value="">
            </div>
            
            <div class="product-hunt-question-row">
                <div class="product-hunt-question-col">
                    <label><?php _e('Question Type', 'product-hunt'); ?></label>
                    <select name="question[{question_id}][type]" class="question-type-selector">
                        <option value="multiple_choice"><?php _e('Multiple Choice (Single Answer)', 'product-hunt'); ?></option>
                        <option value="checkbox"><?php _e('Checkboxes (Multiple Answers)', 'product-hunt'); ?></option>
                        <option value="text"><?php _e('Text Input', 'product-hunt'); ?></option>
                        <option value="email"><?php _e('Email Input', 'product-hunt'); ?></option>
                    </select>
                </div>
                
                <div class="product-hunt-question-col">
                    <label><?php _e('Required', 'product-hunt'); ?></label>
                    <input type="checkbox" name="question[{question_id}][required]" value="1" checked>
                </div>
            </div>
            
            <div class="product-hunt-conditional-logic" style="display: none;">
                <div class="product-hunt-conditional-logic-title"><?php _e('Conditional Logic', 'product-hunt'); ?></div>
                <p class="description"><?php _e('Show this question based on answers to previous questions.', 'product-hunt'); ?></p>
                
                <div class="conditional-rules-container">
                    <!-- Rules will be added here -->
                </div>
                    
                <button type="button" class="button add-logic-rule" data-question-id="{question_id}">
                    <span class="dashicons dashicons-plus"></span> <?php _e('Add Logic Rule', 'product-hunt'); ?>
                </button>
            </div>
            
            <div class="answers-section">
                <h4><?php _e('Answer Options', 'product-hunt'); ?></h4>
                <div class="product-hunt-answers-list">
                    <!-- Answer options will be added here -->
                </div>
                
                <button type="button" class="button add-new-answer" data-question-id="{question_id}">
                    <span class="dashicons dashicons-plus-alt"></span> <?php _e('Add Answer Option', 'product-hunt'); ?>
                </button>
            </div>
        </div>
    </div>
</script>

<!-- Answer template for cloning -->
<script type="text/html" id="answer-template">
    <div id="{answer_id}" class="product-hunt-answer-container">
        <div class="product-hunt-answer-row">
            <span class="product-hunt-answer-handle dashicons dashicons-menu"></span>
            
            <div class="product-hunt-answer-content">
                <input type="hidden" name="answer[{question_id}][{answer_id}][id]" value="">
                <input type="hidden" name="answer[{question_id}][{answer_id}][order]" class="answer-order" value="0">
                
                <input type="text" name="answer[{question_id}][{answer_id}][text]" class="regular-text answer-text" value="" placeholder="<?php esc_attr_e('Answer text...', 'product-hunt'); ?>">
            </div>
            
            <div class="product-hunt-answer-actions">
                <button type="button" class="button toggle-product-mapping" title="<?php esc_attr_e('Map Products', 'product-hunt'); ?>">
                    <span class="dashicons dashicons-cart"></span>
                </button>
                <button type="button" class="button delete-answer" title="<?php esc_attr_e('Delete Answer', 'product-hunt'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        
        <div class="product-hunt-product-mapping" style="display: none;">
            <h4><?php _e('Product Recommendations for this Answer', 'product-hunt'); ?></h4>
            <p class="description"><?php _e('Search and select products to recommend when this answer is chosen.', 'product-hunt'); ?></p>
            
            <div class="product-hunt-product-search">
                <input type="text" class="product-search-input" placeholder="<?php esc_attr_e('Search by product name, SKU...', 'product-hunt'); ?>">
                <div class="product-search-results"></div>
            </div>
            
            <div class="selected-products">
                <!-- Selected products will be added here -->
            </div>
            
            <div class="product-weights-description">
                <p class="description"><?php _e('Weight determines the strength of the recommendation. Higher weights mean the product is more likely to be recommended.', 'product-hunt'); ?></p>
            </div>
        </div>
    </div>
</script>

<!-- Conditional rule template for cloning -->
<script type="text/html" id="conditional-rule-template">
    <div id="{rule_id}" class="product-hunt-logic-rule">
        <input type="hidden" name="logic[{question_id}][{rule_id}][id]" value="">
        
        <div class="logic-rule-content">
            <select name="logic[{question_id}][{rule_id}][if_question]" class="condition-question-selector">
                <option value=""><?php _e('If answer to question', 'product-hunt'); ?></option>
                <!-- Questions will be populated dynamically -->
            </select>
            
            <select name="logic[{question_id}][{rule_id}][if_answer]" class="condition-answer-selector">
                <option value=""><?php _e('is', 'product-hunt'); ?></option>
                <!-- Answers will be populated dynamically based on selected question -->
            </select>
            
            <select name="logic[{question_id}][{rule_id}][comparison]" class="condition-comparison-selector">
                <option value="equals"><?php _e('equals', 'product-hunt'); ?></option>
                <option value="not_equals"><?php _e('does not equal', 'product-hunt'); ?></option>
                <option value="contains"><?php _e('contains', 'product-hunt'); ?></option>
            </select>
            
            <button type="button" class="button delete-logic-rule">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
    </div>
</script>

<script>
    jQuery(document).ready(function($) {
        console.log("Quiz admin form initialized - Edit mode: <?php echo $editing ? 'true' : 'false'; ?>, Quiz ID: <?php echo $quiz_id; ?>");
        
        // Initialize tab navigation only
        if (!$('.product-hunt-tabs a.active').length) {
            $('.product-hunt-tabs a:first').addClass('active');
            $('.product-hunt-tab-content:first').addClass('active');
        }
    });
</script>