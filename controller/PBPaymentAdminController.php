<?php

class PBPaymentAdminController {
    private $currentOptions;

    function __construct() {
        $this->defaultOptions = [
            'enabled_ecpay' => true,
            'enabled_transport' => true,
        ];
        $this->optionValidKeys = [
            ...array_keys($this->defaultOptions)
        ];
        $this->init();
    }

    public function init() {
        $this->currentOptions = get_option('pb_payment_gateway_settings', false);
        $this->checkOptions();
        $this->registerSettingLinkAfterActivated();
        $this->registerWooCommerceSettingsTab();
    }

    function checkOptions(){
        if ($this->currentOptions === false) {
            $this->currentOptions = $this->defaultOptions;
            add_option('pb_payment_gateway_settings', $this->currentOptions);
        } else if($this->currentOptionsInvalidAndFix()){
            update_option('pb_payment_gateway_settings', $this->currentOptions);
        }
    }

    function currentOptionsInvalidAndFix(){
        $invalid = false;

        foreach($this->defaultOptions as $key => $value){
            if(!array_key_exists($key, $this->currentOptions)){
                $invalid = true;
                $this->currentOptions[$key] = $value;
            }
        }

        foreach($this->currentOptions as $key => $value){
            if(!in_array($key, $this->optionValidKeys)){
                $invalid = true;
                unset($this->currentOptions[$key]);
            }
        }

        return $invalid;
    }

    function registerWooCommerceSettingsTab() {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 21);
        add_action('woocommerce_settings_tabs_pb_payment', array($this, 'settings_pb_payment_tab'));
        add_action('woocommerce_update_options_pb_payment', array($this, 'update_pb_payment_settings'));
    }

    function update_pb_payment_settings(){
        $settings = $this->get_pb_payment_settings();
        $payment_fields = array_filter($settings, function($setting){
            return $setting['class'] ?? null === 'enabled-checkbox';
        });

        $payment_fields = array_map(function($enabled_payment){
            $value = $_POST[$enabled_payment['id']] ?? false;
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }, $payment_fields);

        // 暫時寫死 settings name 與 payment_gateway 的 id 關聯，因為不知道新的金流服務的id規範是什麼
        $payment_gateway_id_map = [
            'enabled_ecpay' => 'pb_woo_ecpay',
            'enabled_transport' => 'pb_woo_ecpay_transport'
        ];

        foreach($payment_fields as $payment_key => $payment_value){
            $this->currentOptions[$payment_key] = $payment_value;
        }
        update_option('pb_payment_gateway_settings', $this->currentOptions);

        foreach($payment_fields as $payment_key => $payment_value){
            $value = $payment_value ? 'yes' : 'no';
            $payment_gateway_id = $payment_gateway_id_map[$payment_key];
            $this->setGatewayEnabled($payment_gateway_id, $value);
        }

//        $enabled_payments = array_filter($payment_fields, fn($enabled) => $enabled);
//        if(count($enabled_payments) === 1){
//            $payment_key = array_key_first($enabled_payments);
//            $payment_gateway_id = $payment_gateway_id_map[$payment_key];
//            $this->setGatewayEnabled($payment_gateway_id, 'yes');
//        }
    }

    function setGatewayEnabled($payment_gateway_id, $enabled){
        $payment_gateways = WC_Payment_Gateways::instance();
        $payment_gateway = false;
        foreach($payment_gateways->payment_gateways() as $gateway){
            if(($gateway->id ?? null) === $payment_gateway_id){
                $payment_gateway = $gateway;
                break;
            }
        }

        if($payment_gateway){
            $option_key = $payment_gateway->get_option_key();
            $option = get_option($option_key);
            if($option){
                $option['enabled'] = $enabled;
                update_option($option_key, $option);
            }
        }
    }

    function add_settings_tab($settings_tabs) {
        // 進度條金流控制 還沒有英文對照 測試所以先用中文
        $settings_tabs['pb_payment'] = __('進度條金流控制', 'pb_ecpay_woo');
        return $settings_tabs;
    }

    function settings_pb_payment_tab(){
        woocommerce_admin_fields($this->get_pb_payment_settings());
    }

    function get_pb_payment_settings(){
        $enable_ecpay = $this->currentOptions['enabled_ecpay'] ?? false;
        $enable_transport = $this->currentOptions['enabled_transport'] ?? false;

        $settings = array(
            'section_title' => array(
                'name'     => __('進度條金流控制', 'pb_ecpay_woo'),
                'type'     => 'title',
                'id' => 'wc_settings_tab_pb_payment_section_title'
            ),
            'enabled_ecpay' => array(
                'name' => __('Enable ECPay', 'pb_ecpay_woo'),
                'desc' => __('Enable ECPay', 'pb_ecpay_woo'),
                'type' => 'checkbox',
                'id' => 'wc_settings_tab_pb_payment_enabled_ecpay_checkbox',
                'class' => 'enabled-checkbox',
                'value' => $enable_ecpay ? 'yes' : ''
            ),
            'enabled_transport' => array(
                'name' => __('Enable Transport', 'pb_ecpay_woo'),
                'desc' => __('Enable Transport', 'pb_ecpay_woo'),
                'type' => 'checkbox',
                'id' => 'wc_settings_tab_pb_payment_enabled_transport_checkbox',
                'class' => 'enabled-checkbox',
                'value' => $enable_transport ? 'yes' : ''
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_pb_payment_section_end'
            )
        );

        return apply_filters('wc_settings_pb_payment_settings', $settings);
    }

    function registerSettingLinkAfterActivated(){
        $plugin = PB_ECPAY_PLUGIN;
        add_filter("plugin_action_links_$plugin", array($this, 'plugin_action_links_hooks'));
    }

    function plugin_action_links_hooks($links){
        $url = admin_url("admin.php?page=wc-settings&tab=checkout&section=pb_woo_ecpay");
        $url = esc_url($url);
        $settings_link = "<a href='$url'>" . __('Settings', 'pb_ecpay_woo') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
