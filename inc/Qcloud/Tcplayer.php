<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Qcloud;

class Tcplayer
{
    public function __construct(){
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'qcloud_admin_options' ) );
    }

    public function qcloud_admin_options(){
        $prefix = 'mcv_settings';
        \MCSF::createSection( $prefix, array(
            'parent'      => 'tencentvod',
            'title'       => __('Configure Player', 'mine-cloudvod'),//'播放器配置',
            'icon'        => 'fas fa-play',
            'description' => '',
            'fields'      => array(
                array(
                    'id'        => 'tcplayerconfig',
                    'type'      => 'fieldset',
                    'title'     => __('Configure', 'mine-cloudvod'),//'配置',
                    'fields'    => array(
                        array(
                            'id'    => 'autoplay',
                            'type'  => 'switcher',
                            'title' => __('Autoplay', 'mine-cloudvod'),//'自动播放',
                            'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                            'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                            'default' => false
                        ),
                        array(
                            'id'    => 'preload',
                            'title' => __('Preload', 'mine-cloudvod'),//'自动加载',
                            'type'  => 'select',
                            'options'     => array(
                                'auto' => 'auto',
                                'meta' => 'meta',
                                'none' => 'none',
                            ),
                            'attributes' => array(
                              'style'    => 'min-width: 100px;'
                            ),
                            'default'     => 'none',
                        ),
                        array(
                            'id'    => 'loop',
                            'type'  => 'switcher',
                            'title' => __('Loop', 'mine-cloudvod'),//'循环播放',
                            'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                            'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                            'default' => false
                        ),
                        array(
                            'id'    => 'muted',
                            'type'  => 'switcher',
                            'title' => __('Mute', 'mine-cloudvod'),//'静音播放',
                            'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                            'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                            'default' => false
                        ),
                        array(
                            'id'    => 'bigPlayButton',
                            'type'  => 'switcher',
                            'title' => __('Big play button', 'mine-cloudvod'),//'大播放按钮',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
        
                        array(
                            'type'    => 'submessage',
                            'style'   => 'success',
                            'content' => __('The following is the control bar property settings', 'mine-cloudvod'),//'<p>如下是控制栏属性设置</p>',
                            ),
                        array(
                            'id'    => 'controls',
                            'type'  => 'switcher',
                            'title' => __('Control bar', 'mine-cloudvod'),//'控制栏',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'playToggle',
                            'type'  => 'switcher',
                            'title' => __('Play button', 'mine-cloudvod'),//'播放按钮',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'progressControl',
                            'type'  => 'switcher',
                            'title' => __('Progress bar', 'mine-cloudvod'),//'进度条',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'volumePanel',
                            'type'  => 'switcher',
                            'title' => __('Volume buttons', 'mine-cloudvod'),//'音量按钮',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'currentTimeDisplay',
                            'type'  => 'switcher',
                            'title' => '视频当前时间',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'durationDisplay',
                            'type'  => 'switcher',
                            'title' => '视频时长',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'timeDivider',
                            'type'  => 'switcher',
                            'title' => '时间分割符',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'playbackRateMenuButton',
                            'type'  => 'switcher',
                            'title' => '播放速率选择按钮',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'fullscreenToggle',
                            'type'  => 'switcher',
                            'title' => __('Fullscreen button', 'mine-cloudvod'),//'全屏按钮',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                        array(
                            'id'    => 'QualitySwitcherMenuButton',
                            'type'  => 'switcher',
                            'title' => '清晰度按钮',
                            'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                            'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                            'default' => true
                        ),
                    ),
                ),
              array(
                'id'       => 'tcplayercss',
                'type'     => 'code_editor',
                'title'    => __('Player style', 'mine-cloudvod'),//'宽高样式',
                'subtitle' => __('The css environment of each theme is different, which causes the player style to be disordered. It is normal. Please adjust the css compatibility according to your theme. You can contact QQ 995525477 for assistance.', 'mine-cloudvod'),//'每个主题的css环境不一样，导致播放器样式错乱属正常情况，请根据自己的主题调整css兼容性，可联系Q 995525477 协助，随意打赏或不打赏',
                'settings' => array(
                  'theme'  => 'shadowfox',
                  'mode'   => 'htmlmixed',
                ),
                'default'  =>'.video-js{
            width: 100%;
            height: auto;
            padding-top: 56.25%;
        }',
              ),
            )
        ));
        
        \MCSF::createSection( $prefix, array(
            'parent'      => 'tencentvod',
            'title'       => __('Utility components', 'mine-cloudvod'),//'实用组件',
            'icon'        => 'fab fa-delicious',
            'description' => '',
            'fields'      => array(
                array(
                    'id'        => 'tcplayer_MemoryPlay',
                    'type'      => 'fieldset',
                    'title'     => __('Remember Played Position', 'mine-cloudvod'),//'记忆播放',
                    'subtitle'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'status',
                            'type'  => 'switcher',
                            'title' => __('State', 'mine-cloudvod'),//'状态',
                            'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                            'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                            'default' => false
                        ),
                        array(
                            'id'    => 'type',
                            'title' => __('Type', 'mine-cloudvod'),//'记忆播放类型',
                            'type'  => 'select',
                            'options'     => array(
                                'false'   => __('Click to play', 'mine-cloudvod'),//'点击播放',
                                'true'    => __('Autoplay', 'mine-cloudvod'),//'自动播放',
                            ),
                            'attributes' => array(
                              'style'    => 'min-width: 100px;'
                            ),
                            'default'     => 'false',
                            'dependency' => array( 'status', '==', true ),
                        ),
                    )
                ),
                array(//动态水印
                    'id'        => 'tcplayer_dtwatermark',
                    'type'      => 'fieldset',
                    'title'     => '动态水印',
                    'subtitle'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'status',
                            'type'  => 'switcher',
                            'title' => __('State', 'mine-cloudvod'), //'状态',
                            'text_on'    => __('Enable', 'mine-cloudvod'), //'启用',
                            'text_off'   => __('Disable', 'mine-cloudvod'), //'禁用',
                            'default' => false
                        ),
                        array(
                            'id'     => 'watermarks',
                            'type'   => 'repeater',
                            'fields' => array(
                                array(
                                    'type' => 'submessage',
                                    'style'   => 'warning',
                                    'content' => '注意：<br>
                                    1. 水印移动范围为实际视频显示区域，如果视频自带黑边，播放器无法进行规避。<br>
                                    2. 动态水印不适合移动端场景，特别是劫持播放的场景。常见问题：https://cloud.tencent.com/document/product/881/20219<br>
                                    3. 多条水印随机出现。
                                    ',
                                ),
                                array(
                                    'id'      => 'type',
                                    'type'    => 'radio',
                                    'title'   => 'Watermark Type',
                                    'inline'  => true,
                                    'options' => array(
                                        'image'    => 'Image',
                                        'text'   => 'Text',
                                    ),
                                    'default' => 'image',
                                ),
                                array(
                                    'id'          => 'image',
                                    'type'        => 'upload',
                                    'title'       => __('Image', 'mine-cloudvod'), //'图片',
                                    'library'      => 'image',
                                    'button_title' => 'Select/Upload Image',
                                    'dependency' => array('type', '==', "image"),
                                ),
                                array(
                                    'id'          => 'words',
                                    'type'        => 'text',
                                    'before'       => __('Can be dynamically replaced with these labels: {username} {userid} {userip} {useremail} {usernickname}', 'mine-cloudvod'), //'可动态显示 {username} {userid} {userip} {useremail}',
                                    'title'       => __('Words', 'mine-cloudvod'), //'文本',
                                    'dependency' => array('type', '==', "text"),
                                ),
                            ),
                            'dependency' => array('status', '==', true),
                            'default' => array(
                                array(
                                    'type'    => 'text',
                                    'image'    => '',
                                    'words'    => get_bloginfo("name").' {userid}',
                                ),
                            ),
                        ),
                    )
                ),
                array(//幽灵水印
                    'id'        => 'tcplayer_ylwatermark',
                    'type'      => 'fieldset',
                    'title'     => '幽灵水印',
                    'subtitle'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'status',
                            'type'  => 'switcher',
                            'title' => __('State', 'mine-cloudvod'), //'状态',
                            'text_on'    => __('Enable', 'mine-cloudvod'), //'启用',
                            'text_off'   => __('Disable', 'mine-cloudvod'), //'禁用',
                            'default' => false
                        ),
                        array(
                            'id'     => 'watermarks',
                            'type'   => 'repeater',
                            'fields' => array(
                                array(
                                    'id'          => 'words',
                                    'type'        => 'text',
                                    'before'       => __('Can be dynamically replaced with these labels: {username} {userid} {userip} {useremail} {usernickname}', 'mine-cloudvod'), //'可动态显示 {username} {userid} {userip} {useremail}',
                                    'title'       => __('Words', 'mine-cloudvod'), //'文本',
                                ),
                            ),
                            'dependency' => array('status', '==', true),
                            'default' => array(
                                array(
                                    'words'    => get_bloginfo("name").' {userid}',
                                ),
                            ),
                        ),
                    )
                ),
            )
        ));
    }
    public function mcv_block_tcplayer($parsed_block, $enqueue = true){
        
        $videoId = sprintf('mcv_%s', md5(serialize($parsed_block['attrs'])));
        $is_rendered = mcv_is_block_rendered( $videoId );
        if( $is_rendered ) return ;

        $private = $parsed_block['attrs']['privt']??false;
        if($private && !is_user_logged_in()){
            return include(MINECLOUDVOD_PATH . "/templates/vod/private.php");
        }
        global $pagenow;
        if($enqueue && $pagenow == 'post.php') return false;
        $meta = $parsed_block['attrs'];
        if(isset($meta['videoId'])){
            $width = isset($meta['width'])?$meta['width']:'100%';
            $height = isset($meta['height'])?$meta['height']:'100%';
            $width = str_replace('px', '', $width);
            $height = str_replace('px', '', $height);
            $poster = $meta['thumbnail']??$meta['cover']??'';
            $autoplay = isset($meta['autoplay'])?$meta['autoplay']:(MINECLOUDVOD_SETTINGS['tcplayerconfig']['autoplay']?true:false);
            $captions   = isset($meta['captions'])  ? $meta['captions'] : false;
            $fileID = $meta['videoId'];
            $appID = $meta['appId']??MINECLOUDVOD_SETTINGS['tcvod']['appid'];
            $plugins = ['ProgressMarker' => true];
            if(isset(MINECLOUDVOD_SETTINGS['tcplayer_MemoryPlay']['status']) && MINECLOUDVOD_SETTINGS['tcplayer_MemoryPlay']['status']){
                $plugins['ContinuePlay'] = ['auto' => (bool)MINECLOUDVOD_SETTINGS['tcplayer_MemoryPlay']['type']==='true'];
            }
            if(isset(MINECLOUDVOD_SETTINGS['tcplayer_dtwatermark']['status']) && MINECLOUDVOD_SETTINGS['tcplayer_dtwatermark']['status']){
                $wms = MINECLOUDVOD_SETTINGS['tcplayer_dtwatermark']['watermarks']??[];
                if( count( $wms ) > 0 ){
                    $dtseed = wp_rand(0, count( $wms )-1 );
                    $wm = $wms[$dtseed];
                    $wmdata = [];
                    if( $wm['type'] == 'text' ){
                        global $current_user;
                        $watermarkContent = $wm['words'];
                        $watermarkContent = str_replace(['{userid}', '{username}', '{userip}', '{useremail}', '{usernickname}'], [$current_user->ID??'', $current_user->user_login??'', sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')), $current_user->user_email??'', $current_user->display_name??''], $watermarkContent);
                        $wmdata = [
                            'type' => 'text',
                            'content' => $watermarkContent,
                        ];
                    }
                    $plugins['DynamicWatermark'] = $wmdata;
                }
            }

            global $ActiveAddons;
            $qcvod = $ActiveAddons['qcloud']->Tcvod;

            if ( mcv_is_wechat_miniprogram() ) {
                $minfo =$qcvod->mcv_get_tcvod_mediaUrl($fileID, $appID);
				
                $mp4 = $minfo["MediaInfoSet"][0]["BasicInfo"]["MediaUrl"];//原片
                //如果有转码，则调用最低清晰度，以节省流量
                if(isset($minfo["MediaInfoSet"][0]['TranscodeInfo']['TranscodeSet'])){
                    $transInfo = $minfo["MediaInfoSet"][0]['TranscodeInfo']['TranscodeSet'];
                    if(is_array($transInfo)){
                        $st = array_column($transInfo, 'Url', 'Size');
                        sort($st);
                        $mp4 = $st[0];
                    }
                }
                $src = mcv_gen_tcvod_mediaUrl($mp4);
                $video = '<video style="width:100%;height:'.esc_attr($height).'px;" id="'.esc_attr($videoId).'" autoplay="false" controls="true" show-casting-button="true" show-screen-lock-button="true" show-center-play-btn="true" play-btn-position="center" initial-time="0" objectFit="contain" enable-auto-rotation="true" vslide-gesture-in-fullscreen="true" vslide-gesture="true" src="'.esc_url($src).'" poster="'.esc_url($poster).'" show-progress="true"></video>';
                return $video;
            }

            $pcfg = $meta['pcfg'] ?? MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig'] ?? 'default';
            $taskid = $meta['taskid'] ?? MINECLOUDVOD_SETTINGS['tcvod']['transcode'] ?? 'default';
            $post_id = get_post() ? get_the_ID() : 0;
            $instance = 0;
            $video = '';
            $video .= '<video id="'.esc_attr($videoId).'" width="'.esc_attr($width).'" height="'.esc_attr($height).'" preload="none" controls="controls" playsinline webkit-playsinline></video>';
            $psign = $qcvod->mcv_generate_psign(0, $appID, $fileID, $pcfg, $taskid);
            $pconfig = [
                'fileID'    => $fileID,
                'appID'    => $appID,
                'psign'    => $psign['psign'],
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
            //宽度100%， 高度自适应
            // if($height == 'auto' || $height == '100%'){
            //     $sWidth = $minfo["MediaInfoSet"][0]["MetaData"]["Width"] ?? '-1';
            //     $sHeight = $minfo["MediaInfoSet"][0]["MetaData"]["Height"] ?? '-1';
            //     $pTop = $sHeight / $sWidth;
            //     $pTop = $pTop > 0.8 ? 0.8 : $pTop;
            //     $istyle = '';
            //     if($width == '100%' || $width == 'auto'){
            //         $istyle = '.video-js{width:100% !important;max-width:100% !important;height:auto !important;padding-top:'.($pTop*100).'% !important;}';
            //     }
            //     else if(is_numeric($width)){
            //         $istyle = '.video-js{width:'.$width.'px !important;height:'.($width*$pTop).'px !important;padding-top:0 !important;}';
            //     }
            //     wp_add_inline_style('mine_tcplayer_css', $istyle);
            // }
            $tcplayerId = 'tcplayer_'.$videoId;$events = '';
            if ($captions && is_array($captions) && count($captions)) {
                $events .= 'window.'.$tcplayerId.'.on("ready", function() {';
                $default = 'true';
                foreach ($captions as $caption) {
                    $events .= 'window.'.$tcplayerId.'.addRemoteTextTrack({src: "'.$caption['src'].'", kind:"subtitles" , srclang:"'.$caption['lang'].'", label:"'.$caption['label'].'", default:"'.$default.'"}, true);';
                    $default = 'false';
                }
                $events .= '});';
            }
            /**
             * 过滤tcplayer播放器事件
             * 
             * @since 1.7.6
             */
            $events = apply_filters('mcv_filter_tcplayer_events', $events, $pconfig, $post_id, $parsed_block);
            
            $inlineScript = 'jQuery(function(){if(jQuery("#'.$videoId.'")){var tcplayerconfig_'.$post_id. $instance.';var '.$tcplayerId.';
                tcplayerconfig_'.$post_id. $instance.'='.json_encode($pconfig).';
                if(!window.'.$tcplayerId.'){localStorage.setItem("ghostWatermarkFirstShow", 0);
                    window.'.$tcplayerId.' = TCPlayer(\''.$videoId.'\', tcplayerconfig_'.$post_id. $instance.');
                    '.$events.'
                }}});
            ';
            if(!$enqueue){
                return $video.'<script>'.$inlineScript.'</script>';
            }
            wp_add_inline_script('mcv_tcplayer', $inlineScript);
            $video = apply_filters('mcv_filter_tcplayer', $video, $pconfig, $post_id, $parsed_block, $events);
            return $video;
        }
        elseif(isset($meta['cos']) && $meta['cos']['key']){
            global $ActiveAddons;
            $cos = $ActiveAddons['qcloud']->Tccos;
            $source = $cos->get_mediaUrl($meta['cos']['key'], $meta['cos']['bucket']);
            $meta['source'] = $source['data'];
            unset($meta['cos']);
            wp_register_style( 'mcv-inline-style', false, array(), MINECLOUDVOD_VERSION );
            wp_enqueue_style( 'mcv-inline-style' );
            wp_add_inline_style( 'mcv-inline-style', 'img.tcp-vtt-thumbnail-img{max-width:unset !important;max-height:unset !important;}'.html_entity_decode(MINECLOUDVOD_SETTINGS['tcplayercss']) );
        
            $video = do_blocks('<!-- wp:mine-cloudvod/dplayer '.json_encode($meta).' /-->');

            return $video;
        }
        return false;
    }
    public static function style_script(){
        wp_register_script(
            'mcv_tcplayerhls',
            'https://web.sdk.qcloud.com/player/tcplayer/release/v4.9.0/libs/hls.min.1.1.7.js',
            null,
            MINECLOUDVOD_VERSION,
            true
        );
        wp_register_script(
            'mcv_tcplayer',
            'https://web.sdk.qcloud.com/player/tcplayer/release/v4.9.0/tcplayer.v4.9.0.min.js',
            ['jquery', 'mcv_tcplayerhls'],
            MINECLOUDVOD_VERSION,
            true
        );
        
        wp_register_style(
            'mcv_tcplayer_css',
            'https://tcsdk.com/player/tcplayer/release/v5.3.4/tcplayer.min.css', 
            null,
            MINECLOUDVOD_VERSION,
            'all'
        );
        wp_enqueue_script( 'mcv_tcplayer' );
        wp_enqueue_style( 'mcv_tcplayer_css' );
        wp_add_inline_style('mcv_tcplayer_css', 'img.tcp-vtt-thumbnail-img{max-width:unset !important;max-height:unset !important;}'.html_entity_decode(MINECLOUDVOD_SETTINGS['tcplayercss']));
        
    }
}
