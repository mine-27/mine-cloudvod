<?php
namespace MineCloudvod\LMS\Addons;
use \MineCloudvod\Payment\Base;
defined( 'ABSPATH' ) || exit;

class Haipay extends Base{

    private $id = 'haipay';

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
    
    function pivate_key_encrypt($data, $pivate_key) {
        $pivate_key = '-----BEGIN PRIVATE KEY-----'."\n".$pivate_key."\n".'-----END PRIVATE KEY-----';
        $pi_key = openssl_pkey_get_private($pivate_key);
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $pi_key);
            $crypto .= $encryptData;
        }

        return base64_encode($crypto);
    }
    public function handlePayment( $payAmount, $outTradeNo, $orderName, $post_id, $request ){
        $current_user = wp_get_current_user();
        if( !$request['email'] || !$request['lname'] || !$request['fname'] || !$request['phone'] ){
            return [
                'pay' => $request['pay'],
                'orderid' => $request['orderid'],
                'needs' => [
                    'email' => $current_user->user_email,
                    'fname' => $current_user->user_firstname,
                    'lname' => $current_user->user_lastname,
                    'phone' => get_user_meta( $current_user->ID, 'phone', true ),
                ]
            ];
        }
        $email = $request['email'];
        $fname = $request['fname'];
        $lname = $request['lname'];
        $phone = $request['phone'];
        if($phone){
            update_user_meta( $current_user->ID, 'phone', $phone );
        }
        $outTradeNo .= '_' . time();
        $method = $request['pay'];
        $appid = null;
        $appsecret = null;
        $notifyUrl = '';
        $return_url = urldecode( get_the_permalink( $post_id ) );
        if( $method == 'haipay' ){
            $appid = MINECLOUDVOD_SETTINGS['mcv_payment']['haipay']['appId'];
            $appkey = MINECLOUDVOD_SETTINGS['mcv_payment']['haipay']['appKey'];
            $privatekey = MINECLOUDVOD_SETTINGS['mcv_payment']['haipay']['privatekey'];
            $method = 'haipay';
            $notifyUrl = get_rest_url() . 'mine-cloudvod/v1/haipay_notify';
        }

        $data = [
            "amount"	=> str_replace("0+?$", '', $payAmount),
            "app"	    => 'MineCloudVod',
            'currency'  =>'MYR',
            'merchantOrderNo'  =>$outTradeNo,
            'notifyUrl'  =>$notifyUrl,
            'redirectUrl' => $return_url,
            'timestamp'  => time(),
            'osType' => 'android',
            'email' => $email,
            'lastName' => $lname,
            'firstName' => $fname,
            'phone' => $phone,
        ];
        ksort($data);
        reset($data);

        $sign = '';

        foreach ($data as $key => $val) {
            $sign .= $val;
        }
        $sign = $this->pivate_key_encrypt($sign, $privatekey);
        $paras = [
            'headers' => [
                'X-MERCHANT-CODE' => $appid,
                'X-API-KEY' => $appkey,
                'Content-Type' => 'application/json;charset=utf-8',
            ],
            'body' => json_encode([
                'param' => $data,
                'sign'  => $sign,
            ])
        ];
        if( isset( MINECLOUDVOD_SETTINGS['mcv_payment']['haipay']['haipay_gate'] ) ){
            $gate = MINECLOUDVOD_SETTINGS['mcv_payment']['haipay']['haipay_gate'];
        }
        else{
            $gate = MINECLOUDVOD_SETTINGS['haipay_gate']??'';
        }
        $apiResponse = wp_remote_post($gate.'/mys/openapi/paymentH5',$paras);
        if( is_wp_error($apiResponse) ){
            return ['msg'=> __('no response', 'mine-cloudvod')];
        }
        $result = json_decode( wp_remote_retrieve_body($apiResponse) );
        return ['api'=> $result->data->payUrl];
    }
    /**
     * 返回前端需要的js，用来弹出支付二维码，或者跳转到支付页面
     */
    public function handleScripts( $request ){
        $payment = $request['pay'];
        $name = '';
        $pic = '';
        $color = '';
        if( $payment == 'haipay' ){
            $name = __('HaiPay', 'mine-cloudvod');
            $color = '#7661AA';
        }
        $script = '
        const handlePay = (res) => {
            layer.closeAll();
            layer.open({
                type: 1,
                title: false,
                area: ["300px", "420px"],
                content: \'<div id="swal2-content" style="display: block;width:300px;text-align: center;"><div style=""> <h5 style="padding: 0;margin-top: 1.8em;margin-bottom: 0;"> '.__('Order generated', 'mine-cloudvod').' </h5> <div style="font-size: 16px;margin: 10px auto;">'.__('Complete payment in the link that opens', 'mine-cloudvod').'</div> <div align="center" class="qrcode"> <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="150px" height="150px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve" style="margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;"><path opacity="0.2" fill="#FF6700" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"></path><path fill="#FF6700" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z" transform="rotate(42.1171 20 20)"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 20 20" to="360 20 20" dur="2.5s" repeatCount="indefinite"></animateTransform></path></svg> </div> <div id="mcv_alipay_tips" style="width: 100%;background: #33465a;color: #f2f2f2;padding: 16px 0px;text-align: center;font-size: 14px;margin-top: 20px;background: '.$color.';position: absolute;bottom:0;">'.__('Please complete payment within 2 hours', 'mine-cloudvod').'<br> </div> </div></div>\'
            });
            
            var form = document.createElement("form");
            form.action = res.api;
            form.method = "POST";
            form.target = "_blank";
            document.body.appendChild(form);
            form.submit();
        };
            layer.open({
                type: 1,
                title: false,
                area: ["300px", "320px"],
                content: \'<style>.hp-form{font-size: 16px;margin: 10px auto;}.hp-form input{padding: 0.6rem;border-radius: 0.3rem;border: 1px solid #69C5DF;}</style><div id="swal2-content" style="display: block;width:300px;text-align: center;"><div style=""> <h5 style="padding: 0;margin-top: 1.8em;margin-bottom: 0;"> '.__('Complete the information', 'mine-cloudvod').' </h5> <div class="hp-form"><input id="hp-fname" placeholder="First Name" value="\'+res.needs.fname+\'"/></div> <div class="hp-form"><input id="hp-lname" placeholder="Last Name" value="\'+res.needs.lname+\'"/></div> <div class="hp-form"><input id="hp-email" placeholder="EMail" value="\'+res.needs.email+\'"/></div> <div class="hp-form"><input id="hp-phone" placeholder="Phone" value="\'+res.needs.phone+\'"/></div>  <div id="mcv_haipay_submit" style="width: 100%;cursor:pointer;color: #f2f2f2;padding: 16px 0px;text-align: center;font-size: 14px;margin-top: 20px;background: '.$color.';position: absolute;bottom:0;">'.__('Submit', 'mine-cloudvod').'<br> </div> </div></div>\',
                success: function(layero, index){
                    jQuery("#mcv_haipay_submit", layero).on("click", function(){
                        let fname = jQuery("#hp-fname").val();
                        let lname = jQuery("#hp-lname").val();
                        let email = jQuery("#hp-email").val();
                        let phone = jQuery("#hp-phone").val();
                        if(!fname){
                            jQuery("#hp-fname").trigger("focus");
                            return;
                        }
                        else if(!lname){
                            jQuery("#hp-lname").trigger("focus");
                            return;
                        }
                        else if(!email){
                            jQuery("#hp-email").trigger("focus");
                            return;
                        }
                        else if(!phone){
                            jQuery("#hp-phone").trigger("focus");
                            return;
                        }
                        let ld = layer.load();
                        let odata ={
                            "pay": res.pay,
                            "orderid": res.orderid,
                            "fname": fname,
                            "lname": lname,
                            "email": email,
                            "phone": phone,
                        };
                        wp.apiFetch( {
                            path: `mine-cloudvod/v1/member/order/create`,
                            method: "POST",
                            data: odata,
                        } ).then((res)=>{
                            if(res?.api){
                                handlePay(res);
                            }
                            else{
                                layer.close(ld);
                                layer.msg( res.msg );
                            }
                        }).catch(( e ) => {
                            layer.closeAll();
                            layer.msg( e.message );
                        } );
                    })
                }
            });
        ';
        return mcv_trim( $script );
    }

    private function mcv_trans(){
        $trans = [
            __('HaiPay', 'mine-cloudvod'),
            __('HaiPay mainly provides localized payment solutions in the Southeast Asian.', 'mine-cloudvod'),
            __('State', 'mine-cloudvod'),
            __('Enable', 'mine-cloudvod'),
            __('Disable', 'mine-cloudvod'),
            __('Name' , 'mine-cloudvod'),
            __('no response', 'mine-cloudvod'),
            __('Order generated', 'mine-cloudvod'),
            __('Complete payment in the link that opens', 'mine-cloudvod'),
            __('Please complete payment within 2 hours', 'mine-cloudvod'),
            __('Complete the information', 'mine-cloudvod'),
            __('Submit', 'mine-cloudvod'),
        ];
    }
}