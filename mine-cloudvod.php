<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

/**
 * Plugin Name: Mine CloudVod
 * Plugin URI:  https://www.mine27.cn/
 * Description: Mine CloudVod is an audio and video player, which can play videos from local and cloud. And it is also a complete learning management system, which can help you create an online education website very conveniently.
 * Version: 2.6.2
 * Author: mine27
 * Author URI: https://www.mine27.cn/
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mine-cloudvod
 * Domain Path: /languages/
 */
defined( 'ABSPATH' ) || exit;
define('MINECLOUDVOD_VERSION', '2.6.2');
define('MINECLOUDVOD_PATH', dirname(__FILE__));

require MINECLOUDVOD_PATH.'/inc/constants.php';
require MINECLOUDVOD_PATH.'/autoload.php';
require MINECLOUDVOD_PATH.'/inc/functions.php';
require MINECLOUDVOD_PATH.'/inc/functions-lms.php';

#[AllowDynamicProperties]
class MCVClasses{
    public $Dplayer = null
    , $Aliplayer = null
    , $Tcplayer = null
    , $Audioplayer = null
    , $Playlist = null
    , $Dogecloud = null
    , $Alivod = null
    , $Alilive = null
    , $Tcvod = null
    , $Tccos = null
    , $Alioss = null
    , $Addons = null;
    public function __construct(){
        
        new MineCloudvod\Assets();
        new MineCloudvod\MineCloudVod();
        new MineCloudvod\Admin();
        $this->Addons = new MineCloudvod\Addons();
        new MineCloudvod\RestApi\LMS\Addons();

        if( MINECLOUDVOD_SETTINGS['players']['aliplayer'] ?? true ){
            $this->Aliplayer    = new MineCloudvod\Aliyun\Aliplayer();
        }
        if( MINECLOUDVOD_SETTINGS['players']['dplayer'] ?? true ){
            $this->Dplayer      = new MineCloudvod\Blocks\Dplayer();
        }
        // if( MINECLOUDVOD_SETTINGS['players']['playlist'] ?? true ){
        //     $this->Playlist     = new MineCloudvod\Blocks\PlayList();
        // }
        if( MINECLOUDVOD_SETTINGS['players']['aplayer'] ?? true ){
            $this->Audioplayer  = new MineCloudvod\Blocks\AudioPlayer();
        }
        if( MINECLOUDVOD_SETTINGS['players']['embed'] ?? true ){
            new MineCloudvod\Blocks\EmbedVideo();
        }
        new MineCloudvod\Ability\PostType();
        new MineCloudvod\Ability\Note();
        new MineCloudvod\Ability\Shortcode();
        new MineCloudvod\Ability\Plugin();
        new MineCloudvod\Ability\Ajax();
        new MineCloudvod\Ability\ClassicEditor();
        new MineCloudvod\Ability\Filters();

        new MineCloudvod\RestApi\PostTypeVideo();
        
        if( !isset( MINECLOUDVOD_SETTINGS['mcv_lms']['status'] ) || MINECLOUDVOD_SETTINGS['mcv_lms']['status'] ){
            new MineCloudvod\LMS\Init();
            // Agent REST 端点：供 AI Agent 通过 Application Passwords 发布课程/章节/课时
            // 独立 namespace mine-cloudvod/agent/v1，不影响现有 v1 端点
            new MineCloudvod\RestApi\Agent\Init();
        }
        new \MineCloudvod\Payment\Options();
        include MINECLOUDVOD_PATH.'/csf/csf.php';
    }
}
global $mcv_classes, $McvApi;
$mcv_classes = null;
$McvApi = new MineCloudvod\MineCloudVodAPI();
if(!$mcv_classes) $mcv_classes = new MCVClasses();