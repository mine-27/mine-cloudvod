    <form enctype="multipart/form-data" method="post" action="" class="media-upload-form type-form validate" id="image-form">
        <h3 class="media-title">从您的计算机上传视频文件到腾讯云点播</h3>
        <div id="media-upload-notice">
            <font color="red"><?php __('The upload function here is no longer updated and is no longer recommended. It is recommended to upload videos using the block of the gutenberg editor.', 'mine-cloudvod') ?></font>
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
            <p class="upload-flash-bypass">直接上传到腾讯云点播，不消耗您服务器的流量的磁盘空间。	</p>
            <p class="upload-flash-bypass"><b>上传期间，请勿关闭本页面</b>。	上传完成后，您可以在媒体库中查看使用。</p>
            <p class="upload-flash-bypass"><label>FileId: </label><input type="text" id="mcv_tc_fileid"><button id="mcv_add_fileid">添加到媒体库</button></p>
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