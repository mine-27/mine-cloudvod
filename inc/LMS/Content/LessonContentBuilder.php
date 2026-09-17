<?php
namespace MineCloudvod\LMS\Content;

if ( ! defined( 'ABSPATH' ) )
    exit;

/**
 * 课时内容拼装器
 *
 * 从 RestApi\LMS\Lesson::import_lessons 抽取的区块字符串拼装逻辑
 * 根据 video_source 生成古腾堡区块标记字符串，写入 mcv_lesson 的 post_content
 *
 * 支持的视频源（与 import_lessons 完全对齐）：
 *   - direct : 直链视频（走 aliplayer 或 dplayer，取决于后台设置）
 *   - embed   : 外链嵌入（embed-video 区块）
 *   - alivod  : 阿里云 VOD（aliyun-vod 区块）
 *   - tcvod   : 腾讯云 VOD（tc-vod 区块）
 *   - qiniukodo : 七牛 Kodo（qiniu 区块）
 *   - dogecloud : 多吉云（doge 区块）
 *   - huaweivod : 华为云 VOD（huawei-vod 区块）
 *   - bunnynet  : BunnyNet（bunny 区块）
 *   - cloudflare: Cloudflare Stream（cloudflare 区块）
 */
class LessonContentBuilder{

    /**
     * 根据视频源构建 post_content
     *
     * @param string $video_source 视频源标识
     * @param array  $params       视频参数（各源不同）
     * @return array ['content' => string, 'duration' => ['minute'=>int,'second'=>int]]
     */
    public function build( $video_source, $params = [] ){
        $params = is_array( $params ) ? $params : [];
        $duration = [ 'minute' => 0, 'second' => 0 ];

        switch( $video_source ){

            case 'direct':
                $content = $this->wrap_block( $this->direct_block( $params ) );
                break;

            case 'embed':
                $type = isset( $params['embed_type'] ) ? sanitize_text_field( $params['embed_type'] ) : '';
                $src  = isset( $params['src'] ) ? esc_url_raw( $params['src'] ) : '';
                $content = $this->wrap_block(
                    '<!-- wp:mine-cloudvod/embed-video {"src":"' . esc_attr( $src ) . '","type":"' . esc_attr( $type ) . '"} /-->'
                );
                break;

            case 'alivod':
                $video_id = isset( $params['video_id'] ) ? sanitize_text_field( $params['video_id'] ) : '';
                if( ! $video_id ) return $this->empty_result();
                $content = $this->wrap_block(
                    '<!-- wp:mine-cloudvod/aliyun-vod {"videoId":"' . esc_attr( $video_id ) . '"} /-->'
                );
                $duration = $this->parse_duration( $params['duration'] ?? 0 );
                break;

            case 'tcvod':
                $video_id = isset( $params['video_id'] ) ? sanitize_text_field( $params['video_id'] ) : '';
                $cover    = isset( $params['cover'] ) ? esc_url_raw( $params['cover'] ) : '';
                if( ! $video_id ) return $this->empty_result();
                $content = $this->wrap_block(
                    '<!-- wp:mine-cloudvod/tc-vod {"videoId":"' . esc_attr( $video_id ) . '","cover":"' . esc_attr( $cover ) . '"} /-->'
                );
                $duration = $this->parse_duration( $params['duration'] ?? 0 );
                break;

            case 'qiniukodo':
                $key = isset( $params['key'] ) ? sanitize_text_field( $params['key'] ) : '';
                if( ! $key ) return $this->empty_result();
                $content = $this->wrap_block(
                    '<!-- wp:mine-cloudvod/qiniu {"key":"' . esc_attr( $key ) . '"} /-->'
                );
                $duration = $this->parse_duration( $params['duration'] ?? 0 );
                break;

            case 'dogecloud':
                $vcode = isset( $params['vcode'] ) ? sanitize_text_field( $params['vcode'] ) : '';
                $uid   = isset( $params['uid'] ) ? sanitize_text_field( $params['uid'] ) : '';
                if( ! $vcode ) return $this->empty_result();
                $content = $this->wrap_block(
                    '<!-- wp:mine-cloudvod/doge {"vcode":"' . esc_attr( $vcode ) . '","userId":' . intval( $uid ) . '} /-->'
                );
                $duration = $this->parse_duration( $params['duration'] ?? 0 );
                break;

            case 'huaweivod':
                $video_id = isset( $params['video_id'] ) ? sanitize_text_field( $params['video_id'] ) : '';
                if( ! $video_id ) return $this->empty_result();
                $content = $this->wrap_block(
                    '<!-- wp:mine-cloudvod/huawei-vod {"videoId":"' . esc_attr( $video_id ) . '"} /-->'
                );
                $duration = $this->parse_duration( $params['duration'] ?? 0 );
                break;

            case 'bunnynet':
                $vid   = isset( $params['vid'] ) ? sanitize_text_field( $params['vid'] ) : '';
                $libid = isset( $params['libid'] ) ? sanitize_text_field( $params['libid'] ) : '';
                if( ! $vid || ! $libid ) return $this->empty_result();
                $content = $this->wrap_block(
                    '<!-- wp:mine-cloudvod/bunny {"vid":"' . esc_attr( $vid ) . '","libid":"' . esc_attr( $libid ) . '"} /-->'
                );
                $duration = $this->parse_duration( $params['duration'] ?? 0 );
                break;

            case 'cloudflare':
                $vid = isset( $params['vid'] ) ? sanitize_text_field( $params['vid'] ) : '';
                if( ! $vid ) return $this->empty_result();
                $content = $this->wrap_block(
                    '<!-- wp:mine-cloudvod/cloudflare {"vid":"' . esc_attr( $vid ) . '"} /-->'
                );
                $duration = $this->parse_duration( $params['duration'] ?? 0 );
                break;

            default:
                // 未知源，返回空内容，调用方决定如何处理
                return $this->empty_result();
        }

        return [ 'content' => $content, 'duration' => $duration ];
    }

    /**
     * 直链视频区块：根据后台设置选 aliplayer 或 dplayer
     * 对齐 import_lessons 第 154-159 行逻辑
     */
    private function direct_block( $params ){
        $source = isset( $params['src'] ) ? esc_attr( $params['src'] ) : '';
        $player = 'aliplayer';
        if( isset( MINECLOUDVOD_SETTINGS['mcv_lms_course']['backend']['player'] )
            && MINECLOUDVOD_SETTINGS['mcv_lms_course']['backend']['player'] == '2' ){
            $player = 'dplayer';
        }
        return '<!-- wp:mine-cloudvod/' . $player . ' {"source":"' . $source . '"} /-->';
    }

    /**
     * 用 block-container 包裹区块（与 import_lessons 一致）
     */
    private function wrap_block( $inner ){
        return '<!-- wp:mine-cloudvod/block-container --><div class="wp-block-mine-cloudvod-block-container">'
             . $inner
             . '</div><!-- /wp:mine-cloudvod/block-container -->';
    }

    /**
     * 秒数转 minute/second 数组
     */
    private function parse_duration( $seconds ){
        $seconds = intval( $seconds );
        return [
            'minute' => floor( $seconds / 60 ),
            'second' => $seconds % 60,
        ];
    }

    private function empty_result(){
        return [ 'content' => '', 'duration' => [ 'minute' => 0, 'second' => 0 ] ];
    }
}
