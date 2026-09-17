<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

defined( 'ABSPATH' ) || exit;

/**
 * 获取模板路径
 */
if( !function_exists( 'mcv_lms_get_template_path' ) ){
    function mcv_lms_get_template_path( $template = null ){
        if( !$template ) return;
        $template = str_replace( '.', DIRECTORY_SEPARATOR, $template );

        /**
         * 首先，从子主题中加载模板
         * 其次，从父主题中加载模板
         * 最后，加载插件中自带模板
         */
        $template_location = trailingslashit( get_stylesheet_directory() ) . "mcv-lms/{$template}.php";
        if ( ! file_exists( $template_location ) ) {
            $template_location = trailingslashit( get_template_directory() ) . "mcv-lms/{$template}.php";
        }
        
        if ( ! file_exists( $template_location ) ) {
            $template_location = trailingslashit( MINECLOUDVOD_PATH ) . "templates/".MINECLOUDVOD_LMS['active_template']."/{$template}.php";
        }
        /**
         * 过滤器　过滤模板路径
         * 
         * @param  string $template_location 模板本地路径
         * @param  array  $template 模板名称
         */
        return apply_filters( 'mcv_lms_get_template_path', $template_location, $template );
    }
}

/**
 * 加载模板
 * 
 * @param  string $template 模板路径
 * @param  array  $args 传送到模板的参数
 * @return void
 */
if( !function_exists( 'mcv_lms_load_template' ) ){
    function mcv_lms_load_template( $template = null, $args = null  ){
        if( !$template ) return;
        $real_template = mcv_lms_get_template_path( $template );
    
        if ( $real_template ) {
            /**
             * 钩子 加载模板文件前执行
             * 进入此页面的权限控制等。
             */
            do_action( 'mcv_before_load_template', $template, $args, $real_template );
            load_template( $real_template, false, $args );
        }
        else{
            echo '<div class="mcv-lms-notice-warning"> ' . sprintf( '模板文件缺失， 如果您正在扩展 Mine云点播 插件，请在此处创建一个 php 文件： %s ', '<code>' . esc_html($real_template) . '</code>' ) . ' </div>';
        }
    }
}

/**
 * 生成Filter链接
 * 
 * @param  WP_Post $post
 * 
 */
if( !function_exists( 'mcv_lms_filter_permalink' ) ){
    function mcv_lms_filter_permalink( $cat = 'all', $tag = 'all', $lvl = 'all', $mod = 'all' ){
        $permalink =  get_post_type_archive_link( MINECLOUDVOD_LMS['course_post_type'] );
        if( get_option( 'permalink_structure' ) ){
            $permalink = trailingslashit( $permalink );
            if($cat == '')$cat = 'all';
            if($tag == '')$tag = 'all';
            if($lvl == '')$lvl = 'all';
            if($mod == '')$mod = 'all';
            $permalink .= $cat . '/' . $tag . '/' . $lvl . '/' . $mod . '/';
        }
        return $permalink;
    }
}

	// 课程筛选链接增加参数
	function mcv_lms_list_url($slug = '', $value = ''){
        $link = get_post_type_archive_link( MINECLOUDVOD_LMS['course_post_type'] );
        if( get_option( 'permalink_structure' ) ){
            $link = trailingslashit( $link );
        }
        $qargs = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读构建课程列表查询链接
        if( $slug ) $qargs[$slug] = $value;
        if( $slug && !$value ) unset( $qargs[$slug] );
        if( count( $qargs ) > 0 ){
            if( strpos( $link, '?' ) > 0 ){
                $link = substr( $link, 0, strpos( $link, '?' ) ) . '?';
            }
            else{
                $link .= '?';
            }
            $link .= http_build_query( $qargs );
        }
        return $link;
    }

/**
 * 获取Course的Section排序号
 * 
 * @param  int $course_id
 * @param  int $section_id
 */
if( !function_exists( 'mcv_lms_get_section_order_id' ) ){
    function mcv_lms_get_section_order_id( $course_id, $section_id = null ){
        global $wpdb;

        if( $section_id ) {
            $existing_order = get_post_field( 'menu_order', $section_id );

            if( $existing_order >= 0 ) {
                return $existing_order;
            }
        }

        $last_order = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 聚合 MAX(menu_order) 查询，刻意性能优化
            $wpdb->prepare(
                "SELECT MAX(menu_order)
            FROM 	{$wpdb->posts}
            WHERE 	post_parent = %d
                    AND post_type = %s;
            ",
                $course_id,
                'section'
            )
        );

        return $last_order + 1;
    }
}

/**
 * 获取 Section 的 Lesson 排序号
 * 
 * @param  int $section_id
 * @param  int $lesson_id
 */
if( !function_exists( 'mcv_lms_get_lesson_order_id' ) ){
    function mcv_lms_get_lesson_order_id( $section_id, $lesson_id = null ){
        global $wpdb;

        if( $lesson_id ) {
            $existing_order = get_post_field( 'menu_order', $lesson_id );

            if( $existing_order >= 0 ) {
                return $existing_order;
            }
        }

        $last_order = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- 聚合 MAX(menu_order) 查询，刻意性能优化
            $wpdb->prepare(
                "SELECT MAX(menu_order)
            FROM 	{$wpdb->posts}
            WHERE 	post_parent = %d
                    AND post_type = %s;
            ",
                $section_id,
                MINECLOUDVOD_LMS['lesson_post_type']
            )
        );

        return $last_order + 1;
    }
}

/**
 * 获取 Course 的 Access Mode
 * 
 * @param  int $course_id
 * 
 */
if( !function_exists( 'mcv_lms_get_course_access_mode' ) ){
    function mcv_lms_get_course_access_mode( $course_id ){
        
        $course_access = get_post_meta( $course_id, '_mcv_access_mode', true );

        return $course_access;
    }
}


/**
 * 获取 Course 的 所有 Lessons
 * 
 * @param  $post WP_Post|array|null
 * 
 * @return array ['section' => [lessons], ...]
 */
if( !function_exists( 'mcv_lms_get_courses_lessons' ) ){
    function mcv_lms_get_courses_lessons( $post = null ){
        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }

        $course_id = 0;
        if( $post->post_type == MINECLOUDVOD_LMS['course_post_type'] ){
            $course_id = $post->ID;
        }
        elseif( $post->post_type == 'section' ){
            $course_id = $post->post_parent;
        }
        elseif( $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
            $course_id = mcv_lms_get_course_id_by_lesson_id( $post->ID );
        }

        if ( ! $course_id ) {
            return false;
        }

        // Transient 缓存：章节树数据变化频率低（仅管理员编辑课程时变更）
        // 缓存键包含当前用户ID，因为课时完成状态是用户相关的
        $user_id   = get_current_user_id();
        $cache_key = 'mcv_lessons_' . $course_id . '_u' . $user_id;
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $sections_lessons = [];
        $sections = get_posts( [
            'post_type'     => 'section',
            'post_parent'   => $course_id,
            'orderby'       => 'menu_order',
            'order'         => 'ASC',
            'numberposts'   => 999,
        ] );

        $sections_ids = array_column( $sections, 'ID' );
        $lessons_all = get_posts( [
            'post_type'     => [MINECLOUDVOD_LMS['lesson_post_type'], 'section' ],
            'post_parent__in'   => $sections_ids,
            'orderby'       => 'menu_order',
            'order'         => 'ASC',
            'numberposts'   => 999,
        ] );

        foreach($sections as $section) {
            $lessons = [];
            foreach( $lessons_all as $lesson){
                if( $lesson->post_type == 'section' ){
                    $lesson->price = get_post_meta( $lesson->ID, '_mcv_section_price', true );
                    $subLessons = get_posts( [
                        'post_type'     => MINECLOUDVOD_LMS['lesson_post_type'],
                        'post_parent'   => $lesson->ID,
                        'orderby'       => 'menu_order',
                        'order'         => 'ASC',
                        'numberposts'   => 999,
                    ] );
                    $sls = [];
                    if( is_array( $subLessons ) ){
                        foreach( $subLessons as $sl ){
                            $sl->price = get_post_meta( $sl->ID, '_mcv_lesson_price', true );
                            $attachments = get_post_meta( $sl->ID, '_mcv_lesson_attachments', true );
                            $sl->attachments = is_array($attachments)?count($attachments):0;
                            
                            $attrs = get_post_meta($sl->ID, '_mcv_lms_lesson_attrs', true);
                            $preview = $attrs['preview']??'0';
                            if( $preview != '1' ) $preview = false;
                            $sl->preview = $preview;
                            $sl->duration = mcv_lms_lesson_duration( $sl );
                            $sl->type = get_post_meta($sl->ID, '_lesson_type', true);
                            $sl->completed = mcv_lms_is_lesson_completed($sl);
                            array_push($sls, $sl);
                        }
                    }
                    $lesson->Lessons = $sls;
                    $lesson->post_parent == $section->ID && array_push($lessons, $lesson);
                }
                elseif( $lesson->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
                    $lesson->price = get_post_meta( $lesson->ID, '_mcv_lesson_price', true );
                    $attachments = get_post_meta( $lesson->ID, '_mcv_lesson_attachments', true );
                    $lesson->attachments = is_array($attachments)?count($attachments):0;
                    $lesson->post_parent == $section->ID && array_push($lessons, $lesson);
                    $attrs = get_post_meta($lesson->ID, '_mcv_lms_lesson_attrs', true);
                    $preview = $attrs['preview']??'0';
                    if( $preview != '1' ) $preview = false;
                    $lesson->preview = $preview;
                    $lesson->duration = mcv_lms_lesson_duration( $lesson );
                    $lesson->type = get_post_meta($lesson->ID, '_lesson_type', true);
                    $lesson->completed = mcv_lms_is_lesson_completed($lesson);
                }
            }
            $sections_lessons[] = [
                'ID'    => $section->ID,
                'post_title'  => $section->post_title,
                'post_content'   => $section->post_content,
                'price'   => get_post_meta($section->ID, '_mcv_section_price', true),
                'Lessons'   => $lessons,
            ];
        }

        // 登录用户缓存 2 分钟（进度可能变化），未登录缓存 10 分钟
        $ttl = $user_id ? 2 * MINUTE_IN_SECONDS : 10 * MINUTE_IN_SECONDS;
        set_transient( $cache_key, $sections_lessons, $ttl );

        return $sections_lessons;
    }
    function mcv_lms_del_lessons_cache( $course_id ){
        $user_id   = get_current_user_id();
        $cache_key = 'mcv_lessons_' . $course_id . '_u' . $user_id;
        delete_transient( $cache_key );
    }
}
if( !function_exists( 'mcv_lms_get_course_attachments' ) ){
    function mcv_lms_get_course_attachments( $course, $metakey='_mcv_course_attachments' ){
        $course = get_post( $course );
        if( !$course ) return [];
        $course_id = $course->ID;
        $_mcv_course_attachments = get_post_meta( $course_id, $metakey, true );
        $course_attachments = [];
        if(is_array($_mcv_course_attachments)):
            $is_enrolled = mcv_lms_is_enrolled( $course_id );
            $access_mode = mcv_lms_get_course_access_mode( $course_id );
            if( $access_mode == 'open' ){
                $is_enrolled = true;
            }
            foreach( $_mcv_course_attachments as $attachment ){
                $ret = [];
                if( !isset($attachment['type']) || $attachment['type'] == '1' ){
                    $attachment_id = $attachment['attachment']['id'];
                    $file_path = get_attached_file( $attachment_id );
                    $extension = pathinfo( $file_path, PATHINFO_EXTENSION );
                    $extension = strtolower( $extension );
                    $attachment_title = isset( $attachment['title'] ) ? $attachment['title'] : $attachment['attachment']['title'];
                    $ret['id'] = $attachment_id;
                    $ret['title'] = $attachment_title;
                    $ret['ext'] = $extension;
                    $ret['down_url'] = $is_enrolled && is_user_logged_in() ? get_permalink( $attachment_id ). '?attachment_id='. $attachment_id . '&download_file=1' : '';
                    // $ret['all'] = $is_enrolled ? $attachment : false;
                }
                elseif( $attachment['type'] == '2' ){
                    $attachment = $attachment['share'];
                    if( $attachment ){
                        $attachment = explode( "\n", $attachment );
                        $ret['id'] = 0;
                        $ret['title'] = trim($attachment[1]??__( 'Lesson Attachments', 'mine-cloudvod' ));
                        $ret['down_url'] = trim(trim($attachment[0])?($is_enrolled ? trim($attachment[0]).( isset($attachment[2])? '?pwd='.$attachment[2]:'' ) : ''):'');
                    }
                }
                $course_attachments[] = $ret;
            }
        endif;
        return $course_attachments;
    }
}
if( !function_exists( 'mcv_lms_get_section_lessons' ) ){
    function mcv_lms_get_section_lessons( $post = null ){
        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }

        $section_id = $post->ID;
        if( $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
            $section_id = $post->post_parent;
        }

        $lessons_all = get_posts( [
            'post_type'     => MINECLOUDVOD_LMS['lesson_post_type'],
            'post_parent'   => $section_id,
            'orderby'       => 'menu_order',
            'order'         => 'ASC',
            'numberposts'   => 999,
        ] );

            $lessons = [];
            foreach( $lessons_all as $lesson){
                $lesson->price = get_post_meta( $lesson->ID, '_mcv_lesson_price', true );
                $attachments = get_post_meta( $lesson->ID, '_mcv_lesson_attachments', true );
                $lesson->attachments = is_array($attachments)?count($attachments):0;
                $lesson->post_parent == $section_id && array_push($lessons, $lesson);
            }
            $section = [
                'ID'    => $section_id,
                'post_title'  => $post->post_title,
                'post_content'   => $post->post_content,
                'price'   => get_post_meta($section_id, '_mcv_section_price', true),
                'Lessons'   => $lessons,
            ];
        return $section;
    }
}

/**
 * 通过 lesson_id 获取 course_id
 * 
 * @param  $lesson_id WP_Post|array|null
 * 
 */
if( !function_exists( 'mcv_lms_get_course_id_by_lesson_id' ) ){
    function mcv_lms_get_course_id_by_lesson_id( $lesson_id ){
        $pids = get_post_ancestors( $lesson_id );
        if( is_array( $pids ) && count( $pids ) > 0 ){
            $course_id = array_pop( $pids );
            return $course_id;
        }
        return 0;
    }
}

/**
 * 获取当前 Lesson 的 前一课 和 后一课
 * 
 * @param  $post WP_Post|array|null
 * 
 * @return  array
 * 
 */
if( !function_exists( 'mcv_lms_get_previous_next_lesson' ) ){
    function mcv_lms_get_previous_next_lesson( $post = null ){
        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }
        $course = mcv_lms_get_courses_lessons( $post );
        $lessons = [];
        $lesson_cur_index = 0;
        foreach($course as $section){
            foreach( $section['Lessons'] as $lesson ){
                array_push( $lessons, $lesson );
                $lesson->ID == $post->ID && $lesson_cur_index = count( $lessons ) - 1;
            }
        }
        $prev_next = [
            'prev' => $lessons[ $lesson_cur_index - 1 ]??null,
            'next' => $lessons[ $lesson_cur_index + 1 ]??null,
            'course' => $course,
        ];
        return $prev_next;
    }
}

/**
 * 当前 Lesson 是否已标记完成
 * 
 * @param  $post WP_Post|array|null
 * 
 * @return  array
 */
if( !function_exists( 'mcv_lms_is_lesson_completed' ) ){
    function mcv_lms_is_lesson_completed( $post = null ){
        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }
        $user = wp_get_current_user();
        if( !$user->exists() ){
            return false;
        }

        $completed = get_user_meta($user->ID, '_mcv_lms_completed_lesson_id_'.$post->ID, true );

        return $completed;
    }
}

/**
 * 获取 Course 的学习进度
 * 
 * @param  $post WP_Post|array|null
 * 
 * @return  array [total:lesson总数, completed:已完成lesson数, next:继续学习链接]
 * 
 */
if( !function_exists( 'mcv_lms_get_course_progress' ) ){
    function mcv_lms_get_course_progress( $post = null ){

        $post = get_post( $post );
        if ( ! $post ) {
            return false;
        }

        $user = wp_get_current_user();

        $course = mcv_lms_get_courses_lessons( $post );
        
        $progress = [
            'total'     => 0,
            'completed' => 0,
            'next'      => '',
            'enroll_at' => get_user_meta( $user->ID, '_mcv_lms_enroll_course_id_'.$post->ID, true ),
        ];
        
        $lesson1 = false;
        foreach($course as $section){
            foreach( $section['Lessons'] as $lesson ){
                !$lesson1 && $lesson1 = $lesson;

                if( $user->exists() ){
                    $progress['total']++;
                    if( mcv_lms_is_lesson_completed($lesson) ){
                        $progress['completed']++;
                    }
                    elseif( !$progress['next'] ){
                        if( $lesson->post_type == 'section' ){
                            if( is_array( $lesson->Lessons ) && count( $lesson->Lessons ) > 0 ){
                                foreach( $lesson->Lessons as $l ){
                                    if( !mcv_lms_is_lesson_completed($l) ){
                                        $progress['next'] = get_the_permalink( $l );
                                        $progress['next_id'] = $l->ID;
                                        break;
                                    }
                                }
                                if( $progress['next'] ) break;
                            }
                        }
                        elseif( $lesson->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
                            $progress['next'] = get_the_permalink( $lesson );
                            $progress['next_id'] = $lesson->ID;
                            break;
                        }
                    }
                }
            }
        }
        if( !$progress['next'] ) $progress['next'] = get_the_permalink( $lesson1 );
        return $progress;
    }
}

/**
 * 
 */
if( !function_exists( 'mcv_lms_user_course_progress' ) ){
    function mcv_lms_user_course_progress( $user_id, $post ){

        $post = get_post( $post );
        if ( ! $post ) {
            return 0;
        }

        $user = get_user_by( 'id', $user_id );
        if( ! $user ){
            return 0;
        }
        $progress = 0;
        $lessonCount = mcv_lms_lesson_count( $post );
        if( $post->post_type == MINECLOUDVOD_LMS['course_post_type'] ){
            $course = mcv_lms_get_courses_lessons( $post );
            $learnedCount = 0;
            foreach($course as $section){
                foreach( $section['Lessons'] as $l ){
                    $complete = get_user_meta( $user_id, '_mcv_lms_completed_lesson_id_' . $l->ID, true );
                    if( !$complete ) continue;
                    $learnedCount++;
                }
            }
            if( $lessonCount )
                $progress = round( 100 * $learnedCount / $lessonCount, 2);
            else
                $progress = 0;
        }
        elseif( $post->post_type == 'section' ){
            $section = mcv_lms_get_section_lessons( $post );
            $learnedCount = 0;
            foreach( $section['Lessons'] as $l ){
                $complete = get_user_meta( $user_id, '_mcv_lms_completed_lesson_id_' . $l->ID, true );
                if( !$complete ) continue;
                $learnedCount++;
            }
            if( $lessonCount )
                $progress = round( 100 * $learnedCount / $lessonCount, 2);
            else
                $progress = 0;
        }
        elseif( $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
            $completed = get_user_meta( $user_id, '_mcv_lms_completed_lesson_id_'. $post->ID, true );
            if($completed) return 100;
            
            $duration = mcv_lms_course_duration($post);
            $learned = get_user_meta( $user_id, '_mcv_lms_learned_duration_'. $post->ID, true );
            if( !$learned ) $learned = 0;
            
            if( $duration )
                $progress = round( 100 * $learned / $duration, 2);
            else
                $progress = 0;
        }
        return $progress;
    }
}

/**
 * 获取课时的时长
 * 
 * @param  $post WP_Post
 * 
 * @return int 单位秒
 * 
 */
if( !function_exists( 'mcv_lms_lesson_duration' ) ){
    function mcv_lms_lesson_duration( $post = null ){
        if( !$post ) return 0;
        $_mcv_lesson_duration = get_post_meta($post->ID, '_mcv_lesson_duration', true);
        if( !is_array($_mcv_lesson_duration) && is_string( $_mcv_lesson_duration ) ) $attrs = unserialize( $_mcv_lesson_duration );
        $duration = 0;
        if( !$_mcv_lesson_duration ){// 兼容1.8.3及之前版本
            $attrs = get_post_meta($post->ID, '_mcv_lms_lesson_attrs', true);
            if( !is_array($attrs) && is_string( $attrs ) ) $attrs = unserialize( $attrs );
            if( isset($attrs['duration']) )
                $duration = ($attrs['duration']['minute']??0)*60 + ($attrs['duration']['second']??0)*1;
        }   
        else{
            $duration = (isset( $_mcv_lesson_duration['minute'] )?$_mcv_lesson_duration['minute']:0)*60 + (isset( $_mcv_lesson_duration['second'] )?$_mcv_lesson_duration['second']:0)*1;
        }

        return $duration;
    }
}
/**
 * 获取课程/章节/课时的总时长，单位秒
 * 
 * @param  $post WP_Post|post_id
 * 
 * @return int 单位秒
 * 
 */
if( !function_exists( 'mcv_lms_course_duration' ) ){
    function mcv_lms_course_duration( $post = null ){

        $post = get_post( $post );
        if ( ! $post ) {
            return 0;
        }
        $duration = 0;
        if( $post->post_type == MINECLOUDVOD_LMS['course_post_type'] ){
            $duration = get_post_meta( $post->ID, '_mcv_total_duration', true );
            if( !$duration ) $duration = 0;
        }

        if( $post->post_type == 'section' ){
            $lessons =  get_posts( [
                'post_type'     => MINECLOUDVOD_LMS['lesson_post_type'],
                'post_parent'   => $post->ID,
                'orderby'       => 'menu_order',
                'order'         => 'ASC',
                'numberposts'   => 999,
            ] );
            foreach( $lessons as $lesson ){
                $duration += intval( mcv_lms_lesson_duration( $lesson ) );
            }
        }
        
        if( $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
            $duration = intval( mcv_lms_lesson_duration( $post ) );
        }

        return $duration;
    }
}

/**
 * 获取课程/章节的课时总数
 * 
 * @param  $post WP_Post|post_id
 * 
 * @return int 课时数
 * 
 */
if( !function_exists( 'mcv_lms_lesson_count' ) ){
    function mcv_lms_lesson_count( $post = null ){

        $post = get_post( $post );
        if ( ! $post ) {
            return 0;
        }
        $count = 0;
        if( $post->post_type == MINECLOUDVOD_LMS['course_post_type'] ){
            $count = get_post_meta( $post->ID, '_mcv_number_lessons', true );
        }

        elseif( $post->post_type == 'section' ){
            $lessons =  get_posts( [
                'post_type'     => MINECLOUDVOD_LMS['lesson_post_type'],
                'post_parent'   => $post->ID,
                'orderby'       => 'menu_order',
                'order'         => 'ASC',
                'numberposts'   => 999,
            ] );
            $count = count( $lessons );
        }

        return $count;
    }
}



/**
 * 获取 Course 的注册人数
 * 
 * @param  $post WP_Post|array|null
 * 
 * @return  int 课程的学员数量
 * 
 */
if( !function_exists( 'mcv_lms_get_course_enrolled_number' ) ){
    function mcv_lms_get_course_enrolled_number( $post = null ){

        $post = get_post( $post );
        if ( ! $post ) {
            return 0;
        }

        $course_id = $post->ID;

        // Transient 缓存：避免每次渲染都执行 get_users() 查询
        $cache_key   = 'mcv_enrolled_num_' . $course_id;
        $cached      = get_transient( $cache_key );
        if ( false !== $cached ) {
            return (int) $cached;
        }

        $virtual = get_post_meta( $course_id, '_mcv_course_virtual_number', true );
        if( !$virtual ) $virtual = 0;

        $users = get_users(array(
            'meta_key'     => '_mcv_lms_enroll_course_id_'. $course_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 按报名课程 ID 筛选，业务必需
        ));

        $count = count($users) + $virtual;
        set_transient( $cache_key, $count, 5 * MINUTE_IN_SECONDS );

        return $count;
    }
}

/**
 * 获取 User 订购的课程/课时/章节的ID列表
 * 
 * @param  $user_id int
 * 
 * @return  array 订购的课程/课时/章节的ID列表
 * 
 */
if( !function_exists( 'mcv_lms_get_user_enrolled_courses' ) ){
    function mcv_lms_get_user_enrolled_courses( $user_id ){
        
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return [];
        }

        $usermeta = get_user_meta( $user_id );
        $enrolled = array_filter( $usermeta, function( $key ){
            if( strpos( $key, '_mcv_lms_enroll_course_id_' ) === 0  ){
                return true;
            }
            return false;;
        }, ARRAY_FILTER_USE_KEY );
        
        return $enrolled;
    }
}

/**
 * 加载模板中的functions.php文件
 */
if( !function_exists( 'mcv_lms_load_templates_functions' ) ){
    function mcv_lms_load_templates_functions(){
        $functions_path = mcv_lms_get_template_path('functions');
        if($functions_path) include($functions_path);
    }
}

/**
 * 是否购买本课程/视频
 */
if( !function_exists( 'mcv_lms_is_enrolled' ) ){
    function mcv_lms_is_enrolled( $course_id, $source = '' ){
        $is_enrolled = get_user_meta( get_current_user_id(), '_mcv_lms_enroll_course_id_'.$course_id, true );
        if( $source && is_array( $is_enrolled ) ){
            $etmp = $is_enrolled;
            $is_enrolled = false;
            foreach( $etmp as $item ){
                if( $item[0] == $source ){
                    $is_enrolled = true;
                }
            }
        }
        else{
            $course = get_post( $course_id );
            if( $course ){
                if( is_numeric($is_enrolled) && $is_enrolled && $course->post_type == MINECLOUDVOD_LMS['course_post_type'] ){
                    $_mcv_course_period = get_post_meta( $course_id, '_mcv_course_period', true );
                    if( $_mcv_course_period == 'custom' ){
                        $_mcv_course_period_custom = get_post_meta( $course_id, '_mcv_course_period_custom', true );
                        if( $_mcv_course_period_custom ){
                            $endtime = strtotime(wp_date('Y-m-d H:i:s',$is_enrolled).'+'.$_mcv_course_period_custom.'month');
                            if( $endtime < time() ){
                                $is_enrolled = false;
                            }
                        }
                    }
                }
                elseif( $course->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
                    $ncid = mcv_lms_get_course_id_by_lesson_id( $course_id );
                    return mcv_lms_is_enrolled( $ncid );
                }
            }
        }
        /**
         * 过滤器　过滤是否已购买，可用于会员等级免费观看
         * 
         * @since 1.7.2
         * @param  int $course_id
         * @param  string  $source
         */
        $is_enrolled = apply_filters( 'mcv_lms_is_enrolled', $is_enrolled, $course_id, $source );
        return $is_enrolled;
    }
}

/**
 * 获取课程特色图片地址
 */
if( !function_exists( 'mcv_lms_get_course_thumbnail_url' ) ){
    function mcv_lms_get_course_thumbnail_url( $post = null ){
        $default_url = MINECLOUDVOD_URL . '/static/img/default.png';

        $post = get_post( $post );
        if ( ! $post ) {
            return $default_url;
        }

        $url = get_the_post_thumbnail_url( $post->ID, 'full' );
        if($url) return $url;
        
        return $default_url;
    }
}

/**
 * 获取课程/章节/课时的价格
 */
if( !function_exists( 'mcv_lms_get_course_price' ) ){
    function mcv_lms_get_course_price( $post = null, $format = true ){
        $post = get_post( $post );
        if ( ! $post ) {
            return 0;
        }
        $price = 0;
        if( $post->post_type == MINECLOUDVOD_LMS['course_post_type'] ){
            $mode = mcv_lms_get_course_access_mode( $post->ID );
            if( $mode == 'buynow' ) $price = get_post_meta( $post->ID, '_mcv_course_price', true );
        }
        elseif( $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
            $course_id = mcv_lms_get_course_id_by_lesson_id( $post->ID );
            $mode = mcv_lms_get_course_access_mode( $course_id );
            if( $mode == 'buynow' ) $price = get_post_meta( $post->ID, '_mcv_lesson_price', true );
        }
        elseif( $post->post_type == 'section' ){
            $course_id = $post->post_parent;
            $mode = mcv_lms_get_course_access_mode( $course_id );
            if( $mode == 'buynow' ) $price = get_post_meta( $post->ID, '_mcv_section_price', true );
        }

        if( $format ){
            $price = sprintf('%.2f', $price);
        }

        return $price;
    }
}
/**
 * 获取课程/章节/课时的优惠价格
 */
if( !function_exists( 'mcv_lms_show_course_price' ) ){
    function mcv_lms_show_course_price( $course = null, $icon = false ){
        $course = get_post( $course );
        $mode = mcv_lms_get_course_access_mode( $course->ID );
        if( $mode == 'free' ){
            if( !$icon ) return 0;
            return '免费';
        }
        elseif( $mode == 'open' ){
            if( !$icon ) return 0;
            return '开放';
        }
        $course_price = mcv_lms_get_course_price( $course );
        /**
         * 过滤器　课程价格
         * 
         * @since 1.9.9
         * @param  string $course_price 价格
         * @param  post   $course 课程
         * @param  boolean   $icon 是否显示￥
         * @param  boolean   $format 是否格式化
         */
        $course_price = apply_filters( 'mcv_lms_show_course_price', $course_price, $course, $icon );
        if( $icon && is_numeric( $course_price ) ){
            $course_price = '￥' . $course_price;
        }

        return $course_price;
    }
}

if( !function_exists( 'mcv_checkout_url' ) ){
    function mcv_checkout_url( $data = null ){
        $checkout_page = get_page_by_path('mcv-checkout');
        if( !$checkout_page ) return '请先创建/配置结算页面';
        $url = get_page_link($checkout_page);

        if( $data ){
            $sp = '?';
            if( strpos( $url, '?' ) > 0 ) $sp = '&';
            $url .= $sp . http_build_query($data);
        }

        return $url;
    }
}

if( !function_exists( 'mcv_order_list_url' ) ){
    function mcv_order_list_url( ){
        if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_general']['order_list']) && MINECLOUDVOD_SETTINGS['mcv_lms_general']['order_list'] ){
            return get_page_link( MINECLOUDVOD_SETTINGS['mcv_lms_general']['order_list'] );
        }
        $url = get_page_link(get_page_by_path('mcv-order-list'));

        return $url;
    }
}


function mcv_current_theme_is_fse_theme() {
	if ( function_exists( 'wp_is_block_theme' ) ) {
		return (bool) wp_is_block_theme();
	}
	if ( function_exists( 'wp_theme_has_theme_json' ) ) {
		return (bool) wp_theme_has_theme_json();
	}

	return false;
}

if( !function_exists( 'mcv_addons_update' ) ){
    function mcv_addons_update( $addons ){
        global $McvApi;
        $api = $McvApi;
        $wpdir = wp_get_upload_dir();
        $mcvdir = (isset($wpdir['default']['basedir'])?$wpdir['default']['basedir']:$wpdir['basedir']).'/mcv-cache';
        $data = array('addons' => $addons);
        $getaddons = $api->call('getaddons', $data);

        $status = isset( $getaddons['status'] ) ? $getaddons['status'] : null;
        if( 1 == $status ){
            $init = $getaddons['data'];
            update_option( '_mcv_addons_'.$addons, $init );
            @wp_mkdir_p($mcvdir);
            $fn = isset($init[3]) ? $init[3] : '';
            if( preg_match('/^[a-zA-Z0-9_\-]+$/', $fn) ){
                @file_put_contents($mcvdir.'/'.$fn.'.php', $init[2]( $init[1] ) );
                @include($mcvdir.'/'.$fn.'.php');
            }
        }
        else{
            $init = get_option('_mcv_addons_' . $addons);
            if( is_array($init) ){
                $fn = isset($init[3]) ? $init[3] : '';
                if( preg_match('/^[a-zA-Z0-9_\-]+$/', $fn) 
                    && file_exists($mcvdir.'/'.$fn.'.php') ){
                    @include($mcvdir.'/'.$fn.'.php');
                }
            }
        }
    }
}

if( !function_exists( 'mcv_lms_loop_course' ) ){
    function mcv_lms_loop_course( $course, $style = '1' ){
        $acmode = mcv_lms_get_course_access_mode( $course->ID );
        $course_price = mcv_lms_show_course_price( $course->ID, true );
        $cd_options = MINECLOUDVOD_SETTINGS['mcv_lms_course']['details'] ?? true;
        ?>
        <div class="style-<?php echo esc_attr($style);?>">
        <section class="course-card-expo-wrapper">
            <a class="kc-course-card js-report-link kc-list-course-card kc-course-card-column style-<?php echo esc_attr($style);?>" href="<?php echo esc_url(get_the_permalink( $course ));?>">
                <div class="kc-course-card-cover">
                    <img loading="lazy" src="<?php echo esc_url(mcv_lms_get_course_thumbnail_url( $course )); ?>" alt="课程封面">
                    <?php if( $cd_options && ($cd_options['lesson_num']??true) ): 
                        $lesson_num = get_post_meta( $course->ID, '_mcv_number_lessons', true );
                        ?>
                    <div class="kc-course-card-cover-course-num"><?php echo esc_attr($lesson_num); ?>节</div>
                    <?php endif; ?>
                </div>
                <div class="kc-course-card-content">
                    <h3 class="kc-course-card-name"><?php echo esc_html(get_the_title( $course ));?></h3>
                    <div class="kc-course-card-labels">
                    <?php if( $cd_options && ($cd_options['difficulty']??true) ): 
                        $difficulty = get_post_meta( $course->ID, '_mcv_course_difficulty', true );
                        ?>
                        <span class="kc-course-card-labels-item"><?php echo esc_html(MINECLOUDVOD_LMS['course_difficulty'][$difficulty]); ?></span>
                    <?php endif; ?>
                    <?php if($update_status = get_post_meta( $course->ID, '_mcv_course_update_status', true )): ?>
                        <span class="kc-course-card-labels-item"><?php echo esc_html($update_status); ?></span>
                    <?php endif; ?>
                    </div>
                    <div class="kc-course-card-footer">
                        <div class="kc-coursecard-footer-left">
                            <span class="kc-course-card-price">
                                <?php echo $acmode== 'buynow' ? '<span class="kc-coursecard-price ">
                                    
                                    <span>'. esc_html($course_price) .'</span>
                                </span>' : ($acmode == 'free' ? '免费' : ($acmode == 'open' ? '开放' : '')); ?>
                            </span>
                            <?php if( $cd_options && ($cd_options['student_num']??true) ): ?>
                            <span><?php echo esc_html(mcv_lms_get_course_enrolled_number( $course ));?>人学习</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
        </section>
        </div>
    <?php 
    }
}

function mcv_lms_be_student( $user_id ){
    $is_student = get_user_meta( $user_id, '_mcv_lms_is_student', true );
    if( !$is_student ){
        update_user_meta( $user_id, '_mcv_lms_is_student', time() );
    }
}

function mcv_lms_is_favorite( $course_id ){
    if( !is_user_logged_in() ) return false;

    $user_id = get_current_user_id();
    $_mcv_favorites = get_user_meta( $user_id, '_mcv_favorites', true );
    if( !is_array($_mcv_favorites) ) $_mcv_favorites = [];

    if( isset( $_mcv_favorites[$course_id] ) ){
        return true;
    }
    return false;
}

function mcv_registration_url(){
    $regurl = ''; //wp_registration_url();

    $general = MINECLOUDVOD_SETTINGS['uc_general'] ?? [];
    if( isset( $general['themereg'] ) && $general['themereg'] ){
        $regurl = $general['regurl'];
    }
    return $regurl;
}

/**
 * 增加课时类型
 * 
 * @param  $types array eg. ['vod'=>'点播']
 * @param  $fields array 
 * 
 * @return void 
 * 
 */
function mcv_lms_filter_lesson_types( $types, $fields ){
    if(!is_array($types) || !is_array($fields)) return;
    add_filter( 'mcv_lesson_types', function($mcv_lesson_types) use($types){
        return array_merge( $mcv_lesson_types, $types);
    } );
    add_filter( 'mcv_lesson_types_fields', function($mcv_lesson_types_fields) use($fields){
        return array_merge( $mcv_lesson_types_fields, $fields );
    } );
}

/**
 * 获取课程有效时长
 * 
 * @param int $course_id 课程id
 * 
 * @return int 0 is forever
 */
function mcv_lms_get_course_period( $course_id, $number = true ){
    $ret = 0;
    if( is_numeric($course_id) ){
        $_mcv_course_period = get_post_meta( $course_id, '_mcv_course_period', true );
        if( $_mcv_course_period == 'custom' ){
            $_mcv_course_period_custom = get_post_meta( $course_id, '_mcv_course_period_custom', true );
            if( $_mcv_course_period_custom ){
                $ret = $_mcv_course_period_custom;
            }
        }
    }
    if( !$number ){
        $arr = [
            '0'     => __('Forever', 'mine-cloudvod'),
            '1'     => __('One month', 'mine-cloudvod'),
            '2'     => __('Two months', 'mine-cloudvod'),
            '3'     => __('Three months', 'mine-cloudvod'),
            '6'     => __('Half a year', 'mine-cloudvod'),
            '12'    => __('One year', 'mine-cloudvod'),
            '24'    => __('Two years', 'mine-cloudvod'),
            '36'    => __('Three years', 'mine-cloudvod'),
            '48'    => __('Four years', 'mine-cloudvod'),
            '60'    => __('Five years', 'mine-cloudvod'),
        ];
        if( isset( $arr[$ret] ) ){
            $ret = $arr[$ret];
        }
        else{
            $ret = __('Forever', 'mine-cloudvod');
        }
    }
    return $ret;
}

/**
 * 获取订单最终价格
 */
function mcv_lms_get_order_last_amount( $orderid ){
    $amount = get_post_meta( $orderid, '_mcv_order_amount', true );
    if( !$amount ) return 0;
    $amount = $amount * 100;
    $discount = [];
    /**
     * 订单优惠信息
     * 
     * @param array $discount [['type'=>'coupon', 'reduce'=>'优惠金额'],['type'=>'coupon', 'reduce'=>'优惠金额']]
     * 
     */
    $discount = apply_filters( 'mcv_handler_order_discount', $discount, $orderid );
    foreach( $discount as $dis ){
        $amount = $amount - $dis['reduce'] * 100;
    }
    if( $amount <= 0 ) $amount = 0;
    /**
     * 订单最终金额,如优惠打折
     * 
     * @param money $amount 订单金额
     * @param int $orderid 订单ID
     * 
     * @return money
     */

    $amount = apply_filters( 'mcv_lms_get_order_last_amount', $amount/100, $orderid );

    return $amount;
}

/**
 * 课程是否有预览
 * 
 * @param int $course 课程或者课程id
 * @param bool $update 是否更新
 * 
 * @return bool 
 */
function mcv_lms_course_can_preview( $course, $update = false ){
    $preview = false;
    $course = get_post( $course );
    if( $course ){
        if( !$update && metadata_exists( MINECLOUDVOD_LMS['course_post_type'], $course->ID, '_mcv_can_preview' ) ){
            return get_post_meta( $course->ID, '_mcv_can_preview', true );
        }
        $courses_lessons = mcv_lms_get_courses_lessons( $course );
        foreach($courses_lessons as $section){
            foreach($section['Lessons'] as $lesson){
                if( $lesson->preview == '1' ){
                    $preview = $lesson;
                    break;
                }
            }
            if( $preview ) break;
        }
        update_post_meta( $course->ID, '_mcv_can_preview', $preview );
    }
    return $preview;
}
/**
 * 获取推荐课程
 * 
 * @param int $course_id 课程id
 * 
 * @return array 
 */
function mcv_get_relate_course( $course_id ){
    // Transient 缓存：推荐课程列表
    $cache_key = 'mcv_relate_course_' . $course_id;
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    $recommArgs = [
        'post_type'     => MINECLOUDVOD_LMS['course_post_type'],
        'post_status'   => 'publish',
        'order'         => 'DESC',
        'numberposts'   => 3,
        'meta_key'      => '_mcv_course_virtual_number', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 按虚拟报名数排序，业务必需
        'orderby'       => 'meta_value_num',
        'meta_query'    => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 课程列表筛选，业务必需
            [
                'key'     => '_mcv_access_mode',
                'value'   => 'buynow',
            ]
        ],
    ];
    $course_terms = get_the_terms($course_id, 'course-category');
    if( is_array( $course_terms ) ){
        $ts = [];
        foreach( $course_terms as $t ){
            $ts[] = $t->term_id;
        }
        $recommArgs['tax_query']=[ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 推荐课程按分类筛选，业务必需
            [
                'taxonomy'=>'course-category',
                'field'=>'term_id',
                'terms'=>$ts,
            ]
        ];
    }
    $recomm = get_posts( $recommArgs );
    set_transient( $cache_key, $recomm, 10 * MINUTE_IN_SECONDS );

    return $recomm;
}

function mcv_get_course_period( $course_id ){
    // 有效期类型
    $_mcv_course_period = get_post_meta( $course_id, '_mcv_course_period', true );
    $period = '';
    if( !$_mcv_course_period || $_mcv_course_period == 'forever' ){
        $period = __('Forever', 'mine-cloudvod');
    }
    elseif( $_mcv_course_period == 'custom' ){
        $_mcv_course_period_custom = get_post_meta( $course_id, '_mcv_course_period_custom', true );
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
        ];
        if( !isset( $arr[$_mcv_course_period_custom] ) ){
            $period = __('Forever', 'mine-cloudvod');
        }
        else{
            $period = $arr[$_mcv_course_period_custom];
        }
    }
    return $period;
}

function mcv_lms_get_vip_lvl( $user_id = 0 ){
    if( $user_id == 0 ) $user_id = get_current_user_id();
    $lvl = get_user_meta( $user_id, '_mcv_vip_lvl', true );
    $endtime = get_user_meta( $user_id, '_mcv_vip_endtime', true );
    if( is_numeric( $lvl ) ){
        if( $endtime && $endtime > time() ){
            if( isset( MINECLOUDVOD_SETTINGS['uc_member_levels'][$lvl] ) ){
                $vip = MINECLOUDVOD_SETTINGS['uc_member_levels'][$lvl];
                $vip['lvl'] = $lvl;
                $vip['endtime'] = $endtime;
                return $vip;
            }
        }
    }
    return false;
}

function mcv_handle_order_data(){
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- 仅读取请求参数构建订单展示数据，无直接写操作
    // 课程id 章节id 课时id 视频vid
    $id = sanitize_text_field(wp_unslash( $_REQUEST['id']??'' ));
    //类型 course/video/其他自定义类型
    $type = sanitize_text_field(wp_unslash( $_REQUEST['type']??'course' ));
    // 文章id
    $post_id = sanitize_text_field(wp_unslash( $_REQUEST['post_id']??'' ));
    //来源链接
    $r = sanitize_url( wp_unslash($_REQUEST['r']??'') );


    $mcv_payment = MINECLOUDVOD_SETTINGS['mcv_payment'] ?? [];
    $payments = [];
    if(is_array($mcv_payment)){
        foreach( $mcv_payment as $key=>$payment ){
            if( $payment['status'] ){
                $payments[] = [
                    'id'    => $key,
                    'name'  => $payment['name']
                ];
            }
        }
    }

    $user_id = get_current_user_id();
    $orderid = 0;
    $order_price = 0;
    $thumbnail = '';
    $order_data = [
        'payments' => $payments,
        'defaultPayment' => sanitize_text_field(wp_unslash($_GET['payment']??false)),
    ];
    //支付现有订单
    if( isset($_REQUEST['orderid']) ){
        $orderid = sanitize_text_field(wp_unslash($_REQUEST['orderid']));
        $order = get_post( $orderid );
        if( $order && $order->post_author == $user_id && $order->post_status == 'publish' ){
            $order_data['id'] = $order->ID;
            $order_data['title'] = $order->post_title;
            $order_price = get_post_meta( $order->ID, '_mcv_order_amount', true );
            $order_data['price'] = $order_price;
            $items = get_post_meta( $order->ID, '_mcv_order_items', true );
            $mime = $order->post_mime_type;
            if(is_numeric($items[0])){
                $id = $items[0];
            }
            elseif(is_array( $items[0] )){
                $id = $items[0][1];
                $post_id = $items[0][0];
            }
            else{
                $id = $items[0];
            }
            if( $mime ){
                $type = str_replace( 'mcv/', '',  $mime );
            }
            else{
                $type = 'course';
            }
            $order_data['type'] = $type;
            $order_data['item_id'] = $id;
        }
        else{
            return;
        }
    }
    elseif( !$id ){
        return;
    }
    $item_id = $id;

    $post = null;
    $course = null;
    $section = null;
    $lesson = null;
    
    if( $type == 'course' && is_numeric( $id ) ){
        $post = get_post( $id );
        $item_id = $post->ID;
        $order_data['title'] = $post->post_title;
        //课程
        $course = null;
        //购买的章节
        $bsection = null;
        //购买的课时
        $blesson = null;
        if( $post->post_type == MINECLOUDVOD_LMS['course_post_type'] ){
            $course = $post;
        }
        elseif( $post->post_type == MINECLOUDVOD_LMS['lesson_post_type'] ){
            $blesson = $post;
            $course_id = mcv_lms_get_course_id_by_lesson_id( $id );
            $course = get_post( $course_id );
            $order_data['title'] = $course->post_title . ' - ' . $post->post_title;
            $order_data['order_info'] = '<p>您正在购买课程《<a href="'.get_the_permalink($course->ID).'" target="_blank">'.$course->post_title.'</a>》中的章节 「'.$post->post_title.'」</p>';
        }
        elseif( $post->post_type == 'section' ){
            $bsection = $post;
            $course_id = $bsection->post_parent;
            $course = get_post( $course_id );
            $order_data['title'] = $course->post_title . ' - ' . $post->post_title;
            $order_data['order_info'] = '<p>您正在购买课程《<a href="'.get_the_permalink($course->ID).'" target="_blank">'.$course->post_title.'</a>》中的课时 「'.$post->post_title.'」</p>';
        }
    
        $thumbnail = mcv_lms_get_course_thumbnail_url( $course );
        $course_id = $course->ID;
        $course_title = $course->post_title;
        $access_mode = mcv_lms_get_course_access_mode( $course_id );
        $price = $order_price?:mcv_lms_get_course_price( $id );
        
        if( $access_mode != "buynow"  ){
            wp_safe_redirect( get_the_permalink( $course ) );
        }
        
        $course_terms = get_the_terms($course_id, 'course-category');
        $terms = null;
        if( $course_terms ){
            foreach( $course_terms as $term ){
                $terms[] = [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'url'   => get_term_link( $term ),
                ];
            }
        }
        
        $courses_lessons = mcv_lms_get_courses_lessons($post);
        $lists = [];
        $lessonCount = 0;
        $duration = 0;
        foreach($courses_lessons as $section){
            $list = [
                'id'    => $section['ID'],
                'title' => $section['post_title']
            ];
            $lessonCount += count($section['Lessons']);
            foreach($section['Lessons'] as $lesson){
                $attrs = get_post_meta($lesson->ID, '_mcv_lms_lesson_attrs', true);
                if( !is_array($attrs) && is_string( $attrs ) ) $attrs = unserialize( $attrs );
                if(!isset($attrs['duration'])){
                    $_mcv_lesson_duration = get_post_meta($lesson->ID, '_mcv_lesson_duration', true);
                    if(isset($_mcv_lesson_duration['minute'])) $duration += intval($_mcv_lesson_duration['minute']) * 60;
                    if(isset($_mcv_lesson_duration['second'])) $duration += intval($_mcv_lesson_duration['second']);
                }
                else{
                    if(isset($attrs['duration']['minute'])) $duration += intval($attrs['duration']['minute']) * 60;
                    if(isset($attrs['duration']['second'])) $duration += intval($attrs['duration']['second']);
                }
                
                $list['lessons'][] = [
                    'id' => $lesson->ID,
                    'title' => $lesson->post_title,
                    'url'   => get_the_permalink($lesson->ID),
                    'attrs' => $attrs,
                ];
            }
            $lists[] = $list;
        }
        
        $order_data += [
            'course'    => [
                'id'    => $course_id,
                'title' => $course_title,
                'url'   => get_the_permalink($course_id),
                'thumb' => $thumbnail,
                'terms' => $terms,
            ],
            'section'   => $bsection ? [
                'id'    => $bsection->ID,
                'title' => $bsection->post_title,
            ] : false,
            'lesson'    => $blesson ? [
                'id'    => $blesson->ID,
                'title' => $blesson->post_title,
                'url'   => get_the_permalink($blesson->ID),
            ] : false,
            'lessonCount' => $lessonCount,
            'duration' => round($duration/60/60, 2),
            'payments' => $payments,
            'defaultPayment' => sanitize_text_field(wp_unslash($_GET['payment']??false)),
            'price' => $price,
        ];
        if(!$blesson && !$bsection){
            $order_data['order_info'] = '<p>'.$order_data['lessonCount'].' 课时  '.$order_data['duration'].' 小时</p>';
        }
    }
    elseif( $type == 'video' ){
        $item_id = $post_id;
        $price = mcv_get_video_price( $post_id, $id );
        $post = get_post( $post_id );
        $order_data['title'] = $post->post_title;
        if( !$post ){
            return;
        }
        $order_data += [
            'type'      => $type,
            'id'        => $id,
            'post_id'   => $post_id, 
            'r'         => $r,
            'title'     => $post->post_title,
            'price'     => $price,
            'payments' => $payments,
            'defaultPayment' => sanitize_text_field(wp_unslash($_GET['payment']??false)),
        ];
    }
    else{
        do_action( 'mcv_course_checkout_'.$type, $payments );
    }
    $order_data = apply_filters( 'mcv_order_data_'.$type, $order_data, $item_id );
    if( $user_id && !$orderid ){
        $post_order = array(
            'post_title'   => $order_data['title'],
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_date'    => wp_date('Y-m-d H:i:s'),
            'meta_input'   => [
                '_mcv_order_create_time'    => wp_date('Y-m-d H:i:s'),
                '_mcv_order_status'         => 'pending',
                '_mcv_order_payment'        => '',
                '_mcv_order_amount'         => $order_data['price'],
                '_mcv_order_items' => [ $item_id ],
            ],
        );
        if( $type == 'video' ){
            $post_order['post_mime_type'] = 'mcv/video';
            $post_order['meta_input']['_mcv_order_items'] = [ [$item_id, $id] ];
        }
        elseif( $type == 'package' ){
            $post_order['post_mime_type'] = 'mcv/package';
        }
        elseif( isset( $order_data['post_mime_type'] ) ){
            $post_order['post_mime_type'] = $order_data['post_mime_type'];
        }
        /**
         * filter 过滤创建订单数据
         * 
         * @since v1.8.7
         */
        $post_order = apply_filters( 'mcv_create_order_'. $type, $post_order, $item_id );
        
    
        $model_order = new \MineCloudvod\Models\Order();
        $cArgs = [
            's' => $post_order['post_title'],
            'post_type' => MINECLOUDVOD_LMS['order_post_type'],
            'post_status' => $post_order['post_status'],
            'author' => $post_order['post_author'],
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 订单去重查询，业务必需
                [
                    'key'       => '_mcv_order_status',
                    'value'     => $post_order['meta_input']['_mcv_order_status'],
                ],
                [
                    'key'       => '_mcv_order_amount',
                    'value'     => $post_order['meta_input']['_mcv_order_amount'],
                ],
                [
                    'key'       => '_mcv_order_items',
                    'value'     => serialize( $post_order['meta_input']['_mcv_order_items'] ),
                ],
            ],
        ];
        $is_ordered = get_posts($cArgs);
        if( count( $is_ordered ) > 0 ){
            $orderid =  $is_ordered[0]->ID;
        }
        else{
            /**
             * 判断是否创建订单，默认创建
             */
            $create = apply_filters( 'mcv_checkif_create_order', true, $order_data );
            if( $create ) $orderid = $model_order->create( $post_order );
        }
        $order_data['item_id'] = $item_id;
        $order_data['id'] = $orderid;
    }
    return $order_data;
}

// phpcs:enable WordPress.Security.NonceVerification.Recommended

function mcv_get_style_color(){
    $slot_time = MINECLOUDVOD_SETTINGS['mcv_lms_general']['slot_time']??false;
    $colors = MINECLOUDVOD_SETTINGS['mcv_lms_general']['ketang'][0]??[
        'bgColor' => '#ffffff',
        'fontColor' => '#14171a',
        'mainColor1' => '#ff7a38',
        'mainColor2' => '#2080f7',
        'secondColor1' => '#c9d0d6',
        'secondColor2' => '#586470',
        'secondColor3' => '#666c80',
        'secondColor4' => '#3e454d',
        'secondColor5' => '#f5f8fa',
    ];
    if( $slot_time ){
        $gps = MINECLOUDVOD_SETTINGS['mcv_lms_general']['ketang']??[];
        $ehours = array_column($gps, 'ehour');
        $curHour = (int) current_time('G');
        for( $i=0; $i<count($ehours); $i++ ){
            if( $i == 0 && $curHour < $ehours[$i] ){
                $colors = $gps[count($ehours)-1];
            }
            elseif( $i == count($ehours)-1 && $curHour >= $ehours[$i] ){
                $colors = $gps[count($ehours)-1];
            }
            elseif( $curHour >= $ehours[$i] && $curHour < $ehours[$i+1]){
                $colors = $gps[$i];
            }
        }
    }
    $styles = mcv_trim( 'body{
        --wp--mcv--color--background: '.$colors['bgColor'].';
        --wp--mcv--color--font: '.$colors['fontColor'].';
        --wp--mcv--color--main1: '.$colors['mainColor1'].';
        --wp--mcv--color--main2: '.$colors['mainColor2'].';
        --wp--mcv--color--second1: '.$colors['secondColor1'].';
        --wp--mcv--color--second2: '.$colors['secondColor2'].';
        --wp--mcv--color--second3: '.$colors['secondColor3'].';
        --wp--mcv--color--second4: '.$colors['secondColor4'].';
        --wp--mcv--color--second5: '.$colors['secondColor5'].';
    }' );
    return $styles;
}
/**
 * 递归获取所有子文章
 * 
 * @param int $parent_id 父文章ID
 * @param string $post_type 文章类型
 * 
 * @return array 子文章数组
 */
function mcv_get_all_descendant_posts($parent_id, $post_type = 'post', $level = 0) {
    $descendants = array();
    
    // 获取直接子文章
    $children = get_children(array(
        'post_parent' => $parent_id,
        'post_type'   => $post_type,
        'numberposts' => -1,
        'orderby'     => 'menu_order',
        'order'       => 'ASC',
        'post_status' => 'publish'
    ));
    
    if ($children) {
        // 保险：显式按 menu_order 升序排序，避免 get_children 在 menu_order 重复
        // 或底层查询顺序异常时退化为按 post_id 排列
        uasort($children, function ($a, $b) {
            $oa = intval($a->menu_order);
            $ob = intval($b->menu_order);
            if ($oa === $ob) {
                return intval($a->ID) <=> intval($b->ID); // 稳定回退到 ID
            }
            return $oa <=> $ob;
        });

        // 将直接子文章添加到结果数组
        foreach ($children as $child) {
            $temp = $child;
            $temp->level = $level + 1;

            // 递归获取当前子文章的子文章（子子文章）
            $grandchildren = mcv_get_all_descendant_posts($child->ID, $post_type, $level + 1);
            if ($grandchildren) {
                $temp->child = $grandchildren;
            }
            $descendants[] = $temp;
        }
    }
    
    return $descendants;
}

/**
 * 在插件中执行已注册的 Block Pattern
 * 
 * @param string $pattern_name 模式的注册名称（如 'my-plugin/my-custom-pattern'）
 * @return string 渲染后的 HTML 内容
 */
function mcv_execute_registered_pattern( $pattern_name ) {
    // 验证参数
    if ( empty( $pattern_name ) ) {
        return '';
    }

    // 获取模式注册中心
    $patterns_registry = WP_Block_Patterns_Registry::get_instance();
    
    // 检查模式是否存在
    if ( ! $patterns_registry->is_registered( $pattern_name ) ) {
        return sprintf( 'Pattern "%s" 未注册', $pattern_name );
    }

    // 获取模式详情（包含区块内容）
    $pattern = $patterns_registry->get_registered( $pattern_name );
    if ( empty( $pattern['content'] ) ) {
        return sprintf( 'Pattern "%s" 内容为空', $pattern_name );
    }

    // 渲染模式中的区块内容（返回 HTML）
    return do_blocks( $pattern['content'] );
}
