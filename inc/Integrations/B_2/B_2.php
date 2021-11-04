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

            add_action('wp_footer', array($this, 'postType5_select'), 10);
            add_filter('mcv_filter_aliplayer', array($this, 'b2_mcv_filter_aliplayer'), 10, 5);
            add_filter('mcv_filter_tcplayer', array($this, 'b2_mcv_filter_tcplayer'), 10, 4);
            add_filter('mcv_filter_embedvideo', array($this, 'b2_mcv_filter_embedvideo'), 10, 4);
        }
    }

    public function shortcode_head()
    {
        global $post;
        if (is_singular() && \B2\Modules\Templates\Single::get_single_post_settings($post->ID, 'single_post_style') === 'post-style-5') {
            $videos = get_post_meta($post->ID, 'b2_single_post_video_group', true);

            if ($videos) {
                foreach ($videos as $k => $v) {
                    if ((isset($v['view_url']) && strpos($v['view_url'], '[mine_cloudvod') !== false) || strpos($v['url'], '[mine_cloudvod') !== false) {
                        $this->is_post_style_5 = true;
                    }
                }
            }
            unset($videos);
        }
        if ($this->is_post_style_5) {
            \MineCloudvod\Aliyun\Aliplayer::style_script();
            \MineCloudvod\Qcloud\Tcplayer::style_script();
        }
    }

    public function mcv_aliplayer_ajax_b2()
    {
        global $current_user;
        $uid = $current_user->ID;

        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;

        if ($nonce && !wp_verify_nonce($nonce, 'mcv-aliyunvod-aliplayer-' . $uid)) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));
            exit;
        }

        $url = !empty($_POST['url']) ? $_POST['url'] : null;
        $url = urldecode($url);
        preg_match('/\[mine_cloudvod id\=(\d*)\]/is', $url, $mcv_postid);
        $aliplayer = do_shortcode('[mine_cloudvod id=' . $mcv_postid[1] . ' from="b2"]');
        $aliplayer = explode('{{mcvsplit}}', $aliplayer);
        if (count($aliplayer) == 3) {
            if ($aliplayer[2] == 'aliyunvod') {
                echo json_encode(array('status' => '1', 'aliplayer' => $aliplayer));
            }
            if ($aliplayer[2] == 'qcloudvod') {
                echo json_encode(array('status' => '2', 'aliplayer' => $aliplayer));
            }
            if ($aliplayer[2] == 'embedvideo') {
                echo json_encode(array('status' => '3', 'aliplayer' => $aliplayer));
            }
        }

        exit;
    }
    public function b2_mcv_filter_aliplayer($video, $pconfig, $components, $events, $r)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $video = '<style>' . MINECLOUDVOD_SETTINGS['aliplayercss'] . '.video-role-info{z-index:4000;}</style>';
            $video .= '{{mcvsplit}}var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . ';aliplayerconfig_' . $r . '.id="post-style-5-player";aliplayerconfig_' . $r . '.height="100%"; ' . $components . 'window.mcv_b2_player=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});{{mcvsplit}}aliyunvod';
        }
        return $video;
    }
    public function b2_mcv_filter_tcplayer($video, $pconfig, $post_id, $parsed_block)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $videoId = sprintf('mcv-%s', md5(serialize($parsed_block)));
            $instance = 0;
            $video .= '{{mcvsplit}}';
            $video .= 'if(jQuery("#' . $videoId . '")){var tcplayerconfig_' . $post_id . $instance . '=' . json_encode($pconfig) . ';window.mcv_b2_player = TCPlayer(\'' . $videoId . '\', tcplayerconfig_' . $post_id . $instance . ');}';
            $video .= '{{mcvsplit}}qcloudvod';
        }
        return $video;
    }
    public function b2_mcv_filter_embedvideo($video, $src, $width, $height)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'b2') {
            $video .= '{{mcvsplit}}';
            $video .= 'function mcv_onresize(){document.querySelector("#mcv_embed_iframe").style.height = document.querySelector(".post-style-5-video-box").clientHeight+"px";}window.onresize=mcv_onresize;mcv_onresize();';
            $video .= '{{mcvsplit}}embedvideo';
        }
        return $video;
    }

    public function postType5_select()
    {
        if ($this->is_post_style_5) {
            global $current_user;
            $nonce                = wp_create_nonce('mcv-aliyunvod-aliplayer-' . $current_user->ID);
            $ajaxUrl = admin_url("admin-ajax.php");
            wp_add_inline_script('b2-js-single', mcv_trim("
        jQuery(function(){
            postType5.select = function(index){
                this.index = index;
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
                if(this.url.indexOf('[mine_cloudvod') > -1){
                    jQuery('#post-style-5-player').html('<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" style=\"margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;\"><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>');
                    this.\$http.post('$ajaxUrl','action=mcv_aliplayer_ajax_b2&nonce=$nonce&url='+this.url).then(res=>{
                        if(window.mcv_b2_player){window.mcv_b2_player.dispose();window.mcv_b2_player=null;}
                        jQuery('#post-style-5-player').empty();
                        if(res.data.status == '1'){jQuery('#post-style-5-player').before(res.data.aliplayer[0]);eval(res.data.aliplayer[1]);}
                        if(res.data.status == '2'){jQuery('#post-style-5-player').html(res.data.aliplayer[0]);eval(res.data.aliplayer[1]);}
                        if(res.data.status == '3'){jQuery('#post-style-5-player').html(res.data.aliplayer[0]);eval(res.data.aliplayer[1]);}
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
            };
            jQuery('.post-video-list ul').bind('DOMNodeInserted', function(e) {
                postType5.select(0);
                jQuery('.post-video-list ul').unbind('DOMNodeInserted');
            });  
        });
            "));
        }
    }
}
