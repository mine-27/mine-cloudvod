<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

$user_id = get_current_user_id();

if( !$user_id ) {
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
    return;
}

$_mcv_favorites = get_user_meta( $user_id, '_mcv_favorites', true );
if( !is_array($_mcv_favorites) ) $_mcv_favorites = [];

if( isset($_GET['del']) && is_numeric($course_id = sanitize_text_field(wp_unslash($_GET['del']))) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'mcv-del-fav-' . $user_id ) ){
    if( isset( $_mcv_favorites[$course_id] ) ){
        unset( $_mcv_favorites[$course_id] );
        update_user_meta( $user_id, '_mcv_favorites', $_mcv_favorites );
    }
    unset($course_id);
}
$style_color = mcv_get_style_color();
wp_add_inline_style( 'mine-cloudvod-favorites-style', $style_color );

?>
<div id="mcv-fav-body">
    <section class="mcv-uc">
        <?php if( !isset( $attributes['hidemenu'] ) || !$attributes['hidemenu'] ): ?>
        <aside class="mcv-uc-left">
            <div class="mcv-nav-area">
                <ul class="mcv-nav">
                    <li class="mcv-nav-item"><a title="课程表" href="<?php 
                    if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses']) && MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses'] ){
                        echo esc_url(get_page_link( MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses'] ));
                    }else echo esc_url(get_page_link(get_page_by_path('mcv-my-courses'))); 
                    ?>">课程表</a></li>
                    <li class="mcv-nav-item"><a title="订单管理" href="<?php echo esc_url(mcv_order_list_url()); ?>">订单管理</a></li>
                    <li class="mcv-nav-item"><a title="我的收藏" href="<?php 
                    if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses']) && MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] ){
                        echo esc_url(get_page_link( MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] ));
                    }else echo esc_url(get_page_link(get_page_by_path('mcv-favorites'))); ?>" class="active">我的收藏</a></li>
                </ul>
            </div>
        </aside>
        <?php endif; ?>
        <main class="mcv-uc-main">
            <div class="im-table-wrap">
                <table class="im-table">
                    <thead>
                        <tr>
                            <th style="width: 70%;">课程信息</th>
                            <th style="width: 16%;">金额</th>
                            <th style="width: 14%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach( $_mcv_favorites as $course_id => $time ): 
                        $link = get_permalink( $course_id );
                        $title = get_the_title( $course_id );
                        $price = mcv_lms_show_course_price( $course_id );
                        ?>
                        <tr>
                            <td>
                                <div class="fav-info clearfix">
                                    <a class="fav-info-cover" href="<?php echo esc_url($link); ?>" target="_blank" title="<?php echo esc_html($title); ?>"><img src="<?php echo esc_url(get_the_post_thumbnail_url($course_id)); ?>"></a>
                                    <div class="fav-info-desc">
                                        <p class="fav-info-name">
                                            <a class="link-3" href="<?php echo esc_url($link); ?>" target="_blank" title="<?php echo esc_html($title); ?>"><?php echo esc_html($title); ?></a>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td><div class="fav-price"><?php echo esc_html($price); ?></div></td>
                            <td><a class="link-3" href="<?php echo esc_url(wp_nonce_url(get_page_link(get_page_by_path('mcv-favorites')).'?del='.$course_id, 'mcv-del-fav-' . $user_id)); ?>">取消收藏</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </section>
</div>