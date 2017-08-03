<?php

class WC_Zipmoney_Payment_Gateway_API_Request_Checkout extends WC_Zipmoney_Payment_Gateway_API_Abstract
{
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
        WC_Zipmoney_Payment_Gateway_Util::log($body, WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

        $api_instance = new \zipMoney\Client\Api\CheckoutsApi();

        try {
            $checkout = $api_instance->checkoutsCreate($body);

            //log the checkout information
            WC_Zipmoney_Payment_Gateway_Util::log($checkout, WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

            //Add order note
            $order->add_order_note(sprintf("Resume Application Url:-%s", $checkout->getUri()));

            //Add meta data to order
            update_post_meta($order->id, WC_Zipmoney_Payment_Gateway_Config::META_CHECKOUT_ID, $checkout->getId());

            //change the order type to quote in order to hide the order until the charge is created
            set_post_type($order->id, WC_Zipmoney_Payment_Gateway_Config::POST_TYPE_QUOTE);

            return $checkout;

        } catch (\zipMoney\ApiException $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getResponseBody());

            wc_add_notice(__('Payment error:', 'woothemes') . $exception->getMessage(), 'error');
        } catch (Exception $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            wc_add_notice(__('Payment error:', 'woothemes') . $exception->getMessage(), 'error');
        }

        return null;
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

        //get the order items exclude tax. We get the subtotal value only
        $order_items = array();
        foreach ($order->get_items() as $id => $item) {
            $product = new WC_Product($item['product_id']);

            $order_item_data = array(
                'name' => $item['name'],
                'amount' => floatval($order->get_item_subtotal($item)),
                'reference' => $product->get_sku(),
                'description' => $product->post->post_excerpt,
                'quantity' => intval($item['qty']),
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

        //get the total tax
        $tax_amount = $order->get_total_tax();
        if ($tax_amount > 0) {
            $order_items[] = new \zipMoney\Model\OrderItem(
                array(
                    'name' => 'Total tax',
                    'amount' => floatval($tax_amount),
                    'quantity' => 1,
                    'type' => 'tax'
                )
            );
        }

        //get the shipping cost
        $shipping_amount = $order->get_total_shipping();
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
        $discount_amount = $order->get_total_discount();
        if ($discount_amount > 0) {
            $order_items[] = new \zipMoney\Model\OrderItem(
                array(
                    'name' => 'Discount',
                    'amount' => floatval($discount_amount),
                    'quantity' => 1,
                    'type' => 'discount'
                )
            );
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

}