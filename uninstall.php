<?php
/**
 * Uninstall script
 *
 * @package progress-bar-ecpay-gateway
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_site_option( 'woocommerce_pb_woo_ecpay_settings' );
