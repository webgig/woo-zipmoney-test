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
        //save admin options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        $this->WC_Zipmoney_Payment_Gateway_Widget->init_hooks();
    }

    private function _load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-config.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-widget.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-util.php';
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

    public function process_payment($order_id)
    {
        return parent::process_payment($order_id);
    }

}