<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 
/**
 * template name: MCV Favorites
 */

defined( 'ABSPATH' ) || exit;

get_header( 'mcv-lms' );

do_blocks( '<!-- wp:mine-cloudvod/user /-->' );
$fav_safe = do_blocks( '<!-- wp:mine-cloudvod/favorites /-->' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 渲染的可信区块 HTML
echo $fav_safe;

get_footer( 'mcv-lms' );
