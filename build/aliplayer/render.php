<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

// 后台（区块编辑器）使用客户端组件渲染预览；服务端输出会产生在顶层 document 中查找容器的内联脚本，故跳过。
if ( is_admin() ) return;

    $divId = sprintf('mcv_%s', md5(serialize($attributes)));
    $is_rendered = mcv_is_block_rendered( $divId );
    if( $is_rendered ) return ;

    $private = $attributes['privt']??false;
    
    if($private && !is_user_logged_in()){
        $private_escaped = include(MINECLOUDVOD_PATH . "/templates/vod/private.php");
        echo $private_escaped; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 私有模板输出已由模板内部转义
        return;
    }
    $post_id = get_the_ID() ?: ($attributes['post_id'] ?? 0);

    $videoId    = $attributes['videoId'] ?? '';
    $source     = $attributes['source'] ?? '';
    $oss        = $attributes['oss'] ?? false;
    $width      = $attributes['width'] ?? '100%';
    $height     = $attributes['height'] ?? 'auto';
    $poster     = $attributes['thumbnail'] ?? $attributes['cover'] ?? '';
    $endpoint   = $attributes['endpoint'] ?? MINECLOUDVOD_SETTINGS['alivod']['endpoint'] ?? '';
    $markers    = $attributes['markers']  ?? false;
    $prompts    = $attributes['pausePrompts']  ?? false;
    $captions   = $attributes['captions'] ?? false;
    $slide      = false;
    $slidetext  = false;
    if(isset($attributes['slide']) && $attributes['slide']){
        $slide = true;
        $slidetext  = $attributes['slidetext'] ?? '';
    }
    if( !$slide ){
        if( isset( MINECLOUDVOD_SETTINGS['aliplayer_slide']['status'] ) && MINECLOUDVOD_SETTINGS['aliplayer_slide']['status'] ){
            $slide = true;
        }
    }
    $isLive     = filter_var( $attributes['live'] ?? false, FILTER_VALIDATE_BOOLEAN );
    $autoplay   = filter_var( ( $attributes['autoplay'] ?? MINECLOUDVOD_SETTINGS['aliplayerconfig']['autoplay'] ?? false ), FILTER_VALIDATE_BOOLEAN );
    $countdown  = $attributes['countdown'] ?? false;
    $countdowntips = $attributes['countdowntips'] ?? __('The video will play in ', 'mine-cloudvod');
    $textLiveEnd = $attributes['textLiveEnd'] ?? __('The Live is ended. ', 'mine-cloudvod');
    $referrer = $attributes['referrer'] ?? false;

    $aliMarkers = array();
    if ($markers && is_array($markers) && count($markers)) {
        foreach ($markers as $marker) {
            $aliMarkers[] = array(
                'offset' => intval($marker['time']),
                'isCustomized' => true,
                'coverUrl' => '#',
                'title' => '',
                'describe' => $marker['title'],
            );
        }
    }

    $pctrl = [
        "name" => "controlBar", "align" => "blabs", "x" => 0, "y" => 0,
        'children' => []
    ];
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['progress'] ?? false) {
        $pctrl['children'][] = ["name" => "progress", "align" => "blabs", "x" => 0, "y" => 44];
    }
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['playButton'] ?? false) {
        $pctrl['children'][] = ["name" => "playButton", "align" => "tl", "x" => 15, "y" => 12];
    }
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['timeDisplay'] ?? false) {
        $pctrl['children'][] = ["name" => "timeDisplay", "align" => "tl", "x" => 10, "y" => 7];
    }
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['fullScreenButton'] ?? false) {
        $pctrl['children'][] = ["name" => "fullScreenButton", "align" => "tr", "x" => 10, "y" => 12];
    }
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['subtitle'] ?? false) {
        $pctrl['children'][] = ["name" => "subtitle", "align" => "tr", "x" => 15, "y" => 12];
    }
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['setting'] ?? false) {
        $pctrl['children'][] = ["name" => "setting", "align" => "tr", "x" => 15, "y" => 12];
    }
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['volume'] ?? false) {
        $pctrl['children'][] = ["name" => "volume", "align" => "tr", "x" => 5, "y" => 10];
    }
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['snapshot'] ?? false) {
        $pctrl['children'][] = ["name" => "snapshot", "align" => "tr", "x" => 10, "y" => 12];
    }
    $pskin = array(
        ["name" => "H5Loading", "align" => "cc"],
        ["name" => "errorDisplay", "align" => "tlabs", "x" => 0, "y" => 0],
        ["name" => "infoDisplay"],
        ["name" => "tooltip", "align" => "blabs", "x" => 0, "y" => 56],
        ["name" => "thumbnail"],
        $pctrl,
    );
    if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['bigPlayButton'] ?? true) {
        if(isset(MINECLOUDVOD_SETTINGS['aliplayerconfig']['bigPlayButtonPosition']) && MINECLOUDVOD_SETTINGS['aliplayerconfig']['bigPlayButtonPosition'] == 'ct'){
            $pskin[] = ["name" => "bigPlayButton", "align" => "cc"];
        }
        else{
            $pskin[] = ["name" => "bigPlayButton", "align" => "blabs", "x" => 30, "y" => 80];
        }
    }
    $pconfig = array(
        "id"                => $divId,
        "qualitySort"       => MINECLOUDVOD_SETTINGS['aliplayer_Quality']['type']??'asc',
        // "definition"        => "FD,OD",
        // "defaultDefinition" => "FD",
        "source"            => "",
        "mediaType"         => 'video',
        "width"             => $width,
        "height"            => $height,
        "isLive"            => $isLive,
        "useH5Prism"        => true,
        "cover"             => $poster,
        "autoplay"          => $autoplay,
        "controlBarVisibility"=>'hover',
        "skinLayout" => $pskin,
        "components" => array(),
        "isVBR" => true,
        "autoplayPolicy" => [
            "fallbackToMute" => true,
        ],
        // "disableSeek" => true,
    );
    // if( mcv_is_wechat() ){
    if (stripos(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']??'')), 'miniprogram') !== false) {
        $pconfig['x5_type'] = 'h5';
        $pconfig['playsinline'] = false;
        $pconfig['x5_fullscreen'] = true;
    }
    if ($captions && is_array($captions) && count($captions)) {
        $tracks = [];
        foreach ($captions as $caption) {
            $tracks[] = [
                'kind' => 'subtitles',
                'label' => $caption['label'],
                'src' => $caption['src'],
                'srclang' => $caption['lang']
            ];
        }
        $pconfig['textTracks'] = $tracks;
    }
    if( MINECLOUDVOD_SETTINGS['aliplayerconfig'] ?? false ){
        $pconfig["language"] = MINECLOUDVOD_SETTINGS['aliplayerconfig']['language'] ?? 'zh-cn';
        $pconfig["rePlay"] = MINECLOUDVOD_SETTINGS['aliplayerconfig']['rePlay'] ? true : false;
        $pconfig["preload"] = MINECLOUDVOD_SETTINGS['aliplayerconfig']['preload'] ? true : false;
        $pconfig["controlBarVisibility"] = MINECLOUDVOD_SETTINGS['aliplayerconfig']['controlBarVisibility'] ?? 'hover';
    }
    if(isset(MINECLOUDVOD_SETTINGS['aliplayer_hotKey']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_hotKey']['status']){
        $pconfig['keyShortCuts'] = true;
        $pconfig['keyFastForwardStep'] = is_numeric(MINECLOUDVOD_SETTINGS['aliplayer_hotKey']['time'])?intval(MINECLOUDVOD_SETTINGS['aliplayer_hotKey']['time']):10;
    }

    $r = 270; //wp_rand(100, 999);
    $components = '';
    $events = '';
    if (!$isLive && $aliMarkers) {
        $pconfig['progressMarkers'] = $aliMarkers;
        $components .= "aliplayerconfig_$r.components.push({name:'ProgressComponent',type: AliPlayerComponent.ProgressComponent});";
    }
    if (isset(MINECLOUDVOD_SETTINGS['aliplayer_logo']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_logo']['status']) {
        $logoStyle = MINECLOUDVOD_SETTINGS['aliplayer_logo']['style'];
        if(wp_is_mobile() && MINECLOUDVOD_SETTINGS['aliplayer_logo']['style_mb']){
            $logoStyle = MINECLOUDVOD_SETTINGS['aliplayer_logo']['style_mb'];
        }
        $components .= "aliplayerconfig_$r.components.push({name: 'LogoComponent',type: LogoComponent, args: ['".MINECLOUDVOD_SETTINGS['aliplayer_logo']['src']."','".$logoStyle."']});";
    }
    global $current_user;
    if ($slide) {
        $slideText = '';
        if ($slidetext) $slideText = $slidetext;
        elseif (isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_slide']['status']) {
            if (isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['scrolltext'])) {
                $mST = MINECLOUDVOD_SETTINGS['aliplayer_slide']['scrolltext'];
                if (is_array($mST) && count($mST) > 0) {
                    $ra = wp_rand(0, count($mST) - 1);
                    $slideText = $mST[$ra]['text'];
                }
            }
            //兼容1.2.14之前的版本
            elseif (isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['text'])) {
                $slideText = MINECLOUDVOD_SETTINGS['aliplayer_slide']['text'];
            }
        }
        $slideText = str_replace(['{userid}', '{username}', '{userip}', '{useremail}', '{usernickname}'], [$current_user->ID, $current_user->user_login, sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')), $current_user->user_email, $current_user->display_name], $slideText);
        $components .= "aliplayerconfig_$r.components.push({name:'BulletScreenComponent',type: AliPlayerComponent.BulletScreenComponent,args: ['".esc_js($slideText)."', {}, '" . esc_js(MINECLOUDVOD_SETTINGS['aliplayer_slide']['position']) . "']});";
    }
    if (!$isLive && isset(MINECLOUDVOD_SETTINGS['aliplayer_MemoryPlay']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_MemoryPlay']['status']) {
        $components .= "aliplayerconfig_$r.components.push({name: 'MemoryPlayComponent',type: AliPlayerComponent.MemoryPlayComponent,args: [" . MINECLOUDVOD_SETTINGS['aliplayer_MemoryPlay']['type'] . "]});";
    }
    if (!$isLive && isset(MINECLOUDVOD_SETTINGS['aliplayer_Rate']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_Rate']['status']) {
        $components .= "aliplayerconfig_$r.components.push({name: 'RateComponent',type: AliPlayerComponent.RateComponent});";
    }
    if (!$isLive && isset(MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['status']) {
        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['type']) && MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['type'] == 'video') {
            if (isset(MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['videos'])) {
                $sAD = MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['videos'];
                if (is_array($sAD) && count($sAD) > 0) {
                    $ra = wp_rand(0, count($sAD) - 1);
                    $csAD = $sAD[$ra];
                    $components .= "aliplayerconfig_$r.components.push({name: 'VideoADComponent',type: AliPlayerComponent.VideoADComponent,args: ['" . $csAD['video'] . "', '" . $csAD['url'] . "',,'" . __('Skip Ad', 'mine-cloudvod') . "']});";
                }
            } elseif (isset(MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['video'])) {
                $components .= "aliplayerconfig_$r.components.push({name: 'VideoADComponent',type: AliPlayerComponent.VideoADComponent,args: ['" . MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['video'] . "', '" . MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['url'] . "',,'" . __('Skip Ad', 'mine-cloudvod') . "']});";
            }
        } else {
            if (isset(MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['images'])) {
                $sAD = MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['images'];
                if (is_array($sAD) && count($sAD) > 0) {
                    $ra = wp_rand(0, count($sAD) - 1);
                    $csAD = $sAD[$ra];
                    $components .= "aliplayerconfig_$r.components.push({name: 'StartADComponent',type: AliPlayerComponent.StartADComponent,args: ['" . $csAD['image'] . "', '" . $csAD['url'] . "', " . $csAD['time'] . "]});";
                }
            } elseif (isset(MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['image'])) {
                $components .= "aliplayerconfig_$r.components.push({name: 'StartADComponent',type: AliPlayerComponent.StartADComponent,args: ['" . MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['image'] . "', '" . MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['url'] . "', " . MINECLOUDVOD_SETTINGS['aliplayer_StartAD']['time'] . "]});";
            }
        }
    }
    if (!$isLive && isset(MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['status']) {
        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['images'])) {
            $sAD = MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['images'];
            $hour = MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['hour'] ?? 0;
            if (is_array($sAD) && count($sAD) > 0) {
                $ra = wp_rand(0, count($sAD) - 1);
                $csAD = $sAD[$ra];
                // $components .= "aliplayerconfig_$r.components.push({name: 'PauseADComponent',type: AliPlayerComponent.PauseADComponent,args: ['" . $csAD['image'] . "', '" . $csAD['url'] . "']});";
                
                $events .= '
                player.on("pause", ()=>{
                    const hourLimit = ' . intval($hour) . ';
                    const storageKey = "mcv_pausead_lastshow_' . $divId . '";
                    if (hourLimit > 0) {
                        const lastShowTime = localStorage.getItem(storageKey);
                        if (lastShowTime) {
                            const parsedTime = parseInt(lastShowTime);
                            if (isNaN(parsedTime)) {
                                localStorage.removeItem(storageKey);
                            } else {
                                const hoursPassed = (Date.now() - parsedTime) / (1000 * 60 * 60);
                                if (hoursPassed < hourLimit) {
                                    return;
                                }
                            }
                        }
                    }
                    localStorage.setItem(storageKey, Date.now().toString());
                    let addiv = document.createElement("div");
                    addiv.classList.add("pause-ad");
                    addiv.style.display = "block";
                    addiv.innerHTML = \'<a><span class="ad-text">广告</span></a><a class="ad-content" target="_blank" href="'. $csAD['url'] .'"><img src="'. $csAD['image'] .'"></a>\';
                    let close = document.createElement("a");
                    close.classList.add("btn-close");
                    close.innerHTML = \'<i class="split-left"></i><i class="split-right"></i>\';
                    close.addEventListener("click",()=>{
                        addiv.remove();
                    });
                    addiv.prepend(close);

                    player.el().appendChild(addiv);
                });
                player.on("play", ()=>{
                    document.querySelector("#'.$divId.' .pause-ad")?.remove();
                });
                ';
            }
        } elseif (isset(MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['image'])) {
            $components .= "aliplayerconfig_$r.components.push({name: 'PauseADComponent',type: AliPlayerComponent.PauseADComponent,args: ['" . MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['image'] . "', '" . MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['url'] . "']});";
        }
    }
    if (isset(MINECLOUDVOD_SETTINGS['aliplayer_preview']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_preview']['status'] && !is_user_logged_in()) {
        $components .= "aliplayerconfig_$r.components.push({name: 'PreviewVodComponent',type: AliPlayerComponent.PreviewVodComponent,args: ['" . MINECLOUDVOD_SETTINGS['aliplayer_preview']['duration'] . "', '" . mcv_trim(html_entity_decode(MINECLOUDVOD_SETTINGS['aliplayer_preview']['endhtml'])) . "', '" . html_entity_decode(MINECLOUDVOD_SETTINGS['aliplayer_preview']['barhtml']) . "']});";
    }
    if (isset(MINECLOUDVOD_SETTINGS['aliplayer_RotateMirror']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_RotateMirror']['status']) {
        $components .= "aliplayerconfig_$r.components.push({name: 'RotateMirrorComponent',type: AliPlayerComponent.RotateMirrorComponent});";
    }
    if($prompts && is_array($prompts) && count($prompts)){
        $components .= "aliplayerconfig_$r.components.push({name: 'PauseNoteComponent',type: PauseNoteComponent,args: [".json_encode($prompts).",'']});";
    }
    if (isset(MINECLOUDVOD_SETTINGS['aliplayer_Note']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_Note']['status']) {
        $components .= "aliplayerconfig_$r.components.push({name: 'NoteComponent',type: NoteComponent,args:['".admin_url("admin-ajax.php")."', '".$post_id."', '".$divId."', '".wp_create_nonce("mcv_note_".$post_id)."']});";
    }
    if (isset(MINECLOUDVOD_SETTINGS['aliplayer_watermark']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_watermark']['status']) {
        $watermarks = MINECLOUDVOD_SETTINGS['aliplayer_watermark']['watermarks'];
        foreach($watermarks as $watermark){
            $watermarkContent = $watermark['words'];
            $watermarkContent = str_replace(['{userid}', '{username}', '{userip}', '{useremail}', '{usernickname}'], [$current_user->ID??'', $current_user->user_login??'', sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')), $current_user->user_email??'', $current_user->display_name??''], $watermarkContent);
            if($watermark['type'] == 'image') $watermarkContent = $watermark['image'];
            $components .= "aliplayerconfig_$r.components.push({name: 'WatermarkComponent',type: WatermarkComponent,args:['".$watermark['type']."', '".$watermarkContent."', '".$watermark['style']."']});";
        }
    }
    global $ActiveAddons;
    $playinfo = '';
    $mini_src = '';
    if ($source) {
        $pconfig['source'] = $source;
        $mini_src = $source;
    } elseif ($videoId) {
        $vod = $ActiveAddons['aliyun']->Alivod;
        $playinfo = $vod->get_playinfo($videoId, $endpoint);
        $mini_src = isset($playinfo['mp4']) ? $playinfo['mp4'] : '';
        if (!isset($playinfo['hls']) || !$playinfo['hls'] || ( isset($playinfo['encrypt']) && $playinfo['encrypt'] && 'AliyunVoDEncryption' == $playinfo['encryptType'] ) ) {
            $pconfig['vid'] = $videoId;
            $pconfig['playauth'] = $playinfo['playauth'];
            if( isset($playinfo['encrypt']) && $playinfo['encrypt'] ){
                $pconfig['encryptType'] = 1;
            }
        } 
        else {
            $pihls = $playinfo['hls'];

            $pconfig['source'] = json_encode($pihls);
            $components .= "aliplayerconfig_$r.components.push({name: 'QualityComponent',type: AliPlayerComponent.QualityComponent,args:[(definition,desc)=>{console.log(definition + '-----' + desc);}]});";
            $events .= "player.on('sourceloaded', function(params) {var paramData = params.paramData;var desc = paramData.desc;var definition = paramData.definition;player.getComponent('QualityComponent').setCurrentQuality(desc, definition);});";
        }
        
    }
    elseif ($oss && $oss['key']??false && $oss['bucket']??false) {
        $ossClient = $ActiveAddons['aliyun']->Alioss;
        $media = $ossClient->get_mediaUrl($oss['key'], $oss['bucket']);
        if ($media['status'] == 1) {
            $mini_src = $media['data'];
            $pconfig['source'] = $mini_src;
        }
    } 
    else {
        return '';
    }
    if ( mcv_is_wechat_miniprogram() ) {
        
        echo '<video style="width:100%;height:' . esc_attr($height) . 'px;" id="' . esc_attr($videoId) . '" autoplay="' . esc_attr($pconfig['autoplay']) . '" controls="true" show-casting-button="true" show-screen-lock-button="true" show-center-play-btn="true" play-btn-position="center" initial-time="0" objectFit="contain" enable-auto-rotation="true" vslide-gesture-in-fullscreen="true" vslide-gesture="true" src="' . esc_url($mini_src) . '" poster="' . esc_url($poster) . '" show-progress="true"></video>';
        return;
    }

    $error_expire = '';
    if(isset($pconfig['playauth'])){
        $error_expire = 'window.aliplayer_'.str_replace('-','_',$divId).'.on("error", function(e){
            if(e.paramData.error_code == 4500){
                jQuery(".prism-ErrorMessage").html("<svg style=\"position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" ><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>");
                jQuery.post("'.admin_url("admin-ajax.php").'",{"action":"mcv_alivod_playauth","videoId":aliplayerconfig_'.$r.'.vid}, function(data){
                    window.aliplayer_'.str_replace('-','_',$divId).'.replayByVidAndPlayAuth(aliplayerconfig_'.$r.'.vid,data);
                });
            }
        });';
    }
    else{
        $error_expire = 'window.aliplayer_'.str_replace('-','_',$divId).'.on("error", function(e){
            if(e.paramData.error_code == 4006){
                jQuery(".prism-ErrorMessage").html("<svg style=\"position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" ><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>");
                jQuery.post("'.admin_url("admin-ajax.php").'",{"action":"mcv_alivod_playauth","videoId":aliplayerconfig_'.$r.'.vid}, function(data){
                    var source = JSON.parse(data);
                    var m3u8 = "";
                    for(var s in source){
                        m3u8 = source[s];
                    }
                    window.aliplayer_'.str_replace('-','_',$divId).'.loadByUrl(m3u8);
                });
            }
        });';

    }


    if (isset(MINECLOUDVOD_SETTINGS['aliplayerconfig']) && MINECLOUDVOD_SETTINGS['aliplayerconfig']['snapshot']) {
        $events .= "player.on('snapshoted', function (data) {
            var pictureData = data.paramData.base64;
            var downloadElement = document.createElement('a');
            downloadElement.setAttribute('href', pictureData);
            var fileName = 'Aliplayer' + Date.now() + '.png';
            downloadElement.setAttribute('download', fileName);
            downloadElement.click();
            pictureData = null;
          });";
    }

    if (isset(MINECLOUDVOD_SETTINGS['aliplayer_sticky']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_sticky']['status']) {
        $events .= 'var stickyScrollHandler_' . str_replace('-', '_', $divId) . ' = (function() {
            var ticking = false;
            return function() {
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        var scrollTop = jQuery(window).scrollTop();
                        if(scrollTop > window.outerHeight){
                            jQuery("#' . $divId . '").addClass("mcv-fixed");
                        }else if(scrollTop < window.outerHeight){
                            jQuery("#' . $divId . '").removeClass("mcv-fixed");
                        }
                        ticking = false;
                    });
                    ticking = true;
                }
            };
        })();
        jQuery(window).on("scroll", stickyScrollHandler_' . str_replace('-', '_', $divId) . ', { passive: true });';
    }
    // //press space key to pause or start playback.
    $pauseplay = 'var status = player.getStatus();if(status == "playing"){player.pause();}else if(status == "pause" || status == "ended" || status == "ready" || status == "loading"){player.play();}';
    
    // //click the video to pause or start playback.
    $events .= 'var isTouchDevice = window.matchMedia("(pointer: coarse)").matches || "ontouchstart" in window;
    if (isTouchDevice) {
        var touchtime = new Date().getTime();
        jQuery("#' . $divId . ' video").on("click",function(){
            if((new Date()).getTime() - touchtime < 500 ){
                '.$pauseplay.'
                touchtime=0;
            }else{
                touchtime = new Date().getTime();
            }
            return false;
        });
    }else{
        jQuery("#' . $divId . ' video").on("click",function(){
            '.$pauseplay.'
            return false;
        });
    }';
    //check live is ended.
    $events .= 'player.on("liveStreamStop",function(){
        jQuery("#' . $divId . '").removeClass("prism-player").addClass("mcv-cd-box");
        jQuery("#' . $divId . '").height(jQuery("#' . $divId . '").width()*.5625);
        jQuery("#' . $divId . '").html("<style>.mcv-cd-box {display: flex;justify-content: center;align-items: center;flex-direction: column;width:100%;}.mcv-cd-box h1{text-align: center;letter-spacing: 3px;font-weight: 500;color: #fff;font-size:27px;margin-bottom:20px;}</style><div class=\"mcv-cd-box\"><h1>' . $textLiveEnd . '</h1></div>");
    });';

    //handle wx fullscreen
    // $events .='player.on("requestFullScreen",function(){jQuery(".prism-player").css("padding-top","0");});';
    // $events .='player.on("cancelFullScreen",function(){jQuery(".prism-player").css("padding-top","56.25%");});';
    $events .= 'jQuery("#' . $divId . ' video").attr("crossorigin", "Anonymous");';

    /**
     * 过滤aliplayer播放器事件
     * 
     * @since 1.7.6
     */
    $events = apply_filters('mcv_filter_aliplayer_events', $events, $pconfig, $attributes);

    $video = '<div id="' . $divId . '"></div>';


    // @date_default_timezone_set(wp_timezone_string());
    if ($countdown && strtotime($countdown) > time()) {
        $inlineScript = 'var aliplayer_'.str_replace('-','_',$divId).';
        jQuery(function(){
            var cdid,liveid;
            if(jQuery("#' . $divId . '")){
                jQuery("#' . $divId . '").css({width:"' . $width . '", height:"' . $height . '", "padding-top":"0"});
                jQuery("#' . $divId . '").height(jQuery("#' . $divId . '").width()*.5625);
                jQuery(window).resize(function(){jQuery("#' . $divId . '").height(jQuery("#' . $divId . '").width()*.5625);});
                jQuery("#' . $divId . '").addClass("mcv-cd-box");
                jQuery("#' . $divId . '").html("<style>.mcv-cd-box {display: flex;justify-content: center;align-items: center;flex-direction: column;width:100%;}.mcv-cd-box h1{text-align: center;letter-spacing: 3px;font-weight: 500;color: #fff;font-size:27px;margin-bottom:20px;}#mcv_time{display: flex;flex-direction: row;line-height: 50px;}#mcv_time span {font-size: 20px;color: #fff;}#mcv_time strong {text-align: center;margin-left: 20px;background-color: #3f5174;border-radius: 10px;width: auto;padding:0 7px;height: 50px;display: block;}@media (max-width: 450px) {#mcv_time {flex-direction: column;}#mcv_time strong {margin-left:0;margin-bottom: 10px;}}</style><div class=\"mcv-cd-box\"><h1>' . $countdowntips . '</h1><div id=\"mcv_time\"><strong><span id=\"mcv_day\">**D</span></strong><strong><span id=\"mcv_hour\">**Hr</span></strong><strong><span id=\"mcv_minute\">**Min</span></strong><strong><span id=\"mcv_second\">**Sec</span></strong></div></div>");
                TimeRow();
                cdid = setInterval(TimeRow, 3000);
            }
            function TimeRow() {
                var end = new Date("' . $countdown . '").getTime()/1000;
                var start = Date.parse(new Date())/1000;
                var time = getInterval(start, end);
                jQuery("#mcv_day").html(time.day+" ' . __('Day', 'mine-cloudvod') . '");
                jQuery("#mcv_hour").html(time.hour+" ' . __('Hr', 'mine-cloudvod') . '");
                jQuery("#mcv_minute").html(time.minute+" ' . __('Min', 'mine-cloudvod') . '");
                jQuery("#mcv_second").html(time.second+" ' . __('Sec', 'mine-cloudvod') . '");
            }
            function getInterval(start, end) {
                var interval = end - start;
                var day, hour, minute, second;
                if(interval>=0){
                    day = parseInt(interval / 60 / 60 / 24);
                    hour = parseInt(interval / 60 / 60 % 24);
                    minute = parseInt(interval / 60 % 60);
                    second = parseInt(interval % 60);
                }
                else{
                    clearInterval(cdid);
                    jQuery("#' . $divId . '").html("");
                    day = 0;
                    hour = 0;
                    minute = 0;
                    second = 0;
                    var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; 
                    if(!window.aliplayer_'.str_replace('-','_',$divId).'){' . $components . '
                        aliplayerconfig_' . $r . '.autoplay=true;
                        if(aliplayerconfig_' . $r . '.isLive){
                            jQuery("#' . $divId . '").html("<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" ><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>");
                            liveid = setInterval(checkLive, 3000, aliplayerconfig_' . $r . '.source);
                        }
                        else{
                            window.aliplayer_'.str_replace('-','_',$divId).'=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});'.$error_expire.'
                        }
                    }
                }
                return {
                  day: day,
                  hour: hour,
                  minute: minute,
                  second: second
                }
            }
            function checkLive(source){
                if(source.indexOf(".m3u8")>0){
                jQuery.ajax({
                    url: source,
                    type: "GET",
                    complete: function(response) {
                        if(response.status == 200) {
                            clearInterval(liveid);
                            jQuery("#' . $divId . '").html("");
                            var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; aliplayerconfig_'.$r.'.autoplay=true;
                            window.aliplayer_'.str_replace('-','_',$divId).'=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});'.$error_expire.'
                        } 
                    }
                });}
            }
        });';
    } else {//Aliplayer.__unable2Anti9Debugger13Key = "error";
        $inlineScript = 'jQuery(function(){Aliplayer.__unable2Anti9Debugger13Key = "error";
            var liveid;
            function checkLive(source){
                if(source.indexOf(".m3u8")>0){
                jQuery.ajax({
                    url: source,
                    type: "GET",
                    complete: function(response) {
                        if(response.status == 200) {
                            clearInterval(liveid);
                            jQuery("#' . $divId . '").html("");
                            var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; aliplayerconfig_'.$r.'.autoplay=true;
                             window.aliplayer_'.str_replace('-','_',$divId).'=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});'.$error_expire.'
                        }
                    }
                });}
                else{
                    clearInterval(liveid);
                    jQuery("#' . $divId . '").html("");
                    var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; aliplayerconfig_'.$r.'.autoplay=true;
                    window.aliplayer_'.str_replace('-','_',$divId).'=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});'.$error_expire.'
                }
            }
            if(jQuery("#' . $divId . '")){
                var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; 
                if(aliplayerconfig_' . $r . '.isLive){
                    jQuery("#' . $divId . '").html("<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" ><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>");
                    liveid = setInterval(checkLive, 3000, aliplayerconfig_' . $r . '.source);
                }
                else{
                    if(!window.aliplayer_'.str_replace('-','_',$divId).'){' . $components . '
                        window.aliplayer_'.str_replace('-','_',$divId).'=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});'.$error_expire.'
                    }
                }
            }
            var observer_' . str_replace('-', '_', $divId) . ' = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutationRecord) {
                    mutationRecord.target.style.position && document.querySelector("#' . $divId . ' video")?.remove();
                });
            });
            
            var target_' . str_replace('-', '_', $divId) . ' = document.querySelector("#' . $divId . '");
            if (target_' . str_replace('-', '_', $divId) . ') {
                observer_' . str_replace('-', '_', $divId) . '.observe(target_' . str_replace('-', '_', $divId) . ', { attributes : true, attributeFilter : ["style"] });
            }
        });';
    }
    
    $inlineScript = mcv_trim($inlineScript);
    
    wp_add_inline_script('mcv_aliplayer', $inlineScript);

    $video_safe = apply_filters('mcv_filter_aliplayer', $video, $pconfig, $components, $events, $r, $attributes);
    echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 播放器 HTML 已在构建时逐段 esc_* 转义，并经 apply_filters 过滤
    if (isset($enqueue) && !$enqueue) {
        echo '<script>' . esc_js($inlineScript) . '</script>';
    }