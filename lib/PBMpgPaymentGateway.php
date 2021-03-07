<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * PBPaymentGateway Class.
 */
class PBMpgPaymentGateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'pb_woo_mpg';
        $this->has_fields = false;
        $this->order_button_text = __( 'Enter Newebpay Checkout Page', 'pb_ecpay_woo' );
        $this->method_title = __('Newebpay payment by Progress bar online course', 'pb_ecpay_woo');
        $this->method_description = __('Newebpay payment gateway for customers.', 'pb_ecpay_woo');
        $this->supports = array(
            'products',
        );

        $this->merchantIDForTest = 'MS318433486';
        $this->hashKeyForTest = 'LDab8DaFga7tStd6tniuHlsR0tUK6w9v';
        $this->hashIVForTest = 'Cv7Ek1qTnN6bOrQP';

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = 'yes' === $this->get_option( 'testmode', 'no' );
        $this->devmode = 'yes' === $this->get_option( 'devmode', 'no' );

        // Update options
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );

        add_action('woocommerce_api_' . $this->webhook_name(),
            array($this, 'webhook')
        );

        add_action('woocommerce_receipt_' . $this->id, array($this, "render_mpg_form"));

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

    public function validate_multiple_checkable_input_in_form_field($key, $value) {
		return $this->validated_array($value);
    }
    
    public function validate_locked_text_input_in_form_field( $key, $value ) {
        $value = is_null( $value ) ? '' : $value;
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

    public function init_form_fields()
    {
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
                'default' => __('Newebpay payment gateway', 'pb_ecpay_woo'),
            ),
            'description' => array(
                'title' => __('Description of the gateway', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('This controls the description which the user sees during checkout.', 'pb_ecpay_woo'),
                'default' => __('Checkout with Newebpay page.', 'pb_ecpay_woo'),
            ),
            'merchantID' => array(
                'title' => __('Merchant ID', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Merchant ID, MS318433486 is for test mode.', 'pb_ecpay_woo'),
                'default' => 'MS318433486',
            ),
            'hashKey' => array(
                'title' => 'Hash Key',
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Hash Key, LDab8DaFga7tStd6tniuHlsR0tUK6w9v is for test mode.', 'pb_ecpay_woo'),
                'default' => 'LDab8DaFga7tStd6tniuHlsR0tUK6w9v',
            ),
            'hashIV' => array(
                'title' => 'Hash IV',
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Hash IV, Cv7Ek1qTnN6bOrQP is for test mode.', 'pb_ecpay_woo'),
                'default' => 'Cv7Ek1qTnN6bOrQP',
            ),
            'availablePaymentMethods' => array(
                'title' => __('Enabled payment methods', 'pb_ecpay_woo'),
                'type' => 'multiple_checkable_input_in_form',
                'desc_tip' => true,
                'description' => __('Enabled payment methods', 'pb_ecpay_woo'),
                'options' => $this->getAllPayments()
            ),
            'enableTradeLimit' => array(
                'title' => __('Enabled Trade Limit (Seconds)', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Maximum is 900 and minimum is 60. Default of 0 means disabled.'),
                'default' => 0
            ),
            'enableExpiredDay' => array(
                'title' => __('Enabled Expired Day (Days)', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Maximum is 180 days.'),
                'default' => 7
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
                'value' => $this->merchantIDForTest,
            ),
            'hashKeyForTest'  => array(
                'title' => __('Hash Key for test', 'pb_ecpay_woo'),
                'type' => 'locked_text_input_in_form',
                'value' => $this->hashKeyForTest,
            ),
            'hashIVForTest'  => array(
                'title' => __('Hash IV for test', 'pb_ecpay_woo'),
                'type' => 'locked_text_input_in_form',
                'value' => $this->hashIVForTest,
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

    private function getMerchantIDByMode(){
        return ($this->testmode) ? $this->merchantIDForTest : $this->get_option('merchantID');
    }

    private function getHashKeyByMode(){
        return ($this->testmode) ? $this->hashKeyForTest : $this->get_option('hashKey');
    }

    private function getHashIVByMode(){
        return ($this->testmode) ? $this->hashIVForTest : $this->get_option('hashIV');
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->update_status('pending', __('Waiting for the payment', 'pb_ecpay_woo'));
        $order_key = $order->get_order_key();
        $totalAmount = intval($order->get_total());

        $merchantID = $this->getMerchantIDByMode();
        $hashKey = $this->getHashKeyByMode();
        $hashIV = $this->getHashIVByMode();

        $expiredDay = $this->get_option('enableExpiredDay', 0) > 0 ? $this->get_option('enableExpiredDay') : 7;

        $mpgPaymentFormData = [
            'MerchantID' => $merchantID,
            'RespondType' => 'JSON', //Required
            'TimeStamp' => time(), //Required
            'Version' => '1.5', //Required
            'MerchantOrderNo' => $order_id . '_' . time(),
            'Amt' => $totalAmount,
            'ItemDesc' => __('Some goods', 'pb_ecpay_woo'),
            'TradeLimit' => $this->get_option('enableTradeLimit'),
            'ExpireDate' => date('Ymd', strtotime("+" . $expiredDay . "day")),
            'NotifyURL' => get_site_url() . "/?wc-api=" . $this->webhook_name(),
            'ReturnURL' => $order->get_checkout_order_received_url(),
            'ClientBackURL' => $order->get_cancel_endpoint(),
            'Email' => $order->get_billing_email(),
        ];

        $allPaymentMethod = array_keys($this->getAllPayments());
        $availablePayment = array_filter($this->get_option('availablePaymentMethods'), function($value, $key) use ($allPaymentMethod) { return in_array($value, $allPaymentMethod); }, ARRAY_FILTER_USE_BOTH);
        foreach ($availablePayment as $payment) {
            $mpgPaymentFormData[$payment] = 1;
        }

        $order->add_meta_data('mpg_payment_form_data', $mpgPaymentFormData);
        $order->save();

        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
    }

    public function render_mpg_form($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order) {
            $aioUrl = ($this->testmode) ? "https://ccore.newebpay.com/MPG/mpg_gateway" : "https://core.newebpay.com/MPG/mpg_gateway";
            $tradeInfo = $order->get_meta('mpg_payment_form_data');
            $tradeInfoString = $this->getMpgAesEncrypted($tradeInfo, $this->getHashKeyByMode(), $this->getHashIVByMode());
            $metadata = [
                'MerchantID' => $tradeInfo['MerchantID'],
                'TradeInfo' => $tradeInfoString,
                'TradeSha' => $this->getMpgShaEncrypted($tradeInfoString, $this->getHashKeyByMode(), $this->getHashIVByMode()),
                'Version' => $tradeInfo['Version'],
            ];
            require_once PB_ECPAY_PLUGIN_DIR . "view/" . "ecpay_form.php";
        }
        return;
    }

    private function validated_array($value){
        return is_array( $value ) ? array_map( 'wc_clean', array_map( 'stripslashes', $value ) ) : '';
    }

    private function webhook_name() {
        return $this->id;
    }

    public function webhook()
    {
        $postData = $this->validated_array($_POST);
        $key = $this->getHashKeyByMode();
        $iv = $this->getHashIVByMode();

        if ($this->getMpgShaEncrypted($postData['TradeInfo'], $key, $iv) == $postData['TradeSha']) {
            $tradeInfo = $this->getMpgAesDecrypted($postData['TradeInfo'], $key, $iv);
            if ($tradeInfo = json_decode($tradeInfo, true)) {
                $tradeInfoResult = $tradeInfo['Result'];
                $orderInfo = explode('_', $tradeInfoResult['MerchantOrderNo']);
                $orderId = (count($orderInfo) == 2 ? $orderInfo[0] : false);
            } else {
                echo "false";
                exit;
            }

            if ($orderId && ($order = wc_get_order($orderId)) && $this->verifyMpgCallbackData($order, $tradeInfoResult)) {
                $status = $tradeInfo['Status'];
                $responseData = [
                    'Status' => $status,
                    'Message' => $tradeInfo['Message'],
                    'TradeNo' => $tradeInfoResult['TradeNo'],
                    'PayTime' => $tradeInfoResult['PayTime'],
                ];
                $responseData = array_merge($responseData, $this->getOtherTradeInfo($tradeInfoResult['PaymentMethod'], $tradeInfoResult));
                $order->add_meta_data('mpg_payment_response_data', $responseData);

                if ($status == 'SUCCESS') {
                    $order->save();
                    $order->payment_complete();
                } else {
                    $order->add_order_note(__('Newebpay Failed', 'pb_ecpay_woo'), false, false);
                }

                echo "1|OK";
                exit;
            } else {
                if (isset($order)) {
                    $order->add_order_note(__('ECPay webhook Failed', 'pb_ecpay_woo'), false, false);
                    echo "1|OK";
                } else {
                    echo "false";
                }
                exit;
            }
        } else {
            echo "false";
            exit;
        }
    }

    private function do_action_in_admin_page()
    {
        add_action('woocommerce_admin_order_data_after_order_details', function($order) {
            $metadata = $order->get_meta('mpg_payment_form_data') ?: [];
            $responsed_metadata = $order->get_meta('mpg_payment_response_data') ?: [];

            require PB_ECPAY_VIEW_ADMIN_DIR . "mpg_detail_meta.php";
        });
    }

    private function getOtherTradeInfo($paymentType, $tradeInfo) {
        switch ($paymentType) {
            case 'CREDIT':
            case 'CREDITAE':
            case 'UNIONPAY':
                return [
                    'RespondCode' => $tradeInfo['RespondCode'],
                    'Auth' => $tradeInfo['Auth'],
                    'Card4No' => $tradeInfo['Card4No'],
                    'Inst' => $tradeInfo['Inst'],
                    'PaymentMethod' => $tradeInfo['PaymentMethod'],
                ];
            case 'WEBATM':
            case 'VACC':
                return [
                    'PayBankCode' => $tradeInfo['PayBankCode'],
                    'PayerAccount5Code' => $tradeInfo['PayerAccount5Code']
                ];
            case 'CSV':
                $storeName = [
                    '1' => '7-11',
                    '2' => 'FamilyMart',
                    '3' => 'OK',
                    '4' => 'HiLife',
                ];
                return [
                    'CodeNo' => $tradeInfo['CodeNo'],
                    'StoreType' => (isset($storeName[$tradeInfo['StoreType']]) ? $storeName[$tradeInfo['StoreType']] : $tradeInfo['StoreType']),
                    'StoreID' => $tradeInfo['StoreID']
                ];
            case 'BARCODE':
                $storeName = [
                    'SEVEN' => '7-11',
                    'FAMILY' => 'FamilyMart',
                    'OK' => 'OK',
                    'HILIFE' => 'HiLife',
                ];
                return [
                    'Barcode_1' => $tradeInfo['Barcode_1'],
                    'Barcode_2' => $tradeInfo['Barcode_2'],
                    'Barcode_3' => $tradeInfo['Barcode_3'],
                    'PayStore' => (isset($storeName[$tradeInfo['PayStore']]) ? $storeName[$tradeInfo['PayStore']] : $tradeInfo['PayStore']),
                ];
            default:
                return [];
        }
    }

    private function verifyMpgCallbackData($order, $tradeInfo) {
        $verifiedResult = true;
        $tradeInfo = $this->validated_array($tradeInfo);

        if ($this->devmode) {
            $order->add_order_note(var_export($tradeInfo, true), false, false);
        }
        $metadata = $order->get_meta('mpg_payment_form_data');
        $validateKeys = [
            'MerchantID',
            'MerchantOrderNo'
        ];
        foreach($validateKeys as $key) {
            if ($metadata[$key] !== $tradeInfo[$key]) {
                $verifiedResult = false;
            }
        }

        return $verifiedResult;
    }

    private function getAllPayments(){
        return [
            'CREDIT' => __('Credit Card', 'pb_ecpay_woo'),
            'CREDITAE' => __('American Express Credit Card', 'pb_ecpay_woo'),
            'UNIONPAY' => __('UnionPay', 'pb_ecpay_woo'),
            'WEBATM' => __('Web ATM', 'pb_ecpay_woo'),
            'VACC' => __('ATM', 'pb_ecpay_woo'),
            'CVS' => __('CVS - convenience store', 'pb_ecpay_woo'),
            'BARCODE' => __('Barcode - convenience store', 'pb_ecpay_woo'),
        ];
    }

    /**
     * 取得資料 AES 加密字串
     * @param array $datas
     * @param string $key
     * @param string $iv
     * @return string
     */
    private function getMpgAesEncrypted($datas, $key, $iv){
        if (!empty($datas)) {
            return trim(bin2hex(openssl_encrypt($this->addPadding(http_build_query($datas)), 'aes-256-cbc', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv)));
        } else {
            return "";
        }
    }

    private function getMpgAesDecrypted($datas, $key, $iv){
        return $this->strippadding(openssl_decrypt(hex2bin($datas), 'aes-256-cbc' , $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv));
    }

    private function getMpgShaEncrypted($input, $key, $iv){
        $dataString = implode('&', [http_build_query(['HashKey' => $key]), $input, http_build_query(['HashIV' => $iv])]);
        return strtoupper(hash('sha256', $dataString));
    }

    /**
     * @param string $input
     * @param int $blockSize
     */
    private function addPadding($input, $blockSize=32)
    {
        $padLen = $blockSize - (strlen($input) % $blockSize);
        $padding = str_repeat(chr($padLen), $padLen);
        return $input . $padding;
    }

    private function strippadding($string)
    {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            $string = substr($string, 0, strlen($string) - $slast);
            return $string;
        } else {
            return false;
        }
    }
}
