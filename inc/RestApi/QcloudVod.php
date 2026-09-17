<?php
namespace MineCloudvod\RestApi;

class QcloudVod{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    protected $base = 'qcloud/vod';
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
                    'parentId' => [
                        'type' => 'integer',
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
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'videoId' => [
                        'type' => 'string',
                    ]
                ]
        ]);
        /**
         * psign
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/psign', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_psign'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'appId' => [
                        'type' => 'string',
                    ],
                    'fileId' => [
                        'type' => 'string'
                    ]
                ]
        ]);
        /**
         * upload sign
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/usign', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_usign'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'cid' => [
                        'type' => 'integer',
                    ],
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
                    'appid' => [
                        'type' => 'integer',
                    ],
                ]
        ]);

    }
    public function read_files_permissions_check(){
        return current_user_can('edit_posts');
    }
    /**
     * 保存AccessKey
     */
    public function saveAccessKey(\WP_REST_Request $request){
        $akid = sanitize_text_field( $request['akid'] );
        $aksecret = sanitize_text_field( $request['aksecret'] );
        $appid = sanitize_text_field( $request['appid'] );

        $setting = MINECLOUDVOD_SETTINGS;
        $setting['tcvod']['sid'] = $akid;
        $setting['tcvod']['skey'] = $aksecret;
        $setting['tcvod']['appid'] = $appid;
        $setting['tcvod']['region'] = 'ap-beijing';
        $setting['tcvod']['transcode'] = 'SimpleAesEncryptPreset';
        $setting['tcvod']['plyrconfig'] = 'default';
        $result = update_option('mcv_settings', $setting);
        if (is_wp_error($result)) {
            return $result;
        }
        return rest_ensure_response(['success' => true]);
    }

    public function getCategories(\WP_REST_Request $request){
        $req = array(
            'mode' => 'tcvod'
        );
        $result = $this->_wpcvApi->call('getcate', $req);

        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        $cates = $result['data'];
        return rest_ensure_response($cates);
    }
    public function createCategory(\WP_REST_Request $request){
        $name = sanitize_text_field($request['name']??'');
        $pid = sanitize_text_field($request['parentId']??'-1');
        $req = array(
            'mode' => 'tcvod',
            'className' => $name,
            'parentId' => $pid,
        );
        $result = $this->_wpcvApi->call('addcate', $req);
        
        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        $cates = [
            "ClassId" => $result['data'],
            "ParentId" => $pid,
            "ClassName" => $name,
        ];
        return rest_ensure_response($cates);
    }
    /**
     * Fetch videos from tc vod
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function fetch_videos(\WP_REST_Request $request){
        $req = array(
            'pageNo' => (int) $request['page'],
            'pageSize' => (int)$request['items_per_page'],
            'classId' => (int)$request['cid'],
            'search' => sanitize_text_field( $request['search'] ),
            'mode' => 'tcvod'
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
        if(is_array($videos["items"]) && !empty($videos["items"])){
            $items = [];
            foreach ($videos["items"] as $key => $item) {
                $item["thumbnail"] = $item["CoverUrl"];
                $item["updated_at"] = $item["UpdateTime"];
                $item["created_at"] = $item["CreateTime"];
                $item["videoId"] = $item["Vid"];
                $items[] = $item;
            }
            $videos["items"] = $items;
        }

        return rest_ensure_response($videos);
    }

    public function del_video(\WP_REST_Request $request){
        $req = array(
            'fileId' => $request['videoId'],
            'appId'  => MINECLOUDVOD_SETTINGS['tcvod']['appid'],
            'mode' => 'tcvod'
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

    public function get_psign(\WP_REST_Request $request){
        
        $pcfg   = MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig']??'';
        global $ActiveAddons;
        $psign = $ActiveAddons['qcloud']->Tcvod->mcv_generate_psign(0, $request['appId'], $request['fileId'], $pcfg);
        return rest_ensure_response(['psign'=>$psign['psign']]);
    }

    public function get_usign(\WP_REST_Request $request){
        $data = array(
            'mode' => 'tcvod',
            'classId' => (int)$request['cid'],
        );
        $touwei = get_tcvod_piantouwei();
        if(is_array($touwei)){
            $data['touwei'] = $touwei;
        }
        $result = $this->_wpcvApi->call('usign', $data);

        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['status']) && $result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }
}
