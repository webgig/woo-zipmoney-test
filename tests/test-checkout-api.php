<?php

/**
 * Class CheckoutApiTest
 *
 * @package Woocommerce_Zipmoneypayment_Apiv2
 */
class CheckoutApiTest extends WC_ZipmoneyPaymentGatewayTestMain
{
    public function exception_provider()
    {
        return array(
            array(new \Exception()),
            array(new \zipMoney\ApiException())
        );
    }

    public function test_successful_checkout()
    {
        //set the output result
        $checkout_result = new \zipMoney\Model\Checkout(array('id' => 'co_IwvE8adHGKg9YMURefHDD0'));

        $api_instance = $this->getMock('\zipMoney\Api\CheckoutsApi');
        $api_instance->expects($this->once())
            ->method('checkoutsCreate')
            ->will($this->returnValue($checkout_result));

        $WC_Zipmoney_Payment_Gateway_API_Request_Checkout = new WC_Zipmoney_Payment_Gateway_API_Request_Checkout(
            $this->payment_gateway,
            $api_instance
        );

        $result = $WC_Zipmoney_Payment_Gateway_API_Request_Checkout->create_checkout($this->WC_Session, 'url', 'key');

        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->getId());
        $this->assertEquals(get_class($result), 'zipMoney\Model\Checkout');
    }

    /**
     * @dataProvider exception_provider
     */
    public function test_exception_checkout($exception)
    {
        $api_instance = $this->getMock('\zipMoney\Api\CheckoutsApi');

        $api_instance->expects($this->once())
            ->method('checkoutsCreate')
            ->will($this->throwException($exception));

        $WC_Zipmoney_Payment_Gateway_API_Request_Checkout = new WC_Zipmoney_Payment_Gateway_API_Request_Checkout(
            $this->payment_gateway,
            $api_instance
        );

        $result = $WC_Zipmoney_Payment_Gateway_API_Request_Checkout->create_checkout($this->WC_Session, 'url', 'key');

        $this->assertEmpty($result);
    }

}