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
wp_enqueue_style( 'mine-cloudvod-order-list-editor-style' );
$style_color = mcv_get_style_color();
wp_add_inline_style( 'mine-cloudvod-order-list-editor-style', $style_color );
$model_order = new \MineCloudvod\Models\Order();
if( isset($_GET['del']) && is_numeric($order_id = sanitize_text_field( wp_unslash( $_GET['del']))) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'mcv-del-order-' . $user_id ) ){
    $order = get_post( $order_id );
    if( $order && $order->post_author == $user_id && ($order->post_status == 'publish' || $order->post_status == 'draft') ){
        $order_status = get_post_meta( $order->ID, '_mcv_order_status', true );
        if( $order_status != 'payed' ){
            wp_trash_post( $order->ID );
        }
    }
    unset($order);
}
$user_orders = get_posts( [
    'author' => $user_id,
    'post_type' => MINECLOUDVOD_LMS['order_post_type'],
    'post_status' => ['publish', 'draft'],
    'posts_per_page' => 10,
] );
?>
<div id="mcv-order-list-body">
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
                    <li class="mcv-nav-item"><a title="订单管理" href="<?php echo esc_url(mcv_order_list_url()); ?>" class="active">订单管理</a></li>
                    <li class="mcv-nav-item"><a title="我的收藏" href="<?php 
                    if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses']) && MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] ){
                        echo esc_url(get_page_link( MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] ));
                    }else echo esc_url(get_page_link(get_page_by_path('mcv-favorites'))); ?>">我的收藏</a></li>
                </ul>
            </div>
        </aside>
        <?php endif; ?>
        <main class="mcv-uc-main">
            <div class="order-component">
                <div class="tabs">
                    <div class="tab">
                        <a href="javascript:void(0);" class="active">全部订单</a>
                    </div>
                </div>
                <div class="flex-list">
                    <div class="flex-list-header">
                        <div class="flex-row">
                            <div class="flex-cell first">课程订单</div>
                            <div class="flex-cell">价格</div>
                            <div class="flex-cell">状态</div>
                            <div class="flex-cell">操作</div>
                        </div>
                    </div>
                    <?php foreach( $user_orders as $order ):
                        $price = get_post_meta( $order->ID, '_mcv_order_amount', true );
                        $_mcv_order_create_time = get_post_meta( $order->ID, '_mcv_order_create_time', true );
                        $order_status = get_post_meta( $order->ID, '_mcv_order_status', true );
                        $_mcv_order_items = get_post_meta( $order->ID, '_mcv_order_items', true );
                    ?>
                    <div class="flex-list-item">
                        <div class="flex-row head">
                            <div class="time"><?php echo esc_html($_mcv_order_create_time); ?></div>
                            <div class="order-id">订单号：<?php echo esc_html($order->ID); ?></div>
                            <?php if( $order_status != 'payed' ): ?>
                            <a href="<?php echo esc_url(wp_nonce_url(mcv_order_list_url().'?del='.$order->ID, 'mcv-del-order-' . $user_id)); ?>" class="link icon delete"></a>
                            <?php endif; ?>
                        </div>
                        <div class="flex-row content">
                            <div class="flex-cell first cover">
                                <?php if( $order->post_mime_type == '' ):
                                    $course_id = $_mcv_order_items[0];
                                    $course = get_post( $course_id );
                                    $course_link = get_the_permalink( $course_id );
                                    $course_terms = get_the_terms($course_id, 'course-category');
                                    $terms = '';
                                    if( $course_terms )foreach( $course_terms as $term ){
                                        $terms .= $term->name . '/';
                                    }
                                    $terms = trim($terms, '/');
                                ?>
                                <a href="<?php echo esc_url($course_link); ?>" class="link js-report-link" target="_blank">
                                    <img src="<?php echo esc_url(mcv_lms_get_course_thumbnail_url( $course )); ?>" alt="课程封面" />
                                    <div class="title">
                                        <span
                                            title="<?php echo esc_html($course->post_title); ?>"><?php echo esc_html($course->post_title); ?></span>
                                        <div class="sub">
                                            <span><?php echo esc_html($terms); ?></span>
                                        </div>
                                    </div>
                                </a>
                                <?php elseif( $order->post_mime_type == 'mcv/package' ): 
                                    $pkg_id =  $_mcv_order_items[0];
                                    $courses = get_post_meta( $pkg_id, '_mcv_pkg_cid', true );
                                ?>
                                <a href="<?php echo esc_url(get_the_permalink($courses[0])); ?>" class="link js-report-link">
                                    <div class="title">
                                        <span title="<?php echo esc_html($order->post_title); ?>"><?php echo esc_html($order->post_title); ?></span>
                                    </div>
                                </a>
                                <?php elseif( $order->post_mime_type == 'mcv/video' ): 
                                    $video_id =  $_mcv_order_items[0][0];
                                    $video = get_post( $video_id );
                                ?>
                                <a href="<?php echo esc_url(get_the_permalink($video)); ?>" class="link js-report-link">
                                    <div class="title">
                                        <span title="<?php echo esc_html($order->post_title); ?>"><?php echo esc_html($order->post_title); ?></span>
                                    </div>
                                </a>
                                <?php else: ?>
                                <a href="#" class="link js-report-link">
                                    <div class="title">
                                        <span title="<?php echo esc_html($order->post_title); ?>"><?php echo esc_html($order->post_title); ?></span>
                                    </div>
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="flex-cell price">¥<?php echo esc_html($price); ?></div>
                            <div class="flex-cell wording">
                                <?php if( $order_status == 'pending' || $order_status == 'paying' ): ?>
                                <div class="red">等待付款</div>
                                <?php elseif( $order_status == 'payed' ): ?>
                                <div class="black">报名成功</div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-cell operating">
                                <?php if( $order_status == 'pending' || $order_status == 'paying' ): ?>
                                <a class="im-btn operating-btn btn-default btn-s"
                                    href="<?php echo esc_url(mcv_checkout_url( ['orderid' => $order->ID] )); ?>"
                                    target="_blank">立即付款</a>
                                <?php elseif( $order_status == 'payed' ): ?>
                                <div class="black">报名成功</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </section>
</div>