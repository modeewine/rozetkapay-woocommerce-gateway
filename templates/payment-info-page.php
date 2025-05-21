<?php

    /** @var int $order_id */
    /** @var string|null $error_message */
    /** @var string $json_data */

    $back_url = admin_url('admin.php?page=wc-orders&action=edit&id=' . $order_id);

?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('RozetkaPay payment information for the external order ID', RozetkaPay_Const::TEXT_DOMAIN) ?>:
        <?php echo $order_id; ?>
    </h1>
    <hr class="wp-header-end" style="margin: 4px 0;" />
    <a href="<?php echo esc_url($back_url) ?>" class="page-title-action"><?php _e('Back to order view', RozetkaPay_Const::TEXT_DOMAIN) ?></a>
    <hr class="wp-header-end" style="margin: 4px 0;" />
    <?php

        if ($error_message !== null) {
            ?>
            <p><?php echo $error_message; ?></p>
            <?php
        } else {
            ?>
            <pre
                    style="padding: 24px; overflow: scroll; background-color: #000; font-size: 11px; color: #CCC;"
            ><?php echo esc_html($json_data); ?></pre>
            <?php
        }

    ?>
</div>
