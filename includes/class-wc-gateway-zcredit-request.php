<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates requests to send to ZCredit.
 */
class WC_Gateway_ZCredit_Request
{
    /**
     * Constructor.
     *
     * @param WC_Gateway_ZCredit $gateway
     */
    public function __construct($gateway)
    {
        $this->gateway = $gateway;
        $this->notify_url = WC()->api_request_url('WC_Gateway_ZCredit');
    }

    /**
     * Get the ZCredit request URL for an order.
     *
     * @param  WC_Order $order
     * @param  bool     $sandbox
     *
     * @return string
     */
    public function get_request_url($order, $sandbox = false)
    {
        $zcredit_args = http_build_query($this->get_zcredit_args($order), '', '&');

        WC_Gateway_ZCredit::log('ZCredit request args for order ' . $order->get_order_number() . ': ' . print_r($zcredit_args, true));

        if ($sandbox) {
            return 'https://pci.zcredit.co.il/WebControl/Transaction.aspx?sandbox=1&GUID=$ResGUID&DataPackage=$Resdata' . $zcredit_args;
        } else {
            return 'https://pci.zcredit.co.il/WebControl/Transaction.aspx?GUID=$ResGUID&DataPackage=$Resdata' . $zcredit_args;
        }
    }

    /**
     * Get ZCredit args.
     *
     * @param  WC_Order $order
     *
     * @return array
     */
    protected function get_zcredit_args($order)
    {
        WC_Gateway_ZCredit::log('Generating payment form for order ' . $order->get_order_number());

        return apply_filters('woocommerce_zcredit_args', array_merge(
            array(
                'cmd'           => 'pay',
                'business'      => $this->gateway->get_option('email'),
                'no_note'       => 1,
                'currency_code' => get_woocommerce_currency(),
                'charset'       => 'utf-8',
                'rm'            => is_ssl() ? 2 : 1,
                'upload'        => 1,
                'return'        => esc_url_raw(add_query_arg('utm_nooverride', '1', $this->gateway->get_return_url($order))),
                'cancel_return' => esc_url_raw($order->get_cancel_order_url_raw()),
                'page_style'    => $this->gateway->get_option('page_style'),
                'paymentaction' => $this->gateway->get_option('paymentaction'),
                'bn'            => 'WooThemes_Cart',
                'invoice'       => $this->gateway->get_option('invoice_prefix') . $order->get_order_number(),
                'custom'        => json_encode(['order_id' => $order->id, 'order_key' => $order->order_key]),
                'notify_url'    => $this->notify_url,
                'first_name'    => $order->billing_first_name,
                'last_name'     => $order->billing_last_name,
                'company'       => $order->billing_company,
                'address1'      => $order->billing_address_1,
                'address2'      => $order->billing_address_2,
                'city'          => $order->billing_city,
                'state'         => $order->billing_state,
                'zip'           => $order->billing_postcode,
                'country'       => $order->billing_country,
                'email'         => $order->billing_email
            )
        ), $order);
    }
}
