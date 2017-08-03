<?php

class WC_Zipmoney_Payment_Gateway_Api_Request {

    private $WC_Zipmoney_Payment_Gateway;

    public function __construct(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway)
    {
        $this->WC_Zipmoney_Payment_Gateway = $WC_Zipmoney_Payment_Gateway;
    }


    /**
     * Request the API call to create a checkout object
     *
     * @param WC_Order $order
     * @param $redirect_url
     * @param $api_key
     * @return \zipMoney\Model\Checkout
     * @throws \zipMoney\ApiException
     */
    public function checkout(WC_Order $order, $redirect_url, $api_key)
    {
        zipMoney\Configuration::getDefaultConfiguration()->setApiKey('Authorization', $api_key);
        zipMoney\Configuration::getDefaultConfiguration()->setEnvironment('mock');

        $body = self::_prepare_request_for_checkout($order, $redirect_url);
        //log the body information
        WC_Zipmoney_Payment_Gateway_Util::log(print_r($body, true), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

        $api_instance = new \zipMoney\Client\Api\CheckoutsApi();

        try{

            $checkout = $api_instance->checkoutsCreate($body);

            //log the checkout information
            WC_Zipmoney_Payment_Gateway_Util::log(print_r($checkout, true), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

            return $checkout;

        } catch(\zipMoney\ApiException $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getMessage());
            WC_Zipmoney_Payment_Gateway_Util::log(print_r($exception->getResponseBody(), true));
        } catch(Exception $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getMessage());
        }

        throw $exception;
    }

    /**
     * Construct the checkout object
     *
     * @param WC_Order $order
     * @param $redirect_url
     * @return \zipMoney\Model\CreateCheckoutRequest
     */
    private function _prepare_request_for_checkout(WC_Order $order, $redirect_url)
    {
        //get the shopper
        $shopper = self::_get_shopper($order);

        //get the charge order
        $checkout_order = self::_get_checkout_order($order);

        //get the config
        $checkout_configuration = new \zipMoney\Model\CheckoutConfiguration(
            array(
                'redirect_uri' => $redirect_url
            )
        );

        return new \zipMoney\Model\CreateCheckoutRequest(
            array(
                'shopper' => $shopper,
                'order' => $checkout_order,
                'config' => $checkout_configuration
            )
        );

    }

    /**
     * Construct the checkout order object
     *
     * @param WC_Order $order
     * @return \zipMoney\Model\CheckoutOrder
     */
    private function _get_checkout_order(WC_Order $order)
    {
        $order_shipping = new \zipMoney\Model\OrderShipping(
            array(
                'address' => self::_get_shipping_address($order)
            )
        );

        //get the order items
        $order_items = array();
        foreach($order->get_items() as $id => $item){
            $product = new WC_Product($item['product_id']);

            $order_item_data = array(
                'name' => $item['name'],
                'amount' => floatval(get_post_meta($item['product_id'], '_price', true)),
                'reference' => $product->get_sku(),
                'description' => $product->post->post_excerpt,
                'quantity' => intval($item['qty']),
                'type' => 'sku',
                'item_uri' => $product->get_permalink(),
                'product_code' => strval($product->get_id())
            );

            $attachment_ids = $product->get_gallery_attachment_ids();
            if(!empty($attachment_ids)){
                $order_item_data['image_uri'] = wp_get_attachment_url($attachment_ids[0]);
            }

            $order_items[] = new \zipMoney\Model\OrderItem($order_item_data);
        }

        $checkout_order = new \zipMoney\Model\CheckoutOrder(
            array(
                'reference' => strval($order->id),
                'amount' => $order->get_total(),
                'currency' => $order->get_order_currency(),
                'shipping' => $order_shipping,
                'items' => $order_items
            )
        );

        return $checkout_order;
    }

    /**
     * Get the shopper object
     *
     * @param WC_Order $order
     * @return \zipMoney\Model\Shopper
     */
    private function _get_shopper(WC_Order $order)
    {
        //get the billing information into array
        $billing_array = self::_get_address_array($order, 'billing');
        $billing_address = self::_get_billing_address($order);

        //the shopper's data
        $data = array(
            'first_name' => $billing_array['first_name'],
            'last_name' => $billing_array['last_name'],
            'phone' => $billing_array['phone'],
            'email' => $billing_array['email'],
            'billing_address' => $billing_address
        );

        //get teh shopper statics if it's available
        $shopper_statistics = self::_get_shopper_statistics();
        if(!empty($shopper_statistics)) {
            $data['statistics'] = $shopper_statistics;
        }

        return new \zipMoney\Model\Shopper($data);
    }

    /**
     * Get the login user statistics
     *
     * @return \zipMoney\Model\ShopperStatistics
     */
    private function _get_shopper_statistics()
    {
        if(is_user_logged_in() == false){
            //we won't return anything if the user is not login
            return null;
        }

        $current_user = wp_get_current_user();
        $customer_orders = get_posts(array(
            'numberposts' => -1,
            'meta_key' => '_customer_user',
            'meta_value' => get_current_user_id(),
            'post_type' => wc_get_order_types(),
            'post_status' => array('wc-completed', 'wc-refunded'),
        ));

        $account_created = DateTime::createFromFormat('Y-m-d H:i:s', $current_user->get('user_registered'));
        $sales_total_count = 0;
        $sales_total_amount = 0;
        $sales_avg_amount = 0;
        $sales_max_amount = 0;
        $refunds_total_amount = 0;
        $currency = get_woocommerce_currency();
        $last_login = DateTime::createFromFormat('Y-m-d H:i:s', $current_user->get('user_login'));

        if(!empty($customer_orders)){
            foreach ($customer_orders as $post) {
                $order = new WC_Order($post->ID);

                if($order->get_status() == 'completed'){
                    $sales_total_count++;
                    $sales_total_amount += $order->get_total();

                    if($sales_max_amount < $order->get_total()){
                        $sales_max_amount = $order->get_total();
                    }
                } else if($order->get_status() == 'refunded'){
                    $refunds_total_amount += $order->get_total();
                }
            }
        }

        if($sales_total_count > 0){
            $sales_avg_amount = (float)round($sales_total_count / $sales_total_count, 2);
        }

        $data = array(
            'sales_total_count' => $sales_total_count,
            'sales_total_amount' => $sales_total_amount,
            'sales_avg_amount' => $sales_avg_amount,
            'sales_max_amount' => $sales_max_amount,
            'refunds_total_amount' => $refunds_total_amount,
            'currency' => $currency
        );

        if(!empty($account_created)){
            $data['account_created'] = $account_created;
        }

        if(!empty($last_login)){
            $data['last_login'] = $last_login;
        }

        return new \zipMoney\Model\ShopperStatistics($data);
    }

    /**
     * Get the billing info array by different build-in methods
     *
     * @param WC_Order $order
     * @param string $address_type => 'billing' or 'shipping'
     * @return array|bool   =>  array(
     *      'first_name' => billing_first_name,
            'last_name'  => billing_last_name,
            'address_1'  => billing_address_1,
            'address_2'  => billing_address_2,
            'city'       => billing_city,
            'state'      => billing_state,
            'postcode'   => billing_postcode,
            'country'    => billing_country,
            'email'      => billing_email,
            'phone'      => billing_phone,
     * )
     */
    private function _get_address_array(WC_Order $order, $address_type)
    {
        //Try to get the billing address with different methods
        if (method_exists($order, 'get_address')) {
            return $order->get_address($address_type);
        } else if (method_exists($order, 'get_' . $address_type . '_address')) {
            $get_method = 'get_' . $address_type . '_address';
            return explode(', ', $order->$get_method());
        } else if (method_exists($order, 'get_formatted_' . $address_type . '_address')) {
            $get_method = 'get_formatted_' . $address_type . '_address';
            return explode(', ', $order->$get_method());
        } else {
            return false;
        }
    }


    /**
     * Get the Billing address object
     *
     * @param WC_Order $order
     * @return bool|\zipMoney\Model\Address
     */
    private function _get_billing_address(WC_Order $order)
    {
        $billing_array = self::_get_address_array($order, 'billing');

        return new \zipMoney\Model\Address(
            array(
                'line1' => $billing_array['address_1'],
                'line2' => $billing_array['address_2'],
                'city' => $billing_array['city'],
                'state' => $billing_array['state'],
                'postal_code' => $billing_array['postcode'],
                'country' => $billing_array['country'],
                'first_name' => $billing_array['first_name'],
                'last_name' => $billing_array['last_name']
            )
        );
    }


    /**
     * Get the shipping address object
     *
     * @param WC_Order $order
     * @return \zipMoney\Model\Address
     */
    private function _get_shipping_address(WC_Order $order)
    {
        $shipping_array = self::_get_address_array($order, 'shipping');

        return new \zipMoney\Model\Address(
            array(
                'line1' => $shipping_array['address_1'],
                'line2' => $shipping_array['address_2'],
                'city' => $shipping_array['city'],
                'state' => $shipping_array['state'],
                'postal_code' => $shipping_array['postcode'],
                'country' => $shipping_array['country'],
                'first_name' => $shipping_array['first_name'],
                'last_name' => $shipping_array['last_name']
            )
        );
    }
}