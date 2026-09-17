<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

$logo_url_escaped = get_custom_logo();
if( !$logo_url_escaped ){
	if( $icon = get_site_icon_url() ){
		$logo_url_escaped = '<a href="'.esc_url(home_url()).'" class="custom-logo-link" rel="home"><img width="108" height="108" src="'.esc_url($icon).'" class="custom-logo" decoding="async"></a>';
	}
}
global $wp_query;

$all_terms = get_terms( [
    'taxonomy' => 'docs_category',
    'parent' => 0
] );

$current_post = null;
if( $wp_query->is_archive() ){
    $current_term = get_queried_object();
    $current_term_parent = $current_term;

    $current_term_childs = get_terms( [
        'taxonomy' => 'docs_category',
        'parent' => $current_term->term_id
    ] );
}
elseif( $wp_query->is_single() ){
    $current_post = get_queried_object();

    $current_term = get_the_terms( $current_post->ID, 'docs_category' );
    $current_term = $current_term[0];
    $current_term_parent = $current_term;
    if( $current_term->parent > 0 ) {
        $current_term_parent = get_term( $current_term->parent, 'docs_category' );
    }
    $current_term_childs = get_terms( [
        'taxonomy' => 'docs_category',
        'parent' => $current_term->parent > 0 ? $current_term->parent : $current_term->term_id
    ] );
}


?>
<div class="mcv-doc-container">
	<div style="position: sticky;top: 0px;z-index: 1001;background: #ffffff;">
		<div style="position: relative; border-bottom: 1px solid #e5e6eb; padding-right: 30px; padding-left: 30px;">
			<div class="mcv-doc-header">
				<div class="mcv-doc-header-logo">
					<?php echo wp_kses_post( $logo_url_escaped ); ?>
                    <a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a>
				</div>
				<div class="mcv-doc-header-menu">
					<div class="mcv-doc-header-menu-inner">
                        <?php foreach( $all_terms as $at ): ?>
						<div class="mcv-doc-header-menu-item">
                            <a href="<?php echo esc_url(get_term_link($at)); ?>"><?php echo esc_html($at->name); ?></a>
                            <?php if( $current_term->term_id == $at->term_id || $current_term->parent == $at->term_id ): ?>
                            <div class="mcv-doc-header-menu-selected-label"></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
					</div>
				</div>
				<div class="header-search">
					
				</div>
			</div>
		</div>
	</div>
	<div class="mcv-doc-main">
		<div style="position: relative; min-height: 400px;">
			<div class="mcv-doc-nav">
				<div class="mcv-doc-nav-item left-menu">
					<svg fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 48 48" aria-hidden="true" focusable="false" width="14" height="14" class="arco-icon arco-icon-menu-fold" style="margin-right: 6px;"><path d="M42 11H6M42 24H22M42 37H6M13.66 26.912l-4.82-3.118 4.82-3.118v6.236Z"></path></svg>
					目录
				</div>
				<div class="mcv-doc-nav-item right-menu">
					大纲
					<svg fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 48 48" aria-hidden="true" focusable="false" width="14" height="14" class="arco-icon arco-icon-list" style="margin-left: 6px;"><path d="M13 24h30M5 12h4m4 24h30M13 12h30M5 24h4M5 36h4"></path></svg>
				</div>
			</div>
            <div class="mcv-doc-mask"></div>
			<div class="mcv-doc-body">
				<div class="mcv-doc-wrapper">
					<div class="mcv-doc-left">
						<div class="mcv-doc-title"><?php echo esc_html($current_term_parent->name); ?></div>
						<div class="mcv-doc-menu">
                            <div class="mcv-doc-menu-inner">
                                <?php foreach( $current_term_childs as $t ): 
                                    $docs = get_posts( [
                                        'post_type' => 'mcv_docs',
                                        'post_status' => 'publish',
                                        'numberposts' => 999,
                                        'tax_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 按文档分类筛选，业务必需
                                            [
                                                'taxonomy' => 'docs_category',
                                                'field'    => 'term_id',
                                                'terms'    => $t->term_id
                                            ]
                                        ],
                                        'order' => 'ASC',
                                    ] );
                                    $meun_content_escaped = '';
                                    $has_selected = '';
                                    if( is_array( $docs ) ):
                                        if( !$current_post ) $current_post = $docs[0];
                                        foreach( $docs as $doc ): 
                                            $title = $doc->post_title;
                                            $selected = '';
                                            if( $current_post->ID == $doc->ID ){
                                                $selected = ' mcv-menu-selected';
                                                $has_selected = ' mcv-menu-selected';
                                            }
                                            $meun_content_escaped .= mcv_trim( '<div class="mcv-menu-item'. esc_attr($selected) .'">
                                                <span class="mcv-menu-indent"></span>
                                                <span class="mcv-menu-item-inner">
                                                    <a href="'. esc_url(get_permalink($doc->ID)) .'" title="'. esc_html($title) .'">'. esc_html($title) .'</a>
                                                </span>
                                            </div>' );
                                        endforeach;
                                    endif;
                                ?>
                                <div class="mcv-doc-menu-item<?php echo esc_attr($has_selected); ?>">
                                    <div class="mcv-menu-header">
                                        <span><?php echo esc_html($t->name); ?></span>
                                        <span class="mcv-menu-icon-suffix is-open">
                                            <svg fill="none" stroke="currentColor" stroke-width="4" width="14" height="14" viewBox="0 0 48 48" aria-hidden="true" focusable="false" class="arco-icon arco-icon-down"><path d="M39.6 17.443 24.043 33 8.487 17.443"></path></svg>
                                        </span>
                                    </div>
                                    <div class="mcv-menu-content"<?php echo $has_selected?' style="display:block;"':''; ?>>
                                        <?php echo wp_kses_post( $meun_content_escaped ); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
					</div>
					<div class="mcv-docs-content">
						<div class="mcv-docs-content-inner">
                            <div class="mcv-docs-content-main">
                                <h1 class="mcv-doc-h1"><?php echo esc_html(get_the_title( $current_post )); ?></h1>
                                <div class="mcv-doc-divider"></div>
                                <div class="mcv-doc-post-content">
                                    <?php echo wp_kses_post(apply_filters('the_content', get_the_content( null, false, $current_post ))); ?>
                                </div>
                            </div>
						</div>
					</div>
				</div>
				<div class="mcv-doc-anchor">
                    
                </div>
			</div>
		</div>
	</div>
</div>
<div class="mcv-scroll-progress"></div>