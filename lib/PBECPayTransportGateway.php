<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PBPaymentGateway Class.
 */
class PBECPayTransportGateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'pb_woo_ecpay_transport';
        $this->has_fields = false;
        $this->order_button_text = '貨到付款';
        $this->method_title = '進度條線上課程 - 綠界物流';
        $this->method_description = '讓顧客可以利用綠界 ECPay 物流結帳。';
        $this->supports = array(
            'products',
        );

        $this->logisticsType = 'CVS';
        $this->isCollection = 'Y';
        $this->logisticsSubTypes = [
            'FAMI' => '全家',
            'UNIMART' => '7-ELEVEN 超商',
            'HILIFE' => '萊爾富'
        ];

        $this->senderName = '寄件人';
        $this->senderPhone = '0912345678';
        $this->senderCellPhone = '0987654321';


        $this->map_validation_keys = ['MerchantID', 'MerchantTradeNo', 'LogisticsSubType', 'CVSStoreID', 'CVSStoreName', 'CVSAddress', 'CVSTelephone', 'CVSOutSide', 'ExtraData'];

        $this->merchantIDForTest = '2000132';
        $this->hashKeyForTest = '5294y06JbISpM5x9';
        $this->hashIVForTest = 'v77hoKGq4kWxNNIS';


        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = 'yes' === $this->get_option('testmode', 'no');
        $this->devmode = 'yes' === $this->get_option('devmode', 'no');

        // Update options
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );

        add_action('woocommerce_api_' . $this->webhook_name(),
            array($this, 'webhook')
        );

        add_action('woocommerce_api_map_reply', [$this, 'map_reply']);

        // PB-note: WooCommerce -> Templates -> checkout -> order-receipt.php
        add_action('woocommerce_receipt_' . $this->id, array($this, "render_ecpay_logistics_form"));

        add_action('woocommerce_before_thankyou', [$this, 'before_thankyou']);

        add_action('woocommerce_order_details_after_customer_details', [$this, 'remove_customer_details']);

        $this->do_action_in_admin_page();
    }

    public function remove_customer_details($order)
    {
        echo "<script>document.querySelector('.woocommerce-customer-details').remove();</script>";
    }

    public function validation_map_keys($data)
    {
        return is_array($data) && !in_array(false, array_map(fn($key) => array_key_exists($key, $data), $this->map_validation_keys));
    }

    public function map_reply()
    {
        wp_redirect(site_url() . '/checkout?' . http_build_query($_POST));
        exit;
    }

    public function get_title()
    {
        $title = parent::get_title();
        if (get_query_var('order-pay') && is_numeric(get_query_var('order-pay'))) {
            $order_id = intval(get_query_var('order-pay'));
            $order = wc_get_order($order_id);
            $metadata = $order->get_meta('ec_payment_form_data');
            if (isset($metadata["MerchantTradeNo"])) {
                $title = __('Disabled - ', 'pb_ecpay_woo') . $title;
            }
        }

        if ($this->testmode) {
            $title = __('[Test mode]', 'pb_ecpay_woo') . $title;
        }
        return $title;
    }

    public function get_description()
    {
        $description = parent::get_description();

        if (get_query_var('order-pay') && is_numeric(get_query_var('order-pay'))) {
            $order_id = intval(get_query_var('order-pay'));
            $order = wc_get_order($order_id);
            $metadata = $order->get_meta('ec_payment_form_data');
            if (isset($metadata["MerchantTradeNo"])) {
                $description = __('Payment page was disabled, please checkout again.', 'pb_ecpay_woo');
            }
        }

        if ($this->testmode) {
            $description = __('[Test mode]', 'pb_ecpay_woo') . $description;
        }

        return $description;
    }

    public function payment_fields()
    {
        parent::payment_fields();
        $mapUrl = ($this->testmode) ? "https://logistics-stage.ecpay.com.tw/Express/map" : "https://logistics.ecpay.com.tw/Express/map";
        $availableLogisticsSubTypes = $this->get_option('availableLogisticsSubTypes');
        $logisticsSubType = null;
        if (is_array($availableLogisticsSubTypes) && count($availableLogisticsSubTypes)) {
            $logisticsSubType = $availableLogisticsSubTypes[0];
        }
        $metadata = [
            'MerchantID' => $this->getMerchantIDByMode(),
            'LogisticsType' => $this->logisticsType,
            'LogisticsSubType' => $logisticsSubType,
            'IsCollection' => $this->isCollection,
            'ServerReplyURL' => site_url() . '/wc-api/map_reply'
        ];
        require_once PB_ECPAY_PLUGIN_DIR . "view/" . "map_form.php";
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

    public function validate_multiple_checkable_input_in_form_field($key, $value)
    {
        return $this->validated_array($value);
    }

    public function validate_locked_text_input_in_form_field($key, $value)
    {
        $value = is_null($value) ? '' : $value;
        return wp_kses_post(trim(stripslashes($value)));
    }

    public function init_form_fields()
    {
        $prefix = $this->get_option('orderNumberPrefix') ?: $this->generateRandomCharString(4);
        if (!$this->get_option('orderNumberPrefix')) {
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
                'default' => 'ECPay 綠界物流',
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
            'availableLogisticsSubTypes' => array(
                'title' => '可用的超商',
                'type' => 'multiple_checkable_input_in_form',
                'desc_tip' => true,
                'description' => __('Enabled payment methods', 'pb_ecpay_woo'),
                'options' => $this->logisticsSubTypes
            ),
            'orderNumberPrefix' => array(
                'title' => __('Prefix of orders', 'pb_ecpay_woo'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('Order number cannot be repeated, it will be created with timestamp and prefix.', 'pb_ecpay_woo'),
                'default' => $prefix,
            ),
            'section1' => array(
                'type' => 'hr_in_form'
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
            'hashKeyForTest' => array(
                'title' => __('Hash Key for test', 'pb_ecpay_woo'),
                'type' => 'locked_text_input_in_form',
                'value' => $this->hashKeyForTest,
            ),
            'hashIVForTest' => array(
                'title' => __('Hash IV for test', 'pb_ecpay_woo'),
                'type' => 'locked_text_input_in_form',
                'value' => $this->hashIVForTest,
            ),
            'section2' => array(
                'type' => 'hr_in_form'
            ),
            'devmode' => array(
                'title' => __('Enable development mode', 'pb_ecpay_woo'),
                'type' => 'checkbox',
                'label' => __('Enabled(only if you are a developer)', 'pb_ecpay_woo'),
                'default' => 'no',
            ),
        );
    }

    private function getMerchantIDByMode()
    {
        return ($this->testmode) ? $this->merchantIDForTest : $this->get_option('merchantID');
    }

    private function getHashKeyByMode()
    {
        return ($this->testmode) ? $this->hashKeyForTest : $this->get_option('hashKey');
    }

    private function getHashIVByMode()
    {
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

        $ecPaymentFormData = [
            'MerchantID' => $merchantID,
            'MerchantTradeNo' => $this->get_option('orderNumberPrefix') . time(),
            'MerchantTradeDate' => date("Y/m/d H:i:s"),
            'LogisticsType' => $this->logisticsType,
            'LogisticsSubType' => $_POST['LogisticsSubType'],
            'GoodsAmount' => $totalAmount,
            'IsCollection' => $this->isCollection,
            'SenderName' => $this->senderName,
            'SenderPhone' => $this->senderPhone,
            'SenderCellPhone' => $this->senderCellPhone,
            'ReceiverName' => $order->get_formatted_billing_full_name(),
            'ReceiverCellPhone' => $_POST['billing_phone'],
            'ReceiverStoreID' => $_POST['CVSStoreID'],
            'ServerReplyURL' => get_site_url() . "/?wc-api=" . $this->webhook_name(),
            'ClientReplyURL' => get_site_url() . "/checkout/order-received/{$order_id}/?key={$order_key}",
        ];

        $order->add_meta_data('ec_payment_form_data', $ecPaymentFormData);
        $order->save();

        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
    }

    public function validate_fields()
    {
        $full_name = $_POST['billing_last_name'] . $_POST['billing_first_name'];
        if (!preg_match('/^\p{Han}{2,5}$/u', $full_name) && !preg_match('/^[A-Za-z]{4,10}$/', $full_name)) {
            wc_add_notice('姓名格式有誤(中文 2~5 個字, 英文 4~10 個字, 不可混用)', 'error');
        }

        if (!preg_match('/^09[0-9]{8}$/', $_POST['billing_phone'])) {
            wc_add_notice('聯絡電話格式有誤(09開頭，10位數)', 'error');
        }

        if (!$this->validation_map_keys($_POST)) {
            wc_add_notice('請先選擇取貨的超商', 'error');
        }
    }


    private function verifyECPayCallbackData($order, $postData)
    {
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
        foreach ($validateKeys as $key) {
            $result = ($metadata[$key] == $postData[$key]);
            if (!$result) {
                break;
            }
        }

        $result = ($this->generate(
                $postData,
                $this->getHashKeyByMode(),
                $this->getHashIVByMode()
            ) == $postData["CheckMacValue"]);

        return $result;
    }


    public function webhook()
    {
        $postData = $this->validated_array($_POST);

        if (isset($postData["CustomField1"]) && is_numeric($postData["CustomField1"])) {
            $orderId = $postData["CustomField1"];
            $order = wc_get_order($orderId);

            if ($order && $this->verifyECPayCallbackData($order, $postData)) {
                $rtnCode = $postData["RtnCode"];

                $order->add_meta_data('ec_payment_response_data', [
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
                } else {
                    $order->add_order_note(__('ECpay Failed', 'pb_ecpay_woo'), false, false);
                }

                echo "1|OK";
                exit;
            } else {
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
        add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
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


    public function render_ecpay_logistics_form($order_id)
    {
        $order = wc_get_order($order_id);
        if ($order) {
            $aioUrl = ($this->testmode) ? "https://logistics-stage.ecpay.com.tw/Express/create" : "https://logistics.ecpay.com.tw/Express/create";
            $metadata = $order->get_meta('ec_payment_form_data');
            $metadata["CheckMacValue"] = $this->generate(
                $metadata,
                $this->getHashKeyByMode(),
                $this->getHashIVByMode()
            );
            require_once PB_ECPAY_PLUGIN_DIR . "view/" . "ecpay_form.php";
        }
        return;
    }

    public function before_thankyou($order_id)
    {
        $order = wc_get_order($order_id);
        $order->update_status('processing');
        $order->save();
    }

    private function validated_array($value)
    {
        return is_array($value) ? array_map('wc_clean', array_map('stripslashes', $value)) : '';
    }

    private function webhook_name()
    {
        return $this->id;
    }

    private function generate(
        $arParameters = array(),
        $HashKey = '',
        $HashIV = ''
    )
    {
        $sMacValue = '';

        if (isset($arParameters["CheckMacValue"])) {
            unset($arParameters["CheckMacValue"]);
        }

        if (isset($arParameters)) {
            // arParameters 為傳出的參數，並且做字母 A-Z 排序 unset($arParameters['CheckMacValue']);
            ksort($arParameters); // 組合字串
            $sMacValue = 'HashKey=' . $HashKey;
            foreach ($arParameters as $key => $value) {
                $sMacValue .= '&' . $key . '=' . $value;
            }
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
            $sMacValue = hash('md5', $sMacValue);
            return strtoupper($sMacValue);
        }
    }

    private function generateRandomCharString($length = 10)
    {
        $characters = 'abcdefghjkmnpqrstuvwxyABCDEFGHKMNOPQRSTUVWXY';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
