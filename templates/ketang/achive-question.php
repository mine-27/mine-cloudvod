<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 
/**
 * template name: Mine Question Page
 */

defined( 'ABSPATH' ) || exit;

get_header( 'mcv-lms' );

do_blocks( '<!-- wp:mine-cloudvod/user /-->' );
$archie_safe = mcv_execute_registered_pattern('mine-cloudvod/question-bank-list');
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 渲染的可信区块 HTML
echo $archie_safe;

get_footer( 'mcv-lms' );
