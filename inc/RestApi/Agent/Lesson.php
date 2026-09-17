<?php
namespace MineCloudvod\RestApi\Agent;

if ( ! defined( 'ABSPATH' ) )
    exit;

/**
 * 课时 CRUD 端点
 *
 * 路由前缀：mine-cloudvod/agent/v1/lesson
 *
 * 端点：
 *   POST   /lesson              创建课时
 *   GET    /lesson              批量查询（支持 section_id / course_id / ids / paged）
 *   GET    /lesson/{id}         获取单个课时
 *   PUT    /lesson/{id}         更新课时
 *   DELETE /lesson/{id}         删除课时
 *   POST   /lesson/order        批量排序
 *
 * 课时类型分流（_lesson_type meta）：
 *   - text : 图文课时，post_content 直接为正文
 *   - vod  : 点播课时，post_content 由 LessonContentBuilder 拼古腾堡区块
 *
 * 鉴权：Application Passwords + current_user_can('edit_posts')
 */
class Lesson extends Base{

    protected $base = 'lesson';

    /**
     * @var \MineCloudvod\LMS\Content\LessonContentBuilder
     */
    private $builder;

    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
        $this->builder = new \MineCloudvod\LMS\Content\LessonContentBuilder();
    }

    public function register_routes(){

        $root = $this->root();

        /**
         * 创建课时
         * POST /lesson
         */
        register_rest_route( $root, '/' . $this->base, [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'create' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => $this->get_write_args(),
        ] );

        /**
         * 批量查询课时
         * GET /lesson
         */
        register_rest_route( $root, '/' . $this->base, [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'list_items' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'section_id' => [
                    'validate_callback' => function( $v ){ return is_numeric( $v ); },
                    'description' => '按章节筛选',
                ],
                'course_id'  => [
                    'validate_callback' => function( $v ){ return is_numeric( $v ); },
                    'description' => '按课程筛选（查所有章节下的课时）',
                ],
                'ids'        => [ 'type' => 'string', 'description' => '逗号分隔的课时 ID' ],
                'paged'      => [ 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint' ],
                'per_page'   => [ 'type' => 'integer', 'default' => 50, 'sanitize_callback' => 'absint' ],
                'lesson_type'=> [ 'type' => 'string', 'enum' => [ 'text', 'vod' ], 'description' => '按类型筛选' ],
            ],
        ] );

        /**
         * 获取单个课时
         * GET /lesson/{id}
         */
        register_rest_route( $root, '/' . $this->base . '/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'id' => [ 'validate_callback' => function( $v ){ return is_numeric( $v ); } ],
            ],
        ] );

        /**
         * 更新课时
         * PUT /lesson/{id}
         */
        register_rest_route( $root, '/' . $this->base . '/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'update' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => $this->get_write_args(),
        ] );

        /**
         * 删除课时
         * DELETE /lesson/{id}
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
         * POST /lesson/order
         * body: { "ids": [1,2,3], "section_id": 10 }
         */
        register_rest_route( $root, '/' . $this->base . '/order', [
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'order' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'ids'        => [
                    'type' => 'array',
                    'validate_callback' => function( $v ){
                        if( ! is_array( $v ) ) return false;
                        foreach( $v as $id ){ if( ! is_numeric( $id ) ) return false; }
                        return true;
                    },
                ],
                'section_id' => [ 'validate_callback' => function( $v ){ return is_numeric( $v ); } ],
            ],
        ] );
    }

    /**
     * 写操作公共参数
     */
    private function get_write_args(){
        return [
            'section_id'    => [ 'type' => 'integer', 'required' => false, 'description' => '所属章节 ID（创建时必填）' ],
            'title'         => [ 'type' => 'string', 'description' => '课时标题（创建时必填）' ],
            'lesson_type'   => [
                'type'    => 'string',
                'default' => 'vod',
                'enum'    => [ 'text', 'vod' ],
                'description' => '课时类型：text=图文 / vod=点播',
            ],
            'content'       => [ 'type' => 'string', 'description' => '图文课时正文（lesson_type=text 时必填）' ],
            'preview'       => [ 'type' => 'boolean', 'default' => false, 'description' => '是否可预览' ],
            'thumbnail'     => [ 'type' => 'integer', 'description' => '封面 attachment ID' ],
            // ===== 点播课时专用参数（lesson_type=vod 时生效）=====
            'video_source'  => [
                'type'    => 'string',
                'enum'    => [ 'direct', 'embed', 'alivod', 'tcvod', 'qiniukodo', 'dogecloud', 'huaweivod', 'bunnynet', 'cloudflare' ],
                'description' => '视频源（点播课时必填）',
            ],
            'video_params'  => [
                'type' => 'object',
                'description' => '视频参数，结构随 video_source 变化。详见 docs/agent-api.md',
            ],
            'duration'      => [
                'type' => 'integer',
                'description' => '视频时长（秒），点播课时用。会自动转 minute/second 存入 _mcv_lesson_duration',
            ],
        ];
    }

    /**
     * 创建课时
     */
    public function create( \WP_REST_Request $request ){
        $section_id = (int) $request['section_id'];
        $title      = sanitize_text_field( $request['title'] );
        $lesson_type = sanitize_text_field( $request['lesson_type'] ?? 'vod' );

        if( ! $section_id || ! get_post( $section_id ) ){
            return $this->fail( __( 'section_id is required and must exist', 'mine-cloudvod' ), 400, 'invalid_section' );
        }
        if( ! $title ){
            return $this->fail( __( 'title is required', 'mine-cloudvod' ), 400, 'missing_title' );
        }

        // 按类型拼装 post_content
        $built = $this->build_content( $lesson_type, $request );
        if( is_wp_error( $built ) ){
            return $this->fail( $built->get_error_message(), 400, $built->get_error_code() );
        }

        $order_id = mcv_lms_get_lesson_order_id( $section_id );
        $postarr = [
            'post_type'    => MINECLOUDVOD_LMS['lesson_post_type'],
            'post_title'   => $title,
            'post_content' => $built['content'],
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_parent'  => $section_id,
            'menu_order'   => $order_id,
        ];

        $lesson_id = wp_insert_post( $postarr, true );
        if( is_wp_error( $lesson_id ) ){
            return $this->fail( $lesson_id->get_error_message(), 500, 'insert_failed' );
        }

        // 写入课时类型与时长
        update_post_meta( $lesson_id, '_lesson_type', $lesson_type );
        if( $lesson_type === 'vod' && ! empty( $built['duration'] ) ){
            update_post_meta( $lesson_id, '_mcv_lesson_duration', $built['duration'] );
        }
        // preview
        update_post_meta( $lesson_id, '_mcv_lms_lesson_attrs', [ 'preview' => (bool) $request['preview'] ? '1' : '0' ] );
        // thumbnail
        if( isset( $request['thumbnail'] ) ){
            set_post_thumbnail( $lesson_id, (int) $request['thumbnail'] );
        }

        $this->clear_course_cache( $lesson_id );

        return $this->ok( $this->get_lesson_data( $lesson_id ), 201 );
    }

    /**
     * 批量查询
     */
    public function list_items( \WP_REST_Request $request ){
        $args = [
            'post_type'      => MINECLOUDVOD_LMS['lesson_post_type'],
            'post_status'    => 'any',
            'posts_per_page' => (int) $request['per_page'],
            'paged'          => (int) $request['paged'],
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ];

        if( $request['section_id'] ){
            $args['post_parent'] = (int) $request['section_id'];
        }
        if( $request['ids'] ){
            $args['post__in'] = array_filter( array_map( 'absint', explode( ',', $request['ids'] ) ) );
        }
        // 按课程筛选：先取该课程所有 section id，再 post_parent__in
        if( $request['course_id'] ){
            $sections = get_posts( [
                'post_type'    => 'section',
                'post_parent'  => (int) $request['course_id'],
                'numberposts'  => 999,
                'fields'       => 'ids',
                'post_status'  => 'any',
            ] );
            if( empty( $sections ) ){
                return $this->ok( [ 'list' => [], 'total' => 0 ] );
            }
            $args['post_parent__in'] = $sections;
        }
        // 按类型筛选
        if( $request['lesson_type'] ){
            $args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 按课时类型筛选，业务必需
                [ 'key' => '_lesson_type', 'value' => sanitize_text_field( $request['lesson_type'] ) ],
            ];
        }

        $query = new \WP_Query( $args );
        $list = [];
        foreach( $query->posts as $post ){
            $list[] = $this->get_lesson_data( $post->ID );
        }

        return $this->ok( [
            'list'      => $list,
            'total'     => (int) $query->found_posts,
            'paged'     => (int) $args['paged'],
            'per_page'  => (int) $args['posts_per_page'],
        ] );
    }

    /**
     * 获取单个课时
     */
    public function get( \WP_REST_Request $request ){
        $lesson_id = (int) $request['id'];
        $post = get_post( $lesson_id );
        if( ! $post || $post->post_type !== MINECLOUDVOD_LMS['lesson_post_type'] ){
            return $this->fail( __( 'lesson not found', 'mine-cloudvod' ), 404, 'not_found' );
        }
        return $this->ok( $this->get_lesson_data( $lesson_id ) );
    }

    /**
     * 更新课时
     */
    public function update( \WP_REST_Request $request ){
        $lesson_id = (int) $request['id'];
        $post = get_post( $lesson_id );
        if( ! $post || $post->post_type !== MINECLOUDVOD_LMS['lesson_post_type'] ){
            return $this->fail( __( 'lesson not found', 'mine-cloudvod' ), 404, 'not_found' );
        }

        $postarr = [ 'ID' => $lesson_id ];
        if( isset( $request['title'] ) ){
            $postarr['post_title'] = sanitize_text_field( $request['title'] );
        }
        if( isset( $request['section_id'] ) ){
            $section_id = (int) $request['section_id'];
            if( get_post( $section_id ) ){
                $postarr['post_parent'] = $section_id;
            }
        }

        // 类型变更或内容变更时重新拼装
        $lesson_type = sanitize_text_field( $request['lesson_type'] ?? get_post_meta( $lesson_id, '_lesson_type', true ) ?: 'vod' );
        $need_rebuild = isset( $request['lesson_type'] )
                     || isset( $request['content'] )
                     || isset( $request['video_source'] )
                     || isset( $request['video_params'] )
                     || isset( $request['duration'] );
        if( $need_rebuild ){
            $built = $this->build_content( $lesson_type, $request, $lesson_id );
            if( is_wp_error( $built ) ){
                return $this->fail( $built->get_error_message(), 400, $built->get_error_code() );
            }
            $postarr['post_content'] = $built['content'];
        }

        $result = wp_update_post( $postarr, true );
        if( is_wp_error( $result ) ){
            return $this->fail( $result->get_error_message(), 500, 'update_failed' );
        }

        // 同步 meta
        if( isset( $request['lesson_type'] ) ){
            update_post_meta( $lesson_id, '_lesson_type', $lesson_type );
        }
        if( $need_rebuild && $lesson_type === 'vod' && ! empty( $built['duration'] ) ){
            update_post_meta( $lesson_id, '_mcv_lesson_duration', $built['duration'] );
        }
        if( isset( $request['preview'] ) ){
            $attrs = get_post_meta( $lesson_id, '_mcv_lms_lesson_attrs', true );
            if( ! is_array( $attrs ) ) $attrs = [];
            $attrs['preview'] = (bool) $request['preview'] ? '1' : '0';
            update_post_meta( $lesson_id, '_mcv_lms_lesson_attrs', $attrs );
        }
        if( isset( $request['thumbnail'] ) ){
            set_post_thumbnail( $lesson_id, (int) $request['thumbnail'] );
        }

        $this->clear_course_cache( $lesson_id );

        return $this->ok( $this->get_lesson_data( $lesson_id ) );
    }

    /**
     * 删除课时
     */
    public function delete( \WP_REST_Request $request ){
        $lesson_id = (int) $request['id'];
        $force = (bool) ( $request['force'] ?? true );
        $post = get_post( $lesson_id );
        if( ! $post || $post->post_type !== MINECLOUDVOD_LMS['lesson_post_type'] ){
            return $this->fail( __( 'lesson not found', 'mine-cloudvod' ), 404, 'not_found' );
        }

        $result = wp_delete_post( $lesson_id, $force );
        if( ! $result ){
            return $this->fail( __( 'delete failed', 'mine-cloudvod' ), 500, 'delete_failed' );
        }

        $this->clear_course_cache( $lesson_id );

        return $this->ok( [ 'id' => $lesson_id, 'deleted' => true ] );
    }

    /**
     * 批量排序
     * 对齐 RestApi\LMS\Lesson::section_lesson_order 逻辑
     */
    public function order( \WP_REST_Request $request ){
        global $wpdb;
        $ids        = $request['ids'];
        $section_id = (int) $request['section_id'];
        if( ! $section_id || ! get_post( $section_id ) ){
            return $this->fail( __( 'section_id is required and must exist', 'mine-cloudvod' ), 400, 'invalid_section' );
        }
        if( empty( $ids ) ){
            return $this->fail( __( 'ids is required', 'mine-cloudvod' ), 400, 'missing_ids' );
        }

        $menu_order = 1;
        foreach( $ids as $id ){
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 批量静默更新 menu_order 排序，避免触发 save_post hook
                $wpdb->posts,
                [ 'menu_order' => $menu_order, 'post_parent' => $section_id ],
                [ 'ID' => (int) $id ]
            );
            $menu_order++;
        }

        $this->clear_course_cache( reset( $ids ) );

        return $this->ok( [ 'ordered' => count( $ids ) ] );
    }

    /**
     * 按 lesson_type 拼装 post_content
     *
     * @param string $lesson_type text|vod
     * @param \WP_REST_Request $request
     * @param int $lesson_id 更新时传入，用于回退已有内容
     * @return array|\WP_Error ['content'=>string,'duration'=>array]
     */
    private function build_content( $lesson_type, $request, $lesson_id = 0 ){
        if( $lesson_type === 'text' ){
            $content = isset( $request['content'] ) ? wp_kses_post( $request['content'] ) : '';
            if( ! $content && ! $lesson_id ){
                return new \WP_Error( 'missing_content', __( 'content is required for text lesson', 'mine-cloudvod' ) );
            }
            return [ 'content' => $content, 'duration' => [ 'minute' => 0, 'second' => 0 ] ];
        }

        // vod 点播课时
        $video_source = sanitize_text_field( $request['video_source'] );
        if( ! $video_source && $lesson_id ){
            // 更新时未传 video_source，保留原内容
            return [
                'content'  => get_post_field( 'post_content', $lesson_id ),
                'duration' => get_post_meta( $lesson_id, '_mcv_lesson_duration', true ) ?: [ 'minute' => 0, 'second' => 0 ],
            ];
        }
        if( ! $video_source ){
            return new \WP_Error( 'missing_video_source', __( 'video_source is required for vod lesson', 'mine-cloudvod' ) );
        }

        $video_params = $request['video_params'];
        if( ! is_array( $video_params ) ) $video_params = [];

        // duration 可独立传，也可在 video_params.duration 里
        if( isset( $request['duration'] ) ){
            $video_params['duration'] = (int) $request['duration'];
        }

        $built = $this->builder->build( $video_source, $video_params );
        if( empty( $built['content'] ) ){
            return new \WP_Error( 'build_failed', __( 'failed to build lesson content, check video_source and video_params', 'mine-cloudvod' ) );
        }
        return $built;
    }

    /**
     * 组装课时完整数据
     */
    private function get_lesson_data( $lesson_id ){
        $post = get_post( $lesson_id );
        $data = $this->post_to_array( $post );

        $data['lesson_type'] = get_post_meta( $lesson_id, '_lesson_type', true ) ?: 'vod';

        $attrs = get_post_meta( $lesson_id, '_mcv_lms_lesson_attrs', true );
        if( ! is_array( $attrs ) && is_string( $attrs ) ) $attrs = unserialize( $attrs );
        $data['preview'] = isset( $attrs['preview'] ) ? ( $attrs['preview'] == '1' ) : false;

        $duration = get_post_meta( $lesson_id, '_mcv_lesson_duration', true );
        if( ! is_array( $duration ) ) $duration = [ 'minute' => 0, 'second' => 0 ];
        $data['duration'] = [
            'minute' => (int) ( $duration['minute'] ?? 0 ),
            'second' => (int) ( $duration['second'] ?? 0 ),
            'total_seconds' => (int) ( ( $duration['minute'] ?? 0 ) * 60 + ( $duration['second'] ?? 0 ) ),
        ];

        $thumb_id = (int) get_post_thumbnail_id( $lesson_id );
        $data['thumbnail'] = [
            'id'  => $thumb_id,
            'url' => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '',
        ];

        // 所属课程 ID
        $data['course_id'] = mcv_lms_get_course_id_by_lesson_id( $lesson_id );

        return $data;
    }

    /**
     * 清理课程章节树缓存
     */
    private function clear_course_cache( $lesson_id ){
        $course_id = mcv_lms_get_course_id_by_lesson_id( $lesson_id );
        if( $course_id ){
            mcv_lms_del_lessons_cache( $course_id );
        }
    }
}
