<?php
$ajaxUrl = admin_url("admin-ajax.php");
$mcv_alioss_bucketsList = array('' => __('Please sync Bukcets List first', 'mine-cloudvod'));//'请先同步转码模板');
if($tctc = get_option('mcv_tccos_bucketsList')){
    $mcv_alioss_bucketsList = array();
    foreach($tctc as $tc){
        $mcv_alioss_bucketsList[$tc[0]] =  $tc[0];
    }
}

  MCSF::createSection( $prefix, array(
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
            'after'       => '<p><a href="javascript:mcv_sync_tccos_buckets();">'.__('Sync Buckets List', 'mine-cloudvod').'</a></p>
                <script>function mcv_sync_tccos_buckets(){
                    var index = layer.load(1, {
                        shade: [0.3,"#fff"] 
                    });
                    jQuery(function(){
                        jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_tccos_buckets", nonce: "'. wp_create_nonce('mcv_asyc_tccos_buckets') .'"}, function(data){
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