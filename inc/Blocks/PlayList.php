<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Blocks;

use MineCloudvod\Models\McvVideo;

class PlayList{
    public function __construct(){
        global $pagenow;
        if ($pagenow != 'post.php'){
            add_action('wp_ajax_mcv_playlist_ajax', array($this, 'mcv_playlist_ajax'));
            add_action('wp_ajax_nopriv_mcv_playlist_ajax', array($this, 'mcv_playlist_ajax'));
            // add_filter('render_block_data', array($this, 'render_playlist'), 10, 2);
            add_filter('mcv_filter_aliplayer', array($this, 'mcv_playlist_filter_aliplayer'), 10, 6);
            add_filter('mcv_filter_tcplayer', array($this, 'mcv_playlist_filter_tcplayer'), 10, 5);
            add_filter('mcv_filter_embedvideo', array($this, 'mcv_playlist_filter_embedvideo'), 10, 4);
            add_filter('mcv_filter_audioplayer', array($this, 'mcv_playlist_filter_audioplayer'), 10, 3);
            add_filter('mcv_filter_dplayer', array($this, 'mcv_playlist_filter_dplayer'), 10, 4);
        }
        add_action( 'init',     [ $this, 'mcv_register_block'] );
    }

    public function mcv_register_block(){

        register_block_type( MINECLOUDVOD_PATH . '/build/playlist/');
        
    }
    public function mcv_playlist_ajax(){
        global $current_user, $post;
        $uid = $current_user->ID;

        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;

        if (!$nonce || !wp_verify_nonce($nonce, 'mcv-playlist-' . $uid)) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));
            exit;
        }

        if( !$post ){
            $pid = sanitize_text_field(wp_unslash($_POST['pid'] ?? ''));
            if( !is_numeric( $pid ) )return;
            $post = get_post( $pid );
        }
        
        $mcvid = !empty($_POST['mcvid']) ? sanitize_text_field(wp_unslash($_POST['mcvid'])) : null;
        $aliplayer = do_shortcode('[mine_cloudvod id=' . $mcvid . ' from="mcv_playlist"]');
        $aliplayer = explode('{{mcvsplit}}', $aliplayer);
        // var_dump('1',$aliplayer);
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

    public function mcv_playlist_filter_dplayer($video, $pconfig, $attributes, $events){
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'mcv_playlist') {
            $divId = sprintf('mcv_%s', md5(serialize($attributes)));
            $pconfig_json = json_encode($pconfig);
            $pconfig_json = preg_replace('/("key":"video-info","click":)("([^"]*?)")/is', '$1$3', $pconfig_json);
            $pconfig_json = preg_replace('/("container"\:)"document.getElementById\(\\\"([^\\\]*?)\\\"\)"/is', '$1document.getElementById("' . $divId . '")', $pconfig_json);
            if( !$video ) $video = '<div id="'.$divId.'"></div>';
            if(isset($_POST['an']) && sanitize_text_field(wp_unslash($_POST['an']))){ // phpcs:ignore WordPress.Security.NonceVerification.Missing -- 播放器 AJAX 渲染读连播参数，主入口已校验 nonce
                $events .= 'window.mcv_playlist_dplayer.on("ended", function () {
                    jQuery(".mcv-playlist-ul li.cur", jQuery("#' . $divId .'").parent().parent().next()).next().trigger("click");
                });';
            }
            $events = str_replace( 'window.dplayer_'.$divId, 'window.mcv_playlist_dplayer', $events );
            $video .= '{{mcvsplit}}var dplayerconfig_' . $divId . '=' . $pconfig_json . '; window.mcv_playlist_dplayer=new McvDPlayer(dplayerconfig_' . $divId . ');'.$events.'jQuery("#' . $divId . '").css({"padding-top":"0px", height:"100%"});{{mcvsplit}}dplayer';
            
        }
        return $video;
    }
    public function mcv_playlist_filter_audioplayer($video, $parsed_block, $inlineScript)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'mcv_playlist') {
            $video .= '{{mcvsplit}}'.$inlineScript.'{{mcvsplit}}audioplayer';
        }
        return $video;
    }
    public function render_playlist($parsed_block, $enqueue = true){
        global $pagenow;
        if ($enqueue && $pagenow == 'post.php') return false;
        $video = '';
        if ($parsed_block['blockName'] == "mine-cloudvod/video-playlist" && isset($parsed_block['attrs']['mcvTag'])) {
            $plName = $parsed_block['attrs']['plName'] ?? __('Video Playlist', 'mine-cloudvod');
            $mcvTag = (int)$parsed_block['attrs']['mcvTag'];
            $show = $parsed_block['attrs']['show'] ?? false;
            $mcvVideo = new McvVideo();
            $videoList = $mcvVideo->all([
                'tax_query' => [[ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 按视频标签筛选，业务必需
                    'taxonomy'  => 'mcv_video_tag',
                    'field'     => 'term_id',
                    'terms'     => [$mcvTag],
                    'include_children' => false,
                    'operator'   => 'IN',
                ]],
                'orderby' => ['meta_value_num' => 'ASC'],
                'meta_key' => 'sort_no', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 按排序号排序，业务必需
            ]);
            $videoListStr = '';
            $vli = 1;
            foreach ($videoList as $video) {
                $videoListStr .= '<li data-id="' . $video->ID . '" ' . ($vli == 1 ? 'class="cur"' : '') . '>' . ($vli == 1 ? '<div class="playing-icon"><svg width="16" height="16" viewBox="0 0 135 140" xmlns="http://www.w3.org/2000/svg"> <rect y="10" width="15" height="120" rx="6"> <animate attributeName="height" begin="0.5s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0.5s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> <rect x="30" y="10" width="15" height="120" rx="6"> <animate attributeName="height" begin="0.25s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0.25s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> <rect x="60" width="15" height="140" rx="6"> <animate attributeName="height" begin="0s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> <rect x="90" y="10" width="15" height="120" rx="6"> <animate attributeName="height" begin="0.25s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0.25s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> <rect x="120" y="10" width="15" height="120" rx="6"> <animate attributeName="height" begin="0.5s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0.5s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> </svg></div>' : '') . '<span>' . $vli . '. ' . $video->post_title . '</span></li>';
                $vli++;
            }
            $divId = sprintf('mcv_%s', md5(serialize($parsed_block['attrs'])));
            $video = '
            <div id="' . $divId . '" class="mcv-videos' . ($show ? '' : ' mcv-full-width') . '">
                <div class="mcv-player">
                    <div id="plyr_' . $divId . '"></div>
                    <div class="wide-switch">
                        <span class="btn_switch_bg"><svg viewBox="0 0 9 59" width="9" height="59"><path d="M3.8,5.1C1.7,4.3,0.2,2.4,0,0h0v5v4v41v5v3.9c0.6-1.9,2.1-3.4,4-4v0c2.9-0.7,5-3.2,5-6.3v-37  C9,8.4,6.8,5.7,3.8,5.1z"></path></svg></span>
                        <i class="icon_sm icon_left_sm">
                            <svg id="svg_icon_left_sm" viewBox="0 0 16 16"><path d="M6.427 8l3.284 3.284a1.01 1.01 0 0 1-1.427 1.427L4.29 8.716A1.003 1.003 0 0 1 3.995 8a1.005 1.005 0 0 1 .295-.717l3.994-3.994a1.01 1.01 0 0 1 1.427 1.427L6.427 8z" fill="#e6e6e6"></path></svg>
                        </i>
                        <i class="icon_sm icon_right_sm">
                            <svg id="svg_icon_right_sm" viewBox="0 0 16 16"><path d="M11.71 8.716L7.716 12.71a1.01 1.01 0 0 1-1.427-1.427L9.573 8 6.289 4.716a1.01 1.01 0 0 1 1.427-1.427l3.994 3.994c.198.198.296.458.295.717.001.259-.097.518-.295.716z" fill="#e6e6e6"></path></svg>
                        </i>
                    </div>
                </div>
                <div class="mcv-playlist"' . ($show ? '' : ' style="display:none;"') . '>
                    <div class="mcv-title">
                        ' . $plName . '
                        <span>共<b>' . count($videoList) . '</b>节</span>
                    </div>
                    <div class="mcv-playlist-ul">
                        <ul>
                        ' . $videoListStr . '
                        </ul>
                    </div>
                </div>
            </div>';
            $inlineScript = $this->mcv_playlist_script($enqueue, $parsed_block['attrs']);
            //for elementor
            if (!$enqueue) {
                $video .= '<script>' . $inlineScript . '</script>';
                return $video;
            }
    
            if ($video) {
                $video = mcv_trim($video);
                $parsed_block['innerContent'][0] = $video;
            }

        }
        return $parsed_block;
    }

    public function mcv_playlist_script($enqueue = true, $attributes=[]){
        global $current_user, $post;
        $nonce                = wp_create_nonce('mcv-playlist-' . $current_user->ID);
        $ajaxUrl = admin_url("admin-ajax.php");
        $divId = sprintf('mcv_%s', md5(serialize($attributes)));
        $inlineScript = '
            jQuery(function(){
                function setMcvPlayerHeight(){
                    jQuery(".mcv-videos .mcv-player .wide-switch").show(1000);
                    var mcv_width = jQuery(".mcv-videos .mcv-player").width();
                    var mcv_height = mcv_width*0.5625;
                    jQuery(".mcv-videos .mcv-player, .mcv-videos .mcv-playlist, .mcv-videos iframe, #plyr_' . $divId . '").animate({height: mcv_height},500);
                }
                jQuery(".mcv-videos .mcv-player .wide-switch").on("click",function(){
                    jQuery(".mcv-videos .mcv-player .wide-switch").hide();
                    jQuery(".mcv-videos .mcv-playlist").toggle(500, function(){
                        setMcvPlayerHeight();
                    });
                    jQuery(".mcv-videos").toggleClass("mcv-full-width");
                });
                jQuery(".mcv-videos .mcv-playlist .mcv-playlist-ul li").on("click",function(){
                    var playingicon = jQuery(".mcv-videos .mcv-playlist .mcv-playlist-ul li.cur .playing-icon");
                    jQuery(".mcv-videos .mcv-playlist .mcv-playlist-ul li.cur").removeClass("cur");
                    jQuery(this).addClass("cur").prepend(playingicon);
                    setMcvPlayerHeight();
                    jQuery("#plyr_' . $divId . '").html("<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" style=\"margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;\"><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>");
                    jQuery.post("' . $ajaxUrl . '",{action:"mcv_playlist_ajax",pid:'.$post->ID.',nonce:"' . $nonce . '",mcvid:jQuery(this).data("id")},function(res){
                        if(window.mcv_playlist_aliplayer){window.mcv_playlist_aliplayer.dispose();window.mcv_playlist_aliplayer=null;}
                        if(window.mcv_playlist_tcplayer){window.mcv_playlist_tcplayer.dispose();window.mcv_playlist_tcplayer=null;}
                        if(window.mcv_playlist_dplayer){window.mcv_playlist_dplayer.destroy();window.mcv_playlist_dplayer=null;}
                        jQuery("#plyr_' . $divId . '").attr("style", "").removeClass();
                        jQuery("#plyr_' . $divId . '").html(res.aliplayer[0]);eval(res.aliplayer[1]);
                        jQuery("#' . $divId . ').css({"padding-top":"0px", height:"100%"});
                    }, "json");
                });
                jQuery(".mcv-videos .mcv-playlist .mcv-playlist-ul li:first").trigger("click");
            });
            ';
        global $post, $mcv_classes, $ActiveAddons;
        if (!$enqueue) {
            wp_enqueue_style('mine_cloudvod-aliyunvod-block-editor-css');
            $mcv_classes->Aliplayer && $mcv_classes->Aliplayer::style_script();
            $ActiveAddons['qcloud']??false && $ActiveAddons['qcloud']->Tcplayer::style_script();
            $mcv_classes->Dplayer && $mcv_classes->Dplayer::style_script();
            $mcv_classes->Audioplayer && $mcv_classes->Audioplayer::style_script();
            return $inlineScript;
        }
        if (is_singular() && ( has_block('mine-cloudvod/video-playlist', $post) || has_shortcode( $post->post_content, 'mcv_playlist' ) )) {
            wp_enqueue_style('mine_cloudvod-aliyunvod-block-editor-css');
            $mcv_classes->Aliplayer && $mcv_classes->Aliplayer::style_script();
            $ActiveAddons['qcloud']??false && $ActiveAddons['qcloud']->Tcplayer::style_script();
            $mcv_classes->Dplayer && $mcv_classes->Dplayer::style_script();
            $mcv_classes->Audioplayer && $mcv_classes->Audioplayer::style_script();

            global $isilmd5;
            if (!is_array($isilmd5) || (is_array($isilmd5) && !in_array(md5('mcv-playlist-'), $isilmd5)))
                wp_add_inline_script('mcv_dplayer', $inlineScript);
            $isilmd5[] = md5('mcv-playlist-');
        }
    }

    public function mcv_playlist_filter_aliplayer($video, $pconfig, $components, $events, $r, $attributes){
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'mcv_playlist') {
            $divId = sprintf('mcv_%s', md5(serialize($attributes)));
            $video = '<style>' . MINECLOUDVOD_SETTINGS['aliplayercss'] . '</style><div id="'. $divId .'"></div>';
            if(isset($_POST['an']) && sanitize_text_field(wp_unslash($_POST['an']))){ // phpcs:ignore WordPress.Security.NonceVerification.Missing -- 播放器 AJAX 渲染读连播参数，主入口已校验 nonce
                $events .= 'player.on("ended", function () {
                    jQuery(".mcv-playlist-ul li.cur", jQuery("#' . $divId .'").parent().parent().next()).next().trigger("click");
                });';
            }
            
            $video .= '{{mcvsplit}}var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . ';aliplayerconfig_' . $r . '.id="' . $divId . '";aliplayerconfig_' . $r . '.height="100%"; ' . $components . 'window.mcv_playlist_aliplayer=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . 'jQuery("#'.$divId.'").css("padding-top", "0");});{{mcvsplit}}aliyunvod';
        }
        return $video;
    }
    public function mcv_playlist_filter_tcplayer($video, $pconfig, $post_id, $parsed_block, $events){
        global $mcv_block_ajax_from;
        $instance = 0;
        if ($mcv_block_ajax_from == 'mcv_playlist') {
            $videoId = sprintf('mcv_%s', md5(serialize($parsed_block['attrs'])));
            if(isset($_POST['an']) && sanitize_text_field(wp_unslash($_POST['an']))){ // phpcs:ignore WordPress.Security.NonceVerification.Missing -- 播放器 AJAX 渲染读连播参数，主入口已校验 nonce
                $events .= 'window.mcv_playlist_tcplayer.on("ended", function () {
                    jQuery(".mcv-playlist-ul li.cur", jQuery("#' . $videoId .'").parent().parent().next()).next().trigger("click");
                });';
            }
            $video .= '{{mcvsplit}}';
            $video .= 'if(jQuery("#' . $videoId . '")){var tcplayerconfig_' . $post_id . $instance . '=' . json_encode($pconfig) . ';window.mcv_playlist_tcplayer = TCPlayer(\'' . $videoId . '\', tcplayerconfig_' . $post_id . $instance . ');'.$events.'jQuery("#'.$videoId.'").css({"width":"100%","height":"100%"});}';
            $video = str_replace('window.tcplayer_'.$videoId, 'window.mcv_playlist_tcplayer', $video);
            $video .= '{{mcvsplit}}qcloudvod';
        }
        return $video;
    }
    public function mcv_playlist_filter_embedvideo($video, $src, $width, $height)    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'mcv_playlist') {
            $video = preg_replace('/height="[^"]*?"/is', 'height="100%"', $video);
            $video .= '{{mcvsplit}}';
            $video .= 'function mcv_onresize(){}window.onresize=mcv_onresize;mcv_onresize();';
            $video .= '{{mcvsplit}}embedvideo';
        }
        return $video;
    }
}
