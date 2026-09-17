<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;

class Options{
    
    public function __construct() {
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'course_admin_options' ) );

        add_filter( 'mcv_lms_course_base_slug', [$this, 'course_base_slug'] );
        add_filter( 'mcv_lms_lesson_base_slug', [$this, 'lesson_base_slug'] );
    }

    public function course_base_slug( $slug ){
        if(MINECLOUDVOD_SETTINGS['mcv_lms_general']['course_slug'] ?? false){
            return MINECLOUDVOD_SETTINGS['mcv_lms_general']['course_slug'];
        }
        return $slug;
    }
    public function lesson_base_slug( $slug ){
        if(MINECLOUDVOD_SETTINGS['mcv_lms_general']['lesson_slug'] ?? false){
            return MINECLOUDVOD_SETTINGS['mcv_lms_general']['lesson_slug'];
        }
        return $slug;
    }

    public function course_admin_options(){
        $prefix = 'mcv_settings';
        $parent = 'mcv_lms';
        \MCSF::createSection( $prefix, array(
            'id'      => $parent,
            'title'       => __('Mine LMS', 'mine-cloudvod'),
            'icon'        => 'fas fa-graduation-cap',
            'description' => ''
        ));

        \MCSF::createSection( $prefix, array(
            'parent'      => $parent,
            'title'       => __('General settings', 'mine-cloudvod'),
            'icon'        => 'fas fa-book-reader',
            'description' => '',
            'fields'      => array(
                array(
                    'id'        => 'mcv_lms_general',
                    'type'      => 'fieldset',
                    'fields'    => array(
                        array(
                            'id'            => 'template',
                            'title'         => __('Active Template', 'mine-cloudvod'),
                            'type'          => 'select',
                            /**
                             * 过滤器 mcv_lms_templates 
                             */
                            'options'       =>  apply_filters('mcv_lms_templates', [
                                                    // 'default' => __('Default', 'mine-cloudvod'),
                                                    'ketang' => __('KeTang', 'mine-cloudvod'),
                                                ]),
                            'attributes' => array(
                              'style'    => 'min-width: 100px;'
                            ),
                            'default'     => 'ketang',
                        ),
                        [
                            'id'            => 'slot_time',
                            'title'         => '分时段',
                            'type'          => 'switcher',
                            'dependency' => [ 'template', '==', 'ketang' ],
                            'text_on'       => __('Enable', 'mine-cloudvod'),
                            'text_off'      => __('Disable', 'mine-cloudvod'),
                            'default'       => false,
                        ],
                        array(
                            'id'        => 'ketang',
                            'type'      => 'group',
                            'title'     => 'ketang模板样式',
                            'before'     => '分时段禁用时，排在第一的为启用方案;分时段启用时，请将启用时段从小到大排列，否则将无法正常显示',
                            'dependency' => [ 'template', '==', 'ketang' ],
                            'min'       => 2,
                            'fields'    => array(
                                [
                                    'id'            => 'title',
                                    'title'         => '方案标题',
                                    'type'          => 'text',
                                ],
                                [
                                    'id'         => 'ehour',
                                    'type'       => 'number',
                                    'title'      => '启用时段',
                                    'min'        => 0,
                                    'max'        => 23,
                                ],
                                [
                                    'id'            => 'bgColor',
                                    'title'         => '背景颜色',
                                    'type'          => 'color',
                                    'before'        => '亮色： #ffffff, 暗色： #14171a'
                                ],
                                [
                                    'id'            => 'fontColor',
                                    'title'         => '字体颜色',
                                    'type'          => 'color',
                                    'before'        => '亮色： #14171a, 暗色： #ffffff'
                                ],
                                [
                                    'id'            => 'mainColor1',
                                    'title'         => '主色1',
                                    'after'         => '价格，开始按钮',
                                    'type'          => 'color',
                                    'before'        => '亮色： #ff7a38, 暗色： #ff7a38'
                                ],
                                [
                                    'id'            => 'mainColor2',
                                    'title'         => '主色2',
                                    'after'         => '选项卡，box-shadow',
                                    'type'          => 'color',
                                    'before'        => '亮色： #2080f7, 暗色： #2080f7'
                                ],
                                [
                                    'id'            => 'secondColor1',
                                    'title'         => '配色1',
                                    'after'         => '目录底线，课时类型图标',
                                    'type'          => 'color',
                                    'before'        => '亮色： #c9d0d6, 暗色： #dddddd'
                                ],
                                [
                                    'id'            => 'secondColor2',
                                    'title'         => '配色2',
                                    'after'         => '论评',
                                    'type'          => 'color',
                                    'before'        => '亮色： #586470, 暗色： #586470'
                                ],
                                [
                                    'id'            => 'secondColor3',
                                    'title'         => '配色3',
                                    'after'         => '试一下',
                                    'type'          => 'color',
                                    'before'        => '亮色： #666c80, 暗色： #666c80'
                                ],
                                [
                                    'id'            => 'secondColor4',
                                    'title'         => '配色4',
                                    'after'         => '目录字体颜色, 推荐课程标题',
                                    'type'          => 'color',
                                    'before'        => '亮色： #3e454d, 暗色： #bbbbbb'
                                ],
                                [
                                    'id'            => 'secondColor5',
                                    'title'         => '配色5',
                                    'after'         => '目录背景，评论按钮边框',
                                    'type'          => 'color',
                                    'before'        => '亮色： #f5f8fa, 暗色： #000000'
                                ],
                            ),
                            'default'   => array(
                                array(
                                        'title' => '亮色',
                                        'ehour' => '6',
                                        'bgColor' => '#ffffff',
                                        'fontColor' => '#14171a',
                                        'mainColor1' => '#ff7a38',
                                        'mainColor2' => '#2080f7',
                                        'secondColor1' => '#c9d0d6',
                                        'secondColor2' => '#586470',
                                        'secondColor3' => '#666c80',
                                        'secondColor4' => '#3e454d',
                                        'secondColor5' => '#f5f8fa',
                                ),
                                array(
                                        'title' => '暗色',
                                        'ehour' => '18',
                                        'bgColor' => '#14171a',
                                        'fontColor' => '#ffffff',
                                        'mainColor1' => '#ff7a38',
                                        'mainColor2' => '#2080f7',
                                        'secondColor1' => '#dddddd',
                                        'secondColor2' => '#586470',
                                        'secondColor3' => '#666c80',
                                        'secondColor4' => '#bbbbbb',
                                        'secondColor5' => '#000000',
                                ),
                            ),
                        ),
                        array(
                            'type'    => 'submessage',
                            'style'   => 'success',
                            'content' => __('After change the slug, please enter the Setting - Permalink, and just click the save button, it will take effect.', 'mine-cloudvod'),
                        ),
                        array(
                            'id'            => 'course_slug',
                            'title'         => __('Course Slug', 'mine-cloudvod'),
                            'type'          => 'text',
                            'default'     => MINECLOUDVOD_LMS['course_post_type'],
                        ),
                        array(
                            'id'            => 'lesson_slug',
                            'title'         => __('Lesson Slug', 'mine-cloudvod'),
                            'type'          => 'text',
                            'default'     => MINECLOUDVOD_LMS['lesson_post_type'],
                        ),
                        array(
                            'id'            => 'course_permalink',
                            'title'         => __('Course Permalink', 'mine-cloudvod'),
                            'type'          => 'select',
                            'options'       => [
                                'postname'  => '/'.(MINECLOUDVOD_SETTINGS['mcv_lms_general']['course_slug']??MINECLOUDVOD_LMS['course_post_type']).'/%postname%/',
                                'postid'  => '/'.(MINECLOUDVOD_SETTINGS['mcv_lms_general']['course_slug']??MINECLOUDVOD_LMS['course_post_type']).'/%post_id%/',
                            ],
                            'default'     => 'postname',
                        ),
                        array(
                            'id'            => 'lesson_permalink',
                            'title'         => __('Lesson Permalink', 'mine-cloudvod'),
                            'type'          => 'select',
                            'options'       => [
                                'postname'  => '/'.(MINECLOUDVOD_SETTINGS['mcv_lms_general']['course_slug']??MINECLOUDVOD_LMS['course_post_type']).'/%postname%/'.(MINECLOUDVOD_SETTINGS['mcv_lms_general']['lesson_slug']??MINECLOUDVOD_LMS['lesson_post_type']).'/%postname%/',
                                'postid'  => '/'.(MINECLOUDVOD_SETTINGS['mcv_lms_general']['lesson_slug']??MINECLOUDVOD_LMS['lesson_post_type']).'/%post_id%/',
                            ],
                            'default'     => 'postname',
                        ),
                        array(
                            'id'            => 'wide_size',
                            'title'         => __('LMS Width', 'mine-cloudvod'),
                            'type'          => 'text',
                            'default'       => '1200px',
                        ),
                        array(
                            'id'            => 'user_courses',
                            'title'         => __("User's Courses", 'mine-cloudvod'),
                            'type'          => 'select',
                            'options'       => 'pages',
                            'query_args'    => ['posts_per_page' => -1,],
                            'default'     => mine_get_page_id_by_slug( 'mcv-my-courses' ),
                            'after'         => 'Shortcode: [mcv_user_courses]'
                        ),
                        array(
                            'id'            => 'order_list',
                            'title'         => __("MineCloudVoD Order List", 'mine-cloudvod'),
                            'type'          => 'select',
                            'options'       => 'pages',
                            'query_args'    => ['posts_per_page' => -1,],
                            'default'     => mine_get_page_id_by_slug( 'mcv-order-list' ),
                            'after'         => 'Shortcode: [mcv_order_list]'
                        ),
                        array(
                            'id'            => 'fav_courses',
                            'title'         => __("Favorite Courses", 'mine-cloudvod'),
                            'type'          => 'select',
                            'options'       => 'pages',
                            'query_args'    => ['posts_per_page' => -1,],
                            'default'     => mine_get_page_id_by_slug( 'mcv-favorites' ),
                            'after'         => 'Shortcode: [mcv_course_favorites]'
                        ),
                        [
                            'id'        => 'kefuqr',
                            'title'     => '客服二维码',
                            'type'  => 'upload',
                            'library' => 'image',
                            'preview' => true,
                            'default'   => '',
                        ],
                        [
                            'id'        => 'kefutips',
                            'type'      => 'text',
                            'before'     => '客服二维码下方提示文字',
                            'title'     => '客服二维码提示',
                            'default'   => '',
                        ],
                        [
                            'id'        => 'mpqr',
                            'title'     => '公众号二维码',
                            'type'  => 'upload',
                            'library' => 'image',
                            'preview' => true,
                            'default'   => '',
                        ],
                        [
                            'id'        => 'mptips',
                            'type'      => 'text',
                            'before'     => '公众号二维码下方提示文字',
                            'title'     => '公众号二维码提示',
                            'default'   => '',
                        ],
                    ),
                ),
            )
        ));

        \MCSF::createSection( $prefix, [
            'parent'      => $parent,
            'title'       => __('Course', 'mine-cloudvod'),
            'icon'        => 'fas fa-book-reader',
            'description' => '',
            'fields'      => [
                [
                    'id'        => 'mcv_lms_course',
                    'type'      => 'fieldset',
                    'fields'    => [
                        [
                            'id'            => 'admin_enrolled',
                            'title'         => '管理员无需购买',
                            'type'          => 'switcher',
                            'text_on'       => __('Enable', 'mine-cloudvod'),
                            'text_off'      => __('Disable', 'mine-cloudvod'),
                            'default'       => true,
                        ],
                        [
                            'id'        => 'details',
                            'title'     => __('Details', 'mine-cloudvod'),
                            'type'      => 'fieldset',
                            'fields'    => [
                                [
                                    'id'            => 'lesson_num',
                                    'title'         => __('Number of Lesson', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Show', 'mine-cloudvod'),
                                    'text_off'      => __('Hide', 'mine-cloudvod'),
                                    'default'       => true,
                                ],
                                [
                                    'id'            => 'hours',
                                    'title'         => __('Hours of Course', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Show', 'mine-cloudvod'),
                                    'text_off'      => __('Hide', 'mine-cloudvod'),
                                    'default'       => true,
                                ],
                                [
                                    'id'            => 'student_num',
                                    'title'         => __('Number of Students', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Show', 'mine-cloudvod'),
                                    'text_off'      => __('Hide', 'mine-cloudvod'),
                                    'default'       => true,
                                ],
                                [
                                    'id'            => 'update',
                                    'title'         => __('Date Updated', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Show', 'mine-cloudvod'),
                                    'text_off'      => __('Hide', 'mine-cloudvod'),
                                    'default'       => true,
                                ],
                                [
                                    'id'            => 'difficulty',
                                    'title'         => __('Course Difficulty', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Show', 'mine-cloudvod'),
                                    'text_off'      => __('Hide', 'mine-cloudvod'),
                                    'default'       => true,
                                ],
                                [
                                    'id'            => 'status',
                                    'title'         => __('Course Status', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Show', 'mine-cloudvod'),
                                    'text_off'      => __('Hide', 'mine-cloudvod'),
                                    'default'       => true,
                                ],
                            ]
                        ],
                        [
                            'id'            => 'pagenum',
                            'title'         => __('Courses Per Page', 'mine-cloudvod'),
                            'type'          => 'text',
                            'default'       => 16,
                        ],
                        [
                            'id'            => 'mobile_style',
                            'type'      => 'image_select',
                            'title'     => __('Mobile List Style', 'mine-cloudvod'),
                            'options'   => array(
                                '1' => MINECLOUDVOD_URL . '/static/img/style-1.png',
                                '2' => MINECLOUDVOD_URL . '/static/img/style-2.png',
                            ),
                            'default'   => '1'
                        ],
                        [
                            'id'            => 'course_style',
                            'type'      => 'button_set',
                            'title'     => __('Course Page Style', 'mine-cloudvod'),
                            'options'   => array(
                                '' => __('Left and right', 'mine-cloudvod'),
                                'column' => __('Up and down', 'mine-cloudvod'),
                            ),
                            'default'   => ''
                        ],
                        [
                            'id'            => 'lesson_style',
                            'type'      => 'button_set',
                            'title'     => __('Mobile Lesson Page Style', 'mine-cloudvod'),
                            'options'   => array(
                                '1' => __('Left and right', 'mine-cloudvod'),
                                '2' => __('Up and down', 'mine-cloudvod'),
                            ),
                            'default'   => '2'
                        ],
                        [
                            'id'            => 'filter',
                            'title'         => __('Course Filter', 'mine-cloudvod'),
                            'type'          => 'fieldset',
                            'fields'        =>[
                                [
                                    'id'            => 'status',
                                    'title'         => __('Status', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Enable', 'mine-cloudvod'),
                                    'text_off'      => __('Disable', 'mine-cloudvod'),
                                    'default'       => true,
                                ],
                                [
                                    'id'            => 'items',
                                    'type'          => 'select',
                                    'title'         => __( 'Filter with', 'mine-cloudvod' ),
                                    'chosen'        => true,
                                    'multiple'      => true,
                                    'sortable'      => true,
                                    'options'       => apply_filters( 'mcv_course_filters', [
                                        'category'  => __( 'Category', 'mine-cloudvod' ),
                                        'tag'       => __( 'Tag', 'mine-cloudvod' ),
                                        'difficulty'=> __( 'Difficulty', 'mine-cloudvod' ),
                                        'mode'      => __( 'Access Mode', 'mine-cloudvod' ),
                                    ]),
                                    'default'    => array( 'category' ),
                                    'dependency' => [ 'status', '==', true ]
                                ],
                            ]
                        ],
                        [
                            'id'            => 'watermark',
                            'title'         => __('Watermark', 'mine-cloudvod'),
                            'type'          => 'fieldset',
                            'fields'        =>[
                                [
                                    'id'            => 'status',
                                    'title'         => __('Status', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Enable', 'mine-cloudvod'),
                                    'text_off'      => __('Disable', 'mine-cloudvod'),
                                    'default'       => true,
                                ],
                                [
                                    'id'            => 'area',
                                    'title'         => '显示区域',
                                    'type'          => 'button_set',
                                    'options'       => [
                                        '1'         => '全屏',
                                        '2'         => '主区域',
                                    ],
                                    'default'       => 1,
                                ],
                                [
                                    'id'            => 'text',
                                    'type'          => 'text',
                                    'title'         => __( 'Text', 'mine-cloudvod' ),
                                    'before'       => __('Can be dynamically replaced with these labels: {username} {userid} {userip} {useremail} {usernickname}', 'mine-cloudvod'),
                                    'default'    => get_bloginfo('name'),
                                    'dependency' => [ 'status', '==', true ]
                                ],
                            ]
                        ],
                        [
                            'id'            => 'showCatelogInDetail',
                            'title'         => __('Show Catelog In Detail', 'mine-cloudvod'),
                            'type'          => 'switcher',
                            'text_on'       => __('Show', 'mine-cloudvod'),
                            'text_off'      => __('Hide', 'mine-cloudvod'),
                            'default'       => false,
                        ],
                        [
                            'id'            => 'disableSeek',
                            'title'         => __('Fast-forward disabled for VOD lessons', 'mine-cloudvod'),
                            'type'          => 'switcher',
                            'text_on'       => __('Enable', 'mine-cloudvod'),
                            'text_off'      => __('Disable', 'mine-cloudvod'),
                            'default'       => false,
                        ],
                        [
                            'id'            => 'backend',
                            'title'         => __('Backend options', 'mine-cloudvod'),
                            'type'          => 'fieldset',
                            'fields'        =>[
                                [
                                    'id'            => 'player',
                                    'title'         => __('Default player of lesson import', 'mine-cloudvod'),
                                    'type'          => 'radio',
                                    'options'       => [
                                        '1' => 'Aliplayer',
                                        '2' => 'Dplayer',
                                    ],
                                    'default'       => '1',
                                    'inline'        => true,
                                ],
                            ]
                        ],
                        [
                            'id'        => 'archive',
                            'title'     => __('Archive Meta', 'mine-cloudvod'),
                            'type'      => 'fieldset',
                            'fields'    => [
                                [
                                    'id'            => 'notitle',
                                    'title'         => __('Add title tag', 'mine-cloudvod'),
                                    'desc'          => __('Enable it, if the archive page has no title.', 'mine-cloudvod'),
                                    'type'          => 'switcher',
                                    'text_on'       => __('Enable', 'mine-cloudvod'),
                                    'text_off'      => __('Disable', 'mine-cloudvod'),
                                    'default'       => false,
                                ],
                                [
                                    'id'            => 'title',
                                    'title'         => __('Title', 'mine-cloudvod'),
                                    'type'          => 'text',
                                    'desc'       => __('Courses Title', 'mine-cloudvod'),
                                ],
                                [
                                    'id'            => 'keywords',
                                    'title'         => __('Keywords', 'mine-cloudvod'),
                                    'type'          => 'text',
                                    'desc'       => __('Courses Keywords', 'mine-cloudvod'),
                                ],
                                [
                                    'id'            => 'desc',
                                    'title'         => __('Description', 'mine-cloudvod'),
                                    'type'          => 'text',
                                    'desc'       => __('Courses Description', 'mine-cloudvod'),
                                ],
                            ]
                        ],
                    ],
                ],
            ]
        ]);

        do_action('mcv_lms_admin_options', $prefix, $parent);
    }
}