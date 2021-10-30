<?php
namespace MineCloudvod\Qcloud;
use MineCloudvod\MineCloudVodAPI;

class Vod
{
    private $_wpcvApi;

    public function __construct(){
        $this->_wpcvApi     = new MineCloudVodAPI();
    }
    
    public function mcv_generate_psign($attachment_id, $appId, $fileId, $pcfg){
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
	public function mcv_get_tcvod_mediaUrl($fileId, $appID){
        $data = array('fileId'=>$fileId,'appId'=>$appID,'mode' => 'tcvod');
        $minfo = $this->_wpcvApi->call('mediainfo', $data);
        return $minfo;
    }
}
