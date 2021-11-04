<?php

namespace MineCloudvod\Aliyun;

class Aliplayer
{
    public function mcv_block_aliplayer($parsed_block, $enqueue = true)
    {
        global $pagenow;
        if ($enqueue && $pagenow == 'post.php') return false;
        $meta = $parsed_block['attrs'];
        $post_id = get_post() ? get_the_ID() : 0;
        $divId = sprintf('mcv-%s', md5(serialize($parsed_block)));

        $videoId    = isset($meta['videoId'])   ? $meta['videoId']  : '';
        $source     = isset($meta['source'])    ? $meta['source']   : '';
        $oss        = isset($meta['oss'])       ? $meta['oss']      : false;
        $width      = isset($meta['width'])     ? $meta['width']    : '100%';
        $height     = isset($meta['height'])    ? $meta['height']   : '500px';
        $poster     = isset($meta['thumbnail']) ? $meta['thumbnail'] : '';
        $endpoint   = isset($meta['endpoint'])  ? $meta['endpoint'] : MINECLOUDVOD_SETTINGS['alivod']['endpoint'];
        $markers    = isset($meta['markers'])   ? $meta['markers']  : false;
        $captions   = isset($meta['captions'])  ? $meta['captions'] : false;
        $slide      = $meta['slide'] ?? MINECLOUDVOD_SETTINGS['aliplayer_slide']['status'] ?? false;
        $slidetext  = isset($meta['slidetext']) ? $meta['slidetext'] : false;
        $isLive     = isset($meta['live'])      ? $meta['live']     : false;
        $autoplay   = isset($meta['autoplay'])  ? $meta['autoplay'] : (MINECLOUDVOD_SETTINGS['aliplayerconfig']['autoplay'] ? true : false);
        $countdown  = isset($meta['countdown']) ? $meta['countdown'] : false;
        $countdowntips = $meta['countdowntips'] ?? __('The video will play in ', 'mine-cloudvod');
        $textLiveEnd = $meta['textLiveEnd'] ?? __('The Live is ended. ', 'mine-cloudvod');

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
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['progress']) {
            $pctrl['children'][] = ["name" => "progress", "align" => "blabs", "x" => 0, "y" => 44];
        }
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['playButton']) {
            $pctrl['children'][] = ["name" => "playButton", "align" => "tl", "x" => 15, "y" => 12];
        }
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['timeDisplay']) {
            $pctrl['children'][] = ["name" => "timeDisplay", "align" => "tl", "x" => 10, "y" => 7];
        }
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['fullScreenButton']) {
            $pctrl['children'][] = ["name" => "fullScreenButton", "align" => "tr", "x" => 10, "y" => 12];
        }
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['subtitle']) {
            $pctrl['children'][] = ["name" => "subtitle", "align" => "tr", "x" => 15, "y" => 12];
        }
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['setting']) {
            $pctrl['children'][] = ["name" => "setting", "align" => "tr", "x" => 15, "y" => 12];
        }
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['volume']) {
            $pctrl['children'][] = ["name" => "volume", "align" => "tr", "x" => 5, "y" => 10];
        }
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['snapshot']) {
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
        if (MINECLOUDVOD_SETTINGS['aliplayerconfig']['bigPlayButton']) {
            $pskin[] = ["name" => "bigPlayButton", "align" => "blabs", "x" => 30, "y" => 80];
        }
        $pconfig = array(
            "id"                => $divId,
            "qualitySort"       => 'asc',
            "mediaType"         => 'video',
            'encryptType'       => 1,
            "width"             => $width,
            "height"            => $height,
            "isLive"            => $isLive,
            "playsinline"       => false,
            "useH5Prism"        => true,
            "cover"             => $poster,
            "autoplay"          => $autoplay,
            "language"          => MINECLOUDVOD_SETTINGS['aliplayerconfig']['language'] ?? 'zh-cn',
            "rePlay"            => MINECLOUDVOD_SETTINGS['aliplayerconfig']['rePlay'] ? true : false,
            "preload"           => MINECLOUDVOD_SETTINGS['aliplayerconfig']['preload'] ? true : false,
            "controlBarVisibility"       => MINECLOUDVOD_SETTINGS['aliplayerconfig']['controlBarVisibility'] ?? 'hover',
            "skinLayout" => $pskin,
            "components" => array()
        );

        $r = 270; //mt_rand(100, 999);
        $components = '';
        $events = '';
        if (!$isLive && $aliMarkers) {
            $pconfig['progressMarkers'] = $aliMarkers;
            $components .= "aliplayerconfig_$r.components.push({name:'ProgressComponent',type: AliPlayerComponent.ProgressComponent});";
        }
        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_logo']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_logo']['status']) {
            $components .= "aliplayerconfig_$r.components.push({name: 'LogoComponent',type: LogoComponent, args: ['".MINECLOUDVOD_SETTINGS['aliplayer_logo']['src']."','".MINECLOUDVOD_SETTINGS['aliplayer_logo']['style']."']});";
        }
        if ($slide) {
            if ($slidetext) $slideText = $slidetext;
            elseif (isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_slide']['status']) {
                if (isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['scrolltext'])) {
                    $mST = MINECLOUDVOD_SETTINGS['aliplayer_slide']['scrolltext'];
                    if (is_array($mST) && count($mST) > 0) {
                        $ra = mt_rand(0, count($mST) - 1);
                        $slideText = $mST[$ra]['text'];
                    }
                }
                //兼容1.2.14之前的版本
                elseif (isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['text'])) {
                    $slideText = MINECLOUDVOD_SETTINGS['aliplayer_slide']['text'];
                }
            }
            global $current_user;
            $slideText = str_replace(['{userid}', '{username}', '{userip}', '{useremail}', '{usernickname}'], [$current_user->ID, $current_user->user_login, $_SERVER['REMOTE_ADDR'], $current_user->user_email, $current_user->display_name], $slideText);
            $components .= "aliplayerconfig_$r.components.push({name:'BulletScreenComponent',type: AliPlayerComponent.BulletScreenComponent,args: ['$slideText', {}, '" . MINECLOUDVOD_SETTINGS['aliplayer_slide']['position'] . "']});";
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
                        $ra = mt_rand(0, count($sAD) - 1);
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
                        $ra = mt_rand(0, count($sAD) - 1);
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
                if (is_array($sAD) && count($sAD) > 0) {
                    $ra = mt_rand(0, count($sAD) - 1);
                    $csAD = $sAD[$ra];
                    $components .= "aliplayerconfig_$r.components.push({name: 'PauseADComponent',type: AliPlayerComponent.PauseADComponent,args: ['" . $csAD['image'] . "', '" . $csAD['url'] . "']});";
                }
            } elseif (isset(MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['image'])) {
                $components .= "aliplayerconfig_$r.components.push({name: 'PauseADComponent',type: AliPlayerComponent.PauseADComponent,args: ['" . MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['image'] . "', '" . MINECLOUDVOD_SETTINGS['aliplayer_PauseAD']['url'] . "']});";
            }
        }
        $mini_src = '';
        if ($source) {
            $pconfig['source'] = $source;
            $mini_src = $source;
        } elseif ($oss) {
            $ossClient = new Oss();
            $media = $ossClient->get_mediaUrl($oss['key'], $oss['bucket']);
            if ($media['status'] == 1) {
                $mini_src = $media['data'];
                $pconfig['source'] = $mini_src;
            }
        } elseif ($videoId) {
            $vod = new Vod();
            $playinfo = $vod->get_playinfo($videoId, $endpoint);
            $mini_src = isset($playinfo['mp4']) ? $playinfo['mp4'] : '';
            if (!$playinfo['hls']) {
                $pconfig['vid'] = $videoId;
                $pconfig['playauth'] = $playinfo['playauth'];
            } else {
                $pconfig['format'] = 'm3u8';
                $pihls = json_decode($playinfo['hls'], true);
                unset($pihls['AUTO']);
                $pconfig['source'] = json_encode($pihls);
                $components .= "aliplayerconfig_$r.components.push({name: 'QualityComponent',type: AliPlayerComponent.QualityComponent});";
                $events .= "player.on('sourceloaded', function(params) {var paramData = params.paramData;var desc = paramData.desc;var definition = paramData.definition;player.getComponent('QualityComponent').setCurrentQuality(desc, definition);});";
            }
        } else {
            return '';
        }
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'miniprogram') !== false) {
            $video = '<video style="width:100%;height:' . $height . 'px;" id="' . $videoId . '" autoplay="' . $pconfig['autoplay'] . '" controls="true" show-casting-button="true" show-screen-lock-button="true" show-center-play-btn="true" play-btn-position="center" initial-time="0" objectFit="contain" enable-auto-rotation="true" vslide-gesture-in-fullscreen="true" vslide-gesture="true" src="' . $mini_src . '" poster="' . $poster . '" show-progress="true"></video>';
            return $video;
        }


        if ($captions && is_array($captions) && count($captions)) {
            foreach ($captions as $caption) {
                $events .= sprintf("mcv_getVtt('%s', '%s', '%s');", $caption['src'], $caption['label'], $caption['lang']);
            }
        }

        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_sticky']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_sticky']['status']) {
            $events .= 'jQuery(window).scroll(function(){
                if(jQuery(window).scrollTop()>window.outerHeight){
                    jQuery("#' . $divId . '").addClass("mcv-fixed");
                }else if(jQuery(window).scrollTop()<window.outerHeight){
                    jQuery("#' . $divId . '").removeClass("mcv-fixed");
                }
            });';
        }
        //press space key to pause or start playback.
        $pauseplay = 'var status = player.getStatus();if(status == "playing"){player.pause();}else if(status == "pause" || status == "ended" || status == "ready" || status == "loading"){player.play();}';
        $events .= 'jQuery("body").unbind("keydown").bind("keydown",function(e){
            if(e.keyCode==32){
                '.$pauseplay.'
                return false;
            }
        });';
        //click the video to pause or start playback.
        $events .= 'jQuery("#' . $divId . ' video").click(function(){
            '.$pauseplay.'
            return false;
        });';
        //check live is ended.
        $events .= 'player.on("liveStreamStop",function(){
            jQuery("#' . $divId . '").removeClass("prism-player").addClass("mcv-cd-box");
            jQuery("#' . $divId . '").height(jQuery("#' . $divId . '").width()*.5625);
            jQuery("#' . $divId . '").html("<style>.mcv-cd-box {display: flex;justify-content: center;align-items: center;flex-direction: column;background-color:#2a3852;width:100%;}.mcv-cd-box h1{text-align: center;letter-spacing: 3px;font-weight: 500;color: #fff;font-size:27px;margin-bottom:20px;}</style><div class=\"mcv-cd-box\"><h1>' . $textLiveEnd . '</h1></div>");
        });';

        $video = '<div id="' . $divId . '"></div>';

        self::style_script();
        date_default_timezone_set(wp_timezone_string());
        if ($countdown && strtotime($countdown) > time()) {
            $inlineScript = '
            jQuery(function(){
                var cdid,liveid;
                if(jQuery("#' . $divId . '")){
                    jQuery("#' . $divId . '").css({width:"' . $width . '", height:"' . $height . '"});
                    jQuery("#' . $divId . '").height(jQuery("#' . $divId . '").width()*.5625);
                    jQuery(window).resize(function(){jQuery("#' . $divId . '").height(jQuery("#' . $divId . '").width()*.5625);});
                    jQuery("#' . $divId . '").addClass("mcv-cd-box");
                    jQuery("#' . $divId . '").html("<style>.mcv-cd-box {display: flex;justify-content: center;align-items: center;flex-direction: column;background-color:#2a3852;width:100%;}.mcv-cd-box h1{text-align: center;letter-spacing: 3px;font-weight: 500;color: #fff;font-size:27px;margin-bottom:20px;}#mcv_time{display: flex;flex-direction: row;line-height: 50px;}#mcv_time span {font-size: 20px;color: #fff;}#mcv_time strong {text-align: center;margin-left: 20px;background-color: #3f5174;border-radius: 10px;width: auto;padding:0 7px;height: 50px;display: block;}@media (max-width: 450px) {#mcv_time {flex-direction: column;}#mcv_time strong {margin-left:0;margin-bottom: 10px;}}</style><div class=\"mcv-cd-box\"><h1>' . $countdowntips . '</h1><div id=\"mcv_time\"><strong><span id=\"mcv_day\">**D</span></strong><strong><span id=\"mcv_hour\">**Hr</span></strong><strong><span id=\"mcv_minute\">**Min</span></strong><strong><span id=\"mcv_second\">**Sec</span></strong></div></div>");
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
                        var aliplayer_' . $r . ';var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; 
                        if(!aliplayer_' . $r . '){' . $components . '
                            aliplayerconfig_' . $r . '.autoplay=true;
                            if(aliplayerconfig_' . $r . '.isLive){
                                jQuery("#' . $divId . '").html("<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" ><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>");
                                liveid = setInterval(checkLive, 3000, aliplayerconfig_' . $r . '.source);
                            }
                            else{
                                aliplayer_' . $r . '=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});
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
                    jQuery.ajax({
                        url: source,
                        type: "GET",
                        async: false,
                        complete: function(response) {
                            if(response.status == 200) {
                                clearInterval(liveid);
                                jQuery("#' . $divId . '").html("");
                                var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; aliplayerconfig_'.$r.'.autoplay=true;
                                var aliplayer_' . $r . '=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});
                            } 
                        }
                    });
                }
            });';
        } else {
            $inlineScript = 'jQuery(function(){
                var liveid;
                function checkLive(source){
                    jQuery.ajax({
                        url: source,
                        type: "GET",
                        async: false,
                        complete: function(response) {
                            if(response.status == 200) {
                                clearInterval(liveid);
                                jQuery("#' . $divId . '").html("");
                                var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; aliplayerconfig_'.$r.'.autoplay=true;
                                var aliplayer_' . $r . '=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});
                            }
                        }
                    });
                }
                if(jQuery("#' . $divId . '")){
                    var aliplayer_' . $r . ';var aliplayerconfig_' . $r . '=' . json_encode($pconfig) . '; 
                    if(aliplayerconfig_' . $r . '.isLive){
                        jQuery("#' . $divId . '").html("<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" ><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg>");
                        liveid = setInterval(checkLive, 3000, aliplayerconfig_' . $r . '.source);
                    }
                    else{
                        if(!aliplayer_' . $r . '){' . $components . '
                            aliplayer_' . $r . '=new Aliplayer(aliplayerconfig_' . $r . ', function (player) {' . $events . '});
                        }
                    }
                }
            });';
        }
        $inlineScript = mcv_trim($inlineScript);
        if (!$enqueue) {
            return $video . '<script>' . $inlineScript . '</script>';
        }
        global $isilmd5;
        if (!is_array($isilmd5) || (is_array($isilmd5) && !in_array(md5($divId), $isilmd5)))
            wp_add_inline_script('mcv_aliplayer_components', $inlineScript);
        $isilmd5[] = md5($divId);
        $video = apply_filters('mcv_filter_aliplayer', $video, $pconfig, $components, $events, $r, $parsed_block);
        return $video;
    }
    public static function style_script()
    {
        if (wp_script_is('mcv_aliplayer_components')) return;
        $slideStyle = '';
        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_slide']['status']) {
            $slideStyle .= '.bullet-screen{' . MINECLOUDVOD_SETTINGS['aliplayer_slide']['style'] . '}';
        }
        $sticky = '';
        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_sticky']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_sticky']['status']) {
            $sticky_position = 'right:5px;bottom:5px;';
            switch (MINECLOUDVOD_SETTINGS['aliplayer_sticky']['position']) {
                case 'rt':
                    $sticky_position = 'right:5px;top:5px;';
                    break;
                case 'lb':
                    $sticky_position = 'left:5px;bottom:5px;';
                    break;
                case 'lt':
                    $sticky_position = 'left:5px;top:5px;';
                    break;
            }
            $width_pc       = 35;
            $width_tablet   = 50;
            $width_mobile   = 90;
            if (isset(MINECLOUDVOD_SETTINGS['aliplayer_sticky']['width'])) {
                $width_pc       = MINECLOUDVOD_SETTINGS['aliplayer_sticky']['width']['pc'];
                $width_tablet   = MINECLOUDVOD_SETTINGS['aliplayer_sticky']['width']['tablet'];
                $width_mobile   = MINECLOUDVOD_SETTINGS['aliplayer_sticky']['width']['mobile'];
            }
            $height_pc      = $width_pc     * 0.5625;
            $height_tablet  = $width_tablet * 0.5625;
            $height_mobile  = $width_mobile * 0.5625;

            $sticky = '.mcv-fixed{position:fixed;z-index:99999;width:' . $width_pc . '% !important;height:auto !important;padding-top:' . $height_pc . '%;' . $sticky_position . '-webkit-animation: fadeInDown .5s .2s ease both; -moz-animation: fadeInDown .5s .2s ease both;}@keyframes fade-in {0% {opacity: 0;}40% {opacity: 0;}100% {opacity: 1;}}@-webkit-keyframes fade-in { 0% {opacity: 0;}  40% {opacity: 0;}100% {opacity: 1;}}@-webkit-keyframes fadeInDown{0%{opacity: 0; -webkit-transform: translateY(-10px);} 100%{opacity: 1; -webkit-transform: translateY(0);}}@-moz-keyframes fadeInDown{0%{opacity: 0; -moz-transform: translateY(-10px);} 100%{opacity: 1; -moz-transform: translateY(0);}}@media (max-width: 1024px) {.mcv-fixed{width:' . $width_tablet . '% !important;padding-top:' . $height_tablet . '%;}}@media (max-width: 450px) {.mcv-fixed{width:' . $width_mobile . '% !important;padding-top:' . $height_mobile . '%;}}';
        }
        $inlineStyle = '.prism-player .prism-controlbar {
                width: 96%;
                margin: 0 2%;
            }

            .prism-player .prism-fullscreen-btn {
                margin-top: 14px !important;
                margin-right: 5px !important;
                width: 22px;
                height: 22px;
            }

            .prism-player .prism-thumbnail {
                border: none;
            }

            .prism-player .prism-play-btn {
                margin-top: 14px !important;
                margin-left: 0 !important;
                width: 22px;
                height: 22px;
            }

            .player-olympic-player-next {
                width: 24px;
                height: 28px;
            }

            .prism-volume {
                margin-top: 14px !important;
                margin-right: 17px !important;
            }

            .prism-player .prism-volume .volume-icon {
                width: 27px;
                height: 22px;
                background-repeat: no-repeat;
            }

            .prism-player .prism-volume .volume-icon .short-horizontal {
                width: 2px;
                height: 7px;
            }

            .prism-player .prism-volume .volume-icon .long-horizontal {
                width: 2px;
                height: 13px;
            }

            .prism-player .prism-volume .volume-icon.mute .short-horizontal {
                height: 13px;
                top: 7px;
            }

            .prism-player .prism-volume .volume-icon.mute .long-horizontal {
                top: 7px;
                height: 13px;
            }

            .prism-player .prism-cc-btn {
                height: 22px;
                width: 22px;
                margin-top: 14px !important;
                margin-right: 22px !important;
            }

            .prism-player .prism-setting-btn {
                width: 20px;
                height: 22px;
                margin-top: 14px !important;
                margin-right: 22px !important;
            }

            .prism-player .prism-snapshot-btn {
                width: 29px;
                height: 29px;
                margin-top: 11px !important;
                margin-right: 19px !important;
            }

            .prism-time-display {
                margin-top: 6px !important;
                margin-left: 20px !important;
            }

            .rate-components,
            .quality-components {
                line-height: 40px;
                font-size: 12px;
                padding-top: 6px;
            }

            .quality-list,
            .rate-list {
                list-style: none !important;
                padding: 0 !important;
                bottom: 44px;
            }';
        $inlineStyle .= html_entity_decode(MINECLOUDVOD_SETTINGS['aliplayercss']);
        $inlineStyle .= '.prism-player{overflow:hidden;}' . $slideStyle . '.progress-component{background-color: rgba(0,0,0,.75) !important;height:28px !important;padding: 0 0 0 8px !important;}.progress-component .icon-arrowdown{color: rgba(0,0,0,.75) !important;}.progress-component .img-wrap,.progress-component .info .time,.progress-component .pregress-play-btn{display:none !important;}.progress-component .progress-content{padding:0 !important;}.progress-component .info{padding:0 10px !important;width: auto !important;}.progress-component .info .describe{height:auto !important;margin-top:4px !important;}.prism-player .prism-progress .prism-progress-marker .prism-marker-dot{width: 8px;height: 8px;border-radius: 4px;margin-top: -2px;}.pause-ad{width:auto !important;height:auto !important;}.pause-ad .ad-content img{padding:0;margin:0;}.pause-ad .ad-text,.pause-ad .btn-close{z-index:9999;}.start-ad{position:absolute !important;top:0;}';
        $inlineStyle .= $sticky;
        $inlineStyle = mcv_trim($inlineStyle);

        wp_enqueue_style('mcv_aliplayer_css', MINECLOUDVOD_ALIPLAYER['css'], array(), MINECLOUDVOD_VERSION, false);
        wp_add_inline_style('mcv_aliplayer_css', $inlineStyle);
        wp_enqueue_script('jquery');
        wp_enqueue_script('mcv_aliplayer', MINECLOUDVOD_ALIPLAYER['js'],  array(), MINECLOUDVOD_VERSION, true);
        //wp_enqueue_script('mcv_aliplayer_components', MINECLOUDVOD_ALIPLAYER['anti'],  array('mcv_aliplayer'), MINECLOUDVOD_VERSION , false );
        wp_enqueue_script('mcv_aliplayer_components', MINECLOUDVOD_URL . '/static/aliyun/aliplayercomponents-1.0.6.min.js',  array('mcv_aliplayer'), MINECLOUDVOD_VERSION, true);
    }
}
