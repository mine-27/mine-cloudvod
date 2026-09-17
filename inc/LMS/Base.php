<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;
    
class Base{
    public $course_post_type, $lesson_post_type, $course_post_type_slug, $lesson_post_type_slug;
    public function __construct() {
        $this->course_post_type = MINECLOUDVOD_LMS['course_post_type'];
        $this->lesson_post_type = MINECLOUDVOD_LMS['lesson_post_type'];
        
        $this->course_post_type_slug = apply_filters( 'mcv_lms_course_base_slug', MINECLOUDVOD_LMS['course_post_type'] );
        $this->lesson_post_type_slug = apply_filters( 'mcv_lms_lesson_base_slug', MINECLOUDVOD_LMS['lesson_post_type'] );
    }
}