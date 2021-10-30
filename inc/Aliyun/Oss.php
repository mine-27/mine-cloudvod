<?php
namespace MineCloudvod\Aliyun;
use MineCloudvod\MineCloudVodAPI;

class Oss
{
    private $_wpcvApi;

    public function __construct(){
        $this->_wpcvApi     = new MineCloudVodAPI();
    }

    public function get_mediaUrl($objcet, $bucket){
        $data = array(
            'bucket'  => $bucket,
            'object' => $objcet,
            'mode' => 'alioss'
        );
        $playinfo = $this->_wpcvApi->call('geturl', $data);
        return $playinfo;
    }
    
    public function get_playinfo($videoId, $endpoint){
        $data = array(
            'endpoint'  => $endpoint,
            'videoId' => $videoId,
            'mode' => 'alivod'
        );
        $playinfo = $this->_wpcvApi->call('playauth', $data);
        if($playinfo['hls']){
            $at = isset(MINECLOUDVOD_SETTINGS['alivod']['token'])?MINECLOUDVOD_SETTINGS['alivod']['token']:'';
            $token = new \MineCloudvod\Ability\Token($at);
            $playinfo['hls'] = str_replace('.m3u8"', '.m3u8?MtsHlsUriToken='.$token->generrate_token().'"', $playinfo['hls']);
        }
        return $playinfo;
    }
}
