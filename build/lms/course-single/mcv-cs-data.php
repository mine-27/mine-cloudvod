<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

/**
 * 课程详情页 — 数据准备模块
 *
 * 负责读取访问模式、价格、进度、VIP 信息、有效期等课程元数据。
 */

// 新评论 / 评论状态变更时清除星级评分缓存
add_action( 'wp_insert_comment', function ( $comment_id, $comment ) {
    if ( ! $comment instanceof WP_Comment ) {
        $comment = get_comment( $comment_id );
    }
    if ( $comment && $comment->comment_post_ID ) {
        delete_transient( 'mcv_star_counts_' . $comment->comment_post_ID );
    }
}, 10, 2 );
add_action( 'wp_set_comment_status', function ( $comment_id ) {
    $comment = get_comment( $comment_id );
    if ( $comment && $comment->comment_post_ID ) {
        delete_transient( 'mcv_star_counts_' . $comment->comment_post_ID );
    }
} );

/**
 * 获取课程 VIP 提示 HTML
 */
function mcv_cs_vipstr( $course_id ): string {
    $vipstr = '';

    $vip = get_post_meta( $course_id, '_mcv_member_levels', true );
    if ( $vip === '' || $vip === 'no' ) {
        $levels = MINECLOUDVOD_SETTINGS['uc_member_levels'] ?? [];
        if ( is_array( $levels ) && ( $c = count( $levels ) ) ) {
            $vipstr = '<span class="viptip mcv-vip" data-url="' . esc_url( mcv_checkout_url( [ 'id' => 'vip_' . ( $c - 1 ), 'type' => 'vip' ] ) ) . '">'
                . esc_html( $levels[ $c - 1 ]['title'] ) . ' <b>' . esc_html( ( $levels[ $c - 1 ]['discount'] / 10 ) ) . '折</b>，点击加入>>></span>';
        }
    } elseif ( is_numeric( $vip ) && isset( MINECLOUDVOD_SETTINGS['uc_member_levels'][ $vip ] ) ) {
        $vipstr = '<span class="viptip mcv-vip" data-url="' . esc_url( mcv_checkout_url( [ 'id' => 'vip_' . $vip, 'type' => 'vip' ] ) ) . '">'
            . esc_html( MINECLOUDVOD_SETTINGS['uc_member_levels'][ $vip ]['title'] ) . ' <b>免费</b>，点击加入>>></span>';
    }

    return $vipstr;
}

/**
 * 获取课程起始 URL（试看/购买/学习）
 */
function mcv_cs_start_url( $course_id, $access_mode, $is_enrolled, $is_logged_in, $progress ): string {
    if ( $access_mode === 'open' ) {
        return $progress['next'];
    }
    if ( $access_mode === 'free' ) {
        return $is_logged_in ? $progress['next'] : '';
    }
    if ( $access_mode === 'buynow' ) {
        return $is_enrolled ? $progress['next'] : mcv_checkout_url( [ 'id' => $course_id ] );
    }
    return '';
}

/**
 * 获取价格显示文本
 */
function mcv_cs_price_text( $access_mode, $is_enrolled, $course_price ): string {
    if ( $access_mode === 'buynow' ) {
        return $is_enrolled ? __( 'Buyed', 'mine-cloudvod' ) : $course_price;
    }
    if ( $access_mode === 'free' ) {
        return __( 'Free', 'mine-cloudvod' );
    }
    if ( $access_mode === 'open' ) {
        return __( 'Open', 'mine-cloudvod' );
    }
    return '';
}

/**
 * 获取按钮文本
 */
function mcv_cs_btn_text( $access_mode, $is_enrolled, $course_id ): string {
    if ( $access_mode === 'buynow' && ! $is_enrolled ) {
        return apply_filters( 'mcv_lms_buy_btn', __( 'Buy now', 'mine-cloudvod' ), $course_id );
    }
    return __( 'Start learning', 'mine-cloudvod' );
}

/**
 * 获取有效期文本
 */
function mcv_cs_period_text( $course_id ): string {
    $_mcv_course_period = get_post_meta( $course_id, '_mcv_course_period', true );
    if ( ! $_mcv_course_period || $_mcv_course_period === 'forever' ) {
        return __( 'Forever', 'mine-cloudvod' );
    }
    if ( $_mcv_course_period !== 'custom' ) {
        return __( 'Forever', 'mine-cloudvod' );
    }

    $_mcv_course_period_custom = get_post_meta( $course_id, '_mcv_course_period_custom', true );
    $arr = [
        '1'  => __( 'One month', 'mine-cloudvod' ),
        '2'  => __( 'Two months', 'mine-cloudvod' ),
        '3'  => __( 'Three months', 'mine-cloudvod' ),
        '6'  => __( 'Half a year', 'mine-cloudvod' ),
        '12' => __( 'One year', 'mine-cloudvod' ),
        '24' => __( 'Two years', 'mine-cloudvod' ),
        '36' => __( 'Three years', 'mine-cloudvod' ),
        '48' => __( 'Four years', 'mine-cloudvod' ),
        '60' => __( 'Five years', 'mine-cloudvod' ),
    ];
    return $arr[ $_mcv_course_period_custom ] ?? __( 'Forever', 'mine-cloudvod' );
}

/**
 * 星级评分统计（合并为1次SQL查询）
 *
 * @return int[] [总数, 1-2星, 3星, 4-5星]
 */
function mcv_cs_star_counts( $course_id ): array {
    // Transient 缓存：星级评分统计数据变化频率低
    $cache_key = 'mcv_star_counts_' . $course_id;
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    global $wpdb;

    $row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 聚合 SUM 星级统计，单次 SQL 刻意性能优化
        "SELECT
            SUM(CASE WHEN CAST(cm.meta_value AS UNSIGNED) IN (1,2) THEN 1 ELSE 0 END) AS num1,
            SUM(CASE WHEN CAST(cm.meta_value AS UNSIGNED) = 3 THEN 1 ELSE 0 END) AS num3,
            SUM(CASE WHEN CAST(cm.meta_value AS UNSIGNED) IN (4,5) THEN 1 ELSE 0 END) AS num5
        FROM {$wpdb->comments} c
        INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_ID
        WHERE c.comment_post_ID = %d
            AND c.comment_approved = 'approve'
            AND c.comment_parent = 0
            AND cm.meta_key = 'mcv_stars'",
        $course_id
    ) );

    $num1 = (int) ( $row->num1 ?? 0 );
    $num3 = (int) ( $row->num3 ?? 0 );
    $num5 = (int) ( $row->num5 ?? 0 );

    $result = [ $num1 + $num3 + $num5, $num1, $num3, $num5 ];
    set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );

    return $result;
}
