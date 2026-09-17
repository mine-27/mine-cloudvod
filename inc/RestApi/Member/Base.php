<?php
namespace MineCloudvod\RestApi\Member;

if ( ! defined( 'ABSPATH' ) )
    exit;

class Base{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    
    public function read_files_permissions_check(){
        return current_user_can('edit_posts');
    }
}
