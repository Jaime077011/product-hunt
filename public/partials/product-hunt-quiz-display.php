<?php
/**
 * Template for displaying a product quiz on the frontend
 *
 * @since      1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get quiz data
global $wpdb;
$quiz_id = absint($quiz_id); // Sanitize the quiz ID from shortcode

if ($quiz_id <= 0) {
    return '<p class="product-hunt-error">' . __('Invalid quiz ID.', 'product-hunt') . '</p>';
}

// Get quiz details from database
$quiz = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d AND status = 'published'",
    $quiz_id
));

if (!$quiz) {
    return '<p class="product-hunt-error">' . __('Quiz not found or not published.', 'product-hunt') . '</p>';
}

// Get questions for this quiz
$questions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d ORDER BY question_order ASC",
    $quiz_id
));

if (empty($questions)) {
    return '<p class="product-hunt-error">' . __('No questions found for this quiz.', 'product-hunt') . '</p>';
}

// Set CSS variables for customization
$quiz_settings = maybe_unserialize($quiz->settings);
$primary_color = isset($quiz_settings['primary_color']) ? sanitize_hex_color($quiz_settings['primary_color']) : '#3498db';
$secondary_color = isset($quiz_settings['secondary_color']) ? sanitize_hex_color($quiz_settings['secondary_color']) : '#2ecc71';
$button_style = isset($quiz_settings['button_style']) ? sanitize_text_field($quiz_settings['button_style']) : 'rounded';
$font_family = isset($quiz_settings['font_family']) ? sanitize_text_field($quiz_settings['font_family']) : 'inherit';

// Button radius based on style
$button_radius = '25px'; // Default rounded
if ($button_style === 'square') {
    $button_radius = '0';
} elseif ($button_style === 'rounded-corners') {
    $button_radius = '5px';
}

// Email capture settings
$email_capture = isset($quiz_settings['email_capture']) ? (bool)$quiz_settings['email_capture'] : true;
$email_required = isset($quiz_settings['email_required']) ? (bool)$quiz_settings['email_required'] : true;

// Process conditional logic rules
$conditional_logic = array();
$conditional_rules = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ph_conditional_logic WHERE quiz_id = %d",
    $quiz_id
));

foreach ($conditional_rules as $rule) {
    $conditional_logic[$rule->id] = array(
        'if_question' => $rule->if_question_id,
        'if_answer' => $rule->if_answer_id,
        'then_question' => $rule->then_question_id
    );
}
?>

<div id="product-hunt-quiz-<?php echo esc_attr($quiz_id); ?>" class="product-hunt-quiz-container">
    <!-- Add this at the top of the quiz container -->
    <div class="product-hunt-error-message"></div>
    
    <div class="product-hunt-quiz" data-quiz-id="<?php echo esc_attr($quiz_id); ?>" 
        style="--ph-primary-color: <?php echo esc_attr($primary_color); ?>;
               --ph-secondary-color: <?php echo esc_attr($secondary_color); ?>;
               --ph-button-radius: <?php echo esc_attr($button_radius); ?>;
               font-family: <?php echo esc_attr($font_family); ?>;">
        
        <?php if ($atts['title'] === 'yes'): ?>
            <div class="product-hunt-quiz-header">
                <h2 class="product-hunt-quiz-title"><?php echo esc_html($quiz->title); ?></h2>
                <?php if ($atts['description'] === 'yes' && !empty($quiz->description)): ?>
                    <div class="product-hunt-quiz-description"><?php echo wp_kses_post($quiz->description); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="product-hunt-quiz-progress">
            <div class="product-hunt-quiz-progress-bar"></div>
        </div>
        
        <?php
        // Loop through questions and create HTML structure
        foreach ($questions as $index => $question):
            $question_settings = maybe_unserialize($question->settings);
            $question_type = $question->question_type;
            $is_required = (bool)$question->is_required;
            
            // Get answers for this question
            $answers = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ph_answers WHERE question_id = %d ORDER BY answer_order ASC",
                $question->id
            ));
        ?>
            <div class="product-hunt-question" 
                 data-question-id="<?php echo esc_attr($question->id); ?>"
                 data-required="<?php echo $is_required ? '1' : '0'; ?>"
                 data-type="<?php echo esc_attr($question_type); ?>"
                 data-conditional-logic="<?php echo esc_attr(wp_json_encode($conditional_logic)); ?>">
                
                <h3 class="product-hunt-question-text">
                    <?php echo esc_html($question->question_text); ?>
                    <?php if ($is_required): ?>
                        <span class="product-hunt-question-required">*</span>
                    <?php endif; ?>
                </h3>
                
                <div class="product-hunt-answers">
                    <?php if ($question_type === 'multiple_choice'): ?>
                        <?php foreach ($answers as $answer): ?>
                            <div class="product-hunt-answer">
                                <input type="radio" 
                                       id="answer_<?php echo esc_attr($answer->id); ?>" 
                                       name="question_<?php echo esc_attr($question->id); ?>" 
                                       value="<?php echo esc_attr($answer->id); ?>">
                                <label class="product-hunt-answer-label" for="answer_<?php echo esc_attr($answer->id); ?>">
                                    <?php echo esc_html($answer->answer_text); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    
                    <?php elseif ($question_type === 'checkbox'): ?>
                        <?php foreach ($answers as $answer): ?>
                            <div class="product-hunt-answer">
                                <input type="checkbox" 
                                       id="answer_<?php echo esc_attr($answer->id); ?>" 
                                       name="question_<?php echo esc_attr($question->id); ?>[]" 
                                       value="<?php echo esc_attr($answer->id); ?>">
                                <label class="product-hunt-answer-label" for="answer_<?php echo esc_attr($answer->id); ?>">
                                    <?php echo esc_html($answer->answer_text); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    
                    <?php elseif ($question_type === 'text'): ?>
                        <div class="product-hunt-answer">
                            <input type="text" name="question_<?php echo esc_attr($question->id); ?>" placeholder="<?php esc_attr_e('Your answer...', 'product-hunt'); ?>">
                        </div>
                    
                    <?php elseif ($question_type === 'email'): ?>
                        <div class="product-hunt-answer">
                            <input type="email" name="question_<?php echo esc_attr($question->id); ?>" placeholder="<?php esc_attr_e('Your email...', 'product-hunt'); ?>">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-hunt-error">
                    <?php _e('This question requires an answer.', 'product-hunt'); ?>
                </div>
                
                <div class="product-hunt-navigation">
                    <?php if ($index > 0): ?>
                        <button type="button" class="product-hunt-button product-hunt-prev-button">
                            <?php _e('Previous', 'product-hunt'); ?>
                        </button>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    
                    <?php if ($index < count($questions) - 1): ?>
                        <button type="button" class="product-hunt-button product-hunt-next-button">
                            <?php _e('Next', 'product-hunt'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="product-hunt-button product-hunt-submit-button">
                            <?php _e('Get Results', 'product-hunt'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if ($email_capture): ?>
            <div class="product-hunt-email-capture">
                <h3 class="product-hunt-email-title">
                    <?php echo isset($quiz_settings['email_title']) ? esc_html($quiz_settings['email_title']) : __('Almost there!', 'product-hunt'); ?>
                </h3>
                
                <p class="product-hunt-email-description">
                    <?php echo isset($quiz_settings['email_description']) 
                        ? wp_kses_post($quiz_settings['email_description']) 
                        : __('Enter your email to see your personalized product recommendations.', 'product-hunt'); ?>
                </p>
                
                <form class="product-hunt-email-form">
                    <div class="product-hunt-email-field">
                        <input type="email" 
                               class="product-hunt-email-input" 
                               placeholder="<?php esc_attr_e('Your Email Address', 'product-hunt'); ?>" 
                               required>
                        <button type="submit" class="product-hunt-button">
                            <?php _e('Get Results', 'product-hunt'); ?>
                        </button>
                    </div>
                    
                    <div class="product-hunt-error product-hunt-email-error">
                        <?php _e('Please enter a valid email address.', 'product-hunt'); ?>
                    </div>
                    
                    <p class="product-hunt-email-privacy">
                        <?php echo isset($quiz_settings['privacy_text']) 
                            ? wp_kses_post($quiz_settings['privacy_text'])
                            : __('We respect your privacy and will never share your email address.', 'product-hunt'); ?>
                    </p>
                </form>
                
                <?php if (!$email_required): ?>
                    <p>
                        <a href="#" class="product-hunt-skip-email">
                            <?php _e('Skip and see results', 'product-hunt'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="product-hunt-loading">
            <div class="product-hunt-spinner"></div>
            <p><?php _e('Finding the perfect products for you...', 'product-hunt'); ?></p>
        </div>
        
        <div class="product-hunt-results">
            <h2 class="product-hunt-results-title">
                <?php echo isset($quiz_settings['results_title']) 
                    ? esc_html($quiz_settings['results_title']) 
                    : __('Your Personalized Recommendations', 'product-hunt'); ?>
            </h2>
            
            <div class="product-hunt-results-message">
                <?php echo isset($quiz_settings['results_description']) 
                    ? wp_kses_post($quiz_settings['results_description'])
                    : __('Based on your answers, we recommend these products for you:', 'product-hunt'); ?>
            </div>
            
            <div class="product-hunt-recommendations">
                <!-- Products will be dynamically added here via JavaScript -->
            </div>
        </div>
    </div>
</div>