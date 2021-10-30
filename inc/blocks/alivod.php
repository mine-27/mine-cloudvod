<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function mine_cloudvod_aliyunvod_assets() {
	global $wp_version;
	wp_register_style(
		'mine_cloudvod-aliyunvod-style-css',
		MINECLOUDVOD_URL.'/dist/blocks.style.build.css', 
		is_admin() ? array( 'wp-editor' ) : null,
		MINECLOUDVOD_VERSION
	);

	wp_register_script(
		'mine_cloudvod-aliyunvod-block-js',
		MINECLOUDVOD_URL.'/dist/blocks.build.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
		MINECLOUDVOD_VERSION,
		true
	);

	wp_register_script(
		'mine_cloudvod-integrations-elementor-js',
		MINECLOUDVOD_URL.'/dist/elementor.build.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
		MINECLOUDVOD_VERSION,
		true
	);

	wp_register_style(
		'mine_cloudvod-aliyunvod-block-editor-css',
		MINECLOUDVOD_URL.'/dist/blocks.editor.build.css',
		array( 'wp-edit-blocks' ),
		MINECLOUDVOD_VERSION
	);
	register_block_type(
		'mine-cloudvod/aliyun-vod', array(
			//'style'         => 'mine_cloudvod-aliyunvod-style-css',
			'editor_script' => 'mine_cloudvod-aliyunvod-block-js',
			'editor_style'  => 'mine_cloudvod-aliyunvod-block-editor-css',
		)
	);
	$filter = 'block_categories';
	if (version_compare($wp_version, '5.8', ">=")) {
		$filter = 'block_categories_all';
	}
	add_filter( $filter, function( $categories ) {
		$categories = array_merge(
			array(
				array(
					'slug'  => 'mine',
					'title' => __('Mine', 'mine-cloudvod'),
				),
			),
			$categories
		);
		return $categories;
	} );
	wp_set_script_translations( 'mine_cloudvod-aliyunvod-block-js', 'mine-cloudvod', MINECLOUDVOD_PATH . '/languages' );
}
add_action( 'init', 'mine_cloudvod_aliyunvod_assets' );

