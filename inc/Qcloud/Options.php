<?php
namespace MineCloudvod\Qcloud;

if ( ! defined( 'ABSPATH' ) ) exit;

class Options{
    public $prefix = 'mcv_settings';
    
    public function __construct() {
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'qcloud_admin_options' ) );
    }

    public function qcloud_admin_options(){
        \MCSF::createSection( $this->prefix, array(
            'id'    => 'tencentvod',
            'title' => __('Tencent Cloud', 'mine-cloudvod'),
            'icon'  => 'fas fa-cloud',
        ));
        \MCSF::createSection( $this->prefix, array(
            'parent'     => 'tencentvod',
            'title'  => __('AccessKey setting', 'mine-cloudvod'), //'密钥配置',
            'icon'   => 'fas fa-key',
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => __('By default, Tencent Cloud VOD is charged after the end of the day, and it can also be found on <a href="https://curl.qcloud.com/F8Ad6KaX" target="_blank">Tencent Cloud VOD Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'), //'<p>腾讯云点播默认是日结后收费模式，也可以在 <a href="https://curl.qcloud.com/F8Ad6KaX" target="_blank">腾讯云点播平台</a> 购买相应的资源包消费</p>',
                ),
                array(
                    'id'        => 'tcvod',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'sid',
                            'type'  => 'text',
                            'title' => 'SecretId',
                        ),
                        array(
                            'id'    => 'skey',
                            'type'  => 'text',
                            'attributes'  => array(
                                'type'      => 'password',
                            ),
                            'title' => 'SecretKey',
                            'after' => '<a href="https://console.cloud.tencent.com/cam/capi" target="_blank">点此获取 SecretId 和 SecretKey </a>',
                        ),
                    ),
                ),
            )
        ));
    }
}