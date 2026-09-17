<?php
/**
 * 课程详情页 V2 默认布局 Pattern
 *
 * 此文件返回一个 HTML 字符串，作为 Block Pattern 的 content。
 * 布局模拟原始 monolithic 区块的外观，但用户可自由调整。
 */
return '<!-- wp:mine-cloudvod/course-single-v2 {"align":"wide"} -->
<div class="wp-block-mine-cloudvod-course-single-v2 alignwide">
    <!-- wp:mine-cloudvod/course-breadcrumb -->
    <div class="wp-block-mine-cloudvod-course-breadcrumb"></div>
    <!-- /wp:mine-cloudvod/course-breadcrumb -->

    <!-- wp:mine-cloudvod/course-cover -->
    <div class="wp-block-mine-cloudvod-course-cover"></div>
    <!-- /wp:mine-cloudvod/course-cover -->

    <!-- wp:mine-cloudvod/course-title {"level":1} -->
    <h1 class="wp-block-mine-cloudvod-course-title"></h1>
    <!-- /wp:mine-cloudvod/course-title -->

    <!-- wp:mine-cloudvod/course-meta -->
    <div class="wp-block-mine-cloudvod-course-meta"></div>
    <!-- /wp:mine-cloudvod/course-meta -->

    <!-- wp:mine-cloudvod/course-price -->
    <div class="wp-block-mine-cloudvod-course-price"></div>
    <!-- /wp:mine-cloudvod/course-price -->

    <!-- wp:mine-cloudvod/course-buy-bar -->
    <div class="wp-block-mine-cloudvod-course-buy-bar"></div>
    <!-- /wp:mine-cloudvod/course-buy-bar -->

    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column {"width":"70%"} -->
        <div class="wp-block-column" style="flex-basis:70%">
            <!-- wp:mine-cloudvod/course-content -->
            <div class="wp-block-mine-cloudvod-course-content"></div>
            <!-- /wp:mine-cloudvod/course-content -->

            <!-- wp:mine-cloudvod/course-chapters -->
            <div class="wp-block-mine-cloudvod-course-chapters"></div>
            <!-- /wp:mine-cloudvod/course-chapters -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"width":"30%"} -->
        <div class="wp-block-column" style="flex-basis:30%">
            <!-- wp:mine-cloudvod/course-recommend -->
            <div class="wp-block-mine-cloudvod-course-recommend"></div>
            <!-- /wp:mine-cloudvod/course-recommend -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:mine-cloudvod/course-single-v2 -->';
