<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
global $ActiveAddons;

$config = $attributes;
if( !isset( $attributes['videoId'] ) || !$attributes['videoId'] ) return;
$playinfo = $ActiveAddons['baidu']->vod->get_playinfo($attributes['videoId']);

if( is_wp_error( $playinfo ) ) return;
$config['source'] = $playinfo['playUrl']??'';
if( !isset( $config['cover'] ) || !$config['cover'] ){
    $config['cover'] = $playinfo['thumbnail']??'';
}

unset( $config['oss'] );
unset( $config['videoId'] );
$token = $ActiveAddons['baidu']->vod->generateVodToken($attributes['videoId']);

$hlsConfig = mcv_trim('xhrSetup:function(xhr, url){
    if(url.includes("baidubce")&&url.includes("tokenVideoKey")) {
        xhr.open("GET", "'. rest_url( '/mine-cloudvod/v1/baidu/vod/decrypt?vid='.$attributes['videoId'] ) .'", true);
    }
    else{
        xhr.open("GET", url, true);
    }
},');
$config['hlsConfig'] = $hlsConfig;

$video_safe = do_blocks('<!-- wp:mine-cloudvod/dplayer '.json_encode($config).' /-->');

echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义
