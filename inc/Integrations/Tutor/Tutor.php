<?php

namespace MineCloudvod\Integrations\Tutor;

class Tutor
{
    public function __construct()
    {
        add_filter('tutor_course/single/video', [$this, 'renderVideo']);
        add_filter('tutor_lesson/single/video', [$this, 'renderVideo']);
    }

    public function renderVideo($output)
    {
        if (!strpos($output, '[mine_cloudvod')) {
            return $output;
        }

        preg_match('/\[mine_cloudvod.*?\]/i', $output, $mv);

        if (empty($mv[0])) {
            return $output;
        }

        return \do_shortcode($mv[0]);
    }
}
