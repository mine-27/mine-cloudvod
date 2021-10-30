<?php
MCSF::createSection( $prefix, array(
    'parent'     => 'tencentvod',
    'title'  => __('Tencent Cloud Vod', 'mine-cloudvod'),//'腾讯云点播',
    'icon'   => 'fas fa-video',
    'fields' => array(
        array(
        'type'    => 'submessage',
        'style'   => 'warning',
        'content' => __('By default, Tencent Cloud VOD is charged after the end of the day, and it can also be found on <a href="https://curl.qcloud.com/F8Ad6KaX" target="_blank">Tencent Cloud VOD Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'),//'<p>腾讯云点播默认是日结后收费模式，也可以在 <a href="https://curl.qcloud.com/F8Ad6KaX" target="_blank">腾讯云点播平台</a> 购买相应的资源包消费</p>',
        ),
        array(
        'id'        => 'tcvod',
        'type'      => 'fieldset',
        'title'     => '',
        'fields'    => array(
            array(
            'id'    => 'appid',
            'type'  => 'text',
            'title' => 'SubAppId',
            ),
            array(
            'id'    => 'fdlkey',
            'type'  => 'text',
            'title' => '防盗链 Key',
            'after' => '<a href="https://cloud.tencent.com/document/product/266/33469#key-.E9.98.B2.E7.9B.97.E9.93.BE" target="_blank">点此查看Key 防盗链的相关说明</a>',
            ),
            array(
            'id'          => 'region',
            'type'        => 'select',
            'title'       => __('Storage area', 'mine-cloudvod'),//'存储区域',
            'placeholder' => __('Select storage area', 'mine-cloudvod'),//'选择区域',
            'options'     => MINECLOUDVOD_TCVOD_ENDPOINT,
            'default'     => 'ap-chongqing'
            ),
            array(
            'id'          => 'transcode',
            'type'        => 'select',
            'title'       => __('Transcoding tasks', 'mine-cloudvod'),//'转码任务流',
            'after'       => '<p><a href="javascript:mcv_sync_transcode();">'.__('Sync transcoding task list.', 'mine-cloudvod').'</a></p>',//同步任务流列表
            'placeholder' => __('Select transcoding tasks', 'mine-cloudvod'),//'选择任务流',
            'options'     => $mcv_tc_transcode,
            'default'     => 'default'
            ),
            array(
            'id'          => 'plyrconfig',
            'type'        => 'select',
            'title'       => __('Tcplayer setting', 'mine-cloudvod'),//'超级播放器配置',
            'after'       => '<p><a href="javascript:mcv_asyc_plyrconfig();">'.__('Sync tcplayer setting list', 'mine-cloudvod').'</a></p>',//同步播放器配置列表
            'placeholder' => __('Select tcplayer setting', 'mine-cloudvod'),//'选择播放器配置',
            'options'     => $mcv_tc_plyrconfig,
            'default'     => 'default'
            ),
        ),
        ),
    
    )
    ) );