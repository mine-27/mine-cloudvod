<?php
namespace MineCloudvod\Ability;

class Plugin
{
    protected $plugin_basename = "mine-cloudvod/mine-cloudvod.php";
    public function __construct()
    {
        add_filter('plugin_action_links_' . $this->plugin_basename, array( $this, 'plugin_action_links' ) );
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        add_action('admin_notices', array($this, 'notice_mcv_endtime') );
    }

    public function plugin_action_links($actions){
		$actions['settings'] = '<a href="admin.php?page=mine-cloudvod">' . __('Settings') . '</a>';
		return $actions;
	}

    public function plugin_row_meta($plugin_meta, $plugin_file){

        if ($plugin_file === $this->plugin_basename) {
            $plugin_meta[] = sprintf( '<a href="%s">%s</a>',
                esc_url( 'https://www.zwtt8.com/docs-category/mine-cloudvod/?utm_source=mine_cloudvod&utm_medium=plugins_installation_list&utm_campaign=plugin_docs_link' ),
                __( '<strong style="color: #03bd24">Documentation</strong>', 'mine-cloudvod' )
            );
        }

        return $plugin_meta;
    }

    public function notice_mcv_endtime(){
        $endtime = MINECLOUDVOD_SETTINGS['endtime'];
        if($endtime){
            $endtime = strtotime($endtime);
            if($endtime < time()){
                $class = 'notice notice-error';
                $message = sprintf(__( 'Mine CloudVod is expired, please <a href="%s">renew</a> in time.', 'mine-cloudvod'), admin_url('/admin.php?page=mine-cloudvod#tab='.str_replace(' ', '-', strtolower(urlencode(__('Purchase time', 'mine-cloudvod'))))));
                printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
            }
            elseif($endtime-time() < 3600*24*10){
                $class = 'notice notice-warning is-dismissible';
                $message = sprintf(__( 'Mine CloudVod will expire in %d days, please <a href="%s">renew</a> in time.', 'mine-cloudvod' ), ($endtime-time())/3600/24, admin_url('/admin.php?page=mine-cloudvod#tab='.str_replace(' ', '-', strtolower(urlencode(__('Purchase time', 'mine-cloudvod'))))));
            
                printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
            }
        }
        
    }
}
