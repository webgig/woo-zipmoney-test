<?php
use zipMoney\ApiException;


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
        if (self::$config_log_level > $log_level) {
            //log the message with log_level higher than the default value only
            return;
        }

        if (is_array($message) || is_object($message)) {
            //if the input is array or object, use print_r to convert it to string
            $message = print_r($message, true);
        }

        if (is_null(self::$logger)) {
            //check the logger is initialised
            self::$logger = new WC_Logger();
        }

        //log the message into file
        self::$logger->add('zipmoney', $message);
    }


    /**
     * Use zipmoney SDK to json_encode an object
     *
     * @param $data
     * @return string
     */
    public static function object_json_encode($data)
    {
        return json_encode(\zipMoney\ObjectSerializer::sanitizeForSerialization($data));
    }

    /**
     * This rewrite rule will be called during WordPress init action
     */
    public static function add_rewrite_rules()
    {
        // Define the tag for the individual ID
        add_rewrite_tag('%route%', '([a-zA-Z]*)');
        add_rewrite_tag('%action_type%', '([a-zA-Z]*)');
        add_rewrite_tag('%data%', '([a-zA-Z0-9]*)');
        add_rewrite_rule('^zipmoneypayment/([a-zA-Z]*)/([a-zA-Z]*)/([a-zA-Z0-9]*)/?', 'index.php?p=zipmoneypayment&route=$matches[1]&action_type=$matches[2]&data=$matches[3]', 'top');
        add_rewrite_rule('^zipmoneypayment/([a-zA-Z]*)/?([a-zA-Z]*)/?', 'index.php?p=zipmoneypayment&route=$matches[1]&action_type=$matches[2]', 'top');

        flush_rewrite_rules();
    }


    /**
     * Generate the uuid
     *
     * @return string
     */
    public static function get_uuid()
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
     * Add admin notice to user meta
     *
     * @param $message
     * @param string $type
     */
    public static function add_admin_notice($message, $type = 'error')
    {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $messages = get_user_meta($user_id, WC_Zipmoney_Payment_Gateway_Config::USER_META_ADMIN_NOTICE, true);

            $messages[] = array('message' => $message, 'type' => $type);
            update_user_meta($user_id, WC_Zipmoney_Payment_Gateway_Config::USER_META_ADMIN_NOTICE, $messages);
        }
    }


    /**
     * Add the zipmoney order status
     *
     * @param $order_statuses
     * @return mixed
     */
    public static function add_zipmoney_to_order_statuses($order_statuses)
    {
        $order_statuses[WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY] =
            WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_NAME;

        return $order_statuses;
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

    public static function register_zip_order_statuses()
    {
        register_post_status(WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY, array(
            'label' => WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_NAME,
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_NAME . ' <span class="count">(%s)</span>',
                WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_NAME . ' <span class="count">(%s)</span>'
            )
        ));
    }


    /**
     * Get the order redirect url. Which is used in creating checkout to the API
     *
     * @return string
     */
    public static function get_checkout_endpoint_url()
    {
        return get_site_url() . '/zipmoneypayment/checkout/submit';
    }


    /**
     * Return the redirect url which is called after the checkout is created from the API.
     *
     * @return string
     */
    public static function get_complete_endpoint_url()
    {
        return get_site_url() . '/zipmoneypayment/charge/create';
    }

    /**
     * Return the capture charge url. It's used in capture button in admin order page.
     *
     * @return string
     */
    public static function get_capture_charge_url()
    {
        return get_site_url() . '/zipmoneypayment/charge/capture';
    }

    public static function get_cancel_charge_url()
    {
        return get_site_url() . '/zipmoneypayment/charge/cancel';
    }

    /**
     * Show the error page
     */
    public static function show_error_page()
    {
        include plugin_dir_path(dirname(__FILE__)) . 'includes/view/frontend/error_page.php';
    }

    public static function handle_capture_charge_api_exception(ApiException $exception, WC_Order $order)
    {
        $error_codes_map = array(
            'amount_invalid' => 'Capture amount does not match authorised amount',
            'invalid_state' => 'The charge is not in authorised state'
        );

        self::log($exception->getCode() . $exception->getMessage());
        self::log($exception->getResponseBody());

        $error_code = $exception->getResponseObject()->getError()->getCode();

        if(!empty($error_codes_map[$error_code])){
            $order->add_order_note($error_codes_map[$error_code]);
            self::add_admin_notice($error_codes_map[$error_code]);
        } else {
            self::add_admin_notice($exception->getMessage());
            self::add_admin_notice(print_r($exception->getResponseBody(), true));
        }
    }

    /**
     * Handle the charge create api exception
     *
     * @param ApiException $exception
     * @return array
     */
    public static function handle_create_charge_api_exception(ApiException $exception)
    {
        $error_codes_map = array(
            "account_insufficient_funds" => "WC-0001",
            "account_inoperative" => "WC-0002",
            "account_locked" => "WC-0003",
            "amount_invalid" => "WC-0004",
            "fraud_check" => "WC-0005"
        );

        self::log($exception->getCode() . $exception->getMessage());
        self::log($exception->getResponseBody());

        $response = array(
            'success' => false,
            'code' => $exception->getCode()
        );

        $error_code = 0;

        $response_object = $exception->getResponseObject();
        if(!empty($response_object)){
            $error_code = $response_object->getError()->getCode();
        }

        if($exception->getCode() == 402 && !empty($error_codes_map[$error_code])){
            $response['message'] = sprintf('The payment was declined by Zip.(%s)', $error_codes_map[$error_code]);
        } else {
            $response['message'] = $exception->getMessage();
        }

        return $response;
    }


    /**
     * Show the notification page
     *
     * @param $title
     * @param $content
     */
    public static function show_notification_page($title, $content)
    {
        include plugin_dir_path(dirname(__FILE__)) . 'includes/view/frontend/notification_page.php';
    }

    /**
     * Update the customer details in cart session
     *
     * @param $post_data
     */
    public static function update_customer_details($post_data)
    {
        $customer_details = array();

        $post_data = explode("&", $post_data);

        if ($post_data) {
            foreach ($post_data as $key => $value) {
                list($k, $v) = explode("=", $value);
                $customer_details[$k] = $v;
            }
        }

        $ship_to_different_address = empty($customer_details['ship_to_different_address']) ? false : true;

        //The address keys used for iterate the shipping and billing address
        $address_keys = array(
            'first_name',
            'last_name',
            'company',
            'email',
            'phone',
            'country',
            'address_1',
            'address_2',
            'city',
            'state',
            'postcode'
        );
        $need_decode_address_keys = array('email', 'address_1', 'address_2');
        $zip_billing_details = array();
        $zip_shipping_details = array();


        //set the billing address
        foreach ($address_keys as $address_key) {
            $billing_key = 'billing_' . $address_key;
            if (isset($customer_details[$billing_key])) {
                if (in_array($address_key, $need_decode_address_keys)) {
                    $zip_billing_details['zip_' . $billing_key] = urldecode($customer_details[$billing_key]);
                    continue;
                }
                $zip_billing_details['zip_' . $billing_key] = $customer_details[$billing_key];
            } else {
                $customer_details[$billing_key] = '';
            }
        }

        WC()->session->set('zip_billing_details', $zip_billing_details);

        if (wc_ship_to_billing_address_only() || $ship_to_different_address == false) {
            //if the woocommerce setting is set to ship to billing address only or the customer doesn't select ship to different address
            foreach ($address_keys as $address_key) {
                $shipping_key = 'shipping_' . $address_key;
                $billing_key = 'billing_' . $address_key;
                if (isset($customer_details[$billing_key])) {
                    if (in_array($address_key, $need_decode_address_keys)) {
                        $zip_shipping_details['zip_' . $shipping_key] = urldecode($customer_details[$billing_key]);
                        continue;
                    }
                    $zip_shipping_details['zip_' . $shipping_key] = $customer_details[$billing_key];
                } else {
                    $customer_details[$billing_key] = '';
                }
            }
        } else {
            //if the customer wants to ship to different address
            foreach ($address_keys as $address_key) {
                $shipping_key = 'shipping_' . $address_key;
                if (isset($customer_details[$shipping_key])) {
                    if (in_array($address_key, $need_decode_address_keys)) {
                        $zip_shipping_details['zip_' . $shipping_key] = urldecode($customer_details[$shipping_key]);
                        continue;
                    }
                    $zip_shipping_details['zip_' . $shipping_key] = $customer_details[$shipping_key];
                } else {
                    $customer_details[$shipping_key] = '';
                }
            }
        }

        WC()->session->set('zip_shipping_details', $zip_shipping_details);
    }
}