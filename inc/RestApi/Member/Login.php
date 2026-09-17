<?php
namespace MineCloudvod\RestApi\Member;

if ( ! defined( 'ABSPATH' ) )
    exit;

class Login extends Base{

    protected $base = 'member';

    public function __construct(){
        $this->register();
    }

    public function register(){
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(){
        /**
         * 账号密码登录
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/login", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'user_login'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'log' => [
                        'type' => 'string',
                    ],
                    'pwd' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * 账号密码注册
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/reg", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'user_reg'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'log' => [
                        'type' => 'string',
                    ],
                    'pwd' => [
                        'type' => 'string',
                    ],
                    'email' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * 获取当前登录账号信息
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/info", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'user_info'],
                'permission_callback' => 'is_user_logged_in',
                'args'                => [
                    'action' => [
                        'type' => 'string'
                    ]
                ]
        ]);
        /**
         * 找回密码 — 发送重置链接
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/lostpassword", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'lostpassword'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'user_login' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * 发送邮箱验证码
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/send_email_code", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'send_email_code'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'email' => [
                        'type' => 'string',
                    ],
                ]
        ]);
    }


    public function user_reg(\WP_REST_Request $request){
        $can = get_option( 'users_can_register' );
        if( !$can ){
            return new \WP_Error('cant-trash', '当前站点未开放注册', ['status' => 500]);
        }
        $log  = sanitize_user( $request['log'] );
        $pwd  = sanitize_text_field( $request['pwd'] );
        $email  = sanitize_email( $request['email'] );
        
        if( !$log || !$pwd ){
            return new \WP_Error('cant-trash', '用户名或密码不能为空', ['status' => 500]);
        }
        if( !$email ){
            return new \WP_Error('cant-trash', '请填写正确的邮箱地址', ['status' => 500]);
        }
        $code  = sanitize_text_field( $request['code'] );
        if( !$code ){
            return new \WP_Error('cant-trash', '请输入邮箱验证码', ['status' => 500]);
        }
        $saved_code = get_transient('mcv_email_code_'. $email);
        if( !$saved_code || $saved_code != $code ){
            return new \WP_Error('cant-trash', '验证码错误或已过期', ['status' => 500]);
        }
        delete_transient('mcv_email_code_'. $email);

        $userdata=array(
            'user_login' => $log,
            'display_name' => $log,
            'nickname' => $log,
            'user_pass' => $pwd,
            'first_name' => $log,
            'user_email' => $email
        );
        $user_id = wp_insert_user( $userdata );

        if ( is_wp_error( $user_id ) ) {
            $errors = $user_id->errors;
            $emsg = '';
            foreach($errors as $error){
                $emsg = $error[0];
            }
            return new \WP_Error('cant-trash', $emsg, ['status' => 500]);
        }
        return rest_ensure_response(['success'=>true]);
    }

    public function user_login(\WP_REST_Request $request){
        
        $log  = sanitize_user( $request['log'] );
        $pwd  = sanitize_text_field( $request['pwd'] );
        
        if( !$log || !$pwd ){
            return new \WP_Error('cant-trash', '用户名或密码不能为空', ['status' => 500]);
        }

        $user = wp_signon(['user_login'=>$log,'user_password'=>$pwd,'remember'=>true]);
        if ( is_wp_error( $user ) ) {
            $errors = $user->errors;
            $emsg = '';
            foreach($errors as $error){
                $emsg = $error[0];
            }
            return new \WP_Error('cant-trash', $emsg, ['status' => 500]);
        }
        return rest_ensure_response(['success'=>true]);
    }
    public function user_info(\WP_REST_Request $request){
        $user = wp_get_current_user();
        if( !$user ){
            return new \WP_Error('cant-trash', __('Login first, please.', 'mine-cloudvod'), ['status' => 500]);
        }
        $mcv_wechat_unionid = get_user_meta($user->ID, 'mcv_wechat_unionid', true);
        $mcv_wechat_fwh_openid = get_user_meta($user->ID, 'mcv_wechat_fwh_openid', true);
        $avatar = mcv_get_avatar_url( $user->ID );
        $mcv_phone = get_user_meta( $user->ID, 'mcv_phone', true );
        $action = sanitize_text_field( $request['action']??'' );
        if( $action === 'unbind_wechat' ){
            if( $mcv_wechat_unionid ){
                delete_user_meta($user->ID, 'mcv_wechat_unionid');
                $mcv_wechat_unionid = '';
            }
            if( $mcv_wechat_fwh_openid ){
                delete_user_meta($user->ID, 'mcv_wechat_fwh_openid');
                $mcv_wechat_fwh_openid = '';
            }
        }
        elseif( $action === 'update_nickname' ){
            $nickname = sanitize_text_field( $request['nickname'] );
            if( $nickname ){
                wp_update_user([
                    'ID' => $user->ID,
                    'display_name' => $nickname,
                ]);
            }
        }
        elseif( $action === 'update_avatar' ){
            $files = $request->get_file_params();
            if( ! empty( $files['avatar_file'] ) ){
                // HTTP POST 文件上传
                $file = $files['avatar_file'];
                if ( ! function_exists( 'wp_handle_upload' ) ) {
                    require_once( ABSPATH . 'wp-admin/includes/file.php' );
                }

                // 安全校验：仅允许图片类型，防止任意文件上传（CVE-2026-15447）
                $allowed_mimes = [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png'          => 'image/png',
                    'gif'          => 'image/gif',
                    'webp'         => 'image/webp',
                ];
                $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
                if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
                    return new \WP_Error( 'invalid_file_type', __( 'Only JPG, PNG, GIF and WebP images are allowed.', 'mine-cloudvod' ), [ 'status' => 400 ] );
                }

                $overrides = [ 'test_form' => false ];
                $upload = wp_handle_upload( $file, $overrides );

                if( $upload && ! isset( $upload['error'] ) ){
                    // 删除旧头像
                    $old_avatar = get_user_meta( $user->ID, 'mcv_avatar', true );
                    if( $old_avatar ){
                        if( is_numeric( $old_avatar ) ){
                            wp_delete_attachment( (int) $old_avatar, true );
                        } else {
                            $old_attach_id = attachment_url_to_postid( $old_avatar );
                            if( $old_attach_id ){
                                wp_delete_attachment( $old_attach_id, true );
                            }
                        }
                    }

                    $wp_filetype = wp_check_filetype( $file['name'], null );
                    $attachment = [
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => sanitize_file_name( $file['name'] ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];
                    $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                    if( !is_wp_error( $attach_id ) ){
                        require_once( ABSPATH . 'wp-admin/includes/image.php' );
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                        wp_update_attachment_metadata( $attach_id, $attach_data );
                        $avatar = wp_get_attachment_url( $attach_id );
                        update_user_meta( $user->ID, 'mcv_avatar', $attach_id );
                    }
                }
            }
        }

        $data = [
            'uid' => $user->ID,
            'user_login' => $user->user_login,
            'nickname' => $user->display_name,
            'reg_time' => $user->user_registered,
            'avatar' => $avatar ?: MINECLOUDVOD_URL . '/static/img/user.png',
            'phone' => $mcv_phone,
            'unionid' => $mcv_wechat_unionid?true:false,
        ];

        return rest_ensure_response(['success'=>true, 'data'=>$data]);
    }
    public function send_email_code(\WP_REST_Request $request){
        $email = sanitize_email($request['email']);
        if(!$email){
            return new \WP_Error('cant-trash', __('Please enter a valid email address.', 'mine-cloudvod'), ['status' => 500]);
        }
        if(email_exists($email)){
            return new \WP_Error('cant-trash', __('This email address is already registered.', 'mine-cloudvod'), ['status' => 500]);
        }
        $code = wp_rand(100000, 999999);
        set_transient('mcv_email_code_'. $email, $code, 300);
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        /* translators: %s: site name */
        $subject = sprintf(__('[%s] Email Verification Code', 'mine-cloudvod'), $blogname);
        /* translators: %s: verification code */
        $message = sprintf(__('Your verification code is: %s', 'mine-cloudvod'), $code) . "\r\n\r\n";
        $message .= __('This code will expire in 5 minutes.', 'mine-cloudvod');
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        $sent = wp_mail($email, $subject, $message, $headers);
        if(!$sent){
            return new \WP_Error('cant-trash', __('Failed to send verification email. Please contact the administrator.', 'mine-cloudvod'), ['status' => 500]);
        }
        return rest_ensure_response(['success'=>true]);
    }
    public function lostpassword(\WP_REST_Request $request){
        $user_login = sanitize_text_field($request['user_login']);
        if(!$user_login){
            return new \WP_Error('cant-trash', __('Please enter a username or email address.', 'mine-cloudvod'), ['status' => 500]);
        }
        $user_data = get_user_by('login', $user_login);
        if(!$user_data){
            $user_data = get_user_by('email', $user_login);
        }
        if(!$user_data){
            return new \WP_Error('cant-trash', __('No user found with that username or email address.', 'mine-cloudvod'), ['status' => 500]);
        }
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key = get_password_reset_key($user_data);
        if(is_wp_error($key)){
            return new \WP_Error('cant-trash', $key->get_error_message(), ['status' => 500]);
        }
        $message = __('Someone has requested a password reset for the following account:', 'mine-cloudvod')."\r\n\r\n";
        $message .= network_home_url('/')."\r\n\r\n";
        /* translators: %s: username */
        $message .= sprintf(__('Username: %s', 'mine-cloudvod'), $user_login)."\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'mine-cloudvod')."\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'mine-cloudvod')."\r\n\r\n";
        $message .= network_site_url("wp-login.php?action=rp&key=$key&login=".rawurlencode($user_login), 'login')."\r\n";
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        /* translators: %s: site name */
        $title = sprintf(__('[%s] Password Reset', 'mine-cloudvod'), $blogname);
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        $sent = wp_mail($user_email, $title, $message, $headers);
        if(!$sent){
            return new \WP_Error('cant-trash', __('Failed to send password reset email. Please contact the administrator.', 'mine-cloudvod'), ['status' => 500]);
        }
        return rest_ensure_response(['success'=>true, 'msg'=>__('Password reset link has been sent to your email.', 'mine-cloudvod')]);
    }
}
