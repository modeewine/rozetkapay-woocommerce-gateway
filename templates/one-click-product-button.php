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
<input type="hidden" name="<?php echo RozetkaPay_Const::ID_BUY_ONE_CLICK; ?>" value="" />
<div class="btn-rozetka-wrapper">
    <button
        class="button alt wp-element-button btn-rozetka <?php echo $css_class; ?> view-variant_2"
        onclick="return rozetkapay_one_click(this);"
    >
        <span><?php _e('Купити з', RozetkaPay_Const::TEXT_DOMAIN); ?></span>
        <img
             src="<?php echo ROZETKAPAY_GATEWAY_PLUGIN_URL; ?>assets/img/rozetka_ec_logo_variant_2_<?php echo $img_color; ?>.svg"
             class="img-responsive"
             alt="<?php _e('Buy via RozetkaPay', RozetkaPay_Const::TEXT_DOMAIN); ?>"
        >
    </button>
</div>
<script type="text/javascript">
    function rozetkapay_one_click(_this) {
        let product_id = <?php echo $product->get_id(); ?>;
        let roc = jQuery('input[name=<?php echo RozetkaPay_Const::ID_BUY_ONE_CLICK; ?>]');

        roc.prop({value: product_id});
        jQuery(_this).closest('form').submit();
        roc.prop({value: ''});

        return false;
    }
</script>
