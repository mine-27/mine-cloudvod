<?php
namespace MineCloudvod\Integrations\B_2;

class B_2
{
    private $is_post_style_5 = false;
    public function __construct()
    {
        $theme = wp_get_theme();
        if (strtolower($theme->get('TextDomain')) == 'b2' || strtolower($theme->get('Template')) == 'b2') {
            add_action('template_redirect', array($this, 'shortcode_head'));
            add_action('wp_ajax_mcv_aliplayer_ajax_b2', array($this, 'mcv_aliplayer_ajax_b2'));
            add_action('wp_ajax_nopriv_mcv_aliplayer_ajax_b2', array($this, 'mcv_aliplayer_ajax_b2'));

            add_action('wp_enqueue_scripts', array($this, 'postType5_select'), 99);
            add_filter('mcv_filter_aliplayer', array($this, 'b2_mcv_filter_aliplayer'), 10, 6);
            add_filter('mcv_filter_tcplayer', array($this, 'b2_mcv_filter_tcplayer'), 10, 5);
            add_filter('mcv_filter_embedvideo', array($this, 'b2_mcv_filter_embedvideo'), 10, 4);
            add_filter('mcv_filter_audioplayer', array($this, 'b2_mcv_filter_audioplayer'), 10, 3);
            add_filter('mcv_filter_dplayer', array($this, 'b2_mcv_filter_dplayer'), 10, 4);
            add_filter('mcv_filter_dogevcloud', array($this, 'b2_mcv_filter_dogevcloud'), 10, 3);
            // B2 vip可直接观看所有课程
            add_filter('mcv_lms_is_enrolled', array($this, 'mcv_is_b2_vip'));
            add_action('mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );

            add_filter('mcv_get_video_price', array( $this, 'get_video_price' ), 10, 3);
            add_filter('mcv_mini_user_login', array( $this, 'get_user_id' ), 10, 2);
            add_action('mcv_mini_user_inserted', array( $this, 'user_inserted' ), 10, 2);
        }
    }

    public function get_user_id( $uid, $data ){
        global $wpdb;
        $res = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 按唯一微信标识精确查用户，刻意性能优化 
            $wpdb->prepare(
                'SELECT user_id FROM '. $wpdb->usermeta . ' WHERE meta_key=\'zrz_weixin_uid\' AND meta_value=%s',
                $data['unionid']
            )
         );
        if( !$res ) $res = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 按唯一微信标识精确查用户，刻意性能优化 
            $wpdb->prepare(
                'SELECT user_id FROM '. $wpdb->usermeta . ' WHERE meta_key=\'zrz_weixin_unionid\' AND meta_value=%s',
                $data['unionid']
            )
         );
        if( $res ) $uid = $res;
        return $uid;
    }

    public function user_inserted( $user, $data ){
        if( isset( $data['unionid'] ) ){
            update_user_meta( $user->ID, 'zrz_weixin_unionid', $data['unionid'] );
            update_user_meta( $user->ID, 'zrz_weixin_uid', $data['unionid'] );
        }
    }

    public function get_video_price( $price, $post, $source ){
        if( !$price ){
            $videos = get_post_meta($post->ID, 'b2_single_post_video_group', true);
            if ($videos) {
                foreach ($videos as $k => $v) {
                    if ( strpos($v['url'], '[mine_cloudvod') !== false  ) {
                        $vid = urldecode($v['url']);
                        $vid = str_replace(['[mine_cloudvod id=', ']', 'http://', 'https://'], '', $vid);
                        $vpost = get_post(trim($vid));
                        if($vpost){
                            $price = mcv_get_vodhub_price( $vpost->post_content, $source );
                            if($price) break;
                        }
                    }
                }
            }
        }
        return $price;
    }

    public function mcv_is_b2_vip($canplay){
        if( isset( MINECLOUDVOD_SETTINGS['b2']['vip_free'] ) && MINECLOUDVOD_SETTINGS['b2']['vip_free'] ){
            $userid = get_current_user_id();
            $is_vip = \B2\Modules\Common\User::get_user_lv($userid);
            if($is_vip['vip']['lv']){
                return 1;
            }
        }
        return $canplay;
    }

    public function shortcode_head()
    {
        global $post;
        if (is_singular() && \B2\Modules\Templates\Single::get_single_post_settings($post->ID, 'single_post_style') === 'post-style-5') {
            $videos = get_post_meta($post->ID, 'b2_single_post_video_group', true);

            if ($videos) {
                foreach ($videos as $k => $v) {
                    if ((isset($v['view_url']) && strpos($v['view_url'], '[mine_cloudvod') !== false) || strpos($v['url'], '[mine_cloudvod') !== false || (isset($v['dogecloud_id']) && !empty($v['dogecloud_id'])) ) {
                        $this->is_post_style_5 = true;
                    }
                }
            }
            unset($videos);
        }
        if ($this->is_post_style_5) {
            global $mcv_classes, $ActiveAddons;
            $mcv_classes->Aliplayer && $mcv_classes->Aliplayer::style_script();
            $ActiveAddons['qcloud']??false && $ActiveAddons['qcloud']->Tcplayer::style_script();
            $mcv_classes->Dplayer && $mcv_classes->Dplayer::style_script();
            $mcv_classes->Audioplayer && $mcv_classes->Audioplayer::style_script();
        }
    }

    public function mcv_aliplayer_ajax_b2()
    {
        global $current_user, $post;
        $uid = $current_user->ID;

        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;

        if (!$nonce || !wp_verify_nonce($nonce, 'mcv-aliyunvod-aliplayer-' . $uid)) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));
            exit;
        }

        if( !$post ){
            $pid = sanitize_text_field(wp_unslash($_POST['pid']??''));
            if( !is_numeric( $pid ) )return;
            $post = get_post( $pid );
        }
        
        $aliplayer = '';
        $dgid = sanitize_text_field(wp_unslash($_POST['dgid']??false));
        if($dgid && is_numeric($dgid)){
            $media = \MineCloudvod\RestApi\Dogecloud::get_videoinfo( ['vid'=>$dgid] );

            $aliplayer = do_shortcode('[mcv_dogevcloud vcode="' . $media['data']['vcode'] . '" from="b2"]');
            // var_dump($aliplayer);
            // $aliplayer = '{{mcvsplit}}'.$aliplayer.'{{mcvsplit}}dplayer';
        }
        else{
            $url = !empty($_POST['url']) ? sanitize_text_field(wp_unslash($_POST['url'])) : null;
            $url = urldecode($url);
            preg_match('/\[mine_cloudvod id\=(\d*)\]/is', $url, $mcv_postid);
            $aliplayer = do_shortcode('[mine_cloudvod id=' . $mcv_postid[1] . ' from="b2"]');
        }
        
        
        $aliplayer = explode('{{mcvsplit}}', $aliplayer);
        if (count($aliplayer) == 3) {
            if ($aliplayer[2] == 'aliyunvod') {
                echo wp_json_encode(array('status' => '1', 'aliplayer' => $aliplayer));
            }
            if ($aliplayer[2] == 'qcloudvod') {
                echo wp_json_encode(array('status' => '2', 'aliplayer' => $aliplayer));
            }
            if ($aliplayer[2] == 'embedvideo') {
                echo wp_json_encode(array('status' => '3', 'aliplayer' => $aliplayer));
            }
            if ($aliplayer[2] == 'audioplayer') {
                echo wp_json_encode(array('status' => '4', 'aliplayer' => $aliplayer));
            }
            if ($aliplayer[2] == 'dplayer') {
                echo wp_json_encode(array('status' => '5', 'aliplayer' => $aliplayer));
            }
        }

        exit;
    }
    public function b2_mcv_filter_dogevcloud($video, $pconfig, $parsed_block){
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $r = md5(serialize($parsed_block));
            $pconfig_json = json_encode($pconfig);
            $pconfig_json = preg_replace('/("key":"video-info","click":)("([^"]*?)")/is', '$1$3', $pconfig_json);
            $pconfig_json = preg_replace('/("container"\:)"document.getElementById\(\\\"([^\\\]*?)\\\"\)"/is', '$1document.getElementById("post-style-5-player")', $pconfig_json);
            $video = '';
            $video .= '{{mcvsplit}}var dplayerconfig_' . $r . '=' . $pconfig_json . '; window.mcv_b2_player=new McvDPlayer(dplayerconfig_' . $r . ');{{mcvsplit}}dplayer';
        }
        return $video;
    }
    public function b2_mcv_filter_dplayer($video, $pconfig, $parsed_block, $events){
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $r = md5(serialize($parsed_block));
            $pconfig_json = json_encode($pconfig);
            $pconfig_json = preg_replace('/("key":"video-info","click":)("([^"]*?)")/is', '$1$3', $pconfig_json);
            $pconfig_json = preg_replace('/("container"\:)"document.getElementById\(\\\"([^\\\]*?)\\\"\)"/is', '$1document.getElementById("post-style-5-player")', $pconfig_json);
            $events = str_replace(['window.dplayer_mcv_'.$r, 'mcv_'.$r], ['window.mcv_b2_player', 'post-style-5-player'], $events);
            $video = '';
            $video .= '{{mcvsplit}}var dplayerconfig_' . $r . '=' . $pconfig_json . '; window.mcv_b2_player=new McvDPlayer(dplayerconfig_' . $r . ');'. $events .'{{mcvsplit}}dplayer';
        }
        return $video;
    }
    public function b2_mcv_filter_aliplayer($video, $pconfig, $components, $events, $r, $attributes){
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $divId = sprintf('mcv_%s', md5(serialize($attributes)));
            $video = '<style>' . MINECLOUDVOD_SETTINGS['aliplayercss'] . '.video-role-info{z-index:4000;}</style>';
            $components = str_replace('aliplayerconfig_270', 'aliplayerconfig_b2', $components);
            $video .= '{{mcvsplit}}var aliplayerconfig_b2=' . json_encode($pconfig) . ';aliplayerconfig_b2.height="100%"; ' . $components . 'window.mcv_b2_player=new Aliplayer(aliplayerconfig_b2, function (player) {' . $events . '});{{mcvsplit}}aliyunvod';
            $video = str_replace(['window.aliplayer_'.$divId, $divId], ['window.mcv_b2_player', 'post-style-5-player'], $video);
            // $video = str_replace($pconfig['id'], 'post-style-5-player', $video);
        }
        return $video;
    }
    public function b2_mcv_filter_audioplayer($video, $parsed_block, $inlineScript)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $video .= '{{mcvsplit}}'.$inlineScript.'{{mcvsplit}}audioplayer';
        }
        return $video;
    }
    public function b2_mcv_filter_tcplayer($video, $pconfig, $post_id, $parsed_block, $events)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $videoId = sprintf('mcv_%s', md5(serialize($parsed_block['attrs'])));
            $instance = 0;
            $video .= '{{mcvsplit}}';
            $video .= 'if(jQuery("#' . $videoId . '")){var tcplayerconfig_' . $post_id . $instance . '=' . json_encode($pconfig) . ';window.mcv_b2_player = TCPlayer(\'' . $videoId . '\', tcplayerconfig_' . $post_id . $instance . ');'.$events.'}';
            $video = str_replace('window.tcplayer_'.$videoId, 'window.mcv_b2_player', $video);
            $video .= '{{mcvsplit}}qcloudvod';
        }
        return $video;
    }
    public function b2_mcv_filter_embedvideo($video, $src, $width, $height)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $video .= '{{mcvsplit}}';
            $video .= 'function mcv_onresize(){document.querySelector("#mcv_embed_iframe").style.height = (document.querySelector(".post-style-5-video-box").clientHeight)+"px";}window.onresize=mcv_onresize;mcv_onresize();';
            $video .= '{{mcvsplit}}embedvideo';
        }
        return $video;
    }

    public function postType5_select()
    {
        if ($this->is_post_style_5) {
            global $current_user, $post;
            $nonce                = wp_create_nonce('mcv-aliyunvod-aliplayer-' . $current_user->ID);
            $ajaxUrl = admin_url("admin-ajax.php");
            wp_enqueue_script( 'jquery' );
            wp_add_inline_script('b2-js-single', mcv_trim("
        jQuery(function(){
            postType5.selectVideo = (index,res)=>{};
            postType5.select = function(index){
                this.index = index;
                if(this?.lochostVideo && !this.lochostVideo(index)){
                    this.selectVideo(index)
                }else{
                    postVideoList.index = this.index;
                    if(this.user.allow){
                        this.url = this.videos[index].url;
                    }else{
                        this.url = this.videos[index].view;
                    }
                    if(!this.user.allow){
                        this.show = true;
                    }
                    if(!!this.player)this.player.destroy();
                    jQuery('#post-style-5-player').removeClass();
                    jQuery('#post-style-5-player').removeAttr('style');
                    jQuery('#post-style-5-player').empty();
                    if(this.url.indexOf('[mine_cloudvod') > -1 || this.videos[index].id){
                        jQuery('#post-style-5-player').html('<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" style=\"margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;\"><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>');
                        this.\$http.post('$ajaxUrl','action=mcv_aliplayer_ajax_b2&pid=".$post->ID."&nonce=$nonce&url='+this.url+(this.videos[index].id?'&dgid='+this.videos[index].id:'')).then(res=>{
                            if(window.mcv_b2_player){try{window.mcv_b2_player.dispose();}catch(e){}try{window.mcv_b2_player.destroy();}catch(e){}window.mcv_b2_player=null;}
                            jQuery('#post-style-5-player').empty();
                            if(res.data.status == '1'){jQuery('#post-style-5-player').before(res.data.aliplayer[0]);ev"."al(res.data.aliplayer[1]);}
                            else{jQuery('#post-style-5-player').html(res.data.aliplayer[0]);ev"."al(res.data.aliplayer[1]);}
                        });
                    }
                    else{
                        this.player = new DPlayer({
                            container: document.getElementById('post-style-5-player'),
                            screenshot: false,
                            video: {
                                url: this.url,
                                pic: this.videos[index].poster,
                                type:'auto'
                            },
                            contextmenu:[],
                            airplay:true,
                            mutex:true,
                            hotkey:true,
                            preload:true,
                            logo:b2_global.default_video_logo,
                            autoplay:false
                        });
                    }
                }
            };
            setTimeout('postType5.select(0)', 1000);
            jQuery('.post-video-list').height(document.querySelector('.post-style-5-video-box-in').clientHeight);
        });
            "));
        }
    }

    public function admin_options(){
        $prefix = 'mcv_settings';
        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_b2',
            'title' => 'B2',
            'icon'  => 'fa fa-circle',
            'fields' => array(
                array(
                    'id'        => 'b2',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'vip_free',
                            'type'  => 'switcher',
                            'title' => __('Vip can learn all courses for free', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false,
                        ),
                    ),
                ),
            )
          ) );
    }
}
