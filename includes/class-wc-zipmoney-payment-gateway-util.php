<?php

class WC_Zipmoney_Payment_Gateway_Util
{
    private $WC_Zipmoney_Payment_Gateway;

    public function __construct(WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway)
    {
        $this->WC_Zipmoney_Payment_Gateway = $WC_Zipmoney_Payment_Gateway;
    }

    public function hash_api_key()
    {

    }

}