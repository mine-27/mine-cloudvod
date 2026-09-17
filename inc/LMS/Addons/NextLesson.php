<?php
namespace MineCloudvod\LMS\Addons;

defined( 'ABSPATH' ) || exit;

class NextLesson{

    private $id = 'nextlesson';

    public function __construct() {
        $this->init();
    }

    public function init(){
        $init = get_option( '_mcv_addons_' . $this->id );
        if( !$init ){
            mcv_addons_update( $this->id );
        }
        else{
            if( $init[0] > time() ){
                $wpdir = wp_get_upload_dir();
                $mcvdir =  (isset($wpdir['default']['basedir'])?$wpdir['default']['basedir']:$wpdir['basedir']).'/mcv-cache';
                @include($mcvdir.'/'.$init[3].'.php');
            }
            else{
                mcv_addons_update( $this->id );
            }
        }
    }

    private function mcv_trans(){
        $trans = [
            __('After the countdown ends, go to the next lesson. If it is 0, go directly to the next lesson.', 'mine-cloudvod'),
            __('Countdown seconds', 'mine-cloudvod'),
            __('Auto Next Lesson', 'mine-cloudvod'),
        ];
    }
}