<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
global $post;
$course_id = mcv_lms_get_course_id_by_lesson_id( $post->ID );
$is_enrolled = mcv_lms_is_enrolled( $course_id );

// 私密课程 且 未订购
if( !is_post_publicly_viewable($course_id) && !$is_enrolled ){
    return 'Private course';
}
$course_title =  get_the_title( $course_id );
$course_link = get_the_permalink($course_id);

$access_mode = mcv_lms_get_course_access_mode( $course_id );
$course_price = mcv_lms_show_course_price( $course_id, true );
$user_id = get_current_user_id();

$is_open = $access_mode == 'open';
$is_free = $access_mode == 'free';

$course_terms = get_the_terms($course_id, 'course-category');

$is_user_logged_in = is_user_logged_in();
// 登录后显示目录是否开启
$catelog_show = get_post_meta( $course_id, '_mcv_course_catelog', true );
if( $catelog_show === "" ) $catelog_show = false;
else $catelog_show = !!$catelog_show;
// 购买后显示目录是否开启
$catelog_enroll = get_post_meta( $course_id, '_mcv_course_catelog_enroll', true );
if( $catelog_enroll === "" ) $catelog_enroll = false;
else $catelog_enroll = !!$catelog_enroll;

$next = [];
$nextflag = false;
$courses_lessons = mcv_lms_get_courses_lessons();
$lists = [];
if( is_array( $courses_lessons ) ){
    foreach($courses_lessons as $section){
        $section_enrolled = mcv_lms_is_enrolled( $section['ID'] );
        $list = [
            'id'    => $section['ID'],
            'title' => $section['post_title'],
            'enrolled' => $section_enrolled,
            'price' => get_post_meta($section['ID'], '_mcv_section_price', true),
        ];
        foreach($section['Lessons'] as $lesson){
            if( $lesson->post_type == 'section' ){
                $subLessons = [];
                if( is_array($lesson->Lessons ) ){
                    foreach($lesson->Lessons as $sl){
                        $subAttrs = get_post_meta($sl->ID, '_mcv_lms_lesson_attrs', true);
                        if( !is_array($subAttrs) && is_string( $subAttrs ) ) $subAttrs = unserialize( $subAttrs );
                        if(!$subAttrs) $subAttrs = [];
                        if(!isset($subAttrs['duration'])){
                            $_mcv_lesson_duration = get_post_meta($sl->ID, '_mcv_lesson_duration', true);
                            $subAttrs['duration'] = $_mcv_lesson_duration;
                        }
                        $subLessons[] = [
                            'id' => $sl->ID,
                            'title' => $sl->post_title,
                            'url'   => get_the_permalink($sl->ID),
                            'attrs' => $subAttrs,
                            'lesson_type' => get_post_meta($lesson->ID, '_lesson_type', true),
                            'aliyun_livetime' => get_post_meta($lesson->ID, 'aliyun_livetime', true),
                            'price' => get_post_meta($sl->ID, '_mcv_lesson_price', true),
                            'enrolled' => mcv_lms_is_enrolled( $sl->ID ),
                            'attachments' => 0,
                        ];
                    }
                }
                $list['lessons'][] = [
                    'id' => $lesson->ID,
                    'title' => $lesson->post_title,
                    'enrolled' => mcv_lms_is_enrolled( $lesson->ID ),
                    'price' => get_post_meta($lesson->ID, '_mcv_section_price', true),
                    'post_type' => 'section',
                    'lessons' => $subLessons,
                ];
            }
            else{
                $attrs = get_post_meta($lesson->ID, '_mcv_lms_lesson_attrs', true);
                if( !is_array($attrs) && is_string( $attrs ) ) $attrs = unserialize( $attrs );
                if(!$attrs) $attrs = [];
                $lprice = get_post_meta($lesson->ID, '_mcv_lesson_price', true);
                $lesson_enrolled = mcv_lms_is_enrolled( $lesson->ID );
                if(!isset($attrs['duration'])){
                    $_mcv_lesson_duration = get_post_meta($lesson->ID, '_mcv_lesson_duration', true);
                    $attrs['duration'] = $_mcv_lesson_duration;
                }
                
                $attachments = get_post_meta( $lesson->ID, '_mcv_lesson_attachments', true );

                $url = get_the_permalink($lesson->ID);
                $lid = $lesson->ID;
                $ltitle = $lesson->post_title;
                if( 
                    ( $catelog_show && !$is_user_logged_in ) // 开启登录后显示目录，但没有登录
                    || ( $catelog_show && $is_user_logged_in && $catelog_enroll && !$lesson_enrolled && !$section_enrolled && !$is_enrolled ) // 开启登录后显示目录，已登录，开启购买后显示目录，但未购买
                ){
                    $lid = 0;
                    $ltitle = __('Check the catalog after enrolled.', 'mine-cloudvod');
                    $url = get_the_permalink($course_id);
                }
                $list['lessons'][] = [
                    'id' => $lid,
                    'title' => $ltitle,
                    'url'   => $url,
                    'attrs' => $attrs,
                    'lesson_type' => get_post_meta($lesson->ID, '_lesson_type', true),
                    'aliyun_livetime' => get_post_meta($lesson->ID, 'aliyun_livetime', true),
                    'price' => $lprice,
                    'enrolled' => $lesson_enrolled,
                    'attachments' => is_array($attachments)?count($attachments):0,
                ];
            }
        }
        $lists[] = $list;
    }
}

$viewDependencies = include( MINECLOUDVOD_PATH.'/build/lms/course-single/view.asset.php' );
foreach($viewDependencies['dependencies'] as $dpc){
    wp_enqueue_style( $dpc );
}
wp_set_script_translations( 'mine-cloudvod-lesson-single-editor-script', 'mine-cloudvod' );
wp_enqueue_style( 'wp-block-library' );
wp_enqueue_global_styles();
wp_enqueue_style( 'mine-cloudvod-lesson-single-editor-style' );
wp_enqueue_script( 'jquery' );
wp_enqueue_script( 'mcv_layer' );
wp_enqueue_script( 'mine-cloudvod-lesson-single-editor-script' );
if( !$user_id ){
    wp_enqueue_style( 'mine-cloudvod-user-style' );
}
wp_enqueue_script( 'mine-cloudvod-user-script' );


$catelog_no = get_post_meta( $course_id, '_mcv_course_no_type', true );
if( $catelog_no === "" ) $catelog_no = true;
else $catelog_no = !!$catelog_no;
$watermark = ['status'=>1,'text'=>get_bloginfo('name')];
if( isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['watermark'] ) ){
    $watermark = MINECLOUDVOD_SETTINGS['mcv_lms_course']['watermark'];
    $current_user = wp_get_current_user();
    if( $current_user ){
        $watermark['text'] = str_replace(['{userid}', '{username}', '{userip}', '{useremail}', '{usernickname}'], [$current_user->ID, $current_user->user_login, sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')), $current_user->user_email, $current_user->display_name], $watermark['text']);
    }
    else{
        $watermark['text'] = str_replace(['{userid}', '{username}', '{userip}', '{useremail}', '{usernickname}'], '', $watermark['text']);
    }
}
if( empty( $watermark['text'] ) ) $watermark['text'] = get_bloginfo('name');
global $ActiveAddons;
wp_localize_script( 'mine-cloudvod-lesson-single-editor-script', 'mcv_lesson_data', [
    'course'    => [
        'id'    => $course_id,
        'title' => $course_title,
        'url'   => $course_link,
        // 'attachments' => $course_attachments,
        'access_mode' => $access_mode,
        'catelog_no' => $catelog_no,
        'enrolled' => $is_enrolled,
    ],
    'sections'   => $lists, 
    'current'    => $post->ID,
    'watermark'  => $watermark,
    'progress' => isset( $ActiveAddons['progress'] ),
]);
$lesson_attrs = get_post_meta($post->ID, '_mcv_lms_lesson_attrs', true);
$lesson_type = get_post_meta($post->ID, '_lesson_type', true)?:'vod';

$is_live = false;
$stime = 0;
$etime = 0;
if( $lesson_type && $lesson_type == 'live' ){
    $is_live = true;
    $livetime = get_post_meta($post->ID, 'aliyun_livetime', true);
    $stime = strtotime($livetime['from']);
    $etime = strtotime($livetime['to']);
}

$sprice = mcv_lms_show_course_price($post->post_parent);
$section_enrolled = false;
if( $sprice ){
    $section_enrolled = mcv_lms_is_enrolled( $post->post_parent );
}
$lprice = get_post_meta( $post->ID, '_mcv_lesson_price', true);
$lesson_enrolled = false;
if( $lprice ){
    $lesson_enrolled = mcv_lms_is_enrolled( $post->ID );
}

$is_iframe = false;
if( !empty( $_GET['iframe'] ) ){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 前台只读 iframe 渲染标志
    $is_iframe = true;
}
    show_admin_bar( false );
    remove_action('wp_head', '_admin_bar_bump_cb');

// iframe 模式下移除不必要的资源加载
if( $is_iframe ){
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
    remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
}

if( $user_id ){
    if( $access_mode != "buynow" ){
        //不需要购买的课程，直接添加订购时间。    
        if( !get_user_meta( $user_id, '_mcv_lms_enroll_course_id_'.$course_id, true ) ){
            mcv_order_update_items( [$course_id], $user_id );
        }
    }
}
$canplay = false;
$title = __('Check the catalog after enrolled.', 'mine-cloudvod');
if( (isset($lesson_attrs['preview']) && $lesson_attrs['preview']) || $is_enrolled || ($is_free && $user_id) || $is_open || $section_enrolled || $lesson_enrolled ){
    $canplay = true;
    $title = get_the_title();
}
?>
<div id="mcv_ketang_container" class="web index-placeholder-body<?php echo esc_attr( $is_iframe ? ' is-iframe' : '' ); ?> style<?php echo esc_attr(MINECLOUDVOD_SETTINGS['mcv_lms_course']['lesson_style']??'2'); ?> ">
    <div class="study-header">
        <div class="mcv-breadcrumb">
            <a href="<?php echo esc_url(get_post_type_archive_link(MINECLOUDVOD_LMS['course_post_type'])); ?>"><?php echo esc_html__('All courses', 'mine-cloudvod'); ?></a>
            <?php if( $course_terms ): ?>
            <span class="ke-icon" style="color: rgb(161, 169, 178); font-size: 14px;">
                <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M9.636 20.435a1 1 0 0 0 1.414 0l4.95-4.95a4 4 0 0 0 0-5.656l-4.95-4.95a1 1 0 0 0-1.414 1.414l4.95 4.95a2 2 0 0 1 0 2.828l-4.95 4.95a1 1 0 0 0 0 1.414Z"
                        fill="currentColor"></path>
                </svg>
            </span>
            <?php foreach( $course_terms as $term ): ?>
            <a href="<?php echo esc_url(get_term_link( $term )); ?>"><?php echo esc_html($term->name); ?></a> &nbsp;
            <?php endforeach; ?>
            <?php endif; ?>
            <span class="ke-icon" style="color: rgb(161, 169, 178); font-size: 14px;">
                <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M9.636 20.435a1 1 0 0 0 1.414 0l4.95-4.95a4 4 0 0 0 0-5.656l-4.95-4.95a1 1 0 0 0-1.414 1.414l4.95 4.95a2 2 0 0 1 0 2.828l-4.95 4.95a1 1 0 0 0 0 1.414Z"
                        fill="currentColor"></path>
                </svg>
            </span>
            <a href="<?php echo esc_url($course_link);?>" title="<?php echo esc_html($course_title);?>"><?php echo esc_html($course_title);?></a>
            <span class="ke-icon" style="color: rgb(161, 169, 178); font-size: 14px;">
                <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M9.636 20.435a1 1 0 0 0 1.414 0l4.95-4.95a4 4 0 0 0 0-5.656l-4.95-4.95a1 1 0 0 0-1.414 1.414l4.95 4.95a2 2 0 0 1 0 2.828l-4.95 4.95a1 1 0 0 0 0 1.414Z"
                        fill="currentColor"></path>
                </svg>
            </span>
            <a href="<?php the_permalink();?>" title="<?php echo esc_html($title); ?>"
                class="mcv-curlesson"><?php echo esc_html($title); ?></a>
        </div>
        <div class="operations">
            <div class="nav-comment-wrapper">
            <?php
                $kefuqr = MINECLOUDVOD_SETTINGS['mcv_lms_general']['kefuqr']??'';
                $kefutips = MINECLOUDVOD_SETTINGS['mcv_lms_general']['kefutips']??'';
                if( $kefuqr ) echo '<div class="comment header-item qrtips" data-title="添加客服" data-qr="'.esc_attr($kefuqr).'" data-tips="'.esc_attr($kefutips).'"><span>客服</span></div>';
                $mpqr = MINECLOUDVOD_SETTINGS['mcv_lms_general']['mpqr']??'';
                $mptips = MINECLOUDVOD_SETTINGS['mcv_lms_general']['mptips']??'';
                if( $mpqr ) echo '<div class="comment header-item qrtips" data-title="关注公众号" data-qr="'.esc_attr($mpqr).'" data-tips="'.esc_attr($mptips).'"><span>公众号</span></div>';
                $qqgroup = get_post_meta( $course_id, 'qqgroup', true );
                $qqgrouptips = get_post_meta( $course_id, 'qqgrouptips', true );
                if( $qqgroup ) echo '<div class="comment header-item qqtips" data-title="QQ群" data-qq="'.esc_attr($qqgroup).'" data-tips="'.esc_attr($qqgrouptips).'"><span>QQ群</span></div>';
            ?>
                
                <div class="comment header-item"><span id="mcv-course-review"></span></div>
            </div>
            <div class="header-item profile">
                <div class="s-avatar">
                    <img class="avatar-img img--full-radius"
                        src="<?php echo esc_url( mcv_get_avatar_url( get_current_user_id() ) ?: MINECLOUDVOD_URL.'/static/img/user.png' ); ?>" alt="" width="32" height="32">
                </div>
                <ul class="profile-menu hidden">
                    <li class="li-item"><a href="<?php echo esc_url( home_url() ); ?>" target="_blank"
                            rel="noopener noreferrer">首页</a></li>
                    <li class="li-item"><a href="<?php 
                    if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses']) && MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses'] ){
                        echo esc_url( get_page_link( MINECLOUDVOD_SETTINGS['mcv_lms_general']['user_courses'] ) );
                    }else echo esc_url( get_page_link(get_page_by_path('mcv-my-courses')) ); 
                    ?>" target="_blank" rel="noopener noreferrer">课程表</a></li>
                    <li class="li-item"><a href="<?php echo esc_url( mcv_order_list_url() ); ?>" target="_blank"
                            rel="noopener noreferrer">订单管理</a></li>
                    <li class="li-item"><a href="<?php 
                    if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses']) && MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] ){
                        echo esc_url( get_page_link( MINECLOUDVOD_SETTINGS['mcv_lms_general']['fav_courses'] ) );
                    }else echo esc_url( get_page_link(get_page_by_path('mcv-favorites')) ); ?>"
                            target="_blank" rel="noopener noreferrer">我的收藏</a></li>
                </ul>
            </div>
        </div>
        <div class="mcv-progress"></div>
    </div>

    <?php if($canplay): ?>
        <?php if( $is_live ): $ntime = time(); // 直播 ?>
        <div class="ke_overlay study-body" id="mcv-ketang-main-body">
            <?php if( $ntime >= $stime && $ntime <= $etime ): // 直播中 ?>
            <div class="video-wrap mcv-video" style="height: 100%;">
                <?php the_content();?>
            </div>
            <?php elseif( $ntime < $stime ): // 未开始 ?>
            <div class="ke_overlay_content live">
                <p class="title"><?php echo esc_html($post->post_title); ?> 未开始</p>
                <p class="next">直播时间：<?php echo esc_html(wp_date( 'm月d日 H:i', $stime ) . '-' . wp_date( 'H:i', $etime )); ?></p>

            </div>
            <?php else: // 已结束 ?>
            <div class="ke_overlay_content live">
                <p class="title">该直播任务已结束！</p>
                <?php if( count( $next ) > 0 ): ?>
                <p class="next">下节任务：<?php echo esc_html( $next['title'] ); ?></p>
                <div class="btn-wrap"><a href="<?php echo esc_url( $next['link'] ); ?>" target="_parent"><span
                            class="btn next-btn">下一任务</span></a></div>
                <?php endif; ?>
                <!-- <p class="download-tips">本节课有回放，点击<a target="_blank" href="#">观看回放</a></p> -->
            </div>
            <?php endif; ?>
        </div>
        <?php else: // 点播 ?>
        <div class="ke_overlay study-body <?php echo esc_attr( $lesson_type ); ?>" id="mcv-ketang-main-body">
            <div class="mcv-anchor"></div>
            <div class="video-wrap mcv-video <?php echo esc_attr( $lesson_type ); ?>" style="height: 100%;">
                <?php the_content();?>
            </div>
        </div>
        <?php endif; ?>
    <?php elseif($is_free): 
            $login_url = wp_login_url( get_the_permalink() );
            $register_url = mcv_registration_url();
        ?>
    <div class="ke_overlay study-body" id="mcv-ketang-main-body">
        <div class="ke_overlay_content">
            <p class="title">请登录后学习完整内容</p>
            <div class="pay-info">
                <a target="_blank" href="<?php echo esc_url( get_the_permalink($course_id) ); ?>" class="link"
                    rel="noopener noreferrer"><?php echo esc_html( $course_title ); ?></a>
                <p class="pay">免费</p>
                <div class="btn-wrap">
                    <a class="btn pay-btn mcv-login" href="javascript:;">立即登录</a>
                    <a class="btn consult-btn" href="<?php echo esc_url($register_url);?>" target="_parent">注册会员</a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="ke_overlay" id="mcv-ketang-main-body">
        <div class="ke_overlay_content">
            <p class="title">请付费后学习完整内容</p>
            <?php if( $lprice ): ?>
            <div class="pay-info">
                <p>
                    <span class="link" style="width:170px;">本节课时</span>
                    <a target="_blank" href="<?php echo esc_url( get_the_permalink($course_id) ); ?>" class="link"
                        style="width:170px;" rel="noopener noreferrer">完整课程</a>
                </p>
                <p>
                    <b class="pay" style="width:170px;display: inline-block;"><?php echo esc_html($lprice); ?></b>
                    <b class="pay" style="width:170px;display: inline-block;"><?php echo esc_html($course_price); ?></b>
                </p>
                <div class="btn-wrap">
                    <a class="btn pay-btn" href="<?php echo esc_url(mcv_checkout_url(['id' => $post->ID])); ?>"
                        <?php echo esc_attr($is_iframe ? ' target="_blank"' : '');?>>课时购买</a>
                    <a class="btn consult-btn" href="<?php echo esc_url(mcv_checkout_url(['id' => $course_id])); ?>"
                        <?php echo esc_attr($is_iframe ? ' target="_blank"' : '');?>>完整购买</a>
                </div>
            </div>
            <?php else: ?>
            <div class="pay-info">
                <a target="_blank" href="<?php echo esc_url( get_the_permalink($course_id) ); ?>" class="link"
                    rel="noopener noreferrer"><?php echo esc_html($course_title); ?></a>
                <p class="pay"><?php echo esc_html($course_price); ?></p>
                <div class="btn-wrap">
                    <a class="btn pay-btn" href="<?php echo esc_url( apply_filters( 'mcv_lms_buy_link', mcv_checkout_url(['id' => $course_id]), $course_id ) ); ?>"
                        <?php echo esc_attr($is_iframe ? ' target="_blank"' : '');?>><?php echo esc_html( apply_filters( 'mcv_lms_buy_btn', __('Buy now', 'mine-cloudvod'), $course_id ) ); ?></a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="right-side-bar normal-task" id="right-side-bar" style="right: 0px;"></div>
</div>
<?php
do_action('mcv_lms_lesson_after', $post);
?>