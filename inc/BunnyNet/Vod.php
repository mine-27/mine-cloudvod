<?php
namespace MineCloudvod\BunnyNet;

class Vod{
    private $_wpcvApi;
    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;

        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );
        add_action( 'wp_ajax_mcv_sync_bunny_libs', array($this, 'mcv_sync_bunny_libs') );
        add_action( 'init',     [ $this, 'mcv_register_block'] );
        add_action( 'rest_api_init', [$this, 'register_routes'] );
    }

    public function mcv_register_block(){
        
        wp_register_script(//mcv_dplayer_hls
            'mcv_dplayer_hls',
            MINECLOUDVOD_URL.'/static/dplayer/hls.min.js',
            null,
            MINECLOUDVOD_VERSION,
            true
        );

        wp_register_style(
            'mcv_dplayer_css',
            MINECLOUDVOD_URL.'/static/dplayer/style.css', 
            is_admin() ? array( 'wp-editor' ) : null,
            MINECLOUDVOD_VERSION
        );
        
        
        register_block_type( MINECLOUDVOD_PATH . '/build/bunnynet/');
        
        wp_add_inline_script('jquery','var mcv_bunny_config={bunny_config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('BunnyNet', 'mine-cloudvod'))))).'",apikey:'.(!empty(MINECLOUDVOD_SETTINGS['bunnynet']['apikey']) ? 'true' : 'false').',libid:'.(MINECLOUDVOD_SETTINGS['bunnynet']['library']??0).'};');
    }
    public function mcv_sync_bunny_libs(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_sync_bunny_libs')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = [
            'mode' => 'bunny',
            'sdk'=>$this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['bunnynet'] ),
        ];
        $libs = $this->_wpcvApi->call('libs', $data);
        update_option('mcv_bunny_libs', $libs['data']);
        echo wp_json_encode($libs);
        exit;
    }

    public function admin_options(){
        $prefix = 'mcv_settings';
        $mcv_bunny_libs = array('0' => __('Please sync video libraries first', 'mine-cloudvod'));
        if ($tctc = get_option('mcv_bunny_libs')) {
            $mcv_bunny_libs = array();
            foreach ($tctc as $tc) {
                $mcv_bunny_libs[$tc['Id']] =  $tc['Name'];
            }
        }
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_bunnynet',
            'title'  => __('Stream', 'mine-cloudvod'),
            'icon'   => 'fas fa-key',
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => __('<a href="https://bunny.net?ref=c7jsvi03q6" target="_blank">Bunny.net</a> ', 'mine-cloudvod'), 
                ),
                array(
                    'id'        => 'bunnynet',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'          => 'library',
                            'type'        => 'select',
                            'title'       => __('Video Library', 'mine-cloudvod'),
                            'after'       => '<p><a href="javascript:mcv_sync_bunny_libs();">'.__('Sync Bunny Video Libraries', 'mine-cloudvod').'</a></p>',//同步转码模板组,
                            'options'     => $mcv_bunny_libs,
                            'default'     => '0'
                        ),
                        array(
                            'id'          => 'tokenKey',
                            'type'        => 'text',
                            'title'       => __('Token Authentication Key', 'mine-cloudvod'),
                            'after'       => '若启用了令牌认证，请填写',
                        ),
                    ),
                ),
            )
        ));
    }

    public function register_routes(){
        $namespace = 'mine-cloudvod';
        $version = 'v1';
        $base = 'bunny/vod';
        /**
         * search videos
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/videos', [
                'methods'             => \WP_REST_Server::READABLE,
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
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'create_video'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'title' => [
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
         * list colletions
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/categories', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'video_collections'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                ]
        ]);
        /**
         * create collect
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/createCategory', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'create_category'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'name' => [
                        'type' => 'string',
                    ]
                ]
        ]);
        /**
         * Get bunny.net stream play url.
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/playurl', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'video_playinfo'],
                'permission_callback' => [$this, 'is_admin'],
                'args'                => [
                    'vid' => [
                        'type' => 'string',
                    ],
                    'libid' => [
                        'type' => 'integer',
                    ]
                ]
        ]);
    }
    public function is_admin(){
        $user = wp_get_current_user();
        $allowed_roles = array( 'administrator' );
        if ( array_intersect( $allowed_roles, $user->roles ) ) {
            return true;
        }
        return false;
    }

    /**
     * Create videos collection
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function video_playinfo(\WP_REST_Request $request){

        $libraryId = $request['libid'];
        $videoId = $request['vid'];
        $response_body = $this->get_playinfo($libraryId, $videoId);
        
        $result = [];
        if( isset( $response_body['videoPlaylistUrl'] ) ){
            $result = [
                'playUrl' => $response_body['videoPlaylistUrl'],
                'previewUrl' => $response_body['previewUrl'],
            ];
        }
        return rest_ensure_response($result);
    }
    public function get_playinfo($libraryId, $videoId){
        $accKey = $this->get_library_info( $libraryId, 'ApiAccessKey' );
        $apiUrl = "https://video.bunnycdn.com/library/{$libraryId}/videos/{$videoId}/play?expires=0";
        $tokenKey = MINECLOUDVOD_SETTINGS['bunnynet']['tokenKey']??'';
        $expires = 0;
        if( $tokenKey ){
            $expires = time() + 36000;
            $token = hash("sha256", $tokenKey.$videoId.$expires);
            $apiUrl = "https://video.bunnycdn.com/library/{$libraryId}/videos/{$videoId}/play?token={$token}&expires=".$expires;
        }
        $response = wp_remote_request( $apiUrl, [
            'timeout' => 10,
            'method' => "GET",
            'headers' => [
                'AccessKey' => $accKey,
                'Content-Type' => 'application/json',
            ],
        ] );
        if ( is_wp_error($response) ) {
            return $response;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        if( $tokenKey ) {
            $path =  wp_parse_url($response_body['videoPlaylistUrl']);
            $token_path = dirname($path['path']).'/';
            $response_body['videoPlaylistUrl'] = $response_body['videoPlaylistUrl']."?token=". base64_encode(hash("sha256", str_replace(['\n', '+', '/', '='], ['', '-', '_', ''], $tokenKey.$token_path.$expires.'&token_path='.$token_path))).'&token_path='.$token_path."&expires=".$expires;
        }
        return $response_body;
    }
    /**
     * Create videos collection
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function create_category(\WP_REST_Request $request){
        $libraryId = MINECLOUDVOD_SETTINGS['bunnynet']['library'];
        $accKey = $this->get_library_info( $libraryId, 'ApiAccessKey' );
        
        $response = wp_remote_request( "https://video.bunnycdn.com/library/{$libraryId}/collections", [
            'timeout' => 10,
            'body' => json_encode(['name'=>$request['name']]),
            'method' => "POST",
            'headers' => [
                'AccessKey' => $accKey,
                'Content-Type' => 'application/json',
            ],
        ] );
        if ( is_wp_error($response) ) {
            return $response;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        
        $result = [];
        if( isset( $response_body['guid'] ) && $response_body['guid'] ){
            $result = [
                'cateId' => $response_body['guid'],
                'cateName' => $response_body['name'],
            ];
        }
        return rest_ensure_response($result);
    }
    /**
     * Delete video from bunny.net
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function video_collections(\WP_REST_Request $request){
        $libraryId = MINECLOUDVOD_SETTINGS['bunnynet']['library'];
        $accKey = $this->get_library_info( $libraryId, 'ApiAccessKey' );
        
        $response = wp_remote_request( "https://video.bunnycdn.com/library/{$libraryId}/collections?page=1&itemsPerPage=100&orderBy=date&includeThumbnails=false", [
            'timeout' => 10,
            'method' => "GET",
            'headers' => [
                'AccessKey' => $accKey,
                'Content-Type' => 'application/json',
            ],
        ] );
        if ( is_wp_error($response) ) {
            return $response;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        $result = [];
        if( isset( $response_body['items'] ) && is_array( $response_body['items'] ) ){
            foreach( $response_body['items'] as $item ){
                $result[] = [
                    'cateId' => $item['guid'],
                    'cateName' => $item['name'],
                ];
            }
        }
        return rest_ensure_response($result);
    }
    /**
     * Delete video from bunny.net
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function del_video(\WP_REST_Request $request){
        $videoId = $request['videoId'];
        $libraryId = MINECLOUDVOD_SETTINGS['bunnynet']['library'];
        $accKey = $this->get_library_info( $libraryId, 'ApiAccessKey' );
        
        $response = wp_remote_request( "https://video.bunnycdn.com/library/{$libraryId}/videos/{$videoId}", [
            'timeout' => 10,
            'method' => "DELETE",
            'headers' => [
                'AccessKey' => $accKey,
                'Content-Type' => 'application/json',
            ],
        ] );
        if ( is_wp_error($response) ) {
            return $response;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        
        if(!$response_body['success']){
            return new \WP_Error('delete-error', $response_body['message'], ['status' => $response_body['statusCode']]);
        }
        wp_send_json_success();
    }
    /**
     * Fetch videos from bunny.net
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function fetch_videos(\WP_REST_Request $request){
        $Status = [
            __("Created", "mine-cloudvod"),
            __("Uploaded", "mine-cloudvod"),
            __("Processing", "mine-cloudvod"),
            __("Transcoding", "mine-cloudvod"),
            __("Finished", "mine-cloudvod"),
            __("Error", "mine-cloudvod"),
            __("UploadFailed", "mine-cloudvod"),
            __("Transcribing", "mine-cloudvod"),
        ];
        $req = array(
            'page' => (int) ($request['page']??'1'),
            'page_size' => 100,
            'libraryid' => MINECLOUDVOD_SETTINGS['bunnynet']['library'],
        );
        if(isset($request['search']) && $request['search']){
            $req['search'] = sanitize_text_field($request['search']);
        }
        if(isset($request['cateId']) && $request['cateId']){
            $req['collection'] = sanitize_text_field($request['cateId']);
        }
        
        $libraryId = MINECLOUDVOD_SETTINGS['bunnynet']['library'];
        $accKey = $this->get_library_info( $libraryId, 'ApiAccessKey' );
        
        $response = wp_remote_get( "https://video.bunnycdn.com/library/{$libraryId}/videos?". http_build_query($req), [
            'timeout' => 10,
            'method' => 'GET',
            'headers' => [
                'AccessKey' => $accKey,
                'Content-Type' => 'application/json',
            ],
        ] );
        if ( is_wp_error($response) ) {
            return $response;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        // var_dump($response_body);
        $videos = $response_body['items'];
        $result['count'] = $response_body['totalItems'];
        // prepare for response
        if(is_array($videos) && !empty($videos)){
            $zoneDomain = $this->get_library_info( $libraryId, 'PullZoneDomain' );
            $ssl = $this->get_library_info( $libraryId, 'HasCertificate' );
            $url = 'http'.($ssl?'s':'').'://'.$zoneDomain;
            $items = [];
            
            foreach ($videos as $item) {
                $item["thumbnail"] = $url."/".$item['guid']."/".$item['thumbnailFileName'];
                $item["updated_at"] = $item["dateUploaded"];
                $item["created_at"] = $item["dateUploaded"];
                $item["videoId"] = $item["guid"];
                $item["size"] = $item["storageSize"];
                $item["duration"] = $item["length"];
                $item["status"] = $item["status"] == 4 ? 'Normal' : $Status[$item["status"]*1];
                $items[] = $item;
            }
            $result["items"] = $items;
        }
        
        // var_dump($videos);
        return rest_ensure_response($result);
    }
    public function get_library_info( $libraryId, $filed = 'ApiAccessKey' ){
        $libs = get_option('mcv_bunny_libs');
        if(!$libs || !is_array( $libs )) return false;
        foreach( $libs as $lib ){
            if( $lib['Id'] == $libraryId ){
                return $lib[$filed];
                break;
            }
        }
        return false;
    }
    public function create_video(\WP_REST_Request $request){  
        $libraryId = MINECLOUDVOD_SETTINGS['bunnynet']['library'];
        $accKey = $this->get_library_info( $libraryId, 'ApiAccessKey' );
        $data = [
            'title' => $request['filename'],
        ];
        if( isset($request['cid']) && $request['cid'] ){
            $data['collectionId'] = $request['cid'];
        }
        $response = wp_remote_post( "https://video.bunnycdn.com/library/{$libraryId}/videos", [
            'timeout' => 10,
            'body' => json_encode($data),
            'method' => 'POST',
            'headers' => [
                'AccessKey' => $accKey,
                'Content-Type' => 'application/json',
            ],
        ] );

        if ( is_wp_error($response) ) {
            return $response;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $response_body, true );

        return rest_ensure_response( [
            'path' => 'https://video.bunnycdn.com/library/'.$libraryId.'/videos/'.$body['guid'].'?skipEncoding=false',
            'accesskey' => $accKey,
        ] );
    }
}
