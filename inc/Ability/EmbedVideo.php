<?php
namespace MineCloudvod\Ability;

class EmbedVideo
{
    public function __construct()
    {
        add_filter('render_block_data', array($this, 'render_embed_video'), 10, 2);
    }

    public function render_embed_video($parsed_block, $source_block)
    {
        if($parsed_block['blockName'] == "mine-cloudvod/embed-video" && isset($parsed_block['attrs']['src'])){
            $src = $parsed_block['attrs']['src'];
            $width = isset($parsed_block['attrs']['width'])?$parsed_block['attrs']['width']:'100%';
            $height = isset($parsed_block['attrs']['height'])?$parsed_block['attrs']['height']:'500px';
            $danmaku = isset($parsed_block['attrs']['danmaku'])?$parsed_block['attrs']['danmaku']:true;
            $type = isset($parsed_block['attrs']['type'])?$parsed_block['attrs']['type']:'unknown';
            if(!$danmaku && $type == 'bilibili'){
                $src .= '&danmaku=0';
            }
            $video = '<iframe src="'.$src.'" width="'.$width.'" height="'.$height.'" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true" sandbox="allow-top-navigation allow-same-origin allow-forms allow-scripts" id="mcv_embed_iframe"></iframe>';
            
            $video = apply_filters('mcv_filter_embedvideo', $video, $src, $width, $height);

            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        return $parsed_block;
    }
}
