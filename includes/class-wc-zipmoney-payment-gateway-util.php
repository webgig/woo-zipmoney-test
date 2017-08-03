<?php
class WC_Zipmoney_Payment_Gateway_Util
{
    private static $logger = null;
    public static $config_log_enable = true;

    /**
     * Log the message when necessary
     *
     * @param $message
     * @param bool $forceLog
     */
    public static function log($message, $forceLog = false)
    {
        if(self::$config_log_enable == false && $forceLog == false){
            //if the default setting and it's not force log, then we won't log anything
            return;
        }

        if (is_array($message) || is_object($message)){
            //if the input is array or object, use print_r to convert it to string
            $message = print_r($message, true);
        }

        if(is_null(self::$logger)) {
            //check the logger is initialised
            self::$logger = new WC_Logger();
        }

        //log the message into file
        self::$logger->add('zipmoney', $message);
    }

}