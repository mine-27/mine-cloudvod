<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="X-UA-Compatible" content="ie=edge,chrome=1"> 
    <meta name="renderer" content="webkit"> 
    <meta http-equiv="x-dns-prefetch-control" content="on"> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,maximum-scale=1,minimum-scale=1"> 
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php 
$docs_safe = do_blocks( '<!-- wp:mine-cloudvod/docs /-->' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 渲染的可信区块 HTML
echo $docs_safe;
?>
<?php wp_footer();?>
	</body>
</html>