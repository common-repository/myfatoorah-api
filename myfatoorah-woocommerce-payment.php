<?php

/**
 * MyFatoorah WooCommerce Class
 */
class MyfatoorahWoocommercePayment {
//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     */
    public function __construct($code) {

        $this->code    = $code;
        $this->id      = 'myfatoorah_' . $code;
        $this->gateway = 'WC_Gateway_Myfatoorah_' . $code;

        //filters
        add_filter('woocommerce_payment_gateways', array($this, 'register'), 0);
        add_filter('plugin_action_links_' . MYFATOORAH_WOO_PLUGIN, array($this, 'plugin_action_links'));
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        add_filter('cron_schedules', array($this, 'myfatoorah_add_cron_interval'));

        //actions
        add_action('activate_plugin', array($this, 'superessActivate'), 0);
        add_action('plugins_loaded', array($this, 'init'), 0);
        add_action('wpb_custom_cron', array($this, 'check_pending_payments'));

        //add_action('woocommerce_before_thankyou', array($this, 'getPaymentStatus'), 0);
        add_action('template_redirect', array($this, 'getPaymentStatus'), 0);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Register the gateway to WooCommerce
     */
    public function register($gateways) {
        $gateways[] = $this->gateway;
        return $gateways;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Show action links on the plugin screen.
     *
     * @param mixed $links Plugin Action links.
     *
     * @return array
     */
    public function plugin_action_links($links) {
        //http://wordpress-5.4.2.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=$this->id
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_' . $this->id) . '">' . __(ucfirst($this->code), 'woocommerce') . '</a>',
        );
//        return array_merge($plugin_links, $links);
        return array_merge($links, $plugin_links);
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Show row meta on the plugin screen.
     *
     * @param mixed $links Plugin Row Meta.
     * @param mixed $file  Plugin Base file.
     *
     * @return array
     */
    public static function plugin_row_meta($links, $file) {

        if (MYFATOORAH_WOO_PLUGIN === $file) {
            $row_meta = array(
                'docs'    => '<a href="' . esc_url('https://myfatoorah.readme.io/docs/woocommerce') . '" aria-label="' . esc_attr__('View MyFatoorah documentation', 'myfatoorah-woocommerce') . '">' . esc_html__('Docs', 'woocommerce') . '</a>',
                'apidocs' => '<a href="' . esc_url('https://myfatoorah.readme.io/docs') . '" aria-label="' . esc_attr__('View MyFatoorah API docs', 'myfatoorah-woocommerce') . '">' . esc_html__('API docs', 'woocommerce') . '</a>',
                'support' => '<a href="' . esc_url('https://myfatoorah.com/contact.html') . '" aria-label="' . esc_attr__('Visit premium customer support', 'myfatoorah-woocommerce') . '">' . esc_html__('Premium support', 'woocommerce') . '</a>',
            );

            unset($links[2]);
            return array_merge($links, $row_meta);
        }

        return (array) $links;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function superessActivate($plugin) {

        //it is very important to say that the plugin is MyFatoorah 
        if ($plugin == MYFATOORAH_WOO_PLUGIN && !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $err = 'WooCommerce plugin needs to be activated first to activate Myfatoorah plugin';
            wp_die($err);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Init localizations and files
     */
    public function init() {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        // Includes
        include_once("includes/payments/class-wc-gateway-myfatoorah-$this->code.php");

        // Localisation
        load_plugin_textdomain('myfatoorah-woocommerce', false, MYFATOORAH_WOO_PLUGIN_NAME . '/i18n/languages');
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    public function getPaymentStatus() {

        global $wp;
        $orderId = (is_checkout() && !empty($wp->query_vars['order-received'])) ? $wp->query_vars['order-received'] : null;

        if (!$orderId) {
            return;
        }

        $order = new WC_Order($orderId);
        if ($order->get_payment_method() != $this->id) {
            return;
        }

        //processing    completed
        $status = $order->get_status();
        if ($status == 'processing' || $status == 'completed') {
            return;
        }

        //get Payment Id
        $KeyType = 'PaymentId';
        $key     = isset($_GET['paymentId']) ? sanitize_text_field($_GET['paymentId']) : null;
        if (!$key) {
            $KeyType = 'InvoiceId';
            $key     = get_post_meta($orderId, 'InvoiceId', true);
            if (!$key) {
                return;
            }
        }


        // When "thankyou" order-received page is reached …
        $gateway = new $this->gateway;
        $logMsg  = PHP_EOL . date('d.m.Y h:i:s') . " - Order #$orderId ----- Get Payment Status - Result:";

        try {

            $json = $gateway->mf->getPaymentStatus($key, $KeyType, $orderId);

            $mfStatus = $gateway->get_option('orderStatus');

            $order->update_status($mfStatus);
            $order->save();

            $gateway->updatePostMeta($orderId, $json);
            $gateway->addOrderNote($orderId, $json);

            error_log("$logMsg status is changed from $status to $mfStatus", 3, $gateway->pluginlog);

            if ($gateway->get_option('success_url')) {
                wp_redirect($gateway->get_option('success_url'));
                exit();
            }
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            error_log("$logMsg status is: $msg", 3, $gateway->pluginlog);

            if ($msg != 'Payment is pending') {
                $order->update_status('failed', $msg);
                $order->save();
            }

            if ($gateway->get_option('fail_url')) {
                wp_redirect($gateway->get_option('fail_url') . '?error=' . urlencode($msg));
                exit();
            } else if (is_user_logged_in()) {
                wc_add_notice($msg, 'error');
                wp_redirect(wc_get_checkout_url());
                exit();
            }

            $this->msg = $msg;
            add_action('woocommerce_before_thankyou', array($this, 'woo_change_order_received_text'), 10, 1);
        }
    }

    function woo_change_order_received_text($order) {
        echo '<ul class="woocommerce-error"><li>' . $this->msg . '</li></ul>';
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function myfatoorah_add_cron_interval($schedules) {
        $mins = 10;

        if (class_exists('WC_Admin_Settings')) {
            $woocommerce_hold_stock_minutes = WC_Admin_Settings::get_option('woocommerce_hold_stock_minutes') ? WC_Admin_Settings::get_option('woocommerce_hold_stock_minutes') : $mins;

            if ($woocommerce_hold_stock_minutes < $mins) {
                $mins = $woocommerce_hold_stock_minutes;
            }
        }

        $schedules['myfatoorah_check_pending_payments'] = array(
            'interval' => $mins * 60,
            'display'  => esc_html__('Every ' . $mins . ' Mins'), //todo trans
        );

        return $schedules;
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
    function check_pending_payments() {
        $statuses = array('wc-pending', 'wc-failed');
        $result   = wc_get_orders(array(
            'status' => $statuses,
        ));

        $gateway = new $this->gateway;
        foreach ($result as $results) {
            $orderId = $results->get_id();
            $order   = new WC_Order($orderId);

            if (!$invoiceId = get_post_meta($orderId, 'InvoiceId', true)) {
                exit;
            }
            if ($order->get_payment_method() != $this->id) {
                exit;
            }

            $json = $gateway->mf->getPaymentStatus($invoiceId, 'InvoiceId', $orderId);
            if ($json->Data->InvoiceStatus == 'Paid' && !empty($order)) {
                $gateway->updatePostMeta($orderId, $json);
                $gateway->addOrderNote($orderId, $json);
                $mfStatus = $gateway->get_option('orderStatus');
                $order->update_status($mfStatus);
            }
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------
}
