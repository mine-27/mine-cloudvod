<?php
namespace MineCloudvod\BunnyNet;

class Init{
    public $vod;
    public function __construct(){
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );
        $this->vod = new Vod();
    }

    public function admin_options(){
        $prefix = 'mcv_settings';

        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_bunnynet',
            'title' => __('BunnyNet', 'mine-cloudvod'),
            'icon'  => 'fas fa-cloud',
        ) );
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_bunnynet',
            'title'  => __('AccessKey setting', 'mine-cloudvod'),
            'icon'   => 'fas fa-key',
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => '<a href="https://bunny.net?ref=c7jsvi03q6" target="_blank">Bunny.net</a>', 
                ),
                array(
                    'id'        => 'bunnynet',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'apikey',
                            'type'  => 'text',
                            'title' => 'API Key',
                        ),
                    ),
                ),
            )
        ));
    }
}