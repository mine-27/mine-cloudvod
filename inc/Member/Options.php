<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\Member;

if ( ! defined( 'ABSPATH' ) ) exit;

class Options{
    public $prefix = 'mcv_settings';
    public function __construct() {
        add_action( 'mcsf_init', [ $this, 'init_logins' ] );
        add_action( 'mcv_add_admin_options_before_purchase', [ $this, 'logins_admin_options' ] );

        add_filter('wp_mail_from', [$this, 'mail_from']);
        add_filter('wp_mail_from_name', [$this, 'mail_from_name']);
        add_action('phpmailer_init', [$this, 'smtp_config']);

        add_action( 'init', [ $this, 'localize_script' ] );
        $this->init_vip();
    }

    public function localize_script(){
        $general = MINECLOUDVOD_SETTINGS['uc_general'] ?? [];
        $mcv_uc_general = [];
        if( isset( $general['themelogin'] ) && $general['themelogin'] ){
            $mcv_uc_general['loginselector'] = $general['selector'];
        }
        $mcv_uc_general['regurl'] = mcv_registration_url();
        $mcv_uc_general['privacy'] = MINECLOUDVOD_SETTINGS['privacy']??'';

        $uc_login3 = MINECLOUDVOD_SETTINGS['uc_login3'] ?? [
            'default' => [
                'status' => 1,
                'title' => '默认登录'
                ]
            ];
        $logins = [];
        if( is_array($uc_login3) ){
            foreach( $uc_login3 as $key => $loginInfo ){
                if( !$loginInfo['status'] ) continue;
                $script = apply_filters( 'mcv_login_script', '', $key );
                $logins[] = [
                    'name'      =>$key,
                    'title'     =>$loginInfo['title'],
                    'script'    => $script,
                ];
            }
        }
        
        $mcv_uc_general['uc_login3'] = $logins;
        wp_localize_script( 'mcv_localize_script', 'mcv_uc_general', $mcv_uc_general );
        wp_enqueue_script( 'mcv_localize_script' );
    }

    public function logins_admin_options(){
        \MCSF::createSection( $this->prefix, [
            'id'    => 'mcv_member',
            'title' => __('User Center', 'mine-cloudvod'),
            'icon'  => 'fas fa-user',
        ]);
        $login3 = [
            [
                'id'        => 'default',
                'type'      => 'fieldset',
                'title'     => __('Default user login', 'mine-cloudvod'),
                'fields'    => [
                    [
                        'id'    => 'status',
                        'type'  => 'switcher',
                        'title' => __('State', 'mine-cloudvod'),
                        'text_on'    => __('Enable', 'mine-cloudvod'),
                        'text_off'   => __('Disable', 'mine-cloudvod'),
                        'default' => true,
                    ],
                    array(
                        'id'    => 'title',
                        'type'  => 'text',
                        'title' => __('Title', 'mine-cloudvod'),
                        'dependency' => array('status', '==', true),
                        'default' => __('User Login', 'mine-cloudvod'),
                    ),
                ],
            ]
        ];
        $login3 = apply_filters( 'mcv_user_login_options', $login3 );
        \MCSF::createSection($this->prefix, [
            'parent'     => 'mcv_member',
            'title'  => __('General settings', 'mine-cloudvod'),
            'icon'   => 'fas fa-home',
            'fields' => [
                [
                    'id'        => 'uc_general',
                    'type'      => 'fieldset',
                    'title'     => __('Login', 'mine-cloudvod'),
                    'fields'    => [
                        [
                            'id'    => 'themelogin',
                            'type'  => 'switcher',
                            'title' => '调用主题登录',
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false,
                        ],
                        [
                            'id'    => 'selector',
                            'type'  => 'text',
                            'title' => 'CSS选择器',
                            'dependency' => ['themelogin', '==', true],
                            'desc' => '',
                        ],
                    ],
                ],
                [
                    'id'        => 'uc_general',
                    'type'      => 'fieldset',
                    'title'     => __('Register', 'mine-cloudvod'),
                    'fields'    => [
                        [
                            'id'    => 'themereg',
                            'type'  => 'switcher',
                            'title' => '自定义注册链接',
                            'text_on'    => __('Enable', 'mine-cloudvod'),
                            'text_off'   => __('Disable', 'mine-cloudvod'),
                            'default' => false,
                        ],
                        [
                            'id'    => 'regurl',
                            'type'  => 'text',
                            'title' => '注册链接',
                            'dependency' => ['themereg', '==', true],
                            'desc' => '',
                        ],
                    ],
                ],
                [
                    'id'    => 'hideAdminBar',
                    'type'  => 'switcher',
                    'title' => __('Hide Admin Bar', 'mine-cloudvod'),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'default' => false,
                ],
                [
                    'id'    => 'forceLogin',
                    'type'  => 'switcher',
                    'title' => __('Force Login', 'mine-cloudvod'),
                    'text_on'    => __('Enable', 'mine-cloudvod'),
                    'text_off'   => __('Disable', 'mine-cloudvod'),
                    'after' => __('Users need to login before opening any front-end page.', 'mine-cloudvod'),
                    'default' => false,
                ],
                [
                    'id'    => 'privacy',
                    'type'  => 'wp_editor',
                    'title' => __('Service Agreement and Privacy Policy', 'mine-cloudvod'),
                    'default' => mcv_trim( '<h1>网站服务协议</h1>
                    <p class="last-updated">最后更新日期：<span id="tos-date">2025年4月8日</span></p>
                    
                    <div class="section">
                    <h2>1. 接受条款</h2>
                    访问和使用本网站（以下简称"本网站"或"服务"）即表示您同意遵守本服务协议（以下简称"协议"）的所有条款。如果您不同意，请立即停止使用本网站。
                    
                    </div>
                    <div class="section">
                    <h2>2. 服务描述</h2>
                    本网站提供以下服务：[简要描述网站功能，如"在线购物"、"信息查询"、"会员服务"等]。我们保留随时修改或终止服务的权利，恕不另行通知。
                    
                    </div>
                    <div class="section">
                    <h2>3. 用户义务</h2>
                    您同意：
                    <ul>
                         <li>提供真实、准确的注册信息。</li>
                         <li>不利用本网站从事非法活动或侵犯他人权益。</li>
                         <li>不干扰网站的正常运行（如黑客攻击、恶意爬虫等）。</li>
                    </ul>
                    </div>
                    <div class="section">
                    <h2>4. 知识产权</h2>
                    本网站的所有内容（包括文字、图片、商标、代码等）均受版权和其他知识产权保护。未经授权，不得复制、修改或用于商业用途。
                    
                    </div>
                    <div class="section">
                    <h2>5. 免责声明</h2>
                    本网站"按现状"提供服务，不保证：
                    <ul>
                         <li>服务的连续性、无错误或安全性。</li>
                         <li>用户通过本网站获取的信息的准确性。</li>
                    </ul>
                    </div>
                    <div class="section">
                    <h2>6. 责任限制</h2>
                    在法律允许的范围内，本网站不对因使用或无法使用服务导致的任何直接、间接损失负责。
                    
                    </div>
                    <div class="section">
                    <h2>7. 终止服务</h2>
                    我们有权在不事先通知的情况下，暂停或终止您的账户（如违反本协议）。
                    
                    </div>
                    <div class="section">
                    <h2>8. 修改条款</h2>
                    我们可能随时更新本协议，修改后的条款将在网站上公布。继续使用即视为接受新条款。
                    
                    </div>
                    <div class="section">
                    <h2>9. 适用法律</h2>
                    本协议受[所在国家/地区，如"中华人民共和国法律"]管辖，争议解决地为[填写地点]。
                    
                    </div>
                    <!-- 隐私政策 -->
                    <h1>隐私政策</h1>
                    <p class="last-updated">最后更新日期：<span id="privacy-date">2023年11月1日</span></p>
                    
                    <div class="section">
                    <h2>1. 信息收集</h2>
                    我们可能收集以下信息：
                    <ul>
                         <li><strong>个人数据</strong>：姓名、邮箱、电话（注册或联系时提供）。</li>
                         <li><strong>使用数据</strong>：IP地址、浏览器类型、访问页面（通过Cookie等技术）。</li>
                         <li><strong>支付信息</strong>：通过第三方支付平台处理，我们不会存储信用卡号等敏感数据。</li>
                    </ul>
                    </div>
                    <div class="section">
                    <h2>2. 信息用途</h2>
                    您的信息将用于：
                    <ul>
                         <li>提供和改进服务。</li>
                         <li>与您沟通（如订单确认、客服回复）。</li>
                         <li>分析用户行为以优化网站体验。</li>
                    </ul>
                    </div>
                    <div class="section">
                    <h2>3. 数据共享</h2>
                    我们仅在以下情况共享数据：
                    <ul>
                         <li><strong>第三方服务商</strong>：如支付处理、物流公司（仅限必要信息）。</li>
                         <li><strong>法律要求</strong>：应政府或执法机构要求披露。</li>
                    </ul>
                    </div>
                    <div class="section">
                    <h2>4. Cookie与追踪技术</h2>
                    本网站使用Cookie以记住用户偏好。您可通过浏览器设置禁用，但可能影响部分功能。
                    
                    </div>
                    <div class="section">
                    <h2>5. 数据安全</h2>
                    我们采取合理措施（如加密、访问控制）保护数据，但无法保证绝对安全。
                    
                    </div>
                    <div class="section">
                    <h2>6. 用户权利</h2>
                    您有权：
                    <ul>
                         <li>访问、更正或删除您的个人数据。</li>
                         <li>拒绝营销邮件（通过邮件中的"退订"链接）。</li>
                    </ul>
                    </div>
                    <div class="section">
                    <h2>7. 儿童隐私</h2>
                    本网站不面向13岁以下儿童。如发现儿童数据被收集，我们将尽快删除。
                    
                    </div>
                    <div class="section">
                    <h2>8. 政策更新</h2>
                    隐私政策可能修订，重大变更将通过网站公告或邮件通知。
                    
                    </div>
                    <div class="section">
                    <h2>9. 联系我们</h2>
                    如对隐私政策有疑问，请联系：<a href="mailto:contact@example.com">contact@example.com</a>。
                    
                    </div>' ),
                ],
            ]
        ]); 
        \MCSF::createSection($this->prefix, [
            'parent'     => 'mcv_member',
            'title'  => __('Login', 'mine-cloudvod'),
            'icon'   => 'fas fa-share-alt',
            'fields' => [
                [
                    'type'      => 'submessage',
                    'style'     => 'success',
                    'content'   => __( 'Drag and drop login methods can be sorted.', 'mine-cloudvod' ),
                ],
                [
                    'id'        => 'uc_login3',
                    'class'     => 'uc_login3',
                    'type'      => 'sortable',
                    // 'title'     => __('Login', 'mine-cloudvod'),
                    'fields'    => $login3,
                ],
            ]
        ]); 
        \MCSF::createSection($this->prefix, [
            'parent'     => 'mcv_member',
            'title'  => __('VIP', 'mine-cloudvod'),
            'icon'   => 'fas fa-crown',
            'fields' => [
                [
                    'type'      => 'submessage',
                    'style'     => 'success',
                    'content'   => '
                        <ul style="list-style: inside;">
                        <li>推荐设置一个VIP等级，以降低用户的理解成本</li>
                        <li>VIP等级从上到下依次递增，VIP等级越高，可享受的权益越多，价格越高</li>
                        <li>VIP等级可从较低等级补差价升级到较高等级</li>
                        <li>VIP较高等级可享受较低等级的所有权益</li>
                        <li>VIP购买链接：（ '.mcv_checkout_url(['type'=>'vip', 'id'=>'vip_']).'编号 ）</li>
                        <li>编号是VIP等级，从0开始，0对应从上到下第一个VIP等级，1对应第二个，以次类推</li>
                        </ul>',
                ],
                [
                    'id'        => 'uc_member_levels',
                    'type'      => 'group',
                    'title'     => 'VIP等级',
                    'accordion_title_number' => true,
                    'sortable'    => false,
                    'fields'    => [
                        [
                            'id'        => 'title',
                            'type'      => 'text',
                            'title'     => '名称',
                            'default'   => '超级会员',
                        ],
                        [
                            'id'       => 'period',
                            'type'     => 'select',
                            'title'    => __('Validity period after enrolled', 'mine-cloudvod'),
                            'options'  => [
                                '12'    => __('One year', 'mine-cloudvod'),
                                '24'    => __('Two years', 'mine-cloudvod'),
                                '36'    => __('Three years', 'mine-cloudvod'),
                                '48'    => __('Four years', 'mine-cloudvod'),
                                '60'    => __('Five years', 'mine-cloudvod'),
                                'forever' => __('Forever', 'mine-cloudvod'),
                            ],
                            'default'       => '12',
                        ],
                        [
                            'id'        => 'price',
                            'type'      => 'text',
                            'title'     => '价格',
                            'default'   => '299',
                        ],
                        [
                            'id'        => 'discount',
                            'type'      => 'number',
                            'title'     => '折扣',
                            'default'   => '90',
                            'before'    => '请输入0-99的整数，0代表免费，1-99代表0.1-9.9折',
                        ],
                    ],
                ],
            ]
        ]); 
        \MCSF::createSection($this->prefix, [
            'parent'     => 'mcv_member',
            'title'  => __('Mail', 'mine-cloudvod'),
            'icon'   => 'fas fa-envelope',
            'fields' => [
                [
                    'type'      => 'submessage',
                    'style'     => 'info',
                    'content'   => __('Configure SMTP for sending password reset emails and other notifications.', 'mine-cloudvod'),
                ],
                [
                    'id'        => 'uc_smtp',
                    'type'      => 'fieldset',
                    'title'     => __('SMTP', 'mine-cloudvod'),
                    'fields'    => [
                        [
                            'id'    => 'smtp_host',
                            'type'  => 'text',
                            'title' => __('SMTP Host', 'mine-cloudvod'),
                            'default' => 'smtp.qq.com',
                        ],
                        [
                            'id'    => 'smtp_port',
                            'type'  => 'text',
                            'title' => __('SMTP Port', 'mine-cloudvod'),
                            'default' => '465',
                        ],
                        [
                            'id'    => 'smtp_secure',
                            'type'  => 'select',
                            'title' => __('Encryption', 'mine-cloudvod'),
                            'options' => [
                                'ssl' => 'SSL',
                                'tls' => 'TLS',
                                ''    => __('None', 'mine-cloudvod'),
                            ],
                            'default' => 'ssl',
                        ],
                        [
                            'id'    => 'smtp_user',
                            'type'  => 'text',
                            'title' => __('SMTP Username', 'mine-cloudvod'),
                        ],
                        [
                            'id'    => 'smtp_pass',
                            'type'  => 'text',
                            'title' => __('SMTP Password', 'mine-cloudvod'),
                            'attributes' => ['type' => 'password'],
                        ],
                    ],
                ],
            ]
        ]); 
    }

    public function init_logins(){
        $logins = [
            'weixinopen' => 'MineCloudvod\Member\WechatOpen',
            'sms' => 'MineCloudvod\Member\Sms',
        ];
        /**
         * 登录class过滤器，注册登录的class，在class中处理登录的逻辑
         */
        $logins = apply_filters( 'mcv_user_login_classes', $logins );
        foreach( $logins as $login ){
            if( is_string( $login ) && class_exists( $login ) ){
                $login = new $login();
            }
        }

    }

    public function init_vip(){
        $vip_type = 'vip';
        // 价格 标题
        add_filter( 'mcv_order_data_' . $vip_type, function( $order_data, $vip_id ) use( $vip_type ){
            $vip_id = explode( '_', $vip_id );
            $vips = MINECLOUDVOD_SETTINGS['uc_member_levels'];
            if( count($vip_id) == 2 && $vip_id[0] == 'vip' && isset($vips[$vip_id[1]]) ){
                $order_data['type'] = $vip_type;
                $order_data['post_mime_type'] = 'mcv/vip';
                $order_data['item_id'] = 'vip_' . $vip_id[1];

                $title = $vips[$vip_id[1]]['title'];
                // 默认为开通价格
                $price = $vips[$vip_id[1]]['price'];
                $isvip = mcv_lms_get_vip_lvl();
                if( is_array( $isvip ) ){
                    // 是vip，判断是续费还是升级
                    if( $isvip['lvl'] == $vip_id[1] ){ // 续费
                        $title = '续费 ' . $title;
                    }
                    elseif( $isvip['lvl'] < $vip_id[1] ){ // 升级
                        $title = '升级 ' . $title;
                        $endtime = $isvip['endtime'];
                        $starttime = strtotime( gmdate('Y-m-d H:i:s', $endtime ).'-'.$vips[$vip_id[1]]['period'].'month');
                        $sdays = intval( ( $endtime - time() ) / ( 3600 * 24 ) );
                        $alldays = intval( ( $endtime - $starttime ) / ( 3600 * 24 ) );
                        // 需补差价 = 升级的差价 * ( 剩余天数 / 总天数 )
                        $price = ($vips[$vip_id[1]]['price'] - $vips[$isvip['lvl']]['price']) * $sdays / $alldays;
                        $price = round( $price, 2 );
                    }
                }
                $order_data['title'] = $title;
                $order_data['price'] = $price;
            }
            return $order_data;
        }, 10, 2);
        add_action( 'mcv_checkout_info_' . $vip_type, function ( $order_data, $vip_id ){
            $vip_id = explode( '_', $vip_id );
            $vips = MINECLOUDVOD_SETTINGS['uc_member_levels'];
            if( count($vip_id) == 2 && $vip_id[0] == 'vip' && isset($vips[$vip_id[1]]) ){
                $arr = [
                    '1'     => __('One month', 'mine-cloudvod'),
                    '2'     => __('Two months', 'mine-cloudvod'),
                    '3'     => __('Three months', 'mine-cloudvod'),
                    '6'     => __('Half a year', 'mine-cloudvod'),
                    '12'    => __('One year', 'mine-cloudvod'),
                    '24'    => __('Two years', 'mine-cloudvod'),
                    '36'    => __('Three years', 'mine-cloudvod'),
                    '48'    => __('Four years', 'mine-cloudvod'),
                    '60'    => __('Five years', 'mine-cloudvod'),
                    'forever'    => __('Forever', 'mine-cloudvod'),
                ];
                echo '<div class="course-list col4" style="margin-bottom: 20px;">';
                if( isset($vips[$vip_id[1]]) ){
                    $vip = $vips[$vip_id[1]];
                    $isvip = mcv_lms_get_vip_lvl();
                    $vip_tip = '';
                    $opaction = '';
                    // 不是vip
                    if( $isvip === false ){
                        $opaction = '开通 ' . $arr[$vip['period']];
                    }
                    elseif( is_array( $isvip ) ){
                        // 是vip，判断是续费还是升级
                        if( $isvip['lvl'] == $vip_id[1] ){ // 续费
                            $vip_tip = '<p>续费前到期时间： '. wp_date('Y-m-d H:i:s', $isvip['endtime']) .'</p>';
                            $opaction = '续费 ' . $arr[$vip['period']];
                        }
                        elseif( $isvip['lvl'] < $vip_id[1] ){ // 升级
                            $vip_tip = '<p>当前会员等级： '. $isvip['title'] .'</p>';
                            $opaction = '升级 到 ';
                        }
                    }
                    else{
                        $opaction = '开通 ' . $arr[$vip['period']];
                    }
                    $vip_content = '<h1>' . $opaction . $vip['title'] . '</h1>'. $vip_tip;
                    echo wp_kses_post($vip_content);
                    if( $vip['discount'] == 0 ){
                        echo '<p>所有课程免费学习</p>';
                    }
                    else{
                        echo '<p>所有课程'.esc_attr($vip['discount']/10).'折购买, 有效期内部分课程免费学习</p>';
                    }
                }
                echo '</div>';
            }
        }, 10, 2 );
        // VIP订单完成后，更新用户vip信息
        add_action( 'mcv_course_order_handler', function( $vip_id, $author_id ){
            $vip_id = explode( '_', $vip_id );
            $vips = MINECLOUDVOD_SETTINGS['uc_member_levels'];
            if( count($vip_id) == 2 && $vip_id[0] == 'vip' && isset($vips[$vip_id[1]]) ){
                // 开通的vip等级
                $lvl = $vip_id[1]; 
                // vip到期时间
                $endtime = get_user_meta( $author_id, '_mcv_vip_endtime', true );
                // 当前vip等级
                $isvip = mcv_lms_get_vip_lvl();
                // 不是vip
                // if( $isvip === false ){
                //     $vip_content = '开通';
                // }
                if( is_array( $isvip ) ){
                    // 是vip，判断是续费还是升级
                    if( $isvip['lvl'] == $vip_id[1] ){
                        // 续费，则只更新vip到期时间，不更新vip等级
                        if( !$endtime ) $endtime = 0;
                        if( $endtime < time() ) $endtime = time();
                        $endtime = strtotime( gmdate('Y-m-d H:i:s', $endtime ).'+'.$vips[$lvl]['period'].'month');
                        update_user_meta( $author_id, '_mcv_vip_endtime', $endtime );
                    }
                    elseif( $isvip['lvl'] < $vip_id[1] ){ 
                        // 升级，则只更新vip等级，不更新vip到期时间
                        update_user_meta( $author_id, '_mcv_vip_lvl', $lvl );
                    }
                    return;
                }
                // 新开通vip
                if( !$endtime ) $endtime = 0;
                if( $endtime < time() ) $endtime = time();
                $newtime = strtotime( gmdate('Y-m-d H:i:s', $endtime ).'+'.$vips[$lvl]['period'].'month');
                update_user_meta( $author_id, '_mcv_vip_lvl', $lvl );
                update_user_meta( $author_id, '_mcv_vip_endtime', $newtime );
            }
        }, 10, 2 );

        add_filter( 'mcv_handler_order_discount', function($discount, $orderid){
            $course_id = get_post_meta( $orderid, '_mcv_order_items', true );
            if( isset($course_id[0]) && is_numeric( $course_id[0] ) ){
                $user_id = get_post_field( 'post_author', $orderid );
                $isvip = mcv_lms_get_vip_lvl( $user_id );
                if( is_array( $isvip ) ){
                    $amount = get_post_meta( $orderid, '_mcv_order_amount', true );
                    $reduce = 0;
                    if( $isvip['discount'] > 0 ){
                        $reduce = $amount - $amount * $isvip['discount'] / 100;
                    }
                    elseif( $isvip['discount'] == 0 ){
                        $reduce = $amount;
                    }
                    $discount[] = [
                        'type'=>'vip', 
                        'reduce'=> $reduce
                    ];
                }
            }
            return $discount;
        }, 10, 2 );

        add_filter('mcv_lms_is_enrolled', function($is_enrolled, $course_id){
            $user_id = get_current_user_id(  );
            $isvip = mcv_lms_get_vip_lvl( $user_id );
            if( is_array( $isvip ) ){
                $_mcv_member_levels = get_post_meta($course_id, '_mcv_member_levels', true);
                if( is_numeric($_mcv_member_levels) && $_mcv_member_levels <= $isvip['lvl'] ){
                    return 1;
                }
            }
            return $is_enrolled;
        }, 10, 2 );


        // 在用户列表中添加"VIP等级"列
        add_filter('manage_users_columns', function ($columns) {
            $columns['registration_date'] = __('注册时间', 'mine-cloudvod');
            
            $vips = MINECLOUDVOD_SETTINGS['uc_member_levels']??[];
            if( is_array( $vips ) && count( $vips ) > 0 ){
                $columns['vip_lvl'] = __('VIP Level', 'mine-cloudvod');
            }
            
            return $columns;
        });

        // 显示VIP等级数据
        add_action('manage_users_custom_column', function ($value, $column_name, $user_id) {
            if ('vip_lvl' == $column_name) {
                $ret = __( 'Normal User', 'mine-cloudvod' );
                $lvl = get_user_meta( $user_id, '_mcv_vip_lvl', true );
                $endtime = get_user_meta( $user_id, '_mcv_vip_endtime', true );
                $vips = MINECLOUDVOD_SETTINGS['uc_member_levels']??[];
                
                if( is_numeric( $lvl ) && isset($vips[$lvl]) ){
                    $ret = $vips[$lvl]['title'];
                }
                if( $endtime ){
                    $ret .= '<br><span style="color: '. ($endtime > time()?'green':'red') .';">(' . gmdate('Y-m-d H:i:s', $endtime) . ')</span>';
                }
                $value = $ret;
            }
            if ('registration_date' == $column_name) {
                $user_data = get_userdata($user_id);
                $value = wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($user_data->user_registered));
            }
            return $value;
        }, 10, 3);
        // 添加VIP筛选链接
        add_filter('views_users', function ($views) {
            $vips = MINECLOUDVOD_SETTINGS['uc_member_levels']??[];
            if( is_array( $vips ) ){
                global $wpdb;
                foreach( $vips as $key=>$val ){
                    $vip_num = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 聚合 COUNT 统计 VIP 用户数，刻意性能优化
                        $wpdb->prepare(
                            "SELECT COUNT(ID)
                        FROM 	{$wpdb->users}
                            INNER JOIN {$wpdb->usermeta} viplvl ON {$wpdb->users}.ID = viplvl.user_id AND viplvl.meta_key = '_mcv_vip_lvl'
                            INNER JOIN {$wpdb->usermeta} endtime ON {$wpdb->users}.ID = endtime.user_id AND endtime.meta_key = '_mcv_vip_endtime'
                        WHERE 	viplvl.meta_value = %d AND endtime.meta_value >= %d;",
                            $key,
                            time()
                        )
                    );
                    $views['user_lvl='.$key] = '<a href="?user_lvl='.$key.'">'.$val['title'].'（'. $vip_num .'）</a>';
                }
            }
            return $views;
        });
        // 使注册时间列可排序
        add_filter('manage_users_sortable_columns', function ($columns) {
            return wp_parse_args(array('registration_date' => 'registered'), $columns);
        });
        

        // 处理VIP筛选逻辑
        add_action('pre_get_users', function ($query) {
            global $pagenow;
            
            if (is_admin() && $pagenow == 'users.php' && isset($_GET['user_lvl']) && sanitize_text_field(wp_unslash($_GET['user_lvl'])) != '') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 后台用户 VIP 筛选只读
                $user_lvl = sanitize_text_field(wp_unslash($_GET['user_lvl'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 后台用户 VIP 筛选只读
                $meta_query = [
                    [
                        'key' => '_mcv_vip_lvl',
                        'value' => $user_lvl,
                        'compare' => '=',
                    ]
                ];

                
                if (!empty($meta_query)) {
                    $query->set('meta_query', $meta_query);
                }
            }
        });

        // 添加VIP等级字段到用户编辑页面
        add_action('show_user_profile', [$this, 'add_custom_uservip_fields']);
        add_action('edit_user_profile', [$this, 'add_custom_uservip_fields']);
        // 保存到期时间字段
        add_action('personal_options_update', [$this, 'save_uservip_field']);
        add_action('edit_user_profile_update', [$this, 'save_uservip_field']);

        add_filter( 'mcv_order_items_data', [ $this, 'add_order_items' ], 10, 2 );
    }

    public function add_order_items( $infos, $order ){
        if( $order->post_type == 'mcv_order' && $order->post_mime_type == 'mcv/vip' ){
            $_mcv_order_items = get_post_meta( $order->ID, '_mcv_order_items', true );
            $course_price = get_post_meta( $order->ID, '_mcv_order_amount', true );
            if( is_array( $_mcv_order_items ) ){
                $vip = $_mcv_order_items[0];
                $vip = explode( '_', $vip );
                $viplvl = $vip[1];
            }
            $infos .= '<div style="line-height: 60px;padding: 0 20px;font-size: 16px;display:flex;"><a href="#" style="margin:0 15px;">' . $order->post_title . '</a> 价格 <span style="color:#b32d2e;">￥' . $course_price . '</span></div>';
        }
        return $infos;
    }

    public function add_custom_uservip_fields($user) {
        $user_id = $user->ID;
        $lvl = get_user_meta( $user_id, '_mcv_vip_lvl', true );
        $endtime = get_user_meta( $user_id, '_mcv_vip_endtime', true );
        $vip_endtime = $endtime ? gmdate('Y-m-d H:i:s', $endtime) : '';
        $vips = MINECLOUDVOD_SETTINGS['uc_member_levels']??[];
        $opts_escaped = '<option value="">'. esc_html__('Normal User', 'mine-cloudvod') .'</option>';
        if( is_array( $vips ) ){
            foreach( $vips as $key=>$val ){
                $opts_escaped .= '<option value="'.esc_attr($key).'" '. esc_attr(is_numeric( $lvl ) && $lvl == $key?'selected':'') .'>'.esc_html($val['title']).'</option>';
            }
        }
        else{
            return;
        }
        ?>
        <h3><?php esc_html_e('Mine CloudVod', 'mine-cloudvod'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="vip_lvl"><?php esc_html_e('VIP Level', 'mine-cloudvod'); ?></label></th>
                <td>
                    <select name="vip_lvl" id="vip_lvl">
                        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 已转义的选项 HTML ?>
                        <?php echo $opts_escaped; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="vip_endtime"><?php esc_html_e('Validity Period', 'mine-cloudvod'); ?></label></th>
                <td>
                    <input type="datetime-local" name="vip_endtime" id="vip_endtime" 
                       value="<?php echo esc_attr($vip_endtime); ?>" 
                       class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_uservip_field($user_id) {
        if (!current_user_can('edit_users')) {
            return false;
        }
        
        if (isset($_POST['vip_lvl']) && sanitize_text_field(wp_unslash($_POST['vip_lvl'])) !== '') { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- 已用 current_user_can('edit_users') 校验权限
            $vip_lvl = sanitize_text_field(wp_unslash($_POST['vip_lvl'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- 已用 current_user_can('edit_users') 校验权限
            update_user_meta($user_id, '_mcv_vip_lvl', $vip_lvl);
            // 将日期转换为时间戳存储
            $expiry_date = sanitize_text_field(wp_unslash($_POST['vip_endtime']??'')); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- 已用 current_user_can('edit_users') 校验权限
            $vip_endtime = strtotime($expiry_date);
            update_user_meta($user_id, '_mcv_vip_endtime', $vip_endtime);
        } else {
            // 如果VIP等级为空，删除元数据
            delete_user_meta($user_id, '_mcv_vip_lvl');
            delete_user_meta($user_id, '_mcv_vip_endtime');
        }
    }
    public function mail_from($from){
        $smtp = MINECLOUDVOD_SETTINGS['uc_smtp'] ?? [];
        return $smtp['smtp_user'] ?: (get_option('admin_email') ?: $from);
    }
    public function mail_from_name($name){
        return wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    }
    public function smtp_config($phpmailer){
        $smtp = MINECLOUDVOD_SETTINGS['uc_smtp'] ?? [];
        if (empty($smtp['smtp_host'])) return;
        $phpmailer->isSMTP();
        $phpmailer->Host       = $smtp['smtp_host'];
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = $smtp['smtp_port'] ?: 465;
        $phpmailer->SMTPSecure = $smtp['smtp_secure'] ?: 'ssl';
        $phpmailer->Username   = $smtp['smtp_user'] ?: $phpmailer->From;
        $phpmailer->Password   = $smtp['smtp_pass'] ?: '';
        $phpmailer->From       = $phpmailer->From;
        $phpmailer->FromName   = $phpmailer->FromName;
    }
}