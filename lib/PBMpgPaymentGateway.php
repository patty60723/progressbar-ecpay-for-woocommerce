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

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->title = __('Newebpay payment gateway', 'pb_ecpay_woo');
        $this->description = __('Checkout with Newebpay page.', 'pb_ecpay_woo');
        $this->testmode = 'yes' === $this->get_option( 'testmode', 'no' );

        // Update options
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );
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

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable this payment gateway', 'pb_ecpay_woo'),
                'type' => 'checkbox',
                'label' => __('Enabled', 'pb_ecpay_woo'),
                'default' => 'no',
            ),
        );
    }
}
