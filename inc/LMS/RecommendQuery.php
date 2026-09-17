<?php
namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;

/**
 * 推荐课程 Query Loop 增强
 *
 * 通过 render_block_data filter 检测带 .mcv-recommend-query className 的 core/query 区块，
 * 在渲染前动态注入付费课程过滤、同分类优先和虚拟销量排序参数。
 * 替代原 course-recommend 自定义区块。
 *
 * 用法：在 Site Editor / CPT Template 中为 Query Loop 区块添加额外 CSS 类名 "mcv-recommend-query"。
 * Query Loop 需设置 postType 为课程 CPT，perPage 控制显示数量。
 */
class RecommendQuery {

    /**
     * 标记 className（在 Site Editor 中添加到 Query Loop 区块的额外 CSS 类名）
     */
    const MARKER_CLASS = 'mcv-recommend-query';

    /**
     * 初始化
     */
    public function __construct() {
        // 在区块渲染前修改 query 属性
        add_filter( 'render_block_data', [ $this, 'inject_recommend_query_args' ], 10, 2 );
    }

    /**
     * 为推荐课程 Query Loop 注入查询参数
     *
     * 检测带 .mcv-recommend-query className 的 core/query 区块，
     * 修改其 query 属性，注入以下参数：
     * - metaQuery: _mcv_access_mode = buynow（仅付费课程）
     * - metaKey + orderBy: 按虚拟销量排序
     * - taxQuery: 同分类课程优先
     * - exclude: 排除当前课程
     *
     * @param array $parsed_block 解析后的区块数据
     * @param \WP_Block|null $block 区块对象（可能为 null）
     * @return array 修改后的区块数据
     */
    public function inject_recommend_query_args( array $parsed_block, $block ): array {
        // 仅处理 Query Loop 区块
        $block_name = $parsed_block['blockName'] ?? '';
        if ( 'core/query' !== $block_name ) {
            return $parsed_block;
        }

        // 检测标记 className
        $class_name = $parsed_block['attrs']['className'] ?? '';
        if ( false === strpos( $class_name, self::MARKER_CLASS ) ) {
            return $parsed_block;
        }

        // 仅在课程详情页生效
        if ( ! is_singular( MINECLOUDVOD_LMS['course_post_type'] ) ) {
            return $parsed_block;
        }

        $course_id = get_queried_object_id();
        if ( ! $course_id ) {
            return $parsed_block;
        }

        // 获取现有 query 属性
        $query = $parsed_block['attrs']['query'] ?? [];

        // 注入 metaQuery：仅付费课程
        $meta_query = $query['metaQuery'] ?? [];
        if ( ! is_array( $meta_query ) ) {
            $meta_query = [];
        }
        // 使用 relation 确保 AND 逻辑
        $meta_query['relation'] = 'AND';
        $meta_query[] = [
            'key'   => '_mcv_access_mode',
            'value' => 'buynow',
        ];
        $query['metaQuery'] = $meta_query;

        // 注入排序：按虚拟销量降序
        $query['metaKey'] = '_mcv_course_virtual_number';
        $query['orderBy'] = 'meta_value_num';
        $query['order']   = 'DESC';

        // 注入 taxQuery：同分类课程优先
        $course_terms = get_the_terms( $course_id, 'course-category' );
        if ( is_array( $course_terms ) && ! empty( $course_terms ) ) {
            $term_ids = wp_list_pluck( $course_terms, 'term_id' );
            $tax_query = $query['taxQuery'] ?? [];
            if ( ! is_array( $tax_query ) ) {
                $tax_query = [];
            }
            $tax_query[] = [
                'taxonomy' => 'course-category',
                'field'    => 'term_id',
                'terms'    => array_map( 'intval', $term_ids ),
            ];
            $query['taxQuery'] = $tax_query;
        }

        // 排除当前课程
        $exclude = $query['exclude'] ?? [];
        if ( ! is_array( $exclude ) ) {
            $exclude = [];
        }
        $exclude[] = $course_id;
        $query['exclude'] = array_map( 'intval', array_unique( $exclude ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- 推荐算法需排除当前课程，业务必需

        // 确保查询课程 CPT
        $query['postType'] = MINECLOUDVOD_LMS['course_post_type'];

        // 写回
        $parsed_block['attrs']['query'] = $query;

        return $parsed_block;
    }
}
