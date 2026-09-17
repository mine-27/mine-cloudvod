<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\RestApi\Member;

if ( ! defined( 'ABSPATH' ) )
    exit;

class Order extends Base{

    protected $base = 'member/order';

    public function __construct(){
        $this->register();
    }

    public function register(){
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(){
        /**
         * 创建订单
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/create", [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'create_order'],
                'permission_callback' => '__return_true',
                'args'                => [
					'course_id' => [
						'validate_callback' => function ($param) {
							return is_numeric($param);
						}
					],
                    'pay' => [
                        'type' => 'string',
                    ],
                    'amount' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * 查询订单状态
         */
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/checkQRStatus", [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'checkQRStatus'],
                'permission_callback' => [$this, 'create_order_permissions_check'],
                'args'                => [
					'order_id' => [
						'validate_callback' => function ($param) {
							return is_numeric($param);
						}
					],
                ]
            ],
        ]);
    }

    public function create_order_permissions_check(){
        return is_user_logged_in();
    }

    public function checkQRStatus(\WP_REST_Request $request){
        $order_id = $request['order_id'];
        if( is_numeric( $order_id ) ){
            $_mcv_order_status = get_post_meta( $order_id, '_mcv_order_status', true );
            $ret = [
                'status' => $_mcv_order_status,
            ];
            if( $_mcv_order_status == 'paying' ){
                $_mcv_order_buyer_logon_id = get_post_meta( $order_id, '_mcv_order_buyer_logon_id', true );
                $ret['user'] = $_mcv_order_buyer_logon_id;
            }
            if( $_mcv_order_status == 'payed' ){
                $_mcv_order_items = get_post_meta( $order_id, '_mcv_order_items', true );
                $ret['url'] = home_url();
                if( isset( $_mcv_order_items[0] ) ){
                    if( is_array( $_mcv_order_items[0] ) ){
                        $ret['url'] = get_the_permalink( $_mcv_order_items[0][0] );
                    }
                    elseif( is_numeric( $_mcv_order_items[0] ) ){
                        $ret['url'] = get_the_permalink( $_mcv_order_items[0] );
                    }
                }
            }
            
            return rest_ensure_response( $ret );
        }
        return rest_ensure_response( ['status' => 'pending'] );
    }

    public function create_order(\WP_REST_Request $request){
        if( !is_user_logged_in() ){
            return new \WP_Error('nologin', __( 'Login first, please.', 'mine-cloudvod' ), ['status' => 403]);
        }
        $orderid  = sanitize_text_field( $request['orderid'] );
        if( !is_numeric( $orderid ) ){
            return new \WP_Error('cant-trash', 'error001', ['status' => 500]);
        }
        $order = get_post( $orderid );
        if( !$order ){
            return new \WP_Error('cant-trash', __( 'Order no exists.', 'mine-cloudvod' ), ['status' => 403]);
        }
        $item_id = 0;
        $items = get_post_meta( $order->ID, '_mcv_order_items', true );
        if(is_numeric($items[0])){
            $item_id = $items[0];
        }
        elseif(is_array( $items[0] )){
            $item_id = $items[0][0];
        }
        $order_price = mcv_lms_get_order_last_amount( $orderid );

        $order_title = $order->post_title;

        $payment    = sanitize_text_field( $request['pay'] );

        $cur_payment = MINECLOUDVOD_SETTINGS['mcv_payment'][$payment] ?? false;
        if( !$cur_payment ){
            return new \WP_Error('cant-trash', 'error002', ['status' => 500]);
        }

        $payment_class = new $cur_payment['class'];

        $result = $payment_class->handlePayment($order_price, $orderid, $order_title, $item_id, $request);

        $result['scripts'] = $payment_class->handleScripts( $request );
        $result = apply_filters( 'mcv_order_create_result', $result, $orderid );
        
        return rest_ensure_response([
            'id' => $orderid,
            'amount'=>$order_price,
        ]+$result);
    }
}
