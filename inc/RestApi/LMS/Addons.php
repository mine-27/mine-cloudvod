<?php
namespace MineCloudvod\RestApi\LMS;

if ( ! defined( 'ABSPATH' ) )
    exit;

class Addons extends Base{

    protected $base = 'addons';

    private $_wpcvApi;
    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;
        $this->register();
    }

    public function register(){
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(){

        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/active", [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'addons_active'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
					'addons' => [
                        'type' => 'string',
                    ],
                    'status' => [
                        'type' => 'boolean',
                    ]
                ],
            ],
        ]);
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/infos", [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'addons_infos'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => []
            ],
        ]);
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/buy", [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'addons_buy'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
					'addons' => [
                        'type' => 'string',
                    ],
                    'price' => [
                        'type' => 'string',
                    ],
                    'met' => [
                        'type' => 'string',
                    ]
                ]
            ],
        ]);
        register_rest_route("{$this->namespace}/{$this->version}", "/".$this->base."/get", [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'addons_get'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
					'addons' => [
                        'type' => 'string',
                    ],
                ]
            ],
        ]);
    }

    public function addons_active(\WP_REST_Request $request){
        $addons_id   = sanitize_text_field( $request['addons'] );
        $status = $request['status'];
        $result = false;
        global $mcv_classes;
        if( $status ){
            $result = $mcv_classes->Addons->active_addons( $addons_id );
            if($result) mcv_addons_update( $addons_id );
            $result = true;
        }
        else{
            $result = $mcv_classes->Addons->deactive_addons( $addons_id );
        }
        return rest_ensure_response( [ 'result'=>$result ] );
    }

    public function addons_infos(\WP_REST_Request $request){
        $data   = array();
        $addons = $this->_wpcvApi->call('addons', $data);
        $infos  = array(
            'et'        => '',
            'ets'       => 0,
            'addons'    => array(),
            'level'     => '',
            'bts'       => 0,   // 基础版订阅到期时间戳
            'pts'       => 0,   // 专业版订阅到期时间戳
            'avail'     => array(),
            'mini_time' => 0,   // 微信小程序买断服务到期时间戳
            'prices'    => array(),
        );
        if(isset($addons['data']['et'])){
            $setting = MINECLOUDVOD_SETTINGS;
            $setting['endtime'] = $addons['data']['et'];
            // 缓存档位信息，供后台续费提醒（notice_mcv_endtime）使用
            $setting['level']   = isset( $addons['data']['level'] ) ? sanitize_key( $addons['data']['level'] ) : '';
            $setting['bts']     = isset( $addons['data']['bts'] ) ? absint( $addons['data']['bts'] ) : 0;
            $setting['pts']     = isset( $addons['data']['pts'] ) ? absint( $addons['data']['pts'] ) : 0;
            update_option('mcv_settings', $setting);

            $srv            = $addons['data'];
            $infos['et']    = $srv['et'];
            $infos['ets']   = strtotime( $srv['et'] );
            $infos['addons']= ( isset( $srv['addons'] ) && is_array( $srv['addons'] ) ) ? array_values( $srv['addons'] ) : array();
            // 当前订阅等级：free / basic / pro，服务端未下发时为空
            $infos['level'] = isset( $srv['level'] ) ? sanitize_key( $srv['level'] ) : '';
            // 各档位订阅到期时间，服务端未下发时保持 0，前端回退到主授权到期时间
            $infos['bts']   = isset( $srv['bts'] ) ? absint( $srv['bts'] ) : 0;
            $infos['pts']   = isset( $srv['pts'] ) ? absint( $srv['pts'] ) : 0;
            $infos['avail'] = ( isset( $srv['avail'] ) && is_array( $srv['avail'] ) ) ? array_values( $srv['avail'] ) : array();
            // 微信小程序买断服务到期时间戳，服务端未下发时保持 0
            $infos['mini_time'] = isset( $srv['mini_time'] ) ? absint( $srv['mini_time'] ) : 0;
            // 套餐价格，由服务端下发
            $infos['prices']= $this->normalize_prices( $srv );
        }
        return rest_ensure_response( $infos );
    }

    /**
     * 归一化服务端下发的套餐价格
     * 兼容三种结构：
     *   prices: ['basic' => '199', 'pro' => ['price'=>'399','oprice'=>'699','period'=>'year']]
     *   price : 同上（数组形式）
     *   basic_price / pro_price
     *
     * @param array $data 服务端 addons 接口返回的 data
     * @return array
     */
    private function normalize_prices( $data ) {
        $raw = array();
        if ( isset( $data['prices'] ) && is_array( $data['prices'] ) ) {
            $raw = $data['prices'];
        }
        elseif ( isset( $data['price'] ) && is_array( $data['price'] ) ) {
            $raw = $data['price'];
        }

        $prices = array();
        foreach ( array( 'free', 'basic', 'pro' ) as $tier ) {
            $value = null;
            if ( isset( $raw[ $tier ] ) ) {
                $value = $raw[ $tier ];
            }
            elseif ( isset( $data[ $tier . '_price' ] ) ) {
                $value = $data[ $tier . '_price' ];
            }

            if ( is_array( $value ) ) {
                $prices[ $tier ] = array(
                    'price'  => isset( $value['price'] )  ? (string) $value['price']  : '',
                    'oprice' => isset( $value['oprice'] ) ? (string) $value['oprice'] : '',
                    'period' => isset( $value['period'] ) ? sanitize_key( $value['period'] ) : '',
                    'url'    => isset( $value['url'] )    ? esc_url_raw( $value['url'] ) : '',
                );
            }
            elseif ( null !== $value && '' !== $value && ! is_array( $value ) ) {
                $prices[ $tier ] = array(
                    'price'  => (string) $value,
                    'oprice' => '',
                    'period' => '',
                    'url'    => '',
                );
            }
        }

        return apply_filters( 'mcv_addons_prices', $prices, $data );
    }

    public function addons_buy(\WP_REST_Request $request){
        $tier   = sanitize_key( (string) $request['addons'] );
        $price  = sanitize_text_field( (string) $request['price'] );
        $met    = sanitize_key( (string) $request['met'] );

        // 只允许订阅基础版/专业版，价格以服务端为准
        if ( ! in_array( $tier, array( 'basic', 'pro' ), true ) ) {
            return rest_ensure_response( array(
                'status' => '0',
                'msg'    => __( 'Invalid subscription plan.', 'mine-cloudvod' ),
            ) );
        }
        if ( ! in_array( $met, array( 'alipay', 'wxpay' ), true ) ) {
            $met = 'wxpay';
        }

        $data = array('addons' => $tier, 'price' => $price, 'met' => $met);
        $buyaddons = $this->_wpcvApi->call('buyaddons', $data);

        return rest_ensure_response( $buyaddons );
    }

    public function addons_get(\WP_REST_Request $request){
        $addons_id   = sanitize_text_field( $request['addons'] );

        $data = array('addons' => $addons_id);
        $buyaddons = $this->_wpcvApi->call('getaddons', $data);

        return rest_ensure_response( $buyaddons );
    }

    public function course_section_del(\WP_REST_Request $request){
        $section_id  = $request['section_id'];
        
        $delete = wp_delete_post( $section_id );
        $lessons = get_posts( [
            'post_type'     => MINECLOUDVOD_LMS['lesson_post_type'],
            'post_parent'   => $section_id,
            'orderby'       => 'menu_order',
            'order'         => 'ASC',
            'numberposts'   => 500,
        ] );
        if( is_array( $lessons ) && count( $lessons ) > 0){
            foreach( $lessons as $lesson ){
                wp_delete_post( $lesson->ID );
            }
        }
        if (is_wp_error($delete)) {
            return $delete;
        }
        
        return rest_ensure_response([
            'status'   => 1,
        ]);
    }

    public function course_section_order(\WP_REST_Request $request){
        global $wpdb;
        $section_ids  = $request['ids'];
        
        $menu_order = 1;
        foreach( $section_ids as $section_id ){
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 批量静默更新 menu_order 排序，避免触发 save_post hook
                $wpdb->posts,
                [ 'menu_order' => $menu_order ],
                ['ID' => $section_id ]
            );
            $menu_order++;
        }
        
        wp_send_json_success();
    }
}
