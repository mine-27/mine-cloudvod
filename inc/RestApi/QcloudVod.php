<?php
namespace MineCloudvod\RestApi;
use MineCloudvod\MineCloudVodAPI;

class QcloudVod{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    protected $base = 'qcloud/vod';
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
         * psign
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/psign', [
            [
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
            ],
        ]);
        /**
         * upload sign
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/usign', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_usign'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => []
            ],
        ]);

    }
    public function read_files_permissions_check(){
        return current_user_can('edit_posts');
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
        $data = array(
            'appId'  => $request['appId'],
            'fileId' => $request['fileId'],
            'pcfg'   => MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig'],
            'mode'   => 'tcvod'
        );
        $result = $this->_wpcvApi->call('psign', $data);

        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['status']) && $result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }

    public function get_usign(\WP_REST_Request $request){
        $data = array('mode' => 'tcvod');
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
