<?php
class WC_Zipmoney_Payment_Gateway_API_Request_Charge extends WC_Zipmoney_Payment_Gateway_API_Abstract
{

    /**
     * Create a charge to an order
     *
     * @param WC_Order $order
     * @param $api_key
     * @return null|\zipMoney\Model\Charge
     */
    public function charge(WC_Order $order, $api_key)
    {
        zipMoney\Configuration::getDefaultConfiguration()->setApiKey('Authorization', $api_key);
        zipMoney\Configuration::getDefaultConfiguration()->setEnvironment('mock');

        $api_instance = new \zipMoney\Client\Api\ChargesApi();
        $body = self::_prepare_charges_request($order);

        //log the post body
        WC_Zipmoney_Payment_Gateway_Util::log($body, WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

        try {
            $charge = $api_instance->chargesCreate($body);

            //log the checkout information
            WC_Zipmoney_Payment_Gateway_Util::log($charge, WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

            update_post_meta($order->id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, $charge->getId());

            //change the order type back to order in order to show the order
            set_post_type($order->id, WC_Zipmoney_Payment_Gateway_Config::POST_TYPE_ORDER);

            //Add order note
            $order->payment_complete();

            return $charge;

        } catch (\zipMoney\ApiException $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getResponseBody());

            wc_add_notice(__('Payment error:', 'woothemes') . $exception->getMessage(), 'error');
        } catch(Exception $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            wc_add_notice(__('Payment error:', 'woothemes') . $exception->getMessage(), 'error');
        }

        return null;
    }

    /**
     * Create the ChargeRequest
     *
     * @param WC_Order $order
     * @return \zipMoney\Model\CreateChargeRequest
     */
    private function _prepare_charges_request(WC_Order $order)
    {
        //get the charge order
        $charge_order = self::_get_charge_order($order);

        //get authority
        $authority = new \zipMoney\Model\Authority(
            array(
                'type' => 'checkout_id',
                'value' => get_post_meta($order->id, WC_Zipmoney_Payment_Gateway_Config::META_CHECKOUT_ID, true)
            )
        );

        return new \zipMoney\Model\CreateChargeRequest(
            array(
                'authority' => $authority,
                'amount' => $order->get_total(),
                'currency' => $order->get_order_currency(),
                'order' => $charge_order
            )
        );
    }

    /**
     * Construct the ChargeOrder object
     *
     * @param WC_Order $order
     * @return \zipMoney\Model\ChargeOrder
     */
    private function _get_charge_order(WC_Order $order)
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

        return new \zipMoney\Model\ChargeOrder(
            array(
                'reference' => strval($order->id),
                'shipping' => $order_shipping,
                'items' => $order_items
            )
        );


    }
}