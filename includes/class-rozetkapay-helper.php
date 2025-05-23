<?php

class RozetkaPay_Helper
{
    /**
     * Extract "clear" class name
     *
     * @param string|object $class
     */
    public static function get_class_name($class): string
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (is_string($class)) {
            $path = explode('\\', $class);

            return end($path);
        }

        throw new RuntimeException('Wrong class type');
    }

    public static function get_payment_gateway(): RozetkaPay_Gateway
    {
        return WC()->payment_gateways->payment_gateways()[RozetkaPay_Const::ID_PAYMENT_GATEWAY];
    }

    public static function is_order_edit_page(): bool
    {
        return
            isset($_GET['page'])
            && $_GET['page'] === 'wc-orders'
            && isset($_GET['action'])
            && $_GET['action'] === 'edit'
            && !empty($_GET['id']);
    }

    public static function is_rozetkapay_order(WC_Order $order): bool
    {
        return $order->get_payment_method() === RozetkaPay_Const::ID_PAYMENT_GATEWAY;
    }

    public static function generate_admin_page_url(string $id, string $query = ''): string
    {
        return admin_url(
            sprintf(
                'admin.php?page=%s-%s%s',
                RozetkaPay_Const::ID_PAYMENT_GATEWAY,
                $id,
                !empty($query) ? '&' . $query : '',
            )
        );
    }

    public static function generate_nonce_key(string $id, string $postfix = ''): string
    {
        return sprintf('%s_%s%s', RozetkaPay_Const::ID_PAYMENT_GATEWAY, $id, $postfix);
    }

    public static function get_request_header_value(string $name): ?string
    {
        return sanitize_text_field(
            wp_unslash(
                $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null
            )
        );
    }

    public static function encode_safe_base64(string $data): string
    {
        return strtr(base64_encode($data), '+/', '-_');
    }
}
