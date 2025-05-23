<?php

/**
* Admin page for displaying and managing RozetkaPay logs.
*
* @package RozetkaPay Gateway
*/

    /** @var string $logs_type */
    /** @var array $logs */

?>
<div class="wrap">
    <h1><?php esc_html_e('RozetkaPay Logs', RozetkaPay_Const::TEXT_DOMAIN); ?></h1>
    <?php

        if (
            isset($_POST['rozetkapay_clear_logs'])
            && check_admin_referer('rozetkapay_clear_log_action', 'rozetkapay_clear_log_nonce')
        ) {
            RozetkaPay_Logger::clear_logs($logs_type);

            echo '<div class="updated notice"><p>'
                . __('Logs are cleared successfully', RozetkaPay_Const::TEXT_DOMAIN)
                . '</p></div>';
        }

    ?>
    <h2><?php echo esc_html( ucfirst( str_replace('_', ' ', $logs_type ) ) ); ?></h2>
    <form method="post">
        <?php wp_nonce_field('rozetkapay_clear_log_action', 'rozetkapay_clear_log_nonce'); ?>
        <input type="hidden" name="log_type" value="<?php echo $logs_type; ?>" />
        <p>
            <button type="submit" name="rozetkapay_clear_logs" class="button button-secondary">
                <?php esc_html_e('Clear Logs', RozetkaPay_Const::TEXT_DOMAIN); ?>
            </button>
        </p>
    </form>
    <?php if (!empty( $logs)) : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Timestamp', RozetkaPay_Const::TEXT_DOMAIN); ?></th>
                    <th><?php _e('Data', RozetkaPay_Const::TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php

                    foreach ($logs as $log_entry) {
                        $json_data = json_encode($log_entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 11px;">
                                <?php echo str_replace(' ', '<br />', $log_entry['timestamp']); ?>
                            </td>
                            <td>
                                <pre
                                    style="padding: 24px; overflow: scroll; background-color: #000; font-size: 11px; color: #CCC;"
                                ><?php echo esc_html($json_data); ?></pre>
                            </td>
                        </tr>
                        <?php
                    }

                ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php _e('No log entries found', RozetkaPay_Const::TEXT_DOMAIN); ?></p>
    <?php endif; ?>
</div>
