<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\LMS;
defined( 'ABSPATH' ) || exit;
    
class Template extends Base{

    public function __construct() {
        parent::__construct();
        $this->init();
    }

    public function init(){
        if( mcv_current_theme_is_fse_theme() ){

        }
        else{
            add_filter( 'template_include', array( $this, 'load_lms_template' ), 99 );
        }
    }
    
    public function load_lms_template( $template ){
        global $wp_query, $current_user;

        $post_type          = get_query_var( 'post_type' );
        $course_category    = get_query_var( 'course-category' );
        $course_tag         = get_query_var( 'course-tag' );
        // lesson single
        if ( $wp_query->is_single && ! empty( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === $this->lesson_post_type ) {
            
            
            $template = mcv_lms_get_template_path( 'single-lesson' );
        }
        // course single
        if ( ($wp_query->is_single || ! empty( $wp_query->query_vars[$this->course_post_type] )) && ! empty( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === $this->course_post_type ) {
			wp_reset_postdata();
            $template = mcv_lms_get_template_path( 'single-course' );
        }
        // course achive
        if ( ( $post_type === $this->course_post_type || ! empty( $course_category ) || ! empty( $course_tag ) ) && $wp_query->is_archive ) {
            $template = mcv_lms_get_template_path( 'achive-course' );
            return $template;
        }

        $template = apply_filters( 'mcv_lms_load_template', $template );

        return $template;
    }
}