<?php
if ( ! defined( 'ABSPATH' ) ) exit;
\MCSF::createSection($prefix, array(
    'parent'      => 'aliplayer',
    'title'       => __('Utility components', 'mine-cloudvod'), //'实用组件',
    'icon'        => 'fab fa-delicious',
    'description' => '',
    'fields'      => array(
        array(//logo
            'id'        => 'aliplayer_logo',
            'type'      => 'fieldset',
            'title'     => __('Player Logo', 'mine-cloudvod'), //'LOGO',
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
                array(
                    'id'          => 'style_mb',
                    'type'        => 'text',
                    'title'       => __('Mobile Logo style', 'mine-cloudvod'), //'文本样式',
                    'default'     => "left: 20px;top: 20px;max-width: 30px;max-height: 30px;",
                    'dependency' => array('status', '==', true),
                ),
            )
        ),
        array(//watermark
            'id'        => 'aliplayer_watermark',
            'type'      => 'fieldset',
            'title'     => __('Watermark', 'mine-cloudvod'), //'水印',
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
                        array(
                            'id'          => 'style',
                            'type'        => 'text',
                            'title'       => __('Style', 'mine-cloudvod'), //'样式',
                        ),
                    ),
                    'dependency' => array('status', '==', true),
                    'default' => array(
                        array(
                            'type'    => 'image',
                            'image'    => '',
                            'words'    => get_bloginfo("name"),
                            'style'    => 'opacity: 0.3;color: #FFF;width: 100px;height: 50px;top: 50%;left: 50%;margin-top: -25px;margin-left: -50px;text-align: center;',
                        ),
                    ),
                ),
            )
        ),
        array(//跑马灯
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
        array(//倍速播放
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
        array(//粘性视频
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
        array(//开始广告
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
        array(//暂停广告
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
//         array(//试看
//             'id'        => 'aliplayer_preview',
//             'type'      => 'fieldset',
//             'title'     => __('Preview', 'mine-cloudvod'), //'试看',
//             'subtitle'     => '',
//             'sanitize' => false,
//             'fields'    => array(
//                 array(
//                     'id'    => 'status',
//                     'type'  => 'switcher',
//                     'title' => __('State', 'mine-cloudvod'), //'状态',
//                     'text_on'    => __('Enable', 'mine-cloudvod'), //'启用',
//                     'text_off'   => __('Disable', 'mine-cloudvod'), //'禁用',
//                     'default' => false
//                 ),
//                 array(
//                     'id'          => 'duration',
//                     'type'        => 'text',
//                     'title'       => __('Preview Duration', 'mine-cloudvod'), //'试看时长',
//                     'after'       => __('The default value is 60 seconds.', 'mine-cloudvod'),
//                     'default'     => "60",
//                     'dependency' => array('status', '==', true),
//                 ),
//                 array(
//                     'id'          => 'barhtml',
//                     'type'        => 'text',
//                     'title'       => __('Bar Html', 'mine-cloudvod'), //'试看提示语',
//                     'after'       => __('the prompt that appears in the lower-left corner', 'mine-cloudvod'),
//                     'default'     => '<a href="/wp-login.php" style="color:#FFF;">登录</a>后 观看完整视频',
//                     'dependency' => array('status', '==', true),
//                     'sanitize' => false,
//                 ),
//                 array(
//                     'id'          => 'endhtml',
//                     'type'        => 'code_editor',
//                     'title'       => __('End Html', 'mine-cloudvod'), //'试看结束提示',
//                     'after'       => __('The prompt that appears in the middle of the player after the preview ends.', 'mine-cloudvod'),
//                     'default'     => '<center>
//     试看结束<br>
//     <a href="/" class="vip-join">登录</a>后 观看完整视频
// </center>',
//                     'dependency' => array('status', '==', true),
//                     'settings' => array(
//                         'theme'  => 'mdn-like',
//                         'mode'   => 'htmlmixed',
//                       ),
//                     'sanitize' => false,
//                 ),
//             )
//         ),
        array(
            'id'        => 'aliplayer_Quality',
            'type'      => 'fieldset',
            'title'     => __('Quality', 'mine-cloudvod'), //'清晰度',
            'subtitle'     => '',
            'fields'    => array(
                array(
                    'id'    => 'type',
                    'title' => __('Order', 'mine-cloudvod'), //'记忆播放类型',
                    'type'  => 'select',
                    'options'     => array(
                        'asc'   => __('ASC', 'mine-cloudvod'), //'正序',
                        'desc'    => __('DESC', 'mine-cloudvod'), //'倒序',
                    ),
                    'attributes' => array(
                        'style'    => 'min-width: 100px;'
                    ),
                    'default'     => 'asc',
                ),
            )
        ),
        array(
            'id'        => 'aliplayer_RotateMirror',
            'type'      => 'fieldset',
            'title'     => __('Rotate Mirror', 'mine-cloudvod'), //'旋转镜像',
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
        array(//note
            'id'        => 'aliplayer_Note',
            'type'      => 'fieldset',
            'title'     => __('Note', 'mine-cloudvod') . '<span class="mcv-pro-feature"><span class="plugin-count">Pro</span></span>', //'笔记',
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
        array(//hotkey
            'id'        => 'aliplayer_hotKey',
            'type'      => 'fieldset',
            'title'     => __('Hot Key', 'mine-cloudvod'), //'快捷键',
            'subtitle'     => __('The arrow keys (left and right keys) control fast forward and backward, the direction keys (up and down keys) control the increase or decrease of the volume, and the space bar pauses and plays.', 'mine-cloudvod'),
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
                    'id'          => 'time',
                    'type'        => 'text',
                    'title'       => __('Time in seconds', 'mine-cloudvod'), //'试看时长',
                    'after'       => __('The time length of fast forward and backward, unit: second. The default is 10 seconds.', 'mine-cloudvod'),
                    'default'     => "10",
                    'dependency' => array('status', '==', true),
                ),
            )
        ),
    )
));
