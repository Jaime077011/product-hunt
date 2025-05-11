<?php
/**
 * Template for previewing a quiz
 *
 * @since      1.0.0
 */

// Get the quiz ID from the URL
$quiz_id = intval($_GET['preview_quiz']);

// Get quiz details from database
global $wpdb;
$quiz = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ph_quizzes WHERE id = %d",
    $quiz_id
));

if (!$quiz) {
    wp_die(__('Quiz not found.', 'product-hunt'));
}

// Get questions for this quiz
$questions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ph_questions WHERE quiz_id = %d ORDER BY question_order ASC",
    $quiz_id
));

// Set CSS variables for customization
$quiz_settings = maybe_unserialize($quiz->settings);
$primary_color = isset($quiz_settings['primary_color']) ? sanitize_hex_color($quiz_settings['primary_color']) : '#3498db';
$secondary_color = isset($quiz_settings['secondary_color']) ? sanitize_hex_color($quiz_settings['secondary_color']) : '#2ecc71';
$button_style = isset($quiz_settings['button_style']) ? sanitize_text_field($quiz_settings['button_style']) : 'rounded';
$font_family = isset($quiz_settings['font_family']) ? sanitize_text_field($quiz_settings['font_family']) : 'inherit';
$time_limit = isset($quiz_settings['time_limit']) ? intval($quiz_settings['time_limit']) : 0;
$show_progress = isset($quiz_settings['show_progress']) ? (bool)$quiz_settings['show_progress'] : true;

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

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="product-hunt-preview-header">
            <h1><?php _e('Quiz Preview', 'product-hunt'); ?></h1>
            <p class="preview-notice"><?php _e('This is a preview of your quiz. You can use this page to test how the quiz will look and function before publishing it on your site.', 'product-hunt'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=product-hunt-add-quiz&id=' . $quiz_id); ?>" class="button"><?php _e('Back to Editor', 'product-hunt'); ?></a>
        </div>

        <!-- Quiz Container -->
        <div class="product-hunt-quiz" data-quiz-id="<?php echo esc_attr($quiz_id); ?>" 
            data-email-capture="<?php echo $email_capture ? '1' : '0'; ?>" 
            data-email-required="<?php echo $email_required ? '1' : '0'; ?>"
            data-conditional-logic='<?php echo esc_attr(wp_json_encode($conditional_logic)); ?>'
            data-time-limit="<?php echo esc_attr($time_limit); ?>"
            style="--ph-primary-color: <?php echo esc_attr($primary_color); ?>;
                --ph-secondary-color: <?php echo esc_attr($secondary_color); ?>;
                --ph-button-radius: <?php echo esc_attr($button_radius); ?>;
                font-family: <?php echo esc_attr($font_family); ?>;">

            <div class="product-hunt-quiz-header">
                <h2 class="product-hunt-quiz-title"><?php echo esc_html($quiz->title); ?></h2>
                <?php if (!empty($quiz->description)): ?>
                    <div class="product-hunt-quiz-description"><?php echo wp_kses_post($quiz->description); ?></div>
                <?php endif; ?>
            </div>
            
            <?php if ($show_progress): ?>
            <div class="product-hunt-quiz-progress">
                <div class="product-hunt-quiz-progress-bar"></div>
            </div>
            <?php endif; ?>
            
            <?php if ($time_limit > 0): ?>
            <div class="product-hunt-quiz-timer">
                <div class="product-hunt-timer-icon">⏱️</div>
                <div class="product-hunt-timer-display">
                    <span id="quiz-minutes"><?php echo sprintf('%02d', $time_limit); ?></span>:<span id="quiz-seconds">00</span>
                </div>
            </div>
            <?php endif; ?>
            
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
                
                <div class="product-hunt-recommendations"></div>

                <div class="product-hunt-preview-results">
                    <p class="preview-notice"><?php _e('This is a preview. Product recommendations would appear here in a live quiz.', 'product-hunt'); ?></p>
                    <div class="preview-products">
                        <div class="preview-product">
                            <div class="preview-product-image"></div>
                            <h3 class="preview-product-title"><?php _e('Sample Product 1', 'product-hunt'); ?></h3>
                            <p class="preview-product-price"><?php _e('$49.99', 'product-hunt'); ?></p>
                            <button class="product-hunt-button"><?php _e('View Product', 'product-hunt'); ?></button>
                        </div>
                        <div class="preview-product">
                            <div class="preview-product-image"></div>
                            <h3 class="preview-product-title"><?php _e('Sample Product 2', 'product-hunt'); ?></h3>
                            <p class="preview-product-price"><?php _e('$29.99', 'product-hunt'); ?></p>
                            <button class="product-hunt-button"><?php _e('View Product', 'product-hunt'); ?></button>
                        </div>
                        <div class="preview-product">
                            <div class="preview-product-image"></div>
                            <h3 class="preview-product-title"><?php _e('Sample Product 3', 'product-hunt'); ?></h3>
                            <p class="preview-product-price"><?php _e('$39.99', 'product-hunt'); ?></p>
                            <button class="product-hunt-button"><?php _e('View Product', 'product-hunt'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    /* Preview specific styles */
    .product-hunt-preview-header {
        margin-bottom: 30px;
        padding: 20px;
        background: #f0f0f1;
        border-left: 4px solid #2271b1;
    }
    
    .product-hunt-preview-header h1 {
        margin-top: 0;
    }
    
    .preview-notice {
        color: #646970;
        font-style: italic;
    }
    
    .product-hunt-preview-results {
        margin-top: 30px;
    }
    
    .preview-products {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .preview-product {
        border: 1px solid #dcdcde;
        border-radius: 4px;
        padding: 15px;
        text-align: center;
    }
    
    .preview-product-image {
        width: 150px;
        height: 150px;
        background-color: #f0f0f1;
        margin: 0 auto 15px;
        border-radius: 4px;
    }
    
    .preview-product-title {
        margin: 0 0 5px;
    }
    
    .preview-product-price {
        margin: 0 0 15px;
        font-weight: bold;
    }
    
    /* Quiz timer styles */
    .product-hunt-quiz-timer {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        padding: 10px;
        background: #f0f0f1;
        border-radius: 4px;
    }
    
    .product-hunt-timer-icon {
        font-size: 24px;
        margin-right: 10px;
    }
    
    .product-hunt-timer-display {
        font-size: 20px;
        font-weight: bold;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Initialize quiz timer
        if ($('.product-hunt-quiz').data('time-limit') > 0) {
            let timeLimit = $('.product-hunt-quiz').data('time-limit');
            let minutes = timeLimit;
            let seconds = 0;
            
            const timer = setInterval(function() {
                if (seconds === 0) {
                    if (minutes === 0) {
                        clearInterval(timer);
                        alert('<?php _e('Time\'s up!', 'product-hunt'); ?>');
                        return;
                    }
                    minutes--;
                    seconds = 59;
                } else {
                    seconds--;
                }
                
                $('#quiz-minutes').text(String(minutes).padStart(2, '0'));
                $('#quiz-seconds').text(String(seconds).padStart(2, '0'));
            }, 1000);
        }
        
        // This is just a preview, so we'll simulate the quiz flow
        $('.product-hunt-next-button, .product-hunt-prev-button').on('click', function() {
            const isNext = $(this).hasClass('product-hunt-next-button');
            const $currentQuestion = $('.product-hunt-question.active');
            const $questions = $('.product-hunt-question');
            
            let currentIndex = $questions.index($currentQuestion);
            let nextIndex = isNext ? currentIndex + 1 : currentIndex - 1;
            
            if (nextIndex >= 0 && nextIndex < $questions.length) {
                $currentQuestion.removeClass('active');
                $questions.eq(nextIndex).addClass('active');
                
                // Update progress bar
                const progress = ((nextIndex + 1) / $questions.length) * 100;
                $('.product-hunt-quiz-progress-bar').css('width', progress + '%');
            }
        });
        
        // Start with the first question
        $('.product-hunt-question').first().addClass('active');
        $('.product-hunt-quiz-progress-bar').css('width', (1 / $('.product-hunt-question').length) * 100 + '%');
        
        // Submit button for preview
        $('.product-hunt-submit-button').on('click', function() {
            $('.product-hunt-question').removeClass('active');
            
            if ($('.product-hunt-quiz').data('email-capture') == 1) {
                $('.product-hunt-email-capture').addClass('active');
            } else {
                $('.product-hunt-results').addClass('active');
            }
        });
        
        // Email form submission
        $('.product-hunt-email-form').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('.product-hunt-email-input').val().trim();
            if (!email) {
                $('.product-hunt-email-error').show();
                return;
            }
            
            $('.product-hunt-email-capture').removeClass('active');
            $('.product-hunt-results').addClass('active');
        });
        
        // Skip email link
        $('.product-hunt-skip-email').on('click', function(e) {
            e.preventDefault();
            
            $('.product-hunt-email-capture').removeClass('active');
            $('.product-hunt-results').addClass('active');
        });
    });
</script>

<?php get_footer(); ?>
