<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_ZCredit class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_ZCredit extends WC_Payment_Gateway
{
    /** @var bool Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 'zcredit';
        $this->has_fields = false;
        $this->order_button_text = __('Proceed to Payment', 'woocommerce-gateway-zcredit');
        $this->method_title = __('ZCredit', 'woocommerce-gateway-zcredit');
        $this->supports = [
            'products'
        ];

        // Load the form fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');
        $this->logging = 'yes' === $this->get_option('logging');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->username = $this->testmode ? $this->get_option('test_username') : $this->get_option('username');
        $this->terminal_id = $this->testmode ? $this->get_option('test_terminal_id') : $this->get_option('terminal_id');

        add_action('admin_notices', array( $this, 'admin_notices'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'handle_wc_api'));
    }

    /**
     * Check if SSL is enabled and notify the user
     */
    public function admin_notices()
    {
        if ('no' === $this->enabled) {
            return;
        }

        // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
        if ((function_exists( 'wc_site_is_https' ) && !wc_site_is_https()) && ('no' === get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS'))) {
            echo '<div class="error"><p>' . sprintf(__('ZCredit is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - ZCredit will only work in test mode.', 'woocommerce-gateway-zcredit'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = include 'settings-zcredit.php';
    }

    /**
     * Process the payment and return the result.
     * @param  int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $returnUrl = WC()->api_request_url('WC_Gateway_ZCredit');
        $notifyUrl = '';

        $url = ZCredit\ZCreditHelper::PayWithInvoice(
            $this->terminal_id,
            $this->username,
            $order->get_total(),
            1,
            ZCredit\Languages::Hebrew,
            ZCredit\CurrencyType::NIS,
            "XXX",
            "Test Description",
            1,
            "http://google.com",
            $returnUrl,
            $notifyUrl,
            false,
            false,
            false,
            false,
            "Customer Name",
            "0501234567",
            "x@y.com",
            "123123111",
            1,
            1,
            "http://google.com",
            4,
            0,
            []
        );

        /*
        include_once 'class-wc-gateway-zcredit-request.php';

        $zcredit_request = new WC_Gateway_ZCredit_Request($this);
        */

        return [
            'result' => 'success',
            'redirect' => $url // $zcredit_request->get_request_url($order, $this->testmode)
        ];
    }

    public function handle_wc_api()
    {
        var_dump($_REQUEST);
        die();
    }
}
