<?php
namespace MineCloudvod\Blocks;

class Dplayer
{
    public function __construct()
    {
        if( \WP_Block_Type_Registry::get_instance()->is_registered('mine-cloudvod/dplayer') ) return;
        // add_action( 'init', [ $this, 'mcv_dplayer_assets'] );
        add_action( 'mcv_add_admin_options_after_aliplayer', array( $this, 'dplayer_admin_options' ) );

        add_action( 'init',     [ $this, 'mcv_register_block'] );
    }

    public function mcv_register_block(){
        
        wp_register_script(//mcv_dplayer_hls
            'mcv_dplayer_hls',
            MINECLOUDVOD_URL.'/static/dplayer/hls.min.js',
            null,
            MINECLOUDVOD_VERSION,
            true
        );

        wp_register_style(
            'mcv_dplayer_css',
            MINECLOUDVOD_URL.'/static/dplayer/style.css', 
            is_admin() ? array( 'wp-editor' ) : null,
            MINECLOUDVOD_VERSION
        );
        wp_register_script(//mcv_dplayer
            'mcv_dplayer',
            MINECLOUDVOD_URL.'/static/dplayer/McvDPlayer.min.js',
            // 'http://localhost:8080/McvDPlayer.js',
            array( 'jquery', 'mcv_layer', 'mcv_dplayer_hls' ),
            MINECLOUDVOD_VERSION,
            true
        );
        
        register_block_type( MINECLOUDVOD_PATH . '/build/dplayer/');
    }

    public function dplayer_admin_options(){
        $prefix = 'mcv_settings';

        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_dplayer',
            'title' => 'DPlayer',
            'icon'  => 'fab fa-dochub',
          ) );
        \MCSF::createSection( $prefix, array(
            'parent'     => 'mcv_dplayer',
            'title'  => __('Configure Player', 'mine-cloudvod'),
            'icon'   => 'fas fa-wrench',
            'fields' => array(
                array(
                    'id'        => 'dplayerconfig',
                    'type'      => 'fieldset',
                    'title'     => __('Configure', 'mine-cloudvod'),
                    'fields'    => array(
                        array(
                            'id'    => 'lang',
                            'title' => __('Language', 'mine-cloudvod'),
                            'type'  => 'select',
                            'options'     => array(
                                'zh-cn'      => 'zh-cn',
                                'en'      => 'en-us',
                                'zh-tw'    => 'zh-tw',
                            ),
                            'attributes' => array(
                            'style'    => 'min-width: 100px;'
                            ),
                            'default'     => 'zh-cn',
                        ),
                        array(
                            'id'    => 'autoplay',
                            'type'  => 'switcher',
                            'title' => __('Autoplay', 'mine-cloudvod'),
                            'help' => __('Whether the player automatically plays, the autoplay attribute will be invalid on the mobile terminal. Safari11 will not automatically turn on autoplay<a href="https://h5.m.youku.com//ju/safari11guide.html" target="_blank">How to turn on</a>', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false
                        ),
                        array(
                            'id'    => 'theme',
                            'type'  => 'color',
                            'title' => __('Main Color', 'mine-cloudvod'),
                            'default' => '#b7daff'
                        ),
                        array(
                            'id'    => 'loop',
                            'type'  => 'switcher',
                            'title' => __('Loop', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false
                        ),
                        array(
                            'id'    => 'preload',
                            'title' => __('Preload', 'mine-cloudvod'),
                            'type'  => 'select',
                            'options'     => array(
                                'auto' => 'Auto',
                                'none' => 'None',
                                'metadata' => 'Metadata',
                            ),
                            'attributes' => array(
                            'style'    => 'min-width: 100px;'
                            ),
                            'default'     => 'auto',
                        ),
                        array(
                            'id'       => 'volume',
                            'type'     => 'slider',
                            'title'    => __('Default Volume', 'mine-cloudvod'),
                            'min'      => 0,
                            'max'      => 1,
                            'step'     => 0.1,
                            'default'  => 0.7,
                        ),
                        array(
                            'id'    => 'screenshot',
                            'type'  => 'switcher',
                            'title' => __('Screenshot button', 'mine-cloudvod'),
                            'text_on'    => __('Show', 'mine-cloudvod'),
                            'text_off'   => __('Hide', 'mine-cloudvod'),
                            'default' => false
                        ),
                        array(
                            'id'    => 'airplay',
                            'type'  => 'switcher',
                            'title' => 'AirPlay',
                            'subtitle' => __('Enable airplay in Safari', 'mine-cloudvod'),//'在Safari中启用airplay',
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => true
                        ),
                        array(
                            'id'    => 'hotkey',
                            'type'  => 'switcher',
                            'title' => '热键',
                            'subtitle' => '启用热键支持FF、FR、音量控制、播放和暂停',
                            'text_on'    => '启用',
                            'text_off'   => '禁用',
                            'default' => true
                        ),
                    ),
                ),
                
            )
        ));
        //实用组件
        \MCSF::createSection($prefix, array(
            'parent'      => 'mcv_dplayer',
            'title'       => __('Utility components', 'mine-cloudvod'),
            'icon'        => 'fab fa-delicious',
            'description' => '',
            'fields' => array(
                array(
                    'id'        => 'dplayer_components',
                    'type'      => 'fieldset',
                    'title'     => __('Utility components', 'mine-cloudvod'),
                    'fields'    => array(
                        array(//logo
                            'id'           => 'logo',
                            'type'         => 'upload',
                            'title'        => 'Logo',
                            'library'      => 'image',
                            'button_title' => 'Upload',
                        ),
                        array(//note
                            'id'        => 'note',
                            'type'      => 'fieldset',
                            'title'     => __('Note', 'mine-cloudvod'),
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
                                    'type'    => 'submessage',
                                    'style'   => 'warning',
                                    'content' => ' <a href="javascript:mcv_init_note();">' . __('For the first time, please click here to initialize', 'mine-cloudvod') . '</a>',
                                    'dependency' => array('status', '==', true),
                                ),
                            )
                        ),
                        array(//water mark
                            'id'        => 'watermark',
                            'type'      => 'fieldset',
                            'title'     => __('Watermark', 'mine-cloudvod'), 
                            'subtitle'     => '',
                            'fields'    => array(
                                array(
                                    'id'    => 'status',
                                    'type'  => 'switcher',
                                    'title' => __('State', 'mine-cloudvod'),
                                    'text_on'    => __('Enable', 'mine-cloudvod'),
                                    'text_off'   => __('Disable', 'mine-cloudvod'),
                                    'default' => false
                                ),
                                array(
                                    'id'     => 'watermarks',
                                    'type'   => 'fieldset',
                                    'fields' => array(
                                        array(
                                            'id'      => 'type',
                                            'type'    => 'radio',
                                            'title'   => __('Watermark Type', 'mine-cloudvod'),
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
                                            'title'       => __('Image', 'mine-cloudvod'),
                                            'library'     => 'image',
                                            'button_title'=> 'Select/Upload Image',
                                            'dependency'  => array('type', '==', "image"),
                                        ),
                                        array(
                                            'id'          => 'words',
                                            'type'        => 'text',
                                            'before'      => __('Can be dynamically replaced with these labels: {username} {userid} {userip} {useremail} {usernickname}', 'mine-cloudvod'), //'可动态显示 {username} {userid} {userip} {useremail}',
                                            'title'       => __('Words', 'mine-cloudvod'),
                                            'default'     => get_bloginfo("name"),
                                            'dependency'  => array('type', '==', "text"),
                                        ),
                                        array(
                                            'id'          => 'style',
                                            'type'        => 'text',
                                            'title'       => __('Style', 'mine-cloudvod'),
                                            'default'     => 'opacity: 0.3;color: #FFF;width: 100px;height: 50px;top: 50%;left: 50%;margin-top: -25px;margin-left: -50px;text-align: center;',
                                        ),
                                    ),
                                    'dependency' => array('status', '==', true),
                                ),
                            )
                        ),
                        array(//right click
                            'id'        => 'contextmenu',
                            'type'      => 'fieldset',
                            'title'     => __('Right-Click Menu', 'mine-cloudvod'),
                            'fields'    => array(
                                array(
                                    'id'    => 'videoinfo',
                                    'type'  => 'switcher',
                                    'title' => __('Video Info', 'mine-cloudvod'),
                                    'text_on'    => __('Show', 'mine-cloudvod'),
                                    'text_off'   => __('Hide', 'mine-cloudvod'),
                                    'default' => false
                                ),
                                array(
                                    'id'     => 'links',
                                    'type'   => 'group',
                                    'title'  => __('Right-Click Menu', 'mine-cloudvod'),
                                    'subtitle' => '',
                                    'accordion_title_number' => true,
                                    'fields' => array(
                                        array(
                                            'id'    => 'text',
                                            'type'  => 'text',
                                            'title' => __('Name', 'mine-cloudvod'),
                                        ),
                                        array(
                                            'id'    => 'link',
                                            'type'  => 'text',
                                            'title' => __('URL', 'mine-cloudvod'),
                                        ),
                                    ),
                                    'default' => array(
                                        array(
                                        'text'     => __('Mine CloudVod', 'mine-cloudvod'),
                                        'link' => 'https://wordpress.org/plugins/mine-cloudvod/',
                                        ),
                                    )
                                ),
                            ),
                        ),
                        array(//danmu
                            'id'        => 'danmu',
                            'type'      => 'fieldset',
                            'title'     => __('Bullet Screen', 'mine-cloudvod'),
                            'fields'    => array(
                                array(
                                    'id'    => 'status',
                                    'type'  => 'switcher',
                                    'title' => __('State', 'mine-cloudvod'),
                                    'text_on'    => __('Enable', 'mine-cloudvod'),
                                    'text_off'   => __('Disable', 'mine-cloudvod'),
                                    'default' => false,
                                ),
                                array(
                                    'id'    => 'api',
                                    'type'  => 'text',
                                    'title' => __('Bullet Screen', 'mine-cloudvod'). ' Api',
                                    'default' => '',
                                    'dependency' => array('status', '==', true),
                                ),
                            )
                        ),
                        array(//跑马灯
                            'id'        => 'slide',
                            'type'      => 'fieldset',
                            'title'     => __('Slide', 'mine-cloudvod'), //'跑马灯',
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
                                    'id'     => 'scrolltext',
                                    'type'   => 'repeater',
                                    'title'       => __('Scroll text', 'mine-cloudvod'),
                                    'fields' => array(
                                        array(
                                            'id'    => 'text',
                                            'type'  => 'text',
                                        ),
                                    ),
                                    'before'       => __('Can be dynamically replaced with these labels: {username} {userid} {userip} {useremail} {usernickname}', 'mine-cloudvod'),
                                    'after'   => __('Multiple Scroll text will be show randomly in diffrent videos.', 'mine-cloudvod'),
                                    'default' => array(
                                        array(
                                            'text' => __('Dear {username}, welcome to Mine Cloud Vod.', 'mine-cloudvod'),
                                        ),
                                    ),
                                    'dependency' => array('status', '==', true),
                                ),
                                array(
                                    'id'          => 'style',
                                    'type'        => 'text',
                                    'title'       => __('Text style', 'mine-cloudvod'), //'文本样式',
                                    'after'       => '',
                                    'default'     => "font-size:16px; color:#ddd;",
                                    'dependency' => array('status', '==', true),
                                ),
                                array(
                                    'id'    => 'position',
                                    'title' => __('Position', 'mine-cloudvod'), //'位置',
                                    'type'  => 'select',
                                    'options'     => array(
                                        'random' => __('Random', 'mine-cloudvod'), //'随机',
                                        'top' => __('Top', 'mine-cloudvod'), //'顶部',
                                        'bottom' => __('Bottom', 'mine-cloudvod'), //'底部',
                                    ),
                                    'attributes' => array(
                                        'style'    => 'min-width: 100px;'
                                    ),
                                    'default'     => 'random',
                                    'dependency' => array('status', '==', true),
                                ),
                                array(
                                    'id'          => 'duration',
                                    'type'        => 'number',
                                    'title'       => __('Duration', 'mine-cloudvod'), //'间隔',
                                    'after'       => __('Set the moving speed of the Slide, the longer the time, the slower the speed. It\'s 10 second by defalt.', 'mine-cloudvod'), //'间隔',
                                    'default'     => "10",
                                    'dependency' => array('status', '==', true),
                                ),
                            )
                        ),
                        array(//memory play
                            'id'        => 'memory',
                            'type'      => 'fieldset',
                            'title'     => __('Remember Played Position', 'mine-cloudvod'), //'记忆播放',
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
                            )
                        ),
                        array(//粘性视频
                            'id'        => 'sticky',
                            'type'      => 'fieldset',
                            'title'     => __('Sticky Video', 'mine-cloudvod'), //'粘性视频',
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
                                    'id'    => 'position',
                                    'title' => __('Video Position', 'mine-cloudvod'), //'记忆播放类型',
                                    'type'  => 'select',
                                    'options'     => array(
                                        'rb'   => __('Right Bottom', 'mine-cloudvod'), //'右下角',
                                        'rt'   => __('Right Top', 'mine-cloudvod'), //'右上角',
                                        'lb'   => __('Left Bottom', 'mine-cloudvod'), //'左下角',
                                        'lt'   => __('Left Top', 'mine-cloudvod'), //'左上角',
                                    ),
                                    'attributes' => array(
                                        'style'    => 'min-width: 100px;'
                                    ),
                                    'default'     => 'rb',
                                    'dependency' => array('status', '==', true),
                                ),
                                array(
                                    'id'        => 'width',
                                    'type'      => 'fieldset',
                                    'title'     => __('Width', 'mine-cloudvod'), //'宽度',
                                    'subtitle'     => __('The width of sticky video.', 'mine-cloudvod'),
                                    'fields'    => array(
                                        array(
                                            'id'      => 'pc',
                                            'type'    => 'number',
                                            'title'   => __('PC', 'mine-cloudvod'),
                                            'unit'    => '%',
                                            'default' => 35,
                                        ),
                                        array(
                                            'id'      => 'tablet',
                                            'type'    => 'number',
                                            'title'   => __('Tablet', 'mine-cloudvod'),
                                            'unit'    => '%',
                                            'default' => 50,
                                        ),
                                        array(
                                            'id'      => 'mobile',
                                            'type'    => 'number',
                                            'title'   => __('Mobile', 'mine-cloudvod'),
                                            'unit'    => '%',
                                            'default' => 90,
                                        ),
                                    ),
                                    'dependency' => array('status', '==', true),
                                ),
                            )
                        ),
                        array(//暂停广告
                            'id'        => 'pausead',
                            'type'      => 'fieldset',
                            'title'     => __('AD On Pause', 'mine-cloudvod'), //'暂停广告',
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
                                    'id'    => 'hour',
                                    'type'  => 'number',
                                    'title' => __('Hour', 'mine-cloudvod'), //'小时',
                                    'subtitle' => __('The number of hours the ad remains hidden after being closed.', 'mine-cloudvod'),
                                    'dependency' => array('status', '==', true),
                                    'default' => 0
                                ),
                                array(
                                    'id'     => 'images',
                                    'type'   => 'repeater',
                                    'after'   => __('Multiple Ads will be show randomly in diffrent videos.', 'mine-cloudvod'),
                                    'fields' => array(
                                        array(
                                            'id'          => 'image',
                                            'type'        => 'upload',
                                            'title'       => __('Image', 'mine-cloudvod'), //'图片',
                                            'library'      => 'image',
                                            'button_title' => 'Select/Upload Image',
                                        ),
                                        array(
                                            'id'          => 'url',
                                            'type'        => 'text',
                                            'title'       => __('Link', 'mine-cloudvod'), //'链接',
                                            'after'       => __('The link of the ad page.', 'mine-cloudvod'), //'链接',
                                        ),
                                    ),
                                    'dependency' => array('status', '==', true),
                                    'default' => array(
                                        array(
                                            'image'    => '',
                                            'url'    => '',
                                        ),
                                    ),
                                ),
                            )
                        ),
                    )
                ),
            )));
    }

    public function mcv_block_dplayer($parsed_block, $enqueue = true){
        $attributes = $parsed_block['attrs'];
        ob_start();
        include(MINECLOUDVOD_PATH.'/build/dplayer/render.php');
        $video = ob_get_clean();
            
        return $video;
    }

    public static function style_script(){
        $viewDependencies = include( MINECLOUDVOD_PATH.'/build/dplayer/view.asset.php' );
        wp_register_script(//mcv_dplayer
            'mcv_dplayer',
            MINECLOUDVOD_URL.'/static/dplayer/McvDPlayer.min.js',
            // 'http://localhost:8080/McvDPlayer.js',
            array_merge($viewDependencies['dependencies'], [ 'jquery', 'mcv_layer', 'mcv_dplayer_hls' ]),
            MINECLOUDVOD_VERSION,
            true
        );
        $inlineStyle = '';
        if ( MINECLOUDVOD_SETTINGS['dplayer_components']['sticky']['status'] ?? false ) {
            $sticky_position = 'right:5px;bottom:5px;';
            switch (MINECLOUDVOD_SETTINGS['dplayer_components']['sticky']['position']) {
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

            $inlineStyle .= '.mcv-fixed{position:fixed !important;z-index:99999;width:' . $width_pc . '% !important;height:auto !important;' . $sticky_position . '-webkit-animation: fadeInDown .5s .2s ease both; -moz-animation: fadeInDown .5s .2s ease both;}@media (max-width: 1024px) {.mcv-fixed{width:' . $width_tablet . '% !important;}}@media (max-width: 450px) {.mcv-fixed{width:' . $width_mobile . '% !important;}}
            .mcv-fixed .dplayer-controller{display:none !important;}
            ';
        }
        $inlineStyle = mcv_trim( $inlineStyle );
        wp_enqueue_style('mcv_dplayer_css');
        wp_add_inline_style('mcv_dplayer_css', $inlineStyle);
        wp_enqueue_script('mcv_dplayer');
        wp_add_inline_script('mcv_dplayer','var mcv_dplayer_config={userId:"'.(MINECLOUDVOD_SETTINGS['dogecloud']['userId']??'').'",doge_config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Dogecloud', 'mine-cloudvod'))))).'",doge_sdk:'.(MINECLOUDVOD_SETTINGS['dogecloud']['sid']??MINECLOUDVOD_SETTINGS['dogecloud']['kid']??false ? 'true' : 'false').'};');
        wp_add_inline_script('mine-cloudvod-doge-editor-script','var mcv_dplayer_config={userId:"'.(MINECLOUDVOD_SETTINGS['dogecloud']['userId']??'').'",doge_config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Dogecloud', 'mine-cloudvod'))))).'",doge_sdk:'.(MINECLOUDVOD_SETTINGS['dogecloud']['sid']??MINECLOUDVOD_SETTINGS['dogecloud']['kid']??false ? 'true' : 'false').'};');
    }
}
