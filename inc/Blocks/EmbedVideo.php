<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Blocks;

class EmbedVideo
{
    public function __construct()
    {
        
        add_action( 'init',     [ $this, 'mcv_register_block'] );
    }

    public function mcv_register_block(){

        register_block_type( MINECLOUDVOD_PATH . '/build/embed/');
        
    }

    public function render_embed_video($parsed_block, $source_block)
    {
        if($parsed_block['blockName'] == "mine-cloudvod/embed-video" && isset($parsed_block['attrs']['src'])){
            $src = $parsed_block['attrs']['src'];
            $width = $parsed_block['attrs']['width']??'100%';
            $height = $parsed_block['attrs']['height']??'500px';
            $danmaku = $parsed_block['attrs']['danmaku']??false;
            $type = $parsed_block['attrs']['type']??'unknown';
            if(!$danmaku && $type == 'bilibili'){
                $src .= '&danmaku=0';
            }
            // sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts allow-popups"
            $video = '<iframe src="'.$src.'" width="'.$width.'" height="'.$height.'" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"  id="mcv_embed_iframe" style="max-width:100% !important;min-height: 100%;" class="is-'.$type.'"></iframe>';
            
            $video = apply_filters('mcv_filter_embedvideo', $video, $src, $width, $height);

            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        return $parsed_block;
    }
}
