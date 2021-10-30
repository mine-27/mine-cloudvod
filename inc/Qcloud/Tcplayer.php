<?php
namespace MineCloudvod\Qcloud;

class Tcplayer
{
    public function mcv_block_tcplayer($parsed_block, $enqueue = true){
        global $pagenow;
        if($enqueue && $pagenow == 'post.php') return false;
        $meta = $parsed_block['attrs'];
        if(isset($meta['videoId']) && isset($meta['appId'])){
            $width = isset($meta['width'])?$meta['width']:'100%';
            $height = isset($meta['height'])?$meta['height']:'300';
            $poster = isset($meta['thumbnail'])?$meta['thumbnail']:'';
            $autoplay = isset($meta['autoplay'])?$meta['autoplay']:(MINECLOUDVOD_SETTINGS['tcplayerconfig']['autoplay']?true:false);
            $fileID = $meta['videoId'];
            $appID = $meta['appId'];
            $plugins = ['ProgressMarker' => true];
            if(isset(MINECLOUDVOD_SETTINGS['tcplayer_MemoryPlay']['status']) && MINECLOUDVOD_SETTINGS['tcplayer_MemoryPlay']['status']){
                $plugins['ContinuePlay'] = ['auto' => (bool)MINECLOUDVOD_SETTINGS['tcplayer_MemoryPlay']['type']==='true'];
            }


            $qcvod = new Vod();
            $videoId = sprintf( 'mcv-%s', md5(serialize($parsed_block)) );
            if(stripos($_SERVER['HTTP_USER_AGENT'], 'miniprogram') !== false){
                $minfo =$qcvod->mcv_get_tcvod_mediaUrl($fileID, $appID);
                $src = mcv_gen_tcvod_mediaUrl($minfo["MediaInfoSet"][0]["BasicInfo"]["MediaUrl"]);
                $video = '<video style="width:100%;height:'.$height.'px;" id="'.$videoId.'" autoplay="false" controls="true" show-casting-button="true" show-screen-lock-button="true" show-center-play-btn="true" play-btn-position="center" initial-time="0" objectFit="contain" enable-auto-rotation="true" vslide-gesture-in-fullscreen="true" vslide-gesture="true" src="'.$src.'" poster="'.$poster.'" show-progress="true"></video>';
                return $video;
            }

            $pcfg = isset($meta['pcfg'])?$meta['pcfg']:'default';
            $post_id = get_post() ? get_the_ID() : 0;
            $instance = 0;
            $video = '';
            $video .= '<video id="'.$videoId.'" width="'.$width.'" height="'.$height.'" preload="none" controls="controls" playsinline webkit-playsinline></video>';

            $pconfig = [
                'fileID'    => $fileID,
                'appID'    => $appID,
                'psign'    => $qcvod->mcv_generate_psign(0, $appID, $fileID, $pcfg),
                'poster'    => $poster,
                'preload'    => MINECLOUDVOD_SETTINGS['tcplayerconfig']['preload'],
                'controls'    => MINECLOUDVOD_SETTINGS['tcplayerconfig']['controls']?true:false,
                'autoplay'      => $autoplay,
                'loop'          => MINECLOUDVOD_SETTINGS['tcplayerconfig']['loop']?true:false,
                'muted'         => MINECLOUDVOD_SETTINGS['tcplayerconfig']['muted']?true:false,
                'bigPlayButton'       => MINECLOUDVOD_SETTINGS['tcplayerconfig']['bigPlayButton']?true:false,
                'controlBar'    => [
                    'playToggle'                    => MINECLOUDVOD_SETTINGS['tcplayerconfig']['playToggle']?true:false,
                    'progressControl'               => MINECLOUDVOD_SETTINGS['tcplayerconfig']['progressControl']?true:false,
                    'volumePanel'                   => MINECLOUDVOD_SETTINGS['tcplayerconfig']['volumePanel']?true:false,
                    'currentTimeDisplay'            => MINECLOUDVOD_SETTINGS['tcplayerconfig']['currentTimeDisplay']?true:false,
                    'durationDisplay'               => MINECLOUDVOD_SETTINGS['tcplayerconfig']['durationDisplay']?true:false,
                    'timeDivider'                   => MINECLOUDVOD_SETTINGS['tcplayerconfig']['timeDivider']?true:false,
                    'playbackRateMenuButton'        => MINECLOUDVOD_SETTINGS['tcplayerconfig']['playbackRateMenuButton']?true:false,
                    'fullscreenToggle'              => MINECLOUDVOD_SETTINGS['tcplayerconfig']['fullscreenToggle']?true:false,
                    'QualitySwitcherMenuButton'    => MINECLOUDVOD_SETTINGS['tcplayerconfig']['QualitySwitcherMenuButton']?true:false,
                ],
                'plugins'       => $plugins,
                'hlsConfig'     => ['autoStartLoad' => MINECLOUDVOD_SETTINGS['tcplayerconfig']['preload']=='none'?false:true]
            ];
            self::style_script();
            $inlineScript = 'jQuery(function(){if(jQuery("#'.$videoId.'")){var tcplayerconfig_'.$post_id. $instance.';var tcplayer_'.$post_id. $instance.';
                tcplayerconfig_'.$post_id. $instance.'='.json_encode($pconfig).';
                if(!tcplayer_'.$post_id. $instance.'){
                    tcplayer_'.$post_id. $instance.' = TCPlayer(\''.$videoId.'\', tcplayerconfig_'.$post_id. $instance.');
                }}});
            ';
            if(!$enqueue){
                return $video.'<script>'.$inlineScript.'</script>';
            }
            wp_add_inline_script('mine_tcplayer', $inlineScript);
            $video = apply_filters('mcv_filter_tcplayer', $video, $pconfig, $post_id, $parsed_block);
            return $video;
        }
        elseif(isset($meta['cos']) && $meta['cos']['key']){
            $cos = new Cos();
            $source = $cos->get_mediaUrl($meta['cos']['key'], $meta['cos']['bucket']);
            $meta['source'] = $source['data'];
            unset($meta['cos']);
            $aliplayer = new \MineCloudvod\Aliyun\Aliplayer();
            $video = $aliplayer->mcv_block_aliplayer(['attrs'=>$meta], $enqueue);
            return $video;
        }
        return false;
    }
    public static function style_script(){
        wp_enqueue_style('mine_tcplayer_css', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/tcplayer.min.css', array(), false);
        wp_add_inline_style('mine_tcplayer_css', 'img.tcp-vtt-thumbnail-img{max-width:unset !important;}'.html_entity_decode(MINECLOUDVOD_SETTINGS['tcplayercss']));
        wp_enqueue_script('jquery');
        wp_enqueue_script('mine_tcplayerhls', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/libs/hls.min.0.13.2m.js',  array(), MINECLOUDVOD_VERSION , true );
        wp_enqueue_script('mine_tcplayer', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/tcplayer.v4.2.1.min.js',  array(), MINECLOUDVOD_VERSION , true );
    }
}
