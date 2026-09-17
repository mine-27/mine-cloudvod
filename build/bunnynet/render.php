<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
global $ActiveAddons;

$config = $attributes;

$playinfo = $ActiveAddons['bunnynet']->vod->get_playinfo($attributes['libid'], $attributes['vid']);
if( is_wp_error( $playinfo ) ) return;
$config['source'] = $playinfo['videoPlaylistUrl'];
if( !isset( $config['cover'] ) || !$config['cover'] ){
    $config['cover'] = $playinfo['thumbnailUrl'];
}

unset( $config['oss'] );
unset( $config['libid'] );
unset( $config['vid'] );

$video_safe = do_blocks('<!-- wp:mine-cloudvod/dplayer '.json_encode($config).' /-->');

echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义