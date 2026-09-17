<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

if ( ! defined( 'ABSPATH' ) ) exit;
$ajaxUrl = admin_url("admin-ajax.php");
$mcv_alioss_bucketsList = array('' => __('Please sync Bukcets List first', 'mine-cloudvod'));//'请先同步转码模板');
if($tctc = get_option('mcv_alioss_bucketsList')){
    $mcv_alioss_bucketsList = array();
    foreach($tctc as $tc){
        $mcv_alioss_bucketsList[$tc[0]] =  $tc[0];
    }
}

\MCSF::createSection( $prefix, array(
'parent'     => 'aliyunvod',
'title' => __('Aliyun OSS', 'mine-cloudvod'),//'阿里云OSS',
'icon'   => 'far fa-file-video',
'fields' => array(
    array(
    'type'    => 'submessage',
    'style'   => 'warning',
    'content' => __('By default, Alibaba Cloud OSS is charged after the end of the hour, and it can also be found on <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">Alibaba Cloud OSS Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'),//'<p>阿里云视频点播默认是时结后收费模式，也可以在 <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">阿里云平台</a> 购买相应的资源包消费</p>',
    ),
    array(
    'id'        => 'alivod',
    'type'      => 'fieldset',
    'title'     => __('Aliyun OSS', 'mine-cloudvod'),//'阿里云对象存储 OSS',
    'fields'    => array(
        array(
        'id'          => 'buckets',
        'type'        => 'select',
        'title'       => __('Bucket', 'mine-cloudvod'),//'存储桶',
        'after'       => '<p><a href="javascript:mcv_sync_alioss_buckets();">'.__('Sync Buckets List', 'mine-cloudvod').'</a></p>',//同步Bucket列表,
        'options'     => $mcv_alioss_bucketsList,
        'default'     => ''
        ),
    ),
    ),

)
) );