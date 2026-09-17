<?php
namespace MineCloudvod\RestApi;
use MineCloudvod\Models\McvVideo;

class PostTypeVideo{
    protected $namespace = 'mine-cloudvod';
    protected $version = 'v1';
    protected $base = 'posttype/video';
    private $_mcvVideo;

    public function __construct(){
        $this->_mcvVideo     = new McvVideo();
        $this->register();
        add_action('admin_head-edit.php',[$this, 'addCustomImportButton']);
    }

    /**
     * Register controller
     *
     * @return void
     */
    public function register(){
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register presets routes
     *
     * @return void
     */
    public function register_routes(){
        /**
         * import videos
         */
        register_rest_route("{$this->namespace}/{$this->version}", '/' . $this->base . '/import', [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'import_videos'],
                'permission_callback' => [$this, 'read_files_permissions_check'],
                'args'                => [
                    'from' => [
                        'type' => 'string'
                    ],
                    'tag' => [
                        'type' => 'string'
                    ],
                    'videos[]' => [
                        'type' => 'string',
                    ]
                ]
        ]);

    }
    public function read_files_permissions_check(){
        return current_user_can('edit_posts');
    }

    /**
     * Fetch videos from aliyun oss
     * 
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function import_videos(\WP_REST_Request $request){
        $from = sanitize_text_field($request['from']);
        $tag = sanitize_text_field($request['tag']);
        $videos = $request['videos'];
        $posts = [];
        switch($from){
            case 'videourl':
                $vi = 0;
                foreach($videos as $video){
                    // $video = sanitize_text_field($video);
                    if(!is_array($video)) continue;
                    $vi++;
                    $post_content = '';
                    switch($video[0]){
                        case 'direct':
                            $post_content = '<!-- wp:mine-cloudvod/block-container -->
<div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/dplayer {"source":"'.$video[1].'"} /--></div>
<!-- /wp:mine-cloudvod/block-container -->';
                            break;
                        default:
                            $post_content = '<!-- wp:mine-cloudvod/block-container -->
<div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/embed-video {"src":"'.$video[1].'","type":"'.$video[0].'"} /--></div>
<!-- /wp:mine-cloudvod/block-container -->';
                            break;
                    }
                    $ptitle = '';
                    if( count($video) > 2 ) $ptitle = $video[2];
                    elseif( $tag ){
                        $ptitle = $tag . '-' . $vi;
                    }
                    
                    $post = array(
                        'post_title'    => $ptitle,
                        'post_content'  => $post_content,
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag)
                    );
                    $posts[] = $post;
                }
                break;
            case 'alivod':
                $vi = 0;
                foreach($videos as $video){
                    $videoId = sanitize_text_field($video['videoId']);
                    $videoTitle = sanitize_text_field($video['title']);
                    if(!$videoId) continue;
                    $vi++;
                    $post = array(
                        'post_title'    => $videoTitle??$tag . '-' . $vi,
                        'post_content'  => '<!-- wp:mine-cloudvod/block-container -->
                        <div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/aliyun-vod {"videoId":"'.$videoId.'"} /--></div>
                        <!-- /wp:mine-cloudvod/block-container -->',
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag)
                    );
                    $posts[] = $post;
                }
                break;
            case 'tcvod':
                $vi = 0;
                foreach($videos as $video){
                    $videoId = sanitize_text_field($video['videoId']);
                    $cover = sanitize_text_field($video['CoverUrl']);
                    $videoTitle = sanitize_text_field($video['Name']);
                    if(!$videoId) continue;
                    $vi++;
                    $post = array(
                        'post_title'    => $videoTitle??$tag . '-' . $vi,
                        'post_content'  => '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/tc-vod {"videoId":"'.$videoId.'","cover":"'.$cover.'"} /--></div><!-- /wp:mine-cloudvod/block-container -->',
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag),
                    );
                    $posts[] = $post;
                }
                break;
            case 'qiniukodo':
                $vi = 0;
                foreach($videos as $video){
                    $videoId = sanitize_text_field($video['videoId']);
                    $videoTitle = sanitize_text_field($video['title']);
                    if(!$videoId) continue;
                    $vi++;
                    $post = array(
                        'post_title'    => $videoTitle??$tag . '-' . $vi,
                        'post_content'  => '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/qiniu {"key":"'.$videoId.'"} /--></div><!-- /wp:mine-cloudvod/block-container -->',
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag),
                    );
                    $posts[] = $post;
                }
                break;
            case 'dogecloud':
                $vi = 0;
                foreach($videos as $video){
                    $videoId = sanitize_text_field($video['videoId']);
                    $videoTitle = sanitize_text_field($video['title']);
                    if( !$videoTitle ) $videoTitle = $tag . '-' . $vi;
                    else{
                        $tmp = explode( '/', $videoTitle );
                        $videoTitle = $tmp[count( $tmp ) - 1];
                    }
                    if(!$videoId) continue;
                    $uid = sanitize_text_field($video['uid']);
                    $vi++;
                    $post = array(
                        'post_title'    => $videoTitle,
                        'post_content'  => '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container"><!-- wp:mine-cloudvod/doge {"vcode":"'.$videoId.'","userId":'.$uid.'} /--></div><!-- /wp:mine-cloudvod/block-container -->',
                        'post_status'   => 'publish',
                        'post_author'   => get_current_user_id(),
                        'tags_input'    => explode(",", $tag),
                    );
                    $posts[] = $post;
                }
                break;

        }
        if(count($posts) == 0){
            return new \WP_Error('cant-trash', __('no videos', 'mine-cloudvod'), ['status' => 500]);
        }
        $result = [];
        foreach($posts as $post){
            $pid = $this->_mcvVideo->create($post);
            wp_set_object_terms($pid, explode(",", $tag), 'mcv_video_tag', false);
            $result['num']++;
            update_post_meta($pid, 'sort_no', $result['num']);
        }

        return rest_ensure_response($result);
    }
    public function addCustomImportButton(){
        global $current_screen;
        if ('mcv_video' != $current_screen->post_type) {
            return;
        }
        wp_enqueue_style('wp-components');
        wp_enqueue_style('mine_cloudvod-aliyunvod-block-editor-css');
        wp_enqueue_script('mine_cloudvod-import-js');
    
    }
}
