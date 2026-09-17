<?php
namespace MineCloudvod\Qcloud;

class Vod
{
    private $_wpcvApi;

    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;

        add_action('wp_ajax_mcv_asyc_transcode',        array($this, 'mcv_asyc_transcode'));
        add_action('wp_ajax_mcv_asyc_plyrconfig',       array($this, 'mcv_asyc_plyrconfig'));
        add_action('wp_ajax_mcv_uploadsign',            array($this, 'mcv_uploadsign'));

        // add_filter('render_block_data', array($this, 'mcv_render_tcvod'), 10, 2);

        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'qcloud_admin_options' ) );
        add_action( 'init',     [ $this, 'mcv_register_block'] );
    }

    public function mcv_register_block(){
        wp_register_script(
            'mcv_tcplayerhls',
            'https://web.sdk.qcloud.com/player/tcplayer/release/v4.9.0/libs/hls.min.1.1.7.js',
            null,
            MINECLOUDVOD_VERSION,
            true
        );
        wp_register_script(
            'mcv_tcplayer',
            'https://web.sdk.qcloud.com/player/tcplayer/release/v4.9.0/tcplayer.v4.9.0.min.js',
            ['jquery', 'mcv_tcplayerhls'],
            MINECLOUDVOD_VERSION,
            true
        );
        
        wp_register_style(
            'mcv_tcplayer_css',
            'https://tcsdk.com/player/tcplayer/release/v5.3.4/tcplayer.min.css', 
            null,
            MINECLOUDVOD_VERSION,
            'all'
        );
        wp_add_inline_style('mcv_tcplayer_css', 'img.tcp-vtt-thumbnail-img{max-width:unset !important;max-height:unset !important;}'.html_entity_decode(MINECLOUDVOD_SETTINGS['tcplayercss']??''));
        register_block_type( MINECLOUDVOD_PATH . '/build/tcvod/');

        $uid = get_current_user_id();
        wp_add_inline_script('mine-cloudvod-tc-vod-editor-script','var mcv_tcvod_config={appID:"'.(MINECLOUDVOD_SETTINGS['tcvod']['appid']??'').'",key:"'.(MINECLOUDVOD_SETTINGS['tcvod']['fdlkey']??'').'",pcfg:"'.(MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig']??'').'",transcode:"'.(MINECLOUDVOD_SETTINGS['tcvod']['transcode']??'').'",nonce:"'.wp_create_nonce('mcv-aliyunvod-'.$uid).'",sdk:'.( MINECLOUDVOD_SETTINGS['tcvod']['sid']??MINECLOUDVOD_SETTINGS['tcvod']['skey']??false ? 'true' : 'false' ).',tc_config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Tencent Cloud', 'mine-cloudvod'))))).'"};');
    }

    public function qcloud_admin_options(){
        $prefix = 'mcv_settings';
        $mcv_tc_transcode = array('default' => __('Please synchronize the task flow list first', 'mine-cloudvod')); //'请先同步任务流列表'
        if ($tctc = get_option('mcv_tc_transcode')) {
            $mcv_tc_transcode = array();
            foreach ($tctc as $tc) {
                $mcv_tc_transcode[$tc[0]] = $tc[1] . ' - ' . $tc[0];
            }
        }
        $mcv_tc_plyrconfig = array('default' => __('Please synchronize the player configuration list first', 'mine-cloudvod')); //'请先同步播放器配置列表');
        if ($tctc = get_option('mcv_tc_plyrconfig')) {
            $mcv_tc_plyrconfig = array();
            foreach ($tctc as $tc) {
                $mcv_tc_plyrconfig[$tc[0]] = $tc[1] . ' - ' . $tc[0];
            }
        }
        \MCSF::createSection( $prefix, array(
            'parent'     => 'tencentvod',
            'title'  => __('Tencent Cloud Vod', 'mine-cloudvod'),//'腾讯云点播',
            'icon'   => 'fas fa-video',
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
                    'id'    => 'appid',
                    'type'  => 'text',
                    'title' => 'SubAppId',
                    ),
                    array(
                    'id'    => 'fdlkey',
                    'type'  => 'text',
                    'title' => '防盗链 Key',
                    'after' => '<a href="https://cloud.tencent.com/document/product/266/33469#key-.E9.98.B2.E7.9B.97.E9.93.BE" target="_blank">点此查看Key 防盗链的相关说明</a>',
                    ),
                    array(
                    'id'          => 'region',
                    'type'        => 'select',
                    'title'       => __('Storage area', 'mine-cloudvod'),//'存储区域',
                    'placeholder' => __('Select storage area', 'mine-cloudvod'),//'选择区域',
                    'options'     => MINECLOUDVOD_TCVOD_ENDPOINT,
                    'default'     => 'ap-chongqing'
                    ),
                    array(
                    'id'          => 'transcode',
                    'type'        => 'select',
                    'title'       => __('Transcoding tasks', 'mine-cloudvod'),//'转码任务流',
                    'after'       => '<p><a href="javascript:mcv_sync_transcode();">'.__('Sync transcoding task list.', 'mine-cloudvod').'</a></p>',//同步任务流列表
                    'placeholder' => __('Select transcoding tasks', 'mine-cloudvod'),//'选择任务流',
                    'options'     => $mcv_tc_transcode,
                    'default'     => 'default'
                    ),
                    array(
                    'id'          => 'plyrconfig',
                    'type'        => 'select',
                    'title'       => __('Tcplayer setting', 'mine-cloudvod'),//'超级播放器配置',
                    'after'       => '<p>点播播放器签名升级后，些配置已经不需要，详情查看<a href="https://cloud.tencent.com/document/product/266/81850" target="_blank">官方公告</a></p>',
                    // 'after'       => '<p><a href="javascript:mcv_asyc_plyrconfig();">'.__('Sync tcplayer setting list', 'mine-cloudvod').'</a></p>',//同步播放器配置列表
                    'placeholder' => __('Select tcplayer setting', 'mine-cloudvod'),//'选择播放器配置',
                    'options'     => $mcv_tc_plyrconfig,
                    'attributes' => array(
                        'hidden' => ''
                    ),
                    'default'     => 'default'
                    ),
                    array(
                        'id'          => 'playkey',
                        'type'        => 'text',
                        'title'       => __('Play Key', 'mine-cloudvod'),//播放密钥
                        'before'      => '使用新版签名方法，请勿必重新同步<a href="javascript:mcv_sync_transcode();">'. __('Transcoding tasks', 'mine-cloudvod') . '</a>',
                        'after'       => '<p><a href="https://console.cloud.tencent.com/vod/distribute-play/urlsetting" target="_blank">点此查看'.__('Play Key', 'mine-cloudvod').'</a></p>',
                    ),
                    // array(
                    //     'id'      => 'player',
                    //     'type'    => 'radio',
                    //     'title'   => __('Video Player', 'mine-cloudvod'),
                    //     'inline'  => true,
                    //     'options' => array(
                    //         'default'    => __('Default', 'mine-cloudvod'),
                    //         'dplayer'   => 'DPlayer',
                    //     ),
                    //     'default' => 'default',
                    //     'desc'    => __('Use TCplayer by default, DPlayer is a powerful HTML5 video player.', 'mine-cloudvod'),
                    // ),
                ),
                ),
            
            )
            ) );
    }

    public function mcv_render_tcvod($parsed_block, $source_block){
        if($parsed_block['blockName'] == "mine-cloudvod/tc-vod"){
            $video = $this->mcv_block_tcvod($parsed_block);
            if($video){
                $parsed_block['innerContent'][0] = $video;
            }
        }
        return $parsed_block;
    }

    public function mcv_block_tcvod($parsed_block, $enqueue = true){
        global $mcv_classes;
        $player = MINECLOUDVOD_SETTINGS['tcvod']['player']??'default';
        if($player == 'dplayer'){
            $attrs = $parsed_block['attrs'];
            $pcfg = $attrs['pcfg'] ?? MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig'] ?? 'default';
            $taskid = $attrs['taskid'] ?? MINECLOUDVOD_SETTINGS['tcvod']['transcode'] ?? 'default';
            $psign = $this->mcv_generate_psign(0, $attrs['appId'], $attrs['videoId'], $pcfg, $taskid);
            $dplayer_attrs = [
                'cover'     => $attrs['cover'] ?? false,
                'captions'  => $attrs['captions'] ?? false,
                'markers'   => $attrs['markers'] ?? false,
                'height'   => $attrs['height'] ?? false,
                'minecloudvod' => [
                    'tcvod'     => [
                        'fileID' => $attrs['videoId'],
                        'appID' => $attrs['appId'],
                        'psign' => $psign['psign'],
                    ],
                    'thumbnails_rowcol' => [
                        'row'   => 1,
                        'col'   => 10,
                    ]
                ],
            ];
            $dplayer_block = [
                'blockName'     => 'mine-cloudvod/dplayer',
                'innerBlocks'   => [],
                'innerHTML'     => '',
                'innerContent'  => [],
                'attrs'         => $dplayer_attrs,
            ];
            $dplayer = $mcv_classes->Dplayer;
            $video = $dplayer->mcv_block_dplayer($dplayer_block);
            if($video){
                return $video;
            }
        }
        else{
            global $ActiveAddons;
            $tcplayer = $ActiveAddons['qcloud']->Tcplayer;
            $video = $tcplayer->mcv_block_tcplayer($parsed_block);
            if($video){
                return $video;
            }
        }
    }
    public function getTaskInfo( $taskid ){
        $tasks = get_option( 'mcv_tc_transcode' );
        if( $tasks ){
            foreach( $tasks as $task ){
                if( $task[0] == $taskid ){
                    return $task;
                }
            }
        }
        return false;
    }
    public function mcv_generate_psign($attachment_id, $appId, $fileId, $pcfg='', $taskid = ''){
        $playkey = MINECLOUDVOD_SETTINGS['tcvod']['playkey'] ?? false;
        if( $playkey ){
            $dir = 'qvod';
            $cache = mcv_get_file_cache($dir, $fileId, 3600);
            if($cache) return unserialize($cache);
            $data = array(
                'appId'     => $appId,
                'fileId'    => $fileId,
                'playkey'   => trim($playkey),
                'mode'      => 'tcvod'
            );
            if(isset(MINECLOUDVOD_SETTINGS['tcplayer_ylwatermark']['status']) && MINECLOUDVOD_SETTINGS['tcplayer_ylwatermark']['status']){
                $wms = MINECLOUDVOD_SETTINGS['tcplayer_ylwatermark']['watermarks']??[];
                if( count( $wms ) > 0 ){
                    $dtseed = wp_rand(0, count( $wms )-1 );
                    $wm = $wms[$dtseed];
                    global $current_user;
                    $watermarkContent = $wm['words'];
                    $watermarkContent = str_replace(['{userid}', '{username}', '{userip}', '{useremail}', '{usernickname}'], [$current_user->ID??'', $current_user->user_login??'', sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')), $current_user->user_email??'', $current_user->display_name??''], $watermarkContent);
                    $data['ghost'] = $watermarkContent;
                }
            }
            $jwt = $this->_wpcvApi->call('psign2', $data);
            // var_dump($jwt);exit;
			if(!is_array($jwt)){
				$jwt = $this->_wpcvApi->call('psign2', $data);
			}
			if(!is_array($jwt)){
				$jwt = $this->_wpcvApi->call('psign2', $data);
			}
			if(!is_array($jwt)){
				$jwt = $this->_wpcvApi->call('psign2', $data);
			}
            if(is_array($jwt)){
                mcv_set_file_cache($dir, $fileId, serialize($jwt));
            }
            return $jwt;
        }

        $plyrconfig = MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig'] ?? false;

        if( $plyrconfig && $pcfg ){
            $data = array(
                'appId'  => $appId,
                'fileId' => $fileId,
                'pcfg'   => $pcfg,
                'mode'   => 'tcvod'
            );
            $jwt = $this->_wpcvApi->call('psign', $data);
            return $jwt['psign']??'';
        }
    }

	public function mcv_get_tcvod_mediaUrl($fileId, $appID = 0){
        if(!$appID) $appID = MINECLOUDVOD_SETTINGS['tcvod']['appid']??'';
        $data = array('fileId'=>$fileId,'appId'=>$appID,'mode' => 'tcvod');
        $minfo = $this->_wpcvApi->call('mediainfo', $data);
        return $minfo;
    }
    
	public function mcv_get_tcvod_sprite($id){
        if(!$id) return false;
        $data = array('tid'=>$id,'mode' => 'tcvod');
        $minfo = $this->_wpcvApi->call('spriteTemplate', $data);
        return $minfo;
    }

    /***************腾讯云点播API***************** */
    /**
     * 同步转码任务流列表
     */
    public function mcv_asyc_transcode(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_transcode')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'tcvod');
        $transcode = $this->_wpcvApi->call('transcode', $data);
        update_option('mcv_tc_transcode', $transcode['data']);
        echo wp_json_encode($transcode);
        exit;
    }
    /**
     * 同步播放器配置
     */
    public function mcv_asyc_plyrconfig(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_plyrconfig')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'tcvod');
        $plyrconfig = $this->_wpcvApi->call('plyrconfig', $data);
        update_option('mcv_tc_plyrconfig', $plyrconfig['data']);
        echo wp_json_encode($plyrconfig);
        exit;
    }
    /**
     * 获取上传签名
     */
    public function mcv_uploadsign(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_uploadsign')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('mode' => 'tcvod');
        $usign = $this->_wpcvApi->call('usign', $data);
        echo wp_json_encode($usign);
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
    
    
    /***************腾讯云点播API 结束***************** */
}
