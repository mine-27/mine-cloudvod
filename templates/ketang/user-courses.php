<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 
/**
 * template name: User's Courses Page
 */

defined( 'ABSPATH' ) || exit;

get_header( 'mcv-lms' );

do_blocks( '<!-- wp:mine-cloudvod/user /-->' );
$uc_safe = do_blocks( '<!-- wp:mine-cloudvod/user-courses /-->' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 渲染的可信区块 HTML
echo $uc_safe;

get_footer( 'mcv-lms' );
