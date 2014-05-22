<?php
/*
Plugin Name: WooCommerce SEQR Payment Gateway
Plugin URI: http://developer.seqr.com/woocommerce
Description: SEQR Payment gateway for woocommerce
Version: 0.1.0
Author: Erik Hedenström
Author URI: http://se.linkedin.com/in/ehedenst
*/

add_action('plugins_loaded', 'woocommerce_seqr_init', 0);

function woocommerce_seqr_init()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_SEQR_Payment_Gateway extends WC_Payment_Gateway
    {

        public function __construct()
        {

            $this ->id = 'SEQR';
            $this ->medthod_title = __('SEQR', 'seqr');
            $this->has_fields = false;
            $this->order_button_text = __('Pay with SEQR', 'seqr');
            $this->notify_url = WC()->api_request_url('WC_Gateway_SEQR');
            $this->icon = apply_filters('woocommerce_seqr_icon', $this->plugin_url() . '/assets/logo.png');

            $this->init_form_fields();
            $this ->init_settings();

            $this ->title = $this ->get_option('title');
            $this ->description = $this ->get_option('description');
            $this ->wsdl_uri = $this ->get_option('wsdl_uri');
            $this ->terminal_id = $this ->get_option('terminal_id');
            $this ->terminal_password = $this ->get_option('terminal_password');
            $this->debug = $this->get_option('debug');

            $this->log = new WC_Logger();

            add_action('woocommerce_api_wc_seqr', array($this, 'check_response'));
            add_action('woocommerce_receipt_seqr', array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            $this->log('Settings: ' . json_encode($this->settings));

        }

        function log($message)
        {
            if ('yes' == $this->debug) {
                $this->log->add('seqr', $message);
            }
        }

        function plugin_url()
        {
            return untrailingslashit(plugins_url('/', __FILE__));
        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'seqr'),
                    'type' => 'checkbox',
                    'label' => __('Enable SEQR Checkout', 'seqr'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Title', 'seqr'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'seqr'),
                    'default' => __('SEQR Mobile Payment', 'seqr'),
                    'desc_tip' => true
                ),
                'description' => array(
                    'title' => __('Description', 'seqr'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'seqr'),
                    'default' => __('Pay securely with your mobile phone.', 'seqr'),
                    'desc_tip' => true
                ),
                'wsdl_uri' => array(
                    'title' => __('WSDL URI', 'seqr'),
                    'type' => 'text',
                    'description' => __('URI of the WSDL file', 'seqr'),
                    'default' => 'https://extdev4.seqr.se/extclientproxy/service/v2?wsdl',
                    'desc_tip' => true
                ),
                'terminal_id' => array(
                    'title' => __('Terminal ID', 'seqr'),
                    'type' => 'text',
                    'description' => __('Terminal ID.', 'seqr'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'terminal_password' => array(
                    'title' => __('Terminal Password', 'seqr'),
                    'type' => 'text',
                    'description' => __('Terminal Password.', 'seqr'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'debug' => array(
                    'title' => __('Debug Log', 'seqr'),
                    'type' => 'checkbox',
                    'label' => __('Enable logging', 'seqr'),
                    'description' => sprintf(__('Log SEQR events, such as callback requests, inside <code>woocommerce/logs/seqr-%s.txt</code>', 'seqr'), sanitize_file_name(wp_hash('seqr'))),
                    'default' => 'no',
                    'desc_tip' => false
                )
            );
        }

        public function admin_options()
        {
            echo '<h3>' . __('SEQR Payment Gateway', 'seqr') . '</h3>';
            echo '<p>' . __('SEQR is the premiere solution for mobile payments') . '</p>';
            echo '<table class="form-table">';
            $this ->generate_settings_html();
            echo '</table>';
        }

        /**
         *  There are no payment fields for seqr, but we want to show the description if set.
         **/
        function payment_fields()
        {
            if ($this ->description) echo wpautop(wptexturize($this ->description));
        }

        /**
         * Receipt Page
         **/
        function receipt_page($order)
        {
            echo '<p>' . __('Thank you for your order, please click the button below to pay with SEQR.', 'seqr') . '</p>';
            echo $this ->generate_seqr_form($order);
        }

        /**
         * Generate SEQR form
         **/
        public function generate_seqr_form($order_id)
        {
            log('generate_seqr_form for ' . $order_id);
            return "QR code HERE!";
        }

        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);
            log('redirect = ' . $order->get_checkout_payment_url(true));
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }
    }

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_seqr_gateway($methods)
    {
        $methods[] = 'WC_SEQR_Payment_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_seqr_gateway');
}

?>