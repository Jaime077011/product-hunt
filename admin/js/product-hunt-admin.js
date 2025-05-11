/**
 * Product Hunt Quiz Admin JavaScript
 * 
 * Handles interactive functionality for the quiz builder including:
 * - Drag and drop for questions and answers
 * - Conditional logic management
 * - Product mapping
 * - Quiz style customization preview
 */

(function($) {
    'use strict';

    // Admin UI initialization
    $(document).ready(function() {
        // Skip quiz builder initialization if the quiz editor is already initialized
        if (window.QuizEditorInitialized === true || window.adminQuizBuilderDisabled === true) {
            console.log('Quiz Editor already initialized. Skipping admin quiz builder initialization.');
            return;
        }

        initQuizBuilder();
        initQuestionSorting();
        initAnswerSorting();
        initConditionalLogic();
        initProductSearch();
        initStyleCustomization();
        initColorPickers();
    });

    /**
     * Initialize the quiz builder functionality
     */
    function initQuizBuilder() {
        // Add new question button
        $('#add-new-question').on('click', function(e) {
            e.preventDefault();
            const questionTemplate = $('#question-template').html();
            const newQuestionId = 'question-' + new Date().getTime();
            const newQuestion = questionTemplate.replace(/\{question_id\}/g, newQuestionId);
            
            $('#quiz-questions-container').append(newQuestion);
            
            // Initialize new question's functionality
            initAnswerSorting($('#' + newQuestionId + ' .product-hunt-answers-list'));
            
            // Scroll to new question
            $('html, body').animate({
                scrollTop: $('#' + newQuestionId).offset().top - 50
            }, 500);
        });

        // Delete question button
        $(document).on('click', '.delete-question', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this question? This cannot be undone.')) {
                $(this).closest('.product-hunt-question-container').remove();
                updateConditionalLogicOptions();
            }
        });

        // Add new answer button
        $(document).on('click', '.add-new-answer', function(e) {
            e.preventDefault();
            const answerTemplate = $('#answer-template').html();
            const questionId = $(this).data('question-id');
            const newAnswerId = 'answer-' + new Date().getTime();
            const newAnswer = answerTemplate
                .replace(/\{question_id\}/g, questionId)
                .replace(/\{answer_id\}/g, newAnswerId);
            
            $('#' + questionId + ' .product-hunt-answers-list').append(newAnswer);
            updateConditionalLogicOptions();
        });

        // Delete answer button
        $(document).on('click', '.delete-answer', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this answer? This will also remove any conditional logic associated with it.')) {
                $(this).closest('.product-hunt-answer-container').remove();
                updateConditionalLogicOptions();
            }
        });

        // Change question type
        $(document).on('change', '.question-type-selector', function() {
            const questionContainer = $(this).closest('.product-hunt-question-container');
            const questionType = $(this).val();
            
            // Show/hide relevant options based on question type
            questionContainer.find('.question-options').hide();
            questionContainer.find('.question-options-' + questionType).show();
            
            // Update answer UI based on question type
            updateAnswerUIByQuestionType(questionContainer, questionType);
        });

        // Save quiz form submission
        $('#product-hunt-quiz-form').on('submit', function(e) {
            // Validate required fields
            const quizTitle = $('#quiz-title').val().trim();
            if (!quizTitle) {
                e.preventDefault();
                alert('Quiz title is required.');
                $('#quiz-title').focus();
                return false;
            }
            
            // Make sure there's at least one question
            if ($('.product-hunt-question-container').length === 0) {
                e.preventDefault();
                alert('Please add at least one question to your quiz.');
                return false;
            }
            
            // Validate each question has at least one answer (for non-text questions)
            let isValid = true;
            $('.product-hunt-question-container').each(function() {
                const questionType = $(this).find('.question-type-selector').val();
                const questionText = $(this).find('.question-text').val().trim();
                
                if (!questionText) {
                    isValid = false;
                    alert('All questions must have text.');
                    $(this).find('.question-text').focus();
                    return false;
                }
                
                if (questionType !== 'text' && $(this).find('.product-hunt-answer-container').length === 0) {
                    isValid = false;
                    alert('Each question must have at least one answer option.');
                    $(this).find('.add-new-answer').focus();
                    return false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Prepare data for submission (serialize conditional logic, product mappings, etc.)
            prepareFormDataForSubmission();
        });
    }

    /**
     * Initialize sortable functionality for questions
     */
    function initQuestionSorting() {
        if (typeof $.fn.sortable !== 'undefined') {
            $('#quiz-questions-container').sortable({
                handle: '.product-hunt-question-handle',
                placeholder: 'product-hunt-question-placeholder',
                tolerance: 'pointer',
                update: function() {
                    updateQuestionOrder();
                }
            });
        }
    }

    /**
     * Initialize sortable functionality for answers
     */
    function initAnswerSorting(container) {
        if (typeof $.fn.sortable !== 'undefined') {
            const selector = container || '.product-hunt-answers-list';
            
            $(selector).sortable({
                handle: '.product-hunt-answer-handle',
                placeholder: 'product-hunt-answer-placeholder',
                tolerance: 'pointer',
                update: function() {
                    updateAnswerOrder($(this).closest('.product-hunt-question-container'));
                }
            });
        }
    }

    /**
     * Update question order values
     */
    function updateQuestionOrder() {
        $('.product-hunt-question-container').each(function(index) {
            $(this).find('.question-order').val(index);
        });
    }

    /**
     * Update answer order values for a specific question
     */
    function updateAnswerOrder(questionContainer) {
        questionContainer.find('.product-hunt-answer-container').each(function(index) {
            $(this).find('.answer-order').val(index);
        });
    }

    /**
     * Initialize conditional logic UI and behavior
     */
    function initConditionalLogic() {
        // Toggle conditional logic panel
        $(document).on('click', '.toggle-conditional-logic', function(e) {
            e.preventDefault();
            $(this).closest('.product-hunt-question-container').find('.product-hunt-conditional-logic').slideToggle();
        });
        
        // Add new conditional rule
        $(document).on('click', '.add-logic-rule', function(e) {
            e.preventDefault();
            const ruleTemplate = $('#conditional-rule-template').html();
            const questionId = $(this).data('question-id');
            const newRuleId = 'rule-' + new Date().getTime();
            const newRule = ruleTemplate
                .replace(/\{question_id\}/g, questionId)
                .replace(/\{rule_id\}/g, newRuleId);
            
            $(this).closest('.product-hunt-conditional-logic').find('.conditional-rules-container').append(newRule);
            
            // Initialize the new rule's selectors
            populateConditionalLogicSelectors($('#' + newRuleId));
        });
        
        // Delete conditional rule
        $(document).on('click', '.delete-logic-rule', function(e) {
            e.preventDefault();
            $(this).closest('.product-hunt-logic-rule').remove();
        });
        
        // Populate initial conditional logic options
        updateConditionalLogicOptions();
    }

    /**
     * Update conditional logic selectors when questions or answers change
     */
    function updateConditionalLogicOptions() {
        // For each rule that exists
        $('.product-hunt-logic-rule').each(function() {
            populateConditionalLogicSelectors($(this));
        });
    }

    /**
     * Populate a specific conditional rule with available questions and answers
     */
    function populateConditionalLogicSelectors(ruleContainer) {
        const questionSelector = ruleContainer.find('.condition-question-selector');
        const answerSelector = ruleContainer.find('.condition-answer-selector');
        const showQuestionSelector = ruleContainer.find('.show-question-selector');
        
        // Selected values (to maintain after refresh)
        const selectedQuestion = questionSelector.val();
        const selectedAnswer = answerSelector.val();
        const selectedShowQuestion = showQuestionSelector.val();
        
        // Clear existing options
        questionSelector.find('option:not(:first-child)').remove();
        answerSelector.find('option:not(:first-child)').remove();
        showQuestionSelector.find('option:not(:first-child)').remove();
        
        // Add all questions as options
        $('.product-hunt-question-container').each(function() {
            const qId = $(this).attr('id');
            const qText = $(this).find('.question-text').val().trim() || 'Untitled Question';
            
            // Add to "if question" selector
            questionSelector.append(`<option value="${qId}" ${selectedQuestion === qId ? 'selected' : ''}>${qText}</option>`);
            
            // Add to "show question" selector
            showQuestionSelector.append(`<option value="${qId}" ${selectedShowQuestion === qId ? 'selected' : ''}>${qText}</option>`);
        });
        
        // If a question is selected, populate its answers
        if (selectedQuestion) {
            $('#' + selectedQuestion).find('.product-hunt-answer-container').each(function() {
                const aId = $(this).attr('id');
                const aText = $(this).find('.answer-text').val().trim() || 'Untitled Answer';
                
                answerSelector.append(`<option value="${aId}" ${selectedAnswer === aId ? 'selected' : ''}>${aText}</option>`);
            });
        }
    }

    /**
     * Initialize product search and mapping functionality
     */
    function initProductSearch() {        // Product toggle button with better UI handling
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
          // Improved product search with better error handling
        let searchTimeout;
        $(document).on('input', '.product-search-input', function() {
            const $input = $(this);
            const searchTerm = $input.val().trim();
            const $searchResults = $input.closest('.product-hunt-product-search').find('.product-search-results');
            const $answerContainer = $input.closest('.product-hunt-answer-container');
            
            clearTimeout(searchTimeout);
            
            if (searchTerm.length < 3) {
                $searchResults.html('').hide();
                return;
            }
            
            // Show loading indicator
            $searchResults.html('<p>Searching...</p>').show();
            
            // Debounce the search to prevent too many requests
            searchTimeout = setTimeout(function() {
                console.log("Searching for products: " + searchTerm);
                
                // AJAX call to search products
                $.ajax({
                    url: product_hunt_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'product_hunt_search_products',
                        security: product_hunt_admin.nonce,
                        search: searchTerm
                    },
                    success: function(response) {
                        console.log("Search response:", response);
                        if (response.success) {
                            let resultsHtml = '';
                            
                            if (response.data.products && response.data.products.length) {
                                resultsHtml = '<ul class="product-search-list">';
                                
                                response.data.products.forEach(function(product) {
                                    resultsHtml += `<li>
                                        <a href="#" class="select-product" 
                                           data-product-id="${product.id}" 
                                           data-product-name="${product.name}" 
                                           data-product-image="${product.image || ''}"
                                           data-product-price="${product.price || ''}">
                                            ${product.image ? `<img src="${product.image}" alt="" width="30" height="30" style="vertical-align: middle; margin-right: 8px;">` : ''}
                                            <span>${product.name}</span>
                                            ${product.sku ? `<small style="opacity: 0.7; display: block;">SKU: ${product.sku}</small>` : ''}
                                        </a>
                                    </li>`;
                                });
                                
                                resultsHtml += '</ul>';
                            } else {
                                resultsHtml = '<p>No products found matching your search.</p>';
                            }
                              $searchResults.html(resultsHtml).show();
                        } else {
                            console.error('Product search API error:', response);
                            $searchResults.html(
                                '<p class="error">Error searching for products. ' + 
                                (response.data && response.data.message ? response.data.message : '') + 
                                '</p>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX product search error:", error);
                        console.error("Status:", status);
                        console.error("Response:", xhr.responseText);
                        
                        try {
                            // Try to parse response for more details
                            const errorData = JSON.parse(xhr.responseText);
                            $searchResults.html(
                                '<p class="error">Product search failed: ' + 
                                (errorData.message || error) + 
                                '</p>'
                            );
                        } catch(e) {
                            $searchResults.html('<p class="error">Request failed. Please try again.</p>');
                        }
                    }
                });
            }, 500);
        });
          // Select product from search results - improved with better error handling
        $(document).on('click', '.select-product', function(e) {
            e.preventDefault();
            console.log("Product selected");
            
            try {
                const $this = $(this);
                const productId = $this.data('product-id');
                const productName = $this.data('product-name');
                const productImage = $this.data('product-image') || '';
                const $answerContainer = $this.closest('.product-hunt-answer-container');
                const answerId = $answerContainer.attr('id');
                
                // Validation checks
                if (!answerId) {
                    console.error("Could not find answer ID for product assignment");
                    alert("Error: Could not assign product to answer. Please try again or refresh the page.");
                    return;
                }
                
                if (!productId) {
                    console.error("Product ID missing");
                    alert("Error: Product ID is missing. Please try selecting another product.");
                    return;
                }
                
                console.log(`Assigning product ${productId} (${productName}) to answer ${answerId}`);
                
                // Check if product is already added
                if ($answerContainer.find(`.product-hunt-product-item[data-product-id="${productId}"]`).length) {
                    // Flash the existing product to indicate it's already there
                    const $existingProduct = $answerContainer.find(`.product-hunt-product-item[data-product-id="${productId}"]`);
                    $existingProduct.css('background-color', '#ffff9c');
                    setTimeout(() => {
                        $existingProduct.css('background-color', '');
                    }, 1500);
                    return;
                }
                
                // Create product item HTML
                const productHtml = `
                    <div class="product-hunt-product-item" data-product-id="${productId}">
                        ${productImage ? `<img src="${productImage}" alt="${productName}" width="60" height="60">` : ''}
                        <p>${productName}</p>
                        <div class="product-weight-control">
                            <label>Weight:
                                <input type="number" name="product_weights[${answerId}][${productId}]" value="1.0" min="0.1" max="10" step="0.1" class="product-weight">
                            </label>
                        </div>
                        <button type="button" class="button remove-product">Remove</button>
                        <input type="hidden" name="answer_products[${answerId}][]" value="${productId}">
                    </div>
                `;
                
                // Add product to selected products
                $answerContainer.find('.selected-products').append(productHtml);
                
                // Clear search
                $answerContainer.find('.product-search-input').val('');
                $answerContainer.find('.product-search-results').html('').hide();
                
                // Log success
                console.log("Product successfully assigned");
            } catch (error) {
                console.error("Error adding product:", error);
                alert("There was an error adding the product. Please try again.");
            }
        });
        
        // Remove product
        $(document).on('click', '.remove-product', function() {
            $(this).closest('.product-hunt-product-item').fadeOut(300, function() {
                $(this).remove();
            });
        });
    }

    /**
     * Initialize quiz style customization
     */
    function initStyleCustomization() {
        // Toggle style customization panel
        $('#toggle-style-settings').on('click', function(e) {
            e.preventDefault();
            $('#product-hunt-style-settings-panel').slideToggle();
        });
        
        // Update quiz preview when style settings change
        $('.quiz-style-setting').on('change input', function() {
            updateQuizPreview();
        });
    }
    
    /**
     * Initialize WordPress color pickers
     */
    function initColorPickers() {
        if (typeof $.fn.wpColorPicker !== 'undefined') {
            $('.color-picker').wpColorPicker({
                change: function() {
                    // Delay slightly for the color picker to update the input value
                    setTimeout(updateQuizPreview, 100);
                }
            });
        }
    }
    
    /**
     * Update the quiz preview when style settings change
     */
    function updateQuizPreview() {
        const primaryColor = $('#quiz-primary-color').val() || '#3498db';
        const secondaryColor = $('#quiz-secondary-color').val() || '#2ecc71';
        const buttonStyle = $('#quiz-button-style').val() || 'rounded';
        const fontFamily = $('#quiz-font-family').val() || 'inherit';
        
        // Apply changes to the preview panel
        const previewPanel = $('#quiz-preview-panel');
        
        previewPanel.css('font-family', fontFamily);
        
        // Update button styles
        previewPanel.find('.preview-button').css({
            'background-color': primaryColor,
            'color': '#ffffff',
            'border': 'none',
            'padding': '10px 20px',
            'border-radius': buttonStyle === 'rounded' ? '25px' : 
                            (buttonStyle === 'square' ? '0' : '5px')
        });
        
        // Update question container styles
        previewPanel.find('.preview-question').css({
            'border-left': `4px solid ${secondaryColor}`,
            'padding-left': '15px'
        });
        
        // Update header styles
        previewPanel.find('.preview-header').css({
            'color': primaryColor,
            'border-bottom': `2px solid ${secondaryColor}`
        });
    }
    
    /**
     * Update the answer UI based on the question type
     */
    function updateAnswerUIByQuestionType(questionContainer, questionType) {
        const answersContainer = questionContainer.find('.product-hunt-answers-list');
        
        // Clear message
        questionContainer.find('.question-type-message').remove();
        
        switch (questionType) {
            case 'multiple_choice':
                // Ensure radio button type
                answersContainer.find('.answer-type').val('radio');
                // Show the answers container
                answersContainer.closest('.answers-section').show();
                break;
                
            case 'checkbox':
                // Ensure checkbox type
                answersContainer.find('.answer-type').val('checkbox');
                // Show the answers container
                answersContainer.closest('.answers-section').show();
                break;
                
            case 'text':
                // Hide the answers container
                answersContainer.closest('.answers-section').hide();
                // Add message about text inputs
                questionContainer.find('.question-type-selector').after(
                    '<p class="question-type-message"><em>Text input questions do not have predefined answers.</em></p>'
                );
                break;
                
            case 'dropdown':
                // Ensure dropdown type
                answersContainer.find('.answer-type').val('select');
                // Show the answers container
                answersContainer.closest('.answers-section').show();
                break;
        }
    }
    
    /**
     * Prepare form data before submission
     */
    function prepareFormDataForSubmission() {
        // Ensure all sortable elements have updated order values
        updateQuestionOrder();
        
        $('.product-hunt-question-container').each(function() {
            updateAnswerOrder($(this));
        });
        
        // Additional data serialization can be done here if needed
    }

})(jQuery);

/**
 * Admin JavaScript for Product Hunt Quiz
 *
 * @since      1.0.0
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Global form initialization
        initFormElements();

        // Initialize any data tables
        if ($.fn.DataTable) {
            $('.product-hunt-datatable').DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    search: '',
                    searchPlaceholder: 'Search...'
                }
            });
        }

        // Tooltip initialization
        $('.product-hunt-tooltip').hover(function() {
            $(this).find('.product-hunt-tooltip-content').fadeIn(200);
        }, function() {
            $(this).find('.product-hunt-tooltip-content').fadeOut(200);
        });
        
        // Copy shortcode functionality
        $('.copy-shortcode').on('click', function() {
            const shortcode = $(this).data('shortcode');
            const tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(shortcode).select();
            document.execCommand('copy');
            tempInput.remove();
            
            // Show a temporary notification
            const $button = $(this);
            const originalHtml = $button.html();
            $button.html('<span class="dashicons dashicons-yes"></span>');
            
            setTimeout(function() {
                $button.html(originalHtml);
            }, 1000);
        });
        
        // Confirm delete
        $('.delete-confirm').on('click', function(e) {
            if (!confirm(product_hunt_admin.i18n.confirm_delete)) {
                e.preventDefault();
            }
        });
    });

    /**
     * Initialize common form elements with enhanced UI
     */
    function initFormElements() {
        // Enable select2 for select boxes if available
        if ($.fn.select2) {
            $('.product-hunt-select2').select2({
                width: '100%',
                placeholder: 'Select an option'
            });
        }
    }

})(jQuery);