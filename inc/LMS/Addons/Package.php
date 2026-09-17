<?php
namespace MineCloudvod\LMS\Addons;
use MineCloudvod\RestApi\LMS\Base;
defined( 'ABSPATH' ) || exit;

class Package extends Base{

    private $id = 'package';
    protected $base = 'lms/package';

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
            __('Packages', 'mine-cloudvod'),
            _x( 'Packages', 'post type general name', 'mine-cloudvod' ),
            _x( 'Package', 'post type singular name', 'mine-cloudvod' ),
            _x( 'Packages', 'admin menu', 'mine-cloudvod' ),
            _x( 'Packages', 'add new on admin bar', 'mine-cloudvod' ),
            _x( 'Add New', "mcv order add", 'mine-cloudvod' ),
            __( 'Add New Package', 'mine-cloudvod' ),
            __( 'New Package', 'mine-cloudvod' ),
            __( 'Edit Package', 'mine-cloudvod' ),
            __( 'View Package', 'mine-cloudvod' ),
            __( 'Packages', 'mine-cloudvod' ),
            __( 'Search Packages', 'mine-cloudvod' ),
            __( 'Parent Packages:', 'mine-cloudvod' ),
            __( 'No Packages found.', 'mine-cloudvod' ),
            __( 'No Packages found in Trash.', 'mine-cloudvod' ),
            __( 'Course Package.', 'mine-cloudvod' ),
            __('Package Infos', 'mine-cloudvod'),
            __('Package Price', 'mine-cloudvod'),
            __('Select with courses', 'mine-cloudvod'),
            __('Select some courses', 'mine-cloudvod'),
        ];
    }
}