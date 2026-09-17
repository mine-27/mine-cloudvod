<?php
namespace MineCloudvod\Dogecloud;

defined( 'ABSPATH' ) || exit;
    
class Init{
    public $Dogecloud, $DogecloudOss, $RestApi_Dogecloud;
    public function __construct() {
        $this->Init();
    }
    public function Init(){
        $this->Dogecloud            = new Vod();
        $this->DogecloudOss         = new Oss();
        $this->RestApi_Dogecloud    = new \MineCloudvod\RestApi\Dogecloud();
        if( !isset(MINECLOUDVOD_SETTINGS['players']['dplayer']) || !MINECLOUDVOD_SETTINGS['players']['dplayer'] ) new \MineCloudvod\Blocks\Dplayer();
    }
}