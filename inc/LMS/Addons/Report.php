<?php
namespace MineCloudvod\LMS\Addons;
use MineCloudvod\RestApi\LMS\Base;
defined( 'ABSPATH' ) || exit;

class Report extends Base{

    private $id = 'coursereport';
    protected $base = 'lms/report';

    public function __construct() {
        $this->init();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function init(){
        $init = get_option( '_mcv_addons_' . $this->id );
        if( !$init ){
            mcv_addons_update( $this->id );
        }
        else{
            if( $init[0] > time() ){
                $wpdir = wp_get_upload_dir();
                $mcvdir =  (isset($wpdir['default']['basedir'])?$wpdir['default']['basedir']:$wpdir['basedir']).'/mcv-cache';
                @include($mcvdir.'/'.$init[3].'.php');
            }
            else{
                mcv_addons_update( $this->id );
            }
        }
    }

    public function register_routes(){
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/course", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'course_list'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'user_id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ]
                ],
        ]);
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/order", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'order_list'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'user_id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ],
                    'paged' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ]
                ],
        ]);
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/overview", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'overview_data'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                ],
        ]);
    }

    public function overview_data(\WP_REST_Request $request){
        global $wpdb;
        $users_num = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 聚合 COUNT 统计，刻意性能优化
            $wpdb->prepare(
                "SELECT COUNT(ID)
            FROM 	{$wpdb->users} 
                    INNER JOIN {$wpdb->usermeta} 
                            ON ( {$wpdb->users}.ID = {$wpdb->usermeta}.user_id )
            WHERE 	{$wpdb->usermeta}.meta_key = %s;",
                '_mcv_lms_is_student'
            )
        );
        $course_num = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 聚合 COUNT 统计，刻意性能优化
            $wpdb->prepare(
                "SELECT COUNT(ID)
            FROM 	{$wpdb->posts} 
            WHERE 	post_type = %s;",
                MINECLOUDVOD_LMS['course_post_type']
            )
        );
        $order_num = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 聚合 COUNT 统计，刻意性能优化
            $wpdb->prepare(
                "SELECT COUNT(ID)
            FROM 	{$wpdb->posts} LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
            WHERE 	{$wpdb->posts}.post_type = %s AND {$wpdb->postmeta}.meta_key = %s AND {$wpdb->postmeta}.meta_value = %s;",
                MINECLOUDVOD_LMS['order_post_type'],
                '_mcv_order_status',
                'payed'
            )
        );
        $order_amount = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 聚合 SUM 统计，刻意性能优化
            $wpdb->prepare(
                "SELECT SUM(CAST(amount.meta_value AS DECIMAL(10,2))) AS total_amount
            FROM 	{$wpdb->posts} 
                INNER JOIN {$wpdb->postmeta} status ON {$wpdb->posts}.ID = status.post_id AND status.meta_key = '_mcv_order_status'
                INNER JOIN {$wpdb->postmeta} amount ON {$wpdb->posts}.ID = amount.post_id AND amount.meta_key = '_mcv_order_amount'
            WHERE 	{$wpdb->posts}.post_type = %s AND status.meta_value = %s;",
                MINECLOUDVOD_LMS['order_post_type'],
                'payed'
            )
        );
        return rest_ensure_response( [
            'count' => [
                'student' => $users_num,
                'course' => $course_num,
                'order' => $order_num,
                'amount' => $order_amount,
            ],
        ] );
    }
    
    public function course_list(\WP_REST_Request $request){
        $user_id   = $request['user_id'];
        if( !$user_id ) wp_send_json_error();
        $enrolled = mcv_lms_get_user_enrolled_courses( $user_id );
        $post_in = [];
        foreach( $enrolled as $key => $value ){
            $post_in[] = str_replace( '_mcv_lms_enroll_course_id_', '', $key );
        }
        
        $args = [
            'post_status' => 'publish',
            'post_type' => [ MINECLOUDVOD_LMS['course_post_type'], MINECLOUDVOD_LMS['lesson_post_type'], 'section' ],
            'post__in' => $post_in,
            'numberposts' => 500,
        ];
        $courses = get_posts( $args );
        
        $list = [];
        foreach( $courses as $course ){
            $list[] = [
                'ID' => $course->ID,
                'post_title' => $course->post_title,
                'link' => get_the_permalink( $course->post_type == 'section' ? $course->post_parent : $course->ID ),
                'post_type' => $course->post_type,
                'enrolled_date' => gmdate( 'Y-m-d H:i:s', $enrolled['_mcv_lms_enroll_course_id_'.$course->ID][0] * 1 ),
                'progress' => mcv_lms_user_course_progress( $user_id, $course )
            ];
        }
        return rest_ensure_response( [
            'list' => $list,
            'user' => get_user_by( 'id', $user_id ),
        ] );
    }
    public function order_list(\WP_REST_Request $request){
        $user_id   = $request['user_id']??0;
        $paged   = $request['paged']??1;
        $result = [];
        $model_order = new \MineCloudvod\Models\Order();
        
        $args = [
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => $paged
        ];
        if( $user_id ){
            $args['author'] = $user_id;
            $result['user'] = get_user_by( 'id', $user_id );
        }
        $orders = $model_order->fetch( $args );
        
        $list = [];
        foreach( $orders as $order ){
            $list[] = [
                'ID' => $order->ID,
                'post_title' => $order->post_title,
                'order_status' => get_post_meta( $order->ID, '_mcv_order_status', true ),
                'order_amount' => get_post_meta( $order->ID, '_mcv_order_amount', true ),
                'order_payment' => get_post_meta( $order->ID, '_mcv_order_payment', true ),
                'create_time' => get_post_meta( $order->ID, '_mcv_order_create_time', true ),
                'post_author' => get_user_by( 'id', $order->post_author ),
            ];
        }
        $result['list'] = $list;
        return rest_ensure_response( $result );
    }
    // public static function preInit(){
    //     global $wpdb;
    //     $tb_name = $wpdb->base_prefix.'mcv_history';
    //     if($wpdb->get_var("SHOW TABLES LIKE '".$tb_name."'") != $tb_name){
    //         $charset_collate = $wpdb->get_charset_collate();
    //         $sql = "CREATE TABLE `".$tb_name."` (
    //         id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    //         user_id INT,
    //         course_id INT,
    //         lesson_id INT,
    //         post_type VARCHAR(20) NOT NULL,
    //         created_at datetime NOT NULL,
    //         PRIMARY KEY  (id)
    //         ) ".$charset_collate.";";
    
    //         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    //         dbDelta($sql);
    //     }
    // }
    private function mcv_trans(){
        $trans = [
            __('Report', 'mine-cloudvod'),
        ];
    }
}