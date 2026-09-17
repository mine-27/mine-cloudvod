<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

// 后台（区块编辑器）使用客户端组件渲染预览；服务端输出会产生在顶层 document 中查找容器的内联脚本，故跳过。
if ( is_admin() ) return;

$divId = sprintf('mcv_%s', md5(serialize($attributes)));
$is_rendered = mcv_is_block_rendered( $divId );
if( $is_rendered ) return ;

$private = $attributes['privt'] ?? false;
if($private && !is_user_logged_in()){
    return include(MINECLOUDVOD_PATH . "/templates/vod/private.php");
}
$audio      = isset( $attributes['audio'] ) ? esc_js( $attributes['audio'] ) : '';
$aliyunAid  = $attributes['aliyunAid'] ?? '';
$autoplay   = filter_var( ( $attributes['autoplay'] ?? false ), FILTER_VALIDATE_BOOLEAN );
$thumbnail  = $attributes['thumbnail'] ?? false;
$title      = $attributes['title'] ?? '';
$lrc        = $attributes['lrc'] ?? '';
$mode       = $attributes['mode'] ?? 'normal';
$color      = $attributes['color'] ?? '#600060';
$bkcolor    = $attributes['bkcolor'] ?? '#800080';
$size       = $attributes['size'] ?? '66';

$inlineStyle = '';
if($mode == 'mini'){
    $inlineStyle .= '
        #'.esc_js($divId).'.mcv-aplayer.mcv-aplayer-narrow,#'.esc_js($divId).'.mcv-aplayer.mcv-aplayer-narrow .mcv-aplayer-body,#'.esc_js($divId).'.mcv-aplayer.mcv-aplayer-narrow .mcv-aplayer-pic{
            width:'.esc_js($size).'px;
            height:'.esc_js($size).'px;
        }
    ';
}


global $mcv_classes;
if ( mcv_is_wechat_miniprogram() ) {
    $mini_src = '';
    if( $audio ){
        $mini_src = $audio;
    }
    elseif( $aliyunAid ){
        $vod = $mcv_classes->Alivod;
        $endpoint = MINECLOUDVOD_SETTINGS['alivod']['endpoint'];
        $result = $vod->get_playurl($aliyunAid, $endpoint);
        $mini_src = $result['data']['mp4'];
    }

    echo '<audio poster="'.esc_url($thumbnail).'" name="'.esc_attr($title).'" author="" src="'.esc_url($mini_src).'" id="'.esc_attr($divId).'" controls loop></audio>';
    return;
}

$video_escaped = '<div id="'.esc_attr($divId).'" class="aplayer"><div class="aplayer-body"><center style="line-height:66px;">'.esc_html__('Audio is loading...', 'mine-cloudvod').'</center></div></div>';

$inlineScript_escaped = '
    jQuery(function(){
        const ap = new McvAPlayer({
            container: document.getElementById("'.esc_js($divId).'"),
            audio: {
                name: "'.esc_js($title).'",
                url: "'.esc_url($audio).'",
                artist:"",
                cover: "'.esc_url($thumbnail).'",
                lrc: "'.esc_url($lrc).'"
            },
            '.( $lrc ? 'lrcType: 3,' : '' ).'
            autoplay:'.($autoplay?'true':'false').',
            theme: "'.esc_js($color).'",
            loop: "one",
            mutex: true,
            '. ($mode == 'fixed' ? 'fixed:true,' : ($mode == 'mini:true,' ? 'mini:true,' : '')) .'
        });
    });
';
$inlineStyle .= '
    #'.esc_js($divId).' .mcv-aplayer-body{
        background-color:'.esc_js($bkcolor).';
    }
';
if(!$audio && $aliyunAid){
    $inlineScript_escaped = '
        jQuery(function(){
            jQuery.get("'.get_rest_url().'mine-cloudvod/v1/aliyun/vod/playurl",{vid: "'.esc_js($aliyunAid).'"}, function(data){
                
                const ap = new McvAPlayer({
                    container: document.getElementById("'.esc_js($divId).'"),
                    audio: [{
                        name: "'.esc_js($title).'",
                        url: data.data.mp4,
                        artist:"",
                        cover: "'.esc_url($thumbnail).'",
                        lrc: "'.esc_url($lrc).'"
                    }],
                    autoplay:'.($autoplay?'true':'false').',
                    theme: "'.esc_js($color).'",
                    loop: "one",
                    mutex: true,
                    '. ($mode == 'fixed' ? 'fixed:true,' : ($mode == 'mini:true,' ? 'mini:true,' : '')) .'
                });
                ap.on("noticeshow", function(text){
                    console.log(text);
                    return;
                });
            }, "json");
        });
    ';
}
$inlineScript_escaped = mcv_trim($inlineScript_escaped);
$inlineStyle = mcv_trim($inlineStyle);

wp_register_style( 'mcv-aplayer-inline-style', false, array(), MINECLOUDVOD_VERSION );
wp_enqueue_style( 'mcv-aplayer-inline-style' );
wp_add_inline_style( 'mcv-aplayer-inline-style', $inlineStyle );

$mcv_classes->Audioplayer::style_script();
wp_add_inline_script('mcv_aplayer', $inlineScript_escaped);

$video_escaped = apply_filters('mcv_filter_audioplayer', $video_escaped, $attributes, $inlineScript_escaped);

if (isset($enqueue) && !$enqueue) {
    echo '<style>' . wp_strip_all_tags($inlineStyle) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS 内容已 wp_strip_all_tags 剥离标签
}
echo $video_escaped; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 播放器 HTML 已逐段 esc_* 转义，含 center 标签无法 kses
if (isset($enqueue) && !$enqueue) {
    echo '<script>' . $inlineScript_escaped . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 内联脚本内容已 esc_js 转义
}