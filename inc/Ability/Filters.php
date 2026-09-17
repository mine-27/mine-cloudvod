<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Ability;

use MineCloudvod\MineCloudVod;

class Filters
{
    public function __construct(){
        add_filter( 'post_thumbnail_id',    [ $this, 'mcv_post_thumbnail_id'], 10, 2);
        add_filter( 'page_template',        [ $this, 'mcv_page_template'] );
        add_filter( 'display_post_states',  [ $this, 'mcv_add_post_state'], 10, 2 );
        add_action( 'csf_mcv_settings_save_after', [ $this, 'mcv_flush_permalinks' ] );
        add_filter( 'get_avatar_url', [ $this, 'mcv_avatar_url'], 10, 3 );
        add_action( 'init', [ $this, 'mcv_global_vars' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'remove_themes_assets' ], 99 );
        add_filter( 'mcv_lms_is_enrolled', array($this, 'mcv_admin_can_learn'));
        add_filter( 'update_user_metadata', [ $this, 'mcv_session_tokens' ], 10, 4 );
        add_action('template_redirect', array($this, 'force_login'));
        // 禁止课程视频快进
        if( isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['disableSeek'] ) && MINECLOUDVOD_SETTINGS['mcv_lms_course']['disableSeek'] ){
            add_filter( 'mcv_filter_aliplayer_events', array($this, 'mcv_lessons_disable_seek_aliplayer'), 10, 3 );
            add_filter( 'mcv_filter_dplayer_events', array($this, 'mcv_lessons_disable_seek_dplayer'), 10, 3 );
            add_filter( 'mcv_filter_tcplayer_events', array($this, 'mcv_lessons_disable_seek_tcplayer'), 10, 4 );
        }
        // 禁止在指定浏览器中播放视频
        if( isset( MINECLOUDVOD_SETTINGS['browser_blacklist'] ) && !empty( MINECLOUDVOD_SETTINGS['browser_blacklist'] ) ){
            add_filter( 'render_block', [$this, 'browser_blacklist'], 10, 2 );
        }
    }
    
    public function browser_blacklist( $block_content, $block ) {
        // 你的自定义区块
        if ( !in_array( $block['blockName'],  ['mine-cloudvod/aliplayer', 'mine-cloudvod/dplayer', 'mine-cloudvod/tc-vod']) ) {
            return $block_content;
        }
        $blacklist = trim( MINECLOUDVOD_SETTINGS['browser_blacklist']??'' );
        $userAgent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- User-Agent 仅用于黑名单正则匹配，非输出
        $inBlacklist = preg_match('/'. $blacklist .'/i', $userAgent);

        // 判断参数是否合法
        if ( $inBlacklist ) {
            return '<div style="padding:16px; background:#f44336; color:#fff; border-radius:6px;">'.(MINECLOUDVOD_SETTINGS['browser_blacklist_info']??'❌ 您的浏览器不支持本站视频的播放，请使用 Chrome 或 Safari 最新版。').'</div>';
        }

        return $block_content;
    }
    public function mcv_lessons_disable_seek_tcplayer($events, $pconfig, $post_id, $parsed_block){
        $divId = sprintf('mcv_%s', md5(serialize($parsed_block['attrs'])));
        $events .= $this->removeVideoPrograssBar($divId, '.vjs-progress-control.vjs-control');
        return $events;
    }
    public function mcv_lessons_disable_seek_dplayer($events, $pconfig, $attributes){
        $divId = sprintf('mcv_%s', md5(serialize($attributes)));
        $events .= $this->removeVideoPrograssBar($divId, '.dplayer-bar-wrap');
        return $events;
    }
    public function mcv_lessons_disable_seek_aliplayer($events, $pconfig, $attributes){
        $divId = sprintf('mcv_%s', md5(serialize($attributes)));
        $events .= $this->removeVideoPrograssBar($divId, '.prism-progress');
        return $events;
    }
    public function removeVideoPrograssBar($divId, $barWrap){
        global $post;
        $events = '';
        if( $post && $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
            $events = 'const alidiv = document.querySelector("#'. $divId . ' '.$barWrap.'");alidiv.parentNode.removeChild(alidiv);';
        }
        return $events;
    }
    public function force_login(){
        if( isset( MINECLOUDVOD_SETTINGS['forceLogin'] ) && MINECLOUDVOD_SETTINGS['forceLogin'] && !is_user_logged_in() && !is_admin() ){
            $viewDependencies = include( MINECLOUDVOD_PATH.'/build/lms/user/view.asset.php' );
            foreach($viewDependencies['dependencies'] as $dpc){
                wp_enqueue_style( $dpc );
            }
            wp_enqueue_script( 'mcv_layer' );
            wp_enqueue_style( 'mine-cloudvod-user-style' );
            wp_enqueue_script( 'mine-cloudvod-user-script' );
            wp_add_inline_script( 'mine-cloudvod-user-script', 'window.onload = function() {
                window?.renderMcvLogin();
            };' );
        }
    }
    public function mcv_session_tokens( $null, $object_id, $meta_key, $meta_value ){
        $num = 100; 
        if( $meta_key == 'session_tokens' && is_array( $meta_value ) && count( $meta_value ) > $num - 1 ){
            $meta_value = array_slice($meta_value, count( $meta_value ) - $num, $num, true );
            global $wpdb;
            $meta_ids = $wpdb->get_col(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- session_tokens 底层精确查询，刻意性能优化
                $wpdb->prepare(
                    "SELECT umeta_id FROM {$wpdb->usermeta} WHERE meta_key = 'session_tokens' AND user_id = %d",
                    $object_id
                )
                );
            if ( !empty( $meta_ids ) ) {
                $null = $wpdb->update( $wpdb->usermeta, ['meta_value'=>serialize( $meta_value )], ['user_id'=>$object_id, 'meta_key'=>'session_tokens'] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- session_tokens 底层更新，无 WP 抽象层替代
            }
        }
        return $null;
    }
    public function mcv_admin_can_learn($canplay){
        if( current_user_can( 'manage_options' ) ){
            if( !isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['admin_enrolled'] )
                || MINECLOUDVOD_SETTINGS['mcv_lms_course']['admin_enrolled'] == '1'
            )
                return 1;
        }
        return $canplay;
    }

    public function remove_themes_assets(){
        global $post;
        if( $post && $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
            global $wp_styles, $wp_scripts;
            foreach( $wp_styles->registered as $key => $value ){
                $src = $value->src;
                if( strpos($src, 'wp-content/themes/') > 0 ){
                    unset( $wp_styles->registered[$key] );
                }
            }
            foreach( $wp_scripts->registered as $key => $value ){
                $src = $value->src;
                if( $key != 'jquery' && strpos($src, 'wp-content/themes/') > 0 ){
                    unset( $wp_scripts->registered[$key] );
                }
            }
        }
    }

    public function mcv_global_vars(){
        //全局js变量
        wp_localize_script( 'mcv_localize_script', 'mcv_global', apply_filters( 'mcv_global_vars',  [
            'islogin' => is_user_logged_in(),
        ] ) );
        wp_enqueue_script( 'mcv_localize_script' );
        if( isset( MINECLOUDVOD_SETTINGS['hideAdminBar'] ) && MINECLOUDVOD_SETTINGS['hideAdminBar'] ){
            show_admin_bar( false );
		    remove_action('wp_head', '_admin_bar_bump_cb');
        }
    }

    public function mcv_avatar_url( $url, $id_or_email, $args ){
        if( is_user_logged_in() ){
            $uid = 0;
            if( is_numeric( $id_or_email ) ){
                $uid = $id_or_email;
            }
            elseif( is_string($id_or_email) && is_email( $id_or_email ) ){
                $user = get_user_by( 'email', $id_or_email );
                if( $user ){
                    $uid = $user->ID;
                }
            }
            if( $uid ){
                $mcv_avatar = mcv_get_avatar_url( $uid );
                if( $mcv_avatar ){
                    $url = $mcv_avatar;
                }
            }
        }
        return $url;
    }

    public function mcv_add_post_state( $post_states, $post ) {
        if ( mine_get_page_id_by_slug( 'mcv-aliplayer-note' ) === $post->ID ) {
            $post_states['mcv_aliplayer_note'] = __( 'Mine Aliplayer Note Page', 'mine-cloudvod' );
        }
        if ( mine_get_page_id_by_slug( 'mcv-checkout' ) === $post->ID ) {
            $post_states['mcv_checkout'] = __( 'Mine Checkout Page', 'mine-cloudvod' );
        }
        if ( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['order_list']) ) {
            if( MINECLOUDVOD_SETTINGS['mcv_lms_general']['order_list'] == $post->ID ) $post_states['mcv_order_list'] = __( 'Mine Order List Page', 'mine-cloudvod' );
        }
        elseif ( mine_get_page_id_by_slug( 'mcv-order-list' ) === $post->ID ) {
            $post_states['mcv_order_list'] = __( 'Mine Order List Page', 'mine-cloudvod' );
        }
        if ( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses']) ) {
            if( MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses'] == $post->ID ) $post_states['mcv_my_courses'] = __( 'User\'s courses', 'mine-cloudvod' );
        }
        elseif ( mine_get_page_id_by_slug( 'mcv-my-courses' ) === $post->ID ) {
            $post_states['mcv_my_courses'] = __( 'User\'s courses', 'mine-cloudvod' );
        }
        if ( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses']) ) {
            if( MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] == $post->ID ) $post_states['mcv_favorites'] = __( 'Mine Favorite Courses', 'mine-cloudvod' );
        }
        elseif ( mine_get_page_id_by_slug( 'mcv-favorites' ) === $post->ID ) {
            $post_states['mcv_favorites'] = __( 'Mine Favorite Courses', 'mine-cloudvod' );
        }
        if ( mine_get_page_id_by_slug( 'mcv-index' ) === $post->ID ) {
            $post_states['mcv_index'] = __( 'MCV Index', 'mine-cloudvod' );
        }
        return $post_states;
    }

    public function mcv_page_template( $page_template ){
        $is_fse = mcv_current_theme_is_fse_theme();
        if ( is_page( 'upload-aliyunvod' ) ) {
            $page_template = MINECLOUDVOD_PATH . '/templates/vod/upload-aliyunvod.php';
        }
        if ( is_page( 'mcv-aliplayer-note' ) ) {
            $page_template = MINECLOUDVOD_PATH . '/templates/vod/note.php';
        }
        if ( !$is_fse && is_page( 'mcv-checkout' ) ) {
            $page_template = mcv_lms_get_template_path('checkout');
        }
        if ( !$is_fse && is_page( 'mcv-order-list' ) ) {
            $page_template = mcv_lms_get_template_path('order-list');
        }
        if ( !$is_fse && is_page( 'mcv-my-courses' ) ) {
            $page_template = mcv_lms_get_template_path('user-courses');
        }
        if ( !$is_fse && is_page( 'mcv-favorites' ) ) {
            $page_template = mcv_lms_get_template_path('favorites');
        }
        if ( !$is_fse && is_page( 'mcv-index' ) ) {
            $page_template = mcv_lms_get_template_path('index');
        }
        return $page_template;
    }

    /**
     * 如果文章没有特色图像,则使用播放器的封面图像
     */
    public function mcv_post_thumbnail_id($thumbnail_id, $post){
        if( !$thumbnail_id && has_block('mine-cloudvod/aliyun-vod', $post) ){
            $post_id = is_numeric( $post ) ? $post : $post->ID;
            if( !$post_id ) return $thumbnail_id;
            $_mcv_alivod_snapshot = get_post_meta($post_id, '_mcv_alivod_snapshot', true);
           
            return $_mcv_alivod_snapshot;
        }
        return $thumbnail_id;
    }

    public function mcv_flush_permalinks(){
        flush_rewrite_rules();
    }
}
