<?php
/**
 * Template for rendering a single question item in the quiz builder.
 *
 * @var array $question Question data.
 * @var int $question_id Question ID.
 * @var array $answer Answer data.
 * @var int $answer_id Answer ID.
 */

// Ensure direct access is prevented
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="question-item" data-question-id="<?php echo esc_attr($question_id); ?>">
    <h3><?php echo esc_html($question['text']); ?></h3>
    <div class="answers">
        <?php foreach ($question['answers'] as $answer_id => $answer): ?>
            <div class="answer-item" data-answer-id="<?php echo esc_attr($answer_id); ?>">
                <label>
                    <input type="radio" name="questions[<?php echo esc_attr($question_id); ?>][answers]" value="<?php echo esc_attr($answer_id); ?>">
                    <?php echo esc_html($answer['text']); ?>
                </label>

                <div class="answer-product-mapping">
                    <h4><?php _e('Product Recommendations', 'product-hunt'); ?></h4>
                    <p class="mapping-description"><?php _e('Search for products to recommend when this answer is selected.', 'product-hunt'); ?></p>
                    
                    <div class="product-search">
                        <label for="product-search-<?php echo esc_attr($question_id); ?>-<?php echo esc_attr($answer_id); ?>">
                            <?php _e('Search Products:', 'product-hunt'); ?>
                        </label>
                        <input type="text" 
                               id="product-search-<?php echo esc_attr($question_id); ?>-<?php echo esc_attr($answer_id); ?>" 
                               class="product-search-field" 
                               placeholder="<?php esc_attr_e('Start typing product name or SKU...', 'product-hunt'); ?>"
                               autocomplete="off">
                    </div>
                    
                    <div class="selected-products">
                        <?php
                        // Display already assigned products if in edit mode
                        if (!empty($answer['products']) && is_array($answer['products'])) {
                            foreach ($answer['products'] as $product_id) {
                                // Skip if the product ID is empty
                                if (empty($product_id)) {
                                    continue;
                                }
                                
                                $product = wc_get_product($product_id);
                                if ($product) {
                                    ?>
                                    <div class="selected-product" data-product-id="<?php echo esc_attr($product_id); ?>">
                                        <span class="product-name"><?php echo esc_html($product->get_name()); ?></span>
                                        <input type="hidden" 
                                               name="questions[<?php echo esc_attr($question_id); ?>][answers][<?php echo esc_attr($answer_id); ?>][products][]" 
                                               value="<?php echo esc_attr($product_id); ?>">
                                        <a href="#" class="remove-product dashicons dashicons-no-alt" title="<?php esc_attr_e('Remove', 'product-hunt'); ?>"></a>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </div>
                    
                    <p class="product-count-info">
                        <?php _e('Products assigned:', 'product-hunt'); ?> 
                        <span class="product-count"><?php echo (!empty($answer['products']) && is_array($answer['products'])) ? count(array_filter($answer['products'])) : '0'; ?></span>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>