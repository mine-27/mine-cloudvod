<?php
namespace MineCloudvod\Qcloud;

class Cos
{
    private $_wpcvApi;
    private $upload;

    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;
        add_action('wp_ajax_mcv_asyc_tccos_buckets', array($this, 'mcv_asyc_tccos_buckets'));
        add_action('rest_api_init', [$this, 'register_routes']);

        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'qcloud_admin_options' ) );

        // Media sync hooks
        if ( MINECLOUDVOD_SETTINGS['tcvod_cos']['sync_media'] ?? false ) {
            add_filter( 'wp_handle_upload', [ $this, 'wpHandleUpload' ] );
            add_filter( 'wp_generate_attachment_metadata', [ $this, 'wpGenerateAttachmentMetadata' ], 10, 2 );
            add_filter( 'wp_get_attachment_url', [ $this, 'rewriteUrl' ], 10, 2 );
            add_filter( 'attachment_url_to_postid', [ $this, 'urlToPostid' ], 10, 2 );
        }
        // Always register delete hook — clean up remote files even if sync was later disabled
        add_action( 'delete_attachment', [ $this, 'deleteAttachment' ] );
    }

    public function register_routes() {
        register_rest_route( 'mine-cloudvod/v1', '/qcloud/cos/sync_media', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'syncMediaToCos' ],
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ] );
    }

    public function qcloud_admin_options(){
        $prefix = 'mcv_settings';
        $mcv_alioss_bucketsList = array('' => __('Please sync Bukcets List first', 'mine-cloudvod'));//'请先同步转码模板');
        if($tctc = get_option('mcv_tccos_bucketsList')){
            $mcv_alioss_bucketsList = array();
            foreach($tctc as $tc){
                $mcv_alioss_bucketsList[$tc[0]] =  $tc[0];
            }
        }

        \MCSF::createSection( $prefix, array(
            'parent'     => 'tencentvod',
            'title'  => __('Tencent COS', 'mine-cloudvod'),//'腾讯云COS',
            'icon'   => 'far fa-file-video',
            'fields' => array(
                array(
                'type'    => 'submessage',
                'style'   => 'warning',
                'content' => __('By default, Tencent Cloud VOD is charged after the end of the day, and it can also be found on <a href="https://curl.qcloud.com/F8Ad6KaX" target="_blank">Tencent Cloud VOD Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'),//'<p>腾讯云点播默认是日结后收费模式，也可以在 <a href="https://curl.qcloud.com/F8Ad6KaX" target="_blank">腾讯云点播平台</a> 购买相应的资源包消费</p>',
                ),
                array(
                'id'        => 'tcvod',
                'type'      => 'fieldset',
                'title'     => '',
                'fields'    => array(
                    array(
                    'id'          => 'buckets',
                    'type'        => 'select',
                    'title'       => __('Bucket', 'mine-cloudvod'),//'转码模板',
                    'after'       => '<p><a href="javascript:mcv_sync_tccos_buckets();">'.__('Sync Buckets List', 'mine-cloudvod').'</a></p>',//同步Bucket列表,
                    'options'     => $mcv_alioss_bucketsList,
                    'default'     => ''
                    ),
                ),
                ),
                array(
                    'id'        => 'tcvod_cos',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'         => 'domain',
                            'type'       => 'text',
                            'title'      => __('Domain', 'mine-cloudvod'),
                            'desc'       => __('CDN 加速域名或自定义域名（不含 http/https），例如 cdn.example.com。留空使用COS默认域名。', 'mine-cloudvod'),
                        ),
                    ),
                ),
                array(
                    'type'    => 'submessage',
                    'style'   => 'success',
                    'content' => __('将 WordPress 媒体库文件自动同步到腾讯云 COS（仅支持 10MB 以内文件）。', 'mine-cloudvod'),
                ),
                array(
                    'id'        => 'tcvod_cos',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'      => 'sync_media',
                            'type'    => 'switcher',
                            'title'   => __('Sync Media to COS', 'mine-cloudvod'),
                            'text_on'  => __('Enable', 'mine-cloudvod'),
                            'text_off' => __('Disable', 'mine-cloudvod'),
                            'before'  => '<p>' . __('启用后，新上传的媒体文件会自动同步到腾讯云 COS。', 'mine-cloudvod') . '</p>',
                            'default' => false,
                        ),
                        array(
                            'id'         => 'domain',
                            'type'       => 'text',
                            'title'      => __('Domain', 'mine-cloudvod'),
                            'desc'       => __('CDN 加速域名或自定义域名（不含 http/https），例如 cdn.example.com。留空使用COS默认域名。', 'mine-cloudvod'),
                            'dependency' => array( 'sync_media', '==', true ),
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
                            'content'    => '<a href="javascript:;" id="sync_media_to_tccos" target="_self">' . __('点击同步媒体库文件到腾讯云 COS', 'mine-cloudvod') . '</a>
                            <div id="sync_media_to_tccos_result"></div>',
                        ),
                    ),
                ),
            )
            ) );
    }

    public function get_mediaUrl($objcet, $bucket){
        $data = array(
            'bucket'  => $bucket,
            'object' => $objcet,
            'mode' => 'tccos'
        );
        if( isset( MINECLOUDVOD_SETTINGS['tcvod_cos']['domain'] ) && MINECLOUDVOD_SETTINGS['tcvod_cos']['domain'] ){
            $data['domain']  = MINECLOUDVOD_SETTINGS['tcvod_cos']['domain'];
            $data['iscname'] = 1;
        }
        $playinfo = $this->_wpcvApi->call('geturl', $data);
        return $playinfo;
    }
    
    public function mcv_asyc_tccos_buckets(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_tccos_buckets')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('bucket'=>'mcv','mode' => 'tccos');
        $buckets = $this->_wpcvApi->call('buckets', $data); 
        update_option('mcv_tccos_bucketsList', $buckets['data']);

        // Cache region per bucket (format: [name, region, ...])
        $regions = array();
        if ( ! empty( $buckets['data'] ) ) {
            foreach ( $buckets['data'] as $bucket_info ) {
                if ( ! empty( $bucket_info[0] ) && ! empty( $bucket_info[1] ) ) {
                    $regions[ $bucket_info[0] ] = $bucket_info[1];
                }
            }
        }
        if ( $regions ) {
            update_option( 'mcv_tccos_regions', $regions );
        }
        echo wp_json_encode($buckets);
        exit;
    }

    // ==================== Media Sync ====================

    /**
     * Batch sync existing media library files to Tencent COS
     */
    public function syncMediaToCos() {
        $media_files = get_posts( array(
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'meta_query'  => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 媒体同步筛选，业务必需
                [
                    'key'     => '_is_mcv_tccos',
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

                if ( ! empty( $metadata['sizes'] ) ) {
                    foreach ( $metadata['sizes'] as $size_name => $size ) {
                        $path = dirname( $file_path ) . '/' . $size['file'];
                        $upload_size = array(
                            'type' => $size['mime-type'],
                            'file' => $path,
                        );
                        if ( $this->uploadToCosWithRetry( $upload_size ) ) {
                            $this->delLocalFile( $upload_size );
                        } else {
                            $all_success = false;
                        }
                    }
                }
                $upload_original = array(
                    'type' => get_post_mime_type( $file->ID ),
                    'file' => $file_path,
                );
                if ( $this->uploadToCosWithRetry( $upload_original ) ) {
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
                        if ( $this->uploadToCosWithRetry( $upload_true_original ) ) {
                            $this->delLocalFile( $upload_true_original );
                        } else {
                            $all_success = false;
                        }
                    }
                }

                if ( $all_success ) {
                    update_post_meta( $file->ID, '_is_mcv_tccos', true );
                    $ret[] = esc_html( str_replace( ABSPATH, '', $file_path ) );
                } else {
                }
            }
        }
        return $ret;
    }

    // --- Upload hooks ---

    public function wpHandleUpload( $upload ) {
        if ( ! $this->uploadToCosWithRetry( $upload ) ) {
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
            if ( $this->uploadToCosWithRetry( $upload_original ) ) {
                $this->delLocalFile( $upload_original );
                update_post_meta( $attachment_id, '_is_mcv_tccos', true );
            } else {
            }
            return $metadata;
        }

        $nfile      = explode( '/', $metadata['file'] );
        $nfile      = array_pop( $nfile );

        $all_success = true;

        if ( isset( $metadata['original_image'] ) && $nfile != $metadata['original_image'] ) {
            $path = $file_path . '/' . $nfile;
            $upload_scaled = array(
                'type' => $mime_type,
                'file' => $path,
            );
            if ( $this->uploadToCosWithRetry( $upload_scaled ) ) {
                $this->delLocalFile( $upload_scaled );
            } else {
                $all_success = false;
            }
        }

        if ( ! empty( $metadata['sizes'] ) ) {
            foreach ( $metadata['sizes'] as $size_name => $size ) {
                $path = $file_path . '/' . $size['file'];
                $upload_size = array(
                    'type' => $size['mime-type'],
                    'file' => $path,
                );
                if ( $this->uploadToCosWithRetry( $upload_size ) ) {
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
        if ( $this->uploadToCosWithRetry( $upload_original ) ) {
            $this->delLocalFile( $upload_original );
        } else {
            $all_success = false;
        }

        if ( $all_success ) {
            update_post_meta( $attachment_id, '_is_mcv_tccos', true );
        } else {
        }

        return $metadata;
    }

    /**
     * Upload a single file to Tencent COS.
     *
     * @param array $upload  ['file' => path, 'type' => mime]
     * @return bool  true on success
     */
    private function uploadToCos( $upload ) {
        $key  = str_replace( ABSPATH, '', $upload['file'] );
        $max_size = 10 * 1024 * 1024;
        if ( filesize( $upload['file'] ) > $max_size ) {
            return false;
        }
        $body = file_get_contents( $upload['file'] );
        if ( $body === false ) {
            return false;
        }

        $contentType = $upload['type'] ?? 'application/octet-stream';
        $result = $this->cosSignedRequest( 'PUT', $key, $body, $contentType );

        if ( $result ) {
            $this->upload = $upload;
            return true;
        }

        return false;
    }

    /**
     * Upload to COS with retry for transient network failures.
     */
    private function uploadToCosWithRetry( $upload, $max_retries = 3 ) {
        for ( $i = 0; $i < $max_retries; $i++ ) {
            if ( $this->uploadToCos( $upload ) ) {
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

        $this->deleteCosFiles( $keys );
    }

    private function deleteCosFiles( $keys ) {
        foreach ( $keys as $key ) {
            if ( ! $this->cosSignedRequest( 'DELETE', $key ) ) {
            }
        }
    }

    // --- URL rewriting ---

    public function rewriteUrl( $url, $post_id ) {
        $_is_mcv_tccos = get_post_meta( $post_id, '_is_mcv_tccos', true );
        if ( $_is_mcv_tccos ) {
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
        if ( MINECLOUDVOD_SETTINGS['tcvod_cos']['del_local'] ?? false ) {
            @wp_delete_file( $upload['file'] );
            $this->upload = null;
        }
    }

    private function getMediaSyncDomain() {
        $domain = MINECLOUDVOD_SETTINGS['tcvod_cos']['domain'] ?? '';
        if ( $domain ) {
            return $domain;
        }
        $bucket = MINECLOUDVOD_SETTINGS['tcvod']['buckets'] ?? '';
        $cachedRegions = get_option( 'mcv_tccos_regions', array() );
        $region = $cachedRegions[ $bucket ] ?? '';
        if ( $bucket && $region ) {
            return "{$bucket}.cos.{$region}.myqcloud.com";
        }
        return '';
    }

    // --- Tencent COS Signature (sha1 HMAC) ---

    /**
     * Make a signed COS API request using q-sign-algorithm=sha1.
     */
    private function cosSignedRequest( $method, $key, $body = null, $contentType = null ) {
        $secretId  = MINECLOUDVOD_SETTINGS['tcvod']['sid'] ?? '';
        $secretKey = MINECLOUDVOD_SETTINGS['tcvod']['skey'] ?? '';
        $bucket    = MINECLOUDVOD_SETTINGS['tcvod']['buckets'] ?? '';

        // Region comes from cached bucket sync data
        $cachedRegions = get_option( 'mcv_tccos_regions', array() );
        $region = $cachedRegions[ $bucket ] ?? '';

        if ( empty( $secretId ) || empty( $secretKey ) || empty( $bucket ) ) {
            return false;
        }
        if ( empty( $region ) ) {
            return false;
        }

        $host   = "{$bucket}.cos.{$region}.myqcloud.com";
        $method = strtolower( $method );
        $uri    = '/' . ltrim( $key, '/' );

        $now     = time();
        $expire  = $now + 3600;
        $signTime = "{$now};{$expire}";
        $keyTime  = $signTime;

        // Signed headers
        $signHeaders = array( 'host' => $host );
        if ( $body !== null && $contentType ) {
            $signHeaders['content-type'] = $contentType;
        }

        ksort( $signHeaders );
        $headerList = implode( ';', array_keys( $signHeaders ) );

        $httpHeaders = '';
        foreach ( $signHeaders as $k => $v ) {
            $httpHeaders .= strtolower( $k ) . '=' . rawurlencode( $v ) . '&';
        }
        $httpHeaders = rtrim( $httpHeaders, '&' );

        $httpParameters = '';
        $paramList      = '';

        // Build string to sign
        $httpString = "{$method}\n{$uri}\n{$httpParameters}\n{$httpHeaders}\n";
        $stringToSign = "sha1\n{$signTime}\n" . sha1( $httpString ) . "\n";

        $signKey  = hash_hmac( 'sha1', $keyTime, $secretKey );
        $signature = hash_hmac( 'sha1', $stringToSign, $signKey );

        $auth = "q-sign-algorithm=sha1"
            . "&q-ak={$secretId}"
            . "&q-sign-time={$signTime}"
            . "&q-key-time={$keyTime}"
            . "&q-header-list={$headerList}"
            . "&q-url-param-list={$paramList}"
            . "&q-signature={$signature}";

        // Send via wp_remote_request (WP HTTP API).
        $url = "https://{$host}{$uri}";

        $headers = array(
            'Authorization' => $auth,
            'Host'          => $host,
        );
        if ( $body !== null ) {
            $headers['Content-Type'] = $contentType;
        }

        $args = array(
            'method'    => strtoupper( $method ),
            'headers'   => $headers,
            'timeout'   => 60,
            'sslverify' => true,
        );

        if ( $body !== null ) {
            $args['body'] = $body;
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $http_code = wp_remote_retrieve_response_code( $response );

        // DELETE: 404 means file already gone = success
        if ( strtoupper( $method ) === 'DELETE' && $http_code === 404 ) {
            return true;
        }

        if ( $http_code < 200 || $http_code >= 300 ) {
            return false;
        }

        return true;
    }
}
