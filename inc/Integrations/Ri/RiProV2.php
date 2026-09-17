<?php
namespace MineCloudvod\Integrations\Ri;

class RiProV2
{
    private $is_cao_video = false;
    public function __construct()
    {
        add_action('wp_enqueue_scripts',array($this, 'shortcode_head'));
        add_action('wp_ajax_mcv_ajax_player_riprov2', array($this, 'mcv_ajax_player_riprov2'));
        add_action('wp_ajax_nopriv_mcv_ajax_player_riprov2', array($this, 'mcv_ajax_player_riprov2'));
        add_filter('mcv_filter_aliplayer', array($this, 'riprov2_mcv_filter_aliplayer'), 10, 5);
        add_filter('mcv_filter_tcplayer', array($this, 'riprov2_mcv_filter_tcplayer'), 10, 4);
        add_filter('mcv_filter_embedvideo', array($this, 'riprov2_mcv_filter_embedvideo'), 10, 4);
        add_filter('mcv_filter_audioplayer', array($this, 'riprov2_mcv_filter_audioplayer'), 10, 3);
        add_filter('mcv_filter_dplayer', array($this, 'riprov2_mcv_filter_dplayer'), 10, 3);
        //Ripro vip可直接观看所有课程
        add_filter('mcv_lms_is_enrolled', array($this, 'mcv_is_ripro_vip'));
        add_action( 'mcv_add_admin_options_before_purchase', array( $this, 'admin_options' ) );
    }

    public function mcv_is_ripro_vip($canplay){
        if( isset( MINECLOUDVOD_SETTINGS['ripro']['vip_free'] ) && MINECLOUDVOD_SETTINGS['ripro']['vip_free'] ){
            if( function_exists( '_get_user_vip_type' ) ){
                // _get_user_vip_type($user_id = null) 返回 'nov' 表示非会员，其他(vip boosvip)为会员
                $is_vip = _get_user_vip_type();
                if($is_vip != 'nov'){
                    return 1;
                }
            }
        }
        return $canplay;
    }

    public function shortcode_head(){
        if(is_singular()){
            global $post, $current_user;
            $user_id = $current_user->ID;
            $post_id = $post->ID;
            $mcv = false;

            $cao_video = get_post_meta( $post_id, 'video_url_new', true);
            if(is_array($cao_video)){
                foreach($cao_video as $k => $v){
                    if(strpos($v['src'],'[mine_cloudvod') !== false){
                        $this->is_cao_video = true;
                        break;
                    }
                }
                if($this->is_cao_video){
                    global $mcv_classes, $ActiveAddons;
                    wp_deregister_script('video-js');
                    $mcv_classes->Aliplayer && $mcv_classes->Aliplayer::style_script();
                    isset($ActiveAddons['qcloud']) && $ActiveAddons['qcloud']->Tcplayer::style_script();
                    $mcv_classes->Dplayer && $mcv_classes->Dplayer::style_script();
                    $mcv_classes->Audioplayer && $mcv_classes->Audioplayer::style_script();
                    $IS_PAID        = \get_user_pay_post_status($user_id, $post_id);;
                    $is_free_video  = (bool)get_post_meta( $post_id, 'cao_is_video_free', true);
                    if( $is_free_video || $IS_PAID ){
                        $nonce				= wp_create_nonce('mcv-aliyunvod-aliplayer-' . $current_user->ID);
                        $ajaxUrl = admin_url("admin-ajax.php");

                        wp_add_inline_script('mcv_localize_script', mcv_trim( 'jQuery(function() {
                            jQuery(".ri-video-warp > div:not(#rizhuti-video) *").hide();
                            ri.heroVideoJs = function(t){
                                const a = jQuery(".switch-video");
                                const i = jQuery(".video-title .title-span");
                                jQuery("body").on("click", ".switch-video", function() {
                                    if( !jQuery(this).hasClass("active") ){
                                        var e = jQuery(this).data("index");
                                        e = t[e];
                                        jQuery.post("'.esc_url($ajaxUrl).'",{action:"mcv_ajax_player_riprov2", nonce:"'.esc_attr($nonce).'", url: e.src},function(res){
                                            jQuery(".ri-video-warp").html("<div id=\'rizhuti-video\'><svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" width=\"150px\" height=\"150px\" viewBox=\"0 0 40 40\" enable-background=\"new 0 0 40 40\" xml:space=\"preserve\" style=\"margin: auto;position: absolute;top: 0;bottom: 0;left: 0;right: 0;height: 30%;\"><path opacity=\"0.2\" fill=\"#FF6700\" d=\"M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z\"></path><path fill=\"#FF6700\" d=\"M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z\" transform=\"rotate(42.1171 20 20)\"><animateTransform attributeType=\"xml\" attributeName=\"transform\" type=\"rotate\" from=\"0 20 20\" to=\"360 20 20\" dur=\"0.5s\" repeatCount=\"indefinite\"></animateTransform></path></svg></div>");
                                            if(res.status == "1"){
                                                jQuery("#rizhuti-video").empty();
                                                jQuery("#rizhuti-video").before(res.player[0]);
                                                ev'.'al(res.player[1]);
                                            }
                                            else{
                                                jQuery("#rizhuti-video").empty();
                                                jQuery("#rizhuti-video").html(res.player[0]);
                                                ev'.'al(res.player[1]);
                                            }
                                        }, "json");
                                        a.removeClass("active");
                                        jQuery(this).addClass("active");
                                    }
                                });
                                jQuery(".switch-video").removeClass("active");
                                if(jQuery(".switch-video").length==0){
                                    jQuery(".ri-video-list").append("<a href=\"javascript:;\" class=\"switch-video\" style=\"display:none;\" data-index=\"0\"><span>1</span></a>");
                                }
                                jQuery(".switch-video:first").trigger("click");
                            };
                        });' ));
                    }
                }
                unset($videos);
            }
        }
    }

    public function mcv_ajax_player_riprov2(){
        global $current_user;
        $uid = $current_user->ID;
        
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv-aliyunvod-aliplayer-' . $uid)) {
            echo wp_json_encode(array('status' => '0', 'msg' => __( 'Illegal request', 'mine-cloudvod' )));exit;
        }

        $url = !empty($_POST['url']) ? sanitize_text_field(wp_unslash($_POST['url'])) : null;
        $url = urldecode($url);
        preg_match('/\[mine_cloudvod id\=(\d*)\]/is', $url, $mcv_postid);
        if(!$mcv_postid){
            $aliplayer = do_shortcode('[mcv_dplayer source="' . $url . '" width="100%" height="500px" from="riprov2"]');
        }
        else{
            $aliplayer = do_shortcode('[mine_cloudvod id=' . $mcv_postid[1] . ' from="riprov2"]');
        }
        $aliplayer = explode('{{mcvsplit}}', $aliplayer);
        if(count($aliplayer) == 3){
            if($aliplayer[2] == 'aliyunvod'){
                echo wp_json_encode(array('status' => '1', 'player' => $aliplayer));
            }
            if($aliplayer[2] == 'qcloudvod'){
                echo wp_json_encode(array('status' => '2', 'player' => $aliplayer));
            }
            if($aliplayer[2] == 'embedvideo'){
                echo wp_json_encode(array('status' => '3', 'player' => $aliplayer));
            }
            if($aliplayer[2] == 'audioplayer'){
                echo wp_json_encode(array('status' => '4', 'player' => $aliplayer));
            }
            if($aliplayer[2] == 'dplayer'){
                echo wp_json_encode(array('status' => '5', 'player' => $aliplayer));
            }
        }
        
        exit;

    }
    public function riprov2_mcv_filter_dplayer($video, $pconfig, $parsed_block)
    {
        global $mcv_block_ajax_from;
        if ($mcv_block_ajax_from == 'riprov2') {
            $divId = 'rizhuti-video';
            $r = md5(serialize($parsed_block));
            $pconfig_json = json_encode($pconfig);
            $pconfig_json = preg_replace('/("key":"video-info","click":)("([^"]*?)")/is', '$1$3', $pconfig_json);
            $pconfig_json = preg_replace('/("container"\:)"document.getElementById\(\\\"([^\\\]*?)\\\"\)"/is', '$1document.getElementById("rizhuti-video")', $pconfig_json);
            $video = '';
            $video .= '{{mcvsplit}}var dplayerconfig_' . $r . '=' . $pconfig_json . '; window.mcv_player_riprov2=new McvDPlayer(dplayerconfig_' . $r . ');{{mcvsplit}}dplayer';
        }
        return $video;
    }
    public function riprov2_mcv_filter_aliplayer($video, $pconfig, $components, $events, $r)
    {
        global $mcv_block_ajax_from;
        $divId = 'rizhuti-video';
        if($mcv_block_ajax_from == 'riprov2'){
            $video = '<style>'.MINECLOUDVOD_SETTINGS['aliplayercss'].'.video-role-info{z-index:4000;}</style>';
            $video .= '{{mcvsplit}}var aliplayerconfig_'.$r.'='.json_encode($pconfig).';aliplayerconfig_'.$r.'.height="100%"; '.$components.'
            if(window.mcv_player_riprov2)window.mcv_player_riprov2.dispose();window.mcv_player_riprov2=new Aliplayer(aliplayerconfig_'.$r.', function (player) {'.$events.'});jQuery(".hero-video .hero").css("z-index", "100");{{mcvsplit}}aliyunvod';
            $video = str_replace($pconfig['id'], $divId, $video);
        }
        return $video;
    }
    public function riprov2_mcv_filter_tcplayer($video, $pconfig, $post_id, $instance)
    {
        global $mcv_block_ajax_from;
        $divId = 'rizhuti-video';
        if($mcv_block_ajax_from == 'riprov2'){
            $videoId = sprintf( 'mcv-%s', md5($video) );
            $video = '<style>.video-js{height:100%;padding:inherit;}.video-js .vjs-play-progress:before{top:0 !important;}</style><video id="'.$videoId.'" width="100%" height="100%" preload="none" controls="controls" playsinline webkit-playsinline></video>';
            $video .= '{{mcvsplit}}';
            $video .= 'if(jQuery("#'.$videoId.'")){var tcplayerconfig_'.$post_id.'='.json_encode($pconfig).';if(window.tcplayer_' . $post_id . ')window.tcplayer_' . $post_id . '.dispose(); window.tcplayer_'.$post_id.' = TCPlayer(\''.$videoId.'\', tcplayerconfig_'.$post_id.');}';
            $video .='{{mcvsplit}}qcloudvod';
        }
        return $video;
    }
    public function riprov2_mcv_filter_embedvideo($video, $src, $width, $height)
    {
        global $mcv_block_ajax_from;
        if($mcv_block_ajax_from == 'riprov2'){
            $video .= '{{mcvsplit}}';
            $video .= '';
            $video .='{{mcvsplit}}embedvideo';
        }
        return $video;
    }
    public function riprov2_mcv_filter_audioplayer($video, $parsed_block, $inlineScript)
    {
        global $mcv_block_ajax_from;
        if($mcv_block_ajax_from == 'riprov2'){
            $video .= '{{mcvsplit}}';
            $video .= $inlineScript;
            $video .='{{mcvsplit}}audioplayer';
        }
        return $video;
    }

    public function admin_options(){
        $prefix = 'mcv_settings';
        $mcv_qiniu_bucketsList = array('' => __('Please sync Bukcets List first', 'mine-cloudvod'));
        \MCSF::createSection( $prefix, array(
            'id'    => 'mcv_ripro',
            'title' => 'RiPro',
            'icon'  => 'fa fa-circle',
            'fields' => array(
                array(
                    'id'        => 'ripro',
                    'type'      => 'fieldset',
                    'title'     => '',
                    'fields'    => array(
                        array(
                            'id'    => 'vip_free',
                            'type'  => 'switcher',
                            'title' => __('Vip can learn all courses for free', 'mine-cloudvod'),
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false,
                        ),
                    ),
                ),
            )
          ) );
    }

}