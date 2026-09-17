<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

$attrs = $attributes;
global $ActiveAddons;

if( isset( $ActiveAddons['qiniukodo'] ) && is_object( $ActiveAddons['qiniukodo'] ) ){
    if( !isset($attrs['key']) ) return;
    $geturl = $ActiveAddons['qiniukodo']->call_url( $attrs['key'] );
    
    if( $geturl['status'] == '1' ){
        $attrs['source'] = $geturl['data'];
        unset( $attrs['key'] );
        $video_safe = do_blocks('<!-- wp:mine-cloudvod/dplayer '.json_encode($attrs).' /-->');
        
        echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义
    }
}