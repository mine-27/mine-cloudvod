<?php
namespace MineCloudvod\LMS\Addons;

defined( 'ABSPATH' ) || exit;

class Docs{

    private $id = 'docs';

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
            __( 'Docs', 'mine-cloudvod' ),
            __( 'Doc', 'mine-cloudvod' ),
            _x( 'Docs', 'Admin menu name', 'mine-cloudvod' ),
            __( 'Add Doc', 'mine-cloudvod' ),
            __( 'Add New Doc', 'mine-cloudvod' ),
            __( 'Edit', 'mine-cloudvod' ),
            __( 'Edit Doc', 'mine-cloudvod' ),
            __( 'New Doc', 'mine-cloudvod' ),
            __( 'View Doc', 'mine-cloudvod' ),
            __( 'View Doc', 'mine-cloudvod' ),
            __( 'Search Docs', 'mine-cloudvod' ),
            __( 'No Docs found', 'mine-cloudvod' ),
            __( 'No Docs found in trash', 'mine-cloudvod' ),
            __( 'Parent Doc', 'mine-cloudvod' ),
            __( 'Docs image', 'mine-cloudvod' ),
            __( 'Set Docs image', 'mine-cloudvod' ),
            __( 'Remove Docs image', 'mine-cloudvod' ),
            __( 'Use as Docs image', 'mine-cloudvod' ),
            __( 'Docs list', 'mine-cloudvod' ),
            __( 'Docs categories', 'mine-cloudvod' ),
            __( 'Category', 'mine-cloudvod' ),
            _x( 'Categories', 'Admin menu name', 'mine-cloudvod' ),
            __( 'Search categories', 'mine-cloudvod' ),
            __( 'All categories', 'mine-cloudvod' ),
            __( 'Parent category', 'mine-cloudvod' ),
            __( 'Parent category:', 'mine-cloudvod' ),
            __( 'Edit category', 'mine-cloudvod' ),
            __( 'Update category', 'mine-cloudvod' ),
            __( 'Add new category', 'mine-cloudvod' ),
            __( 'New category name', 'mine-cloudvod' ),
            __( 'No categories found', 'mine-cloudvod' ),
        ];
    
    }
}