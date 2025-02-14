<?php
/**
 * @var string $start_date
 * @var string $end_date
 * @var int    $paged
 * @var int    $per_page
 */

$per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10; // Orders per page
$paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1; // Current page

// Add these lines after $paged definition
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Handle date range input
if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
    $dates = explode(' to ', $_GET['date_range']);
    $start_date = $dates[0];
    $end_date = isset($dates[1]) ? $dates[1] : '';
} else {
    $start_date = '';
    $end_date = '';
}

$args = array(
    'limit'   => $per_page,
    'page'    => $paged,
    'orderby' => $orderby,
    'order'   => $order,
);

if (!empty($start_date) && !empty($end_date)) {
    $args['date_created'] = $start_date . '...' . $end_date;
} elseif (!empty($start_date)) {
    $args['date_created'] = '>=' . $start_date;
} elseif (!empty($end_date)) {
    $args['date_created'] = '<=' . $end_date;
}

$orders = wc_get_orders($args);

// Replace this line:
// $total_orders_count = wc_get_orders(array_merge($args, array('count' => true, 'limit' => -1)));

// With these lines:
$count_args = array(
    'limit' => -1,
    'return' => 'ids',
);
if (!empty($start_date) && !empty($end_date)) {
    $count_args['date_created'] = $start_date . '...' . $end_date;
} elseif (!empty($start_date)) {
    $count_args['date_created'] = '>=' . $start_date;
} elseif (!empty($end_date)) {
    $count_args['date_created'] = '<=' . $end_date;
}
$total_orders_count = count(wc_get_orders($count_args));

$total_pages = ceil((int)$total_orders_count / $per_page); // Fixed line: Explicitly cast to integer

?>
<div class="container mx-auto p-6">

    <div class="bg-white rounded-lg shadow-md p-6 space-y-6">
        <!-- Header Section -->
        <header class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">OrderFlow Manager</h2>
            </div>
            <form method="get" action="" class="flex items-center space-x-4">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">  <!-- Keep plugin page slug -->
                <div class="flex items-center">
                    <!-- <label for="date-range" class="block text-sm font-medium text-gray-700 mr-2">Date Range</label> -->
                    <input type="text" id="date-range" name="date_range" value="<?php echo esc_attr($start_date . (!empty($end_date) ? ' to ' . $end_date : '')); ?>" class="border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm p-2">
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    <i class="fas fa-filter mr-2"></i> Filter Orders
                </button>
                <button type="button" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                    <i class="fas fa-download mr-2"></i> Download Report
                </button>
            </form>
        </header>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <?php
                        $columns = array(
                            'id' => 'Order ID',
                            'customer' => 'Customer',
                            'total' => 'Total',
                            'date' => 'Order Date',
                            'status' => 'Status'
                        );

                        foreach ($columns as $column_key => $column_label) {
                            $current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'date';
                            $current_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
                            
                            $new_order = ($current_orderby === $column_key && $current_order === 'ASC') ? 'DESC' : 'ASC';
                            $sort_url = add_query_arg(array(
                                'orderby' => $column_key,
                                'order' => $new_order
                            ));

                            $is_current = $current_orderby === $column_key;
                            $sort_indicator = $is_current ? ($current_order === 'ASC' ? '↑' : '↓') : '';
                            ?>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="<?php echo esc_url($sort_url); ?>" class="group inline-flex items-center space-x-1 hover:text-gray-900">
                                    <span><?php echo esc_html($column_label); ?></span>
                                    <span class="text-gray-400 group-hover:text-gray-500"><?php echo $sort_indicator; ?></span>
                                </a>
                            </th>
                        <?php } ?>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ( $orders ) : ?>
                        <?php foreach ( $orders as $order ) : ?>
                            <?php 
                            // Skip if order is not a valid order object
                            if (!is_a($order, 'WC_Order')) {
                                continue;
                            }
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    // Safely get order number
                                    $order_number = method_exists($order, 'get_order_number') ? $order->get_order_number() : $order->get_id();
                                    echo esc_html($order_number);
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    $first_name = $order->get_billing_first_name();
                                    $last_name = $order->get_billing_last_name();
                                    if (!empty($first_name) || !empty($last_name)) : ?>
                                        <?php echo esc_html(trim($first_name . ' ' . $last_name)); ?>
                                    <?php else : ?>
                                        <?php esc_html_e('Guest', 'orderflow-manager-for-woocommerce'); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="order-status-wrapper" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
                                        <?php
                                        $status = $order->get_status();
                                        $status_colors = array(
                                            'pending' => 'bg-yellow-500',
                                            'processing' => 'bg-blue-500',
                                            'on-hold' => 'bg-orange-500',
                                            'completed' => 'bg-green-500',
                                            'cancelled' => 'bg-red-500',
                                            'refunded' => 'bg-purple-500',
                                            'failed' => 'bg-gray-500'
                                        );
                                        $status_color = isset($status_colors[$status]) ? $status_colors[$status] : 'bg-gray-500';
                                        ?>
                                        <span class="status-display inline-flex items-center">
                                            <span class="inline-block h-2.5 w-2.5 rounded-full <?php echo esc_attr($status_color); ?> mr-2"></span>
                                            <?php echo esc_html( wc_get_order_status_name( $status ) ); ?>
                                        </span>
                                        <select class="status-select hidden">
                                            <option value="pending" <?php selected( $status, 'pending' ); ?>>Pending</option>
                                            <option value="processing" <?php selected( $status, 'processing' ); ?>>Processing</option>
                                            <option value="on-hold" <?php selected( $status, 'on-hold' ); ?>>On Hold</option>
                                            <option value="completed" <?php selected( $status, 'completed' ); ?>>Completed</option>
                                            <option value="cancelled" <?php selected( $status, 'cancelled' ); ?>>Cancelled</option>
                                            <option value="refunded" <?php selected( $status, 'refunded' ); ?>>Refunded</option>
                                            <option value="failed" <?php selected( $status, 'failed' ); ?>>Failed</option>
                                        </select>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <a href="#" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>" class="text-blue-500 hover:text-blue-700 view-order-link">View Order</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <?php esc_html_e( 'No orders found.', 'orderflow-manager-for-woocommerce' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Start Pagination -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium"><?php echo ($paged - 1) * $per_page + 1; ?></span>
                    to
                    <span class="font-medium"><?php echo min($paged * $per_page, $total_orders_count); ?></span>
                    of
                    <span class="font-medium"><?php echo $total_orders_count; ?></span>
                    results
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-xs" aria-label="Pagination">
                    <?php if ($paged > 1) : ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['paged' => $paged - 1])); ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                            <span class="sr-only">Previous</span>
                            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['paged' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold <?php echo $i === $paged ? 'bg-indigo-600 text-white' : 'text-gray-900 ring-1 ring-gray-300 ring-inset hover:bg-gray-50'; ?> focus:z-20 focus:outline-offset-0">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($paged < $total_pages) : ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['paged' => $paged + 1])); ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                            <span class="sr-only">Next</span>
                            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
        <!-- End Pagination -->

        <!-- Order Details Modal -->
        <div id="order-details-modal" class="fixed z-10 inset-0 overflow-y-auto hidden">
            <div class="flex items-center justify-center min-h-screen">
                <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
                    <form id="order-details-content" class="space-y-4">
                        <!-- Order details will be loaded here -->
                        <input type="hidden" name="order_id" id="order_id">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="on-hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="refunded">Refunded</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div>
                            <label for="customer_note" class="block text-sm font-medium text-gray-700">Customer Note</label>
                            <textarea name="customer_note" id="customer_note" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            Update Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>

</div>

<!-- Include flatpickr library -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr("#date-range", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: ["<?php echo esc_js($start_date); ?>", "<?php echo esc_js($end_date); ?>"],
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0].toISOString().split('T')[0];
                    const endDate = selectedDates[1].toISOString().split('T')[0];
                    instance.input.value = startDate + ' to ' + endDate;
                } else if (selectedDates.length === 1) {
                    const startDate = selectedDates[0].toISOString().split('T')[0];
                    instance.input.value = startDate;
                }
            }
        });
    });
</script>