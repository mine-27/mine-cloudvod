<?php
namespace MineCloudvod\Qcloud;
use MineCloudvod\MineCloudVodAPI;

class Cos
{
    private $_wpcvApi;

    public function __construct(){
        $this->_wpcvApi     = new MineCloudVodAPI();
    }

    public function get_mediaUrl($objcet, $bucket){
        $data = array(
            'bucket'  => $bucket,
            'object' => $objcet,
            'mode' => 'tccos'
        );
        $playinfo = $this->_wpcvApi->call('geturl', $data);
        return $playinfo;
    }
    
}
