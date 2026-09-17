<?php
namespace MineCloudvod\LMS\Blocks;

use MineCloudvod\LMS\Bindings;
use MineCloudvod\LMS\RecommendQuery;

class Course{

    /**
     * 需要通过 render_block_context 注入 courseId 的原子区块列表
     * FSE 模板模式下仅保留 course-chapters
     */
    private static $atomic_blocks = [
        'mine-cloudvod/course-chapters',
    ];

    public function __construct()
    {
        add_action( 'init',                     [ $this, 'mcv_register_course_blocks'] );
        add_filter( 'render_block_context',     [ $this, 'inject_course_id_context' ], 10, 2 );

        // FSE 模式：注册 Block Bindings 源和推荐课程 Query 增强
        if ( function_exists( 'register_block_bindings_source' ) ) {
            new Bindings();
            new RecommendQuery();
        }
    }

    /**
     * 动态向区块注入 courseId
     *
     * 当区块渲染时 context 中缺少 courseId 时，
     * 从当前查询对象补全（兼容 FSE 模板渲染和编辑器预览）。
     */
    public function inject_course_id_context( $context, $parsed_block ) {
        $block_name = $parsed_block['blockName'] ?? '';

        if ( ! in_array( $block_name, self::$atomic_blocks, true ) ) {
            return $context;
        }

        // 父级已通过属性提供了有效的 courseId，不要覆盖
        if ( ! empty( $context['mine-cloudvod/courseId'] ) ) {
            return $context;
        }

        // 从当前查询对象获取课程 ID（兼容 FSE 模板渲染）
        $queried = get_queried_object();
        if ( $queried instanceof \WP_Post && $queried->post_type === MINECLOUDVOD_LMS['course_post_type'] ) {
            $context['mine-cloudvod/courseId'] = (int) $queried->ID;
        }

        return $context;
    }

    public function mcv_register_course_blocks(){
        if( !mcv_current_theme_is_fse_theme() ){
            wp_register_style( 'mcv-global-styles-inline', false, array(), MINECLOUDVOD_VERSION );
            wp_enqueue_style( 'mcv-global-styles-inline' );
            wp_add_inline_style( 'mcv-global-styles-inline', 'body{ --wp--style--global--wide-size: '. (MINECLOUDVOD_SETTINGS['mcv_lms_general']['wide_size']??'1200px') .'; }' );
        }

        // Legacy monolithic blocks
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/user/');
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/course-list/');
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/course-single/');
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/lesson-single/');
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/course-checkout/');
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/order-list/');
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/favorites/');
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/user-courses/');

        // FSE atomic block — course chapters（唯一保留的自定义区块）
        require_once MINECLOUDVOD_PATH . '/build/lms/atomic/mcv-atomic-context.php';
        register_block_type( MINECLOUDVOD_PATH . '/build/lms/atomic/course-chapters/');
    }

}
