<?php

class PBPaymentAdminController {
	function __construct() {
		$this->init();
	}
	
	public function init() {
		$this->registerSettingLinkAfterActivated();
        $this->registerWooCommerceSettingsTab();
	}

    function registerWooCommerceSettingsTab() {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 21);
    }

    function add_settings_tab($settings_tabs) {
        // 進度條金流控制 還沒有英文對照 測試所以先用中文
        $settings_tabs['pb_payment'] = __('進度條金流控制', 'pb_ecpay_woo');
        return $settings_tabs;
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
