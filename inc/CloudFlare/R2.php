<?php
declare( strict_types=1 );
namespace MineCloudvod\CloudFlare;

class R2{
    private $_wpcvApi;
    private $upload;
    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;

        add_action( 'rest_api_init', [$this, 'register_routes'] );
        add_action( 'wp_ajax_mcv_save_r2_buckets', [$this, 'mcv_save_r2_buckets'] );
        add_action( 'mcv_add_admin_options_before_purchase', [$this, 'admin_options'] );

        // Media sync hooks
        if ( MINECLOUDVOD_SETTINGS['cloudflare_r2']['sync_media'] ?? false ) {
            add_filter( 'wp_handle_upload', [ $this, 'wpHandleUpload' ] );
            add_filter( 'wp_generate_attachment_metadata', [ $this, 'wpGenerateAttachmentMetadata' ], 10, 2 );
            add_filter( 'wp_get_attachment_url', [ $this, 'rewriteUrl' ], 10, 2 );
            add_filter( 'attachment_url_to_postid', [ $this, 'urlToPostid' ], 10, 2 );
        }

        // Always register delete hook so R2 files get cleaned up even if sync is later disabled
        add_action( 'delete_attachment', [ $this, 'deleteAttachment' ] );
    }

    /**
     * Get CloudFlare API credentials from settings
     */
    private function get_credentials(){
        return [
            'email'            => MINECLOUDVOD_SETTINGS['cloudflare']['email'] ?? '',
            'account_id'       => MINECLOUDVOD_SETTINGS['cloudflare']['accountid'] ?? '',
            'apikey'           => MINECLOUDVOD_SETTINGS['cloudflare']['apikey'] ?? '',
            'apitoken'         => MINECLOUDVOD_SETTINGS['cloudflare']['apitoken'] ?? '',
            'access_key_id'    => MINECLOUDVOD_SETTINGS['cloudflare_r2']['access_key_id'] ?? '',
            'secret_access_key'=> MINECLOUDVOD_SETTINGS['cloudflare_r2']['secret_access_key'] ?? '',
        ];
    }

    /**
     * Check if credentials are configured for R2 API
     * R2 REST API requires an API Token (Bearer), NOT Global API Key
     * We accept either 'apitoken' field or 'apikey' field (auto-detected as token)
     */
    public function is_configured(){
        $creds = $this->get_credentials();
        if( empty( $creds['account_id'] ) ){
            return false;
        }
        // R2 requires Bearer token - check if we have one
        return ! empty( $creds['apitoken'] ) || ! empty( $creds['apikey'] );
    }

    /**
     * Add R2 admin options section under CloudFlare settings
     */
    public function admin_options(){
        $prefix = 'mcv_settings';

        // Build bucket options from cached list
        $mcv_r2_bucketsList = ['' => __('Please sync Buckets List first', 'mine-cloudvod')];
        if( $cached = get_option('mcv_r2_bucketsList') ){
            $mcv_r2_bucketsList = [];
            foreach( $cached as $name ){
                $mcv_r2_bucketsList[$name] = $name;
            }
        }

        \MCSF::createSection( $prefix, array(
            'parent'     => 'mcv_cloudflare',
            'title'      => __('R2', 'mine-cloudvod'),
            'icon'       => 'fas fa-database',
            'fields'     => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'info',
                    'content' => __('R2 requires an API Token with "Cloudflare R2:Edit" permission. Global API Key does not work for R2 API.', 'mine-cloudvod'),
                ),
                array(
                    'id'        => 'cloudflare_r2',
                    'type'      => 'fieldset',
                    'title'     => __('CloudFlare R2', 'mine-cloudvod'),
                    'fields'    => array(
                        array(
                            'id'          => 'bucket',
                            'type'        => 'select',
                            'title'       => __('Bucket', 'mine-cloudvod'),
                            'after'       => '<p><a href="javascript:mcv_sync_r2_buckets();">' . __('Sync Buckets List', 'mine-cloudvod') . '</a></p>',
                            'options'     => $mcv_r2_bucketsList,
                            'default'     => '',
                        ),
                        array(
                            'id'    => 'access_key_id',
                            'type'  => 'text',
                            'title' => __('R2 Access Key ID', 'mine-cloudvod'),
                            'desc'  => __('For client-side direct upload. Create an R2 API Token in CloudFlare dashboard.', 'mine-cloudvod'),
                        ),
                        array(
                            'id'    => 'secret_access_key',
                            'type'  => 'text',
                            'title' => __('R2 Secret Access Key', 'mine-cloudvod'),
                        ),
                        array(
                            'id'         => 'domain',
                            'type'       => 'text',
                            'title'      => __('Domain', 'mine-cloudvod'),
                            'desc'       => __('自定义域名（不含 http/https），例如 cdn.example.com。留空则使用 R2 默认 S3 域名。', 'mine-cloudvod'),
                        ),
                        array(
                            'type'    => 'submessage',
                            'style'   => 'info',
                            'content' => __('将 WordPress 媒体库文件自动同步到 R2（仅支持 10MB 以内文件）。', 'mine-cloudvod'),
                        ),
                        array(
                            'id'      => 'sync_media',
                            'type'    => 'switcher',
                            'title'   => __('Sync Media to R2', 'mine-cloudvod'),
                            'text_on'  => __('Enable', 'mine-cloudvod'),
                            'text_off' => __('Disable', 'mine-cloudvod'),
                            'before'  => '<p>' . __('启用后，新上传的媒体文件会自动同步到 CloudFlare R2。', 'mine-cloudvod') . '</p>',
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
                            'content'    => '<a href="javascript:;" id="sync_media_to_r2" target="_self">' . __('点击同步媒体库文件到 R2 存储', 'mine-cloudvod') . '</a>
                            <div id="sync_media_to_r2_result"></div>',
                        ),
                    ),
                ),
            ),
        ));
    }

    /**
     * AJAX handler: save R2 bucket list to option (called after sync)
     */
    public function mcv_save_r2_buckets(){
        if( ! current_user_can( 'manage_options' ) ){
            echo wp_json_encode(['status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')]);
            exit;
        }
        $nonce = ! empty( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : null;
        if( ! $nonce || ! wp_verify_nonce( $nonce, 'mcv_save_r2_buckets' ) ){
            echo wp_json_encode(['status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')]);
            exit;
        }
        $buckets = isset( $_POST['buckets'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['buckets'] ) ) : [];
        update_option( 'mcv_r2_bucketsList', $buckets );

        // Auto-configure CORS for each bucket (for client-side direct upload)
        $cors_ok = 0;
        foreach( $buckets as $bucket_name ){
            if( $this->set_bucket_cors( $bucket_name ) ){
                $cors_ok++;
            }
        }

        echo wp_json_encode(['status' => '1', 'cors' => $cors_ok]);
        exit;
    }

    /**
     * Build auth headers based on available credentials
     * 
     * IMPORTANT: CloudFlare R2 REST API only supports Bearer Token authentication.
     * X-Auth-Email + X-Auth-Key does NOT work for R2 API (unlike Stream API).
     * 
     * Strategy:
     * 1. If 'apitoken' field is set → use it as Bearer
     * 2. If 'apikey' field looks like an API Token (not 37-char hex Global API Key) → use it as Bearer
     * 3. If 'apikey' is a Global API Key → user must create an API Token for R2
     */
    private function get_auth_headers(){
        $creds = $this->get_credentials();

        // Explicit API Token field takes priority
        if( ! empty( $creds['apitoken'] ) ){
            return [
                'Authorization' => 'Bearer ' . $creds['apitoken'],
            ];
        }

        // For R2 API, we MUST use Bearer token
        // Check if apikey looks like an API Token (not Global API Key)
        $apikey = $creds['apikey'];
        if( ! empty( $apikey ) ){
            // Global API Key is always a 37-char lowercase hex string
            // API Token contains dashes, underscores, mixed case, or different length
            if( ! preg_match( '/^[a-f0-9]{37}$/i', $apikey ) ){
                // Looks like an API Token → use Bearer
                return [
                    'Authorization' => 'Bearer ' . $apikey,
                ];
            }
            // This is a Global API Key which doesn't work with R2 REST API
            // Return it anyway so the error message from CloudFlare is clear
            return [
                'Authorization' => 'Bearer ' . $apikey,
            ];
        }

        // No credentials at all
        return [];
    }

    /**
     * Auto-configure CORS for a bucket to allow client-side direct upload
     * Uses S3-compatible PutBucketCors API with AWS Signature V4
     */
    private function set_bucket_cors( $bucket ){
        $creds = $this->get_credentials();
        $access_key_id     = $creds['access_key_id'] ?? '';
        $secret_access_key = $creds['secret_access_key'] ?? '';
        $account_id        = $creds['account_id'] ?? '';

        if( ! $access_key_id || ! $secret_access_key || ! $account_id ){
            return false;
        }

        $site_url = get_site_url();
        $host     = "{$bucket}.{$account_id}.r2.cloudflarestorage.com";
        $region   = 'auto';
        $service  = 's3';
        $method   = 'PUT';
        $uri      = '/';
        $query    = 'cors=';

        // Build CORS XML body
        $cors_xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CORSConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">' // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- AWS S3 CORS 配置请求体的 XML 命名空间声明，非媒体卸载
            . '<CORSRule>'
            . '<AllowedOrigin>' . esc_xml( $site_url ) . '</AllowedOrigin>'
            . '<AllowedMethod>PUT</AllowedMethod>'
            . '<AllowedMethod>GET</AllowedMethod>'
            . '<AllowedMethod>HEAD</AllowedMethod>'
            . '<AllowedHeader>Content-Type</AllowedHeader>'
            . '<ExposeHeader>ETag</ExposeHeader>'
            . '<MaxAgeSeconds>3600</MaxAgeSeconds>'
            . '</CORSRule>'
            . '</CORSConfiguration>';

        // Build AWS Signature V4
        $date       = gmdate( 'Ymd' );
        $timestamp  = gmdate( 'Ymd\THis\Z' );
        $content_hash = hash( 'sha256', $cors_xml );

        $credential_scope = "{$date}/{$region}/{$service}/aws4_request";
        $signed_headers   = 'content-type;host;x-amz-content-sha256;x-amz-date';

        $canonical_headers  = "content-type:application/xml\n"
            . "host:{$host}\n"
            . "x-amz-content-sha256:{$content_hash}\n"
            . "x-amz-date:{$timestamp}\n";
        $canonical_request  = "{$method}\n{$uri}\n{$query}\n{$canonical_headers}\n{$signed_headers}\n{$content_hash}";

        $algorithm      = 'AWS4-HMAC-SHA256';
        $string_to_sign = "{$algorithm}\n{$timestamp}\n{$credential_scope}\n" . hash( 'sha256', $canonical_request );

        $k_date    = hash_hmac( 'sha256', $date,          'AWS4' . $secret_access_key, true );
        $k_region  = hash_hmac( 'sha256', $region,        $k_date,    true );
        $k_service = hash_hmac( 'sha256', $service,       $k_region,  true );
        $k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );

        $signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

        $auth_header = "{$algorithm} Credential={$access_key_id}/{$credential_scope},"
            . "SignedHeaders={$signed_headers},Signature={$signature}";

        // Send request via wp_remote_request (WP HTTP API).
        $url = "https://{$host}/?cors";
        $response = wp_remote_request( $url, [
            'method'    => 'PUT',
            'headers'   => [
                'Authorization'        => $auth_header,
                'Content-Type'         => 'application/xml',
                'x-amz-content-sha256' => $content_hash,
                'x-amz-date'           => $timestamp,
            ],
            'body'      => $cors_xml,
            'timeout'   => 15,
            'sslverify' => true,
        ]);

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        if ( $http_code >= 400 ) {
            return false;
        }

        return true;
    }

    /**
     * Make CloudFlare R2 API request
     * R2 REST API only supports Bearer Token authentication
     */
    private function api_request( $method, $path, $body = null, $query = [] ){
        $creds = $this->get_credentials();
        $account_id = $creds['account_id'];

        if( empty( $account_id ) ){
            return new \WP_Error( 'r2_no_account', 'CloudFlare Account ID is not configured', ['status' => 400] );
        }

        if( empty( $creds['apitoken'] ) && empty( $creds['apikey'] ) ){
            return new \WP_Error( 'r2_no_auth', 'CloudFlare API Token is required for R2. Please configure an API Token with R2 permissions in settings.', ['status' => 401] );
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/r2{$path}";
        if( !empty( $query ) ){
            $url = add_query_arg( $query, $url );
        }

        $result = $this->do_api_request( $method, $url, $body );

        // If auth failed, provide helpful error message
        if( is_wp_error( $result ) && $result->get_error_code() === 'r2_api_error' ){
            $error_msg = $result->get_error_message();
            if( stripos( $error_msg, 'authentication' ) !== false || stripos( $error_msg, 'auth' ) !== false ){
                // Override with helpful message
                return new \WP_Error( 'r2_auth_failed', 'R2 API requires an API Token (not Global API Key). Please create a CloudFlare API Token with "Cloudflare R2:Edit" permission and enter it in the API Token field.', ['status' => 401] );
            }
        }

        return $result;
    }

    /**
     * Execute a single API request
     */
    private function do_api_request( $method, $url, $body = null ){
        $args = [
            'timeout' => 30,
            'method'  => $method,
            'headers' => $this->get_auth_headers(),
        ];

        if( $body !== null ){
            $args['body'] = is_array( $body ) ? wp_json_encode( $body ) : $body;
            if( ! isset( $args['headers']['Content-Type'] ) ){
                $args['headers']['Content-Type'] = 'application/json';
            }
        }

        $response = wp_remote_request( $url, $args );

        if( is_wp_error( $response ) ){
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $result = json_decode( $response_body, true );

        if( ! $result || ! isset( $result['success'] ) || ! $result['success'] ){
            $error_msg = $result['errors'][0]['message'] ?? 'Unknown R2 API error';
            $error_code = $result['errors'][0]['code'] ?? 0;
            // Include request URL and response code for debugging
            $debug_info = " (status: {$response_code}, url: {$url}, code: {$error_code})";
            return new \WP_Error( 'r2_api_error', $error_msg . $debug_info, ['status' => $response_code] );
        }

        return $result;
    }

    public function register_routes(){
        $namespace = 'mine-cloudvod';
        $version = 'v1';
        $base = 'cloudflare/r2';

        /**
         * List R2 buckets
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/buckets', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'list_buckets'],
            'permission_callback' => [$this, 'is_admin'],
        ]);

        /**
         * List objects in a bucket
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/objects', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'list_objects'],
            'permission_callback' => [$this, 'is_admin'],
            'args'                => [
                'bucket' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'prefix' => [
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'cursor' => [
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'type'    => 'integer',
                    'default' => 50,
                ],
                'delimiter' => [
                    'type'    => 'string',
                    'default' => '/',
                ],
            ]
        ]);

        /**
         * Upload object to R2 bucket
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/upload', [
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => [$this, 'upload_object'],
            'permission_callback' => [$this, 'is_admin'],
            'args'                => [
                'bucket' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'key' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        ]);

        /**
         * Get presigned upload URL for client-side direct upload
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/usign', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_upload_sign'],
            'permission_callback' => [$this, 'is_admin'],
            'args'                => [
                'bucket' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'key' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        ]);

        /**
         * Get object public URL (for playback)
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/playurl', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_playurl'],
            'permission_callback' => [$this, 'is_admin'],
            'args'                => [
                'bucket' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'key' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        ]);

        /**
         * Delete object from R2 bucket
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/delete', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete_object'],
            'permission_callback' => [$this, 'is_admin'],
            'args'                => [
                'bucket' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'key' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        ]);

        /**
         * Batch sync existing media library files to R2
         */
        register_rest_route("{$namespace}/{$version}", '/' . $base . '/sync_media', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'syncMediaToR2'],
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ]);
    }

    public function is_admin(){
        $user = wp_get_current_user();
        $allowed_roles = array( 'administrator' );
        if( array_intersect( $allowed_roles, $user->roles ) ){
            return true;
        }
        return false;
    }

    /**
     * List all R2 buckets
     */
    public function list_buckets( \WP_REST_Request $request ){
        $result = $this->api_request( 'GET', '/buckets' );

        if( is_wp_error( $result ) ){
            return $result;
        }

        $buckets = [];
        if( isset( $result['result']['buckets'] ) && is_array( $result['result']['buckets'] ) ){
            foreach( $result['result']['buckets'] as $bucket ){
                $buckets[] = [
                    'name'         => $bucket['name'] ?? '',
                    'creationDate' => $bucket['creation_date'] ?? '',
                ];
            }
        }

        return rest_ensure_response([
            'success' => true,
            'buckets' => $buckets,
        ]);
    }

    /**
     * List objects in an R2 bucket
     */
    public function list_objects( \WP_REST_Request $request ){
        $bucket    = $request['bucket'];
        $prefix    = $request['prefix'] ?? '';
        $cursor    = $request['cursor'] ?? '';
        $per_page  = $request['per_page'] ?? 50;
        $delimiter = $request['delimiter'] ?? '/';

        $query = [
            'per_page' => $per_page,
        ];
        if( $prefix ){
            $query['prefix'] = $prefix;
        }
        if( $cursor ){
            $query['cursor'] = $cursor;
        }
        if( $delimiter ){
            $query['delimiter'] = $delimiter;
        }

        $result = $this->api_request( 'GET', "/buckets/{$bucket}/objects", null, $query );

        if( is_wp_error( $result ) ){
            return $result;
        }

        $objects = [];
        $cursor_out = '';
        $delimited_prefixes = [];

        if( isset( $result['result'] ) ){
            $r = $result['result'];

            // R2 API returns two possible formats:
            // 1. Wrapped: { objects: [...], delimitedPrefixes: [...], cursor: "..." }
            // 2. Flat array: [ { key: "...", ... }, ... ] (when no delimiter grouping)
            $raw_objects = [];
            if( isset( $r['objects'] ) && is_array( $r['objects'] ) ){
                $raw_objects = $r['objects'];
                if( isset( $r['delimited_prefixes'] ) && is_array( $r['delimited_prefixes'] ) ){
                    $delimited_prefixes = $r['delimited_prefixes'];
                }
                $cursor_out = $r['cursor'] ?? '';
            } elseif( is_array( $r ) && array_keys( $r ) === range( 0, count( $r ) - 1 ) ){
                // Flat array format
                $raw_objects = $r;
            }

            foreach( $raw_objects as $obj ){
                $key = $obj['key'] ?? '';
                if( $this->is_video_file( $key ) ){
                    $objects[] = [
                        'key'           => $key,
                        'videoId'       => $key,
                        'title'         => $key,
                        'status'        => 'Normal',
                        'size'          => $obj['size'] ?? 0,
                        'etag'          => $obj['etag'] ?? '',
                        'lastModified'  => $obj['last_modified'] ?? '',
                        'storageClass'  => $obj['storage_class'] ?? 'Standard',
                        'httpMetadata'  => $obj['http_metadata'] ?? [],
                        'customMetadata' => $obj['custom_metadata'] ?? [],
                    ];
                }
            }
        }

        return rest_ensure_response([
            'success'             => true,
            'objects'             => $objects,
            'delimited_prefixes'  => $delimited_prefixes,
            'cursor'              => $cursor_out,
        ]);
    }

    /**
     * Upload object to R2 bucket via server-side proxy
     * Frontend uploads file to WordPress, then WordPress uploads to R2
     */
    public function upload_object( \WP_REST_Request $request ){
        $bucket = sanitize_text_field( $request['bucket'] );
        $key    = sanitize_text_field( $request['key'] );

        // 防目录穿越：禁止 object key 中出现 ..
        if ( ! $key || false !== strpos( $key, '..' ) ) {
            return new \WP_Error( 'invalid_key', 'Invalid object key', ['status' => 400] );
        }

        // Get the uploaded file from the request body
        $files = $request->get_file_params();
        if( empty( $files ) || ! isset( $files['file'] ) ){
            return new \WP_Error( 'no_file', 'No file uploaded', ['status' => 400] );
        }

        $file = $files['file'];

        // 文件大小校验：拒绝空文件与超大文件
        $size = isset( $file['size'] ) ? (int) $file['size'] : 0;
        if ( $size <= 0 ) {
            return new \WP_Error( 'empty_file', 'Uploaded file is empty', ['status' => 400] );
        }
        if ( function_exists( 'wp_max_upload_size' ) && $size > wp_max_upload_size() ) {
            return new \WP_Error( 'file_too_large', 'File exceeds the maximum upload size', ['status' => 400] );
        }

        // 危险扩展名拦截：禁止脚本/可执行文件上传
        $ext = strtolower( (string) pathinfo( (string) ( $file['name'] ?? '' ), PATHINFO_EXTENSION ) );
        $blocked = [ 'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht', 'phar', 'asp', 'aspx', 'jsp', 'cgi', 'pl', 'py', 'sh', 'exe', 'bat', 'cmd', 'htaccess' ];
        if ( in_array( $ext, $blocked, true ) ) {
            return new \WP_Error( 'blocked_type', 'This file type is not allowed', ['status' => 400] );
        }

        $file_path = $file['tmp_name'];
        $file_content = file_get_contents( $file_path );

        if( $file_content === false ){
            return new \WP_Error( 'read_error', 'Failed to read uploaded file', ['status' => 500] );
        }

        // Upload to R2 via API
        $creds = $this->get_credentials();
        $account_id = $creds['account_id'];

        // Encode the key for URL but preserve slashes
        $encoded_key = str_replace( '%2F', '/', rawurlencode( $key ) );
        $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/r2/buckets/{$bucket}/objects/{$encoded_key}";

        $content_type = $file['type'] ?: 'application/octet-stream';

        $args = [
            'timeout' => 300,
            'method'  => 'PUT',
            'headers' => array_merge( $this->get_auth_headers(), [
                'Content-Type'  => $content_type,
            ] ),
            'body' => $file_content,
        ];

        $response = wp_remote_request( $url, $args );

        if( is_wp_error( $response ) ){
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        // R2 Upload Object returns 200 with empty body on success
        // Only treat 4xx/5xx as errors
        if( $response_code >= 400 ){
            $result = json_decode( $response_body, true );
            $error_msg = $result['errors'][0]['message'] ?? 'Upload failed (HTTP ' . $response_code . ')';
            return new \WP_Error( 'r2_upload_error', $error_msg, ['status' => $response_code] );
        }

        return rest_ensure_response([
            'success' => true,
            'key'     => $key,
            'bucket'  => $bucket,
        ]);
    }

    /**
     * Generate a presigned upload URL for client-side direct upload
     * Uses AWS Signature V4 against the R2 S3-compatible endpoint
     */
    public function get_upload_sign( \WP_REST_Request $request ){
        $bucket = $request['bucket'];
        $key    = $request['key'];

        $creds = $this->get_credentials();
        $access_key_id     = $creds['access_key_id'] ?? '';
        $secret_access_key = $creds['secret_access_key'] ?? '';
        $account_id        = $creds['account_id'] ?? '';

        if( ! $access_key_id || ! $secret_access_key || ! $account_id ){
            return new \WP_Error(
                'r2_s3_creds_missing',
                'R2 S3 credentials not configured.',
                ['status' => 400]
            );
        }

        return rest_ensure_response([
            'success'         => true,
            'endpoint'        => "https://{$account_id}.r2.cloudflarestorage.com",
            'region'          => 'auto',
            'bucket'          => $bucket,
            'key'             => $key,
            'accessKeyId'     => $access_key_id,
            'secretAccessKey' => $secret_access_key,
        ]);
    }

    /**
     * Generate a presigned PUT URL using AWS Signature V4
     *
     * @param string $access_key     R2 Access Key ID
     * @param string $secret_key     R2 Secret Access Key
     * @param string $region         R2 region (always 'auto')
     * @param string $service        AWS service (always 's3')
     * @param string $host           R2 S3 endpoint host
     * @param string $key            Object key
     * @param int    $expires        URL expiration in seconds
     * @return string                Presigned PUT URL
     */
    private function generate_presigned_upload_url( $access_key, $secret_key, $region, $service, $host, $path, $expires = 3600 ){
        $method = 'PUT';
        $canonical_uri = $path;

        $date      = gmdate( 'Ymd' );
        $timestamp  = gmdate( 'Ymd\THis\Z' );

        $credential_scope = "{$date}/{$region}/{$service}/aws4_request";
        $signed_headers   = 'host';

        // Build canonical request
        $canonical_headers  = "host:{$host}\n";
        $payload_hash       = 'UNSIGNED-PAYLOAD';
        $canonical_request  = "{$method}\n{$canonical_uri}\n\n{$canonical_headers}\n{$signed_headers}\n{$payload_hash}";

        // Build string to sign
        $algorithm       = 'AWS4-HMAC-SHA256';
        $string_to_sign  = "{$algorithm}\n{$timestamp}\n{$credential_scope}\n" . hash( 'sha256', $canonical_request );

        // Build signing key
        $k_date    = hash_hmac( 'sha256', $date,      'AWS4' . $secret_key, true );
        $k_region  = hash_hmac( 'sha256', $region,    $k_date,    true );
        $k_service = hash_hmac( 'sha256', $service,   $k_region,  true );
        $k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );

        // Signature
        $signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

        // Build the presigned URL
        $credential_encoded = rawurlencode( "{$access_key}/{$credential_scope}" );

        return "https://{$host}{$canonical_uri}"
            . "?X-Amz-Algorithm={$algorithm}"
            . "&X-Amz-Credential={$credential_encoded}"
            . "&X-Amz-Date={$timestamp}"
            . "&X-Amz-Expires={$expires}"
            . "&X-Amz-SignedHeaders={$signed_headers}"
            . "&X-Amz-Signature={$signature}";
    }

    /**
     * Get a signed/public URL for an R2 object for playback
     */
    public function get_playurl( \WP_REST_Request $request ){
        $bucket = $request['bucket'];
        $key    = $request['key'];

        $url = $this->get_object_url( $bucket, $key );

        return rest_ensure_response([
            'success' => true,
            'playUrl' => $url,
        ]);
    }

    /**
     * Get object URL — try custom domain first, then r2.dev domain
     */
    public function get_object_url( $bucket, $key ){
        $creds = $this->get_credentials();
        $account_id = $creds['account_id'];

        // Try to get custom domain first
        $domain = $this->get_bucket_public_domain( $bucket, $account_id );

        if( $domain ){
            $encoded_key = str_replace( '%2F', '/', rawurlencode( $key ) );
            return trailingslashit( $domain ) . $encoded_key;
        }

        // Fallback: try to get r2.dev managed domain
        $managed_domain = $this->get_bucket_managed_domain( $bucket, $account_id );
        if( $managed_domain ){
            $encoded_key = str_replace( '%2F', '/', rawurlencode( $key ) );
            return trailingslashit( $managed_domain ) . $encoded_key;
        }

        // Last resort: use API direct download (time-limited)
        return $this->get_direct_download_url( $bucket, $key );
    }

    /**
     * Get bucket's custom domain
     */
    private function get_bucket_public_domain( $bucket, $account_id ){
        // Check cache first
        $cache_key = "mcv_r2_custom_domain_{$bucket}";
        $cached = get_transient( $cache_key );
        if( $cached !== false ){
            return $cached;
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/r2/buckets/{$bucket}/domains/custom";
        $response = wp_remote_get( $url, [
            'timeout' => 10,
            'headers' => $this->get_auth_headers(),
        ] );

        if( is_wp_error( $response ) ){
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $domain = false;

        if( isset( $body['success'] ) && $body['success'] && isset( $body['result']['domains'] ) ){
            foreach( $body['result']['domains'] as $d ){
                if( ! empty( $d['domain'] ) && ( $d['status'] ?? '' ) === 'ACTIVE' ){
                    $domain = 'https://' . $d['domain'];
                    break;
                }
            }
        }

        // Cache for 1 hour (empty string means no domain found, cache that too)
        set_transient( $cache_key, $domain ?: '', HOUR_IN_SECONDS );

        return $domain;
    }

    /**
     * Get bucket's r2.dev managed domain
     */
    private function get_bucket_managed_domain( $bucket, $account_id ){
        $cache_key = "mcv_r2_managed_domain_{$bucket}";
        $cached = get_transient( $cache_key );
        if( $cached !== false ){
            return $cached;
        }

        $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/r2/buckets/{$bucket}/domains/managed";
        $response = wp_remote_get( $url, [
            'timeout' => 10,
            'headers' => $this->get_auth_headers(),
        ] );

        if( is_wp_error( $response ) ){
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $domain = false;

        if( isset( $body['success'] ) && $body['success'] && isset( $body['result']['domain'] ) ){
            $domain = 'https://' . $body['result']['domain'];
        }

        set_transient( $cache_key, $domain ?: '', HOUR_IN_SECONDS );

        return $domain;
    }

    /**
     * Get a direct download URL via API (time-limited access)
     * This is used as a fallback when no public domain is configured
     */
    private function get_direct_download_url( $bucket, $key ){
        // Return the API endpoint for direct object access
        // The frontend can proxy through our REST endpoint
        $encoded_key = str_replace( '%2F', '/', rawurlencode( $key ) );
        return rest_url( "mine-cloudvod/v1/cloudflare/r2/proxy" ) . "?bucket={$bucket}&key=" . rawurlencode( $key );
    }

    /**
     * Delete object from R2 bucket
     */
    public function delete_object( \WP_REST_Request $request ){
        $bucket = $request['bucket'];
        $key    = $request['key'];

        $creds = $this->get_credentials();
        $account_id = $creds['account_id'];

        $encoded_key = str_replace( '%2F', '/', rawurlencode( $key ) );
        $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/r2/buckets/{$bucket}/objects/{$encoded_key}";

        $response = wp_remote_request( $url, [
            'timeout' => 10,
            'method'  => 'DELETE',
            'headers' => $this->get_auth_headers(),
        ] );

        if( is_wp_error( $response ) ){
            return $response;
        }

        return rest_ensure_response([
            'success' => true,
        ]);
    }

    /**
     * Check if a file key looks like a video file
     */
    private function is_video_file( $key ){
        $video_extensions = [
            'mp4', 'webm', 'm3u8', 'mpd', 'flv', 'avi', 'mov', 'mkv',
            'wmv', 'm4v', '3gp', 'ts', 'f4v',
        ];
        $ext = strtolower( pathinfo( $key, PATHINFO_EXTENSION ) );
        return in_array( $ext, $video_extensions, true );
    }

    // ==================== Media Sync ====================

    /**
     * Batch sync existing media library files to R2
     */
    public function syncMediaToR2() {
        $media_files = get_posts( array(
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'meta_query'  => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 媒体同步筛选，业务必需
                [
                    'key'     => '_is_mcv_r2',
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
                        if ( $this->uploadToR2WithRetry( $upload_size ) ) {
                            $this->delLocalFile( $upload_size );
                        } else {
                            $all_success = false;
                        }
                    }
                }

                // Upload main file (scaled version for large images, or the original for normal images)
                $upload_original = array(
                    'type' => get_post_mime_type( $file->ID ),
                    'file' => $file_path,
                );
                if ( $this->uploadToR2WithRetry( $upload_original ) ) {
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
                        if ( $this->uploadToR2WithRetry( $upload_true_original ) ) {
                            $this->delLocalFile( $upload_true_original );
                        } else {
                            $all_success = false;
                        }
                    }
                }

                // Only mark as synced when ALL sizes uploaded successfully
                if ( $all_success ) {
                    update_post_meta( $file->ID, '_is_mcv_r2', true );
                    $ret[] = esc_html( str_replace( ABSPATH, '', $file_path ) );
                } else {
                }
            }
        }
        return $ret;
    }

    // --- Upload hooks ---

    public function wpHandleUpload( $upload ) {
        if ( ! $this->uploadToR2WithRetry( $upload ) ) {
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
            if ( $this->uploadToR2WithRetry( $upload_original ) ) {
                $this->delLocalFile( $upload_original );
                update_post_meta( $attachment_id, '_is_mcv_r2', true );
            } else {
            }
            return $metadata;
        }

        $nfile      = explode( '/', $metadata['file'] );
        $nfile      = array_pop( $nfile );

        $all_success = true;

        // Upload scaled image (when different from original_image)
        if ( isset( $metadata['original_image'] ) && $nfile != $metadata['original_image'] ) {
            $path = $file_path . '/' . $nfile;
            $upload_scaled = array(
                'type' => $mime_type,
                'file' => $path,
            );
            if ( $this->uploadToR2WithRetry( $upload_scaled ) ) {
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
                if ( $this->uploadToR2WithRetry( $upload_size ) ) {
                    $this->delLocalFile( $upload_size );
                } else {
                    $all_success = false;
                }
            }
        }

        // Upload and delete original file
        // For scaled images: original_image is the true original (scaled version was handled above)
        // For non-scaled images: nfile IS the original (not yet handled)
        $original_name = $metadata['original_image'] ?? $nfile;
        $original_path = $file_path . '/' . $original_name;
        $upload_original = array(
            'type' => $mime_type,
            'file' => $original_path,
        );
        if ( $this->uploadToR2WithRetry( $upload_original ) ) {
            $this->delLocalFile( $upload_original );
        } else {
            $all_success = false;
        }

        // Only mark as synced when ALL sizes uploaded successfully
        if ( $all_success ) {
            update_post_meta( $attachment_id, '_is_mcv_r2', true );
        } else {
        }

        return $metadata;
    }

    /**
     * Upload a single file to R2.
     *
     * @param array $upload  ['file' => path, 'type' => mime]
     * @return bool  true on success
     */
    private function uploadToR2( $upload ) {
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
        $result = $this->s3SignedRequest( 'PUT', $key, $body, $contentType );

        if ( $result ) {
            $this->upload = $upload;
            return true;
        }

        return false;
    }

    /**
     * Upload to R2 with retry for transient network failures.
     *
     * @param array $upload     ['file' => path, 'type' => mime]
     * @param int   $max_retries  Maximum attempts (default 3)
     * @return bool  true if any attempt succeeded
     */
    private function uploadToR2WithRetry( $upload, $max_retries = 3 ) {
        for ( $i = 0; $i < $max_retries; $i++ ) {
            if ( $this->uploadToR2( $upload ) ) {
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
        // Always attempt R2 cleanup — file may exist in R2 even if sync meta wasn't set (partial sync failure)
        $meta = wp_get_attachment_metadata( $post_id );
        if ( empty( $meta['file'] ) ) {
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

        $this->deleteR2Files( $keys );
    }

    private function deleteR2Files( $keys ) {
        foreach ( $keys as $key ) {
            $result = $this->s3SignedRequest( 'DELETE', $key );
            if ( ! $result ) {
            }
        }
    }

    // --- URL rewriting ---

    public function rewriteUrl( $url, $post_id ) {
        $_is_mcv_r2 = get_post_meta( $post_id, '_is_mcv_r2', true );
        if ( $_is_mcv_r2 ) {
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

    // --- Local file cleanup ---

    private function delLocalFile( $upload ) {
        if ( MINECLOUDVOD_SETTINGS['cloudflare_r2']['del_local'] ?? false ) {
            @wp_delete_file( $upload['file'] );
            $this->upload = null;
        }
    }

    // --- S3 SigV4 request (generic, for PUT/DELETE to R2 S3 endpoint) ---

    private function s3SignedRequest( $method, $key, $body = null, $contentType = null ) {
        $bucket          = MINECLOUDVOD_SETTINGS['cloudflare_r2']['bucket'] ?? '';
        $access_key_id   = MINECLOUDVOD_SETTINGS['cloudflare_r2']['access_key_id'] ?? '';
        $secret_key      = MINECLOUDVOD_SETTINGS['cloudflare_r2']['secret_access_key'] ?? '';
        $account_id      = MINECLOUDVOD_SETTINGS['cloudflare']['accountid'] ?? '';

        if ( empty( $bucket ) || empty( $access_key_id ) || empty( $secret_key ) || empty( $account_id ) ) {
            return false;
        }

        $region    = 'auto';
        $service   = 's3';
        $algorithm = 'AWS4-HMAC-SHA256';
        $host      = "{$bucket}.{$account_id}.r2.cloudflarestorage.com";

        $date      = gmdate( 'Ymd' );
        $timestamp = gmdate( 'Ymd\THis\Z' );
        $credential_scope = "{$date}/{$region}/{$service}/aws4_request";

        $amz_headers = array(
            'host'       => $host,
            'x-amz-date' => $timestamp,
        );

        if ( $body !== null ) {
            $content_hash = hash( 'sha256', $body );
            $amz_headers['x-amz-content-sha256'] = $content_hash;
            if ( $contentType ) {
                $amz_headers['content-type'] = $contentType;
            }
        } else {
            $content_hash = hash( 'sha256', '' );
            $amz_headers['x-amz-content-sha256'] = $content_hash;
        }

        $segments = explode( '/', ltrim( $key, '/' ) );
        $segments = array_map( 'rawurlencode', $segments );
        $canonical_uri = '/' . implode( '/', $segments );

        $signed_headers_list = array_keys( $amz_headers );
        sort( $signed_headers_list );
        $signed_headers = implode( ';', $signed_headers_list );

        $canonical_headers = '';
        foreach ( $signed_headers_list as $h ) {
            $canonical_headers .= "{$h}:{$amz_headers[$h]}\n";
        }

        $canonical_request = "{$method}\n{$canonical_uri}\n\n{$canonical_headers}\n{$signed_headers}\n{$content_hash}";
        $string_to_sign    = "{$algorithm}\n{$timestamp}\n{$credential_scope}\n" . hash( 'sha256', $canonical_request );

        $k_date    = hash_hmac( 'sha256', $date,          'AWS4' . $secret_key, true );
        $k_region  = hash_hmac( 'sha256', $region,        $k_date,    true );
        $k_service = hash_hmac( 'sha256', $service,       $k_region,  true );
        $k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );
        $signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

        $auth = "{$algorithm} Credential={$access_key_id}/{$credential_scope},SignedHeaders={$signed_headers},Signature={$signature}";
        $url  = "https://{$host}{$canonical_uri}";

        $headers = array( 'Authorization' => $auth );
        foreach ( $amz_headers as $h => $v ) {
            $headers[ $h ] = $v;
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

        // For DELETE, 404 means the object is already gone — treat as success
        if ( $method === 'DELETE' && $http_code === 404 ) {
            return true;
        }

        return $http_code >= 200 && $http_code < 300;
    }

    /**
     * Get the public domain for media URL rewriting.
     * Priority: custom domain > S3 virtual-hosted-style endpoint
     */
    private function getMediaSyncDomain() {
        $domain = MINECLOUDVOD_SETTINGS['cloudflare_r2']['domain'] ?? '';
        if ( $domain ) {
            return $domain;
        }
        // Fallback to S3 endpoint (requires bucket public access)
        $bucket     = MINECLOUDVOD_SETTINGS['cloudflare_r2']['bucket'] ?? '';
        $account_id = MINECLOUDVOD_SETTINGS['cloudflare']['accountid'] ?? '';
        if ( $bucket && $account_id ) {
            return "{$bucket}.{$account_id}.r2.cloudflarestorage.com";
        }
        return '';
    }
}
