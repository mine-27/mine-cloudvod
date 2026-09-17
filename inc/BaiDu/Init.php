<?php
namespace MineCloudvod\BaiDu;

class Init{
    public $vod;
    public function __construct(){
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );
        $this->vod = new Vod();
    }

    public function admin_options(){
        $prefix = 'mcv_settings';

        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_baiducloud',
            'title' => __('BaiDu Cloud', 'mine-cloudvod'),
            'icon'  => 'fas fa-cloud',
        ) );
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_baiducloud',
            'title'  => __('AccessKey setting', 'mine-cloudvod'),
            'icon'   => 'fas fa-key',
            'fields' => array(
                array(
                    'type'   => 'submessage',
                    'style'  => 'success',
                    'content'=> '<a href="https://console.bce.baidu.com/vod2/#/mediaManager" target="_blank">百度云智能点播平台</a>是百度提供的云点播平台。'
                ),
                array(
                    'id'        => 'baiducloud',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'sid',
                            'type'  => 'text',
                            'title' => '密钥ID',
                        ),
                        array(
                            'id'    => 'skey',
                            'type'  => 'text',
                            'title' => '密钥',
                        ),
                        array(
                            'type'   => 'submessage',
                            'style'  => 'success',
                            'content'=> '<a href="https://console.bce.baidu.com/iam/#/iam/accesslist" target="_blank">获取密钥</a>'
                        ),
                    ),
                ),
            )
        ));
    }
}