<?php

class WC_Zip_Controller_Charge_Controller extends WC_Zip_Controller_Abstract_Controller
{
    /**
     * Create a charge
     *
     * @param $options => array(
     *      'checkoutId' => '',
     *      'result' => 'approved'
     * )
     * @return array
     */
    public function create_charge($options)
    {
        $result = array('result' => false);

        //validate the $options
        if(isset($options['result']) == false || isset($options['checkoutId']) == false){
            $result['title'] = 'Invalid request';
            $result['content'] = 'There are some parameters missing in the request url.';
            return $result;
        }

        WC_Zipmoney_Payment_Gateway_Util::log(
            sprintf('CheckoutId: %s, Result: %s', $options['checkoutId'], $options['result']),
            WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG
        );

        switch ($options['result']){
            case 'approved':
                //get session from option table by checkout id
                $WC_Session = get_option($options['checkoutId'], false);

                if (empty($WC_Session)) {
                    WC_Zipmoney_Payment_Gateway_Util::log(
                        'Empty session record with checkoutId: ' . $options['checkoutId'],
                        WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG
                    );

                    $result['title'] = 'Unable to get order with checkout id: ' . $options['checkoutId'];
                    $result['content'] = '';

                    return $result;
                }

                //if it is approved, then we will create a charge
                $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge($this->WC_Zipmoney_Payment_Gateway);

                $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->create_charge(
                    $WC_Session,
                    $this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_public_key(),
                    $options
                );

                WC_Zipmoney_Payment_Gateway_Util::log(json_encode($response), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG);

                $result['result'] = $response['success'];
                $result['order'] = $response['order'];
                $result['title'] = $response['success']?'Success': 'Error';
                $result['content'] = $response['message'];
                break;
            case 'referred':
                $result['title'] = 'The payment is in referred state';
                $result['content'] = 'Your application is currently under review by zipMoney and will be processed very shortly. You can contact the customer care at customercare@zipmoney.com.au for any enquiries.';
                break;
            case 'declined':
                $result['title'] = 'The checkout is declined';
                $result['content'] = 'Your application has been declined by zipMoney. Please contact zipMoney for further information.';
                break;
            case 'cancelled':
                $result['title'] = 'The checkout has been cancelled';
                $result['content'] = 'The checkout has bee cancelled.';
                break;
        }

        return $result;
    }

    /**
     * Cancel an authorized charge
     *
     * @param $order_id
     * @return bool
     */
    public function cancel_charge($order_id)
    {
        $order = new WC_Order($order_id);

        if (empty($order)) {
            //if it can't find the order
            wc_add_notice(__('Unable to find order by id: ' . $order_id, 'woothemes'), 'error');
            return false;
        }

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge($this->WC_Zipmoney_Payment_Gateway);

        $is_success = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->cancel_order_charge(
            $order,
            $this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_public_key()
        );

        if($is_success == true){
            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice('The zipMoney payment has been cancelled.', 'success');
        } else {
            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice('Unable to cancel payment.', 'error');
        }
    }


    /**
     * Capture an authorized charge
     *
     * @param $order_id
     * @return bool
     */
    public function capture_charge($order_id)
    {
        $order = new WC_Order($order_id);

        if(empty($order)){
            //if it can't find the order
            wc_add_notice(__('Unable to find order by id: ' . $order_id, 'woothemes'), 'error');
            return false;
        }

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge($this->WC_Zipmoney_Payment_Gateway);

        $is_success = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->capture_order_charge(
            $order,
            $this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_public_key()
        );

        if($is_success == true){
            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice('The zipMoney payment has been captured.', 'success');
        } else {
            WC_Zipmoney_Payment_Gateway_Util::add_admin_notice('Unable to capture payment.', 'error');
        }
    }

}