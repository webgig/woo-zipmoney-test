<?php

class WC_Zipmoney_Payment_Gateway extends WC_Payment_Gateway {

    //essential settings
    public $id = 'zipmoney';
    public $icon = '';
    public $has_fields = true;
    public $method_title = 'ZipMoney';
    public $method_description = 'ZipMoney Payments allows real-time credit to customers in a seamless and user friendly way.';
    public $title = 'Pay Later with zipPay';
    public $description = 'Interest Free Always!';

    public $version = '1.0.0-rc3';

    public $supports = array('products', 'refunds');

    public $form_fields;

    public $WC_Zipmoney_Payment_Gateway_Config;
    public $WC_Zipmoney_Payment_Gateway_Widget;

    public function __construct()
    {
        //load dependencies
        self::_load_dependencies();

        //load form fields
        self::init_form_fields();

        //load settings
        self::init_settings();
    }

    /**
     * Initialize the web hook
     */
    private function _init_hooks()
    {
        add_action('init', array('WC_Zipmoney_Payment_Gateway_Util', 'add_rewrite_rules'));
        add_action('init', array('WC_Zipmoney_Payment_Gateway_Util', 'register_quote_post_type'));

        add_action('init', array('WC_Zipmoney_Payment_Gateway_Util', 'register_zip_order_statuses'));
        //add the zipmoney status
        add_filter('wc_order_statuses', array('WC_Zipmoney_Payment_Gateway_Util', 'add_zipmoney_to_order_statuses'));

        add_action('parse_request', array($this, 'process_zipmoney_actions'));

        //have some checking
        add_action('admin_notices', array($this, 'check_requirement'));

        //save admin options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        $this->WC_Zipmoney_Payment_Gateway_Widget->init_hooks();

        add_action('admin_notices', array($this, 'show_notices'));
    }

    private function _load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-config.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-widget.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-zipmoney-payment-gateway-util.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/controller/class-wc-zipmoney-payment-abstract-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/controller/class-wc-zipmoney-payment-checkout-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/controller/class-wc-zipmoney-payment-charge-controller.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-wc-zipmoney-payment-gateway-api-abstract.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-wc-zipmoney-payment-gateway-api-checkout.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/class-wc-zipmoney-payment-gateway-api-charge.php';
    }

    /**
     * Return the form fields array
     */
    public function init_form_fields()
    {
        $this->form_fields = WC_Zipmoney_Payment_Gateway_Config::get_admin_form_fields();
    }

    public function init_settings()
    {
        parent::init_settings();

        $this->title = self::get_option(WC_Zipmoney_Payment_Gateway_Config::CONFIG_TITLE, '');
        $this->icon = self::get_option(WC_Zipmoney_Payment_Gateway_Config::CONFIG_PRODUCT, '') == WC_Zipmoney_Payment_Gateway_Config::PRODUCT_ZIP_PAY ?
            WC_Zipmoney_Payment_Gateway_Config::LOGO_ZIP_PAY : WC_Zipmoney_Payment_Gateway_Config::LOGO_ZIP_MONEY;
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


    public function show_notices()
    {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $messages = get_user_meta($user_id, WC_Zipmoney_Payment_Gateway_Config::USER_META_ADMIN_NOTICE, true);

            if (!empty($messages)) {
                foreach ($messages as $message) {
                    printf('<div class="notice notice-%s">%s</div>', $message['type'], $message['message']);
                }
                //remove user meta
                update_user_meta($user_id, WC_Zipmoney_Payment_Gateway_Config::USER_META_ADMIN_NOTICE, array());
            }
        }
    }


    /**
     * Check the environment meet the minimum requirement
     */
    public function check_requirement()
    {
        if($this->WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_ENABLED) == false) {
            return;
        }

        if (version_compare(phpversion(), '5.3.0', '<')) {
            // PHP Version
            echo '<div class="error"><p>' . sprintf(__('ZipMoney Error: ZipMoney requires PHP 5.3.0 and above. You are using version %s.', 'woocommerce'), phpversion()) . '</p></div>';
        } elseif ('no' == get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS')) {
            // Show message if enabled and FORCE SSL is disabled and WordPressHTTPS plugin is not detected
            echo '<div class="error"><p>' . sprintf(__('WARN: ZipMoney is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - ZipMoney will only work in sandbox mode.', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
        }
    }

    /**
     * This is the function to process the custom defined endpoint
     *
     * @param $wp
     * @return bool
     */
    public function process_zipmoney_actions($wp)
    {
        $query_vars = $wp->query_vars;

        if (isset($query_vars['p']) == false || $query_vars['p'] != "zipmoneypayment") {
            return false;
        }

        if (isset($query_vars['route']) == false) {
            return false;
        }

        WC_Zipmoney_Payment_Gateway_Util::log('Query vars:' . print_r($query_vars, true));

        switch ($query_vars['route']) {
            case 'checkout':
                //create the checkout object
                $checkout_controller = new WC_Zip_Controller_Checkout_Controller($this);
                $response = $checkout_controller->create_checkout($_POST);
                wp_send_json($response);
                break;
            case 'charge':
                if(isset($query_vars['data']) == false){
                    $query_vars['data'] = array();
                }
                self::_handle_charge_request($query_vars['action_type'], $query_vars['data']);
                break;
            case 'error':
                WC_Zipmoney_Payment_Gateway_Util::show_error_page();
                break;
            case 'clear':
                WC_Zipmoney_Payment_Gateway_Util::log($_POST);

                if(!empty($_POST['checkout_id'])){
                    delete_option($_POST['checkout_id']);
                }
                break;
        }
        exit;
    }

    /**
     *
     *
     * @param int $order_id
     * @param null $amount
     * @param string $reason
     * @return bool
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        WC_Zipmoney_Payment_Gateway_Util::log('process refund', WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO);

        $order = new WC_Order($order_id);

        WC_Zipmoney_Payment_Gateway_Util::log(sprintf('order value: %s, amount: %s, refund: %s', $order->get_total(), $amount, $order->get_total_refunded()));

        $this->WC_Zipmoney_Payment_Gateway_Config = new WC_Zipmoney_Payment_Gateway_Config($this);
        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this,
            new \zipMoney\Api\RefundsApi()
        );

        $amount = empty($amount) ? 0 : $amount;
        $reason = empty($reason) ? 'No reason' : $reason;

        return $WC_Zipmoney_Payment_Gateway_API_Request_Charge->refund_order_charge(
            $order,
            $this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_private_key(),
            $amount,
            $reason
        );
    }

    /**
     * handle the charge request by custom url call
     *
     * @param $action_type
     * @param $data
     */
    private function _handle_charge_request($action_type, $data)
    {
        WC_Zipmoney_Payment_Gateway_Util::log('Charge process started');

        //process the charge process
        $charge_controller = new WC_Zip_Controller_Charge_Controller($this);

        //store the referrer
        $referrer = $_SERVER['HTTP_REFERER'];

        switch ($action_type){
            case 'create':
                $result = $charge_controller->create_charge($_GET);

                if ($result['result'] == true) {
                    //successfully create charge
                    wp_redirect($this->get_return_url($result['order']));
                    exit;
                }

                if (!empty($result['redirect_url'])) {
                    //if it contains redirect url
                    wp_redirect($result['redirect_url']);
                    exit;
                }

                WC_Zipmoney_Payment_Gateway_Util::show_notification_page($result['title'], $result['content']);
                exit;
                break;
            case 'capture':
                $charge_controller->capture_charge($_POST['zip_order_id']);
                wp_redirect($referrer);
                exit;
                break;
            case 'cancel':
                $charge_controller->cancel_charge($_POST['zip_order_id']);
                wp_redirect($referrer);
                exit;
                break;
        }
    }

}