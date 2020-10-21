<?php

class PBPaymentAdminController {
	function __construct() {
		$this->init();
	}
	
	public function init() {
		add_filter(
			'bulk_actions-edit-shop_order', 
			[$this, 'append_customized_order_status_bulk']
		);
	}

	public function append_customized_order_status_bulk($bulk_actions) {
		$bulk_actions['mark_pb-order'] = '將狀態變更為進度條測試用狀態';
		return $bulk_actions;
	}
	
	public function render(){
		require_once(PLUGIN_DIR."view/"."index.php");
	}
}