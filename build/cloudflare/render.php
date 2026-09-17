<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
global $ActiveAddons;

$config = $attributes;

// Check if this is an R2 video (oss.key is set)
if( isset( $attributes['oss'] ) && ! empty( $attributes['oss']['key'] ) ){
    // R2 video: get public URL via R2 class
    $bucket = $attributes['oss']['bucket'] ?? '';
    $key    = $attributes['oss']['key'] ?? '';

    if( $bucket && $key && isset( $ActiveAddons['cloudflare'] ) && isset( $ActiveAddons['cloudflare']->r2 ) ){
        $play_url = $ActiveAddons['cloudflare']->r2->get_object_url( $bucket, $key );
        $config['source'] = $play_url;
    }

    unset( $config['oss'] );
    unset( $config['libid'] );
    unset( $config['vid'] );

    $video_safe = do_blocks('<!-- wp:mine-cloudvod/aliplayer '.json_encode($config).' /-->');

    echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义
} elseif( ! empty( $attributes['vid'] ) ){
    // CloudFlare Stream video
    $playinfo = $ActiveAddons['cloudflare']->vod->get_playinfo($attributes['vid']);

    if( is_wp_error( $playinfo ) ) return;
    $config['source'] = $playinfo['playUrl']??'';
    if( !isset( $config['cover'] ) || !$config['cover'] ){
        $config['cover'] = $playinfo['thumbnail']??'';
    }

    unset( $config['oss'] );
    unset( $config['libid'] );
    unset( $config['vid'] );

    $video_safe = do_blocks('<!-- wp:mine-cloudvod/aliplayer '.json_encode($config).' /-->');

    echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义
}
