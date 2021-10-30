<?php
function wpPrepareAttachmentForJs($response, $attachment, $meta){
	if(isset($meta['mode'])){
		$response['mode'] = $meta['mode'];
        $response['filename'] = $meta['mode'].': '.$response['filename'];
	}
	return $response;
}
function mcv_tcvod_upload(){
    include MINECLOUDVOD_PATH.'/inc/MineTcVodClientUploader.php';
}
function mcv_alivod_upload(){
    include MINECLOUDVOD_PATH.'/inc/MineAliVodClientUploader.php';
}
function mcv_admin_scripts($hook){
    global $current_user;
    $uid = $current_user->ID;
    $ajaxUrl = admin_url("admin-ajax.php");
    if($hook == 'media_page_mine-tcvod'){
        wp_enqueue_script('mcv_layer', MINECLOUDVOD_URL.'/static/layer/layer.js',  array(), MINECLOUDVOD_VERSION , true );
        wp_enqueue_script('mcv_tcvod_sdk', 'https://cdn-go.cn/cdn/vod-js-sdk-v6/latest/vod-js-sdk-v6.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_add_inline_script('mcv_tcvod_sdk','
        jQuery(function(){
            var oFileBox = jQuery("#plupload-upload-ui");
            var oFileInput = jQuery("#plupload-browse-file");
            var oFileSpan = jQuery("#drag-drop-area");
            var oFileList = jQuery("#media-items");
            jQuery("#plupload-browse-button").click(function(){
                oFileInput.click();
            });
            oFileSpan.on("dragenter",function(){
                oFileBox.addClass("drag-over");
            });
            oFileSpan.on("dragover",function(){
                return false;
            });
            oFileSpan.on("dragleave",function(){
                oFileBox.removeClass("drag-over");
            });
            oFileSpan.on("drop",function(ev){
                var fs = ev.originalEvent.dataTransfer.files;
                mcv_tcvod_upload_pre(fs);
                return false;
            });
            oFileInput.on("change",function(){
                mcv_tcvod_upload_pre(this.files);
            })
            var mcv_fileid = 0;
            function mcv_tcvod_upload_pre(obj){
                if( obj.length<1 ){
                    return false;
                }
                var fileList = [];
                for( var i=0;i<obj.length;i++ ){
                    var fileObj = obj[i];
                    var name = fileObj.name;
                    var size = fileObj.size;
                    var type = fileType(name);
                    var itemArr = [fileObj,name,size,type,mcv_fileid,window.videoWidth];
                    mcv_fileid++;
                    fileList.push(itemArr);
                }
                createList(fileList);
                mcv_tcvod_upload_start(fileList);
            }
            function createList(fileList){
                for( var i=0;i<fileList.length;i++ ){
                    var fileData = fileList[i];
                    var objData = fileData[0];
                    var name = fileData[1];
                    var size = fileData[2];
                    var type = fileData[3];
                    var fid = fileData[4];
                    var oTr = jQuery("<div class=\"media-item child-of-"+fid+"\"></div>");
                    var str = "<div class=\"progress\"><div class=\"percent\">'.__('Waiting for upload', 'mine-cloudvod').'</div><div class=\"bar\" style=\"width: 0px;\"></div></div>";
                    str += "<div class=\"filename original\">"+ name +"</div>";
                    oTr.html(str);
                    oTr.appendTo( oFileList );
                }
            }
            function mcv_tcvod_upload_start(fileList){
                var mcv_tcvod = mcv_createTcVod();
                for( var i=0;i<fileList.length;i++ ){
                mcv_createTcUploader(mcv_tcvod, fileList[i]);
                }
            }
            function mcv_getSignature(){
                var sign = "";
                jQuery.ajax({
                type : "post",
                url : "'.$ajaxUrl.'",  
                data : {action:"mcv_uploadsign",nonce:"'.wp_create_nonce("mcv_uploadsign").'"},  
                async : false,  
                dataType: "json",
                success : function(data){
                    if(data.status==0){
                    layer.msg("'.__('upload failed', 'mine-cloudvod').' " + data.msg);
                    return false;
                    }
                    else sign = data.data.usign;
                }
                });
                return sign;
            }
            function mcv_createTcVod(){
                const tcVod = new TcVod.default({
                getSignature: mcv_getSignature
                });
                return tcVod;
            }
            function mcv_createTcUploader(tcVod, mediaFile){
                var uploader = tcVod.upload({
                mediaFile: mediaFile[0],
                });
                uploader.on("media_progress", function(info) {
                    jQuery(".child-of-"+mediaFile[4]+" .progress .percent").html(parseInt(info.percent*100)+"%");
                    jQuery(".child-of-"+mediaFile[4]+" .progress .bar").css("width", parseInt(info.percent*100)+"%");
                });
                uploader.done().then(function (doneResult) {
                    jQuery(".child-of-"+mediaFile[4]+" .progress .percent").html("'.__('Processing...', 'mine-cloudvod').'");
                    jQuery.post("'.$ajaxUrl.'",{"action":"mcv_tcvod_uploaded","fileId":doneResult.fileId,"mediaUrl":doneResult.video.url,"nonce":"'.wp_create_nonce("mcv_tcvod_uploaded").'","fileName":mediaFile[1]}, function(data){
                        if(data.status == "0"){
                            layer.msg(data.msg);
                        }
                        else{
                            jQuery(".child-of-"+mediaFile[4]+"").html("<img class=\"pinkynail\" src=\"'. home_url() .'/wp-includes/images/media/video.png\"><a class=\"edit-attachment\" href=\"'. admin_url("post.php?action=edit&post=") .'"+data.data.mid+"\" target=\"_blank\">'.__('Edit').'</a><div class=\"filename new\"><span class=\"title\">"+mediaFile[1]+"</span></div>");
                        }
                    },"json");
                }).catch(function (err) {
                    layer.msg(err);
                });
            }
            function fileType(name){
                var nameArr = name.split(".");
                return nameArr[nameArr.length-1].toLowerCase();
            }
            jQuery("#mcv_add_fileid").click(function(){
                var fileid = jQuery("#mcv_tc_fileid").val();
                jQuery.post("'.$ajaxUrl.'",{"action":"mcv_tcvod_uploaded","fileId":fileid,"mediaUrl":"","nonce":"'.wp_create_nonce("mcv_tcvod_uploaded").'","fileName":""}, function(data){
                    if(data.status == "0"){
                        layer.msg(data.msg);
                    }
                    else{
                        var oTr = jQuery("<div class=\"media-item child-of-"+data.data.mid+"\"></div>");
                        var str = "<img class=\"pinkynail\" src=\"'. home_url() .'/wp-includes/images/media/video.png\"><a class=\"edit-attachment\" href=\"'. admin_url("post.php?action=edit&post=") .'"+data.data.mid+"\" target=\"_blank\">'.__('Edit').'</a><div class=\"filename new\"><span class=\"title\">"+fileid+"</span></div>";
                        oTr.html(str);
                        oTr.appendTo( oFileList );
                    }
                },"json");
                return false;
            });
        });');
    }
    if($hook == 'media_page_mine-alivod'){
        $wp_create_nonce		= wp_create_nonce('mcv-aliyunvod-'.$uid);
        wp_enqueue_script('mcv_layer', MINECLOUDVOD_URL.'/static/layer/layer.js',  array(), MINECLOUDVOD_VERSION , true );
        wp_enqueue_script('mcv_alivod_sdk', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/aliyun-upload-sdk-1.5.0.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_enqueue_script('mcv_alivod_es6-promise', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/lib/es6-promise.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_enqueue_script('mcv_alivod_oss', MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/lib/aliyun-oss-sdk-5.3.1.min.js',  array(), MINECLOUDVOD_VERSION , true);
        wp_add_inline_script('mcv_alivod_oss','
        jQuery(function(){
            var oFileBox = jQuery("#plupload-upload-ui");
            var oFileInput = jQuery("#plupload-browse-file");
            var oFileSpan = jQuery("#drag-drop-area");
            var oFileList = jQuery("#media-items");
            var uploader = null;
            var mcv_fileid = 0;
            jQuery("#plupload-browse-button").click(function(){
                oFileInput.click();
            });
            oFileSpan.on("dragenter",function(){
                oFileBox.addClass("drag-over");
            });
            oFileSpan.on("dragover",function(){
                return false;
            });
            oFileSpan.on("dragleave",function(){
                oFileBox.removeClass("drag-over");
            });
            oFileSpan.on("drop",function(ev){
                var fs = ev.originalEvent.dataTransfer.files;
                mcv_alivod_upload_pre(fs);
                return false;
            });
            oFileInput.on("change",function(){
                mcv_alivod_upload_pre(this.files);
            });
            function mcv_alivod_upload_pre(obj){
                if( obj.length<1 ){
                    return false;
                }
                var fileList = [];
                for( var i=0;i<obj.length;i++ ){
                    var fileObj = obj[i];
                    fileObj["mcv_fileid"] = mcv_fileid++;
                    var name = fileObj.name;
                    var size = fileObj.size;
                    var type = fileType(name);
                    var itemArr = [fileObj,name,size,type];
                    fileList.push(itemArr);
                }
                createList(fileList);
                mcv_alivod_upload_start(fileList);
            }
            function createList(fileList){
                for( var i=0;i<fileList.length;i++ ){
                    var fileData = fileList[i];
                    var objData = fileData[0];
                    var name = fileData[1];
                    var size = fileData[2];
                    var type = fileData[3];
                    var fid = objData["mcv_fileid"];
                    var oTr = jQuery("<div class=\"media-item child-of-"+fid+"\"></div>");
                    var str = "<div class=\"progress\"><div class=\"percent\">'.__('Waiting for upload', 'mine-cloudvod').'</div><div class=\"bar\" style=\"width: 0px;\"></div></div>";
                    str += "<div class=\"filename original\">"+ name +"</div>";
                    oTr.html(str);
                    oTr.appendTo( oFileList );
                }
            }
            function mcv_alivod_upload_start(fileList){
                uploader = mcv_aliyun_createUploader();
                console.log(uploader);
                for( var i=0;i<fileList.length;i++ ){
                    var userData = "{\"Vod\":{}}";
                    uploader.addFile(fileList[i][0], null, null, null, userData);
                }
                uploader.startUpload();
            }
            function mcv_aliyun_createUploader () {
                var uploader = new AliyunUpload.Vod({
                    timeout: 60000,
                    partSize: 1024*1024*2,
                    parallel: 5,
                    retryCount: 3,
                    retryDuration: 2,
                    region: "'.MINECLOUDVOD_SETTINGS['alivod']['endpoint'].'",
                    userId: "'.MINECLOUDVOD_SETTINGS['alivod']['userId'].'",
                    // 添加文件成功
                    addFileSuccess: function (uploadInfo) {
                        console.log("addFileSuccess: " + uploadInfo.file.name)
                    },
                    // 开始上传
                    onUploadstarted: function (uploadInfo) {
                    if (!uploadInfo.videoId) {
                        var createUrl = "'.$ajaxUrl.'";
                        jQuery.post(createUrl,{"action":"mcv_alivod_upload","op":"getuvinfo","endpoint":this.region,"nonce":"'.$wp_create_nonce.'","FileName":uploadInfo.file.name,"FileSize":uploadInfo.file.size}, function (data) {
                            var uploadAuth = data.UploadAuth
                            var uploadAddress = data.UploadAddress
                            var videoId = data.VideoId
                            uploader.setUploadAuthAndAddress(uploadInfo, uploadAuth, uploadAddress,videoId)
                        }, "json");
                        layer.msg("'.__('Start upload', 'mine-cloudvod').'");
                        console.log("onUploadStarted:" + uploadInfo.file.name + ", endpoint:" + uploadInfo.endpoint + ", bucket:" + uploadInfo.bucket + ", object:" + uploadInfo.object);
                    } else {
                        var refreshUrl = "'.$ajaxUrl.'";
                        jQuery.post(refreshUrl,{"action":"mcv_alivod_upload","op":"refreshuvinfo","endpoint":this.region,"nonce":"'.$wp_create_nonce.'","FileName":uploadInfo.file.name,"VideoId":uploadInfo.videoId}, function (data) {
                            var uploadAuth = data.UploadAuth
                            var uploadAddress = data.UploadAddress
                            var videoId = data.VideoId
                            uploader.setUploadAuthAndAddress(uploadInfo, uploadAuth, uploadAddress,videoId);
                        }, "json")
                    }
                    },
                    // 文件上传成功
                    onUploadSucceed: function (uploadInfo) {
                        jQuery(".child-of-"+uploadInfo.file.mcv_fileid+" .progress .percent").html("处理中...");
                        jQuery.post("'.$ajaxUrl.'",{"action":"mcv_alivod_upload","op":"uvsucceed","endpoint":this.region,"nonce":"'.$wp_create_nonce.'","FileName":uploadInfo.file.name,"VideoId":uploadInfo.videoId}, function(data){
                            jQuery(".child-of-"+uploadInfo.file.mcv_fileid+"").html("<img class=\"pinkynail\" src=\"'.home_url().'/wp-includes/images/media/video.png\"><a class=\"edit-attachment\" href=\"'.admin_url("post.php?action=edit&post=").'"+data.mid+"\" target=\"_blank\">'.__('Edit').'</a><div class=\"filename new\"><span class=\"title\">"+uploadInfo.file.name+"</span></div>");
                        },"json");
                    },
                    // 文件上传失败
                    onUploadFailed: function (uploadInfo, code, message) {
                        console.log("onUploadFailed: file:" + uploadInfo.file.name + ",code:" + code + ", message:" + message)
                        layer.msg("Upload Failed!")
                    },
                    // 取消文件上传
                    onUploadCanceled: function (uploadInfo, code, message) {
                        console.log("Canceled file: " + uploadInfo.file.name + ", code: " + code + ", message:" + message)
                        layer.msg("Upload Canceled!")
                    },
                    // 文件上传进度，单位：字节, 可以在这个函数中拿到上传进度并显示在页面上
                    onUploadProgress: function (uploadInfo, totalSize, progress) {
                        var progressPercent = Math.ceil(progress * 100)
                        jQuery(".child-of-"+uploadInfo.file.mcv_fileid+" .progress .percent").html(progressPercent+"%");
                        jQuery(".child-of-"+uploadInfo.file.mcv_fileid+" .progress .bar").css("width", progressPercent+"%");
                    },
                    // 上传凭证超时
                    onUploadTokenExpired: function (uploadInfo) {
                        //layer.msg("UploadTokenExpired!");
                        let refreshUrl = "'.$ajaxUrl.'";
                        jQuery.post(refreshUrl,{"action":"mcv_alivod_upload","op":"refreshuvinfo","endpoint":this.region,"nonce":"'.$wp_create_nonce.'","FileName":uploadInfo.file.name,"VideoId":uploadInfo.videoId}, function (data) {
                            var uploadAuth = data.UploadAuth;
                            uploader.resumeUploadWithAuth(uploadAuth);
                            console.log("upload expired and resume upload with uploadauth " + uploadAuth);
                        }, "json");
                    },
                    onUploadEnd: function (uploadInfo) {
                        //layer.msg("uploaded all the files!")
                        console.log("onUploadEnd: uploaded all the files")
                    }
                })
                return uploader;
            }
            
            function fileType(name){
                var nameArr = name.split(".");
                return nameArr[nameArr.length-1].toLowerCase();
            }
            jQuery("#mcv_add_videoid").click(function(){
                var fileid = jQuery("#mcv_ali_videoid").val();
                jQuery.post("'.$ajaxUrl.'",{"action":"mcv_alivod_upload","op":"uvsucceed","VideoId":fileid,"nonce":"'.$wp_create_nonce.'","FileName":""}, function(data){
                    var oTr = jQuery("<div class=\"media-item child-of-"+data.mid+"\"></div>");
                    var str = "<img class=\"pinkynail\" src=\"'. home_url() .'/wp-includes/images/media/video.png\"><a class=\"edit-attachment\" href=\"'. admin_url("post.php?action=edit&post=") .'"+data.mid+"\" target=\"_blank\">'.__('Edit').'</a><div class=\"filename new\"><span class=\"title\">"+fileid+"</span></div>";
                    oTr.html(str);
                    oTr.appendTo( oFileList );
                },"json");
                return false;
            });
        });
        ');
    }
    if($hook == 'toplevel_page_mine-cloudvod'){
        wp_enqueue_script('mcv_layer', MINECLOUDVOD_URL.'/static/layer/layer.js',  array(), MINECLOUDVOD_VERSION , true );
        wp_add_inline_script('mcv_layer','
        function mcv_sync_endtime(){
            var index = layer.load(1, {
                shade: [0.3,"#fff"] 
            });
            jQuery(function(){
            jQuery.post("'.$ajaxUrl.'", {action: "mcv_sync_endtime", nonce: "'. wp_create_nonce('mcv_sync_endtime') .'"}, function(data){
                layer.close(index);
                if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                if(data.status=="1"){
                    layer.msg("'.__('Synchronize time successfully', 'mine-cloudvod').'");
                    jQuery("input[data-depend-id=endtime]").val(data.data.endtime);
                }
            }, "json");
            });
        }
        function mcv_sync_transcode(){
            var index = layer.load(1, {
                shade: [0.3,"#fff"] 
            });
            jQuery(function(){
                jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_transcode", nonce: "'. wp_create_nonce('mcv_asyc_transcode') .'"}, function(data){
                    layer.close(index);
                    if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                    if(data.status=="1"){
                        layer.msg("'.__('Successfully synchronized task flow list', 'mine-cloudvod').'");
                        var tcoptions = "", tdata = data.data;
                        for(var i=0; i<tdata.length; i++){
                        tcoptions += "<option value=\""+tdata[i][0]+"\">"+tdata[i][1]+" - "+tdata[i][0]+"</option>";
                        }
                        jQuery("select[data-depend-id=transcode]").html(tcoptions);
                    }
                }, "json");
            });
        }
        function mcv_sync_ali_transcode(){
            var index = layer.load(1, {
                shade: [0.3,"#fff"] 
            });
            jQuery(function(){
                jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_ali_transcode", nonce: "'. wp_create_nonce('mcv_asyc_ali_transcode') .'"}, function(data){
                    layer.close(index);
                    if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                    if(data.status=="1"){
                        layer.msg("'.__('Successfully synchronized transcoding template', 'mine-cloudvod').'");
                        var tcoptions = "", tdata = data.data;
                        for(var i=0; i<tdata.length; i++){
                        tcoptions += "<option value=\""+tdata[i][1]+"\">"+tdata[i][0]+"</option>";
                        }
                        jQuery("select[data-depend-id=transcode]").html(tcoptions);
                    }
                }, "json");
            });
        }
        function mcv_asyc_plyrconfig(){
            var index = layer.load(1, {
                shade: [0.3,"#fff"] 
            });
            jQuery(function(){
            jQuery.post("'.$ajaxUrl.'", {action: "mcv_asyc_plyrconfig", nonce: "'. wp_create_nonce('mcv_asyc_plyrconfig') .'"}, function(data){
                layer.close(index);
                if(data.status=="0")layer.msg("'.__('Get failed', 'mine-cloudvod').'"+data.msg);
                if(data.status=="1"){
                    layer.msg("'.__('Successfully synchronized the player configuration list', 'mine-cloudvod').'");
                    var tcoptions = "", tdata = data.data;
                    for(var i=0; i<tdata.length; i++){
                        tcoptions += "<option value=\""+tdata[i][0]+"\">"+tdata[i][1]+" - "+tdata[i][0]+"</option>";
                    }
                    jQuery("select[data-depend-id=plyrconfig]").html(tcoptions);
                }
            }, "json");
            });
        }
        jQuery(function(){
            jQuery("#buytimebug").click(function(){
            var index = layer.load(1, {
                shade: [0.3,"#fff"] 
            });
            jQuery.post("'.$ajaxUrl.'", {action: "mcv_buytimebug", timebug:jQuery(":radio[data-depend-id=timebug]:checked").val(), nonce: "'. wp_create_nonce('mcv_buytimebug') .'"}, function(data){
                layer.close(index);
                if(data.status=="0")alert("'.__('Get failed', 'mine-cloudvod').'");
                if(data.status=="1"){
                var tradeno = data.data.tradeno;
                console.log(tradeno);
                layer.open({
                    type: 1,
                    title: false,
                    area: ["300px", "400px"],
                    content: \'<div id="swal2-content" style="display: block;width:300px;text-align: center;"><div style=""> <h5 style="padding: 0;margin-top: 1.8em;"> <img src="'.MINECLOUDVOD_URL.'/static/img/alipay.jpg" style="display: inline-block;margin: 0;padding: 0;width: 120px;text-align: center;"> </h5> <div style="font-size: 16px;margin: 10px auto;">'.__('Alipay scan code payment', 'mine-cloudvod').' \'+data.data.payamount+\' '.__('Yuan', 'mine-cloudvod').'</div> <div align="center" class="qrcode"> <img style="width: 200px;height: 200px;" src="\'+data.data.paycode+\'" id="buytimebug_qrcode"> </div> <div style="width: 100%;background: #33465a;color: #f2f2f2;padding: 16px 0px;text-align: center;font-size: 14px;margin-top: 20px;background: #00a7ef;position: absolute;bottom:0;"> '.__('Please use Alipay <br>to scan the QR code to pay', 'mine-cloudvod').'<br> </div> </div></div>\'
                });
                }
            }, "json");
            });
        });');
    }
}
function mcv_vod_unique($filename){
	if(strpos($filename, 'action=mcv_tcvod_url&fid=') > 0){
		$upload_dir = wp_upload_dir(); 
		$filename = str_replace($upload_dir['baseurl'].'/', '', $filename);
	}
	elseif(strpos($filename, 'action=mcv_alivod_url&vid=') > 0){
		$upload_dir = wp_upload_dir(); 
		$filename = str_replace($upload_dir['baseurl'].'/', '', $filename);
	}
	return $filename;
}
function mcv_tcvod_url(){
	if(!is_user_logged_in())exit;
    global $current_user;
	if(!empty($current_user->roles) && in_array('administrator', $current_user->roles)){
		$postId = sanitize_text_field($_GET['fid']);
		if(!is_numeric($postId)){
			echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'004'));exit;
		}
		$meta = wp_get_attachment_metadata($postId);
		$mediaUrl = $meta['mediaUrl'];
        $murl = mcv_gen_tcvod_mediaUrl($mediaUrl);
        header('location:'.$murl);
	}
}

function mcv_gen_tcvod_mediaUrl($mediaUrl){
    $key = isset(MINECLOUDVOD_SETTINGS['tcvod']['fdlkey'])?MINECLOUDVOD_SETTINGS['tcvod']['fdlkey']:'';
    $dir = explode('/', $mediaUrl);
    unset($dir[count($dir)-1],$dir[0], $dir[1], $dir[2]);
    $dir = '/'.implode('/', $dir).'/';
    $time = dechex(time() + 600);
    $murl = $mediaUrl.'?t='.$time.'&rlimit=1&sign='.md5($key.$dir.$time.'1');
    return $murl;
}

function get_tcvod_piantouwei(){
    $tw = false;
    if(MINECLOUDVOD_SETTINGS['tcvodpiantou']['status'] && MINECLOUDVOD_SETTINGS['tcvodpiantou']['fileid']){
        $tw['tou'] = MINECLOUDVOD_SETTINGS['tcvodpiantou']['fileid'];
    }
    if(MINECLOUDVOD_SETTINGS['tcvodpianwei']['status'] && MINECLOUDVOD_SETTINGS['tcvodpianwei']['fileid']){
        $tw['wei'] = MINECLOUDVOD_SETTINGS['tcvodpianwei']['fileid'];
    }
    return $tw;
}

function mine_cloudvod($id)
{
    echo do_shortcode('[mine_cloudvod id=' . $id . ']');
}

if( ! function_exists( 'remove_class_filter' ) ){

	/**
	 * Remove Class Filter Without Access to Class Object
	 *
	 * In order to use the core WordPress remove_filter() on a filter added with the callback
	 * to a class, you either have to have access to that class object, or it has to be a call
	 * to a static method.  This method allows you to remove filters with a callback to a class
	 * you don't have access to.
	 *
	 * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
	 * Updated 2-27-2017 to use internal WordPress removal for 4.7+ (to prevent PHP warnings output)
	 *
	 * @param string $tag         Filter to remove
	 * @param string $class_name  Class name for the filter's callback
	 * @param string $method_name Method name for the filter's callback
	 * @param int    $priority    Priority of the filter (default 10)
	 *
	 * @return bool Whether the function is removed.
	 */
	function remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {

		global $wp_filter;

		// Check that filter actually exists first
		if ( ! isset( $wp_filter[ $tag ] ) ) {
			return FALSE;
		}

		/**
		 * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
		 * a simple array, rather it is an object that implements the ArrayAccess interface.
		 *
		 * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
		 *
		 * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
		 */
		if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) {
			// Create $fob object from filter tag, to use below
			$fob       = $wp_filter[ $tag ];
			$callbacks = &$wp_filter[ $tag ]->callbacks;
		} else {
			$callbacks = &$wp_filter[ $tag ];
		}

		// Exit if there aren't any callbacks for specified priority
		if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) {
			return FALSE;
		}

		// Loop through each filter for the specified priority, looking for our class & method
		foreach ( (array) $callbacks[ $priority ] as $filter_id => $filter ) {

			// Filter should always be an array - array( $this, 'method' ), if not goto next
			if ( ! isset( $filter['function'] ) || ! is_array( $filter['function'] ) ) {
				continue;
			}

			// If first value in array is not an object, it can't be a class
			if ( ! is_object( $filter['function'][0] ) ) {
				continue;
			}

			// Method doesn't match the one we're looking for, goto next
			if ( $filter['function'][1] !== $method_name ) {
				continue;
			}

			// Method matched, now let's check the Class
			if ( get_class( $filter['function'][0] ) === $class_name ) {

				// WordPress 4.7+ use core remove_filter() since we found the class object
				if ( isset( $fob ) ) {
					// Handles removing filter, reseting callback priority keys mid-iteration, etc.
					$fob->remove_filter( $tag, $filter['function'], $priority );

				} else {
					// Use legacy removal process (pre 4.7)
					unset( $callbacks[ $priority ][ $filter_id ] );
					// and if it was the only filter in that priority, unset that priority
					if ( empty( $callbacks[ $priority ] ) ) {
						unset( $callbacks[ $priority ] );
					}
					// and if the only filter for that tag, set the tag to an empty array
					if ( empty( $callbacks ) ) {
						$callbacks = array();
					}
					// Remove this filter from merged_filters, which specifies if filters have been sorted
					unset( $GLOBALS['merged_filters'][ $tag ] );
				}

				return TRUE;
			}
		}

		return FALSE;
	}
}

/**
 * Make sure the function does not exist before defining it
 */
if( ! function_exists( 'remove_class_action') ){

	/**
	 * Remove Class Action Without Access to Class Object
	 *
	 * In order to use the core WordPress remove_action() on an action added with the callback
	 * to a class, you either have to have access to that class object, or it has to be a call
	 * to a static method.  This method allows you to remove actions with a callback to a class
	 * you don't have access to.
	 *
	 * Works with WordPress 1.2+ (4.7+ support added 9-19-2016)
	 *
	 * @param string $tag         Action to remove
	 * @param string $class_name  Class name for the action's callback
	 * @param string $method_name Method name for the action's callback
	 * @param int    $priority    Priority of the action (default 10)
	 *
	 * @return bool               Whether the function is removed.
	 */
	function remove_class_action( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
		return remove_class_filter( $tag, $class_name, $method_name, $priority );
	}
}

/**
 * remove the space chars
 */
function mcv_trim($string){
    return str_replace(["    ", "\n", "\r", "\t"], '', $string);
}