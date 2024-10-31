<?php

if (class_exists('WC_Shipping_Myfatoorah')) {
    return;
}

class WC_Shipping_Myfatoorah extends WC_Shipping_Method {

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id                 = 'myfatoorah_shipping';
        $this->method_title       = __('MyFatoorah Shipping API Ver 2.0', 'myfatoorah-woocommerce');
        $this->method_description = __('Custom Shipping Method for myfatoorah', 'myfatoorah-woocommerce');
        // Actions
        $this->pluginlog          = WC_LOG_DIR . $this->id . '.log';
        $this->init();
        //to stop taxes rate
        $this->tax_status         = false;
        $this->enabled            = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title              = isset($this->settings['title']) ? $this->settings['title'] : __('MyFatoorah Shipping API Ver 2.0', 'myfatoorah-woocommerce');
        $this->init_settings();
        //enabled, title, description, apiKey, testMode, listOptions, orderStatus, success_url, fail_url, debug, icon, 
        foreach ($this->settings as $key => $val) {
            $this->$key = $val;
        }

        if ($this->enabled == 'yes' && is_checkout()) {
            if ('yes' === $this->debug) {
                $this->mf = new ShippingMyfatoorahApiV2($this->api_key, ($this->test_mode === 'yes'), $this->pluginlog);
            } else {
                $this->mf = new ShippingMyfatoorahApiV2($this->api_key, ($this->test_mode === 'yes'));
            }
        }
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init() {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();
        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Define settings field for this shipping
     * @return void 
     */
    function init_form_fields() {
        $this->form_fields = include(MYFATOORAH_WOO_PLUGIN_PATH . 'includes/admin/shipping.php' );
    }

    public function getCities($countryCode) {
        $exe_ship_countries = $this->get_option('exe_ship_countries');

        if (!empty($exe_ship_countries) && (false !== array_search($countryCode, $exe_ship_countries))) {
            return array();
        }

        // get MyFatoorah DHL Cities
        if ('yes' === $this->debug) {
            $mf = new ShippingMyfatoorahApiV2($this->api_key, ($this->test_mode === 'yes'), $this->pluginlog);
        } else {
            $mf = new ShippingMyfatoorahApiV2($this->api_key, ($this->test_mode === 'yes'));
        }

        if (empty($this->shipping)) {
            throw new Exception(__('Kindly, review your Myfatoorah admin configuration due to a wrong entry to get Myfatoorah shipping cities.', 'myfatoorah-woocommerce'));
        }

        $cities = [];
        foreach ($this->shipping as $value) {
            $shippingCities = $mf->getShippingCities($value, $countryCode);
            if (!empty($shippingCities->Data->CityNames)) {
                $cities = array_unique(array_merge($cities, $shippingCities->Data->CityNames));
            }
        }
        array_unshift($cities, __('Select Town / City', 'myfatoorah-woocommerce'));
        return $cities;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package = array()) {
        if ($this->enabled == 'no' || empty($this->shipping)) {
            return [];
        }

        $exe_ship_countries = $this->get_option('exe_ship_countries');
        if (!empty($exe_ship_countries) && (false !== array_search($package['destination']['country'], $exe_ship_countries))) {
            return [];
        }

        if (!$package['destination']['city']) {
            return [];
        }

        try {
            $mf = new ShippingMyfatoorahApiV2($this->settings['api_key'], ($this->settings['test_mode'] === 'yes'));

            $weightRate    = $mf->getWeightRate(get_option('woocommerce_weight_unit'));
            $dimensionRate = $mf->getDimensionRate(get_option('woocommerce_dimension_unit'));

            $invoiceItemsArr = array();
            foreach ($package['contents'] as $item) {
                $product = wc_get_product($item['product_id']);
                if (!$product->get_weight() || !$product->get_width() || !$product->get_height() || !$product->get_length()) {
                    wc_add_notice(__('Please make sure products have dimentions and wieght as well to get right Myfatoorah Shipping rates.', 'myfatoorah-woocommerce'), 'error');
                    return [];
                }

                $invoiceItemsArr[] = array(
                    'ProductName' => $product->get_title(),
                    "Description" => ($product->get_description()) ?: $product->get_title(),
                    'weight'      => (float) $product->get_weight() * $weightRate,
                    'Width'       => (float) $product->get_width() * $dimensionRate,
                    'Height'      => (float) $product->get_height() * $dimensionRate,
                    'Depth'       => (float) $product->get_length() * $dimensionRate,
                    'Quantity'    => $item['quantity'],
                    'UnitPrice'   => $product->get_price(),
                );
            }


            $wooCurrency  = get_woocommerce_currency();
            $currencyRate = $mf->getCurrencyRate($wooCurrency);

            foreach ($this->shipping as $sh_method) {

                $methodName   = ($sh_method == 2) ? __('MyFatoorah Aramex Shipping', 'myfatoorah-woocommerce') : __('MyFatoorah DHL Shipping', 'myfatoorah-woocommerce');
                $shippingData = array(
                    'ShippingMethod' => $sh_method,
                    'Items'          => $invoiceItemsArr,
                    'CountryCode'    => $package['destination']['country'],
                    'CityName'       => $package['destination']['city'],
                    'PostalCode'     => $package['destination']['postcode'],
                );

                $shippingInfo = $mf->calculateShippingCharge($shippingData);
                if (isset($shippingInfo->Data->Fees) && $shippingInfo->Data->Fees != 0) {

                    $rate = array(
                        'id'        => $methodName,
                        'label'     => $methodName, //todo method display name is var can be changed by usrs
                        'cost'      => round($shippingInfo->Data->Fees * $currencyRate, 2),
                        'meta_data' => array(),
                    );
                    $this->add_rate($rate);
                }
            }
        } catch (Exception $ex) {
            wc_add_notice($ex->getMessage(), 'error');
            return [];
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
