<?php

namespace MineCloudvod\Integrations\Elementor;
use MineCloudvod\Aliyun\Aliplayer;
use MineCloudvod\Qcloud\Tcplayer;
use MineCloudvod\Ability\Audioplayer;

class Elementor
{
    public function __construct()
    {
        add_action( 'elementor/preview/enqueue_styles', [$this, 'enqueue'] );
        add_action('elementor/widgets/widgets_registered', [$this, 'widget']);
    }

    public function enqueue(){
        global $mcv_classes;
        wp_enqueue_script('mine_cloudvod-integrations-elementor-js');
        $mcv_classes->Aliplayer && $mcv_classes->Aliplayer::style_script();
        $ActiveAddons['qcloud']??false && $ActiveAddons['qcloud']->Tcplayer::style_script();
        if($mcv_classes->Audioplayer)$mcv_classes->Audioplayer->style_script();
        if($mcv_classes->Dplayer)$mcv_classes->Dplayer->style_script();
    }

    public function widget()
    {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new McvVideoWidget());
    }
}
