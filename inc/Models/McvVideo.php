<?php

namespace MineCloudvod\Models;
defined( 'ABSPATH' ) || exit;
class McvVideo
{
    public $post;
    private $post_type = 'mcv_video';

    public function __construct($id = 0)
    {
        if (!empty($id)) {
            if (is_array($id)) $id = $id[0];
            $this->post = \get_post($id);
            return $this;
        }
        return $this;
    }

    /**
     * Get attributes properties
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        return isset($this->post->$property) ? $this->post->$property : null;
    }

    public function create($args = [])
    {
        return wp_insert_post(wp_parse_args($args, [
            'post_type' => $this->post_type
        ]));
    }

    public function fetch($args = [])
    {
        $args = wp_parse_args($args, [
            'post_type' => $this->post_type
        ]);

        return get_posts($args);
    }

    public function all($args = [])
    {
        $args = wp_parse_args($args, [
            'post_type' => $this->post_type,
            'posts_per_page' => -1
        ]);

        return get_posts($args);
    }

    public function first($args = [])
    {
        $fetched = $this->fetch(wp_parse_args($args, ['per_page' => 1]));
        return !empty($fetched[0]) ? new static($fetched[0]) : false;
    }

    /**
     * Get block from video post
     *
     * @return array
     */
    public function getBlock()
    {
        if (empty($this->post->post_content)) {
            return [];
        }
        $blocks = \parse_blocks($this->post->post_content);

        return !empty($blocks[0]['innerBlocks'][0]) ? $blocks[0]['innerBlocks'][0] : [];
    }

    public function renderBlock($overrides = [])
    {
        $block = $this->getBlock();
        if (empty($block)) {
            return '';
        }
        global $mcv_classes, $ActiveAddons;
        // allow overriding attributes
        $block['attrs'] = wp_parse_args($overrides, (array)$block['attrs']);
        
        // $video = render_block( $block );
        // return $video;

        switch ($block['blockName']) {
            case 'mine-cloudvod/aliyun-vod':
                $aliplayer = $mcv_classes->Aliplayer;
                $video = '<!--mine-cloudvod/aliyun-vod -->';
                $video .= $aliplayer->mcv_block_aliplayer($block, false);
                return $video;

            case 'mine-cloudvod/tc-vod':
                $tcplayer = $ActiveAddons['qcloud']->Tcplayer;
                $video = '<!--mine-cloudvod/tc-vod -->';
                $video .= $tcplayer->mcv_block_tcplayer($block, false);
                return $video;

            case 'mine-cloudvod/embed-video':
                $video = render_block($block);
                return $video;

            case 'mine-cloudvod/aliplayer':
                $aliplayer = $mcv_classes->Aliplayer;
                $video = '<!--mine-cloudvod/aliplayer -->';
                $video .= $aliplayer->mcv_block_aliplayer($block, false);
                return $video;

            case 'mine-cloudvod/video-playlist':
                $playlist = $mcv_classes->Playlist;
                $video = '<!--mine-cloudvod/video-playlist -->';
                $video .= $playlist->render_playlist($block, false);
                return $video;

            case 'mine-cloudvod/audioplayer':
                $aplayer = $mcv_classes->Audioplayer;
                $video = '<!--mine-cloudvod/audioplayer -->';
                $video .= $aplayer->mcv_block_audioplayer($block, false);
                return $video;

            case 'mine-cloudvod/dplayer':
                $dplayer = $mcv_classes->Dplayer;
                $video = '<!--mine-cloudvod/dplayer -->';
                $video .= $dplayer->mcv_block_dplayer($block, false);
                return $video;

            case 'mine-cloudvod/doge':
                $doge = $ActiveAddons['doge']->Dogecloud;
                $video = '<!--mine-cloudvod/doge -->';
                $video .= $doge->mcv_block_doge($block, false);
                return $video;
        }
    }
}
