<?php
namespace MineCloudvod\LMS\Addons;

defined( 'ABSPATH' ) || exit;

class Promotion{

    private $id = 'promotion';

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
        $tb_name = $wpdb->base_prefix.'mcv_promotion_record';
        $charset_collate = $wpdb->get_charset_collate();
        // status COMMENT '0未提现 1已提现 2作废 10提现未支付 11提现已支付'
        // 为提现记录时，remark保存提现包含的所有返佣ID
        $sql = "CREATE TABLE `".$tb_name."` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id INT,
        user_id INT,
        referral_id INT,
        order_price varchar(50),
        ratio INT,
        status TINYINT NOT NULL DEFAULT '0',
        created_at datetime NOT NULL,
        remark text NULL,
        PRIMARY KEY  (id)
        ) ".$charset_collate.";";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    private function mcv_trans(){
        $trans = [
            __('Promotion Rebate', 'mine-cloudvod'),
            __('Rebate Amount', 'mine-cloudvod'),
            __('Referral', 'mine-cloudvod'),
            __('Date Created', 'mine-cloudvod'),
            __('Login first, please.', 'mine-cloudvod'),
        ];
    }
}