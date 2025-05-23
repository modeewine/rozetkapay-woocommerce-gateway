<?php

/**
 * RozetkaPay Callback handler class.
 *
 * @package RozetkaPay Gateway
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * RozetkaPay Callback interaction class.
 */
class RozetkaPay_Callback
{
    /**
     * Initialize callback endpoint.
     */
    public static function init(): void {
        add_action(
            sprintf(
                'woocommerce_api_%s',
                strtolower(RozetkaPay_Helper::get_class_name(RozetkaPay_Callback::class)),
            ),
            [__CLASS__, 'handle_callback'],
        );
    }

    /**
     * Handle the incoming callback from RozetkaPay.
     */
    public static function handle_callback(): void
    {
        $data = self::extractRequestJsonData();
        $parsedData = json_decode($data, true);
        $headers = getallheaders();
        $api_password = RozetkaPay_Helper::get_payment_gateway()->get_option('password');
        $original_signature =
            RozetkaPay_Helper::get_request_header_value(RozetkaPay_Const::HEADER_SIGNATURE) ?? '';
        $calculated_signature = self::calculate_signature($data, $api_password);

        // Log the callback data.
        RozetkaPay_Logger::log(
            'callbacks',
            [
                'raw' => $data,
                'parsed' => $parsedData,
            ],
            [
                'headers' => $headers,
                'original_signature' => $original_signature,
                'calculated_signature' => $calculated_signature,
            ],
        );

        if (empty($data)) {
            self::sendBadRequestResponse();
        }

        self::validateCommonDataStructure($parsedData);

        $dataStructureType = self::detectDataStructureType($parsedData);

        // todo: temporary fix because there are some problems with calculation signature on the API side
        if ($dataStructureType === 'refund' && !empty($original_signature)) {
            $calculated_signature = $original_signature = 'skipped';
        }

        self::verifySignature(
            $calculated_signature,
            $original_signature,
            $data,
            $parsedData,
        );

        $order = wc_get_order((int) $parsedData['external_id']);

        if (!$order) {
            status_header(404);
            exit('Order not found');
        }

        switch ($dataStructureType) {
            case 'payment':
                self::handle_payment_callback($order, $parsedData);
                break;

            case 'refund':
                self::handle_refund_payment_callback($order, $parsedData);
                break;

            case 'instalment_payment':
                self::handle_instalment_payment_callback($order, $parsedData);
                break;

            case 'post_payment':
                self::handle_post_payment_callback($order, $parsedData);
                break;

            default:
                self::sendBadRequestResponse();
        }

        self::set_order_payment_operation_type($order, $dataStructureType);

        status_header(200);
        exit('Callback processed');
    }

    /**
     * Extract and decode form data from the callback request
     */
    private static function extractRequestJsonData(): ?string
    {
        $rawBody = file_get_contents('php://input');
        $rawBody = (string) preg_replace('/^data=/i', '', $rawBody);

        if (preg_match('/%[0-9A-Fa-f]{2}/', $rawBody)) {
            return urldecode($rawBody);
        }

        return $rawBody;
    }

    /**
     * @param array $data
     */
    private static function validateCommonDataStructure(array $data): void
    {
        if (
            empty($data['external_id'])
            || !array_key_exists('customer', $data)
        ) {
            self::sendBadRequestResponse();
        }
    }

    /**
     * @param array $data
     */
    private static function detectDataStructureType(array $data): ?string
    {
        if (
            !empty($data['operation'])
            && !empty($data['details']['status'])
            && !empty($data['details']['transaction_id'])
        ) {
            if ($data['operation'] === 'payment') {
                return 'payment';
            }

            if (
                $data['operation'] === 'refund'
                && !empty($data['details']['amount'])
            ) {
                return 'refund';
            }
        }

        if (
            !empty($data['details']['status'])
            && !empty($data['details']['transaction_id'])
        ) {
            return 'instalment_payment';
        }

        if (
            array_key_exists('purchased', $data)
            && !empty($data['purchase_details'])
            && is_array($data['purchase_details'])
        ) {
            foreach ($data['purchase_details'] as $item) {
                if (
                    !empty($item['status_code'])
                    && $item['status_code'] === 'order_with_postpayment_confirmed'
                ) {
                    return 'post_payment';
                }
            }
        }

        return null;
    }

    private static function calculate_signature(string $data, string $password, bool $safe_mode = false): string
    {
        $encoded_data = $safe_mode ? RozetkaPay_Helper::encode_safe_base64($data) : base64_encode($data);
        $sha1_data = sha1($password . $encoded_data . $password, true);

        return $safe_mode ? RozetkaPay_Helper::encode_safe_base64($sha1_data) : base64_encode($sha1_data);
    }

    /**
     * Verify calculated signature with original signature and log during error.
     *
     * @param string $calculated_signature
     * @param string $original_signature
     * @param string|null $data
     * @param array|null $parsed_data
     */
    private static function verifySignature(
        string $calculated_signature,
        string $original_signature,
        ?string $data,
        ?array $parsed_data
    ): void
    {
        if ($calculated_signature !== $original_signature) {
            $message = 'Wrong signature';

            RozetkaPay_Logger::log('errors', [
                'message' => $message,
                'original_signature' => $original_signature,
                'calculated_signature' => $calculated_signature,
            ], [
                'data_raw' => $data,
                'data_parsed' => $parsed_data,
            ]);

            status_header(406);
            exit($message);
        }
    }

    private static function sendBadRequestResponse(): void
    {
        status_header(400);
        exit('Invalid callback structure');
    }

    private static function handle_payment_callback(WC_Order $order, array $data): void
    {
        self::handle_order_status(
            $order,
            strtolower(trim($data['operation'])),
            strtolower(trim($data['details']['status'])),
            $data['details']['transaction_id'] ?? null,
        );

        self::handle_order_billing_data($order, $data['customer']);
        self::handle_order_shipping_data($order, $data['delivery_details'] ?? null);

        self::handle_order_recipient_data(
            $order,
            $data['order_recipient'] ?? $data['customer'] ?? null,
        );
    }

    private static function handle_refund_payment_callback(WC_Order $order, array $data): void
    {
        $operation_status = strtolower(trim($data['details']['status']));
        $amount = !empty($data['details']['amount']) ? (float) $data['details']['amount'] : 0;
        $reason = $data['details']['payload'] ?? null;

        if ($operation_status !== 'success') {
            return;
        }

        wc_create_refund([
            'amount' => round($amount, 2),
            'reason' => (string) $reason,
            'order_id' => $order->get_id(),
            'refund_payment' => false,
        ]);

        if (
            (float) $order->get_remaining_refund_amount() === 0.0
            && $order->get_status() !== 'refunded'
        ) {
            $order->set_status('refunded');
            $order->save();
        }
    }

    private static function handle_instalment_payment_callback(WC_Order $order, array $data): void
    {
        self::handle_order_status(
            $order,
            'payment',
            strtolower(trim($data['details']['status'])),
            $data['details']['transaction_id'] ?? null,
        );

        self::handle_order_billing_data($order, $data['customer']);
        self::handle_order_shipping_data($order, $data['delivery_details'] ?? null);

        self::handle_order_recipient_data(
            $order,
            $data['order_recipient'] ?? $data['customer'] ?? null,
        );
    }

    private static function handle_post_payment_callback(WC_Order $order, array $data): void
    {
        $process = false;

        if ($data['purchased'] === true) {
            foreach ($data['purchase_details'] as $item) {
                if (
                    !empty($item['status_code'])
                    && $item['status_code'] === 'order_with_postpayment_confirmed'
                    && !empty($item['status'])
                    && $item['status'] === 'success'
                ) {
                    $process = true;
                    break;
                }
            }
        }

        if (!$process) {
            return;
        }

        self::handle_order_status(
            $order,
            'payment',
            'success',
            null,
        );

        self::handle_order_billing_data($order, $data['customer']);

        $dd = $data['delivery_details'] ?? null;

        if (!empty($dd)) {
            self::handle_order_shipping_data($order, [
                'city' => $dd['city']['cityName'] ?? null,
                'street' => $dd['street']['name'] ?? null,
                'house' => $dd['house'] ?? null,
                'apartment' => $dd['apartment'] ?? null,
                'delivery_type' => $dd['delivery_type'] ?? null,
                'provider' => $dd['provider'] ?? null,
                'warehouse_number' => $dd['warehouse_number']['name'] ?? null,
            ]);
        }

        self::handle_order_recipient_data(
            $order,
            $data['order_recipient'] ?? $data['customer'] ?? null,
        );
    }

    private static function handle_order_status(
        WC_Order $order,
        string $operation,
        string $operation_status,
        ?string $transaction_id
    ): void
    {
        switch ($operation) {
            case 'payment':
                if (
                    $operation_status === 'success'
                    && !$order->is_paid()
                ) {
                    $order->payment_complete($transaction_id ?? '');
                    $order->add_order_note(__('Payment completed via RozetkaPay', RozetkaPay_Const::TEXT_DOMAIN));
                } elseif ($operation_status === 'failure' && $order->get_status() !== 'failed') {
                    $order->update_status(
                        'failed',
                        __('Payment failed via RozetkaPay', RozetkaPay_Const::TEXT_DOMAIN),
                    );
                }
                break;

            // todo: at the current moment this not working on the API side
            case 'cancel':
                if ($operation_status === 'success' && $order->get_status() !== 'cancelled') {
                    $order->update_status(
                        'cancelled',
                        __('Payment cancelled via RozetkaPay', RozetkaPay_Const::TEXT_DOMAIN),
                    );
                }
                break;
        }

        if (!empty($transaction_id) && $order->get_transaction_id() !== $transaction_id) {
            $order->set_transaction_id($transaction_id);
            $order->save();
        }
    }

    private static function handle_order_billing_data(WC_Order $order, ?array $customerData): void
    {
        if (empty($customerData)) {
            return;
        }

        if (!empty($value = $customerData['email'] ?? null)) {
            $order->set_billing_email($value);
        }

        if (!empty($value = $customerData['phone'] ?? null)) {
            $order->set_billing_phone($value);
        }

        if (!empty($value = $customerData['first_name'] ?? null)) {
            $order->set_billing_first_name($value);
        }

        if (!empty($value = $customerData['last_name'] ?? null)) {
            $order->set_billing_last_name($value);
        }

        if (!empty($value = $customerData['patronym'] ?? null)) {
            update_post_meta($order->get_id(), RozetkaPay_Const::BILLING_PATRONYM_OPTION_KEY, $value);
        }

        $order->save();
    }

    private static function handle_order_shipping_data(WC_Order $order, ?array $shippingData): void
    {
        if (empty($shippingData)) {
            return;
        }

        if (!empty($value = $shippingData['city'] ?? null)) {
            $order->set_shipping_city($value);
        }

        $address_1 = '';

        if (!empty($value = $shippingData['street'] ?? null)) {
            $address_1 .= !empty($address_1) ? ' ' : '';
            $address_1 .= $value;
        }

        if (!empty($value = $shippingData['house'] ?? null)) {
            $address_1 .= !empty($address_1) ? ' ' : '';
            $address_1 .= $value;
        }

        if (empty($address_1)) {
            $address_1 = '-';
        }

        $order->set_shipping_address_1($address_1);

        if (!empty($value = $shippingData['apartment'] ?? null)) {
            $order->set_shipping_address_2(sprintf(__('apartment %s'), $value));
        }

        if (!empty($value = $shippingData['delivery_type'] ?? null)) {
            update_post_meta($order->get_id(), RozetkaPay_Const::SHIPPING_DELIVERY_TYPE_OPTION_KEY, $value);
        }

        if (!empty($value = $shippingData['provider'] ?? null)) {
            update_post_meta($order->get_id(), RozetkaPay_Const::SHIPPING_PROVIDER_OPTION_KEY, $value);
        }

        if (!empty($value = $shippingData['warehouse_number'] ?? null)) {
            update_post_meta($order->get_id(), RozetkaPay_Const::SHIPPING_WAREHOUSE_NUMBER_OPTION_KEY, $value);
        }

        $order->save();
    }

    private static function handle_order_recipient_data(WC_Order $order, ?array $recipientData): void
    {
        if (!empty($value = $recipientData['phone'] ?? null)) {
            $order->set_shipping_phone($value);
        }

        if (!empty($value = $recipientData['first_name'] ?? null)) {
            $order->set_shipping_first_name($value);
        }

        if (!empty($value = $recipientData['last_name'] ?? null)) {
            $order->set_shipping_last_name($value);
        }

        if (!empty($value = $recipientData['patronym'] ?? null)) {
            update_post_meta($order->get_id(), RozetkaPay_Const::RECIPIENT_PATRONYM_OPTION_KEY, $value);
        }

        $order->save();
    }

    private static function set_order_payment_operation_type(WC_Order $order, string $operation_type): void
    {
        update_post_meta(
            $order->get_id(),
            RozetkaPay_Const::PAYMENT_OPERATION_TYPE_OPTION_KEY,
            $operation_type,
        );
    }
}
