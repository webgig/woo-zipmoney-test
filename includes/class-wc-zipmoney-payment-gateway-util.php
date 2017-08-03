<?php
class WC_Zipmoney_Payment_Gateway_Util
{
    private static $logger = null;
    public static $config_log_level = WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_ALL;

    /**
     * Log the message when necessary
     *
     * @param $message
     * @param int $log_level
     */
    public static function log($message, $log_level = WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_ALL)
    {
        if(self::$config_log_level > $log_level){
            //log the message with log_level higher than the default value only
            return;
        }

        if (is_array($message) || is_object($message)){
            //if the input is array or object, use print_r to convert it to string
            $message = print_r($message, true);
        }

        if(is_null(self::$logger)) {
            //check the logger is initialised
            self::$logger = new WC_Logger();
        }

        //log the message into file
        self::$logger->add('zipmoney', $message);
    }

    /**
     * This rewrite rule will be called during WordPress init action
     */
    public static function add_rewrite_rules()
    {
        // Define the tag for the individual ID
        add_rewrite_tag('%route%', '([a-zA-Z]*)');
        add_rewrite_tag('%action_type%', '([a-zA-Z]*)');
        add_rewrite_tag('%order_number%', '([a-zA-Z0-9]*)');
        add_rewrite_rule('^zipmoneypayment/([a-zA-Z]*)/?([a-zA-Z]*)/([a-zA-Z0-9]*)', 'index.php?p=zipmoneypayment&route=$matches[1]&action_type=$matches[2]&order_number=$matches[3]', 'top');

        flush_rewrite_rules();
    }

    /**
     *  Registers custom post type required for Express Checkout Flow
     */
    public static function register_quote_post_type()
    {
        // Register shop_quote post types
        register_post_type(
            WC_Zipmoney_Payment_Gateway_Config::POST_TYPE_QUOTE,
            array(
                'labels' => array(
                    'name' => __('Quote'),
                    'singular_name' => __('Quote')
                ),
                'public' => false,
                'has_archive' => true
            )
        );
    }

    /**
     * @param WC_Order $order
     * @return string
     */
    public static function get_order_redirect_url(WC_Order $order)
    {
        return get_site_url() . '/zipmoneypayment/order/confirm/' . $order->id;
    }

    /**
     * Show the error page
     */
    public static function show_error_page()
    {
        include plugin_dir_path(dirname(__FILE__)) . 'includes/view/frontend/error.php';
    }
}