<?php
namespace MineCloudvod\RestApi\Agent;

if ( ! defined( 'ABSPATH' ) )
    exit;

/**
 * Agent REST 模块入口
 *
 * 在 mine-cloudvod.php 主入口实例化：
 *   new MineCloudvod\RestApi\Agent\Init();
 *
 * 加载顺序：Course / Section / Lesson 三个资源控制器
 */
class Init{

    public function __construct(){
        new Course();
        new Section();
        new Lesson();
    }
}
