<?php
/*
 * Plugin Name: WooCommerce ZCredit Gateway
 * Plugin URI: https://github.com/ilanco/woocommerce-gateway-zcredit
 * Description: Take credit card payments on your store using ZCredit.
 * Author: Ilan Cohen
 * Author URI: https://github.com/ilanco
 * Version: 1.0.0
 * Text Domain: woocommerce-gateway-zcredit
 * Domain Path: /languages
 *
 * Copyright (c) 2016 Ilan Cohen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once 'vendor/autoload.php';

define('WC_ZCREDIT_VERSION', '1.0.0');
define('WC_ZCREDIT_MIN_PHP_VER', '5.3.0');
define('WC_ZCREDIT_MIN_WC_VER', '2.5.0');
define('WC_ZCREDIT_MAIN_FILE', __FILE__);
define('WC_ZCREDIT_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));

if (!class_exists('WC_ZCredit')) {

class WC_ZCredit
{
    /**
     * @var Singleton The reference the *Singleton* instance of this class
     */
    private static $instance;

    /**
     * @var Reference to logging class.
     */
    private static $log;

    /**
     * Notices (array)
     * @var array
     */
    public $notices = [];

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        add_action('admin_init', [$this, 'check_environment']);
        add_action('admin_notices', [$this, 'admin_notices'], 15);
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Init the plugin after plugins_loaded so environment variables are set.
     */
    public function init()
    {
        // Don't hook anything else in the plugin if we're in an incompatible environment
        if (self::get_environment_warning()) {
            return;
        }

        // Init the gateway itself
        $this->init_gateways();
    }

    /**
     * Allow this class and other classes to add slug keyed notices (to avoid duplication)
     */
    public function add_admin_notice($slug, $class, $message)
    {
        $this->notices[$slug] = [
            'class' => $class,
            'message' => $message
        ];
    }

    /**
     * The primary sanity check, automatically disable the plugin on activation if it doesn't
     * meet minimum requirements.
     *
     * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
     */
    public static function activation_check()
    {
        $environment_warning = self::get_environment_warning(true);
        if ($environment_warning) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die($environment_warning);
        }
    }

    /**
     * The backup sanity check, in case the plugin is activated in a weird way,
     * or the environment changes after activation.
     */
    public function check_environment()
    {
        $environment_warning = self::get_environment_warning();
        if ($environment_warning && is_plugin_active(plugin_basename(__FILE__))) {
            deactivate_plugins(plugin_basename(__FILE__));
            $this->add_admin_notice('bad_environment', 'error', $environment_warning);
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

    /**
     * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
     * found or false if the environment has no problems.
     */
    static function get_environment_warning($during_activation = false)
    {
        if (version_compare(phpversion(), WC_ZCREDIT_MIN_PHP_VER, '<')) {
            if ($during_activation) {
                $message = __('The plugin could not be activated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-zcredit', 'woocommerce-gateway-zcredit');
            } else {
                $message = __('The WooCommerce ZCredit plugin has been deactivated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-zcredit' );
            }

            return sprintf($message, WC_ZCREDIT_MIN_PHP_VER, phpversion());
        }

        if (version_compare(WC_VERSION, WC_ZCREDIT_MIN_WC_VER, '<')) {
            if ($during_activation) {
                $message = __('The plugin could not be activated. The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-zcredit', 'woocommerce-gateway-zcredit');
            } else {
                $message = __('The WooCommerce ZCredit plugin has been deactivated. The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-zcredit');
            }

            return sprintf($message, WC_ZCREDIT_MIN_WC_VER, WC_VERSION);
        }

        if (!function_exists('curl_init')) {
            if ($during_activation) {
                return __('The plugin could not be activated. cURL is not installed.', 'woocommerce-gateway-zcredit');
            }

            return __('The WooCommerce ZCredit plugin has been deactivated. cURL is not installed.', 'woocommerce-gateway-zcredit');
        }

        return false;
    }

    /**
     * Display any notices we've collected thus far (e.g. for connection, disconnection)
     */
    public function admin_notices()
    {
        foreach ((array) $this->notices as $notice_key => $notice) {
            echo "<div class='" . esc_attr($notice['class']) . "'><p>";
            echo wp_kses($notice['message'], ['a' => ['href' => []]]);
            echo "</p></div>";
        }
    }

    /**
     * Initialize the gateway. Called very early - in the context of the plugins_loaded action
     *
     * @since 1.0.0
     */
    public function init_gateways()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        include_once plugin_basename('includes/class-wc-gateway-zcredit.php');

        load_plugin_textdomain('woocommerce-gateway-zcredit', false, plugin_basename(dirname(__FILE__)) . '/languages');
        add_filter('woocommerce_payment_gateways', [$this, 'add_gateways']);
    }

    /**
     * Add the gateways to WooCommerce
     *
     * @since 1.0.0
     */
    public function add_gateways($methods)
    {
        $methods[] = 'WC_Gateway_ZCredit';

        return $methods;
    }

    /**
     * Logger
     *
     * @since 1.0.0
     */
    public static function log($message)
    {
        if (empty(self::$log)) {
            self::$log = new WC_Logger();
        }

        self::$log->add('woocommerce-gateway-zcredit', $message);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }
}

$wc_zredit = WC_ZCredit::get_instance();
register_activation_hook(__FILE__, ['WC_ZCredit', 'activation_check']);

}
