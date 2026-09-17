<?php
namespace MineCloudvod\RestApi\Agent;

if ( ! defined( 'ABSPATH' ) )
    exit;

/**
 * 课程 CRUD 端点
 *
 * 路由前缀：mine-cloudvod/agent/v1/course
 *
 * 端点：
 *   POST   /course              创建课程
 *   GET    /course              批量查询（支持 ids / status / paged / per_page）
 *   GET    /course/{id}         获取单个课程
 *   PUT    /course/{id}         更新课程
 *   DELETE /course/{id}         删除课程（级联删除章节与课时）
 *
 * 鉴权：Application Passwords + current_user_can('edit_posts')
 * 数据模型：post_type=mcv_course，meta 为独立 key（_mcv_access_mode 等）
 */
class Course extends Base{

    protected $base = 'course';

    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(){

        $root = $this->root();

        /**
         * 创建课程
         * POST /course
         */
        register_rest_route( $root, '/' . $this->base, [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'create' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => $this->get_write_args(),
        ] );

        /**
         * 批量查询课程
         * GET /course
         */
        register_rest_route( $root, '/' . $this->base, [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'list_items' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'ids'      => [
                    'type'        => 'string',
                    'description' => '逗号分隔的课程 ID 列表',
                ],
                'status'    => [
                    'type'        => 'string',
                    'default'     => 'any',
                    'description' => 'publish / draft / any',
                ],
                'paged'     => [
                    'type'        => 'integer',
                    'default'     => 1,
                    'sanitize_callback' => 'absint',
                ],
                'per_page'  => [
                    'type'        => 'integer',
                    'default'     => 20,
                    'sanitize_callback' => 'absint',
                ],
                'search'    => [
                    'type'        => 'string',
                ],
            ],
        ] );

        /**
         * 获取单个课程
         * GET /course/{id}
         */
        register_rest_route( $root, '/' . $this->base . '/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'id' => [
                    'validate_callback' => function( $v ){ return is_numeric( $v ); },
                ],
            ],
        ] );

        /**
         * 更新课程
         * PUT /course/{id}
         */
        register_rest_route( $root, '/' . $this->base . '/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'update' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => $this->get_write_args(),
        ] );

        /**
         * 删除课程（级联删除章节与课时）
         * DELETE /course/{id}
         */
        register_rest_route( $root, '/' . $this->base . '/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'delete' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'id' => [
                    'validate_callback' => function( $v ){ return is_numeric( $v ); },
                ],
                'force' => [
                    'type'    => 'boolean',
                    'default' => true,
                    'description' => 'true 直接删除，false 移入回收站',
                ],
            ],
        ] );
    }

    /**
     * 写操作（创建/更新）的公共参数定义
     */
    private function get_write_args(){
        return [
            'title'          => [ 'type' => 'string', 'required' => false, 'description' => '课程标题（创建时必填）' ],
            'content'        => [ 'type' => 'string', 'description' => '课程描述（HTML）' ],
            'excerpt'        => [ 'type' => 'string', 'description' => '摘要' ],
            'status'         => [
                'type'    => 'string',
                'default' => 'draft',
                'enum'    => [ 'publish', 'draft', 'pending', 'private' ],
            ],
            'thumbnail'      => [ 'type' => 'integer', 'description' => '封面图 attachment ID' ],
            'access_mode'    => [
                'type'    => 'string',
                'default' => 'open',
                'enum'    => array_keys( MINECLOUDVOD_LMS['access_mode'] ),
                'description' => 'open=公开 / free=免费(需注册) / buynow=付费',
            ],
            'price'          => [ 'type' => 'string', 'description' => '课程价格（access_mode=buynow 时生效）' ],
            'period'         => [
                'type'    => 'string',
                'default' => 'forever',
                'enum'    => [ 'forever', 'custom' ],
                'description' => '有效期类型',
            ],
            'period_custom'  => [
                'type'    => 'integer',
                'description' => '有效期月数（period=custom 时生效，1/2/3/6/12/24/36/48/60）',
            ],
            'difficulty'     => [
                'type'    => 'string',
                'enum'    => array_keys( MINECLOUDVOD_LMS['course_difficulty'] ),
                'description' => '难度：1=初级 / 2=中级 / 3=高级',
            ],
            'virtual_number' => [ 'type' => 'integer', 'default' => 0, 'description' => '虚拟报名人数' ],
            'update_status'  => [ 'type' => 'string', 'description' => '课程更新状态文案' ],
            'category_ids'   => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ], 'description' => '分类 ID 列表（taxonomy: course-category）' ],
            'tag_ids'        => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ], 'description' => '标签 ID 列表（taxonomy: course-tag）' ],
        ];
    }

    /**
     * 创建课程
     */
    public function create( \WP_REST_Request $request ){
        $title = sanitize_text_field( $request['title'] );
        if( ! $title ){
            return $this->fail( __( 'title is required', 'mine-cloudvod' ), 400, 'missing_title' );
        }

        $postarr = [
            'post_type'    => MINECLOUDVOD_LMS['course_post_type'],
            'post_title'   => $title,
            'post_status'  => sanitize_text_field( $request['status'] ?? 'draft' ),
            'post_author'  => get_current_user_id(),
        ];
        if( isset( $request['content'] ) )  $postarr['post_content'] = wp_kses_post( $request['content'] );
        if( isset( $request['excerpt'] ) )  $postarr['post_excerpt'] = sanitize_text_field( $request['excerpt'] );

        $course_id = wp_insert_post( $postarr, true );
        if( is_wp_error( $course_id ) ){
            return $this->fail( $course_id->get_error_message(), 500, 'insert_failed' );
        }

        $this->sync_meta( $course_id, $request );
        $this->sync_terms( $course_id, $request );

        return $this->ok( $this->get_course_data( $course_id ), 201 );
    }

    /**
     * 批量查询
     */
    public function list_items( \WP_REST_Request $request ){
        $args = [
            'post_type'      => MINECLOUDVOD_LMS['course_post_type'],
            'post_status'    => sanitize_text_field( $request['status'] ?? 'any' ),
            'posts_per_page' => (int) $request['per_page'],
            'paged'          => (int) $request['paged'],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        if( $request['ids'] ){
            $args['post__in'] = array_filter( array_map( 'absint', explode( ',', $request['ids'] ) ) );
        }
        if( $request['search'] ){
            $args['s'] = sanitize_text_field( $request['search'] );
        }

        $query = new \WP_Query( $args );
        $list = [];
        foreach( $query->posts as $post ){
            $list[] = $this->get_course_data( $post->ID );
        }

        return $this->ok( [
            'list'       => $list,
            'total'      => (int) $query->found_posts,
            'paged'      => (int) $args['paged'],
            'per_page'   => (int) $args['posts_per_page'],
            'max_pages'  => (int) $query->max_num_pages,
        ] );
    }

    /**
     * 获取单个课程
     */
    public function get( \WP_REST_Request $request ){
        $course_id = (int) $request['id'];
        $post = get_post( $course_id );
        if( ! $post || $post->post_type !== MINECLOUDVOD_LMS['course_post_type'] ){
            return $this->fail( __( 'course not found', 'mine-cloudvod' ), 404, 'not_found' );
        }
        return $this->ok( $this->get_course_data( $course_id ) );
    }

    /**
     * 更新课程
     */
    public function update( \WP_REST_Request $request ){
        $course_id = (int) $request['id'];
        $post = get_post( $course_id );
        if( ! $post || $post->post_type !== MINECLOUDVOD_LMS['course_post_type'] ){
            return $this->fail( __( 'course not found', 'mine-cloudvod' ), 404, 'not_found' );
        }

        $postarr = [ 'ID' => $course_id ];
        if( isset( $request['title'] ) )   $postarr['post_title']   = sanitize_text_field( $request['title'] );
        if( isset( $request['content'] ) ) $postarr['post_content'] = wp_kses_post( $request['content'] );
        if( isset( $request['excerpt'] ) ) $postarr['post_excerpt'] = sanitize_text_field( $request['excerpt'] );
        if( isset( $request['status'] ) )  $postarr['post_status']  = sanitize_text_field( $request['status'] );

        $result = wp_update_post( $postarr, true );
        if( is_wp_error( $result ) ){
            return $this->fail( $result->get_error_message(), 500, 'update_failed' );
        }

        $this->sync_meta( $course_id, $request );
        $this->sync_terms( $course_id, $request );

        return $this->ok( $this->get_course_data( $course_id ) );
    }

    /**
     * 删除课程（级联删除章节 + 课时）
     */
    public function delete( \WP_REST_Request $request ){
        $course_id = (int) $request['id'];
        $force = (bool) ( $request['force'] ?? true );
        $post = get_post( $course_id );
        if( ! $post || $post->post_type !== MINECLOUDVOD_LMS['course_post_type'] ){
            return $this->fail( __( 'course not found', 'mine-cloudvod' ), 404, 'not_found' );
        }

        // 先级联删除所有章节与章节下的课时
        $sections = get_posts( [
            'post_type'      => 'section',
            'post_parent'    => $course_id,
            'numberposts'    => 999,
            'post_status'    => 'any',
        ] );
        foreach( $sections as $section ){
            $this->delete_section_recursive( $section->ID );
        }

        $result = wp_delete_post( $course_id, $force );
        if( ! $result ){
            return $this->fail( __( 'delete failed', 'mine-cloudvod' ), 500, 'delete_failed' );
        }

        mcv_lms_del_lessons_cache( $course_id );

        return $this->ok( [ 'id' => $course_id, 'deleted' => true ] );
    }

    /**
     * 递归删除章节及其课时（供 delete 与 Section::delete 复用）
     */
    public static function delete_section_recursive( $section_id ){
        $lessons = get_posts( [
            'post_type'     => [ MINECLOUDVOD_LMS['lesson_post_type'], 'section' ],
            'post_parent'   => $section_id,
            'numberposts'   => 999,
            'post_status'   => 'any',
        ] );
        foreach( $lessons as $lesson ){
            // 如果是子章节，继续递归
            if( $lesson->post_type === 'section' ){
                self::delete_section_recursive( $lesson->ID );
            } else {
                wp_delete_post( $lesson->ID, true );
            }
        }
        wp_delete_post( $section_id, true );
    }

    /**
     * 同步课程 meta（独立 key 模式，与 Metabox.php / functions-lms.php 一致）
     */
    private function sync_meta( $course_id, \WP_REST_Request $request ){
        // 封面
        if( isset( $request['thumbnail'] ) ){
            set_post_thumbnail( $course_id, (int) $request['thumbnail'] );
        }

        // 访问模式
        if( isset( $request['access_mode'] ) ){
            update_post_meta( $course_id, '_mcv_access_mode', sanitize_text_field( $request['access_mode'] ) );
        }
        // 价格（仅 buynow 时有意义，但写入不做强校验，由前端逻辑控制）
        if( isset( $request['price'] ) ){
            update_post_meta( $course_id, '_mcv_course_price', sanitize_text_field( $request['price'] ) );
        }
        // 有效期
        if( isset( $request['period'] ) ){
            update_post_meta( $course_id, '_mcv_course_period', sanitize_text_field( $request['period'] ) );
        }
        if( isset( $request['period_custom'] ) ){
            update_post_meta( $course_id, '_mcv_course_period_custom', absint( $request['period_custom'] ) );
        }
        // 难度
        if( isset( $request['difficulty'] ) ){
            update_post_meta( $course_id, '_mcv_course_difficulty', sanitize_text_field( $request['difficulty'] ) );
        }
        // 虚拟报名数
        if( isset( $request['virtual_number'] ) ){
            update_post_meta( $course_id, '_mcv_course_virtual_number', absint( $request['virtual_number'] ) );
        }
        // 更新状态文案
        if( isset( $request['update_status'] ) ){
            update_post_meta( $course_id, '_mcv_course_update_status', sanitize_text_field( $request['update_status'] ) );
        }
    }

    /**
     * 同步分类与标签
     */
    private function sync_terms( $course_id, \WP_REST_Request $request ){
        if( isset( $request['category_ids'] ) && is_array( $request['category_ids'] ) ){
            wp_set_object_terms( $course_id, array_map( 'absint', $request['category_ids'] ), 'course-category', false );
        }
        if( isset( $request['tag_ids'] ) && is_array( $request['tag_ids'] ) ){
            wp_set_object_terms( $course_id, array_map( 'absint', $request['tag_ids'] ), 'course-tag', false );
        }
    }

    /**
     * 组装课程完整数据（post + meta + terms + thumbnail）
     */
    private function get_course_data( $course_id ){
        $post = get_post( $course_id );
        $data = $this->post_to_array( $post );

        $thumb_id = (int) get_post_thumbnail_id( $course_id );
        $data['thumbnail'] = [
            'id'  => $thumb_id,
            'url' => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '',
        ];

        $data['access_mode']    = get_post_meta( $course_id, '_mcv_access_mode', true ) ?: 'open';
        $data['price']          = get_post_meta( $course_id, '_mcv_course_price', true );
        $data['period']         = get_post_meta( $course_id, '_mcv_course_period', true ) ?: 'forever';
        $data['period_custom']  = (int) get_post_meta( $course_id, '_mcv_course_period_custom', true );
        $data['difficulty']     = get_post_meta( $course_id, '_mcv_course_difficulty', true );
        $data['virtual_number'] = (int) get_post_meta( $course_id, '_mcv_course_virtual_number', true );
        $data['update_status']  = get_post_meta( $course_id, '_mcv_course_update_status', true );

        // 分类与标签
        $data['categories'] = $this->terms_to_array( wp_get_post_terms( $course_id, 'course-category' ) );
        $data['tags']       = $this->terms_to_array( wp_get_post_terms( $course_id, 'course-tag' ) );

        // 统计
        $data['lesson_count']   = (int) get_post_meta( $course_id, '_mcv_number_lessons', true );
        $data['enrolled_count'] = (int) mcv_lms_get_course_enrolled_number( $course_id );

        return $data;
    }

    /**
     * term 列表转精简数组
     */
    private function terms_to_array( $terms ){
        $out = [];
        if( is_array( $terms ) ){
            foreach( $terms as $term ){
                $out[] = [
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }
        return $out;
    }
}
