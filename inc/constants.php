<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

if(!defined('ABSPATH'))exit;

define('MINECLOUDVOD_SETTINGS', get_option('mcv_settings'));
if( isset(MINECLOUDVOD_SETTINGS['cdntype']) && MINECLOUDVOD_SETTINGS['cdntype'] == 'customize'){
    define('MINECLOUDVOD_URL', str_replace('{version}', MINECLOUDVOD_VERSION,MINECLOUDVOD_SETTINGS['cdnprefix']));
}
else{
    define('MINECLOUDVOD_URL', plugins_url('', dirname(__FILE__)));
}
define('MINECLOUDVOD_ALIPLAYER', array(
    'js'  => MINECLOUDVOD_URL.'/static/aliyun/aliplayer-min.js',
    'v'  => '2.27.0'//防调试代码
));

define('MINECLOUDVOD_TCVOD_ENDPOINT', array(
    'ap-beijing' => '华北地区(北京)',
    'ap-chengdu' => '西南地区(成都)',
    'ap-chongqing' => '西南地区(重庆)',
    'ap-guangzhou' => '华南地区(广州)',
    'ap-hongkong' => '港澳台地区(中国香港)',
    'ap-shanghai' => '华东地区(上海)',
    'ap-shanghai-fsi' => '华东地区(上海金融)',
    'ap-shenzhen-fsi' => '华南地区(深圳金融)',
    'ap-bangkok' => '亚太东南(曼谷)',
    'ap-mumbai' => '亚太南部(孟买)',
    'ap-seoul' => '亚太东北(首尔)',
    'ap-singapore' => '亚太东南(新加坡)',
    'ap-tokyo' => '亚太东北(东京)',
    'eu-frankfurt' => '欧洲地区(法兰克福)',
    'eu-moscow' => '欧洲地区(莫斯科)',
    'na-ashburn' => '美国东部(弗吉尼亚)',
    'na-siliconvalley' => '美国西部(硅谷)',
    'na-toronto' => '北美地区(多伦多)'
));

define('MINECLOUDVOD_LMS', [
    'course_post_type'      => 'mcv_course',
    'lesson_post_type'      => 'mcv_lesson',
    'order_post_type'       => 'mcv_order',
    'active_template'       => MINECLOUDVOD_SETTINGS['mcv_lms_general']['template'] ?? 'ketang',
    'course_difficulty'     => apply_filters( 'mcv_course_difficulty', [
        '1'      => '初级',//__("Beginner", "mine-cloudvod"),
        '2'      => '中级',//__("Intermediate", "mine-cloudvod"),
        '3'      => '高级',//__("Expert", "mine-cloudvod"),
    ] ),
    'access_mode'     => [
        'open'      => '公开',//__("Open", "mine-cloudvod"),
        'free'      => '免费',//__("Free", "mine-cloudvod"),
        'buynow'    => '付费',//__("Buy Now", "mine-cloudvod"),
        // 'closed'    => __("Closed", "mine-cloudvod"),
    ]
]);