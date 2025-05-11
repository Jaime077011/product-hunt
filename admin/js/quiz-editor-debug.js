/**
 * Debug version of Quiz Editor JavaScript
 * This version adds extra logging to track event bindings
 */
(function($) {
    'use strict';
    
    console.log('🔍 DEBUG: Loading Quiz Editor Debug Script');
    
    // Override jQuery's on method to track all bindings
    const originalOn = $.fn.on;
    $.fn.on = function() {
        console.log('🔍 DEBUG: Event binding:', arguments[0], 'on selector:', this.selector || this[0]);
        return originalOn.apply(this, arguments);
    };
    
    // Rest of the quiz editor code...
    // ...
})(jQuery);
