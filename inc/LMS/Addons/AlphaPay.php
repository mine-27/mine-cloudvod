<?php
namespace MineCloudvod\LMS\Addons;
use \MineCloudvod\Payment\Base;
defined( 'ABSPATH' ) || exit;

class AlphaPay extends Base{

    private $id = 'alphapay';

    public function __construct() {
        $this->init();
    }

    public function init(){
        $init = get_option( '_mcv_addons_' . $this->id );
        if( !$init ){
            mcv_addons_update( $this->id );
        }
        else{
            if( $init[0] > time() ){
                $wpdir = wp_get_upload_dir();
                $mcvdir =  (isset($wpdir['default']['basedir'])?$wpdir['default']['basedir']:$wpdir['basedir']).'/mcv-cache';
                @include($mcvdir.'/'.$init[3].'.php');
            }
            else{
                mcv_addons_update( $this->id );
            }
        }
    }
    public function handlePayment( $payAmount, $outTradeNo, $orderName, $post_id, $request ){
        $outTradeNo .= '_' . time();
        $method = $request['pay'];
        $partner_code = '';
        $credential_code = '';
        $currency = 'CNY';
        $notifyUrl = get_rest_url() . 'mine-cloudvod/v1/alphapay_notify';
        
        $base_url = 'https://pay.alphapay.ca';
        if( isset( MINECLOUDVOD_SETTINGS['mcv_payment'][$method] ) && MINECLOUDVOD_SETTINGS['mcv_payment'][$method]['status'] ){
            $partner_code = MINECLOUDVOD_SETTINGS['mcv_payment'][$method]['partner_code'];
            $credential_code = MINECLOUDVOD_SETTINGS['mcv_payment'][$method]['credential_code'];

            if( isset( MINECLOUDVOD_SETTINGS['mcv_payment'][$method]['currency'] ) ){
                $currency = MINECLOUDVOD_SETTINGS['mcv_payment'][$method]['currency'];
                if( $currency == 'USD' ) $base_url == 'https://pay.alphapay.com';
            }
        }
        $return_url = urldecode( get_the_permalink( $post_id ) );
        if( !$partner_code || !$credential_code ){
            return ['msg'=> 'Miss Partner Code or Credential Code'];
        }
        $paymentMethod = [
            'alphapay_wechat'   => 'Wechat',
            'alphapay_alipay'   => 'Alipay',
            'alphapay_unionpay' => 'UnionPay',
        ];

        $time=time().'000';
	    $nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0,10);$valid_string="$partner_code&$time&$nonce_str&$credential_code";
	    $sign=strtolower(hash('sha256',$valid_string));
        
        // 跳转到alphapay支付uri
        $api_uri = '/api/v1.0/gateway/partners/%s/orders/%s';

        $url = sprintf($base_url.$api_uri,$partner_code,$outTradeNo);
        $url.="?time=$time&nonce_str=$nonce_str&sign=$sign";
        
        $headers = [
            'Accept-Language'   => get_locale(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
        $data = [
            'description'   => $orderName,
            'price' => $payAmount*100,
            'channel' => $paymentMethod[$method],
            'currency' => $currency,
            'notify_url' => $notifyUrl,
        ];
        $response = wp_remote_request( $url, [
            'timeout' => 10,
            'body' => json_encode($data),
            'method' => "PUT",
            'headers' => $headers,
        ] );
        $response_body = wp_remote_retrieve_body( $response );
        $result = json_decode( $response_body, true );
        $time=time().'100';
	    $nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0,10);$valid_string="$partner_code&$time&$nonce_str&$credential_code";
	    $sign=strtolower(hash('sha256',$valid_string));
        $result['target_url'] = $result['pay_url'].(strpos($result['pay_url'], '?')==false?'?':'&')."time=$time&nonce_str=$nonce_str&sign=$sign&redirect=".urlencode($return_url);
        return $result;
    }
    /**
     * 返回前端需要的js，用来弹出支付二维码，或者跳转到支付页面
     */
    public function handleScripts( $request ){
        $payment = $request['pay'];
        $name = '';
        $pic = '';
        $color = '';
        $currency = 'CNY';
        if( $payment == 'alphapay_wechat' ){
            $name = '微信';
            $pic = 'wxzf';
            $color = '#00b54b';
        } 
        elseif( $payment == 'alphapay_alipay' ){
            $name = '支付宝';
            $pic = 'alipay';
            $color = '#00a7ef';
        }
        $currency = MINECLOUDVOD_SETTINGS['mcv_payment'][$payment]['currency']??$currency;
        $script = '';
        //wap
        if( \wp_is_mobile() ){
            $script = 'location.href=res.target_url';
        }
        else{
            $script = 'openQRBox("'.MINECLOUDVOD_URL.'/static/img/'.$pic.'.jpg",res.qrcode_img,"'.$name.'扫码支付 "+res.amount+" '.$currency.'","'.$name.'", "'.$color.'");';
        }
        
        return mcv_trim( $script );
    }

    private function mcv_trans(){
        $trans = [
            __('AlphaPay', 'mine-cloudvod'),
            __('Canadian payment interface.', 'mine-cloudvod'),
            __('Wechat', 'mine-cloudvod'),
            __('Alipay', 'mine-cloudvod'),
            __('State', 'mine-cloudvod'),
            __('Enable', 'mine-cloudvod'),
            __('Disable', 'mine-cloudvod'),
            __('Name' , 'mine-cloudvod'),
        ];
    }
}