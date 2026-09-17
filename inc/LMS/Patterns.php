<?php
namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;

class Patterns extends Base{

    public function __construct() {
        parent::__construct();

        add_action( 'init', [ $this, 'mcv_pattern_category' ] );
        add_action( 'init', [ $this, 'mcv_pattern_course_list' ] );
        add_action( 'init', [ $this, 'mcv_pattern_course_single_v2' ] );
    }

    public function mcv_pattern_course_list() {
        register_block_pattern(
            'mine-cloudvod/lms/courses',
            array(
                'title'         => __( 'MineLMS Courses Block Pattern', 'mine-cloudvod' ),
                'description'   => _x( 'This block pattern display a courses list.', 'Block pattern description', 'mine-cloudvod' ),
                'content'       => include( MINECLOUDVOD_PATH . '/inc/LMS/Patterns/courses_list.php' ),
                'categories'    => array( 'mine-lms' ),
                'keywords'      => array( 'course', 'mcv', 'mine' ),
                // 'viewportWidth' => 800,
            )
        );
    }

    public function mcv_pattern_course_single_v2() {
        register_block_pattern(
            'mine-cloudvod/lms/course-single-v2',
            array(
                'title'         => __( 'Course Detail Page (V2 Atomic)', 'mine-cloudvod' ),
                'description'   => _x( 'Course detail page with atomic blocks. Users can freely rearrange layout components.', 'Block pattern description', 'mine-cloudvod' ),
                'content'       => include( MINECLOUDVOD_PATH . '/inc/LMS/Patterns/course_single_v2.php' ),
                'categories'    => array( 'mine-lms' ),
                'keywords'      => array( 'course', 'detail', 'single', 'mcv', 'mine', 'atomic' ),
            )
        );
    }

    public function mcv_pattern_category(){
        register_block_pattern_category( 'mine-lms', array(
            'label' => __( 'Mine LMS', 'mine-cloudvod' )
        ) );
    }
}
