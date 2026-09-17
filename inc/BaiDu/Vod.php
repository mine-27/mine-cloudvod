<?php
namespace MineCloudvod\BaiDu;

class Vod{
    private $_wpcvApi;
    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;

        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );
        add_action( 'init',     [ $this, 'mcv_register_block'] );
        add_action( 'rest_api_init', [$this, 'register_routes'] );
    }
    public function admin_options(){
        $prefix = 'mcv_settings';
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_baiducloud',
            'title'     => __('VOD', 'mine-cloudvod'),
            'icon'   => 'fas fa-video',
            'fields' => array(
                array(
                    'type' => 'submessage',
                    'style' => 'success',
                    'content' => '更多百度云点播相关说明，<a href="https://www.mine27.cn/docs/baiducloud/" target="_blank">请点击此链接查看</a>'
                ),
                array(
                    'id'        => 'baiducloud',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'workflowId',
                            'type'  => 'text',
                            'title' => '工作流ID',
                        ),
                        array(
                            'id'    => 'secretkey',
                            'type'  => 'text',
                            'title' => '高级鉴权 主Key',
                            'after' => '<p>留空表示不启用此功能，<a href="https://www.mine27.cn/docs/baiducloud/" target="_blank">点击这里查看启用方法</a></p>',
                        ),
                        array(
                            'id'    => 'encrypt',
                            'type'  => 'switcher',
                            'title' => 'Token 加密',
                            'after' => '<br /><p style="color:red">启用后，通过插件上传的视频会自动进行Token加密，请选用开启Token加密的工作流ID</p>',
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false
                        ),
                        array(
                            'id'    => 'userid',
                            'type'  => 'text',
                            'title' => '账号ID',
                            'dependency' => array( 'encrypt', '==', true ),
                            'after' => '<p><a href="https://console.bce.baidu.com/iam/#/iam/baseinfo" target="_blank">点此查看账号ID</a></p>',
                        ),
                        array(
                            'id'    => 'appid',
                            'type'  => 'text',
                            'title' => 'AppId',
                            'dependency' => array( 'encrypt', '==', true ),
                            'after' => '<p><a href="https://console.bce.baidu.com/vod2/#/encryption" target="_blank">点此查看AppId</a></p>',
                        ),
                        array(
                            'id'    => 'userkey',
                            'type'  => 'text',
                            'title' => 'UserKey',
                            'after' => '<p>'.__('Valid duration of transparent transmission parameters', 'mine-cloudvod').'</p>',
                            'dependency' => array( 'encrypt', '==', true ),
                            'after' => '<p><a href="https://console.bce.baidu.com/vod2/#/encryption" target="_blank">点此查看UserKey</a></p>',
                        ),
                    ),
                ),
            )
        ));
    }

    public function mcv_register_block(){
        register_block_type( MINECLOUDVOD_PATH . '/build/baidu/');
        
        wp_add_inline_script('jquery','var mcv_bd_config={config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('BaiDu Cloud', 'mine-cloudvod'))))).'",sdk:'.(!empty(MINECLOUDVOD_SETTINGS['baiducloud']['skey']) ? 'true' : 'false').'};');
    }

    public function register_routes(){
        $namespace = 'mine-cloudvod';
        $version = 'v1';
        $base = 'baidu/vod';
        /**
         * search videos
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/videos', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'fetch_videos'],
                'permission_callback' => [$this, 'is_admin'],
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
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/create', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'create_video'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'name' => [
                        'type' => 'string'
                    ],
                    'size' => [
                        'type' => 'number'
                    ],
                    'type' => [
                        'type' => 'string'
                    ],
                    'cid' => [
                        'type' => 'number'
                    ]
                ]
        ]);
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/uploaded', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'uploaded_video'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'asset_id' => [
                        'type' => 'string'
                    ],
                ]
        ]);
        /**
         * delete video
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/delvideo', [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'del_video'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'videoId' => [
                        'type' => 'string',
                    ]
                ]
        ]);
        /**
         * playurl
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/playurl', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_playurl'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'vid' => [
                        'type' => 'string',
                    ],
                ]
        ]);
        /**
         * decrypt
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/decrypt', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getKey'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'vid' => [
                        'type' => 'string',
                    ],
                ]
        ]);
    }
    public function getKey(\WP_REST_Request $request){
        $mediaId = $request['vid'];

        $dir = 'baidu/key';
        $cache = mcv_get_file_cache($dir, $mediaId, 864000); // 缓存10天
        if($cache){
            echo esc_html($cache);
            exit;
        }

        $playerId = 'pid-1-5-1';
        $token = $this->generateVodToken( $mediaId );
        $url = 'https://drm.media.baidubce.com/v1/tokenVideoKey?videoKeyId='. $mediaId .'&playerId=' . $playerId . '&token=' . $token;
        $response = wp_remote_get($url);
        if ( is_wp_error($response) ) {
            return $response;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        if( isset( $response_body['encryptedVideoKey'] ) ){
            $encryptedVideoKey =  $response_body['encryptedVideoKey'];
            $key = $this->aesEcbDecrypt( $encryptedVideoKey );
            if( $key ) mcv_set_file_cache($dir, $mediaId, $key);
            echo esc_html($key);
        }
    }
    public function aesEcbDecrypt($encryptedData, $key='72Fhskjglp8qjpqx') {
        $keyLength = strlen($key);
        if (!in_array($keyLength, [16, 24, 32])) {
            throw new \InvalidArgumentException("Key must be 16, 24, or 32 bytes long");
        }
        $keyBytes = [];
        for ($i = 0; $i < strlen($encryptedData); $i += 2) {
            $hexByte = substr($encryptedData, $i, 2);
            $keyBytes[] = hexdec($hexByte);
        }
        $binaryKey = '';
        foreach ($keyBytes as $byte) {
            $binaryKey .= chr($byte);
        }
        $decrypted = openssl_decrypt(
            $binaryKey,
            'AES-' . ($keyLength * 8) . '-ECB',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );
    
        if ($decrypted === false) {
            throw new \RuntimeException("Decryption failed: " . esc_html(openssl_error_string()));
        }
    
        return $decrypted;
    }
    public function is_admin(){
        $user = wp_get_current_user();
        $allowed_roles = array( 'administrator' );
        if ( array_intersect( $allowed_roles, $user->roles ) ) {
            return true;
        }
        return false;
    }

    public function get_playurl(\WP_REST_Request $request){
        $videoId = $request['vid'];
        $vinfo = $this->get_playinfo($videoId);
        $vinfo['decrypturi'] = rest_url( '/mine-cloudvod/v1/baidu/vod/decrypt?vid='.$videoId );
        return rest_ensure_response($vinfo);
    }
    public function get_playinfo( $asset_id ){
        $result = $this->get_videoinfo( $asset_id );
        $vinfo = [];
        if( isset( $result['source']['coverUrl'] ) ){
            $thumbnail = $this->generateAuthUrl( $result['source']['coverUrl'] );
            $vinfo['thumbnail'] = $thumbnail;
        }
        if( isset( $result['transcodeOutputs'][0]['url'] ) ){
            $purl = $this->generateAuthUrl( $result['transcodeOutputs'][0]['url'] );
            if( strpos( $purl, '.m3u8' ) > 0 ){
                $str = $this->generateVodToken( $asset_id );
                $purl .= strpos( $purl, '?') > 0 ? '&' : '?';
                $purl .= 'token=' . $str;
            }
            $vinfo['playUrl'] = $purl;
        }
        elseif( isset( $result['source']['sourceUrl'] ) ){
            $vurl = $this->generateAuthUrl( $result['source']['sourceUrl'] );
            $vinfo['playUrl'] = $vurl;
        }
        return $vinfo;
    }
    public function get_videoinfo( $asset_id ){
        $req = [
            'mode' => 'baidu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['baiducloud'] ),
            'asset_id' => $asset_id,
        ];
        $response_body = $this->_wpcvApi->call( 'getvinfo', $req );
        if( $response_body['status'] == 1 ){
            return $response_body['data'];
        }
        return $response_body;
    }
    /**
     * Delete video
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function del_video(\WP_REST_Request $request){
        $videoId = $request['videoId'];

        $req = [
            'mode' => 'baidu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['baiducloud'] ),
            'asset_id' => $videoId,
        ];
        $result = $this->_wpcvApi->call( 'deletevideo', $req );
        return rest_ensure_response( $result );
    }
    /**
     * Fetch videos
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function fetch_videos(\WP_REST_Request $request){
        $cid = $request['cateId']??0;
        $key = $request['search']??'';
        $page = $request['page']??1;
        $size = $request['items_per_page']??100;
        $page--;
        $req = [
            'mode' => 'baidu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['baiducloud'] ),
            'cid' => $cid,
            'key' => $key,
            'page' => $page,
            'size'  => $size
        ];
        $response_body = $this->_wpcvApi->call( 'videolist', $req );
        
        $result = [];
        if( $response_body['status'] == 1 ){
            $videos = $response_body['data'];
            $result['count'] = count($videos['data']);
            $result['marker'] = $videos['marker'];
            $videos = $videos['data'];

            $items = [];
            foreach ($videos as $item) {
                $nitem = $item;
                $nitem["title"] = $item['name'];
                $nitem["videoId"] = $item["mediaId"];
                $nitem["thumbnail"] = $item['source']['coverUrl'];
                if( !$nitem["thumbnail"] ) $nitem["thumbnail"] = MINECLOUDVOD_URL. '/static/img/default.png';
                $date = new \DateTime( $item["createTime"] );
                $nitem["created_at"] = $date?$date->getTimestamp()*1000:0;
                $nitem["cateName"] = 'system';
                $nitem["size"] = $item["sourceMetadata"]['fileSizeInByte'];
                $nitem["duration"] = $item["sourceMetadata"]['durationInSecond'];
                $status = $item["transcode_status"];
                if( $item['banStatus'] == 'NORMAL' ){
                    $status = 'Normal';
                }
                else{
                    $status =  $item['banStatus'];
                }
                $nitem["status"] = $status;
                $items[] = $nitem;
            }
            $result["items"] = $items;
        }
        
        return rest_ensure_response($result);
    }
    public function create_video(\WP_REST_Request $request){
        $name = $request['name'];
        $size = $request['size'];
        $type = $request['type'];
        $cid = $request['cid'];
        if( $type ){
            $type = explode( '/', $type )[1];
            $type = strtoupper( $type );
        }
        else{
            $type = 'MP4';
        }
        $req = [
            'mode' => 'baidu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['baiducloud'] ),
            // 'cid' => $cid,
            'name' => $name,
            'vtype' => $type,
        ];
        $response_body = $this->_wpcvApi->call( 'createvideo', $req );

        $result = [];
        if( $response_body['status'] == 1 ){
            $result = [
                'path' => $response_body['data']['urls'][0],
                'sessionKey' => $response_body['data']['sessionKey'],
            ];
        }
        else{
            $result = $response_body;
        }
        return rest_ensure_response( $result );
    }
    public function uploaded_video(\WP_REST_Request $request){
        $asset_id = $request['asset_id'];
        $req = [
            'mode' => 'baidu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['baiducloud'] ),
            'asset_id' => $asset_id,
        ];
        $response_body = $this->_wpcvApi->call( 'uploadedvideo', $req );

        return rest_ensure_response( $response_body );
    }
    /**
     * 生成算法D的鉴权URL
     * @param string $originalUrl 原始URL
     * @param int|null $pliveStartTime 伪直播开始时间(UTC时间戳，可选)
     * @return string 鉴权URL
     */
    public function generateAuthUrl($originalUrl) {
        // 控制台设置的防盗链Key值
        $key = MINECLOUDVOD_SETTINGS['baiducloud']['secretkey']??'';
        if( !$key ) return $originalUrl;
        // 解析URL获取path部分
        $parsedUrl = wp_parse_url($originalUrl);
        $filename = $parsedUrl['path'];
        $domain = $parsedUrl['scheme']. "://". $parsedUrl['host'];

        $time = strtotime("+1 hours");
        $rand = wp_rand(100000, 999999);

        $sstring = $filename."-".$time."-". $rand ."-0-".$key;
        $md5 = md5($sstring);
        $auth_key = "auth_key=".$time."-". $rand ."-0-".$md5;
        $authUrl = $domain.$filename."?".$auth_key;
        return $authUrl;
    }
    /**
     * 生成百度智能云VOD的Token
     * 
     * @param string $mediaId 媒资ID
     * @param int $expirationTime 过期时间戳（秒）
     * @return string 生成的Token
     */
    function generateVodToken($mediaId, $expirationTime=1800) {
        $expirationTime += time();
        // 百度云账号ID
        $userId = MINECLOUDVOD_SETTINGS['baiducloud']['userid']??'';
        // AppId
        $appId = MINECLOUDVOD_SETTINGS['baiducloud']['appid']??'';
        // UserKey
        $userKey = MINECLOUDVOD_SETTINGS['baiducloud']['userkey']??'';
        // 生成签名
        $stringToSign = "/{$mediaId}/{$expirationTime}";
        $signature = hash_hmac('sha256', $stringToSign, $userKey);
        
        // 组合成Token
        $token = "{$signature}_VOD2-{$userId}-{$appId}_{$expirationTime}";
        
        return $token;
    }
}
