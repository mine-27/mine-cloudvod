<?php
namespace MineCloudvod\Aliyun;

class Options{
    public $prefix = 'mcv_settings';
    public function __construct() {
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'aliyun_admin_options' ) );
    }

    public function aliyun_admin_options(){
        \MCSF::createSection( $this->prefix, array(
            'id'    => 'aliyunvod',
            'title' => __('Alibaba Cloud', 'mine-cloudvod'),
            'icon'  => 'fas fa-cloud',
        ));
        \MCSF::createSection( $this->prefix, array(
            'parent'     => 'aliyunvod',
            'title'  => __('AccessKey setting', 'mine-cloudvod'), //'密钥配置',
            'icon'   => 'fas fa-key',
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'content' => __('By default, Alibaba Cloud VOD is charged after the end of the day, and it can also be found on <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">Alibaba Cloud VOD Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'), //'<p>阿里云视频点播默认是日结后收费模式，也可以在 <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">阿里云视频点播平台</a> 购买相应的资源包消费</p>',
                ),
                array(
                    'id'        => 'alivod',
                    'type'      => 'fieldset',
                    'title'     => __('ApsaraVideo VOD', 'mine-cloudvod'), //'阿里云视频点播',
                    'fields'    => array(
                        array(
                            'id'    => 'accessKeyID',
                            'type'  => 'text',
                            'title' => 'AccessKeyID',
                            'attributes'  => array(
                                'autocomplete' => 'off'
                            ),
                        ),
                        array(
                            'id'    => 'accessKeySecret',
                            'type'  => 'text',
                            'attributes'  => array(
                                'type'      => 'password',
                                'autocomplete' => 'off'
                            ),
                            'title' => 'AccessKeySecret',
                            'after' => __('<a href="https://ram.console.aliyun.com/manage/ak" target="_blank">Click here to get AccessKeyID and AccessKeySecret </a>', 'mine-cloudvod'), //'<a href="https://ram.console.aliyun.com/manage/ak" target="_blank">点此获取 AccessKeyID 和 AccessKeySecret </a>',
                        ),
                    )
                )
            )
        ));
    }
}