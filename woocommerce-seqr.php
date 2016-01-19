<?php
/*
Plugin Name: WooCommerce SEQR Payment Gateway
Plugin URI: http://developer.seqr.com/woocommerce
Description: SEQR Payment gateway for WooCommerce
Author: Seamless
Author URI: http://www.seamless.se
Version: 0.1.1
License: Apache License, Version 2.0 - http://www.apache.org/licenses/LICENSE-2.0.html
*/
function woocommerce_seqr_load_plugin_textdomain() {
  load_plugin_textdomain( 'seqr', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'woocommerce_seqr_load_plugin_textdomain' );
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

    function add_seqr_refund_action($actions)
    {
        $order = new WC_Order($_REQUEST['post']);
        $paymentReference = get_post_meta($order->id, 'SEQR Payment Reference', true);
        $refundReference = get_post_meta($order->id, 'SEQR Refund Reference', true);
        if ($paymentReference && !$refundReference) {
            $actions['seqr_refund'] = __('SEQR Refund Payment', 'seqr');
        }
        return $actions;
    }

    function do_seqr_refund($order)
    {
        $payment_gateway = new WC_SEQR_Payment_Gateway();
        if (!defined('SEQR_REFUND_PAYMENT')) {
            define('SEQR_REFUND_PAYMENT', TRUE);
            $payment_gateway->refund_payment($order, NULL);
        }
    }

    add_filter('woocommerce_order_actions', 'add_seqr_refund_action');
    add_action('woocommerce_order_action_seqr_refund', 'do_seqr_refund');

}

add_action('plugins_loaded', 'woocommerce_seqr_init');
