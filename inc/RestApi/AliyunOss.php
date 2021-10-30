<?php
namespace MineCloudvod\RestApi;
use MineCloudvod\MineCloudVodAPI;

class AliyunOss{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    protected $base = 'aliyun/oss';
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
         * Buckets List
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/buckets', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'fetch_buckets'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => []
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
         * playurl
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/playurl', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_oss_url'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'bucket' => [
                        'type' => 'string',
                    ],
                    'object' => [
                        'type' => 'string'
                    ]
                ]
            ],
        ]);
        /**
         * playurl
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/uploadauth', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_upload_auth'],
                'permission_callback' => function(){return true;},
                'args'                => [
                    'bucket' => [
                        'type' => 'string',
                    ],
                ]
            ],
        ]);

    }
    public function read_files_permissions_check(){
        return current_user_can('edit_posts');
    }

    public function get_upload_auth(\WP_REST_Request $request){
        $bucket     = $request['bucket'];

        $req = array(
            'bucket' => $bucket,
            'mode' => 'alioss'
        );
        $result = $this->_wpcvApi->call('region', $req);
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        $region     = $result['data'];
        
        $id         = MINECLOUDVOD_SETTINGS['alivod']['accessKeyID'];
        $key        = MINECLOUDVOD_SETTINGS['alivod']['accessKeySecret'];
        $host = sprintf('//%s.%s.aliyuncs.com', $bucket, $region);
        $dir = 'mcv/';

        $callback_param = array(
            'callbackUrl' => '',
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => "application/x-www-form-urlencoded"
        );
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 30;  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);


        //最大文件大小.用户可以自己设置
        $condition = array(0 => 'content-length-range', 1 => 0, 2 => 1048576000);
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
        $conditions[] = $start;


        $arr = array('expiration' => $expiration, 'conditions' => $conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        $response['dir'] = $dir;
        $response['x-oss-content-type'] = 'multipart/form-data';
        return rest_ensure_response($response);
    }
    private function gmt_iso8601($time)
    {
        return str_replace('+00:00', '.000Z', gmdate('c', $time));
    }

    public function fetch_buckets(\WP_REST_Request $request){
        if($bucketsList = get_option('mcv_alioss_bucketsList')){
            $buckets = array();
            
            foreach($bucketsList as $bucket){
                $bucket[1] = $bucket[0] == MINECLOUDVOD_SETTINGS['alivod']['buckets'];
                $buckets[] = $bucket;
            }
            return rest_ensure_response($buckets);
        }
        else{
            $req = array(
                'bucket'=>'mcv',
                'mode' => 'alioss'
            );
            $result = $this->_wpcvApi->call('buckets', $req);
    
            if (is_wp_error($result)) {
                return $result;
            }
            if($result['status'] == 0){
                return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
            }
            $bucketsList = $result['data'];
            $buckets = array();
            foreach($bucketsList as $bukcet){
                $bucket[1] = $bucket[0] == MINECLOUDVOD_SETTINGS['alivod']['buckets'];
                $buckets[] = $bucket;
            }
    
            return rest_ensure_response($buckets);
        }
        
    }
    /**
     * Fetch videos from aliyun oss
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function fetch_videos(\WP_REST_Request $request){
        if(empty($request['bucket'])){
            return new \WP_Error('cant-trash', __('Bucket is missed', 'mine-cloudvod'), ['status' => 500]);
        }
        $req = array(
            'pageNo' => (int) $request['page'],
            'pageSize' => (int)$request['items_per_page'],
            'bucket' => $request['bucket'],
            'mode' => 'alioss'
        );
        $result = $this->_wpcvApi->call('list', $req);

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
            'bucket' => $request['bucket'],
            'object' => $request['object'],
            'mode' => 'alioss'
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

    public function get_oss_url(\WP_REST_Request $request){
        $oss = new \MineCloudvod\Aliyun\Oss();
        $result = $oss->get_mediaUrl($request['object'], $request['bucket']);

        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['status']) && $result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }

}
