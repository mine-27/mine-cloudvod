<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

/**
 * 课程详情页 — 资源加载模块
 *
 * 负责注册和加载前端所需的脚本、样式及内联数据。
 */

/**
 * 加载课程详情页前端资源
 *
 * @param bool $is_user_logged_in  是否已登录
 */
function mcv_cs_enqueue_assets( $is_user_logged_in ): void {
    $viewDependencies = include MINECLOUDVOD_PATH . '/build/lms/course-single/view.asset.php';

    wp_register_script(
        'mine-cloudvod-course-single-view-script',
        MINECLOUDVOD_URL . '/build/lms/course-single/view.js',
        array_merge( $viewDependencies['dependencies'], [ 'mcv_layer' ] ),
        MINECLOUDVOD_VERSION,
        true
    );

    wp_set_script_translations( 'mine-cloudvod-course-single-view-script', 'mine-cloudvod' );

    foreach ( $viewDependencies['dependencies'] as $dpc ) {
        wp_enqueue_style( $dpc );
    }

    wp_enqueue_style( 'wp-block-library' );
    wp_enqueue_global_styles();
    wp_enqueue_style( 'mine-cloudvod-course-single-editor-style' );

    $style_color = mcv_get_style_color();
    wp_add_inline_style( 'mine-cloudvod-course-single-editor-style', $style_color );

    wp_enqueue_script( 'mine-cloudvod-course-single-view-script' );

    if ( ! $is_user_logged_in ) {
        wp_enqueue_style( 'mine-cloudvod-user-style' );
        wp_enqueue_script( 'mine-cloudvod-user-script' );
    }
}

/**
 * 传递课程数据到前端 JS（wp_localize_script）
 *
 * @param int    $course_id
 * @param string $course_title
 * @param string $thumbnail
 * @param object $cpost
 * @param string $access_mode
 * @param bool   $is_enrolled
 * @param array  $course_attachments
 * @param int[]  $star_counts  [总数, 1-2星, 3星, 4-5星]
 * @param bool   $catelog_no
 * @param bool   $catelog_show
 * @param bool   $catelog_enroll
 * @param int    $lessonCount
 * @param array  $lists
 */
function mcv_cs_localize_data(
    $course_id,
    $course_title,
    $thumbnail,
    $cpost,
    $access_mode,
    $is_enrolled,
    $course_attachments,
    $star_counts,
    $catelog_no,
    $catelog_show,
    $catelog_enroll,
    $lessonCount,
    $lists
): void {
    wp_localize_script( 'mine-cloudvod-course-single-view-script', 'mcv_lesson_data', [
        'course' => [
            'id'          => $course_id,
            'title'       => $course_title,
            'thumb'       => $thumbnail,
            'url'         => get_the_permalink( $course_id ),
            'content'     => do_blocks( $cpost->post_content ),
            'access_mode' => $access_mode,
            'enrolled'    => $is_enrolled,
            'attachments' => $course_attachments,
            'fav'         => mcv_lms_is_favorite( $course_id ),
            'num'         => array_merge( $star_counts, [ 10 ] ),
            'catelog_no'  => $catelog_no,
            'catelog_show'   => $catelog_show,
            'catelog_enroll' => $catelog_enroll,
            'price'       => '￥' . mcv_lms_get_course_price( $course_id ),
            'lessonCount' => $lessonCount,
        ],
        'sections' => $lists,
        'current'  => $cpost->ID,
        'ckurl'    => mcv_checkout_url(),
        'showcate' => isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['showCatelogInDetail'] )
            && MINECLOUDVOD_SETTINGS['mcv_lms_course']['showCatelogInDetail'] === '1',
    ] );
}
