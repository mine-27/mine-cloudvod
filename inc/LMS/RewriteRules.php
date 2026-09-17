<?php
namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;
    
class RewriteRules extends Base{
    public function __construct() {
        parent::__construct();

        add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		add_filter( 'post_type_link', [ $this, 'lesson_permalink' ], 10, 3 );
		// add_filter( 'term_link', [ $this, 'filter_permalink' ], 10, 3 );
		add_filter( 'pre_handle_404', [ $this, 'filter_archive_404' ], 10, 2 );
        
        add_action( 'generate_rewrite_rules', [ $this, 'generate_rewrite_rules' ] );
        add_action( 'parse_tax_query', [ $this, 'parse_tax_query' ] );
    }

    public function filter_archive_404( $preempt, $wp_query ){
        if( !$preempt && is_archive() && $wp_query->query_vars['post_type'] == $this->course_post_type ){
            return true;
        }
        return $preempt;
    }

    public function parse_tax_query( $wp_query ){
        if( isset( $wp_query->query_vars['course-category'] ) && $wp_query->query_vars['course-category'] == 'all' ) 
            unset( $wp_query->query_vars['course-category'] );
        if( isset( $wp_query->query_vars['course-tag'] ) && $wp_query->query_vars['course-tag'] == 'all' ) 
            unset( $wp_query->query_vars['course-tag'] );
    }

    public function filter_permalink( $termlink, $term, $taxonomy ){
        $permalink_structure = get_option( 'permalink_structure' );

        $is_rewrite = true;
        if( strpos( $permalink_structure, '?') || strpos( $termlink, '?') ) $is_rewrite = false;

        if( $taxonomy != 'course-tag' && $taxonomy != 'course-category' ) return $termlink;

        global $wp_query;
        $query_vars = $wp_query->query_vars;
        
        $args = [
            'course-category'   => $query_vars['course-category']??'all',
            'course-tag'        => $query_vars['course-tag']??'all',
            'mcv-lvl'           => $query_vars['mcv-lvl']??'all',
            'mcv-mod'           => $query_vars['mcv-mod']??'all',
        ];
        if( $taxonomy == 'course-tag' ){
            if( $is_rewrite ){
                return trailingslashit( $termlink ) . $args['course-category'] . '/' . $args['mcv-lvl'] . '/' . $args['mcv-mod'] . '/';
            }
            else{
                unset( $args['course-tag'] );
                return $termlink . '&' . http_build_query( $args );
            }
        }
        elseif( $taxonomy == 'course-category' ){
            if( $is_rewrite ){
                return trailingslashit( $termlink ) . $args['course-tag'] . '/' . $args['mcv-lvl'] . '/' . $args['mcv-mod'] . '/';
            }
            else{
                unset( $args['course-category'] );
                return $termlink . '&' . http_build_query( $args );
            }
        }
    }

    public function lesson_permalink( $post_link, $post, $leavename ){
        $post = get_post($post);
        if( $post && $post->post_type == $this->lesson_post_type && strpos( $post_link, '?' ) === false ){
            $link_type = MINECLOUDVOD_SETTINGS['mcv_lms_general']['lesson_permalink'] ?? 'postname';
            if( $link_type == 'postid' ){
                $post_link = home_url( '/' . $this->lesson_post_type_slug . '/' . $post->ID . '/' );
            }
            elseif( $link_type == 'postname' ){
                $course_id = mcv_lms_get_course_id_by_lesson_id( $post->ID );

                if( is_numeric( $course_id ) ){
                    $course = get_post( $course_id );
                    $course_slug = 'mcv_course';
                    if( $course ){
                        $course_slug = $course->post_name;
                    }
                    $post_link = home_url( '/' . $this->course_post_type_slug . '/' . $course_slug . '/' . $this->lesson_post_type_slug . '/' . ( $leavename ? '%postname%' : $post->post_name ) . '/');
                }
            }
        }
        if( $post && $post->post_type == $this->course_post_type && strpos( $post_link, '?' ) === false ){
            $link_type = MINECLOUDVOD_SETTINGS['mcv_lms_general']['course_permalink'] ?? 'postname';
            if( $link_type == 'postid' ){
                $post_link = home_url( '/' . $this->course_post_type_slug . '/' . $post->ID . '/' );
            }
        }
        return $post_link;
    }

    public function register_query_vars( $query_vars ){
        $query_vars[] = 'mcv-lvl';
        $query_vars[] = 'mcv-mod';
        $query_vars[] = 'mcv-s';
        $query_vars[] = 'mcv-vip';
        return $query_vars;
    }

    public function generate_rewrite_rules( $wp_rewrite ) {
        $new_rules = [];
        $llink_type = MINECLOUDVOD_SETTINGS['mcv_lms_general']['lesson_permalink'] ?? 'postname';
        if( $llink_type == 'postid' ){
            $new_rules[$this->lesson_post_type_slug . '/(\d+?)/?$'] = 'index.php?post_type=' . $this->lesson_post_type . '&p=' . $wp_rewrite->preg_index(1);
        }
        elseif( $llink_type == 'postname' ){
            $new_rules[$this->course_post_type_slug . '/(.+?)/' . $this->lesson_post_type_slug . '/(.+?)/?$'] = 'index.php?post_type=' . $this->lesson_post_type . '&name=' . $wp_rewrite->preg_index(2);
        }
        $clink_type = MINECLOUDVOD_SETTINGS['mcv_lms_general']['course_permalink'] ?? 'postname';
        if( $clink_type == 'postid' ){
            $new_rules[$this->course_post_type_slug . '/(\d+?)/?$'] = 'index.php?post_type=' . $this->course_post_type . '&p=' . $wp_rewrite->preg_index(1);
        }
        $new_rules += array(

            $this->course_post_type_slug . '/all/all/([^/]+)/([^/]+)/?$' => 'index.php?post_type=' . $this->course_post_type . '&mcv-lvl=' . $wp_rewrite->preg_index(1) . '&mcv-mod=' . $wp_rewrite->preg_index(2),

            $this->course_post_type_slug . '/all/all/([^/]+)/([^/]+)/page/([^/]+)/?$' => 'index.php?post_type=' . $this->course_post_type . '&mcv-lvl=' . $wp_rewrite->preg_index(1) . '&mcv-mod=' . $wp_rewrite->preg_index(2) . '&paged=' . $wp_rewrite->preg_index(3),

            $this->course_post_type_slug . '/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?post_type=' . $this->course_post_type . '&course-category=' . $wp_rewrite->preg_index(1) . '&course-tag=' . $wp_rewrite->preg_index(2) . '&mcv-lvl=' . $wp_rewrite->preg_index(3) . '&mcv-mod=' . $wp_rewrite->preg_index(4),

            $this->course_post_type_slug . '/([^/]+)/([^/]+)/([^/]+)/([^/]+)/page/([^/]+)/?$' => 'index.php?post_type=' . $this->course_post_type . '&course-category=' . $wp_rewrite->preg_index(1) . '&course-tag=' . $wp_rewrite->preg_index(2) . '&mcv-lvl=' . $wp_rewrite->preg_index(3) . '&mcv-mod=' . $wp_rewrite->preg_index(4) . '&paged=' . $wp_rewrite->preg_index(5),

            'course-tag/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?course-category=' . $wp_rewrite->preg_index(2) . '&course-tag=' . $wp_rewrite->preg_index(1) . '&mcv-lvl=' . $wp_rewrite->preg_index(3) . '&mcv-mod=' . $wp_rewrite->preg_index(4),

            'course-category/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?course-category=' . $wp_rewrite->preg_index(1) . '&course-tag=' . $wp_rewrite->preg_index(2) . '&mcv-lvl=' . $wp_rewrite->preg_index(3) . '&mcv-mod=' . $wp_rewrite->preg_index(4),

            'course-tag/([^/]+)/([^/]+)/([^/]+)/([^/]+)/page/([^/]+)/?$' => 'index.php?course-category=' . $wp_rewrite->preg_index(2) . '&course-tag=' . $wp_rewrite->preg_index(1) . '&mcv-lvl=' . $wp_rewrite->preg_index(3) . '&mcv-mod=' . $wp_rewrite->preg_index(4) . '&paged=' . $wp_rewrite->preg_index(5),

            'course-category/([^/]+)/([^/]+)/([^/]+)/([^/]+)/page/([^/]+)/?$' => 'index.php?course-category=' . $wp_rewrite->preg_index(1) . '&course-tag=' . $wp_rewrite->preg_index(2) . '&mcv-lvl=' . $wp_rewrite->preg_index(3) . '&mcv-mod=' . $wp_rewrite->preg_index(4) . '&paged=' . $wp_rewrite->preg_index(5),
        );

        $wp_rewrite->rules =  $new_rules + $wp_rewrite->rules;
    }
}