<?php

/**
 * Class ChargeApiTest
 *
 * @package Woocommerce_Zipmoneypayment_Apiv2
 */
class ChargeApiTest extends WC_ZipmoneyPaymentGatewayTestMain
{

    public function charge_result_provider()
    {
        $states = array('captured', 'authorised', 'cancelled');

        $result = array();

        foreach($states as $state){
            $result[] = array(new \zipMoney\Model\Charge(
                array(
                    'id' => 'test',
                    'state' => $state
                )
            ));
        }

        return $result;
    }

    public function exception_provider()
    {
        $apiException = new \zipMoney\ApiException();


        return array(
            array(new \Exception()),
            array(new \zipMoney\ApiException())
        );
    }

    /**
     * @dataProvider charge_result_provider
     */
    public function test_create_charge($charge_result)
    {
        $api_instance = $this->getMock('\zipMoney\Api\ChargesApi');
        $api_instance->expects($this->once())
            ->method('chargesCreate')
            ->will($this->returnValue($charge_result));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = self::get_mock_order();

        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->create_charge($this->WC_Session, 'key', $order);

        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('order', $response);

        if($charge_result->getState() == 'cancelled'){
            $this->assertFalse($response['success']);
        } else {
            $this->assertTrue($response['success']);
        }
    }


    /**
     * @dataProvider exception_provider
     */
    public function test_create_charge_with_exception($exception)
    {
        $api_instance = $this->getMock('\zipMoney\Api\ChargesApi');
        $api_instance->expects($this->once())
            ->method('chargesCreate')
            ->will($this->throwException($exception));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = self::get_mock_order();

        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->create_charge($this->WC_Session, 'key', $order);
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);

        $this->assertFalse($response['success']);
    }

    /**
     * @dataProvider charge_result_provider
     */
    public function test_capture_charge($charge_result)
    {
        $api_instance = $this->getMock('\zipMoney\Api\ChargesApi');
        $api_instance->expects($this->any())
            ->method('chargesCapture')
            ->will($this->returnValue($charge_result));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = self::get_mock_order();
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->capture_order_charge($order, 'key');
        $this->assertFalse($response);

        //order status is not authorized
        update_post_meta($order->id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, 'charge_id');
        $order = self::get_mock_order('processing');
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->capture_order_charge($order, 'key');
        $this->assertFalse($response);

        $order = self::get_mock_order(WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY_COMPARE);
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->capture_order_charge($order, 'key');

        if ($charge_result->getState() == 'captured') {
            $this->assertTrue($response);
        } else {
            $this->assertFalse($response);
        }
    }

    /**
     * @param null $status
     * @return mixed
     */
    private function get_mock_order($status = null)
    {
        $order = $this->getMock('WC_Order');
        $order->expects($this->any())
            ->method('payment_complete')
            ->will($this->returnValue(null));

        $order->id = 1;

        if(empty($status)){
            return $order;
        }

        $order->expects($this->any())
            ->method('get_status')
            ->will($this->returnValue($status));

        return $order;
    }

    /**
     * @dataProvider exception_provider
     */
    public function test_capture_charge_with_exception($exception)
    {
        $api_instance = $this->getMock('\zipMoney\Api\ChargesApi');
        $api_instance->expects($this->any())
            ->method('chargesCapture')
            ->will($this->throwException($exception));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = self::get_mock_order();

        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->capture_order_charge($order, 'key');
        $this->assertFalse($response);
    }

    /**
     * @dataProvider charge_result_provider
     */
    public function test_cancel_order_charge($charge_result)
    {
        $api_instance = $this->getMock('\zipMoney\Api\ChargesApi');
        $api_instance->expects($this->any())
            ->method('chargesCancel')
            ->will($this->returnValue($charge_result));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = self::get_mock_order();

        //order without charge_id
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->cancel_order_charge($order, 'key');
        $this->assertFalse($response);

        //order status is not authorized
        update_post_meta($order->id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, 'charge_id');
        self::get_mock_order('processing');
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->cancel_order_charge($order, 'key');
        $this->assertFalse($response);

        $order = self::get_mock_order(WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY_COMPARE);
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->cancel_order_charge($order, 'key');

        if ($charge_result->getState() == 'cancelled') {
            $this->assertTrue($response);
        } else {
            $this->assertFalse($response);
        }
    }

    /**
     * @dataProvider exception_provider
     */
    public function test_cancel_charge_with_exception($exception)
    {
        $api_instance = $this->getMock('\zipMoney\Api\ChargesApi');
        $api_instance->expects($this->any())
            ->method('chargesCancel')
            ->will($this->throwException($exception));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = self::get_mock_order();

        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->capture_order_charge($order, 'key');
        $this->assertFalse($response);
    }

    public function test_refund_order()
    {
        $api_instance = $this->getMock('\zipMoney\Api\RefundsApi');
        $api_instance->expects($this->any())
            ->method('refundsCreate')
            ->will($this->returnValue(new \zipMoney\Model\Refund()));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = self::get_mock_order();

        //order without charge_id
        $order->id = 1;
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->refund_order_charge(
            $order,
            'key',
            100,
            'Reason'
        );
        $this->assertFalse($response);

        update_post_meta($order->id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, 'charge_id');

        //order with <=0 amount
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->refund_order_charge(
            $order,
            'key',
            0,
            'Reason'
        );
        $this->assertFalse($response);

        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->refund_order_charge(
            $order,
            'key',
            -100,
            'Reason'
        );
        $this->assertFalse($response);

        //correct order
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->refund_order_charge(
            $order,
            'key',
            100,
            'Reason'
        );
        $this->assertTrue($response);
    }

    /**
     * @dataProvider exception_provider
     */
    public function test_refund_order_with_exception($exception)
    {
        $api_instance = $this->getMock('\zipMoney\Api\RefundsApi');
        $api_instance->expects($this->any())
            ->method('refundsCreate')
            ->will($this->throwException($exception));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = self::get_mock_order();
        update_post_meta($order->id, WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, 'charge_id');

        //correct order
        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->refund_order_charge(
            $order,
            'key',
            100,
            'Reason'
        );
        $this->assertFalse($response);
    }
}