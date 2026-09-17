<?php
namespace MineCloudvod\RestApi\Agent;

if ( ! defined( 'ABSPATH' ) )
    exit;

/**
 * 章节 CRUD 端点
 *
 * 路由前缀：mine-cloudvod/agent/v1/section
 *
 * 端点：
 *   POST   /section              创建章节
 *   GET    /section              批量查询（按 course_id）
 *   GET    /section/{id}         获取单个章节（含其下课时列表）
 *   PUT    /section/{id}         更新章节
 *   DELETE /section/{id}         删除章节（级联删除其下课时）
 *   POST   /section/order        批量排序
 *
 * 数据模型：post_type='section'（已在 LMS\PostType::register_topic_post_types 注册）
 *          post_parent = 课程 ID
 *
 * 鉴权：Application Passwords + current_user_can('edit_posts')
 */
class Section extends Base{

    protected $base = 'section';

    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(){

        $root = $this->root();

        /**
         * 创建章节
         * POST /section
         */
        register_rest_route( $root, '/' . $this->base, [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'create' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => $this->get_write_args(),
        ] );

        /**
         * 批量查询章节
         * GET /section?course_id=xxx
         */
        register_rest_route( $root, '/' . $this->base, [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'list_items' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'course_id' => [
                    'validate_callback' => function( $v ){ return is_numeric( $v ); },
                    'required' => true,
                    'description' => '课程 ID',
                ],
                'with_lessons' => [
                    'type'    => 'boolean',
                    'default' => false,
                    'description' => '是否附带章节下的课时列表',
                ],
            ],
        ] );

        /**
         * 获取单个章节
         * GET /section/{id}
         */
        register_rest_route( $root, '/' . $this->base . '/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'id' => [ 'validate_callback' => function( $v ){ return is_numeric( $v ); } ],
                'with_lessons' => [ 'type' => 'boolean', 'default' => true ],
            ],
        ] );

        /**
         * 更新章节
         * PUT /section/{id}
         */
        register_rest_route( $root, '/' . $this->base . '/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'update' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => $this->get_write_args(),
        ] );

        /**
         * 删除章节（级联删除其下课时）
         * DELETE /section/{id}
         */
        register_rest_route( $root, '/' . $this->base . '/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'delete' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'id'    => [ 'validate_callback' => function( $v ){ return is_numeric( $v ); } ],
                'force' => [ 'type' => 'boolean', 'default' => true ],
            ],
        ] );

        /**
         * 批量排序
         * POST /section/order
         * body: { "ids": [1,2,3], "course_id": 10 }
         */
        register_rest_route( $root, '/' . $this->base . '/order', [
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'order' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'ids'       => [
                    'type' => 'array',
                    'validate_callback' => function( $v ){
                        if( ! is_array( $v ) ) return false;
                        foreach( $v as $id ){ if( ! is_numeric( $id ) ) return false; }
                        return true;
                    },
                ],
                'course_id' => [ 'validate_callback' => function( $v ){ return is_numeric( $v ); } ],
            ],
        ] );
    }

    /**
     * 写操作公共参数
     */
    private function get_write_args(){
        return [
            'course_id'  => [ 'type' => 'integer', 'required' => false, 'description' => '所属课程 ID（创建时必填）' ],
            'title'      => [ 'type' => 'string', 'description' => '章节标题（创建时必填）' ],
            'content'    => [ 'type' => 'string', 'description' => '章节描述' ],
            'price'      => [ 'type' => 'string', 'description' => '章节独立价格（access_mode=buynow 时生效）' ],
            'is_chapter' => [ 'type' => 'boolean', 'default' => false, 'description' => '是否为子章节（章节下还能嵌章节）' ],
        ];
    }

    /**
     * 创建章节
     * 对齐 RestApi\LMS\Section::course_section_save 逻辑
     */
    public function create( \WP_REST_Request $request ){
        $course_id = (int) $request['course_id'];
        $title     = sanitize_text_field( $request['title'] );

        if( ! $course_id || ! get_post( $course_id ) ){
            return $this->fail( __( 'course_id is required and must exist', 'mine-cloudvod' ), 400, 'invalid_course' );
        }
        if( ! $title ){
            return $this->fail( __( 'title is required', 'mine-cloudvod' ), 400, 'missing_title' );
        }

        $order_id = mcv_lms_get_section_order_id( $course_id );
        $postarr = [
            'post_type'    => 'section',
            'post_title'   => $title,
            'post_content' => isset( $request['content'] ) ? wp_kses_post( $request['content'] ) : '',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_parent'  => $course_id,
            'menu_order'   => $order_id,
        ];

        $section_id = wp_insert_post( $postarr, true );
        if( is_wp_error( $section_id ) ){
            return $this->fail( $section_id->get_error_message(), 500, 'insert_failed' );
        }

        $this->sync_meta( $section_id, $request );

        mcv_lms_del_lessons_cache( $course_id );

        return $this->ok( $this->get_section_data( $section_id, false ), 201 );
    }

    /**
     * 批量查询
     */
    public function list_items( \WP_REST_Request $request ){
        $course_id = (int) $request['course_id'];
        if( ! $course_id ){
            return $this->fail( __( 'course_id is required', 'mine-cloudvod' ), 400, 'missing_course' );
        }

        $with_lessons = (bool) $request['with_lessons'];
        $sections = get_posts( [
            'post_type'    => 'section',
            'post_parent'  => $course_id,
            'orderby'      => 'menu_order',
            'order'        => 'ASC',
            'numberposts'  => 999,
            'post_status'  => 'any',
        ] );

        $list = [];
        foreach( $sections as $section ){
            $list[] = $this->get_section_data( $section->ID, $with_lessons );
        }

        return $this->ok( [ 'list' => $list, 'total' => count( $list ) ] );
    }

    /**
     * 获取单个章节
     */
    public function get( \WP_REST_Request $request ){
        $section_id = (int) $request['id'];
        $post = get_post( $section_id );
        if( ! $post || $post->post_type !== 'section' ){
            return $this->fail( __( 'section not found', 'mine-cloudvod' ), 404, 'not_found' );
        }
        return $this->ok( $this->get_section_data( $section_id, (bool) $request['with_lessons'] ) );
    }

    /**
     * 更新章节
     */
    public function update( \WP_REST_Request $request ){
        $section_id = (int) $request['id'];
        $post = get_post( $section_id );
        if( ! $post || $post->post_type !== 'section' ){
            return $this->fail( __( 'section not found', 'mine-cloudvod' ), 404, 'not_found' );
        }

        $postarr = [ 'ID' => $section_id ];
        if( isset( $request['title'] ) )   $postarr['post_title']   = sanitize_text_field( $request['title'] );
        if( isset( $request['content'] ) ) $postarr['post_content'] = wp_kses_post( $request['content'] );
        if( isset( $request['course_id'] ) && get_post( (int) $request['course_id'] ) ){
            $postarr['post_parent'] = (int) $request['course_id'];
        }

        $result = wp_update_post( $postarr, true );
        if( is_wp_error( $result ) ){
            return $this->fail( $result->get_error_message(), 500, 'update_failed' );
        }

        $this->sync_meta( $section_id, $request );

        $course_id = (int) ( $postarr['post_parent'] ?? $post->post_parent );
        mcv_lms_del_lessons_cache( $course_id );

        return $this->ok( $this->get_section_data( $section_id, false ) );
    }

    /**
     * 删除章节（级联删除其下课时与子章节）
     * 复用 Course::delete_section_recursive
     */
    public function delete( \WP_REST_Request $request ){
        $section_id = (int) $request['id'];
        $force = (bool) ( $request['force'] ?? true );
        $post = get_post( $section_id );
        if( ! $post || $post->post_type !== 'section' ){
            return $this->fail( __( 'section not found', 'mine-cloudvod' ), 404, 'not_found' );
        }

        $course_id = (int) $post->post_parent;

        // 递归删除子章节与课时
        Course::delete_section_recursive( $section_id );

        mcv_lms_del_lessons_cache( $course_id );

        return $this->ok( [ 'id' => $section_id, 'deleted' => true ] );
    }

    /**
     * 批量排序
     * 对齐 RestApi\LMS\Section::course_section_order 逻辑
     */
    public function order( \WP_REST_Request $request ){
        global $wpdb;
        $ids       = $request['ids'];
        $course_id = (int) $request['course_id'];
        if( ! $course_id || ! get_post( $course_id ) ){
            return $this->fail( __( 'course_id is required and must exist', 'mine-cloudvod' ), 400, 'invalid_course' );
        }
        if( empty( $ids ) ){
            return $this->fail( __( 'ids is required', 'mine-cloudvod' ), 400, 'missing_ids' );
        }

        $menu_order = 1;
        foreach( $ids as $id ){
            $id = (int) $id;
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 批量静默更新 menu_order 排序，避免触发 save_post hook
                $wpdb->posts,
                [ 'menu_order' => $menu_order ],
                [ 'ID' => $id ]
            );
            // 关键：直改 wp_posts 表绕过了 WP 对象缓存，必须手动失效，
            // 否则打包端 get_children / get_post 会命中旧 menu_order 缓存
            clean_post_cache( $id );
            $menu_order++;
        }

        mcv_lms_del_lessons_cache( $course_id );

        return $this->ok( [ 'ordered' => count( $ids ) ] );
    }

    /**
     * 同步章节 meta
     */
    private function sync_meta( $section_id, \WP_REST_Request $request ){
        if( isset( $request['price'] ) ){
            update_post_meta( $section_id, '_mcv_section_price', sanitize_text_field( $request['price'] ) );
        }
        if( isset( $request['is_chapter'] ) ){
            update_post_meta( $section_id, '_mcv_is_chapter', sanitize_text_field( $request['is_chapter'] ? '1' : '0' ) );
        }
    }

    /**
     * 组装章节数据
     */
    private function get_section_data( $section_id, $with_lessons = false ){
        $post = get_post( $section_id );
        $data = $this->post_to_array( $post );

        $data['price']      = get_post_meta( $section_id, '_mcv_section_price', true );
        $data['is_chapter'] = (bool) get_post_meta( $section_id, '_mcv_is_chapter', true );
        $data['course_id']  = (int) $post->post_parent;

        if( $with_lessons ){
            $lessons = get_posts( [
                'post_type'    => MINECLOUDVOD_LMS['lesson_post_type'],
                'post_parent'  => $section_id,
                'orderby'      => 'menu_order',
                'order'        => 'ASC',
                'numberposts'  => 999,
                'post_status'  => 'any',
            ] );
            $data['lessons'] = [];
            foreach( $lessons as $lesson ){
                $lesson_type = get_post_meta( $lesson->ID, '_lesson_type', true ) ?: 'vod';
                $duration = get_post_meta( $lesson->ID, '_mcv_lesson_duration', true );
                if( ! is_array( $duration ) ) $duration = [ 'minute' => 0, 'second' => 0 ];
                $data['lessons'][] = [
                    'id'          => $lesson->ID,
                    'title'       => $lesson->post_title,
                    'lesson_type' => $lesson_type,
                    'menu_order'  => (int) $lesson->menu_order,
                    'status'      => $lesson->post_status,
                    'duration'    => [
                        'minute' => (int) ( $duration['minute'] ?? 0 ),
                        'second' => (int) ( $duration['second'] ?? 0 ),
                    ],
                ];
            }
        }

        return $data;
    }
}
