<?php

namespace MineCloudvod\Integrations\Ri;

class RiProV2
{
    private $is_cao_video = false;
    public function __construct()
    {
        $theme = wp_get_theme();
        if(strtolower($theme->get('TextDomain')) == 'ripro-v2' || strtolower($theme->get('Template')) == 'ripro-v2'){
            add_action('template_redirect',array($this, 'shortcode_head'));
            add_action('wp_ajax_mcv_ajax_player_riprov2', array($this, 'mcv_ajax_player_riprov2'));
            add_action('wp_ajax_nopriv_mcv_ajax_player_riprov2', array($this, 'mcv_ajax_player_riprov2'));
            add_action('wp_footer',array($this, 'ri_script'));
            add_filter('mcv_filter_aliplayer', array($this, 'riprov2_mcv_filter_aliplayer'), 10, 5);
            add_filter('mcv_filter_tcplayer', array($this, 'riprov2_mcv_filter_tcplayer'), 10, 4);
            add_filter('mcv_filter_embedvideo', array($this, 'riprov2_mcv_filter_embedvideo'), 10, 4);
        }
    }

    public function shortcode_head(){
        if(is_singular()){
            global $post, $current_user;
            $user_id = $current_user->ID;
            $post_id = $post->ID;
            $mcv = false;

            $cao_video = get_post_meta($post_id,'cao_video',true);
            if($cao_video){
                $video_textarea = get_post_meta($post_id,'video_url',true);
                $videos         = explode(PHP_EOL, trim($video_textarea));
                if($videos){
                    foreach($videos as $k => $v){
                        if(strpos($v,'[mine_cloudvod') !== false){
                            $this->is_cao_video = true;
                            break;
                        }
                    }
                }
                
                if($this->is_cao_video){
                    \MineCloudvod\Aliyun\Aliplayer::style_script();
                    \MineCloudvod\Qcloud\Tcplayer::style_script();
                    if(count($videos) === 1){
                        if(class_exists('\RiClass')){
                            $ri        = new \RiClass($post_id, $user_id);
                            $IS_PAID        = $ri->is_pay_post();
                            $is_free_video  = (int) get_post_meta($post_id, 'cao_is_video_free', true);
                            if($is_free_video || $IS_PAID > 0)
                                wp_add_inline_script( 'mine_tcplayer', "window.mcv_single_video_riprov2 = '$videos[0]';" );
                        }
                    }
                }
                unset($videos);
            }
        }
    }

    public function mcv_ajax_player_riprov2(){
        global $current_user;
        $uid = $current_user->ID;
        
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;

        if ($nonce && !wp_verify_nonce($nonce, 'mcv-aliyunvod-aliplayer-' . $uid)) {
            echo json_encode(array('status' => '0', 'msg' => __( 'Illegal request', 'mine-cloudvod' )));exit;
        }

        $url = !empty($_POST['url']) ? $_POST['url'] : null;
        $url = urldecode($url);
        preg_match('/\[mine_cloudvod id\=(\d*)\]/is', $url, $mcv_postid);
        $aliplayer = do_shortcode('[mine_cloudvod id=' . $mcv_postid[1] . ' from="riprov2"]');
        $aliplayer = explode('{{mcvsplit}}', $aliplayer);
        if(count($aliplayer) == 3){
            if($aliplayer[2] == 'aliyunvod'){
                echo json_encode(array('status' => '1', 'player' => $aliplayer));
            }
            if($aliplayer[2] == 'qcloudvod'){
                echo json_encode(array('status' => '2', 'player' => $aliplayer));
            }
            if($aliplayer[2] == 'embedvideo'){
                echo json_encode(array('status' => '3', 'player' => $aliplayer));
            }
        }
        
        exit;

    }
    public function riprov2_mcv_filter_aliplayer($video, $pconfig, $components, $events, $r)
    {
        global $mcv_block_ajax_from;
        $divId = 'rizhuti-video';
        if($mcv_block_ajax_from == 'riprov2'){
            $video = '<style>'.MINECLOUDVOD_SETTINGS['aliplayercss'].'.video-role-info{z-index:4000;}</style>';
            $video .= '{{mcvsplit}}var aliplayerconfig_'.$r.'='.json_encode($pconfig).';aliplayerconfig_'.$r.'.id="'.$divId.'";aliplayerconfig_'.$r.'.height="100%"; '.$components.'
            if(window.mcv_aliplayer_riprov2)window.mcv_aliplayer_riprov2.dispose();window.mcv_aliplayer_riprov2=new Aliplayer(aliplayerconfig_'.$r.', function (player) {'.$events.'});{{mcvsplit}}aliyunvod';
        }
        return $video;
    }
    public function riprov2_mcv_filter_tcplayer($video, $pconfig, $post_id, $instance)
    {
        global $mcv_block_ajax_from;
        $divId = 'rizhuti-video';
        if($mcv_block_ajax_from == 'riprov2'){
            $videoId = sprintf( 'mcv-%s', md5($video) );
            $video = '<style>.video-js{height:100%;padding:inherit;}</style><video id="'.$videoId.'" width="100%" height="100%" preload="none" controls="controls" playsinline webkit-playsinline></video>';
            $video .= '{{mcvsplit}}';
            $video .= 'if(jQuery("#'.$videoId.'")){var tcplayerconfig_'.$post_id. $instance.'='.json_encode($pconfig).';if(window.tcplayer_' . $post_id . $instance . ')window.tcplayer_' . $post_id . $instance . '.dispose(); window.tcplayer_'.$post_id. $instance.' = TCPlayer(\''.$videoId.'\', tcplayerconfig_'.$post_id. $instance.');}';
            $video .='{{mcvsplit}}qcloudvod';
        }
        return $video;
    }
    public function riprov2_mcv_filter_embedvideo($video, $src, $width, $height)
    {
        global $mcv_block_ajax_from;
        if($mcv_block_ajax_from == 'riprov2'){
            $video .= '{{mcvsplit}}';
            $video .= '';
            $video .='{{mcvsplit}}embedvideo';
        }
        return $video;
    }

    public function ri_script(){
        if($this->is_cao_video){
            global $current_user;
            $nonce				= wp_create_nonce('mcv-aliyunvod-aliplayer-' . $current_user->ID);
            $ajaxUrl = admin_url("admin-ajax.php");
            wp_add_inline_script( 'dplayer', mcv_trim("
                jQuery(function(){
                    var h = jQuery('#rizhuti-video').height();
                    jQuery('#rizhuti-video-page .switch-video').unbind('click');
                    if(window.mcv_single_video_riprov2){
                        jQuery('#rizhuti-video').removeClass();
                        jQuery('#rizhuti-video').removeAttr('style');
                        jQuery('#rizhuti-video').empty();
                        jQuery('#rizhuti-video').height(h);
                        jQuery('#rizhuti-video').html('<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" style=\"margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;\"><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>');
                        jQuery.post('$ajaxUrl',{action:'mcv_ajax_player_riprov2', nonce:'$nonce', url: window.mcv_single_video_riprov2},function(res){
                            jQuery('#rizhuti-video').empty();
                            if(res.status == '1'){jQuery('#rizhuti-video').before(res.player[0]);eval(res.player[1]);}
                            if(res.status == '2'){jQuery('#rizhuti-video').html(res.player[0]);eval(res.player[1]);}
                            if(res.status == '3'){jQuery('#rizhuti-video').html(res.player[0]);eval(res.player[1]);}
                            console.log(res.player[1]);
                        }, 'json');
                    }
                    jQuery('#rizhuti-video-page .switch-video').click(function(){
                        var js_video_content = jQuery('#rizhuti-video .dplayer-mask').html();
                        jQuery('#rizhuti-video').removeClass();
                        jQuery('#rizhuti-video').removeAttr('style');
                        jQuery('#rizhuti-video').empty();
                        jQuery('#rizhuti-video').height(h);
                        jQuery('#rizhuti-video').html('<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" style=\"margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;\"><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>');
                        jQuery('#rizhuti-video-page .switch-video').removeClass('active'), jQuery(this).addClass('active');
                        var js_video_url = jQuery(this).attr('data-url');
                        var js_video_pic = jQuery(this).attr('data-pic');
                        if(js_video_url.indexOf('[mine_cloudvod') > -1){
                            jQuery.post('$ajaxUrl',{action:'mcv_ajax_player_riprov2', nonce:'$nonce', url: js_video_url},function(res){
                                jQuery('#rizhuti-video').empty();
                                if(res.status == '1'){jQuery('#rizhuti-video').before(res.player[0]);eval(res.player[1]);}
                                if(res.status == '2'){jQuery('#rizhuti-video').html(res.player[0]);eval(res.player[1]);}
                                if(res.status == '3'){jQuery('#rizhuti-video').html(res.player[0]);eval(res.player[1]);}
                                console.log(res.player[1]);
                            }, 'json');
                        }
                        else{
                            const dp = new DPlayer({
                                container: document.getElementById(\"rizhuti-video\"),
                                theme: \"#fd7e14\",
                                screenshot: !1,
                                video: {
                                    url: js_video_url,
                                    type: \"auto\",
                                    pic: js_video_pic
                                }
                            });
                            var video_vh = \"inherit\";
                            if ($(\".dplayer-video\").bind(\"loadedmetadata\", function() {
                                var e = this.videoWidth || 0,
                                    i = this.videoHeight || 0,
                                    a = $(\"#rizhuti-video\").width();
                                i > e && (video_vh = e / i * a, $(\".dplayer-video\").css(\"max-height\", video_vh))
                            }), \"\" == js_video_url) {
                                var mask = $(\".dplayer-mask\");
                                mask.show(), mask.hasClass(\"content-do-video\") || (mask.append(js_video_content), $(\".dplayer-video-wrap\").addClass(\"video-filter\"))
                            } else {
                                var notice = $(\".dplayer-notice\");
                                notice.hasClass(\"dplayer-notice\") && (notice.css(\"opacity\", \"0.8\"), notice.append('<i class=\"fa fa-unlock-alt\"></i> 您已获得播放权限'), setTimeout(function() {
                                    notice.css(\"opacity\", \"0\")
                                }, 2e3)), dp.on(\"fullscreen\", function() {
                                    $(\".dplayer-video\").css(\"max-height\", \"unset\")
                                }), dp.on(\"fullscreen_cancel\", function() {
                                    $(\".dplayer-video\").css(\"max-height\", video_vh)
                                })
                            }
                        }
                        console.log(jQuery(this).attr('data-url'));
                    });
                    jQuery('#rizhuti-video-page .switch-video:first').click();
                });
            "));
        }
    }
}
//<ul class="pricing-options"><li><span>用户购买价格：</span><b>0.1金币</b></li><li><span>用户购买价格：</span><b>0.1金币</b></li><li><span>用户购买价格：</span><b>0.1金币</b></li></ul><button type="button" class="btn btn-sm btn-dark mb-4 click-pay-post" data-postid="2138" data-nonce="d854775db4" data-price="0.1">立即购买</button></div></div>