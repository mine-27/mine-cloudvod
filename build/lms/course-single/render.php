<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

/* ── 加载职责模块 ── */
require_once __DIR__ . '/mcv-cs-data.php';
require_once __DIR__ . '/mcv-cs-sections.php';
require_once __DIR__ . '/mcv-cs-assets.php';

if ( ! is_admin() ):

/* ── 课程帖子初始化 ── */
$cid   = $attributes['cid'] ?? 0;
global $post;
$cpost = $post;
if ( $cid ) {
    $cpost = get_post( $cid );
}
if ( ! $cpost ) return;

$course_id    = $cpost->ID;
$course_title = get_the_title( $cpost );
$thumbnail    = mcv_lms_get_course_thumbnail_url( $cpost );
$access_mode  = mcv_lms_get_course_access_mode( $course_id );
$course_price = mcv_lms_show_course_price( $course_id, true );
$is_enrolled  = mcv_lms_is_enrolled( $course_id );
$progress     = mcv_lms_get_course_progress( $cpost );
$is_user_logged_in = is_user_logged_in();
$login_url    = wp_login_url( $progress['next'] );

/* ── 价格 / 按钮 / VIP ── */
$start_url = mcv_cs_start_url( $course_id, $access_mode, $is_enrolled, $is_user_logged_in, $progress );
$start_url = apply_filters( 'mcv_lms_buy_link', $start_url, $course_id );
$vipstr    = $access_mode === 'buynow' ? mcv_cs_vipstr( $course_id ) : '';
$str_price = mcv_cs_price_text( $access_mode, $is_enrolled, $course_price );
$btn_txt   = mcv_cs_btn_text( $access_mode, $is_enrolled, $course_id );

/* ── 课程元数据 ── */
$course_terms   = get_the_terms( $course_id, 'course-category' );
$update_status  = get_post_meta( $course_id, '_mcv_course_update_status', true );
$period         = mcv_cs_period_text( $course_id );
$cd_options     = MINECLOUDVOD_SETTINGS['mcv_lms_course']['details'] ?? true;

/* ── 目录可见性 ── */
$catelog_show   = get_post_meta( $course_id, '_mcv_course_catelog', true );
$catelog_show   = $catelog_show === '' ? false : (bool) $catelog_show;
$catelog_enroll = get_post_meta( $course_id, '_mcv_course_catelog_enroll', true );
$catelog_enroll = $catelog_enroll === '' ? false : (bool) $catelog_enroll;
$catelog_no     = get_post_meta( $course_id, '_mcv_course_no_type', true );
$catelog_no     = $catelog_no === '' ? true : (bool) $catelog_no;

/* ── 章节数据 ── */
$sections_data = mcv_cs_build_sections( $cpost, $catelog_show, $catelog_enroll, $is_user_logged_in, $is_enrolled, $course_id );
$lists         = $sections_data['lists'];
$lessonCount   = $sections_data['lessonCount'];
$duration      = $sections_data['duration'];
$lesson_list   = $sections_data['lesson_list'];
$shixue        = $sections_data['shixue'];

/* ── 资源加载 + 前端数据 ── */
mcv_cs_enqueue_assets( $is_user_logged_in );
$course_attachments = mcv_lms_get_course_attachments( $course_id );
$star_counts = mcv_cs_star_counts( $course_id );
mcv_cs_localize_data( $course_id, $course_title, $thumbnail, $cpost, $access_mode, $is_enrolled, $course_attachments, $star_counts, $catelog_no, $catelog_show, $catelog_enroll, $lessonCount, $lists );

/* ── HTML 模板 ── */
if ( $cid ):
?>
    <div class="banner-wrap">
        <div class="course-banner--pay">
            <div class="course-banner-bg" style="background-image: url(&quot;<?php echo esc_url($thumbnail);?>&quot;);"></div>
            <div class="course-banner-hd">
            </div>
            <div class="course-banner-inner">
                <div class="course-banner-row course-single">
                    <div class="section-left">
                        <div class="cover-pay-wrapper" style="background-image: url(&quot;<?php echo esc_url($thumbnail);?>&quot;);width:100%;height: auto;padding-top: 56.25%;">
                            <a class="cover-mask cover-mask--trial <?php echo esc_attr(!$shixue && !$is_user_logged_in && $access_mode!='open' ? 'mcv-login' : '') ;?>" href="<?php echo esc_url($shixue ? $shixue : $start_url) ; ?>">
                                <div class="cover-mask-icon js-expr-btn">
                                    <span class="ke-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none">
                                            <path d="M18.76 9.856c1.619.971 1.619 3.317 0 4.288l-9.307 5.584c-1.666 1-3.786-.2-3.786-2.144V6.415c0-1.943 2.12-3.143 3.786-2.143l9.308 5.584Z" fill="currentColor"></path>
                                        </svg>
                                    </span>
                                </div>
                                <p class="cover-mask-txt"><?php echo esc_html__('Start learning', 'mine-cloudvod'); ?></p>
                            </a>
                        </div>
                    </div>
                    <div class="section-right">
                        <a href="<?php echo esc_url(get_the_permalink( $cpost )); ?>"><h1 class="course-title"><?php echo esc_html($course_title); ?></h1></a>
                        <p style="color:#ff7a38;margin:0;padding:0;"><?php echo esc_html($str_price); ?></p>
                        <p class="course-hints" style="flex-wrap: wrap;line-height:1.6;">
                            <?php if( $cd_options && ($cd_options['lesson_num']??true) ): ?>
                            <span>
                                <span class="num"><?php echo esc_html($lessonCount); ?></span><?php echo esc_html_x( 'Lessons', 'course single page', 'mine-cloudvod' );?>
                            </span>
                            <?php endif; ?>
                            <?php if( $cd_options && ($cd_options['hours']??true) ): ?>
                            <span>
                                <span class="num"><?php echo esc_html(round($duration/60/60, 2));?></span><?php echo esc_html_x( 'Hours', 'course single page', 'mine-cloudvod' );?>
                            </span>
                            <?php endif; ?>
                            <?php if( $cd_options && ($cd_options['student_num']??true) ): ?>
                            <span>
                                <span class="num"><?php echo esc_html(mcv_lms_get_course_enrolled_number( $cpost )); ?></span><?php echo esc_html_x( 'Students', 'course single page', 'mine-cloudvod' );?>
                            </span>
                            <?php endif; ?>
                            <?php if( $cd_options && ($cd_options['update']??true) ): ?>
                            <span>
                                <span class="num"><?php echo esc_html(get_the_modified_date('Y-m-d')); ?></span><?php echo esc_html_x( 'Updated', 'course single page', 'mine-cloudvod' );?>
                            </span>
                            <?php endif; ?>
                            <?php if( $cd_options && ($cd_options['difficulty']??true) ): 
                                $darr = MINECLOUDVOD_LMS['course_difficulty'];
                                $difficulty = get_post_meta( $course_id, '_mcv_course_difficulty', true );
                                ?>
                            <span>
                            <?php echo esc_html_x( 'Difficulty', 'course single page', 'mine-cloudvod' );?> <span class="num"><?php echo esc_html($darr[$difficulty]); ?></span>
                            </span>
                            <?php endif; ?>
                            <span>
                                <?php echo esc_html__('Validity period', 'mine-cloudvod'); ?> <span class="num"><?php echo esc_html($period); ?></span>
                            </span>
                        </p>
                        <?php do_action( 'mcv_course_attrs_after' ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
else:
?>
    <main class="mcv-global-main">
        <div class="banner-wrap">
            <div class="course-banner--pay">
                <div class="course-banner-bg" style="background-image: url(&quot;<?php echo esc_url($thumbnail);?>&quot;);"></div>
                <div class="course-banner-hd">
                    <div class="mcv-breadcrumb">
                        <a href="<?php echo esc_url(get_post_type_archive_link($cpost->post_type)); ?>"><?php echo esc_html__('All courses', 'mine-cloudvod'); ?></a>
                        <?php if( $course_terms ): ?>
                        <span class="ke-icon" style="color: rgb(161, 169, 178); font-size: 14px;">
                            <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M9.636 20.435a1 1 0 0 0 1.414 0l4.95-4.95a4 4 0 0 0 0-5.656l-4.95-4.95a1 1 0 0 0-1.414 1.414l4.95 4.95a2 2 0 0 1 0 2.828l-4.95 4.95a1 1 0 0 0 0 1.414Z" fill="currentColor"></path>
                            </svg>
                        </span>
                        <?php foreach( $course_terms as $term ): ?>
                        <a href="<?php echo esc_url(get_term_link( $term )); ?>"><?php echo esc_html($term->name); ?></a> &nbsp;
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <span class="ke-icon" style="color: rgb(161, 169, 178); font-size: 14px;">
                            <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M9.636 20.435a1 1 0 0 0 1.414 0l4.95-4.95a4 4 0 0 0 0-5.656l-4.95-4.95a1 1 0 0 0-1.414 1.414l4.95 4.95a2 2 0 0 1 0 2.828l-4.95 4.95a1 1 0 0 0 0 1.414Z" fill="currentColor"></path>
                            </svg>
                        </span>
                        <a>正文</a>
                    </div>
                    <div class="section-corner">
                        <div id="mcv-course-fav"></div>
                        <div id="mcv-course-share"></div>
                    </div>
                </div>
                <div class="course-banner-inner">
                    <div class="course-banner-row">
                        <div class="section-left">
                            <div class="cover-pay-wrapper" style="background-image: url(&quot;<?php echo esc_url($thumbnail);?>&quot;);">
                                <a class="cover-mask cover-mask--trial <?php echo esc_attr(!$shixue && !$is_user_logged_in && $access_mode!='open' ? 'mcv-login' : '') ;?>" href="<?php echo esc_url($shixue ? $shixue : $start_url) ; ?>">
                                    <div class="cover-mask-icon js-expr-btn">
                                        <span class="ke-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none">
                                                <path d="M18.76 9.856c1.619.971 1.619 3.317 0 4.288l-9.307 5.584c-1.666 1-3.786-.2-3.786-2.144V6.415c0-1.943 2.12-3.143 3.786-2.143l9.308 5.584Z" fill="currentColor"></path>
                                            </svg>
                                        </span>
                                    </div>
                                    <p class="cover-mask-txt"><?php echo $shixue ? esc_html__('Can try', 'mine-cloudvod') : esc_html__('Start learning', 'mine-cloudvod'); ?></p>
                                </a>
                            </div>
                        </div>
                        <div class="section-right">
                            <h1 class="course-title"><?php echo esc_html($course_title); ?></h1>
                            <?php if( current_user_can( 'edit_post', $course_id ) ): ?>
                            <div class="course-highlights">
                                <div class="highlight-item">
                                    <span><a href="<?php echo esc_url(admin_url('post.php?post='.$course_id.'&action=edit'));?>">编辑</a></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <p class="course-hints">
                                <?php if( $cd_options && ($cd_options['lesson_num']??true) ): ?>
                                <span>
                                    <span class="num"><?php echo esc_html($lessonCount); ?></span><?php echo esc_html_x( 'Lessons', 'course single page', 'mine-cloudvod' );?>
                                </span>
                                <?php endif; ?>
                                <?php if( $cd_options && ($cd_options['hours']??true) ): ?>
                                <span>
                                    <span class="num"><?php echo esc_html(round($duration/60/60, 2));?></span><?php echo esc_html_x( 'Hours', 'course single page', 'mine-cloudvod' );?>
                                </span>
                                <?php endif; ?>
                                <?php if( $cd_options && ($cd_options['student_num']??true) ): ?>
                                <span>
                                    <span class="num"><?php echo esc_html(mcv_lms_get_course_enrolled_number()); ?></span><?php echo esc_html_x( 'Students', 'course single page', 'mine-cloudvod' );?>
                                </span>
                                <?php endif; ?>
                                <?php if( $cd_options && ($cd_options['update']??true) ): ?>
                                <span>
                                    <span class="num"><?php echo esc_html(get_the_modified_date('Y-m-d')); ?></span><?php echo esc_html_x( 'Updated', 'course single page', 'mine-cloudvod' );?>
                                </span>
                                <?php endif; ?>
                                <?php if( $cd_options && ($cd_options['difficulty']??true) ): 
                                    $darr = MINECLOUDVOD_LMS['course_difficulty'];
                                    $difficulty = get_post_meta( $course_id, '_mcv_course_difficulty', true );
                                    ?>
                                <span>
                                <?php echo esc_html_x( 'Difficulty', 'course single page', 'mine-cloudvod' );?> <span class="num"><?php echo esc_html($darr[$difficulty]); ?></span>
                                </span>
                                <?php endif; ?>
                                <span>
                                    <?php echo esc_html__('Validity period', 'mine-cloudvod'); ?> <span class="num"><?php echo esc_html($period); ?></span>
                                </span>
                            </p>
                            <?php do_action( 'mcv_course_attrs_after' ); ?>
                        <p class="course-hints mcv-kefu">
                            <?php
                                $kefuqr = MINECLOUDVOD_SETTINGS['mcv_lms_general']['kefuqr']??'';
                                $kefutips = MINECLOUDVOD_SETTINGS['mcv_lms_general']['kefutips']??'';
                                if( $kefuqr ) echo '<span class="btn-fav qrtips" data-title="添加客服" data-qr="'.esc_attr($kefuqr).'" data-tips="'.esc_attr($kefutips).'">客服</span>';
                                $mpqr = MINECLOUDVOD_SETTINGS['mcv_lms_general']['mpqr']??'';
                                $mptips = MINECLOUDVOD_SETTINGS['mcv_lms_general']['mptips']??'';
                                if( $mpqr ) echo '<span class="btn-fav qrtips" data-title="关注公众号" data-qr="'.esc_attr($mpqr).'" data-tips="'.esc_attr($mptips).'">公众号</span>';
                                $qqgroup = get_post_meta( $course_id, 'qqgroup', true );
                                $qqgrouptips = get_post_meta( $course_id, 'qqgrouptips', true );
                                if( $qqgroup ) echo '<span class="btn-fav qqtips " data-title="QQ群" data-qq="'.esc_attr($qqgroup).'" data-tips="'.esc_attr($qqgrouptips).'">QQ群</span>';
                            ?>
                        </p>
                        </div>
                    </div>
                    <div class="section-apply">
                        <div class="apply-bar-workspace apply-bar" style="--bgColor:#f23f4e;">
                            <div class="apply-bar-main">
                                <div class="main-left">
                                    <div class="js-course-price normal-course course-price">
                                        <div class="course-price-info">
                                            <span id="course-price" class="price-container"><?php echo esc_html($str_price); ?></span>
                                            <?php echo wp_kses($vipstr, mcsf_allowed_html()); ?>
                                        </div>
                                    </div>
                                    <?php if( $update_status ): ?>
                                    <p class="dividing">|</p>
                                    <div class="apply-promise"><?php echo esc_html($update_status); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="main-right">
                                    <div class="apply-btn-box">
                                        <div>
                                            <div class="">
                                                <div class="general-btn institution-btn">
                                                    <a type="button" class="general-btn-main institution-btn-main <?php echo esc_attr(!$is_user_logged_in && $access_mode!='open' ? 'mcv-login' : '');?>" href="<?php echo esc_url($start_url) ; ?>">
                                                        <span class="general-btn-txt institution-btn-txt"><?php echo esc_html($btn_txt); ?></span>
                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="apply-bar-suffix" id="apply-bar-suffix">
                                <div class="basic-bar-layout-mores"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="section-main <?php echo esc_attr(MINECLOUDVOD_SETTINGS['mcv_lms_course']['course_style']??''); ?>">
            <main id="mcv-course-main">
            <div class="mcv-tab-panel">
                <div role="tablist" aria-orientation="horizontal" class="components-tab-panel__tabs">
                    <button type="button" role="tab" aria-selected="true" id="tab-panel-0-tab-content" aria-controls="tab-panel-0-tab-content-view" class="components-button components-tab-panel__tabs-item tab-content is-active"><?php echo esc_html__('Course Details', 'mine-cloudvod');?></button>
                    <button type="button" role="tab" tabindex="-1" aria-selected="false" id="tab-panel-0-tab-mulu" aria-controls="tab-panel-0-tab-mulu-view" class="components-button components-tab-panel__tabs-item tab-mulu"><?php echo esc_html__('Course Catelog', 'mine-cloudvod');?></button>
                </div>
                <div aria-labelledby="tab-panel-0-tab-content" role="tabpanel" id="tab-panel-0-tab-content-view" class="components-tab-panel__tab-content">
                    <div class="course-catalog-ctn">
                        <section class="section detail-content">
                            <?php the_content(); ?>
                        </section>
                </div>
            </div>
            </main>
            <aside class="aside">
                <div class="recommend ">
                    <div class="recommend-box">
                        <h2 class="recommend-tt"><?php echo esc_html__('Recommended courses', 'mine-cloudvod');?></h2>
                        <ul class="course-list agency">
                            <?php 
                            $recomm = mcv_get_relate_course( $course_id );
                            foreach($recomm as $rc){
                                $acmode = mcv_lms_get_course_access_mode( $rc->ID );
                                $cprice = mcv_lms_show_course_price( $rc->ID, true );
                            ?>
                            <li>
                                <section class="course-card-expo-wrapper">
                                    <a class="kc-course-card js-report-link kc-course-card-row" href="<?php echo esc_url(get_the_permalink($rc->ID));?>">
                                        <div class="kc-course-card-cover">
                                            <img src="<?php echo esc_url(mcv_lms_get_course_thumbnail_url($rc->ID));?>" alt="课程封面" class="">
                                        </div>
                                        <div class="kc-course-card-content">
                                            <h3 class="kc-course-card-name" title="<?php echo esc_html($rc->post_title);?>"><?php echo esc_html($rc->post_title);?></h3>
                                            <div class="kc-course-card-footer">
                                                <div class="kc-coursecard-footer-left">
                                                    <span class="kc-course-card-price">
                                                        <?php echo $acmode == 'buynow' ? '<span class="kc-coursecard-price ">
                                                            <span>'. esc_html($cprice) .'</span>
                                                        </span>' : ($acmode == 'free' ? esc_html__('Free', 'mine-cloudvod') : ($acmode == 'open' ? esc_html__('Open', 'mine-cloudvod') : '')); ?>
                                                    </span>
                                                    <?php if( $cd_options && ($cd_options['student_num']??true) ): ?>
                                                    <span class="kc-course-card-student"><?php echo esc_html(mcv_lms_get_course_enrolled_number($rc)) . esc_html_x( 'Students', 'course single page', 'mine-cloudvod' );?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </section>
                            </li>
                            <?php
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </main>
    <?php do_action( 'mcv_after_course_rendered' ); ?>
<?php endif;endif; ?>
