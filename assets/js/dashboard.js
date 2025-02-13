jQuery(document).ready(function ($) {
    // Add status color mapping
    const statusColors = {
        'pending': 'bg-yellow-500',
        'processing': 'bg-blue-500',
        'on-hold': 'bg-orange-500',
        'completed': 'bg-green-500',
        'cancelled': 'bg-red-500',
        'refunded': 'bg-purple-500',
        'failed': 'bg-gray-500'
    };

    // Enable inline editing for the status column
    $('.order-status-wrapper').off('click', '.status-display').on('click', '.status-display', function () {
        const $wrapper = $(this).closest('.order-status-wrapper');
        $wrapper.find('.status-display').addClass('hidden');
        $wrapper.find('.status-select').removeClass('hidden').focus();
    });

    // Handle status change
    $('.order-status-wrapper').off('change', '.status-select').on('change', '.status-select', function () {
        const $select = $(this);
        const newStatus = $select.val();
        const orderId = $select.closest('.order-status-wrapper').data('order-id');
        const $wrapper = $select.closest('.order-status-wrapper');
        const $statusDot = $wrapper.find('.status-display span.inline-block');
        const $statusDisplay = $select.siblings('.status-display');

        // Show loading indicator
        $statusDisplay.text('Updating...').removeClass('hidden');
        $select.addClass('hidden');

        // Log the request payload
        console.log('Sending AJAX request to update status:', {
            orderId: orderId,
            status: newStatus
        });

        // Send AJAX request to update the status
        $.ajax({
            url: `/wp-json/orderflow-manager/v1/order/${orderId}`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ status: newStatus }),
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce); // Ensure nonce is set correctly
            },
            success: function (response) {
                console.log('AJAX Success:', response);
                if (response.message) {
                    // Update the status display with the new value
                    $statusDisplay.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    $statusDisplay.removeClass('hidden');
                    $select.addClass('hidden');

                    // Update the status dot color
                    $statusDot.removeClass(Object.values(statusColors).join(' '))
                             .addClass(statusColors[newStatus]);

                    alert('Order status updated successfully!');
                } else {
                    console.log('Unexpected response:', response);
                    $statusDisplay.text('Error').removeClass('hidden');
                    $select.addClass('hidden');
                }
            },
            error: function (xhr, status, error) {
                console.log('AJAX Error:', status, error);
                console.log('Response Text:', xhr.responseText);
                alert('Failed to update the order status. Please try again.');
                $statusDisplay.text('Error').removeClass('hidden');
                $select.addClass('hidden');
            },
        });
    });

    // Hide dropdown on blur without changes
    $('.order-status-wrapper').off('blur', '.status-select').on('blur', '.status-select', function () {
        const $wrapper = $(this).closest('.order-status-wrapper');
        $wrapper.find('.status-display').removeClass('hidden');
        $wrapper.find('.status-select').addClass('hidden');
    });
});
