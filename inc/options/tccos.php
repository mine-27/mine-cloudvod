<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

if ( ! defined( 'ABSPATH' ) ) exit;
$mcv_alioss_bucketsList = array('' => __('Please sync Bukcets List first', 'mine-cloudvod'));//'请先同步转码模板');
if($tctc = get_option('mcv_tccos_bucketsList')){
    $mcv_alioss_bucketsList = array();
    foreach($tctc as $tc){
        $mcv_alioss_bucketsList[$tc[0]] =  $tc[0];
    }
}

  \MCSF::createSection( $prefix, array(
    'parent'     => 'tencentvod',
    'title'  => __('Tencent COS', 'mine-cloudvod'),//'腾讯云COS',
    'icon'   => 'far fa-file-video',
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
            'id'          => 'buckets',
            'type'        => 'select',
            'title'       => __('Bucket', 'mine-cloudvod'),//'转码模板',
            'after'       => '<p><a href="javascript:mcv_sync_tccos_buckets();">'.__('Sync Buckets List', 'mine-cloudvod').'</a></p>',//同步Bucket列表,
            'options'     => $mcv_alioss_bucketsList,
            'default'     => ''
            ),
        ),
        ),
    
    )
    ) );