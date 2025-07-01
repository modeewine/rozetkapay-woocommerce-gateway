<?php
/**
 * Plugin Name: Buy with RozetkaPay Gateway for WooCommerce
 * Plugin URI:
 * Description: WooCommerce Payment Gateway for Buy with RozetkaPay
 * Version: 1.0.6
 * Author: RozetkaPay
 * License: GPL2
 * Text Domain: buy-rozetkapay-woocommerce
 * Domain Path: /languages
 * Requires PHP: 7.3
 * Requires at least: 6.2
 * Tested up to: 6.8
 * WC requires at least: 8.0
 * WC tested up to: 9.8
 *
 * @package RozetkaPay Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'ROZETKAPAY_GATEWAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ROZETKAPAY_GATEWAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin textdomain.
add_action(
	'plugins_loaded',
	function () {
		require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-const.php';

		load_plugin_textdomain(
			'buy-rozetkapay-woocommerce',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages',
		);
	}
);

// Load the gateway only if WooCommerce is active.
add_action( 'plugins_loaded', 'gateway_init', 20 );

// Register the payment gateway.
add_filter( 'woocommerce_payment_gateways', 'rozetkapay_gateway_register_gateway' );

// Activation hook to create logs folder.
register_activation_hook( __FILE__, 'rozetkapay_gateway_activate' );

/**
 * Initializes the RozetkaPay Gateway.
 *
 * This function checks for the existence of the WooCommerce payment gateway class
 * and requires the necessary classes for the RozetkaPay integration. It initializes
 * the callback handler for processing RozetkaPay callback interactions.
 *
 * The function is hooked into the 'plugins_loaded' action and is executed only if
 * WooCommerce is active.
 *
 * @since 1.0.0
 */
function gateway_init(): void {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	// Require necessary classes.
	require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-helper.php';
	require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-logger.php';
	require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-api.php';
	require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-callback.php';
	require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-gateway.php';
	require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-one-click.php';
	require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-admin-view.php';
	require_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'includes/class-rozetkapay-order-payment-action.php';

	// Initialize callback handler.
	RozetkaPay_Callback::init();

	// Initialize one-click integration.
	RozetkaPay_One_Click::init();

	RozetkaPay_Admin_View::init();
	RozetkaPay_Order_Payment_Action::init();
}

/**
 * Registers the RozetkaPay payment gateway class with WooCommerce.
 *
 * The `woocommerce_payment_gateways` filter is used to register the gateway.
 *
 * @param array $methods The array of registered payment gateways.
 *
 * @return array The updated array of registered payment gateways.
 * @since 1.0.0
 */
function rozetkapay_gateway_register_gateway( $methods ): array {
	$class = RozetkaPay_Helper::get_class_name( RozetkaPay_Gateway::class );

	if ( class_exists( $class ) ) {
		$methods[] = $class;
	}

	return $methods;
}

/**
 * Creates the logs folder when the plugin is activated.
 * The folder is created with permissions 0755 and is created recursively if it doesn't exist.
 *
 * @global WP_Filesystem_Base $wp_filesystem
 */
function rozetkapay_gateway_activate(): void {
	global $wp_filesystem;

	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
	}

	$log_dir = ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'logs';

	if ( ! $wp_filesystem->is_dir( $log_dir ) ) {
		$wp_filesystem->mkdir( $log_dir, FS_CHMOD_DIR );
	}
}
