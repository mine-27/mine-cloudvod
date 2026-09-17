<?php
namespace MineCloudvod\Qcloud;

defined( 'ABSPATH' ) || exit;
    
class Init{
    public $Tcvod, $Tccos, $Tcplayer;
    public function __construct() {
        $this->Init();
    }
    public function Init(){
        new Options();
        $this->Tcvod = new Vod();
        $this->Tccos = new Cos();
        new \MineCloudvod\RestApi\QcloudVod();
        new \MineCloudvod\RestApi\QcloudCos();
        $this->Tcplayer = new Tcplayer();
    }
}