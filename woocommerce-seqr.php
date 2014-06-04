<?php
/*
Plugin Name: WooCommerce SEQR Payment Gateway
Plugin URI: http://developer.seqr.com/woocommerce
Description: SEQR Payment gateway for WooCommerce
Author: Seamless
Author URI: http://www.seamless.se
Version: 0.1.0
License: Apache License, Version 2.0 - http://www.apache.org/licenses/LICENSE-2.0.html
*/

function woocommerce_seqr_init()
{
    if (class_exists('WC_Payment_Gateway')) {

        require_once 'includes/class-wc-gateway-seqr.php';

        function woocommerce_add_seqr_gateway($methods)
        {
            $methods[] = 'WC_SEQR_Payment_Gateway';
            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'woocommerce_add_seqr_gateway');

    }
}

add_action('plugins_loaded', 'woocommerce_seqr_init');
