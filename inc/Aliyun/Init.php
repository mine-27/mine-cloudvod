<?php
namespace MineCloudvod\Aliyun;

defined( 'ABSPATH' ) || exit;
    
class Init{
    public $Alivod, $Alilive, $Alioss;
    public function __construct() {
        $this->Init();
    }
    public function Init(){
        if( !isset(MINECLOUDVOD_SETTINGS['players']['aliplayer']) || !MINECLOUDVOD_SETTINGS['players']['aliplayer'] ) new Aliplayer();
        new \MineCloudvod\Aliyun\Options();
        $this->Alivod = new Vod();
        // $this->Alilive = new Live();
        $this->Alioss = new Oss();
        new \MineCloudvod\RestApi\AliyunVod();
        new \MineCloudvod\RestApi\AliyunOss();
    }
}