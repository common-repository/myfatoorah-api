<?php

if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('PaymentMyfatoorahApiV2')) {
    include_once(MYFATOORAH_WOO_PLUGIN_PATH . 'includes/libraries/PaymentMyfatoorahApiV2.php');
}
if (!class_exists('WC_Gateway_Myfatoorah')) {
    include_once('class-wc-gateway-myfatoorah.php');
}

/**
 * Myfatoorah_Direct Payment Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class       WC_Gateway_Myfatoorah_Direct
 * @extends     WC_Payment_Gateway
 * @version     2.0.0
 */
class WC_Gateway_Myfatoorah_direct extends WC_Gateway_Myfatoorah {

    protected $code;
    protected $defaultIcon;
    protected $count;
    protected $gateways;

    /**
     * Constructor
     */
    public function __construct() {
        $this->code               = 'direct';
        $this->method_description = __('The Myfatoorah Gateway is simple and powerful. The plugin works by adding credit card fields on the checkout page, and then sending the details to myfatoorah for verification.', 'myfatoorah-woocommerce');
        $this->defaultIcon        = plugins_url(MYFATOORAH_WOO_PLUGIN_NAME) . '/assets/images/cards.png';

        parent::__construct();

        $this->gateways = $this->mf->getVendorGatewaysByType(true);
        $this->count    = count($this->gateways);

        $this->has_fields = true;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Initialize Gateway Settings Form Fields.
     */
    function init_form_fields() {
        $this->form_fields = include(MYFATOORAH_WOO_PLUGIN_PATH . 'includes/admin/payment.php' );
        unset($this->form_fields['listOptions']);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Process the payment and return the result.
     *
     * @param int $orderId
     * @return array
     */
    public function process_payment($orderId) {

        $cardInfo = [
            'PaymentType' => 'card',
            'Bypass3DS'   => 'false',
            'Card'        => [
                'CardHolderName' => $this->get_post('cardHolderName'),
                'Number'         => str_replace(array(' ', '-'), '', $this->get_post('ccnum')),
                'ExpiryMonth'    => $this->get_post('expmonth'),
                'ExpiryYear'     => substr($this->get_post('expyear'), -2),
                'SecurityCode'   => $this->get_post('cvv'),
            ]
        ];


        $curlData = $this->getPayLoadData($orderId);

        $gateway = ($this->count == 1) ? $this->gateways[0]->PaymentMethodId : $this->get_post('cctype');

        $data = $this->mf->directPayment($orderId, $curlData, $gateway, $cardInfo);

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
        if ($this->count == 0) {
            $error = __('Direct Payment Methods are not activated. Kindly, contact your MyFatoorah account manager or sales representative to activate it.', 'myfatoorah-woocommerce');
        }

        include_once(MYFATOORAH_WOO_PLUGIN_PATH . 'templates/paymentFieldsDirect.php');
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Return the gateway's title.
     *
     * @return string
     */
    public function get_title() {

        if ($this->count == 1) {
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

        if ($this->count == 1) {
            $icon = '<img src="' . $this->gateways[0]->ImageUrl . '" alt="' . esc_attr($this->get_title()) . '" style="margin: 0px; width: 50px; height: 30px;"/>';
        } else {
            $icon = $this->icon ? '<img src="' . WC_HTTPS::force_https_url($this->icon) . '" alt="' . esc_attr($this->get_title()) . '" />' : '';
        }

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Check payment details for valid format
     *
     * @return bool
     */
    function validate_fields() {

        if (!wc_checkout_is_https()) {
            wc_add_notice(__('Myfatoorah forces SSL checkout Payemnt. Your checkout is not secure! Please, contact site admin to enable SSL and ensure that the server has a valid SSL certificate.', 'myfatoorah-woocommerce'), 'error');
            return false;
        }

        $card_number           = str_replace(array(' ', '-'), '', $this->get_post('ccnum'));
        $card_csc              = $this->get_post('cvv');
        $card_expiration_month = $this->get_post('expmonth');
        $card_expiration_year  = $this->get_post('expyear');

        // Check card number
        if (empty($card_number) || !ctype_digit($card_number)) {
            wc_add_notice(__('Card number is invalid.', 'myfatoorah-woocommerce'), 'error');
            return false;
        }

        // Check security code
        if (!ctype_digit($card_csc)) {
            wc_add_notice(__('Card security code is invalid (only digits are allowed).', 'myfatoorah-woocommerce'), 'error');
            return false;
        }

        if (strlen($card_csc) != 3 && strlen($card_csc) != 4) {
            wc_add_notice(__('Card security code is invalid (wrong length).', 'myfatoorah-woocommerce'), 'error');
            return false;
        }

        // Check expiration data
        $current_year = date('Y');
        if (!ctype_digit($card_expiration_month) || !ctype_digit($card_expiration_year) ||
                $card_expiration_month > 12 || $card_expiration_month < 1 ||
                $card_expiration_year < $current_year || $card_expiration_year > $current_year + 20) {

            wc_add_notice(__('Card expiration date is invalid', 'myfatoorah-woocommerce'), 'error');
            return false;
        }


        return true;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
