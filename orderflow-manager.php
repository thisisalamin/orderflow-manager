<?php
/**
 * Plugin Name: OrderFlow Manager for WooCommerce
 * Description: Enhanced WooCommerce order management dashboard with advanced features.
 * Version: 1.0.0
 * Author: Crafely
 * Author URI: https://crafely.com
 * Text Domain: orderflow-manager-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.3
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

if ( ! class_exists( 'OrderFlow_Manager_For_WooCommerce' ) ) :

	/**
	 * Main plugin class.
	 */
	class OrderFlow_Manager_For_WooCommerce {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();
		}

		/**
		 * Define plugin constants.
		 */
		private function define_constants() {
			define( 'OFMW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
			define( 'OFMW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			define( 'OFMW_VERSION', $this->version );
		}

		/**
		 * Include required files.
		 */
		private function includes() {
			include_once OFMW_PLUGIN_PATH . 'includes/class-orderflow-manager-admin.php';
			require_once plugin_dir_path(__FILE__) . 'includes/api.php';
		}

		/**
		 * Initialize hooks.
		 */
		private function init_hooks() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		}

		/**
		 * Enqueue admin scripts and styles.
		 *
		 * @param string $hook The current admin page hook.
		 */
		public function enqueue_admin_scripts( $hook ) {
			// Only enqueue on our plugin's admin page
			if ( 'woocommerce_page_ofm-order-dashboard' !== $hook ) { // Updated slug to 'ofm-order-dashboard'
				return;
			}

			wp_enqueue_style( 'ofmw-styles', OFMW_PLUGIN_URL . 'assets/css/styles.css', array(), OFMW_VERSION );
			wp_enqueue_style( 'ofmw-tailwind-admin', OFMW_PLUGIN_URL . 'assets/css/tailwind.css', array(), OFMW_VERSION );
			wp_enqueue_script( 'ofmw-order-details', OFMW_PLUGIN_URL . 'assets/js/order-details.js', array( 'jquery' ), OFMW_VERSION, true ); // If you need JS
			wp_enqueue_script( 'ofmw-dashboard', OFMW_PLUGIN_URL . 'assets/js/dashboard.js', array( 'jquery' ), OFMW_VERSION, true );
		}

		/**
		 * Get the plugin URL.
		 *
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugin_dir_url( __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}
	}

endif;

/**
 * Instantiate the main plugin class.
 */
function ofmw_init() {
	return new OrderFlow_Manager_For_WooCommerce();
}

// Global for access in templates and functions.
$GLOBALS['orderflow_manager_for_woocommerce'] = ofmw_init();