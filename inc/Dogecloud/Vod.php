<?php
namespace MineCloudvod\Dogecloud;

class Vod{
    public function __construct(){
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'doge_admin_options' ) );

        add_action( 'init',     [ $this, 'mcv_register_block'] );
    }

    public function mcv_register_block(){
        
        wp_register_script(//mcv_dplayer_hls
            'mcv_dplayer_hls',
            MINECLOUDVOD_URL.'/static/dplayer/hls.min.js',
            null,
            MINECLOUDVOD_VERSION,
            true
        );

        wp_register_style(
            'mcv_dplayer_css',
            MINECLOUDVOD_URL.'/static/dplayer/style.css', 
            is_admin() ? array( 'wp-editor' ) : null,
            MINECLOUDVOD_VERSION
        );
        
        
        register_block_type( MINECLOUDVOD_PATH . '/build/dogecloud/');
        
        wp_add_inline_script('mine-cloudvod-doge-editor-script','var mcv_dplayer_config={userId:"'.(MINECLOUDVOD_SETTINGS['dogecloud']['userId']??'').'",doge_config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Dogecloud', 'mine-cloudvod'))))).'",doge_sdk:'.(MINECLOUDVOD_SETTINGS['dogecloud']['sid']??MINECLOUDVOD_SETTINGS['dogecloud']['kid']??false ? 'true' : 'false').'};');
    }

    public function doge_admin_options(){
        $prefix = 'mcv_settings';

        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_doge',
            'title' => __('Dogecloud', 'mine-cloudvod'),
            'icon'  => 'fas fa-cloud',
          ) );
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_doge',
            'title'  => __('AccessKey setting', 'mine-cloudvod'),
            'icon'   => 'fas fa-key',
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => __('<a href="https://www.dogecloud.com/?iuid=2453" target="_blank">Dogecloud官网</a> ', 'mine-cloudvod'), 
                ),
                array(
                    'id'        => 'dogecloud',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'sid',
                            'type'  => 'text',
                            'title' => 'AccessKey',
                        ),
                        array(
                            'id'    => 'skey',
                            'type'  => 'text',
                            'attributes'  => array(
                                'type'      => 'password',
                            ),
                            'title' => 'SecretKey',
                            'after' => '<a href="https://console.dogecloud.com/user/keys" target="_blank">点此获取 AccessKey 和 SecretKey </a>',
                        ),
                        array(
                        'id'    => 'userId',
                        'type'  => 'text',
                        'title' => __('User ID', 'mine-cloudvod'),
                        ),
                    ),
                ),
            )
        ));
    }

    public function mcv_render_doge($parsed_block, $source_block){
        if($parsed_block['blockName'] == "mine-cloudvod/doge"){
            $video = $this->mcv_block_doge($parsed_block);
            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        return $parsed_block;
    }
    public function mcv_block_doge($parsed_block, $enqueue = true){
        $doge_block = $parsed_block;
        $doge_block['attrs']['minecloudvod']['doge'] = [
            'vcode' => $parsed_block['attrs']['vcode'],
            'userId' => $parsed_block['attrs']['userId'],
        ];

        global $mcv_classes;
        $video = $mcv_classes->Dplayer->mcv_block_dplayer($doge_block, $enqueue);

        return $video;
    }
}
