<?php

class WC_Zipmoney_Payment_Gateway_Config
{
    const PLATFORM = 'Woocommerce';
    const CLIENT = 'WooCommerce ZipMoney Payment API';

    const POST_TYPE_QUOTE = 'shop_quote';
    const POST_TYPE_ORDER = 'shop_order';

    const ZIP_ORDER_STATUS_AUTHORIZED_KEY = 'wc-zip-authorised';    //The key to write in the DB
    const ZIP_ORDER_STATUS_AUTHORIZED_NAME = 'Authorised';  //The label

    const USER_META_ADMIN_NOTICE = 'zip-admin-notice';

    const LOGO_SOURCE_URL = "http://d3k1w8lx8mqizo.cloudfront.net/logo/25px/";

    const IFRAME_API_URL_PRODUCTION = 'https://account.zipmoney.com.au/scripts/iframe/zipmoney-checkout.js';
    const IFRAME_API_URL_SANDBOX = 'https://account.sandbox.zipmoney.com.au/scripts/iframe/zipmoney-checkout.js';

    const META_CHECKOUT_ID = '_zipmoney_checkout_id';
    const META_CHARGE_ID = '_zipmoney_charge_id';

    //Admin setting key
    const CONFIG_ENABLED = 'enabled';
    const CONFIG_SANDBOX = 'sandbox';
    const CONFIG_SANDBOX_MERCHANT_PUBLIC_KEY = 'sandbox_merchant_public_key';
    const CONFIG_SANDBOX_MERCHANT_PRIVATE_KEY = 'sandbox_merchant_private_key';
    const CONFIG_MERCHANT_PUBLIC_KEY = 'merchant_public_key';
    const CONFIG_MERCHANT_PRIVATE_KEY = 'merchant_private_key';
    const CONFIG_CHARGE_CAPTURE = 'charge_capture';
    const CONFIG_LOGGING_LEVEL = 'log_level';
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
    const SINGLE_CONFIG_API_SETTINGS = '_api_settings';

    //Log levels
    const LOG_LEVEL_ALL = 1;
    const LOG_LEVEL_DEBUG = 2;
    const LOG_LEVEL_INFO = 3;
    const LOG_LEVEL_WARN = 4;
    const LOG_LEVEL_ERROR = 5;
    const LOG_LEVEL_FATAL = 6;
    const LOG_LEVEL_OFF = 7;


    public static $zip_order_status = array(
        'wc-zip-authorised' => 'Authorised',
        'wc-zip-under-review' => 'Under Review'
    );

    public $WC_Zipmoney_Payment_Gateway;

    /**
     * We need to load the gateway class to use it's build-in functions
     *
     * WC_Zipmoney_Payment_Gateway_Config constructor.
     * @param WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway
     */
    public function __construct(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway)
    {
        $this->WC_Zipmoney_Payment_Gateway = $WC_Zipmoney_Payment_Gateway;
    }

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
            self::CONFIG_SANDBOX_MERCHANT_PUBLIC_KEY => array(
                'title' => __('Sandbox Merchant Public Key', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('Get your Sandbox Merchant Public Key from your zipMoney account.', 'woocommerce'),
                'default' => '',
            ),
            self::CONFIG_SANDBOX_MERCHANT_PRIVATE_KEY => array(
                'title' => __('Sandbox Merchant Private Key', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('Get your Sandbox Merchant Private Key from your zipMoney account.', 'woocommerce'),
                'default' => '',
            ),
            self::CONFIG_MERCHANT_PUBLIC_KEY => array(
                'title' => __('Merchant Public Key', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('Get your Merchant Public Key from your zipMoney account.', 'woocommerce'),
                'default' => '',
            ),
            self::CONFIG_MERCHANT_PRIVATE_KEY => array(
                'title' => __('Merchant Key', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => __('Get your Merchant Private Key from your zpMoney account.', 'woocommerce'),
                'default' => '',
            ),
            self::CONFIG_CHARGE_CAPTURE => array(
                'title' => __('Charge Capture', 'woocommerce'),
                'label' => 'Immediately after checkout',
                'type' => 'checkbox',
                'desc_tip' => __('If it is checked, this will be a direct capture. Un-check it to perform an authorisation only.', 'woocommerce'),
                'default' => 'yes'
            ),
            self::CONFIG_LOGGING_LEVEL => array(
                'title' => __('Log Message level', 'woocommerce'),
                'description' => __('The log level will be used to log the messages. The orders are: ALL < DEBUG < INFO < WARN < ERROR < FATAL < OFF.'),
                'type' => 'select',
                'default' => self::LOG_LEVEL_ALL,
                'options' => array(
                    self::LOG_LEVEL_ALL => 'All messages',
                    self::LOG_LEVEL_DEBUG => 'Debug (and above)',
                    self::LOG_LEVEL_INFO => 'Info (and above)',
                    self::LOG_LEVEL_WARN => 'Warn (and above)',
                    self::LOG_LEVEL_ERROR => 'Error (and above)',
                    self::LOG_LEVEL_FATAL => 'Fatal (and above)',
                    self::LOG_LEVEL_OFF => 'Off (No message will be logged)'
                )
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
                'label' => __('Enable In-Context Checkout Flow.', 'woocommerce'),
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

    public function get_checkout_redirect_url()
    {
        $url = get_home_url();

        if(self::get_bool_config_by_key(self::CONFIG_IS_IFRAME_FLOW)){
            $url .= '/zipmoneypayment/expresscheckout/getredirecturl/';
        } else {
            $url .= '/zipmoneypayment/expresscheckout/';
        }

        if (is_product()) {
            global $product;
            $checkout_url = add_query_arg(array(
                'product_id' => $product->id,
                'checkout_source' => 'product_page'
            ), $url);
        } elseif (is_cart()) {
            $checkout_url = add_query_arg(array(
                'checkout_source' => 'cart'
            ), $url);
        } else {
            $checkout_url = add_query_arg(array(
                'checkout_source' => 'checkout'
            ), $url);
        }

        return $checkout_url;
    }

    /**
     * Hash the updated merchant_id and merchant_key into a md5 key.
     * This function will be called in the config save hook.
     *
     */
    public function hash_api_key()
    {
        $merchant_public_key = self::get_merchant_public_key();
        $merchant_private_key = self::get_merchant_private_key();

        //get the update key
        $update_key = self::get_single_config_key(self::SINGLE_CONFIG_API_KEY);

        $current_api_hash = get_option($update_key, true);

        //hash the new changes
        $new_hash = md5(serialize(array($merchant_public_key, $merchant_private_key)));

        if($current_api_hash !== $new_hash) {
            //update config in single entry
            update_option($update_key, $new_hash);
        }
    }

    /**
     * Return the environment
     *
     * @return string
     */
    public function get_environment()
    {
        return self::get_bool_config_by_key(self::CONFIG_SANDBOX) ? 'sandbox' : 'production';
    }

    /**
     * Get the merchant public key
     *
     * @return string
     */
    public function get_merchant_public_key()
    {
        if(self::get_bool_config_by_key(self::CONFIG_SANDBOX)){
            return $this->WC_Zipmoney_Payment_Gateway->get_option(self::CONFIG_SANDBOX_MERCHANT_PUBLIC_KEY);
        }

        return $this->WC_Zipmoney_Payment_Gateway->get_option(self::CONFIG_MERCHANT_PUBLIC_KEY);
    }

    /**
     * Get the merchant private key
     *
     * @return string
     */
    public function get_merchant_private_key()
    {
        if(self::get_bool_config_by_key(self::CONFIG_SANDBOX)){
            return $this->WC_Zipmoney_Payment_Gateway->get_option(self::CONFIG_SANDBOX_MERCHANT_PRIVATE_KEY);
        }

        return $this->WC_Zipmoney_Payment_Gateway->get_option(self::CONFIG_MERCHANT_PRIVATE_KEY);
    }


    /**
     * Get the single config key.
     * It's a single entry in the config table.
     *
     * @param $key
     * @return string
     */
    public function get_single_config_key($key)
    {
        return $this->WC_Zipmoney_Payment_Gateway->plugin_id . $this->WC_Zipmoney_Payment_Gateway->id . $key;
    }

    /**
     * Get the config value by key.
     * NOTE: The value must be 'yes' or 'no'
     *
     * @param $key
     * @return bool
     */
    public function get_bool_config_by_key($key)
    {
        return $this->WC_Zipmoney_Payment_Gateway->get_option($key) === 'yes' ? true : false;
    }

}