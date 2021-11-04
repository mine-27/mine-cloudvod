<?php

namespace MineCloudvod\Ability;

use MineCloudvod\Models\McvVideo;

class PlayList
{
    public function __construct()
    {
        add_action('wp_ajax_mcv_playlist_ajax', array($this, 'mcv_playlist_ajax'));
        add_action('wp_ajax_nopriv_mcv_playlist_ajax', array($this, 'mcv_playlist_ajax'));

        add_filter('render_block_data', array($this, 'render_playlist'), 10, 2);
        add_filter('mcv_filter_aliplayer', array($this, 'mcv_playlist_filter_aliplayer'), 10, 6);
        add_filter('mcv_filter_tcplayer', array($this, 'mcv_playlist_filter_tcplayer'), 10, 4);
        add_filter('mcv_filter_embedvideo', array($this, 'mcv_playlist_filter_embedvideo'), 10, 4);
    }

    public function render_playlist($parsed_block, $enqueue = true)
    {
        global $pagenow;
        if ($enqueue && $pagenow == 'post.php') return false;
        $video = '';
        if ($parsed_block['blockName'] == "mine-cloudvod/video-playlist" && isset($parsed_block['attrs']['mcvTag'])) {
            $plName = $parsed_block['attrs']['plName'] ?? __('Video Playlist', 'mine-cloudvod');
            $mcvTag = $parsed_block['attrs']['mcvTag'];
            $show = $parsed_block['attrs']['show'] ?? true;
            $mcvVideo = new McvVideo();
            $videoList = $mcvVideo->all([
                'tax_query' => [[
                    'taxonomy'  => 'mcv_video_tag',
                    'field'     => 'term_id',
                    'terms'     => [$mcvTag],
                    'include_children' => false,
                    'operator'   => 'IN',
                ]],
                'orderby' => ['meta_value_num' => 'ASC'],
                'meta_key' => 'sort_no',
            ]);
            $videoListStr = '';
            $vli = 1;
            foreach ($videoList as $video) {
                $videoListStr .= '<li data-id="' . $video->ID . '" ' . ($vli == 1 ? 'class="cur"' : '') . '>' . ($vli == 1 ? '<div class="playing-icon"><svg width="16" height="16" viewBox="0 0 135 140" xmlns="http://www.w3.org/2000/svg"> <rect y="10" width="15" height="120" rx="6"> <animate attributeName="height" begin="0.5s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0.5s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> <rect x="30" y="10" width="15" height="120" rx="6"> <animate attributeName="height" begin="0.25s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0.25s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> <rect x="60" width="15" height="140" rx="6"> <animate attributeName="height" begin="0s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> <rect x="90" y="10" width="15" height="120" rx="6"> <animate attributeName="height" begin="0.25s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0.25s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> <rect x="120" y="10" width="15" height="120" rx="6"> <animate attributeName="height" begin="0.5s" dur="1s" values="120;110;100;90;80;70;60;50;40;140;120" calcMode="linear" repeatCount="indefinite" /> <animate attributeName="y" begin="0.5s" dur="1s" values="10;15;20;25;30;35;40;45;50;0;10" calcMode="linear" repeatCount="indefinite" /> </rect> </svg></div>' : '') . '<span>' . $vli . '. ' . $video->post_title . '</span></li>';
                $vli++;
            }
            $inlineStyle = '
            <style>
                .mcv-videos{
                    position: relative;
                    display: flex;
                    justify-content: left;
                    width:100%;
                    margin: 0;
                }
                .mcv-videos .mcv-player {
                    flex: 1;
                    width: 0;
                    position: relative;
                    background-color: #000;
                }
                .mcv-videos .mcv-player .wide-switch {
                    position: absolute;
                    top: calc(50% - 29.5px);;
                    right: -9px;
                    width: 9px;
                    height: 59px;
                    margin-top: -4px;
                    cursor: pointer;
                    justify-content: center;
                    align-items: center;
                    color: #ccc;
                    z-index: 999;
                    display: none;
                }
                .mcv-videos .mcv-player .wide-switch .btn_switch_bg {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    color: #36363e;
                }
                .mcv-videos .mcv-player .wide-switch .btn_switch_bg svg {
                    fill: #000;
                }
                .mcv-videos .mcv-player .wide-switch .icon_left_sm {
                    display: none;
                    left: -4px;
                }
                .mcv-videos .mcv-player .wide-switch .icon_right_sm {
                    display: block;
                    left: -4px;
                }
                
                .mcv-videos .mcv-player .wide-switch .icon_sm {
                    position: absolute;
                    line-height: 16px !important;
                    top: 22px;
                    left: -4px;
                    width: 16px;
                    height: 16px;
                }
                
                .mcv-videos .mcv-playlist {
                    overflow: hidden;
                    background: #333;
                    width: auto;
                    max-width:280px;
                    padding-left: 16px; 
                }
                .mcv-videos .mcv-playlist .mcv-title {
                    color: #fff;
                    display: flex;
                    align-items: center;
                    padding: 20px 70px 10px 5px;
                }
                .mcv-videos .mcv-playlist .mcv-title span {
                    font-size: 12px;
                    margin-left: 10px;
                    color: #AAAEB3;
                    right: 20px;
                    position: absolute;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul {
                    height:100%;
                    padding-right:10px;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul ul{
                    color: #fff;
                    margin: 0;
                    padding: 10px 20px 0 0;
                    overflow-y: scroll;
                    height: calc(100% - 62px);
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul ul::-webkit-scrollbar {
                    width: 4px;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul ul::-webkit-scrollbar-track {
                    border-radius: 4.5px;
                    background: #333;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul ul::-webkit-scrollbar-thumb {
                    border-radius: 4.5px;
                    background: #666;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul{
                    overflow: hidden;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul li {
                    display: flex;
                    font-size: 14px;
                    align-items: center;
                    padding: 5px;
                    margin: 0 0 5px 0;
                    cursor: pointer;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul li span{
                    padding-left: 24px;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul li.cur span{
                    padding-left: 8px;
                }
                .mcv-videos .mcv-playlist .mcv-playlist-ul li:hover, .mcv-videos .mcv-playlist .mcv-playlist-ul li.cur {
                    background: #555;
                    border-radius: 4px;
                }
                .mcv-full-width{
                    width: calc(100% - 9px);
                }
                .mcv-full-width.mcv-videos .mcv-player .wide-switch .icon_right_sm{
                    display: none;
                }
                .mcv-full-width.mcv-videos .mcv-player .wide-switch .icon_left_sm{
                    display: block;
                }
                .playing-icon{
                    display: flex;
                    fill:#fff;
                }
                @media screen and (max-width:768px){
                    .mcv-videos{
                        flex-flow: column;
                        height: auto;
                    }
                    .mcv-videos .mcv-player{
                        width:auto !important;
                    }
                    .mcv-videos .mcv-player .wide-switch{
                        display:none;
                    }
                    .mcv-videos .mcv-playlist {
                        display: block !important;
                        width: 100%!important;
                        margin: 0!important;
                        border-bottom: 1px solid #f3f3f3;
                        -webkit-overflow-scrolling: touch;
                        height: auto!important;
                        padding-left: 0;
                    }
                    .mcv-videos .mcv-playlist .mcv-title{
                        display: none;
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul{
                        padding-right: 0;
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul ul{
                        width:100%;
                        margin:0;
                        display: flex;
                        padding:6px 4px 4px 0;
                        background: #fff;
                        color: initial;
                        padding-top:6px;
                        -webkit-overflow-scrolling: touch;
                        align-items: flex-end;
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul ul::-webkit-scrollbar {
                        width: 0 !important;
                        display: none; 
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul ul li{
                        width:140px;
                        min-width: 140px;
                        max-width:140px;
                        border: 1px solid #eee;
                        margin: 4px;
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul ul li:first-child{
                        margin-left: 0 !important;
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul li.cur span{
                        color: #ff6a00;
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul li:hover, .mcv-videos .mcv-playlist .mcv-playlist-ul li.cur {
                        background: none;
                        border: 1px solid #ff6a00;
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul li span{
                        display: -webkit-box;
                        -webkit-box-orient: vertical;
                        -webkit-line-clamp: 2;
                    }
                    .mcv-videos .mcv-playlist .mcv-playlist-ul li.cur span{
                        padding-left: 8px;
                    }
                    .playing-icon{
                        fill: #ff6a00;
                    }
                }
            </style>';
            $divId = sprintf('mcv-%s', md5(serialize($parsed_block)));
            $video = $inlineStyle . '
            <div id="' . $divId . '" class="mcv-videos' . ($show ? '' : ' mcv-full-width') . '">
                <div class="mcv-player">
                    <div id="mcv_player_con"></div>
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
            </div>'; //apply_filters('mcv_filter_embedvideo', $video, $src, $width, $height);

        }
        //for elementor
        $inlineScript = $this->mcv_playlist_script($enqueue);
        if (!$enqueue) {
            $video .= '<script>' . $inlineScript . '</script>';
            return $video;
        }

        if ($video) {
            $video = mcv_trim($video);
            $parsed_block['innerContent'][0] = $video;
        }
        return $parsed_block;
    }

    public function mcv_playlist_ajax()
    {
        global $current_user;
        $uid = $current_user->ID;

        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;

        if ($nonce && !wp_verify_nonce($nonce, 'mcv-playlist-' . $uid)) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));
            exit;
        }

        $mcvid = !empty($_POST['mcvid']) ? $_POST['mcvid'] : null;
        $aliplayer = do_shortcode('[mine_cloudvod id=' . $mcvid . ' from="mcv_playlist"]');
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
    public function mcv_playlist_script($enqueue = true)
    {
        global $current_user;
        $nonce                = wp_create_nonce('mcv-playlist-' . $current_user->ID);
        $ajaxUrl = admin_url("admin-ajax.php");
        $inlineScript = '
            jQuery(function(){
                function setMcvPlayerHeight(){
                    jQuery(".mcv-videos .mcv-player .wide-switch").show(1000);
                    var mcv_width = jQuery(".mcv-videos .mcv-player").width();
                    var mcv_height = mcv_width*0.5625;
                    jQuery(".mcv-videos .mcv-player, .mcv-videos .mcv-playlist, .mcv-videos iframe").animate({height: mcv_height},500);
                }
                jQuery(".mcv-videos .mcv-player .wide-switch").click(function(){
                    jQuery(".mcv-videos .mcv-player .wide-switch").hide();
                    jQuery(".mcv-videos .mcv-playlist").toggle(500, function(){
                        setMcvPlayerHeight();
                    });
                    jQuery(".mcv-videos").toggleClass("mcv-full-width");
                });
                jQuery(".mcv-videos .mcv-playlist .mcv-playlist-ul li").click(function(){
                    var playingicon = jQuery(".mcv-videos .mcv-playlist .mcv-playlist-ul li.cur .playing-icon");
                    jQuery(".mcv-videos .mcv-playlist .mcv-playlist-ul li.cur").removeClass("cur");
                    jQuery(this).addClass("cur").prepend(playingicon);
                    setMcvPlayerHeight();
                    jQuery("#mcv_player_con").html("<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" style=\"margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;\"><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>");
                    jQuery.post("' . $ajaxUrl . '",{action:"mcv_playlist_ajax",nonce:"' . $nonce . '",mcvid:jQuery(this).data("id")},function(res){
                        if(window.mcv_playlist_aliplayer){window.mcv_playlist_aliplayer.dispose();window.mcv_playlist_aliplayer=null;}
                        if(window.mcv_playlist_tcplayer){window.mcv_playlist_tcplayer.dispose();window.mcv_playlist_tcplayer=null;}
                        jQuery("#mcv_player_con").attr("style", "").removeClass();
                        jQuery("#mcv_player_con").css("padding-top", "0px");
                        jQuery("#mcv_player_con").empty();
                        if(res.status == "1"){jQuery("#mcv_player_con").before(res.aliplayer[0]);eval(res.aliplayer[1]);}
                        if(res.status == "2"){jQuery("#mcv_player_con").html(res.aliplayer[0]);eval(res.aliplayer[1]);}
                        if(res.status == "3"){jQuery("#mcv_player_con").html(res.aliplayer[0]);eval(res.aliplayer[1]);}
                    }, "json");
                });
                jQuery(".mcv-videos .mcv-playlist .mcv-playlist-ul li:first").click();
            });
            ';
        if (!$enqueue) {
            \MineCloudvod\Aliyun\Aliplayer::style_script();
            \MineCloudvod\Qcloud\Tcplayer::style_script();
            return $inlineScript;
        }
        global $post;
        if (is_singular() && (has_block('mine-cloudvod/video-playlist', $post) || has_shortcode($post->post_content, 'mine_cloudvod'))) {
            \MineCloudvod\Aliyun\Aliplayer::style_script();
            \MineCloudvod\Qcloud\Tcplayer::style_script();

            global $isilmd5;
            if (!is_array($isilmd5) || (is_array($isilmd5) && !in_array(md5('mcv-playlist-'), $isilmd5)))
                wp_add_inline_script('mine_tcplayer', $inlineScript);
            $isilmd5[] = md5('mcv-playlist-');
        }
    }

    public function mcv_playlist_filter_aliplayer($video, $pconfig, $components, $events, $r, $parsed_block)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'mcv_playlist') {
            $video = '<style>' . MINECLOUDVOD_SETTINGS['aliplayercss'] . '</style>';
            $divId = sprintf('mcv-%s', md5(serialize($parsed_block)));
            $events = str_replace('#' . $divId, '#mcv_player_con', $events);
            $video .= '{{mcvsplit}}var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . ';aliplayerconfig_' . $r . '.id="mcv_player_con";aliplayerconfig_' . $r . '.height="100%"; ' . $components . 'window.mcv_playlist_aliplayer=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});{{mcvsplit}}aliyunvod';
        }
        return $video;
    }
    public function mcv_playlist_filter_tcplayer($video, $pconfig, $post_id, $parsed_block)
    {
        global $mcv_block_ajax_from;
        $instance = 0;
        if ($mcv_block_ajax_from == 'mcv_playlist') {
            $videoId = sprintf('mcv-%s', md5(serialize($parsed_block)));
            $video .= '{{mcvsplit}}';
            $video .= 'if(jQuery("#' . $videoId . '")){var tcplayerconfig_' . $post_id . $instance . '=' . json_encode($pconfig) . ';window.mcv_playlist_tcplayer = TCPlayer(\'' . $videoId . '\', tcplayerconfig_' . $post_id . $instance . ');}';
            $video .= '{{mcvsplit}}qcloudvod';
        }
        return $video;
    }
    public function mcv_playlist_filter_embedvideo($video, $src, $width, $height)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'mcv_playlist') {
            $video = preg_replace('/height="[^"]*?"/is', 'height="100%"', $video);
            $video .= '{{mcvsplit}}';
            $video .= 'function mcv_onresize(){document.querySelector("#mcv_embed_iframe").style.height = (document.querySelector("#mcv_embed_iframe").clientWidth*.5625)+"px";}window.onresize=mcv_onresize;mcv_onresize();';
            $video .= '{{mcvsplit}}embedvideo';
        }
        return $video;
    }
}
