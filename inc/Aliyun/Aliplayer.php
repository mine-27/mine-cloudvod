<?php
namespace MineCloudvod\Aliyun;

class Aliplayer
{
    public function __construct(){
        if( \WP_Block_Type_Registry::get_instance()->is_registered('mine-cloudvod/aliplayer') ) return;
        add_action( 'mcv_add_admin_options_after_aliplayer', array( $this, 'mcv_admin_options' ) );
        add_action( 'init',     [ $this, 'mcv_register_block'] );
    }

    public function mcv_register_block(){
        wp_register_script(
            'mcv_aliplayer_components',
            MINECLOUDVOD_URL.'/static/aliyun/aliplayercomponents-1.0.6.min.js',
            array(  ),
            MINECLOUDVOD_VERSION,
            true
        );
        wp_register_script(
            'mcv_aliplayer',
            MINECLOUDVOD_ALIPLAYER['js'],
            array( 'jquery','mcv_layer' ),
            MINECLOUDVOD_VERSION,
            true
        );

        register_block_type( MINECLOUDVOD_PATH . '/build/aliplayer/');
        $this->style_script();
        $uid = get_current_user_id();
        // endtime 缺失时 strtotime() 返回 false，会拼出 `endtime:,` 的非法 JS，
        // 使本段内联里的 mcv_alivod_config / mcv_aliplayer_config / mcv_nonce 全部失效
        wp_add_inline_script('mcv_alivod_sdk','var mcv_alivod_config={endpoint:"'.(MINECLOUDVOD_SETTINGS['alivod']['endpoint']??'').'",userId:"'.(MINECLOUDVOD_SETTINGS['alivod']['userId']??'').'",nonce:"'.wp_create_nonce('mcv-aliyunvod-'.$uid).'",down_snapshot:'.(isset(MINECLOUDVOD_SETTINGS['alivod']['down_snapshot'])&&MINECLOUDVOD_SETTINGS['alivod']['down_snapshot']?MINECLOUDVOD_SETTINGS['alivod']['down_snapshot']:'false').',sdk:'.(MINECLOUDVOD_SETTINGS['alivod']['accessKeyID']??MINECLOUDVOD_SETTINGS['alivod']['accessKeyID']??false ? 'true' : 'false').',aliyun_config_url:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Alibaba Cloud', 'mine-cloudvod'))))).'"};var mcv_aliplayer_config={slide:'.(!empty(MINECLOUDVOD_SETTINGS['aliplayer_slide']['status'])?'true':'false').'};var mcv_nonce={ajaxUrl:"'.admin_url("admin-ajax.php").'",et:"'.wp_create_nonce('mcv_sync_endtime').'",endtime:'.(int) strtotime( (string) ( MINECLOUDVOD_SETTINGS['endtime'] ?? '' ) ).', buynow:"'.admin_url('/admin.php?page=mcv-options#tab='.str_replace(' ', '-', strtolower(urlencode(__('Purchase time', 'mine-cloudvod'))))).'", restRootUrl:"'.get_rest_url().'"};');
    }

    public function mcv_block_aliplayer($parsed_block, $enqueue = true){
        $attributes = $parsed_block['attrs'];

        ob_start();
        include(MINECLOUDVOD_PATH.'/build/aliplayer/render.php');
        $video = ob_get_clean();
        
        return $video;
    }
    public static function style_script(){
        
        $slideStyle = '';
        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_slide']['status']) {
            if(isset(MINECLOUDVOD_SETTINGS['aliplayer_slide']['duration']) && MINECLOUDVOD_SETTINGS['aliplayer_slide']['duration']){
                $slideStyle .= '.bullet-screen{' . MINECLOUDVOD_SETTINGS['aliplayer_slide']['style'] . 'animation-duration: '.MINECLOUDVOD_SETTINGS['aliplayer_slide']['duration'].'s !important;' . '}';
            }
            else{
                $slideStyle .= '.bullet-screen{' . MINECLOUDVOD_SETTINGS['aliplayer_slide']['style'] . '}';
            }
        }
        $sticky = '';
        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_sticky']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_sticky']['status']) {
            $sticky_position = 'right:5px;bottom:5px;';
            switch (MINECLOUDVOD_SETTINGS['aliplayer_sticky']['position']) {
                case 'rt':
                    $sticky_position = 'right:5px;top:5px;';
                    break;
                case 'lb':
                    $sticky_position = 'left:5px;bottom:5px;';
                    break;
                case 'lt':
                    $sticky_position = 'left:5px;top:5px;';
                    break;
            }
            $width_pc       = 35;
            $width_tablet   = 50;
            $width_mobile   = 90;
            if (isset(MINECLOUDVOD_SETTINGS['aliplayer_sticky']['width'])) {
                $width_pc       = MINECLOUDVOD_SETTINGS['aliplayer_sticky']['width']['pc'];
                $width_tablet   = MINECLOUDVOD_SETTINGS['aliplayer_sticky']['width']['tablet'];
                $width_mobile   = MINECLOUDVOD_SETTINGS['aliplayer_sticky']['width']['mobile'];
            }
            $height_pc      = $width_pc     * 0.5625;
            $height_tablet  = $width_tablet * 0.5625;
            $height_mobile  = $width_mobile * 0.5625;

            $sticky = '.mcv-fixed{position:fixed;z-index:99999;width:' . $width_pc . '% !important;height:auto !important;padding-top:' . $height_pc . '%;' . $sticky_position . '-webkit-animation: fadeInDown .5s .2s ease both; -moz-animation: fadeInDown .5s .2s ease both;}@keyframes fade-in {0% {opacity: 0;}40% {opacity: 0;}100% {opacity: 1;}}@-webkit-keyframes fade-in { 0% {opacity: 0;}  40% {opacity: 0;}100% {opacity: 1;}}@-webkit-keyframes fadeInDown{0%{opacity: 0; -webkit-transform: translateY(-10px);} 100%{opacity: 1; -webkit-transform: translateY(0);}}@-moz-keyframes fadeInDown{0%{opacity: 0; -moz-transform: translateY(-10px);} 100%{opacity: 1; -moz-transform: translateY(0);}}@media (max-width: 1024px) {.mcv-fixed{width:' . $width_tablet . '% !important;padding-top:' . $height_tablet . '%;}}@media (max-width: 450px) {.mcv-fixed{width:' . $width_mobile . '% !important;padding-top:' . $height_mobile . '%;}}
            .mcv-fixed .prism-controlbar,.mcv-fixed .preview-component-tip,.mcv-fixed .memory-play-wrap{display:none !important;}
            ';
        }
        $inlineStyle = '';
        if (isset(MINECLOUDVOD_SETTINGS['aliplayerconfig']['controlColor']) && MINECLOUDVOD_SETTINGS['aliplayerconfig']['controlColor']) {
            $inlineStyle .= '.prism-player .prism-controlbar .prism-controlbar-bg{background:'.MINECLOUDVOD_SETTINGS['aliplayerconfig']['controlColor'].'}';
        }
        $inlineStyle .= html_entity_decode(MINECLOUDVOD_SETTINGS['aliplayercss'] ?? '.prism-player{width: 100%;height: auto;padding-top: 56.25%;}');
        $inlineStyle .=  $slideStyle ;
        $inlineStyle .= $sticky;
        $inlineStyle = mcv_trim($inlineStyle);
        // wp_enqueue_script( 'jquery' );
        // wp_enqueue_script( 'mcv_aliplayer' );
        wp_add_inline_style('mine-cloudvod-aliyun-vod-style', $inlineStyle);
        wp_add_inline_style('mine-cloudvod-aliplayer-style', $inlineStyle);
    }
    public function mcv_admin_options(){
        $prefix = 'mcv_settings';
        include MINECLOUDVOD_PATH . '/inc/options/aliplayer.php';
        include MINECLOUDVOD_PATH . '/inc/options/aliplayer_components.php';
    }
}
