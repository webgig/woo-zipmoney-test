<?php

/**
 * Plugin Name:       WooCommerce ZipMoney Payments
 * Plugin URI:        http://www.zipmoney.com.au/
 * Description:       Buy Now Pay Later. ZipMoney Payment Gateway allows realtime credit to customers in a seamless and user friendly way – all without the need to exchange any financial information, and leverage the power of promotional finance to increase sales for our business partners, risk free.
 * Version:           1.0.0-rc4
 * Author:            ZipMoney Payments
 * Author URI:        http://www.zipmoney.com.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Github URI:            https://github.com/zipMoney/woocommerce/
 *
 *
 * @version  1.0.0-rc4
 * @package  zipMoney Payments
 * @author   zipMoney Payments
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}


/**
 * Add zipMoney gateway class to hook
 *
 * @param $methods
 * @return array
 */
function add_zipmoney_gateway_class($methods)
{
    $methods[] = 'WC_Zipmoney_Payment_Gateway';
    return $methods;
}

/**
 * Instantiates the Zipmoney Payment Gateway class and then
 * calls its run method officially starting up the plugin.
 */
function run_zipmoney_payment_gateway()
{
    //Include the vendor repositories
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    /**
     * Include the core class responsible for loading all necessary components of the plugin.
     */
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-zipmoney-payment-gateway.php';

    if (!class_exists('WC_Payment_Gateway')) {
        //if the woocommerce payment gateway is not defined, then we won't activate the zipmoney payment gateway
        return;
    }

    $wc_zipmoney_payment_gateway = new WC_Zipmoney_Payment_Gateway();
    $wc_zipmoney_payment_gateway->run();

    //After the class is initialized, we put the class to wc payment options
    add_filter('woocommerce_payment_gateways', 'add_zipmoney_gateway_class');
}

// Call the above function to begin execution of the plugin.
add_action('plugins_loaded', 'run_zipmoney_payment_gateway');
