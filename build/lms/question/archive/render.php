<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

wp_enqueue_style( 'wp-element' );
wp_enqueue_style( 'wp-components' );

$question_category = get_terms( 'question_category' );
$inner_blocks = $block->parsed_block['innerBlocks'];

if( is_array($question_category) && count($question_category) > 0 ){
    foreach ( $question_category as $category ) {
        $term_id = $category->term_id;
        add_filter( 'render_block_context', function($context, $parsed_block) use ( $term_id ) {
            $context['mine-cloudvod/termId'] = $term_id;
            return $context;
        }, 10, 2 );
        echo do_blocks( serialize_blocks($inner_blocks) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义
        remove_filter( 'render_block_context', '__return_null', 10 );
    }
    
}
