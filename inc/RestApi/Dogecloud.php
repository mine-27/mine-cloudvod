<?php
namespace MineCloudvod\RestApi;

class Dogecloud{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    protected $base = 'doge/vod';
    private $_wpcvApi;

    public function __construct(){
        $this->register();
    }

    /**
     * Register controller
     *
     * @return void
     */
    public function register(){
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register presets routes
     *
     * @return void
     */
    public function register_routes(){
        /**
         * search videos
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/videos', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'fetch_videos'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'page' => [
                        'type' => 'integer',
                    ],
                    'search' => [
                        'type' => 'string'
                    ],
                    'items_per_page'  => [
                        'type' => 'integer',
                    ],
                    'order_by' => [
                        'type' => 'string'
                    ],
                    'cid' => [
                        'type' => 'string'
                    ]
                ]
        ]);
        /**
         * delete video
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/delvideo', [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'del_video'],
                'permission_callback' => [$this, 'is_admin_editor'],
                'args'                => [
                    'videoId' => [
                        'type' => 'integer',
                    ]
                ]
        ]);
        /**
         * upload sign
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/usign', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_usign'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'filename' => [
                        'type' => 'string',
                    ],
                ]
            ],
        ]);

        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/getvcode', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_vcode'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'fsize' => [
                        'type' => 'integer',
                    ],
                    'did' => [
                        'type' => 'string',
                    ],
                ]
            ],
        ]);
    }
    public function is_admin_editor(){
        $user = wp_get_current_user();
        $allowed_roles = array( 'editor', 'administrator' );
        if ( array_intersect( $allowed_roles, $user->roles ) ) {
            return true;
        }
        return false;
    }
    public function read_files_permissions_check(){
        return current_user_can('edit_posts');
    }
    /**
     * 删除视频云文件
     */
    public function del_video(\WP_REST_Request $request){
        $req = array(
            'vids' => $request['videoId']
        );
        $result = self::call('/console/video/delete.json', $req);
        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['code']) && $result['code'] != 200){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        wp_send_json_success();
    }
    /**
     * Fetch videos from tc vod
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function fetch_videos(\WP_REST_Request $request){
        $req = array(
            'page' => (int) ($request['page']??'1'),
            'page_size' => (int)$request['items_per_page'],
            'cid' => -1,
        );
        if(isset($request['search']) && $request['search']){
            $req['name_search'] = sanitize_text_field($request['search']);
        }
        $result = self::call('/console/video/list.json', $req);
        
        if($result['code'] != 200){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        $videos = $result['data'];
        // prepare for response
        if(is_array($videos["videos"]) && !empty($videos["videos"])){
            $items = [];
            foreach ($videos["videos"] as $key => $item) {
                $item["thumbnail"] = $item["thumb"];
                $item["updated_at"] = $item["etime"];
                $item["created_at"] = $item["ctime"];
                $item["videoId"] = $item["vcode"];
                $item["title"] = $item["name"];
                $item["size"] = $item["space"];
                $item["status"] = $item["status"] == 10 ? 'Normal' : $item["status"];
                $items[] = $item;
            }
            $videos["items"] = $items;
        }
        // var_dump($videos);
        return rest_ensure_response($videos);
    }

    public function get_usign(\WP_REST_Request $request){
        $filename = sanitize_text_field($request['filename']);
        $data = json_encode([
            'channel' => 'VOD_UPLOAD',
            'vodConfig' => [
                'filename' => $filename,
                'vn' => $filename
            ],
        ]);

        $result = self::call('/auth/tmp_token.json', $data);

        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['code']) && $result['code'] != 200){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        $info = $result['data']['VodUploadInfo'];
        $info['credentials'] = $result['data']['Credentials'];
        return rest_ensure_response($info);
    }

    public function get_vcode(\WP_REST_Request $request){
        $fsize = sanitize_text_field($request['fsize']);
        $did = sanitize_text_field($request['did']);
        $data_vid = [
            'fsize' => $fsize,
            'did' => $did,
        ];

        $result_vid = self::call('/callback/upload.json', $data_vid);

        if (is_wp_error($result_vid)) {
            return $result_vid;
        }
        if(isset($result_vid['code']) && $result_vid['code'] != 200){
            return new \WP_Error('cant-trash', $result_vid['msg'], ['status' => 500]);
        }

        $data = [
            'vid' => $result_vid['data']['vid'],
        ];

        $result = self::get_videoinfo($data);
        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['code']) && $result['code'] != 200){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response(['vcode'=>$result['data']['vcode']]);
    }
    
    public static function get_videoinfo($data){

        // $data = [
        //     'vid' => $result_vid['data']['vid'],
        // ];

        $result = self::call('/video/info.json', $data);
        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['code']) && $result['code'] != 200){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return $result;
    }
    

    public static function call($api, $data = '') {
        $ak = MINECLOUDVOD_SETTINGS['dogecloud']['sid'] ?? false;
        $sk = MINECLOUDVOD_SETTINGS['dogecloud']['skey'] ?? false;
        $param = array();
        $body = "";
        if($data) {
            $body = is_array($data) ? http_build_query($data) : $data;
            $param['method'] = 'POST';
            $param['body'] = $body;
        }
        $param['headers'] = array();
        if($ak) {
            $Authorization = 'TOKEN ' . $ak . ':' .hash_hmac('sha1', $api . "\n" . $body, $sk);
            $param['headers']['Authorization'] = $Authorization;
        }
        $response = wp_remote_get('https://api.dogecloud.com'.$api, $param);
        $ret = [];
        if( !is_wp_error($response) ){
            $ret = json_decode($response['body'], true);
        }
        return $ret;
    }
}
