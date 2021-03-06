<?php
class WC_Zipmoney_Payment_Gateway_Widget
{
    private $WC_Zipmoney_Payment_Gateway;

    public function __construct(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway)
    {
        $this->WC_Zipmoney_Payment_Gateway = $WC_Zipmoney_Payment_Gateway;
    }

    public function init_hooks()
    {
        add_action('woocommerce_before_main_content', array($this, 'render_root_el'));
        add_action('woocommerce_before_cart', array($this, 'render_root_el'));
        add_action('woocommerce_before_checkout_form', array($this, 'render_root_el'));

        add_filter('woocommerce_gateway_description', array($this, 'updateMethodDescription'), 10, 2);

        $WC_Zipmoney_Payment_Gateway_Config = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config;

        //inject the order button
        add_filter('woocommerce_order_button_html', array($this, 'order_button'), 10, 2);

        //use this hook to convert customer address
        add_action('woocommerce_checkout_update_order_review', array('WC_Zipmoney_Payment_Gateway_Util', 'update_customer_details'));

        //add banner hook
        self::_add_banner_hook($WC_Zipmoney_Payment_Gateway_Config);

        //Tag line
        self::_add_tagline_hook($WC_Zipmoney_Payment_Gateway_Config);

        //Widget
        self::_add_widget_hook($WC_Zipmoney_Payment_Gateway_Config);

        //Add the express button
        //TODO: Express checkout is not completed at this state
//        self::_add_express_button_hook($WC_Zipmoney_Payment_Gateway_Config);

        //Init the widget scripts
        add_action('admin_enqueue_scripts', array($this, 'backend_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));

        //Add the capture charge button and cancel charge button
        add_action('woocommerce_order_item_add_action_buttons', array($this, 'action_add_charge_buttons'));

        //Add the authorised status for payment complete
        add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array($this, 'filter_add_authorize_order_status_for_payment_complete'));

        //add the payment gateway hook to order total
        add_filter( 'woocommerce_available_payment_gateways', array($this, 'process_available_payment_gateways_with_order_threshold'));

        //add the notification section on checkout page
        add_action('woocommerce_before_checkout_form', array($this, 'add_zip_notification_section_on_checkout'));
    }

    public function add_zip_notification_section_on_checkout($wccm_autocreate_account)
    {
        include plugin_dir_path(dirname(__FILE__)) . 'includes/view/frontend/checkout_notification_section.php';
    }


    public function process_available_payment_gateways_with_order_threshold($gateways)
    {
        if (isset($gateways[$this->WC_Zipmoney_Payment_Gateway->id]) == false) {
            //if the zipmoney payment is not active, then we won't process anything
            return $gateways;
        }

        if(WC()->cart->total < $this->WC_Zipmoney_Payment_Gateway->get_option(WC_Zipmoney_Payment_Gateway_Config::CONFIG_ORDER_THRESHOLD_MIN_TOTAL)){
            //if the cart total has exceeded the threshold, then we will hide the payment option
            unset($gateways[$this->WC_Zipmoney_Payment_Gateway->id]);
        }

        return $gateways;
    }

    /**
     * Added the authorize status for payment complete
     *
     * @param $statuses
     * @param $instance
     * @return array
     */
    public function filter_add_authorize_order_status_for_payment_complete($statuses, $instance = null)
    {
        $statuses[] = str_replace('wc-', '', WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY);

        return $statuses;
    }

    /**
     * Add the capture charge button to admin order page
     *
     * @param WC_Order $order
     */
    public function action_add_charge_buttons(WC_Order $order)
    {
        include plugin_dir_path(dirname(__FILE__)) . 'includes/view/backend/charge_buttons.php';
    }


    /**
     * Add the widget hook
     *
     * @param WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config
     */
    private function _add_widget_hook(WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config)
    {
        $config_display_widget = $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET);

        if($config_display_widget){
            //if the widget is enable
            add_action('zipmoney_wc_render_widget_general', array($this, 'render_widget_general'), 10);

            if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_PRODUCT_PAGE)){
                //product page widget
                add_action('woocommerce_after_add_to_cart_button', array($this, 'render_widget_product'));
            }

            if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_CART)) {
                //cart page widget
                add_action('woocommerce_proceed_to_checkout', array($this, 'render_widget_cart'), 20);
            }
        }
    }


    /**
     * TODO: Express checkout is disabled at this state. It will be implemented in the future
     *
     * Add express button hook
     *
     * @param WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config
     */
//    private function _add_express_button_hook(WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config)
//    {
//        $config_is_express = $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS);
//        $config_display_widget = $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET);
//
//        //Express in Customise template
//        if ($config_is_express) {
//            add_action('zipmoney_wc_render_widget_general', array($this, 'render_express_payment_button'), 12);
//        } else if ($config_display_widget) {
//            add_action('zipmoney_wc_render_widget_general', array($this, 'render_widget_general'), 10);
//        }
//        //Express in product page
//        if ($config_is_express && $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS_PRODUCT_PAGE)) {
//            //Express checkout on product page
//            add_action('woocommerce_after_add_to_cart_button', array($this, 'render_express_payment_button'));
//        } else if ($config_display_widget && $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_PRODUCT_PAGE)) {
//            //The widget on product page
//            add_action('woocommerce_after_add_to_cart_button', array($this, 'render_widget_product'));
//        }
//        //Express in cart page
//        if ($config_is_express && $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS_CART)) {
//            //Express checkout on cart page
//            add_action('woocommerce_after_add_to_cart_button', array($this, 'render_widget_product'));
//        } else if ($config_display_widget && $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_CART)) {
//            //The widget on cart page
//            add_action('woocommerce_proceed_to_checkout', array($this, 'render_widget_cart'), 20);
//        }
//    }


    /**
     * Add the tagline hook
     *
     * @param WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config
     */
    private function _add_tagline_hook(WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config)
    {
        if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_TAGLINE_PRODUCT_PAGE)){
            add_action('woocommerce_single_product_summary', array($this, 'render_tagline'));
        }
        if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_TAGLINE_CART)){
            add_action('woocommerce_proceed_to_checkout', array($this, 'render_tagline'), 10);
        }
    }

    /**
     * Renders the widget below add to cart / proceed to checkout button in product or cart pages.
     *
     * @access public
     */
    public function render_widget_cart()
    {
        echo '<div class="widget-cart" data-zm-asset="cartwidget" zm-widget="popup"  data-zm-popup-asset="termsdialog"></div>';
    }

    /**
     * Add the banner hook
     *
     * @param WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config
     */
    private function _add_banner_hook(WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config)
    {
        //Banners
        if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNERS)){
            //if the display banner is enabled
            if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNER_SHOP)){
                add_action('woocommerce_before_main_content', array($this, 'render_banner_shop'));
            }

            if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNER_PRODUCT_PAGE)){
                add_action('woocommerce_before_main_content', array($this, 'render_banner_product_page'));
            }

            if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNER_CATEGORY)){
                add_action('woocommerce_before_main_content', array($this, 'render_banner_category'));
            }

            if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNER_CART)){
                add_action('woocommerce_before_main_content', array($this, 'render_banner_cart'));
            }
        }
    }


    /**
     * Outputs style used for ZipMoney Payment admin section
     */
    public function backend_scripts()
    {
        wp_register_style(
            'wc-zipmoney-style-admin',
            esc_url(plugins_url('assets/css/woocommerce-zipmoney-payment-admin.css', dirname(__FILE__))),
            array(),
            '20151118', 'all'
        );
        wp_enqueue_style('wc-zipmoney-style-admin');

    }

    /**
     * Register style and scripts required
     */
    public function frontend_scripts()
    {

        wp_register_style('wc-zipmoney-style', esc_url(plugins_url('assets/css/woocommerce-zipmoney-payment-front.css', dirname(__FILE__))));
        wp_enqueue_style('wc-zipmoney-style');

        wp_register_script('wc-zipmoney-script', esc_url(plugins_url('assets/js/woocommerce-zipmoney-payment-front.js', dirname(__FILE__))), array('thickbox'), '2.0.0', true);
        wp_enqueue_script('wc-zipmoney-script');

        wp_register_script('wc-zipmoney-widget-js', 'https://d3k1w8lx8mqizo.cloudfront.net/lib/js/zm-widget-js/dist/zipmoney-widgets-v1.min.js', array('jquery'), '2.0.0', true);
        wp_enqueue_script('wc-zipmoney-widget-js');

        wp_register_script('wc-zipmoney-checkout-js', 'https://static.zipmoney.com.au/checkout/checkout-v1.js', array('jquery'));
        wp_enqueue_script('wc-zipmoney-checkout-js');

        wp_enqueue_script('wc-zipmoney-js');
    }


    /**
     * Renders the widget below add to cart / proceed to checkout button in product or cart pages.
     *
     * @access public
     */
    public function render_widget_product()
    {
        echo '<div class="widget-product" data-zm-asset="productwidget" data-zm-widget="popup"  data-zm-popup-asset="termsdialog"></div>';
    }


    /**
     * Renders the widget below add to cart / proceed to checkout button in product or cart pages.
     *
     * @access public
     */
    public function render_widget_general()
    {
        echo '<div class="widget-product-cart" data-zm-asset="productwidget" data-zm-widget="popup"  data-zm-popup-asset="termsdialog"></div>';
    }

    /**
     * Renders the express payment button.
     *
     * @access public
     */
    public function render_express_payment_button()
    {
        include plugin_dir_path(dirname(__FILE__)) . 'includes/view/frontend/express_payment_button.php';
    }

    /**
     * Renders the banner in the shop page.
     *
     * @access public
     */
    public function render_banner_shop()
    {
        if (is_shop())
            $this->_render_banner();
    }

    /**
     * Renders the banner in the cart page.
     *
     * @access public
     */
    public function render_banner_cart()
    {
        if (is_cart())
            $this->_render_banner();
    }

    /**
     * Renders the banner in the product page.
     *
     * @access public
     */
    public function render_banner_product_page()
    {
        if (is_product())
            $this->_render_banner();
    }

    /**
     * Renders the banner in the category page.
     *
     * @access public
     */
    public function render_banner_category()
    {
        if (is_product_category())
            $this->_render_banner();
    }

    /**
     * Renders the widget below add to cart / proceed to checkout button in product or cart pages.
     */
    public function render_tagline()
    {
        echo '<div id="zip-tagline" data-zm-widget="tagline"  data-zm-info="true"></div>';
    }


    /**
     * Renders the banner across the shop, cart, product, category pages.
     *
     * @access private
     */
    private function _render_banner()
    {
        echo '<div class="zipmoney-strip-banner"  zm-asset="stripbanner"   zm-widget="popup"  zm-popup-asset="termsdialog" ></div>';
    }

    /**
     * Renders the element to store the merchant public key for widget to get content from API
     */
    public function render_root_el()
    {
        echo '<div data-zm-merchant="'.$this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_merchant_public_key().'" data-env="' .
            $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_environment() . '"></div> ';
    }

    /**
     * Updated the method description text to include the Learn More link.
     *
     * @access public
     * @param string $description , string $id
     * @return string $description
     */
    public function updateMethodDescription($description, $id)
    {
        if ($id != $this->WC_Zipmoney_Payment_Gateway->id){
            return $description;
        }

        return '<span zm-widget=\'inline\' zm-asset=\'checkoutdescription\'></span> <a  id="zipmoney-learn-more" class="zip-hover"  zm-widget="popup"  zm-popup-asset="checkoutdialog">Learn More</a>
    <script>if(window.$zmJs!==undefined) window.$zmJs._collectWidgetsEl(window.$zmJs);</script>';
    }


    /**
     * Renders the place order button in the checkout page by using the checkout.js
     *
     * @access public
     */
    public function order_button($text)
    {

        $is_iframe_checkout = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_IFRAME_FLOW);

        include plugin_dir_path(dirname(__FILE__)) . 'includes/view/frontend/order_button.php';

        return $text;
    }
}