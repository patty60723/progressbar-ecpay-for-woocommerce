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

define('PB_ECPAY_PLUGIN', plugin_basename( __FILE__ ) );
define('PB_ECPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PB_ECPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PB_ECPAY_VIEW_COMPOMENTS_DIR', PB_ECPAY_PLUGIN_DIR."view/compoments/");
define('PB_ECPAY_VIEW_ADMIN_DIR', PB_ECPAY_PLUGIN_DIR."view/admin/");

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
            function($payment_gateways){
                include_once PB_ECPAY_PLUGIN_DIR . "lib/" . "PBECPayPaymentGateway.php";

                $payment_gateways[] = 'PBECPayPaymentGateway';

                return $payment_gateways;
            }
        );
    }
}

function init_pb_ecpay_payment() {
    require_once PB_ECPAY_PLUGIN_DIR . "controller/" . "PBPaymentAdminController.php";
    new PB_ECPay_Payment();
}

add_action('plugins_loaded', 'init_pb_ecpay_payment');
