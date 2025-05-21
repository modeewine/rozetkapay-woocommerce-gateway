<?php

/**
 * RozetkaPay One Click class.
 *
 * Buy one-click integration
 *
 * @package RozetkaPay Gateway
 */

if ( ! defined('ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class RozetkaPay_One_Click
{
    public static function init(): void
    {
        if (!RozetkaPay_Helper::get_payment_gateway()->one_click_button_enabled) {
            return;
        }

        add_action('wp_enqueue_scripts', [__CLASS__, 'inject_styles']);

        add_action('woocommerce_after_add_to_cart_button', [__CLASS__, 'view_product_button'], 20);
        add_action('woocommerce_proceed_to_checkout', [__CLASS__, 'view_cart_button'], 20);

        add_action('template_redirect', [__CLASS__, 'handle_action']);
    }

    public static function inject_styles(): void
    {
        wp_enqueue_style(
            'rozetkapay-styles',
            ROZETKAPAY_GATEWAY_PLUGIN_URL . 'assets/css/rozetka-one-click.css',
            [],
            RozetkaPay_Const::VERSION,
        );
    }

    public static function view_product_button(): void
    {
        if (!is_product()) {
            return;
        }

        $view_mode = RozetkaPay_Helper::get_payment_gateway()->one_click_button_view_mode;

        include_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/one-click-product-button.php';
    }

    public static function view_cart_button(): void
    {
        $view_mode = RozetkaPay_Helper::get_payment_gateway()->one_click_button_view_mode;

        include_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/one-click-cart-button.php';
    }

    public static function handle_action(): void
    {
        if (!isset($_POST[RozetkaPay_Const::ID_BUY_ONE_CLICK])) {
            return;
        }

        $product_id = (int) $_POST[RozetkaPay_Const::ID_BUY_ONE_CLICK];

        $order = wc_create_order();

        if ($product_id > 0) {
            $order->add_product(wc_get_product($product_id));
        } else {
            $cart_items = WC()->cart->get_cart();

            foreach ($cart_items as $cart_item) {
                $order->add_product($cart_item['data']);
            }
        }

        $order->calculate_totals();
        $order->set_payment_method(RozetkaPay_Const::ID_PAYMENT_GATEWAY);
        $order->set_customer_id(get_current_user_id());
        $order->save();

        $result = RozetkaPay_Helper::get_payment_gateway()->process_payment($order->get_id());

        if ($result['result'] === 'success') {
            wp_redirect($result['redirect']);
            exit;
        }
    }
}
