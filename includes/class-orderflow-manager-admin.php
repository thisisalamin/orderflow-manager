<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin class for the OrderFlow Manager for WooCommerce plugin.
 */
class OrderFlow_Manager_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function admin_menu() {
		add_submenu_page(
			'woocommerce',           // Parent slug: WooCommerce menu
			__( 'OrderFlow Manager', 'orderflow-manager-for-woocommerce' ), // Page title
			__( 'OrderFlow Manager', 'orderflow-manager-for-woocommerce' ), // Menu title
			'view_woocommerce_reports', // Capability required (adjust as needed)
			'ofm-order-dashboard',      // Menu slug (updated to 'ofm-order-dashboard')
			array( $this, 'admin_dashboard_page' ) // Callback function to render the page
		);
	}

	/**
	 * Render the admin dashboard page.
	 */
	public function admin_dashboard_page() {
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';
		$paged      = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

		include_once OFMW_PLUGIN_PATH . 'templates/admin-dashboard.php';
	}
}

new OrderFlow_Manager_Admin();