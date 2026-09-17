<?php
namespace MineCloudvod;

class Assets{
    /**
     * 前端脚本 handle 列表（用于添加 defer 属性）
     */
    private const FRONTEND_SCRIPT_HANDLES = [
        'mcv_layer',
        'mine-cloudvod-course-single-view-script',
        'mine-cloudvod-lesson-single-editor-script',
        'mine-cloudvod-user-script',
        'mine-cloudvod-course-list-editor-script',
        'mcv_dplayer',
        'mcv_dplayer_hls',
        'mcv_aliplayer',
        'mcv_aliplayer_components',
        'mcv_tcplayer',
        'mcv_aplayer',
        'mcv_playlist_js',
    ];

    public function __construct() {
        add_action( 'init',                     [ $this, 'mcv_assets_register'] );
        // 防止jQuery被注销
        add_action( 'wp_enqueue_scripts',       function(){
            if (!wp_script_is('jquery', 'registered')) wp_register_script('jquery', includes_url('/js/jquery/jquery.min.js'), array(), MINECLOUDVOD_VERSION, true);
        }, 9999 );
        // mcv_nonce 必须在所有后台页面注入，优先级必须早于其它脚本注册回调
        add_action( 'admin_enqueue_scripts',    [ $this, 'mcv_admin_nonce' ], 5 );
        add_action( 'admin_enqueue_scripts',    [ $this, 'mcv_course_admin_assets' ] );
        add_action( 'admin_enqueue_scripts',	[ $this, 'mcv_blocks_assets'] );
        add_action( 'admin_enqueue_scripts',	[ $this, 'mcv_options_scripts'] );
        // 课时编辑 iframe 弹窗模式：隐藏 admin bar 与 Gutenberg 返回按钮
        add_action( 'admin_init',               [ $this, 'mcv_lesson_embed_setup' ] );
        // 前端脚本添加 defer 属性
        add_filter( 'script_loader_tag', [ $this, 'add_defer_to_frontend_scripts' ], 10, 2 );
    }

    /**
     * 课时编辑 iframe 弹窗（mcv_embed=1）：
     * - 隐藏 admin bar（后台 is_admin() 恒 true，show_admin_bar filter 不生效，只能 CSS 隐藏）
     * - 编辑器骨架 .interface-interface-skeleton 固定偏移 32px(桌面)/46px(移动) 为 admin bar 让位，需归零
     * - 隐藏 WP 7.1+ Gutenberg 编辑器左上角返回按钮（.editor-header__back-button）
     */
    public function mcv_lesson_embed_setup() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 仅识别 iframe 嵌入标识，非数据修改操作
        if( empty( $_GET['mcv_embed'] ) ){
            return;
        }
        add_action( 'admin_head', function(){
            echo '<style id="mcv-embed-hide">'
                . '#wpadminbar{display:none!important}'
                . 'body.admin-bar .interface-interface-skeleton{top:0!important}'
                . '.editor-header__back-button{display:none!important}'
                . '</style>';
        }, 99 );
    }

    /**
     * 为前端脚本添加 defer 属性
     *
     * defer 脚本在 DOM 解析完成后按顺序执行，不阻塞页面渲染。
     */
    public function add_defer_to_frontend_scripts( string $tag, string $handle ): string {
        // 仅在前端非管理页面生效
        if ( is_admin() ) {
            return $tag;
        }
        if ( in_array( $handle, self::FRONTEND_SCRIPT_HANDLES, true ) ) {
            return str_replace( ' src=', ' defer src=', $tag );
        }
        return $tag;
    }

    /**
     * mcv_nonce 前端配置（唯一数据源）
     *
     * 供后台本地化脚本与区块编辑器脚本共用。endtime 缺失时 strtotime() 返回 false，
     * 直接拼接会输出 `endtime:,` 造成 JS 语法错误，进而连同段内联的其它变量一起失效，
     * 因此这里强制转成整型。
     */
    public static function mcv_nonce_config(): array {
        $endtime = MINECLOUDVOD_SETTINGS['endtime'] ?? '';
        return array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'et'          => wp_create_nonce( 'mcv_sync_endtime' ),
            'endtime'     => (int) strtotime( (string) $endtime ),
            'buynow'      => admin_url( '/admin.php?page=mcv-options#tab=' . str_replace( ' ', '-', strtolower( urlencode( __( 'Purchase time', 'mine-cloudvod' ) ) ) ) ),
            'restRootUrl' => get_rest_url(),
        );
    }

    /**
     * 在所有后台页面注入 mcv_nonce
     *
     * 区块编辑器（以及各 mcv_* 编辑器脚本）可能出现在任意 $hook 页面 —— 除 post.php /
     * post-new.php / site-editor.php 之外，自定义器小工具面板、区块小工具、第三方页面构建器
     * 同样会加载 editor_script。原先按 $hook 白名单注入会漏页面，导致脚本执行时抛出
     * "mcv_nonce is not defined"。此处用幂等赋值，避免与 mcv_blocks_assets() 及
     * Aliyun\Aliplayer 的注入互相覆盖。
     */
    public function mcv_admin_nonce() {
        if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
            wp_enqueue_script( 'jquery' );
        }
        wp_add_inline_script( 'jquery', 'window.mcv_nonce=window.mcv_nonce||'.wp_json_encode( self::mcv_nonce_config() ).';' );
    }

    public function mcv_assets_register(){
        
        global $wp_version;
        //将php变量本地化到页面js中
        wp_register_script( 'mcv_localize_script', false, array(), MINECLOUDVOD_VERSION, true );
        wp_register_script(
            'mcv_layer',
            MINECLOUDVOD_URL.'/static/layer/layer.js',
            array( 'jquery' ),
            MINECLOUDVOD_VERSION,
            true
        );
        

        wp_register_style(
            'mine_cloudvod-aliyunvod-style-css',
            MINECLOUDVOD_URL.'/build/block/style-mcv.blocks.css', 
            is_admin() ? array( 'wp-editor' ) : null,
            MINECLOUDVOD_VERSION
        );
        wp_register_style(
            'mine-cloudvod-admin-css',
            MINECLOUDVOD_URL.'/build/admin/index.css', 
            null,
            MINECLOUDVOD_VERSION
        );

        $adminDependencies = include( MINECLOUDVOD_PATH.'/build/admin/index.asset.php' );
        wp_register_script(
            'mine-cloudvod-admin-js',
            MINECLOUDVOD_URL.'/build/admin/index.js',
            $adminDependencies['dependencies'],
            MINECLOUDVOD_VERSION,
            true
        );

        $adminDependencies = include( MINECLOUDVOD_PATH.'/build/admin/init/index.asset.php' );
        wp_register_script(
            'mcv-admin-init',
            MINECLOUDVOD_URL.'/build/admin/init/index.js',
            $adminDependencies['dependencies'],
            MINECLOUDVOD_VERSION,
            true
        );

        wp_register_script(
            'mine_cloudvod-aliyunvod-block-js',
            MINECLOUDVOD_URL.'/build/block/mcv.blocks.js',
            array( 'jquery', 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
            MINECLOUDVOD_VERSION,
            true
        );
        /*
         * mcv_nonce 绑定脚本自身 handle（before 内联 + 幂等赋值）。
         * 该 handle 由 register_block_type() 以 editor_script 注册，WordPress 会在**任何区块编辑器
         * 上下文**加载它，与 admin_enqueue_scripts 的 $hook 无关（自定义器小工具、区块小工具、
         * 第三方页面构建器同样会加载）。原先只在 hook 白名单页面注入 nonce 会漏注入，
         * 导致本脚本执行时抛出 "mcv_nonce is not defined"。
         */
        wp_add_inline_script(
            'mine_cloudvod-aliyunvod-block-js',
            'window.mcv_nonce=window.mcv_nonce||'.wp_json_encode( self::mcv_nonce_config() ).';',
            'before'
        );

        wp_register_script(
            'mine_cloudvod-classic-js',
            MINECLOUDVOD_URL.'/build/classic/index.js',
            array( 'wp-i18n', 'wp-block-editor', 'wp-api-fetch' ),
            MINECLOUDVOD_VERSION,
            true
        );

        wp_register_script(//import
            'mine_cloudvod-import-js',
            MINECLOUDVOD_URL.'/build/import/index.js',
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
            MINECLOUDVOD_VERSION,
            true
        );

        wp_register_script(//lms
            'mine_cloudvod-lms-js',
            MINECLOUDVOD_URL.'/build/lms/course-metabox/index.js',
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-data' ),
            MINECLOUDVOD_VERSION,
            true
        );

        wp_register_script(//elementor
            'mine_cloudvod-integrations-elementor-js',
            MINECLOUDVOD_URL.'/build/integrations/elementor/editor.js',
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
            MINECLOUDVOD_VERSION,
            true
        );

        wp_register_style(
            'mine_cloudvod-lms-css',
            MINECLOUDVOD_URL.'/build/lms/course-metabox/index.css',
            array(  ),
            MINECLOUDVOD_VERSION
        );

        wp_register_style(
            'mine_cloudvod-aliyunvod-block-editor-css',
            MINECLOUDVOD_URL.'/build/block/mcv.blocks.css',
            array( ),
            MINECLOUDVOD_VERSION
        );
        wp_set_script_translations( 'mine_cloudvod-aliyunvod-block-js', 'mine-cloudvod' );
        wp_set_script_translations( 'mine_cloudvod-import-js', 'mine-cloudvod' );
        wp_set_script_translations( 'mine_cloudvod-integrations-elementor-js', 'mine-cloudvod' );
        wp_set_script_translations( 'mine_cloudvod-classic-js', 'mine-cloudvod' );
        wp_set_script_translations( 'mine_cloudvod-lms-js', 'mine-cloudvod' );
        wp_set_script_translations( 'mine-cloudvod-admin-js', 'mine-cloudvod' );

        if( !mcv_check_role_permission() ) return;

        register_block_type(
            'mine-cloudvod/block-container', array(
                'editor_script' => 'mine_cloudvod-aliyunvod-block-js',
                'editor_style'  => 'mine_cloudvod-aliyunvod-block-editor-css',
            )
        );


        $filter = 'block_categories';
        if (version_compare($wp_version, '5.8', ">=")) {
            $filter = 'block_categories_all';
        }
        add_filter( $filter, function( $categories ) {
            $categories = array_merge(
                array(
                    array(
                        'slug'  => 'mine',
                        'title' => __('Mine', 'mine-cloudvod'),
                    ),
                ),
                $categories
            );
            return $categories;
        } );
    }

    /**
     * 区块
     */
    public function mcv_blocks_assets($hook){
        if($hook != "post.php" && $hook != "post-new.php" && $hook != "edit.php" && $hook != "site-editor.php") return;
        global $current_user;
        $uid = $current_user->ID;
        wp_enqueue_script('jquery');
        // wp_enqueue_script('mcv_aliplayer', MINECLOUDVOD_ALIPLAYER['js'],  array(), MINECLOUDVOD_VERSION , true);
        // wp_enqueue_script('mcv_aliplayer_components', MINECLOUDVOD_URL.'/static/aliyun/aliplayercomponents-1.0.6.min.js',  array('mcv_aliplayer'), MINECLOUDVOD_VERSION , false );
        wp_enqueue_script('mcv_aplayer', MINECLOUDVOD_URL.'/static/aplayer/McvAPlayer.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_enqueue_script('mcv_alivod_sdk', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/aliyun-upload-sdk-1.5.0.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_enqueue_script('mcv_alivod_es6-promise', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/lib/es6-promise.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_enqueue_script('mcv_alivod_oss', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/lib/aliyun-oss-sdk-5.3.1.min.js',  array(), MINECLOUDVOD_VERSION , true);
        // 注意：endtime 缺失时 strtotime() 返回 false，直接拼接会输出 `endtime:,` 造成 JS 语法错误，
        // 而同一句柄下的内联脚本是拼在同一个 <script> 里的，一处语法错误会让 mcv_nonce、
        // mcv_hw_config、mcv_bd_config、mcv_bunny_config 等全部不执行。必须强制转成整型。
        $mcv_endtime_ts = (int) strtotime( (string) ( MINECLOUDVOD_SETTINGS['endtime'] ?? '' ) );
        wp_add_inline_script('jquery','var mcv_nonce={ajaxUrl:"'.admin_url("admin-ajax.php").'",et:"'.wp_create_nonce('mcv_sync_endtime').'",endtime:'.$mcv_endtime_ts.', buynow:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Purchase time', 'mine-cloudvod'))))).'", restRootUrl:"'.get_rest_url().'"};');
        wp_add_inline_script('jquery', 'var mcv_cloudflare_r2_default_bucket="' . esc_js(MINECLOUDVOD_SETTINGS['cloudflare_r2']['bucket'] ?? '') . '";');
        
        wp_enqueue_style('mcv_aplayer_css', MINECLOUDVOD_URL.'/static/aplayer/APlayer.min.css', array(), MINECLOUDVOD_VERSION);
        wp_enqueue_style('mcv_tcplayer_css', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.7.2/tcplayer.min.css', array(), MINECLOUDVOD_VERSION);
        // wp_enqueue_script('mcv_tcplayerhls', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.6.0/libs/hls.min.1.1.5.js',  array(), MINECLOUDVOD_VERSION , true );
        wp_enqueue_script('mcv_tcplayer', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.7.2/tcplayer.v4.7.2.min.js',  array(), MINECLOUDVOD_VERSION , true );
        wp_add_inline_script('mcv_tcplayer','var mcv_tcvod_config={appID:"'.(MINECLOUDVOD_SETTINGS['tcvod']['appid']??'').'",key:"'.(MINECLOUDVOD_SETTINGS['tcvod']['fdlkey']??'').'",pcfg:"'.(MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig']??'').'",nonce:"'.wp_create_nonce('mcv-aliyunvod-'.$uid).'",sdk:'.( MINECLOUDVOD_SETTINGS['tcvod']['sid']??MINECLOUDVOD_SETTINGS['tcvod']['skey']??false ? 'true' : 'false' ).',tc_config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Tencent Cloud', 'mine-cloudvod'))))).'pro"};');

        global $mcv_classes;
        wp_localize_script( 'jquery-core', 'mcv_addons', [
            'actived' => $mcv_classes->Addons->get_actived_addons()
        ] );
    }

    /**
     * 课程
     */
    public function mcv_course_admin_assets($hook) {
        global $post_type, $post_id;
        if( $post_type && $post_type == MINECLOUDVOD_LMS['course_post_type'] && ( $hook == 'post.php' || $hook == 'post-new.php' ) ){
            wp_enqueue_style( 'mine_cloudvod-lms-css' );
            wp_enqueue_editor();

            $lms_config = [
                'new_lesson' => admin_url('post-new.php?post_type=mcv_lesson'),
                'edit_lesson' => admin_url('post.php?action=edit&post='),
            ];
            $catelog_no = true;
            if( $post_id ){
                $catelog_no = get_post_meta( $post_id, '_mcv_course_no_type', true );
                if( $catelog_no === "" ) $catelog_no = true;
                else $catelog_no = !!$catelog_no;
            }
            $lms_config['catelog_no'] = $catelog_no;
            wp_enqueue_script( 'mine_cloudvod-lms-js' );
            wp_localize_script('mine_cloudvod-lms-js', 'mcv_lms_config', $lms_config);
        }
    }

    /**
     * 设置
     */
    public function mcv_options_scripts($hook){
        wp_enqueue_style('mine_cloudvod-aliyunvod-style-css');
        $ajaxUrl = admin_url("admin-ajax.php");
        $is_mcv_options = ( $hook === 'toplevel_page_mcv-options' || $hook === 'mine-cloudvod_page_mcv-options' || ( isset($_GET['page']) && $_GET['page'] === 'mcv-options' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 仅判断后台当前页面
        if($is_mcv_options){
            wp_enqueue_script('mcv_layer', MINECLOUDVOD_URL.'/static/layer/layer.js',  array(), MINECLOUDVOD_VERSION , true );
            wp_add_inline_script('mcv_layer', mcv_trim( '
            function mcv_sync_tccos_buckets(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_tccos_buckets", nonce: "'. wp_create_nonce('mcv_asyc_tccos_buckets') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized buckets', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data;
                            for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i][0]+"\">"+tdata[i][0]+"</option>";
                            }
                            jQuery("select[name=\'mcv_settings[tcvod][buckets]\']").html(tcoptions);
                        }
                    }, "json");
                });
            }
            function mcv_sync_qiniu_buckets(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_qiniu_buckets", nonce: "'. wp_create_nonce('mcv_asyc_qiniu_buckets') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized buckets', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data[0];
                            for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i]+"\">"+tdata[i]+"</option>";
                            }
                            jQuery("select[name=\'mcv_settings[qiniu][bucket]\']").html(tcoptions);
                        }
                    }, "json");
                });
            }
            function mcv_sync_alioss_buckets(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_alioss_buckets", nonce: "'. wp_create_nonce('mcv_asyc_alioss_buckets') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized buckets', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data;
                            for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i][0]+"\">"+tdata[i][0]+"</option>";
                            }
                            jQuery("select[name=\'mcv_settings[alivod][buckets]\']").html(tcoptions);
                        }
                    }, "json");
                });
            }
            function mcv_sync_r2_buckets(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"]
                });
                jQuery(function(){
                    jQuery.get("'. rest_url('mine-cloudvod/v1/cloudflare/r2/buckets') .'?_wpnonce='. wp_create_nonce('wp_rest') .'", function(data){
                        layer.close(index);
                        if(data.success){
                            layer.msg("'.__('Successfully synchronized buckets', 'mine-cloudvod').'");
                            var buckets = data.buckets;
                            var tcoptions = "";
                            var bucketNames = [];
                            for(var i=0; i<buckets.length; i++){
                                bucketNames.push(buckets[i].name);
                                tcoptions += "<option value=\""+buckets[i].name+"\">"+buckets[i].name+"</option>";
                            }
                            jQuery("select[name=\'mcv_settings[cloudflare_r2][bucket]\']").html(tcoptions);
                            jQuery.post("'.$ajaxUrl.'", {action: "mcv_save_r2_buckets", buckets: bucketNames, nonce: "'. wp_create_nonce('mcv_save_r2_buckets') .'"});
                        } else {
                            var msg = data.message ? data.message : "'.__('Get failed', 'mine-cloudvod').'";
                            layer.msg(msg);
                        }
                    }).fail(function(xhr){
                        layer.close(index);
                        var msg = "'.__('Get failed', 'mine-cloudvod').'";
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if(resp.message) msg = resp.message;
                        } catch(e){}
                        layer.msg(msg);
                    });
                });
            }
            function mcv_sync_alilive_domains(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_sync_alilive_domains", nonce: "'. wp_create_nonce('mcv_sync_alilive_domains') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized pull domains', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data;
                            for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i]["DomainName"]+"\">"+tdata[i]["DomainName"]+"</option>";
                            }
                            jQuery("select[name=\'mcv_settings[alivod][PullDomain]\']").html(tcoptions);
                        }
                    }, "json");
                });
            }
            function mcv_sync_alilive_pushdomain(){
                let pd = jQuery("select[name=\'mcv_settings[alivod][PullDomain]\']").val();
                if(!pd){
                    layer.msg("'.__('Please sync Pull Domains first', 'mine-cloudvod').'");
                    return;
                }
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_sync_alilive_domains", domain: pd, nonce: "'. wp_create_nonce('mcv_sync_alilive_domains') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized pull domains', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data;
                            for(var i=0; i<tdata.length; i++){
                            if(tdata[i]["Type"] == "publish")tcoptions += "<option value=\""+tdata[i]["DomainName"]+"\">"+tdata[i]["DomainName"]+"</option>";
                            }
                            jQuery("select[name=\'mcv_settings[alivod][PushDomain]\']").html(tcoptions);
                        }
                    }, "json");
                });
            }
            function mcv_modify_valid(type){
                if(type="pull"){
                    let pulldomain = jQuery("select[name=\'mcv_settings[alivod][PullDomain]\']").val();
                    if(!pulldomain){
                        layer.msg("'.__('Please sync Pull Domains first', 'mine-cloudvod').'");
                        return;
                    }
                    window.open("https://live.console.aliyun.com/#/domain/"+pulldomain+"/liveVideo/access");
                }
                else if(type="push"){
                    let pulldomain = jQuery("select[name=\'mcv_settings[alivod][PushDomain]\']").val();
                    if(!pulldomain){
                        layer.msg("'.__('Please sync Push Domains first', 'mine-cloudvod').'");
                        return;
                    }
                    window.open("https://live.console.aliyun.com/#/domain/"+pulldomain+"/liveEdge/access");
                }
            }
            function mcv_sync_ali_keyid(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_sync_ali_keyid", nonce: "'. wp_create_nonce('mcv_sync_ali_keyid') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully get the KeyId', 'mine-cloudvod').'");
                            jQuery("input[data-depend-id=keyId]").val(data.data.keyId);
                        }
                    }, "json");
                });
            }
            function mcv_sync_endtime(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                jQuery.post("'.$ajaxUrl.'", {action: "mcv_sync_endtime", nonce: "'. wp_create_nonce('mcv_sync_endtime') .'"}, function(data){
                    layer.close(index);
                    if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                    if(data.status=="1"){
                        layer.msg("'.__('Synchronize time successfully', 'mine-cloudvod').'");
                        jQuery("input[data-depend-id=endtime]").val(data.data.endtime);
                    }
                }, "json");
                });
            }
            function mcv_init_note(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                jQuery.post("'.$ajaxUrl.'", {action: "mcv_note_init", nonce: "'. wp_create_nonce('mcv_note_init') .'"}, function(data){
                    layer.close(index);
                    layer.msg(data.msg);
                }, "json");
                });
            }
            function mcv_init_lms(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                jQuery.post("'.$ajaxUrl.'", {action: "mcv_order_init", nonce: "'. wp_create_nonce('mcv-admin-nonce') .'"}, function(data){
                    layer.close(index);
                    layer.msg(data.data.msg);
                }, "json");
                });
            }
            function mcv_sync_transcode(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_transcode", nonce: "'. wp_create_nonce('mcv_asyc_transcode') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized task flow list', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data;
                            for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i][0]+"\">"+tdata[i][1]+" - "+tdata[i][0]+"</option>";
                            }
                            jQuery("select[data-depend-id=transcode]").html(tcoptions);
                        }
                    }, "json");
                });
            }
            function mcv_sync_ali_transcode(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_ali_transcode", nonce: "'. wp_create_nonce('mcv_asyc_ali_transcode') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized transcoding template', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data;
                            for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i][1]+"\">"+tdata[i][0]+"</option>";
                            }
                            jQuery("select[data-depend-id=transcode]").html(tcoptions);
                        }
                    }, "json");
                });
            }
            function mcv_sync_bunny_libs(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_sync_bunny_libs", nonce: "'. wp_create_nonce('mcv_sync_bunny_libs') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data;
                            for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i]["Id"]+"\">"+tdata[i]["Name"]+"</option>";
                            }
                            jQuery("select[data-depend-id=library]").html(tcoptions);
                        }
                    }, "json");
                });
            }
            function mcv_asyc_plyrconfig(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_plyrconfig", nonce: "'. wp_create_nonce('mcv_asyc_plyrconfig') .'"}, function(data){
                    layer.close(index);
                    if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                    if(data.status=="1"){
                        layer.msg("'.__('Successfully synchronized the player configuration list', 'mine-cloudvod').'");
                        var tcoptions = "", tdata = data.data;
                        for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i][0]+"\">"+tdata[i][1]+" - "+tdata[i][0]+"</option>";
                        }
                        jQuery("select[data-depend-id=plyrconfig]").html(tcoptions);
                    }
                }, "json");
                });
            }
            function mcv_sync_huawei_transcode(){
                wp.apiFetch({
                    path: "/mine-cloudvod/v1/huawei/vod/templates",
                }).then(function(response) {
                    console.log(response);
                    if(response.status == 0){
                        layer.msg(response.msg);
                        return;
                    }
                    var options = "", tdata = response.data.template_group_list;
                    for(var i=0; i<tdata.length; i++){
                        options += "<option value=\""+tdata[i]["name"]+"\">系统自带-"+tdata[i]["name"]+"</option>";
                    }
                    jQuery(".hw_transcode select").html(options);
                });
            }
            jQuery(function(){
                function getQrCode(met){
                    var index = layer.load(1, {
                        shade: [0.3,"#fff"] 
                    });
                    var tb = jQuery(":radio[data-depend-id=timebug]:checked").val();
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_buytimebug",met:met, timebug:tb, nonce: "'. wp_create_nonce('mcv_buytimebug') .'"}, function(data){
                        layer.closeAll();
                        if(data.status=="0"){
                            if(tb=="1200"&&met=="alipay"){
                            alert("请使用微信支付");
                            getQrCode("wxpay");}
                            return;
                        }
                        else if(data.status=="1"){
                        var tradeno = data.data.tradeno;
                        var color="#00a7ef";
                        var plogo="'.MINECLOUDVOD_URL.'/static/img/alipay.jpg";
                        var txt1 = "'.__('Alipay scan code payment', 'mine-cloudvod').'";
                        var txt2 = "'.__('Please use Alipay <br>to scan the QR code to pay', 'mine-cloudvod').'";
                        var h = "435.4px";
                        if(met=="wxpay"){
                            color="#00b54b";
                            plogo="'.MINECLOUDVOD_URL.'/static/img/wxzf.jpg";
                            txt1 = "'.__('Wechat scan code payment', 'mine-cloudvod').'";
                            txt2 = "'.__('Please use Wechat <br>to scan the QR code to pay', 'mine-cloudvod').'";
                            h="422.4px";
                        }
                        layer.open({
                            type: 1,
                            title: false,
                            area: ["300px", h],
                            content: \'<style>.layui-layer-content{overflow:hidden !important;}#btb_alipay_time,#btb_wxpay_time{width:50%;color: #fff;display:inline-block;margin:0;padding:0;border:none;cursor:pointer;padding:7px 0;background:#ddd;}#btb_alipay_time{background:#00a7ef;border-color:#00a7ef;}#btb_wxpay_time{background:#00b54b;border-color:#00b54b;}</style><div id="swal2-content" style="display: block;width:300px;text-align: center;"><div style="border-bottom: 2px solid \'+color+\';"><input type="button" id="btb_alipay_time" class="" value="Alipay"><input type="button" id="btb_wxpay_time" class="cur" value="Wechat"></div><div style=""> <h5 style="padding: 0;margin-top: 1.8em;"> <img src="\'+plogo+\'" style="display: inline-block;margin: 0;padding: 0;width: 120px;text-align: center;"> </h5> <div style="font-size: 16px;margin: 10px auto;">\'+txt1+\' \'+data.data.payamount+\' '.__('Yuan', 'mine-cloudvod').'</div> <div align="center" class="qrcode"> <img style="width: 200px;height: 200px;" src="\'+data.data.paycode+\'" id="buytimebug_qrcode"> </div> <div style="width: 100%;color: #f2f2f2;padding: 16px 0px;text-align: center;font-size: 14px;margin-top: 20px;background: \'+color+\';"> \'+txt2+\'<br> </div> </div></div>\',
                            success: function(layero, index){
                                jQuery("#btb_alipay_time", layero).on("click", function(){
                                    getQrCode("alipay");
                                });
                                jQuery("#btb_wxpay_time", layero).on("click", function(){
                                    getQrCode("wxpay");
                                });
                            }
                        });
                        }
                    }, "json");
                }
                jQuery("#buytimebug").click(function(){
                    getQrCode("wxpay");
                });
                function handle_sync(){
                    wp.apiFetch({path: "/mine-cloudvod/v1/doge/oss/sync_media"}).then(function(response){
                        let result = "";
                        response.forEach((file)=>{
                            result += "<p>已同步："+ file +"</p>";
                        });
                        jQuery("#sync_media_to_doge_result").prepend(result);
                        if( response.length == 1 ) handle_sync();
                        else jQuery("#sync_media_to_doge_result").prepend("<p>同步完成！</p>");
                    });
                }
                jQuery("#sync_media_to_doge").click(function(){
                    handle_sync();
                });
                function handle_sync_r2(){
                    wp.apiFetch({path: "/mine-cloudvod/v1/cloudflare/r2/sync_media"}).then(function(response){
                        let result = "";
                        response.forEach((file)=>{
                            result += "<p>已同步："+ file +"</p>";
                        });
                        jQuery("#sync_media_to_r2_result").prepend(result);
                        if( response.length == 1 ) handle_sync_r2();
                        else jQuery("#sync_media_to_r2_result").prepend("<p>同步完成！</p>");
                    });
                }
                jQuery("#sync_media_to_r2").click(function(){
                    handle_sync_r2();
                });
                function handle_sync_qiniu(){
                    wp.apiFetch({path: "/mine-cloudvod/v1/qiniu/kodo/sync_media"}).then(function(response){
                        let result = "";
                        response.forEach((file)=>{
                            result += "<p>已同步："+ file +"</p>";
                        });
                        jQuery("#sync_media_to_qiniu_result").prepend(result);
                        if( response.length == 1 ) handle_sync_qiniu();
                        else jQuery("#sync_media_to_qiniu_result").prepend("<p>同步完成！</p>");
                    });
                }
                jQuery("#sync_media_to_qiniu").click(function(){
                    handle_sync_qiniu();
                });
                function handle_sync_tccos(){
                    wp.apiFetch({path: "/mine-cloudvod/v1/qcloud/cos/sync_media"}).then(function(response){
                        let result = "";
                        response.forEach((file)=>{
                            result += "<p>已同步："+ file +"</p>";
                        });
                        jQuery("#sync_media_to_tccos_result").prepend(result);
                        if( response.length == 1 ) handle_sync_tccos();
                        else jQuery("#sync_media_to_tccos_result").prepend("<p>同步完成！</p>");
                    });
                }
                jQuery("#sync_media_to_tccos").click(function(){
                    handle_sync_tccos();
                });
                function handle_sync_alioss(){
                    wp.apiFetch({path: "/mine-cloudvod/v1/aliyun/oss/sync_media"}).then(function(response){
                        let result = "";
                        response.forEach((file)=>{
                            result += "<p>已同步："+ file +"</p>";
                        });
                        jQuery("#sync_media_to_alioss_result").prepend(result);
                        if( response.length == 1 ) handle_sync_alioss();
                        else jQuery("#sync_media_to_alioss_result").prepend("<p>同步完成！</p>");
                    });
                }
                jQuery("#sync_media_to_alioss").click(function(){
                    handle_sync_alioss();
                });
                jQuery("#mcv_sync_fwh_menu").click(function(){
                    wp.apiFetch({path: "/mine-cloudvod/v1/fwh/setmenu"}).then(function(response){
                        console.log(response);
                    });
                });
            });'));
        }
    }
}