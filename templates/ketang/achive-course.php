<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 
/**
 * template name: Mine Course Page
 */

defined( 'ABSPATH' ) || exit;

get_header( 'mcv-lms' );

$archie_safe = do_blocks( '<!-- wp:mine-cloudvod/course-list {"title":"全部课程","template":"archive-mcv_course"} /-->' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 渲染的可信区块 HTML
echo $archie_safe;

get_footer( 'mcv-lms' );
