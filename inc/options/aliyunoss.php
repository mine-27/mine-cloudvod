<?php
$ajaxUrl = admin_url("admin-ajax.php");
$mcv_alioss_bucketsList = array('' => __('Please sync Bukcets List first', 'mine-cloudvod'));//'请先同步转码模板');
if($tctc = get_option('mcv_alioss_bucketsList')){
    $mcv_alioss_bucketsList = array();
    foreach($tctc as $tc){
        $mcv_alioss_bucketsList[$tc[0]] =  $tc[0];
    }
}

MCSF::createSection( $prefix, array(
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
        'after'       => '<p><a href="javascript:mcv_sync_alioss_buckets();">'.__('Sync Buckets List', 'mine-cloudvod').'</a></p>
            <script>function mcv_sync_alioss_buckets(){
                var index = layer.load(1, {
                    shade: [0.3,"#fff"] 
                });
                jQuery(function(){
                    jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_alioss_buckets", nonce: "'. wp_create_nonce('mcv_asyc_alioss_buckets') .'"}, function(data){
                        layer.close(index);
                        if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                        if(data.status=="1"){
                            layer.msg("'.__('Successfully synchronized buckets', 'mine-cloudvod').'");
                            var tcoptions = "", tdata = data.data;
                            for(var i=0; i<tdata.length; i++){
                            tcoptions += "<option value=\""+tdata[i][0]+"\">"+tdata[i][0]+"</option>";
                            }
                            jQuery("select[data-depend-id=buckets]").html(tcoptions);
                        }
                    }, "json");
                });
            }</script>
        ',//同步Bucket列表,
        'options'     => $mcv_alioss_bucketsList,
        'default'     => ''
        ),
    ),
    ),

)
) );