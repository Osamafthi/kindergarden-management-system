/**
 * Arabic to Western Numeral Converter
 * Automatically converts Arabic numerals (٠-٩) to Western numerals (0-9)
 * in all numeric and phone input fields across the application.
 */

(function() {
    'use strict';

    // Arabic to Western numeral mapping
    const arabicToWestern = {
        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
        '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
    };

    /**
     * Convert Arabic numerals to Western numerals
     * @param {string} text - Text containing Arabic numerals
     * @returns {string} - Text with Western numerals
     */
    function convertArabicToWestern(text) {
        if (!text) return text;
        
        return text.replace(/./g, function(char) {
            return arabicToWestern[char] || char;
        });
    }

    /**
     * Process an input field and convert Arabic numerals
     * Maintains cursor position during conversion
     * @param {HTMLInputElement} input - The input element to process
     */
    function processInput(input) {
        if (!input) return;
        
        const originalValue = input.value;
        const convertedValue = convertArabicToWestern(originalValue);
        
        // Only update if conversion changed the value
        if (originalValue !== convertedValue) {
            const cursorPosition = input.selectionStart;
            input.value = convertedValue;
            
            // Adjust cursor position based on changed length
            const positionDiff = convertedValue.length - originalValue.length;
            input.setSelectionRange(cursorPosition + positionDiff, cursorPosition + positionDiff);
        }
    }

    /**
     * Attach conversion listeners to existing input fields
     */
    function attachListeners() {
        // Find all number and tel input fields
        const numericInputs = document.querySelectorAll('input[type="number"], input[type="tel"]');
        
        numericInputs.forEach(function(input) {
            // Skip if listener already attached
            if (input.dataset.arabicConverter === 'true') return;
            
            input.dataset.arabicConverter = 'true';
            
            // Attach input event listener for real-time conversion
            input.addEventListener('input', function() {
                processInput(this);
            });
            
            // Also attach to beforeinput to catch before the browser processes it
            if ('onbeforeinput' in input) {
                input.addEventListener('beforeinput', function(e) {
                    if (e.data) {
                        const converted = convertArabicToWestern(e.data);
                        if (converted !== e.data) {
                            e.preventDefault();
                            const target = e.target;
                            const start = target.selectionStart || 0;
                            const end = target.selectionEnd || 0;
                            const value = target.value || '';
                            target.value = value.substring(0, start) + converted + value.substring(end);
                            target.setSelectionRange(start + converted.length, start + converted.length);
                        }
                    }
                });
            }
        });
    }

    /**
     * Initialize converter on page load
     */
    function init() {
        // Process existing fields
        attachListeners();
        
        // Watch for dynamically added fields
        const observer = new MutationObserver(function(mutations) {
            let shouldCheck = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const hasInput = node.tagName === 'INPUT' || 
                                           node.querySelector && node.querySelector('input[type="number"], input[type="tel"]');
                            if (hasInput) {
                                shouldCheck = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldCheck) {
                attachListeners();
            }
        });
        
        // Start observing the document
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            init();
            // Re-attach listeners after a short delay to catch any scripts that add listeners during initialization
            setTimeout(attachListeners, 100);
        });
    } else {
        init();
        // Re-attach listeners after a short delay to catch any scripts that add listeners during initialization
        setTimeout(attachListeners, 100);
    }
})();
