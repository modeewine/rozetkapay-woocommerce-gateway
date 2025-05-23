<?php

/**
 * RozetkaPay Logger class.
 *
 * Handles logging of API requests, callbacks, and errors.
 *
 * @package RozetkaPay Gateway
 */

if ( ! defined('ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * RozetkaPay Logger Class
 */
class RozetkaPay_Logger
{
    /**
     * Get the log directory path.
     *
     * @return string
     */
    private static function get_log_dir(): string {
        return ROZETKAPAY_GATEWAY_PLUGIN_DIR . 'logs/';
    }

    /**
     * Get the full log file path based on log type.
     *
     * @param string $type Log type (e.g., api_requests, callbacks, errors).
     *
     * @return string
     */
    private static function get_log_file_path( string $type ): string {
        return self::get_log_dir() . sanitize_file_name(strtolower($type)) . '.json';
    }

    /**
     * Add a new entry to the log.
     *
     * @param string $type Log type.
     * @param array  $data Data to log.
     */
    public static function log(string $type, array $data, array $additionalData = []): void
    {
        $file = self::get_log_file_path($type);

        if (!file_exists( $file)) {
            file_put_contents($file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $existing_data = json_decode(file_get_contents($file), true);

        if (!is_array( $existing_data)) {
            $existing_data = [];
        }

        $entry = [
            'timestamp' => current_time('mysql'),
            'data' => $data,
        ];

        $entry = array_merge($entry, $additionalData);

        self::add_log_entry($existing_data, $entry, RozetkaPay_Const::MAX_LOG_ITEMS);

        file_put_contents($file, json_encode($existing_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Retrieve log entries.
     *
     * @param string $type Log type.
     *
     * @return array
     */
    public static function get_logs( string $type ): array
    {
        $file = self::get_log_file_path($type);

        if (file_exists($file)) {
            $content = json_decode(file_get_contents( $file ), true);

            return is_array($content) ? array_reverse($content) : [];
        }

        return [];
    }

    /**
     * Clear log entries.
     *
     * @param string $type Log type.
     */
    public static function clear_logs( string $type ): void
    {
        $file = self::get_log_file_path( $type );

        if (file_exists($file)) {
            file_put_contents($file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Insert log item to the log array with max items limit
     *
     * @param array $logs
     * @param array $log
     * @param int $limit
     */
    private static function add_log_entry(array &$logs, array $log, int $limit): void
    {
        if (count($logs) >= $limit) {
            array_shift($logs);
        }

        $logs[] = $log;
    }
}
