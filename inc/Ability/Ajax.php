<?php
namespace MineCloudvod\Ability;
use MineCloudvod\MineCloudVodAPI;

class Ajax
{
    private $_wpcvApi;
    public function __construct()
    {
        $this->_wpcvApi     = new MineCloudVodAPI();
        add_action('wp_ajax_mcv_asyc_alioss_buckets', array($this, 'mcv_asyc_alioss_buckets'));
        add_action('wp_ajax_mcv_asyc_tccos_buckets', array($this, 'mcv_asyc_tccos_buckets'));
        //add_action('wp_ajax_nopriv_mcv_alivod_upload', array($mineCloudVod, 'mcv_alivod_upload'));
    }

    public function mcv_asyc_alioss_buckets(){
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_alioss_buckets')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('bucket'=>'mcv','mode' => 'alioss');
        $buckets = $this->_wpcvApi->call('buckets', $data);
        update_option('mcv_alioss_bucketsList', $buckets['data']);
        echo json_encode($buckets);
        exit;
    }

    public function mcv_asyc_tccos_buckets(){
        if(!current_user_can('manage_options')){
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        header('Content-type:application/json; Charset=utf-8');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, 'mcv_asyc_tccos_buckets')) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $data = array('bucket'=>'mcv','mode' => 'tccos');
        $buckets = $this->_wpcvApi->call('buckets', $data);
        update_option('mcv_tccos_bucketsList', $buckets['data']);
        echo json_encode($buckets);
        exit;
    }
}
