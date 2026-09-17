<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;

mcv_atomic_load_course_helpers();

$course_id     = mcv_atomic_get_course_id( $attributes, $block );
if ( ! $course_id ) return;

$cpost = get_post( $course_id );
if ( ! $cpost ) return;

// 读取目录可见性设置
$catelog_show   = get_post_meta( $course_id, '_mcv_course_catelog', true );
$catelog_show   = $catelog_show === '' ? false : (bool) $catelog_show;
$catelog_enroll = get_post_meta( $course_id, '_mcv_course_catelog_enroll', true );
$catelog_enroll = $catelog_enroll === '' ? false : (bool) $catelog_enroll;

$is_logged_in = is_user_logged_in();
$is_enrolled  = mcv_lms_is_enrolled( $course_id );

$sections_data = mcv_cs_build_sections( $cpost, $catelog_show, $catelog_enroll, $is_logged_in, $is_enrolled, $course_id );
$lists         = $sections_data['lists'];

?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 核心函数返回已转义的属性字符串 ?>>
    <?php if ( ! empty( $lists ) ) : ?>
        <div class="mcv-chapters-list">
            <?php foreach ( $lists as $idx => $section ) : ?>
                <div class="mcv-chapter-section" data-section-id="<?php echo esc_attr( $section['id'] ); ?>">
                    <div class="mcv-chapter-title">
                        <span class="mcv-chapter-index"></span>
                        <span class="mcv-chapter-name"><?php echo esc_html( $section['title'] ); ?></span>
                        <span class="mcv-chapter-lbl"><?php echo esc_html( count($section['lessons']) ); ?>节</span>
                    </div>
                    <?php if ( ! empty( $section['lessons'] ) ) : ?>
                        <ul class="mcv-chapter-lessons">
                            <?php foreach ( $section['lessons'] as $lesson ) : ?>
                                <?php if ( isset( $lesson['post_type'] ) && $lesson['post_type'] === 'section' ) : ?>
                                    <li class="mcv-lesson-item mcv-lesson-subsection">
                                        <span class="mcv-lesson-title"><?php echo esc_html( $lesson['title'] ); ?></span>
                                        <?php if ( ! empty( $lesson['lessons'] ) ) : ?>
                                            <ul class="mcv-chapter-lessons mcv-sublessons">
                                                <?php foreach ( $lesson['lessons'] as $sub_lesson ) : ?>
                                                    <li class="mcv-lesson-item">
                                                        <a href="<?php echo esc_url( $sub_lesson['url'] ); ?>"
                                                            class="mcv-lesson-link"
                                                            title="<?php echo esc_attr( $sub_lesson['title'] ); ?>">
                                                            <span class="mcv-lesson-title"><?php echo esc_html( $sub_lesson['title'] ); ?></span>
                                                            <?php if ( ! empty( $sub_lesson['attrs']['preview'] ) ) : ?>
                                                                <span class="mcv-lesson-badge mcv-badge-preview"><?php echo esc_html__( 'Preview', 'mine-cloudvod' ); ?></span>
                                                            <?php endif; ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php else : ?>
                                    <li class="mcv-lesson-item">
                                        <a href="<?php echo esc_url( $lesson['url'] ); ?>"
                                            class="mcv-lesson-link"
                                            title="<?php echo esc_attr( $lesson['title'] ); ?>"
                                            data-lesson-id="<?php echo esc_attr( $lesson['id'] ); ?>">
                                            <span class="mcv-lesson-title"><?php echo esc_html( $lesson['title'] ); ?></span>
                                            <?php if ( ! empty( $lesson['attrs']['preview'] ) ) : ?>
                                                <span class="mcv-lesson-badge mcv-badge-preview"><?php echo esc_html__( 'Preview', 'mine-cloudvod' ); ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="mcv-no-chapters"><?php echo esc_html__( 'No chapters available yet.', 'mine-cloudvod' ); ?></p>
    <?php endif; ?>
</div>
