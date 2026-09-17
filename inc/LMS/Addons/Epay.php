<?php
namespace MineCloudvod\LMS\Addons;
use \MineCloudvod\Payment\Base;
defined( 'ABSPATH' ) || exit;

class Epay extends Base{

    private $id = 'epay';

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
        $method = $request['pay'];
        $appid = null;
        $appsecret = null;
        $notifyUrl = '';
        $return_url = urldecode(get_the_permalink( $post_id ));
        $gate = '';
        if( $method == 'epay_alipay' ){
            $appid = MINECLOUDVOD_SETTINGS['mcv_payment']['epay_alipay']['appId'];
            $appsecret = MINECLOUDVOD_SETTINGS['mcv_payment']['epay_alipay']['appSecret'];
            $method = 'alipay';
            $notifyUrl = get_rest_url() . 'mine-cloudvod/v1/epay_alipay_notify';
            $gate = MINECLOUDVOD_SETTINGS['mcv_payment']['epay_alipay']['epay_gate']??'';
        }
        if( $method == 'epay_wechat' ){
            $appid = MINECLOUDVOD_SETTINGS['mcv_payment']['epay_wechat']['appId'];
            $appsecret = MINECLOUDVOD_SETTINGS['mcv_payment']['epay_wechat']['appSecret'];
            $method = 'wxpay';
            $notifyUrl = get_rest_url() . 'mine-cloudvod/v1/epay_wechat_notify';
            $gate = MINECLOUDVOD_SETTINGS['mcv_payment']['epay_wechat']['epay_gate']??'';
        }
        if(!$gate){
            $gate = MINECLOUDVOD_SETTINGS['epay_gate']??'';
        }

        $data = [
            "pid" => $appid,
            "type" => $method,
            "notify_url"	=> $notifyUrl,
            "return_url"	=> $return_url,
            "out_trade_no"	=> $outTradeNo,
            "name"	=> $orderName,
            "money"	=> $payAmount,
            "sitename"	=> 'MineCloudVod',
            'sign_type'=>'MD5',
        ];
        ksort($data);
        reset($data);

        $sign = '';

        foreach ($data AS $key => $val) {
            if ($val == '' || $key == 'sign' || $key == 'sign_type') continue;
            if ($sign != '') {
                $sign .= "&";
            }
            $sign .= $key."=".$val;
        }
        $data['sign'] = md5( $sign . trim( $appsecret ) );
        
        return ['data' => $data, 'api'=> $gate.'submit.php'];
    }
    /**
     * 返回前端需要的js，用来弹出支付二维码，或者跳转到支付页面
     */
    public function handleScripts( $request ){
        $payment = $request['pay'];
        $name = '';
        $pic = '';
        $color = '';
        if( $payment == 'epay_wechat' ){
            $name = '微信';
            $pic = 'wxzf';
            $color = '#00b54b';
        } 
        if( $payment == 'epay_alipay' ){
            $name = '支付宝';
            $pic = 'alipay';
            $color = '#00a7ef';
        }
        $script = 'layer.open({
            type: 1,
            title: false,
            area: ["300px", "420px"],
            content: \'<div id="swal2-content" style="display: block;width:300px;text-align: center;"><div style=""> <h5 style="padding: 0;margin-top: 1.8em;margin-bottom: 0;"> 订单已生成 </h5> <div style="font-size: 16px;margin: 10px auto;">请在打开的链接中完成支付</div> <div align="center" class="qrcode"> <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="150px" height="150px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve" style="margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;"><path opacity="0.2" fill="#FF6700" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"></path><path fill="#FF6700" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z" transform="rotate(42.1171 20 20)"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 20 20" to="360 20 20" dur="2.5s" repeatCount="indefinite"></animateTransform></path></svg> </div> <div id="mcv_alipay_tips" style="width: 100%;background: #33465a;color: #f2f2f2;padding: 16px 0px;text-align: center;font-size: 14px;margin-top: 20px;background: '.$color.';position: absolute;bottom:0;"> 请使用支付宝扫一扫<br>请在２小时内完成支付<br> </div> </div></div>\'
        });
        
        var form = document.createElement("form");
        form.action = res.api;
        form.method = "POST";
        Object.keys(res.data).map(key => {
            let input = document.createElement("input");
            input.type = "hidden";
            input.name = key;
            input.value = decodeURIComponent(res.data[key]);

            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();';
        return $script;
    }

    private function mcv_trans(){
        $trans = [
            __('Easy Pay', 'mine-cloudvod'),
        ];
    }
}