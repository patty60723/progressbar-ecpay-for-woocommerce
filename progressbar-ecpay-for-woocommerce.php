<?php
/*
 * Plugin Name: Progress Bar - ECPay gateway
 * Plugin URI: https://progressbar.tw/courses/18
 * Description: 進度條 - 綠界金流模組
 * Version: 1.0.1
 * Author: 進度條線上課程
 * Author URI: https://progressbar.tw/
 * License: GPL-3.0-or-later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('PB_ECPAY_PLUGIN', plugin_basename(__FILE__));
define('PB_ECPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PB_ECPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PB_ECPAY_VIEW_COMPOMENTS_DIR', PB_ECPAY_PLUGIN_DIR . "view/compoments/");
define('PB_ECPAY_VIEW_ADMIN_DIR', PB_ECPAY_PLUGIN_DIR . "view/admin/");

class PB_ECPay_Payment
{
    private $adminController;

    public function __construct()
    {
        $this->init();

        if (is_admin()) {
            $this->adminController = new PBPaymentAdminController();
        }
    }

    public function init()
    {
        load_plugin_textdomain('pb_ecpay_woo', false, basename(dirname(__FILE__)) . '/languages/');
        add_filter(
            'woocommerce_payment_gateways',
            function ($payment_gateways) {

                include_once PB_ECPAY_PLUGIN_DIR . "lib/" . "PBECPayPaymentGateway.php";
                include_once PB_ECPAY_PLUGIN_DIR . "lib/" . "PBECPayTransportGateway.php";
                if (get_option('pb_payment_gateway_settings')['enabled_ecpay'] ?? false) {
                    $payment_gateways[] = 'PBECPayPaymentGateway';
                }

                if (get_option('pb_payment_gateway_settings')['enabled_transport'] ?? false) {
                    $payment_gateways[] = 'PBECPayTransportGateway';
                }

                return $payment_gateways;
            }
        );


        add_action('woocommerce_checkout_process', function () {
            if ($_POST['payment_method'] === 'pb_woo_ecpay_transport') {
                add_action('woocommerce_checkout_fields', function ($fields) {
                    unset($fields['billing']['billing_country']);
                    unset($fields['billing']['billing_address_1']);
                    unset($fields['billing']['billing_address_2']);
                    unset($fields['billing']['billing_city']);
                    unset($fields['billing']['billing_state']);
                    unset($fields['billing']['billing_postcode']);
                    return $fields;
                });
            }
        });
    }
}

function init_pb_ecpay_payment()
{
    require_once PB_ECPAY_PLUGIN_DIR . "controller/" . "PBPaymentAdminController.php";

    new PB_ECPay_Payment();
}

add_action('plugins_loaded', 'init_pb_ecpay_payment');
