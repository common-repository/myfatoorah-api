<?php

if (!defined('WPINC')) {
    die;
}

if (!class_exists('ShippingMyfatoorahApiV2')) {
    include_once('includes/libraries/ShippingMyfatoorahApiV2.php');
}

//-----------------------------------------------------------------------------------------------------------------------------------------
// Hook in
//add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');
//
//// Our hooked in function - $fields is passed via the filter!
//function custom_override_checkout_fields($fields) {
//    $fields['billing']['billing_city']['required']   = false;
//    $fields['shipping']['shipping_city']['required'] = false;
//    return $fields;
//}
//-----------------------------------------------------------------------------------------------------------------------------------------
// Change "city" checkout billing and shipping fields to a dropdown
//-----------------------------------------------------------------------------------------------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------------------------------
// add the filter 

class MyfatoorahWoocommerceShipping {
//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_get_cities', [$this, 'get_cities'], 1);
        add_action('wp_ajax_nopriv_get_cities', [$this, 'get_cities'], 1);
        add_filter('woocommerce_checkout_fields', [$this, 'get_cities_first_time']);

        add_filter('woocommerce_shipping_methods', [$this, 'add_woocommerce_shipping_methods']);
        add_action('wp_enqueue_scripts', [$this, 'my_enqueue']);
        add_filter('woocommerce_available_payment_gateways', [$this, 'disable_shipping']);

        # add this in your plugin file and that's it, the calculate_shipping method of your shipping plugin class will be called again
        add_action('woocommerce_checkout_update_order_review', [$this, 'action_woocommerce_checkout_update_order_review']);
        add_filter('woocommerce_update_cart_action_cart_updated', [$this, 'clear_notices_on_cart_update'], 10, 1);
        add_action('woocommerce_shipping_init', [$this, 'woocommerce_shipping_init']);
        add_filter('plugin_action_links_' . MYFATOORAH_WOO_PLUGIN, [$this, 'plugin_action_links']);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function woocommerce_shipping_init() {
        if (!class_exists('WC_Shipping_Myfatoorah')) {
            include_once('includes/shipping/class-wc-shipping-myfatoorah.php');
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Add links to plugins page for settings and documentation
     * @param array $links
     * @return array
     */
    function plugin_action_links($links) {

        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&section=myfatoorah_shipping') . '">' . __('Shipping', 'woocommerce') . '</a>',
        );
//        return array_merge($plugin_links, $links);
        return array_merge($links, $plugin_links);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function add_woocommerce_shipping_methods($methods) {
        $methods[] = 'WC_Shipping_Myfatoorah';
        return $methods;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * @snippet       Disable Other Payment Gateway For MyFatoorah Shipping Method
     */
    function disable_shipping($available_gateways) {
        if (!is_admin() && isset(WC()->session)) {

            $chosen_methods = WC()->session->get('chosen_shipping_methods');

	    $chosen_shipping = isset($chosen_methods[0]) ? $chosen_methods[0] : null;
            if ($chosen_shipping == 'MyFatoorah DHL Shipping' || $chosen_shipping == 'MyFatoorah Aramex Shipping') {
                foreach ($available_gateways as $key => $val) {
                    if ($key != 'myfatoorah_v2' && $key != 'myfatoorah_direct') {
                        unset($available_gateways[$key]);
                    }
                }
            }
        }

        return $available_gateways;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function action_woocommerce_checkout_update_order_review() {
        wc_clear_notices();
        WC()->cart->calculate_shipping();
        return;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    //clear notices on cart update
    function clear_notices_on_cart_update() {
        wc_clear_notices();
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function get_cities() {

        if (!class_exists('WC_Shipping_Myfatoorah')) {
            include_once('includes/shipping/class-wc-shipping-myfatoorah.php');
        }
        $myfatoorahShipping = new WC_Shipping_Myfatoorah();


        if ($myfatoorahShipping->enabled === 'no') {
            die();
        }

        // this cond. for ajax call
        try {
            if(!$_REQUEST['country_code']){
                die();
            }
            
            $cities = $myfatoorahShipping->getCities(sanitize_text_field($_REQUEST['country_code']));
            die(json_encode($cities));
        } catch (Exception $ex) {
            die(json_encode($ex->getMessage()));
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function get_cities_first_time($fields) {

        // Define here in the array your desired cities (Here an example of cities)
        if (!class_exists('WC_Shipping_Myfatoorah')) {
            include_once('includes/shipping/class-wc-shipping-myfatoorah.php');
        }
        $mfShippingObj = new WC_Shipping_Myfatoorah();

        if ($mfShippingObj->enabled === 'no') {
            return $fields;
        }

        // this for first load of page
        $shippingCC = WC()->customer->get_shipping_country();
        $billingCC  = WC()->customer->get_billing_country();

        try {
            $fields['billing']['billing_city']   = $fields['shipping']['shipping_city'] = $this->get_city_args($shippingCC, $mfShippingObj, $fields['shipping']['shipping_city']);
            if ($billingCC != $shippingCC) {
                $fields['billing']['billing_city'] = $this->get_city_args($billingCC, $mfShippingObj, $fields['billing']['billing_city']);
            }
        } catch (Exception $ex) {
            wc_add_notice(__('Kindly, review your Myfatoorah admin configuration due to a wrong entry to get Myfatoorah shipping cities.', 'myfatoorah-woocommerce'), 'notice');
        }
        return $fields;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function get_city_args($countryCode, $mfShippingObj, $field) {
        $cities = $mfShippingObj->getCities($countryCode);
        if (empty($cities)) {
            return wp_parse_args(array('type' => 'text'), $field);
        } else {
            $option_cities = array_combine($cities, $cities);
            return wp_parse_args(array('type' => 'select', 'options' => $option_cities), $field);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function my_enqueue() {
        wp_enqueue_script('ajax-script', plugins_url('/assets/js/cities.js', __FILE__), array('jquery'));
        wp_localize_script('ajax-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
