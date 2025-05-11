/**
 * JavaScript for Product Hunt Quiz Editor
 *
 * @since      1.0.0
 */
(function($) {
    'use strict';

    // Create a singleton instance to prevent multiple initializations
    if (window.QuizEditorInitialized === true) {
        console.log('Quiz Editor already initialized. Preventing duplicate initialization.');
        return;
    }
    
    // Mark as initialized
    window.QuizEditorInitialized = true;

    // Add quiz state management
    const quizEditorState = {
        isEditing: false,
        quizId: 0,
        initialLoad: true,
        eventsBound: false
    };
    
    // Create a safe fallback for product_hunt_admin if it's not defined
    if (typeof product_hunt_admin === 'undefined') {
        console.warn('product_hunt_admin is not defined! Creating default values.');
        window.product_hunt_admin = {
            ajax_url: ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: '',
            placeholder_img: '',
            i18n: {
                confirm_delete: 'Are you sure you want to delete this item? This action cannot be undone.',
                missing_title: 'Quiz title is required.',
                missing_question: 'Please enter text for all questions.',
                missing_answer: 'Please add at least one answer option to the question:'
            }
        };
    }

    $(document).ready(function() {
        // We need to completely disable any initialization in product-hunt-admin.js
        // Add this to prevent product-hunt-admin.js from initializing the same functionality
        window.adminQuizBuilderDisabled = true;
        
        // Set editor state based on quiz_id
        const quizId = $('#product-hunt-quiz-form input[name="quiz_id"]').val();
        quizEditorState.quizId = parseInt(quizId, 10) || 0;
        quizEditorState.isEditing = quizEditorState.quizId > 0;
        
        console.log(`Quiz Editor initialized - Edit Mode: ${quizEditorState.isEditing}, Quiz ID: ${quizEditorState.quizId}`);

        // Completely unbind all possible event handlers first
        unbindAllEditorEvents();
        
        // Initialize WordPress color pickers
        if (typeof $.fn.wpColorPicker !== 'undefined') {
            $('.color-picker').wpColorPicker({
                change: function() {
                    // Delay slightly for the color picker to update the input value
                    setTimeout(updateQuizPreview, 100);
                }
            });
        }
        
        // Initialize question sorting
        initQuestionSorting();
        
        // Initialize the quiz preview
        updateQuizPreview();
        
        // Initialize event bindings only once
        bindEditorEvents();

        // Update all answer orders on page load for editing mode
        if (quizEditorState.isEditing) {
            $('.product-hunt-question-container').each(function() {
                updateAnswerOrder($(this));
            });
            updateQuestionOrder();
        }
        
        // Mark initialization complete
        quizEditorState.initialLoad = false;
        quizEditorState.eventsBound = true;
    });
    
    // Thoroughly unbind all event handlers that we might be using
    function unbindAllEditorEvents() {
        console.log('Unbinding all editor events to prevent duplicates');
        
        // Unbind all click events on add question button
        $('#add-new-question').off('click');
        
        // Unbind document events for all our selectors
        $(document).off('click', '.add-new-answer');
        $(document).off('click', '.delete-question');
        $(document).off('click', '.delete-answer');
        $(document).off('click', '.toggle-product-mapping');
        $(document).off('input', '.product-search-input');
        $(document).off('click', '.select-product');
        $(document).off('click', '.remove-product');
        $(document).off('input', '.question-text');
        $(document).off('change', '.question-type-selector');
        
        // Unbind form submit
        $('#product-hunt-quiz-form').off('submit');
        
        // Unbind tab navigation
        $('.product-hunt-tabs a').off('click');
        
        // Unbind email capture toggle
        $('#email-capture').off('change');
    }
    
    // Updates the quiz preview when style settings change
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
    
    // Initialize question sorting
    function initQuestionSorting() {
        if (typeof $.fn.sortable !== 'undefined') {
            // Destroy any existing sortable to prevent duplicates
            if ($('#quiz-questions-container').hasClass('ui-sortable')) {
                $('#quiz-questions-container').sortable('destroy');
            }
            
            $('#quiz-questions-container').sortable({
                handle: '.product-hunt-question-handle',
                placeholder: 'product-hunt-question-placeholder',
                tolerance: 'pointer',
                update: function() {
                    updateQuestionOrder();
                }
            });
            
            // Initialize answer sorting for existing questions
            $('.product-hunt-answers-list').each(function() {
                initAnswerSorting($(this));
            });
        }
    }
    
    // Initialize answer sorting for a container
    function initAnswerSorting(container) {
        if (typeof $.fn.sortable !== 'undefined') {
            // Destroy any existing sortable to prevent duplicates
            if ($(container).hasClass('ui-sortable')) {
                $(container).sortable('destroy');
            }
            
            $(container).sortable({
                handle: '.product-hunt-answer-handle',
                placeholder: 'product-hunt-answer-placeholder',
                tolerance: 'pointer',
                update: function() {
                    updateAnswerOrder($(this).closest('.product-hunt-question-container'));
                }
            });
        }
    }
    
    // Update question order values
    function updateQuestionOrder() {
        $('.product-hunt-question-container').each(function(index) {
            $(this).find('.question-order').val(index);
        });
    }
    
    // Update answer order values
    function updateAnswerOrder(questionContainer) {
        questionContainer.find('.product-hunt-answer-container').each(function(index) {
            $(this).find('.answer-order').val(index);
        });
    }
    
    // Bind all editor events - ensures we don't have duplicate handlers
    function bindEditorEvents() {
        if (quizEditorState.eventsBound) {
            console.log('Events already bound. Skipping to prevent duplicates.');
            return;
        }
        
        console.log('Binding editor events...');
        
        // Add question button with direct handler (no delegation)
        $('#add-new-question').on('click', function(e) {
            e.preventDefault();
            console.log('Add question clicked');
            
            const questionTemplate = $('#question-template').html();
            const newQuestionId = 'question-' + new Date().getTime();
            const newQuestion = questionTemplate.replace(/\{question_id\}/g, newQuestionId);
            
            $('#quiz-questions-container').append(newQuestion);
            
            // Initialize new question's functionality
            if (typeof $.fn.sortable !== 'undefined') {
                initAnswerSorting($('#' + newQuestionId + ' .product-hunt-answers-list'));
            }
            
            // Initialize question title
            const $newQuestion = $('#' + newQuestionId);
            $newQuestion.find('.question-title').text('New Question');
            
            // Show/hide answer options based on question type
            const questionType = $newQuestion.find('.question-type-selector').val();
            if (questionType === 'text' || questionType === 'email') {
                $newQuestion.find('.answers-section').addClass('hidden');
            }
            
            // Switch to questions tab if not already there
            $('.product-hunt-tabs a[href="#tab-questions"]').trigger('click');
            
            // Scroll to new question
            $('html, body').animate({
                scrollTop: $('#' + newQuestionId).offset().top - 50
            }, 500);
        });
        
        // Use direct binding for static elements and delegation for dynamic elements
        $(document).on('click', '.add-new-answer', function(e) {
            e.preventDefault();
            console.log('Add answer clicked');
            
            const answerTemplate = $('#answer-template').html();
            const questionId = $(this).data('question-id');
            const newAnswerId = 'answer-' + new Date().getTime();
            const newAnswer = answerTemplate
                .replace(/\{question_id\}/g, questionId)
                .replace(/\{answer_id\}/g, newAnswerId);
            
            $('#' + questionId + ' .product-hunt-answers-list').append(newAnswer);
        });
        
        // Handle product mapping specially to fix the flickering issue
        $(document).on('click', '.toggle-product-mapping', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            
            const $mapping = $(this).closest('.product-hunt-answer-container').find('.product-hunt-product-mapping');
            
            // Close any other open product mappings first
            $('.product-hunt-product-mapping').not($mapping).slideUp();
            
            // Toggle this one
            $mapping.slideToggle();
        });
        
        // Select product from search results - properly namespaced
        $(document).off('click.quizEditor', '.select-product').on('click.quizEditor', '.select-product', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $this = $(this);
            const productId = $this.data('id');
            const productTitle = $this.data('title');
            // Use a safe check for product_hunt_admin
            const productImage = $this.data('image') || (window.product_hunt_admin && window.product_hunt_admin.placeholder_img) || '';
            const productPrice = $this.data('price') || '';
            
            // ...existing code...
        });
        
        // Rest of your event binding code...
        // ...existing code...
    }
})(jQuery);
