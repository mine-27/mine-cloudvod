<?php
namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;

/**
 * Block Bindings 自定义绑定源
 *
 * 将课程动态数据（价格、购买链接、课程元信息）绑定到 WordPress 核心区块的属性上，
 * 使用户在 FSE 模板编辑器中可以自由布局课程详情页。
 *
 * @requires WordPress 6.5+
 */
class Bindings {

    /**
     * 初始化：在 init hook 中注册绑定源
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_bindings' ] );
    }

    /**
     * 注册所有 Block Bindings 自定义源
     *
     * @return void
     */
    public function register_bindings(): void {
        if ( ! function_exists( 'register_block_bindings_source' ) ) {
            return;
        }

        // 加载课程数据辅助函数（复用 static cache）
        $this->load_helpers();

        // 课程价格（含 VIP 提示）
        register_block_bindings_source(
            'mcv/course-price',
            [
                'label'              => __( 'Course Price', 'mine-cloudvod' ),
                'get_value_callback' => [ $this, 'get_course_price' ],
                'uses_context'       => [ 'postId' ],
            ]
        );

        // 购买/学习按钮 URL
        register_block_bindings_source(
            'mcv/buy-url',
            [
                'label'              => __( 'Course Buy/Learn URL', 'mine-cloudvod' ),
                'get_value_callback' => [ $this, 'get_buy_url' ],
                'uses_context'       => [ 'postId' ],
            ]
        );

        // 购买/学习按钮文本
        register_block_bindings_source(
            'mcv/buy-text',
            [
                'label'              => __( 'Course Buy/Learn Button Text', 'mine-cloudvod' ),
                'get_value_callback' => [ $this, 'get_buy_text' ],
                'uses_context'       => [ 'postId' ],
            ]
        );

        // 课程元信息 — 6个独立绑定源，用户在编辑器中一目了然
        $meta_sources = [
            'mcv/course-lesson-count' => [ __( 'Lessons', 'mine-cloudvod' ), 'get_course_lesson_count' ],
            'mcv/course-duration'     => [ __( 'Hours', 'mine-cloudvod' ),     'get_course_duration' ],
            'mcv/course-enrolled'     => [ __( 'Students', 'mine-cloudvod' ), 'get_course_enrolled' ],
            'mcv/course-update-date'  => [ __( 'Updated', 'mine-cloudvod' ),  'get_course_update_date' ],
            'mcv/course-difficulty'   => [ __( 'Difficulty', 'mine-cloudvod' ),   'get_course_difficulty' ],
            'mcv/course-period'       => [ __( 'Validity period', 'mine-cloudvod' ), 'get_course_period' ],
        ];
        foreach ( $meta_sources as $name => [ $label, $callback ] ) {
            register_block_bindings_source(
                $name,
                [
                    'label'              => $label,
                    'get_value_callback' => [ $this, $callback ],
                    'uses_context'       => [ 'postId' ],
                ]
            );
        }

        // 课程 VIP 提示（HTML）
        register_block_bindings_source(
            'mcv/course-vip',
            [
                'label'              => __( 'Course VIP Tip', 'mine-cloudvod' ),
                'get_value_callback' => [ $this, 'get_course_vip' ],
                'uses_context'       => [ 'postId' ],
            ]
        );
    }

    /**
     * 获取课程 ID（从 Block Context 或全局 $post）
     *
     * @param array $source_args 绑定源的参数
     * @param \WP_Block $block_instance 区块实例
     * @return int
     */
    private function resolve_course_id( array $source_args, $block_instance ): int {
        // 1. Block Context（postId 由 FSE 模板或 Query Loop 提供）
        if ( ! empty( $block_instance->context['postId'] ) ) {
            $pid = (int) $block_instance->context['postId'];
            if ( get_post_type( $pid ) === MINECLOUDVOD_LMS['course_post_type'] ) {
                return $pid;
            }
        }

        // 2. 全局 $post 回退（课程详情页）
        global $post;
        if ( $post && $post->post_type === MINECLOUDVOD_LMS['course_post_type'] ) {
            return (int) $post->ID;
        }

        return 0;
    }

    /**
     * 课程价格绑定源回调
     *
     * 返回价格文本（含 VIP 标记 HTML），用于绑定 core/paragraph 的 content 属性。
     *
     * @param array    $source_args  绑定源参数（支持 showVip: bool）
     * @param \WP_Block $block_instance 区块实例
     * @return string
     */
    public function get_course_price( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $data = mcv_atomic_get_course_data( $course_id );
        return $data['str_price'];
    }

    /**
     * 购买/学习 URL 绑定源回调
     *
     * 返回课程起始 URL（试看/购买/继续学习），用于绑定 core/button 的 url 属性。
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_buy_url( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '#';
        }

        $data = mcv_atomic_get_course_data( $course_id );
        return $data['start_url'];
    }

    /**
     * 购买/学习按钮文本绑定源回调
     *
     * 返回按钮文本（"立即购买"或"开始学习"），用于绑定 core/button 的 text 属性。
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_buy_text( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $data = mcv_atomic_get_course_data( $course_id );
        return $data['btn_text'];
    }

    /**
     * 课程课时数
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_course_lesson_count( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $cd_opts = MINECLOUDVOD_SETTINGS['mcv_lms_course']['details'] ?? true;
        if ( $cd_opts && ! ( $cd_opts['lesson_num'] ?? true ) ) {
            return '';
        }

        $meta = mcv_atomic_get_course_meta( $course_id );
        return sprintf(
            /* translators: %d: number of lessons */
            __( '%d Lessons', 'mine-cloudvod' ),
            $meta['lessonCount']
        );
    }

    /**
     * 课程时长（小时）
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_course_duration( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $cd_opts = MINECLOUDVOD_SETTINGS['mcv_lms_course']['details'] ?? true;
        if ( $cd_opts && ! ( $cd_opts['hours'] ?? true ) ) {
            return '';
        }

        $meta = mcv_atomic_get_course_meta( $course_id );
        return sprintf(
            '%s %s',
            round( $meta['duration'] / 60 / 60, 1 ),
            __( 'Hours', 'mine-cloudvod' )
        );
    }

    /**
     * 已购买学生数
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_course_enrolled( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $cd_opts = MINECLOUDVOD_SETTINGS['mcv_lms_course']['details'] ?? true;
        if ( $cd_opts && ! ( $cd_opts['student_num'] ?? true ) ) {
            return '';
        }

        $meta = mcv_atomic_get_course_meta( $course_id );
        return sprintf(
            /* translators: %d: number of students */
            __( '%d Students', 'mine-cloudvod' ),
            $meta['enrolled']
        );
    }

    /**
     * 最近更新日期
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_course_update_date( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $cd_opts = MINECLOUDVOD_SETTINGS['mcv_lms_course']['details'] ?? true;
        if ( $cd_opts && ! ( $cd_opts['update'] ?? true ) ) {
            return '';
        }

        $meta = mcv_atomic_get_course_meta( $course_id );
        return $meta['update_date'];
    }

    /**
     * 难度等级
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_course_difficulty( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $cd_opts = MINECLOUDVOD_SETTINGS['mcv_lms_course']['details'] ?? true;
        if ( $cd_opts && ! ( $cd_opts['difficulty'] ?? true ) ) {
            return '';
        }

        $meta = mcv_atomic_get_course_meta( $course_id );
        return $meta['difficulty'];
    }

    /**
     * 有效期
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_course_period( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $meta = mcv_atomic_get_course_meta( $course_id );
        return sprintf(
            '%s: %s',
            __( 'Validity', 'mine-cloudvod' ),
            $meta['period']
        );
    }

    /**
     * 课程 VIP 提示绑定源回调
     *
     * 返回 VIP 提示 HTML（用于购买栏旁的会员优惠提示），用于绑定 core/paragraph 的 content 属性。
     *
     * @param array    $source_args
     * @param \WP_Block $block_instance
     * @return string
     */
    public function get_course_vip( array $source_args, $block_instance ): string {
        $course_id = $this->resolve_course_id( $source_args, $block_instance );
        if ( ! $course_id ) {
            return '';
        }

        $data = mcv_atomic_get_course_data( $course_id );
        return $data['vipstr'];
    }

    /**
     * 加载课程数据辅助函数
     *
     * 复用 atomic-context.php 中的函数，确保 static cache 生效。
     */
    private function load_helpers(): void {
        $context_path = MINECLOUDVOD_PATH . '/build/lms/atomic/mcv-atomic-context.php';
        if ( file_exists( $context_path ) ) {
            require_once $context_path;
        }

        // 加载课程数据辅助函数（mcv_cs_period_text 等依赖）
        if ( function_exists( 'mcv_atomic_load_course_helpers' ) ) {
            mcv_atomic_load_course_helpers();
        }
    }
}
