<?php

class WC_Zip_Controller_Charge_Controller extends WC_Zip_Controller_Abstract_Controller
{
    public function create_charge($options)
    {
        //get session from option table by checkout id
        $WC_Session = get_option($options['checkoutId'], false);

        if(empty($WC_Session)){
            return false;
        }

        if($options['result'] == 'approved'){
            //if it is approved, then we will create a charge
            $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge($this->WC_Zipmoney_Payment_Gateway);

            $order = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->create_charge(
                $WC_Session,
                $this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_public_key(),
                $options
            );

            WC_Zipmoney_Payment_Gateway_Util::log($order, WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

            return $order;
        }

        return false;
    }
}