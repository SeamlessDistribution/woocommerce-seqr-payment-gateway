<?php
/*
Plugin Name: WooCommerce SEQR Payment Gateway
Plugin URI: http://developer.seqr.com/woocommerce
Description: SEQR Payment gateway for woocommerce
Version: 0.1.0
Author: Erik Hedenström
Author URI: http://se.linkedin.com/in/ehedenst
*/

require_once 'Mobile_Detect.php';

add_action('plugins_loaded', 'woocommerce_seqr_init');

function woocommerce_seqr_init()
{

    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_SEQR_Payment_Gateway extends WC_Payment_Gateway
    {

        public function __construct()
        {

            $this->id = 'seqr';
            $this->method_title = __('SEQR', 'seqr');
            $this->has_fields = false;
            $this->order_button_text = __('Pay with SEQR', 'seqr');
            $this->icon = apply_filters('woocommerce_seqr_icon', $this->plugin_url() . '/assets/logo.png');
            $this->javascript = apply_filters('woocommerce_seqr_script', $this->plugin_url() . '/assets/seqr.js');
            $this->callback_url = WC()->api_request_url(get_class($this));

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this ->get_option('title');
            $this->description = $this ->get_option('description');
            $this->wsdl_uri = $this ->get_option('wsdl_uri');
            $this->terminal_id = $this ->get_option('terminal_id');
            $this->terminal_password = $this ->get_option('terminal_password');
            $this->poll = $this->get_option('poll');
            $this->debug = $this->get_option('debug');

            $this->log = new WC_Logger();

            add_action('woocommerce_receipt_seqr', array($this, 'receipt_page'));
            add_action('woocommerce_thankyou_seqr', array($this, 'thankyou_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Payment listener/API hooks and actions
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'process_callback'));
            add_action('woocommerce_resume_order', array($this, 'resume_order'));

        }

        function log($message)
        {
            if ('yes' == $this->debug) {
                $caller = array_shift(debug_backtrace());
                $this->log->add('seqr', $caller['line'] . ' : ' . $message);
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
                    'default' => 'https://extdev.seqr.com/extclientproxy/service/v2?wsdl',
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
                    'type' => 'password',
                    'description' => __('Terminal Password.', 'seqr'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'poll' => array(
                    'title' => __('Poll Status', 'seqr'),
                    'type' => 'checkbox',
                    'label' => __('Poll Status', 'seqr'),
                    'description' => sprintf(__('Enable this option if callbacks from SEQR to <code>%s</code> are not possible.', 'seqr'), $this->callback_url),
                    'default' => 'no',
                    'desc_tip' => false
                ),
                'debug' => array(
                    'title' => __('Debug Log', 'seqr'),
                    'type' => 'checkbox',
                    'label' => __('Enable logging', 'seqr'),
                    'description' => sprintf(__('Log SEQR events, such as callback requests, inside <code>woocommerce/logs/seqr-%s.txt</code>.', 'seqr'), sanitize_file_name(wp_hash('seqr'))),
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
            $this->generate_settings_html();
            echo '</table>';
        }

        /**
         *  There are no payment fields for seqr, but we want to show the description if set.
         **/
        function payment_fields()
        {
            if ($this ->description) echo wpautop(wptexturize($this->description));
        }

        /**
         * Receipt Page
         **/
        function receipt_page($order_id)
        {
            $order = new WC_Order($order_id);
            $invoiceReference = get_post_meta($order->id, 'SEQR Invoice Reference', true);
            if (!$invoiceReference) {
                $result = $this->send_invoice($order);
                if ($result->resultCode == 0) {
                    $invoiceReference = wc_clean($result->invoiceReference);
                    add_post_meta($order->id, 'SEQR Invoice Reference', $invoiceReference, true);
                } else {
                    $this->log(json_encode($result));
                    echo '<h3>' . __('SEQR Payment Failed', 'seqr') . '</h3><p><pre>' . __($result->resultDescription, 'seqr') . '</pre></p>';
                }
            }
            if ($invoiceReference) {
                $callbackUrl = urlencode($this->callback_url . '?clientInvoiceId=' . $order_id);
                echo '<script id="seqr_js" src="' . $this->javascript . '#!callbackUrl=' . $callbackUrl . '"></script><p><img src="' . apply_filters('woocommerce_seqr_icon', $this->plugin_url() . '/assets/logo_wide.png') . '" width="195" height="36"/></p><p><img id="seqr_qr" src="https://chart.googleapis.com/chart?chs=195x195&cht=qr&chld=M|0&chl=HTTP%3A%2F%2FSEQR.SE%2FR' . $invoiceReference . '" width="195" height="195"/></p>';
            }
        }

        /**
         * Order Received Page.
         */
        public function thankyou_page()
        {
            echo '<p><img src="' . apply_filters('woocommerce_seqr_icon', $this->plugin_url() . '/assets/logo_wide.png') . '" width="195" height="36"/></p>';
        }

        public function resume_order($order_id)
        {
            $order = new WC_Order($order_id);
            $invoiceReference = get_post_meta($order->id, 'SEQR Invoice Reference', true);
            if ($invoiceReference) {
                $result = $this->cancel_invoice($invoiceReference);
                if ($result->resultCode == 0 || $result->resultCode == 95) { // Success or already canceled
                    delete_post_meta($order->id, 'SEQR Invoice Reference');
                }
            }
        }

        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            $detect = new Mobile_Detect();
            $order = new WC_Order($order_id);
            if ($detect->isMobile()) {
                $invoiceReference = get_post_meta($order->id, 'SEQR Invoice Reference', true);
                if (!$invoiceReference) {
                    $result = $this->send_invoice($order);
                    if ($result->resultCode == 0) {
                        $invoiceReference = wc_clean($result->invoiceReference);
                    }
                }
                if ($invoiceReference) {
                    return array(
                        'result' => 'success',
                        'redirect' => 'SEQR-DEMO://SEQR.SE/R' . $invoiceReference
                    );
                } else {
                    // TODO: Handle error
                }
            } else {
                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_payment_url(true)
                );
            }
        }

        function process_callback()
        {
            $method = $_SERVER['REQUEST_METHOD'];
            switch ($method) {
                case 'GET' :
                    $order = new WC_Order($_GET['clientInvoiceId']);
                    if ($order->id) {
                        $invoiceReference = get_post_meta($order->id, 'SEQR Invoice Reference', true);
                        if ('yes' == $this->poll) {
                            $url = $this->update_order_status($invoiceReference, $order);
                        } else {
                            $url = $order->get_checkout_order_received_url();
                        }
                        $response =
                            array(
                                'status' => $order->status,
                                'url' => $url
                            );
                        @ob_clean();
                        header('HTTP/1.1 200 OK');
                        echo json_encode($response);
                        @ob_flush();
                        return true;
                    } else {
                        return false;
                    }
                case 'POST' :
                    $order = new WC_Order($_POST['clientInvoiceId']);
                    if ('pending' != $order->status || !isset($_POST['invoiceReference'])) {
                        return false;
                    }
                    $invoiceReference = get_post_meta($order->id, 'SEQR Invoice Reference', true);
                    if ($invoiceReference == $_POST['invoiceReference']) {
                        if (isset($_POST['msisdn'])) {
                            add_post_meta($order->id, 'SEQR MSISDN', wc_clean($_POST['msisdn']), true);
                        }
                        $this->update_order_status($invoiceReference, $order);
                        return true;
                    } else {
                        return false;
                    }
                default:
                    wp_die("Malformed request", "SEQR", array('response' => 400));
                    break;
            }
        }

        function update_order_status($invoiceReference, &$order)
        {
            $result = $this->get_payment_status($invoiceReference);
            switch ($result->status) {
                case 'PAID' :
                    WC()->cart->empty_cart();
                    $order->payment_complete();
                    add_post_meta($order->id, 'SEQR Payment Reference', wc_clean($result->receipt->paymentReference), true);
                    return $order->get_checkout_order_received_url();
                case 'CANCELED' :
                    WC()->cart->empty_cart();
                    $order->update_status('cancelled', __('SEQR payment cancelled', 'seqr'));
                    return $order->get_cancel_order_url();
                default:
                    return $order->get_checkout_order_received_url();
            }
        }

        function add_invoice_row(&$invoiceRows, $label, $quantity, $currency, $value)
        {
            $invoiceRow =
                array(
                    "itemDescription" => $label,
                    "itemTotalAmount" =>
                        array(
                            "currency" => $currency,
                            "value" => $value
                        )
                );
            if ($quantity > 0) {
                $invoiceRow["itemQuantity"] = $quantity;
            }
            array_push($invoiceRows, $invoiceRow);
        }

        /**
         * Send Invoice
         */
        function send_invoice($order)
        {

            $invoiceRows = array();
            $inc_tax = get_option('woocommerce_prices_include_tax') == 'yes';
            $currency = $order->get_order_currency();

            // Cart Contents
            foreach ($order->get_items() as $item) {
                if ($item['qty']) {
                    $item_name = $item['name'];
                    $item_meta = new WC_Order_Item_Meta($item['item_meta']);
                    if ($meta = $item_meta->display(true, true)) {
                        $item_name .= ' ( ' . $meta . ' )';
                    }
                    $this->add_invoice_row($invoiceRows, $item_name, $item['qty'], $currency, $order->get_line_subtotal($item, $inc_tax, true));
                }
            }

            if ($order->get_cart_discount() > 0) {
                $this->add_invoice_row($invoiceRows, __('Cart Discount:', 'woocommerce'), 0, $currency, -1 * $order->get_cart_discount());
            }

            if ($order->get_total_shipping() > 0) {
                $this->add_invoice_row($invoiceRows, __('Shipping:', 'woocommerce'), 0, $currency, $order->get_total_shipping());
            }

            if ($order->get_order_discount() > 0) {
                $this->add_invoice_row($invoiceRows, __('Order Discount:', 'woocommerce'), 0, $currency, -1 * $order->get_order_discount());
            }

            foreach ($order->get_tax_totals() as $tax) {
                if ($tax->amount > 0) {
                    $this->add_invoice_row($invoiceRows, $tax->label, 0, $currency, round($tax->amount, 2));
                }
            }

            $invoice = array(
                "acknowledgmentMode" => "NO_ACKNOWLEDGMENT",
                "issueDate" => date('Y-m-d\TH:i:s', time()),
                "title" => $order->get_order_number(),
                "clientInvoiceId" => $order->id,
                "invoiceRows" => $invoiceRows,
                "totalAmount" =>
                    array(
                        "currency" => $order->get_order_currency(),
                        "value" => $order->get_total()
                    ),
                "backURL" => $this->callback_url
            );

            if ("no" == $this->poll) {
                $invoice['notificationUrl'] = $this->callback_url;
            }

            $params = array(
                "invoice" => $invoice
            );
            return $this->soap_call('sendInvoice', $params);

        }

        function cancel_invoice($invoiceReference)
        {
            $params = array(
                "invoiceReference" => $invoiceReference
            );
            return $this->soap_call('cancelInvoice', $params);
        }

        function get_payment_status($invoiceReference)
        {
            $params = array(
                "invoiceReference" => $invoiceReference,
                "invoiceVersion" => 0
            );
            return $this->soap_call('getPaymentStatus', $params);
        }

        function soap_call($function_name, $params)
        {

            $params['context'] = array(
                'initiatorPrincipalId' => array(
                    'id' => $this->terminal_id,
                    'type' => 'TERMINALID'
                ),
                'password' => $this->terminal_password,
                'clientRequestTimeout' => '0'
            );

            $this->log('SOAP Request, ' . $function_name . ' : ' . json_encode($params));
            $soapClient = new SoapClient($this->wsdl_uri);
            $result = $soapClient->__soapCall($function_name, array($params));
            $this->log('SOAP Response, ' . $function_name . ' : ' . json_encode($result));

            return $result->return;

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