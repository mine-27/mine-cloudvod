<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

if ( ! defined( 'ABSPATH' ) ) exit;
$modes = MINECLOUDVOD_LMS['access_mode'];
$args = $wp_query->query_vars;
do_action( 'mcv_before_filter_item_mode', $modes );
?>

<div class="selector-line">
    <div class="selector-title"><?php echo esc_html__('Access Mode', 'mine-cloudvod'); ?> : </div>
    <div class="selector-main" style="height:auto">
        <div class="kc-tag-group">
            <a href="<?php echo esc_url(mcv_lms_list_url( 'mcv-mod', '' )); ?>" class="kc-tag <?php echo esc_attr( !isset($args['mcv-mod']) || $args['mcv-mod'] == '' ?' is-active' : '' );?>">全部</a>
            <?php 
            if( is_array( $modes ) ): 
                foreach( $modes as $key => $value ):
                    $is_active = '';
                    if( $key == ( $args['mcv-mod']??'' ) ) $is_active = ' is-active';
            ?>
                <a href="<?php echo esc_url(mcv_lms_list_url( 'mcv-mod', $key )); ?>" class="kc-tag<?php echo esc_attr($is_active); ?>"><?php echo esc_html($value); ?></a>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <div class="selector-aside"></div>
    </div>
</div>

<?php
do_action( 'mcv_after_filter_item_mode', $modes );
?>