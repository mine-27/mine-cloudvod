<?php

MCSF::createSection( $prefix, array(
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
                    'title' => __('Autoplay'),//'自动播放',
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
                    'title' => __('Mute', 'mine_cloudvod'),//'静音播放',
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

MCSF::createSection( $prefix, array(
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
    )
));