<?php

/**
 * Settings for MyFatoorah Gateway.
 */
return array(
    'enabled'     => array(
        'title'   => __('Enable/Disable', 'woocommerce'),
        'type'    => 'checkbox',
        'default' => 'no',
        'label'   => __('Enable MyFatoorah', 'myfatoorah-woocommerce'),
    ),
    'title'       => array(
        'title'       => __('Title', 'woocommerce'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => __($this->method_title, 'myfatoorah-woocommerce'), //todo trans
    ),
    'description' => array(
        'title'       => __('Description', 'woocommerce'),
        'type'        => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => __('Checkout with MyFatoorah Payment Gateway', 'myfatoorah-woocommerce'),
    ),
    'testMode'    => array(
        'title'       => __('Test Mode', 'myfatoorah-woocommerce'),
        'type'        => 'checkbox',
        'description' => __('Enable test / sandbox Mode', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => 'yes',
        'label'       => __('Enable Test Mode', 'myfatoorah-woocommerce'),
    ),
    'apiKey'      => array(
        'title'       => __('API Key', 'myfatoorah-woocommerce'),
        'type'        => 'textarea',
        'description' => __('Get your API Token Key from Myfatoorah Vendor Account.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
    ),
    'listOptions' => array(
        'title'       => __('List Payment Options', 'myfatoorah-woocommerce'),
        'type'        => 'select',
        'description' => __('MyFatoorah is default gateway. You can select one of below payment gateway which the user can checkout directly from it.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => 'myfatoorah',
        'options'     => [
            'myfatoorah'    => __('Myfatoorah Invoice Page (Redirect)', 'myfatoorah-woocommerce'),
            'multigateways' => __('List All Enabled Gateways in Checkout Page', 'myfatoorah-woocommerce'),
        ],
    ),
    'orderStatus' => array(
        'title'       => __('Order Status', 'woocommerce'),
        'type'        => 'select',
        'description' => __('How to mark the successful payment in the Admin Orders Page.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => 'processing',
        'options'     => array(
            'processing' => __('Processing', 'woocommerce'),
            'completed'  => __('Completed', 'woocommerce'),
        ),
    ),
    'success_url' => array(
        'title'       => __('Payment Success URL', 'myfatoorah-woocommerce'),
        'type'        => 'text',
        'description' => __('Please insert your Success url.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => '',
        'placeholder' => 'https://www.example.com/success',
    ),
    'fail_url'    => array(
        'title'       => __('Payment Fail URL', 'myfatoorah-woocommerce'),
        'type'        => 'text',
        'description' => __('Please insert your Fail url.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => '',
        'placeholder' => 'https://www.example.com/failed',
    ),
    'debug'       => array(
        'title'       => __('Debug Mode', 'myfatoorah-woocommerce'),
        'type'        => 'checkbox',
        'description' => __('Log MyFatoorah events in ', 'myfatoorah-woocommerce') . $this->pluginlog,
        'desc_tip'    => true,
        'default'     => 'yes',
        'label'       => __('Enable logging', 'myfatoorah-woocommerce'),
    ),
    'icon'        => array(
        'title'       => __('MyFatoorah Logo URL', 'myfatoorah-woocommerce'),
        'type'        => 'text',
        'description' => __('Please insert your logo url which the user sees during checkout.', 'myfatoorah-woocommerce'),
        'desc_tip'    => true,
        'default'     => $this->defaultIcon,
        'placeholder' => 'https://www.exampleurl.com',
    ),
);
