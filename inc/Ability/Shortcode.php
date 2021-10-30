<?php
namespace MineCloudvod\Ability;

class Shortcode
{
  public function __construct()
  {
    add_shortcode('mine_cloudvod', [$this, 'shortcode'], 1, 2);
  }

  public function shortcode($atts, $content)
  {
    global $mcv_block_ajax_from;
    $mcv_block_ajax_from = false;
    if(isset($atts['from'])){
      $mcv_block_ajax_from = $atts['from'];
    }
    if ($atts['id']) {
        $post = get_post($atts['id']);
        $blocks = parse_blocks($post->post_content);
        $out = '';
        foreach ($blocks as $block) {
            $out .= render_block($block);
        }
        return $out;
    }
  }
}
