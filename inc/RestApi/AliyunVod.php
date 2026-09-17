<?php
namespace MineCloudvod\RestApi;

class AliyunVod{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    protected $base = 'aliyun/vod';
    private $_wpcvApi;

    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;
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
         * hls decrypt
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/decrypt', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'hls_decrypt'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'CipherText' => [
                        'type' => 'string',
                    ],
                    'MtsHlsUriToken' => [
                        'type' => 'string',
                    ]
                ]
        ]);
        /**
         * get categories
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/categories', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getCategories'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => []
        ]);
        /**
         * create a category
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/createCategory', [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'createCategory'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'name' => [
                        'type' => 'string',
                    ],
                ]
        ]);
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
                    'st' => [
                        'type' => 'string'
                    ],
                    'searchType' => [
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
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'videoId' => [
                        'type' => 'string',
                    ]
                ]
        ]);
        /**
         * playauth
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/playauth', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_playauth'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'vid' => [
                        'type' => 'string',
                    ],
                    'endpoint' => [
                        'type' => 'string'
                    ]
                ]
        ]);
        /**
         * playurl
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/playurl', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_playurl'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'vid' => [
                        'type' => 'string',
                    ],
                    'endpoint' => [
                        'type' => 'string'
                    ]
                ]
        ]);
        /**
         * 保存AccessKey
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/accesskey', [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'saveAccessKey'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'akid' => [
                        'type' => 'string',
                    ],
                    'aksecret' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * 下载snapshot
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/cover', [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'download_snapshot'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'pid' => [
                        'type' => 'integer',
                    ],
                    'cover' => [
                        'type' => 'string',
                    ],
                    'vid' => [
                        'type' => 'string',
                    ],
                    'title' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * 下载snapshot
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/update/cover', [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'update_cover'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'pid' => [
                        'type' => 'integer',
                    ],
                    'cid' => [
                        'type' => 'integer',
                    ],
                ]
        ]);

    }
    public function read_files_permissions_check(){
        return current_user_can('edit_posts');
    }
    /**
     * 更新cover
     */
    public function update_cover(\WP_REST_Request $request){
        $pid = is_numeric( $request['pid'] ) ? $request['pid'] : 0;
        $cid = is_numeric( $request['cid'] ) ? $request['cid'] : 0;
        if( $pid ){
            update_post_meta($pid, '_mcv_alivod_snapshot', $cid);
            wp_send_json_success();
        }
        wp_send_json_error();
    }
    /**
     * 下载snapshot
     */
    public function download_snapshot(\WP_REST_Request $request){
        $pid = is_numeric( $request['pid'] ) ? $request['pid'] : 0;
        $cover = sanitize_url( $request['cover'] );
        $title = sanitize_text_field( $request['title'] );
        $vid = sanitize_text_field( $request['vid'] );
        $desc = "vid-{$vid}-{$title}";

        if( $pid && $cover ){
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $_mcv_alivod_snapshot = get_post_meta($pid, '_mcv_alivod_snapshot', true);
            if( !$_mcv_alivod_snapshot ){
                $_mcv_alivod_snapshot = media_sideload_image($cover, $pid, $desc, 'id');
                if( is_numeric( $_mcv_alivod_snapshot ) ){
                    // 保存id到postmeta
                    update_post_meta($pid, '_mcv_alivod_snapshot', $_mcv_alivod_snapshot);
                }
            }
            else{
                $post_snapshot = get_post( $_mcv_alivod_snapshot );
                if( strpos( $post_snapshot->post_name, $vid ) === false ){
                    $_mcv_alivod_snapshot = media_sideload_image($cover, $pid, $desc, 'id');
                    if( is_numeric( $_mcv_alivod_snapshot ) ){
                        // 保存id到postmeta
                        update_post_meta($pid, '_mcv_alivod_snapshot', $_mcv_alivod_snapshot);
                    }
                }
            }
            // 返回图片src
            $image_src = wp_get_attachment_url( $_mcv_alivod_snapshot );
            return rest_ensure_response(['src'=>$image_src]);
        }
        wp_send_json_error();
    }
    /**
     * 保存AccessKey
     */
    public function saveAccessKey(\WP_REST_Request $request){
        $akid = sanitize_text_field( $request['akid'] );
        $aksecret = sanitize_text_field( $request['aksecret'] );

        $setting = MINECLOUDVOD_SETTINGS;
        $setting['alivod']['accessKeyID'] = $akid;
        $setting['alivod']['accessKeySecret'] = $aksecret;
        $setting['alivod']['endpoint'] = 'cn-beijing';
        $setting['alivod']['transcode'] = 'VOD_NO_TRANSCODE';
        $result = update_option('mcv_settings', $setting);
        if (is_wp_error($result)) {
            return $result;
        }
        return rest_ensure_response(['success' => true]);
    }

    public function getCategories(\WP_REST_Request $request){
        $cateId = sanitize_text_field( $request['cateId'] ?? 0 );
        $req = array(
            'mode' => 'alivod',
            'cateId' => $cateId
        );
        $result = $this->_wpcvApi->call('getcate', $req);

        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        $cates = $result['data'] ?? [];
        return rest_ensure_response($cates);
    }
    public function createCategory(\WP_REST_Request $request){
        $req = array(
            'mode' => 'alivod',
            'cateName' => $request['name'],
        );
        $result = $this->_wpcvApi->call('addcate', $req);

        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        $cates = $result['data'];
        return rest_ensure_response($cates);
    }

    public function hls_decrypt(\WP_REST_Request $request){
        $uriToken = $request['MtsHlsUriToken'];
        $token = new \MineCloudvod\Ability\Token(MINECLOUDVOD_SETTINGS['alivod']['token'], MINECLOUDVOD_SETTINGS['alivod']['tokenTime']??10);
        $result = $token->check_token($uriToken);
        if($result['code'] == '200'){

            // $videoId = $request['MediaId'];
            // $dir = 'alivod_decrypt';
            // $cache = mcv_get_file_cache($dir, $videoId, 360000);
            // if($cache){
            //     $result = unserialize($cache);
            //     $dkey64 = $result['data'];
            //     if($dkey = base64_decode($dkey64)){
            //         echo $dkey;
            //         exit;
            //     }
            // }

            $req = array(
                'Ciphertext' => $request['CipherText'],
                'mode' => 'alivod'
            );
            $result = $this->_wpcvApi->call('deplay', $req);

            if(!is_array($result)){
                $result = $this->_wpcvApi->call('deplay', $req);
            }
            if(!is_array($result)){
                $result = $this->_wpcvApi->call('deplay', $req);
            }
            if(!is_array($result)){
                $result = $this->_wpcvApi->call('deplay', $req);
            }
            if(!is_array($result)){
                $result = $this->_wpcvApi->call('deplay', $req);
            }
            if(!is_array($result)){
                $result = $this->_wpcvApi->call('deplay', $req);
            }
            if(!is_array($result)){
                $result = $this->_wpcvApi->call('deplay', $req);
            }
            if(!is_array($result)){
                $result = $this->_wpcvApi->call('deplay', $req);
            }
            if(!is_array($result)){
                $result = $this->_wpcvApi->call('deplay', $req);
            }
            // if(is_array($result)){
            //     mcv_set_file_cache($dir, $videoId, serialize($result));
            // }

            if (is_wp_error($result) || !is_array($result)) {
                return new \WP_Error('cant-trash', $result, ['status' => 500]);
            }
            if($result['status'] == 0){
                return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
            }
            $dkey64 = $result['data'];
            if($dkey_safe = base64_decode($dkey64)){
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 解密数据，输出原始内容
                echo $dkey_safe;
            }
        }
        else
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
    }

    /**
     * Fetch videos from aliyun vod
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function fetch_videos(\WP_REST_Request $request){
        $req = array(
            'pageNo' => (int) $request['page'],
            'pageSize' => (int)$request['items_per_page'],
            'mode' => 'alivod',
            'cateId'    => (int)$request['cateId'],
            'scrollToken'    => $request['st'],
            'keyword'    => $request['search'],
            'searchType'    => $request['searchType'],
        );
        $result = $this->_wpcvApi->call('search', $req);

        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        $videos = $result['data'];
        // prepare for response
        // var_dump(is_array($videos['items']) , !empty($videos['items']));
        if(is_array($videos['items']) && !empty($videos['items'])){
            $items = [];
            foreach ($videos['items'] as $key => $item) {
                $temp = $item;
                $temp['thumbnail'] = $item['coverURL'];
                $temp['updated_at'] = $item['creationTime'];
                $temp['created_at'] = $item['creationTime'];
                $temp['mediaType'] = $request['searchType'];
                if($request['searchType'] == 'audio'){
                    $temp['videoId'] = $item['audioId'];
                }
                $items[] = $temp;
            }
            $videos['items'] = $items;
        }

        return rest_ensure_response($videos);
    }

    public function del_video(\WP_REST_Request $request){
        $req = array(
            'videoId' => $request['videoId'],
            'mode' => 'alivod'
        );
        $resultArray = $this->_wpcvApi->call('delete', $req);

        if ($resultArray['status'] == 1) {
            return new \WP_REST_Response(true, 200);
        }

        if (is_wp_error($resultArray)) {
            return $resultArray;
        }

        return new \WP_Error('cant-trash', $resultArray['msg'], ['status' => 500]);
    }

    public function get_playauth(\WP_REST_Request $request){
        $vod = new \MineCloudvod\Aliyun\Vod();
        $result = $vod->get_playinfo($request['vid'], $request['endpoint'], !isset($request['cache']));

        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['status']) && $result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }
    public function get_playurl(\WP_REST_Request $request){
        $vod = new \MineCloudvod\Aliyun\Vod();
        $endpoint = $request['endpoint'];
        if(!$endpoint) $endpoint = MINECLOUDVOD_SETTINGS['alivod']['endpoint'];
        $result = $vod->get_playurl($request['vid'], $endpoint);

        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['status']) && $result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }

}
