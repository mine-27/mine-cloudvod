<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

namespace MineCloudvod\LMS;

defined( 'ABSPATH' ) || exit;

class PostType{
    
    public $course_post_type;
    public $lesson_post_type;
    public $course_category;
    public $course_tag;

    public function __construct() {
        $this->course_post_type = MINECLOUDVOD_LMS['course_post_type'];
        $this->lesson_post_type = MINECLOUDVOD_LMS['lesson_post_type'];
        $this->course_category = 'course-category';
        $this->course_tag = 'course-tag';
        
        add_action( 'init', array($this, 'register_course_post_types') );
        add_action( 'init', array($this, 'register_topic_post_types') );
        add_action( 'init', array($this, 'register_lesson_post_types') );

        add_action( 'enqueue_block_editor_assets', [$this, 'force_editor_fullscreen_by_default'], 9999 );

        add_filter( "manage_{$this->course_post_type}_posts_columns", [$this, 'course_post_columns'] );
        add_action( "manage_{$this->course_post_type}_posts_custom_column", [$this, 'course_post_columns_content'], 10, 2);

        // 标签筛选
        add_action( 'restrict_manage_posts', [$this, 'tagFilter']);
        add_action( 'parse_query', [$this, 'tagQuery']);

        add_filter( 'post_type_archive_title', [$this, 'course_archive_title'], 10, 2);
        add_action( 'wp_head', [$this, 'course_archive_meta']);
        add_action( 'wp_head', [$this, 'course_archive_title2']);

        add_filter( 'gutenberg_can_edit_post_type', array( $this, 'gutenberg_can_edit_post_type' ), 10, 2 );
        add_filter( 'use_block_editor_for_post_type', array( $this, 'gutenberg_can_edit_post_type' ), 10, 2 );

        add_filter( 'enter_title_here', [$this, 'lessonTitle'] );
        add_filter( 'wp_insert_post_data', [$this, 'mcv_before_lesson_save'] );

        add_action( 'wp_after_insert_post',     [ $this, 'update_lesson_number' ], 10, 2 );
        add_action( 'after_delete_post',        [ $this, 'update_lesson_number' ], 10, 2 );


        add_action( 'pre_get_posts', [ $this, 'mcv_pre_get_posts_course' ], 1 );

        // SEO field
        add_action( $this->course_category . '_edit_form_fields', [ $this, 'edit_tag_category_seo' ], 10, 2 );

        add_action( $this->course_category . '_add_form_fields', [ $this, 'add_tag_category_seo' ] );
        
        // SEO save
        add_action( 'created_' . $this->course_category, [ $this, 'save_seo_data' ] );
        add_action( 'edited_' . $this->course_category, [ $this, 'save_seo_data' ] );
        
    }
    public function save_seo_data( $term_id ){
        if (isset($_POST['mcv-title']) && isset($_POST['mcv-keywords'])){ // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP taxonomy 编辑流程已在核心层校验 nonce

            if (!current_user_can('manage_categories')){
                return;
            }
    
            $title_key = 'mcv-title-'.$term_id; // key
            $title_value = sanitize_text_field(wp_unslash($_POST['mcv-title'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP taxonomy 编辑流程已校验 nonce
    
            $words_key = 'mcv-keywords-'.$term_id;
            $words_value = sanitize_text_field(wp_unslash($_POST['mcv-keywords'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP taxonomy 编辑流程已校验 nonce
    
            update_term_meta( $term_id, 'mcv-title', $title_value );
            update_term_meta( $term_id, 'mcv-keywords', $words_value );
        }
    }
    public function add_tag_category_seo( $taxonomy ){
        if( $taxonomy != $this->course_category && $taxonomy != $this->course_tag ) return;
        ?>
        <div class="form-field">
            <label for="mcv-title">SEO title</label>
            <input name="mcv-title" id="mcv-title" type="text" value="" aria-required="" aria-describedby="mcv-title-description">
            <p id="mcv-title-description">SEO title</p>
        </div>
        <div class="form-field">
            <label for="mcv-keywords">SEO keywords</label>
            <input name="mcv-keywords" id="mcv-keywords" type="text" value="" aria-required="" aria-describedby="mcv-keywords-description">
            <p id="mcv-keywords-description">SEO keywords</p>
        </div>
        <?php
    }
    public function edit_tag_category_seo( $tag, $taxonomy ){
        if( $taxonomy != $this->course_category && $taxonomy != $this->course_tag ) return;
        ?>
        <tr class="form-field">
            <th scope="row"><label for="mcv-title">SEO title</label></th>
            <td><input name="mcv-title" id="mcv-title" type="text" value="<?php echo esc_html(get_term_meta( $tag->term_id, 'mcv-title', true )); ?>" aria-required="true" aria-describedby="name-mcv-title">
            <p class="description" id="name-mcv-title"></p></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="mcv-keywords">SEO keywords</label></th>
            <td><input name="mcv-keywords" id="mcv-keywords" type="text" value="<?php echo esc_html(get_term_meta( $tag->term_id, 'mcv-keywords', true )); ?>" aria-required="true" aria-describedby="name-mcv-keywords">
            <p class="description" id="name-mcv-keywords"></p></td>
        </tr>
        <?php
    }
    /**
     * 一些不规范主题没有标题
     */
    public function course_archive_title2() { 
        if( isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['notitle'] ) && MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['notitle'] && is_post_type_archive(MINECLOUDVOD_LMS['course_post_type']) && isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['title'] ) ){
            global $wp_query;
            $args = $wp_query->query_vars;
            
            $new_name = '';
            $filter = MINECLOUDVOD_SETTINGS['mcv_lms_course']['filter']??[];
            if( isset( $filter['status'] ) && $filter['status'] ){
                if( is_array( $filter['items'] ) ): 
                    foreach( $filter['items'] as $item ):
                        switch( $item ){
                            case 'category':
                                if( isset($args['course-category']) ){
                                    $cat = get_term_by( 'slug', $args['course-category'], 'course-category' );
                                    if( $st = get_term_meta( $cat->term_id, 'mcv-title', true ) ){
                                        $new_name .= ' ' . $st;
                                    }
                                    elseif( $cat ) $new_name .= ' ' . $cat->name;
                                }
                                break;
                            
                            case 'difficulty':
                                if( isset($args['mcv-lvl']) ){
                                    $difficulties = MINECLOUDVOD_LMS['course_difficulty'];
                                    if( isset( $difficulties[$args['mcv-lvl']] ) ) $new_name .= ' ' . $difficulties[$args['mcv-lvl']];
                                }
                                break;

                            case 'mode':
                                if( isset($args['mcv-mod']) ){
                                    $modes = MINECLOUDVOD_LMS['access_mode'];
                                    if( isset( $modes[$args['mcv-mod']] ) ) $new_name .= ' ' . $modes[$args['mcv-mod']];
                                }
                                break;

                            case 'tag':
                                if( isset($args['course-category']) ){
                                    $tag = get_term_by( 'slug', $args['course-category'], 'course-tag' );
                                    if( $tag ) $new_name .= ' ' . $tag->name;
                                }
                                break;
                        }
                    endforeach;
                endif; 
            }
            $name = trim( $new_name . ' ' . MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['title'] );
            echo '<title>'. esc_html($name) .' </title>' . "\n"; 
        }
    }

    public function course_archive_meta() { 
        if( !is_post_type_archive(MINECLOUDVOD_LMS['course_post_type']) ) return;
        $course_category    = get_query_var( 'course-category' );
        $keywords = '';
        $desc = '';
        if( $course_category ){
            $cat = get_term_by( 'slug', $course_category, 'course-category' );
            if( $sk = get_term_meta( $cat->term_id, 'mcv-keywords', true ) ){
                $keywords = $sk;
            }
            if( !empty( $cat->description ) ){
                $desc = $cat->description;
            }
        }
        elseif( isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['keywords'] ) ){
            $keywords = MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['keywords']; 
            $desc = MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['desc'];
        }
        if( $keywords ){
            echo '<meta name="keywords" content="'. esc_html($keywords) .'" />' . "\n"; 
        }
        if( $desc ){
            echo '<meta name="description" content="'. esc_html($desc) .'" />' . "\n"; 
        }
    }

    public function course_archive_title($name, $post_type){
        if($post_type == MINECLOUDVOD_LMS['course_post_type'] && isset(MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['title'])){
            global $wp_query;
            $args = $wp_query->query_vars;
            $new_name = '';
            $filter = MINECLOUDVOD_SETTINGS['mcv_lms_course']['filter']??[];
            if( isset( $filter['status'] ) && $filter['status'] ){
                if( is_array( $filter['items'] ) ): 
                    foreach( $filter['items'] as $item ):
                        switch( $item ){
                            case 'category':
                                if( isset($args['course-category']) && $args['course-category'] ){
                                    $cat = get_term_by( 'slug', $args['course-category'], 'course-category' );
                                    if( $cat && $st = get_term_meta( $cat->term_id, 'mcv-title', true ) ){
                                        $new_name .= ' ' . $st;
                                    }
                                    elseif( $cat ) $new_name .= ' ' . $cat->name;
                                }
                                break;
                            
                            case 'difficulty':
                                if( isset($args['mcv-lvl']) ){
                                    $difficulties = MINECLOUDVOD_LMS['course_difficulty'];
                                    if( isset( $difficulties[$args['mcv-lvl']] ) ) $new_name .= ' ' . $difficulties[$args['mcv-lvl']];
                                }
                                break;

                            case 'mode':
                                if( isset($args['mcv-mod']) ){
                                    $modes = MINECLOUDVOD_LMS['access_mode'];
                                    if( isset( $modes[$args['mcv-mod']] ) ) $new_name .= ' ' . $modes[$args['mcv-mod']];
                                }
                                break;

                            case 'tag':
                                if( isset($args['course-category']) ){
                                    $tag = get_term_by( 'slug', $args['course-category'], 'course-tag' );
                                    if( $tag ) $new_name .= ' ' . $tag->name;
                                }
                                break;
                        }
                    endforeach;
                endif; 
            }
            $name = trim( $new_name . ' ' . MINECLOUDVOD_SETTINGS['mcv_lms_course']['archive']['title'] );
        }
        return $name;
    }

    public function mcv_pre_get_posts_course(  $wp_query  ){
        if( is_admin() ) return;
        if( isset($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] == MINECLOUDVOD_LMS['course_post_type'] ){
            
            if( isset(MINECLOUDVOD_SETTINGS['mcv_lms_course']['pagenum']) && is_archive() ){
                $wp_query->set( 'posts_per_page', MINECLOUDVOD_SETTINGS['mcv_lms_course']['pagenum'] );
            }
            if( isset( $wp_query->query_vars['mcv-lvl'] ) ){
                if( $wp_query->query_vars['mcv-lvl'] != 'all' ){
                    $mq = $wp_query->get('meta_query');
                    if( !$mq ) $mq = [];
                    $mq[] = [
                        'key'     => '_mcv_course_difficulty',
                        'value'   => sanitize_text_field( $wp_query->query_vars['mcv-lvl'] ),
                    ];
                    $wp_query->set( 'meta_query', $mq );
                }
            }
        
            if( isset( $wp_query->query_vars['mcv-mod'] ) ){
                if( $wp_query->query_vars['mcv-mod'] != 'all' ){
                    $mq = $wp_query->get('meta_query');
                    if( !$mq ) $mq = [];
                    $mq[] = [
                        'key'     => '_mcv_access_mode',
                        'value'   => sanitize_text_field( $wp_query->query_vars['mcv-mod'] ),
                    ];
                    $wp_query->set( 'meta_query', $mq );
                }
            }
        
            if( isset( $wp_query->query_vars['mcv-s'] ) ){
                if( $wp_query->query_vars['mcv-s'] != '' ){
                    $s = $wp_query->get('mcv-s');
                    $wp_query->set( 's', $s );
                }
            }
        
            if( isset( $wp_query->query_vars['mcv-vip'] ) ){
                if( isset( $wp_query->query_vars['mcv-vip'] ) && $wp_query->query_vars['mcv-vip'] != '' ){
                    $vip_id = explode( '_', $wp_query->query_vars['mcv-vip'] );
                    $vips = MINECLOUDVOD_SETTINGS['uc_member_levels'];
                    if( count($vip_id) == 2 && $vip_id[0] == 'vip' && isset($vips[$vip_id[1]]) ){
                        $mq = $wp_query->get('meta_query');
                        if( !$mq ) $mq = [];
                        $mq[] = [
                            'compare' => '>=',
                            'key'     => '_mcv_member_levels',
                            'value'   => $vip_id[1],
                        ];
                        $wp_query->set( 'meta_query', $mq );
                    }
                }
            }
        }
    }

    public function update_lesson_number( $lesson_id, $lesson ){
        if( !$lesson_id || !$lesson ) return;
        if( $lesson->post_type == $this->lesson_post_type ){
            $course_id = mcv_lms_get_course_id_by_lesson_id( $lesson_id );
            if( $course_id ){
                $this->update_lesson_number_by_course_id( $course_id );
                mcv_lms_del_lessons_cache( $course_id );
            }
        }
    }

    public function update_lesson_number_by_course_id( $course_id ){
        $courses_lessons = mcv_lms_get_courses_lessons( $course_id );
        $lessonCount = 0;
        $duration = 0;
        foreach($courses_lessons as $section){
            $lessonCount += count($section['Lessons']);
            foreach($section['Lessons'] as $lesson){
                if( $lesson->post_type == 'section' ){
                    $lessonCount += count($lesson->Lessons) - 1;
                    foreach($lesson->Lessons as $sub_lesson){
                        $duration += mcv_lms_lesson_duration($sub_lesson);
                    }
                }
                else{
                    $duration += mcv_lms_lesson_duration($lesson);
                }
                
            }
        }
        update_post_meta( $course_id, '_mcv_number_lessons', $lessonCount );
        update_post_meta( $course_id, '_mcv_total_duration', $duration );
    }

    /**
     * Adds a tag filter dropdown
     *
     * @return void
     */
    public function tagFilter(){
        global $typenow;
        if ($typenow !== $this->course_post_type) {
            return;
        }

        $taxonomy1  = $this->course_category ;
        $selected1      = isset($_GET[$taxonomy1]) ? sanitize_text_field(wp_unslash($_GET[$taxonomy1])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 后台分类筛选下拉只读
        if( $selected1 && !is_numeric($selected1) ){
            $term = get_term_by('slug', $selected1, $taxonomy1);
            $selected1 = $term->term_id;
        }
        $info_taxonomy1 = get_taxonomy($taxonomy1);
        wp_dropdown_categories(array(
            'show_option_all' => sprintf(
                // translators: %s: taxonomy name
                __('Show all %s', 'mine-cloudvod'), 
                $info_taxonomy1->label
            ),
            'taxonomy'        => $taxonomy1,
            'name'            => $taxonomy1,
            'orderby'         => 'name',
            'selected'        => $selected1,
            'show_count'      => true,
            'hide_empty'      => true,
            'hierarchical'    => true,
        ));

        $taxonomy2  = $this->course_tag ;
        $selected2      = isset($_GET[$taxonomy2]) ? sanitize_text_field(wp_unslash($_GET[$taxonomy2])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 后台分类筛选下拉只读
        $info_taxonomy2 = get_taxonomy($taxonomy2);
        wp_dropdown_categories(array(
            'show_option_all' => sprintf(
                // translators: %s: taxonomy name
                __('Show all %s', 'mine-cloudvod'), 
                $info_taxonomy2->label),
            'taxonomy'        => $taxonomy2,
            'name'            => $taxonomy2,
            'orderby'         => 'name',
            'selected'        => $selected2,
            'show_count'      => true,
            'hide_empty'      => true,
            'hierarchical'    => true,
        ));
    }

    public function tagQuery($query){
        global $pagenow;
        $q_vars    = &$query->query_vars;
        if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $this->course_post_type) {
            $tag = $this->course_tag;
            if(isset($q_vars[$tag]) && is_numeric($q_vars[$tag]) && $q_vars[$tag] != 0){
                $term = get_term_by('id', $q_vars[$tag], $tag);
                if($term){
                    $q_vars[$tag] = $term->slug;
                    $query->is_search = false;
                }
            }
            $cat = $this->course_category;
            if(isset($q_vars[$cat]) && is_numeric($q_vars[$cat]) && $q_vars[$cat] != 0){
                $term = get_term_by('id', $q_vars[$cat], $cat);
                if($term){
                    $q_vars[$cat] = $term->slug;
                    $query->is_search = false;
                }
            }
        }
    }

    /**
     * 列
     */
    public function course_post_columns($defaults){
        $columns = array(
            'cb'    => $defaults['cb'],
            'title' => $defaults['title'],
            'price' => __('Course Price', 'mine-cloudvod'),
            'lvl' => __( 'Difficulty', 'mine-cloudvod' ),
            'lesson_num' => __( 'Number Lessons', 'mine-cloudvod' ),
            'num' => __('Number Enrolled', 'mine-cloudvod'),
            'author' => __('Author', 'mine-cloudvod'),
            'taxonomy-'.$this->course_category => '分类',
            'taxonomy-'.$this->course_tag => '标签',
            'date' => $defaults['date'],
        );
        $columns = apply_filters( 'mcv_'.MINECLOUDVOD_LMS['course_post_type'].'_posts_columns', $columns );
        return $columns;
    }

    /**
     * 列的数据
     */
    public function course_post_columns_content($column_name, $post_ID){
        if ( 'taxonomy-'.$this->course_tag === $column_name || 'taxonomy-'.$this->course_category == $column_name ) {
            $tags = get_the_terms($post_ID, $this->course_tag);
            if (is_array($tags)) {
                foreach ($tags as $key => $tag) {
                    $tags[$key] = '<a href="?post_type=' .$this->course_post_type . '&' . $this->course_tag  . '=' . $tag->term_id . '">' . $tag->name . '</a>';
                }
                echo wp_kses(implode(', ', $tags), mcsf_allowed_html());
            }
        }
        if ( 'price' === $column_name ) {
            $_mcv_access_mode =  get_post_meta( $post_ID, '_mcv_access_mode', true );
            $_mcv_course_price = '';
            if( $_mcv_access_mode == 'buynow' ){
                $_mcv_course_price = '￥' . get_post_meta( $post_ID, '_mcv_course_price', true );
                echo esc_html($_mcv_course_price);
            }
            else{
                echo esc_html($_mcv_access_mode);
            }
        }
        if ( 'lvl' === $column_name ) {
            $difficulties = MINECLOUDVOD_LMS['course_difficulty'];
            $_mcv_course_difficulty =  get_post_meta( $post_ID, '_mcv_course_difficulty', true );
            echo esc_html($difficulties[$_mcv_course_difficulty]??'All');
        }
        if ( 'lesson_num' === $column_name ) {
            $_mcv_number_lessons =  get_post_meta( $post_ID, '_mcv_number_lessons', true );
            if( !$_mcv_number_lessons ){
                $this->update_lesson_number_by_course_id( $post_ID );
            }
            echo esc_html(apply_filters( 'mcv_course_lessons_number_column_content', $_mcv_number_lessons, $post_ID ));
        }
        if ( 'num' === $column_name ) {
            $num = mcv_lms_get_course_enrolled_number( $post_ID );
            echo wp_kses(apply_filters( 'mcv_course_enrolled_number_column_content', $num, $post_ID ) . ' - ' . get_post_meta( $post_ID, '_mcv_course_virtual_number', true ), mcsf_allowed_html());
        }
    }
    /**
     * 设置lesson的parent id, menu order
     */
    public function mcv_before_lesson_save($data){
        if( !isset($_GET['sectionId']) || $data['post_type'] != $this->lesson_post_type ) return $data; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读 section ID 设置课时父级
        $section_id = sanitize_text_field(wp_unslash($_GET['sectionId'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读 section ID 设置课时父级
        $lesson_id = $data['ID'] ?? 0;
        if( is_numeric( $section_id ) ){
            $data['post_parent'] = $section_id;
            $order_id    = mcv_lms_get_lesson_order_id( $section_id, $lesson_id );
            $data['menu_order'] = $order_id;
            $course_id = wp_get_post_parent_id( $section_id );
            if( $course_id ){
                mcv_lms_course_can_preview( $course_id, true );
            }
        }
        return $data;
    }

    public function force_editor_fullscreen_by_default() {
        global $post_type;
        if($post_type == MINECLOUDVOD_LMS['lesson_post_type']){
            $js_code = ('window._wpLoadBlockEditor.then( function() {
                var isFullScreenMode = wp.data.select("core/edit-post").isFeatureActive("fullscreenMode");
                if ( !isFullScreenMode ){
                    wp.data.dispatch("core/edit-post").toggleFeature("fullscreenMode");
                }
                let saved = false;
                let unsubSave = wp.data.subscribe(() => {
                    const isSaving = wp.data.select( "core/editor" ).isSavingPost();
                    const isAutoSaving = wp.data.select( "core/editor" ).isAutosavingPost();
                    if ( isSaving && !isAutoSaving ) {
                        saved = true;
                    }
                    if(!isSaving && saved){
                        unsubSave();
                        parent.wp.data.dispatch("mine-cloudvod/vod").setUI("lessonSaved", true);
                        saved = false;
                    }
                } );
            });
            ');
            wp_add_inline_script( 'mine_cloudvod-classic-js', $js_code );
            wp_add_inline_style('wp-block-editor', '#editor .interface-more-menu-dropdown,#editor .components-button.edit-post-fullscreen-mode-close{display:none !important;}');
        }
    }
    public function register_course_category(){
        $labels = array(
            'name'                       => _x( 'Course Categories', 'taxonomy general name', 'mine-cloudvod' ),
            'singular_name'              => _x( 'Category', 'taxonomy singular name', 'mine-cloudvod' ),
            'search_items'               => __( 'Search Categories', 'mine-cloudvod' ),
            'popular_items'              => __( 'Popular Categories', 'mine-cloudvod' ),
            'all_items'                  => __( 'All Categories', 'mine-cloudvod' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Category', 'mine-cloudvod' ),
            'update_item'                => __( 'Update Category', 'mine-cloudvod' ),
            'add_new_item'               => __( 'Add New Category', 'mine-cloudvod' ),
            'new_item_name'              => __( 'New Category Name', 'mine-cloudvod' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'mine-cloudvod' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'mine-cloudvod' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'mine-cloudvod' ),
            'not_found'                  => __( 'No categories found.', 'mine-cloudvod' ),
            'menu_name'                  => __( 'Course Categories', 'mine-cloudvod' ),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'                   => true,
            'show_in_menu'              => false,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'show_in_rest'          => true,
            'menu_position'             => null,
            'rewrite'               => array( 'slug' => $this->course_category ),
        );

        register_taxonomy( $this->course_category, $this->course_post_type, $args );
    }
    public function register_course_tag(){
        $labels = array(
            'name'                       => _x( 'Tags', 'taxonomy general name', 'mine-cloudvod' ),
            'singular_name'              => _x( 'Tag', 'taxonomy singular name', 'mine-cloudvod' ),
            'search_items'               => __( 'Search Tags', 'mine-cloudvod' ),
            'popular_items'              => __( 'Popular Tags', 'mine-cloudvod' ),
            'all_items'                  => __( 'All Tags', 'mine-cloudvod' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Tag', 'mine-cloudvod' ),
            'update_item'                => __( 'Update Tag', 'mine-cloudvod' ),
            'add_new_item'               => __( 'Add New Tag', 'mine-cloudvod' ),
            'new_item_name'              => __( 'New Tag Name', 'mine-cloudvod' ),
            'separate_items_with_commas' => __( 'Separate Tags with commas', 'mine-cloudvod' ),
            'add_or_remove_items'        => __( 'Add or remove Tags', 'mine-cloudvod' ),
            'choose_from_most_used'      => __( 'Choose from the most used Tags', 'mine-cloudvod' ),
            'not_found'                  => __( 'No Tags found.', 'mine-cloudvod' ),
            'menu_name'                  => __( 'Tags', 'mine-cloudvod' ),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'                   => true,
            'show_in_menu'              => false,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'show_in_rest'          => true,
            'menu_position'             => null,
            'rewrite'               => array( 'slug' => $this->course_tag ),
        );

        register_taxonomy( $this->course_tag, $this->course_post_type, $args );
    }
    public function register_course_post_types() {
        $course_post_type = $this->course_post_type;
        
        /**
         * Filter 自定义course　slug
         */
        $course_base_slug = apply_filters('mcv_lms_course_base_slug', $course_post_type);

        $labels = array(
            'name'                      => _x( 'Courses', 'post type general name', 'mine-cloudvod' ),
            'singular_name'             => _x( 'Course', 'post type singular name', 'mine-cloudvod' ),
            'menu_name'                 => _x( 'Courses', 'admin menu', 'mine-cloudvod' ),
            'name_admin_bar'            => _x( 'Course', 'add new on admin bar', 'mine-cloudvod' ),
            'add_new'                   => _x( 'Add New', 'mcv course add', 'mine-cloudvod' ),
            'add_new_item'              => __( 'Add New Course', 'mine-cloudvod' ),
            'new_item'                  => __( 'New Course', 'mine-cloudvod' ),
            'edit_item'                 => __( 'Edit Course', 'mine-cloudvod' ),
            'view_item'                 => __( 'View Course', 'mine-cloudvod' ),
            'all_items'                 => __( 'Courses', 'mine-cloudvod' ),
            'search_items'              => __( 'Search Courses', 'mine-cloudvod' ),
            'parent_item_colon'         => __( 'Parent Courses:', 'mine-cloudvod' ),
            'not_found'                 => __( 'No courses found.', 'mine-cloudvod' ),
            'not_found_in_trash'        => __( 'No courses found in Trash.', 'mine-cloudvod' )
        );

        $args = array(
            'labels'                    => $labels,
            'description'               => __( 'Description.', 'mine-cloudvod' ),
            'public'                    => true,
            'publicly_queryable'        => true,
            'show_ui'                   => true,
            'show_in_menu'              => false,
            'show_in_admin_bar'         => true,
            'query_var'                 => true,
            'rewrite'                   => array( 'slug' => $course_base_slug, 'with_front' => true ),
            'menu_icon'                 => 'dashicons-book-alt',
            'capability_type'           => 'post',
            'has_archive'               => true,
            'hierarchical'              => false,
            'menu_position'             => null,
            'taxonomies'                => array( $this->course_category, $this->course_tag ),
            'supports'                  => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments' ),
            'show_in_rest'              => true,
            'show_in_nav_menus'         => true,
            'capabilities' => array(),
        );
        add_theme_support( 'post-thumbnails' );

        register_post_type($course_post_type, $args);

        /**
         * Taxonomy
         */
        $this->register_course_category();
        $this->register_course_tag();
    }

    public function register_lesson_post_types() {
        $lesson_post_type = $this->lesson_post_type;
        /**
         * Filter 自定义 lesson slug
         */
        $lesson_base_slug = apply_filters('mcv_lms_lesson_base_slug', $lesson_post_type);

        $labels = array(
            'name'               => _x( 'Lessons', 'post type general name', 'mine-cloudvod' ),
            'singular_name'      => _x( 'Lesson', 'post type singular name', 'mine-cloudvod' ),
            'menu_name'          => _x( 'Lessons', 'admin menu', 'mine-cloudvod' ),
            'name_admin_bar'     => _x( 'Lesson', 'add new on admin bar', 'mine-cloudvod' ),
            'add_new'            => _x( 'Add New', "mcv lesson add", 'mine-cloudvod' ),
            'add_new_item'       => __( 'Add New Lesson', 'mine-cloudvod' ),
            'new_item'           => __( 'New Lesson', 'mine-cloudvod' ),
            'edit_item'          => __( 'Edit Lesson', 'mine-cloudvod' ),
            'view_item'          => __( 'View Lesson', 'mine-cloudvod' ),
            'all_items'          => __( 'Lessons', 'mine-cloudvod' ),
            'search_items'       => __( 'Search Lessons', 'mine-cloudvod' ),
            'parent_item_colon'  => __( 'Parent Lessons:', 'mine-cloudvod' ),
            'not_found'          => __( 'No lessons found.', 'mine-cloudvod' ),
            'not_found_in_trash' => __( 'No lessons found in Trash.', 'mine-cloudvod' )
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Description.', 'mine-cloudvod' ),
            'public'             => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => $lesson_base_slug, 'with_front' => true ),
            'menu_icon'          => 'dashicons-list-view',
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'excerpt'),
            'show_in_rest'       => true,
            'show_in_nav_menus'         => false,
            'exclude_from_search' => true,
            'capabilities' => array(),
            'template' => [
                ['mine-cloudvod/block-container']
            ],
        );

        register_post_type($lesson_post_type, $args );
    }

    /**
     * 标题
     */
    public function lessonTitle($title)
    {
        $screen = get_current_screen();
        if ($this->lesson_post_type == $screen->post_type) {
            $title = __('Add Lesson Title', 'mine-cloudvod');
        }
        return $title;
    }
    
    public function gutenberg_can_edit_post_type( $can_edit, $post_type ) {
        $enable_gutenberg = true;
        return $this->course_post_type === $post_type ? $enable_gutenberg : $can_edit;
    }

    public function register_topic_post_types(){
        $args = array(
            'label'              => __( 'Section', 'mine-cloudvod' ),
            'description'        => __( 'Description.', 'mine-cloudvod' ),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'query_var'          => false,
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
        );
        register_post_type( 'section', $args );
    }
}
