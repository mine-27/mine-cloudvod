<?php
	// 如果使用前台上传视频，则需要处理相应模板和插件
	// /wp-content/plugins/mas-wp-job-manager-company/templates/company-submit.php	36行 删除 esc_html
	// /wp-content/themes/workscout/company-submit.php	40行 删除 esc_html
namespace MineCloudvod\Integrations\WPJobManager;

class WPJobManager
{
    public function __construct()
    {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if (is_plugin_active("wp-job-manager/wp-job-manager.php")) {

			$this->job_upload_alivod();
            add_filter('the_company_video_embed', [$this, 'the_company_video_embed'], 10, 2);
            add_filter('the_candidate_video_embed', [$this, 'the_candidate_video_embed'], 10, 2);
            
            add_filter('submit_company_form_fields', [$this, 'replaceVideoCompany'], 10, 1);//company_video
            add_filter('submit_resume_form_fields', [$this, 'replaceVideoResume'], 10, 1);//candidate_video

		}
    }

	public function the_candidate_video_embed($output, $post){
		if(!$output){
			$video       = get_the_candidate_video( $post );
			return $this->renderVideo($video);
		}
	}

	public function the_company_video_embed($output, $post){
		if(!$output){
			$video       = mas_wpjmc_get_the_meta_data( '_company_video' );
			return $this->renderVideo($video);
		}
	}

    public function renderVideo($video)
    {
		$mcvBlock = "";
		if(strpos($video, "[mine_cloudvod id=") !== false){
			$mcvBlock = do_shortcode($video);
		}
		else{
			$block = [
				"blockName" => "mine-cloudvod/aliyun-vod",
				"attrs"		=> [
					"videoId" 	=> $video,
					"thumbnail" => "",
					"slide"		=> true,
					"height"	=> "auto"
				],
				"innerBlocks" 	=> [],
				"innerHTML"		=> "",
				"innerContent"	=> []
			];
			$mcvBlock = render_block($block);
		}
		return $mcvBlock;
    }
	//增加前台上传按钮
    public function replaceVideoResume($fields){
        if(isset($fields['resume_fields']['candidate_video'])){
            $fields['resume_fields']['candidate_video']['label'] .= '<input id="plupload-browse-button" class="button" type="button" value="'. __( 'Select Video File', 'mine-cloudvod' ) .'" class="button" style="position: relative; z-index: 1;" onclick="jQuery(\'#plupload-browse-file\').click()"><input type="file"  id="plupload-browse-file" style="display:none" onchange="mcv_alivod_upload_pre(this.files)" /><div class="mcv-alivod-progress" style="display:none;"><div class="progress" style="height:22px;margin:16px 0;width:100%;line-height:2em;padding:0;overflow:hidden;border-radius:22px;background:#dcdcde;box-shadow:inset 0 1px 2px rgba(0,0,0,.1)"><div class="percent" style="z-index:10;position:relative;width:100%;padding:0;color:#fff;text-align:center;line-height:22px;font-weight:400;text-shadow:0 1px 2px rgba(0,0,0,.2)">等待上传</div><div class="bar" style="z-index:9;width:0;height:22px;margin-top:-22px;border-radius:22px;background-color:#2271b1;box-shadow:inset 0 0 2px rgba(0,0,0,.3)"></div></div></div>';
        }
        return $fields;
    }
	//增加前台上传按钮
    public function replaceVideoCompany($fields){
        if(isset($fields['company_fields']['company_video'])){
            $fields['company_fields']['company_video']['label'] .= '<input id="plupload-browse-button" class="button" type="button" value="'. __( 'Select Video File', 'mine-cloudvod' ) .'" class="button" style="position: relative; z-index: 1;" onclick="jQuery(\'#plupload-browse-file\').click()"><input type="file"  id="plupload-browse-file" style="display:none" onchange="mcv_alivod_upload_pre(this.files)" /><div class="mcv-alivod-progress" style="display:none;"><div class="progress" style="height:22px;margin:16px 0;width:100%;line-height:2em;padding:0;overflow:hidden;border-radius:22px;background:#dcdcde;box-shadow:inset 0 1px 2px rgba(0,0,0,.1)"><div class="percent" style="z-index:10;position:relative;width:100%;padding:0;color:#fff;text-align:center;line-height:22px;font-weight:400;text-shadow:0 1px 2px rgba(0,0,0,.2)">等待上传</div><div class="bar" style="z-index:9;width:0;height:22px;margin-top:-22px;border-radius:22px;background-color:#2271b1;box-shadow:inset 0 0 2px rgba(0,0,0,.3)"></div></div></div>';
        }
        return $fields;
    }
	//前台上传按钮功能实现
	public function job_upload_alivod(){
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
				var oFileBox = jQuery("#plupload-upload-ui");
				var oFileInput = jQuery("#plupload-browse-file");
				var oFileSpan = jQuery("#drag-drop-area");
				var oFileList = jQuery("#media-items");
				var uploader = null;
				var mcv_fileid = 0;
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
					mcv_alivod_upload_start(fileList);
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
							console.log("addFileSuccess: " + uploadInfo.file.name);
							jQuery("#plupload-browse-button").hide();
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
							jQuery("#plupload-browse-button").hide();
							jQuery(".mcv-alivod-progress").show();
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
						onUploadSucceed: function (uploadInfo) {console.log(uploadInfo);
							jQuery("#candidate_video, #company_video").val(uploadInfo.videoId);
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
							jQuery(".mcv-alivod-progress .progress .percent").html(progressPercent+"%");
							jQuery(".mcv-alivod-progress .progress .bar").css("width", progressPercent+"%");
						},
						// 上传凭证超时
						onUploadTokenExpired: function (uploadInfo) {
							let refreshUrl = "'.$ajaxUrl.'";
							jQuery.post(refreshUrl,{"action":"mcv_alivod_upload","op":"refreshuvinfo","endpoint":this.region,"nonce":"'.$wp_create_nonce.'","FileName":uploadInfo.file.name,"VideoId":uploadInfo.videoId}, function (data) {
								var uploadAuth = data.UploadAuth;
								uploader.resumeUploadWithAuth(uploadAuth);
								console.log("upload expired and resume upload with uploadauth " + uploadAuth);
							}, "json");
						},
						onUploadEnd: function (uploadInfo) {
							console.log("onUploadEnd: uploaded all the files")
						}
					})
					return uploader;
				}
				function fileType(name){
					var nameArr = name.split(".");
					return nameArr[nameArr.length-1].toLowerCase();
				}
			');
		});
	}
}
