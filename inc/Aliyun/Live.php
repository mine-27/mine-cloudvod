<?php
namespace MineCloudvod\Aliyun;

class Live{
    private $_wpcvApi;

    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;
        add_action( 'wp_ajax_mcv_sync_alilive_domains', array($this, 'mcv_sync_alilive_domains') );
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'aliyun_admin_options' ) );
        add_action('wp_ajax_mcv_aliyun_liveurl', array($this, 'mcv_aliyun_liveurl'));

        add_action( 'admin_enqueue_scripts',function(){
            global $typenow;
            if( $typenow != MINECLOUDVOD_LMS['lesson_post_type'] ) return;
            wp_enqueue_script('clipboard');
            
            wp_add_inline_script( 'clipboard', mcv_trim( '
            jQuery(function(){
                jQuery.post("'.admin_url("admin-ajax.php").'", {action: "mcv_aliyun_liveurl", pid:jQuery("#post_ID").val(), nonce: "'. wp_create_nonce('mcv_aliyun_liveurl') .'"}, function(data){
                    
                    if(data?.data?.push){
                        let push = data.data.push;
                        let btns = "";
                        btns += \'<a href="#" class="button button-primary mcv-liveurl" data-clipboard-text="\'+push.rtmp+\'">RTMP</a>\';
                        btns += \'\t<a href="#" class="button button-primary mcv-liveurl" data-clipboard-text="\'+push.rts+\'">RTS</a>\';
                        jQuery("#mcv-copy-push").html(btns);
                    }
                    if(data?.data?.pull){
                        let pull = data.data.pull;
                        let btns = "";
                        btns += \'<a href="#" class="button button-primary mcv-liveurl" data-clipboard-text="\'+pull.rtmp+\'">RTMP</a>\';
                        btns += \'\t<a href="#" class="button button-primary mcv-liveurl" data-clipboard-text="\'+pull.m3u8+\'">M3U8</a><br><br>\';
                        btns += \'\t<a href="#" class="button button-primary mcv-liveurl" data-clipboard-text="\'+pull.flv+\'">FLV</a>\';
                        btns += \'\t<a href="#" class="button button-primary mcv-liveurl" data-clipboard-text="\'+pull.rts+\'">RTS</a>\';
                        jQuery("#mcv-copy-pull").html(btns);
                    }
                    var clipboard = new ClipboardJS(".mcv-liveurl");
                    clipboard.on("success", function(e) {
                        var cur = jQuery(e.trigger);
                        cur.after("<span>' . __('Copied', 'mine-cloudvod') . '</span>");
                        setTimeout(function() {
                            cur.next("span").remove();}, 2000);
                            e.clearSelection();
                        }
                    );
                }, "json");
            });
            ' ) );
        });
        add_action( 'init', function(){
            \mcv_lms_filter_lesson_types(['live' => __('Live', 'mine-cloudvod')], [[
                'id'         => 'aliyun_livetime',
                'type'       => 'datetime',
                'title'      => __('Live time', 'mine-cloudvod'),
                'settings' => array(
                  'dateFormat' => 'Y-m-d H:i',
                  'enableTime' => true,
                  'time_24hr'  => true,
                ),
                'from_to'   => true,
                'dependency'    => ['_lesson_type', '==', 'live'],
            ],
            [
                'type'    => 'submessage',
                'style'   => 'success',
                'content'   => __('Push URL.', 'mine-cloudvod').'<br /><p id="mcv-copy-push"></p>',
                'dependency'    => ['_lesson_type', '==', 'live'],
            ],
            [
                'type'    => 'submessage',
                'style'   => 'warning',
                'content'   => __('Pull URL.', 'mine-cloudvod').'<br /><p id="mcv-copy-pull"></p>',
                'dependency'    => ['_lesson_type', '==', 'live'],
            ],]);
        } );
    }

    public function mcv_aliyun_liveurl(){
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv_aliyun_liveurl')) {
            wp_send_json_error(['msg' => __('Illegal request', 'mine-cloudvod')]);
        }
        $user = wp_get_current_user();
        if( !$user->exists() ){
            wp_send_json_error(['msg' => __('Illegal request', 'mine-cloudvod')]);
        }

        $pid = sanitize_text_field(wp_unslash($_POST['pid']??0));
        if( !is_numeric( $pid ) ) $pid = 0;
        
        //AppName
        $appName = MINECLOUDVOD_SETTINGS['alivod']['AppName'];
        //StreamName
        $streamName = 'live-lesson-' . $pid;

        $data = [];
        //推流域名
        $push_domain = MINECLOUDVOD_SETTINGS['alivod']['PushDomain']??'';

        $push = get_option('mcv_alilive_push_domains');
        //推流域名配置的鉴权Key
        $push_key = '';
        //配置过期时间为1小时
        $expireTime = 0;
        if( is_array( $push ) ){
            foreach( $push as $ps ){
                if( $ps['DomainName'] == $push_domain && $ps['Type'] == 'publish' ){
                    if( isset( $ps['aliauth']['DomainConfigs']['DomainConfig'][0]['FunctionArgs']['FunctionArg'] ) && is_array( $ps['aliauth']['DomainConfigs']['DomainConfig'][0]['FunctionArgs']['FunctionArg'] ) ){
                        $args = array_column( $ps['aliauth']['DomainConfigs']['DomainConfig'][0]['FunctionArgs']['FunctionArg'], 'ArgValue', 'ArgName' );
                        if( $args['auth_type'] == 'type_a' ){
                            $push_key = $args['auth_key1'];
                            $expireTime = $args['ali_auth_delta'];
                        }
                    }
                }
            }
        }
        $data['push']['rtmp'] = $this->push_url( $push_domain, $push_key, $expireTime, $appName, $streamName, 'rtmp' );
        $data['push']['rts'] = $this->push_url( $push_domain, $push_key, $expireTime, $appName, $streamName, 'rts' );
        
        //播流域名
        $pull_domain = MINECLOUDVOD_SETTINGS['alivod']['PullDomain']??'';

        $pull = get_option('mcv_alilive_pull_domains');
        //播流域名配置的鉴权Key
        $pull_key = '';
        //配置过期时间为1小时
        $expireTime = 0;
        if( is_array( $pull ) ){
            foreach( $pull as $ps ){
                if( $ps['DomainName'] == $pull_domain && $ps['LiveDomainStatus'] == 'online' ){
                    if( isset( $ps['aliauth']['DomainConfigs']['DomainConfig'][0]['FunctionArgs']['FunctionArg'] ) && is_array( $ps['aliauth']['DomainConfigs']['DomainConfig'][0]['FunctionArgs']['FunctionArg'] ) ){
                        $args = array_column( $ps['aliauth']['DomainConfigs']['DomainConfig'][0]['FunctionArgs']['FunctionArg'], 'ArgValue', 'ArgName' );
                        if( $args['auth_type'] == 'type_a' ){
                            $pull_key = $args['auth_key1'];
                            $expireTime = $args['ali_auth_delta'];
                        }
                    }
                }
            }
        }
        foreach(['rtmp', 'rts', 'flv', 'm3u8'] as $type){
            $data['pull'][$type] = $this->play_url( $pull_domain, $pull_key, $expireTime, $appName, $streamName, $type );
        }
        wp_send_json_success($data);
    }

    public function push_url( $push_domain, $push_key, $expireTime, $appName, $streamName, $type = 'rtmp'){
        $type = strtolower( $type );
        if( !in_array( $type, ['rtmp', 'rts'] ) ){
            $type = 'rtmp';
        }
        $push_url = '';
        //未开启鉴权Key的情况下
        if($push_key==''){
                $push_url = $type . '://'.$push_domain.'/'.$appName.'/'.$streamName;
                return $push_url;
        }
        $timeStamp = time() + $expireTime;
        $sstring = '/'.$appName.'/'.$streamName.'-'.$timeStamp.'-0-0-'.$push_key;
        $md5hash = md5($sstring);
        $push_url = $type . '://'.$push_domain.'/'.$appName.'/'.$streamName.'?auth_key='.$timeStamp.'-0-0-'.$md5hash;
        
        return $push_url;
    }
    
    public function play_url( $play_domain, $play_key, $expireTime, $appName, $streamName, $type = 'rtmp' ){
        $type = strtolower( $type );
        if( !in_array( $type, ['rtmp', 'rts', 'flv', 'm3u8'] ) ){
            $type = 'rtmp';
        }
        //未开启鉴权Key的情况下
        if($play_key==''){
            if( $type == 'rtmp' ){
                return 'rtmp://'.$play_domain.'/'.$appName.'/'.$streamName;
            }
            elseif( $type == 'rts' ){
                return 'rts://'.$play_domain.'/'.$appName.'/'.$streamName;
            }
            elseif( $type == 'm3u8' ){
                return (is_ssl()?'https':'http') . '://'.$play_domain.'/'.$appName.'/'.$streamName.'.m3u8';
            }
            elseif( $type == 'flv' ){
                return (is_ssl()?'https':'http') . '://'.$play_domain.'/'.$appName.'/'.$streamName.'.flv';
            }
        }else{
            $timeStamp = time() + $expireTime;
        
            if( $type == 'rtmp' ){
                $rtmp_sstring = '/'.$appName.'/'.$streamName.'-'.$timeStamp.'-0-0-'.$play_key;
                $rtmp_md5hash = md5($rtmp_sstring);
                $rtmp_play_url = 'rtmp://'.$play_domain.'/'.$appName.'/'.$streamName.'?auth_key='.$timeStamp.'-0-0-'.$rtmp_md5hash;
                return $rtmp_play_url;
            }
            elseif( $type == 'rts' ){
                $rtmp_sstring = '/'.$appName.'/'.$streamName.'-'.$timeStamp.'-0-0-'.$play_key;
                $rtmp_md5hash = md5($rtmp_sstring);
                $rts_play_url = 'rts://'.$play_domain.'/'.$appName.'/'.$streamName.'?auth_key='.$timeStamp.'-0-0-'.$rtmp_md5hash;
                return $rts_play_url;
            }
            elseif( $type == 'm3u8' ){
                $hls_sstring = '/'.$appName.'/'.$streamName.'.m3u8-'.$timeStamp.'-0-0-'.$play_key;
                $hls_md5hash = md5($hls_sstring);
                $hls_play_url = (is_ssl()?'https':'http') . '://'.$play_domain.'/'.$appName.'/'.$streamName.'.m3u8?auth_key='.$timeStamp.'-0-0-'.$hls_md5hash;
                return $hls_play_url;
            }
            elseif( $type == 'flv' ){
                $flv_sstring = '/'.$appName.'/'.$streamName.'.flv-'.$timeStamp.'-0-0-'.$play_key;
                $flv_md5hash = md5($flv_sstring);
                $flv_play_url = (is_ssl()?'https':'http') . '://'.$play_domain.'/'.$appName.'/'.$streamName.'.flv?auth_key='.$timeStamp.'-0-0-'.$flv_md5hash;
                return $flv_play_url;
            }
        }

        return '';
    }
    
    public function mcv_sync_alilive_domains(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_sync_alilive_domains')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        if( isset( $_POST['domain'] ) && !empty( $_POST['domain'] ) ){
            $data = array(
                'mode' => 'alilive',
                'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['alivod'] ),
                'domain' => sanitize_text_field(wp_unslash($_POST['domain']))
            );
            $domains = $this->_wpcvApi->call('domainMapping', $data);
            update_option('mcv_alilive_push_domains', $domains['data']);
            echo wp_json_encode($domains);
        }
        else{
            $data = array(
                'mode' => 'alilive',
                'sdk' => $this->_wpcvApi->encrypt( MINECLOUDVOD_SETTINGS['alivod'] ),
            );
            $domains = $this->_wpcvApi->call('domains', $data);
            update_option('mcv_alilive_pull_domains', $domains['data']);
            update_option('mcv_alilive_push_domains', []);
            echo wp_json_encode($domains);
        }
        
        exit;
    }

    public function aliyun_admin_options(){
        $prefix = 'mcv_settings';
        $ajaxUrl = admin_url("admin-ajax.php");
        $mcv_alilive_pull_domains = array('' => __('Please sync Pull Domains first', 'mine-cloudvod'));
        $mcv_alilive_push_domains = array('' => __('Please sync Push Domains first', 'mine-cloudvod'));
        if($tctc = get_option('mcv_alilive_pull_domains')){
            $mcv_alilive_pull_domains = array();
            if( is_array( $tctc ) ){
                foreach($tctc as $tc){
                    $args = $tc['aliauth']['DomainConfigs']['DomainConfig'][0]['FunctionArgs']['FunctionArg']??false;
                    $name = $tc['DomainName'];
                    if($args){
                        foreach($args as $arg){
                            if($arg['ArgName'] == 'ali_auth_delta'){
                                $name .= ' - '. sprintf( 
                                    // translators: %d: 有效分钟数
                                    __('Valid for %d minutes', 'mine-cloudvod'),
                                     $arg['ArgValue'] / 60 
                                    );
                            }
                        }
                    }
                    $mcv_alilive_pull_domains[$tc['DomainName']] =  $name;
                }
            }
        }
        if($push = get_option('mcv_alilive_push_domains')){
            $mcv_alilive_push_domains = array();
            if( is_array( $push ) ){
                foreach($push as $tc){
                    if( $tc['Type'] == 'publish' ){
                        $args = $tc['aliauth']['DomainConfigs']['DomainConfig'][0]['FunctionArgs']['FunctionArg']??false;
                        $name = $tc['DomainName'];
                        if($args){
                            foreach($args as $arg){
                                if($arg['ArgName'] == 'ali_auth_delta'){
                                    $name .= ' - '. sprintf( 
                                        // translators: %d: 有效分钟数
                                        __('Valid for %d minutes', 'mine-cloudvod'),
                                         $arg['ArgValue'] / 60 );
                                }
                            }
                        }
                        $mcv_alilive_push_domains[$tc['DomainName']] =  $name;
                    }
                }
            }
        }

        \MCSF::createSection( $prefix, array(
        'parent'     => 'aliyunvod',
        'title' => __('ApsaraVideo Live', 'mine-cloudvod'),//'视频直播',
        'icon'   => 'fas fa-video',
        'fields' => array(
            array(
            'type'    => 'submessage',
            'style'   => 'warning',
            'content' => __('By default, Alibaba Cloud OSS is charged after the end of the hour, and it can also be found on <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">Alibaba Cloud OSS Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'),//'<p>阿里云视频点播默认是时结后收费模式，也可以在 <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">阿里云平台</a> 购买相应的资源包消费</p>',
            ),
            array(
            'id'        => 'alivod',
            'type'      => 'fieldset',
            'title'     => __('ApsaraVideo Live', 'mine-cloudvod'),//'视频直播',
            'fields'    => array(
                array(
                'id'          => 'PullDomain',
                'type'        => 'select',
                'title'       => __('Pull Domain', 'mine-cloudvod'),//'播流域名',
                'after'       => '<p><a href="javascript:mcv_sync_alilive_domains();">'.__('Sync Pull Domains', 'mine-cloudvod').'</a></p><p>'.__('The valid duration needs to be modified in the authentication configuration', 'mine-cloudvod').' <a href="javascript:mcv_modify_valid(\'pull\');">'.__('To Modify', 'mine-cloudvod').'</a></p>',
                'options'     => $mcv_alilive_pull_domains,
                'default'     => ''
                ),
                array(
                'id'          => 'PushDomain',
                'type'        => 'select',
                'title'       => __('Push Domain', 'mine-cloudvod'),//'推流域名',
                'after'       => '<p><a href="javascript:mcv_sync_alilive_pushdomain();">'.__('Sync Push Domains', 'mine-cloudvod').'</a></p><p>'.__('The valid duration needs to be modified in the authentication configuration', 'mine-cloudvod').' <a href="javascript:mcv_modify_valid(\'push\');">'.__('To Modify', 'mine-cloudvod').'</a></p>',
                'options'     => $mcv_alilive_push_domains,
                'default'     => ''
                ),
                array(
                'id'    => 'AppName',
                'type'  => 'text',
                'title' => 'AppName',
                'default' => 'mcv',
                ),
            ),
            ),

        )
        ) );
    }

}
