<?php

class WC_Zipmoney_Payment_Gateway_API_Abstract {
    protected $WC_Zipmoney_Payment_Gateway;

    public function __construct(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway)
    {
        $this->WC_Zipmoney_Payment_Gateway = $WC_Zipmoney_Payment_Gateway;

        //set the environment
        $is_sandbox = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_SANDBOX);
        if($is_sandbox == true){
            zipMoney\Configuration::getDefaultConfiguration()->setEnvironment('sandbox');
        } else {
            zipMoney\Configuration::getDefaultConfiguration()->setEnvironment('production');
        }
    }

    /**
     * Set the api key
     *
     * @param $api_key
     */
    protected function set_api_key($api_key)
    {
        zipMoney\Configuration::getDefaultConfiguration()->setApiKey('Authorization', 'Bearer ' . $api_key);
    }

    /**
     * Get the login user statistics
     *
     * @return \zipMoney\Model\ShopperStatistics
     */
    protected function _get_shopper_statistics()
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
     * Create the order items
     *
     * @param WC_Session $WC_Session
     * @return array
     */
    protected function _get_order_items(WC_Session $WC_Session)
    {
        $order_items = array();

        foreach ($WC_Session->get('cart', array()) as $id => $item) {
            $product = new WC_Product($item['product_id']);

            $item_quantity = intval($item['quantity']);

            $order_item_data = array(
                'name' => $product->get_title(),
                'amount' => (floatval($item['line_subtotal']) + floatval($item['line_subtotal_tax'])) / $item_quantity,
                'reference' => $product->get_sku(),
                'description' => $product->post->post_excerpt,
                'quantity' => $item_quantity,
                'type' => 'sku',
                'item_uri' => $product->get_permalink(),
                'product_code' => strval($product->get_id())
            );

            $attachment_ids = $product->get_gallery_attachment_ids();
            if (!empty($attachment_ids)) {
                $order_item_data['image_uri'] = wp_get_attachment_url($attachment_ids[0]);
            }

            $order_items[] = new \zipMoney\Model\OrderItem($order_item_data);
        }

        //get the shipping cost
        $shipping_amount = $WC_Session->get('shipping_total', 0) + $WC_Session->get('shipping_tax_total', 0);
        if ($shipping_amount > 0) {
            $order_items[] = new \zipMoney\Model\OrderItem(
                array(
                    'name' => 'Shipping cost',
                    'amount' => floatval($shipping_amount),
                    'quantity' => 1,
                    'type' => 'shipping'
                )
            );
        }

        //get the discount
        $discount_amount = $WC_Session->get('discount_cart', 0) + $WC_Session->get('discount_cart_tax', 0);
        if ($discount_amount > 0) {
            $order_items[] = new \zipMoney\Model\OrderItem(
                array(
                    'name' => 'Discount',
                    'amount' => floatval($discount_amount) * -1,
                    'quantity' => 1,
                    'type' => 'discount'
                )
            );
        }

        return $order_items;
    }


    /**
     * Create the billing address
     *
     * @param array $billing_array => array(
     *      'zip_billing_address_1' => '',
     *      'zip_billing_address_2' =>,
     *      'zip_billing_city' =>,
     *      'zip_billing_state' =>,
     *      'zip_billing_postcode' =>,
     *      'zip_billing_country' =>,
     *      'zip_billing_first_name' =>,
     *      'zip_billing_last_name' =>
     * )
     * @return \zipMoney\Model\Address
     */
    protected function _create_billing_address(array $billing_array)
    {
        return new \zipMoney\Model\Address(
            array(
                'line1' => $billing_array['zip_billing_address_1'],
                'line2' => $billing_array['zip_billing_address_2'],
                'city' => $billing_array['zip_billing_city'],
                'state' => $billing_array['zip_billing_state'],
                'postal_code' => $billing_array['zip_billing_postcode'],
                'country' => $billing_array['zip_billing_country'],
                'first_name' => $billing_array['zip_billing_first_name'],
                'last_name' => $billing_array['zip_billing_last_name']
            )
        );
    }

    /**
     * Create the shipping address
     *
     * @param array $shipping_array
     * @return \zipMoney\Model\Address
     */
    protected function _create_shipping_address(array $shipping_array)
    {
        return new \zipMoney\Model\Address(
            array(
                'line1' => $shipping_array['zip_shipping_address_1'],
                'line2' => $shipping_array['zip_shipping_address_2'],
                'city' => $shipping_array['zip_shipping_city'],
                'state' => $shipping_array['zip_shipping_state'],
                'postal_code' => $shipping_array['zip_shipping_postcode'],
                'country' => $shipping_array['zip_shipping_country'],
                'first_name' => $shipping_array['zip_shipping_first_name'],
                'last_name' => $shipping_array['zip_shipping_last_name']
            )
        );
    }


}