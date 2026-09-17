<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

if ( ! defined( 'ABSPATH' ) ) exit;
$difficulties = MINECLOUDVOD_LMS['course_difficulty'];
$args = $wp_query->query_vars;
do_action( 'mcv_before_filter_item_difficulty', $difficulties );
?>

<div class="selector-line">
    <div class="selector-title"><?php echo esc_html__( 'Difficulty', 'mine-cloudvod' ); ?> : </div>
    <div class="selector-main" style="height:auto">
        <div class="kc-tag-group">
                <a href="<?php echo esc_url(mcv_lms_list_url( 'mcv-lvl', '' )); ?>" class="kc-tag<?php echo esc_attr( !isset($args['mcv-lvl']) || $args['mcv-lvl'] == '' ?' is-active' : '' );?>">全部</a>
            <?php 
            if( is_array( $difficulties ) ): 
                foreach( $difficulties as $key => $value ):
                    $is_active = '';
                    if( $key == ( $args['mcv-lvl']??'' ) ) $is_active = ' is-active';
            ?>
                <a href="<?php echo esc_url(mcv_lms_list_url( 'mcv-lvl', $key )); ?>" class="kc-tag<?php echo esc_attr($is_active); ?>"><?php echo esc_html($value); ?></a>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <div class="selector-aside"></div>
    </div>
</div>

<?php
do_action( 'mcv_after_filter_item_difficulty', $difficulties );
?>