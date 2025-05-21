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
            <?php _e('Payment info', RozetkaPay_Const::TEXT_DOMAIN); ?>
        </a>
        <br />
        <?php
    }

    if ($show_receipt) {
        ?>
        <a href="<?php echo esc_url($payment_receipt_url) ?>" target="_blank" class="button button-primary" style="margin-top: 8px;">
            <?php _e('Payment receipt', RozetkaPay_Const::TEXT_DOMAIN); ?>
        </a>
        <br />
        <?php
    }

    if ($show_resend_payment_callback) {
        ?>
        <a href="<?php echo $resend_payment_callback_url; ?>" class="button button-primary" style="margin-top: 8px;">
            <?php _e('Resend payment callback', RozetkaPay_Const::TEXT_DOMAIN); ?>
        </a>
        <br />
        <?php
    }

    if ($show_cancel_payment) {
        ?>
        <a href="<?php echo $cancel_payment_url; ?>" class="button button-primary" style="margin-top: 8px;">
            <?php _e('Cancel payment', RozetkaPay_Const::TEXT_DOMAIN); ?>
        </a>
        <br />
        <?php
    }

?>
