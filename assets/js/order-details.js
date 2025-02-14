jQuery(document).ready(function ($) {
    // Attach click event to view-order-link
    $('.view-order-link').on('click', function (e) {
        e.preventDefault();

        const orderId = $(this).data('order-id');
        const $modal = $('#order-details-modal');
        const $modalContent = $('#order-details-content');

        // Show loading state
        $modalContent.html('<div class="text-center">Loading order details...</div>');
        $modal.removeClass('hidden');

        // Fetch order details via AJAX
        $.getJSON(`/wp-json/orderflow-manager/v1/order/${orderId}`, function (data) {
            let itemsHtml = '';
            data.items.forEach(function (item) {
                itemsHtml += `
                    <div class="flex justify-between py-2 border-b border-gray-200 last:border-b-0">
                        <div>${item.name} (x${item.quantity})</div>
                        <div>
                            <span class="text-sm text-gray-500">${data.currency}</span> ${item.total}
                        </div>
                    </div>
                `;
            });

            $modalContent.html(`
                <div id="invoice-content" class="bg-gray-50 p-6 rounded-md">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6 border-b border-gray-300 pb-2">Order #${data.id}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">Customer Information</h3>
                            <div class="text-gray-600 space-y-2">
                                <p><strong class="font-medium text-gray-800">Customer:</strong> ${data.customer || 'N/A'}</p>
                                <p><strong class="font-medium text-gray-800">Email:</strong> ${data.email || 'N/A'}</p>
                                <p><strong class="font-medium text-gray-800">Phone:</strong> ${data.phone || 'N/A'}</p>
                                <p><strong class="font-medium text-gray-800">Address:</strong> ${data.address || 'N/A'}</p>
                                <p><strong class="font-medium text-gray-800">Note:</strong> ${data.customer_note || 'N/A'}</p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">Order Summary</h3>
                            <div class="text-gray-600 space-y-2">
                                <p><strong class="font-medium text-gray-800">Total:</strong> ${data.total || 'N/A'}</p>
                                <p><strong class="font-medium text-gray-800">Order Date:</strong> ${data.date.formatted} ${data.date.timezone.abbr} (${data.date.timezone.name})</p>
                                <p><strong class="font-medium text-gray-800">Status:</strong> <span class="${getStatusBadgeClass(data.status)}">${data.status || 'N/A'}</span></p>
                                <p><strong class="font-medium text-gray-800">Discount:</strong> ${data.discount || 'N/A'}</p>
                                <p><strong class="font-medium text-gray-800">Shipping:</strong> ${data.shipping || 'N/A'}</p>
                                <p><strong class="font-medium text-gray-800">Payment Method:</strong> ${data.payment_method || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Items</h3>
                        <div class="text-gray-600">
                            ${itemsHtml || '<p>No items in this order.</p>'}
                        </div>
                    </div>
                    <div class="flex justify-between items-center gap-3 mt-6 border-t pt-4">
                        <button type="button" id="close-modal" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm">
                            Close
                        </button>
                        <div class="flex gap-3">
                            <a href="/wp-admin/post.php?post=${data.id}&action=edit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm">
                                Edit Order
                            </a>
                            <button class="generate-invoice-btn px-4 py-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 text-sm">
                                Generate Invoice
                            </button>
                            <button class="send-email-btn px-4 py-2 bg-green-50 text-green-600 rounded hover:bg-green-100 text-sm">
                                Send Email
                            </button>
                        </div>
                    </div>
                </div>
            `);
        });
    });

    // Remove old print handler

    // Add new button handlers
    $(document).on('click', '.generate-invoice-btn', function() {
        const orderId = $(this).closest('#invoice-content').find('h2').text().replace('Order #', '');
        const $modal = $('#order-details-modal');

        // Show loading state
        $modal.html('<div class="fixed inset-0 bg-white z-[9999] flex items-center justify-center">Loading invoice...</div>');

        // Fetch order details for invoice
        $.getJSON(`/wp-json/orderflow-manager/v1/order/${orderId}`, function (data) {
            let itemsHtml = '';
            let subtotal = 0;

            data.items.forEach(function (item) {
                const amount = parseFloat(item.total);
                subtotal += amount;
                itemsHtml += `
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <div class="col-span-full sm:col-span-2">
                            <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">Item</h5>
                            <p class="font-medium text-gray-800">${item.name}</p>
                        </div>
                        <div>
                            <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">Qty</h5>
                            <p class="text-gray-800">${item.quantity}</p>
                        </div>
                        <div>
                            <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">Rate</h5>
                            <p class="text-gray-800">${data.currency}${(amount / item.quantity).toFixed(2)}</p>
                        </div>
                        <div>
                            <h5 class="sm:hidden text-xs font-medium text-gray-500 uppercase">Amount</h5>
                            <p class="sm:text-end text-gray-800">${data.currency}${amount.toFixed(2)}</p>
                        </div>
                    </div>
                    <div class="sm:hidden border-b border-gray-200"></div>
                `;
            });

            const tax = parseFloat(data.tax || 0);
            const total = subtotal + tax;

            const invoiceHtml = `
                <div class="invoice-print-container fixed inset-0 bg-white z-[9999] overflow-y-auto">
                    <div class="sticky top-0 left-0 right-0 bg-white border-b border-gray-200 p-4 flex justify-end gap-2 z-[99999]">
                        <button type="button" class="close-invoice-view py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Close
                        </button>
                        <button type="button" class="print-invoice-btn py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                            Print
                        </button>
                    </div>
                    <div class="max-w-[85rem] px-4 sm:px-6 lg:px-8 mx-auto my-4 sm:my-10">
                        <div class="sm:w-11/12 lg:w-3/4 mx-auto bg-white shadow-md rounded-xl">
                            <div class="flex flex-col p-4 sm:p-10 bg-white shadow-md rounded-xl">
                                <div class="flex justify-between">
                                    <div>
                                        <svg class="size-10" width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 26V13C1 6.37258 6.37258 1 13 1C19.6274 1 25 6.37258 25 13C25 19.6274 19.6274 25 13 25H12" class="stroke-blue-600" stroke="currentColor" stroke-width="2"/>
                                            <path d="M5 26V13.16C5 8.65336 8.58172 5 13 5C17.4183 5 21 8.65336 21 13.16C21 17.6666 17.4183 21.32 13 21.32H12" class="stroke-blue-600" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="13" cy="13.0214" r="5" fill="currentColor" class="fill-blue-600"/>
                                        </svg>
                                        <h1 class="mt-2 text-lg md:text-xl font-semibold text-blue-600">${data.store.name}</h1>
                                    </div>
                                    <div class="text-end">
                                        <h2 class="text-2xl md:text-3xl font-semibold text-gray-800">Invoice #${orderId}</h2>
                                        <span class="mt-1 block text-gray-500">${data.date.formatted}</span>
                                        <address class="mt-4 not-italic text-gray-800">
                                            ${data.store.address.address_1}<br>
                                            ${data.store.address.address_2 ? data.store.address.address_2 + '<br>' : ''}
                                            ${data.store.address.city}, ${data.store.address.state} ${data.store.address.postcode}<br>
                                            ${data.store.address.country}
                                        </address>
                                    </div>
                                </div>

                                <div class="mt-8 grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">Bill to:</h3>
                                        <h3 class="text-lg font-semibold text-gray-800">${data.customer}</h3>
                                        <address class="mt-2 not-italic text-gray-500">
                                            ${data.address || 'N/A'}<br>
                                            ${data.email || 'N/A'}<br>
                                            ${data.phone || 'N/A'}
                                        </address>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <div class="border border-gray-200 p-4 rounded-lg space-y-4">
                                        <div class="hidden sm:grid sm:grid-cols-5">
                                            <div class="sm:col-span-2 text-xs font-medium text-gray-500 uppercase">Item</div>
                                            <div class="text-start text-xs font-medium text-gray-500 uppercase">Qty</div>
                                            <div class="text-start text-xs font-medium text-gray-500 uppercase">Rate</div>
                                            <div class="text-end text-xs font-medium text-gray-500 uppercase">Amount</div>
                                        </div>
                                        <div class="hidden sm:block border-b border-gray-200"></div>
                                        ${itemsHtml}
                                    </div>
                                </div>

                                <div class="mt-8 flex sm:justify-end">
                                    <div class="w-full max-w-2xl sm:text-end space-y-2">
                                        <div class="grid grid-cols-2 sm:grid-cols-1 gap-3 sm:gap-2">
                                            <dl class="grid sm:grid-cols-5 gap-x-3">
                                                <dt class="col-span-3 font-semibold text-gray-800">Subtotal:</dt>
                                                <dd class="col-span-2 text-gray-500">${data.currency}${subtotal.toFixed(2)}</dd>
                                            </dl>
                                            <dl class="grid sm:grid-cols-5 gap-x-3">
                                                <dt class="col-span-3 font-semibold text-gray-800">Tax:</dt>
                                                <dd class="col-span-2 text-gray-500">${data.currency}${tax.toFixed(2)}</dd>
                                            </dl>
                                            <dl class="grid sm:grid-cols-5 gap-x-3">
                                                <dt class="col-span-3 font-semibold text-gray-800">Total:</dt>
                                                <dd class="col-span-2 text-gray-500">${data.currency}${total.toFixed(2)}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 sm:mt-12">
                                    <h4 class="text-lg font-semibold text-gray-800">Thank you!</h4>
                                    <p class="text-gray-500">If you have any questions concerning this invoice, use the following contact information:</p>
                                    <div class="mt-2">
                                        <p class="block text-sm font-medium text-gray-800">${data.store.email}</p>
                                        <p class="block text-sm font-medium text-gray-800">${data.store.phone}</p>
                                    </div>
                                </div>

                                <p class="mt-5 text-sm text-gray-500">© ${new Date().getFullYear()} ${data.store.name}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $modal.html(invoiceHtml);

            // Add close button handler
            $('.close-invoice-view').on('click', function() {
                $modal.html('');
                location.reload(); // Reload to restore WordPress dashboard state
            });

            // Update print styles to handle fixed positioning
            $('.print-invoice-btn').on('click', function() {
                const orderId = $(this).closest('.invoice-print-container').find('h2').text().replace('Invoice #', '');
                const fileName = `invoice-${orderId}.pdf`;

                // Store current document title
                const originalTitle = document.title;
                
                // Change title to filename for save suggestion
                document.title = fileName;

                const $printContent = $('.invoice-print-container').clone();
                
                // Remove action buttons from the clone
                $printContent.find('.sticky').remove();
        
                // Create a new print container
                const $printContainer = $('<div>')
                    .attr('id', 'print-container')
                    .css({
                        'position': 'fixed',
                        'left': '-9999px',
                        'top': '-9999px'
                    })
                    .append($printContent)
                    .appendTo('body');
        
                const printStyles = `
                    <style type="text/css" media="print">
                        /* Hide everything except print container */
                        body > *:not(#print-container) {
                            display: none !important;
                        }
        
                        /* Show print container */
                        #print-container {
                            display: block !important;
                            position: static !important;
                            left: auto !important;
                            top: auto !important;
                            margin: 0 !important;
                            padding: 20px !important;
                        }
        
                        /* Preserve layout */
                        .flex { display: flex !important; }
                        .grid { display: grid !important; }
                        .block { display: block !important; }
                        
                        /* Reset container widths */
                        .max-w-\\[85rem\\],
                        .sm\\:w-11\\/12,
                        .lg\\:w-3\\/4 {
                            max-width: none !important;
                            width: 100% !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }
        
                        /* Remove decorative styles */
                        .shadow-md,
                        .rounded-xl {
                            box-shadow: none !important;
                            border-radius: 0 !important;
                        }
        
                        /* Force colors and backgrounds */
                        body {
                            background: white !important;
                        }
                        * {
                            color: black !important;
                            background: transparent !important;
                        }
        
                        /* Keep borders */
                        .border,
                        .border-t,
                        .border-b {
                            border-color: #000 !important;
                        }
        
                        @page {
                            size: A4;
                            margin: 1.5cm;
                        }

                        /* Force filename for PDF save */
                        @page :first {
                            margin-top: 0;
                        }
                    </style>
                `;
        
                // Add print styles
                $('head').append(printStyles);
        
                // Print with delay to ensure content is ready
                setTimeout(() => {
                    window.print();
                    
                    // Cleanup after printing
                    setTimeout(() => {
                        document.title = originalTitle;
                        $printContainer.remove();
                        $('head').find('style').last().remove();
                    }, 100);
                }, 100);
            });

            // Remove PDF download handler - no longer needed
        });
    });

    $(document).on('click', '.send-email-btn', function() {
        const orderId = $(this).closest('#invoice-content').find('h2').text().replace('Order #', '');
        // Add your email sending logic here
        alert('Sending email for order ' + orderId);
    });

    // Close modal
    $(document).on('click', '#close-modal', function(e) {
        e.preventDefault();
        $('#order-details-modal').addClass('hidden');
    });

    // Helper function for status badges
    function getStatusBadgeClass(status) {
        status = status ? status.toLowerCase() : '';
        switch (status) {
            case 'completed':
                return 'inline-flex items-center px-3 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
            case 'processing':
            case 'on-hold':
                return 'inline-flex items-center px-3 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
            case 'cancelled':
            case 'refunded':
            case 'failed':
                return 'inline-flex items-center px-3 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800';
            default:
                return 'inline-flex items-center px-3 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
        }
    }
});