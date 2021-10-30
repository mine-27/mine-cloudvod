<?php
namespace MineCloudvod\RestApi;
use MineCloudvod\MineCloudVodAPI;
use MineCloudvod\Qcloud\Sts;

class QcloudCos{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    protected $base = 'qcloud/cos';
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
                'callback'            => [$this, 'get_cos_url'],
                'permission_callback' => function(){return true;},
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
        $region = $this->get_bucket_region($bucket);
        if(!$region){
            return new \WP_Error('cant-trash', __('Sync the buckets list, please.', 'mine-cloudvod'), ['status' => 500]);
        }
        $sts = new STS();
        $dir = 'mcv/';
        
        $config = array(
            'url' => 'https://sts.tencentcloudapi.com/',
            'domain' => 'sts.tencentcloudapi.com',
            'proxy' => '',
            'secretId' => MINECLOUDVOD_SETTINGS['tcvod']['sid'],
            'secretKey' => MINECLOUDVOD_SETTINGS['tcvod']['skey'],
            'bucket' => $bucket,
            'region' => $region,
            'durationSeconds' => 1800, // 密钥有效期
            'allowPrefix' => '*',
            'allowActions' => array (
                // 所有 action 请看文档 https://cloud.tencent.com/document/product/436/31923
                // 简单上传
                'name/cos:GetObject',
                'name/cos:PutObject',
                'name/cos:PostObject',
                'name/cos:GetBucket',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload'
            )
        );
        $tempKeys = $sts->getTempKeys($config);
        $tempKeys['dir'] = $dir;
        $tempKeys['host'] = 'http://' . $bucket . '.cos.' . $region . '.myqcloud.com/';

        return rest_ensure_response($tempKeys);
    }
    public function get_bucket_region($bucket){
        $regions = get_option('mcv_tccos_bucketsList');
        if(!$regions) return false;
        foreach($regions as $region){
            if($region[0] == $bucket){
                return $region[1];
                break;
            }
        }
        return false;
    }

    public function fetch_buckets(\WP_REST_Request $request){
        if($bucketsList = get_option('mcv_tccos_bucketsList')){
            $buckets = array();
            
            foreach($bucketsList as $bucket){
                $bucket[1] = $bucket[0] == MINECLOUDVOD_SETTINGS['tcvod']['buckets'];
                $buckets[] = $bucket;
            }
            return rest_ensure_response($buckets);
        }
        else{
            $req = array(
                'bucket'=>'mcv',
                'mode' => 'tccos'
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
                $bucket[1] = $bucket[0] == MINECLOUDVOD_SETTINGS['tcvod']['buckets'];
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
            'mode' => 'tccos'
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
        if(is_array($videos['items']) && !empty($videos['items'])){
            $items = [];
            foreach ($videos['items'] as $key => $item) {
                $item['bucket'] = $request['bucket'];
                $item['Key'] = urldecode($item['Key']);
                $items[] = $item;
            }
            $videos['items'] = $items;
        }

        return rest_ensure_response($videos);
    }

    public function del_video(\WP_REST_Request $request){
        $req = array(
            'bucket' => $request['bucket'],
            'object' => $request['object'],
            'mode' => 'tccos'
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

    public function get_cos_url(\WP_REST_Request $request){
        $cos = new \MineCloudvod\Qcloud\Cos();
        $result = $cos->get_mediaUrl($request['object'], $request['bucket']);

        if (is_wp_error($result)) {
            return $result;
        }
        if(isset($result['status']) && $result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }

}
