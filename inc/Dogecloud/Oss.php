<?php
namespace MineCloudvod\Dogecloud;

class Oss{
    private $mediaTypes = array(
        "jpg"=> "image/jpeg",
        "jpeg"=> "image/jpeg",
        "jpe"=> "image/jpeg",
        "gif"=> "image/gif",
        "png"=> "image/png",
        "bmp"=> "image/bmp",
        "tiff"=> "image/tiff",
        "tif"=> "image/tiff",
        "ico"=> "image/x-icon",
        "vtt"=> "text/vtt",
    );
    private $upload;
    public function __construct(){
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'doge_admin_options' ) );

        if(MINECLOUDVOD_SETTINGS['doge_oss']['sync_media'] ?? false){
            add_filter( 'wp_handle_upload', [ $this, 'wpHandleUpload' ] );

	        add_filter( 'wp_generate_attachment_metadata', [ $this, 'wpGenerateAttachmentMetadata' ], 10, 2 );
            add_filter( 'wp_get_attachment_url', [ $this, 'doge_media_url' ], 10, 2 );
            add_filter( 'attachment_url_to_postid', [ $this, 'url_to_postid' ], 10, 2 );

            add_action('rest_api_init', [$this, 'register_routes']);
            //rename
            // if( MINECLOUDVOD_SETTINGS['doge_oss']['rename'] ){
            //     add_filter( 'sanitize_file_name', [ $this, 'renameDogeFile' ] );
            // }
        }
        // Always register delete hook — clean up remote files even if sync was later disabled
        add_action('delete_attachment', [ $this, 'deleteAttachment' ] );
        
    }
    public function register_routes(){
        register_rest_route("mine-cloudvod/v1", '/doge/oss/sync_media', [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'sync_media_to_oss'],
                'permission_callback' => function() {return current_user_can('manage_options');},
                'args'                => [
                ]
        ]);
    }

    public function sync_media_to_oss(){
        $media_files = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 媒体同步筛选，业务必需
                [
                    'key' => '_is_mcv_doge',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'numberposts' => 1,
        ));
        $ret=[];
        if($media_files){
            foreach ($media_files as $file) {
                $metadata = wp_get_attachment_metadata($file->ID);
                $file_path = get_attached_file($file->ID);
                $all_success = true;

                if (!empty($metadata['sizes'])) {
                    foreach ($metadata['sizes'] as $size_name => $size) {
                        $path = dirname($file_path) . '/' . $size['file'];
                        $upload_size = [
                            'type' => $size['mime-type'],
                            'file' => $path,
                        ];
                        if ( $this->upload_to_doge_with_retry( $upload_size ) ) {
                            $this->del_local_file( $upload_size );
                        } else {
                            $all_success = false;
                        }
                    }
                }
                // Upload original
                $upload_original = [
                    'type' => get_post_mime_type($file->ID),
                    'file' => $file_path,
                ];
                if ( $this->upload_to_doge_with_retry( $upload_original ) ) {
                    $this->del_local_file( $upload_original );
                } else {
                    $all_success = false;
                }

                // Upload true original (only exists when image was scaled down)
                if ( ! empty( $metadata['original_image'] ) ) {
                    $original_path = dirname( $file_path ) . '/' . $metadata['original_image'];
                    if ( $original_path !== $file_path ) {
                        $upload_true_original = [
                            'type' => get_post_mime_type($file->ID),
                            'file' => $original_path,
                        ];
                        if ( $this->upload_to_doge_with_retry( $upload_true_original ) ) {
                            $this->del_local_file( $upload_true_original );
                        } else {
                            $all_success = false;
                        }
                    }
                }

                if ( $all_success ) {
                    update_post_meta( $file->ID, '_is_mcv_doge', true );
                    $ret[] = esc_html( str_replace(ABSPATH, '', $file_path) );
                } else {
                }
            }
        }
        return $ret;
    }

    /**
     * Rename doge file
     */
    // public function renameDogeFile( $filename ){
    //     $ext = '.' . pathinfo( $filename, PATHINFO_EXTENSION );
    //     $new_filename = rtrim( $filename, $ext) . '_' . wp_rand(1,999) . $ext;
    //     return $new_filename;
    // }

    /**
     * attachment_url_to_postids
     */
    public function url_to_postid( $post_id, $url ){
        if( !$post_id && isset( MINECLOUDVOD_SETTINGS['doge_oss']['domain'] ) && strpos( $url, MINECLOUDVOD_SETTINGS['doge_oss']['domain'] ) >= 0 ){
       
            global $wpdb;
            $dir  = wp_get_upload_dir();
            // Parse URL to strip protocol, then remove domain + uploads base
            $parsed = wp_parse_url( $url );
            $url_path = $parsed['path'] ?? '';
            $base = '/' . str_replace( ABSPATH, '', $dir['basedir'] ) . '/';
            $path = substr( $url_path, strlen( $base ) );
            
            $results = $wpdb->get_results(  $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 按文件路径精确反查附件，刻意性能优化
                "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
                $path
            ) );
            $post_id = null;

            if ( $results ) {
                // Use the first available result, but prefer a case-sensitive match, if exists.
                $post_id = reset( $results )->post_id;

                if ( count( $results ) > 1 ) {
                    foreach ( $results as $result ) {
                        if ( $path === $result->meta_value ) {
                            $post_id = $result->post_id;
                            break;
                        }
                    }
                }
            }
            return (int)$post_id;
        }
    }
    
    public function deleteAttachment($post_id){
        $meta = wp_get_attachment_metadata( $post_id );
        if ( ! $meta || empty( $meta['file'] ) ) {
            return;
        }
        $dir  = wp_get_upload_dir();
        $file_path = str_replace( ABSPATH, '', $dir['basedir'] . '/'. $meta['file'] );
        $file_dir = dirname( $file_path );
        $file_data = [ $file_path ];
        if ( ! empty( $meta['original_image'] ) ) {
            $file_data[] = $file_dir . '/'. $meta['original_image'];
        }
        if (!empty($meta['sizes'])) {
            foreach ($meta['sizes'] as $size) {
                $file_data[] = $file_dir . '/' . $size['file'];
            }
        }
        $this->del_doge_file( $file_data );
    }
    public function del_doge_file( $file_data ){
        $data = [
            'bucket' => MINECLOUDVOD_SETTINGS['doge_oss']['bucket'],
        ];
        $resultArray = \MineCloudvod\RestApi\Dogecloud::call('/oss/file/delete.json?'.http_build_query($data), json_encode( $file_data ) );
        if ( ! isset( $resultArray['code'] ) || $resultArray['code'] != 200 ) {
        }
    }

    public function doge_media_url($url, $post_id){
        $_is_mcv_doge = get_post_meta( $post_id, '_is_mcv_doge', true );
        if( $_is_mcv_doge ){
            $url = (is_ssl()?'https':'http') . '://' . MINECLOUDVOD_SETTINGS['doge_oss']['domain'] . '/' . str_replace( ABSPATH, '', get_attached_file( $post_id ) );
        }
        return $url;
    }

    public function wpGenerateAttachmentMetadata( $metadata, $attachment_id  ){
        $mime_type = get_post_mime_type( $attachment_id );
        $attached_file = get_attached_file( $attachment_id );
        if ( ! $attached_file ) {
            return $metadata;
        }
        $file_path = dirname( $attached_file );

        // Non-image/audio/video files: metadata may be empty, just handle the original file
        if ( empty( $metadata['file'] ) ) {
            $upload_original = [
                'type' => $mime_type,
                'file' => $attached_file,
            ];
            if ( $this->upload_to_doge_with_retry( $upload_original ) ) {
                $this->del_local_file( $upload_original );
                update_post_meta( $attachment_id, '_is_mcv_doge', true );
            } else {
            }
            return $metadata;
        }

        $nfile = explode( '/', $metadata['file'] );
        $nfile = array_pop( $nfile );

        $all_success = true;

        // Upload scaled image
        if( isset($metadata['original_image']) && $nfile != $metadata['original_image'] ){
            $path = $file_path . '/' . $nfile;
            $upload_scaled = [
                'type' => $mime_type,
                'file' => $path,
            ];
            if ( $this->upload_to_doge_with_retry( $upload_scaled ) ) {
                $this->del_local_file( $upload_scaled );
            } else {
                $all_success = false;
            }
        }
        // Upload all thumbnail sizes
        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_name => $size) {
                $path = $file_path . '/' . $size['file'];
                $upload_size = [
                    'type' => $size['mime-type'],
                    'file' => $path,
                ];
                if ( $this->upload_to_doge_with_retry( $upload_size ) ) {
                    $this->del_local_file( $upload_size );
                } else {
                    $all_success = false;
                }
            }
        }

        // Upload and delete original file
        $original_name = $metadata['original_image'] ?? $nfile;
        $original_path = $file_path . '/' . $original_name;
        $upload_original = [
            'type' => $mime_type,
            'file' => $original_path,
        ];
        if ( $this->upload_to_doge_with_retry( $upload_original ) ) {
            $this->del_local_file( $upload_original );
        } else {
            $all_success = false;
        }

        if ( $all_success ) {
            update_post_meta( $attachment_id, '_is_mcv_doge', true );
        } else {
        }

        return $metadata;
    }

    public function wpHandleUpload($upload){
        if ( ! $this->upload_to_doge_with_retry( $upload ) ) {
        }
        return $upload;
    }
    
    public function del_local_file( $upload ){
        if( MINECLOUDVOD_SETTINGS['doge_oss']['del_local'] ?? false ){
            @wp_delete_file($upload['file']);
            $this->upload = null;
        }
    }
    
    /**
     * Upload a single file to Dogecloud OSS.
     *
     * @param array $upload  ['file' => path]
     * @return bool  true on success
     */
    public function upload_to_doge( $upload ){
        $data = [
            'bucket' => MINECLOUDVOD_SETTINGS['doge_oss']['bucket'],
            'key' => str_replace(ABSPATH, '', $upload['file']) ,
        ];
        $max_size = 10 * 1024 * 1024;
        if ( filesize( $upload['file'] ) > $max_size ) {
            return false;
        }
        $file_data = file_get_contents($upload['file']);
        if ( $file_data === false ) {
            return false;
        }
        $resultArray = \MineCloudvod\RestApi\Dogecloud::call('/oss/upload/put.json?'.http_build_query($data), $file_data);

        if(isset($resultArray['code']) && $resultArray['code'] == 200){
            $this->upload = $upload;
            return true;
        }

        return false;
    }

    /**
     * Upload to Dogecloud OSS with retry for transient network failures.
     */
    public function upload_to_doge_with_retry( $upload, $max_retries = 3 ) {
        for ( $i = 0; $i < $max_retries; $i++ ) {
            if ( $this->upload_to_doge( $upload ) ) {
                return true;
            }
            if ( $i < $max_retries - 1 ) {
                $file = basename( $upload['file'] );
                sleep( 1 );
            }
        }
        return false;
    }

    public function doge_admin_options(){
        $prefix = 'mcv_settings';

        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_doge',
            'title'  => __('Doge OSS', 'mine-cloudvod'),
            'icon'   => 'far fa-file',
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => __('<a href="https://www.dogecloud.com/?iuid=2453" target="_blank">Dogecloud官网</a> ', 'mine-cloudvod'), 
                ),
                array(
                    'id'        => 'doge_oss',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'sync_media',
                            'type'  => 'switcher',
                            'title' => __('Sync Media to OSS', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'before' => '<p>注意：只支持 10MB 以内的小文件同步</p><p>启用后，新上传的媒体文件会自动同步到<a href="https://www.dogecloud.com/?iuid=2453" target="_blank">Dogecloud</a>云存储的指定空间内。</p>',
                            'default' => false,
                        ),
                        array(
                            'id'    => 'bucket',
                            'type'  => 'text',
                            'title' => __('Bucket' , 'mine-cloudvod'),
                            'dependency' => array('sync_media', '==', true),
                        ),
                        array(
                            'id'    => 'domain',
                            'type'  => 'text',
                            'title' => __('Domain' , 'mine-cloudvod'),
                            'dependency' => array('sync_media', '==', true),
                        ),
                        array(
                            'id'    => 'del_local',
                            'type'  => 'switcher',
                            'title' => __('Delete Local Media', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'after' => '同步成功后，是否删除本地文件。',
                            'dependency' => array('sync_media', '==', true),
                            'default' => false,
                        ),
                        array(
                            'dependency' => array('sync_media', '==', true),
                            'type'=>'submessage',
                            'style'   => 'warning',
                            'content' => '<a href="javascript:;" id="sync_media_to_doge" target="_self">点击同步媒体库文件到多吉云存储</a>
                            <div id="sync_media_to_doge_result"></div>',
                        ),
                        // array(
                        //     'id'    => 'rename',
                        //     'type'  => 'switcher',
                        //     'title' => '重命名文件',
                        //     'text_on'    => __('Enable', 'mine-cloudvod'),
                        //     'text_off'   => __('Disable', 'mine-cloudvod'),
                        //     'after' => '可有效防止文件名重复而被覆盖。',
                        //     'dependency' => array('sync_media', '==', true),
                        //     'default' => false,
                        // ),
                    ),
                ),  
            )
        ));
    }
}
