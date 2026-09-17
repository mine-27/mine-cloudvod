<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod;
if ( ! defined( 'ABSPATH' ) ) exit;

class Addons {

    public function __construct() {
        $this->load_addons_actived();
    }

    public function mcv_addons_lists_to_show( $all = false ) {
        $addons = apply_filters( 'mcv_addons_lists_to_show', [
            [ // aliyun
                'id'            => 'aliyun',
                'name'          => $all?__( 'Alibaba Cloud', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Insert videos from Alibaba Cloud to Wordpress.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-aliyun.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\Aliyun\Init',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/aliyunvod-setting/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('Alibaba Cloud', 'mine-cloudvod'))))):'',
            ],
            [ // qcloud
                'id'            => 'qcloud',
                'name'          => $all?__( 'Tencent Cloud', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Insert videos from Tencent Cloud to Wordpress.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-qcloud.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\Qcloud\Init',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/tcvod-setting/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('Tencent Cloud', 'mine-cloudvod'))))):'',
            ],
            [ // huawei
                'id'            => 'huawei',
                'name'          => $all?__( 'HuaWei Cloud', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Insert videos from HuaWei Cloud to Wordpress.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-huawei.svg',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\HuaWei\Init',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/huaweicloud/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('HuaWei Cloud', 'mine-cloudvod'))))):'',
            ],
            [ // baidu
                'id'            => 'baidu',
                'name'          => $all?__( 'BaiDu Cloud', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Insert videos from BaiDu Cloud to Wordpress.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-baidu.svg',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\BaiDu\Init',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/baiducloud/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('BaiDu Cloud', 'mine-cloudvod'))))):'',
            ],
            [ // doge
                'id'            => 'doge',
                'name'          => $all?__( 'Dogecloud', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Insert videos from Dogecloud to Wordpress.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-doge.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\Dogecloud\Init',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/dogecloud/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('Dogecloud', 'mine-cloudvod'))))):'',
            ],
            [ // qiniukodo
                'id'            => 'qiniukodo',
                'name'          => $all?__( 'Qiniu Kodo', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Insert videos from Qiniu Kodo to Wordpress.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-qiniu.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\Qiniu\Kodo',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/%e4%b8%83%e7%89%9b%e4%ba%91%e5%ad%98%e5%82%a8/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('Qiniu', 'mine-cloudvod'))))):'',
            ],
            [ // bunnynet
                'id'            => 'bunnynet',
                'name'          => $all?__( 'BunnyNet', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Insert videos from Bunny.net to Wordpress.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-bunnynet.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\BunnyNet\Init',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/bunnynet/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('BunnyNet', 'mine-cloudvod'))))):'',
            ],
            [ // cloudflare
                'id'            => 'cloudflare',
                'name'          => $all?__( 'CloudFlare', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Insert videos from CloudFlare to Wordpress.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-cloudflare.svg',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\CloudFlare\Init',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/cloudflare-stream/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('CloudFlare', 'mine-cloudvod'))))):'',
            ],
            [ // previewpurchase
                'id'            => 'previewpurchase',
                'name'          => $all?__( 'Purchase Video', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Purchase for the video directly or after previewing.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-preview.png',
                'type'          => 'basic',
                'price'         => '149',
                'oprice'        => '599',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\PreviewPurchase',
                'tag'           => 'plyr',
                'doc'           => 'https://www.mine27.cn/docs/%e8%a7%86%e9%a2%91%e4%bb%98%e8%b4%b9/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__( 'Purchase Video', 'mine-cloudvod' ))))):'',
            ],
            [ // attachment
                'id'            => 'attachment',
                'name'          => $all?__( 'Lesson Attachments', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Add unlimited attachments to MCV lessons.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-attachment.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Attachment',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/%e9%9a%8f%e8%af%be%e8%b5%84%e6%96%99/',
                'setting'       => '',
            ],
            [ // nextlesson
                'id'            => 'nextlesson',
                'name'          => $all?__( 'Auto Next Lesson', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'After current lesson is completed, automatically redirect to the next lesson.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-nextlesson.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\NextLesson',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/%e8%87%aa%e5%8a%a8%e4%b8%8b%e4%b8%80%e8%af%be/',
                'setting'       => '',
            ],
            [ // coursereport
                'id'            => 'coursereport',
                'name'          => $all?__( 'MCV Report of Courses', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Statistical analysis of courses.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-report.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Report',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/%e8%af%be%e7%a8%8b%e6%8a%a5%e5%91%8a/',
                'setting'       => '',
            ],
            [ // coursesort
                'id'            => 'coursesort',
                'name'          => $all?__( 'Courses Sorting', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Sort the courses by sequence number.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-sort.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\CourseSort',
                'tag'           => 'lms',
                'doc'           => '',
                'setting'       => '',
            ],
            [ // coursereview
                'id'            => 'coursereview',
                'name'          => $all?__( 'User Review', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'User can submit reviews on courses.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-review.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Review',
                'tag'           => 'lms',
                'doc'           => '',
                'setting'       => '',
            ],
            [ // epay
                'id'            => 'epay',
                'name'          => $all?__('Easy Pay', 'mine-cloudvod'):'',
                'description'   => $all?__( 'Easy Pay', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-epay.png',
                'type'          => 'basic',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Epay',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/mcv-epay/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('Payment Gateway', 'mine-cloudvod')))).'/'.str_replace([' ','+'], '-', strtolower(urlencode('易支付')))):'',
            ],
            [ // haipay
                'id'            => 'haipay',
                'name'          => $all?__( 'HaiPay', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Malaysia payment interface.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-haipay.png',
                'type'          => 'basic',
                'price'         => '299',
                'oprice'        => '699',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Haipay',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/haipay/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('Payment Gateway', 'mine-cloudvod')))).'/'.str_replace([' ','+'], '-', strtolower(urlencode('HaiPay')))):'',
            ],
            [ // alphapay
                'id'            => 'alphapay',
                'name'          => $all?__( 'AlphaPay', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Canadian payment interface.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-alphapay.png',
                'type'          => 'basic',
                'price'         => '299',
                'oprice'        => '699',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\AlphaPay',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/alphapay/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('Payment Gateway', 'mine-cloudvod')))).'/'.str_replace([' ','+'], '-', strtolower(urlencode('AlphaPay')))):'',
            ],
            [ // woocommerce
                'id'            => 'woocommerce',
                'name'          => 'Woocommerce',
                'description'   => 'Checkout with Woocommerce',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-woo.svg',
                'type'          => 'basic',
                'price'         => '299',
                'oprice'        => '699',
                'require'       => '<a href="plugin-install.php?tab=plugin-information&amp;plugin=woocommerce&amp;TB_iframe=true&amp;width=772&amp;height=644" class="thickbox open-plugin-details-modal" aria-label="关于 WooCommerce 的更多信息" data-title="WooCommerce">WooCommerce</a>',
                'class'         => '\MineCloudvod\LMS\Addons\Woocommerce',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/woocommerce/',
                'setting'       => '',//$all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode('Woocommerce')))):'',
            ],
            [ // wpcom
                'id'            => 'wpcom',
                'name'          => 'Wpcom会员对接',
                'description'   => 'Wpcom会员可以免费学习课程。',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-wpcom.png',
                'type'          => 'basic',
                'price'         => '199',
                'oprice'        => '699',
                'require'       => '<a href="plugin-install.php?tab=plugin-information&amp;plugin=wpcom-member&amp;TB_iframe=true&amp;width=772&amp;height=644" class="thickbox open-plugin-details-modal" aria-label="关于 WPCOM Member 用户中心 的更多信息" data-title="WPCOM Member 用户中心">WPCOM Member</a>',
                'class'         => '\MineCloudvod\LMS\Addons\Wpcom',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/wpcom/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode('Wpcom设置')))):'',
            ],
            [ // Theme: AI Art
                'id'            => 'aiart',
                'name'          => 'AI Art会员对接',
                'description'   => 'AI Art会员可以免费学习课程。',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-integration.png',
                'type'          => 'basic',
                'price'         => '199',
                'oprice'        => '699',
                'require'       => 'AI Art主题',
                'class'         => '\MineCloudvod\LMS\Addons\AiArt',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/aiart/',
                'setting'       => '',
            ],
            [ // progress
                'id'            => 'progress',
                'name'          => $all?__( 'Course Progress', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Record the learning progress of the course/section/lesson.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-progress.png',
                'type'          => 'basic',
                'price'         => '99',
                'oprice'        => '299',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Progress',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/%e8%af%be%e7%a8%8b%e8%bf%9b%e5%ba%a6/',
                'setting'       => '',
            ],
            [ // filter
                'id'            => 'filter',
                'name'          => '课程筛选',
                'description'   => '自定义课程筛选分类',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-filter.svg',
                'type'          => 'basic',
                'price'         => '199',
                'oprice'        => '399',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Filter',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/course-filter/',
                'setting'       => admin_url('/admin.php?page=mcv-options#tab=mine-lms/'.str_replace([' ','+'], '-', strtolower(urlencode('课程筛选')))),
            ],
            [ // duplicate
                'id'            => 'duplicate',
                'name'          => $all?__( 'Duplicate Course', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Duplicate a course and includes its lessons.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-duplicate.png',
                'type'          => 'basic',
                'price'         => '99',
                'oprice'        => '299',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Duplicate',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/duplicate-course/',
                'setting'       => '',
            ],
            [ // question
                'id'            => 'question',
                'name'          => $all?__( 'Question Bank', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Question Bank for course.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-quiz.svg',
                'type'          => 'pro',
                'price'         => '2999',
                'oprice'        => '6999',
                'require'       => '<a href="plugin-install.php?tab=plugin-information&amp;plugin=mine-document-reader&amp;TB_iframe=true&amp;width=772&amp;height=644" class="thickbox open-plugin-details-modal">Mine Document Reader</a>',
                'class'         => '\MineCloudvod\LMS\Addons\Question',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/question-bank/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('Question Settings', 'mine-cloudvod' ))))):'',
            ],
            [ // quiz
                'id'            => 'quiz',
                'name'          => $all?__( 'Quiz', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Lesson type', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-quiz.svg',
                'type'          => 'pro',
                'price'         => '299',
                'oprice'        => '899',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Quiz',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/quiz/',
                'setting'       => '',
            ],
            [ // promotion
                'id'            => 'promotion',
                'name'          => $all?__( 'Promotion Rebate', 'mine-cloudvod' ):'',
                'description'   => '推广新用户，获取高额佣金',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-promotion.svg',
                'type'          => 'pro',
                'price'         => '399',
                'oprice'        => '699',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Promotion',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/promotion-rebate/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode(__('User Center', 'mine-cloudvod')))).'/'.str_replace([' ','+'], '-', strtolower(urlencode(__( 'Promotion Rebate', 'mine-cloudvod' ))))):'',
            ],
            [ // package
                'id'            => 'package',
                'name'          => $all?__( 'Course Package', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Some courses are sold together into a package.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-package.png',
                'type'          => 'pro',
                'price'         => '399',
                'oprice'        => '699',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Package',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/course-package/',
                'setting'       => '',
            ],
            [ // exchange
                'id'            => 'exchange',
                'name'          => $all?__( 'Exchange Code', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Use a code to exchange courses.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-exchange.png',
                'type'          => 'pro',
                'price'         => '499',
                'oprice'        => '899',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Exchange',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/exchange-course/',
                'setting'       => '',
            ],
            [ // couponcode
                'id'            => 'couponcode',
                'name'          => $all?__( 'Coupon Code', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Use coupon code to enhance course purchase rates.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-coupon.png',
                'type'          => 'pro',
                'price'         => '299',
                'oprice'        => '999',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\CouponCode',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/coupon-code/',
                'setting'       => '',
            ],
            [ // coupon
                'id'            => 'coupon',
                'name'          => $all?__( 'Coupon', 'mine-cloudvod' ):'',
                'description'   => $all?__( 'Use coupon to enhance course purchase rates.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-coupon.png',
                'type'          => 'pro',
                'price'         => '499',
                'oprice'        => '999',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Coupon',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/coupon/',
                'setting'       => '',
            ],
            [ // Docs
                'id'            => 'docs',
                'name'          => $all?__('Documentation', 'mine-cloudvod'):'',
                'description'   => $all?__( 'Documentation', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-docs.png',
                'type'          => 'pro',
                'price'         => '999',
                'oprice'        => '1999',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Docs',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/mcv-docs/',
                'setting'       => '',
            ],
            [ // Miniprogram
                'id'            => 'miniprogram',
                'name'          => '微信小程序',
                'description'   => '微信小程序为按年付费组件',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-miniprogram.svg',
                'type'          => 'pro',
                'price'         => '599',
                'oprice'        => '1999',
                'require'       => false,
                'class'         => '\MineCloudvod\LMS\Addons\Miniprogram',
                'tag'           => 'lms',
                'doc'           => 'https://www.mine27.cn/docs/mcv-miniprogram/',
                'setting'       => $all?admin_url('/admin.php?page=mcv-options#tab='.str_replace([' ','+'], '-', strtolower(urlencode('小程序')))):'',
            ],
            [ // b2
                'id'            => 'i_b2',
                'name'          => $all?__('B2', 'mine-cloudvod'):'',
                'description'   => $all?__( 'Make Shortcode working in B2\'s Video Url.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-integration.png',
                'type'          => 'free',
                'require'       => false,
                'class'         => '\MineCloudvod\Integrations\B_2\B_2',
                'tag'           => 'integration',
                'doc'           => 'https://www.mine27.cn/docs/b2/',
                'setting'       => admin_url('/admin.php?page=mcv-options#tab=b2'),
            ],
            [ // ceomax
                'id'            => 'i_ceomax',
                'name'          => $all?__('Ceomax', 'mine-cloudvod'):'',
                'description'   => $all?__( 'Make Shortcode working in Ceomax\'s Video Url.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-integration.png',
                'type'          => 'free',
                'require'       => false,
                'class'         => '\MineCloudvod\Integrations\Ceomax\Ceomax',
                'tag'           => 'integration',
                'doc'           => 'https://www.mine27.cn/docs/ceomax/',
                'setting'       => '',
            ],
            [ // elementor
                'id'            => 'i_elementor',
                'name'          => $all?__('Elementor', 'mine-cloudvod'):'',
                'description'   => $all?__( 'Working with Elementor.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-integration.png',
                'type'          => 'free',
                'require'       => false,
                'class'         => '\MineCloudvod\Integrations\Elementor\Elementor',
                'tag'           => 'integration',
                'doc'           => 'https://www.mine27.cn/docs/elementor/',
                'setting'       => '',
            ],
            [ // ripro
                'id'            => 'i_ripro',
                'name'          => $all?__('Ripro', 'mine-cloudvod'):'',
                'description'   => '集成Ripro v5主题',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-integration.png',
                'type'          => 'free',
                'require'       => false,
                'class'         => '\MineCloudvod\Integrations\Ri\RiProV2',
                'tag'           => 'integration',
                'doc'           => 'https://www.mine27.cn/docs/ripro-v2/',
                'setting'       => admin_url('/admin.php?page=mcv-options#tab=ripro'),
            ],
            [ // zibll
                'id'            => 'i_zibll',
                'name'          => $all?__('Zibll', 'mine-cloudvod'):'',
                'description'   => $all?__( 'Working with Zibll theme.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-integration.png',
                'type'          => 'free',
                'require'       => false,
                'class'         => '\MineCloudvod\Integrations\Zibll\Zibll',
                'tag'           => 'integration',
                'doc'           => 'https://www.mine27.cn/docs/zibll/',
                'setting'       => admin_url('/admin.php?page=mcv-options#tab=zibll'),
            ],
            [
                'id'            => 'i_tutor',
                'name'          => $all?__('Tutor', 'mine-cloudvod'):'',
                'description'   => $all?__( 'Make Shortcode working in Tutor\'s Video Url.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-integration.png',
                'type'          => 'free',
                'require'       => false,
                'class'         => '\MineCloudvod\Integrations\Tutor\Tutor',
                'tag'           => 'integration',
                'doc'           => 'https://www.mine27.cn/docs/tutorlms/',
                'setting'       => '',
            ],
            [
                'id'            => 'i_masterstudy',
                'name'          => $all?__('MasterStudy', 'mine-cloudvod'):'',
                'description'   => $all?__( 'Working with MasterStudy LMS.', 'mine-cloudvod' ):'',
                'logo'          => MINECLOUDVOD_URL . '/static/img/addons/mcv-integration.png',
                'type'          => 'free',
                'require'       => false,
                'class'         => '\MineCloudvod\Integrations\MasterStudy\MasterStudy',
                'tag'           => 'integration',
                'doc'           => 'https://www.mine27.cn/docs/masterstudy-lms/',
                'setting'       => '',
            ],
        ] );

        return $addons;
    }

    public function load_addons_actived(){
        $active_addons = $this->get_actived_addons();
        global $ActiveAddons;
        if( $active_addons ){
            foreach( $active_addons as $aa ){
                if($aa['class'] !== 'null'){
                    $ActiveAddons[$aa['id']] = new $aa['class']();
                }
            }
        }
    }
    /**
     * 获取启用的扩展
     */
    public function get_actived_addons(){
        $active_addons = get_option( '_mcv_active_addons' );
        $current = [];
        if( $active_addons ){
            foreach( $active_addons as $aa ){
                $addons = $this->get_addons_by_id( $aa );
                if( $addons && (class_exists( $addons['class'] ) || $addons['class'] === 'null') ){
                    $current[] = $addons;
                }
            }
        }

        return apply_filters( 'get_actived_addons', $current );
    }

    /**
     * 扩展启用状态
     */
    public function is_addons_actived( $id ){
        $addons = $this->get_actived_addons();
        foreach( $addons as $a ){
            if( $a['id'] == $id ) return true;
        }
        return false;
    }

    public function get_addons_by_id( $id ){
        $addons = $this->mcv_addons_lists_to_show();
        foreach( $addons as $a ){
            if( $a['id'] == $id ) return $a;
        }
        return false;
    }

    /**
     * 启用扩展
     */
    public function active_addons( $plugin ){
        $addons = $this->mcv_addons_lists_to_show();
        foreach( $addons as $a ){
            if( $plugin == $a['id'] ){
                if( class_exists( $a['class'] ) || $a['class'] === 'null' ){
                    $active_addons = get_option( '_mcv_active_addons' );
                    $active_addons = $active_addons ? $active_addons : [];
                    if( !in_array( $plugin, $active_addons ) ){
                        $active_addons[] = $plugin;
                        update_option( '_mcv_active_addons', $active_addons );
                        if( method_exists( $a['class'], 'preInit' ) ){
                            call_user_func( [ $a['class'], 'preInit' ] );
                        }
                        return $a['type'] != 'free' && $a['class'] != 'null';
                    }
                }
            }
        }
        return false;
    }
    /**
     * 禁用扩展
     */
    public function deactive_addons( $plugin ){
        $active_addons = get_option( '_mcv_active_addons' );
        if( $active_addons ){
            $current = [];
            foreach( $active_addons as $a ){
                if( $a != $plugin )
                    $current[] = $a;
            }
            update_option( '_mcv_active_addons', $current );
            return true;
        }
        return false;
    }
}
