<?php
/**
 * Template for a question in the quiz builder
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get question data if editing
$question_id = isset($question) ? $question->id : '';
$question_text = isset($question) ? $question->question_text : '';
$question_type = isset($question) ? $question->question_type : 'multiple_choice';
$question_order = isset($question) ? $question->question_order : 0;
$question_required = isset($question) ? (bool)$question->is_required : true;
$question_settings = isset($question) ? maybe_unserialize($question->settings) : array();
$question_dom_id = !empty($question_id) ? 'question-' . $question_id : '{question_id}';
?>

<div id="<?php echo esc_attr($question_dom_id); ?>" class="product-hunt-question-container">
    <div class="product-hunt-question-handle">
        <span class="dashicons dashicons-menu"></span>
        <span class="question-title"><?php echo !empty($question_text) ? esc_html(substr($question_text, 0, 30) . (strlen($question_text) > 30 ? '...' : '')) : __('New Question', 'product-hunt'); ?></span>
        <div class="product-hunt-question-actions">
            <button type="button" class="button delete-question">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
    </div>
    
    <div class="product-hunt-question-content">
        <div class="product-hunt-question-row">
            <div class="product-hunt-question-col">
                <label for="<?php echo esc_attr($question_dom_id); ?>-text"><?php _e('Question Text', 'product-hunt'); ?></label>
                <input type="text" id="<?php echo esc_attr($question_dom_id); ?>-text" 
                       name="question[<?php echo esc_attr($question_dom_id); ?>][text]" 
                       value="<?php echo esc_attr($question_text); ?>" 
                       class="regular-text question-text" placeholder="<?php esc_attr_e('Enter question here...', 'product-hunt'); ?>">
                <input type="hidden" name="question[<?php echo esc_attr($question_dom_id); ?>][id]" value="<?php echo esc_attr($question_id); ?>">
                <input type="hidden" name="question[<?php echo esc_attr($question_dom_id); ?>][order]" value="<?php echo esc_attr($question_order); ?>" class="question-order">
            </div>
            
            <div class="product-hunt-question-col">
                <label for="<?php echo esc_attr($question_dom_id); ?>-type"><?php _e('Question Type', 'product-hunt'); ?></label>
                <select id="<?php echo esc_attr($question_dom_id); ?>-type" 
                        name="question[<?php echo esc_attr($question_dom_id); ?>][type]" 
                        class="question-type-selector">
                    <option value="multiple_choice" <?php selected($question_type, 'multiple_choice'); ?>><?php _e('Multiple Choice (Single Selection)', 'product-hunt'); ?></option>
                    <option value="checkbox" <?php selected($question_type, 'checkbox'); ?>><?php _e('Checkboxes (Multiple Selection)', 'product-hunt'); ?></option>
                    <option value="text" <?php selected($question_type, 'text'); ?>><?php _e('Text Input', 'product-hunt'); ?></option>
                    <option value="email" <?php selected($question_type, 'email'); ?>><?php _e('Email Input', 'product-hunt'); ?></option>
                </select>
            </div>
            
            <div class="product-hunt-question-col">
                <label for="<?php echo esc_attr($question_dom_id); ?>-required">
                    <input type="checkbox" id="<?php echo esc_attr($question_dom_id); ?>-required" 
                           name="question[<?php echo esc_attr($question_dom_id); ?>][required]" 
                           value="1" <?php checked($question_required); ?>>
                    <?php _e('Required', 'product-hunt'); ?>
                </label>
            </div>
        </div>
        
        <div class="answers-section <?php echo ($question_type === 'text' || $question_type === 'email') ? 'hidden' : ''; ?>">
            <h4><?php _e('Answer Options', 'product-hunt'); ?></h4>
            
            <div class="product-hunt-answers-list">
                <?php 
                // Display answers if they exist
                if (isset($question) && $question_id) {
                    global $wpdb;
                    $answers = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ph_answers WHERE question_id = %d ORDER BY answer_order ASC",
                        $question_id
                    ));
                    
                    if ($answers) {
                        foreach ($answers as $answer) {
                            // Include the answer template with data
                            include(plugin_dir_path(__FILE__) . 'product-hunt-admin-answer-template.php');
                        }
                    }
                }
                ?>
            </div>
            
            <button type="button" class="button add-new-answer" data-question-id="<?php echo esc_attr($question_dom_id); ?>">
                <span class="dashicons dashicons-plus"></span> <?php _e('Add Answer Option', 'product-hunt'); ?>
            </button>
        </div>
    </div>
</div>
