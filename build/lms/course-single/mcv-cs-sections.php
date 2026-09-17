<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

/**
 * 课程详情页 — 章节数据构建模块
 *
 * 负责读取课程的章节/课时列表并构建前端所需的数据结构。
 */

/**
 * 解析课时属性（兼容序列化字符串）
 */
function mcv_cs_parse_lesson_attrs( $lesson_id ): array {
    $attrs = get_post_meta( $lesson_id, '_mcv_lms_lesson_attrs', true );
    if ( ! is_array( $attrs ) && is_string( $attrs ) ) {
        $attrs = unserialize( $attrs );
    }
    if ( ! $attrs ) {
        $attrs = [];
    }
    if ( ! isset( $attrs['duration'] ) ) {
        $attrs['duration'] = get_post_meta( $lesson_id, '_mcv_lesson_duration', true );
    }
    return $attrs;
}

/**
 * 构建单个课时数据（用于前端 JSON）
 */
function mcv_cs_build_lesson_data( $lesson, $overrides = [] ): array {
    $attrs     = mcv_cs_parse_lesson_attrs( $lesson->ID );
    $price     = get_post_meta( $lesson->ID, '_mcv_lesson_price', true );
    $enrolled  = mcv_lms_is_enrolled( $lesson->ID );
    $attach    = get_post_meta( $lesson->ID, '_mcv_lesson_attachments', true );

    return array_merge( [
        'id'           => $lesson->ID,
        'title'        => $lesson->post_title,
        'url'          => get_the_permalink( $lesson->ID ),
        'attrs'        => $attrs,
        'lesson_type'  => get_post_meta( $lesson->ID, '_lesson_type', true ),
        'aliyun_livetime' => get_post_meta( $lesson->ID, 'aliyun_livetime', true ),
        'price'        => $price,
        'enrolled'     => $enrolled,
        'attachments'  => is_array( $attach ) ? count( $attach ) : 0,
    ], $overrides );
}

/**
 * 构建课程完整章节列表（用于前端 JSON + SEO 列表）
 *
 * @param object $cpost  课程 WP_Post 对象
 * @param bool   $catelog_show     登录后显示目录
 * @param bool   $catelog_enroll   购买后显示目录
 * @param bool   $is_user_logged_in
 * @param bool   $is_enrolled      是否已购买课程
 * @param int    $course_id
 * @return array{ lists: array, lessonCount: int, duration: int, lesson_list: string, shixue: string }
 */
function mcv_cs_build_sections(
    $cpost,
    $catelog_show,
    $catelog_enroll,
    $is_user_logged_in,
    $is_enrolled,
    $course_id
): array {
    $courses_lessons = mcv_lms_get_courses_lessons( $cpost );
    $lessonCount     = mcv_lms_lesson_count( $cpost );
    $duration        = mcv_lms_course_duration( $cpost );
    $lesson_list     = '';
    $shixue          = '';
    $lists           = [];

    if ( ! is_array( $courses_lessons ) ) {
        return compact( 'lists', 'lessonCount', 'duration', 'lesson_list', 'shixue' );
    }

    foreach ( $courses_lessons as $section ) {
        $section_enrolled = mcv_lms_is_enrolled( $section['ID'] );
        $list = [
            'id'       => $section['ID'],
            'title'    => $section['post_title'],
            'enrolled' => $section_enrolled,
            'price'    => get_post_meta( $section['ID'], '_mcv_section_price', true ),
        ];

        foreach ( $section['Lessons'] as $lesson ) {
            if ( $lesson->post_type === 'section' ) {
                // 子章节：section 节点下嵌套课时
                $subLessons = [];
                if ( is_array( $lesson->Lessons ) ) {
                    foreach ( $lesson->Lessons as $sl ) {
                        $subLessons[] = mcv_cs_build_lesson_data( $sl, [
                            'lesson_type'    => get_post_meta( $lesson->ID, '_lesson_type', true ),
                            'aliyun_livetime' => get_post_meta( $lesson->ID, 'aliyun_livetime', true ),
                            'enrolled'       => mcv_lms_is_enrolled( $sl->ID ),
                            'attachments'    => 0,
                        ] );
                    }
                }
                $list['lessons'][] = [
                    'id'        => $lesson->ID,
                    'title'     => $lesson->post_title,
                    'enrolled'  => $section_enrolled,
                    'price'     => get_post_meta( $lesson->ID, '_mcv_section_price', true ),
                    'post_type' => 'section',
                    'lessons'   => $subLessons,
                ];
            } else {
                // 普通课时
                $lesson_enrolled = mcv_lms_is_enrolled( $lesson->ID );
                $url   = get_the_permalink( $lesson->ID );
                $lid   = $lesson->ID;
                $ltitle = $lesson->post_title;

                $attrs = mcv_cs_parse_lesson_attrs( $lesson->ID );
                if ( isset( $attrs['preview'] ) && $attrs['preview'] && ! $shixue ) {
                    $shixue = $url;
                }

                // 目录可见性控制
                if (
                    ( $catelog_show && ! $is_user_logged_in )
                    || ( $catelog_show && $is_user_logged_in && $catelog_enroll && ! $lesson_enrolled && ! $section_enrolled && ! $is_enrolled )
                ) {
                    $lid    = 0;
                    $ltitle = __( 'Check the catalog after enrolled.', 'mine-cloudvod' );
                    $url    = get_the_permalink( $course_id );
                }

                $list['lessons'][] = mcv_cs_build_lesson_data( $lesson, [
                    'id'       => $lid,
                    'title'    => $ltitle,
                    'url'      => $url,
                    'enrolled' => $lesson_enrolled,
                ] );

                $lesson_list .= '<li><a href="' . esc_url( $url ) . '" title="' . esc_attr( $lesson->post_title ) . '">'
                    . esc_html( $lesson->post_title ) . '</a></li>';
            }
        }

        $lists[] = $list;
    }

    return compact( 'lists', 'lessonCount', 'duration', 'lesson_list', 'shixue' );
}
