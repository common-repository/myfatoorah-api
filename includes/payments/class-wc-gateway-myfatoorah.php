<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('PaymentMyfatoorahApiV2')) {
    include_once( MYFATOORAH_WOO_PLUGIN_PATH . 'includes/libraries/PaymentMyfatoorahApiV2.php' );
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
class WC_Gateway_Myfatoorah extends WC_Payment_Gateway {
//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     */
    public function __construct() {

        $this->id           = 'myfatoorah_' . $this->code;
        $this->method_title = __('Myfatoorah - ' . $this->code, 'myfatoorah-woocommerce'); //todo translation note or replace

        $this->lang     = get_bloginfo("language");
        //this will appeare in the setting details page. For more customize page you override function admin_options()
        //todo translation file 
        $this->supports = array(
            'products',
            'refunds',
        );
        //Get setting values
        $this->init_settings();
        //enabled, title, description, apiKey, testMode, listOptions, orderStatus, success_url, fail_url, debug, icon, 
        foreach ($this->settings as $key => $val) {
            $this->$key = $val;
        }
        $this->pluginlog = WC_LOG_DIR . $this->id . '.log';
        if ('yes' === $this->debug) {
            $this->mf = new PaymentMyfatoorahApiV2($this->apiKey, ($this->testMode === 'yes'), $this->pluginlog);
        } else {
            $this->mf = new PaymentMyfatoorahApiV2($this->apiKey, ($this->testMode === 'yes'));
        }
        //Create plugin admin fields
        $this->init_form_fields();
        //Add hooks
        //save admin setting action
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Initialize Gateway Settings Form Fields.
     */
    function init_form_fields() {
        $this->form_fields = include(MYFATOORAH_WOO_PLUGIN_PATH . 'includes/admin/payment.php' );
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Process a refund if supported
     *
     * @param  int $orderId
     * @param  float $amount
     * @param  string $reason
     * @return  bool|wp_error True or false based on success, or a WP_Error object
     */
    public function process_refund($orderId, $amount = null, $reason = '') {

        if (!$paymentId = get_post_meta($orderId, 'PaymentId', true)) {
            return new WP_Error('mfMakeRefund', __('Please, refund manually for this order', 'myfatoorah-woocommerce'));
        }

        $order        = wc_get_order($orderId);
        $currencyCode = $order->get_currency();

        $json = $this->mf->refund($paymentId, $amount, $currencyCode, $reason, $orderId);

        // Success
        update_post_meta($orderId, 'RefundReference', $json->Data->RefundReference);
        update_post_meta($orderId, 'RefundAmount', $json->Data->Amount);


        $order->add_order_note(__('Myfatoorah refund completed. Refund Reference ID: ', 'myfatoorah-woocommerce') . $json->Data->RefundReference);
        return true; //imp
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function getPayLoadData($orderId) {
        $order = new WC_Order($orderId);

        $fName = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_first_name : $order->get_billing_first_name();
        if (!$fName) {
            $fName = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
        }

        $lname = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_last_name : $order->get_billing_last_name();
        if (!$lname) {
            $lname = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
        }

        //phone & email are not exist in shipping address!!
        $email = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_email : $order->get_billing_email();
        if (empty($email)) {
            $email = 'noreply@myfatoorah.com';
        }

        $phone    = version_compare(WC_VERSION, '3.0.0', '<') ? $order->billing_phone : $order->get_billing_phone();
        $phoneArr = $this->mf->getPhone($phone);

        $civilId = get_post_meta($order->get_id(), 'billing_cid', true);

        //get $expiryDate
        $expiryDate = '';
        if (class_exists('WC_Admin_Settings')) {

            $date        = new DateTime('now', new DateTimeZone('Asia/Kuwait'));
            $currentDate = $date->format('Y-m-d\TH:i:s');

            $woocommerce_hold_stock_minutes = WC_Admin_Settings::get_option('woocommerce_hold_stock_minutes') ?: 60;

            $expires    = strtotime("$currentDate + $woocommerce_hold_stock_minutes minutes");
            $expiryDate = date('Y-m-d\TH:i:s', $expires);
        }

        //set multiused vars
        $sucess_url = $order->get_checkout_order_received_url();
        //$err_url = $order->get_cancel_order_url_raw();
        //$err_url = wc_get_checkout_url();


        $currencyIso = $order->get_currency();
        //if the WPML is accivate (need better sol????????)
//        if ($currencyIso = 'CLOUDWAYS') {
//            $currencyIso = get_woocommerce_currency_symbol($currencyIso);
//        }


        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $shipingMethod  = ($chosen_methods[0] == __("MyFatoorah DHL Shipping", 'myfatoorah-woocommerce')) ? 1 : (($chosen_methods[0] == __("MyFatoorah Aramex Shipping", 'myfatoorah-woocommerce')) ? 2 : null);

//        $amount       = version_compare(WC_VERSION, '3.0.0', '<') ? $order->order_total : $order->get_total();
//        $invoiceItems = [['ItemName' => 'Total amount', 'Quantity' => 1, 'UnitPrice' => "$amount"]];

        $amount       = 0;
        $invoiceItems = $this->getInvoiceItems($order, $amount, $shipingMethod);


        //$address = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
        //$city = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_city : $order->get_shipping_city();
        //$country = version_compare(WC_VERSION, '3.0.0', '<') ? $order->shipping_country : $order->get_shipping_country();

        $address = WC()->customer->get_shipping_address_1() . ' ' . WC()->customer->get_shipping_address_2();

        // custom fields
        /* if(empty($address)){
          $block = get_post_meta( $order->get_id(), 'billing_block', true );
          $street = get_post_meta( $order->get_id(), 'billing_street', true );
          $gada = get_post_meta( $order->get_id(), 'billing_gada', true );
          $house = get_post_meta( $order->get_id(), 'billing_house', true );
          $address =$block. ' , ' .$street . ' , '. $house. ' , '. $gada ;
          } */

        $customerAddress = array(
            'Block'               => 'string',
            'Street'              => 'string',
            'HouseBuildingNo'     => 'string',
            'Address'             => $address,
            'AddressInstructions' => 'string'
        );


        $shippingConsignee = array(
            'PersonName'   => "$fName $lname",
            'Mobile'       => $phoneArr[1],
            'EmailAddress' => $email,
            'LineAddress'  => $address,
            'CityName'     => WC()->customer->get_shipping_city(),
            'PostalCode'   => WC()->customer->get_shipping_postcode(),
            'CountryCode'  => WC()->customer->get_shipping_country()
        );


        return [
            'CustomerName'       => "$fName $lname",
            'DisplayCurrencyIso' => $currencyIso,
            'MobileCountryCode'  => $phoneArr[0],
            'CustomerMobile'     => $phoneArr[1],
            'CustomerEmail'      => $email,
            'InvoiceValue'       => "$amount",
            'CallBackUrl'        => $sucess_url,
            'ErrorUrl'           => $sucess_url,
            'Language'           => ($this->lang == 'ar') ? 'ar' : 'en',
            'CustomerReference'  => $orderId,
            'CustomerCivilId'    => $civilId,
            'UserDefinedField'   => $orderId,
            'ExpiryDate'         => $expiryDate,
            'SourceInfo'         => 'WooCommerce ' . WC_VERSION . ' - ' . $this->id,
            'CustomerAddress'    => $customerAddress,
            'ShippingConsignee'  => ($shipingMethod) ? $shippingConsignee : null,
            'ShippingMethod'     => $shipingMethod,
            'InvoiceItems'       => $invoiceItems,
        ];
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function getInvoiceItems($order, &$amount, $shipingMethod) {

        $weightRate    = $this->mf->getWeightRate(get_option('woocommerce_weight_unit'));
        $dimensionRate = $this->mf->getDimensionRate(get_option('woocommerce_dimension_unit'));

        $invoiceItemsArr = array();

        $items = $order->get_items();
        foreach ($items as $item) {
            $product = wc_get_product($item->get_product_id());

            $ptice1            = $item->get_subtotal() + (wc_prices_include_tax() ? $item->get_subtotal_tax() : 0);
            $price             = round($ptice1 / $item->get_quantity(), 2);
            $amount            += $item->get_quantity() * $price;
            $invoiceItemsArr[] = [
                'ItemName'  => $item->get_name(),
                'Quantity'  => $item->get_quantity(),
                'UnitPrice' => "$price",
                'weight'    => (float) ($product->get_weight()) * $weightRate,
                'Width'     => (float) ($product->get_width()) * $dimensionRate,
                'Height'    => (float) ($product->get_height()) * $dimensionRate,
                'Depth'     => (float) ($product->get_length()) * $dimensionRate,
            ];
        }
//todo no need for translation or leave it if normal payment
        $discount = round($order->get_total_discount(), 2);
        if ($discount > 0) {
            $amount            -= $discount;
            $invoiceItemsArr[] = ['ItemName' => __('Discount', 'myfatoorah-woocommerce'), 'Quantity' => '1', 'UnitPrice' => "-$discount", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];
        }


        $shipping1 = $order->get_total_shipping() + ((wc_prices_include_tax()) ? $order->get_shipping_tax() : 0);
        $shipping  = round($shipping1, 2);
        if ($shipping > 0 && $shipingMethod === null) {
            $amount            += $shipping;
            $invoiceItemsArr[] = ['ItemName' => __('Shipping', 'myfatoorah-woocommerce'), 'Quantity' => '1', 'UnitPrice' => "$shipping", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];
        }


        $fees = round($order->get_total_fees(), 2);
        if ($fees > 0) {
            $amount            += $fees;
            $invoiceItemsArr[] = ['ItemName' => __('Fees', 'myfatoorah-woocommerce'), 'Quantity' => '1', 'UnitPrice' => "$fees", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];
        }


        if (!wc_prices_include_tax()) {
            $tax = round($order->get_total_tax(), 2);
            if ($tax > 0) {
                $amount            += $tax;
                $invoiceItemsArr[] = ['ItemName' => __('Taxes', 'woocommerce'), 'Quantity' => '1', 'UnitPrice' => "$tax", 'Weight' => '0', 'Width' => '0', 'Height' => '0', 'Depth' => '0'];
            }
        }

        return $invoiceItemsArr;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    public function updatePostMeta($orderId, $json) {
        $InvoiceTransactions    = count($json->Data->InvoiceTransactions);
        $InvoiceTransactionsArr = $json->Data->InvoiceTransactions[$InvoiceTransactions - 1];
        update_post_meta($orderId, 'InvoiceValue', $json->Data->InvoiceValue);
        update_post_meta($orderId, 'CreatedDate', $json->Data->CreatedDate);
        update_post_meta($orderId, 'InvoiceDisplayValue', $json->Data->InvoiceDisplayValue);
        update_post_meta($orderId, 'PaymentGateway', $InvoiceTransactionsArr->PaymentGateway);
        update_post_meta($orderId, 'PaidCurrency', $InvoiceTransactionsArr->PaidCurrency);
        update_post_meta($orderId, 'PaymentId', $InvoiceTransactionsArr->PaymentId);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    public function addOrderNote($orderId, $json) {
        $InvoiceTransactions    = count($json->Data->InvoiceTransactions);
        $InvoiceTransactionsArr = $json->Data->InvoiceTransactions[$InvoiceTransactions - 1];
        $order                  = wc_get_order($orderId);
        $note                   = 'Payment Details : <br> ';
        $note                   .= 'Gateway : ' . $InvoiceTransactionsArr->PaymentGateway . '<br>';
        $note                   .= 'PaymentId : ' . $InvoiceTransactionsArr->PaymentId . '<br>';
        $note                   .= 'InvoiceValue :' . $json->Data->InvoiceValue . '<br>';
        $note                   .= 'InvoiceDisplayValue : ' . $json->Data->InvoiceDisplayValue . '<br>';
        $note                   .= 'PaidCurrency : ' . $InvoiceTransactionsArr->PaidCurrency . '<br>';
        $note                   .= 'Date : ' . $json->Data->CreatedDate . '<br>';
        $order->add_order_note($note);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get post data if set
     *
     * @param string $name
     * @return string|null
     */
    protected function get_post($name) {
        if (isset($_POST[$name])) {
            return sanitize_text_field($_POST[$name]);
        }
        return null;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
