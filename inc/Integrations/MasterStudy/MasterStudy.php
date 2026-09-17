<?php
namespace MineCloudvod\Integrations\MasterStudy;

class MasterStudy{
    public function __construct(){
		$theme = wp_get_theme();
        if (strtolower($theme->get('TextDomain')) == 'masterstudy' || strtolower($theme->get('Template')) == 'masterstudy') {
			$this->lesson_upload_alivod();
		}
        //增加前台上传按钮
        add_action('stm_lms_lesson_manage_settings', [$this, 'lesson_manage_settings']);

        add_filter( 'stm_wpcfto_fields', array( $this, 'add_lesson_type_admin' ), 100, 1 );

        // 点播视频处理
        add_filter('stm_lms_lesson_content', [$this, 'course_item_content'], 10, 3);

        // 显示点播视频
        add_filter('stm_lms_show_item_content', [$this, 'show_item_content'], 10, 3);


        // 保存视频id
        add_action('stm_lms_save_lesson_after_validation', [$this, 'update_mcv_alivod'], 10, 2 );
        //add_action('save_post', [$this, 'update_admin_mcv_alivod'], 10, 2 );
    }

	
    public static function add_lesson_type_admin( $fields )
    {

        $fields[ 'stm_lesson_settings' ][ 'section_lesson_settings' ][ 'fields' ][ 'mcv_alivod_vid' ] = array(
            'type' => 'text',
            'label' => esc_html__('ApsaraVideo VOD', 'mine-cloudvod'),
            'value' => '',
            'dependency' => array(
                'key' => 'type',
                'value' => 'video'
            ),
        );

        return $fields;
    }
	
    public function lesson_manage_settings(){ 
		?>

        <div v-if="fields['type'] === 'video'">
            <div class="form-group">
                <label>
                    <h4><?php esc_html_e( 'Upload video to ApsaraVideo VOD', 'mine-cloudvod' ); ?></h4>
                </label>
				<input id="plupload-browse-button" type="button" value="<?php esc_html_e( 'Select Video File', 'mine-cloudvod' ); ?>" class="button" style="position: relative; z-index: 1;" onclick="jQuery('#plupload-browse-file').click()">
				<input type="file"  id="plupload-browse-file" style="display:none" onchange="mcv_alivod_upload_pre(this.files)" />
				<div class="mcv-alivod-progress" style="display:none;"><div class="progress" style="height:22px;margin:16px 0;width:100%;line-height:2em;padding:0;overflow:hidden;border-radius:22px;background:#dcdcde;box-shadow:inset 0 1px 2px rgba(0,0,0,.1)"><div class="percent" style="z-index:10;position:relative;width:100%;padding:0;color:#fff;text-align:center;line-height:22px;font-weight:400;text-shadow:0 1px 2px rgba(0,0,0,.2)">等待上传</div><div class="bar" style="z-index:9;width:0;height:22px;margin-top:-22px;border-radius:22px;background-color:#2271b1;box-shadow:inset 0 0 2px rgba(0,0,0,.3)"></div></div></div>

                <div class="stm-lms-admin-checkbox-wrapper">
					<input type="text" class="form-control" v-model="fields['mcv_alivod_vid']" id="mcv_alivod_vid" />
                </div>
            </div>

        </div>

    <?php }

	public function lesson_upload_alivod(){
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
							jQuery("#mcv_alivod_vid").val(uploadInfo.videoId);
							document.getElementById("mcv_alivod_vid").dispatchEvent(new Event("input"));
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
			');
		});
	}

    public static function show_item_content($show, $post_id, $item_id)
    {

        if (self::is_mcv_alivod($item_id)) return true;

        return $show;
    }

    public static function course_item_content($content, $post_id, $item_id)
    {
        if (self::is_mcv_alivod($item_id)) {
            $mcv_alivod_vid = get_post_meta($item_id, 'mcv_alivod_vid', true);
			$mcvBlock = "";
			if(strpos($mcv_alivod_vid, "[mine_cloudvod id=") !== false){
				$mcvBlock = do_shortcode($mcv_alivod_vid);
			}
			else{
				$block = [
					"blockName" => "mine-cloudvod/aliyun-vod",
					"attrs"		=> [
						"videoId" 	=> $mcv_alivod_vid,
						"thumbnail" => "",
						"slide"		=> true
					],
					"innerBlocks" 	=> [],
					"innerHTML"		=> "",
					"innerContent"	=> []
				];
				$mcvBlock = render_block($block);
			}
			
			$content = $mcvBlock . $content;
        }
        return $content;

    }

    public static function is_mcv_alivod($post_id)
    {

        $type = get_post_meta($post_id, 'type', true);
		$mcv_alivod_vid = get_post_meta($post_id, 'mcv_alivod_vid', true);

        return $type === 'video' && $mcv_alivod_vid;

    }
	
    public function update_mcv_alivod( $post_id, $post_data ){
        if (isset($post_data['mcv_alivod_vid'])) {
            $value = $post_data['mcv_alivod_vid'];
            update_post_meta($post_id, 'mcv_alivod_vid', $value);
        }
    }

}
