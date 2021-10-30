<?php
namespace MineCloudvod\RestApi;
use MineCloudvod\MineCloudVodAPI;

class AliyunVod{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    protected $base = 'aliyun/vod';
    private $_wpcvApi;

    public function __construct(){
        $this->_wpcvApi     = new MineCloudVodAPI();
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
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'hls_decrypt'],
                'permission_callback' => function(){return true;},
                'args'                => [
                    'CipherText' => [
                        'type' => 'string',
                    ],
                    'MtsHlsUriToken' => [
                        'type' => 'string',
                    ]
                ]
            ],
        ]);
        /**
         * get categories
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/categories', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getCategories'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => []
            ],
        ]);
        /**
         * create a category
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/createCategory', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'createCategory'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'name' => [
                        'type' => 'string',
                    ],
                ]
            ],
        ]);
        /**
         * search videos
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/videos', [
            [
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
                    ]
                ]
            ],
        ]);
        /**
         * delete video
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/delvideo', [
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'del_video'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'videoId' => [
                        'type' => 'string',
                    ]
                ]
            ],
        ]);
        /**
         * playauth
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/playauth', [
            [
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
            ],
        ]);

    }
    public function read_files_permissions_check(){
        return current_user_can('edit_posts');
    }

    public function getCategories(\WP_REST_Request $request){
        $req = array(
            'mode' => 'alivod'
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
        $token = new \MineCloudvod\Ability\Token(MINECLOUDVOD_SETTINGS['alivod']['token']);
        $result = $token->check_token($uriToken);
        if($result['code'] == '200'){
            $req = array(
                'Ciphertext' => $request['CipherText'],
                'mode' => 'alivod'
            );
            $result = $this->_wpcvApi->call('deplay', $req);

            if (is_wp_error($result)) {
                return $result;
            }
            if($result['status'] == 0){
                return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
            }
            $dkey64 = $result['data'];
            if($dkey = base64_decode($dkey64)){
                echo $dkey;
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
        if(is_array($videos->items) && !empty($videos->items)){
            $items = [];
            foreach ($videos->items as $key => $item) {
                $item->thumbnail = $item->coverURL;
                $item->updated_at = $item->creationTime;
                $item->created_at = $item->creationTime;
                $items[] = $item;
            }
            $videos->items = $items;
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
        $result = $vod->get_playinfo($request['vid'], $request['endpoint']);

        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['status']) && $result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }

}
