<?php

class WC_Zip_Controller_Checkout_Controller extends WC_Zip_Controller_Abstract_Controller
{

    /**
     * Convert the current checkout session to some static data
     */
    public function create_checkout()
    {
        WC_Zipmoney_Payment_Gateway_Util::log('Checkout session started');

        $WC_Zipmoney_Payment_Gateway_API_Request_Checkout = new WC_Zipmoney_Payment_Gateway_API_Request_Checkout($this->WC_Zipmoney_Payment_Gateway);

        $checkout_response = $WC_Zipmoney_Payment_Gateway_API_Request_Checkout->create_checkout(
            WC()->session,
            WC_Zipmoney_Payment_Gateway_Util::get_complete_endpoint_url(),
            $this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_public_key()
        );

        if(empty($checkout_response)){
            return array('message' => 'Can not redirect to zipMoney.');
        }

        return array(
            'redirect_uri' => $checkout_response->getUri(),
            'message' => 'Redirecting to zipMoney.'
        );

    }
}