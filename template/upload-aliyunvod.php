<?php 
/**
 * template name: 前台上传到阿里云视频点播
 */
global $current_user;
//check whether the user role is the author
if(!in_array('author', $current_user->roles) && !in_array('administrator', $current_user->roles)) exit('No permission');


wp_enqueue_style(['common','media']);
wp_enqueue_script('jquery');
add_action( 'wp_enqueue_scripts',function(){
    global $current_user;
    $uid = $current_user->ID;
    $ajaxUrl = admin_url("admin-ajax.php");
    wp_add_inline_style( 'media', ".hide-if-js{display:none;}" );
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
                var fileObj = obj[i];		//单个文件
                fileObj["mcv_fileid"] = mcv_fileid++;
                var name = fileObj.name;	//文件名
                var size = fileObj.size;	//文件大小
                var type = fileType(name);	//文件类型，获取的是文件的后缀
                var itemArr = [fileObj,name,size,type];	//文件，文件名，文件大小，文件类型
                fileList.push(itemArr);
            }
            createList(fileList);
            mcv_alivod_upload_start(fileList);
        }
        function createList(fileList){
            for( var i=0;i<fileList.length;i++ ){
                var fileData = fileList[i];
                var objData = fileData[0];//文件
                var name = fileData[1];//文件名
                var size = fileData[2];//文件大小
                var type = fileData[3];//文件类型
                var fid = objData["mcv_fileid"];//文件编号
                var oTr = jQuery("<div class=\"media-item child-of-"+fid+"\"></div>");
                var str = "<div class=\"progress\"><div class=\"percent\">等待上传</div><div class=\"bar\" style=\"width: 0px;\"></div></div>";
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
                    layer.msg("开始上传...");
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
                    jQuery(".child-of-"+uploadInfo.file.mcv_fileid+"").html("<img class=\"pinkynail\" src=\"'.home_url().'/wp-includes/images/media/video.png\"><a class=\"edit-attachment\" href=\"javascript:alert(\'"+uploadInfo.videoId+"\')\">AliyunVod VideoId: "+uploadInfo.videoId+"</a><div class=\"filename new\"><span class=\"title\">"+uploadInfo.file.name+"</span></div>");
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
    });
    ');
});
wp_head();


?>

<form enctype="multipart/form-data" method="post" action="" class="media-upload-form type-form validate" id="image-form">
        <h3 class="media-title">从您的计算机上传视频文件到阿里云视频点播</h3>
        <div id="media-upload-notice">
        </div>
        <div id="media-upload-error">
        </div>

        <div id="plupload-upload-ui" class="hide-if-no-js drag-drop">
            <div id="drag-drop-area" style="position: relative;">
                <div class="drag-drop-inside">
                    <p class="drag-drop-info">拖文件至此可上传</p>
                    <p>或</p>
                    <p class="drag-drop-buttons"><input id="plupload-browse-button" type="button" value="选择文件" class="button" style="position: relative; z-index: 1;"><input type="file" multiple id="plupload-browse-file" style="display:none" /></p>
                </div>
            </div>
			<p class="upload-flash-bypass"><b>上传期间，请勿关闭本页面</b>。</p>
        </div>

        <div id="html-upload-ui" class="hide-if-js">
            <p id="async-upload-wrap">
                <label class="screen-reader-text" for="async-upload">上传</label>
                <input type="file" name="async-upload" id="async-upload">
                <input type="submit" name="html-upload" id="html-upload" class="button button-primary" value="上传">		<a href="#" onclick="try{top.tb_remove();}catch(e){}; return false;">取消</a>
            </p>
            <div class="clear"></div>
            
        </div>
    
        <div id="media-items">
        </div>

    </form>
<?php
wp_footer();