<?php
if ( ! defined( 'ABSPATH' ) ) exit;
return '<div style="display: flex;justify-content: center;align-items: center;flex-direction: column;background-color:#2a3852;width:100%;height:70px;font-size:20px;"><a href="'. esc_url(wp_login_url(get_permalink())). '" style="color:#FFF;text-decoration:none;">'. esc_html__("Please login for access.", "mine-cloudvod").'</a></div>';