<?php
namespace MineCloudvod;
use MineCloudvod\MineCloudVodAPI;

class MineCloudVod{
    private $taskId, $jobId, $sId, $sKey;
    private $_wpcvApi;
    private $videoTypes = array(
        'asf' => 'video/x-ms-asf',
        'wmv' => 'video/x-ms-wmv',
        'wmx' => 'video/x-ms-wmx',
        'wm' => 'video/x-ms-wm',
        'avi' => 'video/avi',
        'divx' => 'video/divx',
        'flv' => 'video/x-flv',
        'mov' => 'video/quicktime',
        'mpeg' => 'video/mpeg',
        'mp4' => 'video/mp4',
        'ogv' => 'video/ogg',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',
        '3gp' => 'video/3gpp',  // Can also be audio.
        '3g2' => 'video/3gpp2'
    );

    public function __construct(){
        $this->_wpcvApi     = new MineCloudVodAPI();
    }

    public function mcv_alivod_EncryptHLS($videoId, $endpoint){
        $req = array(
            'videoId' => $videoId,
            'endpoint' => $endpoint,
            'mode' => 'alivod'
        );
        $resultArray = $this->_wpcvApi->call('entranscode', $req);
    }

    public function mcv_show_cloudvod($query){
        $gquery = [];
        $url = wp_get_raw_referer();
        $parts = parse_url($url);
        isset($parts['query']) ? parse_str($parts['query'], $gquery) : '';
        $mode = isset($gquery['mcv_mode']) ? $gquery['mcv_mode'] : '';
        switch ($mode) {
            case 'alivod':
                $query['meta_query'] = [[
                    'key'   => 'mcv_mode',
                    'value' => 'alivod'
                ]];
                break;
            case 'tcvod': 
                $query['meta_query'] = [[
                    'key'   => 'mcv_mode',
                    'value' => 'tcvod'
                ]];
                break;
            default:
                $query['meta_query'] = [[
                    'key'   => 'mcv_mode',
                    'compare' => 'NOT EXISTS',
                    'value' => ''
                ]];
        }

        return $query;
    }
    /**
     * 同步到期时间
     */
    public function mcv_sync_endtime(){
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_sync_endtime')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array();
        $endtime = $this->_wpcvApi->call('endtime', $data);
        if(isset($endtime['data']['endtime'])){
            $setting = MINECLOUDVOD_SETTINGS;
            $setting['endtime'] = $endtime['data']['endtime'];
            update_option('mcv_settings', $setting);
        }
        echo json_encode($endtime);
        exit;
    }
    /**
     * 购买时长包
     */
    public function mcv_buytimebug(){
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_buytimebug')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $timebug = !empty($_POST['timebug']) ? sanitize_text_field($_POST['timebug']) : null;
        if(!is_numeric($timebug)){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('timebug' => intval($timebug));
        $buytime = $this->_wpcvApi->call('buytime', $data);
        echo json_encode($buytime);
        exit;
    }
    /**
     * 上传文件到cloudvod
     */
    public function wpHandleUpload($upload, $context){
        if (in_array($upload['type'], $this->videoTypes)) {
            if(MINECLOUDVOD_SETTINGS['mode'] == 'tcvod'){
                $req = array(
                    'mediaUrl' => $upload['url'],
                    'procedure' => MINECLOUDVOD_SETTINGS['tcvod']['transcode'],
                    'region' => MINECLOUDVOD_SETTINGS['tcvod']['region'],
                    'mode' => 'tcvod'
                );
                $resultArray = $this->_wpcvApi->call('upload', $req);
                if(isset($resultArray['status']) && $resultArray['status'] == 0){
                    unlink($upload['file']);
                    return array( 'error' => $resultArray['msg'] );
                }
                $this->taskId = $resultArray['TaskId'];
            }
            if(MINECLOUDVOD_SETTINGS['mode'] == 'alivod'){
                $req = array(
                    'mediaUrl' => $upload['url'],
                    'endpoint' => 'cn-shanghai',
                    'mode' => 'alivod'
                );
                $resultArray = $this->_wpcvApi->call('upload', $req);
                if(isset($resultArray['status']) && $resultArray['status'] == 0){
                    unlink($upload['file']);
                    return array( 'error' => $resultArray['msg'] );
                }
                $this->jobId = $resultArray["data"]["jobId"];
            }
        }
        return $upload;
    }
    /**
     * 保存taskId
     */
    public function wpGenerateAttachmentMetadata($metadata){
        if($this->taskId){
            if(MINECLOUDVOD_SETTINGS['mode'] == 'tcvod'){
                $metadata['taskId'] = $this->taskId;
                $metadata['pcfg'] = MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig'];
                $metadata['transcode'] = MINECLOUDVOD_SETTINGS['tcvod']['transcode'];
                $metadata['region'] = MINECLOUDVOD_SETTINGS['tcvod']['region'];
                $metadata['mode'] = 'tcvod';
            }
        }
            
        if($this->jobId){
            if(MINECLOUDVOD_SETTINGS['mode'] == 'alivod'){
                $metadata['jobId'] = $this->jobId;
                $metadata['endpoint'] = 'cn-shanghai';
                $metadata['mode'] = 'alivod';
            }
        }
        return $metadata;
    }
    /**
     * video short code
     */
    public function mcv_video_shortcode($what, $attr, $content, $instance){
        $width = isset($attr['width'])?$attr['width']:0;
        $height = isset($attr['height'])?$attr['height']:0;
        $mp4 = isset($attr['mp4'])?$attr['mp4']:'';
        $poster = isset($attr['poster'])?$attr['poster']:'';
        if($mp4){
            $attachment_id = attachment_url_to_postid($mp4);
            $meta = wp_get_attachment_metadata($attachment_id);
            $video = '';
            if($meta['mode']=='tcvod'){
                $video = $this->mcv_tcvod_player($attachment_id, $poster);
            }
            elseif($meta['mode'] == 'alivod'){
                $video = $this->mcv_alivod_player($meta, $attachment_id, $poster);
            }
            
            if($video){
                return $video;
            }
        }
        return '';
    }
    /**
     * video block
     */
    public function mcv_render_block_data($parsed_block, $source_block){
        if($parsed_block['blockName'] == "core/video" && isset($parsed_block['attrs']['id'])){
            $attachment_id = $parsed_block['attrs']['id'];
            $meta = wp_get_attachment_metadata($attachment_id);
            if(isset($meta['mode'])){
                $poster = '';
                preg_match('/poster="([^"]*?)"/is', $source_block['innerContent'][0], $mat);
                if(isset($mat[1]))$poster = $mat[1];
                
                if($meta['mode']=='tcvod'){
                    $video = $this->mcv_tcvod_player($attachment_id, $poster);
                }
                elseif($meta['mode'] == 'alivod'){
                    $video = $this->mcv_alivod_player($meta, $attachment_id, $poster);
                }
                
                if($video){
                    $parsed_block['innerContent'][0] = $video;
                }
            }
        }
        if ('mine-cloudvod/block-container' === $parsed_block['blockName'] && !empty($parsed_block['innerBlocks'])) {
            $parsed_block = $parsed_block['innerBlocks'][0];
        }
        if($parsed_block['blockName'] == "mine-cloudvod/aliyun-vod"){
            $aliplayer = new Aliyun\Aliplayer();
            $video = $aliplayer->mcv_block_aliplayer($parsed_block);
            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        if($parsed_block['blockName'] == "mine-cloudvod/aliplayer"){
            $aliplayer = new Aliyun\Aliplayer();
            $video = $aliplayer->mcv_block_aliplayer($parsed_block);
            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        if($parsed_block['blockName'] == "mine-cloudvod/tc-vod"){
            $tcplayer = new Qcloud\Tcplayer();
            $video = $tcplayer->mcv_block_tcplayer($parsed_block);
            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        return $parsed_block;
    }
    private function mcv_get_tcvod_mediaUrl($fileId, $appID){
        $data = array('fileId'=>$fileId,'appId'=>$appID,'mode' => 'tcvod');
        $minfo = $this->_wpcvApi->call('mediainfo', $data);
        return $minfo;
    }

    /**
     * 删除媒体
     */
    public function deleteAttachment($post_id, $post){
        $meta = wp_get_attachment_metadata($post_id);
        if(isset($meta['mode'])){
            if($meta['mode'] == 'tcvod'){
                $meta = $this->mine_taskId2FileId($post_id, $meta);
                if(isset($meta['fileId'])){
                    try {
                        $fileId = $meta['fileId'];
                        $appId = $meta['appId'];
                        $req = array(
                            'fileId' => $fileId,
                            'appId'  => $appId,
                            'mode' => 'tcvod'
                        );
                        $resultArray = $this->_wpcvApi->call('delete', $req);
                    }
                    catch(\Exception $e) {
                        
                    }
                }
            }
            if($meta['mode'] == 'alivod'){
                $meta = $this->mcv_jobId2videoId($post_id, $meta);
                if(isset($meta['videoId'])){
                    try {
                        $videoId = $meta['videoId'];
                        $endpoint = $meta['endpoint'];
                        $req = array(
                            'videoId' => $videoId,
                            'endpoint'  => $endpoint,
                            'mode' => 'alivod'
                        );
                        $resultArray = $this->_wpcvApi->call('delete', $req);
                    }
                    catch(\Exception $e) {
                        
                    }
                }
            }
        }
    }
    
    /***************阿里云视频点播API***************** */
    /**
     * 阿里云视频点播片头片尾是否启用
     * 若启用返回数组，两个都未启用返回false
     */
    private function mcv_alivod_piantouwei(){
        $tw = false;
        if(MINECLOUDVOD_SETTINGS['alivodpiantou']['status'] && MINECLOUDVOD_SETTINGS['alivodpiantou']['videoid']){
            $tw['tou'] = MINECLOUDVOD_SETTINGS['alivodpiantou']['videoid'];
        }
        if(MINECLOUDVOD_SETTINGS['alivodpianwei']['status'] && MINECLOUDVOD_SETTINGS['alivodpianwei']['videoid']){
            $tw['wei'] = MINECLOUDVOD_SETTINGS['alivodpianwei']['videoid'];
        }
        return $tw;
    }
    public function mcv_alivod_upload(){
        header('Content-type:application/json; Charset=utf-8');
        global $current_user;
        $uid = $current_user->ID;
        
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;

        if ($nonce && !wp_verify_nonce($nonce, 'mcv-aliyunvod-' . $uid)) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'001'));exit;
        }
        $endpoint = sanitize_text_field($_POST['endpoint']);
        if(!$endpoint) $endpoint = MINECLOUDVOD_SETTINGS['alivod']['endpoint'];
        switch($_POST['op']){
            case 'getuvinfo':
                if(!array_key_exists($endpoint, MINECLOUDVOD_ALIYUNVOD_ENDPOINT)){
                    echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'002'));exit;
                }
                $fileName = sanitize_text_field($_POST['FileName']);
                $fileSize = sanitize_text_field($_POST['FileSize']);
                $cateId = sanitize_text_field($_POST['cateId']);
                // uid as cateId when there is no cateId
                if(!$cateId){
                    $cateId = get_user_meta($current_user->ID, 'mcv_aliyunvod_cateId', true);
                    if(isset($cateId['cateId'])){
                        $cateId = $cateId['cateId'];
                    }
                    else{
                        $cdata = array('mode' => 'alivod', 'cateName' => 'WPUID-'.$uid);
                        $cate = $this->_wpcvApi->call('addcate', $cdata);
                        if($cate["status"] == 1){
                            $umid = update_user_meta($uid, 'mcv_aliyunvod_cateId', $cate['data']);
                            $cateId = $cate['data']['cateId'];
                        }
                    }
                }
                
                $touwei = false;//$this->mcv_alivod_piantouwei();
                $encrypt = MINECLOUDVOD_SETTINGS['alivod']['encrypt'];
                $data = array(
                    'fileName'  => $fileName,
                    'fileSize'  => $fileSize,
                    'cateId'    => $cateId,
                    'touwei'    => $touwei,
                    'encrypt'    => $encrypt,
                    'mode' => 'alivod'
                );
                $usign = $this->_wpcvApi->call('getuvinfo', $data);
                echo json_encode($usign);
            break;
            case 'refreshuvinfo':
                $videoId = sanitize_text_field($_POST['VideoId']);
                if(strlen($videoId) !== 32){
                    echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'003'));exit;
                }
                $data = array(
                    'endpoint'  => $endpoint,
                    'videoId' => $videoId,
                    'mode' => 'alivod'
                );
                $mine_uvinfo = $this->_wpcvApi->call('refreshuvinfo', $data);
                echo json_encode($mine_uvinfo);
            break;
            case 'playauth':
                $postId = sanitize_text_field($_POST['vid']);
                $videoId = '';
                $endpoint = '';
                if(is_numeric($postId)){
                    $meta = get_post_meta($postId,'_wp_attachment_metadata');
                    $videoId = $meta[0]['videoId'];
                    $endpoint = $meta[0]['endpoint'];
                }
                else{
                    $videoId = $postId;
                    $endpoint = sanitize_text_field($_POST['endpoint']);
                }
                
                
                $data = array(
                    'endpoint'  => $endpoint,
                    'videoId' => $videoId,
                    'mode' => 'alivod'
                );
                $playauth = $this->_wpcvApi->call('playauth', $data);
                echo json_encode($playauth);
            break;
            case 'uvsucceed'://视频上传成功后
                $videoId = sanitize_text_field($_POST['VideoId']);
                if(strlen($videoId) !== 32){
                    echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'005'));exit;
                }

                //HLS标准加密
                $encrypt = MINECLOUDVOD_SETTINGS['alivod']['encrypt'] ?? false;
                if($encrypt && !empty(MINECLOUDVOD_SETTINGS['alivod']['keyId'])){
                    $this->mcv_alivod_EncryptHLS($videoId, $endpoint);
                }
            break;
        }
        exit;
    }
    public function mcv_alivod_url(){
        if(!is_user_logged_in())exit;
        global $current_user;
        if(!empty($current_user->roles) && in_array('administrator', $current_user->roles)){
            $postId = sanitize_text_field($_GET['vid']);
            if(!is_numeric($postId)){
                echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'004'));exit;
            }
            $meta = get_post_meta($postId,'_wp_attachment_metadata');
            $videoId = $meta[0]['videoId'];
            $endpoint = $meta[0]['endpoint'];
            
            
            $data = array(
                'endpoint'  => $endpoint,
                'videoId' => $videoId,
                'mode' => 'alivod'
            );
            $vod = new Aliyun\Vod();
            $mp4 =$vod->get_mediaUrl($videoId, $endpoint);
            if($mp4){
                header('location:'.$mp4);
            }
        }
    }
    private function mcv_jobId2videoId($attachment_id, $meta = false){
        if(!$meta) $meta = wp_get_attachment_metadata($attachment_id);
        if(isset($meta['jobId'])){
            $jobId = $meta['jobId'];
            $events = $this->_wpcvApi->call('jobid2videoid', array('jobId'=>$jobId,'endpoint'=>'cn-shanghai','mode' => 'alivod'));
            if($events){
                unset($meta['jobId']);
                $meta['videoId'] = $events["data"]["videoId"];
                wp_update_attachment_metadata($attachment_id, $meta);
            }
        }
        return $meta;
    }
    private function mcv_alivod_player($meta, $attachment_id, $poster=''){
        global $pagenow;
        if($pagenow == 'post.php') return false;
        $meta = $this->mcv_jobId2videoId($attachment_id, $meta);
        if(isset($meta['videoId']) && isset($meta['endpoint'])){
            $width = isset($meta['width'])?$meta['width']:'500';
            $height = isset($meta['height'])?$meta['height']:'300';
            $videoId = $meta['videoId'];
            $endpoint = $meta['endpoint'];
            $post_id = get_post() ? get_the_ID() : 0;
            $divId = sprintf( 'mine-video-%d-%d', $post_id, $attachment_id);
            $video = '<style>'.MINECLOUDVOD_SETTINGS['aliplayercss'].'</style>';
            $video .= '<div id="'.$divId.'"></div>';

            global $current_user;
            $uid = $current_user->ID;
            $ajaxUrl = admin_url("admin-ajax.php");
            $wp_create_nonce		= wp_create_nonce('mcv-aliyunvod-'.$uid);
            $r = mt_rand(100, 999);
            $pctrl = ["name" => "controlBar", "align" => "blabs", "x" => 0, "y" => 0,
                'children' => []
            ];
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['progress']){
                $pctrl['children'][] = ["name" => "progress", "align" => "blabs", "x" => 0, "y" => 44];
            }
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['playButton']){
                $pctrl['children'][] = ["name" => "playButton", "align" => "tl", "x" => 15, "y" => 12];
            }
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['timeDisplay']){
                $pctrl['children'][] = ["name" => "timeDisplay", "align" => "tl", "x" => 10, "y" => 7];
            }
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['fullScreenButton']){
                $pctrl['children'][] = ["name" => "fullScreenButton", "align" => "tr", "x" => 10, "y" => 12];
            }
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['subtitle']){
                $pctrl['children'][] = ["name" => "subtitle", "align" => "tr", "x" => 15, "y" => 12];
            }
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['setting']){
                $pctrl['children'][] = ["name" => "setting", "align" => "tr", "x" => 15, "y" => 12];
            }
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['volume']){
                $pctrl['children'][] = ["name" => "volume", "align" => "tr", "x" => 5, "y" => 10];
            }
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['snapshot']){
                $pctrl['children'][] = ["name" => "snapshot", "align" => "tr", "x" => 10, "y" => 12];
            }
            $pskin = array(
                ["name" => "H5Loading", "align" => "cc"],
                ["name" => "errorDisplay", "align" => "tlabs", "x" => 0, "y" => 0],
                ["name" => "infoDisplay"],
                ["name" => "tooltip", "align" => "blabs", "x" => 0, "y" => 56],
                ["name" => "thumbnail"],
                $pctrl,
            );
            if(MINECLOUDVOD_SETTINGS['aliplayerconfig']['bigPlayButton']){
                $pskin[] = ["name" => "bigPlayButton", "align" => "blabs", "x" => 30, "y" => 80];
            }
            $pconfig = array(
                "id"        => $divId,
                "vid"       => $videoId,
                "playauth"       => '',
                "qualitySort"       => 'asc',
                "format"       => 'mp4',
                "mediaType"       => 'video',
                'encryptType'       => 1,
                "width"       => '100%',
                "height"       => '100%',
                "isLive"       => false,
                "playsinline"       => false,
                "useH5Prism"       => true,

                "autoplay"       => MINECLOUDVOD_SETTINGS['aliplayerconfig']['autoplay']? true : false,
                "rePlay"       => MINECLOUDVOD_SETTINGS['aliplayerconfig']['rePlay']? true : false,
                "preload"       => MINECLOUDVOD_SETTINGS['aliplayerconfig']['preload']? true : false,
                "controlBarVisibility"       => isset(MINECLOUDVOD_SETTINGS['aliplayerconfig']['controlBarVisibility'])?MINECLOUDVOD_SETTINGS['aliplayerconfig']['controlBarVisibility']:'hover',
                "skinLayout" => $pskin
            );

            wp_enqueue_style('mcv_aliplayer_css', 'https://g.alicdn.com/de/prismplayer/2.9.1/skins/default/aliplayer-min.css', array(), MINECLOUDVOD_VERSION, false);
            wp_add_inline_style('mcv_aliplayer_css', html_entity_decode(MINECLOUDVOD_SETTINGS['aliplayercss']));
            wp_enqueue_script('jquery');
            wp_enqueue_script('mcv_aliplayer', 'https://g.alicdn.com/de/prismplayer/2.9.1/aliplayer-min.js',  array(), MINECLOUDVOD_VERSION , false );
            wp_enqueue_script('mcv_aliplayer_components', MINECLOUDVOD_URL.'/static/aliyun/aliplayercomponents-1.0.5.min.js',  array('mcv_aliplayer'), MINECLOUDVOD_VERSION , false );
            wp_add_inline_script('mcv_aliplayer','
            if(jQuery("#'.$divId.'")){
            var aliplayerconfig_'.$r.'='.json_encode($pconfig).'; 
            jQuery.post("'.$ajaxUrl.'",{"action":"mcv_alivod_upload","nonce":"'.$wp_create_nonce.'","op":"playauth","vid":"'.$attachment_id.'",endpoint:"'.$endpoint.'"}, function (data) {
                if(!window.aliplayer_'.$r.'){
                    if(data.hls==false){
                        window.aliplayerconfig_'.$r.'.playauth=data.playauth;
                        window.aliplayerconfig_'.$r.'.vid=data.vid;
                    }else{
                        window.aliplayerconfig_'.$r.'.format="m3u8";
                        window.aliplayerconfig_'.$r.'.source=data.hls;
                        window.aliplayerconfig_'.$r.'.components=[{name: "QualityComponent",type: AliPlayerComponent.QualityComponent}];
                    }
                    window.aliplayer_'.$r.'=new Aliplayer(aliplayerconfig_'.$r.', function (player) {
                        if(data.hls!=false){
                            player.on("sourceloaded", function(params) {
                                var paramData = params.paramData;
                                var desc = paramData.desc;
                                var definition = paramData.definition;
                                player.getComponent("QualityComponent").setCurrentQuality(desc, definition);
                            });
                        }
                    });
                }
            }, \'json\');}
            ');
            return $video;
        }
        return false;
    }
    public function mcv_asyc_ali_transcode(){
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_ali_transcode')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'alivod');
        $transcode = $this->_wpcvApi->call('transcode', $data);
        update_option('mcv_ali_transcode', $transcode['data']);
        echo json_encode($transcode);
        exit;
    }
    /***************阿里云视频点播API 结束***************** */

    /***************腾讯云点播API***************** */
    /**
     * 同步转码任务流列表
     */
    public function mcv_asyc_transcode(){
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_transcode')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'tcvod');
        $transcode = $this->_wpcvApi->call('transcode', $data);
        update_option('mcv_tc_transcode', $transcode['data']);
        echo json_encode($transcode);
        exit;
    }
    /**
     * 同步播放器配置
     */
    public function mcv_asyc_plyrconfig(){
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_plyrconfig')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'tcvod');
        $plyrconfig = $this->_wpcvApi->call('plyrconfig', $data);
        update_option('mcv_tc_plyrconfig', $plyrconfig['data']);
        echo json_encode($plyrconfig);
        exit;
    }
    /**
     * 腾讯云点播片头片尾是否启用
     * 若启用返回数组，两个都未启用返回false
     */
    private function mcv_tcvod_piantouwei(){
        $tw = false;
        if(MINECLOUDVOD_SETTINGS['tcvodpiantou']['status'] && MINECLOUDVOD_SETTINGS['tcvodpiantou']['fileid']){
            $tw['tou'] = MINECLOUDVOD_SETTINGS['tcvodpiantou']['fileid'];
        }
        if(MINECLOUDVOD_SETTINGS['tcvodpianwei']['status'] && MINECLOUDVOD_SETTINGS['tcvodpianwei']['fileid']){
            $tw['wei'] = MINECLOUDVOD_SETTINGS['tcvodpianwei']['fileid'];
        }
        return $tw;
    }
    /**
     * 获取上传签名
     */
    public function mcv_uploadsign(){
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_uploadsign')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'tcvod');
        $touwei = $this->mcv_tcvod_piantouwei();
        if(is_array($touwei)){
            $data['touwei'] = $touwei;
        }
        $usign = $this->_wpcvApi->call('usign', $data);
        echo json_encode($usign);
        exit;
    }
    /**
     * 腾讯云点播文件上传完成后处理
     */
    public function mcv_tcvod_uploaded(){
        global $current_user;
        $uid = $current_user->ID;
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_tcvod_uploaded')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $fileId = sanitize_text_field($_POST['fileId']);
        $title = sanitize_text_field($_POST['fileName']);

        $touwei = $this->mcv_tcvod_piantouwei();
        if(is_array($touwei)){//处理头尾
            $data = array('fileId'=>$fileId,'fileName'=>$title,'touwei'=>$touwei,'mode' => 'tcvod');
            $twret = $this->_wpcvApi->call('touwei', $data);
            if(isset($twret['status']) && $twret['status'] == 1){
                $this->taskId = $twret['data']['TaskId'];
                $mine_uploadrecord = array(
                    'post_mime_type' => 'video/mp4',
                    'post_parent'    => 0,
                    'post_title'     => $twret['data']['title'],
                    "post_author"	 => $uid
                );
                $attachment_id = wp_insert_attachment($mine_uploadrecord);
                if ( ! is_wp_error( $attachment_id ) ) {
                    $guid = admin_url('admin.php?action=mcv_tcvod_url&fid='.$attachment_id.'&/'.$title);
                    update_post_meta( $attachment_id, '_wp_attached_file', $guid );
                    update_post_meta( $attachment_id, 'mcv_mode', 'tcvod' );
                    $metadata = array();
                    $metadata = $this->wpGenerateAttachmentMetadata($metadata);
                    $metaid = wp_update_attachment_metadata( $attachment_id, $metadata );
                    echo json_encode(array('status'=>1, 'data'=>array('mid'=>$attachment_id)));
                }
            }
        }
        else{
            $data = array('fileId'=>$fileId,'appId'=>MINECLOUDVOD_SETTINGS['tcvod']['appid'],'mode' => 'tcvod');
            $minfo = $this->_wpcvApi->call('mediainfo', $data);
            if($minfo && isset($minfo["MediaInfoSet"][0]["MetaData"]["Width"]) && isset($minfo["MediaInfoSet"][0]["MetaData"]["Height"])){
                $width = $minfo["MediaInfoSet"][0]["MetaData"]["Width"];
                $height = $minfo["MediaInfoSet"][0]["MetaData"]["Height"];
            }
            $mediaUrl = esc_url($_POST['mediaUrl']);
            if($minfo && isset($minfo["MediaInfoSet"][0]["BasicInfo"]["MediaUrl"]) && isset($minfo["MediaInfoSet"][0]["BasicInfo"]["Name"])){
                if(!$mediaUrl)$mediaUrl = $minfo["MediaInfoSet"][0]["BasicInfo"]["MediaUrl"];
                if(!$title)$title = $minfo["MediaInfoSet"][0]["BasicInfo"]["Name"];
            }
            $width  = is_numeric($width) ? $width : 500;
            $height = is_numeric($height) ? $height : 300;
            $type = 'video/mp4';
            $post_id = 0;
            $mine_uploadrecord = array(
                'post_mime_type' => $type,
                'post_parent'    => $post_id,
                'post_title'     => $title,
                "post_author"	 => $uid
            );
            $attachment_id = wp_insert_attachment($mine_uploadrecord);
            if ( ! is_wp_error( $attachment_id ) ) {
                $guid = admin_url('admin.php?action=mcv_tcvod_url&fid='.$attachment_id.'&/'.$title);
                update_post_meta( $attachment_id, '_wp_attached_file', $guid );
                update_post_meta( $attachment_id, 'mcv_mode', 'tcvod' );
                $metadata['fileId'] 	= $fileId;
                $metadata['mediaUrl'] 	= $mediaUrl;
                $metadata['width'] 	    = $width;
                $metadata['height'] 	= $height;
                $metadata['region']     = MINECLOUDVOD_SETTINGS['tcvod']['region'];
                $metadata['appId']      = intval(MINECLOUDVOD_SETTINGS['tcvod']['appid']);
                $metadata['pcfg'] 	    = MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig'];
                $metadata['mode'] 	    = 'tcvod';
                $metaid = wp_update_attachment_metadata( $attachment_id, $metadata );
                echo json_encode(array('status'=>1, 'data'=>array('mid'=>$attachment_id)));
            }
            else{
                echo json_encode(array('status' => '0', 'msg' => '出错了ErrorCode:3000'));
            }
        }
        exit;
    }

    /**
     * tencentvod TaskId2FileId
     */
    private function mine_taskId2FileId($attachment_id, $meta = false){
        if(!$meta) $meta = wp_get_attachment_metadata($attachment_id);
        if(isset($meta['taskId'])){
            $taskId = $meta['taskId'];
            $data = array('mode' => 'tcvod');
            $events = $this->_wpcvApi->call('event', $data);
            if($events){
                foreach($events as $ev){
                    if($taskId == $ev['TaskId']){
                        unset($meta['taskId']);
                        $meta['fileId'] = $ev['FileId'];
                        $meta['region'] = $ev['Region'];
                        $meta['appId']  = intval(explode('-', $taskId)[0]);
                        if(wp_update_attachment_metadata($attachment_id, $meta)){
                            $eh = array($ev['EventHandle']);
                            $this->_wpcvApi->call('confirm', array('eventHandles'=>$eh,'mode' => 'tcvod'));
                        }
                        break;
                    }
                }
            }
        }
        return $meta;
    }
    /**
     * 
     */
    private function mcv_tcvod_player($attachment_id, $poster=''){
        global $pagenow;
        if($pagenow == 'post.php') return false;
        $meta = $this->mine_taskId2FileId($attachment_id);
        if(isset($meta['fileId']) && isset($meta['appId'])){
            $width = isset($meta['width'])?$meta['width']:'500';
            $height = isset($meta['height'])?$meta['height']:'300';
            $fileID = $meta['fileId'];
            $appID = $meta['appId'];
            $pcfg = isset($meta['pcfg'])?$meta['pcfg']:'default';
            $post_id = get_post() ? get_the_ID() : 0;
            $instance = $attachment_id;
            $videoId = sprintf( 'mine-video-%d-%d', $post_id, $instance );
            $video = '';
            $video .= '<video id="'.$videoId.'" width="'.$width.'" height="'.$height.'" preload="none" controls="controls" playsinline webkit-playsinline></video>';

            $pconfig = [
                'fileID'    => $fileID,
                'appID'    => $appID,
                'psign'    => $this->mcv_generate_psign($attachment_id, $appID, $fileID, $pcfg),
                'poster'    => $poster,
                'preload'    => MINECLOUDVOD_SETTINGS['tcplayerconfig']['preload'],
                'controls'    => MINECLOUDVOD_SETTINGS['tcplayerconfig']['controls']?true:false,
                'autoplay'      => MINECLOUDVOD_SETTINGS['tcplayerconfig']['autoplay']?true:false,
                'loop'          => MINECLOUDVOD_SETTINGS['tcplayerconfig']['loop']?true:false,
                'muted'         => MINECLOUDVOD_SETTINGS['tcplayerconfig']['muted']?true:false,
                'bigPlayButton'       => MINECLOUDVOD_SETTINGS['tcplayerconfig']['bigPlayButton']?true:false,
                'controlBar'    => [
                    'playToggle'                    => MINECLOUDVOD_SETTINGS['tcplayerconfig']['playToggle']?true:false,
                    'progressControl'               => MINECLOUDVOD_SETTINGS['tcplayerconfig']['progressControl']?true:false,
                    'volumePanel'                   => MINECLOUDVOD_SETTINGS['tcplayerconfig']['volumePanel']?true:false,
                    'currentTimeDisplay'            => MINECLOUDVOD_SETTINGS['tcplayerconfig']['currentTimeDisplay']?true:false,
                    'durationDisplay'               => MINECLOUDVOD_SETTINGS['tcplayerconfig']['durationDisplay']?true:false,
                    'timeDivider'                   => MINECLOUDVOD_SETTINGS['tcplayerconfig']['timeDivider']?true:false,
                    'playbackRateMenuButton'        => MINECLOUDVOD_SETTINGS['tcplayerconfig']['playbackRateMenuButton']?true:false,
                    'fullscreenToggle'              => MINECLOUDVOD_SETTINGS['tcplayerconfig']['fullscreenToggle']?true:false,
                    'QualitySwitcherMenuButton'    => MINECLOUDVOD_SETTINGS['tcplayerconfig']['QualitySwitcherMenuButton']?true:false,
                ],
                'plugins'       => ['ProgressMarker' => true],
                'hlsConfig'     => ['autoStartLoad' => MINECLOUDVOD_SETTINGS['tcplayerconfig']['preload']=='none'?false:true]
            ];

            wp_enqueue_style('mine_tcplayer_css', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/tcplayer.min.css', array(), false);
            wp_add_inline_style('mine_tcplayer_css', 'img.tcp-vtt-thumbnail-img{max-width:unset !important;}'.html_entity_decode(MINECLOUDVOD_SETTINGS['tcplayercss']));
            wp_enqueue_script('jquery');
            wp_enqueue_script('mine_tcplayerhls', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/libs/hls.min.0.13.2m.js',  array(), MINECLOUDVOD_VERSION , true );
            wp_enqueue_script('mine_tcplayer', 'https://web.sdk.qcloud.com/player/tcplayer/release/v4.2.1/tcplayer.v4.2.1.min.js',  array(), MINECLOUDVOD_VERSION , true );
            wp_add_inline_script('mine_tcplayer','if(jQuery("#'.$videoId.'")){var tcplayerconfig_'.$post_id. $instance.';var tcplayer_'.$post_id. $instance.';
            var psign_'.$post_id. $instance.' = "'.$this->mcv_generate_psign($attachment_id, $appID, $fileID, $pcfg).'";
                tcplayerconfig_'.$post_id. $instance.'='.json_encode($pconfig).';
                if(!window.tcplayer_'.$post_id. $instance.'){
                    window.tcplayer_'.$post_id. $instance.' = TCPlayer(\''.$videoId.'\', window.tcplayerconfig_'.$post_id. $instance.');
                }}
            ');
            return $video;
        }
        return false;
    }
    
    private function mcv_generate_psign($attachment_id, $appId, $fileId, $pcfg){
        if($attachment_id){
            $meta = wp_get_attachment_metadata($attachment_id);
            if(isset($meta['psign']) && is_array($meta['psign'])){
                if($meta['psign'][0] > time())
                    return $meta['psign'][1];
            }
        }
        $data = array(
            'appId'  => $appId,
            'fileId' => $fileId,
            'pcfg'   => $pcfg,
            'mode'   => 'tcvod'
        );
        $jwt = $this->_wpcvApi->call('psign', $data);
        if($attachment_id){
            $meta['psign'] = array($jwt['ctime'], $jwt['psign']);
            wp_update_attachment_metadata($attachment_id, $meta);
        }
        return $jwt['psign'];
    }
    
    /***************腾讯云点播API 结束***************** */
}
