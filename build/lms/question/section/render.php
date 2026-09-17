<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
/**
 * 递归渲染嵌套子文章
 * @param int $parent_id 父文章ID
 * @param array $attributes 区块属性（包含最大深度、每页数量等）
 * @param array $inner_blocks 用户自定义的子文章模板
 */
if(  !function_exists('render_nested_posts') ){
    function render_nested_posts($parent_id, $inner_blocks) {
        $query = new WP_Query([
            'post_type'      => 'section',
            'posts_per_page' => 9999,
            'post_parent'    => $parent_id, // 关键：只查询指定父ID的子文章
            'post_status'    => 'publish',
            'order'          => 'ASC',
            'orderby'        => 'menu_order' // 按菜单顺序排序（可自定义）
        ]);
        if (!$query->have_posts()) {
            return; // 没有子文章则返回
        }

        while ($query->have_posts()) {
            $query->the_post();
            global $post;
            $inner_blocks_new = find_question_section($inner_blocks,$post->ID);
            
            echo do_blocks( serialize_blocks($inner_blocks_new) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义
            // echo '</article>';
        }
        // echo '</div>';

        wp_reset_postdata();
    }
    function find_question_section($inner_blocks, $parent_id){
        $new_blocks = [];
        foreach ($inner_blocks as $inner_block) {
            // 关键点：如果子块是嵌套循环自身，传递当前文章ID作为下一级的父ID
            if ($inner_block['blockName'] === 'mine-cloudvod/question-section') {
                $inner_block['attrs']['parentPostId'] = $parent_id;
            }
            if( !empty($inner_block['innerBlocks']) ){
                $inner_block['innerBlocks'] = find_question_section($inner_block['innerBlocks'], $parent_id);
            }
            $new_blocks[] = $inner_block;
        }
        return $new_blocks;
    }
}

wp_enqueue_style( 'wp-element' );
wp_enqueue_style( 'wp-components' );

$is_login = is_user_logged_in();
if( !$is_login ) {
    $viewDependencies = include( MINECLOUDVOD_PATH.'/build/lms/user/view.asset.php' );
    foreach($viewDependencies['dependencies'] as $dpc){
        wp_enqueue_style( $dpc );
    }
    wp_enqueue_script( 'mcv_layer' );
    wp_enqueue_style( 'mine-cloudvod-user-style' );
    wp_enqueue_script( 'mine-cloudvod-user-script' );
}
$inner_blocks = $block->parsed_block['innerBlocks'];
$current_post_id = get_the_ID();
$isChapter = $attributes['isChapter'] ?? false;
if($isChapter){
    $parent_id = $attributes['parentPostId'] ?? 0;
    if( !$parent_id ){
        $chapter = get_posts([
            'post_type'      => 'section',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 按章节标记筛选，业务必需
                [
                    'key'     => '_mcv_is_chapter',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
            'post_parent'    => $current_post_id,
            'order'          => 'ASC',
            'orderby'        => 'menu_order'
        ]);
        if( isset($chapter[0]) ){
            $parent_id = $chapter[0]->ID;
        }   
    }

    // 开始递归渲染
    render_nested_posts($parent_id, $inner_blocks);
}
else{
    $bank_id = get_the_ID();

    $prompt = get_post_meta( $bank_id, '_mcv_prompt', true );
    if( !$prompt ){
        $prompt = MINECLOUDVOD_SETTINGS['question_base']['prompt']??'';
    }
    if( $prompt ){
        $prompt = wpautop($prompt);
    }
    wp_localize_script( 'mine-cloudvod-question-section-view-script', 'mcv_question_info', [
        'post_id' => $bank_id, 
        'is_enrolled' => mcv_lms_is_enrolled( $bank_id ),
        'purchase_url' => mcv_checkout_url([ 'type' => 'question', 'id' => $bank_id]),
        'prompt' => $prompt,
    ] );
    $query = new WP_Query([
        'post_type'      => 'section',
        'numberposts' => 99,
        'post_status'    => 'publish',
        // 'meta_query'     => [
        //     [
        //         'key'     => '_mcv_is_chapter',
        //         'value'   => '0',
        //         'compare' => '=',
        //     ],
        // ],
        'post_parent'    => $current_post_id,
        'order'          => 'ASC',
        'orderby'        => 'menu_order',
        'suppress_filters' => false,
    ]);
    if (!$query->have_posts()) {
        return;
    }

    while ($query->have_posts()) {
        $query->the_post();
        global $post;
        $_mcv_is_chapter = get_post_meta($post->ID, '_mcv_is_chapter', true);
        if( !$_mcv_is_chapter ) $_mcv_is_chapter = 0;
        echo '<div class="mcv-question-section-outer'.($is_login ? '' : ' mcv-login').'" data-is-chapter="'.esc_attr($_mcv_is_chapter).'" data-id="'.esc_attr($post->ID).'">';
        echo do_blocks( serialize_blocks($inner_blocks) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 输出已由区块渲染管线转义
        echo '</div>';
    }

    wp_reset_postdata();
}
