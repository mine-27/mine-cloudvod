<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
if( is_admin() ) return;
if( !isset($parsed_block) && $block) 
    $parsed_block = $block->parsed_block;
$divId = sprintf('mcv_%s', md5(serialize($attributes)));
$is_rendered = mcv_is_block_rendered( $divId );
if( $is_rendered ) return ;

$private = $attributes['privt']??false;

if($private && !is_user_logged_in()){
    return include(MINECLOUDVOD_PATH . "/templates/vod/private.php");
}
$post_id = get_the_ID() ?: ($parsed_block['post_id'] ?: 0);


$src        = $attributes['source']??'';
$width      = $attributes['width'] ?? '100%';
$height     = $attributes['height'] ?? 'auto';
$poster     = $attributes['thumbnail'] ?? $attributes['cover'] ?? '';
$isLive     = filter_var( $attributes['live'] ?? false, FILTER_VALIDATE_BOOLEAN );
$autoplay   = filter_var( ( $attributes['autoplay'] ?? MINECLOUDVOD_SETTINGS['dplayerconfig']['autoplay'] ?? false ), FILTER_VALIDATE_BOOLEAN );
$theme      = MINECLOUDVOD_SETTINGS['dplayerconfig']['theme'] ?? '#b7daff';
$lang       = MINECLOUDVOD_SETTINGS['dplayerconfig']['lang'] ?? 'zh-cn';
$loop       = filter_var( ( MINECLOUDVOD_SETTINGS['dplayerconfig']['loop'] ?? false ), FILTER_VALIDATE_BOOLEAN );
$preload    = MINECLOUDVOD_SETTINGS['dplayerconfig']['preload'] ?? 'auto';
$volume     = MINECLOUDVOD_SETTINGS['dplayerconfig']['volume'] ?? '0.7';
$screenshot = filter_var( ( MINECLOUDVOD_SETTINGS['dplayerconfig']['screenshot'] ?? false ), FILTER_VALIDATE_BOOLEAN );
$airplay    = filter_var( ( MINECLOUDVOD_SETTINGS['dplayerconfig']['airplay'] ?? false ), FILTER_VALIDATE_BOOLEAN );
$hotkey     = filter_var( ( MINECLOUDVOD_SETTINGS['dplayerconfig']['hotkey'] ?? false ), FILTER_VALIDATE_BOOLEAN );
$logo       = MINECLOUDVOD_SETTINGS['dplayer_components']['logo'] ?? false;
$contextmenu= MINECLOUDVOD_SETTINGS['dplayer_components']['contextmenu'] ?? false;
$danmu      = MINECLOUDVOD_SETTINGS['dplayer_components']['danmu']['status'] ?? false;
$danmuApi   = MINECLOUDVOD_SETTINGS['dplayer_components']['danmu']['api'] ?? false;
$watermark  = MINECLOUDVOD_SETTINGS['dplayer_components']['watermark'] ?? false;
$note       = MINECLOUDVOD_SETTINGS['dplayer_components']['note']['status'] ?? false;
$memory     = MINECLOUDVOD_SETTINGS['dplayer_components']['memory']['status'] ?? false;
$slide      = MINECLOUDVOD_SETTINGS['dplayer_components']['slide']['status'] ?? false;
$sticky     = MINECLOUDVOD_SETTINGS['dplayer_components']['sticky']['status'] ?? false;
$pausead    = MINECLOUDVOD_SETTINGS['dplayer_components']['pausead']['status'] ?? false;
$markers    = $attributes['markers']  ?? false;
$captions   = $attributes['captions'] ?? false;
$minecloudvod   = $attributes['minecloudvod'] ?? false;

$pconfig = [
    'container' => 'document.getElementById("'.$divId.'")',
    'autoplay' => $autoplay,
    'theme' => $theme,
    'loop' => $loop,
    'lang' => $lang,
    'screenshot' => $screenshot,
    'hotkey' => true,
    'preload' => $preload,
    'volume' => $volume,
    'mutex' => true,
    'video' => [
        'url' => $src,
        'pic' => $poster,
    ],
    'hlsConfig' => $attributes['hlsConfig']??false,
];
if($logo){
    $pconfig['logo'] = $logo;
}

if ( mcv_is_wechat_miniprogram() ) {

    echo '<video poster="'.esc_url($poster).'" name="" author="" src="'.esc_url($src).'" id="'.esc_attr($divId).'" controls loop></video>';
    return;
}

if($minecloudvod){
    if(isset($minecloudvod['tcvod'])){
        $pconfig['video'] = $minecloudvod['tcvod'];
        unset( $minecloudvod['tcvod'] );
    }
    $pconfig['minecloudvod'] = $minecloudvod;
}


if ($captions && is_array($captions) && count($captions)) {
    $subtitles = [];
    foreach ($captions as $caption) {
        $subtitles[] = [
            'name' => $caption['label'],
            'lang' => $caption['lang'],
            'url'  => $caption['src'],
        ];
    }
    $pconfig['subtitle']['url'] = $subtitles;
}
if ($markers && is_array($markers) && count($markers)) {
    $dMarkers = [];
    foreach ($markers as $marker) {
        $dMarkers[] = array(
            'time' => intval($marker['time']),
            'text' => $marker['title'],
        );
    }
    $pconfig['highlight'] = $dMarkers;
}

if($watermark && isset($watermark['status']) && $watermark['status']){
    $pconfig['watermark'] = [
        'style' => $watermark['watermarks']['style']
    ];
    if($watermark['watermarks']['type'] == 'image' && $watermark['watermarks']['image']){
        $pconfig['watermark']['image'] = $watermark['watermarks']['image'];
    }
    elseif($watermark['watermarks']['type'] == 'text' && $watermark['watermarks']['words']){
        $pconfig['watermark']['words'] = $watermark['watermarks']['words'];
    }
}

if($contextmenu){
    if( isset($contextmenu['videoinfo']) && $contextmenu['videoinfo'] == '1' )
        $contextmenu['links'][] = ['key'=>'video-info','click'=>'(player) => { player.infoPanel.triggle(); }'];
    else
        unset( $contextmenu['videoinfo'] );
    if( isset($contextmenu['links']) && is_array($contextmenu['links']) && count($contextmenu['links'])>0 )
        $pconfig['contextmenu'] = $contextmenu['links'];
    else
        $pconfig['contextmenu'] = $contextmenu;
}
if($danmu && $danmuApi){
    $pconfig['danmaku'] = [
        'id' => md5('mcv_' . $src),
        'api' => $danmuApi,
    ];
}
if ($slide) {
    $slideText = '';
    if ( MINECLOUDVOD_SETTINGS['dplayer_components']['slide']['status'] ?? false ) {
        if ( MINECLOUDVOD_SETTINGS['dplayer_components']['slide']['scrolltext'] ?? false ) {
            $mST = MINECLOUDVOD_SETTINGS['dplayer_components']['slide']['scrolltext'];
            if (is_array($mST) && count($mST) > 0) {
                $ra = wp_rand(0, count($mST) - 1);
                $slideText = $mST[$ra]['text'];
            }
        }
    }
    global $current_user;
    $slideText = str_replace(['{userid}', '{username}', '{userip}', '{useremail}', '{usernickname}'], [$current_user->ID, $current_user->user_login, sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')), $current_user->user_email, $current_user->display_name], $slideText);
    $pconfig['slide'] = [
        'text' => $slideText,
        'speed' => MINECLOUDVOD_SETTINGS['dplayer_components']['slide']['duration'] ?? 10,
        'style' => MINECLOUDVOD_SETTINGS['dplayer_components']['slide']['style'] ?? 'font-size:16px; color:#ddd;',
        'position' => MINECLOUDVOD_SETTINGS['dplayer_components']['slide']['position'] ?? 'random',
    ];
}
$components = '';
if($note){
    $components .= 'dplayerconfig_' . $divId . '.note = function(){
        layer.closeAll();
        layer.open({
            contentId: "#' . $divId . '",
            type: 2,
            title: "笔记加载中...",
            area: ["424px", "504px"],
            shade: 0,
            offset: "rb",
            anim: 5,
            content: [wp.url.addQueryArgs("'.get_page_link(get_page_by_path('mcv-aliplayer-note')).'", {did:"' . $divId . '",pid:"' . $post_id . '",nonce:"' . wp_create_nonce("mcv_note_".$post_id) . '",plyr:"dplayer"}), "no"],
            zIndex: layer.zIndex,
            success: function(layero) {
                var tt = layero.find(".layui-layer-title");
                tt[0].innerText = "笔记加载完成";
            }
        });
    };';
}
$events = '';
if( $memory ){
    $events .= '
    window.dplayer_' . $divId . '.on("timeupdate", function () {
        localStorage.setItem("_'.$divId.'", window.dplayer_' . $divId . '.video.currentTime);
    });
    window.dplayer_' . $divId . '.on("ended", function () {
        localStorage.setItem("_'.$divId.'", -1);
    });
    window.dplayer_' . $divId . '.on("loadeddata", function () {
        let t = localStorage.getItem("_'.$divId.'");
        if(t>0){
            window.dplayer_' . $divId . '.seek(t);
            window.dplayer_' . $divId . '.notice("' . __('The video have been positioned where you were last viewed.', 'mine-cloudvod') . '");
        }
    });
    ';
}//已为您定位到上次观看的位置
if( $sticky ){
    $events .= '
    let c = window.dplayer_' . $divId . '.container;
    let ct = jQuery(c).offset().top+parseInt(c.offsetHeight);
    onscroll = (e) => {
        try {
            let d = document.createElement("div");
            d.style.width = c.offsetWidth+"px";
            d.style.height = c.offsetHeight+"px";
            if( window.scrollY > ct){
                if(!c.classList.contains("mcv-fixed")){
                    c.before(d);
                    c.classList.add("mcv-fixed");
                }
            }
            else{
                if(c.classList.contains("mcv-fixed")){
                    c.previousSibling.remove();
                    c.classList.remove("mcv-fixed");
                }
            }
        } catch(error) {
            console.log(error);
        }
    };
    ';
}
if( $pausead ){
    $pauseads = MINECLOUDVOD_SETTINGS['dplayer_components']['pausead']['images'] ?? false;
    $hour = MINECLOUDVOD_SETTINGS['dplayer_components']['pausead']['hour'] ?? 0;
    if( $pauseads ) {
        $sAD = $pauseads;
        if (is_array($sAD) && count($sAD) > 0) {
            $ra = wp_rand(0, count($sAD) - 1);
            $csAD = $sAD[$ra];
            $events .= '
            window.dplayer_' . $divId . '.on("pause", ()=>{
                const hourLimit = ' . intval($hour) . ';
                if (hourLimit > 0) {
                    const lastShowTime = localStorage.getItem("mcv_pausead_lastshow");
                    if (lastShowTime) {
                        const hoursPassed = (Date.now() - parseInt(lastShowTime)) / (1000 * 60 * 60);
                        if (hoursPassed < hourLimit) {
                            return;
                        }
                    }
                }
                localStorage.setItem("mcv_pausead_lastshow", Date.now().toString());
                let addiv = document.createElement("div");
                addiv.classList.add("pause-ad");
                addiv.innerHTML = \'<a><span class="ad-text">广告</span></a><a class="ad-content" target="_blank" href="'. $csAD['url'] .'"><img src="'. $csAD['image'] .'"></a>\';
                let close = document.createElement("a");
                close.classList.add("btn-close");
                close.innerHTML = \'<i class="split-left"></i><i class="split-right"></i>\';
                close.addEventListener("click",()=>{
                    addiv.remove();
                });
                addiv.prepend(close);

                window.dplayer_' . $divId . '.container.appendChild(addiv);
            });
            window.dplayer_' . $divId . '.on("playing", ()=>{
                document.querySelector(".dplayer .pause-ad")?.remove();
            });
            ';
        }
    }
}

$video = '<div id="' . $divId . '" style="'.($height=='auto'?'':'height:'.$height).'"></div>';

$hlsConfig = '';
if( $pconfig['hlsConfig'] ?? false ){
    $hlsConfig = $pconfig['hlsConfig'];
    unset($pconfig['hlsConfig']);
}
$pconfig_json = json_encode($pconfig);
$pconfig_json = preg_replace('/("key":"video-info","click":)("([^"]*?)")/is', '$1$3', $pconfig_json);
$pconfig_json = preg_replace('/("container"\:)"document.getElementById\(\\\"([^\\\]*?)\\\"\)"/is', '$1document.getElementById("$2")', $pconfig_json);
$pconfig_json = substr_replace( $pconfig_json, 'pluginOptions:{hls:{'. $hlsConfig .'}},', 1, 0 );

//disablePictureInPicture
$events .= 'jQuery("#' . $divId . ' video").attr("disablePictureInPicture", "true");';
/**
 * 过滤dplayer播放器事件
 * 
 * @since 1.7.6
 */
$events = apply_filters('mcv_filter_dplayer_events', $events, $pconfig, $attributes);
$inlineScript = '
    jQuery(function(){
        var dplayerconfig_' . $divId . '=' . $pconfig_json . ';
        '.$components.'
        window.dplayer_' . $divId . ' = new window.McvDPlayer(dplayerconfig_' . $divId . ');
        '.$events.'
    });
';
$inlineScript = mcv_trim($inlineScript);

global $mcv_classes;
$mcv_classes->Dplayer::style_script();
wp_add_inline_script('mcv_dplayer', $inlineScript);

$video_safe = apply_filters('mcv_filter_dplayer', $video, $pconfig, $attributes, $events);

echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 播放器 HTML 已逐段 esc_* 转义，经 apply_filters 过滤
if (isset($enqueue) && !$enqueue) {
    echo '<script>' . esc_js($inlineScript) . '</script>';
}