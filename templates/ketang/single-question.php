<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 
defined( 'ABSPATH' ) || exit;

get_header( 'mcv-lms' );

$question_safe = do_blocks('<!-- wp:template-part {"slug":"header","theme":"mine-educn","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","metadata":{"name":"Main"},"align":"full","className":"medu-main","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"layout":{"type":"default"}} -->
<main class="wp-block-group alignfull medu-main" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"blockGap":"0px","padding":{"top":"1.5rem","right":"0px","bottom":"0px","left":"0px"}}},"backgroundColor":"background","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background-background-color has-background" style="padding-top:1.5rem;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"1.4rem","bottom":"1.4rem","left":"1.4rem","right":"1.4rem"}},"border":{"radius":"6px"},"shadow":"var:preset|shadow|natural"},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="border-radius:6px;padding-top:1.4rem;padding-right:1.4rem;padding-bottom:1.4rem;padding-left:1.4rem;box-shadow:var(--wp--preset--shadow--natural)"><!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:post-title {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontStyle":"normal","fontWeight":"600"}}} /-->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"#1c79eb"}}},"color":{"text":"#1c79eb"}}} -->
<p class="has-text-color has-link-color" style="color:#1c79eb"><a href="'.esc_url(get_post_type_archive_link('mcv_question')).'">切换科目</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_exam_time","content":"距离考试还有\u0026nbsp;{$_mcv_exam_time}\u0026nbsp;天"}}}}} -->
<p>距离考试还有&nbsp;{$_mcv_exam_time} 天</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"mcv-grid","style":{"spacing":{"blockGap":"1.55rem","margin":{"top":"1.55rem"}}},"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":null}} -->
<div class="wp-block-group alignwide mcv-grid" style="margin-top:1.55rem"><!-- wp:mine-cloudvod/question-section -->
<div class="wp-block-mine-cloudvod-question-section"><!-- wp:group {"style":{"spacing":{"padding":{"top":"1.8rem","bottom":"1.8rem"},"blockGap":"1.5rem"},"color":{"background":"#d2eaf8"},"border":{"radius":"6px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="border-radius:6px;background-color:#d2eaf8;padding-top:1.8rem;padding-bottom:1.8rem"><!-- wp:post-title {"textAlign":"center","level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"1.45rem"},"spacing":{"margin":{"top":"0px","bottom":"0px"}}}} /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"24px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
<div class="wp-block-group" style="margin-top:24px"><!-- wp:post-content {"style":{"elements":{"link":{"color":{"text":"#5c5c5c"}}},"color":{"text":"#5c5c5c"}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:mine-cloudvod/question-section -->

<!-- wp:group {"className":"mcv-questions-fav-0","style":{"spacing":{"padding":{"top":"1.8rem","bottom":"1.8rem"},"blockGap":"1.5rem"},"border":{"radius":"6px"}},"gradient":"pale-ocean","layout":{"type":"constrained"}} -->
<div class="wp-block-group mcv-questions-fav-0 has-pale-ocean-gradient-background has-background" style="border-radius:6px;padding-top:1.8rem;padding-bottom:1.8rem"><!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"1.45rem"},"spacing":{"margin":{"top":"0px","bottom":"0px"}}}} -->
<h3 class="wp-block-heading has-text-align-center" style="margin-top:0px;margin-bottom:0px;font-size:1.45rem;font-style:normal;font-weight:500">收藏夹</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#5c5c5c"}}},"color":{"text":"#5c5c5c"},"spacing":{"margin":{"top":"24px"}}}} -->
<p class="has-text-align-center has-text-color has-link-color" style="color:#5c5c5c;margin-top:24px">查漏补缺，提升刷题技巧</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"mcv-questions-fav-1","style":{"spacing":{"padding":{"top":"1.8rem","bottom":"1.8rem"},"blockGap":"2rem"},"border":{"radius":"6px"}},"gradient":"vivid-cyan-blue-to-vivid-purple","layout":{"type":"constrained"}} -->
<div class="wp-block-group mcv-questions-fav-1 has-vivid-cyan-blue-to-vivid-purple-gradient-background has-background" style="border-radius:6px;padding-top:1.8rem;padding-bottom:1.8rem"><!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"1.45rem"},"spacing":{"margin":{"top":"0px","bottom":"0px"}}}} -->
<h3 class="wp-block-heading has-text-align-center" style="margin-top:0px;margin-bottom:0px;font-size:1.45rem;font-style:normal;font-weight:500">错题本</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#5c5c5c"}}},"color":{"text":"#5c5c5c"},"spacing":{"margin":{"top":"24px"}}}} -->
<p class="has-text-align-center has-text-color has-link-color" style="color:#5c5c5c;margin-top:24px">查漏补缺，提升刷题技巧</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"mcv-questions-attachments","style":{"spacing":{"padding":{"top":"1.8rem","bottom":"1.8rem"},"blockGap":"2rem"},"border":{"radius":"6px"}},"gradient":"light-green-cyan-to-vivid-green-cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group mcv-questions-attachments has-light-green-cyan-to-vivid-green-cyan-gradient-background has-background" style="border-radius:6px;padding-top:1.8rem;padding-bottom:1.8rem"><!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"1.45rem"},"spacing":{"margin":{"top":"0px","bottom":"0px"}}}} -->
<h3 class="wp-block-heading has-text-align-center" style="margin-top:0px;margin-bottom:0px;font-size:1.45rem;font-style:normal;font-weight:500">资料下载</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#5c5c5c"}}},"color":{"text":"#5c5c5c"},"spacing":{"margin":{"top":"24px"}}}} -->
<p class="has-text-align-center has-text-color has-link-color" style="color:#5c5c5c;margin-top:24px">题库配套资料下载</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"mcv-questions-buynow","style":{"spacing":{"padding":{"top":"1.8rem","bottom":"1.8rem"},"blockGap":"1.5rem"},"border":{"radius":"6px"},"color":{"gradient":"linear-gradient(135deg,rgb(255,105,0) 0%,rgb(207,46,46) 100%)"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group mcv-questions-buynow has-background" style="border-radius:6px;background:linear-gradient(135deg,rgb(255,105,0) 0%,rgb(207,46,46) 100%);padding-top:1.8rem;padding-bottom:1.8rem"><!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"1.45rem"},"spacing":{"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
<h3 class="wp-block-heading has-text-align-center" style="margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;font-size:1.45rem;font-style:normal;font-weight:500">立即购买</h3>
<!-- /wp:heading --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"3.6rem","bottom":"3.6rem","left":"1.4rem","right":"1.4rem"},"margin":{"top":"2rem","bottom":"2rem"}},"border":{"radius":"6px"},"shadow":"var:preset|shadow|natural"},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="border-radius:6px;margin-top:2rem;margin-bottom:2rem;padding-top:3.6rem;padding-right:1.4rem;padding-bottom:3.6rem;padding-left:1.4rem;box-shadow:var(--wp--preset--shadow--natural)"><!-- wp:group {"align":"wide","className":"mcv-grid","layout":{"type":"grid","columnCount":4,"minimumColumnWidth":null}} -->
<div class="wp-block-group alignwide mcv-grid"><!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"},"blockGap":"1rem"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-bottom:0"><!-- wp:paragraph {"align":"center","metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_correct","type":"question"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|luminous-vivid-orange"}}},"typography":{"fontSize":"2rem"}},"textColor":"luminous-vivid-orange"} -->
<p class="has-text-align-center has-luminous-vivid-orange-color has-text-color has-link-color" style="font-size:2rem"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">答对题数</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"},"blockGap":"1rem"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-bottom:0"><!-- wp:paragraph {"align":"center","metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_incorrect","type":"question"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|luminous-vivid-orange"}}},"typography":{"fontSize":"2rem"}},"textColor":"luminous-vivid-orange"} -->
<p class="has-text-align-center has-luminous-vivid-orange-color has-text-color has-link-color" style="font-size:2rem"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">答错题数</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"},"blockGap":"1rem"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-bottom:0"><!-- wp:paragraph {"align":"center","metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_correctrate","type":"question"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|luminous-vivid-orange"}}},"typography":{"fontSize":"2rem"}},"textColor":"luminous-vivid-orange"} -->
<p class="has-text-align-center has-luminous-vivid-orange-color has-text-color has-link-color" style="font-size:2rem"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">正确率</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"},"blockGap":"1rem"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-bottom:0"><!-- wp:paragraph {"align":"center","metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_count"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|luminous-vivid-orange"}}},"typography":{"fontSize":"2rem"}},"textColor":"luminous-vivid-orange"} -->
<p class="has-text-align-center has-luminous-vivid-orange-color has-text-color has-link-color" style="font-size:2rem"><strong>5000</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">总题数</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Chapter"},"align":"wide","style":{"spacing":{"padding":{"top":"1.4rem","bottom":"1.4rem","left":"1.4rem","right":"1.4rem"},"margin":{"top":"2rem","bottom":"2rem"}},"border":{"radius":"6px"},"shadow":"var:preset|shadow|natural"},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" id="mcv_question_chapter_section" style="border-radius:6px;margin-top:2rem;margin-bottom:2rem;padding-top:1.4rem;padding-right:1.4rem;padding-bottom:1.4rem;padding-left:1.4rem;box-shadow:var(--wp--preset--shadow--natural)"><!-- wp:group {"metadata":{"name":"Title"},"align":"wide","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25rem"}}} -->
<p style="font-size:1.25rem">章节练习</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:mine-cloudvod/question-section {"isChapter":true,"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-mine-cloudvod-question-section alignwide"><!-- wp:group {"metadata":{"name":"Section Level 1"},"align":"wide","className":"mcv-section","style":{"spacing":{"padding":{"top":"1.1rem","bottom":"1.1rem","left":"1.1rem","right":"1.1rem"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide mcv-section" style="padding-top:1.1rem;padding-right:1.1rem;padding-bottom:1.1rem;padding-left:1.1rem"><!-- wp:mine-cloudvod/question-section-title /-->

<!-- wp:group {"className":"mcv-mobile-hide","style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group mcv-mobile-hide"><!-- wp:paragraph -->
<p>正确率</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_correctrate","type":"section"}}}},"textColor":"luminous-vivid-amber"} -->
<p class="has-luminous-vivid-amber-color has-text-color"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_answered","type":"section"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|vivid-green-cyan"}}}},"textColor":"vivid-green-cyan"} -->
<p class="has-vivid-green-cyan-color has-text-color has-link-color"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>/</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_count"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|vivid-cyan-blue"}}}},"textColor":"vivid-cyan-blue"} -->
<p class="has-vivid-cyan-blue-color has-text-color has-link-color"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Sections Level 2"},"align":"wide","className":"mcv-section-sub","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"blockGap":"0"},"color":{"background":"#f9f8f8"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide mcv-section-sub has-background" style="background-color:#f9f8f8;margin-top:0;margin-bottom:0"><!-- wp:mine-cloudvod/question-section {"isChapter":true,"align":"wide","className":"medu-faq-toggle","layout":{"type":"constrained"}} -->
<div class="wp-block-mine-cloudvod-question-section alignwide medu-faq-toggle"><!-- wp:group {"metadata":{"name":"Section 2"},"align":"wide","style":{"spacing":{"padding":{"top":"1.1rem","bottom":"1.1rem","left":"2.2rem","right":"1.1rem"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="padding-top:1.1rem;padding-right:1.1rem;padding-bottom:1.1rem;padding-left:2.2rem"><!-- wp:mine-cloudvod/question-section-title /-->

<!-- wp:group {"className":"mcv-mobile-hide","style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group mcv-mobile-hide"><!-- wp:paragraph -->
<p>正确率</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_correctrate","type":"section"}}}},"textColor":"luminous-vivid-amber"} -->
<p class="has-luminous-vivid-amber-color has-text-color"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_answered","type":"section"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|vivid-green-cyan"}}}},"textColor":"vivid-green-cyan"} -->
<p class="has-vivid-green-cyan-color has-text-color has-link-color"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>/</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"mine-cloudvod/post-meta","args":{"key":"_mcv_question_count"}}}},"style":{"elements":{"link":{"color":{"text":"var:preset|color|vivid-cyan-blue"}}}},"textColor":"vivid-cyan-blue"} -->
<p class="has-vivid-cyan-blue-color has-text-color has-link-color"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:mine-cloudvod/question-section --></div>
<!-- /wp:group --></div>
<!-- /wp:mine-cloudvod/question-section --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","theme":"mine-educn","tagName":"footer"} /-->');
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks 渲染的可信区块 HTML
echo $question_safe;

get_footer( 'mcv-lms' );
