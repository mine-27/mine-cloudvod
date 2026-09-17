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
do_action( 'mcv_user_courses_before' );
wp_enqueue_style( 'mine-cloudvod-course-list-editor-style' );
$style_color = mcv_get_style_color();
wp_add_inline_style( 'mine-cloudvod-course-list-editor-style', $style_color );
$all_meta = get_user_meta( $user_id );

$my_courses = [];
if( is_array( $all_meta ) ){
    foreach( $all_meta as $k => $v ){
        if( substr( $k, 0, 26 ) === '_mcv_lms_enroll_course_id_' ){
            $eid = substr( $k, 26);
            $post = get_post( $eid );

            if($post && in_array( $post->post_type, [MINECLOUDVOD_LMS['course_post_type'], MINECLOUDVOD_LMS['lesson_post_type'], 'section']) ){
                $title = $post->post_title;
                $course_id = $post->ID;
                if( $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
                    $course_id = mcv_lms_get_course_id_by_lesson_id( $post->ID );
                    $course = get_post( $course_id );
                    $title = $course->post_title . ' - ' . $title;
                }
                elseif( $post->post_type == 'section' ){
                    $course_id = $bsection->post_parent;
                    $course = get_post( $course_id );
                    $title = $course->post_title . ' - ' . $title;
                }
                $progress = mcv_lms_user_course_progress( get_current_user_id(), $post );
                $expire_in = __('Forever', 'mine-cloudvod');
                $_mcv_course_period = mcv_lms_get_course_period( $course_id );

                if( $_mcv_course_period > 0 ){
                    $expire_in = sprintf(
                        // translators: 过期时间
                        __( 'Expires in %s', 'mine-cloudvod' ), 
                        wp_date('Y-m-d', strtotime(wp_date('Y-m-d H:i:s',$v[0]).'+'.$_mcv_course_period.'month'))
                    );
                }
                $my_courses[] = [
                    'id' => $post->ID,
                    'course_id' => $course_id,
                    'thumb' => mcv_lms_get_course_thumbnail_url($course_id),
                    'course_url' => get_permalink($course_id),
                    'title' => $title,
                    'progress' => $progress,
                    'expire_in' => $expire_in,
                    'price' => mcv_lms_get_course_price( $course_id ),
                    'count' => mcv_lms_lesson_count($post),
                    'sections' => [],
                ];
            }
        }
    }
}
wp_localize_script( 'mine-cloudvod-user-courses-view-script', 'mcv_my_courses', $my_courses );

?>
<div id="mcv-user-courses-body">
<section class="mcv-uc">
    <?php if( !isset( $attributes['hidemenu'] ) || !$attributes['hidemenu'] ): ?>
    <aside class="mcv-uc-left">
        <div class="mcv-nav-area">
            <ul class="mcv-nav">
                <li class="mcv-nav-item"><a title="课程表" href="<?php 
                    if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses']) && MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses'] ){
                        echo esc_url(get_page_link( MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses'] ));
                    }else echo esc_url(get_page_link(get_page_by_path('mcv-my-courses'))); 
                    ?>" class="active">课程表</a></li>
                <li class="mcv-nav-item"><a title="订单管理" href="<?php echo esc_url(mcv_order_list_url()); ?>">订单管理</a></li>
                <li class="mcv-nav-item"><a title="我的收藏" href="<?php 
                    if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses']) && MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] ){
                        echo esc_url(get_page_link( MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] ));
                    }else echo esc_url(get_page_link(get_page_by_path('mcv-favorites'))); ?>">我的收藏</a></li>
            </ul>
        </div>
    </aside>
    <?php endif; ?>
    <main class="mcv-uc-main">
        <div class="wrapper-plan">
            <div id="mcv-my-courses">
                
            </div>
        </div>
    </main>
</section>
</div>