<?php

if (!defined('ABSPATH')) {
    exit;
}

return apply_filters('wc_zcredit_settings',
    array(
        'enabled' => array(
            'title'       => __('Enable/Disable', 'woocommerce-gateway-zcredit'),
            'label'       => __('Enable ZCredit', 'woocommerce-gateway-zcredit'),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no'
        ),
        'title' => array(
            'title'       => __('Title', 'woocommerce-gateway-zcredit'),
            'type'        => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-zcredit'),
            'default'     => __('Credit Card (ZCredit)', 'woocommerce-gateway-zcredit'),
            'desc_tip'    => true,
        ),
        'description' => array(
            'title'       => __('Description', 'woocommerce-gateway-zcredit'),
            'type'        => 'text',
            'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-zcredit'),
            'default'     => __('Pay with your credit card via ZCredit.', 'woocommerce-gateway-zcredit'),
            'desc_tip'    => true,
        ),
        'testmode' => array(
            'title'       => __('Test mode', 'woocommerce-gateway-zcredit'),
            'label'       => __('Enable Test Mode', 'woocommerce-gateway-zcredit'),
            'type'        => 'checkbox',
            'description' => __('Place the payment gateway in test mode using test API keys.', 'woocommerce-gateway-zcredit'),
            'default'     => 'yes',
            'desc_tip'    => true,
        ),
        'username' => array(
            'title'       => __('Live Username', 'woocommerce-gateway-zcredit'),
            'type'        => 'text',
            'description' => __('Username from your ZCredit account.', 'woocommerce-gateway-zcredit'),
            'default'     => '',
            'desc_tip'    => true,
        ),
        'terminal_id' => array(
            'title'       => __('Live Terminal Number', 'woocommerce-gateway-zcredit' ),
            'type'        => 'text',
            'description' => __('Get your terminal number from your ZCredit account.', 'woocommerce-gateway-zcredit' ),
            'default'     => '',
            'desc_tip'    => true,
        ),
        'test_username' => array(
            'title'       => __('Test Username', 'woocommerce-gateway-zcredit'),
            'type'        => 'text',
            'description' => __('Username from your ZCredit account.', 'woocommerce-gateway-zcredit'),
            'default'     => '',
            'desc_tip'    => true,
        ),
        'test_terminal_id' => array(
            'title'       => __('Test Terminal Number', 'woocommerce-gateway-zcredit'),
            'type'        => 'text',
            'description' => __('Get your terminal number from your ZCredit account.', 'woocommerce-gateway-zcredit'),
            'default'     => '',
            'desc_tip'    => true,
        ),
        'zcredit_checkout_locale' => array(
            'title'       => __('zcredit Checkout locale', 'woocommerce-gateway-zcredit'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Language to display in ZCredit Checkout. English will be used by default.', 'woocommerce-gateway-zcredit'),
            'default'     => 'en-US',
            'desc_tip'    => true,
            'options'     => array(
                'en-US'   => __('English', 'woocommerce-gateway-zcredit'),
                'he-IL'   => __('Hebrew', 'woocommerce-gateway-zcredit'),
            ),
        ),
        'zcredit_checkout_image' => array(
            'title'       => __('ZCredit Checkout Image', 'woocommerce-gateway-zcredit'),
            'description' => __('Optionally enter the URL to a 128x128px image of your brand or product. e.g. <code>https://yoursite.com/wp-content/uploads/2013/09/yourimage.jpg</code>', 'woocommerce-gateway-zcredit'),
            'type'        => 'text',
            'default'     => '',
            'desc_tip'    => true,
        ),
        'logging' => array(
            'title'       => __('Logging', 'woocommerce-gateway-zcredit'),
            'label'       => __('Log debug messages', 'woocommerce-gateway-zcredit'),
            'type'        => 'checkbox',
            'description' => __('Save debug messages to the WooCommerce System Status log.', 'woocommerce-gateway-zcredit'),
            'default'     => 'no',
            'desc_tip'    => true,
        ),
    )
);
