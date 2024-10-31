<?php

/**
 * Settings for MyFatoorah Gateway.
 */
$countries_obj = new WC_Countries();
$countries     = $countries_obj->get_allowed_countries();
return array(
    'enabled'            => array(
        'title'   => __('Enable/Disable', 'woocommerce'),
        'type'    => 'checkbox',
        'default' => 'no',
        'label'   => __('Enable MyFatoorah Shipping', 'myfatoorah-woocommerce'),
    ),
    'title_sh'           => array(
        'title'       => __('Title', 'woocommerce'),
        'type'        => 'text',
        'description' => __('Title to be display on site', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => __('Myfatoorah Shipping', 'myfatoorah-woocommerce'),
    ),
    'test_mode'          => array(
        'title' => __('Test Mode', 'myfatoorah-woocommerce'),
        'type'  => 'checkbox',
        'description' => __('Enable test / sandbox Mode', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => 'yes',
        'label'       => __('Enable Test Mode', 'myfatoorah-woocommerce'),
    ),
    'api_key'            => array(
        'title'       => __('API Key', 'myfatoorah-woocommerce'),
        'type'        => 'textarea',
        'description' => __('Get your API Token Key from Myfatoorah Vendor Account.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
    ),
    'debug'              => array(
        'title'       => __('Debug Mode', 'myfatoorah-woocommerce'),
        'type'        => 'checkbox',
        'description' => __('Log MyFatoorah events in ', 'myfatoorah-woocommerce') . $this->pluginlog,
        'desc_tip'    => true,
        'default'     => 'yes',
        'label'       => __('Enable logging', 'myfatoorah-woocommerce'),
    ),
    'shipping'           => array(
        'title'   => __('Enable DHL / Aramex', 'myfatoorah-woocommerce'),
        'type'    => 'multiselect',
        'label'   => __('DHL / Aramex', 'myfatoorah-woocommerce'),
        'options' => array(1 => 'DHL', 2 => 'Aramex'),
    ),
    'exe_ship_countries' => array(
        'title'       => __('Exclude countries from shipping rates', 'myfatoorah-woocommerce'),
        'type'        => 'multiselect',
        'description' => __('Exclude countries from MyFatoorah shipping rates.', 'myfatoorah-woocommerce'),
        'default'     => '',
        'desc_tip'    => true,
        'options'     => $countries,
    ),
);
