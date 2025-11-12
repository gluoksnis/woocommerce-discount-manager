/**
 * WooCommerce Discount Manager - Admin Script
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Add any custom JavaScript functionality here
        
        // Example: Confirm before clearing all discounts
        $('#clear_all').on('change', function() {
            if ($(this).is(':checked')) {
                if (!confirm('Are you sure you want to clear ALL product discounts?')) {
                    $(this).prop('checked', false);
                }
            }
        });
    });

})(jQuery);
