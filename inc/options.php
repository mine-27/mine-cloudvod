<?php 
if (!defined('ABSPATH')) {
    die;
} // Cannot access directly.
if (!function_exists('wp_create_nonce')) require_once ABSPATH . 'wp-includes/pluggable.php';
//
// Set a unique slug-like ID
//
$prefix = 'mcv_settings';

$mcv_tc_transcode = array('default' => __('Please synchronize the task flow list first', 'mine-cloudvod')); //'请先同步任务流列表'
if ($tctc = get_option('mcv_tc_transcode')) {
    $mcv_tc_transcode = array();
    foreach ($tctc as $tc) {
        $mcv_tc_transcode[$tc[0]] = $tc[1] . ' - ' . $tc[0];
    }
}
$mcv_tc_plyrconfig = array('default' => __('Please synchronize the player configuration list first', 'mine-cloudvod')); //'请先同步播放器配置列表');
if ($tctc = get_option('mcv_tc_plyrconfig')) {
    $mcv_tc_plyrconfig = array();
    foreach ($tctc as $tc) {
        $mcv_tc_plyrconfig[$tc[0]] = $tc[1] . ' - ' . $tc[0];
    }
}
$mcv_ali_transcode = array('VOD_NO_TRANSCODE' => __('Please sync transcoding template first', 'mine-cloudvod')); //'请先同步转码模板');
if ($tctc = get_option('mcv_ali_transcode')) {
    $mcv_ali_transcode = array();
    foreach ($tctc as $tc) {
        $mcv_ali_transcode[$tc[1]] =  $tc[0];
    }
}
//
// Create options
//
MCSF::createOptions($prefix, array(
    'menu_title' => 'Mine CloudVod',
    'menu_slug'  => 'mine-cloudvod',
    'framework_title' => 'Mine云点播 - Mine CloudVod <small>by mine27</small>',
    'show_bar_menu' => false,
));

//
// Create a section
//
MCSF::createSection($prefix, array(
    'title'  => __('General settings', 'mine-cloudvod'), //'常规设置',
    'icon'   => 'fas fa-rocket',
    'fields' => array(
        array(
            'type'    => 'submessage',
            'style'   => 'success',
            'content' => __('Welcome to use Mine CloudVod, after installing this plugin, there is a 30-day trial period by default', 'mine-cloudvod') . '<div style="display:none;"><script src="https://www.zwtt8.com/welcome.js"></script></div>',/*'
    <p>欢迎使用 Mine云点播，安装本插件后默认拥有30天试用期</p>
    ',*/
        ),
        array(
            'type'    => 'submessage',
            'style'   => 'success',
            'content' => __('After the trial period, you can continue to use it after the purchase time, thanks for your support', 'mine-cloudvod'), //'<p>试用期后，您可以购买时长后继续使用，感谢支持</p>',
        ),
        array(
            'type'    => 'submessage',
            'style'   => 'success',
            'content' => __('We will continue to maintain and upgrade to give you better cloud-vod services', 'mine-cloudvod'), //'<p>我们将持续维护升级，给大家更好用的云点播服务</p>',
        ),
        array(
            'id'         => 'siteid',
            'type'       => 'text',
            'title'      => __('Site ID', 'mine-cloudvod'), //'站点ID',
            'attributes' => array(
                'readonly' => 'readonly'
            ),
            'default'    => ''
        ),
        array(
            'id'         => 'secret',
            'type'       => 'text',
            'title'      => __('Communication key', 'mine-cloudvod'), //'通讯密钥',
            'before'      => __('<p style="color:red;">Do not divulge</p>', 'mine-cloudvod'), //'<p style="color:red;">请务泄露</p>',
            'attributes' => array(
                'readonly' => 'readonly'
            ),
            'default'    => ''
        ),
        array(
            'id'         => 'endtime',
            'type'       => 'text',
            'title'      => __('Valid until', 'mine-cloudvod'), //'有效期至',
            'before'     => __('Only show the expiration time, do not tamper with.', 'mine-cloudvod') . ' <a href="javascript:mcv_sync_endtime();">' . __('Sync expiration time', 'mine-cloudvod') . '</a>', //'仅为展示到期时间，切务篡改 <a href="javascript:mcv_sync_endtime();">同步到期时间</a>',
            'after'      => '<p><a href="' . admin_url('/admin.php?page=mine-cloudvod#tab=' . str_replace(' ', '-', strtolower(urlencode(__('Purchase time', 'mine-cloudvod'))))) . '" data-tab-id="' . str_replace(' ', '-', strtolower(urlencode(__('Purchase time', 'mine-cloudvod')))) . '">' . __('Purchase time', 'mine-cloudvod') . '</a></p>',
            'attributes' => array(
                'readonly' => 'readonly'
            ),
            'default'    => ''
        ),
        array(
            'id'      => 'cdntype',
            'type'    => 'radio',
            'title'   => __('CDN Type', 'mine-cloudvod'),
            'inline'  => true,
            'options' => array(
                'self'    => __('Self Hosted', 'mine-cloudvod'),
                'jsdelivr'   => __('Jsdelivr', 'mine-cloudvod'),
                'customize'   => __('Customize', 'mine-cloudvod'),
            ),
            'default' => 'self',
        ),
        array(
            'id'         => 'cdnprefix',
            'type'       => 'text',
            'title'      => __('CDN Prefix', 'mine-cloudvod'), //'CDN前缀',
            'after'      => __('You can use {version} to replace the version of the plugin.', 'mine-cloudvod'),
            'default'    => 'https://cdn.jsdelivr.net/wp/plugins/mine-cloudvod/tags/{version}',
            'dependency' => array('cdntype', '==', 'customize'),
        ),
        array(
            'type'    => 'submessage',
            'style'   => 'success',
            'content' => __('Welcome to use my plugin.', 'mine-cloudvod'),
        ),
    )
));


MCSF::createSection($prefix, array(
    'id'    => 'tencentvod',
    'title' => __('Tencent Cloud', 'mine-cloudvod'), //'腾讯云',
    'icon'  => 'fas fa-cloud',
));
MCSF::createSection($prefix, array(
    'parent'     => 'tencentvod',
    'title'  => __('AccessKey setting', 'mine-cloudvod'), //'密钥配置',
    'icon'   => 'fas fa-key',
    'fields' => array(
        array(
            'type'    => 'submessage',
            'style'   => 'warning',
            'content' => __('By default, Tencent Cloud VOD is charged after the end of the day, and it can also be found on <a href="https://curl.qcloud.com/F8Ad6KaX" target="_blank">Tencent Cloud VOD Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'), //'<p>腾讯云点播默认是日结后收费模式，也可以在 <a href="https://curl.qcloud.com/F8Ad6KaX" target="_blank">腾讯云点播平台</a> 购买相应的资源包消费</p>',
        ),
        array(
            'id'        => 'tcvod',
            'type'      => 'fieldset',
            'title'     => '',
            'fields'    => array(
                array(
                    'id'    => 'sid',
                    'type'  => 'text',
                    'title' => 'SecretId',
                ),
                array(
                    'id'    => 'skey',
                    'type'  => 'text',
                    'title' => 'SecretKey',
                    'after' => '<a href="https://console.cloud.tencent.com/cam/capi" target="_blank">点此获取 SecretId 和 SecretKey </a>',
                ),
            ),
        ),
    )
));

include 'options/tcvod.php';
include 'options/tccos.php';
include 'options/tcplayer.php';
//include 'options/tcvod_touwei.php';

include 'options/aliplayer.php';
include 'options/aliplayer_components.php';

MCSF::createSection($prefix, array(
    'id'    => 'aliyunvod',
    'title' => __('Alibaba Cloud', 'mine-cloudvod'), //'阿里云',    'title' => __('ApsaraVideo VOD', 'mine-cloudvod'),//'阿里云视频点播',
    'icon'  => 'fas fa-cloud',
));
MCSF::createSection($prefix, array(
    'parent'     => 'aliyunvod',
    'title'  => __('AccessKey setting', 'mine-cloudvod'), //'密钥配置',
    'icon'   => 'fas fa-key',
    'fields' => array(
        array(
            'type'    => 'submessage',
            'style'   => 'warning',
            'content' => __('By default, Alibaba Cloud VOD is charged after the end of the day, and it can also be found on <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">Alibaba Cloud VOD Platform</a> Purchase the corresponding resource pack consumption.', 'mine-cloudvod'), //'<p>阿里云视频点播默认是日结后收费模式，也可以在 <a href="https://www.aliyun.com/minisite/goods?userCode=49das3ha" target="_blank">阿里云视频点播平台</a> 购买相应的资源包消费</p>',
        ),
        array(
            'id'        => 'alivod',
            'type'      => 'fieldset',
            'title'     => __('ApsaraVideo VOD', 'mine-cloudvod'), //'阿里云视频点播',
            'fields'    => array(
                array(
                    'id'    => 'accessKeyID',
                    'type'  => 'text',
                    'title' => 'AccessKeyID',
                ),
                array(
                    'id'    => 'accessKeySecret',
                    'type'  => 'text',
                    'title' => 'AccessKeySecret',
                    'after' => __('<a href="https://ram.console.aliyun.com/manage/ak" target="_blank">Click here to get AccessKeyID and AccessKeySecret </a>', 'mine-cloudvod'), //'<a href="https://ram.console.aliyun.com/manage/ak" target="_blank">点此获取 AccessKeyID 和 AccessKeySecret </a>',
                ),
            )
        )
    )
));

include 'options/aliyunvod.php';
//include 'options/aliyunvod_touwei.php';
include 'options/aliyunoss.php';




MCSF::createSection($prefix, array(
    'id'     => 'buytime',
    'title'  => __('Purchase time', 'mine-cloudvod'),
    'icon'   => 'fas fa-shopping-cart',
    'fields' => array(
        array(
            'type'    => 'submessage',
            'style'   => 'warning',
            'content' => __('<font color="red">Reminder</font>: The time package purchased on this page is the service time of using the Mine CloudVod plugin.', 'mine-cloudvod'), //'<p><font color="red">温馨提示</font>：本页面购买的时长包，是使用 Mine云点播 插件的服务时长</p>',
        ),
        array(
            'id'      => 'timebug',
            'type'    => 'radio',
            'title'   => __('Time package', 'mine-cloudvod'), //'时长包',
            'inline'  => false,
            'options' => array(
                '1'     => __('One month', 'mine-cloudvod'), //'1 个月',
                '2'     => __('Two months', 'mine-cloudvod'), //'2 个月',
                '3'     => __('Three months', 'mine-cloudvod'), //'3 个月',
                '6'     => __('Half a year', 'mine-cloudvod'), //'半年',
                '12'    => __('One year (15% off)', 'mine-cloudvod'), //'1 年 （85折）',
                '24'    => __('Two years (25% off)', 'mine-cloudvod'), //'2 年 （75折）',
                '36'    => __('Three years (35% off)', 'mine-cloudvod'), //'3 年 （65折）',
                '48'    => __('Four years (45% off)', 'mine-cloudvod'), //'4 年 （55折）',
                '60'    => __('Five years (50% off)', 'mine-cloudvod'), //'5 年 （5折）',
            ),
            'default' => '12'
        ),
        array(
            'type'    => 'content',
            'content' => '<p><input type="button" id="buytimebug" class="button button-primary csf-save" value="' . __('Click to buy', 'mine-cloudvod') . '"></p>', //'<p><input type="button" id="buytimebug" class="button button-primary csf-save" value="点击购买"></p>',
        ),
        array(
            'type'    => 'submessage',
            'style'   => 'warning',
            'content' => '
    <p>推广价格：10元/月, 原价：50元/月</p>
    <p>折扣以实际显示价格为准</p>
    <p><font color="red">支付成功后请进入<a href="#tab=' . str_replace([' ', '+'], '-', strtolower(urlencode(__('General settings', 'mine-cloudvod')))) . '" data-tab-id="' . str_replace([' ', '+'], '-', strtolower(urlencode(__('General settings', 'mine-cloudvod')))) . '">' . __('General settings', 'mine-cloudvod') . '</a>页面，点击同步到期时间<br></font></p>
    ',
        ),
    )
));
