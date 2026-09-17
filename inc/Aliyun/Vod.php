<?php
namespace MineCloudvod\Aliyun;

class Vod
{
    private $_wpcvApi;

    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;

        add_action('wp_ajax_mcv_alivod_upload',         array($this, 'mcv_alivod_upload'));
        add_action('wp_ajax_nopriv_mcv_alivod_upload',  array($this, 'mcv_alivod_upload'));
        add_action('admin_action_mcv_alivod_url',       array($this, 'mcv_alivod_url'));
        add_action('wp_ajax_mcv_asyc_ali_transcode',    array($this, 'mcv_asyc_ali_transcode'));
        add_action('wp_ajax_mcv_alivod_playauth', array($this, 'mcv_asyc_aliyun_vod_playauth'));
        add_action('wp_ajax_nopriv_mcv_alivod_playauth', array($this, 'mcv_asyc_aliyun_vod_playauth'));
        add_action('wp_ajax_mcv_sync_ali_keyid', array($this, 'mcv_sync_ali_keyid'));

        // add_filter('render_block_data', array($this, 'mcv_render_alivod'), 10, 2);

        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'aliyun_admin_options' ) );

        add_action( 'init',     [ $this, 'mcv_register_block'] );
    }

    public function mcv_register_block(){
        wp_register_script(
            'mcv_alivod_sdk',
            MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/aliyun-upload-sdk-1.5.0.min.js',
            null,
            MINECLOUDVOD_VERSION,
            true
        );
        wp_register_script(
            'mcv_alivod_es6-promise',
            MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/lib/es6-promise.min.js',
            null,
            MINECLOUDVOD_VERSION,
            true
        );
        wp_register_script(
            'mcv_alivod_oss',
            MINECLOUDVOD_URL.'/static/aliyun/upload-js-sdk/lib/aliyun-oss-sdk-5.3.1.min.js',
            null,
            MINECLOUDVOD_VERSION,
            true
        );

        register_block_type( MINECLOUDVOD_PATH . '/build/alivod/');
        global $mcv_classes;
        $mcv_classes->Aliplayer::style_script();
        $uid = get_current_user_id();
    }

    public function aliyun_admin_options(){
        $prefix = 'mcv_settings';
        $mcv_ali_transcode = array('VOD_NO_TRANSCODE' => __('Please sync transcoding template first', 'mine-cloudvod')); //'请先同步转码模板');
        if ($tctc = get_option('mcv_ali_transcode')) {
            $mcv_ali_transcode = array();
            foreach ($tctc as $tc) {
                $mcv_ali_transcode[$tc[1]] =  $tc[0];
            }
        }
        \MCSF::createSection( $prefix, array(
            'parent'     => 'aliyunvod',
            'title'  => __('ApsaraVideo VOD', 'mine-cloudvod'),
            'icon'   => 'fas fa-video',
            'fields' => array(
                array(
                'type'    => 'submessage',
                'style'   => 'warning',
                'content' => __('By default, Alibaba Cloud VOD is charged after the end of the day, and it can also be found on <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">Alibaba Cloud VOD Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'),//'<p>阿里云视频点播默认是日结后收费模式，也可以在 <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">阿里云视频点播平台</a> 购买相应的资源包消费</p>',
                ),
                array(
                'id'        => 'alivod',
                'type'      => 'fieldset',
                'title'     => __('ApsaraVideo VOD', 'mine-cloudvod'),//'阿里云视频点播',
                'fields'    => array(
                    array(
                    'id'    => 'userId',
                    'type'  => 'text',
                    'title' => '账号 ID',
                    'after' => '在阿里云控制台右上角用户头像的弹窗中可以找到',
                    ),
                    array(
                    'id'          => 'endpoint',
                    'type'        => 'select',
                    'title'       => __('Storage area', 'mine-cloudvod'),//'存储区域',
                    'placeholder' => __('Select storage area', 'mine-cloudvod'),//'选择区域',
                    'options'     => [
                        'cn-beijing'        => __('China(Beijing)', 'mine-cloudvod'),//'华北2（北京）',
                        'cn-zhangjiakou'    => __('China(Zhangjiakou)', 'mine-cloudvod'),//'华北3（张家口）',
                        'cn-hangzhou'       => __('China(Hangzhou)', 'mine-cloudvod'),//'华东1（杭州）',
                        'cn-shanghai'       => __('China(Shanghai)', 'mine-cloudvod'),//'华东2（上海）',
                        'cn-shenzhen'       => __('China(Shenzhen)', 'mine-cloudvod'),//'华南1（深圳）',
                        'cn-hongkong'       => __('China(Hongkong)', 'mine-cloudvod'),//'香港',
                        'ap-northeast'      => __('Janpan(Tokyo)', 'mine-cloudvod'),//'日本（东京）',
                        'ap-southeast-1'    => __('Singapore', 'mine-cloudvod'),//'新加坡',
                        'ap-southeast-5'    => __('Indonesia(Jakarta)', 'mine-cloudvod'),//'印度尼西亚（雅加达）',
                        'us-west-1'         => __('USA (Silicon Valley)', 'mine-cloudvod'),//'美国（硅谷）',
                        'eu-west-1'         => __('U.K (London)', 'mine-cloudvod'),//'英国（伦敦）',
                        'eu-central-1'      => __('Germany(Frankfurt)', 'mine-cloudvod'),//'德国（法兰克福）',
                        'ap-south-1'        => __('India(Mumbai)', 'mine-cloudvod'),//'印度（孟买）'
                    ],
                    'default'     => 'cn-shanghai'
                    ),
                    array(
                    'id'          => 'transcode',
                    'type'        => 'select',
                    'title'       => __('Transcoding template', 'mine-cloudvod'),//'转码模板',
                    'after'       => '<p><a href="javascript:mcv_sync_ali_transcode();">'.__('Sync transcoding template', 'mine-cloudvod').'</a></p>',//同步转码模板组,
                    'placeholder' => __('Select transcoding template', 'mine-cloudvod'),//'选择转码模板',
                    'options'     => $mcv_ali_transcode,
                    'default'     => 'VOD_NO_TRANSCODE'
                    ),
                    array(
                        'id'    => 'down_snapshot',
                        'type'  => 'switcher',
                        'title' => __('Download Snapshot', 'mine-cloudvod'),//'HLS标准加密',
                        'after' => __('<br /><p>After enabling, the snapshot of video will download to Media Libary, and it will be the feature image of the post when there has no feature image.</p>', 'mine-cloudvod'),
                        'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                        'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                        'default' => false
                    ),
                    array(
                        'id' => 'privatelhls',
                        'type'  => 'fieldset',
                        'title' => __('Private HLS encryption', 'mine-cloudvod'),//'私有加密',
                        'before' => '<a href="https://help.aliyun.com/document_detail/124734.html" target="_blank">视频加密简介</a>',
                        'fields'    => array(
                            array(
                            'type'    => 'submessage',
                            'style'   => 'warning',
                            'content' => __('私有加密　<b>暂不支持iOS系统的网页端播放</b>！如需，请使用HLS标准加密。', 'mine-cloudvod'),
                            ),
                            array(
                            'type'    => 'submessage',
                            'style'   => 'success',
                            'content' => __('开启方法：选择一个开启视频加密选项的转码模板，并且禁用　HLS标准加密。', 'mine-cloudvod'),
                            ),
                        )
                    ),
                    array(
                        'id'    => 'encrypt',
                        'type'  => 'switcher',
                        'title' => __('Standard HLS encryption', 'mine-cloudvod'),//'HLS标准加密',
                        'after' => __('<br /><p style="color:red">After enabling, the video uploaded through the plugin will be automatically encrypted with Standard HLS encryption</p>', 'mine-cloudvod'),//'<br /><p style="color:red">启用后，通过插件上传的视频会自动进行标准加密</p>',
                        'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                        'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                        'default' => false
                    ),
                    array(
                    'type'    => 'submessage',
                    'style'   => 'success',
                    'dependency' => array( 'encrypt', '==', true ),
                    'content' => '
                    <p>使用流程：</p>
                    <p>1. <a href="https://help.aliyun.com/zh/vod/getting-started/activate-apsaravideo-vod" target="_blank">开通视频点播</a>.</p>
                    <p>2. <a href="https://ram.console.aliyun.com/role/authorization?spm=a2c4g.11186623.0.0.4bc62453ncagLb&request=%7B%22Services%22%3A%5B%7B%22Service%22%3A%22VOD%22%2C%22Roles%22%3A%5B%7B%22RoleName%22%3A%22AliyunVODDefaultRole%22%2C%22TemplateId%22%3A%22DefaultRole%22%7D%5D%7D%5D%2C%22ReturnUrl%22%3A%22https%3A%2F%2Fvod.console.aliyun.com%2F%22%7D" target="_blank">云资源访问授权</a>.</p>
                    <p>3. <a href="https://vod.console.aliyun.com/#/settings/encryption" target="_blank">创建service key</a>.</p>
                    ',
                    ),
                    array(
                    'type'    => 'submessage',
                    'style'   => 'warning',
                    'dependency' => array( 'encrypt', '==', true ),
                    'content' => '
                    <p>注意事项：</p>
                    <p>1. 转码模板必须选择封装格式为hls、视频加密开启的模板.</p>
                    <p>2. 必须开启HLS标准加密参数透传。<p>
                    ',
                    ),
                    array(
                    'id'    => 'keyId',
                    'type'  => 'text',
                    'title' => '密钥（Service key）',
                    'dependency' => array( 'encrypt', '==', true ),
                    'after' => '<a href="javascript:mcv_sync_ali_keyid();">点击自动获取密钥</a>',
                    ),
                    array(
                    'id'    => 'token',
                    'type'  => 'text',
                    'title' => __('Security key', 'mine-cloudvod'),//'安全密钥',
                    'dependency' => array( 'encrypt', '==', true ),
                    'after' => __('Security verification for playing standard hls encrypted video', 'mine-cloudvod'),//'用于播放HLS标准加密视频的安全验证',
                    'default' => time()
                    ),
                    array(
                    'id'    => 'tokenTime',
                    'type'  => 'number',
                    'title' => __('Valid Duration', 'mine-cloudvod'),//'有效时长',
                    'subtitle' => __('Valid duration of transparent transmission parameters', 'mine-cloudvod'),//'透传参数的有效时长',
                    'dependency' => array( 'encrypt', '==', true ),
                    'after' => __('Hour', 'mine-cloudvod'),//'小时',
                    'default' => 10
                    ),
                ),
                ),
            
            )
            ) );
    }

    public function mcv_render_alivod($parsed_block, $source_block){
        if($parsed_block['blockName'] == "mine-cloudvod/aliyun-vod"){
            global $mcv_classes;
            $aliplayer = $mcv_classes->Aliplayer;
            $video = $aliplayer->mcv_block_aliplayer($parsed_block);
            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        return $parsed_block;
    }

    public function get_mediaUrl($videoId, $endpoint){
        $vinfo = $this->get_playinfo($videoId, $endpoint);
        if($vinfo['status'] == 1){
            $mp4 = $vinfo['data']['mp4'];
            return $mp4;
        }
        return false;
    }
    
    public function get_playinfo($videoId, $endpoint = '', $getCache = true){
        if( !$endpoint ) $endpoint = MINECLOUDVOD_SETTINGS['alivod']['endpoint'];
        $dir = 'alivod';
        if( $getCache ){
            $cache = mcv_get_file_cache($dir, $videoId, 1800);
            $cache = maybe_unserialize($cache);
            if($cache && $cache['status'] != 0) return $cache;
        }
        $data = array(
            'endpoint'  => $endpoint,
            'videoId' => $videoId,
            'getCache' => $getCache,
            'mode' => 'alivod'
        );
        $playinfo = $this->_wpcvApi->call('playauth_v2', $data);

        if(!is_array($playinfo)){
            $playinfo = $this->_wpcvApi->call('playauth_v2', $data);
        }
        if(!is_array($playinfo)){
            $playinfo = $this->_wpcvApi->call('playauth_v2', $data);
        }
        if(!is_array($playinfo)){
            $playinfo = $this->_wpcvApi->call('playauth_v2', $data);
        }
        if(!is_array($playinfo)){
            $playinfo = $this->_wpcvApi->call('playauth_v2', $data);
        }
        if(!is_array($playinfo)){
            $playinfo = $this->_wpcvApi->call('playauth_v2', $data);
        }
        if(!is_array($playinfo)){
            $playinfo = $this->_wpcvApi->call('playauth_v2', $data);
        }
        if(!is_array($playinfo)){
            $playinfo = $this->_wpcvApi->call('playauth_v2', $data);
        }
        if(!is_array($playinfo)){
            $playinfo = $this->_wpcvApi->call('playauth_v2', $data);
        }

        if( isset( $playinfo['hls'] ) && is_array( $playinfo['hls'] ) ){
            $ret = [];
            foreach( $playinfo['hls'] as $key => $hls ){
                if( $key == 'AUTO' ) continue;
                $encrypt = 0;
                $encryptType = '';
                if( isset( $hls['encrypt'] ) ){
                    $encrypt = $hls['encrypt'];
                    $encryptType = $hls['encryptType'];
                }
                if( $encryptType == 'AliyunVoDEncryption' ){
                    $ret[ $key ] = 'mcv_enc://' . $playinfo['playauth'];
                    $playinfo['encrypt'] = true;
                }
                elseif( $encryptType == 'HLSEncryption' ){
                    $at = isset(MINECLOUDVOD_SETTINGS['alivod']['token'])?MINECLOUDVOD_SETTINGS['alivod']['token']:'';
                    $token = new \MineCloudvod\Ability\Token($at, MINECLOUDVOD_SETTINGS['alivod']['tokenTime']??10);
                    if( strpos( $hls['playUrl'], '?' ) > 0 ){
                        $ret[ $key ] = $hls['playUrl'] . '&MtsHlsUriToken=' . $token->generrate_token();
                    }
                    else{
                        $ret[ $key ] = str_replace( '.m3u8', '.m3u8?MtsHlsUriToken='.$token->generrate_token(), $hls['playUrl'] );
                    }
                }
                else{
                    $ret[ $key ] = $hls['playUrl'];
                }
            }
            
            $playinfo['encrypt'] = $encrypt;
            $playinfo['encryptType'] = $encryptType;
            $playinfo['hls'] = $ret;
        }
        if(is_array($playinfo)){
            mcv_set_file_cache($dir, $videoId, serialize($playinfo));
        }
        return $playinfo;
    }
    
    public function get_playurl($videoId, $endpoint){
        $data = array(
            'endpoint'  => $endpoint,
            'videoId' => $videoId,
            'mode' => 'alivod'
        );
        $playinfo = $this->_wpcvApi->call('playurl', $data);
        if($playinfo['status'] != '1'){
            return new \WP_Error('cant-trash', $playinfo['msg'], ['status' => 500]);
        }
        return $playinfo;
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
        
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;

        if (!$nonce || !wp_verify_nonce($nonce, 'mcv-aliyunvod-' . $uid)) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'001'));exit;
        }
        $endpoint = sanitize_text_field(wp_unslash($_POST['endpoint']??''));
        if(!$endpoint) $endpoint = MINECLOUDVOD_SETTINGS['alivod']['endpoint'];
        switch(sanitize_text_field(wp_unslash($_POST['op']??''))){
            case 'getuvinfo':
                $fileName = sanitize_text_field(wp_unslash($_POST['FileName']??''));
                $fileSize = sanitize_text_field(wp_unslash($_POST['FileSize']??''));
                $cateId = sanitize_text_field(wp_unslash($_POST['cateId']??''));
                $mediaType = sanitize_text_field(wp_unslash($_POST['mediaType']??''));
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
                if($mediaType == 'audio'){
                    $data['encrypt'] = 'true';
                }
                $usign = $this->_wpcvApi->call('getuvinfo', $data);
                echo wp_json_encode($usign);
            break;
            case 'refreshuvinfo':
                $videoId = sanitize_text_field(wp_unslash($_POST['VideoId']??''));
                if(strlen($videoId) !== 32){
                    echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'003'));exit;
                }
                $data = array(
                    'endpoint'  => $endpoint,
                    'videoId' => $videoId,
                    'mode' => 'alivod'
                );
                $mine_uvinfo = $this->_wpcvApi->call('refreshuvinfo', $data);
                echo wp_json_encode($mine_uvinfo);
            break;
            case 'playauth':
                $postId = sanitize_text_field(wp_unslash($_POST['vid']??''));
                $videoId = '';
                $endpoint = '';
                if(is_numeric($postId)){
                    $meta = get_post_meta($postId,'_wp_attachment_metadata');
                    $videoId = $meta[0]['videoId'];
                    $endpoint = $meta[0]['endpoint'];
                }
                else{
                    $videoId = $postId;
                    $endpoint = sanitize_text_field(wp_unslash($_POST['endpoint']));
                }
                
                
                $data = array(
                    'endpoint'  => $endpoint,
                    'videoId' => $videoId,
                    'mode' => 'alivod'
                );
                $playauth = $this->_wpcvApi->call('playauth', $data);
                echo wp_json_encode($playauth);
            break;
            case 'uvsucceed'://视频上传成功后
                $videoId = sanitize_text_field(wp_unslash($_POST['VideoId']??''));
                if(strlen($videoId) !== 32){
                    echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod').'005'));exit;
                }

                //HLS标准加密
                $encrypt = MINECLOUDVOD_SETTINGS['alivod']['encrypt'] ?? false;
                if($encrypt && !empty(MINECLOUDVOD_SETTINGS['alivod']['keyId'])){
                    $result = $this->mcv_alivod_EncryptHLS($videoId, $endpoint);
                    
                    echo wp_json_encode($result);
                }
            break;
        }
        exit;
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
    public function mcv_asyc_ali_transcode(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv_asyc_ali_transcode')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'alivod');
        $transcode = $this->_wpcvApi->call('transcode', $data);
        update_option('mcv_ali_transcode', $transcode['data']);
        echo wp_json_encode($transcode);
        exit;
    }
    public function mcv_alivod_EncryptHLS($videoId, $endpoint){
        $req = array(
            'videoId' => $videoId,
            'endpoint' => $endpoint,
            'mode' => 'alivod'
        );
        $resultArray = $this->_wpcvApi->call('entranscode', $req);
        return $resultArray;
    }

    public function mcv_asyc_aliyun_vod_playauth(){
        global $current_user;
        $uid = $current_user->ID;
        $nonce = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv-aliyunvod-' . $uid)) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $videoId = sanitize_text_field(wp_unslash($_POST['videoId']??''));
        $endpoint = MINECLOUDVOD_SETTINGS['alivod']['endpoint'];
        $playinfo = $this->get_playinfo($videoId, $endpoint);
        if (!$playinfo['hls']) {
            echo esc_html($playinfo['playauth']);
        } else {
            $pihls = json_decode($playinfo['hls'], true);
            $pihls = array_reverse($pihls);
            unset($pihls['AUTO']);
            echo wp_json_encode($pihls);
        }
        exit;
    }

    public function mcv_sync_ali_keyid(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv_sync_ali_keyid')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'alivod');
        $keyid = $this->_wpcvApi->call('getkeyid', $data);
        echo wp_json_encode($keyid);
        exit;
    }
    /***************阿里云视频点播API 结束***************** */
}
