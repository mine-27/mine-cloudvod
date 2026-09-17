<?php
namespace MineCloudvod\Blocks;

class AudioPlayer
{
    public function __construct()
    {
        add_action( 'init',     [ $this, 'mcv_register_block'] );
        add_filter( 'upload_mimes', [ $this, 'upload_mimes_lrc' ] );
    }

    public function upload_mimes_lrc( $mimes ){
        $mimes['lrc'] = 'text/plain';
        return $mimes;
    }

    public function mcv_register_block(){
        wp_register_script(
            'mcv_aplayer',
            MINECLOUDVOD_URL.'/static/aplayer/McvAPlayer.min.js',
            ['jquery'],
            MINECLOUDVOD_VERSION,
            true
        );
        register_block_type( MINECLOUDVOD_PATH . '/build/audioplayer/');
        
    }

    public function render_audio_player($parsed_block, $source_block){
        if($parsed_block['blockName'] == "mine-cloudvod/audioplayer" && (isset($parsed_block['attrs']['audio']) || isset($parsed_block['attrs']['aliyunAid']) )){
            
            $video = $this->mcv_block_audioplayer($parsed_block);

            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        return $parsed_block;
    }

    public function mcv_block_audioplayer($parsed_block, $enqueue = true){
        $attributes = $parsed_block['attrs'];
        
        ob_start();
        include(MINECLOUDVOD_PATH.'/build/audioplayer/render.php');
        $video = ob_get_clean();

        return $video;
    }
    public static function style_script()
    {
        wp_enqueue_script('mcv_aplayer');
    }
}
