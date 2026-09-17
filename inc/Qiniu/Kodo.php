<?php
namespace MineCloudvod\Qiniu;

defined( 'ABSPATH' ) || exit;

class Kodo{

    private $id = 'qiniukodo';
    private $_wpcvApi;
    private $upload;

    public function __construct() {
        global $McvApi;
        $this->_wpcvApi     = $McvApi;
        $this->init();
    }

    public function init(){
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );

        add_action( 'init',     [ $this, 'mcv_register_block'] );
        add_action('wp_ajax_mcv_asyc_qiniu_buckets', array($this, 'mcv_asyc_qiniu_buckets'));
        add_action('rest_api_init', [$this, 'register_routes']);

        // Media sync hooks
        if ( MINECLOUDVOD_SETTINGS['qiniu']['sync_media'] ?? false ) {
            add_filter( 'wp_handle_upload', [ $this, 'wpHandleUpload' ] );
            add_filter( 'wp_generate_attachment_metadata', [ $this, 'wpGenerateAttachmentMetadata' ], 10, 2 );
            add_filter( 'wp_get_attachment_url', [ $this, 'rewriteUrl' ], 10, 2 );
            add_filter( 'attachment_url_to_postid', [ $this, 'urlToPostid' ], 10, 2 );
        }
        // Always register delete hook — clean up remote files even if sync was later disabled
        add_action( 'delete_attachment', [ $this, 'deleteAttachment' ] );
    }

    public function register_routes(){
        /**
         * search videos
         */
        register_rest_route("mine-cloudvod/v1", '/qiniu/kodo/videos', [
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
                    ]
                ]
        ]);
        /**
         * delete video
         */
        register_rest_route("mine-cloudvod/v1", '/qiniu/kodo/delvideo', [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'del_video'],
                'permission_callback' => [$this, 'is_admin_editor'],
                'args'                => [
                    'videoId' => [
                        'type' => 'string',
                    ]
                ]
        ]);
        /**
         * upload sign
         */
        register_rest_route("mine-cloudvod/v1", '/qiniu/kodo/usign', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_usign'],
                'permission_callback' => '__return_true',
                'args'                => []
        ]);
        /**
         * play url
         */
        register_rest_route("mine-cloudvod/v1", '/qiniu/kodo/url', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'get_play_url'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'key' => [
                        'type' => 'string',
                    ],
                ]
        ]);

        /**
         * Batch sync existing media library files to Qiniu Kodo
         */
        register_rest_route("mine-cloudvod/v1", '/qiniu/kodo/sync_media', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'syncMediaToKodo'],
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ]);
    }

    public function get_play_url(\WP_REST_Request $request){

        $result = $this->call_url(sanitize_text_field( $request['key'] ));

        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }
    public function call_url( $key ){
        $req = [
            'mode' => 'qiniu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['qiniu'] ),
            'bucket'    => MINECLOUDVOD_SETTINGS['qiniu']['bucket'],
            'scheme' => is_ssl() ? 'https' : 'http',
            'key' => $key,
        ];
        $result = $this->_wpcvApi->call('geturl', $req);
        return $result;
    }

    public function del_video(\WP_REST_Request $request){
        $req = array(
            'mode' => 'qiniu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['qiniu'] ),
            'bucket'    => MINECLOUDVOD_SETTINGS['qiniu']['bucket'],
            'key'    => sanitize_text_field( $request['videoId'] ),
        );
        $result = $this->_wpcvApi->call('delete', $req);

        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }

        return rest_ensure_response($result);
    }

    public function get_usign(\WP_REST_Request $request){
        $req = array(
            'mode' => 'qiniu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['qiniu'] ),
            'bucket'    => MINECLOUDVOD_SETTINGS['qiniu']['bucket'],
        );
        $result = $this->_wpcvApi->call('usign', $req);

        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        
        $result['path'] = sprintf('http%s://upload%s.qiniup.com',
            is_ssl()?'s':'',
            MINECLOUDVOD_SETTINGS['qiniu']['region'] == 'z0' ? '' : '-' . MINECLOUDVOD_SETTINGS['qiniu']['region']
        );

        return rest_ensure_response($result);
    }

    public function fetch_videos(\WP_REST_Request $request){
        $req = array(
            'pageNo' => (int) $request['page'],
            'pageSize' => (int)$request['items_per_page'],
            'mode' => 'qiniu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['qiniu'] ),
            'bucket'    => MINECLOUDVOD_SETTINGS['qiniu']['bucket'],
            'scrollToken'    => $request['st'],
            'keyword'    => $request['search'],
        );
        $result = $this->_wpcvApi->call('search', $req);

        if (is_wp_error($result)) {
            return $result;
        }
        if($result['status'] == 0){
            return new \WP_Error('cant-trash', $result['msg'], ['status' => 500]);
        }
        $videos = $result['data'];
        
        // var_dump($videos);
        if(is_array($videos['items']) && !empty($videos['items'])){
            $items = [];
            foreach ($videos['items'] as $key => $item) {
                if($item['fsize'] == 0) continue;
                $temp = $item;
                $temp['thumbnail'] = $item['coverURL']??'';
                $temp['updated_at'] = $item['putTime']??0;
                $temp['created_at'] = $item['putTime']??0;
                $temp['title'] = $item['x-qn-meta']['name']?:$item['key'];
                $temp['videoId'] = $item['key'];
                $temp['size'] = $item['fsize'];
                $temp['status'] = 'Normal';
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
        
        
        register_block_type( MINECLOUDVOD_PATH . '/build/qiniu/');
        
        wp_add_inline_script('mine-cloudvod-qiniu-editor-script','var mcv_qiniu_config={qiniu_config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Qiniu', 'mine-cloudvod'))))).'",sdk:'.(MINECLOUDVOD_SETTINGS['qiniu']['sid']??MINECLOUDVOD_SETTINGS['qiniu']['kid']??false ? 'true' : 'false').'};');
    }

    public function mcv_asyc_qiniu_buckets(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_qiniu_buckets')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array(
            'mode' => 'qiniu',
            'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['qiniu'] ),
            'region' => MINECLOUDVOD_SETTINGS['qiniu']['region'],
        );
        $buckets = $this->_wpcvApi->call('bucketsv2', $data);
        
        update_option('mcv_qiniu_bucketsList', $buckets['data'][0]);
        echo wp_json_encode($buckets);
        exit;
    }

    public function admin_options(){
        $prefix = 'mcv_settings';
        $mcv_qiniu_bucketsList = array('' => __('Please sync Bukcets List first', 'mine-cloudvod'));
        if($tctc = get_option('mcv_qiniu_bucketsList')){
            $mcv_qiniu_bucketsList = array();
            foreach($tctc as $tc){
                $mcv_qiniu_bucketsList[$tc] =  $tc;
            }
        }
        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_qiniu',
            'title' => __('Qiniu', 'mine-cloudvod'),
            'icon'  => 'fas fa-cloud',
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => sprintf('<a href="https://s.qiniu.com/nMzeQb" target="_blank">%s</a>', __('Click here to register Qiniu Kodo and enjoy the gift: forever free.', 'mine-cloudvod')), 
                ),
                array(
                    'id'        => 'qiniu',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'sid',
                            'type'  => 'text',
                            'title' => 'AccessKey',
                            'attributes'  => array(
                                'autocomplete' => 'off'
                            ),
                        ),
                        array(
                            'id'    => 'skey',
                            'type'  => 'text',
                            'attributes'  => array(
                                'type'      => 'password',
                                'autocomplete' => 'off'
                            ),
                            'title' => 'SecretKey',
                            'after' => '<a href="https://portal.qiniu.com/user/key?cps_key=1h7kzmxg6f2oi" target="_blank">点此获取 AccessKey 和 SecretKey </a>',
                        ),
                        array(
                            'id'          => 'region',
                            'type'        => 'select',
                            'title'       => __('Storage area', 'mine-cloudvod'),//'存储区域',
                            'placeholder' => __('Select storage area', 'mine-cloudvod'),//'选择区域',
                            'options'     => [
                                'z0'                => '华东-浙江',
                                'cn-east-2'         => '华东-浙江2',
                                'z1'                => '华北-河北',
                                'z2'                => '华南-广东',
                                'na0'               => '北美-洛杉矶',
                                'as0'               => '亚太-新加坡',
                                'ap-northeast-1'    => '亚太-首尔',
                            ],
                        ),
                        array(
                            'id'          => 'bucket',
                            'type'        => 'select',
                            'title'       => __('Bucket', 'mine-cloudvod'),//'存储桶',
                            'after'       => '<p><a onclick="javascript:mcv_sync_qiniu_buckets();">'.__('Sync Buckets List', 'mine-cloudvod').'</a></p>',//同步Bucket列表,
                            'options'     => $mcv_qiniu_bucketsList,
                            'default'     => ''
                        ),
                        [
                            'id'            => 'transcode',
                            'title'         => '转码',
                            'type'          => 'fieldset',
                            'fields'        => [
                                [
                                    'id'            => 'status',
                                    'title'         => __('Status', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Enable', 'mine-cloudvod'),
                                    'text_off'      => __('Disable', 'mine-cloudvod'),
                                    'default'       => false,
                                ],
                                array(
                                    'id'    => 'style',
                                    'type'  => 'text',
                                    'title' => '样式名称',
                                    'attributes'  => array(
                                        'autocomplete' => 'off'
                                    ),
                                    'dependency'    => [ 'status', '==', true ]
                                ),
                            ],
                        ],
                        array(
                            'id'         => 'domain',
                            'type'       => 'text',
                            'title'      => __('Domain', 'mine-cloudvod'),
                            'desc'       => __('CDN 加速域名或自定义域名（不含 http/https），例如 cdn.example.com。留空不重写附件 URL。', 'mine-cloudvod'),
                        ),
                        array(
                            'type'    => 'submessage',
                            'style'   => 'info',
                            'content' =>  __('将 WordPress 媒体库文件自动同步到七牛云 Kodo（仅支持 10MB 以内文件）。', 'mine-cloudvod'),
                        ),
                        array(
                            'id'      => 'sync_media',
                            'type'    => 'switcher',
                            'title'   => __('Sync Media to Kodo', 'mine-cloudvod'),
                            'text_on'  => __('Enable', 'mine-cloudvod'),
                            'text_off' => __('Disable', 'mine-cloudvod'),
                            'before'  => '<p>' . __('启用后，新上传的媒体文件会自动同步到七牛云 Kodo。', 'mine-cloudvod') . '</p>',
                            'default' => false,
                        ),
                        array(
                            'id'         => 'del_local',
                            'type'       => 'switcher',
                            'title'      => __('Delete Local Media', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'after'      => __('同步成功后，是否删除本地文件。', 'mine-cloudvod'),
                            'dependency' => array( 'sync_media', '==', true ),
                            'default'    => false,
                        ),
                        array(
                            'dependency' => array( 'sync_media', '==', true ),
                            'type'       => 'submessage',
                            'style'      => 'warning',
                            'content'    => '<a href="javascript:;" id="sync_media_to_qiniu" target="_self">' . __('点击同步媒体库文件到七牛云 Kodo', 'mine-cloudvod') . '</a>
                            <div id="sync_media_to_qiniu_result"></div>',
                        ),
                    ),
                ),
            )
          ) );
    }

    public function mcv_block_render($parsed_block, $enqueue = true){
        global $mcv_classes;
        $video = $mcv_classes->Dplayer->mcv_block_dplayer($parsed_block, $enqueue);

        return $video;
    }

    // ==================== Media Sync ====================

    /**
     * Batch sync existing media library files to Qiniu Kodo
     */
    public function syncMediaToKodo() {
        $media_files = get_posts( array(
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'meta_query'  => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 媒体同步筛选，业务必需
                [
                    'key'     => '_is_mcv_qiniu',
                    'compare' => 'NOT EXISTS',
                ]
            ],
            'numberposts' => 1,
        ) );

        $ret = [];
        if ( $media_files ) {
            foreach ( $media_files as $file ) {
                $metadata  = wp_get_attachment_metadata( $file->ID );
                $file_path = get_attached_file( $file->ID );
                $all_success = true;

                // Upload all thumbnail sizes first
                if ( ! empty( $metadata['sizes'] ) ) {
                    foreach ( $metadata['sizes'] as $size_name => $size ) {
                        $path = dirname( $file_path ) . '/' . $size['file'];
                        $upload_size = array(
                            'type' => $size['mime-type'],
                            'file' => $path,
                        );
                        if ( $this->uploadToQiniuWithRetry( $upload_size ) ) {
                            $this->delLocalFile( $upload_size );
                        } else {
                            $all_success = false;
                        }
                    }
                }
                // Upload original
                $upload_original = array(
                    'type' => get_post_mime_type( $file->ID ),
                    'file' => $file_path,
                );
                if ( $this->uploadToQiniuWithRetry( $upload_original ) ) {
                    $this->delLocalFile( $upload_original );
                } else {
                    $all_success = false;
                }

                // Upload true original (only exists when image was scaled down)
                if ( ! empty( $metadata['original_image'] ) ) {
                    $original_path = dirname( $file_path ) . '/' . $metadata['original_image'];
                    if ( $original_path !== $file_path ) {
                        $upload_true_original = array(
                            'type' => get_post_mime_type( $file->ID ),
                            'file' => $original_path,
                        );
                        if ( $this->uploadToQiniuWithRetry( $upload_true_original ) ) {
                            $this->delLocalFile( $upload_true_original );
                        } else {
                            $all_success = false;
                        }
                    }
                }

                if ( $all_success ) {
                    update_post_meta( $file->ID, '_is_mcv_qiniu', true );
                    $ret[] = esc_html( str_replace( ABSPATH, '', $file_path ) );
                } else {
                }
            }
        }
        return $ret;
    }

    // --- Upload hooks ---

    public function wpHandleUpload( $upload ) {
        if ( ! $this->uploadToQiniuWithRetry( $upload ) ) {
        }
        return $upload;
    }

    public function wpGenerateAttachmentMetadata( $metadata, $attachment_id ) {
        $mime_type  = get_post_mime_type( $attachment_id );
        $attached_file = get_attached_file( $attachment_id );
        if ( ! $attached_file ) {
            return $metadata;
        }
        $file_path  = dirname( $attached_file );

        // Non-image/audio/video files: metadata may be empty, just handle the original file
        if ( empty( $metadata['file'] ) ) {
            $upload_original = array(
                'type' => $mime_type,
                'file' => $attached_file,
            );
            if ( $this->uploadToQiniuWithRetry( $upload_original ) ) {
                $this->delLocalFile( $upload_original );
                update_post_meta( $attachment_id, '_is_mcv_qiniu', true );
            } else {
            }
            return $metadata;
        }

        $nfile      = explode( '/', $metadata['file'] );
        $nfile      = array_pop( $nfile );

        $all_success = true;

        // Upload scaled/original image (when different from original_image)
        if ( isset( $metadata['original_image'] ) && $nfile != $metadata['original_image'] ) {
            $path = $file_path . '/' . $nfile;
            $upload_scaled = array(
                'type' => $mime_type,
                'file' => $path,
            );
            if ( $this->uploadToQiniuWithRetry( $upload_scaled ) ) {
                $this->delLocalFile( $upload_scaled );
            } else {
                $all_success = false;
            }
        }

        // Upload all thumbnail sizes
        if ( ! empty( $metadata['sizes'] ) ) {
            foreach ( $metadata['sizes'] as $size_name => $size ) {
                $path = $file_path . '/' . $size['file'];
                $upload_size = array(
                    'type' => $size['mime-type'],
                    'file' => $path,
                );
                if ( $this->uploadToQiniuWithRetry( $upload_size ) ) {
                    $this->delLocalFile( $upload_size );
                } else {
                    $all_success = false;
                }
            }
        }

        // Upload and delete original file
        $original_name = $metadata['original_image'] ?? $nfile;
        $original_path = $file_path . '/' . $original_name;
        $upload_original = array(
            'type' => $mime_type,
            'file' => $original_path,
        );
        if ( $this->uploadToQiniuWithRetry( $upload_original ) ) {
            $this->delLocalFile( $upload_original );
        } else {
            $all_success = false;
        }

        if ( $all_success ) {
            update_post_meta( $attachment_id, '_is_mcv_qiniu', true );
        } else {
        }

        return $metadata;
    }

    /**
     * Upload a single file to Qiniu Kodo.
     *
     * @param array $upload  ['file' => path, 'type' => mime]
     * @return bool  true on success
     */
    private function uploadToQiniu( $upload ) {
        $ak     = MINECLOUDVOD_SETTINGS['qiniu']['sid'] ?? '';
        $sk     = MINECLOUDVOD_SETTINGS['qiniu']['skey'] ?? '';
        $bucket = MINECLOUDVOD_SETTINGS['qiniu']['bucket'] ?? '';
        if ( empty( $ak ) || empty( $sk ) || empty( $bucket ) ) {
            return false;
        }

        $key  = str_replace( ABSPATH, '', $upload['file'] );
        $max_size = 10 * 1024 * 1024;
        if ( filesize( $upload['file'] ) > $max_size ) {
            return false;
        }
        $body = file_get_contents( $upload['file'] );
        if ( $body === false ) {
            return false;
        }

        // Generate upload token
        $deadline = time() + 3600;
        $policy = json_encode( array(
            'scope'    => "{$bucket}:{$key}",
            'deadline' => $deadline,
        ) );
        $encodedPolicy = $this->qiniuBase64UrlEncode( $policy );
        $sign = hash_hmac( 'sha1', $encodedPolicy, $sk, true );
        $encodedSign = $this->qiniuBase64UrlEncode( $sign );
        $token = "{$ak}:{$encodedSign}:{$encodedPolicy}";

        // Upload via curl (multipart form)
        $uploadUrl = $this->getQiniuUploadUrl();
        $contentType = $upload['type'] ?? 'application/octet-stream';
        $fileName = basename( $upload['file'] );

        $postFields = array(
            'token' => $token,
            'key'   => $key,
            'file'  => new \CURLFile( $upload['file'], $contentType, $fileName ),
        );

        $response = wp_remote_post( $uploadUrl, array(
            'body'      => $postFields,
            'timeout'   => 60,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $http_code = wp_remote_retrieve_response_code( $response );

        if ( $http_code != 200 ) {
            return false;
        }

        $this->upload = $upload;
        return true;
    }

    /**
     * Upload to Qiniu Kodo with retry for transient network failures.
     */
    private function uploadToQiniuWithRetry( $upload, $max_retries = 3 ) {
        for ( $i = 0; $i < $max_retries; $i++ ) {
            if ( $this->uploadToQiniu( $upload ) ) {
                return true;
            }
            if ( $i < $max_retries - 1 ) {
                $file = basename( $upload['file'] );
                sleep( 1 );
            }
        }
        return false;
    }

    // --- Delete hook ---

    public function deleteAttachment( $post_id ) {
        $meta = wp_get_attachment_metadata( $post_id );
        if ( ! $meta || empty( $meta['file'] ) ) {
            return;
        }

        $dir       = wp_get_upload_dir();
        $file_path = str_replace( ABSPATH, '', $dir['basedir'] . '/' . $meta['file'] );
        $file_dir  = dirname( $file_path );

        $keys = array( $file_path );

        if ( ! empty( $meta['original_image'] ) ) {
            $keys[] = $file_dir . '/' . $meta['original_image'];
        }
        if ( ! empty( $meta['sizes'] ) ) {
            foreach ( $meta['sizes'] as $size ) {
                $keys[] = $file_dir . '/' . $size['file'];
            }
        }

        $this->deleteQiniuFiles( $keys );
    }

    /**
     * Delete files from Qiniu Kodo (batch via rs.qiniu.com)
     */
    private function deleteQiniuFiles( $keys ) {
        $ak     = MINECLOUDVOD_SETTINGS['qiniu']['sid'] ?? '';
        $sk     = MINECLOUDVOD_SETTINGS['qiniu']['skey'] ?? '';
        $bucket = MINECLOUDVOD_SETTINGS['qiniu']['bucket'] ?? '';
        if ( empty( $ak ) || empty( $sk ) || empty( $bucket ) ) {
            return;
        }

        // Build batch operations: op=/delete/{EncodedEntry}
        $ops = [];
        foreach ( $keys as $key ) {
            $entry = $this->qiniuBase64UrlEncode( "{$bucket}:{$key}" );
            $ops[] = 'op=/delete/' . $entry;
        }
        $body = implode( '&', $ops );

        $path = '/batch';
        $auth = $this->qiniuMgmtToken( $path, $body );

        $response = wp_remote_post( 'https://rs.qiniu.com' . $path, array(
            'headers'   => array(
                'Authorization' => $auth,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'      => $body,
            'timeout'   => 30,
            'sslverify' => true,
        ) );
    }

    // --- URL rewriting ---

    public function rewriteUrl( $url, $post_id ) {
        $_is_mcv_qiniu = get_post_meta( $post_id, '_is_mcv_qiniu', true );
        if ( $_is_mcv_qiniu ) {
            $domain = $this->getMediaSyncDomain();
            if ( $domain ) {
                $url = ( is_ssl() ? 'https' : 'http' ) . '://' . $domain . '/' . str_replace( ABSPATH, '', get_attached_file( $post_id ) );
            }
        }
        return $url;
    }

    public function urlToPostid( $post_id, $url ) {
        if ( ! $post_id ) {
            $domain = $this->getMediaSyncDomain();
            if ( $domain && strpos( $url, $domain ) !== false ) {
                global $wpdb;
                $dir  = wp_get_upload_dir();
                $parsed = wp_parse_url( $url );
                $url_path = $parsed['path'] ?? '';
                $base = '/' . str_replace( ABSPATH, '', $dir['basedir'] ) . '/';
                $path = substr( $url_path, strlen( $base ) );

                $results = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 按文件路径精确反查附件，刻意性能优化
                    "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
                    $path
                ) );

                if ( $results ) {
                    $post_id = (int) reset( $results )->post_id;
                    if ( count( $results ) > 1 ) {
                        foreach ( $results as $result ) {
                            if ( $path === $result->meta_value ) {
                                $post_id = (int) $result->post_id;
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $post_id;
    }

    // --- Helpers ---

    private function delLocalFile( $upload ) {
        if ( MINECLOUDVOD_SETTINGS['qiniu']['del_local'] ?? false ) {
            @wp_delete_file( $upload['file'] );
            $this->upload = null;
        }
    }

    /**
     * URL-safe base64 encoding (Qiniu standard)
     */
    private function qiniuBase64UrlEncode( $data ) {
        return str_replace( array( '+', '/' ), array( '-', '_' ), base64_encode( $data ) );
    }

    /**
     * Get Qiniu upload URL based on region
     */
    private function getQiniuUploadUrl() {
        $region = MINECLOUDVOD_SETTINGS['qiniu']['region'] ?? 'z0';
        $suffix = $region === 'z0' ? '' : '-' . $region;
        return 'https://upload' . $suffix . '.qiniup.com';
    }

    /**
     * Generate Qiniu management API token (QBox)
     */
    private function qiniuMgmtToken( $path, $body = '' ) {
        $ak = MINECLOUDVOD_SETTINGS['qiniu']['sid'] ?? '';
        $sk = MINECLOUDVOD_SETTINGS['qiniu']['skey'] ?? '';

        $data = "{$path}\n{$body}";
        $sign = hash_hmac( 'sha1', $data, $sk, true );
        $encodedSign = $this->qiniuBase64UrlEncode( $sign );

        return "QBox {$ak}:{$encodedSign}";
    }

    /**
     * Get the domain for media URL rewriting.
     */
    private function getMediaSyncDomain() {
        return MINECLOUDVOD_SETTINGS['qiniu']['domain'] ?? '';
    }
}
