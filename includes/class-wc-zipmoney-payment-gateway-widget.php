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

        //Tag line
        if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_TAGLINE_PRODUCT_PAGE)){
            add_action('woocommerce_single_product_summary', array($this, 'render_tagline'));
        }
        if($WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_TAGLINE_CART)){
            add_action('woocommerce_proceed_to_checkout', array($this, 'render_tagline'), 10);
        }

        $config_is_express = $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS);
        $config_display_widget = $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET);

        //Express in Customise template
        if ($config_is_express) {
            add_action('zipmoney_wc_render_widget_general', array($this, 'render_express_payment_button'), 12);
        } else if ($config_display_widget) {
            add_action('zipmoney_wc_render_widget_general', array($this, 'render_widget_general'), 10);
        }
        //Express in product page
        if ($config_is_express && $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS_PRODUCT_PAGE)) {
            //Express checkout on product page
            add_action('woocommerce_after_add_to_cart_button', array($this, 'render_express_payment_button'));
        } else if ($config_display_widget && $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_PRODUCT_PAGE)) {
            //The widget on product page
            add_action('woocommerce_after_add_to_cart_button', array($this, 'render_widget_product'));
        }
        //Express in cart page
        if ($config_is_express && $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS_CART)) {
            //Express checkout on cart page
            add_action('woocommerce_after_add_to_cart_button', array($this, 'render_widget_product'));
        } else if ($config_display_widget && $WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_CART)) {
            //The widget on cart page
            add_action('woocommerce_proceed_to_checkout', array($this, 'render_widget_cart'), 20);
        }

        //Init the widget scripts
        add_action('admin_enqueue_scripts', array($this, 'backend_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
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

        wp_register_script('wc-zipmoney-script', esc_url(plugins_url('assets/js/woocommerce-zipmoney-payment-front.js', dirname(__FILE__))), array('thickbox'), '1.0.0', true);
        wp_enqueue_script('wc-zipmoney-script');

        wp_register_script('wc-zipmoney-widget-js', 'https://d3k1w8lx8mqizo.cloudfront.net/lib/js/zm-widget-js/dist/zipmoney-widgets-v1.min.js', array('jquery'), '1.0.5', true);
        wp_enqueue_script('wc-zipmoney-widget-js');

        $is_sandbox = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_SANDBOX);

        $iframe_lib_url = $is_sandbox ? WC_Zipmoney_Payment_Gateway_Config::IFRAME_API_URL_SANDBOX : WC_Zipmoney_Payment_Gateway_Config::IFRAME_API_URL_PRODUCTION;

        wp_register_script('wc-zipmoney-js', $iframe_lib_url);
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

        if (preg_match("/Learn More/", $description)){
            return $description;
        }

        $html = null;

        return $description . ' <a  id="zipmoney-learn-more" class="zip-hover"  zm-widget="popup"  zm-popup-asset="termsdialog">Learn More</a>
    <script>if(window.$zmJs!==undefined) window.$zmJs._collectWidgetsEl(window.$zmJs);</script>';
    }
}