<?php

class WC_Zipmoney_Payment_Gateway extends WC_Payment_Gateway {

    //essential settings
    public $id = 'zipmoney';
    public $icon = '';
    public $has_fields = true;
    public $method_title = 'ZipMoney';
    public $method_description = 'ZipMoney Payments allows real-time credit to customers in a seamless and user friendly way.';
    public $title = 'Pay Later with zipPay';
    public $description = 'No interest ever - get a decision in seconds!';

    public $form_fields;

    public $WC_Zipmoney_Payment_Gateway_Config;
    public $WC_Zipmoney_Payment_Gateway_Widget;
    public $WC_Zipmoney_Payment_Gateway_Api_Request;

    public function __construct()
    {
        //load dependencies
        self::_load_dependencies();

        //load settings
        self::init_settings();

        //load form fields
        self::init_form_fields();
    }

    /**
     * Initialize the web hook
     */
    private function _init_hooks()
    {
        //have some checking
        add_action('admin_notices', array($this, 'check_requirement'));

        //save admin options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        $this->WC_Zipmoney_Payment_Gateway_Widget->init_hooks();
    }

    private function _load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-config.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-widget.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-util.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-wc-zipmoney-payment-gateway-api-request.php';
    }

    /**
     * Return the form fields array
     */
    public function init_form_fields()
    {
        $this->form_fields = WC_Zipmoney_Payment_Gateway_Config::get_admin_form_fields();
    }

    /**
     * Print the admin options fields
     */
    public function admin_options()
    {
        //this variable will be used in the include php file
        $admin_option_js = esc_url(plugins_url('assets/js/admin_options.js', dirname(__FILE__)));

        include plugin_dir_path(dirname(__FILE__)) . 'includes/view/backend/admin_options.php';
    }


    /**
     * Add the hash api key process into admin option processing
     *
     * @return bool
     */
    public function process_admin_options()
    {
        $result = parent::process_admin_options();

        $this->WC_Zipmoney_Payment_Gateway_Config->hash_api_key($this);
        //update the log level
        WC_Zipmoney_Payment_Gateway_Util::$config_log_level = self::get_option(WC_Zipmoney_Payment_Gateway_Config::CONFIG_LOGGING_LEVEL);

        return $result;
    }


    public function run()
    {
        $this->WC_Zipmoney_Payment_Gateway_Config = new WC_Zipmoney_Payment_Gateway_Config($this);
        $this->WC_Zipmoney_Payment_Gateway_Widget = new WC_Zipmoney_Payment_Gateway_Widget($this);


        //check the logger is enable or not
        WC_Zipmoney_Payment_Gateway_Util::$config_log_level =
            $this->WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_LOGGING_LEVEL);

        //load the hooks
        self::_init_hooks();
    }

    /**
     * Check the environment meet the minimum requirement
     */
    public function check_requirement()
    {
        if($this->WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_ENABLED) == false) {
            return;
        }

        // PHP Version
        if (version_compare(phpversion(), '5.2.1', '<')) {
            echo '<div class="error"><p>' . sprintf(__('ZipMoney Error: ZipMoney requires PHP 5.3 and above. You are using version %s.', 'woocommerce'), phpversion()) . '</p></div>';
        } // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
        elseif ('no' == get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS')) {
            echo '<div class="error"><p>' . sprintf(__('WARN: ZipMoney is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - ZipMoney will only work in sandbox mode.', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
        }
    }

    /**
     * Process payment after the order is created
     *
     * @param int $order_id
     * @return array|null
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);

        WC_Zipmoney_Payment_Gateway_Util::log('process payment');

        $this->WC_Zipmoney_Payment_Gateway_Config = new WC_Zipmoney_Payment_Gateway_Config($this);
        $this->WC_Zipmoney_Payment_Gateway_Api_Request = new WC_Zipmoney_Payment_Gateway_Api_Request($this);

        try {
            $checkout_response = $this->WC_Zipmoney_Payment_Gateway_Api_Request->checkout(
                $order,
                $this->get_return_url($order),
                $this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_public_key()
            );

            return array(
                'result' => 'success',
                'redirect' => $checkout_response->getUri()
            );
        } catch (Exception $exception) {
            wc_add_notice(__('Payment error:', 'woothemes') . $exception->getMessage(), 'error');
            return null;
        }

    }

}