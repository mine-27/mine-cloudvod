<?php
namespace MineCloudvod\HuaWei;

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
        $templates = get_option( 'mcv_huawei_transcode' );
        $templatesOptions = [];
        if( is_array( $templates ) ){
            foreach( $templates as $template ){
                $name = '';
                if( $template['type'] == '' ){
                    $name = '系统自带-' . $template['name'];
                }
                else{
                    $name =  '自定义-' . $template['name'];
                }
                $templatesOptions[$template['name']] = $name;
            }
        }
        else{
            $templatesOptions = [''=>__('Please sync transcoding template first', 'mine-cloudvod')];
        }
        $prefix = 'mcv_settings';
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_huaweicloud',
            'title'     => __('VOD', 'mine-cloudvod'),
            'icon'   => 'fas fa-video',
            'fields' => array(
                array(
                    'type' => 'submessage',
                    'style' => 'success',
                    'content' => '更多华为云点播相关说明，<a href="https://www.mine27.cn/docs/huaweicloud/" target="_blank">请点击此链接查看</a>'
                ),
                array(
                    'id'        => 'huaweicloud',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'          => 'endpoint',
                            'type'        => 'select',
                            'title'       => __('Storage area', 'mine-cloudvod'),
                            'placeholder' => __('Select storage area', 'mine-cloudvod'),
                            'options'     => [
                                'cn-north-4'        => '华北-北京四',
                                'ap-southeast-3'    => '亚太-新加坡',
                            ],
                            'default'     => 'cn-north-4'
                        ),
                        array(
                            'type'  => 'submessage',
                            'style' => 'success',
                            'content' => '<p><a href="javascript:mcv_sync_projectinfo();">点击获取账号ID/项目ID</a></p>
                            <script>
                            function mcv_sync_projectinfo(){
                                wp.apiFetch({
                                    path: "/mine-cloudvod/v1/huawei/vod/projectinfo",
                                }).then(function(response) {
                                    if(response.status == 0){
                                        layer.msg(response.msg);
                                        return;
                                    }
                                    layer.msg("获取成功");
                                    jQuery("input[data-depend-id=domainId]").val(response.data.domain_id);
                                    jQuery("input[data-depend-id=projectId]").val(response.data.id);
                                });
                            }
                            </script>
                            ',
                        ),
                        array(
                            'id'    => 'domainId',
                            'type'  => 'text',
                            'title' => '账号ID',
                            'after' => '<p><a href="javascript:mcv_sync_projectinfo();">点击获取账号ID/项目ID</a></p>',
                        ),
                        array(
                            'id'    => 'projectId',
                            'type'  => 'text',
                            'title' => '项目ID',
                            'after' => '<p><a href="javascript:mcv_sync_projectinfo();">点击获取账号ID/项目ID</a></p>',
                        ),
                        array(
                            'id'          => 'transcode',
                            'class'       => 'hw_transcode',
                            'type'        => 'select',
                            'title'       => __('Transcoding template', 'mine-cloudvod'),
                            'placeholder' => __('Select transcoding template', 'mine-cloudvod'),
                            'options'     => $templatesOptions,
                            'default'     => '',
                            'after'       => '<p><a href="javascript:mcv_sync_huawei_transcode();">'.__('Sync transcoding template', 'mine-cloudvod').'</a></p>',//同步转码模板组,
                        ),
                        array(
                            'id'    => 'scretkey',
                            'type'  => 'text',
                            'title' => '防盗链Key',
                            'after' => '<p>留空表示不启用此功能，加密请选择<b>算法D</b></p>',
                        ),
                        array(
                            'id'    => 'encrypt',
                            'type'  => 'switcher',
                            'title' => 'HLS加密',
                            'after' => '<br /><p style="color:red">启用后，通过插件上传的视频会自动进行HLS加密，请选用开启加密的HLS转码模板</p>',
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false
                        ),
                        array(
                            'type' => 'submessage',
                            'style' => 'warning',
                            'content' => '<p>请将下列URL配置到华为云点播后台，<a href="https://console.huaweicloud.com/vod2/#/vod/globalSetting/securitySetting" target="_blank">点击云配置</a></p>
                            <p>'.rest_url('mine-cloudvod/v1/huawei/vod/key').'</p>',
                            'dependency' => array( 'encrypt', '==', true ),
                        ),
                        array(
                            'id'    => 'token',
                            'type'  => 'text',
                            'title' => __('Security key', 'mine-cloudvod'),
                            'dependency' => array( 'encrypt', '==', true ),
                            'after' => '用于播放HLS加密视频的安全验证,防止视频被盗播',
                            'default' => time()
                        ),
                        array(
                            'id'    => 'tokenTime',
                            'type'  => 'number',
                            'title' => __('Valid Duration', 'mine-cloudvod'),
                            'after' => '<p>'.__('Valid duration of transparent transmission parameters', 'mine-cloudvod').'</p>',
                            'dependency' => array( 'encrypt', '==', true ),
                            'unit' => __('Hour', 'mine-cloudvod'),
                            'default' => 10
                        ),
                    ),
                ),
            )
        ));
    }

    public function mcv_register_block(){
        register_block_type( MINECLOUDVOD_PATH . '/build/huawei/');
        
        wp_add_inline_script('jquery','var mcv_hw_config={config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('HuaWei Cloud', 'mine-cloudvod'))))).'",sdk:'.(!empty(MINECLOUDVOD_SETTINGS['huaweicloud']['skey']) ? 'true' : 'false').'};');
    }

    public function register_routes(){
        $namespace = 'mine-cloudvod';
        $version = 'v1';
        $base = 'huawei/vod';
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
         * 获取项目信息
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/projectinfo', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getProjectInfo'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                ]
        ]);
        /**
         * 创建分类
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/createCategory', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'createCategory'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'name' => [
                        'type' => 'string',
                    ],
                    'pid' => [
                        'type' => 'integer',
                    ]
                ]
        ]);
        /**
         * 分类列表
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/categories', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'categoriesList'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'pid' => [
                        'type' => 'integer',
                    ]
                ]
        ]);
        /**
         * 转码模板列表
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/templates', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'templatesList'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                ]
        ]);
        /**
         * 转码模板列表
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/key', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getKey'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'asset_id' => [
                        'type' => 'string',
                    ],
                    'token' => [
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
                    'endpoint' => [
                        'type' => 'string'
                    ]
                ]
        ]);
    }
    public function getKey(\WP_REST_Request $request){
        $token = $request['token'];
        $tokenObj = new \MineCloudvod\Ability\Token(MINECLOUDVOD_SETTINGS['huaweicloud']['token']??'mcv', MINECLOUDVOD_SETTINGS['huaweicloud']['tokenTime']??10);
        $result = $tokenObj->check_token($token);
        if($result['code'] == '200'){
            $asset_id = $request['asset_id'];

            $dir = 'huawei/key';
            $cache = mcv_get_file_cache($dir, $asset_id, 864000); // 缓存10天
            if($cache){
                echo esc_html(base64_decode( $cache ));
                exit;
            }

            $req = [
                'mode' => 'huawei',
                'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
                'asset_id' => $asset_id,
            ];
            $result = $this->_wpcvApi->call( 'getkey', $req );

            if( $result['status'] == 1 && isset( $result['data']['dk'] ) ){
                mcv_set_file_cache($dir, $asset_id, $result['data']['dk']);
                echo esc_html(base64_decode( $result['data']['dk'] ));
            }
        }
        else
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
    }
    public function templatesList(\WP_REST_Request $request){
        $req = [
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
        ];
        $result = $this->_wpcvApi->call( 'templates', $req );
        if( $result['status'] == 1 ){
            update_option('mcv_huawei_transcode', $result['data']['template_group_list']);
        }
        return rest_ensure_response($result);
    }
    public function categoriesList(\WP_REST_Request $request){
        $pid = $request['pid']??0;
        $req = [
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
            'catpid' => $pid,
        ];
        $result = $this->_wpcvApi->call( 'catslist', $req );
        if( is_array( $result['data'] ) ){
            $newData = [];
            foreach( $result['data'] as $c ){
                $newData[] = [
                    'cateId' => $c['id'],
                    'cateName' => $c['name'],
                    // 'children' =>  $c['children'],
                ];
            }
            $result['data'] = $newData;
        }

        return rest_ensure_response($result);
    }
    public function createCategory(\WP_REST_Request $request){
        $name = $request['name'];
        $pid = $request['pid'];
        $req = [
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
            'catname' => $name,
            'catpid' => $pid,
        ];
        $result = $this->_wpcvApi->call( 'createcat', $req );
        $cat = [];
        if( $result['status'] == 1 ){
            $cat = [
                'cateId' => $result['data']['id'],
                'cateName' => $name,
            ];
        }
        else{
            $cat = $result;
        }
        return rest_ensure_response($cat);
    }
    public function getProjectInfo(\WP_REST_Request $request){
        $req = [
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
        ];
        $result = $this->_wpcvApi->call( 'projectinfo', $req );
        return rest_ensure_response($result);
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
        return rest_ensure_response($vinfo);
    }
    public function get_playinfo( $asset_id ){
        $result = $this->get_videoinfo( $asset_id );
        $vinfo = [];
        if( isset( $result['base_info']['cover_info_array'][0]['cover_url'] ) ){
            $thumbnail = $this->generateAuthUrl($result['base_info']['cover_info_array'][0]['cover_url']);
            $vinfo['thumbnail'] = $thumbnail;
        }
        if( isset( $result['transcode_info']['output'][0]['url'] ) ){
            $purl = $this->generateAuthUrl($result['transcode_info']['output'][0]['url']);
            if( strpos( $purl, '.m3u8' ) > 0 ){
                $token = new \MineCloudvod\Ability\Token(MINECLOUDVOD_SETTINGS['huaweicloud']['token']??'mcv', MINECLOUDVOD_SETTINGS['huaweicloud']['tokenTime']??10);
                $str = $token->generrate_token();
                $purl .= strpos( $purl, '?') > 0 ? '&' : '?';
                $purl .= 'token=' . $str;
                $vinfo['playUrl'] = $purl;
            }
        }
        elseif( isset( $result['base_info']['video_url'] ) ){
            $vurl = $this->generateAuthUrl($result['base_info']['video_url']);
            $vinfo['playUrl'] = $vurl;
        }
        return $vinfo;
    }
    public function get_videoinfo( $asset_id ){
        $req = [
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
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
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
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
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
            'cid' => $cid,
            'key' => $key,
            'page' => $page,
            'size'  => $size
        ];
        $response_body = $this->_wpcvApi->call( 'videolist', $req );

        $result = [];
        if( $response_body['status'] == 1 ){
            $videos = $response_body['data'];
            $result['count'] = $videos['total'];
            $videos = $videos['assets'];

            $items = [];
            foreach ($videos as $item) {
                $nitem = $item;
                // $nitem["title"] = $item['meta']['name'];
                $date = \DateTime::createFromFormat('YmdHis', $item["create_time"]);
                $nitem["videoId"] = $item["asset_id"];
                $nitem["thumbnail"] = $item['covers'][0]['cover_url'];
                if( !$nitem["thumbnail"] ) $nitem["thumbnail"] = MINECLOUDVOD_URL. '/static/img/default.png';
                $nitem["created_at"] = $date?$date->getTimestamp()*1000:0;
                $nitem["cateName"] = $item["category"];
                // $nitem["size"] = $item["size"];
                // $nitem["duration"] = $item["duration"];
                $status = $item["transcode_status"];
                if( $item['asset_status'] == 'PUBLISHED' && ($status == 'TRANSCODE_SUCCEED' || $status == 'UN_TRANSCODE' ) ){
                    $status = 'Normal';
                }
                else{
                    $status =  $item['asset_status'] . '-' . $status;
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
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
            'cid' => $cid,
            'name' => $name,
            'vtype' => $type,
        ];
        $response_body = $this->_wpcvApi->call( 'createvideo', $req );

        $result = [];
        if( $response_body['status'] == 1 ){
            $result = [
                'path' => $response_body['data']['video_upload_url'],
                'asset_id' => $response_body['data']['asset_id'],
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
            'mode' => 'huawei',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['huaweicloud'] ),
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
    public static function generateAuthUrl($originalUrl, $pliveStartTime = null) {
        // 控制台设置的防盗链Key值
        $key = MINECLOUDVOD_SETTINGS['huaweicloud']['scretkey']??'';
        if( !$key ) return $originalUrl;
        // 鉴权URL生成时间(格式: yyyyMMddHHmmss)
        $timestamp = gmdate('YmdHis');
        // 解析URL获取path部分
        $parsedUrl = wp_parse_url($originalUrl);
        $path = dirname($parsedUrl['path']);        

        // 确保path以/结尾
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }        
        // 生成16字节的随机IV
        $iv = openssl_random_pseudo_bytes(16);        
        // 构建原始加密串
        $originalString = urlencode($path) . '$' . $timestamp;
        if ($pliveStartTime !== null) {
            $originalString .= '$' . $pliveStartTime;
        }  
        // 使用AES-CBC-128-PKCS5Padding加密
        $encrypted = openssl_encrypt(
            $originalString,
            'AES-128-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );        
        // Base64编码加密结果
        $encryptedBase64 = base64_encode($encrypted);        
        // 将IV转换为十六进制
        $encodedIV = bin2hex($iv);        
        // 构建鉴权URL
        $authUrl = $originalUrl . '?auth_info=' . urlencode($encryptedBase64) . '.' . $encodedIV;        
        if ($pliveStartTime !== null) {
            $authUrl .= '&plive=' . $pliveStartTime;
        }        
        return $authUrl;
    }
}
