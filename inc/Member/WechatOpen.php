<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Member;
use MineCloudvod\Libs\QRcode;

class WechatOpen {
    private $is_wechat, $is_mobile, $murl, $login_id = 'wechat_open';
    public function __construct( ) {

        add_action('rest_api_init', [$this, 'register_routes']);

        add_filter( 'mcv_user_login_options', [ $this, 'login_options' ] );

        add_filter( 'mcv_login_script', [ $this, 'login_script' ], 10, 2 );
        //处理微信客户端登录/注册
        add_action('init', [$this, 'handle_wx_client']);
    }

    public function handle_wx_client(){
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 微信登录按唯一 unionid/openid 精确查用户，刻意性能优化
        if( !is_user_logged_in() && isset( $_GET['mcv_wx_client'] ) && sanitize_text_field(wp_unslash($_GET['mcv_wx_client'])) == '1' && isset( $_GET['code'] ) ){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 微信 OAuth 回调由第三方跳转，无法携带 nonce
            $code = sanitize_text_field(wp_unslash($_GET['code'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 微信 OAuth 回调由第三方跳转，无法携带 nonce

            $wechat_open = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id] ?? [];
            $appid = trim( $wechat_open['MpAppID']??'' );
            $secret = trim( $wechat_open['MpAppSecret']??'' );

            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
            $response = wp_remote_get( $url );
            if( is_wp_error( $response ) ){
                esc_html_e('No response, try later!', 'mine-cloudvod');
                return;
            }
            $data = json_decode($response['body'], true);
            global $wpdb;
            $uid = 0;
            if( isset( $data['unionid'] ) && $data['unionid'] ){
                $uid = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID=$wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key='mcv_wechat_unionid' and $wpdb->usermeta.meta_value=%s;",
                        $data['unionid']
                    )
                );
            }
            elseif( isset( $data['openid'] ) && $data['openid'] ){
                $uid = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID=$wpdb->usermeta.user_id WHERE ($wpdb->usermeta.meta_key='mcv_wechat_fwh_openid' OR $wpdb->usermeta.meta_key='mcv_wechat_openid') and $wpdb->usermeta.meta_value=%s;",
                        $data['openid']
                    )
                );
            }
            elseif( isset( $data['errmsg'] ) ){
                echo esc_html($data['errmsg']);
                return;
            }
            
            if( $uid ){
                $user = get_user_by('id', $uid);
                wp_set_current_user( $uid );
                wp_set_auth_cookie($uid,true,is_ssl());
                // do_action( 'wp_login', $user->user_login, $user );
            }else{
                $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$data['access_token'].'&openid='.$data['openid'].'&lang=zh_CN';
                $info_result = wp_remote_get( $info_url );
                if( is_wp_error( $info_result ) ){
                    esc_html_e('No response 1, try later!', 'mine-cloudvod');
                    return;
                }
                $uinfo = json_decode($info_result['body'], true);
                
                $pass = wp_create_nonce(wp_rand(10,1000));
                $login_name = "mcv".time().wp_rand(1000,9999);
                $username = $uinfo['nickname'];
                $userdata=array(
                    'user_login' => $login_name,
                    // 'user_nicename' => $login_name,
                    'user_pass' => $pass,
                    'display_name' => $username,
                    'first_name' => $username
                );
                $user_id = wp_insert_user( $userdata );
                
                if ( is_wp_error( $user_id ) ) {
                    esc_html_e('No response 3, try later!', 'mine-cloudvod');
                    return;
                }else{
                    $user = get_user_by('id', $user_id);
                    $mcv_avatar_id = mcv_sideload_avatar( $uinfo['headimgurl'] ?? '' );
                    if( $mcv_avatar_id ){
                        update_user_meta($user_id, 'mcv_avatar', $mcv_avatar_id);
                    }
                    update_user_meta($user_id, 'mcv_wechat_unionid', $uinfo['unionid']??'');
                    update_user_meta($user_id, 'mcv_wechat_fwh_openid', $uinfo['openid']);
                    wp_set_current_user( $user_id );
                    wp_set_auth_cookie($user_id,true,is_ssl());
                    // do_action( 'wp_login', $user->user_login, $user );
                }
            }
        }
    }

    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    public function login_script( $script, $key ){
        if( $key == $this->login_id ){
            $is_user_logged_in = is_user_logged_in();
            $wechat_open = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id] ?? [];
            $appid = trim( $wechat_open['AppID']??'' );
            $open_scripts = '';
            $mp_scripts = '';
            if( $appid ){
                // PC微信开放平台
                $open_scripts = 'const script = document.createElement("script");
                script.src = "//res.wx.qq.com/connect/zh_CN/htmledition/js/wxLogin.js";
                const head = document.getElementsByTagName("head")[0];
                head.appendChild(script);
                script.onload = function() {
                    var obj = new WxLogin({
                        self_redirect:false,
                        id:"login_container", 
                        appid: "'.($appid).'", 
                        scope: "snsapi_login", 
                        redirect_uri: encodeURIComponent("'.get_rest_url( null, 'mine-cloudvod/v1/login_wechat_open' ).'"),
                        state: "'.urlencode( base64_encode(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']??''))).'_'.wp_create_nonce( 'wechat_open' ) ).'",
                        style: "",
                        href: "",
                        fast_login:1
                    });
                }';
            }
            $mpappid = trim( $wechat_open['MpAppID']??'' );
            if( $mpappid ){
                // 手机微信浏览器
                $mp_scripts = 'var ua = window.navigator.userAgent.toLowerCase();
                if(ua.match(/MicroMessenger/i) == "micromessenger"){
                    let ruri = location.href;
                    ruri += (ruri.indexOf("?")>0?"&":"?") + "mcv_wx_client=1";
                    location.href="https://open.weixin.qq.com/connect/oauth2/authorize?appid='.esc_js($mpappid).'&redirect_uri="+encodeURIComponent(ruri)+"&response_type=code&scope=snsapi_userinfo&state=200#wechat_redirect";
                }';
                $mp_first = $wechat_open['mp_first']??false;
                if( $mp_first ){
                    $mp_scripts .= 'else{
                        let showQr = \'<div style="line-height:32px;margin-bottom:16px;text-align:center;"><center>扫码关注公众号后'.($is_user_logged_in?'绑定':'登录').'</center><p style="margin:0;padding:0;"><div style="width:100%;height:220px;line-height:220px;text-align:center;">请稍候</div></p><p style="font-size:12px;color:#07c160;text-align:center;">请使用微信扫一扫</p></div>\';
                        jQuery("#login_container").html(showQr);
                        wp.apiFetch({path: "/mine-cloudvod/v1/fwh/scene_qr"}).then(function(response){
                            let result = \'<div style="line-height:32px;margin-bottom:16px;text-align:center;"><p>扫码关注公众号后'.($is_user_logged_in?'绑定':'登录').'</p><p style="margin:0;padding:0;"><img style="width:80%;max-width:286px;" src="\'+ response.qrcode_url +\'" /></p><p style="font-size:12px;color:#07c160">请在 <strong id="expire_seconds">\'+ response.expire_seconds +\'</strong> 秒内完成扫码</p></div>\';
                            jQuery("#login_container").html(result);
                            jQuery("#expire_seconds").html(response.expire_seconds);
                            const sceneCheckIt = setInterval(function(){
                                let seconds = jQuery("#expire_seconds").html();
                                seconds--;
                                jQuery("#expire_seconds").html(seconds);
                                if(seconds <= 0){
                                    location.reload();
                                }
                                if(seconds%2 == 0){
                                    wp.apiFetch({
                                        path: "/mine-cloudvod/v1/fwh/scene_check",
                                        method: "POST",
                                        data: {
                                            scene: response.scene_str
                                        }
                                    }).then(function(res){
                                        if(!res.status && res.msg){
                                            layer.msg(res.msg);
                                            clearInterval(sceneCheckIt);
                                        }
                                        else{
                                            if( res.status == "success" ){
                                                layer.msg(res.msg,{time: 500},function(){location.reload();});
                                                clearInterval(sceneCheckIt);
                                            }
                                        }
                                    });
                                }
                            }, 1000);
                        }).catch(function(error){
                            console.log(error);
                        });
                    }'; 
                }
                else{
                    $mp_scripts .= 'else{'.$open_scripts.'}';
                }
            }
            else{
                $mp_scripts = $open_scripts;
            }
            $script = mcv_trim( '
                jQuery("#login_container").next().remove();
                jQuery("#login_container").after(\'<p class="mcv-uc-protocol checked">我已阅读并同意<a class="mcv-privacy-btn">《服务协议和隐私政策》</a></p>\');
                let logintip = \'<div style="line-height:32px;margin-bottom:16px;"><p>请阅读并同意</p><p><a class="mcv-privacy-btn">《服务协议和隐私政策》</a></p><p>后显示登录二维码</p></div>\';
                if(jQuery(".mcv-uc-protocol").hasClass("checked")){
                    '. $mp_scripts .'
                }
                jQuery(".mcv-uc-protocol").on("mcvProtocolToggled", function(){
                    if(jQuery(this).hasClass("checked")){
                        '. $mp_scripts .'
                    }
                    else{
                        jQuery("#login_container").html(logintip);
                    }
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
            'title'     => __('Wechat Login', 'mine-cloudvod'),
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
                    'default' => '微信登录',
                ),
                array(
                    'type'  => 'submessage',
                    'style'     => 'success',
                    'content'   => '微信开放平台：可用于PC网站扫码登录;若已登录PC端微信，即可使用微信快捷登录。',
                ),
                array(
                    'id'    => 'AppID',
                    'type'  => 'text',
                    'title' => __('Wechat Open Platform', 'mine-cloudvod') . 'AppID',
                    'dependency' => array('status', '==', true),
                    'default' => $ops ? $ops['AppID'] : false,
                ),
                array(
                    'id'    => 'AppSecret',
                    'type'  => 'text',
                    'title' => __('Wechat Open Platform', 'mine-cloudvod') . 'AppSecret',
                    'dependency' => array('status', '==', true),
                    'default' => $ops ? $ops['AppSecret'] : false,
                ),
                array(
                    'type'  => 'submessage',
                    'style'     => 'success',
                    'content'   => '微信服务号：微信客户端打开网站自动登录;若未配置开放平台信息，也可用于PC网站扫码登录。',
                ),
                array(
                    'id'    => 'MpAppID',
                    'type'  => 'text',
                    'title' => '服务号AppID',
                    'dependency' => array('status', '==', true),
                    'desc' => '微信服务号的AppID',
                    'default' => $ops ? $ops['AppID'] : false,
                ),
                array(
                    'id'    => 'MpAppSecret',
                    'type'  => 'text',
                    'title' => '服务号AppSecret',
                    'dependency' => array('status', '==', true),
                    'desc' => '微信服务号的AppSecret',
                    'default' => $ops ? $ops['AppSecret'] : false,
                ),
                array(
                    'id'    => 'mp_first',
                    'type'  => 'switcher',
                    'title' => '优先使用服务号',
                    'desc' => '<p>启用状态下，PC端将优化使用微信服务号扫码登录（<strong>注意：没有PC微信快捷登录功能</strong>）。</p>',
                    'dependency' => array('status', '==', true),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => false,
                ),
                array(
                    'type'  => 'submessage',
                    'style'     => 'success',
                    'content'   => '<p>设置与开发 - 开发接口管理 - 基本设置 - 消息加解密方式: <strong>安全模式（推荐）</strong></p>',
                    'dependency' => array('status|mp_first', '==|==', 'true|true'),
                ),
                array(
                    'id'    => 'MpUrl',
                    'type'  => 'text',
                    'title' => 'URL',
                    'dependency' => array('status|mp_first', '==|==', 'true|true'),
                    'desc' => '设置与开发 - 开发接口管理 - 基本设置 - URL',
                    'value' => get_rest_url( null, 'mine-cloudvod/v1/fwh/receive' ),
                    'default' => get_rest_url( null, 'mine-cloudvod/v1/fwh/receive' ),
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                ),
                array(
                    'id'    => 'MpToken',
                    'type'  => 'text',
                    'title' => 'Token',
                    'dependency' => array('status|mp_first', '==|==', 'true|true'),
                    'desc' => '设置与开发 - 开发接口管理 - 基本设置 - Token',
                    'default' => '',
                ),
                array(
                    'id'    => 'MpAeskey',
                    'type'  => 'text',
                    'title' => 'EncodingAESKey',
                    'dependency' => array('status|mp_first', '==|==', 'true|true'),
                    'desc' => '设置与开发 - 开发接口管理 - 基本设置 - EncodingAESKey',
                    'default' => '',
                ),
                array(
                    'id'    => 'MpScanreplay',
                    'type'  => 'text',
                    'title' => '扫码回复内容',
                    'dependency' => array('status|mp_first', '==|==', 'true|true'),
                    'desc' => '扫码登录时，公众号自动回复的消息。',
                    'default' => '扫码登录成功。',
                ),
                array(
                    'dependency' => array('status|mp_first', '==|==', 'true|true'),
                    'id'    => 'fwh_menu',
                    'type'  => 'group',
                    'title' => '自定义菜单',
                    'button_title'  => '添加一级菜单',
                    'before' => '<p>自定义菜单最多包括3个一级菜单，每个一级菜单最多包含5个二级菜单。</p><p>当一级菜单有子菜单时，点击一级菜单仅弹出二级菜单。</p><p>设置好自定义菜单，保存配置后，<a href="javascript:;" id="mcv_sync_fwh_menu">请点击这里同步到服务号生效</a></p>',
                    'accordion_title_number' => true,
                    'max'   => 3,
                    'fields'    => [
                        [
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => '名称',
                            'default' => '',
                        ],
                        [ // 类型
                            'id'      => 'menu_type',
                            'type'    => 'button_set',
                            'title'   => '类型',
                            'inline'  => true,
                            'options' => [
                                'view'          => '跳转URL',
                                'miniprogram'   => '小程序',
                                // 'click' => '点击推事件',
                                // 'media_id' => '素材',
                                // 'view_limited' => '图文消息',
                            ],
                            'default'   => 'view',
                        ],
                        [
                            'id'    => 'url',
                            'type'  => 'text',
                            'title' => '链接地址',
                            'desc' => '用户点击菜单后，跳转到该链接，需填写完整URL',
                            'default' => home_url(),
                            'dependency' => array('menu_type', '==', 'view'),
                        ],
                        [
                            'id'    => 'appid',
                            'type'  => 'text',
                            'title' => '小程序AppID',
                            'default' => '',
                            'dependency' => array('menu_type', '==', 'miniprogram'),
                        ],
                        [
                            'id'    => 'pagepath',
                            'type'  => 'text',
                            'title' => '小程序页面路径',
                            'default' => '',
                            'dependency' => array('menu_type', '==', 'miniprogram'),
                        ],
                        [
                            'id'    => 'sub_button',
                            'type'  => 'group',
                            'title' => '二级菜单',
                            'button_title'  => '添加二级菜单',
                            'accordion_title_number' => true,
                            'max'   => 5,
                            'fields' => [
                                [
                                    'id'    => 'name',
                                    'type'  => 'text',
                                    'title' => '名称',
                                    'default' => '',
                                ],
                                [ // 类型
                                    'id'      => 'menu_type2',
                                    'type'    => 'button_set',
                                    'title'   => '类型',
                                    'inline'  => true,
                                    'options' => [
                                        'view'          => '跳转URL',
                                        'miniprogram'   => '小程序',
                                        // 'click' => '点击推事件',
                                        // 'media_id' => '素材',
                                        // 'view_limited' => '图文消息',
                                    ],
                                    'default'   => 'view',
                                ],
                                [
                                    'id'    => 'url',
                                    'type'  => 'text',
                                    'title' => '链接地址',
                                    'desc' => '用户点击菜单后，跳转到该链接，需填写完整URL',
                                    'default' => home_url(),
                                    'dependency' => array('menu_type2', '==', 'view'),
                                ],
                                [
                                    'id'    => 'appid',
                                    'type'  => 'text',
                                    'title' => '小程序AppID',
                                    'default' => '',
                                    'dependency' => array('menu_type2', '==', 'miniprogram'),
                                ],
                                [
                                    'id'    => 'pagepath',
                                    'type'  => 'text',
                                    'title' => '小程序页面路径',
                                    'default' => '',
                                    'dependency' => array('menu_type2', '==', 'miniprogram'),
                                ],
                            ]
                        ],
                    ]
                ),
                array(
                    'id'    => 'class',
                    'type'  => 'text',
                    'title' => '',
                    'dependency' => array('status', '==', 'none'),
                    'default' => 'MineCloudvod\Payment\WechatOpen',
                ),
            ),
        );
        return $login3;
    }
    public function register_routes(){
        /**
         * 登录回调
         */
        register_rest_route('mine-cloudvod/v1', '/login_wechat_open', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'mcv_login_wechat_open'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'code' => [
                        'type' => 'string'
                    ],
                    'state' => [
                        'type' => 'string'
                    ],
                ]
        ]);

        // 获取 服务号临时场景码
        register_rest_route('mine-cloudvod/v1', "/fwh/scene_qr", [
                'methods'             => \WP_REST_Server::ALLMETHODS,
                'permission_callback' => '__return_true',
                'args'                => [
                ],
                'callback'            => [$this, 'fwh_login_qr'],
        ]);
        // 检测 场景码扫码状态
        register_rest_route('mine-cloudvod/v1', "/fwh/scene_check", [
                'methods'             => \WP_REST_Server::ALLMETHODS,
                'permission_callback' => '__return_true',
                'args'                => [
                    'scene' => [
                        'type' => 'string',
                    ]
                ],
                'callback'            => [$this, 'fwh_scene_check'],
        ]);
        // 接收 服务号推送的消息
        register_rest_route('mine-cloudvod/v1', "/fwh/receive", [
                'methods'             => \WP_REST_Server::ALLMETHODS,
                'permission_callback' => '__return_true',
                'args'                => [
                    'signature' =>[
                        'type' => 'string',
                    ],
                    'timestamp' =>[
                        'type' => 'number',
                    ],
                    'nonce' =>[
                        'type' => 'string',
                    ],
                    'encrypt_type' =>[
                        'type' => 'string',
                    ],
                    'msg_signature' =>[
                        'type' => 'string',
                    ],
                ],
                'callback'            => [$this, 'fwh_receive'],
        ]);
        // 设置自定义菜单
        register_rest_route('mine-cloudvod/v1', "/fwh/setmenu", [
                'methods'             => \WP_REST_Server::ALLMETHODS,
                'permission_callback' => function(){
                    return current_user_can( 'manage_options' );
                },
                'args'                => [
                ],
                'callback'            => [$this, 'fwh_setmenu'],
        ]);
    }
    public function fwh_setmenu( \WP_REST_Request $request ){
        $menus = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id]['fwh_menu'] ?? [];
        if( is_array( $menus ) && $menus ){
            $_menu = [];
            foreach( $menus as $menu ){
                $cur_menu = [];
                if( isset( $menu['sub_button'] ) && is_array( $menu['sub_button'] ) ){
                    $subs = [];
                    foreach( $menu['sub_button'] as $sub ){
                        if( isset( $sub['name'] ) && $sub['name'] ){
                            $tmp = [
                                'type' => $sub['menu_type2'],
                                'name' => $sub['name'],
                            ];
                            if( $sub['menu_type2'] == 'view' && isset( $sub['url'] ) && $sub['url'] ){
                                $tmp['url'] = $sub['url'];
                            }
                            if( $sub['menu_type2'] == 'miniprogram' ){
                                $tmp['url'] = $sub['url'] ? $sub['url'] : home_url();
                                $tmp['appid'] = $sub['appid'] ?? '';
                                $tmp['pagepath'] = $sub['pagepath'] ?? '';
                            }
                            $subs[] = $tmp;
                        }
                    }
                    $cur_menu = [
                        'name' => $menu['name'],
                        'sub_button' => $subs,
                    ];
                }
                else{
                    if( isset( $menu['name'] ) && $menu['name'] ){
                        $cur_menu = [
                            'type' => $menu['menu_type'],
                            'name' => $menu['name'],
                        ];
                        if( $menu['menu_type'] == 'view' && isset( $menu['url'] ) && $menu['url'] ){
                            $cur_menu['url'] = $menu['url'];
                        }
                        if( $menu['menu_type'] == 'miniprogram' ){
                            $cur_menu['url'] = $menu['url'] ? $menu['url'] : home_url();
                            $cur_menu['appid'] = $menu['appid'] ?? '';
                            $cur_menu['pagepath'] = $menu['pagepath'] ?? '';
                        }
                    }
                }
                $_menu['button'][] = $cur_menu;
            }

            $ACCESS_TOKEN = $this->get_wechat_access_token();
            $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$ACCESS_TOKEN;
    
            $post_data = json_encode( $_menu, JSON_UNESCAPED_UNICODE );
            $response = wp_remote_post( $url, [
                'body' => $post_data
            ] );
            return rest_ensure_response($response);
        }
    }
    /**
     * 登录回调
     */
    public function mcv_login_wechat_open( \WP_REST_Request $request ){
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 微信登录按唯一 unionid/openid 精确查用户，刻意性能优化
        if(empty($request['code']) || empty($request['state'])){
            return __('Code or State is missed', 'mine-cloudvod');
            exit;
        }
        $state = $request['state'];
        $state = explode( '_', urldecode( $state ) );
        $redirect_to = base64_decode( $state[0] );
        if( !wp_verify_nonce($state[1], 'wechat_open') ){
            return __('Hello Mine', 'mine-cloudvod');
            exit;
        }

        $code = $request['code'];
        $wechat_open = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id];
        $wechat_api = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='. trim( $wechat_open['AppID'] ).'&secret='. trim( $wechat_open['AppSecret'] ).'&code='.$code.'&grant_type=authorization_code';
        $response = wp_remote_get( $wechat_api );
        if( is_wp_error( $response ) ){
            return __('No response, try later!', 'mine-cloudvod');
        }
        $data = json_decode($response['body'], true);
    	global $wpdb;
        $uid = 0;
        if( isset( $data['unionid'] ) && $data['unionid'] ){
            $uid = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID=$wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key='mcv_wechat_unionid' and $wpdb->usermeta.meta_value=%s;",
                    $data['unionid']
                )
            );
        }
        elseif( isset( $data['openid'] ) && $data['openid'] ){
            $uid = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID=$wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key='mcv_wechat_openid' and $wpdb->usermeta.meta_value=%s;",
                    $data['openid']
                )
            );
        }
        else{
            return 'No response, try later!';
        }
        
        if( $uid ){
            $user = get_user_by('id', $uid);
            wp_set_current_user( $uid );
            wp_set_auth_cookie($uid,true,is_ssl());
            // do_action( 'wp_login', $user->user_login, $user );
            wp_safe_redirect( $redirect_to );
            echo '<script>location.href="'.esc_url($redirect_to).'"</script>';
            return;
        }else{
            $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$data['access_token'].'&openid='.$data['openid'].'&lang=zh_CN';
            $info_result = wp_remote_get( $info_url );
            if( is_wp_error( $info_result ) ){
                esc_html_e('No response 1, try later!', 'mine-cloudvod');
                exit;
            }
            $uinfo = json_decode($info_result['body'], true);
            if( !isset( $uinfo['unionid'] ) ){
                esc_html_e('No response 2, try later!', 'mine-cloudvod');
                exit;
            }
            $pass = wp_create_nonce(wp_rand(10,1000));
            $login_name = "mcv".time().wp_rand(1000,9999);
            $username = $uinfo['nickname'];
            $userdata=array(
                'user_login' => $login_name,
                'display_name' => $username,
                'user_pass' => $pass,
                'first_name' => $username
            );
            $user_id = wp_insert_user( $userdata );
            
            if ( is_wp_error( $user_id ) ) {
                esc_html_e('No response 3, try later!', 'mine-cloudvod');
                exit;
            }else{
                $user = get_user_by('id', $user_id);
                $mcv_avatar_id = mcv_sideload_avatar( $uinfo['headimgurl'] ?? '' );
                if( $mcv_avatar_id ){
                    update_user_meta($user_id, 'mcv_avatar', $mcv_avatar_id);
                }
                update_user_meta($user_id, 'mcv_wechat_unionid', $uinfo['unionid']);
                update_user_meta($user_id, 'mcv_wechat_openid', $uinfo['openid']);
                wp_set_current_user( $user_id );
                wp_set_auth_cookie($user_id,true,is_ssl());
                // do_action( 'wp_login', $user->user_login, $user );
                wp_safe_redirect( $redirect_to );
                echo '<script>location.href="'.esc_url($redirect_to).'"</script>';
                return;
            }
        }
    }
    
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    public function fwh_scene_check( \WP_REST_Request $request ){
        $scene_str = sanitize_text_field( $request['scene'] );
        $ret = ['status' => 'pending',];
        if( !$scene_str ){
            $ret = [
                'status' => false,
                'msg' => '扫码超时，请重新扫码'
            ];
        }
        $session = get_transient( 'mcv_fwh_scene_'. $scene_str );
        if( !$session ){
            $ret = [
                'status' => false,
                'msg' => '扫码超时，请重新扫码'
            ];
        }
        if( $session['status'] == 'success' && isset( $session['openid'] ) && $session['openid'] ){
            $openid = $session['openid'];
            $isLogin = is_user_logged_in();
            if($this->fwh_login_reg($openid)){
                $msg = '登录成功';
                if( $isLogin ) $msg = '绑定成功';
                $ret = [
                    'status' => 'success',
                    'msg' => $msg,
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                ];
            }
            else{
                $msg = '登录失败';
                if( $isLogin ) $msg = '本微信已绑定其他账号';
                $ret = [
                    'status' => false,
                    'msg' => $msg,
                ];
            }
        }
        return rest_ensure_response( $ret );
    }
    /**
     * 生成待登录会话并返回临时二维码
     */
    public function fwh_login_qr() {
        // 生成唯一场景值（如UUID）
        $scene_str = wp_generate_uuid4(); // WordPress 5.0+ 自带函数，或用uniqid()替代
        $expire_seconds = 300; // 二维码有效期5分钟（避免长期有效导致安全风险）

        // 存储待登录会话（使用数据库或缓存，这里以options为例）
        $login_session = [
            'scene_str' => $scene_str,
            'expire_time' => time() + $expire_seconds,
            'client_ip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            'status' => 'pending' // 状态：pending（待扫码）、scanned（已扫码）、success（登录成功）
        ];
        set_transient('mcv_fwh_scene_'. $scene_str, $login_session, $expire_seconds);

        // 调用接口生成临时字符串场景二维码
        $qrcode_data = $this->generate_wechat_qrcode([
            'expire_seconds' => $expire_seconds,
            'action_name' => 'QR_STR_SCENE', // 临时字符串场景值
            'action_info' => [
                'scene' => ['scene_str' => $scene_str]
            ]
        ]);

        if (!$qrcode_data) {
            return new \WP_Error('wechat_qrcode_failed', 'Failed to generate wechat qrcode', ['status' => 500]);
        }

        // 返回二维码信息（供前端展示）
        return rest_ensure_response([
            'scene_str' => $scene_str,
            'qrcode_url' => "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($qrcode_data['ticket']),
            'expire_seconds' => $expire_seconds
        ]);
    }
    public function decrypt($encrypted_data) {
        $wechat_open = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id] ?? [];
        $appid = trim( $wechat_open['MpAppID']??'' );
        $EncodingAESKey = trim( $wechat_open['MpAeskey']??'' );

        $aes_key = base64_decode($EncodingAESKey . '='); // 补全Base64填充
        $iv = substr($aes_key, 0, 16); // IV为密钥前16字节
    
        // Base64解码加密数据
        $encrypted = base64_decode($encrypted_data);
    
        // AES-CBC解密（PKCS#7填充）
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $aes_key,
            OPENSSL_RAW_DATA, // 原始数据模式
            $iv
        );
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $aes_key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
        if ($decrypted === false) {
            return false;
        }
    
		$pad = ord(substr($decrypted, -1));
		if ($pad < 1 || $pad > 32) {
			$pad = 0;
		}
		$result = substr($decrypted, 0, (strlen($decrypted) - $pad));
		if (strlen($result) < 16)
            return "";
        $content = substr($result, 16, strlen($result));
        $len_list = unpack("N", substr($content, 0, 4));
        $xml_len = $len_list[1];
        $xml_content = substr($content, 4, $xml_len);
        $from_appid = substr($content, $xml_len + 4);
        
    
        // 验证AppID是否匹配
        if ($appid !== $from_appid) {
            return false;
        }
    
        return $xml_content;
    }
    public function encrypt($msg, $nonce, $config) {
        $wechat_open = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id] ?? [];
        $appid = trim( $wechat_open['MpAppID']??'' );
        $EncodingAESKey = trim( $wechat_open['MpAeskey']??'' );
        
        $aes_key = base64_decode($EncodingAESKey . '=');
        $iv = substr($aes_key, 0, 16);
    
        // 构造原始数据：random(16B) + msg_len(4B) + msg + appid
        $random = random_bytes(16); // 16字节随机字符串
        $msg_len = pack('N', strlen($msg)); // 网络字节序
        $full_data = $random . $msg_len . $msg . $appid;
    
        // PKCS#7填充（AES要求数据长度为密钥长度的整数倍）
        $block_size = 32; // AES-256-CBC块大小为32字节
        $pad = $block_size - (strlen($full_data) % $block_size);
        $full_data .= str_repeat(chr($pad), $pad);
    
        // AES-CBC加密
        $encrypted = openssl_encrypt(
            $full_data,
            'aes-256-cbc',
            $aes_key,
            OPENSSL_RAW_DATA,
            $iv
        );
    
        $encrypt = base64_encode($encrypted);
    
        // 生成MsgSignature
        $timestamp = time();
        $tmp_arr = [$config['token'], (string)$timestamp, $nonce, $encrypt];
        sort($tmp_arr, SORT_STRING);
        $msg_signature = sha1(implode($tmp_arr));
    
        // 返回加密后的回复格式
        return [
            'Encrypt' => $encrypt,
            'MsgSignature' => $msg_signature,
            'TimeStamp' => $timestamp,
            'Nonce' => $nonce
        ];
    }
    public function fwh_receive(\WP_REST_Request $request){      
        $wechat_open = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id] ?? [];
        $token = trim( $wechat_open['MpToken']??'' );

        $signature = sanitize_text_field($request["signature"]);
        $timestamp = sanitize_text_field($request["timestamp"]);
        $nonce = sanitize_text_field($request["nonce"]);
        
        if ( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) === 'GET' ) {
            $tmpArr = array($token, $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode( $tmpArr );
            $tmpStr = sha1( $tmpStr );
            if( $tmpStr == $signature ){
                echo esc_html(sanitize_text_field($request["echostr"]));
            }else{
                return false;
            }
        }
        if ( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) === 'POST' ) {
            $encrypt_type = sanitize_text_field($request['encrypt_type']);
            $msg_signature = sanitize_text_field($request['msg_signature']);

            
            $postBody = $request->get_body();
            $xml = simplexml_load_string($postBody, 'SimpleXMLElement', LIBXML_NOCDATA);
            
            $to_user_name = (string)$xml->ToUserName;
            $encrypt_data = (string)$xml->Encrypt;
    
            // 验证签名（安全模式用msg_signature）
            $tmpArr = array($token, $timestamp, $nonce, $encrypt_data);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode( $tmpArr );
            $tmpStr = sha1( $tmpStr );
            if( $tmpStr !== $msg_signature ){
                return false;
            }
            // 解密消息
            $decrypted_msg = $this->decrypt($encrypt_data);
            
            if (!$decrypted_msg) {
                return new \WP_REST_Response('Decrypt failed', 500);
            }
            
            $xml_data = simplexml_load_string($decrypted_msg, 'SimpleXMLElement', LIBXML_NOCDATA);
            
            // 提取字段（直接从数组中读取，无需类型转换）
            $ToUserName = (string)$xml_data->ToUserName;
            $FromUserName = (string)$xml_data->FromUserName;
            $CreateTime = (string)$xml_data->CreateTime;
            $MsgType = (string)$xml_data->MsgType;
            $Event = (string)$xml_data->Event;
            
            $content = '';
            if( $MsgType == 'event' ){
                switch( $Event ){
                    case 'subscribe':
                        $EventKey = (string)$xml_data->EventKey;
                        if( strpos($EventKey, 'qrscene_') !== false ){
                            $scene_str = str_replace('qrscene_', '', $EventKey);
                        
                            $content = $this->fwh_handle_scene( $scene_str, $FromUserName );
                            if(!$content){
                                $content = '公众号开小差了，请稍后重试';
                            }
                        }
                        break;
                    case 'SCAN':
                        $EventKey = (string)$xml_data->EventKey;
                        $scene_str = str_replace('qrscene_', '', $EventKey);
                        
                        $content = $this->fwh_handle_scene( $scene_str, $FromUserName );
                        if(!$content){
                            $content = '公众号开小差了，请稍后重试';
                        }
                        break;
                }
            }
            if( $MsgType == 'text' ){
                // 可以根据用户发送的文本消息内容进行处理
                // 
            }
            /**
             * 接收微信服务号推送消息并处理
             * 
             * @param string $content 回复内容
             * @param SimpleXMLElement $xml_data 微信推送消息
             * @return string 回复内容
             */
            $content = apply_filters( 'mcv_fwh_receive', $content, $xml_data );
            if( $content ){
                // escapted in fwh_return_msg
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fwh_return_msg 内部已转义
                echo $this->fwh_return_msg( $FromUserName, $ToUserName, $content );
            }
            return ;
        }
    }
    /*
     * @return false: 登录失败， string: 提示语
     */
    public function fwh_handle_scene($scene_str, $openid){
        if (!$scene_str) {
            return false; // 非扫码事件，直接返回
        }
        
        // 查找对应的待登录会话
        $session = get_transient('mcv_fwh_scene_'. $scene_str);
        if (!$session || $session['expire_time'] < time()) {
            return '二维码已过期，请刷新网站后重新扫描。';
        }
        
            $session['status'] = 'success';
            $session['openid'] = $openid;
            set_transient('mcv_fwh_scene_'. $scene_str, $session, $session['expire_time'] -  time() );
            
            $wechat_open = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id] ?? [];
            $MpScanreplay = $wechat_open['MpScanreplay']??'';
            if( !$MpScanreplay ) $MpScanreplay = '扫码登录成功。';
            
            return $MpScanreplay;
        
    }
    public function fwh_login_reg($openid){
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 微信登录按唯一 unionid/openid 精确查用户，刻意性能优化
        $user_id = get_current_user_id();
        // 当前用户已经登录，则绑定微信
        if( $user_id ){
            $access_token = $this->get_wechat_access_token();
            $info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
            $info_result = wp_remote_get( $info_url );
        
            if( is_wp_error( $info_result ) ){
                esc_html_e('No response 1, try later!', 'mine-cloudvod');
                return false;
            }
            $uinfo = json_decode($info_result['body'], true);

            global $wpdb;
            $uid = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID=$wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key='mcv_wechat_unionid' and $wpdb->usermeta.meta_value=%s;",
                    $uinfo['unionid']
                )
                );
            if( $uid ){
                return false;
            }

            // if( isset( $uinfo['headimgurl'] ) ) update_user_meta($user_id, 'mcv_avatar', $uinfo['headimgurl']);
            if( isset( $uinfo['unionid'] ) ) update_user_meta($user_id, 'mcv_wechat_unionid', $uinfo['unionid']);
            if( isset( $uinfo['openid'] ) ) update_user_meta($user_id, 'mcv_wechat_fwh_openid', $uinfo['openid']);
        }
        // 未登录，则执行登录逻辑
        else{
            global $wpdb;
            $uid = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID=$wpdb->usermeta.user_id WHERE ($wpdb->usermeta.meta_key='mcv_wechat_fwh_openid' OR $wpdb->usermeta.meta_key='mcv_wechat_openid') and $wpdb->usermeta.meta_value=%s;",
                    $openid
                )
            );
            if( $uid ){
                $user = get_user_by('id', $uid);
                wp_set_current_user( $uid );
                wp_set_auth_cookie($uid,true,is_ssl());
                // do_action( 'wp_login', $user->user_login, $user );
            }else{
                $access_token = $this->get_wechat_access_token();
                $info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
                $info_result = wp_remote_get( $info_url );
            
                if( is_wp_error( $info_result ) ){
                    esc_html_e('No response 1, try later!', 'mine-cloudvod');
                    return false;
                }
                $uinfo = json_decode($info_result['body'], true);

                if( isset($uinfo['unionid']) && $uinfo['unionid'] ){
                    $uid = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID=$wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key='mcv_wechat_unionid' and $wpdb->usermeta.meta_value=%s;",
                            $uinfo['unionid']
                        )
                    );
                }
                if( $uid ){
                    $user = get_user_by('id', $uid);
                    wp_set_current_user( $uid );
                    wp_set_auth_cookie($uid,true,is_ssl());
                    // do_action( 'wp_login', $user->user_login, $user );
                }
                else{
                    $pass = wp_create_nonce(wp_rand(10,1000));
                    $login_name = "mcv".time().wp_rand(1000,9999);
                    $username = $uinfo['nickname'];
                    $userdata=array(
                        'user_login' => $login_name,
                        'user_pass' => $pass,
                        'display_name' => $username,
                        'first_name' => $username
                    );
                    $user_id = wp_insert_user( $userdata );
                    
                    if ( is_wp_error( $user_id ) ) {
                        esc_html_e('No response 3, try later!', 'mine-cloudvod');
                        return false;
                    }else{
                        $user = get_user_by('id', $user_id);
                        $mcv_avatar_id = mcv_sideload_avatar( $uinfo['headimgurl'] ?? '' );
                        if( $mcv_avatar_id ){
                            update_user_meta($user_id, 'mcv_avatar', $mcv_avatar_id);
                        }
                        update_user_meta($user_id, 'mcv_wechat_unionid', $uinfo['unionid']??'');
                        update_user_meta($user_id, 'mcv_wechat_fwh_openid', $uinfo['openid']);
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie($user_id,true,is_ssl());
                        // do_action( 'wp_login', $user->user_login, $user );
                    }
                }
                
            }
        }
        return true;
    }
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    // 返回给服务号的消息
    public function fwh_return_msg( $ToUserName, $FromUserName, $content = '', $type = 'text' ){
        if( !$content ) $content = '欢迎关注Mine云点播';
        $str = "<xml><ToUserName><![CDATA[" . esc_html($ToUserName) . "]]></ToUserName><FromUserName><![CDATA[" . esc_html($FromUserName) . "]]></FromUserName><CreateTime>" . time() . "</CreateTime><MsgType><![CDATA[text]]></MsgType>";
        if( $type == 'text' ){
            $str .= "<Content><![CDATA[" . wp_kses_post($content) . "]]></Content>";
        }
        $str .= "</xml>";
        return $str;
    }
    /**
    * 获取微信access_token并缓存
    * @return string|bool access_token或false（失败时）
    */
    public function get_wechat_access_token() {
        $wechat_open = MINECLOUDVOD_SETTINGS['uc_login3'][$this->login_id] ?? [];
        $appid = trim( $wechat_open['MpAppID']??'' );
        $appsecret = trim( $wechat_open['MpAppSecret']??'' );
        $cache_key = 'mcv_fwh_access_token'; // 缓存键名
    
        // 尝试从缓存获取
        $access_token = get_transient($cache_key);
        if ($access_token) {
            return $access_token;
        }
    
        // 缓存过期，重新获取
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
        $response = wp_remote_get($url);
    
        // 检查请求是否成功
        if (is_wp_error($response)) {
            throw new \Exception('获取access_token失败：' . esc_html($response->get_error_message()));
            return false;
        }
    
        $result = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($result['access_token'])) {
            // 缓存access_token（有效期7200秒，提前200秒过期避免失效）
            set_transient($cache_key, $result['access_token'], 7000);
            return $result['access_token'];
        } else {
            throw new \Exception(esc_html(serialize($result)));
            return false;
        }
    }
    /**
     * 生成带参数的微信二维码
     * @param array $params 二维码参数（场景值、类型、过期时间等）
     * @return array|bool 包含ticket、url等信息的数组，或false（失败时）
     */
    function generate_wechat_qrcode($params = []) {
        // 获取access_token
        $access_token = $this->get_wechat_access_token();
        if (!$access_token) {
            return false;
        }

        // 接口URL
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$access_token}";

        // 构建请求参数（默认值处理）
        $default_params = [
            'expire_seconds' => 1800, // 临时二维码默认30天过期（最大2592000秒）
            'action_name' => 'QR_SCENE', // 默认临时整型场景值
            'action_info' => [
                'scene' => [
                    'scene_id' => 1 // 默认场景值（可替换为scene_str）
                ]
            ]
        ];
        $request_data = wp_parse_args($params, $default_params);

        // 发送POST请求
        $response = wp_remote_post($url, [
            'body' => json_encode($request_data, JSON_UNESCAPED_UNICODE),
            'headers' => ['Content-Type' => 'application/json']
        ]);

        // 检查请求是否成功
        if (is_wp_error($response)) {
            throw new \Exception('生成二维码失败：' . esc_html($response->get_error_message()));
            return false;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($result['ticket'])) {
            return $result; // 成功返回ticket、expire_seconds、url
        } else {
            throw new \Exception(esc_html(serialize( $result )));
            return false;
        }
    }
}