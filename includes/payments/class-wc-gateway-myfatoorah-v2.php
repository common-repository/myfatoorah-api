<?php

if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('PaymentMyfatoorahApiV2')) {
    include_once( MYFATOORAH_WOO_PLUGIN_PATH . 'includes/libraries/PaymentMyfatoorahApiV2.php' );
}
if (!class_exists('WC_Gateway_Myfatoorah')) {
    include_once( 'class-wc-gateway-myfatoorah.php' );
}


/**
 * Myfatoorah_V2 Payment Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class       WC_Gateway_Myfatoorah_V2
 * @extends     WC_Payment_Gateway
 * @version     2.0.0
 */
class WC_Gateway_Myfatoorah_v2 extends WC_Gateway_Myfatoorah {

    protected $code;
    protected $defaultIcon;
    protected $count;
    protected $gateways;

    /**
     * Constructor
     */
    public function __construct() {
        $this->code               = 'v2';
        $this->method_description = __('The Myfatoorah Gateway is simple and powerful. The plugin works by sending customers to Myfatoorah invoice page to enter their payment information.', 'myfatoorah-woocommerce');
        $this->defaultIcon        = 'https://portal.myfatoorah.com/imgs/logo-myfatoorah-sm-blue.png';

        parent::__construct();

        $this->count = 0;
        if ($this->listOptions === 'multigateways') {
            $this->gateways = $this->mf->getVendorGatewaysByType();
            $this->count    = count($this->gateways);
        }

        $this->has_fields = ($this->count > 1) ? true : false;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Process the payment and return the result.
     * 
     * @param int $orderId
     * @return array
     */
    public function process_payment($orderId) {


        $curlData = $this->getPayLoadData($orderId);

        $gateway = ($this->listOptions === 'myfatoorah' || $this->count == 0) ? 'myfatoorah' : (($this->count == 1) ? $this->gateways[0]->PaymentMethodId : $this->get_post('mf_gateway'));

        $data = $this->mf->getInvoiceURL($orderId, $curlData, $gateway);

        update_post_meta($orderId, 'InvoiceId', $data['invoiceId']);

        return array(
            'result'   => 'success',
            'redirect' => $data['invoiceURL'],
        );
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    function payment_fields() {

        if (!wc_checkout_is_https()) {
            $error = __('Myfatoorah forces SSL checkout Payemnt. Your checkout is not secure! Please, contact site admin to enable SSL and ensure that the server has a valid SSL certificate.', 'myfatoorah-woocommerce');
        }

        include_once(MYFATOORAH_WOO_PLUGIN_PATH . 'templates/paymentFieldsV2.php');
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Return the gateway's title.
     *
     * @return string
     */
    public function get_title() {

        if ($this->listOptions === 'multigateways' && $this->count == 1) {
            return ($this->lang == 'ar') ? $this->gateways[0]->PaymentMethodAr : $this->gateways[0]->PaymentMethodEn;
        } else {
            return apply_filters('woocommerce_gateway_title', $this->title, $this->id);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Return the gateway's icon.
     *
     * @return string
     */
    public function get_icon() {

        if ($this->listOptions === 'multigateways' && $this->count == 1) {
            $icon = '<img src="' . $this->gateways[0]->ImageUrl . '" alt="' . esc_attr($this->get_title()) . '" style="margin: 0px; width: 50px; height: 30px;"/>';
        } else {
            $icon = $this->icon ? '<img src="' . WC_HTTPS::force_https_url($this->icon) . '" alt="' . esc_attr($this->get_title()) . '" />' : '';
        }

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
