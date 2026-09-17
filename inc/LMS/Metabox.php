<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;

class Metabox{
    
    public function __construct() {
        add_action( 'init', [ $this, 'course_access_metabox' ] );
        add_action( 'init', [ $this, 'mcv_lesson_metabox' ] );
        add_action( 'init', [ $this, 'mcv_lesson_type' ] );
    }


    public function course_builder(){
        add_meta_box( 'mine-cloudvod-courses', __( 'Course Builder', 'mine-cloudvod' ), array( $this, 'course_builder_display_callback' ), MINECLOUDVOD_LMS['course_post_type'] );
    }

    public function course_builder_display_callback(){
        echo '<div id="mcv-lms-course-wrap"></div>';
    }


    public function mcv_lesson_metabox(){
        
        $prefix = '_mcv_lms_lesson_attrs';
        \MCSF::createMetabox( $prefix, array(
            'title'     => __('Lesson Attrs', 'mine-cloudvod'),
            'icon'   => 'fas fa-rocket',
            'post_type' => MINECLOUDVOD_LMS['lesson_post_type'],
            'context'            => 'side',
            'class'              => '',
            'priority'      => 'high',
        ) );
        \MCSF::createSection($prefix, array(
            'fields' =>[
                [
                    'id'    => 'preview',
                    'type'  => 'switcher',
                    'title' => __('Preview', 'mine-cloudvod'), //'状态',
                    'text_on'    => __('Enable', 'mine-cloudvod'), //'启用',
                    'text_off'   => __('Disable', 'mine-cloudvod'), //'禁用',
                    'default' => false,
                    'class'     => 'd-flex'
                ],
            ]
        ) );
    }

    public function mcv_lesson_type(){
        $duration = ['minute'=>0,'second'=>0];
        if(isset($_GET['post']) && is_numeric($_GET['post'])){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 后台只读当前文章 ID 加载课时类型
            $attr = get_post_meta( sanitize_text_field(wp_unslash($_GET['post']??0)), '_mcv_lms_lesson_attrs', true ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 后台只读当前文章 ID 加载课时类型
            if(isset($attr['duration'])) $duration = $attr['duration'];
        }
        /**
         * 过虑课时类型
         */
        $mcv_lesson_types = \apply_filters( 'mcv_lesson_types', [ 'text' => _x('Text', 'Lesson type.', 'mine-cloudvod'),'vod' => __('VOD', 'mine-cloudvod'),  ] );
        $mcv_lesson_types_fields = \apply_filters( 'mcv_lesson_types_fields', [
            [
                'id'         => '_lesson_type',
                'type'       => 'radio',
                'inline'  => true,
                'options'    => $mcv_lesson_types,
                'default'    => 'vod'
            ],
            [
                'id'         => '_mcv_lesson_duration',
                'type'       => 'fieldset',
                'title'      => __('Duration', 'mine-cloudvod'),
                'class'  => 'mcv_fieldset',
                'dependency'    => count($mcv_lesson_types)>1?['_lesson_type', '==', 'vod']:'',
                'fields'    => [
                    array(
                        'id'    => 'minute',
                        'type'    => 'number',
                        'unit'    => __('Minute', 'mine-cloudvod'),
                        'default' => $duration['minute'],
                    ),
                    array(
                        'id'    => 'second',
                        'type'    => 'number',
                        'unit'    => __('Second', 'mine-cloudvod'),
                        'default' => $duration['second'],
                    ),
                ]
            ],]);

        $prefix_lessontype = '_mcv_lms_lesson_type';
        \MCSF::createMetabox( $prefix_lessontype, array(
            'title'     => __('Lesson type', 'mine-cloudvod'),
            'icon'   => 'fas fa-rocket',
            'post_type' => MINECLOUDVOD_LMS['lesson_post_type'],
            'context'           => 'side',
            'priority'          => 'high',
            'data_type'         => 'unserialize'
        ) );
        \MCSF::createSection($prefix_lessontype, array(
            'fields' =>$mcv_lesson_types_fields
        ) );
    }


    public function course_access_metabox(){
        $prefix = '_mcv_lms_course_access';
        \MCSF::createMetabox( $prefix, array(
            'title'     => __('Course Management', 'mine-cloudvod'),
            'icon'   => 'fas fa-rocket',
            'post_type' => MINECLOUDVOD_LMS['course_post_type'],
            'data_type'     => 'unserialize',
            'class'              => '',
            'priority'      => 'high',
        ) );
    
        \MCSF::createSection($prefix, array(
            'title'     => __('Course Builder', 'mine-cloudvod'),
            'fields' =>[
                [
                    'type'          => 'content',
                    'content'       => '<div id="mcv-lms-course-wrap"></div>',
                    
                ],
            ]
        ));
        $uc_member_levels = MINECLOUDVOD_SETTINGS['uc_member_levels']??[];
        $levels = [
            'no' => '不免费'
        ];
        if( is_array($uc_member_levels) && count($uc_member_levels)>0 ){
            foreach( $uc_member_levels as $key=>$lvl ){
                $levels[$key] = $lvl['title'];
            }
        }
        \MCSF::createSection($prefix, array(
            'title'     => __('Course Access Setting', 'mine-cloudvod'),
            'fields' =>[
                [
                    'type'          => 'submessage',
                    'style'         => 'warning',
                    'content'       => __('The course is not protected. Any user can access its content without the need to be logged-in or enrolled.', 'mine-cloudvod'),
                    'dependency'    => ['_mcv_access_mode', '==', 'open'],
                ],
                [
                    'type'          => 'submessage',
                    'style'         => 'info',
                    'content'       => __('The course is protected. Registration and enrollment are required in order to access the content.', 'mine-cloudvod'),
                    'dependency'    => ['_mcv_access_mode', '==', 'free'],
                ],
                [
                    'type'          => 'submessage',
                    'style'         => 'success',
                    'content'       => __('Users need to purchase the course (one-time fee) in order to gain access.', 'mine-cloudvod'),
                    'dependency'    => ['_mcv_access_mode', '==', 'buynow'],
                ],
                [
                    'id'         => '_mcv_access_mode',
                    'type'       => 'radio',
                    'title'      => __('Access Mode', 'mine-cloudvod'),
                    'inline'  => true,
                    'options'    => MINECLOUDVOD_LMS['access_mode'],
                    'default'    => 'open'
                ],
                [
                    'id'       => '_mcv_course_price',
                    'type'     => 'text',
                    'title'    => __('Course Price', 'mine-cloudvod'),
                    'dependency'    => ['_mcv_access_mode', '==', 'buynow'],
                ],
                [
                    'id'       => '_mcv_member_levels',
                    'type'     => 'radio',
                    'title'    => '会员免费',
                    'dependency'    => ['_mcv_access_mode', '==', 'buynow'],
                    'options'    => $levels,
                    'inline'  => true,
                    'default' => 'no',
                ],
                [
                    'id'       => '_mcv_course_period',
                    'type'     => 'radio',
                    'title'    => __('Validity period', 'mine-cloudvod'),
                    'dependency'    => ['_mcv_access_mode', 'any', 'free,buynow'],
                    'options'    => [
                        'forever' => __('Forever', 'mine-cloudvod'),
                        'custom' => __('Custom', 'mine-cloudvod'),
                    ],
                    'inline'  => true,
                    'default' => 'forever',
                ],
                [
                    'id'       => '_mcv_course_period_custom',
                    'type'     => 'select',
                    'title'    => __('Validity period after enrolled', 'mine-cloudvod'),
                    'options'  => [
                        '1'     => __('One month', 'mine-cloudvod'),
                        '2'     => __('Two months', 'mine-cloudvod'),
                        '3'     => __('Three months', 'mine-cloudvod'),
                        '6'     => __('Half a year', 'mine-cloudvod'),
                        '12'    => __('One year', 'mine-cloudvod'),
                        '24'    => __('Two years', 'mine-cloudvod'),
                        '36'    => __('Three years', 'mine-cloudvod'),
                        '48'    => __('Four years', 'mine-cloudvod'),
                        '60'    => __('Five years', 'mine-cloudvod'),
                    ],
                    'dependency'    => ['_mcv_access_mode|_mcv_course_period', 'any|==', 'free,buynow|custom'],
                    'default'       => '60',
                ],
                [
                    'id'       => '_mcv_course_update_status',
                    'type'     => 'text',
                    'title'    => __('Course Status', 'mine-cloudvod'),
                    'after'    => __('Status of Course Updates', 'mine-cloudvod'),
                ],
                [
                    'id'       => '_mcv_course_difficulty',
                    'type'     => 'select',
                    'title'    => __('Course Difficulty', 'mine-cloudvod'),
                    'options'  => MINECLOUDVOD_LMS['course_difficulty'],
                ],
                [
                    'id'       => '_mcv_course_virtual_number',
                    'type'     => 'number',
                    'title'    => __('Number of virtual students', 'mine-cloudvod'),
                    'default'  => 0,
                ],
                [
                    'id'       => '_mcv_course_no_type',
                    'type'     => 'switcher',
                    'title' => __('Course catalog number', 'mine-cloudvod'),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => true,
                ],
                [
                    'id'       => '_mcv_course_catelog',
                    'type'     => 'switcher',
                    'title' => __('Show course catalog after login', 'mine-cloudvod'),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => false,
                ],
                [
                    'id'       => '_mcv_course_catelog_enroll',
                    'type'     => 'switcher',
                    'title' => __('Show course catalog after enrolled', 'mine-cloudvod'),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => false,
                    'dependency'    => ['_mcv_course_catelog', '==', '1'],
                    'after' => __('After enabled, users need to purchase the course before they can see the catalog.', 'mine-cloudvod')
                ],
                [
                    'id'        => 'qqgrouptips',
                    'type'      => 'text',
                    'title'     => 'QQ群号提示信息',
                    'default'   => '加入QQ群可获得更多福利',
                ],
                [
                    'id'        => 'qqgroup',
                    'type'      => 'text',
                    'title'     => 'QQ群号',
                    'before'    => '为空则不显示',
                    'default'   => '',
                ],
            ]
        ));
        \MCSF::createSection($prefix, array(
            'title'     => __('Course Students', 'mine-cloudvod'),
            'fields' =>[
                [
                    'type'          => 'content',
                    'content'       => '<div id="mcv-lms-course-users"></div>',
                ],
            ]
        ));

        do_action( 'mcv_lms_course_access_after', $prefix );
    }
}