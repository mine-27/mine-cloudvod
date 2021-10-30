<?php
namespace MineCloudvod\Aliyun;
use MineCloudvod\MineCloudVodAPI;

class Vod
{
    private $_wpcvApi;

    public function __construct(){
        $this->_wpcvApi     = new MineCloudVodAPI();
    }

    public function get_mediaUrl($videoId, $endpoint){
        $vinfo = $this->get_playinfo($videoId, $endpoint);
        if($vinfo['status'] == 1){
            $mp4 = $vinfo['data']['mp4'];
            return $mp4;
        }
        return false;
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
