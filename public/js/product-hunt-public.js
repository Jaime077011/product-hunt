/**
 * Product Hunt Quiz Public JavaScript
 *
 * Handles the frontend quiz functionality including:
 * - Progressive question display
 * - Conditional logic for questions
 * - Form validation
 * - Email capture
 * - Quiz submission and results display
 */

(function($) {
    'use strict';

    // Store quiz state
    const quizState = {
        currentQuestionIndex: 0,
        questions: [],
        answers: {},
        conditionalLogic: {},
        quizId: 0,
        totalQuestions: 0,
        emailCaptureEnabled: true,
        userEmail: '',
        questionPath: [] // Track the path of questions for navigation
    };
    
    // Initialize the quiz
    $(document).ready(function() {
        initQuiz();
        bindEvents();
    });
    
    /**
     * Initialize the quiz functionality and state
     */
    function initQuiz() {
        // Get quiz container
        const $quizContainer = $('.product-hunt-quiz');
        if (!$quizContainer.length) return;
        
        // Set quiz ID
        quizState.quizId = $quizContainer.data('quiz-id');
        
        // Get all questions
        const $questions = $('.product-hunt-question');
        quizState.totalQuestions = $questions.length;
        
        // Initialize quiz data
        $questions.each(function(index) {
            const $question = $(this);
            const questionId = $question.data('question-id');
            
            // Add to questions array
            quizState.questions.push({
                id: questionId,
                index: index,
                required: $question.data('required') === 1,
                type: $question.data('type')
            });
            
            // Set conditional logic if exists
            if ($question.data('conditional-logic')) {
                quizState.conditionalLogic[questionId] = $question.data('conditional-logic');
            }
        });
        
        // Display first question
        showQuestion(0);
        updateProgressBar();
    }
    
    /**
     * Bind all event handlers
     */
    function bindEvents() {
        // Next button click
        $(document).on('click', '.product-hunt-next-button', function(e) {
            e.preventDefault();
            nextQuestion();
        });
        
        // Previous button click
        $(document).on('click', '.product-hunt-prev-button', function(e) {
            e.preventDefault();
            previousQuestion();
        });
        
        // Submit button click
        $(document).on('click', '.product-hunt-submit-button', function(e) {
            e.preventDefault();
            completeQuiz();
        });
        
        // Input change (for validation)
        $(document).on('change', '.product-hunt-answer input, .product-hunt-answer textarea, .product-hunt-answer select', function() {
            validateCurrentQuestion();
        });
        
        // Email capture form submission
        $(document).on('submit', '.product-hunt-email-form', function(e) {
            e.preventDefault();
            submitEmail();
        });
        
        // Skip email button click
        $(document).on('click', '.product-hunt-skip-email', function(e) {
            e.preventDefault();
            showResults();
        });
        
        // Product interaction tracking
        $(document).on('click', '.product-hunt-product-button', function() {
            const productId = $(this).closest('.product-hunt-product').data('product-id');
            trackProductInteraction(productId, 'click');
        });
    }
    
    /**
     * Show a specific question by index
     */
    function showQuestion(index) {
        // Hide all questions
        $('.product-hunt-question').removeClass('active');
        
        // Get the current question
        const currentQuestion = findQuestionByIndex(index);
        if (!currentQuestion) return;
        
        // Show current question
        $('.product-hunt-question[data-question-id="' + currentQuestion.id + '"]').addClass('active');
        
        // Update current index
        quizState.currentQuestionIndex = index;
        
        // Update navigation buttons
        updateNavigationButtons();
        
        // Validate current question (in case it was previously filled)
        validateCurrentQuestion();
        
        // Update progress bar
        updateProgressBar();
        
        // Add to question path if not already there
        if (!quizState.questionPath.includes(index)) {
            quizState.questionPath.push(index);
        }
        
        // Scroll to top of question
        $('html, body').animate({
            scrollTop: $('.product-hunt-question.active').offset().top - 50
        }, 300);
    }
    
    /**
     * Move to the next question based on conditional logic
     */
    function nextQuestion() {
        // Validate current question
        if (!validateCurrentQuestion()) return;
        
        // Save answers from current question
        saveCurrentQuestionAnswers();
        
        // Find next question index based on conditional logic
        const nextIndex = findNextQuestionIndex();
        
        // Show next question
        showQuestion(nextIndex);
    }
    
    /**
     * Move to the previous question
     */
    function previousQuestion() {
        // Get previous question from path
        const currentPathIndex = quizState.questionPath.indexOf(quizState.currentQuestionIndex);
        
        if (currentPathIndex > 0) {
            const prevIndex = quizState.questionPath[currentPathIndex - 1];
            showQuestion(prevIndex);
            
            // Remove current question from path
            quizState.questionPath.splice(currentPathIndex, 1);
        }
    }
    
    /**
     * Find the next question index based on conditional logic
     */
    function findNextQuestionIndex() {
        const currentIndex = quizState.currentQuestionIndex;
        const currentQuestion = findQuestionByIndex(currentIndex);
        
        // Get current answer(s)
        const currentAnswers = quizState.answers[currentQuestion.id];
        
        // Check if there's conditional logic for this question+answer
        if (currentAnswers && quizState.conditionalLogic) {
            for (const logicRule of Object.values(quizState.conditionalLogic)) {
                if (logicRule.if_question == currentQuestion.id) {
                    // For radio/select (single choice)
                    if (typeof currentAnswers === 'string' && logicRule.if_answer == currentAnswers) {
                        return findQuestionIndexById(logicRule.then_question);
                    }
                    
                    // For checkbox (multiple choice)
                    if (Array.isArray(currentAnswers) && currentAnswers.includes(logicRule.if_answer)) {
                        return findQuestionIndexById(logicRule.then_question);
                    }
                }
            }
        }
        
        // Default to next sequential question
        return currentIndex + 1;
    }
    
    /**
     * Validate the current question's answers
     */
    function validateCurrentQuestion() {
        const currentQuestion = findQuestionByIndex(quizState.currentQuestionIndex);
        if (!currentQuestion) return true;
        
        const $questionElement = $('.product-hunt-question[data-question-id="' + currentQuestion.id + '"]');
        const $errorMessage = $questionElement.find('.product-hunt-error');
        
        // Skip validation if question is not required
        if (!currentQuestion.required) {
            $errorMessage.removeClass('show');
            return true;
        }
        
        let isValid = true;
        
        // Validate based on question type
        switch (currentQuestion.type) {
            case 'multiple_choice':
                isValid = $questionElement.find('input[type="radio"]:checked').length > 0;
                break;
                
            case 'checkbox':
                isValid = $questionElement.find('input[type="checkbox"]:checked').length > 0;
                break;
                
            case 'text':
                const textValue = $questionElement.find('input[type="text"], textarea').val().trim();
                isValid = textValue !== '';
                break;
                
            case 'email':
                const emailValue = $questionElement.find('input[type="email"]').val().trim();
                isValid = emailValue !== '' && isValidEmail(emailValue);
                break;
        }
        
        // Show/hide error message
        if (isValid) {
            $errorMessage.removeClass('show');
        } else {
            $errorMessage.addClass('show');
            
            // Update error message based on question type
            if (currentQuestion.type === 'email') {
                $errorMessage.text('Please enter a valid email address.');
            } else {
                $errorMessage.text('This question requires an answer.');
            }
        }
        
        // Enable/disable next button
        $questionElement.find('.product-hunt-next-button, .product-hunt-submit-button').prop('disabled', !isValid);
        
        return isValid;
    }
    
    /**
     * Save answers from the current question
     */
    function saveCurrentQuestionAnswers() {
        const currentQuestion = findQuestionByIndex(quizState.currentQuestionIndex);
        if (!currentQuestion) return;
        
        const $questionElement = $('.product-hunt-question[data-question-id="' + currentQuestion.id + '"]');
        
        // Save answers based on question type
        switch (currentQuestion.type) {
            case 'multiple_choice':
                const radioValue = $questionElement.find('input[type="radio"]:checked').val();
                if (radioValue) {
                    quizState.answers[currentQuestion.id] = radioValue;
                }
                break;
                
            case 'checkbox':
                const checkboxValues = [];
                $questionElement.find('input[type="checkbox"]:checked').each(function() {
                    checkboxValues.push($(this).val());
                });
                if (checkboxValues.length) {
                    quizState.answers[currentQuestion.id] = checkboxValues;
                }
                break;
                
            case 'text':
            case 'email':
                const inputValue = $questionElement.find('input, textarea').val().trim();
                if (inputValue) {
                    quizState.answers[currentQuestion.id] = inputValue;
                    
                    // Save email specifically if it's an email question
                    if (currentQuestion.type === 'email') {
                        quizState.userEmail = inputValue;
                    }
                }
                break;
        }
    }
    
    /**
     * Update the navigation buttons (prev/next/submit)
     */
    function updateNavigationButtons() {
        const currentIndex = quizState.currentQuestionIndex;
        const $currentQuestion = $('.product-hunt-question.active');
        
        // Reset buttons
        $currentQuestion.find('.product-hunt-prev-button').show();
        $currentQuestion.find('.product-hunt-next-button').show();
        $currentQuestion.find('.product-hunt-submit-button').hide();
        
        // Hide prev button on first question
        if (currentIndex === 0) {
            $currentQuestion.find('.product-hunt-prev-button').hide();
        }
        
        // Show submit button on last question
        if (currentIndex === quizState.totalQuestions - 1) {
            $currentQuestion.find('.product-hunt-next-button').hide();
            $currentQuestion.find('.product-hunt-submit-button').show();
        }
    }
    
    /**
     * Update the progress bar
     */
    function updateProgressBar() {
        // Calculate progress percentage
        const progress = ((quizState.currentQuestionIndex + 1) / quizState.totalQuestions) * 100;
        
        // Update progress bar width
        $('.product-hunt-quiz-progress-bar').css('width', progress + '%');
    }
    
    /**
     * Complete the quiz and proceed to email capture or results
     */
    function completeQuiz() {
        // Validate current question
        if (!validateCurrentQuestion()) return;
        
        // Save answers from current question
        saveCurrentQuestionAnswers();
        
        // Show loading indicator
        $('.product-hunt-loading').show();
        $('.product-hunt-question').removeClass('active');
        
        // Check if we need to show email capture form
        if (quizState.emailCaptureEnabled && !quizState.userEmail) {
            showEmailCapture();
        } else {
            submitQuizData();
        }
    }
    
    /**
     * Show the email capture form
     */
    function showEmailCapture() {
        // Hide loading indicator
        $('.product-hunt-loading').hide();
        
        // Show email capture form
        $('.product-hunt-email-capture').addClass('active');
        
        // Focus on email input
        setTimeout(function() {
            $('.product-hunt-email-input').focus();
        }, 100);
    }
    
    /**
     * Process email capture form submission
     */
    function submitEmail() {
        const email = $('.product-hunt-email-input').val().trim();
        
        // Validate email
        if (!email || !isValidEmail(email)) {
            $('.product-hunt-email-error').addClass('show');
            return;
        }
        
        // Store email
        quizState.userEmail = email;
        
        // Hide error message
        $('.product-hunt-email-error').removeClass('show');
        
        // Submit quiz data with email
        submitQuizData();
    }
      /**
     * Submit quiz data to get results
     */
    function submitQuizData() {
        // Show loading indicator
        $('.product-hunt-loading').show();
        
        // Hide any open sections
        $('.product-hunt-question, .product-hunt-email-capture').removeClass('active');
        
        console.log('Submitting quiz data - Quiz ID: ' + quizState.quizId);
        
        // Prepare user data
        const userData = {
            email: quizState.userEmail || ''
        };
        
        // AJAX settings with improved error handling
        const ajaxSettings = {
            url: product_hunt_public.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'product_hunt_submit_quiz',
                nonce: product_hunt_public.nonce, // Fixed inconsistent nonce parameter name
                quiz_id: quizState.quizId,
                answers: quizState.answers,
                user_data: userData
            },
            // Longer timeout for slow local environments
            timeout: 30000,
            // Handle localhost-specific CORS issues
            crossDomain: false,
            xhrFields: {
                withCredentials: true
            },
            cache: false, // Prevent caching
            success: function(response) {
                console.log('Quiz submission response:', response);
                $('.product-hunt-loading').hide();
                
                if (response && response.success) {
                    // Store results
                    quizState.results = response.data.products || [];
                    
                    // Show results
                    showResults();
                } else {
                    // Error handling
                    console.error('Server returned error:', response.data);
                    showError(response && response.data && response.data.message 
                        ? response.data.message 
                        : 'There was a problem submitting your quiz. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                // Log comprehensive error information
                console.error('AJAX Error Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                // Special handling for localhost XAMPP issues
                if (xhr.responseText && xhr.responseText.includes('<?php')) {
                    console.log("Detected PHP parsing issue - trying to extract JSON from response");
                    tryParseLocalXamppResponse(xhr.responseText);
                } else {
                    $('.product-hunt-loading').hide();
                    showError('There was a problem connecting to the server. Please try again later.');
                }
            }
        };
        
        // Send the AJAX request
        $.ajax(ajaxSettings);
    }
      /**
     * Try to parse XAMPP responses which might contain PHP errors mixed with JSON
     * Improved with better JSON extraction logic
     */
    function tryParseLocalXamppResponse(responseText) {
        try {
            // More reliable method to extract JSON from mixed content
            const jsonStart = responseText.indexOf('{');
            const jsonEnd = responseText.lastIndexOf('}') + 1;
            
            if (jsonStart >= 0 && jsonEnd > jsonStart) {
                const jsonStr = responseText.substring(jsonStart, jsonEnd);
                const data = JSON.parse(jsonStr);
                
                console.log("Successfully extracted JSON:", data);
                
                $('.product-hunt-loading').hide();
                
                if (data.success) {
                    // Store results
                    quizState.results = data.data.products || [];
                    
                    // Show results
                    showResults();
                    return;
                } else {
                    showError(data.data && data.data.message 
                        ? data.data.message 
                        : 'An error occurred processing your quiz results.');
                    return;
                }
            }
        } catch (e) {
            console.error("Failed to extract valid JSON:", e);
        }
        
        // If we get here, we couldn't extract valid JSON
        $('.product-hunt-loading').hide();
        showError('There was a problem processing the server response. Please try again.');
    }
    
    /**
     * Show specific help for local environment issues
     */
    function showLocalEnvironmentHelp() {
        // Create local environment help message
        const errorHtml = `
            <div class="product-hunt-connection-error">
                <div class="product-hunt-error-icon">⚠️</div>
                <h3>Local Environment Issue</h3>
                <p>The connection error is likely caused by your local development environment (XAMPP). Here are some things to try:</p>
                <ul class="product-hunt-troubleshooting-tips">
                    <li>Check your PHP error log for server-side errors</li>
                    <li>Make sure WP_DEBUG is set to true in wp-config.php</li>
                    <li>Try increasing PHP memory limit in php.ini</li>
                    <li>Check that your AJAX URL is correct: <code>${product_hunt_public.ajax_url}</code></li>
                </ul>
                <div class="product-hunt-error-actions">
                    <button class="product-hunt-button product-hunt-retry-button">Try Again</button>
                    <button class="product-hunt-button product-hunt-show-dummy-button">Show Sample Results</button>
                </div>
            </div>
        `;
        
        // Show the error message
        $('.product-hunt-results').addClass('active').html(errorHtml);
        
        // Bind retry button
        $('.product-hunt-retry-button').on('click', function() {
            $('.product-hunt-results').removeClass('active');
            submitQuizData();
        });
        
        // Bind show dummy results button (helpful for development)
        $('.product-hunt-show-dummy-button').on('click', function() {
            showDummyResults();
        });
    }
    
    /**
     * Show dummy results for local testing
     */
    function showDummyResults() {
        // Create sample product data
        const sampleProducts = [
            {
                id: 1,
                name: 'Sample Product 1',
                price: '$49.99',
                permalink: '#',
                image: product_hunt_public.placeholder_img,
                score: 5
            },
            {
                id: 2,
                name: 'Sample Product 2',
                price: '$29.99',
                permalink: '#',
                image: product_hunt_public.placeholder_img,
                score: 4
            },
            {
                id: 3,
                name: 'Sample Product 3',
                price: '$39.99',
                permalink: '#',
                image: product_hunt_public.placeholder_img,
                score: 3
            }
        ];
        
        // Store as results
        quizState.results = sampleProducts;
        
        // Show results
        showResults();
    }
    
    /**
     * Display connection error with recovery options
     */
    function showConnectionError() {
        // Create connection error display
        const errorHtml = `
            <div class="product-hunt-connection-error">
                <div class="product-hunt-error-icon">❌</div>
                <h3>${product_hunt_public.i18n.connection_error_title || 'Connection Error'}</h3>
                <p>${product_hunt_public.i18n.connection_error_message || 'Unable to connect to the server. This could be due to your internet connection or a temporary server issue.'}</p>
                <div class="product-hunt-error-actions">
                    <button class="product-hunt-button product-hunt-retry-button">${product_hunt_public.i18n.retry_button || 'Try Again'}</button>
                    <button class="product-hunt-button product-hunt-restart-button">${product_hunt_public.i18n.restart_button || 'Restart Quiz'}</button>
                </div>
            </div>
        `;
        
        // Show the connection error
        $('.product-hunt-results').addClass('active').html(errorHtml);
        
        // Bind retry button
        $('.product-hunt-retry-button').on('click', function() {
            $('.product-hunt-results').removeClass('active');
            submitQuizData();
        });
        
        // Bind restart button
        $('.product-hunt-restart-button').on('click', function() {
            location.reload();
        });
    }
    
    /**
     * Display quiz results with product recommendations
     */
    function showResults() {
        // Hide email capture if visible
        $('.product-hunt-email-capture').removeClass('active');
        
        // Show results section
        $('.product-hunt-results').addClass('active');
        
        // If we have results, display them
        if (quizState.results && quizState.results.length > 0) {
            console.log('Displaying ' + quizState.results.length + ' product recommendations');
            displayProducts(quizState.results);
        } else {
            console.log('No product recommendations returned');
            // Show no results message
            $('.product-hunt-recommendations').html('<p class="no-recommendations">' + 
                product_hunt_public.i18n.no_recommendations + '</p>');
        }
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('.product-hunt-results').offset().top - 50
        }, 300);
    }
    
    /**
     * Display product recommendations
     */
    function displayProducts(products) {
        const $productsContainer = $('.product-hunt-recommendations');
        
        console.log('Displaying products:', products);
        
        // Clear existing products
        $productsContainer.empty();
        
        // Check if we have products
        if (!products || products.length === 0) {
            $productsContainer.html('<p class="product-hunt-no-results">' + 
                (product_hunt_public.i18n.no_recommendations || 'No product recommendations found.') + '</p>');
            return;
        }
        
        // Add products
        products.forEach(function(product) {
            // Ensure default values for any missing properties
            const image = product.image || product_hunt_public.placeholder_img;
            const name = product.name || 'Product';
            const price = product.price || '';
            const permalink = product.permalink || '#';
            
            const productHtml = `
                <div class="product-hunt-product" data-product-id="${product.id || 0}">
                    <div class="product-hunt-product-image" style="background-image: url('${image}')"></div>
                    <div class="product-hunt-product-info">
                        <h3 class="product-hunt-product-title">${name}</h3>
                        <div class="product-hunt-product-price">${price}</div>
                        <a href="${permalink}" class="product-hunt-product-button" target="_blank">
                            ${product_hunt_public.i18n.view_product || 'View Product'}
                        </a>
                    </div>
                </div>
            `;
            
            $productsContainer.append(productHtml);
            
            // Track product view for analytics
            if (typeof trackProductInteraction === 'function') {
                trackProductInteraction(product.id, 'view');
            }
        });
    }
    
    /**
     * Track product interactions (clicks, views)
     */
    function trackProductInteraction(productId, interactionType) {
        $.ajax({
            url: product_hunt_public.ajax_url,
            type: 'POST',
            data: {
                action: 'product_hunt_track_interaction',
                security: product_hunt_public.nonce,
                quiz_id: quizState.quizId,
                product_id: productId,
                interaction_type: interactionType
            }
        });
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        $('.product-hunt-error-message')
            .text(message)
            .addClass('active');
        
        setTimeout(function() {
            $('.product-hunt-error-message').removeClass('active');
        }, 5000);
    }
    
    /**
     * Helper: Find question by index
     */
    function findQuestionByIndex(index) {
        return quizState.questions.find(q => q.index === index);
    }
    
    /**
     * Helper: Find question index by ID
     */
    function findQuestionIndexById(id) {
        const question = quizState.questions.find(q => q.id === id);
        return question ? question.index : 0;
    }
    
    /**
     * Helper: Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

})(jQuery);