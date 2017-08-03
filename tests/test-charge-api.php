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
    public function test_successful_create_charge($charge_result)
    {
        $api_instance = $this->getMock('\zipMoney\Api\ChargesApi');
        $api_instance->expects($this->once())
            ->method('chargesCreate')
            ->will($this->returnValue($charge_result));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = $this->getMock('WC_Order');
        $order->expects($this->any())
            ->method('payment_complete')
            ->will($this->returnValue(null));

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
    public function test_failed_create_charge($exception)
    {
        $api_instance = $this->getMock('\zipMoney\Api\ChargesApi');
        $api_instance->expects($this->once())
            ->method('chargesCreate')
            ->will($this->throwException($exception));

        $WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
            $this->payment_gateway,
            $api_instance
        );

        $order = $this->getMock('WC_Order');
        $order->expects($this->any())
            ->method('payment_complete')
            ->will($this->returnValue(null));

        $response = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->create_charge($this->WC_Session, 'key', $order);
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);

        $this->assertFalse($response['success']);
    }
}