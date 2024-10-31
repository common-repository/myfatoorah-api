<?php

/**
 * Plugin Name: Myfatoorah - WooCommerce
 * Plugin URI: https://myfatoorah.readme.io/docs/woocommerce
 * Description: Myfatoorah Payment Gateway for WooCommerce. Integrated with Myfatoorah DHL/Aramex Shipping Methods
 * Version: 2.0.0
 * Tested up to: 5.6.2
 * Author: MyFatoorah
 * Author URI: https://www.myfatoorah.com/
 * 
 * Text Domain: myfatoorah-woocommerce
 * Domain Path: /i18n/languages/
 *
 * WC requires at least: 4.0
 * WC tested up to: 5.0.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * @package Myfatoorah
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MyfatoorahWoocommercePayment')) {
    require_once 'myfatoorah-woocommerce-payment.php';
}
if (!class_exists('MyfatoorahWoocommerceShipping')) {
    require_once 'myfatoorah-woocommerce-shipping.php';
}

//MFWOO_PLUGIN
define('MYFATOORAH_WOO_PLUGIN', plugin_basename(__FILE__));
define('MYFATOORAH_WOO_PLUGIN_NAME', dirname(MYFATOORAH_WOO_PLUGIN));
define('MYFATOORAH_WOO_PLUGIN_PATH', plugin_dir_path(__FILE__));

new MyfatoorahWoocommercePayment('v2');
new MyfatoorahWoocommercePayment('direct');
new MyfatoorahWoocommerceShipping();