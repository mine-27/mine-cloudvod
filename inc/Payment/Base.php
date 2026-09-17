<?php
namespace MineCloudvod\Payment;

if ( ! defined( 'ABSPATH' ) ) exit;
abstract class Base {
    /**
     * 处理服务端支付逻辑，返回处理结果
     */
    abstract function handlePayment( $payAmount, $outTradeNo, $orderName, $course_id, $method );
    /**
     * 返回前端需要的js，用来弹出支付二维码，或者跳转到支付页面
     */
    abstract function handleScripts( $payment );
}