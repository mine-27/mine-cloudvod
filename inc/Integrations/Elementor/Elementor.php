<?php

namespace MineCloudvod\Integrations\Elementor;
use MineCloudvod\Aliyun\Aliplayer;
use MineCloudvod\Qcloud\Tcplayer;

class Elementor
{
    public function __construct()
    {
        add_action( 'elementor/preview/enqueue_styles', [$this, 'enqueue'] );
        add_action('elementor/widgets/widgets_registered', [$this, 'widget']);
    }

    public function enqueue(){
        wp_enqueue_script('mine_cloudvod-integrations-elementor-js');
        Aliplayer::style_script();
        Tcplayer::style_script();
    }

    public function widget()
    {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new McvVideoWidget());
    }
}
