WooCommerce SEQR Payment Gateway
===================

### SEQR ###
SEQR is Sweden’s and Europe’s most used mobile wallet in stores and online. SEQR enables anybody with a smartphone to pay in stores online and in-app. Users can also transfer money at no charge, store receipts digitally and receive offers and promotions directly through one mobile app.

SEQR offer the merchant 50% in reduction to payment card interchange and no capital investment requirements. SEQR as method of payment is also completely independent of PCI and traditional card networks.

SEQR is based on Seamless’ technology, a mobile phone payment and transaction service using QR codes & NFC on the front-end and Seamless’ proven transaction server on the back-end. SEQR is the only fully-integrated mobile phone payment solution handling the entire transaction chain, from customer through to settlement. Through our state of the art technology, we have created the easiest, secure, and most cost effective payment system.

Learn more about SEQR on www.seqr.com

### Plugin ###
Plugin provide possibility for shop clients to select SEQR as payment method, and after order placement pay it via scanning QR code (or directly from your mobile device).  

* SEQR as payment method on checkout page. In case of usage mobile device SEQR application will be opened automaticly by click to the payment button, otherwise QR code will be displayed.
 
![alt tag](/doc/WC-SEQR-Select.png)

* Payment via scanning of QR code.

![alt tag](/doc/WC-SEQR-QR.png)

### Installation & Configuration ###
![alt tag](/doc/WC-SEQR-Settings.png)

Plugin can be installed by copping all plugin files to the woocommerce directory (`/wp-content/plugins/woocommerce-seqr`). Plugin directory name should be `woocommerce-seqr`. In case of package installation archive name should be `woocommerce-seqr.zip`.  

Plugin configuration properties available on Woocommerce administration page WooCommerce > Settings > Checkout > SEQR.

Contact Seamless on integrations@seamless.se to get the right settings for the SOAP url, Terminal ID and Terminal Password. 

Paid order and cancelled order statuses, used to marking orders in Woocommerce (It visible in orders history).

Title is shown as option of payment method in checkout process. 

All properties are required and should be configured before enabling this payment method in production.

### Tested with ###
* WordPress 3.9 + WooCommerce 2.1.0 
* WordPress 4.0 + WooCommerce 2.2.4
* Wordpress 4.4 + WooCommerce 2.4.13

### Development & File structure ###

Plugin based on javascript plugin for SEQR integration. Please check it for understanding how work web component http://github.com/SeamlessDistribution/seqr-webshop-plugin. For more information about SEQR API please check http://developer.seqr.com/merchant/webshop/
