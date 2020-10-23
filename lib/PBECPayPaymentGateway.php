<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * PBPaymentGateway Class.
 */
class PBECPayPaymentGateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'pb_woo_ecpay';
        $this->has_fields = false;
        $this->order_button_text = __( 'Enter ECPay Checkout Page', 'pb_ecpay_woo' );
        $this->method_title = __('ECPay payment by Progress bar online course', 'pb_ecpay_woo');
        $this->method_description = __('ECPay payment gateway for customers.', 'pb_ecpay_woo');
        $this->supports = array(
            'products',
        );

        $this->paymentMethods = [
            "Credit" => __('Credit Card and UnionPay. (Needs to apply for activation from ECPay)', 'pb_ecpay_woo'),
            "WebATM" => __("Web ATM. (Not shows on phone)", 'pb_ecpay_woo'),
            "ATM" => __('ATM', 'pb_ecpay_woo'),
            "CVS" => __('CVS - convenience store', 'pb_ecpay_woo'),
            "BARCODE" => __('Barcode - convenience store', 'pb_ecpay_woo'),
        ];

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = 'yes' === $this->get_option( 'testmode', 'no' );
        $this->devmode = 'yes' === $this->get_option( 'devmode', 'no' );

        add_action('woocommerce_api_' . $this->webhook_name(),
            array($this, 'webhook')
        );

        // PB-note: WooCommerce -> Templates -> checkout -> order-receipt.php
        add_action('woocommerce_receipt_' . $this->id, array($this, "render_ecpay_form"));
    
        $this->do_action_in_admin_page();
    }

    public function get_title(){
        $title = parent::get_title();
        if (get_query_var('order-pay') && is_numeric(get_query_var('order-pay'))){
            $order_id = intval(get_query_var('order-pay'));
            $order = wc_get_order($order_id);
            $metadata = $order->get_meta('ec_payment_form_data');
            if (isset($metadata["MerchantTradeNo"])){
                $title = __('Disabled - ', 'pb_ecpay_woo') . $title;
            }
        } 

        if ($this->testmode){
            $title = __('[Test mode]', 'pb_ecpay_woo') . $title;
        }
        return $title;
    }

    public function get_description(){
        $description = parent::get_description();
        
        if (get_query_var('order-pay') && is_numeric(get_query_var('order-pay'))){
            $order_id = intval(get_query_var('order-pay'));
            $order = wc_get_order($order_id);
            $metadata = $order->get_meta('ec_payment_form_data');
            if (isset($metadata["MerchantTradeNo"])){
                $description = __('Payment page was disabled, please checkout again.', 'pb_ecpay_woo');
            }
        } 

        if ($this->testmode){
            $description = __('[Test mode]', 'pb_ecpay_woo'). $description;
        }
        return $description;
    }

    // woocommerce -> includes -> abstracts -> abstract-wc-settings-api.php 
    public function generate_hr_in_form_html($key, $value)
    {
        ob_start();

        require PB_ECPAY_VIEW_COMPOMENTS_DIR . "hr_in_form.php";

        return ob_get_clean();
    }

    public function generate_locked_text_input_in_form_html($key, $value)
    {
        ob_start();

        require PB_ECPAY_VIEW_COMPOMENTS_DIR . "locked_text_input_in_form.php";

        return ob_get_clean();
    }

    public function generate_multiple_checkable_input_in_form_html($key, $value)
    {
        ob_start();
        
        $inputName = "woocommerce_{$this->id}_{$key}";
        $optionValues = $this->get_option($key);
        require PB_ECPAY_VIEW_COMPOMENTS_DIR . "multiple_checkable_input_in_form.php";

        return ob_get_clean();
    }

    public function validate_multiple_checkable_input_in_form_field( $key, $value ) {
		return $this->validated_array($value);
    }
    
    public function validate_locked_text_input_in_form_field( $key, $value ) {
        $value = is_null( $value ) ? '' : $value;
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

    public function init_form_fields()
    {
        $prefix = $this->get_option('orderNumberPrefix') ?: $this->generateRandomCharString(4);
        if (!$this->get_option('orderNumberPrefix')){
            $this->update_option('orderNumberPrefix', $prefix);
        }
        
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable this payment gateway', 'pb_ecpay_woo'),
                'type' => 'checkbox',
                'label' => __('Enabled', 'pb_ecpay_woo'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title of the gateway', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('This controls the title which the user sees during checkout.', 'pb_ecpay_woo'),
                'default' => __('ECPay payment gateway', 'pb_ecpay_woo'),
            ),
            'description' => array(
                'title' => __('Description of the gateway', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('This controls the description which the user sees during checkout.', 'pb_ecpay_woo'),
                'default' => __('Checkout with ECPay page.', 'pb_ecpay_woo'),
            ),
            'merchantID' => array(
                'title' => __('Merchant ID', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Merchant ID, 2000132 is for test mode.', 'pb_ecpay_woo'),
                'default' => '2000132',
            ),
            'hashKey' => array(
                'title' => 'Hash Key',
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Hash Key, 5294y06JbISpM5x9 is for test mode.', 'pb_ecpay_woo'),
                'default' => '5294y06JbISpM5x9',
            ),
            'hashIV' => array(
                'title' => 'Hash IV',
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Hash IV，v77hoKGq4kWxNNIS is for test mode.', 'pb_ecpay_woo'),
                'default' => 'v77hoKGq4kWxNNIS',
            ),
            'availablePaymentMethods' => array(
                'title' => __('Enabled payment methods', 'pb_ecpay_woo'),
                'type' => 'multiple_checkable_input_in_form',
                'desc_tip' => true,
                'description' => __('Enabled payment methods', 'pb_ecpay_woo'),
                'options' => $this->paymentMethods
            ),
            'orderNumberPrefix' => array(
                'title' => __('Prefix of orders', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Order number cannot be repeated, it will be created with timestamp and prefix.', 'pb_ecpay_woo'),
                'default' => $prefix,
            ),
            'section1' => array(
                'type'  => 'hr_in_form'
            ),
            'testmode' => array(
                'title' => __('Enable test mode', 'pb_ecpay_woo'),
                'type' => 'checkbox',
                'label' => __('Enabled', 'pb_ecpay_woo'),
                'default' => 'yes',
            ),
            'merchantIDForTest' => array(
                'title' => __('Merchant ID for test', 'pb_ecpay_woo'),
                'type' => 'locked_text_input_in_form',
                'value' => "2000132",
            ),
            'hashKeyForTest'  => array(
                'title' => __('Hash Key for test', 'pb_ecpay_woo'),
                'type' => 'locked_text_input_in_form',
                'value' => '5294y06JbISpM5x9',
            ),
            'hashIVForTest'  => array(
                'title' => __('Hash IV for test', 'pb_ecpay_woo'),
                'type' => 'locked_text_input_in_form',
                'value' => 'v77hoKGq4kWxNNIS',
            ),
            'section2' => array(
                'type'  => 'hr_in_form'
            ),
            'devmode' => array(
                'title' => __('Enable development mode', 'pb_ecpay_woo'),
                'type' => 'checkbox',
                'label' => __('Enabled(only if you are a developer)', 'pb_ecpay_woo'),
                'default' => 'no',
            ),
        );
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->update_status('pending', __('Waiting for the payment', 'pb_ecpay_woo'));
        $order_key = $order->get_order_key();
        $totalAmount = intval($order->get_total());

        $merchantID = ($this->testmode) ? $this->get_option('merchantIDForTest') : $this->get_option('merchantID');
        $hashKey = ($this->testmode)? $this->get_option('hashKeyForTest') : $this->get_option('hashKey');
        $hashIV = ($this->testmode) ? $this->get_option('hashIVForTest') : $this->get_option('hashIV');

        $ecPaymentFormData = [
            'MerchantID' => $this->get_option('merchantID'),
            'MerchantTradeNo' => $this->get_option('orderNumberPrefix') . time(),
            'MerchantTradeDate' => date("Y/m/d H:i:s"), 
            "PaymentType" => "aio",
            'TotalAmount' => $totalAmount,
            'TradeDesc' => __('Progressbar ECPay transaction module', 'pb_ecpay_woo'),
            'ItemName' => __('Some goods', 'pb_ecpay_woo'),
            'ChoosePayment' => "ALL",
            'ReturnURL' => get_site_url() . "/?wc-api=" . $this->webhook_name(),
            'ClientBackURL' => get_site_url() . "/checkout/order-received/{$order_id}/?key={$order_key}",
            'ExpireDate' => 7, // ATM 過期時間 1 ~ 60 天
            'IgnorePayment' => $this->getIgnorePayments()
        ];

        $order->add_meta_data( 'ec_payment_form_data', $ecPaymentFormData);
        $order->save();

        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
    }

    
    private function verifyECPayCallbackData($order, $postData) {
        $postData = $this->validated_array($postData);

        if ($this->devmode) {
            $order->add_order_note(var_export($postData, true), false, false);
        } 

        if (!isset($postData['SimulatePaid']) || 
            ($postData['SimulatePaid'] == 1 && !$this->testmode) 
        ) {
            return false;
        }

        $metadata = $order->get_meta('ec_payment_form_data');
        $validateKeys = [
            'MerchantID', 
            'MerchantTradeNo'
        ];

        $result = true;
        foreach($validateKeys as $key){
            $result = ($metadata[$key] == $postData[$key]);
            if (!$result){
                break;
            }
        }

        $CheckMacValue = $this->generate(
            $postData,
            $this->get_option('hashKey'),
            $this->get_option('hashIV')
        );

        $result = ($this->generate(
            $postData,
            $this->get_option('hashKey'),
            $this->get_option('hashIV')
        ) == $postData["CheckMacValue"]);

        return $result;
    }

    
    public function webhook()
    {
        $postData = $this->validated_array($_POST);

        if (isset($postData["CustomField1"]) && is_numeric($postData["CustomField1"])) {
            $orderId = $postData["CustomField1"];
            $order = wc_get_order($orderId);

            if ( $order && $this->verifyECPayCallbackData($order, $postData)) {
                $rtnCode = $postData["RtnCode"];

                $order->add_meta_data( 'ec_payment_response_data', [
                    "RtnCode" => $rtnCode,
                    "PaymentType" => $postData["PaymentType"],
                    "RtnMsg" => $postData["RtnMsg"],
                    "PaymentDate" => $postData["PaymentDate"],
                    "TradeNo" => $postData["TradeNo"],
                    "PaymentTypeChargeFee" => $postData["PaymentTypeChargeFee"],
                ]);

                if ($rtnCode == '1') {
                    $order->save();
                    $order->payment_complete();
                }else {
                    $order->add_order_note(__('ECpay Failed', 'pb_ecpay_woo'), false, false);
                }

                echo "1|OK";
                exit;
            }else {
                $order->add_order_note(__('ECPay webhook Failed', 'pb_ecpay_woo'), false, false);
                echo "1|OK";
                exit;
            }
        } else {
            echo "false";
            exit;
        }
    }

    private function do_action_in_admin_page()
    {
        // woocommerce -> includes -> admin -> metaboxes -> class-wc-metabox-order-data.php
        add_action('woocommerce_admin_order_data_after_order_details', function($order){
            $metadata = $order->get_meta('ec_payment_form_data') ?: [];
            $responsed_metadata = $order->get_meta('ec_payment_response_data') ?: [];

            require PB_ECPAY_VIEW_ADMIN_DIR . "ecpay_detail_meta.php";
        });
        // add_action('woocommerce_admin_order_data_after_billing_address', function($order){
        //    echo "<p class='form-field form-field-wide'>測試用資料</p>";
        // });
        // add_action('woocommerce_admin_order_data_after_shipping_address', function($order){
        //    echo "<p class='form-field form-field-wide'>測試用資料</p>";
        // });
    }

    
    public function render_ecpay_form($order_id)
    {
        $order = wc_get_order($order_id);
        if ($order) {
            $aioUrl = ($this->testmode) ? "https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5" : "https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5";
            $metadata = $order->get_meta('ec_payment_form_data');
            $metadata["CustomField1"] = $order_id;
            $metadata["EncryptType"] = 1;
            $metadata["CheckMacValue"] = $this->generate(
                $metadata,
                $this->get_option('hashKey'),
                $this->get_option('hashIV')
            );
            require_once PB_ECPAY_PLUGIN_DIR . "view/" . "ecpay_form.php";
        }
        return;
    }


    private function getIgnorePayments(){
        $availablePaymentMethods = $this->get_option("availablePaymentMethods") ?: [];
        return array_reduce(array_keys($this->paymentMethods), function($result, $paymentMethod) use ($availablePaymentMethods) {
            if(!in_array($paymentMethod, $availablePaymentMethods)){
                $result = ($result == "") ? $paymentMethod : "$result#{$paymentMethod}";
            }
            return $result;
        }, "");
    }

    private function validated_array($value){
        return is_array( $value ) ? array_map( 'wc_clean', array_map( 'stripslashes', $value ) ) : '';
    }

    private function webhook_name() {
        return $this->id;
    }

    private function generate(
        $arParameters = array(),
        $HashKey = '',
        $HashIV = ''
    ) {
        $sMacValue = '';

        if (isset($arParameters["CheckMacValue"])){
            unset($arParameters["CheckMacValue"]);
        }

        if (isset($arParameters)) {
            // arParameters 為傳出的參數，並且做字母 A-Z 排序 unset($arParameters['CheckMacValue']);
            ksort($arParameters); // 組合字串
            $sMacValue = 'HashKey=' . $HashKey;foreach ($arParameters as $key => $value) {
                $sMacValue .= '&' . $key . '=' . $value;}
            $sMacValue .= '&HashIV=' . $HashIV; // URL Encode 編碼
            $sMacValue = urlencode($sMacValue); // 轉成小寫
            $sMacValue = strtolower($sMacValue);

            // 取代為與 dotNet 相符的字元
            $sMacValue = str_replace('%2d', '-', $sMacValue);
            $sMacValue = str_replace('%5f', '_', $sMacValue);
            $sMacValue = str_replace('%2e', '.', $sMacValue);
            $sMacValue = str_replace('%21', '!', $sMacValue);
            $sMacValue = str_replace('%2a', '*', $sMacValue);
            $sMacValue = str_replace('%28', '(', $sMacValue);
            $sMacValue = str_replace('%29', ')', $sMacValue); // 編碼
            $sMacValue = hash('sha256', $sMacValue);
            return strtoupper($sMacValue);
        }
    }

    private function generateRandomCharString($length = 10) {
        $characters = 'abcdefghjkmnpqrstuvwxyABCDEFGHKMNOPQRSTUVWXY';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
