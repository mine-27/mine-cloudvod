<?php
namespace MineCloudvod;

class MineCloudVod{
    private $_wpcvApi;
    public function __construct(){
        global $McvApi;
        $this->_wpcvApi     = $McvApi;
        add_action('wp_ajax_mcv_sync_endtime',          array($this, 'mcv_sync_endtime'));
        add_action('wp_ajax_mcv_buytimebug',            array($this, 'mcv_buytimebug'));
        //video block
        add_filter('render_block_data',                 array($this, 'mcv_render_block_data'), 6, 2);
    }


    /**
     * 同步到期时间
     */
    public function mcv_sync_endtime(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv_sync_endtime')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array();
        $endtime = $this->_wpcvApi->call('endtime', $data);
        if(isset($endtime['data']['endtime'])){
            $setting = MINECLOUDVOD_SETTINGS;
            $setting['endtime'] = $endtime['data']['endtime'];
            update_option('mcv_settings', $setting);
        }
        echo wp_json_encode($endtime);
        exit;
    }
    /**
     * 购买时长包
     */
    public function mcv_buytimebug(){
        if(!current_user_can('manage_options')){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv_buytimebug')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $timebug = !empty($_POST['timebug']) ? sanitize_text_field(wp_unslash($_POST['timebug'])) : null;
        $met = !empty($_POST['met']) ? sanitize_text_field(wp_unslash($_POST['met'])) : null;
        if(!is_numeric($timebug)){
            echo wp_json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('timebug' => intval($timebug),'met' => $met);
        $buytime = $this->_wpcvApi->call('buytime', $data);
        // var_dump($buytime, $data);
        echo wp_json_encode($buytime);
        exit;
    }
    /**
     * video block
     */
    public function mcv_render_block_data($parsed_block, $source_block){
        if ('mine-cloudvod/block-container' === $parsed_block['blockName'] && !empty($parsed_block['innerBlocks'])) {
            $pid = isset($parsed_block["post_id"]) ? $parsed_block["post_id"] : 0;
            $parsed_block = $parsed_block['innerBlocks'][0];
            if($pid)$parsed_block["post_id"] = $pid;
        }
        
        
        
        return $parsed_block;
    }
    

}
