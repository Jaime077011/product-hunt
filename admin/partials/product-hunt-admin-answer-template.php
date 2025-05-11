<?php
/**
 * Template for an answer option in the quiz builder
 *
 * @since      1.0.0
 */

// Get answer data if editing
$answer_id = isset($answer) ? $answer->id : '';
$answer_text = isset($answer) ? $answer->answer_text : '';
$answer_order = isset($answer) ? $answer->answer_order : 0;
$answer_dom_id = !empty($answer_id) ? 'answer-' . $answer_id : '{answer_id}';
$question_dom_id = !empty($question_id) ? 'question-' . $question_id : $question_dom_id;
?>

<div id="<?php echo esc_attr($answer_dom_id); ?>" class="product-hunt-answer-container">
    <div class="product-hunt-answer-row">
        <div class="product-hunt-answer-handle">
            <span class="dashicons dashicons-menu"></span>
        </div>
        
        <div class="product-hunt-answer-content">
            <input type="text" 
                   name="answer[<?php echo esc_attr($question_dom_id); ?>][<?php echo esc_attr($answer_dom_id); ?>][text]" 
                   value="<?php echo esc_attr($answer_text); ?>" 
                   class="regular-text answer-text" 
                   placeholder="<?php esc_attr_e('Enter answer option here...', 'product-hunt'); ?>">
            <input type="hidden" 
                   name="answer[<?php echo esc_attr($question_dom_id); ?>][<?php echo esc_attr($answer_dom_id); ?>][id]" 
                   value="<?php echo esc_attr($answer_id); ?>">
            <input type="hidden" 
                   name="answer[<?php echo esc_attr($question_dom_id); ?>][<?php echo esc_attr($answer_dom_id); ?>][order]" 
                   value="<?php echo esc_attr($answer_order); ?>" 
                   class="answer-order">
        </div>
        
        <div class="product-hunt-answer-actions">
            <button type="button" class="button toggle-product-mapping" title="<?php esc_attr_e('Product Recommendations', 'product-hunt'); ?>">
                <span class="dashicons dashicons-cart"></span>
            </button>
            <button type="button" class="button delete-answer" title="<?php esc_attr_e('Delete', 'product-hunt'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
    </div>
    
    <div class="product-hunt-product-mapping" style="display: none;">
        <h4><?php _e('Product Recommendations', 'product-hunt'); ?></h4>
        <p class="description"><?php _e('Products to recommend when this answer is selected.', 'product-hunt'); ?></p>
        
        <div class="product-hunt-product-search">
            <input type="text" 
                   class="product-search-input" 
                   placeholder="<?php esc_attr_e('Search for products to add...', 'product-hunt'); ?>">
            <div class="product-search-results"></div>
        </div>
        
        <div class="selected-products">
            <?php
            // Display associated products if they exist
            if (isset($answer) && $answer_id) {
                global $wpdb;
                $recommendations = $wpdb->get_results($wpdb->prepare(
                    "SELECT pr.*, p.post_title FROM {$wpdb->prefix}ph_product_recommendations pr
                    JOIN {$wpdb->posts} p ON pr.product_id = p.ID
                    WHERE pr.answer_id = %d",
                    $answer_id
                ));
                
                if ($recommendations) {
                    foreach ($recommendations as $recommendation) {
                        $product_id = $recommendation->product_id;
                        $product_title = $recommendation->post_title;
                        $product_weight = $recommendation->weight;
                        
                        // Get product image
                        $image_url = get_the_post_thumbnail_url($product_id, 'thumbnail') ?: '';
                        
                        // Get price for WooCommerce products
                        $price_html = '';
                        if (function_exists('wc_get_product')) {
                            $product = wc_get_product($product_id);
                            if ($product) {
                                $price_html = $product->get_price_html();
                            }
                        }
                        ?>
                        <div class="product-hunt-product-item" data-product-id="<?php echo esc_attr($product_id); ?>">
                            <div class="product-image">
                                <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product_title); ?>" width="60" height="60">
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-title"><?php echo esc_html($product_title); ?></div>
                                <div class="product-price"><?php echo $price_html; ?></div>
                                <div class="product-weight">
                                    <label><?php _e('Recommendation Weight:', 'product-hunt'); ?>
                                        <input type="number"
                                               name="product_weight[<?php echo esc_attr($answer_dom_id); ?>][<?php echo esc_attr($product_id); ?>]"
                                               value="<?php echo esc_attr($product_weight); ?>"
                                               min="0.1"
                                               max="10"
                                               step="0.1"
                                               class="product-weight">
                                    </label>
                                </div>
                                <input type="hidden" name="answer_products[<?php echo esc_attr($answer_dom_id); ?>][]" value="<?php echo esc_attr($product_id); ?>">
                            </div>
                            <button type="button" class="button remove-product">
                                <span class="dashicons dashicons-no-alt"></span> <?php _e('Remove', 'product-hunt'); ?>
                            </button>
                        </div>
                        <?php
                    }
                }
            }
            ?>
        </div>
    </div>
</div>
