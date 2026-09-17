<?php
namespace MineCloudvod\LMS\Addons;
use MineCloudvod\RestApi\LMS\Base;
defined( 'ABSPATH' ) || exit;

class CouponCode extends Base{

    private $id = 'couponcode';
    protected $base = 'lms/couponcode';

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
        $tb_name = $wpdb->base_prefix.'mcv_coupon_code';
        $charset_collate = $wpdb->get_charset_collate();
        // status COMMENT '0未使用 1已使用 2作废'
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
            __('Coupon Code', 'mine-cloudvod'),
            __('Not started', 'mine-cloudvod'),
            __('In progress', 'mine-cloudvod'),
            __('Ended', 'mine-cloudvod'),
            __('Validity period', 'mine-cloudvod'),
            __('Status', 'mine-cloudvod'),
            __('Date Created', 'mine-cloudvod'),
            __('Used at', 'mine-cloudvod'),
            __('Order ID', 'mine-cloudvod'),
            __('User', 'mine-cloudvod'),
            __('Invalid', 'mine-cloudvod'),
            _x( 'Coupon Code', 'post type general name', 'mine-cloudvod' ),
            _x( 'Coupon', 'post type singular name', 'mine-cloudvod' ),
            _x( 'Coupon Code', 'admin menu', 'mine-cloudvod' ),
            _x( 'Coupon Code', 'add new on admin bar', 'mine-cloudvod' ),
            _x( 'Add New', "mcv order add", 'mine-cloudvod' ),
            __( 'Add New Coupon Code', 'mine-cloudvod' ),
            __( 'New Coupon Code', 'mine-cloudvod' ),
            __( 'Edit Coupon Code', 'mine-cloudvod' ),
            __( 'View Coupon Code', 'mine-cloudvod' ),
            __( 'Coupon Code', 'mine-cloudvod' ),
            __( 'Search Coupon Code', 'mine-cloudvod' ),
            __( 'Parent Coupon Code:', 'mine-cloudvod' ),
            __( 'No Coupon Code found.', 'mine-cloudvod' ),
            __( 'No Coupon Code found in Trash.', 'mine-cloudvod' ),
            __( 'Use coupon code to enhance course purchase rates.', 'mine-cloudvod' ),
            __('Coupon Code Infos', 'mine-cloudvod'),
            __('Internal remarks', 'mine-cloudvod'),
            __('Coupon settings', 'mine-cloudvod'),
            __('Validity period', 'mine-cloudvod'),
            __('Stock', 'mine-cloudvod'),
            __('Coupon Type', 'mine-cloudvod'),
            __('Exclusive Code', 'mine-cloudvod'),
            __('Generic Code', 'mine-cloudvod'),
            __('Promotion Type', 'mine-cloudvod'),
            __('Directly reduce', 'mine-cloudvod'),
            __('Discount', 'mine-cloudvod'),
            __('Reduce amount', 'mine-cloudvod'),
            __('When selecting Direct reduce, please enter the direct discount amount;<br />When selecting Discount, enter the discount percentage ratio. For example, if you enter "80" for 20% off, the price of 100 yuan will be sold as 80 yuan.','mine-cloudvod'),
            __('Applicable courses', 'mine-cloudvod'),
            __('All courses', 'mine-cloudvod'),
            __('Specified courses', 'mine-cloudvod'),
            __('Courses', 'mine-cloudvod'),
            __('Select some courses', 'mine-cloudvod'),
            __('Rules of use', 'mine-cloudvod'),
            __('Promotion Type', 'mine-cloudvod'),
            __('Unlimited', 'mine-cloudvod'),
            __('Limit times', 'mine-cloudvod'),
            __('Instructions for use', 'mine-cloudvod'),
            __('Invalid coupon code, or it has expired.', 'mine-cloudvod'),
            __( 'Login first, please.', 'mine-cloudvod' ),
            __( 'Invalid coupon code', 'mine-cloudvod' ),
            __( 'Invalid order id', 'mine-cloudvod' ),
            __( 'Order not exit.', 'mine-cloudvod' ),
            // translators: %s: number of times
            __('This coupon code can only be used %s time(s).', 'mine-cloudvod'),
            // translators: %1$s: start time, %2$s: end time
            __('This coupon code is valid from %1$s to %2$s.', 'mine-cloudvod'),
            __('This coupon code has expired', 'mine-cloudvod'), 
            // translators: %s: courses
            __('This coupon code is only applicable to the following courses.%s', 'mine-cloudvod'),
            _x('Submit', 'Submit to use the coupon code.', 'mine-cloudvod'),
            _x('Cancel', 'Cancel use the coupon code.', 'mine-cloudvod'),
            __('Unused', 'mine-cloudvod'),
            __('Used', 'mine-cloudvod'),
            __('Void', 'mine-cloudvod')
        ];
    }
}