<?php

class WC_Zipmoney_Payment_Gateway extends WC_Payment_Gateway {

    //essential settings
    public $id = 'zipmoney';
    public $icon = '';
    public $has_fields = false;
    public $method_title = 'ZipMoney';
    public $method_description = 'ZipMoney Payments allows real-time credit to customers in a seamless and user friendly way.';

    public $form_fields;

    public function __construct()
    {
        //load dependencies
        self::_load_dependencies();

        //load settings
        self::init_settings();

        //load form fields
        self::init_form_fields();
    }


    private function _init_hooks()
    {
        //save admin options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        //show
    }


    private function _load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-config.php';
    }

    public function init_settings()
    {
        //load the parent settings
        parent::init_settings();
    }

    public function init_form_fields()
    {
        $this->form_fields = WC_Zipmoney_Payment_Gateway_Config::get_admin_form_fields();
    }

    public function admin_options()
    {
        echo '<h3>';
        _e($this->method_title, 'woocommerce');
        echo '</h3>';
        echo '<p>';
        _e($this->method_description, 'woocommerce');
        echo '</p>';
        echo '<table class="form-table">';
        echo parent::generate_settings_html();
        echo '</table>';
        echo '<script src="' . esc_url(plugins_url('assets/js/admin_options.js', dirname(__FILE__))) . '"></script>';
    }

    public function process_admin_options()
    {
        $result = parent::process_admin_options();

        WC_Zipmoney_Payment_Gateway_Config::hash_api_key($this);

        return $result;
    }


    public function run()
    {
        //load the hooks
        self::_init_hooks();
    }
}