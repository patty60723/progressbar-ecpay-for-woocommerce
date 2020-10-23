<?php

class PBPaymentAdminController {
	function __construct() {
		$this->init();
	}
	
	public function init() {
		$this->registerSettingLinkAfterActivated();
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