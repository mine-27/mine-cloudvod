<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Ability;

class Shortcode
{
    public function __construct()
    {
        add_shortcode('mine_cloudvod', [$this, 'mine_cloudvod']);
        add_shortcode('mcv_aliplayer', [$this, 'mcv_aliplayer']);
        add_shortcode('mcv_alivod', [$this, 'mcv_alivod']);
        add_shortcode('mcv_alioss', [$this, 'mcv_alioss']);
        add_shortcode('mcv_audio', [$this, 'mcv_audio']);
        add_shortcode('mcv_embed', [$this, 'mcv_embed']);
        add_shortcode('mcv_tcvod', [$this, 'mcv_tcvod']);
        add_shortcode('mcv_tccos', [$this, 'mcv_tccos']);
        add_shortcode('mcv_dplayer', [$this, 'mcv_dplayer']);
        add_shortcode('mcv_qiniukodo', [$this, 'mcv_qiniukodo']);
        add_shortcode('mcv_bunnynet', [$this, 'mcv_bunnynet']);
        add_shortcode('mcv_cloudflare', [$this, 'mcv_cloudflare']);
        add_shortcode('mcv_huaweivod', [$this, 'mcv_huaweivod']);
        add_shortcode('mcv_dogevcloud', [$this, 'mcv_dogevcloud']);
        // add_shortcode('mcv_playlist', [$this, 'mcv_playlist']);
        add_shortcode('mcv_course_list', [$this, 'mcv_course_list']);
        add_shortcode('mcv_course_single', [$this, 'mcv_course_single']);
        add_shortcode('mcv_lesson_single', [$this, 'mcv_lesson_single']);
        add_shortcode('mcv_order_list', [$this, 'mcv_order_list']);
        add_shortcode('mcv_course_favorites', [$this, 'mcv_course_favorites']);
        add_shortcode('mcv_user_courses', [$this, 'mcv_user_courses']);
    }
    /**
     * mcv_course_list
     */
    public function mcv_course_list($attrs, $content){
        $block = [
            'blockName'     => 'mine-cloudvod/course-list',
            'attrs'         => $attrs,
            'innerBlocks'   => [],
            'innerHTML'     => '',
            'innerContent'  => [$content]
        ];
        $out = render_block($block);
        return $out;
    }
    public function mcv_course_single($attrs, $content){
        $block = [
            'blockName'     => 'mine-cloudvod/course-single',
            'attrs'         => $attrs,
            'innerBlocks'   => [],
            'innerHTML'     => '',
            'innerContent'  => [$content]
        ];
        $out = render_block($block);
        return $out;
    }
    public function mcv_lesson_single($attrs, $content){
        $block = [
            'blockName'     => 'mine-cloudvod/course-single',
            'attrs'         => $attrs,
            'innerBlocks'   => [],
            'innerHTML'     => '',
            'innerContent'  => [$content]
        ];
        $out = render_block($block);
        return $out;
    }
    public function mcv_order_list($attrs, $content){
        if( !isset( $attrs['hidemenu'] ) ) $attrs['hidemenu'] = true;
        $block = [
            'blockName'     => 'mine-cloudvod/order-list',
            'attrs'         => $attrs,
            'innerBlocks'   => [],
            'innerHTML'     => '',
            'innerContent'  => [$content]
        ];
        $out = render_block($block);
        return $out;
    }
    public function mcv_course_favorites($attrs, $content){
        if( !isset( $attrs['hidemenu'] ) ) $attrs['hidemenu'] = true;
        $block = [
            'blockName'     => 'mine-cloudvod/favorites',
            'attrs'         => $attrs,
            'innerBlocks'   => [],
            'innerHTML'     => '',
            'innerContent'  => [$content]
        ];
        $out = render_block($block);
        return $out;
    }
    public function mcv_user_courses($attrs, $content){
        if( !isset( $attrs['hidemenu'] ) ) $attrs['hidemenu'] = true;
        $block = [
            'blockName'     => 'mine-cloudvod/user-courses',
            'attrs'         => $attrs,
            'innerBlocks'   => [],
            'innerHTML'     => '',
            'innerContent'  => [$content]
        ];
        $out = render_block($block);
        return $out;
    }

    public function mcv_check_from($attrs){
        global $mcv_block_ajax_from;
        $mcv_block_ajax_from = false;
        if(isset($attrs['from'])){
            $mcv_block_ajax_from = $attrs['from'];
        }
    }

    public function mine_cloudvod($attrs, $content)
    {
        $this->mcv_check_from($attrs);
        if ($attrs['id']) {
            $post = get_post($attrs['id']);
            $blocks = parse_blocks($post->post_content);
            $out = '';
            foreach ($blocks as $block) {
                $block['post_id'] = $attrs['id'];
                $out .= render_block($block);
            }
            return $out;
        }
    }
    /**
     * mcv_playlist
     */
    // public function mcv_playlist($attrs, $content){
    //     $attrs['mcvTag'] = $attrs['mcvtag'] ?? $attrs['mcvTag'] ?? '';
    //     $attrs['plName'] = $attrs['plname'] ?? $attrs['mcvTag'] ?? '';
    //     $attrs['show'] = $attrs['show']  ?? false;
    //     if ( $attrs['mcvTag'] ) {
    //         $block = [
    //             'blockName'     => 'mine-cloudvod/video-playlist',
    //             'attrs'         => $attrs,
    //             'innerBlocks'   => [],
    //             'innerHTML'     => '',
    //             'innerContent'  => [$content]
    //         ];
    //         $out = render_block($block);
    //         return $out;
    //     }
    // }
    /**
     * mcv_dplayer
     */
    public function mcv_dplayer($attrs, $content){
        $this->mcv_check_from($attrs);
        if ($attrs['source']) {
            $attrs['source'] = html_entity_decode($attrs['source']);
            $block = [
                'blockName'     => 'mine-cloudvod/dplayer',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_qiniukodo
     */
    public function mcv_qiniukodo($attrs, $content){
        $this->mcv_check_from($attrs);
        if ($attrs['key']) {
            
            $block = [
                'blockName'     => 'mine-cloudvod/qiniu',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_bunnynet
     */
    public function mcv_bunnynet($attrs, $content){
        $this->mcv_check_from($attrs);
        if ($attrs['vid'] && $attrs['libid']) {
            
            $block = [
                'blockName'     => 'mine-cloudvod/bunny',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
    public function mcv_cloudflare($attrs, $content){
        $this->mcv_check_from($attrs);
        if ($attrs['vid']) {
            
            $block = [
                'blockName'     => 'mine-cloudvod/cloudflare',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
    public function mcv_huaweivod($attrs, $content){
        $this->mcv_check_from($attrs);
        $attrs['videoId'] = $attrs['videoId'] ?? $attrs['videoid'];
        if ($attrs['videoId']) {
            
            $block = [
                'blockName'     => 'mine-cloudvod/huawei-vod',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_dogevcloud
     */
    public function mcv_dogevcloud($attrs, $content){
        $this->mcv_check_from($attrs);
        if ($attrs['vcode']) {
            $attrs['userId'] = $attrs['userId']??MINECLOUDVOD_SETTINGS['dogecloud']['userId']??'';
            $block = [
                'blockName'     => 'mine-cloudvod/doge',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_aliplayer
     */
    public function mcv_aliplayer($attrs, $content){
        $this->mcv_check_from($attrs);
        if ($attrs['source']) {
            $attrs['source'] = html_entity_decode($attrs['source']);
            $block = [
                'blockName'     => 'mine-cloudvod/aliplayer',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_alivod
     */
    public function mcv_alivod($attrs){
        $this->mcv_check_from($attrs);
        $attrs['videoId'] = $attrs['videoId'] ?? $attrs['videoid'];
        if ($attrs['videoId']) {
            $block = [
                'blockName'     => 'mine-cloudvod/aliyun-vod',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => []
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_alioss
     */
    public function mcv_alioss($attrs){
        $this->mcv_check_from($attrs);
        if ($attrs['key'] && $attrs['bucket']) {
            $attrs['oss'] = [
                'key'       => $attrs['key'],
                'bucket'    => $attrs['bucket']
            ];
            $block = [
                'blockName'     => 'mine-cloudvod/aliyun-vod',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => []
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_tcvod
     */
    public function mcv_tcvod($attrs){
        $this->mcv_check_from($attrs);
        $attrs['videoId'] = $attrs['videoId'] ?? $attrs['videoid'];
        if ($attrs['videoId']) {
            $block = [
                'blockName'     => 'mine-cloudvod/tc-vod',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => []
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_tccos
     */
    public function mcv_tccos($attrs){
        $this->mcv_check_from($attrs);
        if ($attrs['key'] && $attrs['bucket']) {
            $attrs['cos'] = [
                'key'       => $attrs['key'],
                'bucket'    => $attrs['bucket']
            ];
            $block = [
                'blockName'     => 'mine-cloudvod/tc-vod',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => []
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_audio
     */
    public function mcv_audio($attrs, $content){
        $this->mcv_check_from($attrs);
        if ($attrs['audio'] || $attrs['aliyunAid']) {
            $attrs['audio'] = html_entity_decode($attrs['audio']);
            $block = [
                'blockName'     => 'mine-cloudvod/audioplayer',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
    /**
     * mcv_embed
     */
    public function mcv_embed($attrs, $content){
        $this->mcv_check_from($attrs);
        if ($attrs['src']) {
            $block = [
                'blockName'     => 'mine-cloudvod/embed-video',
                'attrs'         => $attrs,
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [$content]
            ];
            $out = render_block($block);
            return $out;
        }
    }
}
