<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

/**
 * 原子化区块 — 共享上下文数据助手
 *
 * 所有原子区块的 render.php 引入此文件来获取课程/课时上下文数据。
 * Block Context 优先，全局 $post 回退。
 */

/**
 * 从 Block Context 或全局状态中获取课程 ID
 *
 * @param array    $attributes 区块属性
 * @param WP_Block $block      区块对象（含 context）
 * @return int
 */
function mcv_atomic_get_course_id( array $attributes, $block ): int {
    // 0. 编辑器预览桥接（ServerSideRender REST API 从 edit.js 传入）
    if ( ! empty( $attributes['_courseId'] ) ) {
        return (int) $attributes['_courseId'];
    }
    // 1. Block Context 传递
    $context = $block->context ?? [];
    if ( isset( $context['mine-cloudvod/courseId'] ) ) {
        return (int) $context['mine-cloudvod/courseId'];
    }
    // 2. 属性直接传递（编排器或独立使用）
    if ( ! empty( $attributes['cid'] ) ) {
        return (int) $attributes['cid'];
    }
    // 3. 全局 $post 回退
    global $post;
    if ( $post && $post->post_type === 'mcv_course' ) {
        return (int) $post->ID;
    }
    return 0;
}

/**
 * 从 Block Context 或全局状态中获取课时 ID
 *
 * @param array    $attributes 区块属性
 * @param WP_Block $block      区块对象（含 context）
 * @return int
 */
function mcv_atomic_get_lesson_id( array $attributes, $block ): int {
    $context = $block->context ?? [];
    if ( isset( $context['mine-cloudvod/lessonId'] ) ) {
        return (int) $context['mine-cloudvod/lessonId'];
    }
    if ( ! empty( $attributes['lid'] ) ) {
        return (int) $attributes['lid'];
    }
    global $post;
    if ( $post && $post->post_type === 'mcv_lesson' ) {
        return (int) $post->ID;
    }
    return 0;
}

/**
 * 获取课程基础数据（供多个原子区块复用，避免重复查询）
 *
 * @param int $course_id 课程 ID
 * @return array{ post: WP_Post|null, title: string, thumbnail: string, access_mode: string, price: string, is_enrolled: bool, terms: WP_Term[]|false, progress: array, is_logged_in: bool, start_url: string, btn_text: string, vipstr: string, str_price: string }
 */
function mcv_atomic_get_course_data( int $course_id ): array {
    static $cache = [];

    if ( isset( $cache[ $course_id ] ) ) {
        return $cache[ $course_id ];
    }

    $cpost         = get_post( $course_id );
    $title         = $cpost ? get_the_title( $cpost ) : '';
    $thumbnail     = $cpost ? mcv_lms_get_course_thumbnail_url( $cpost ) : '';
    $access_mode   = mcv_lms_get_course_access_mode( $course_id );
    $course_price  = mcv_lms_show_course_price( $course_id, true );
    $is_enrolled   = mcv_lms_is_enrolled( $course_id );
    $progress      = mcv_lms_get_course_progress( $cpost );
    $is_logged_in  = is_user_logged_in();
    $terms         = get_the_terms( $course_id, 'course-category' );

    $start_url     = mcv_cs_start_url( $course_id, $access_mode, $is_enrolled, $is_logged_in, $progress );
    $start_url     = apply_filters( 'mcv_lms_buy_link', $start_url, $course_id );
    $vipstr        = $access_mode === 'buynow' ? mcv_cs_vipstr( $course_id ) : '';
    $str_price     = mcv_cs_price_text( $access_mode, $is_enrolled, $course_price );
    $btn_text      = mcv_cs_btn_text( $access_mode, $is_enrolled, $course_id );

    $data = compact(
        'cpost', 'title', 'thumbnail', 'access_mode', 'course_price',
        'is_enrolled', 'progress', 'is_logged_in', 'terms', 'start_url',
        'vipstr', 'str_price', 'btn_text'
    );

    $cache[ $course_id ] = $data;
    return $data;
}

/**
 * 获取课程元数据（课时数、时长、学生数等）
 *
 * @param int $course_id
 * @return array{ lesson_count: int, duration: int, enrolled_number: int, update_date: string, difficulty: string, period: string }
 */
function mcv_atomic_get_course_meta( int $course_id ): array {
    static $cache = [];

    if ( isset( $cache[ $course_id ] ) ) {
        return $cache[ $course_id ];
    }

    $cpost      = get_post( $course_id );
    $lessonCount = mcv_lms_lesson_count( $cpost );
    $duration    = mcv_lms_course_duration( $cpost );
    $enrolled    = mcv_lms_get_course_enrolled_number( $cpost );
    $update_date = get_the_modified_date( 'Y-m-d', $cpost );
    $period      = mcv_cs_period_text( $course_id );

    $difficulty  = '';
    $cd_options  = MINECLOUDVOD_SETTINGS['mcv_lms_course']['details'] ?? true;
    if ( $cd_options && ( $cd_options['difficulty'] ?? true ) ) {
        $darr      = MINECLOUDVOD_LMS['course_difficulty'] ?? [];
        $diff_val  = get_post_meta( $course_id, '_mcv_course_difficulty', true );
        $difficulty = $darr[ $diff_val ] ?? '';
    }

    $data = compact( 'lessonCount', 'duration', 'enrolled', 'update_date', 'difficulty', 'period' );
    $cache[ $course_id ] = $data;
    return $data;
}

/**
 * 加载课程详情页所需的辅助函数文件
 * 各原子区块的 render.php 应在顶部调用此函数
 */
function mcv_atomic_load_course_helpers(): void {
    static $loaded = false;
    if ( $loaded ) return;

    // 用 function_exists 防止 build/ 和 src/ 路径重复加载
    if ( ! function_exists( 'mcv_cs_vipstr' ) ) {
        require_once __DIR__ . '/../course-single/mcv-cs-data.php';
    }
    if ( ! function_exists( 'mcv_cs_build_sections' ) ) {
        require_once __DIR__ . '/../course-single/mcv-cs-sections.php';
    }
    if ( ! function_exists( 'mcv_cs_enqueue_assets' ) ) {
        require_once __DIR__ . '/../course-single/mcv-cs-assets.php';
    }

    $loaded = true;
}
