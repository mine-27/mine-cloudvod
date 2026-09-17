<?php
namespace MineCloudvod\RestApi\LMS;

if ( ! defined( 'ABSPATH' ) )
    exit;

class Lesson extends Base{

    protected $base = 'lms/lesson';
    private $_mLesson;

    public function __construct(){
        $this->register();
        $this->_mLesson = new \MineCloudvod\Models\Lesson();
    }

    public function register(){
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(){
        /**
         * Get section's lesson by section id.
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/(?P<lesson_id>\d+)", [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'lesson_meta'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'lesson_id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ]
                ],
        ]);
        /**
         * create a section
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/save", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'section_lesson_save'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'section_id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ],
                    'lesson_id' => [
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
                    'image' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ],
                    'videoSource' => [
                        'type' => 'string',
                    ],
                    'videoUrl' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * 删除 Lesson
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/del/(?P<lesson_id>\d+)", [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'section_lesson_del'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'lesson_id' => [
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
                'callback'            => [$this, 'section_lesson_order'],
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
                    'section' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ],
                ]
        ]);
        /**
         * 批量导入
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/import", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'import_lessons'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'from' => [
                        'type' => 'string',
                    ],
                    'tag' => [
                        'type' => 'string',
                    ],
                    'sectionId' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ],
                    'videos[]' => [
                        'type' => 'string',
                    ]
                ]
        ]);
    }

    public function import_lessons(\WP_REST_Request $request){
        $from = sanitize_text_field($request['from']);
        $tag = sanitize_text_field($request['tag']);
        $section_id = $request['sectionId'];
        $videos = $request['videos'];
        $order_id    = mcv_lms_get_lesson_order_id( $section_id );
        $posts = [];
        switch($from){
            case 'videourl':
                $vi = 0;
                foreach($videos as $video){
                    if(!is_array($video)) continue;
                    $vi++;
                    $post_content = '';
                    switch($video[0]){
                        case 'direct':
                            $player = 'aliplayer';
                            if( isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['backend']['player'] ) && MINECLOUDVOD_SETTINGS['mcv_lms_course']['backend']['player'] == '2' ){
                                $player = 'dplayer';
                            }
                            $post_content = '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/'. $player .' {"source":"'.$video[1].'"} /--></div><!-- /wp:mine-cloudvod/block-container -->';
                            break;
                        default:
                            $post_content = '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/embed-video {"src":"'.$video[1].'","type":"'.$video[0].'"} /--></div><!-- /wp:mine-cloudvod/block-container -->';
                            break;
                    }
                    $ptitle = __( 'Lesson', 'mine-cloudvod' ) .  $vi;
                    if( count($video) > 2 ) $ptitle = $video[2];
                    elseif( $tag ){
                        $ptitle = $tag . '-' . $vi;
                    }
                    $post = array(
                        'post_title'    => $ptitle,
                        'post_content'  => $post_content,
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag),
                        'post_parent'  => $section_id,
                        'menu_order'   => $order_id*1 + $vi,
                    );
                    $posts[] = $post;
                }
                break;
            case 'alivod':
                $vi = 0;
                foreach($videos as $video){
                    $videoId = sanitize_text_field($video['videoId']);
                    $videoTitle = sanitize_text_field($video['title']);
                    if(!$videoId) continue;
                    $duration = sanitize_text_field($video['duration']);
                    $duration = intval( $duration );
                    $minute = floor( $duration / 60 );
                    $second = $duration % 60;
                    $vi++;
                    $post = array(
                        'post_title'    => $videoTitle??$tag . '-' . $vi,
                        'post_content'  => '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/aliyun-vod {"videoId":"'.$videoId.'"} /--></div><!-- /wp:mine-cloudvod/block-container -->',
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag),
                        'post_parent'  => $section_id,
                        'menu_order'   => $order_id*1 + $vi,
                        'meta_input'   => [
                            '_mcv_lesson_duration' => [
                                'minute' => $minute,
                                'second' => $second,
                            ],
                        ]
                    );
                    $posts[] = $post;
                }
                break;
            case 'tcvod':
                $vi = 0;
                foreach($videos as $video){
                    $videoId = sanitize_text_field($video['videoId']);
                    $cover = sanitize_text_field($video['CoverUrl']);
                    $videoTitle = sanitize_text_field($video['Name']);
                    if(!$videoId) continue;
                    $duration = sanitize_text_field($video['Duration']);
                    $duration = intval( $duration );
                    $minute = floor( $duration / 60 );
                    $second = $duration % 60;
                    $vi++;
                    $post = array(
                        'post_title'    => $videoTitle??$tag . '-' . $vi,
                        'post_content'  => '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/tc-vod {"videoId":"'.$videoId.'","cover":"'.$cover.'"} /--></div><!-- /wp:mine-cloudvod/block-container -->',
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag),
                        'post_parent'  => $section_id,
                        'menu_order'   => $order_id*1 + $vi,
                        'meta_input'   => [
                            '_mcv_lesson_duration' => [
                                'minute' => $minute,
                                'second' => $second,
                            ],
                        ]
                    );
                    $posts[] = $post;
                }
                break;
            case 'qiniukodo':
                $vi = 0;
                foreach($videos as $video){
                    $videoId = sanitize_text_field($video['videoId']);
                    $videoTitle = sanitize_text_field($video['title']);
                    if( !$videoTitle ) $videoTitle = $tag . '-' . $vi;
                    else{
                        $tmp = explode( '/', $videoTitle );
                        $videoTitle = $tmp[count( $tmp ) - 1];
                    }
                    if(!$videoId) continue;
                    $duration = 0; // sanitize_text_field($video['duration']);
                    $duration = intval( $duration );
                    $minute = floor( $duration / 60 );
                    $second = $duration % 60;
                    $vi++;
                    $post = array(
                        'post_title'    => $videoTitle,
                        'post_content'  => '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/qiniu {"key":"'.$videoId.'"} /--></div><!-- /wp:mine-cloudvod/block-container -->',
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag),
                        'post_parent'  => $section_id,
                        'menu_order'   => $order_id*1 + $vi,
                        'meta_input'   => [
                            '_mcv_lesson_duration' => [
                                'minute' => $minute,
                                'second' => $second,
                            ],
                        ]
                    );
                    $posts[] = $post;
                }
                break;
            case 'dogecloud':
                $vi = 0;
                foreach($videos as $video){
                    $videoId = sanitize_text_field($video['videoId']);
                    $videoTitle = sanitize_text_field($video['title']);
                    if(!$videoId) continue;
                    $duration = sanitize_text_field($video['duration']);
                    $uid = sanitize_text_field($video['uid']);
                    $duration = intval( $duration );
                    $minute = floor( $duration / 60 );
                    $second = $duration % 60;
                    $vi++;
                    $post = array(
                        'post_title'    => $videoTitle??$tag . '-' . $vi,
                        'post_content'  => '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/doge {"vcode":"'.$videoId.'","userId":'.$uid.'} /--></div><!-- /wp:mine-cloudvod/block-container -->',
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag),
                        'post_parent'  => $section_id,
                        'menu_order'   => $order_id*1 + $vi,
                        'meta_input'   => [
                            '_mcv_lesson_duration' => [
                                'minute' => $minute,
                                'second' => $second,
                            ],
                        ]
                    );
                    $posts[] = $post;
                }
                break;
        }
        if(count($posts) == 0){
            return new \WP_Error('cant-trash', __('no videos', 'mine-cloudvod'), ['status' => 500]);
        }
        $result = [];
        foreach($posts as $post){
            $pid = $this->_mLesson->create($post);
            $result['num']++;
        }

        return rest_ensure_response($result);
    }

    public function lesson_meta(\WP_REST_Request $request){
        $lesson_id   = $request['lesson_id'];

        $thumbnail_id = get_post_thumbnail_id($lesson_id);
        $thumbnail_url  = wp_get_attachment_image_url($thumbnail_id);

        $video = get_post_meta( $lesson_id, '_mcv_lms_video', true);

        return rest_ensure_response( [
            'thumbnail' => [
                'id'    => $thumbnail_id,
                'url'   => $thumbnail_url,
            ],
            'video'     => $video
        ] );
    }

    public function section_lesson(\WP_REST_Request $request){
        $section_id   = $request['section_id'];
        $lessons = get_posts( [
            'post_type'     => MINECLOUDVOD_LMS['lesson_post_type'],
            'post_parent'   => $section_id,
            'orderby'       => 'menu_order',
            'order'         => 'ASC',
            'numberposts'   => 999,
        ] );
        return rest_ensure_response( $lessons );
    }

    public function section_lesson_save(\WP_REST_Request $request){
        $section_id  = $request['section_id'];
        $lesson_id   = $request['lesson_id'];
        $name        = sanitize_text_field( $request['name'] );
        $desc        = wp_kses_post( $request['desc'] );
        $image       = sanitize_text_field( $request['image'] );
        $videoSource = sanitize_text_field( $request['videoSource'] );
        $videoUrl    = sanitize_text_field( $request['videoUrl'] );
        $order_id    = mcv_lms_get_lesson_order_id( $section_id, $lesson_id );

        $post_lesson = array(
            'post_type'    => MINECLOUDVOD_LMS['lesson_post_type'],
            'post_title'   => $name,
            'post_content' => $desc,
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_parent'  => $section_id,
            'menu_order'   => $order_id,
        );

        if( $lesson_id ){
            $post_lesson['ID'] = $lesson_id;
            if( !$image ){
                //删除thumbnail
                set_post_thumbnail( $lesson_id, 0);
            }
        }

        $post_lesson['meta_input']['_thumbnail_id'] = $image && is_numeric( $image ) ? $image : 0;
        $videoSource ? $post_lesson['meta_input']['_mcv_lms_video'] = [
            'video_source'  => $videoSource,
            'video_url'     => $videoUrl,
        ] : 0;

        $current_lesson_id = wp_insert_post( $post_lesson );

        if (is_wp_error($current_lesson_id)) {
            return $current_lesson_id;
        }
        
        $course_id = mcv_lms_get_course_id_by_lesson_id( $section_id );
        mcv_lms_del_lessons_cache( $course_id );
        
        return rest_ensure_response([
            'id'   => $current_lesson_id, 
            'name' => $name,
            'desc' => $desc,
        ]);
    }

    public function section_lesson_del(\WP_REST_Request $request){
        $lesson_id  = $request['lesson_id'];
        
        $delete = wp_delete_post( $lesson_id );

        if (is_wp_error($delete)) {
            return $delete;
        }
        
        $course_id = mcv_lms_get_course_id_by_lesson_id( $lesson_id );
        mcv_lms_del_lessons_cache( $course_id );

        return rest_ensure_response([
            'status'   => 1,
        ]);
    }

    public function section_lesson_order(\WP_REST_Request $request){
        global $wpdb;
        $lesson_ids  = $request['ids'];
        $section_id  = $request['section'];
        if( !get_post( $section_id ) ) wp_send_json_error();
        $menu_order = 1;
        foreach( $lesson_ids as $lesson_id ){
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 批量静默更新 menu_order 排序，避免触发 save_post hook
                $wpdb->posts,
                [ 'menu_order' => $menu_order, 'post_parent' => $section_id ],
                ['ID' => $lesson_id ]
            );
            $menu_order++;
        }
        
        wp_send_json_success();
    }
}
