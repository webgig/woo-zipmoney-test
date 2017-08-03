jQuery('#woocommerce_zipmoney_sandbox').change(function () {
    var sandbox = jQuery('#woocommerce_zipmoney_sandbox_merchant_id, #woocommerce_zipmoney_sandbox_merchant_key').closest('tr');
    production = jQuery('#woocommerce_zipmoney_merchant_id, #woocommerce_zipmoney_merchant_key').closest('tr');

    if (jQuery(this).is(':checked')) {
        sandbox.show();
        production.hide();
    } else {
        sandbox.hide();
        production.show();
    }

}).change();

jQuery('#woocommerce_zipmoney_display_banners').change(function () {

    var banner_settings = jQuery('#woocommerce_zipmoney_display_banner_shop, #woocommerce_zipmoney_display_banner_productpage, #woocommerce_zipmoney_display_banner_category, #woocommerce_zipmoney_display_banner_cart');
    var banner_settings_tr = banner_settings.closest('tr');

    if (jQuery(this).is(':checked')) {
        banner_settings_tr.show();
    } else {
        banner_settings_tr.hide();
    }

}).change();

jQuery('#woocommerce_zipmoney_display_widget').change(function () {

    var banner_settings = jQuery('#woocommerce_zipmoney_display_widget_productpage, #woocommerce_zipmoney_display_widget_cart');
    var banner_settings_tr = banner_settings.closest('tr');

    if (jQuery(this).is(':checked')) {
        banner_settings_tr.show();
    } else {
        banner_settings_tr.hide();
    }

}).change();

jQuery('#woocommerce_zipmoney_is_express').change(function () {

    var banner_settings = jQuery('#woocommerce_zipmoney_is_express_productpage, #woocommerce_zipmoney_is_express_cart');
    var banner_settings_tr = banner_settings.closest('tr');

    if (jQuery(this).is(':checked')) {
        banner_settings_tr.show();
    } else {
        banner_settings_tr.hide();
    }

}).change();