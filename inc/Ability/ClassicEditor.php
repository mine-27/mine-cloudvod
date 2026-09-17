<?php
namespace MineCloudvod\Ability;

class ClassicEditor
{
    public function __construct(){
        add_filter("mce_external_plugins",	array($this, "add_mcv_tinymce_plugin"), 9999);
		add_filter('mce_buttons',			array($this, 'register_mcv_button'), 9999);
    }

    public function add_mcv_tinymce_plugin( $plugins ){
        if( !mcv_check_role_permission() ) return $plugins;
        wp_enqueue_style('wp-components');
        wp_enqueue_style('mine_cloudvod-aliyunvod-block-editor-css');
        wp_enqueue_style('mine_cloudvod-classic-css', MINECLOUDVOD_URL.'/build/classic/style-index.css', [], MINECLOUDVOD_VERSION);
        global $mcv_classes;
        wp_enqueue_script('mine_cloudvod-classic-js');
        wp_localize_script( 'mine_cloudvod-classic-js', 'mcv_switch', [
            'vod' => [
                'aliyun' => $mcv_classes->Addons->is_addons_actived('aliyun'),
                'qcloud' => $mcv_classes->Addons->is_addons_actived('qcloud'),
                'dogecloud' => $mcv_classes->Addons->is_addons_actived('doge'),
                'qiniukodo' => $mcv_classes->Addons->is_addons_actived('qiniukodo'),
            ],
            'players' => MINECLOUDVOD_SETTINGS['players']??[
                'aliplayer'=>'1',
                'dplayer'=>'1',
                'playlist'=>'1',
                'aplayer'=>'1',
                'embed'=>'1',
            ],
            'addons' => $mcv_classes->Addons->get_actived_addons(),
            'lms' => MINECLOUDVOD_SETTINGS['mcv_lms']['status'] ?? true,
        ]);
        $plugins['mcv_tinymce'] = MINECLOUDVOD_URL.'/build/classic/index.js';
		return $plugins;
    }

	public function register_mcv_button($buttons) {
        if( !mcv_check_role_permission() ) return $buttons;
		array_push($buttons, "mcv_tinymce");
		return $buttons;
	}
}