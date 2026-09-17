<?php

namespace MineCloudvod\Ability;

class Note
{
    protected $post_type = 'mcv_note';

    public function __construct()
    {
        if (isset(MINECLOUDVOD_SETTINGS['aliplayer_Note']['status']) && MINECLOUDVOD_SETTINGS['aliplayer_Note']['status']) {

            add_action('init', [$this, 'init']);

            add_filter('enter_title_here', [$this, 'videoTitle']);

            // 列表页
            add_filter("manage_{$this->post_type}_posts_columns", [$this, 'postColumns'], 1);
            add_action("manage_{$this->post_type}_posts_custom_column", [$this, 'postColumnsContent'], 10, 2);

            // 标签筛选
            add_action('parse_query', [$this, 'tagQuery']);

            // 只显示mcv_note的文章
            add_filter('pre_get_posts', [$this, 'limitMcvNotePosts']);

            //add_filter('request', 'suren_column_ordering', 10, 2);

            add_action('wp_ajax_mcv_note_save', [$this, 'mcv_note_save']);
            add_action('wp_ajax_nopriv_mcv_note_save', [$this, 'mcv_note_save']);
            //截图
            add_action('wp_ajax_mcv_note_screenshot', array($this, 'mcv_note_screenshot'));
            add_action('wp_ajax_nopriv_mcv_note_screenshot', array($this, 'mcv_note_screenshot'));

        }
        add_action( 'wp_ajax_mcv_note_init', [$this, 'mcv_note_init'] );
    }

    public function mcv_note_init(){
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv_note_init')) {
            echo wp_json_encode(array('status' => '0', 'msg' => __( 'Illegal request', 'mine-cloudvod' )));exit;
        }
        $init_pages = array(
            MINECLOUDVOD_PATH.'/templates/vod/note.php' => array(__("MineCloudVoD Note", "mine-cloudvod"), 'mcv-aliplayer-note'),
        );
        foreach ($init_pages as $template => $item) {
            $one_page = array(
                'post_title'  => $item[0],
                'post_name'   => $item[1],
                'post_status' => 'publish',
                'post_type'   => 'page',
                'post_author' => 1,
            );
            $one_page_check =  null;
            $query = new \WP_Query(
                array(
                    'post_type'              => 'page',
                    'title'                  => $item[0],
                    'post_status'            => 'all',
                    'posts_per_page'         => 1,
                    'no_found_rows'          => true,
                    'ignore_sticky_posts'    => true,
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false,
                    'orderby'                => 'post_date ID',
                    'order'                  => 'ASC',
                )
            );
             
            if ( ! empty( $query->post ) ) {
                $one_page_check = $query->post;
            }
            if (!isset($one_page_check->ID)) {
                $one_page_id = wp_insert_post($one_page);
                update_post_meta($one_page_id, '_wp_page_template', $template);
            }
        }
        echo wp_json_encode(array('status' => '1', 'msg' => __( 'Initialized', 'mine-cloudvod' )));exit;
    }
    
    public function mcv_note_save(){
        header('Content-type:application/json; Charset=utf-8');
        if(!is_user_logged_in())exit;
        $did        = !empty($_POST['did']) ? sanitize_text_field(wp_unslash($_POST['did'])) : null;
        $pid        = !empty($_POST['pid']) ? sanitize_text_field(wp_unslash($_POST['pid'])) : 0;
        global $current_user;
        $uid = $current_user->ID;
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv_note_' . $pid)) {
            echo wp_json_encode(array('status' => '0', 'msg' => __( 'Illegal request', 'mine-cloudvod' )));exit;
        }
        $vpost = get_post($pid);
        if(!$vpost){
            echo wp_json_encode(array('status' => '0', 'msg' => __( 'Illegal request', 'mine-cloudvod' )));exit;
        }
        $post_content = !empty($_POST['content']) ? trim(sanitize_text_field(wp_unslash($_POST['content']))) : '';

        $post_status = 'publish';

        // if (!current_user_can('publish_posts')) {
        //     echo wp_json_encode(array('status' => '0', 'msg' => '您没有权限发布或修改文章'));exit;
        // }
        $vnote = $this->getPostByMeta($pid, $uid);
        $post = array(
            'post_title'    => $vpost->post_title,
            'post_content'  => $post_content,
            'post_status'   => $post_status,
            'post_author'   => get_current_user_id(),
            'post_type'     => $this->post_type,
        );
        if($vnote){
            $post['ID'] = $vnote->ID;
            wp_update_post( $post );
        }
        else{
            // 插入文章
            $new_post = wp_insert_post( $post );
            update_post_meta($new_post, 'mcv_post_id', $pid);
        }
        if($new_post instanceof \WP_Error) {
            echo wp_json_encode(array('status' => '0', 'msg' => '网络错误，请重试或联系管理员'));exit;
        }
        wp_send_json_success();
        exit;
    }
    public function getPostByMeta($pid, $uid){
        $args = array (
            'post_type'     => 'mcv_note',
            'post_status' => 'publish',
            'author'        => $uid,
            'meta_query'    => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 按文章/用户筛选笔记，业务必需
                'relation' => 'AND',
                array(
                    'key'   => 'mcv_post_id',
                    'value' => $pid,
                ),
            ),
        );

        // The Query
        $query = new \WP_Query( $args );
        $vnote = false;
        // The Loop
        if ( $query->have_posts() ) {
            $vnote = $query->posts[0];
        } 
        wp_reset_postdata();
        return $vnote;
    }
    
    public function mcv_note_screenshot(){
        header('Content-type:application/json; Charset=utf-8');
        global $current_user;
        $uid = $current_user->ID;
        
        $postid     = !empty($_POST['pid']) ? sanitize_text_field(wp_unslash($_POST['pid'])) : null;
        $did        = !empty($_POST['did']) ? sanitize_text_field(wp_unslash($_POST['did'])) : null;
        $nonce   = !empty($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : null;
        if (!$nonce || !wp_verify_nonce($nonce, 'mcv_note_' . $postid)) {
            echo wp_json_encode(array('status' => '0', 'msg' => '非法请求'));exit;
        }
        $img        = !empty($_POST['img']) ? sanitize_text_field(wp_unslash($_POST['img'])) : null;
        $type       = '';
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)){
            $type = $result[2];
            $img = base64_decode(str_replace($result[1], '', $img));
        }
        if (strtolower($type) != 'png') {
            echo wp_json_encode(array('status' => '0', 'msg' => '图片类型错误'));exit;
        }
    
    
        if ($type && $img && is_user_logged_in()) {
            $wp_upload_dir = wp_upload_dir();
            $basename   = "mcv_ss_" . time() . "." . $type;
            $filename   = $wp_upload_dir['path'] . '/' . $basename;
            file_put_contents($filename, $img);
            $attachment = array(
                    'guid'           => $wp_upload_dir['url'] . '/' . $basename,
                    'post_mime_type' => $type,
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', $basename ),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
            );
            $attach_id  = wp_insert_attachment( $attachment, $filename );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
            wp_update_attachment_metadata( $attach_id, $attach_data );
            // 返回图片地址和状态
            echo wp_json_encode(
                [
                'src' => $attachment['guid']
                ]
            );
        }
        exit;
    }
    /**
     * 只显示mcv_nonte的文章
     */
    public function limitMcvNotePosts($query)
    {
        global $pagenow, $typenow;

        if ('edit.php' != $pagenow || !$query->is_admin || $this->post_type !== $typenow) {
            return $query;
        }

        if (!current_user_can('edit_others_posts')) {
            //$query->set('author', get_current_user_id());
        }

        return $query;
    }

    /**
     * 列
     */
    public function postColumns($defaults)
    {
        $columns = array_merge($defaults, array(
            'title' => $defaults['title'],
            'author' => __('Author', 'mine-cloudvod'),
        ));

        $v = $columns['taxonomy-mcv_note_tag'];
        unset($columns['taxonomy-mcv_note_tag']);
        $columns['taxonomy-mcv_note_tag'] = $v;

        $v = $columns['date'];
        unset($columns['date']);
        $columns['date'] = $v;
        return $columns;
    }

    /**
     * 列的数据
     */
    public function postColumnsContent($column_name, $post_ID)
    {
        if ('note_tags' === $column_name) {
            $tags = get_the_terms($post_ID, 'mcv_note_tag');
            if (is_array($tags)) {
                foreach ($tags as $key => $tag) {
                    $tags[$key] = '<a href="?post_type=mcv_note&mcv_note_tag=' . $tag->term_id . '">' . $tag->name . '</a>';
                }
                echo wp_kses_post(implode(', ', $tags));
            }
        }
    }

    /**
     * 标题
     */
    public function videoTitle($title)
    {
        $screen = get_current_screen();
        if ($this->post_type == $screen->post_type) {
            $title = __('Add Title', 'mine-cloudvod');
        }
        return $title;
    }

    /**
     * Register post type
     */
    public function init()
    {
        register_taxonomy('mcv_note_tag', $this->post_type, [
            'labels'                => array(
                'name'                     => _x('Note Tags', 'post type general name', 'mine-cloudvod'),
                'singular_name'            => _x('Note Tag', 'post type singular name', 'mine-cloudvod'),
                'search_items'             => _x('Search Note Tags', 'admin menu', 'mine-cloudvod'),
                'popular_items'            => _x('Popular Note Tags', 'add new on admin bar', 'mine-cloudvod'),
            ),
            'label' => __('Tag', 'mine-cloudvod'),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ]);

        register_post_type(
            $this->post_type,
            array(
                'labels'                => array(
                    'name'                     => _x('CloudVod Note', 'post type general name', 'mine-cloudvod'),
                    'singular_name'            => _x('CloudVod Note', 'post type singular name', 'mine-cloudvod'),
                    'menu_name'                => _x('CloudVod Note', 'admin menu', 'mine-cloudvod'),
                    'name_admin_bar'           => _x('Note', 'add new on admin bar', 'mine-cloudvod'),
                    'add_new'                  => _x('Add New', 'Note', 'mine-cloudvod'),
                    'add_new_item'             => __('Add New Note', 'mine-cloudvod'),
                    'new_item'                 => __('New Note', 'mine-cloudvod'),
                    'edit_item'                => __('Edit Note', 'mine-cloudvod'),
                    'view_item'                => __('View Note', 'mine-cloudvod'),
                    'all_items'                => __('All Notes', 'mine-cloudvod'),
                    'search_items'             => __('Search Notes', 'mine-cloudvod'),
                    'not_found'                => __('No Notes found.', 'mine-cloudvod'),
                    'not_found_in_trash'       => __('No Notes found in Trash.', 'mine-cloudvod'),
                    'filter_items_list'        => __('Filter Notes list', 'mine-cloudvod'),
                    'items_list_navigation'    => __('Notes list navigation', 'mine-cloudvod'),
                    'items_list'               => __('Notes list', 'mine-cloudvod'),
                    'item_published'           => __('Note published.', 'mine-cloudvod'),
                    'item_published_privately' => __('Note published privately.', 'mine-cloudvod'),
                    'item_reverted_to_draft'   => __('Note reverted to draft.', 'mine-cloudvod'),
                    'item_scheduled'           => __('Note scheduled.', 'mine-cloudvod'),
                    'item_updated'             => __('Note updated.', 'mine-cloudvod'),
                ),
                'public'                => false,
                'show_ui'               => true,
                'show_in_menu'          => false,
                'rewrite'               => false,
                'show_in_rest'          => true,
                'rest_base'             => $this->post_type,
                'rest_controller_class' => 'WP_REST_Blocks_Controller',
                'map_meta_cap'          => true,
                'supports'              => [
                    'title',
                    'editor',
                ],
                'taxonomies' => ['mcv_note_tag'],
                'template' => [
                    
                ],
                'template_lock' => 'all'
            )
        );
    }

    /**
     * Modify admin query for tag
     *
     * @param \WP_Query $query
     * @return void
     */
    public function tagQuery($query)
    {
        global $pagenow;
        $taxonomy  = 'mcv_note_tag';
        $q_vars    = &$query->query_vars;
        if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $this->post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0) {
            $term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
            $q_vars[$taxonomy] = $term->slug;
            $query->is_search = false;
        }
    }
}
