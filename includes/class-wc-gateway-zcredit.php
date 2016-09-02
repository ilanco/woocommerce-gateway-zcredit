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
        $this->method_title = 'ZCredit';
        $this->method_description = 'ZCredit Payment Gateway Plugin for WooCommerce';
        $this->icon = null;
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

        self::$log_enabled = $this->logging;

        add_action('admin_notices', array( $this, 'admin_notices'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'handle_wc_api'));
    }

    /**
     * Logging method.
     * @param string $message
     */
    public static function log($message)
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = new WC_Logger();
            }

            self::$log->add(__CLASS__, $message);
        }
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
        global $woocommerce;

        $order = wc_get_order($order_id);

        self::log('process_payment');

        self::log($order->get_status());

        $returnUrl = $this->get_return_url($order);
        $notifyUrl = WC()->api_request_url(__CLASS__);
        $cancelUrl = $order->get_cancel_order_url_raw();

        /*
        $returnUrl = 'http://558066b8.ngrok.io/checkout/order-received/57?key=wc_order_57c9e3b6ab941'; // WC()->api_request_url('WC_Gateway_ZCredit');
        $notifyUrl = 'http://558066b8.ngrok.io/wc-api/WC_Gateway_ZCredit';
        $cancelUrl = 'http://558066b8.ngrok.io/cart/?cancel_order=true&order=wc_order_57c9e3b6ab941&order_id=57&redirect';
        */

        self::log('returnUrl: ' . $returnUrl);
        self::log('cancelUrl: ' . $cancelUrl);
        self::log('notifyUrl: ' . $notifyUrl);

        $lineItems = $this->get_line_items($order);

        $url = ZCredit\ZCreditHelper::PayWithInvoice(
            $this->terminal_id,
            $this->username,
            $order->get_total(),
            1,
            ZCredit\Languages::Hebrew,
            ZCredit\CurrencyType::NIS,
            $order->get_order_number(),
            "Zushik",
            1,
            "", // picture
            $returnUrl,
            $notifyUrl,
            false,
            false,
            false,
            false,
            $order->billing_first_name . ' ' . $order->billing_last_name,
            $order->billing_phone,
            $order->billing_email,
            "",
            1,
            1,
            $cancelUrl,
            4,
            0,
            $lineItems
        );

        self::log($url);

        return [
            'result' => 'success',
            'redirect' => $url
        ];
    }

    public function get_line_items($order)
    {
        $lineItems = [];

        foreach ($order->get_items(array('line_item')) as $item) {
            $product = $order->get_product_from_item($item);
            $sku = $product ? $product->get_sku() : '';
            $item_line_total = $order->get_item_subtotal($item, false);
            $lineItems[] = $this->add_line_item($item['name'], $item['qty'], $item_line_total, $sku);
        }

        return $lineItems;
    }

    /**
     * Add Line Item.
     *
     * @param  string  $item_name
     * @param  int     $quantity
     * @param  int     $amount
     * @param  string  $item_number
     *
     * @return bool successfully added or not
     */
    protected function add_line_item($item_name, $quantity = 1, $amount = 0, $item_number = '')
    {
        $lineItem = new ZCredit\CartItem();
        $lineItem->Name = html_entity_decode(wc_trim_string($item_name ? $item_name : 'Item', 127), ENT_NOQUOTES, 'UTF-8');
        $lineItem->PictureURL = '';
        $lineItem->SN = $item_number;
        $lineItem->Amount = $quantity;
        $lineItem->ItemPrice = $amount;

        return $lineItem;
    }

    public function handle_wc_api()
    {
        self::log(print_r($_REQUEST, true));

        if (!empty($_POST)) {
            $posted = wp_unslash($_POST);

            $order = wc_get_order($posted['UniqueID']);

            self::log('Found order #' . $order->id);
            self::log('Approval: ' . $posted['ApprovalNumber']);

            $this->payment_status_completed($order, $posted);
        }

        die();
    }

    public function payment_status_completed($order, $posted)
    {
       if ($order->has_status('completed')) {
            self::log('Aborting, Order #' . $order->id . ' is already complete.');
            exit;
        }

        if (!empty($posted['GUID'])) {
            $order->payment_complete(wc_clean($posted['GUID']));

            self::log('Order #' . $order->id . ' completed.');
        }
    }
}
