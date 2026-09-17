<?php
if ( ! defined( 'ABSPATH' ) ) exit;

\MCSF::createSection( $prefix, array(
    'id'    => 'aliplayer',
    'title' => __('Aliplayer', 'mine-cloudvod'),
    'icon'  => 'fas fa-play',
  ) );

\MCSF::createSection( $prefix, array(
    'parent'      => 'aliplayer',
    'title'       => __('Configure Player', 'mine-cloudvod'),//'播放器配置',
    'icon'        => 'fas fa-play',
    'description' => '',
    'fields'      => array(
        array(
            'id'        => 'aliplayerconfig',
            'type'      => 'fieldset',
            'title'     => __('Configure', 'mine-cloudvod'),//'配置',
            'fields'    => array(
                array(
                    'id'    => 'autoplay',
                    'type'  => 'switcher',
                    'title' => __('Autoplay', 'mine-cloudvod'),//'自动播放',
                    'help' => __('Whether the player automatically plays, the autoplay attribute will be invalid on the mobile terminal. Safari11 will not automatically turn on autoplay<a href="https://h5.m.youku.com//ju/safari11guide.html" target="_blank">How to turn on</a>', 'mine-cloudvod'),//'播放器是否自动播放，在移动端autoplay属性会失效。Safari11不会自动开启自动播放<a href="https://h5.m.youku.com//ju/safari11guide.html" target="_blank">如何开启</a>',
                    'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                    'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                    'default' => false
                ),
                array(
                    'id'    => 'preload',
                    'type'  => 'switcher',
                    'title' => __('Preload', 'mine-cloudvod'),//'自动加载',
                    'help' => __('Preload currently only available in h5', 'mine-cloudvod'),//'播放器自动加载，目前仅h5可用',
                    'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                    'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                    'default' => false
                ),
                array(
                    'id'    => 'rePlay',
                    'type'  => 'switcher',
                    'title' => __('Loop', 'mine-cloudvod'),//'循环播放',
                    'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                    'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                    'default' => false
                ),
                array(
                    'id'    => 'controlBarVisibility',
                    'type'  => 'select',
                    'options'     => array(
                        'hover' => 'hover',
                        'click' => 'click',
                        'always' => 'always',
                    ),
                    'attributes' => array(
                      'style'    => 'min-width: 100px;'
                    ),
                    'default'     => 'hover',
                    'title' => __('How to display the control panel', 'mine-cloudvod'),//'控制面板的显示方式',
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
                    'id'    => 'bigPlayButtonPosition',
                    'title' => __('Big play button position', 'mine-cloudvod'), //'大播放按钮位置',
                    'type'  => 'select',
                    'options'     => array(
                        'ct'   => __('Center', 'mine-cloudvod'), //'中间',
                        'lb'    => __('Left Bottom', 'mine-cloudvod'), //'左下角',
                    ),
                    'attributes' => array(
                        'style'    => 'min-width: 100px;'
                    ),
                    'default'     => 'ct',
                    'dependency' => array('bigPlayButton', '==', true),
                ),
                array(
                    'id'    => 'language',
                    'type'  => 'select',
                    'options'     => array(
                        'en-us' => 'en-us',
                        'zh-cn' => 'zh-cn',
                    ),
                    'attributes' => array(
                      'style'    => 'min-width: 100px;'
                    ),
                    'default'     => 'zh-cn',
                    'title' => __('Language', 'mine-cloudvod'),//'语言',
                ),
                array(
                    'type'    => 'submessage',
                    'style'   => 'success',
                    'content' => __('The following is the control bar property settings', 'mine-cloudvod'),//'<p>如下是控制栏属性设置</p>',
                ),
                array(
                    'id'    => 'controlColor',
                    'type'  => 'color',
                    'title' => __('Control Bar Background Color', 'mine-cloudvod'),//'控制栏背景色',
                    'default'=> 'rgba(75,75,75,.15)'
                ),
                array(
                    'id'    => 'progress',
                    'type'  => 'switcher',
                    'title' => __('Progress bar', 'mine-cloudvod'),//'进度条',
                    'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                    'text_off'   => __('Hide', 'mine-cloudvod'),//__('Hide', 'mine-cloudvod'),//'隐藏',
                    'default' => true
                ),
                array(
                    'id'    => 'playButton',
                    'type'  => 'switcher',
                    'title' => __('Play button', 'mine-cloudvod'),//'播放按钮',
                    'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                    'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                    'default' => true
                ),
                array(
                    'id'    => 'timeDisplay',
                    'type'  => 'switcher',
                    'title' => __('Time', 'mine-cloudvod'),//'时间',
                    'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                    'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                    'default' => true
                ),
                array(
                    'id'    => 'fullScreenButton',
                    'type'  => 'switcher',
                    'title' => __('Fullscreen button', 'mine-cloudvod'),//'全屏按钮',
                    'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                    'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                    'default' => true
                ),
                array(
                    'id'    => 'setting',
                    'type'  => 'switcher',
                    'title' => __('Settings button', 'mine-cloudvod'),//'设置按钮',
                    'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                    'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                    'default' => true
                ),
                array(
                    'id'    => 'volume',
                    'type'  => 'switcher',
                    'title' => __('Volume buttons', 'mine-cloudvod'),//'音量按钮',
                    'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                    'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                    'default' => true
                ),
                array(
                    'id'    => 'subtitle',
                    'type'  => 'switcher',
                    'title' => __('Subtitle button', 'mine-cloudvod'),//'字幕按钮',
                    'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                    'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                    'default' => false
                ),
                array(
                    'id'    => 'snapshot',
                    'type'  => 'switcher',
                    'title' => __('Screenshot button', 'mine-cloudvod'),//'截图按钮',
                    'text_on'    => __('Show', 'mine-cloudvod'),//'显示',
                    'text_off'   => __('Hide', 'mine-cloudvod'),//'隐藏',
                    'default' => false
                ),
            ),
        ),
        array(
            'id'       => 'aliplayercss',
            'type'     => 'code_editor',
            'title'    => __('Player style', 'mine-cloudvod'),//'宽高样式',
            'settings' => array(
            'theme'  => 'shadowfox',
            'mode'   => 'htmlmixed',
            ),
            'default'  =>'.prism-player{
                width: 100%;
                height: auto;
                padding-top: 56.25%;
            }',
        ),
    )
));