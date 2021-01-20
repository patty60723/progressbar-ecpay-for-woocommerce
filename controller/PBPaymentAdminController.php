<?php

class PBPaymentAdminController {
	function __construct() {
		$this->init();
	}
	
	public function init() {
	    $this->checkOptions();
		$this->registerSettingLinkAfterActivated();
        $this->registerWooCommerceSettingsTab();
	}

	function checkOptions(){
	    $option = get_option('pb_payment_gateway_settings');

        if(!$option){
            add_option('pb_payment_gateway_settings', array(
                'enabled_ecpay' => true
            ));
        } else {
            if(!isset($option['enabled_ecpay'])){
                $option['enabled_ecpay'] = true;
                update_option('pb_payment_gateway_settings', $option);
            }
        }
    }

    function registerWooCommerceSettingsTab() {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 21);
        add_action('woocommerce_settings_tabs_pb_payment', array($this, 'settings_pb_payment_tab'));
        add_action('woocommerce_update_options_pb_payment', array($this, 'update_pb_payment_settings'));
    }

    function update_pb_payment_settings(){
	    $option = get_option('pb_payment_gateway_settings');
	    $settings = $this->get_pb_payment_settings();
	    $payment_fields = array_filter($settings, function($setting){
	        return $setting['class'] ?? null === 'enabled-checkbox';
        });

        $payment_fields = array_map(function($enabled_payment){
            $value = $_POST[$enabled_payment['id']] ?? false;
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);;
        }, $payment_fields);

        // 暫時寫死 settings name 與 payment_gateway 的 id 關聯，因為不知道新的金流服務的id規範是什麼
        $payment_gateway_id_map = [
            'enabled_ecpay' => 'pb_woo_ecpay'
        ];

        foreach($payment_fields as $payment_key => $payment_value){
            if($payment_value === false){
                $payment_gateway_id = $payment_gateway_id_map[$payment_key];
                $this->setGatewayEnabled($payment_gateway_id, 'no');
            }

            if($option){
                $option[$payment_key] = $payment_value;
                update_option('pb_payment_gateway_settings', $option);
            } else {
                add_option('pb_payment_gateway_settings', array(
                    $payment_key => $payment_value
                ));
            }
        }

        $enabled_payments = array_filter($payment_fields, fn($enabled) => $enabled);
        if(count($enabled_payments) === 1){
            $payment_key = array_key_first($enabled_payments);
            $payment_gateway_id = $payment_gateway_id_map[$payment_key];
            $this->setGatewayEnabled($payment_gateway_id, 'yes');
        }
    }

    function setGatewayEnabled($payment_gateway_id, $enabled){
        $payment_gateways = WC_Payment_Gateways::instance();
        $payment_gateway = $payment_gateways->payment_gateways()[$payment_gateway_id];
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
        $enable = get_option('pb_payment_gateway_settings')['enabled_ecpay'] ?? false;

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
                'value' => $enable ? 'yes' : ''
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
