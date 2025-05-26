<?php

/** @var string $view_mode */

global $product;

if ($view_mode === 'white') {
    $css_class = 'white';
    $img_color = 'black';
} else {
    $css_class = '';
    $img_color = 'white';
}

?>
<form method="post">
    <?php wp_nonce_field('rozetkapay_one_click_action', 'rozetkapay_one_click_nonce'); ?>
    <input type="hidden" name="<?php echo esc_html(RozetkaPay_Const::ID_BUY_ONE_CLICK); ?>" value="-1" />
    <div class="btn-rozetka-wrapper">
        <button
            class="button alt wp-element-button btn-rozetka <?php echo esc_html($css_class); ?> view-variant_2"
            onclick="return rozetkapay_one_click(this);"
        >
            <span><?php esc_html_e('Купити з', 'buy-rozetkapay-woocommerce'); ?></span>
            <img
                src="<?php echo esc_url(ROZETKAPAY_GATEWAY_PLUGIN_URL); ?>assets/img/rozetka_ec_logo_variant_2_<?php echo esc_html($img_color); ?>.svg"
                class="img-responsive"
                alt="<?php esc_html_e('Buy via RozetkaPay', 'buy-rozetkapay-woocommerce'); ?>"
            >
        </button>
    </div>
</form>
<script type="text/javascript">
    function rozetkapay_one_click(_this) {
        jQuery(_this).closest('form').submit();

        return false;
    }
</script>
