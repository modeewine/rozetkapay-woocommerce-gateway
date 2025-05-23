<?php

    /** @var WC_Order $order */
    /** @var string $payment_info_url */
    /** @var string $payment_receipt_url */
    /** @var string $resend_payment_callback_url */
    /** @var string $cancel_payment_url */
    /** @var bool $show_payment_info */
    /** @var bool $show_receipt */
    /** @var bool $show_resend_payment_callback */
    /** @var bool $show_cancel_payment */

    if ($show_payment_info) {
        ?>
        <a href="<?php echo esc_url($payment_info_url) ?>" class="button button-primary" style="margin-top: 8px;">
            <?php esc_html_e('Payment info', 'rozetkapay-gateway'); ?>
        </a>
        <br />
        <?php
    }

    if ($show_receipt) {
        ?>
        <a href="<?php echo esc_url($payment_receipt_url) ?>" target="_blank" class="button button-primary" style="margin-top: 8px;">
            <?php esc_html_e('Payment receipt', 'rozetkapay-gateway'); ?>
        </a>
        <br />
        <?php
    }

    if ($show_resend_payment_callback) {
        ?>
        <a href="<?php echo esc_url($resend_payment_callback_url); ?>" class="button button-primary" style="margin-top: 8px;">
            <?php esc_html_e('Resend payment callback', 'rozetkapay-gateway'); ?>
        </a>
        <br />
        <?php
    }

    if ($show_cancel_payment) {
        ?>
        <a href="<?php echo esc_url($cancel_payment_url); ?>" class="button button-primary" style="margin-top: 8px;">
            <?php esc_html_e('Cancel payment', 'rozetkapay-gateway'); ?>
        </a>
        <br />
        <?php
    }

?>
