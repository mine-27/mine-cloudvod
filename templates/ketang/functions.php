<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

defined( 'ABSPATH' ) || exit;

add_action( 'init','mcv_ketang_assets_register' );
function mcv_ketang_assets_register(){
    wp_register_style(
        'mcv_lms_ketang_lesson_css',
        MINECLOUDVOD_URL.'/templates/ketang/build/lesson/style-single.css', 
        is_admin() ? array( 'wp-editor' ) : null,
        MINECLOUDVOD_VERSION
    );

    wp_register_script(
        'mcv_lms_ketang_lesson_js',
        MINECLOUDVOD_URL.'/templates/ketang/build/lesson/single.js',
        array( 'jquery','wp-element','wp-api-fetch','wp-components' ),
        MINECLOUDVOD_VERSION,
        true
    );
    wp_set_script_translations( 'mcv_lms_ketang_lesson_js', 'mine-cloudvod', MINECLOUDVOD_PATH . '/languages/' );

    wp_register_style(
        'mcv_lms_ketang_course_css',
        MINECLOUDVOD_URL.'/templates/ketang/build/course/style-course.css', 
        null,
        MINECLOUDVOD_VERSION
    );

    wp_register_script(
        'mcv_lms_ketang_course_js',
        MINECLOUDVOD_URL.'/templates/ketang/build/course/course.js',
        array( 'jquery','wp-element','wp-components' ),
        MINECLOUDVOD_VERSION,
        true
    );
    wp_set_script_translations( 'mcv_lms_ketang_course_js', 'mine-cloudvod', MINECLOUDVOD_PATH . '/languages/' );

    wp_register_style(
        'mcv_lms_ketang_archive_css',
        MINECLOUDVOD_URL.'/templates/ketang/build/archive/style-index.css', 
        null,
        MINECLOUDVOD_VERSION
    );

    wp_register_script(
        'mcv_lms_ketang_archive_js',
        MINECLOUDVOD_URL.'/templates/ketang/build/archive/index.js',
        array( 'jquery' ),
        MINECLOUDVOD_VERSION,
        true
    );
    wp_set_script_translations( 'mcv_lms_ketang_archive_js', 'mine-cloudvod', MINECLOUDVOD_PATH . '/languages/' );

    wp_register_style(
        'mcv_lms_checkout_css',
        MINECLOUDVOD_URL.'/templates/ketang/build/checkout/style-index.css', 
        null,
        MINECLOUDVOD_VERSION
    );

    wp_register_script(
        'mcv_lms_checkout_js',
        MINECLOUDVOD_URL.'/templates/ketang/build/checkout/index.js',
        array( 'wp-api-fetch', 'wp-dom-ready', 'wp-element', 'mcv_layer' ),
        MINECLOUDVOD_VERSION,
        true
    );
    wp_set_script_translations( 'mcv_lms_checkout_js', 'mine-cloudvod', MINECLOUDVOD_PATH . '/languages/' );

    wp_register_style(
        'mcv_lms_order_list_css',
        MINECLOUDVOD_URL.'/templates/ketang/build/order/list.css', 
        null,
        MINECLOUDVOD_VERSION
    );
}


// add_action( 'wp_print_scripts', 'dequeue_all_scripts' );
function dequeue_all_scripts(){
    if( mcv_current_theme_is_fse_theme() ) return ;
    if (is_singular(MINECLOUDVOD_LMS['lesson_post_type'])){
        global $wp_scripts;
        $scripts = $wp_scripts->registered;
        foreach ( $scripts as $script ){
            if( strpos( $script->src, 'wp-content/themes' ) >= 0 &&  $script->handle != 'jquery' &&  $script->handle != 'mcv_lms_ketang_lesson_js' ){
                wp_dequeue_script($script->handle);
            }
        }
    }
}