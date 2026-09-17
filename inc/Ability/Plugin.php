<?php
namespace MineCloudvod\Ability;

class Plugin
{
    protected $plugin_basename = "mine-cloudvod/mine-cloudvod.php";
    public function __construct()
    {
        add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'plugin_action_links' ) );
        add_filter( 'plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2 );
        add_action( 'admin_notices', array($this, 'notice_mcv_endtime') );
        add_action( 'admin_notices', array($this, 'init_pages') );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_mcv_dismiss_notice', [ $this, 'dismiss_notice' ] );
    }

    public function plugin_action_links($actions){
		$actions['addons'] = '<a href="admin.php?page=mcv-addons">' . __('Add-ons', 'mine-cloudvod') . '</a>';
		$actions['settings'] = '<a href="admin.php?page=mcv-options">' . __('Settings', 'mine-cloudvod') . '</a>';
		return $actions;
	}

    public function plugin_row_meta($plugin_meta, $plugin_file){

        if ($plugin_file === $this->plugin_basename) {
            $plugin_meta[] = sprintf( '<a href="%s">%s</a>',
                esc_url( 'https://www.mine27.cn/docs/%E5%BF%AB%E9%80%9F%E5%BC%80%E5%A7%8B/' ),
                '<strong style="color: #03bd24">'. __( 'Documentation', 'mine-cloudvod' ). '</strong>'
            );
        }

        return $plugin_meta;
    }

    public function notice_mcv_endtime(){
        global $mcv_classes;
        $setting = MINECLOUDVOD_SETTINGS;
        $level   = isset( $setting['level'] ) ? sanitize_key( $setting['level'] ) : '';

        // 免费版不受时间限制，无需提醒
        if( 'free' === $level ){
            return;
        }

        // 档位到期时间：pro 只看 pts，basic 取 max(bts, pts)，取不到回退主授权 endtime
        $bts = isset( $setting['bts'] ) ? absint( $setting['bts'] ) : 0;
        $pts = isset( $setting['pts'] ) ? absint( $setting['pts'] ) : 0;
        $endtime = 'pro' === $level ? $pts : max( $bts, $pts );
        if( ! $endtime && ! empty( $setting['endtime'] ) ){
            $endtime = strtotime( $setting['endtime'] );
        }

        // 服务端尚未下发档位（老站点兼容）：只有激活了付费组件才按主授权时间提醒
        if( '' === $level ){
            $has_paid = $mcv_classes->Addons->is_addons_actived('aliyun')
                || $mcv_classes->Addons->is_addons_actived('qcloud')
                || $mcv_classes->Addons->is_addons_actived('qiniukodo')
                || $mcv_classes->Addons->is_addons_actived('attachment')
                || $mcv_classes->Addons->is_addons_actived('nextlesson')
                || $mcv_classes->Addons->is_addons_actived('coursereport');
            if( ! $has_paid ){
                return;
            }
        }

        if( ! $endtime ){
            return;
        }

        if( $endtime < time() ){
            $class = 'notice notice-error';
            $message = sprintf(
                // translators: %s: 续费链接
                __( 'Mine CloudVod is expired, please <a href="%s">renew</a> in time.', 'mine-cloudvod'), 
                admin_url('/admin.php?page=mcv-addons')
            );
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post($message) );
        }
        elseif( $endtime - time() < 3600*24*10 ){
            $class = 'notice notice-warning is-dismissible';
            $message = sprintf(
                // translators: %1$d: 剩余天数 %2$s: 续费链接
                __( 'Mine CloudVod will expire in %1$d days, please <a href="%2$s">renew</a> in time.', 'mine-cloudvod' ), 
                ceil( ( $endtime - time() ) / 3600 / 24 ), 
                admin_url('/admin.php?page=mcv-addons')
            );
        
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post($message) );
        }
    }

    public function enqueue_scripts(){
        wp_enqueue_script( 'mcv_layer' );
        wp_enqueue_script( 'mcv-admin-init' );
        wp_localize_script( 'mcv-admin-init', 'mcv_admin_init', [
            'nonce' => wp_create_nonce( 'mcv-admin-nonce' )
        ] );
    }

    public function dismiss_notice(){
        if ( ! empty( $_REQUEST['key'] ) && ! empty( $_REQUEST['mcv_nonce'] ) ) {
            if ( wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['mcv_nonce'])), 'mcv-admin-nonce' ) ) {
                $hidden_notices = get_option( '_mcv_hidden_notices', array() );
                if ( ! is_array( $hidden_notices ) ) {
                    $hidden_notices = array();
                }

                $hidden_notices[] = sanitize_key( $_REQUEST['key'] );

                update_option( '_mcv_hidden_notices', $hidden_notices );
                wp_send_json_success();
            } else {
                wp_send_json_error( __( 'Illegal request', 'mine-cloudvod' ) );
            }
        }
    }

    public function init_pages(){
        $key = 'initpages';
        $hidden = get_option( '_mcv_hidden_notices', array() );
        if ( empty( $hidden ) || ! in_array( $key, $hidden ) ) {
            $class = 'mcv-notice notice notice-warning is-dismissible';
            $message = sprintf( 
                // translators: %s: 插件名称
                __( '%s needs to create several pages (Checkout, Order List, Favorites) to function correctly.', 'mine-cloudvod' ), 
                __('Mine CloudVod', 'mine-cloudvod')
            );
        
            printf( '<div class="%1$s" data-id="'. esc_attr($key) .'"><p>%2$s</p><p>%3$s</p></div>', esc_attr( $class ), 
                wp_kses_post($message), 
                '<a href="javascript:;" class="button button-primary mcv-'. esc_attr($key) .'">'. esc_html__('Create', 'mine-cloudvod') .'</a> <a href="javascript:void(0);" class="button-secondary mcv-dismiss">'. esc_html__('Cancle', 'mine-cloudvod') .'</a>'
            );
        }
    }
}
