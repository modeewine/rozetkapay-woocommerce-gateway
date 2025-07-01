<?php
/**
 * RozetkaPay One Click class.
 *
 * Buy one-click integration
 *
 * @package RozetkaPay Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * RozetkaPay One Click Class.
 */
class RozetkaPay_One_Click {
	/**
	 * Initialize "one click button" view.
	 */
	public static function init(): void {
		if ( ! RozetkaPay_Helper::get_payment_gateway()->one_click_button_enabled ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'inject_styles' ) );

		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'view_product_button' ), 20 );
		add_action( 'woocommerce_proceed_to_checkout', array( __CLASS__, 'view_cart_button' ), 20 );

		add_action( 'template_redirect', array( __CLASS__, 'handle_action' ) );
	}

	/**
	 * Inject styles resource for "one click button" view.
	 */
	public static function inject_styles(): void {
		wp_enqueue_style(
			'rozetkapay-styles',
			ROZETKAPAY_GATEWAY_PLUGIN_URL . 'assets/css/rozetka-one-click.css',
			array(),
			RozetkaPay_Const::VERSION,
		);
	}

	/**
	 * View button on the product view page.
	 */
	public static function view_product_button(): void {
		if ( ! is_product() ) {
			return;
		}

		$view_mode = RozetkaPay_Helper::get_payment_gateway()->one_click_button_view_mode;

		include_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/one-click-product-button.php';
	}

	/**
	 * View button on the cart view page.
	 */
	public static function view_cart_button(): void {
		$view_mode = RozetkaPay_Helper::get_payment_gateway()->one_click_button_view_mode;

		include_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/one-click-cart-button.php';
	}

	/**
	 * Handle "one click button" action.
	 */
	public static function handle_action(): void {
		$nonce_key = 'rozetkapay_one_click_nonce';

		if (
			! isset( $_POST[ RozetkaPay_Const::ID_BUY_ONE_CLICK ] )
			|| ! isset( $_POST[ $nonce_key ] )
		) {
			return;
		}

		$nonce_value = sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) );

		if ( ! wp_verify_nonce( $nonce_value, 'rozetkapay_one_click_action' ) ) {
			return;
		}

		$one_click_value = trim( sanitize_text_field( wp_unslash( $_POST[ RozetkaPay_Const::ID_BUY_ONE_CLICK ] ) ) );

		if ( '' === $one_click_value ) {
			return;
		}

		$one_click_value = (int) $one_click_value;

		$order = wc_create_order();

		if ( $one_click_value > 0 ) {
			$quantity = sanitize_text_field( wp_unslash( $_POST['quantity'] ?? 1 ) );

			$order->add_product( wc_get_product( $one_click_value ), $quantity );
			$order->calculate_totals();
		} else {
			$cart_items = WC()->cart->get_cart();

			foreach ( $cart_items as $cart_item ) {
				$order->add_product( $cart_item['data'], $cart_item['quantity'] ?? 1 );
			}

			$order->set_total( WC()->cart->get_cart_contents_total() );
		}

		$order->set_payment_method( RozetkaPay_Const::ID_PAYMENT_GATEWAY );
		$order->set_customer_id( get_current_user_id() );
		$order->save();

		$result = RozetkaPay_Helper::get_payment_gateway()->process_payment( $order->get_id() );

		if ( 'success' === $result['result'] ) {
            // phpcs:ignore
			wp_redirect( $result['redirect'] );
			exit;
		}
	}
}
