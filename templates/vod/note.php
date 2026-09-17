<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容
 
/**
 * template name: Aliplayer Note Component
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if(!is_user_logged_in()) exit(esc_html__('Login first, please.', 'mine-cloudvod'));
global $current_user;
$uid = $current_user->ID;
$pid = sanitize_text_field(wp_unslash($_GET['pid'] ?? 0)); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读笔记文章 ID，且已校验登录态
if(!$pid) exit;
wp_enqueue_style('mcv_aliplayer_css', MINECLOUDVOD_URL."/static/css/note.css", array(), MINECLOUDVOD_VERSION, false);
show_admin_bar( false );
add_filter( 'show_admin_bar', '__return_false' );
remove_action('wp_head', '_admin_bar_bump_cb');
wp_head();
function add_mcv_note_tinymce_plugin($plugins) {
    $plugins['mcv_note_screenshot'] = MINECLOUDVOD_URL.'/static/tinymce/note_screenshot.js';
    $plugins['mcv_note_flag'] = MINECLOUDVOD_URL.'/static/tinymce/note_flag.js';
    $plugins['mcv_note_save'] = MINECLOUDVOD_URL.'/static/tinymce/note_save.js';
    return $plugins;
}
add_filter("mce_external_plugins",	"add_mcv_note_tinymce_plugin", 9999);
add_filter('tiny_mce_before_init','mcv_editor_dynamic_styles');
function mcv_editor_dynamic_styles( $mceInit ) {
    $styles = '.time-tag-item {transition: .2s;background: #e6f4ff;background-image: initial;background-position-x: initial;background-position-y: initial;background-size: initial;background-repeat-x: initial;background-repeat-y: initial;background-attachment: initial;background-origin: initial;background-clip: initial;background-color: rgb(230, 244, 255);border-radius: 12px;height: 22px;line-height: 19px;display: inline-block;padding: 0 12px;font-size: 12px;color: #2392e5;border: 1px solid #e6f4ff;cursor: pointer;font-weight: 700;display: -ms-inline-flexbox;display: inline-flex;}';
    if ( isset( $mceInit['content_style'] ) ) {
        $mceInit['content_style'] .= ' ' . $styles . ' ';
    } else {
        $mceInit['content_style'] = $styles . ' ';
    }
    return $mceInit;
}

$args = array(
    'tinymce'       => array(
        'toolbar1'      => 'align,forecolor,bold,italic,underline,bullist,numlist,mcv_note_screenshot,mcv_note_flag,mcv_note_save',
        'toolbar2'      => '',
        'toolbar3'      => '',
    ),
    'media_buttons' => false,
    'quicktags' => false,
    'textarea_rows' => 19,
    'editor_css'    => '<style>div.mce-toolbar-grp>div{padding:0;width:404px;}.mce-toolbar .mce-btn-group .mce-btn{margin:0;}#mceu_7-button{background:#f79e00;}#mceu_8-button{background:#2392e5;}#mceu_9-button{background:#ea4335;}
    @media screen and (max-width: 424px) {div.mce-toolbar-grp>div{width:auto;}}}
    #mcv_note_editor_ifr .time-tag-item {
        transition: .2s;
        background: #e6f4ff;
        background-image: initial;
        background-position-x: initial;
        background-position-y: initial;
        background-size: initial;
        background-repeat-x: initial;
        background-repeat-y: initial;
        background-attachment: initial;
        background-origin: initial;
        background-clip: initial;
        background-color: rgb(230, 244, 255);
        border-radius: 12px;
        height: 22px;
        line-height: 19px;
        display: inline-block;
        padding: 0 12px;
        font-size: 12px;
        color: #2392e5;
        border: 1px solid #e6f4ff;
        cursor: pointer;
        font-weight: 700;
        display: -ms-inline-flexbox;
        display: inline-flex;
    }
    </style>',
);
$Note = new MineCloudvod\Ability\Note();
$vpost = $Note->getPostByMeta($pid, $uid);
$content = '';
if($vpost){
    $content = $vpost->post_content;
}
$fpost = get_post($pid);
echo '<script>var ptitle="'.esc_html($fpost->post_title).'";</script>';
$editor_id = 'mcv_note_editor';
wp_editor( $content, $editor_id, $args );
wp_footer();