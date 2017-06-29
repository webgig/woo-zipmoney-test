<?php
use \zipMoney\Model\CheckoutConfiguration;
use \zipMoney\Model\CreateCheckoutRequest;
use \zipMoney\Model\Shopper;
use \zipMoney\Model\OrderShipping;
use \zipMoney\Model\CheckoutOrder;
use \zipMoney\ApiException;

class WC_Zipmoney_Payment_Gateway_API_Request_Checkout extends WC_Zipmoney_Payment_Gateway_API_Abstract
{
    private $api_instance;

    public function __construct(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway, $api_instance)
    {
        parent::__construct($WC_Zipmoney_Payment_Gateway);

        $this->api_instance = $api_instance;
    }

    /**
     * Create checkout to API
     *
     * @param WC_Session $WC_Session
     * @param $redirect_url
     * @param $api_key
     * @return null|\zipMoney\Model\Checkout
     */
    public function create_checkout(WC_Session $WC_Session, $redirect_url, $api_key)
    {
        parent::set_api_key($api_key);

        $body = self::_prepare_request_for_checkout($WC_Session, $redirect_url);
        //log the body information
        WC_Zipmoney_Payment_Gateway_Util::log('Sending checkout request to API', WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO);
        WC_Zipmoney_Payment_Gateway_Util::log(WC_Zipmoney_Payment_Gateway_Util::object_json_encode($body), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

        try {
            $checkout = $this->api_instance->checkoutsCreate($body);

            //log the checkout information
            WC_Zipmoney_Payment_Gateway_Util::log('Return from checkout API', WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO);
            WC_Zipmoney_Payment_Gateway_Util::log(WC_Zipmoney_Payment_Gateway_Util::object_json_encode($checkout), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

            //add meta data to session
            $WC_Session->set(WC_Zipmoney_Payment_Gateway_Config::META_CHECKOUT_ID, $checkout->getId());

            //set user id if there is any
            if(is_user_logged_in()){
                $WC_Session->set(WC_Zipmoney_Payment_Gateway_Config::META_USER_ID, get_current_user_id());
            }

            //save the checkout and session into option table
            if(version_compare(WC()->version, '4.2.0', '>=')){
                update_option($checkout->getId(), $WC_Session, false);
            } else {
                update_option($checkout->getId(), $WC_Session);
            }

            return $checkout;

        } catch (ApiException $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getResponseBody());

            wc_add_notice(__('Payment error:', 'woothemes') . $exception->getMessage() . print_r($exception->getResponseBody(), true), 'error');
        } catch (Exception $exception) {
            WC_Zipmoney_Payment_Gateway_Util::log($exception->getCode() . $exception->getMessage());
            wc_add_notice(__('Payment error:', 'woothemes') . $exception->getMessage(), 'error');
        }

        return null;
    }


    /**
     * Prepare the checkout request
     *
     * @param WC_Session $WC_Session
     * @param $redirect_url
     * @return \zipMoney\Model\CreateCheckoutRequest
     */
    private function _prepare_request_for_checkout(WC_Session $WC_Session, $redirect_url)
    {
        //construct the shopper
        $shopper = self::_create_shopper($WC_Session);

        //get the checkout object
        $checkout_order = self::_create_checkout_order($WC_Session);

        //get the config
        $checkout_configuration = new CheckoutConfiguration(
            array(
                'redirect_uri' => $redirect_url
            )
        );

        return new CreateCheckoutRequest(
            array(
                'shopper' => $shopper,
                'order' => $checkout_order,
                'config' => $checkout_configuration
            )
        );
    }

    /**
     * Create the shopper object
     *
     * @param WC_Session $WC_Session
     * @return \zipMoney\Model\Shopper
     */
    private function _create_shopper(WC_Session $WC_Session)
    {
        $billing_array = $WC_Session->get('zip_billing_details');

        //get shopper's data
        $data = array(
            'first_name' => $billing_array['zip_billing_first_name'],
            'last_name' => $billing_array['zip_billing_last_name'],
            'phone' => $billing_array['zip_billing_phone'],
            'email' => $billing_array['zip_billing_email'],
            'billing_address' => self::_create_billing_address($billing_array)
        );

        //get teh shopper statics if it's available
        $shopper_statistics = self::_get_shopper_statistics();
        if(!empty($shopper_statistics)) {
            $data['statistics'] = $shopper_statistics;
        }

        return new Shopper($data);
    }



    /**
     * Create checkout order object
     *
     * @param WC_Session $WC_Session
     * @return \zipMoney\Model\CheckoutOrder
     */
    private function _create_checkout_order(WC_Session $WC_Session)
    {
        $chosen_shipping_rates = $WC_Session->get('chosen_shipping_methods', array());
        $is_pickup = in_array('local_pickup', $chosen_shipping_rates);

        $order_shipping = new OrderShipping(
            array(
                'address' => self::_create_shipping_address($WC_Session->get('zip_shipping_details')),
                'pickup' => $is_pickup
            )
        );

        //Create the checkout order
        $checkout_order = new CheckoutOrder(
            array(
                'amount' => $WC_Session->get('total'),
                'currency' => get_woocommerce_currency(),
                'shipping' => $order_shipping,
                'items' => self::_get_order_items($WC_Session)
            )
        );

        return $checkout_order;
    }

}