<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
global $ActiveAddons;
if( !isset( $attributes['videoId'] ) ) return;
$config = $attributes;

$playinfo = $ActiveAddons['huawei']->vod->get_playinfo($attributes['videoId']);

if( is_wp_error( $playinfo ) ) return;
$config['source'] = $playinfo['playUrl']??'';
if( !isset( $config['cover'] ) || !$config['cover'] ){
    $config['cover'] = $playinfo['thumbnail']??'';
}

unset( $config['oss'] );
unset( $config['videoId'] );

$video_safe = do_blocks('<!-- wp:mine-cloudvod/aliplayer '.json_encode($config).' /-->');

echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义