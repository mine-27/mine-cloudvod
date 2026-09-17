<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
$title          = $attributes['title']??'';
$morelink       = $attributes['morelink'] ?? '';
$moretext       = $attributes['moretext'] ?? '';
$categories     = $attributes['categories'] ?? false;
$tags           = $attributes['tags'] ?? false;
$rows           = (int)($attributes['rows'] ?? 1);
$columns        = (int)($attributes['columns'] ?? 4);
$template       = $attributes['template'] ?? false;
$className       = $attributes['className'] ?? '';

wp_enqueue_style( 'mine-cloudvod-course-list-editor-style' );
$style_color = mcv_get_style_color();
wp_add_inline_style( 'mine-cloudvod-course-list-editor-style', $style_color );

if( $template ){
    global $wp_query, $paged;
    if( !$wp_query->is_archive() ){
        $wp_query = new WP_Query([
            'post_type' => MINECLOUDVOD_LMS['course_post_type'],
            'posts_per_page' => MINECLOUDVOD_SETTINGS['mcv_lms_course']['pagenum']??10,
            'paged' => $paged,
        ]);
    }
    $args = $wp_query->query_vars;
    $cd = MINECLOUDVOD_SETTINGS['mcv_lms_course']??[];
    $vips = MINECLOUDVOD_SETTINGS['uc_member_levels']??[];
    ?>
    <?php if( $cd['filter']['status']??false ) : ?>
    <div class="mcv-block-selector mcv-block-wrap">
        <div class="mcv-block">
            <div class="block-container">
                <div class="selector">
                    <div class="selector-main" style="height:auto">
                        <div class="kc-tag-group" style="width: 100%;margin: 10px;justify-content:center;">
                            <form method="get" action="<?php echo esc_url(mcv_lms_list_url()); ?>" class="mcv-search-form">
                                <?php 
                                if( is_array($_GET) ){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读回填搜索/筛选参数
                                    foreach( wp_unslash($_GET) as $k=>$v ){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读回填搜索/筛选参数
                                        if( $k == 'paged' ) continue;
                                        if( $k == 'mcv-s' ) continue;
                                        echo '<input name="'. esc_attr($k) .'" type="hidden" value="'. esc_attr($v) .'" />';
                                    }
                                }
                                ?>
                                <input name="mcv-s" class="mcv-search" placeholder="<?php echo esc_html__('Search Courses', 'mine-cloudvod'); ?>" value="<?php echo isset($wp_query->query_vars['mcv-s'])?esc_attr($wp_query->query_vars['mcv-s']):''; ?>">
                                <input type="submit" class="mcv-search-btn" value="<?php echo esc_html__('Search', 'mine-cloudvod'); ?>" />
                            </form>
                        </div>
                        <div class="selector-aside"></div>
                    </div>
                    <?php 
                    if( is_array( $cd['filter']['items'] ) ): 
                        foreach( $cd['filter']['items'] as $item ):
                            if( file_exists( MINECLOUDVOD_PATH . '/build/lms/course-list/filter-item-' . $item . '.php' ) )
                                include( MINECLOUDVOD_PATH . '/build/lms/course-list/filter-item-' . $item . '.php' );
                            else do_action('mcv_course_filter_handler', $item);
                        endforeach;
                    endif; 
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="mcv-block-wrap">
    <div class="mcv-block pb-32">
        <div class="block-container">
            <div class="result">
            <a href="<?php echo esc_url(mcv_lms_list_url( 'mcv-vip', '' )); ?>"><h2 class="result__title"><?php echo esc_html($title); ?></h2></a>
                <?php if( is_array($vips) && count($vips) ): ?>
                <div class="vip-filters">
                    <?php foreach( $vips as $l=>$vip ){
                        echo '<a href="'.esc_url(mcv_lms_list_url( 'mcv-vip', 'vip_'.$l )).'" class="'.(isset($args['mcv-vip'])&&$args['mcv-vip']=='vip_'.$l?'active':'').'">'.esc_html($vip['title']).'免费</a>';
                    } ?>
                </div>
                <?php endif; ?>
                <span class="result__desc">
                    <em class="result__count"><?php echo esc_html($wp_query->found_posts); ?></em> <?php echo esc_html__('Courses', 'mine-cloudvod'); ?>
                </span>
            </div>
            <div class="course-list col<?php echo esc_attr($columns);?> style-<?php echo esc_attr($cd['mobile_style']??'1');?>">
            <?php 
            if( $wp_query->have_posts() ): 
            while($wp_query->have_posts()) :
                $wp_query->the_post();
                global $post;
                mcv_lms_loop_course( $post, $cd['mobile_style']??'1' );
            endwhile;
            endif; 
            ?>
            </div>
            <?php
            $max_num_pages = $wp_query->max_num_pages;
            if( $max_num_pages > 1 ) :
            $paged = $paged ?: 1;
            ?>
            <ul class="rc-pagination pager course-list-pagination" unselectable="unselectable">
            <?php 
            //上一页
            if( $paged <= 1 || $paged > $max_num_pages ):?>
                <li title="上一页" class="rc-pagination-prev rc-pagination-disabled" aria-disabled="true">
                    <span disabled="" class="ke-icon">
                        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.364 20.435a1 1 0 0 1-1.414 0L8 15.485A4 4 0 0 1 8 9.83l4.95-4.95a1 1 0 1 1 1.414 1.414l-4.95 4.95a2 2 0 0 0 0 2.828l4.95 4.95a1 1 0 0 1 0 1.414Z" fill="currentColor"></path>
                        </svg>
                    </span>
                </li>
            <?php
            elseif( $paged <= $max_num_pages):?>
                <li title="上一页" class="rc-pagination-prev" onclick="location.href='<?php echo esc_url(get_pagenum_link( $paged - 1 ));?>'">
                    <span disabled="" class="ke-icon">
                        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.364 20.435a1 1 0 0 1-1.414 0L8 15.485A4 4 0 0 1 8 9.83l4.95-4.95a1 1 0 1 1 1.414 1.414l-4.95 4.95a2 2 0 0 0 0 2.828l4.95 4.95a1 1 0 0 1 0 1.414Z" fill="currentColor"></path>
                        </svg>
                    </span>
                </li>
            <?php
            endif;
            //数字页码
            for( $pi = 1; $pi <= $max_num_pages; $pi++ ){
                $cbtn = '';
                if( $pi == $paged ) $cbtn = ' rc-pagination-item-active';
            ?>
                <li title="<?php echo esc_attr($pi);?>" class="rc-pagination-item rc-pagination-item-1<?php echo esc_attr($cbtn);?>" tabindex="0" onclick="location.href='<?php echo esc_url(get_pagenum_link( $pi ));?>'">
                    <a rel="nofollow"><?php echo esc_html($pi);?></a>
                </li>
            <?php
            }
            //下一页
            if( $paged >= $max_num_pages ):
            ?>
                <li title="下一页" tabindex="0" class="rc-pagination-next  rc-pagination-disabled">
                    <span class="ke-icon">
                        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M9.636 20.435a1 1 0 0 0 1.414 0l4.95-4.95a4 4 0 0 0 0-5.656l-4.95-4.95a1 1 0 0 0-1.414 1.414l4.95 4.95a2 2 0 0 1 0 2.828l-4.95 4.95a1 1 0 0 0 0 1.414Z" fill="currentColor"></path>
                        </svg>
                    </span>
                </li>
            <?php
            elseif( $paged < $max_num_pages):
                ?>
                    <li title="下一页" tabindex="0" class="rc-pagination-next" onclick="location.href='<?php echo esc_url(get_pagenum_link( $paged + 1 ));?>'">
                        <span class="ke-icon">
                            <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M9.636 20.435a1 1 0 0 0 1.414 0l4.95-4.95a4 4 0 0 0 0-5.656l-4.95-4.95a1 1 0 0 0-1.414 1.414l4.95 4.95a2 2 0 0 1 0 2.828l-4.95 4.95a1 1 0 0 0 0 1.414Z" fill="currentColor"></path>
                            </svg>
                        </span>
                    </li>
                <?php
            endif;
            ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <?php
}
else{

    $qargs = [
        'post_type'     => MINECLOUDVOD_LMS['course_post_type'],
        'post_status'   => 'publish',
        'order'         => 'ASC',
        'orderby'       => 'menu_order',
        'showposts'     => $rows * $columns,
    ];
    if( $categories ){
        $qargs[ 'tax_query' ][] = [
            'taxonomy' => 'course-category',
            'field'    => 'term_id',
            'terms'    => $categories
        ];
    }
    if( $tags ){
        // $qargs['course-tag'] = $tags;
        $qargs[ 'tax_query' ][] = [
            'taxonomy' => 'course-tag',
            'field'    => 'term_id',
            'terms'    => $tags
        ];
    }
    $courses = get_posts( $qargs );
    ?>
    
    <div class="mcv-block <?php echo esc_attr($className); ?>">
        <div class="block-container">
            <?php if( $title ): ?>
            <div class="result">
                <h2 class="result__title"><?php echo esc_html($title); ?></h1>
                <?php if( !$template && $moretext && $morelink ): ?>
                <a href="<?php echo esc_url($morelink); ?>" class="result__desc"><?php echo esc_html($moretext); ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="course-list col<?php echo esc_attr($columns);?> style-<?php echo esc_attr($cd['mobile_style']??'1');?>">
            <?php 
            if( $courses && count($courses) > 0 ): 
            foreach( $courses as $course ) :
                mcv_lms_loop_course( $course, $cd['mobile_style']??'1' );
            endforeach;
            endif; 
            ?>
        </div>
        </div>
    </div>
    <?php
}