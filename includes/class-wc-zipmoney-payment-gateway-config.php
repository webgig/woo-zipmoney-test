<?php
class WC_Zipmoney_Payment_Gateway_Config
{
    const LOGO_SOURCE_URL = "http://d3k1w8lx8mqizo.cloudfront.net/logo/25px/";

    const CONFIG_ENABLED = 'enabled';
    const CONFIG_SANDBOX = 'sandbox';
    const CONFIG_SANDBOX_MERCHANT_ID = 'sandbox_merchant_id';
    const CONFIG_SANDBOX_MERCHANT_KEY = 'sandbox_merchant_key';
    const CONFIG_MERCHANT_ID = 'merchant_id';
    const CONFIG_MERCHANT_KEY = 'merchant_key';
    const CONFIG_DEBUG = 'debug';
    const CONFIG_IS_EXPRESS = 'is_express';
    const CONFIG_IS_EXPRESS_PRODUCT_PAGE = 'is_express_product_page';
    const CONFIG_IS_EXPRESS_CART = 'is_express_cart';
    const CONFIG_IS_IFRAME_FLOW = 'is_iframe_flow';
    const CONFIG_DISPLAY_WIDGET = 'display_widget';
    const CONFIG_DISPLAY_WIDGET_PRODUCT_PAGE = 'display_widget_product_page';
    const CONFIG_DISPLAY_WIDGET_CART = 'display_widget_cart';
    const CONFIG_DISPLAY_BANNERS = 'display_banners';
    const CONFIG_DISPLAY_BANNER_SHOP = 'display_banner_shop';
    const CONFIG_DISPLAY_BANNER_PRODUCT_PAGE = 'display_banner_product_page';
    const CONFIG_DISPLAY_BANNER_CATEGORY = 'display_banner_category';
    const CONFIG_DISPLAY_BANNER_CART = 'display_banner_cart';
    const CONFIG_DISPLAY_TAGLINE_PRODUCT_PAGE = 'display_tagline_product_page';
    const CONFIG_DISPLAY_TAGLINE_CART = 'display_tagline_cart';

    const SINGLE_CONFIG_API_KEY = '_api_hash';


    public static $zip_order_status = array(
        'wc-zip-authorised' => 'Authorised',
        'wc-zip-under-review' => 'Under Review'
    );

    //return the admin form fields
    public static function get_admin_form_fields(){
        return array(
            self::CONFIG_ENABLED => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'label' => __('Enable ZipMoney Payment', 'woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            self::CONFIG_SANDBOX => array(
                'title' => __('Sandbox', 'woocommerce'),
                'label' => __('Enable Sandbox Mode', 'woocommerce'),
                'type' => 'checkbox',
                'desc_tip' => __('Place the payment gateway in sandbox mode using sandbox API credentials for testing.', 'woocommerce'),
                'default' => 'no'
            ),
            self::CONFIG_SANDBOX_MERCHANT_ID => array(
                'title' => __('Sandbox Merchant ID', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('Get your Sandbox Merchant ID from your zipMoney account.', 'woocommerce'),
                'default' => '',
            ),

            self::CONFIG_SANDBOX_MERCHANT_KEY => array(
                'title' => __('Sandbox Merchant Key', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('Get your Sandbox Merchant Key from your zipMoney account.', 'woocommerce'),
                'default' => '',
            ),
            self::CONFIG_MERCHANT_ID => array(
                'title' => __('Merchant ID', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('Get your Merchant ID from your zipMoney account.', 'woocommerce'),
                'default' => '',
            ),
            self::CONFIG_MERCHANT_KEY => array(
                'title' => __('Merchant Key', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('Get your Merchant Key from your zpMoney account.', 'woocommerce'),
                'default' => '',
            ),
            self::CONFIG_DEBUG => array(
                'title' => __('Debug', 'woocommerce'),
                'label' => __('Enable logging', 'woocommerce'),
                'type' => 'checkbox',
                'desc_tip' => __('Enables logging.', 'woocommerce'),
                'default' => 'no'
            ),
            self::CONFIG_IS_EXPRESS => array(
                'title' => __('Express Checkout', 'woocommerce'),
                'label' => __('Enable express checkout.', 'woocommerce'),
                'type' => 'checkbox',
                'desc_tip' => __('Enables express checkout on product & cart page.', 'woocommerce'),
                'default' => 'no'
            ),
            self::CONFIG_IS_EXPRESS_PRODUCT_PAGE => array(
                'label' => __('Enable express checkout on product page.', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            self::CONFIG_IS_EXPRESS_CART => array(
                'label' => __('Enable express checkout on cart.', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            self::CONFIG_IS_IFRAME_FLOW => array(
                'title' => __('Iframe Checkout', 'woocommerce'),
                'label' => __('Enable Iframe Checkout Flow.', 'woocommerce'),
                'type' => 'checkbox',
                'description' => __('In-context checkout flow without leaving the store. In order for this to work on product and cart pages, express checkout has to be turned on for those pages.', 'woocommerce'),
                'default' => 'no'
            ),
            self::CONFIG_DISPLAY_WIDGET => array(
                'title' => __('Marketing Widgets', 'woocommerce'),
                'label' => __('Display Marketing Widgets', 'woocommerce'),
                'type' => 'checkbox',
                'desc_tip' => __('Enables the display of marketing widgets below the add to cart and checkout button.', 'woocommerce'),
                'default' => 'yes'
            ),
            self::CONFIG_DISPLAY_WIDGET_PRODUCT_PAGE => array(
                'label' => __('Display on product page', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            self::CONFIG_DISPLAY_WIDGET_CART => array(
                'label' => __('Display on cart page', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            self::CONFIG_DISPLAY_BANNERS => array(
                'title' => __('Marketing Banners', 'woocommerce'),
                'label' => __('Display Marketing Banners', 'woocommerce'),
                'type' => 'checkbox',
                'desc_tip' => __('Enables the display of marketing banners in the site.', 'woocommerce'),
                'default' => 'no'
            ),
            self::CONFIG_DISPLAY_BANNER_SHOP => array(
                'label' => __('Display on Shop', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            self::CONFIG_DISPLAY_BANNER_PRODUCT_PAGE => array(
                'label' => __('Display on Product Page', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            self::CONFIG_DISPLAY_BANNER_CATEGORY => array(
                'label' => __('Display on Category Page', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            self::CONFIG_DISPLAY_BANNER_CART => array(
                'label' => __('Display on Cart', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            self::CONFIG_DISPLAY_TAGLINE_PRODUCT_PAGE => array(
                'title' => __('Tagline', 'woocommerce'),
                'label' => __('Display on product page', 'woocommerce'),
                'desc_tip' => __('Enables the display of tagline widgets below the price in product page.', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            self::CONFIG_DISPLAY_TAGLINE_CART => array(
                'label' => __('Display on cart page', 'woocommerce'),
                'desc_tip' => __('Enables the display of tagline widgets below grand total in cart page.', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'yes'
            )
        );
    }

    /**
     * Hash the updated merchant_id and merchant_key into a md5 key.
     * This function will be called in the config save hook.
     *
     *
     * @param WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway
     */
    public static function hash_api_key(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway)
    {
        $is_sandbox = self::get_bool_config_by_key($WC_Zipmoney_Payment_Gateway, self::CONFIG_SANDBOX);

        $merchant_id = null;
        $merchant_key = null;

        if($is_sandbox == true) {
            $merchant_id = $WC_Zipmoney_Payment_Gateway->get_option(self::CONFIG_SANDBOX_MERCHANT_ID);
            $merchant_key = $WC_Zipmoney_Payment_Gateway->get_option(self::CONFIG_SANDBOX_MERCHANT_KEY);
        } else {
            $merchant_id = $WC_Zipmoney_Payment_Gateway->get_option(self::CONFIG_MERCHANT_ID);
            $merchant_key = $WC_Zipmoney_Payment_Gateway->get_option(self::CONFIG_MERCHANT_KEY);
        }

        //get the update key
        $update_key = self::get_single_config_key($WC_Zipmoney_Payment_Gateway, self::SINGLE_CONFIG_API_KEY);

        $current_api_hash = get_option($update_key, true);

        //hash the new changes
        $new_hash = md5(serialize(array($merchant_id, $merchant_key)));

        if($current_api_hash !== $new_hash) {
            //update config in single entry
            update_option($update_key, $new_hash);
        }
    }

    /**
     * Get the single config key.
     * It's a single entry in the config table.
     *
     * @param WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway
     * @param $key
     * @return string
     */
    public static function get_single_config_key(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway, $key)
    {
        return $WC_Zipmoney_Payment_Gateway->plugin_id . $WC_Zipmoney_Payment_Gateway->id . $key;
    }

    /**
     * Get the config value by key.
     * NOTE: The value must be 'yes' or 'no'
     *
     * @param WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway
     * @param $key
     * @return bool
     */
    public static function get_bool_config_by_key(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway, $key)
    {
        return $WC_Zipmoney_Payment_Gateway->get_option($key) === 'yes' ? true : false;
    }

}