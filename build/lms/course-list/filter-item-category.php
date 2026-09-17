<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * 顶级分类过滤
 */

$args = $wp_query->query_vars;
$parent = 0;
$curCat = null;

if( isset( $args['course-category'] ) && $args['course-category'] ):
    $curCat = get_term_by( 'slug', $args['course-category'], 'course-category' );
// 父分类
$parents = get_ancestors($curCat->term_id, 'course-category');
$parents = array_reverse( $parents );
foreach( $parents as $p ){
    $term = get_term_by('id', $p, 'course-category');
    $pcats = get_terms( [
        'taxonomy' => 'course-category',
        'parent' => $term->parent,
    ] );
?>
<div class="selector-line">
    <div class="selector-title"><?php echo esc_html($term->parent==0?'分类：':''); ?></div>
    <div class="selector-main" style="height:auto">
        <div class="kc-tag-group">
            <a href="<?php echo esc_url(mcv_lms_list_url( 'course-category', $term->parent==0?'':get_term_by('id', $term->parent, 'course-category')->slug )); ?>" class="kc-tag">全部</a>
            <?php 
                foreach( $pcats as $category ):
                    $is_active = '';
                    if( $category->term_id == $p ){
                        $is_active = ' is-active';
                    }
            ?>
                <a href="<?php echo esc_url(mcv_lms_list_url( 'course-category', $category->slug )); ?>" class="kc-tag<?php echo esc_attr($is_active); ?>"><?php echo esc_html($category->name); ?></a>
            <?php
                endforeach;
            ?>
        </div>
        <div class="selector-aside"></div>
    </div>
</div>
<?php
}
$parent = $curCat->parent??0;
endif;
// 当前分类 
$categories = get_terms( [
    'taxonomy' => 'course-category',
    'parent' => $parent,
] );
// 下级分类
$categories2 = false;
do_action( 'mcv_before_filter_item_category', $categories );
?>

<div class="selector-line">
    <div class="selector-title"><?php echo esc_html($parent==0?'分类：':''); ?></div>
    <div class="selector-main" style="height:auto">
        <div class="kc-tag-group">
                <a href="<?php echo esc_url(mcv_lms_list_url( 'course-category', $parent==0?'':get_term_by('id', $parent, 'course-category')->slug )); ?>" class="kc-tag <?php echo esc_attr( !isset($args['course-category']) || (isset($args['course-category']) && $args['course-category'] == '')?'is-active':'' );?>">全部</a>
            <?php 
            if( is_array( $categories ) ): 
                foreach( $categories as $category ):
                    $is_active = '';
                    if( $category->slug == ( $args['course-category']??'' ) ){
                        $is_active = ' is-active';
                        // 下级分类
                        $categories2 = get_terms( [
                            'taxonomy' => 'course-category',
                            'parent' => $category->term_id,
                        ] );
                    }
            ?>
                <a href="<?php echo esc_url(mcv_lms_list_url( 'course-category', $category->slug )); ?>" class="kc-tag<?php echo esc_attr($is_active); ?>"><?php echo esc_html($category->name); ?></a>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <div class="selector-aside"></div>
    </div>
</div>

<?php
// 下级分类
if( $categories2 && is_array( $categories2 ) ):
?>
<div class="selector-line">
    <div class="selector-title"></div>
    <div class="selector-main" style="height:auto">
        <div class="kc-tag-group">
                <a href="<?php echo esc_url(mcv_lms_list_url( 'course-category', $curCat->slug )); ?>" class="kc-tag <?php echo esc_attr($curCat->parent == $parent?'is-active':'' );?>">全部</a>
            <?php 
                foreach( $categories2 as $category ):
                    $is_active = '';
                    if( $category->slug == ( $args['course-category']??'' ) ){
                        $is_active = ' is-active';
                    }
            ?>
                <a href="<?php echo esc_url(mcv_lms_list_url( 'course-category', $category->slug )); ?>" class="kc-tag<?php echo esc_attr($is_active); ?>"><?php echo esc_html($category->name); ?></a>
            <?php
                endforeach;
            ?>
        </div>
        <div class="selector-aside"></div>
    </div>
</div>

<?php
endif;
?>

<?php
do_action( 'mcv_after_filter_item_category', $categories );
?>