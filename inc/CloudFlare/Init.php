<?php
namespace MineCloudvod\CloudFlare;

class Init{
    public $vod;
    public $r2;
    public function __construct(){
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );
        $this->vod = new Vod();
        $this->r2  = new R2();
    }

    public function admin_options(){
        $prefix = 'mcv_settings';

        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_cloudflare',
            'title' => __('CloudFlare', 'mine-cloudvod'),
            'icon'  => 'fas fa-cloud',
        ) );
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_cloudflare',
            'title'  => __('AccessKey setting', 'mine-cloudvod'),
            'icon'   => 'fas fa-key',
            'fields' => array(
                array(
                    'id'        => 'cloudflare',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'email',
                            'type'  => 'text',
                            'title' => 'CloudFlare EMail',
                        ),
                        array(
                            'id'    => 'accountid',
                            'type'  => 'text',
                            'title' => 'Account ID',
                        ),
                        array(
                            'id'    => 'apikey',
                            'type'  => 'text',
                            'title' => 'API Key',
                        ),
                        array(
                            'type'    => 'submessage',
                            'style'   => 'success',
                            'content' => '如果有API Token，就可以不填写Email和API Key，两组提供一组即可',
                        ),
                        array(
                            'id'    => 'apitoken',
                            'type'  => 'text',
                            'title' => 'API Token',
                        ),
                    ),
                ),
            )
        ));
    }
}