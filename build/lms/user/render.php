<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

$submenus = $attributes['submenus'] ?? [];

$user_id = get_current_user_id();
$avatar = MINECLOUDVOD_URL.'/static/img/user.png';

$viewDependencies = include( MINECLOUDVOD_PATH.'/build/lms/user/view.asset.php' );
foreach($viewDependencies['dependencies'] as $dpc){
    wp_enqueue_style( $dpc );
    wp_enqueue_script( $dpc );
}

if( $user_id ){
    $avatar = mcv_get_avatar_url( $user_id ) ?: $avatar;
    
    $ucStr_escaped = '<div class="mcv-usercenter">
        <img class="mcv-avatar" src="'.esc_url($avatar).'" />
        <div class="submenu">';
        foreach( $submenus as $sm ){
            $ucStr_escaped .= '<a href="'.esc_url($sm['url']).'" rel="nofollow">'.esc_html($sm['title']).'</a>';
        }
        $ucStr_escaped .= '<a href="#" class="mcv-bind-account" style="border-top: 1px solid #ccc;margin-top: 5px;padding-top: 5px;">'.esc_html__('Account Settings', 'mine-cloudvod').'</a>';
        $ucStr_escaped .= '<a href="'.esc_url(wp_logout_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']??'')))).'" rel="nofollow">'.esc_html__('Log out', 'mine-cloudvod').'</a>
        </div>
    </div>';
    echo wp_kses_post( $ucStr_escaped );
}
else{
    echo '<div class="mcv-usercenter mcv-login"><img class="mcv-avatar" src="'.esc_url($avatar).'" title="'.esc_html__('Click to login', 'mine-cloudvod').'" /></div>';
}
?>