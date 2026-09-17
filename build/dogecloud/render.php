<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

$doge_attrs = $attributes;
$doge_attrs['minecloudvod']['doge'] = [
    'vcode' => $attributes['vcode'],
    'userId' => $attributes['userId'],
];
unset( $doge_attrs['vcode'] );
unset( $doge_attrs['userId'] );

$video_safe = do_blocks('<!-- wp:mine-cloudvod/dplayer '.json_encode($doge_attrs).' /-->');

echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义