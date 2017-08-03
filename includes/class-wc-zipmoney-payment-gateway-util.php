<?php
class WC_Zipmoney_Payment_Gateway_Util
{
    private static $logger = null;
    public static $config_log_level = WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_ALL;

    /**
     * Log the message when necessary
     *
     * @param $message
     * @param int $log_level
     */
    public static function log($message, $log_level = WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_ALL)
    {
        if(self::$config_log_level > $log_level){
            //log the message with log_level higher than the default value only
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