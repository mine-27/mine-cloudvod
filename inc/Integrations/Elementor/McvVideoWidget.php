<?php

namespace MineCloudvod\Integrations\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use MineCloudvod\Models\McvVideo;
use MineCloudvod\Aliyun\Aliplayer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * video widget.
 */
class McvVideoWidget extends Widget_Base
{
    /**
     * Get widget name.
     */
    public function get_name()
    {
        return 'mcv_video';
    }

    /**
     * Get widget title.
     */
    public function get_title()
    {
        return __('Mine CloudVod', 'mine-cloudvod');
    }

    /**
     * Get widget icon.
     */
    public function get_icon()
    {
        return 'eicon-cloud-check';
    }

    /**
     * Get widget categories.
     */
    public function get_categories()
    {
        return ['basic'];
    }

    /**
     * Get widget keywords.
     */
    public function get_keywords()
    {
        return ['video', 'player', 'embed', 'youtube', 'vimeo', 'bilibili', 'youku', 'aliyun', 'tencent', 'vod'];
    }

    /**
     * Register video widget controls.
     */
    protected function _register_controls()
    {
        $this->start_controls_section(
            'section_video',
            [
                'label' => __('Cloud Vod', 'mine-cloudvod'),
            ]
        );


        $options = $this->get_videos_options();
        $this->add_control(
            'video_block',
            [
                'label' => __('Choose A Video', 'mine-cloudvod'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $options,
                'default' => @[array_keys($options)[0]]
            ]
        );

        $this->add_control(
            'edit_video',
            [
                'label' => __('Video Options', 'mine-cloudvod'),
                'type' => \Elementor\Controls_Manager::BUTTON,
                'text' => __('Edit Video', 'mine-cloudvod'),
                'event' => 'mcv:video:edit',
                // 'conditions' => [
                // 	'video_block[value]!' => null
                // ]
            ]
        );

        $this->add_control(
            'create_video',
            [
                'label' => __('New Video', 'mine-cloudvod'),
                'separator' => 'before',
                'classes' => 'testclass',
                'type' => \Elementor\Controls_Manager::BUTTON,
                'text' => __('New Video', 'mine-cloudvod'),
                'event' => 'mcv:video:create',
            ]
        );


        $this->end_controls_section();
    }

    public function get_videos_options()
    {
        $videos = (new McvVideo())->fetch();
        $options = [];
        foreach ($videos as $video) {
            $options[$video->ID] = sanitize_text_field($video->post_title);
        }
        return $options;
    }

    /**
     * Render video widget output on the frontend.
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $video = new McvVideo($settings['video_block']);
        $overrides = [];
        $render = $video->renderBlock($overrides);
        if ($render) {
            echo $render;
            return;
        }

        $video = (new McvVideo())->first();
        echo $video ? $video->renderBlock($overrides) : '';
    }
}
