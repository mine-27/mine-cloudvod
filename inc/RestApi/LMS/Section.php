<?php
namespace MineCloudvod\RestApi\LMS;

if ( ! defined( 'ABSPATH' ) )
    exit;

class Section extends Base{

    protected $base = 'lms/section';

    public function __construct(){
        $this->register();
    }

    public function register(){
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(){
        /**
         * Get course's sections by course id.
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/(?P<course_id>\d+)", [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'course_section'],
                'permission_callback' => '__return_true',
                'args'                => [
					'course_id' => [
						'validate_callback' => function ($param) {
							return is_numeric($param);
						}
					],
                    'from' =>[
                        'type' => 'string',
                    ],
                ],
        ]);
        /**
         * create a section
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/save", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'course_section_save'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
					'course_id' => [
						'validate_callback' => function ($param) {
							return is_numeric($param);
						}
					],
					'section_id' => [
						'validate_callback' => function ($param) {
							return is_numeric($param);
						}
					],
                    'name' => [
                        'type' => 'string',
                    ],
                    'desc' => [
                        'type' => 'string',
                    ],
                    'price' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * 删除Section
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/del/(?P<section_id>\d+)", [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'course_section_del'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
					'section_id' => [
						'validate_callback' => function ($param) {
							return is_numeric($param);
						}
					]
                ],
        ]);
        /**
         * 排序
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/order", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'course_section_order'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
					'ids' => [
						'validate_callback' => function ($param) {
							if( !is_array($param) ) return false;
                            foreach( $param as $para ){
                                if( !is_numeric( $para ) ) return false;
                            }
                            return true;
						}
					],
                ]
        ]);
    }


    public function course_section(\WP_REST_Request $request){
        $course_id   = $request['course_id'];
        $courses_lessons = mcv_lms_get_courses_lessons( $course_id );
        $from   = $request['from']??'';
        if( !$from ){
            return rest_ensure_response( $courses_lessons );
        }
        elseif( $from == 'user' ){
            $lists = [];
            $lessonCount = 0;
            if( is_array( $courses_lessons ) ){
                foreach($courses_lessons as $section){
                    $section_enrolled = mcv_lms_is_enrolled( $section['ID'] );
                    $sprogress = mcv_lms_user_course_progress( get_current_user_id(), $section['ID'] );
                    $list = [
                        'id'    => $section['ID'],
                        'title' => $section['post_title'],
                        'enrolled' => $section_enrolled,
                        'progress' => $sprogress
                    ];
                    $lessonCount += count($section['Lessons']);
                    foreach($section['Lessons'] as $lesson){
                        $attrs = get_post_meta($lesson->ID, '_mcv_lms_lesson_attrs', true);
                        if( !is_array($attrs) && is_string( $attrs ) ) $attrs = unserialize( $attrs );
                        if(!$attrs) $attrs = [];
                        if( !isset( $attrs['duration'] ) ){
                            $_mcv_lesson_duration = get_post_meta($lesson->ID, '_mcv_lesson_duration', true);
                            $attrs['duration'] = $_mcv_lesson_duration;
                        }
                        $lesson_enrolled = mcv_lms_is_enrolled( $lesson->ID );
            
                        $url = get_the_permalink($lesson->ID);
                        $lprogress = mcv_lms_user_course_progress( get_current_user_id(), $lesson );
                        $list['lessons'][] = [
                            'id' => $lesson->ID,
                            'title' => $lesson->post_title,
                            'url'   => $url,
                            'attrs' => $attrs,
                            'lesson_type' => get_post_meta($lesson->ID, '_lesson_type', true),
                            'enrolled' => $lesson_enrolled,
                            'progress' => $lprogress
                        ];
                    }
                    $lists[] = $list;
                }
            }
            return rest_ensure_response( $lists );
        }
    }

    public function course_section_save(\WP_REST_Request $request){
        $course_id   = $request['course_id'];
        $section_id  = $request['section_id'];
        $name        = sanitize_text_field( $request['name'] );
        $desc        = wp_kses_post( $request['desc'] );
        $price       = sanitize_text_field( $request['price'] );
        $isc         = sanitize_text_field( $request['isc'] );
        $order_id    = mcv_lms_get_section_order_id( $course_id, $section_id );

		$post_section = array(
			'post_type'    => 'section',
			'post_title'   => $name,
			'post_content' => $desc,
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_parent'  => $course_id,
			'menu_order'   => $order_id,
		);
        if( $price ){
            $post_section['meta_input']['_mcv_section_price'] = $price;
        }
        $post_section['meta_input']['_mcv_is_chapter'] = $isc;
        $section_id ? $post_section['ID'] = $section_id : 0;
        $current_section_id = wp_insert_post( $post_section );

        if (is_wp_error($current_section_id)) {
            return $current_section_id;
        }
        
        mcv_lms_del_lessons_cache( $course_id );
        return rest_ensure_response([
            'id'   => $current_section_id, 
            'name' => $name,
            'desc' => $desc,
        ]);
    }

    public function course_section_del(\WP_REST_Request $request){
        $section_id  = $request['section_id'];
        
        $course_id = mcv_lms_get_course_id_by_lesson_id( $section_id );
        $delete = wp_delete_post( $section_id );
        $lessons = get_posts( [
            'post_type'     => ['section', MINECLOUDVOD_LMS['lesson_post_type']],
            'post_parent'   => $section_id,
            'orderby'       => 'menu_order',
            'order'         => 'ASC',
            'numberposts'   => 5000,
        ] );
        if( is_array( $lessons ) && count( $lessons ) > 0){
            foreach( $lessons as $lesson ){
                wp_delete_post( $lesson->ID );
            }
        }
        if (is_wp_error($delete)) {
            return $delete;
        }
        
        mcv_lms_del_lessons_cache( $course_id );

        return rest_ensure_response([
            'status'   => 1,
        ]);
    }

    public function course_section_order(\WP_REST_Request $request){
        global $wpdb;
        $section_ids  = $request['ids'];
        
        $menu_order = 1;
        foreach( $section_ids as $section_id ){
            $section_id = (int) $section_id;
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 批量静默更新 menu_order 排序，避免触发 save_post hook
                $wpdb->posts,
                [ 'menu_order' => $menu_order ],
                ['ID' => $section_id ]
            );
            // 关键：直改 wp_posts 表绕过了 WP 对象缓存，必须手动失效，
            // 否则打包端 get_children / get_post 会命中旧 menu_order 缓存
            clean_post_cache( $section_id );
            $menu_order++;
        }
        
        wp_send_json_success();
    }
}
