<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 
defined( 'ABSPATH' ) || exit;

get_header( 'mcv-lms' );

$course_safe = do_blocks('<!-- wp:mine-cloudvod/course-single /-->');
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 渲染的可信区块 HTML
echo $course_safe;

get_footer( 'mcv-lms' );
