<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;

class Options{
    public $prefix = 'mcv_settings';
    public function __construct() {
        $this->init_payments();

        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'payment_admin_options' ) );
    }

    public function payment_admin_options(){
        /**
         * 支付方式过滤器
         */
        $payments = apply_filters( 'mcv_payment_methods', array() );
        \MCSF::createSection( $this->prefix, array(
            'id'    => 'mcv_payment',
            'title' => __('Payment Gateway', 'mine-cloudvod'),
            'icon'  => 'fas fa-dollar-sign',
            'fields' => [
                [
                    'type'      => 'submessage',
                    'style'     => 'success',
                    'content'   => __( 'Drag and drop login methods can be sorted.', 'mine-cloudvod' ),
                ],
                [
                    'id'        => 'mcv_payment',
                    'class'     => 'mcv_payment',
                    'type'      => 'sortable',
                    'fields'    => $payments,
                ],
            ]
        ));
    }

    public function init_payments(){
        $payments = [
            'alipay' => 'MineCloudvod\Payment\Alipay',
            'wechat' => 'MineCloudvod\Payment\Wechat',
            'hupijiao' => 'MineCloudvod\Payment\Hupijiao',
            'offline' => 'MineCloudvod\Payment\Offline',
            'paypal' => 'MineCloudvod\Payment\Paypal',
        ];
        /**
         * 支付class过滤器，注册支付的class，在class中处理支付的逻辑
         */
        $payments = apply_filters( 'mcv_order_payment_classes', $payments );
        foreach( $payments as $payment ){
            if( is_string( $payment ) && class_exists( $payment ) ){
                $payment = new $payment();
            }
        }
    }
}