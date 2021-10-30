<?php
namespace MineCloudvod\Ability;

class PostType
{
    protected $post_type = 'mcv_video';

    public function __construct()
    {
        global $wp_version;

        add_action('init', [$this, 'init']);

        if (version_compare($wp_version, '5.8', ">=")) {
            add_filter("allowed_block_types_all", [$this, 'allowedTypes'], 10, 2);
        } else {
            add_filter("allowed_block_types", [$this, 'allowedTypesDeprecated'], 10, 2);
        }

        add_filter('enter_title_here', [$this, 'videoTitle']);

        // 列表页
        add_filter("manage_{$this->post_type}_posts_columns", [$this, 'postColumns'], 1);
        add_action("manage_{$this->post_type}_posts_custom_column", [$this, 'postColumnsContent'], 10, 2);

        // 标签筛选
        add_action('restrict_manage_posts', [$this, 'tagFilter']);
        add_action('parse_query', [$this, 'tagQuery']);

        // 强制使用区块编辑器
        add_action('use_block_editor_for_post', [$this, 'forceGutenberg'], 999, 2);

        // 只显示mcv_video的文章
        add_filter('pre_get_posts', [$this, 'limitMcvVideoPosts']);
    }

    /**
     * 只显示mcv_video的文章
     */
    public function limitMcvVideoPosts($query)
    {
        global $pagenow, $typenow;

        if ('edit.php' != $pagenow || !$query->is_admin || $this->post_type !== $typenow) {
            return $query;
        }

        if (!current_user_can('edit_others_posts')) {
            $query->set('author', get_current_user_id());
        }

        return $query;
    }

    /**
     * 强制使用区块编辑器
     */
    public function forceGutenberg($use, $post)
    {
        if ($this->post_type === $post->post_type) {
            return true;
        }

        return $use;
    }

    /**
     * 列
     */
    public function postColumns($defaults)
    {
        $columns = array_merge($defaults, array(
            'title' => $defaults['title'],
            'shortcode' => __('Shortcode', 'mine-cloudvod'),
            'php_function' => __('PHP Function', 'mine-cloudvod'),
        ));

        $v = $columns['taxonomy-mcv_video_tag'];
        unset($columns['taxonomy-mcv_video_tag']);
        $columns['taxonomy-mcv_video_tag'] = $v;

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
        if ('shortcode' === $column_name) {
            echo '<code class="mcv_copy" data-clipboard-text="[mine_cloudvod id=' . (int) $post_ID . ']">[mine_cloudvod id=' . (int) $post_ID . ']</code>';
        }
        if ('php_function' === $column_name) {
            echo '<code class="mcv_copy" data-clipboard-text="mine_cloudvod(' . (int) $post_ID . ')">mine_cloudvod(' . (int) $post_ID . ')</code>';
        }
        if ('video_tags' === $column_name) {
            $tags = get_the_terms($post_ID, 'mcv_video_tag');
            if (is_array($tags)) {
                foreach ($tags as $key => $tag) {
                    $tags[$key] = '<a href="?post_type=mcv_video&mcv_video_tag=' . $tag->term_id . '">' . $tag->name . '</a>';
                }
                echo implode(', ', $tags);
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
     * 允许加载的区块
     */
    public function allowedTypes($allowed_block_types, $block_editor_content)
    {
        if (!empty($block_editor_content->post->post_type)) {
            if ($block_editor_content->post->post_type === $this->post_type) {
                return [
                    'mine-cloudvod/aliyun-vod',
                    'mine-cloudvod/tc-vod',
                    'mine-cloudvod/video-playlist',
                    'mine-cloudvod/embed-video',
                    'mine-cloudvod/aliplayer',
                ];
            }
        }

        return $allowed_block_types;
    }

    /**
     * 允许加载的区块<5.8
     */
    public function allowedTypesDeprecated($allowed_block_types, $post)
    {
        if ($post->post_type !== $this->post_type) {
            return $allowed_block_types;
        }

        return [
            'mine-cloudvod/aliyun-vod',
            'mine-cloudvod/tc-vod',
            'mine-cloudvod/video-playlist',
            'mine-cloudvod/embed-video',
            'mine-cloudvod/aliplayer',
        ];
    }

    /**
     * Register post type
     */
    public function init()
    {
        add_action('admin_enqueue_scripts',	function(){
            wp_enqueue_script('mcv_clipboard', 'https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js',  [], MINECLOUDVOD_VERSION , true );
            wp_add_inline_script('mcv_clipboard', 'var clipboard = new ClipboardJS(".mcv_copy");clipboard.on("success", function(e) {var cur = jQuery(".mcv_copy[data-clipboard-text=\'"+e.text+"\']");cur.css("color","green");cur.after("<span>'.__('Copied', 'mine-cloudvod').'</span>");setTimeout(function() {cur.next("span").remove();}, 2000);e.clearSelection();});');
        });
        register_taxonomy('mcv_video_tag', $this->post_type, [
            'labels'                => array(
                'name'                     => _x('Video Tags', 'post type general name', 'mine-cloudvod'),
                'singular_name'            => _x('Video Tag', 'post type singular name', 'mine-cloudvod'),
                'search_items'             => _x('Search Video Tags', 'admin menu', 'mine-cloudvod'),
                'popular_items'            => _x('Popular Video Tags', 'add new on admin bar', 'mine-cloudvod'),
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
                    'name'                     => _x('CloudVod Hub', 'post type general name', 'mine-cloudvod'),
                    'singular_name'            => _x('Mine CloudVod', 'post type singular name', 'mine-cloudvod'),
                    'menu_name'                => _x('Mine CloudVod', 'admin menu', 'mine-cloudvod'),
                    'name_admin_bar'           => _x('Video', 'add new on admin bar', 'mine-cloudvod'),
                    'add_new'                  => _x('Add New', 'Video', 'mine-cloudvod'),
                    'add_new_item'             => __('Add New Video', 'mine-cloudvod'),
                    'new_item'                 => __('New Video', 'mine-cloudvod'),
                    'edit_item'                => __('Edit Video', 'mine-cloudvod'),
                    'view_item'                => __('View Video', 'mine-cloudvod'),
                    'all_items'                => __('All Videos', 'mine-cloudvod'),
                    'search_items'             => __('Search Videos', 'mine-cloudvod'),
                    'not_found'                => __('No Videos found.', 'mine-cloudvod'),
                    'not_found_in_trash'       => __('No Videos found in Trash.', 'mine-cloudvod'),
                    'filter_items_list'        => __('Filter Videos list', 'mine-cloudvod'),
                    'items_list_navigation'    => __('Videos list navigation', 'mine-cloudvod'),
                    'items_list'               => __('Videos list', 'mine-cloudvod'),
                    'item_published'           => __('Video published.', 'mine-cloudvod'),
                    'item_published_privately' => __('Video published privately.', 'mine-cloudvod'),
                    'item_reverted_to_draft'   => __('Video reverted to draft.', 'mine-cloudvod'),
                    'item_scheduled'           => __('Video scheduled.', 'mine-cloudvod'),
                    'item_updated'             => __('Video updated.', 'mine-cloudvod'),
                ),
                'public'                => false,
                'show_ui'               => true,
                'show_in_menu'          => false,
                'rewrite'               => false,
                'show_in_rest'          => true,
                'rest_base'             => 'mcv_video',
                'rest_controller_class' => 'WP_REST_Blocks_Controller',
                'map_meta_cap'          => true,
                'supports'              => [
                    'title',
                    'editor',
                ],
                'taxonomies' => ['mcv_video_tag'],
                'template' => [
                    ['mine-cloudvod/block-container']
                ],
                'template_lock' => 'all'
            )
        );
    }

    /**
     * Adds a tag filter dropdown
     *
     * @return void
     */
    public function tagFilter()
    {
        global $typenow;

        $taxonomy  = 'mcv_video_tag';

        if ($typenow !== $this->post_type) {
            return;
        }

        $selected      = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
        $info_taxonomy = get_taxonomy($taxonomy);

        wp_dropdown_categories(array(
            'show_option_all' => sprintf(__('Show all %s', 'mine-cloudvod'), $info_taxonomy->label),
            'taxonomy'        => $taxonomy,
            'name'            => $taxonomy,
            'orderby'         => 'name',
            'selected'        => $selected,
            'show_count'      => true,
            'hide_empty'      => true,
        ));
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

        $taxonomy  = 'mcv_video_tag';

        $q_vars    = &$query->query_vars;
        if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $this->post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0) {
            $term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
            $q_vars[$taxonomy] = $term->slug;
        }
    }
}
