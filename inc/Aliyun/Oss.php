<?php
namespace MineCloudvod\Aliyun;

class Oss{
    private $_wpcvApi;
    private $upload;

    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;
        add_action('wp_ajax_mcv_asyc_alioss_buckets', array($this, 'mcv_asyc_alioss_buckets'));
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'aliyun_admin_options' ) );

        // Media sync hooks
        if ( MINECLOUDVOD_SETTINGS['alivod_oss']['sync_media'] ?? false ) {
            add_filter( 'wp_handle_upload', [ $this, 'wpHandleUpload' ] );
            add_filter( 'wp_generate_attachment_metadata', [ $this, 'wpGenerateAttachmentMetadata' ], 10, 2 );
            add_filter( 'wp_get_attachment_url', [ $this, 'rewriteUrl' ], 10, 2 );
            add_filter( 'attachment_url_to_postid', [ $this, 'urlToPostid' ], 10, 2 );
        }
        // Always register delete hook — clean up remote files even if sync was later disabled
        add_action( 'delete_attachment', [ $this, 'deleteAttachment' ] );
    }

    public function register_routes() {
        register_rest_route( 'mine-cloudvod/v1', '/aliyun/oss/sync_media', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'syncMediaToOss' ],
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ] );
    }

    public function get_mediaUrl($objcet, $bucket=''){
        $data = array(
            'bucket'  => $bucket,
            'object' => $objcet,
            'mode' => 'alioss'
        );
        if( isset( MINECLOUDVOD_SETTINGS['alivod_oss']['domain'] ) && MINECLOUDVOD_SETTINGS['alivod_oss']['domain'] ){
            $data['domain']  = MINECLOUDVOD_SETTINGS['alivod_oss']['domain'];
            $data['iscname'] = 1;
        }
        $playinfo = $this->_wpcvApi->call('geturl', $data);
        return $playinfo;
    }

    public function aliyun_admin_options(){
        $prefix = 'mcv_settings';
        $ajaxUrl = admin_url("admin-ajax.php");
        $mcv_alioss_bucketsList = array('' => __('Please sync Bukcets List first', 'mine-cloudvod'));//'请先同步转码模板');
        if($tctc = get_option('mcv_alioss_bucketsList')){
            $mcv_alioss_bucketsList = array();
            foreach($tctc as $tc){
                $mcv_alioss_bucketsList[$tc[0]] =  $tc[0];
            }
        }

        \MCSF::createSection( $prefix, array(
        'parent'     => 'aliyunvod',
        'title' => __('Aliyun OSS', 'mine-cloudvod'),//'阿里云OSS',
        'icon'   => 'far fa-file-video',
        'fields' => array(
            array(
            'type'    => 'submessage',
            'style'   => 'warning',
            'content' => __('By default, Alibaba Cloud OSS is charged after the end of the hour, and it can also be found on <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">Alibaba Cloud OSS Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'),//'<p>阿里云视频点播默认是时结后收费模式，也可以在 <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">阿里云平台</a> 购买相应的资源包消费</p>',
            ),
            array(
            'id'        => 'alivod',
            'type'      => 'fieldset',
            'title'     => __('Aliyun OSS', 'mine-cloudvod'),//'阿里云对象存储 OSS',
            'fields'    => array(
                array(
                'id'          => 'buckets',
                'type'        => 'select',
                'title'       => __('Bucket', 'mine-cloudvod'),//'存储桶',
                'after'       => '<p><a href="javascript:mcv_sync_alioss_buckets();">'.__('Sync Buckets List', 'mine-cloudvod').'</a></p>',//同步Bucket列表,
                'options'     => $mcv_alioss_bucketsList,
                'default'     => ''
                ),
            ),
            ),
            array(
                'id'        => 'alivod_oss',
                'type'      => 'fieldset',
                'title'     => '',
                'fields'    => array(
                    array(
                        'id'         => 'domain',
                        'type'       => 'text',
                        'title'      => __('Domain', 'mine-cloudvod'),
                        'desc'       => __('CDN 加速域名或自定义域名（不含 http/https）。留空自动使用 OSS 默认域名。', 'mine-cloudvod'),
                    ),
                ),
            ),
            array(
                'type'    => 'submessage',
                'style'   => 'success',
                'content' => __('将 WordPress 媒体库文件自动同步到阿里云 OSS（仅支持 10MB 以内文件）。', 'mine-cloudvod'),
            ),
            array(
                'id'        => 'alivod_oss',
                'type'      => 'fieldset',
                'title'     => '',
                'fields'    => array(
                    array(
                        'id'      => 'sync_media',
                        'type'    => 'switcher',
                        'title'   => __('Sync Media to OSS', 'mine-cloudvod'),
                        'text_on'  => __('Enable', 'mine-cloudvod'),
                        'text_off' => __('Disable', 'mine-cloudvod'),
                        'before'  => '<p>' . __('启用后，新上传的媒体文件会自动同步到阿里云 OSS。', 'mine-cloudvod') . '</p>',
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
                        'content'    => '<a href="javascript:;" id="sync_media_to_alioss" target="_self">' . __('点击同步媒体库文件到阿里云 OSS', 'mine-cloudvod') . '</a>
                        <div id="sync_media_to_alioss_result"></div>',
                    ),
                ),
            ),

        )
        ) );
    }
    
    public function mcv_asyc_alioss_buckets(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_alioss_buckets')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('bucket'=>'mcv','mode' => 'alioss');
        $buckets = $this->_wpcvApi->call('buckets', $data);
        update_option('mcv_alioss_bucketsList', $buckets['data']);

        // Cache region per bucket
        $regions = array();
        if ( ! empty( $buckets['data'] ) ) {
            foreach ( $buckets['data'] as $bucket_info ) {
                if ( ! empty( $bucket_info[0] ) && ! empty( $bucket_info[1] ) ) {
                    $regions[ $bucket_info[0] ] = $bucket_info[1];
                }
            }
        }
        if ( $regions ) {
            update_option( 'mcv_alioss_regions', $regions );
        }
        echo wp_json_encode($buckets);
        exit;
    }

    // ==================== Media Sync ====================

    public function syncMediaToOss() {
        $media_files = get_posts( array(
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'meta_query'  => [[ 'key' => '_is_mcv_alioss', 'compare' => 'NOT EXISTS' ]], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 媒体同步筛选，业务必需
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
                        $upload_size = array( 'type' => $size['mime-type'], 'file' => $path );
                        if ( $this->uploadToOssWithRetry( $upload_size ) ) {
                            $this->delLocalFile( $upload_size );
                        } else {
                            $all_success = false;
                        }
                    }
                }
                $upload_original = array( 'type' => get_post_mime_type( $file->ID ), 'file' => $file_path );
                if ( $this->uploadToOssWithRetry( $upload_original ) ) {
                    $this->delLocalFile( $upload_original );
                } else {
                    $all_success = false;
                }

                // Upload true original (only exists when image was scaled down)
                if ( ! empty( $metadata['original_image'] ) ) {
                    $original_path = dirname( $file_path ) . '/' . $metadata['original_image'];
                    if ( $original_path !== $file_path ) {
                        $upload_true_original = array( 'type' => get_post_mime_type( $file->ID ), 'file' => $original_path );
                        if ( $this->uploadToOssWithRetry( $upload_true_original ) ) {
                            $this->delLocalFile( $upload_true_original );
                        } else {
                            $all_success = false;
                        }
                    }
                }

                if ( $all_success ) {
                    update_post_meta( $file->ID, '_is_mcv_alioss', true );
                    $ret[] = esc_html( str_replace( ABSPATH, '', $file_path ) );
                } else {
                }
            }
        }
        return $ret;
    }

    public function wpHandleUpload( $upload ) {
        if ( ! $this->uploadToOssWithRetry( $upload ) ) {
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
            if ( $this->uploadToOssWithRetry( $upload_original ) ) {
                $this->delLocalFile( $upload_original );
                update_post_meta( $attachment_id, '_is_mcv_alioss', true );
            } else {
            }
            return $metadata;
        }

        $nfile      = explode( '/', $metadata['file'] );
        $nfile      = array_pop( $nfile );

        $all_success = true;

        if ( isset( $metadata['original_image'] ) && $nfile != $metadata['original_image'] ) {
            $upload_scaled = array( 'type' => $mime_type, 'file' => $file_path . '/' . $nfile );
            if ( $this->uploadToOssWithRetry( $upload_scaled ) ) {
                $this->delLocalFile( $upload_scaled );
            } else {
                $all_success = false;
            }
        }
        if ( ! empty( $metadata['sizes'] ) ) {
            foreach ( $metadata['sizes'] as $size_name => $size ) {
                $path = $file_path . '/' . $size['file'];
                $upload_size = array( 'type' => $size['mime-type'], 'file' => $path );
                if ( $this->uploadToOssWithRetry( $upload_size ) ) {
                    $this->delLocalFile( $upload_size );
                } else {
                    $all_success = false;
                }
            }
        }

        // Upload and delete original file
        $original_name = $metadata['original_image'] ?? $nfile;
        $original_path = $file_path . '/' . $original_name;
        $upload_original = array( 'type' => $mime_type, 'file' => $original_path );
        if ( $this->uploadToOssWithRetry( $upload_original ) ) {
            $this->delLocalFile( $upload_original );
        } else {
            $all_success = false;
        }

        if ( $all_success ) {
            update_post_meta( $attachment_id, '_is_mcv_alioss', true );
        } else {
        }

        return $metadata;
    }

    /**
     * Upload a single file to Aliyun OSS.
     *
     * @param array $upload  ['file' => path, 'type' => mime]
     * @return bool  true on success
     */
    private function uploadToOss( $upload ) {
        $key  = str_replace( ABSPATH, '', $upload['file'] );
        $max_size = 10 * 1024 * 1024;
        if ( filesize( $upload['file'] ) > $max_size ) {
            return false;
        }
        $body = file_get_contents( $upload['file'] );
        if ( $body === false ) {
            return false;
        }
        $ct = $upload['type'] ?? 'application/octet-stream';
        if ( $this->ossSignedRequest( 'PUT', $key, $body, $ct ) ) {
            $this->upload = $upload;
            return true;
        }
        return false;
    }

    /**
     * Upload to OSS with retry for transient network failures.
     */
    private function uploadToOssWithRetry( $upload, $max_retries = 3 ) {
        for ( $i = 0; $i < $max_retries; $i++ ) {
            if ( $this->uploadToOss( $upload ) ) {
                return true;
            }
            if ( $i < $max_retries - 1 ) {
                $file = basename( $upload['file'] );
                sleep( 1 );
            }
        }
        return false;
    }

    public function deleteAttachment( $post_id ) {
        $meta = wp_get_attachment_metadata( $post_id );
        if ( ! $meta || empty( $meta['file'] ) ) {
            return;
        }
        $dir       = wp_get_upload_dir();
        $file_path = str_replace( ABSPATH, '', $dir['basedir'] . '/' . $meta['file'] );
        $file_dir  = dirname( $file_path );
        $keys = array( $file_path );
        if ( ! empty( $meta['original_image'] ) ) $keys[] = $file_dir . '/' . $meta['original_image'];
        if ( ! empty( $meta['sizes'] ) ) {
            foreach ( $meta['sizes'] as $size ) $keys[] = $file_dir . '/' . $size['file'];
        }
        foreach ( $keys as $k ) {
            if ( ! $this->ossSignedRequest( 'DELETE', $k ) ) {
            }
        }
    }

    public function rewriteUrl( $url, $post_id ) {
        if ( get_post_meta( $post_id, '_is_mcv_alioss', true ) ) {
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
                    "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s", $path
                ) );
                if ( $results ) {
                    $post_id = (int) reset( $results )->post_id;
                    if ( count( $results ) > 1 ) {
                        foreach ( $results as $r ) { if ( $path === $r->meta_value ) { $post_id = (int) $r->post_id; break; } }
                    }
                }
            }
        }
        return $post_id;
    }

    private function delLocalFile( $upload ) {
        if ( MINECLOUDVOD_SETTINGS['alivod_oss']['del_local'] ?? false ) {
            @wp_delete_file( $upload['file'] );
            $this->upload = null;
        }
    }

    private function getMediaSyncDomain() {
        $domain = MINECLOUDVOD_SETTINGS['alivod_oss']['domain'] ?? '';
        if ( $domain ) return $domain;
        $bucket = MINECLOUDVOD_SETTINGS['alivod']['buckets'] ?? '';
        $cached = get_option( 'mcv_alioss_regions', array() );
        $region = $cached[ $bucket ] ?? '';
        return ( $bucket && $region ) ? "{$bucket}.oss-{$region}.aliyuncs.com" : '';
    }

    // --- OSS Signature (Authorization: OSS AccessKeyId:Signature) ---

    private function ossSignedRequest( $method, $key, $body = null, $contentType = null ) {
        $akId     = MINECLOUDVOD_SETTINGS['alivod']['accessKeyID'] ?? '';
        $akSecret = MINECLOUDVOD_SETTINGS['alivod']['accessKeySecret'] ?? '';
        $bucket   = MINECLOUDVOD_SETTINGS['alivod']['buckets'] ?? '';
        $cached   = get_option( 'mcv_alioss_regions', array() );
        $region   = $cached[ $bucket ] ?? '';

        if ( empty( $akId ) || empty( $akSecret ) || empty( $bucket ) ) {
            return false;
        }
        if ( empty( $region ) ) {
            return false;
        }

        $host   = "{$bucket}.oss-{$region}.aliyuncs.com";
        $method = strtoupper( $method );
        $uri    = '/' . ltrim( $key, '/' );
        $date   = gmdate( 'D, d M Y H:i:s \G\M\T' );
        $ct     = $contentType ?? '';

        // StringToSign: VERB\nContent-MD5\nContent-Type\nDate\nCanonicalizedOSSHeaders\nCanonicalizedResource
        $stringToSign = "{$method}\n\n{$ct}\n{$date}\n/{$bucket}{$uri}";
        $signature = base64_encode( hash_hmac( 'sha1', $stringToSign, $akSecret, true ) );
        $auth = "OSS {$akId}:{$signature}";

        $url = "https://{$host}{$uri}";
        $headers = array(
            'Authorization' => $auth,
            'Date'          => $date,
            'Host'          => $host,
        );
        if ( $body !== null && $ct ) {
            $headers['Content-Type'] = $ct;
        }

        $args = array(
            'method'    => $method,
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
        if ( $method === 'DELETE' && $http_code === 404 ) {
            return true;
        }
        if ( $http_code < 200 || $http_code >= 300 ) {
            return false;
        }
        return true;
    }
}
