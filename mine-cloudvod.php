<?php
/**
 * Plugin Name: Mine CloudVod 云点播
 * Plugin URI:  https://www.zwtt8.com/mine-cloudvod/
 * Description: 将视频直传到阿里云视频点播、OSS、腾讯云点播、COS，嵌入WP中播放，配置简单，使用方便，支持gutenberg编辑器，兼容微信小程序。
 * Version: 1.2.16
 * Author: mine27
 * Author URI: https://www.zwtt8.com/
 * Text Domain: mine-cloudvod
 * Domain Path: /languages/
 */
if(!defined('ABSPATH'))exit;


define('MINECLOUDVOD_VERSION', '1.2.16');
define('MINECLOUDVOD_URL', plugins_url('', __FILE__));
define('MINECLOUDVOD_PATH', dirname(__FILE__));
define('MINECLOUDVOD_SETTINGS', get_option('mcv_settings'));
define('MINECLOUDVOD_ALIYUNVOD_ENDPOINT', array(
    'cn-beijing'        => __('China(Beijing)', 'mine-cloudvod'),//'华北2（北京）',
    'cn-zhangjiakou'    => __('China(Zhangjiakou)', 'mine-cloudvod'),//'华北3（张家口）',
    'cn-hangzhou'       => __('China(Hangzhou)', 'mine-cloudvod'),//'华东1（杭州）',
    'cn-shanghai'       => __('China(Shanghai)', 'mine-cloudvod'),//'华东2（上海）',
    'cn-shenzhen'       => __('China(Shenzhen)', 'mine-cloudvod'),//'华南1（深圳）',
    'cn-hongkong'       => __('China(Hongkong)', 'mine-cloudvod'),//'香港',
    'ap-northeast'      => __('Janpan(Tokyo)', 'mine-cloudvod'),//'日本（东京）',
    'ap-southeast-1'    => __('Singapore', 'mine-cloudvod'),//'新加坡',
    'ap-southeast-5'    => __('Indonesia(Jakarta)', 'mine-cloudvod'),//'印度尼西亚（雅加达）',
    'us-west-1'         => __('USA (Silicon Valley)', 'mine-cloudvod'),//'美国（硅谷）',
    'eu-west-1'         => __('U.K (London)', 'mine-cloudvod'),//'英国（伦敦）',
    'eu-central-1'      => __('Germany(Frankfurt)', 'mine-cloudvod'),//'德国（法兰克福）',
    'ap-south-1'        => __('India(Mumbai)', 'mine-cloudvod'),//'印度（孟买）'
));
define('MINECLOUDVOD_ALIPLAYER', array(
    'css' => 'https://g.alicdn.com/de/prismplayer/2.9.12/skins/default/aliplayer-min.css',
    'js'  => 'https://g.alicdn.com/de/prismplayer/2.9.12/aliplayer-min.js',
    'anti'  => 'https://g.alicdn.com/de/prismplayer/2.9.12/hls/aliplayer-vod-anti-min.js'//防调试代码
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

require_once MINECLOUDVOD_PATH.'/autoload.php';
require_once MINECLOUDVOD_PATH.'/csf/csf.php';
require_once MINECLOUDVOD_PATH.'/inc/options.php';
require_once MINECLOUDVOD_PATH.'/inc/functions.php';
require_once MINECLOUDVOD_PATH.'/inc/blocks/alivod.php';

if (class_exists('MineCloudvod\\MineCloudVod')) {
    new MineCloudvod\Integrations\Tutor\Tutor();
    new MineCloudvod\Integrations\B_2\B_2();
    new MineCloudvod\Integrations\Elementor\Elementor();
    new MineCloudvod\Integrations\Ri\RiProV2();

    new MineCloudvod\Ability\PostType();
    new MineCloudvod\Ability\Shortcode();
    new MineCloudvod\Ability\Plugin();
    new MineCloudvod\Ability\EmbedVideo();
    new MineCloudvod\Ability\Ajax();
    new MineCloudvod\Ability\PlayList();

    new MineCloudvod\RestApi\AliyunVod();
    new MineCloudvod\RestApi\AliyunOss();
    new MineCloudvod\RestApi\QcloudVod();
    new MineCloudvod\RestApi\QcloudCos();
    
	$mineCloudVod = new MineCloudvod\MineCloudVod();
    //媒体库上传到云点播开关
    if(!isset(MINECLOUDVOD_SETTINGS['mcv_media2vod']) || MINECLOUDVOD_SETTINGS['mcv_media2vod']){
        add_action('wp_handle_upload', array($mineCloudVod, 'wpHandleUpload'), 9, 2);
    }
	
	add_filter('wp_generate_attachment_metadata', array($mineCloudVod, 'wpGenerateAttachmentMetadata'), 10, 1);
	add_action('delete_attachment', array($mineCloudVod, 'deleteAttachment'), 10, 2);
    add_action('wp_ajax_mcv_asyc_transcode', array($mineCloudVod, 'mcv_asyc_transcode'));
    add_action('wp_ajax_mcv_asyc_plyrconfig', array($mineCloudVod, 'mcv_asyc_plyrconfig'));
    add_action('wp_ajax_mcv_sync_endtime', array($mineCloudVod, 'mcv_sync_endtime'));
    add_action('wp_ajax_mcv_buytimebug', array($mineCloudVod, 'mcv_buytimebug'));
    add_action('wp_ajax_mcv_uploadsign', array($mineCloudVod, 'mcv_uploadsign'));
    add_action('wp_ajax_mcv_tcvod_uploaded', array($mineCloudVod, 'mcv_tcvod_uploaded'));
    //video block
    add_filter('render_block_data', array($mineCloudVod, 'mcv_render_block_data'), 6, 2);
    //video short code
    add_filter('wp_video_shortcode_override', array($mineCloudVod, 'mcv_video_shortcode'), 4, 4);
    add_action('admin_menu', function(){
            add_submenu_page('mine-cloudvod',  __('CloudVod Hub', 'mine-cloudvod'), __('CloudVod Hub', 'mine-cloudvod'), 'publish_posts', 'edit.php?post_type=mcv_video');
    });
    
    add_filter('wp_get_attachment_url', 'mcv_vod_unique', 10, 1);
    add_action('admin_action_mcv_tcvod_url', 'mcv_tcvod_url');
    add_action('admin_enqueue_scripts',	function(){
        global $current_user;
        $uid = $current_user->ID;
        wp_enqueue_script('mcv_aliplayer', 'https://g.alicdn.com/de/prismplayer/2.9.6/aliplayer-min.js',  array(), MINECLOUDVOD_VERSION , true);
	    wp_enqueue_script('mcv_aliplayer_components', MINECLOUDVOD_URL.'/static/aliyun/aliplayercomponents-1.0.6.min.js',  array('mcv_aliplayer'), MINECLOUDVOD_VERSION , false );
        wp_enqueue_script('mcv_alivod_sdk', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/aliyun-upload-sdk-1.5.0.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_enqueue_script('mcv_alivod_es6-promise', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/lib/es6-promise.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_enqueue_script('mcv_alivod_oss', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/lib/aliyun-oss-sdk-5.3.1.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_add_inline_script('mcv_alivod_sdk','var mcv_alivod_config={endpoint:"'.MINECLOUDVOD_SETTINGS['alivod']['endpoint'].'",userId:"'.MINECLOUDVOD_SETTINGS['alivod']['userId'].'",nonce:"'.wp_create_nonce('mcv-aliyunvod-'.$uid).'"};var mcv_aliplayer_config={slide:'.(!empty(MINECLOUDVOD_SETTINGS['aliplayer_slide']['status'])?'true':'false').'};var mcv_nonce={ajaxUrl:"'.admin_url("admin-ajax.php").'",et:"'.wp_create_nonce('mcv_sync_endtime').'",endtime:'.strtotime(MINECLOUDVOD_SETTINGS['endtime']).', buynow:"'.admin_url('/admin.php?page=mine-cloudvod#tab='.str_replace(' ', '-', strtolower(urlencode(__('Purchase time', 'mine-cloudvod'))))).'"};');
        
        wp_enqueue_style('mine_tcplayer_css', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/tcplayer.min.css', array(), false);
        wp_enqueue_script('mine_tcplayerhls', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/libs/hls.min.0.13.2m.js',  array(), MINECLOUDVOD_VERSION , true );
        wp_enqueue_script('mine_tcplayer', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/tcplayer.v4.2.1.min.js',  array(), MINECLOUDVOD_VERSION , true );
        wp_add_inline_script('mine_tcplayer','var mcv_tcvod_config={appID:"'.MINECLOUDVOD_SETTINGS['tcvod']['appid'].'",key:"'.MINECLOUDVOD_SETTINGS['tcvod']['fdlkey'].'",pcfg:"'.MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig'].'",nonce:"'.wp_create_nonce('mcv-aliyunvod-'.$uid).'"};');
    });

    add_action('admin_enqueue_scripts', 'mcv_admin_scripts');
    add_action('wp_ajax_mcv_alivod_upload', array($mineCloudVod, 'mcv_alivod_upload'));
    add_action('wp_ajax_nopriv_mcv_alivod_upload', array($mineCloudVod, 'mcv_alivod_upload'));
    add_action('admin_action_mcv_alivod_url', array($mineCloudVod, 'mcv_alivod_url'));
    add_action('wp_ajax_mcv_asyc_ali_transcode', array($mineCloudVod, 'mcv_asyc_ali_transcode'));

    
    add_filter('wp_prepare_attachment_for_js', 'wpPrepareAttachmentForJs', 10, 3);
}