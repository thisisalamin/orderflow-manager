<?php

add_action('rest_api_init', function () {
    register_rest_route('orderflow-manager/v1', '/order/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_order_details',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('orderflow-manager/v1', '/order/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_order_details',
        'permission_callback' => '__return_true',
    ));
});

function get_order_details($data) {
    $order_id = $data['id'];
    $order = wc_get_order($order_id);

    if (!$order) {
        return new WP_Error('no_order', 'Order not found', array('status' => 404));
    }

    $items = array();
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $items[] = array(
            'name' => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'total' => $item->get_total(),
            'product_id' => $product ? $product->get_id() : null,
        );
    }

    $wp_timezone = wp_timezone();
    $date_created = $order->get_date_created();
    $date_created->setTimezone($wp_timezone);

    $response = array(
        'id' => $order->get_id(),
        'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'email' => $order->get_billing_email(),
        'phone' => $order->get_billing_phone(),
        'currency' => get_woocommerce_currency_symbol(),
        'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . ', ' . $order->get_billing_city() . ', ' . $order->get_billing_state() . ' ' . $order->get_billing_postcode(),
        'total' => $order->get_formatted_order_total(),
        'date' => array(
            'raw' => $date_created->format('c'),
            'formatted' => $date_created->date_i18n(
                get_option('date_format') . ' ' . get_option('time_format')
            ),
            'timezone' => array(
                'name' => $wp_timezone->getName(),
                'offset' => $wp_timezone->getOffset($date_created) / 3600, // Convert seconds to hours
                'abbr' => $date_created->format('T')
            )
        ),
        'status' => wc_get_order_status_name($order->get_status()),
        'items' => $items,
        'discount' => $order->get_discount_total(),
        'shipping' => $order->get_shipping_total(),
        'payment_method' => $order->get_payment_method_title(),
        'customer_note' => $order->get_customer_note(),
        'store' => array(
            'name' => get_option('woocommerce_store_name', get_bloginfo('name')),
            'address' => array(
                'address_1' => get_option('woocommerce_store_address'),
                'address_2' => get_option('woocommerce_store_address_2'),
                'city' => get_option('woocommerce_store_city'),
                'state' => get_option('woocommerce_store_state'),
                'postcode' => get_option('woocommerce_store_postcode'),
                'country' => get_option('woocommerce_default_country')
            )
        )
    );

    return rest_ensure_response($response);
}

function update_order_details($data) {
    $order_id = $data['id'];
    $order = wc_get_order($order_id);

    if (!$order) {
        return new WP_Error('no_order', 'Order not found', array('status' => 404));
    }

    $params = $data->get_params();
    if (isset($params['status'])) {
        $order->set_status($params['status']);
    }
    if (isset($params['customer_note'])) {
        $order->set_customer_note ( $params['customer_note'] );
    }
    // Add more fields as needed

    $order->save();

    return rest_ensure_response(array('message' => 'Order updated successfully.'));
}

function enqueue_orderflow_manager_scripts() {
    wp_enqueue_script('orderflow-manager-dashboard', plugin_dir_url(__FILE__) . '../assets/js/dashboard.js', array('jquery'), '1.0.0', true);
    wp_localize_script('orderflow-manager-dashboard', 'wpApiSettings', array(
        'root' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_orderflow_manager_scripts');
