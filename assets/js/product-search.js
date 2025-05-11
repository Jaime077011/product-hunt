/**
 * Product Hunt Quiz - Product Search
 * Handles AJAX product search functionality for quiz questions
 */
(function($) {
    'use strict';
    
    // Track initialization to prevent duplicate handlers
    let isInitialized = false;
    
    $(document).ready(function() {
        console.log('Product search script loaded');
        initProductSearch();
        
        // Re-initialize on question added - important for dynamic content
        $(document).on('question_added answer_added', function(e, container) {
            console.log('Question/answer added event detected, initializing product search');
            initProductSearch(container);
        });
    });
    
    /**
     * Initialize product search functionality
     * @param {jQuery|undefined} container Optional container to limit the scope
     */
    function initProductSearch(container) {
        const $context = container || document;
        const $productSearch = $('.product-search-field', $context);
        
        console.log('Initializing product search, found fields:', $productSearch.length);
        
        $productSearch.each(function() {
            const $this = $(this);
            
            // Skip if already initialized to prevent duplicate handlers
            if ($this.data('initialized')) {
                console.log('Product search already initialized for this field, skipping');
                return;
            }
            
            const $productList = $this.closest('.answer-product-mapping').find('.selected-products');
            const $questionContainer = $this.closest('.quiz-question');
            const $answerContainer = $this.closest('.quiz-answer');
            
            console.log('Setting up autocomplete for product search field');
            
            // Set up autocomplete
            $this.autocomplete({
                minLength: 2,
                delay: 500,
                source: function(request, response) {
                    console.log('Searching for products with term:', request.term);
                    
                    // Show loading indicator
                    $this.addClass('searching');
                    
                    $.ajax({
                        url: phProductSearch.ajaxurl,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'ph_product_search',
                            term: request.term,
                            nonce: phProductSearch.nonce
                        },
                        success: function(data) {
                            console.log('Search response received:', data);
                            $this.removeClass('searching');
                            
                            if (data && data.success && data.data) {
                                response(data.data);
                            } else {
                                console.error('Product search error:', data);
                                response([]);
                                alert('Error searching for products. Please try again.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error:', status, error);
                            console.log('Response text:', xhr.responseText);
                            $this.removeClass('searching');
                            response([]);
                            alert('Error connecting to the server. Please try again.');
                        }
                    });
                },
                select: function(event, ui) {
                    console.log('Product selected:', ui.item);
                    event.preventDefault();
                    addSelectedProduct(ui.item, $productList, $questionContainer, $answerContainer);
                    $this.val('');
                    return false;
                }
            }).data('initialized', true);
            
            // Custom rendering of autocomplete items
            $this.autocomplete("instance")._renderItem = function(ul, item) {
                console.log('Rendering autocomplete item:', item);
                
                // Prepare the image HTML
                const imageHtml = item.image 
                    ? `<img src="${item.image}" alt="" class="product-thumbnail">` 
                    : `<div class="no-image"></div>`;
                
                return $("<li>")
                    .append(`<div class="product-item">${imageHtml}<span class="product-name">${item.label}</span></div>`)
                    .appendTo(ul);
            };
            
            // Add custom classes to the autocomplete menu
            $this.autocomplete("instance")._renderMenu = function(ul, items) {
                var that = this;
                $.each(items, function(index, item) {
                    that._renderItemData(ul, item);
                });
                $(ul).addClass("product-search-results");
            };
            
            // Apply CSS to make sure the autocomplete dropdown appears correctly
            $this.on('focus', function() {
                // Ensure the field's container has relative positioning for proper dropdown alignment
                $this.closest('.product-search').css('position', 'relative');
            });
        });
        
        // Handle remove product button clicks - use delegate event handling
        if (!isInitialized) {
            $(document).on('click', '.remove-product', function(e) {
                e.preventDefault();
                console.log('Remove product clicked');
                $(this).closest('.selected-product').remove();
                updateProductCount();
            });
            
            isInitialized = true;
        }
    }
    
    /**
     * Add a selected product to the list
     * @param {Object} product The product data
     * @param {jQuery} $productList The container for selected products
     * @param {jQuery} $questionContainer The question container
     * @param {jQuery} $answerContainer The answer container
     */
    function addSelectedProduct(product, $productList, $questionContainer, $answerContainer) {
        console.log('Adding selected product:', product);
        
        // Don't add if already in the list
        if ($productList.find(`[data-product-id="${product.id}"]`).length) {
            console.log('Product already in list, not adding duplicate');
            alert('This product is already added to this answer.');
            return;
        }
        
        // Get question and answer IDs for field names
        const questionId = $questionContainer.data('question-id') || 
                          $questionContainer.attr('id').replace('question-', '') || 
                          `new_q_${Date.now()}`;
                          
        const answerId = $answerContainer.data('answer-id') || 
                        $answerContainer.attr('id').replace('answer-', '') || 
                        `new_a_${Date.now()}`;
        
        console.log(`Adding product ${product.id} to question ${questionId}, answer ${answerId}`);
        
        // Create selected product HTML
        const $productItem = $(
            `<div class="selected-product" data-product-id="${product.id}">
                <span class="product-name">${product.value}</span>
                <input type="hidden" name="questions[${questionId}][answers][${answerId}][products][]" value="${product.id}">
                <a href="#" class="remove-product dashicons dashicons-no-alt" title="Remove"></a>
            </div>`
        );
        
        // Add to the list
        $productList.append($productItem);
        
        // Update product count
        updateProductCount();
    }
    
    /**
     * Update product count indicators
     */
    function updateProductCount() {
        $('.quiz-answer').each(function() {
            const $answer = $(this);
            const $productList = $answer.find('.selected-products');
            const count = $productList.find('.selected-product').length;
            
            $answer.find('.product-count').text(count);
        });
    }
    
})(jQuery);
