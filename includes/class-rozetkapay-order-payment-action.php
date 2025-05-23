<?php

class RozetkaPay_Order_Payment_Action
{
    public static function init(): void
    {
        add_action('admin_menu', function(){
            add_submenu_page(
                null,
                __('RozetkaPay payment receipt', 'rozetkapay-gateway'),
                null,
                'manage_woocommerce',
                'rozetkapay-payment-receipt',
                [__CLASS__, 'view_payment_receipt'],
            );
        });

        add_action('admin_menu', function(){
            add_submenu_page(
                null,
                __('RozetkaPay resend payment callback', 'rozetkapay-gateway'),
                null,
                'manage_woocommerce',
                'rozetkapay-resend-payment-callback',
                [__CLASS__, 'resend_payment_callback'],
            );
        });

        add_action('admin_menu', function(){
            add_submenu_page(
                null,
                __('RozetkaPay cancel payment', 'rozetkapay-gateway'),
                null,
                'manage_woocommerce',
                'rozetkapay-cancel-payment',
                [__CLASS__, 'cancel_payment'],
            );
        });
    }

    public static function view_payment_receipt(): void
    {
        $nonce_key = RozetkaPay_Helper::generate_nonce_key('payment-receipt', '_nonce');

        if (
            !isset($_GET[$nonce_key])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_GET[$nonce_key])),
                RozetkaPay_Helper::generate_nonce_key('payment-receipt', '_action')
            )
        ) {
            wp_die('Wrong action nonce');
        }

        $order_id = (int) $_GET['order_id'] ?? 0;
        $gateway = RozetkaPay_Helper::get_payment_gateway();

        $response = RozetkaPay_API::get_payment_receipt(
            $order_id,
            $gateway->login,
            $gateway->password,
        );

        $error_message = is_wp_error($response) ? $response->get_error_message() : null;

        if (!empty($error_message)) {
            self::show_error_message($error_message);
        } else {
            if (!empty($response['receipt_url'])) {
                wp_redirect($response['receipt_url']);
                exit;
            } elseif (!empty($response['message'])) {
                echo '<div class="error"><p>' . esc_html($response['message']) . '</p></div>';
            }
        }

        self::show_back_button($order_id);
    }

    public static function resend_payment_callback(): void
    {
        $nonce_key = RozetkaPay_Helper::generate_nonce_key('resend-payment-callback', '_nonce');

        if (
            !isset($_GET[$nonce_key])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_GET[$nonce_key])),
                RozetkaPay_Helper::generate_nonce_key('resend-payment-callback', '_action')
            )
        ) {
            wp_die('Wrong action nonce');
        }

        $order_id = (int) $_GET['order_id'] ?? 0;

        if (!isset($_GET['sent'])) {
            $gateway = RozetkaPay_Helper::get_payment_gateway();

            $response = RozetkaPay_API::resend_payment_callback(
                $order_id,
                $gateway->login,
                $gateway->password,
            );

            $error_message = is_wp_error($response) ? $response->get_error_message() : null;

            if (!empty($error_message)) {
                self::show_error_message($error_message);
            } else {
                $id = 'resend-payment-callback';

                wp_redirect(RozetkaPay_Helper::generate_admin_page_url(
                    $id,
                    'order_id=' . $order_id . '&sent=' . ($response === true ? 'yes' : 'no')
                        . '&' . RozetkaPay_Helper::generate_nonce_key($id, '_nonce') . '='
                            . wp_create_nonce(RozetkaPay_Helper::generate_nonce_key($id, '_action'))
                ));

                exit;
            }
        } else {
            if ($_GET['sent'] === 'yes') {
                echo '<div class="updated"><p>'
                    . esc_html__('Payment callback was successfully resent', 'rozetkapay-gateway')
                    . '</p></div>';
            } else {
                echo '<div class="error"><p>'
                    . esc_html__('Something went wrong', 'rozetkapay-gateway')
                    . '</p></div>';
            }
        }

        self::show_back_button($order_id);
    }

    public static function cancel_payment(): void
    {
    }

    private static function show_error_message(string $message): void
    {
        echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
    }

    private static function show_back_button(int $order_id): void
    {
        $back_url = admin_url('admin.php?page=wc-orders&action=edit&id=' . $order_id);

        ?>
        <p>
            <a href="<?php echo esc_url($back_url) ?>" class="page-title-action">
                <?php echo esc_html__('Back to order view', 'rozetkapay-gateway'); ?>
            </a>
        </p>
        <?php
    }
}
