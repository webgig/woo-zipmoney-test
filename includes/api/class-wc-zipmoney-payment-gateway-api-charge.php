<?php

use \zipMoney\Model\Authority;
use \zipMoney\ApiException;
use \zipMoney\Model\CreateChargeRequest;
use \zipMoney\Model\OrderShipping;
use \zipMoney\Model\ChargeOrder;
use \zipMoney\Model\CreateRefundRequest;
use \zipMoney\Model\Refund;
use \zipMoney\Model\CaptureChargeRequest;
use \zipMoney\ObjectSerializer;

class WC_Zipmoney_Payment_Gateway_API_Request_Charge extends WC_Zipmoney_Payment_Gateway_API_Abstract
{
    private $api_instance;

    public function __construct(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway, $api_instance)
    {
        parent::__construct($WC_Zipmoney_Payment_Gateway);

        $this->api_instance = $api_instance;
    }


    /**
     * Create refund by order charge
     *
     * @param WC_Order $order
     * @param $api_key
     * @param int $amount
     * @param string $reason
     * @return bool
     */
    public function refund_order_charge(WC_Order $order, $api_key, $amount = 0, $reason = '')
    {
        parent::set_api_key($api_key);

        try {
            $order_id = WC_Zipmoney_Payment_Gateway_Util::get_order_id($order);

            $charge_id = get_post_meta($order_id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, true);

            if (empty($charge_id)) {
                //if the charge id is empty, then we won't process the charge anymore
                throw new Exception('Empty charge id');
            }
            if($amount <= 0){
                throw new Exception('The amount should greater than 0');
            }

            $body = new CreateRefundRequest(
                array(
                    'charge_id' => $charge_id,
                    'reason' => $reason,
                    'amount' => $amount
                )
            );

            WC_Zipmoney_Payment_Gateway_Util::log('Refund request: ' . WC_Zipmoney_Payment_Gateway_Util::object_json_encode($body));

            //Call the API
            $refund = $this->api_instance->refundsCreate($body, WC_Zipmoney_Payment_Gateway_Util::get_uuid());

            WC_Zipmoney_Payment_Gateway_Util::log('Refund response: ' . WC_Zipmoney_Payment_Gateway_Util::object_json_encode($refund));

            //update the order info
            self::_update_order_refund($order, $refund);

            return true;
        } catch (ApiException $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getResponseBody());

            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice($exception->getMessage());
            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice(print_r($exception->getResponseBody(), true));
        } catch (Exception $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());

            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice($exception->getMessage());
        }

        return false;
    }


    /**
     * Update the order status
     *
     * @param WC_Order $order
     * @param Refund $refund
     */
    private function _update_order_refund(WC_Order $order, Refund $refund)
    {
        //write the order note
        $order->add_order_note(sprintf('The ZipMoney refund has been successfully performed. [Charge id:%s, Refund id:%s, Amount: %s]', $refund->getChargeId(), $refund->getId(), $refund->getAmount()));

        if (wc_format_decimal($order->get_total()) == wc_format_decimal($order->get_total_refunded())) {
            //if the order is fully refunded
            $order->update_status('wc-refunded');
        }
        // Clear transients
        $order_id = WC_Zipmoney_Payment_Gateway_Util::get_order_id($order);
        //wc_delete_shop_order_transients($order_id);

        //log the message
        WC_Zipmoney_Payment_Gateway_Util::log(sprintf('ZipMoney refund success! [Order id: %s, Refund id:%s]', $order_id, $refund->getId()));
    }


    /**
     * Cancel an authorized charge
     *
     * @param WC_Order $order
     * @param $api_key
     * @return bool
     */
    public function cancel_order_charge(WC_Order $order, $api_key)
    {
        parent::set_api_key($api_key);

        try {
            $order_id = WC_Zipmoney_Payment_Gateway_Util::get_order_id($order);

            $charge_id = get_post_meta($order_id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, true);

            if (empty($charge_id)) {
                //if the charge id is empty, then we won't process the charge anymore
                throw new Exception('Empty charge id');
            }

            if ($order->get_status() != WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY_COMPARE) {
                //if the order status is not authorized, then we won't charge it again
                throw new Exception('The order status is not in Authorized status');
            }

            WC_Zipmoney_Payment_Gateway_Util::log('Cancel charge request: charge_id:' . $charge_id);

            $charge = $this->api_instance->chargesCancel($charge_id, WC_Zipmoney_Payment_Gateway_Util::get_uuid());

            WC_Zipmoney_Payment_Gateway_Util::log('Cancel charge response: ' . WC_Zipmoney_Payment_Gateway_Util::object_json_encode($charge));

            if($charge->getState() == 'cancelled'){
                WC_Zipmoney_Payment_Gateway_Util::log('Charge has been cancelled. charge_id: ' . $charge->getId());

                $order->update_status( 'wc-cancelled', sprintf('The zipMoney charge (id:%s) has been cancelled.', $charge->getId()) );
                return true;
            } else {
                WC_Zipmoney_Payment_Gateway_Util::log('Unable to cancel charge. charge_id: ' . $charge->getId());
                return false;
            }

        } catch (ApiException $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getResponseBody());

            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice($exception->getMessage());
            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice(print_r($exception->getResponseBody(), true));
        } catch (Exception $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());

            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice($exception->getMessage());
        }

        return false;
    }

    /**
     * Capture order charge
     *
     * @param WC_Order $order
     * @param $api_key
     * @return bool
     */
    public function capture_order_charge(WC_Order $order, $api_key)
    {
        parent::set_api_key($api_key);

        try {

            $order_id = WC_Zipmoney_Payment_Gateway_Util::get_order_id($order);
            $charge_id = get_post_meta($order_id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, true);

            if (empty($charge_id)) {
                //if the charge id is empty, then we won't process the charge anymore
                throw new Exception('Empty charge id');
            }

            if ($order->get_status() != WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY_COMPARE) {
                //if the order status is not authorized, then we won't charge it again
                throw new Exception('The order status is not in Authorized status');
            }

            $body = new CaptureChargeRequest(
                array('amount' => $order->get_total())
            );

            WC_Zipmoney_Payment_Gateway_Util::log('Capture charge capture_id:' . $charge_id);
            WC_Zipmoney_Payment_Gateway_Util::log('Capture charge request: ' . WC_Zipmoney_Payment_Gateway_Util::object_json_encode($body));

            $charge = $this->api_instance->chargesCapture($charge_id, $body, WC_Zipmoney_Payment_Gateway_Util::get_uuid());

            WC_Zipmoney_Payment_Gateway_Util::log('Capture charge response: ' . WC_Zipmoney_Payment_Gateway_Util::object_json_encode($charge));

            if ($charge->getState() == 'captured') {

                WC_Zipmoney_Payment_Gateway_Util::log('Has captured. charge_id: ' . $charge->getId());

                $order->payment_complete($charge->getId());
                return true;
            } else {

                WC_Zipmoney_Payment_Gateway_Util::log('Charge failed. charge_id: ' . $charge->getId());
                return false;
            }

        } catch (ApiException $exception) {
            WC_Zipmoney_Payment_Gateway_Util::handle_capture_charge_api_exception($exception, $order);
        } catch (Exception $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());

            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice($exception->getMessage());
        }

        return false;
    }


    /**
     * @param WC_Session $WC_Session
     * @param $api_key
     * @param WC_Order|null $order
     * @return array    =>  array(
     *                          'success' => bool,
     *                          'order' => order object,
     *                          'message' => ''
     *                      )
     */
    public function create_charge(WC_Session $WC_Session, $api_key, WC_Order $order = null)
    {
        $response = array(
            'success' => false,
            'message' => ''
        );

        parent::set_api_key($api_key);

        try {
            if (empty($order)) {
                $order = self::_create_order_by_charge($WC_Session);
            }

            $checkout_id = $WC_Session->get(WC_Zipmoney_Payment_Gateway_Config::META_CHECKOUT_ID);

            $body = self::_prepare_charges_request($WC_Session);

            $order_id = WC_Zipmoney_Payment_Gateway_Util::get_order_id($order);

            //log the post body
            WC_Zipmoney_Payment_Gateway_Util::log(
                sprintf('Request charge order (%s):', $order_id) . WC_Zipmoney_Payment_Gateway_Util::object_json_encode($body),
                WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG
            );

            //write the charge object info to order meta
            update_post_meta($order_id, WC_Zipmoney_Payment_Gateway_Config::META_CHECKOUT_ID, $checkout_id);

            $user_id = $WC_Session->get(WC_Zipmoney_Payment_Gateway_Config::META_USER_ID, '');
            if (!empty($user_id)) {
                update_post_meta($order_id, '_customer_user', $user_id);
            }

            $charge = $this->api_instance->chargesCreate($body, WC_Zipmoney_Payment_Gateway_Util::get_uuid());

            //delete the option
            delete_option($checkout_id);

            //log the charge information
            WC_Zipmoney_Payment_Gateway_Util::log(
                sprintf('Order (%s) Charge response:', $order_id) . WC_Zipmoney_Payment_Gateway_Util::object_json_encode($charge),
                WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG
            );

            //if it is not successful, throw exception
            $charge_state = $charge->getState();
            if (empty($charge_state)) {
                throw new Exception('Unable to create charges');
            }

            //set the charge id to order
            update_post_meta($order_id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, $charge->getId());

            if ($charge->getState() == 'captured') {
                //if the payment is captured, we will complete the order
                $order->payment_complete($charge->getId());

                $response['success'] = true;
            } else if ($charge->getState() == 'authorised') {
                //if it is authorised, then we will charge the order later
                $order->add_order_note('A zipMoney charge authorization is completed. Waiting for shop administrator to complete the charge. Charge id: ' . $charge->getId());
                $order->update_status(WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY);

                $response['success'] = true;
            } else {
                //otherwise, we will cancelled the order
                $order->update_status('cancelled', 'order_note');
                $response['message'] = 'Unable to create charge. The charge state is: ' . $charge->getState();
            }
        } catch (ApiException $exception){
            $response = WC_Zipmoney_Payment_Gateway_Util::handle_create_charge_api_exception($exception);

            if(!empty($order)){
                $order->add_order_note($response['message']);
                $order->update_status('cancelled', 'order_note');
            }
        } catch (Exception $exception) {
            if(!empty($order)){
                $order->add_order_note($exception->getCode() . $exception->getMessage());
                $order->update_status('cancelled', 'order_note');
            }

            $response['message'] = $exception->getMessage();

            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            wc_add_notice(__('Payment error:', 'woothemes') . $exception->getMessage(), 'error');
        }

        $response['order'] = $order;

        return $response;
    }


    /**
     * Create an order once the charge is completed
     *
     * @param WC_Session $WC_Session
     * @return WC_Order|WP_Error
     * @throws Exception
     */
    private function _create_order_by_charge(WC_Session $WC_Session)
    {
        $order = wc_create_order();
        foreach ($WC_Session->get('cart') as $order_item) {
            $product = new WC_Product(intval($order_item['product_id']));
            if (empty($product)) {
                throw new Exception('Unable to find product with product_id: ' . $order_item['product_id']);
            }
            if ($product->managing_stock() == true && $product->get_stock_quantity() < $order_item['quantity']) {
                //if the product is managing stock and the available quantity is less than quantity, we will throw exception
                throw new Exception(
                    sprintf(
                        'Product %s has insufficient stock. Available: %s, request: %s',
                        $product->get_title(),
                        $product->get_stock_quantity(),
                        $order_item['quantity']
                    )
                );
            }
            $order->add_product(
                $product,
                $order_item['quantity'],
                array(
                    'variation' => $order_item['variation'],
                    'totals'    => array(
                        'subtotal'     => $order_item['line_subtotal'],
                        'subtotal_tax' => $order_item['line_subtotal_tax'],
                        'total'        => $order_item['line_total'],
                        'tax'          => $order_item['line_tax'],
                        'tax_data'     => $order_item['line_tax_data']
                    )
                )
            );
        }

        //set the shipping address
        $zip_shipping_details = $WC_Session->get('zip_shipping_details');
        $order->set_address(
            array(
                'first_name' => $zip_shipping_details['zip_shipping_first_name'],
                'last_name' => $zip_shipping_details['zip_shipping_last_name'],
                'company' => $zip_shipping_details['zip_shipping_company'],
                'email' => empty($zip_shipping_details['zip_shipping_email']) ? '' : $zip_shipping_details['zip_shipping_email'],
                'phone' => empty($zip_shipping_details['zip_shipping_phone']) ? '' : $zip_shipping_details['zip_shipping_phone'],
                'address_1' => $zip_shipping_details['zip_shipping_address_1'],
                'address_2' => $zip_shipping_details['zip_shipping_address_2'],
                'city' => $zip_shipping_details['zip_shipping_city'],
                'state' => $zip_shipping_details['zip_shipping_state'],
                'postcode' => $zip_shipping_details['zip_shipping_postcode'],
                'country' => $zip_shipping_details['zip_shipping_country']
            ),
            'shipping'
        );

        //set the billing address
        $zip_billing_details = $WC_Session->get('zip_billing_details');
        $order->set_address(
            array(
                'first_name' => $zip_billing_details['zip_billing_first_name'],
                'last_name' => $zip_billing_details['zip_billing_last_name'],
                'company' => $zip_billing_details['zip_billing_company'],
                'email' => $zip_billing_details['zip_billing_email'],
                'phone' => $zip_billing_details['zip_billing_phone'],
                'address_1' => $zip_billing_details['zip_billing_address_1'],
                'address_2' => $zip_billing_details['zip_billing_address_2'],
                'city' => $zip_billing_details['zip_billing_city'],
                'state' => $zip_billing_details['zip_billing_state'],
                'postcode' => $zip_billing_details['zip_billing_postcode'],
                'country' => $zip_billing_details['zip_billing_country']
            ),
            'billing'
        );

        //set the shipping rates
        $shipping_rates = self::_get_shipping_rates($WC_Session);
        foreach ($WC_Session->get('chosen_shipping_methods', array()) as $chosen_shipping_method) {
            $order->add_shipping($shipping_rates[$chosen_shipping_method]);
        }

        $order->set_payment_method($this->WC_Zipmoney_Payment_Gateway);

        $order->calculate_totals();

        //set the coupon
        $applied_coupons = $WC_Session->get('applied_coupons', array());
        $coupon_discount_amounts = $WC_Session->get('coupon_discount_amounts', array());
        $coupon_discount_tax_amounts = $WC_Session->get('coupon_discount_tax_amounts', array());

        foreach ($applied_coupons as $coupon) {
            $order->add_coupon($coupon, $coupon_discount_amounts[$coupon], $coupon_discount_tax_amounts[$coupon]);
        }

        return $order;
    }

    /**
     * @param WC_Session $WC_Session
     * @return array
     */
    private function _get_shipping_rates(WC_Session $WC_Session)
    {
        $shipping_rates = array();

        for($i = 0; $i < 100; $i++){
            $shipping_package = $WC_Session->get('shipping_for_package_' . $i, false);

            if(empty($shipping_package)){
                return $shipping_rates;
            }

            $shipping_rates = array_merge($shipping_rates, $shipping_package['rates']);
        }

        return $shipping_rates;
    }


    /**
     * Prepare the charge request
     *
     * @param WC_Session $WC_Session
     * @return \zipMoney\Model\CreateChargeRequest
     */
    private function _prepare_charges_request(WC_Session $WC_Session)
    {
        //get the charge order
        $charge_order = self::_create_charge_order($WC_Session);

        //get authority
        $authority = new Authority(
            array(
                'type' => 'checkout_id',
                'value' => $WC_Session->get(WC_Zipmoney_Payment_Gateway_Config::META_CHECKOUT_ID)
            )
        );

        $capture_charge_option = $this->WC_Zipmoney_Payment_Gateway->get_option(WC_Zipmoney_Payment_Gateway_Config::CONFIG_CHARGE_CAPTURE);

        return new CreateChargeRequest(
            array(
                'authority' => $authority,
                'amount' => $WC_Session->get('total'),
                'currency' => get_woocommerce_currency(),
                'order' => $charge_order,
                'capture' => $capture_charge_option == WC_Zipmoney_Payment_Gateway_Config::CAPTURE_CHARGE_IMMEDIATELY ? true : false
            )
        );
    }


    /**
     * Construct the charge order object
     *
     * @param WC_Session $WC_Session
     * @return \zipMoney\Model\ChargeOrder
     */
    private function _create_charge_order(WC_Session $WC_Session)
    {
        $order_shipping = new OrderShipping(
            array(
                'address' => self::_create_shipping_address($WC_Session->get('zip_shipping_details'))
            )
        );

        return new ChargeOrder(
            array(
                'shipping' => $order_shipping,
                'items' => self::_get_order_items($WC_Session)
            )
        );
    }
}