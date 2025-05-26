<?php

class RozetkaPay_Admin_View
{
    public static function init(): void
    {
        if (self::is_available()) {
            add_action('woocommerce_admin_order_data_after_billing_address', [__CLASS__, 'view_billing_info'], 10, 1);
            add_action('woocommerce_admin_order_data_after_shipping_address', [__CLASS__, 'view_shipping_info'], 10, 1);

            add_action('add_meta_boxes', function(){
                add_meta_box(
                    'rozetkapay-order-metabox',
                    __('RozetkaPay order', 'buy-rozetkapay-woocommerce'),
                    [__CLASS__, 'view_order_metabox'],
                    wc_get_page_screen_id('shop-order'),
                    'side',
                    'high'
                );
            });
        }

        add_action('admin_menu', function(){
            add_submenu_page(
                null,
                __('RozetkaPay payment information', 'buy-rozetkapay-woocommerce'),
                null,
                'edit_shop_orders',
                'rozetkapay-payment-info',
                [__CLASS__, 'view_payment_info_page']
            );
        });

        self::init_payment_logs_pages();
    }

    public static function view_billing_info(WC_Order $order): void
    {
        $patronym = get_post_meta($order->get_id(), RozetkaPay_Const::BILLING_PATRONYM_OPTION_KEY, true);

        if (!empty($patronym)) {
            echo '<div class="address"><p><strong>'
                . esc_html__('Patronym', 'buy-rozetkapay-woocommerce')
                . ':</strong> '
                . esc_html($patronym)
                . '</p></div>';
        }

        $transaction_id = $order->get_transaction_id();

        if (!empty($transaction_id)) {
            echo '<div class="address"><p><strong>'
                . esc_html__('Transaction ID', 'buy-rozetkapay-woocommerce')
                . ':</strong> '
                . esc_html($transaction_id)
                . '</p></div>';
        }

        $payment_operation_type =
            get_post_meta($order->get_id(), RozetkaPay_Const::PAYMENT_OPERATION_TYPE_OPTION_KEY, true);

        if ($payment_operation_type === 'post_payment') {
            echo '<div class="address"><p><strong>'
                . esc_html__('Payment upon receipt', 'buy-rozetkapay-woocommerce')
                . '</strong></p></div>';
        }
    }

    public static function view_shipping_info(WC_Order $order): void
    {
        $patronym = get_post_meta($order->get_id(), RozetkaPay_Const::RECIPIENT_PATRONYM_OPTION_KEY, true);

        if (!empty($patronym)) {
            echo '<div class="address"><p><strong>'
                . esc_html__('Patronym', 'buy-rozetkapay-woocommerce')
                . ':</strong> '
                . esc_html($patronym)
                . '</p></div>';
        }

        $delivery_type = get_post_meta($order->get_id(), RozetkaPay_Const::SHIPPING_DELIVERY_TYPE_OPTION_KEY, true);

        if (!empty($delivery_type)) {
            echo '<div class="address"><p><strong>'
                . esc_html__('Delivery type', 'buy-rozetkapay-woocommerce')
                . ':</strong> '
                . esc_html(self::map_delivery_type($delivery_type))
                . '</p></div>';
        }

        $provider = get_post_meta($order->get_id(), RozetkaPay_Const::SHIPPING_PROVIDER_OPTION_KEY, true);

        if (!empty($provider)) {
            echo '<div class="address"><p><strong>'
                . esc_html__('Provider', 'buy-rozetkapay-woocommerce')
                . ':</strong> '
                . esc_html($provider)
                . '</p></div>';
        }

        $warehouse_number = get_post_meta(
            $order->get_id(),
            RozetkaPay_Const::SHIPPING_WAREHOUSE_NUMBER_OPTION_KEY,
            true,
        );

        if (!empty($warehouse_number)) {
            echo '<div class="address"><p><strong>'
                . esc_html__('Warehouse number', 'buy-rozetkapay-woocommerce')
                . ':</strong> '
                . esc_html($warehouse_number)
                . '</p></div>';
        }
    }

    public static function view_order_metabox(WC_Order $order): void
    {
        $payment_info_url = self::generate_metabox_action_url('payment-info', $order->get_id());
        $payment_receipt_url = self::generate_metabox_action_url('payment-receipt', $order->get_id());
        $resend_payment_callback_url =
            self::generate_metabox_action_url('resend-payment-callback', $order->get_id());
        $cancel_payment_url = self::generate_metabox_action_url('cancel-payment', $order->get_id());

        $show_payment_info =
        $show_receipt =
        $show_resend_payment_callback = !empty($order->get_transaction_id());

        $show_cancel_payment = false; // todo: temporary disabled // !$order->is_paid();

        include_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/order-metabox.php';
    }

    public static function view_payment_info_page(): void
    {
        $nonce_key = RozetkaPay_Helper::generate_nonce_key('payment-info', '_nonce');

        if (
            !isset($_GET[$nonce_key])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_GET[$nonce_key])),
                RozetkaPay_Helper::generate_nonce_key('payment-info', '_action')
            )
        ) {
            wp_die('Wrong action nonce');
        }

        $order_id = (int) $_GET['order_id'] ?? 0;
        $gateway = RozetkaPay_Helper::get_payment_gateway();

        $response = RozetkaPay_API::get_payment_info(
            $order_id,
            $gateway->login,
            $gateway->password,
        );

        $error_message = is_wp_error($response) ? $response->get_error_message() : null;
        $json_data = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        include_once ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/payment-info-page.php';
    }

    public static function view_payment_request_logs_page(): void
    {
        $logs_type = 'requests';
        $logs = RozetkaPay_Logger::get_logs($logs_type);

        include ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/payment-logs-page.php';
    }

    public static function view_payment_callback_logs_page(): void
    {
        $logs_type = 'callbacks';
        $logs = RozetkaPay_Logger::get_logs($logs_type);

        include ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/payment-logs-page.php';
    }

    public static function view_payment_error_logs_page(): void
    {
        $logs_type = 'errors';
        $logs = RozetkaPay_Logger::get_logs($logs_type);

        include ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'templates/payment-logs-page.php';
    }

    private static function generate_metabox_action_url(string $id, int $order_id): string
    {
        return RozetkaPay_Helper::generate_admin_page_url(
            $id,
            'order_id=' . $order_id
            . '&' . RozetkaPay_Helper::generate_nonce_key($id, '_nonce') . '='
                . wp_create_nonce(RozetkaPay_Helper::generate_nonce_key($id, '_action'))
        );
    }

    private static function init_payment_logs_pages(): void
    {
        add_action('admin_menu', function(){
            $main_slug = 'rozetkapay-payment-logs';

            add_menu_page(
                __('RozetkaPay Logs', 'buy-rozetkapay-woocommerce'),
                __('RozetkaPay Logs', 'buy-rozetkapay-woocommerce'),
                'manage_woocommerce',
                $main_slug,
                null,
                'dashicons-clipboard',
                56,
            );

            remove_submenu_page($main_slug, $main_slug);

            add_submenu_page(
                $main_slug,
                __('Requests', 'buy-rozetkapay-woocommerce'),
                __('Requests', 'buy-rozetkapay-woocommerce'),
                'manage_woocommerce',
                $main_slug,
                [__CLASS__, 'view_payment_request_logs_page'],
            );

            add_submenu_page(
                $main_slug,
                __('Callbacks', 'buy-rozetkapay-woocommerce'),
                __('Callbacks', 'buy-rozetkapay-woocommerce'),
                'manage_woocommerce',
                'rozetkapay-payment-callback-logs',
                [__CLASS__, 'view_payment_callback_logs_page'],
            );

            add_submenu_page(
                $main_slug,
                __('Errors', 'buy-rozetkapay-woocommerce'),
                __('Errors', 'buy-rozetkapay-woocommerce'),
                'manage_woocommerce',
                'rozetkapay-payment-error-logs',
                [__CLASS__, 'view_payment_error_logs_page'],
            );
        });
    }

    private static function map_delivery_type(string $delivery_type): string
    {
        switch (strtoupper($delivery_type)) {
            case 'W':
                return __('Department', 'buy-rozetkapay-woocommerce');
            case 'P':
                return __('Paketautomat', 'buy-rozetkapay-woocommerce');
            case 'D':
                return __('Courier', 'buy-rozetkapay-woocommerce');
            default:
                return '-';
        }
    }

    private static function is_available(): bool
    {
        if (!RozetkaPay_Helper::is_order_edit_page()) {
            return false;
        }

        return
            ($post = get_post(sanitize_text_field(wp_unslash($_GET['id'] ?? 0))))
            && preg_match('/^shop_order($|_)/i', $post->post_type)
            && RozetkaPay_Helper::is_rozetkapay_order(new WC_Order($post->ID));
    }
}
