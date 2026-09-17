<?php
namespace MineCloudvod\Payment;

class Offline extends Base {
    private $murl, $payment = 'offline';
    public function __construct( ) {

        add_filter( 'mcv_payment_methods', [ $this, 'admin_options' ] );

        add_filter( 'mcv_order_payment_methods', [ $this, 'add_payment_methods' ] );

    }

    public function add_payment_methods( $methods ){
        if( MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['status']??false ){
            $methods[$this->payment] = __('Offline Payment', 'mine-cloudvod');
        }
        return $methods;
    }

    public function admin_options( $payments ){
        $payments[] = array(
            'id'        => $this->payment,
            'type'      => 'fieldset',
            'title'     => __('Offline Payment', 'mine-cloudvod'),
            'fields'    => array(
                array(
                    'id'    => 'status',
                    'type'  => 'switcher',
                    'title' => __('State', 'mine-cloudvod'),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => false,
                ),
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => '线下支付需要手动处理订单状态', 
                ),
                array(
                    'id'    => 'name',
                    'type'  => 'text',
                    'title' => __('Offline Payment' , 'mine-cloudvod'),
                    'dependency' => array('status', '==', true),
                    'default' => __('Offline Payment' , 'mine-cloudvod'),
                ),
                array(
                    'id'    => 'desc',
                    'type'  => 'wp_editor',
                    'title' => '内容',
                    'dependency' => array('status', '==', true),
                    'after' => '这里添加线下支付的银行卡号或收款二维码',
                    'default' => '支付后联系微信：xxxxxxxx'
                ),
                array(
                    'id'    => 'class',
                    'type'  => 'text',
                    'title' => '',
                    'dependency' => array('status', '==', 'none'),
                    'default' => 'MineCloudvod\Payment\Offline',
                ),
            ),
        );
        return $payments;
    }

    public function handlePayment( $payAmount, $outTradeNo, $orderName, $course_id, $request ){

        $desc = MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['desc'] ?? false;
        $result = [
            'id' => $outTradeNo,
            'amount' => $payAmount,
            'desc' => $desc,
        ];
        return $result;
    }
    /**
     * 返回需要执行的js代码
     */
    public function handleScripts( $request ){
        $script = 'if( res.desc ) layer.open({
            type: 1,
            title: "请备注订单ID: "+res.id+" 支付金额："+res.amount,
            area: ["360px"],
            content: "<div style=\"padding:12px;\">"+res.desc+"</div>"
        });';
        
        return $script;
    }
}