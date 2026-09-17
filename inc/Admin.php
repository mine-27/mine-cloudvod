<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod;
if ( ! defined( 'ABSPATH' ) ) exit;
    
class Admin{
    public function __construct() {

        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        // 强制使用区块编辑器
        add_action('use_block_editor_for_post', [$this, 'forceGutenberg'], 9999, 2);

		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

        add_filter( 'parent_file', [$this, 'keep_parent_menu_open'] );
        // add_filter( 'submenu_file', [$this, 'keep_sub_menu_open'], 10, 2 );

        // 优先级999：确保在子菜单注册完成后执行
        add_action('admin_menu', [$this, 'hide_empty_submenu'], 999);
        add_action( 'admin_enqueue_scripts',function(){
            wp_add_inline_style('csf','
            .mcv_fieldset .csf-fieldset-content{
                display: inline-flex;
                border: 0;
            }
            .mcv_fieldset .csf-field{
                display: inline-flex;
                align-items: flex-end;
            }
            .mcv_fieldset .csf-field .csf-before-text{
                padding-right: 8px;
            }
            ');
        } );
    }
    public function mcv_to_course(){
        $to = 'admin.php?page=mcv-options';
        if(MINECLOUDVOD_SETTINGS['mcv_lms']['status']) $to = 'edit.php?post_type='.MINECLOUDVOD_LMS['course_post_type'];
        echo '<script>location.href="'.esc_url($to).'";</script>';
    }

    public function hide_empty_submenu() {
        global $submenu;
        
        foreach( $submenu as $key => &$val ) {
            foreach( $val as $k => &$v ) {
                if( $v[0] == '' ) $v[4] = 'mcv-hidden';
            }
        }
    }
    public function register_menu(){
        add_menu_page(__('Mine CloudVod', 'mine-cloudvod'), __('Mine CloudVod', 'mine-cloudvod'), 'manage_options', 'mine-cloudvod', [$this, 'mcv_to_course'],MINECLOUDVOD_URL.'/static/img/aliplayer_20.png', 2 );
        
        if( MINECLOUDVOD_SETTINGS['mcv_lms']['status'] ?? true ){
            
            add_submenu_page(
                'mine-cloudvod', 
                __('Course', 'mine-cloudvod'), 
                __('Course', 'mine-cloudvod'), 
                'manage_options', 
                'edit.php?post_type='.MINECLOUDVOD_LMS['course_post_type']
            );

            do_action( 'mcv_course_menu_after' );

            add_submenu_page(
                'mine-cloudvod', 
                __('Categories', 'mine-cloudvod'), 
                __('Categories', 'mine-cloudvod'), 
                'manage_options', 
                'edit-tags.php?taxonomy=course-category&post_type=' . MINECLOUDVOD_LMS['course_post_type']
            );

            add_submenu_page(
                'mine-cloudvod', 
                __('Tags', 'mine-cloudvod'), 
                __('Tags', 'mine-cloudvod'), 
                'manage_options', 
                'edit-tags.php?taxonomy=course-tag&post_type=' . MINECLOUDVOD_LMS['course_post_type']
            );

            add_submenu_page(
                'mine-cloudvod', 
                __('Order', 'mine-cloudvod'), 
                __('Orders', 'mine-cloudvod'), 
                'manage_options', 
                'edit.php?post_type=mcv_order&order_status=payed'
            );
            
            do_action( 'mcv_admin_after_lms_submenu' );
        }

        add_submenu_page( 
            'mine-cloudvod',  
            __('Add-ons', 'mine-cloudvod'),
            __('Add-ons', 'mine-cloudvod'), 
            'manage_options', 
            'mcv-addons', 
            [ $this, 'mcv_addons_switch' ]
        );
        add_submenu_page( 'mine-cloudvod',  __('Settings', 'mine-cloudvod'), __('Settings', 'mine-cloudvod'), 'manage_options', 'mcv-options' );

        global $submenu;
        $mcv_submenu = $submenu['mine-cloudvod'] ?? false;
        
        if( !$mcv_submenu ) return;

        $mcv_submenu_new = [];
        foreach( $mcv_submenu as $sub ){
            if( $sub[2] == 'mine-cloudvod' ) continue;
            $tmp = $sub;
            if( $sub[2] == 'edit.php?post_type=mcv_video' )
                $tmp[4] = 'mcv-submenu-course';
            if( $sub[2] == 'mcv-addons' )
                $tmp[4] = 'mcv-submenu-settings';
            // if( $sub[0] == '' )
            //     $tmp[4] = 'mcv-hidden';
            $mcv_submenu_new[] = $tmp;
        }
        $submenu['mine-cloudvod'] = $mcv_submenu_new;
        
    }

    public function keep_parent_menu_open($parent_file) {
        global $current_screen;
        $taxonomy = $current_screen->taxonomy;
        if( !$taxonomy ) $taxonomy = $current_screen->post_type;
        if ( $taxonomy == 'course-category' || $taxonomy == 'course-tag' || $taxonomy == MINECLOUDVOD_LMS['course_post_type'] || $taxonomy == MINECLOUDVOD_LMS['lesson_post_type'] )
            $parent_file = 'mine-cloudvod';
        return $parent_file;
    }

    public function keep_sub_menu_open( $submenu_file, $parent_file ){
        if( $submenu_file ) return $submenu_file;
        if( !$submenu_file ){
            $submenu_file = html_entity_decode(sanitize_text_field(wp_unslash($_GET['page']??''))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读判断后台菜单高亮
        }
        
        return $submenu_file;
    }

    /**
     * 强制使用区块编辑器
     */
    public function forceGutenberg($use, $post){
        if ($post && ( 'mcv_video' === $post->post_type || MINECLOUDVOD_LMS['course_post_type'] == $post->post_type || MINECLOUDVOD_LMS['lesson_post_type'] == $post->post_type ) ) {
            return true;
        }

        return $use;
    }

	public function admin_footer_text( $footer_text ) {
		$current_screen = get_current_screen();
		if ( apply_filters( 'mcv_display_admin_footer_text', $current_screen->parent_base === 'mine-cloudvod' ) ) {
			$footer_text = sprintf(
                /* translators: 1: Plugin name 2: Review link */
				__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'mine-cloudvod' ),
				sprintf( '<strong>%s</strong>', esc_html__( 'Mine CloudVod', 'mine-cloudvod' ) ),
				'<a href="https://wordpress.org/support/plugin/mine-cloudvod/reviews?rate=5#new-post" target="_blank" class="mcv-rating-link">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);
		}
		return $footer_text;
	}

    public function mcv_addons_switch(){
        wp_enqueue_style( 'mine-cloudvod-admin-css' );
        wp_enqueue_style( 'thickbox' );
        wp_enqueue_script( 'mcv_layer' );
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_script( 'mine-cloudvod-admin-js' );
        global $mcv_classes;
        $list = $mcv_classes->Addons->mcv_addons_lists_to_show(true);
        wp_localize_script( 'mine-cloudvod-admin-js', 'mcv_addons', [
            'plugin_url' => MINECLOUDVOD_URL,
            'list' => $list,
            'actived' => $mcv_classes->Addons->get_actived_addons(),
            'et' => (int) strtotime( (string) ( MINECLOUDVOD_SETTINGS['endtime'] ?? '' ) ),
        ] );
        wp_localize_script( 'mine-cloudvod-admin-js', 'mcv_nonce', [
            'buynow' => admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Purchase time', 'mine-cloudvod')))))
        ] );
        echo '<div id="mcv-addons-list"></div>';
    }
}