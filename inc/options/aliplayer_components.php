<?php
MCSF::createSection($prefix, array(
    'parent'      => 'aliplayer',
    'title'       => __('Utility components', 'mine-cloudvod'), //'实用组件',
    'icon'        => 'fab fa-delicious',
    'description' => '',
    'fields'      => array(
        array(
            'id'        => 'aliplayer_logo',
            'type'      => 'fieldset',
            'title'     => __('Player Logo', 'mine-cloudvod'), //'跑马灯',
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
                    'id'          => 'src',
                    'type'        => 'upload',
                    'title'       => __('Logo', 'mine-cloudvod'), //'Logo',
                    'library'      => 'image',
                    'button_title' => 'Select/Upload Image',
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'          => 'style',
                    'type'        => 'text',
                    'title'       => __('Logo style', 'mine-cloudvod'), //'文本样式',
                    'after'       => __('The logo is in the upper left corner by default', 'mine-cloudvod'),
                    'default'     => "left: 20px;top: 20px;max-width: 50px;max-height: 50px;",
                    'dependency' => array('status', '==', true),
                ),
            )
        ),
        array(
            'id'        => 'aliplayer_slide',
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
                    'title'       => __('Scroll text', 'mine-cloudvod'), //'滚动文本',
                    'fields' => array(
                        array(
                            'id'    => 'text',
                            'type'  => 'text',
                        ),
                    ),
                    'before'       => __('Can be dynamically replaced with these labels: {username} {userid} {userip} {useremail} {usernickname}', 'mine-cloudvod'), //'可动态显示 {username} {userid} {userip} {useremail}',
                    'after'   => __('Multiple Scroll text will be show randomly in diffrent videos.', 'mine-cloudvod'),
                    'default' => array(
                        array(
                            'text' => __('Dear {username}, welcome to Mine Cloud Vod.', 'mine-cloudvod'), //'亲爱的{username}用户，欢迎使用云点播',
                        ),
                    ),
                    'dependency' => array('status', '==', true),
                ),
                array(
                    'id'          => 'style',
                    'type'        => 'text',
                    'title'       => __('Text style', 'mine-cloudvod'), //'文本样式',
                    'after'       => '',
                    'default'     => "font-size:16px; color:red;",
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
            )
        ),
        array(
            'id'        => 'aliplayer_MemoryPlay',
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
                array(
                    'id'    => 'type',
                    'title' => __('Type', 'mine-cloudvod'), //'记忆播放类型',
                    'type'  => 'select',
                    'options'     => array(
                        'false'   => __('Click to play', 'mine-cloudvod'), //'点击播放',
                        'true'    => __('Autoplay', 'mine-cloudvod'), //'自动播放',
                    ),
                    'attributes' => array(
                        'style'    => 'min-width: 100px;'
                    ),
                    'default'     => 'false',
                    'dependency' => array('status', '==', true),
                ),
            )
        ),
        array(
            'id'        => 'aliplayer_Rate',
            'type'      => 'fieldset',
            'title'     => __('Rate play', 'mine-cloudvod'), //'倍速播放',
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
                    'content' => __('After the playback speed component is added, the playback speed setting in the player is hidden.', 'mine-cloudvod'), //'<p>启用倍速播放组件之后, 播放器的设置里面的倍速选项会被隐藏</p>',
                    'dependency' => array('status', '==', true),
                ),
            )
        ),
        array(
            'id'        => 'aliplayer_sticky',
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
        array(
            'id'        => 'aliplayer_StartAD',
            'type'      => 'fieldset',
            'title'     => __('AD Before Play', 'mine-cloudvod'), //'开始广告',
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
                    'id'      => 'type',
                    'type'    => 'radio',
                    'title'   => 'Ad Type',
                    'inline'  => true,
                    'options' => array(
                        'image'    => 'Image',
                        'video'   => 'Video',
                    ),
                    'default' => 'image',
                    'dependency' => array('status', '==', true),
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
                        array(
                            'id'          => 'time',
                            'type'        => 'number',
                            'title'       => __('Duration', 'mine-cloudvod'), //'时长',
                            'after'       => __('Enter the number of seconds to display the image ad before the video plays.', 'mine-cloudvod'),
                        ),
                    ),
                    'dependency' => array('status|type', '==|==', 'true|image'),
                    'default' => array(
                        array(
                            'image'    => '',
                            'time' => 6,
                            'url'    => '',
                        ),
                    ),
                ),
                array(
                    'id'     => 'videos',
                    'type'   => 'repeater',
                    'after'   => __('Multiple Ads will be show randomly in diffrent videos.', 'mine-cloudvod'),
                    'fields' => array(
                        array(
                            'id'          => 'video',
                            'type'        => 'upload',
                            'title'       => __('Video', 'mine-cloudvod'), //'视频',
                            'library'      => 'video',
                            'button_title' => 'Select/Upload Video',
                        ),
                        array(
                            'id'          => 'url',
                            'type'        => 'text',
                            'title'       => __('Link', 'mine-cloudvod'), //'链接',
                            'after'       => __('The link of the ad page.', 'mine-cloudvod'), //'链接',
                        ),
                    ),
                    'default' => array(
                        array(
                            'video' => '',
                            'url'    => '',
                        ),
                    ),
                    'dependency' => array('status|type', '==|==', 'true|video'),
                ),
            )
        ),
        array(
            'id'        => 'aliplayer_PauseAD',
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
));
