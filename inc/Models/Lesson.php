<?php

namespace MineCloudvod\Models;
defined( 'ABSPATH' ) || exit;
class Lesson{
    public $post;
    private $post_type = MINECLOUDVOD_LMS['lesson_post_type'];

    public function __construct($id = 0){
        if (!empty($id)) {
            if (is_array($id)) $id = $id[0];
            $this->post = \get_post($id);
            return $this;
        }
        return $this;
    }

    /**
     * Get attributes properties
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property){
        return isset($this->post->$property) ? $this->post->$property : null;
    }

    public function create($args = []){
        return wp_insert_post(wp_parse_args($args, [
            'post_type' => $this->post_type
        ]));
    }

    public function fetch($args = [])
    {
        $args = wp_parse_args($args, [
            'post_type' => $this->post_type
        ]);

        return get_posts($args);
    }

    public function all($args = []){
        $args = wp_parse_args($args, [
            'post_type' => $this->post_type,
            'posts_per_page' => -1
        ]);

        return get_posts($args);
    }

    public function first($args = []){
        $fetched = $this->fetch(wp_parse_args($args, ['per_page' => 1]));
        return !empty($fetched[0]) ? new static($fetched[0]) : false;
    }
}
