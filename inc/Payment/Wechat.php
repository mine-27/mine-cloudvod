<?php
namespace MineCloudvod\Payment;
use MineCloudvod\Libs\QRcode;

class Wechat extends Base {
    private $is_wechat, $is_mobile, $murl, $payment = 'wechat', $error = false;
    public function __construct( ) {

        if( version_compare( MINECLOUDVOD_VERSION, '2.1.0', '>' ) ){
            add_filter( 'mcv_payment_methods', [ $this, 'wechat_admin_options' ] );
        }
        else{
            add_action( 'mcv_add_admin_options_before_purchase', [ $this, 'wechat_admin_options_old' ] );
        }

        add_action('rest_api_init', [$this, 'register_routes']);

        add_filter( 'mcv_order_payment_methods', [ $this, 'add_payment_methods' ] );

        $this->is_wechat = mcv_is_wechat();
        // $this->is_mobile = \wp_is_mobile();
    }

    public function add_payment_methods( $methods ){
        if( MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['status']??false ){
            $methods[$this->payment] = __('Wechat Pay', 'mine-cloudvod');
        }
        return $methods;
    }

    public function register_routes(){
        /**
         * 支付回调
         */
        register_rest_route('mine-cloudvod/v1', '/wechat_notify', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'mcv_wechat_notify'],
                'permission_callback' => '__return_true',
                'args'                => [
                ]
        ]);
    }
    /**
     * 支付回调
     */
    public function mcv_wechat_notify( \WP_REST_Request $request ){
        $wechat = new WechatService();
        $result = $wechat->notify();
        if($result){
            $tradeno = $result['out_trade_no'];
            $tnTmp = explode( '_', $tradeno );
            $tradeno = $tnTmp[0];
            $wpdir = wp_get_upload_dir();
            $mcvdir = (isset($wpdir['default']['basedir'])?$wpdir['default']['basedir']:$wpdir['basedir']).'/mcv-cache';
            if( is_numeric( $tradeno ) ){
                $order = get_post( $tradeno );
                if( $order ){
                    //收款额度
                    $total_amount = sanitize_text_field( $result['total_fee'] / 100 );
                    $order_amount = mcv_lms_get_order_last_amount( $order->ID );
                    $order_items = get_post_meta( $order->ID, '_mcv_order_items', true );
                    $order_status = get_post_meta( $order->ID, '_mcv_order_status', true );
                    // if( $order_status != 'payed' && $total_amount == $order_amount && $order_items ){
                    if( $order_status != 'payed' && $order_items ){
                        // 支付平台内部交易号
                        $trade_no = sanitize_text_field( $result['transaction_id'] );
                        //付款时间
                        $gmt_payment = wp_date( 'Y-m-d H:i:s' );
                        
                        update_post_meta( $order->ID, '_mcv_order_status', 'payed' );
                        update_post_meta( $order->ID, '_mcv_order_transaction_id', $trade_no) ;
                        update_post_meta( $order->ID, '_mcv_order_gmt_payment', $gmt_payment );
                        update_post_meta( $order->ID, '_mcv_order_notify', $result );
                        
                        mcv_order_update_items( $order_items, $order->post_author, $order, $total_amount );
                        
                        update_post_meta( $order->ID, '_mcv_order_payment', $this->payment );
                    }
                    exit();
                }
            }
        }else{
            echo 'pay error';
        }
        exit;
    }
    public function wechat_admin_options( $payments ){
        $payments[] = array(
            'id'        => $this->payment,
            'type'      => 'fieldset',
            'title'     => __('Wechat', 'mine-cloudvod'),
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
                    'content' => __('微信官方支付.', 'mine-cloudvod'), 
                ),
                array(
                    'type'    => 'submessage',
                    'style'   => 'success',
                    'content' => '<a href="https://pay.weixin.qq.com/static/applyment_guide/applyment_index.shtml" target="_blank">微信商户接入指引</a>', 
                ),
                array(
                    'id'      => 'product',
                    'type'    => 'checkbox',
                    'title'   => __('Product', 'mine-cloudvod'),
                    'inline'  => true,
                    'options' => array(
                        '1'     => __('Native Payment', 'mine-cloudvod'),
                        '2'     => __('H5 Payment', 'mine-cloudvod'),
                        '3'     => __('JSAPI Payment', 'mine-cloudvod'),
                    ),
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'    => 'name',
                    'type'  => 'text',
                    'title' => __('Wechat Name' , 'mine-cloudvod'),
                    'dependency' => array('status', '==', true),
                    'default' => '微信支付',
                ),
                array(
                    'id'    => 'class',
                    'type'  => 'text',
                    'title' => '',
                    'dependency' => array('status', '==', 'none'),
                    'default' => 'MineCloudvod\Payment\Wechat',
                ),
                array(
                    'id'    => 'mchid',
                    'type'  => 'text',
                    'title' => __('MCHID' , 'mine-cloudvod'),
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'    => 'appId',
                    'type'  => 'text',
                    'title' => __('AppId' , 'mine-cloudvod'),
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'    => 'appSecret',
                    'type'  => 'text',
                    'title' => '服务号AppSecret',
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'    => 'apiKey',
                    'type'  => 'text',
                    'title' => __('APIv2' , 'mine-cloudvod'),
                    'dependency' => array('status', '==', true),
                ),
            ),
        );
        return $payments;
    }
    public function wechat_admin_options_old(){
        $prefix = 'mcv_settings';
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_payment',
            'title'  => __('Wechat', 'mine-cloudvod'),
            'icon'   => 'fab fa-weixin',
            'fields' => array(
                array(
                    'id'        => 'mcv_payment',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'type'    => 'submessage',
                            'style'   => 'warning',
                            'content' => __('微信官方支付.', 'mine-cloudvod'), 
                        ),
                        array(
                            'id'        => $this->payment,
                            'type'      => 'fieldset',
                            'title'     => __('Wechat', 'mine-cloudvod'),
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
                                    'style'   => 'success',
                                    'content' => '<a href="https://pay.weixin.qq.com/static/applyment_guide/applyment_index.shtml" target="_blank">微信商户接入指引</a>', 
                                ),
                                array(
                                    'id'      => 'product',
                                    'type'    => 'checkbox',
                                    'title'   => __('Product', 'mine-cloudvod'),
                                    'inline'  => true,
                                    'options' => array(
                                        '1'     => __('Native Payment', 'mine-cloudvod'),
                                        '2'     => __('H5 Payment', 'mine-cloudvod'),
                                        '3'     => __('JSAPI Payment', 'mine-cloudvod'),
                                    ),
                                    'dependency' => array('status', '==', true),
                                ),
                                array(
                                    'id'    => 'name',
                                    'type'  => 'text',
                                    'title' => __('Wechat Name' , 'mine-cloudvod'),
                                    'dependency' => array('status', '==', true),
                                    'default' => '微信支付',
                                ),
                                array(
                                    'id'    => 'class',
                                    'type'  => 'text',
                                    'title' => '',
                                    'dependency' => array('status', '==', 'none'),
                                    'default' => 'MineCloudvod\Payment\Wechat',
                                ),
                                array(
                                    'id'    => 'mchid',
                                    'type'  => 'text',
                                    'title' => __('MCHID' , 'mine-cloudvod'),
                                    'dependency' => array('status', '==', true),
                                ),
                                array(
                                    'id'    => 'appId',
                                    'type'  => 'text',
                                    'title' => __('AppId' , 'mine-cloudvod'),
                                    'dependency' => array('status', '==', true),
                                ),
                                array(
                                    'id'    => 'appSecret',
                                    'type'  => 'text',
                                    'title' => __('AppSecret' , 'mine-cloudvod'),
                                    'dependency' => array('status', '==', true),
                                ),
                                array(
                                    'id'    => 'apiKey',
                                    'type'  => 'text',
                                    'title' => __('APIv2' , 'mine-cloudvod'),
                                    'dependency' => array('status', '==', true),
                                ),
                            ),
                        ),
                    ),
                ),
            )
        ));
    }

    public function handlePayment( $payAmount, $outTradeNo, $orderName, $course_id, $request ){
        $orderName = substr( $orderName, 0, 126 );
        $outTradeNo .= '_' . time();

        $notifyUrl = get_rest_url() . 'mine-cloudvod/v1/wechat_notify';
        $course_url = get_the_permalink( $course_id );

        $product = MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['product'] ?? false;
        $wechat = new WechatService();
        $result = [
            'amount' => $payAmount,
        ];
        if( \wp_is_mobile() ){
            // JSAPI
            if( $this->is_wechat && $product && in_array('3', $product ) ){
                if( !isset( $request['code'] ) ){
                    
                    $baseUrl = urlencode(mcv_checkout_url( ['orderid' => $request['orderid'], 'payment' => $this->payment] ));
                    $url = $wechat->__CreateOauthUrlForCode($baseUrl);
                    $result['get_code_url'] = $url;
                }
                else{
                    $openid = $wechat->getOpenidFromMp( $request['code'] );
                    if( !isset( $openid['openid'] ) ){
                        $result['error'] = $openid;
                        $this->error = true;
                    }
                    else{
                        $response = $wechat->createJsBizPackage_jsapi( $openid['openid'], $payAmount, $outTradeNo, $orderName, $notifyUrl, time() );
                        // $jsApiParameters = json_encode($response);
                        $result['jsapiparas'] = $response;
                    }
                }
                return $result;
            }
            // H5
            if( $product && in_array('2', $product ) ){
                $murl = $wechat->createJsBizPackage_h5( $payAmount, $outTradeNo, $orderName, $notifyUrl, time() );
                if( $murl ){
                    $murl[0] = $murl[0].'&redirect_url='.urlencode($course_url);
                    $this->murl = $murl;
                    $result['murl'] = $murl;
                    return $result;
                }
            }
        }
        // Native
        $response = $wechat->createJsBizPackage_native( $payAmount, $outTradeNo, $orderName, $notifyUrl, time() );

        if( isset( $response['code_url'] ) ){
            $result['qrimg'] = $this->getQrcode( $response['code_url'] );
        }
        else{
            $result['msg'] = $response['err_code_des'] ?: $response['return_msg'];
        }
        return $result;
    }
    public function getQrcode($url){
        $errorCorrectionLevel = 'L'; //容错级别
        $matrixPointSize      = 6; //生成图片大小
        ob_start();
        QRcode::png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
        $data = ob_get_contents();
        ob_end_clean();

        $imageString = base64_encode($data);
        return 'data:image/jpeg;base64,'.$imageString;
    }
    /**
     * 返回需要执行的js代码
     */
    public function handleScripts( $request ){
        $payment = $request['pay'];
        $name = '微信';
        $script = '';
        $product = MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['product'] ?? false;
        if( \wp_is_mobile() ){
            // JSAPI
            if( $this->is_wechat && $product && in_array('3', $product ) ){
                if( !isset( $request['code'] ) ){
                    $script = mcv_trim("var form = document.createElement('form');
                    form.action = res.get_code_url;
                    form.method = 'POST';
                    document.body.appendChild(form);
                    form.submit();");
                }
                else{
                    if( $this->error ){
                        $script = 'layer.msg(res.error.errmsg);';
                    }
                    else{
                        $script = "function jsApiCall(){
                            WeixinJSBridge.invoke(
                                'getBrandWCPayRequest',
                                res.jsapiparas,
                                function(res){}
                            );
                        }if (typeof WeixinJSBridge == 'undefined'){
                            if( document.addEventListener ){
                                document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                            }else if (document.attachEvent){
                                document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                                document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                            }
                        }else{
                            jsApiCall();
                        }";
                    }
                }
                return $script;
            }
            // H5
            elseif( $product && in_array('2', $product ) ){
                if( $this->murl ){
                    $script = mcv_trim("var form = document.createElement('form');
                    form.action = res.murl[0];
                    form.method = 'POST';
                    document.body.appendChild(form);
                    form.submit();");
                    return $script;
                }
            }
        }
        // Native
        $script = 'if( res.qrimg ) openQRBox("'.MINECLOUDVOD_URL.'/static/img/wxzf.jpg",res.qrimg,"'.$name.'扫码支付 "+res.amount+" 元","'.$name.'", "#00b54b"); else{ if( res.return_code && res.return_code == "SUCCESS" ){layer.msg("订单已完成");}else{if(res?.msg)layer.msg(res.msg[0]);} }';
        
        return $script;
    }
}
class WechatService
{
    protected $mchid;
    protected $appid;
    protected $appKey;
    protected $apiKey;
    protected $payment = 'wechat';
    public function __construct($appid = '', $appKey = ''){
        $this->mchid = MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['mchid'];
        $this->apiKey = MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['apiKey'];
        if( $appid && $appKey ){
            $this->appid = trim( $appid );
            $this->appKey = trim( $appKey );
        }
        else{
            $this->appid = trim( MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['appId'] );
            $this->appKey = trim( MINECLOUDVOD_SETTINGS['mcv_payment'][$this->payment]['appSecret'] );
        }
        
    }

    /**
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     * @return openid
     */
	public function GetOpenidFromMp($code)
	{
		$url = $this->__CreateOauthUrlForOpenid($code);
		$res = wp_remote_get($url);
        if( is_wp_error($res) ){
            return false;
        }
        $res = wp_remote_retrieve_body($res);
		//取出openid
		$data = json_decode($res,true);
        return $data;
	}

    /**
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["secret"] = $this->appKey;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }

    /**
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * @return 返回构造好的url
     */
    public function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["redirect_uri"] = $redirectUrl;
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }
    /**
     * 拼接签名字符串
     * @param array $urlObj
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign") $buff .= $k . "=" . $v . "&";
        }
        $buff = trim($buff, "&");
        return $buff;
    }/**
     * 统一下单
     * @param string $openid 调用【网页授权获取用户信息】接口获取到用户在该公众号下的Openid
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 支付时间
     * @return string
     */
    public function createJsBizPackage_jsapi($openid, $totalFee, $outTradeNo, $orderName, $notifyUrl, $timestamp)
    {
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
        );
        // $orderName = iconv('GBK','UTF-8',$orderName);
        $unified = array(
            'appid' => $config['appid'],
            'attach' => 'pay',             //商家数据包，原样返回，如果填写中文，请注意转换为utf-8
            'body' => $orderName,
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::createNonceStr(),
            'notify_url' => $notifyUrl,
            'openid' => $openid,            //rade_type=JSAPI，此参数必传
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => '127.0.0.1',
            'total_fee' => intval($totalFee * 100),       //单位 转为分
            'trade_type' => 'JSAPI',
        );
        $unified['sign'] = self::getSign($unified, $config['key']);
        $responseXml = wp_remote_post('https://api.mch.weixin.qq.com/pay/unifiedorder', ['body' => self::arrayToXml($unified)]);
        if (is_wp_error($responseXml)) {
            return false;
        }
        $responseXml = wp_remote_retrieve_body($responseXml);
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            // die('parse xml error');
            return false;
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            // die($unifiedOrder->return_msg);
            return false;
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            // die($unifiedOrder->err_code);
            return false;
        }
        $arr = array(
            "appId" => $config['appid'],
            "timeStamp" => "$timestamp",        //这里是字符串的时间戳，不是int，所以需加引号
            "nonceStr" => self::createNonceStr(),
            "package" => "prepay_id=" . $unifiedOrder->prepay_id,
            "signType" => 'MD5',
        );
        $arr['paySign'] = self::getSign($arr, $config['key']);
        return $arr;
    }

    /**
     * 发起订单
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 订单发起时间
     * @return array
     */
    public function createJsBizPackage_native($totalFee, $outTradeNo, $orderName, $notifyUrl, $timestamp){
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
        );
        //$orderName = iconv('GBK','UTF-8',$orderName);
        $unified = array(
            'appid' => $config['appid'],
            'attach' => 'pay',             //商家数据包，原样返回，如果填写中文，请注意转换为utf-8
            'body' => $orderName,
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::createNonceStr(),
            'notify_url' => $notifyUrl,
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => '127.0.0.1',
            'total_fee' => intval($totalFee * 100),       //单位 转为分
            'trade_type' => 'NATIVE',
        );
        $unified['sign'] = self::getSign($unified, $config['key']);
        $responseXml = wp_remote_post('https://api.mch.weixin.qq.com/pay/unifiedorder', ['body' => self::arrayToXml($unified)]);
        if (is_wp_error($responseXml)) {
            return false;
        }
        $responseXml = wp_remote_retrieve_body($responseXml);
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            // die('parse xml error');
            return false;
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            return $unifiedOrder;
            // return false;
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            return $unifiedOrder;
            // return false;
        }
        $codeUrl = (array)($unifiedOrder->code_url);
        if(!$codeUrl[0]) return false;//exit('get code_url error');
        $arr = array(
            "appId" => $config['appid'],
            "timeStamp" => $timestamp,
            "nonceStr" => self::createNonceStr(),
            "package" => "prepay_id=" . $unifiedOrder->prepay_id,
            "signType" => 'MD5',
            "code_url" => $codeUrl[0],
        );
        $arr['paySign'] = self::getSign($arr, $config['key']);
        return $arr;
    }

    public function createJsBizPackage_h5($totalFee, $outTradeNo, $orderName, $notifyUrl, $timestamp)
    {
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
        );
        $scene_info = array(
            'h5_info' =>array(
                'type'=>'Wap',
                'wap_url'=>home_url(),
                'wap_name'=>get_bloginfo( 'name' ),
            )
        );
        $unified = array(
            'appid' => $config['appid'],
            'attach' => 'pay',             //商家数据包，原样返回，如果填写中文，请注意转换为utf-8
            'body' => $orderName,
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::createNonceStr(),
            'notify_url' => $notifyUrl,
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')),
            'total_fee' => intval($totalFee * 100),       //单位 转为分
            'trade_type' => 'MWEB',
            'scene_info'=>json_encode($scene_info)
        );
        $unified['sign'] = self::getSign($unified, $config['key']);
        $responseXml = wp_remote_post('https://api.mch.weixin.qq.com/pay/unifiedorder', ['body' => self::arrayToXml($unified)]);
        if (is_wp_error($responseXml)) {
            return false;
        }
        $responseXml = wp_remote_retrieve_body($responseXml);
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder->return_code != 'SUCCESS') {
            // die($unifiedOrder->return_msg);
            return false;
        }
        if($unifiedOrder->mweb_url){
            return $unifiedOrder->mweb_url;
        }
        return false;
    }
    public function notify()
    {
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
        );
        $postStr = '';
        if(version_compare(phpversion(), '7.0.0') >= 0){
			$postStr = file_get_contents('php://input');
		}else{
			$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
		}
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($postObj === false) {
            die('parse xml error');
        }
        if ($postObj->return_code != 'SUCCESS') {
            die(esc_html($postObj->return_msg));
        }
        if ($postObj->result_code != 'SUCCESS') {
            die(esc_html($postObj->err_code));
        }
        $arr = (array)$postObj;
        unset($arr['sign']);
        if (self::getSign($arr, $config['key']) == $postObj->sign) {
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return $arr;
        }
        return false;
    }
    /**
     * curl get
     *
     * @param string $url
     * @param array $options
     * @return mixed
     */
    // public static function curlGet($url = '', $options = array())
    // {
    //     $ch = curl_init($url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    //     if (!empty($options)) {
    //         curl_setopt_array($ch, $options);
    //     }
    //     //https请求 不验证证书和host
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //     $data = curl_exec($ch);
    //     curl_close($ch);
    //     return $data;
    // }
    // public static function curlPost($url = '', $postData = '', $options = array())
    // {
    //     if (is_array($postData)) {
    //         $postData = http_build_query($postData);
    //     }
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    //     curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
    //     if (!empty($options)) {
    //         curl_setopt_array($ch, $options);
    //     }
    //     //https请求 不验证证书和host
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //     $data = curl_exec($ch);
    //     curl_close($ch);
    //     return $data;
    // }
    public static function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }
    /**
     * 获取签名
     */
    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }
    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}