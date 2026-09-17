<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

$src = $attributes['src']??'';
$width = $attributes['width']??'100%';
$height = $attributes['height']??'500px';
$danmaku = $attributes['danmaku']??false;
$type = $attributes['type']??'unknown';
if(!$danmaku && $type == 'bilibili'){
    $src .= '&danmaku=0';
}
// sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts allow-popups"
$video = '<iframe src="'.esc_url($src).'" width="'.esc_attr($width).'" height="'.esc_attr($height).'" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"  id="mcv_embed_iframe" style="max-width:100% !important;" class="is-'.esc_attr($type).'" sandbox="allow-top-navigation allow-same-origin allow-scripts "></iframe>';

$video_escaped = apply_filters('mcv_filter_embedvideo', $video, $src, $width, $height);

echo $video_escaped; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iframe 无法用 wp_kses 包裹，属性已 esc_url/esc_attr 转义