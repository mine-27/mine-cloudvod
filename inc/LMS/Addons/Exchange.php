<?php
namespace MineCloudvod\LMS\Addons;
use MineCloudvod\RestApi\LMS\Base;
defined( 'ABSPATH' ) || exit;

class Exchange extends Base{

    private $id = 'exchange';
    protected $base = 'lms/exchange';

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
    public static function preInit(){
        global $wpdb;
        $tb_name = $wpdb->base_prefix.'mcv_exchange_code';
        $charset_collate = $wpdb->get_charset_collate();
        // status COMMENT '0未使用 1已使用 2作废'
        // order_id COMMENT '1用码 2邀请'
        $sql = "CREATE TABLE `".$tb_name."` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id INT NOT NULL,
        code varchar(255) NOT NULL,
        status TINYINT NOT NULL DEFAULT '0',
        created_at datetime NOT NULL,
        used_at datetime,
        order_id INT,
        user_id INT,
        remark varchar(255),
        PRIMARY KEY  (id)
        ) ".$charset_collate.";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function mcv_trans(){
        $trans = [
            __('Exchange Code', 'mine-cloudvod'),
            __('Not started', 'mine-cloudvod'),
            __('In progress', 'mine-cloudvod'),
            __('Ended', 'mine-cloudvod'),
            __('Validity period', 'mine-cloudvod'),
            __('Status', 'mine-cloudvod'),
            __('Date Created', 'mine-cloudvod'),
            __('Exchange time', 'mine-cloudvod'),
            __('Order ID', 'mine-cloudvod'),
            __('User', 'mine-cloudvod'),
            __('Invalid', 'mine-cloudvod'),
            _x( 'Exchange Code', 'post type general name', 'mine-cloudvod' ),
            _x( 'Exchange', 'post type singular name', 'mine-cloudvod' ),
            _x( 'Exchange Code', 'admin menu', 'mine-cloudvod' ),
            _x( 'Exchange', 'add new on admin bar', 'mine-cloudvod' ),
            _x( 'Add New', "mcv order add", 'mine-cloudvod' ),
            __( 'Add New Exchange', 'mine-cloudvod' ),
            __( 'New Exchange', 'mine-cloudvod' ),
            __( 'Edit Exchange', 'mine-cloudvod' ),
            __( 'View Exchange', 'mine-cloudvod' ),
            __( 'Exchange', 'mine-cloudvod' ),
            __( 'Search Exchange', 'mine-cloudvod' ),
            __( 'Parent Exchange:', 'mine-cloudvod' ),
            __( 'No Exchange found.', 'mine-cloudvod' ),
            __( 'No Exchange found in Trash.', 'mine-cloudvod' ),
            __( 'Use a code to exchange courses.', 'mine-cloudvod' ),
            __('Exchange Infos', 'mine-cloudvod'),
            __('Stock', 'mine-cloudvod'),
            __('Courses', 'mine-cloudvod'),
            __('Select some courses', 'mine-cloudvod'),
            __('Exchange', 'mine-cloudvod'),
            __('Please enter the exchange code', 'mine-cloudvod'),
            __('Exchange failure', 'mine-cloudvod'),
            __('Exchange successful', 'mine-cloudvod'),
            __('My courses', 'mine-cloudvod'),
            __( 'Login first, please.', 'mine-cloudvod' ),
            __( 'Invalid exchange code', 'mine-cloudvod' ),
            __('Invite', 'mine-cloudvod'),
            __('Click the button to view enrolled courses', 'mine-cloudvod'),
        ];
    }
}