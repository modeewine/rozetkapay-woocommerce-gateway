<?php

/**
 * RozetkaPay API client class.
 *
 * @package RozetkaPay Gateway
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * RozetkaPay API interaction class.
 */
class RozetkaPay_API
{
    /**
     * Create a payment using RozetkaPay API.
     *
     * @param array  $payment_data Payment data.
     * @param string $login API login.
     * @param string $password API password.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public static function create_payment(
        array $payment_data,
        string $login,
        string $password
    )
    {
        $response = self::sendPostRequest(
            '/payments/v1/new',
            $payment_data,
            $login,
            $password,
        );

        if (!is_array($response)) {
            return new WP_Error(
                'invalid_response',
                __('Invalid API response from RozetkaPay', 'buy-rozetkapay-woocommerce'),
            );
        }

        if (
            empty($response['action']['type'])
            || $response['action']['type'] !== 'url'
            || empty($response['action']['value'])
        ) {
            return new WP_Error(
                'missing_action_url',
                __('Missing payment URL from RozetkaPay response', 'buy-rozetkapay-woocommerce'),
            );
        }

        return $response;
    }

    /**
     * Get payment information from RozetkaPay API.
     *
     * @param int $order_id External order ID.
     * @param string $login API login.
     * @param string $password API password.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public static function get_payment_info(
        int $order_id,
        string $login,
        string $password
    )
    {

        return self::sendGetRequest(
            '/payments/v1/info',
            ['external_id' => $order_id],
            $login,
            $password,
        );
    }

    /**
     * Get payment receipt from RozetkaPay API.
     *
     * @param int $order_id External order ID.
     * @param string $login API login.
     * @param string $password API password.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public static function get_payment_receipt(
        int $order_id,
        string $login,
        string $password
    )
    {
        return self::sendGetRequest(
            '/payments/v1/receipt',
            ['external_id' => $order_id],
            $login,
            $password,
        );
    }

    /**
     * Resend payment callback from RozetkaPay API.
     *
     * @param int $order_id External order ID.
     * @param string $login API login.
     * @param string $password API password.
     *
     * @return WP_Error|bool true or WP_Error on failure.
     */
    public static function resend_payment_callback(
        int $order_id,
        string $login,
        string $password
    )
    {
        self::sendPostRequest(
            '/payments/v1/callback/resend',
            [
                'external_id' => (string) $order_id,
                'operation' => 'payment',
            ],
            $login,
            $password,
        );

        return true;
    }

    /**
     * Refund a payment using RozetkaPay API.
     *
     * @param array  $data_params Request data.
     * @param string $login API login.
     * @param string $password API password.
     *
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public static function refund_payment(
        array $data_params,
        string $login,
        string $password
    )
    {
        return self::sendPostRequest(
            '/payments/v1/refund',
            $data_params,
            $login,
            $password,
        );
    }

    /**
     * Get a full API endpoint URL.
     *
     * @param string $path    API endpoint path.
     *
     * @return string Full API URL.
     */
    private static function get_api_url(string $path): string
    {
        return trailingslashit(RozetkaPay_Const::API_BASE_URL) . ltrim($path, '/');
    }

    /**
     * Get headers for API request.
     *
     * @param string $login    API login.
     * @param string $password API password.
     *
     * @return array
     */
    private static function get_headers(string $login, string $password): array
    {
        $auth = base64_encode(sprintf('%s:%s', $login, $password));

        return [
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Inner method for sending GET request
     *
     * @param string $api_path
     * @param array $query_params
     * @param string $login
     * @param string $password
     *
     * @return WP_Error|array
     */
    private static function sendGetRequest(
        string $api_path,
        array $query_params,
        string $login,
        string $password
    )
    {
        $url = self::get_api_url($api_path);

        $response = wp_remote_get(
            add_query_arg($query_params, $url),
            [
                'headers' => self::get_headers($login, $password),
                'timeout' => RozetkaPay_Const::API_REQUEST_TIMEOUT,
            ],
        );

        $error = is_wp_error($response);

        if ($error) {
            $response_log = $response->get_error_message();
        } else {
            $response_body = wp_remote_retrieve_body($response);
            $response_log = $response_body;
        }

        RozetkaPay_Logger::log(
            'requests',
            [
                'endpoint' => $url,
                'query' => $query_params,
                'response_raw' => $response_log,
                'response_parsed' => json_decode($response_log, true),
            ],
        );

        if ($error) {
            return $response;
        }

        $body = json_decode($response_body, true);

        if (!is_array($body)) {
            return new WP_Error(
                'invalid_response',
                __('Invalid API response from RozetkaPay', 'buy-rozetkapay-woocommerce'),
            );
        }

        return $body;
    }

    /**
     * @param string $api_path
     * @param array $request_data
     * @param string $login
     * @param string $password
     *
     * @return WP_Error|array
     */
    private static function sendPostRequest(
        string $api_path,
        array $request_data,
        string $login,
        string $password
    )
    {
        $api_url = self::get_api_url($api_path);

        $response = wp_remote_post($api_url, [
            'method' => 'POST',
            'headers' => self::get_headers($login, $password),
            'body' => wp_json_encode($request_data),
            'timeout' => RozetkaPay_Const::API_REQUEST_TIMEOUT,
            'data_format' => 'body',
        ]);

        $error = is_wp_error($response);

        if ($error) {
            $response_log = $response->get_error_message();
        } else {
            $response_body = wp_remote_retrieve_body($response);
            $response_log = $response_body;
        }

        RozetkaPay_Logger::log('requests', [
            'endpoint' => $api_url,
            'request' => $request_data,
            'response_raw' => $response_log,
            'response_parsed' => json_decode($response_log, true),
        ]);

        if ($error) {
            return $response;
        }

        $body = json_decode($response_body, true);

        if (!is_array($body)) {
            return new WP_Error(
                'invalid_response',
                __('Invalid API response from RozetkaPay', 'buy-rozetkapay-woocommerce'),
            );
        }

        return $body;
    }
}
