<?php
namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;
    
class Init{
    public function __construct() {
        mcv_lms_load_templates_functions();
        new \MineCloudvod\RestApi\LMS\Course();
        new \MineCloudvod\RestApi\LMS\Section();
        new \MineCloudvod\RestApi\LMS\Lesson();

        new \MineCloudvod\LMS\Options();
        new \MineCloudvod\LMS\PostType();
        new \MineCloudvod\LMS\Metabox();
        new \MineCloudvod\LMS\Template();
        new \MineCloudvod\LMS\RewriteRules();
        // new MineCloudvod\LMS\Patterns();
        new \MineCloudvod\LMS\Blocks\Course();
        new \MineCloudvod\LMS\Blocks\BlockTemplates();

        new \MineCloudvod\Member\Options();
        new \MineCloudvod\RestApi\Member\Order();
        new \MineCloudvod\RestApi\Member\Login();
        new \MineCloudvod\Order\PostType();
    }
}