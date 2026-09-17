<?php
namespace MineCloudvod\HuaWei;

class Init{
    public $vod;
    public function __construct(){
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );
        $this->vod = new Vod();
    }

    public function admin_options(){
        $prefix = 'mcv_settings';

        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_huaweicloud',
            'title' => __('HuaWei Cloud', 'mine-cloudvod'),
            'icon'  => 'fas fa-cloud',
        ) );
        \MCSF::createSection($prefix, array(
            'parent'     => 'mcv_huaweicloud',
            'title'  => __('AccessKey setting', 'mine-cloudvod'),
            'icon'   => 'fas fa-key',
            'fields' => array(
                array(
                    'type'   => 'submessage',
                    'style'  => 'success',
                    'content'=> '<a href="https://www.huaweicloud.com/special/vod.html?fromacct=4e4f8188c844405692be04d7ef40489a&utm_source=V1g3MDY4NTY=&utm_medium=cps&utm_campaign=201905" target="_blank">华为云视频点播</a>是集视频上传、自动化转码处理、媒体资源管理、分发加速、视频播放于一体的一站式媒体服务。借助华为云提供灵活弹性解决方案，您无需关注服务依赖的底层基础设施，只需要依托高质量的媒体处理服务来快速搭建安全、弹性的点播平台。'
                ),
                array(
                    'id'        => 'huaweicloud',
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
                            'content'=> '<a href="https://console.huaweicloud.com/iam/?locale=zh-cn#/mine/accessKey" target="_blank">获取密钥</a>'
                        ),
                    ),
                ),
            )
        ));
    }
}