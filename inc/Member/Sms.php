<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Member;
use MineCloudvod\Libs\QRcode;

class Sms {
    private $login_id = 'sms';
    public function __construct( ) {

        add_action('rest_api_init', [$this, 'register_routes']);

        add_filter( 'mcv_user_login_options', [ $this, 'login_options' ] );

        add_filter( 'mcv_login_script', [ $this, 'login_script' ], 10, 2 );
        
    }

    public function login_script( $script, $key ){
        if( $key == $this->login_id ){
            $is_user_logged_in = is_user_logged_in();
            $script = mcv_trim( '
                jQuery(function(){
                    let html = \'<div class="mcv-uc-login"><div class="components-base-control text"><div class="components-base-control__field"><input class="components-text-control__input" type="text" id="mcv-uc-phone" placeholder="请输入手机号" name="phone" autocomplete="phone"></div></div><div class="components-base-control text"><div class="components-base-control__field" style="position: relative;"><input class="components-text-control__input" type="text" id="mcv-uc-check-code" placeholder="验证码" autocomplete="off"><button id="mcv-uc-send-code" style="border: 0;background: transparent;position: absolute;right: 0;padding: 0 12px;line-height: 48px;color: var(--wp--preset--color--maincolor, #1769fe);cursor: pointer;">发送验证码</button></div></div><p class="mcv-uc-protocol checked">我已阅读并同意<a class="mcv-privacy-btn">《服务协议和隐私政策》</a></p><div class="components-base-control text"><div class="components-base-control__field"><input class="components-text-control__input login-btn" type="button" id="mcv-uc-phone-login" value="'.($is_user_logged_in?'绑定':'登录').'" style="color:#fff;border:0;cursor:pointer;"></div></div></div>\';
                    jQuery("#login_container").html(html);
                    if(!window?.mcvmsgtime) window.mcvmsgtime = 0;
                    jQuery("#mcv-uc-send-code").on("click", function(){
                        let phone = jQuery("#mcv-uc-phone").val();
                        if( !phone ){
                            jQuery("#mcv-uc-phone").focus();
                            return;
                        }
                        if( !/^1[3456789]\d{9}$/.test(phone) ){
                            layer.msg("手机号码格式错误");
                            jQuery("#mcv-uc-phone").focus();
                            return;
                        }

                        if( window.mcvmsgtime > 0 ){
                            return;
                        }
                        if( !jQuery(".mcv-uc-protocol").hasClass("checked")){
                            layer.msg("请先同意服务协议和隐私政策");
                            return;
                        }
                        window.mcvmsgtime = 60;
                        let timer = setInterval(function(){
                            window.mcvmsgtime--;
                            jQuery("#mcv-uc-send-code", "body").html(window.mcvmsgtime+"秒后重发");
                            if(window.mcvmsgtime <= 0){
                                clearInterval(timer);
                                jQuery("#mcv-uc-send-code").html("发送验证码");
                            }
                        }, 1000);
                        wp.apiFetch({
                            path: "/mine-cloudvod/v1/member/send_code",
                            method: "POST",
                            data: {
                                phone: phone,
                            }
                        }).then(function(res){
                            if(res.status == "0"){
                                layer.msg("验证码发送失败（"+res.msg+"）");
                            }
                            if(res.status == "1"){
                                if(res.data.code == "OK"){
                                    layer.msg("验证码发送成功");
                                    jQuery("#mcv-uc-check-code").val("").focus();
                                }else{
                                    layer.msg("验证码发送失败（"+res.data.message+"）");
                                }
                            }
                        });
                    });
                    jQuery("#mcv-uc-phone-login").on("click", function(){
                        let phone = jQuery("#mcv-uc-phone").val();
                        let code = jQuery("#mcv-uc-check-code").val();
                        if( !phone ){
                            jQuery("#mcv-uc-phone").focus();
                            return;
                        }
                        if( !/^1[3456789]\d{9}$/.test(phone) ){
                            layer.msg("手机号码格式错误");
                            jQuery("#mcv-uc-phone").focus();
                            return;
                        }
                        if( !code ){
                            layer.msg("请输入验证码");
                            jQuery("#mcv-uc-check-code").focus();
                            return;
                        }
                        wp.apiFetch({
                            path: "/mine-cloudvod/v1/member/sms_login",
                            method: "POST",
                            data: {
                                phone: phone,
                                code: code,
                            },
                        }).then(function(res){
                            if(res.status == "0"){
                                layer.msg("'.($is_user_logged_in?'绑定':'登录').'失败（"+res.msg+"）");
                            }
                            if(res.status == "1"){
                                if(res.data.uid){
                                    layer.msg("'.($is_user_logged_in?'绑定':'登录').'成功",{time:1000}, function(){
                                        window.location.reload();
                                    });
                                }else{
                                    layer.msg("'.($is_user_logged_in?'绑定':'登录').'失败（"+res.msg+"）");
                                }
                            }
                        });
                    });
                                
                });
            ' );
        }
        return $script;
    }

    public function login_options( $login3 ){
        $ops = false;
        if( isset( MINECLOUDVOD_SETTINGS[$this->login_id] ) ) $ops = MINECLOUDVOD_SETTINGS[$this->login_id];
        $login3[] = array(
            'id'        => $this->login_id,
            'type'      => 'fieldset',
            'title'     => '短信登录',
            'fields'    => array(
                array(
                    'id'    => 'status',
                    'type'  => 'switcher',
                    'title' => __('State', 'mine-cloudvod'),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => $ops ? $ops['status'] : false,
                ),
                array(
                    'id'    => 'title',
                    'type'  => 'text',
                    'title' => __('Title', 'mine-cloudvod'),
                    'dependency' => array('status', '==', true),
                    'default' => '验证码登录',
                ),
                [ // 短信服务商
                    'id'      => 'company',
                    'type'    => 'button_set',
                    'title'   => '短信服务商',
                    'inline'  => true,
                    'options' => [
                        'aliyun'    => '阿里云',
                    ],
                    'dependency' => array('status', '==', true),
                    'default'   => 'aliyun',
                ],
                array(
                    'id'    => 'AppID',
                    'type'  => 'text',
                    'title' => 'AccessKeyId',
                    'dependency' => array('status', '==', true),
                    'default' => $ops ? $ops['AppID'] : false,
                ),
                array(
                    'id'    => 'AppSecret',
                    'type'  => 'text',
                    'title' => 'AccessKeySecret',
                    'dependency' => array('status', '==', true),
                    'default' => $ops ? $ops['AppSecret'] : false,
                ),
                array(
                    'id'    => 'sign',
                    'type'  => 'text',
                    'title' => '签名名称',
                    'desc'  => '系统赠送签名名称，如"恒创联众"。请在阿里云控制台-短信认证参数配置-赠送签名配置中查看',
                    'dependency' => array('status', '==', true),
                    'default' => $ops ? $ops['sign'] : '',
                ),
                array(
                    'id'    => 'template',
                    'type'  => 'text',
                    'title' => '模板CODE',
                    'desc'  => '系统赠送模板CODE。登录/注册:100001, 修改绑定:100002, 重置密码:100003, 绑定新手机:100004, 验证绑定:100005',
                    'dependency' => array('status', '==', true),
                    'default' => $ops ? ($ops['template'] ?: '100001') : '100001',
                ),
            ),
        );
        return $login3;
    }
    public function register_routes(){
        /**
         * 登录回调
         */
        register_rest_route('mine-cloudvod/v1', '/member/send_code', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'send_code'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'phone' => [
                        'type' => 'string'
                    ],
                ]
        ]);
        /**
         * 登录回调
         */
        register_rest_route('mine-cloudvod/v1', '/member/sms_login', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'mcv_login'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'code' => [
                        'type' => 'string'
                    ],
                    'phone' => [
                        'type' => 'string'
                    ],
                ]
        ]);
    }
    /**
     * 阿里云 POP API 专用 URL 编码
     */
    private function percent_encode( $str ) {
        $res = urlencode( $str );
        $res = str_replace( ['+', '*', '%7E'], ['%20', '%2A', '~'], $res );
        return $res;
    }

    /**
     * 调用阿里云号码认证服务 OpenAPI (Dypnsapi)
     * 使用 POP RPC 签名方式 (HMAC-SHA1)
     */
    private function aliyun_dypns_api( $accessKeyID, $accessKeySecret, $action, $params ) {
        $apiParams = array_merge( $params, [
            'AccessKeyId'      => $accessKeyID,
            'Action'           => $action,
            'Format'           => 'JSON',
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureNonce'   => uniqid( wp_rand( 0, 0xffff ), true ),
            'SignatureVersion' => '1.0',
            'Timestamp'        => gmdate( 'Y-m-d\TH:i:s\Z' ),
            'Version'          => '2017-05-25',
        ] );

        // 按参数名排序
        ksort( $apiParams );

        // 构建规范化查询字符串
        $canonicalized = '';
        foreach ( $apiParams as $key => $value ) {
            $canonicalized .= '&' . $this->percent_encode( $key ) . '=' . $this->percent_encode( $value );
        }
        $canonicalized = substr( $canonicalized, 1 );

        // 构造待签名字符串
        $stringToSign = 'POST' . '&' . $this->percent_encode( '/' ) . '&' . $this->percent_encode( $canonicalized );

        // 计算签名
        $signature = base64_encode( hash_hmac( 'sha1', $stringToSign, $accessKeySecret . '&', true ) );
        $apiParams['Signature'] = $signature;

        // 发起 HTTPS POST 请求 — 使用 WordPress HTTP API
        $response = wp_remote_post( 'https://dypnsapi.aliyuncs.com/', [
            'headers'   => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
            'body'      => http_build_query( $apiParams, '', '&', PHP_QUERY_RFC3986 ),
            'timeout'   => 10,
            'sslverify' => false,
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'Code' => 'WP_ERROR', 'Message' => $response->get_error_message() ];
        }

        $body     = wp_remote_retrieve_body( $response );
        $httpCode = wp_remote_retrieve_response_code( $response );

        $result = json_decode( $body, true );
        if ( ! $result ) {
            return [ 'Code' => 'HTTP_' . $httpCode, 'Message' => $body ];
        }

        return $result;
    }

    /**
     * 发送验证码 — 阿里云短信认证服务 SendSmsVerifyCode
     */
    public function send_code( \WP_REST_Request $request ){
        $phone = sanitize_text_field( $request['phone'] );
        $msg = '';

        if ( empty( $phone ) ) {
            $msg = __( 'Phone is missed', 'mine-cloudvod' );
        }
        if ( ! preg_match( '/^1[3-9]\d{9}$/', $phone ) ) {
            $msg = __( 'Invalid phone number format', 'mine-cloudvod' );
        }

        $options = MINECLOUDVOD_SETTINGS['uc_login3'][ $this->login_id ] ?? [];
        $accessKeyID     = $options['AppID'] ?? '';
        $accessKeySecret = $options['AppSecret'] ?? '';
        $signName        = $options['sign'] ?? '';
        $templateCode    = $options['template'] ?? '';

        if ( ! $accessKeyID || ! $accessKeySecret || ! $signName || ! $templateCode ) {
            $msg = __( 'Some settings is missed', 'mine-cloudvod' );
        }

        if ( $msg ) {
            return rest_ensure_response( [ 'status' => '0', 'msg' => $msg ] );
        }

        $params = [
            'PhoneNumber'       => $phone,
            'SignName'          => $signName,
            'TemplateCode'      => $templateCode,
            'TemplateParam'     => '{"code":"##code##","min":"5"}',
            'CodeType'          => 1,      // 纯数字验证码
            'ValidTime'         => 300,    // 5 分钟有效
            'Interval'          => 60,     // 60 秒发送间隔
            'ReturnVerifyCode'  => 'false',
        ];

        $result = $this->aliyun_dypns_api( $accessKeyID, $accessKeySecret, 'SendSmsVerifyCode', $params );

        if ( isset( $result['Code'] ) && $result['Code'] === 'OK' ) {
            return rest_ensure_response( [
                'status' => '1',
                'data'   => [
                    'code'    => 'OK',
                    'message' => 'success',
                    'bizId'   => $result['Model']['BizId'] ?? '',
                ],
            ] );
        }

        return rest_ensure_response( [
            'status' => '0',
            'msg'    => $result['Message'] ?? __( 'Send SMS failed', 'mine-cloudvod' ),
        ] );
    }
    /**
     * 登录回调 — 阿里云短信认证服务 CheckSmsVerifyCode 核验验证码
     */
    public function mcv_login( \WP_REST_Request $request ){
        $phone = sanitize_text_field( $request['phone'] );
        $msg = '';

        if ( empty( $phone ) ) {
            $msg = __( 'Phone is missed', 'mine-cloudvod' );
        }
        if ( ! preg_match( '/^1[3-9]\d{9}$/', $phone ) ) {
            $msg = __( 'Invalid phone number format', 'mine-cloudvod' );
        }

        $code = sanitize_text_field( $request['code'] );
        if ( empty( $code ) ) {
            $msg = __( 'Code is missed', 'mine-cloudvod' );
        }

        if ( $msg ) {
            return rest_ensure_response( [ 'status' => '0', 'msg' => $msg ] );
        }

        // 调用阿里云 CheckSmsVerifyCode 核验验证码
        $options = MINECLOUDVOD_SETTINGS['uc_login3'][ $this->login_id ] ?? [];
        $accessKeyID     = $options['AppID'] ?? '';
        $accessKeySecret = $options['AppSecret'] ?? '';

        if ( $accessKeyID && $accessKeySecret ) {
            $params = [
                'PhoneNumber' => $phone,
                'VerifyCode'  => $code,
            ];

            $result = $this->aliyun_dypns_api( $accessKeyID, $accessKeySecret, 'CheckSmsVerifyCode', $params );

            // API 调用成功不代表验证码核验通过，必须检查 Model.VerifyResult
            if ( ! isset( $result['Code'] ) || $result['Code'] !== 'OK' ) {
                return rest_ensure_response( [
                    'status' => '0',
                    'msg'    => $result['Message'] ?? __( 'Verification failed', 'mine-cloudvod' ),
                ] );
            }

            if ( ! isset( $result['Model']['VerifyResult'] ) || $result['Model']['VerifyResult'] !== 'PASS' ) {
                return rest_ensure_response( [ 'status' => '0', 'msg' => __( 'Code is incorrect', 'mine-cloudvod' ) ] );
            }
        }
        
        $user_id = get_current_user_id();
        // 当前用户已经登录，则绑定手机号
        if( $user_id ){
            update_user_meta($user_id, 'mcv_phone', $phone);
            return rest_ensure_response( [ 'status' => '1', 'data' => [ 'uid' => $user_id ] ] );
        }
        // 验证码核验通过，执行登录/注册
        global $wpdb;
        $uid = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 按唯一手机号精确查用户，刻意性能优化
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID=$wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key='mcv_phone' and $wpdb->usermeta.meta_value=%s;",
                $phone
            )
        );

        if ( $uid ) {
            $user = get_user_by( 'id', $uid );
            wp_set_current_user( $uid );
            wp_set_auth_cookie( $uid, true, is_ssl() );
            do_action( 'wp_login', $user->user_login, $user );
            return rest_ensure_response( [ 'status' => '1', 'data' => [ 'uid' => $uid ] ] );
        } else {
            $pass       = wp_create_nonce( wp_rand( 10, 1000 ) );
            $login_name = $phone;
            $username   = 'mcv_' . $phone;
            $userdata   = [
                'user_login'    => $login_name,
                'display_name'  => $username,
                'user_nicename' => $username,
                'user_pass'     => $pass,
                'first_name'    => $username,
            ];
            $user_id = wp_insert_user( $userdata );

            if ( ! is_wp_error( $user_id ) ) {
                $user = get_user_by( 'id', $user_id );
                update_user_meta( $user_id, 'mcv_phone', $phone );
                wp_set_current_user( $user_id );
                wp_set_auth_cookie( $user_id, true, is_ssl() );
                do_action( 'wp_login', $user->user_login, $user );
                return rest_ensure_response( [ 'status' => '1', 'data' => [ 'uid' => $user_id ] ] );
            } else {
                return rest_ensure_response( [ 'status' => '0', 'msg' => __( 'Database error, try later!', 'mine-cloudvod' ) ] );
            }
        }
    }
}