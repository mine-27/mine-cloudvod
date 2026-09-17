<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 
namespace MineCloudvod;

if(!defined('ABSPATH'))exit;

class McvOptions{
    public $prefix = 'mcv_settings';
    public function __construct() {

        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'function_switch_options' ) );

        $this->init();
    }

    public function function_switch_options(){
        
        \MCSF::createSection(  $this->prefix, array(
            'parent'      => 'mcv_general',
            'title'       => __('Function Switch', 'mine-cloudvod'),
            'icon'        => 'fas fa-power-off',
            'description' => '',
            'fields'      => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'success',
                    'content' => __('Turning off unneeded functions can properly save server resources.', 'mine-cloudvod'),
                ),
                array(
                    'id'        => 'mcv_lms',
                    'title'     => __('Mine LMS', 'mine-cloudvod'),
                    'type'      => 'fieldset',
                    'fields'    => array(
                        array(
                            'id'    => 'status',
                            'type'  => 'switcher',
                            'title' => ' ' . __('State', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => true,
                        ),
                        array(
                            'type'    => 'submessage',
                            'style'   => 'warning',
                            'content' => ' <a href="javascript:mcv_init_lms();">' . __('For the first time, please click here to initialize', 'mine-cloudvod') . '</a>',
                            'dependency' => array('status', '==', true),
                        ),
                    ),
                ),
                array(
                    'id'        => 'players',
                    'title'     => __('Player', 'mine-cloudvod'),
                    'type'      => 'fieldset',
                    'fields'    => array(
                        array(
                            'id'    => 'dplayer',
                            'type'  => 'switcher',
                            'title' => __('DPlayer', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => true,
                        ),
                        array(
                            'id'    => 'aplayer',
                            'type'  => 'switcher',
                            'title' => __('Audio Player', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => true,
                        ),
                        array(
                            'id'    => 'aliplayer',
                            'type'  => 'switcher',
                            'title' => __('Aliplayer', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => true,
                        ),
                        array(
                            'id'    => 'embed',
                            'type'  => 'switcher',
                            'title' => __('Embed Video', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => true,
                        ),
                        // array(
                        //     'id'    => 'playlist',
                        //     'type'  => 'switcher',
                        //     'title' => __('Video Playlist', 'mine-cloudvod'),
                        //     'text_on'    => __('Enable', 'mine-cloudvod'),
                        //     'text_off'   => __('Disable', 'mine-cloudvod'),
                        //     'default' => true,
                        // ),
                    )
                ), 
                array(
                    'id'    => 'browser_blacklist',
                    'type'  => 'text',
                    'title' => __('Browser Blacklist', 'mine-cloudvod'),
                    'after' => __('Access to courses is prohibited in these browsers to prevent course theft. Format: mqqbrowser|mqqbrowser', 'mine-cloudvod'),
                    'default' => '',
                ),
                array(
                    'id'    => 'browser_blacklist_info',
                    'type'  => 'text',
                    'title' => __('Browser Blacklist', 'mine-cloudvod'),
                    'default' => '❌ 您的浏览器不支持本站视频的播放，请使用 Chrome 或 Safari 最新版。',
                    'dependency' => array('browser_blacklist', '!=', ''),
                ),
            ),
        ));
    }
    public function init() {
        $prefix = $this->prefix;
        
        \MCSF::createOptions( $this->prefix, array(
            'menu_title' => __('Mine CloudVod', 'mine-cloudvod'),
            'menu_slug'  => 'mcv-options',
            'admin_bar_menu_icon'=>'fas fa-cloud',
            'framework_title' => __('Mine CloudVod', 'mine-cloudvod') . ' <small>by mine27</small>',
            'menu_icon'=>MINECLOUDVOD_URL.'/static/img/aliplayer_20.png',
            'show_bar_menu' => false,
            'show_sub_menu'=>false,
            'menu_position' => 2,
            'menu_hidden' => true,
            'show_search' => false,
            'show_reset_all' => false,
            'footer_text'             => __('Welcome to use my plugin.', 'mine-cloudvod'),
            'footer_credit' => sprintf(
                /*
                 * Translators: %1$s is the plugin name, %2$s is the link to the plugin review page.
                 */
				__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'mine-cloudvod' ),
				sprintf( '<strong>%s</strong>', esc_html__( 'Mine CloudVod', 'mine-cloudvod' ) ),
				'<a href="https://wordpress.org/support/plugin/mine-cloudvod/reviews?rate=5#new-post" target="_blank" class="mcv-rating-link">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			)
        ));
        
        
        \MCSF::createSection(  $this->prefix, array(
            'id'          => 'mcv_general',
            'title'       => __('General settings', 'mine-cloudvod'),
            'icon'        => 'fas fa-home',
            'description' => ''
        ));
        \MCSF::createSection( $this->prefix, array(
            'parent'      => 'mcv_general',
            'title'  => __('General settings', 'mine-cloudvod'),
            'icon'   => 'fas fa-home',
            'fields' => array(
                array(
                    'id'         => 'siteid',
                    'type'       => 'text',
                    'title'      => __('Site ID', 'mine-cloudvod'), //'站点ID',
                    'attributes' => array(
                        'readonly' => 'readonly'
                    ),
                    'default'    => ''
                ),
                array(
                    'id'         => 'secret',
                    'type'       => 'text',
                    
        
                    'attributes' => array(
                        'hidden' => ''
                    ),
                    'default'    => ''
                ),
                array(
                    'id'      => 'cdntype',
                    'type'    => 'radio',
                    'title'   => __('CDN Type', 'mine-cloudvod'),
                    'inline'  => true,
                    'options' => array(
                        'self'    => __('Self Hosted', 'mine-cloudvod'),
                        // 'jsdelivr'   => __('Jsdelivr', 'mine-cloudvod'),
                        'customize'   => __('Customize', 'mine-cloudvod'),
                    ),
                    'default' => 'self',
                ),
                array(
                    'id'         => 'cdnprefix',
                    'type'       => 'text',
                    'title'      => __('CDN Prefix', 'mine-cloudvod'), //'CDN前缀',
                    'after'      => __('You can use {version} to replace the version of the plugin.', 'mine-cloudvod'),
                    'default'    => 'https://cdn.jsdelivr.net/wp/plugins/mine-cloudvod/tags/{version}', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- CDN 前缀配置项默认值，属插件功能非违规卸载
                    'dependency' => array('cdntype', '==', 'customize'),
                ),
                array(
                    'id'        => 'rolePermission',
                    'type'      => 'fieldset',
                    'title' => __('Role Permission', 'mine-cloudvod'),//'角色权限',
                    'subtitle'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'status',
                            'type'  => 'switcher',
                            'title' => __('State', 'mine-cloudvod'), //'状态',
                            'text_on'    => __('Enable', 'mine-cloudvod'), //'启用',
                            'text_off'   => __('Disable', 'mine-cloudvod'), //'禁用',
                            'default' => false
                        ),
                        array(
                            'id'    => 'roles',
                            'type'  => 'select',
                            'options'     => 'roles',
                            'multiple'      => true,
                            'attributes' => array(
                              'style'    => 'min-width: 150px;min-height:200px;'
                            ),
                            'dependency' => array('status', '==', true),
                            'default'    => ['administrator','author','editor']
                        ),
                    )
                ),
                array(
                    'type'    => 'submessage',
                    'style'   => 'success',
                    'content' => __('Welcome to use my plugin.', 'mine-cloudvod'),
                ),
            )
        ));

        global $pagenow;
        if( is_admin() && ($pagenow == 'admin-ajax.php' || ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'mcv-options')) ){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 仅判断后台当前页面
            do_action( 'mcv_add_admin_options_after_aliplayer' );
            
            do_action( 'mcv_add_admin_options_before_purchase' );
        }
    }
}


