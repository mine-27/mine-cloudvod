<?php
namespace MineCloudvod\RestApi\Agent;

if ( ! defined( 'ABSPATH' ) )
    exit;

/**
 * Agent REST 基类
 *
 * 独立 namespace：mine-cloudvod/agent/v1
 * 鉴权走 WordPress Application Passwords（WP 5.6+ 原生 Basic Auth）
 * permission_callback 复用 WP 原生能力检查，无需自建鉴权层
 *
 * 所有 Agent 端点继承此类，统一 namespace 与权限校验
 */
class Base{

    /**
     * REST 命名空间根
     * @var string
     */
    protected $namespace = 'mine-cloudvod';

    /**
     * 版本前缀（独立于 v1，避免污染现有用户端点）
     * @var string
     */
    protected $version = 'agent/v1';

    /**
     * 权限校验：要求 edit_posts 能力
     * Application Password 绑定的用户必须具备此能力
     * mcv_course / mcv_lesson / section 的 capability_type 均为 'post'，故 edit_posts 足够
     *
     * @return bool
     */
    public function permission_check(){
        return current_user_can( 'edit_posts' );
    }

    /**
     * 拼接完整 namespace
     * @return string
     */
    protected function root(){
        return "{$this->namespace}/{$this->version}";
    }

    /**
     * 统一成功响应
     *
     * @param mixed $data
     * @param int $status
     * @return \WP_REST_Response
     */
    protected function ok( $data = null, $status = 200 ){
        return new \WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ], $status );
    }

    /**
     * 统一失败响应
     *
     * @param string $message 错误信息
     * @param int $status HTTP 状态码
     * @param string $code 错误码
     * @return \WP_REST_Response
     */
    protected function fail( $message, $status = 400, $code = 'mcv_agent_error' ){
        return new \WP_REST_Response( [
            'success' => false,
            'code'    => $code,
            'message' => $message,
        ], $status );
    }

    /**
     * 把 WP_Post 序列化为 agent 友好的精简结构
     *
     * @param \WP_Post $post
     * @return array
     */
    protected function post_to_array( $post ){
        if( ! $post ) return null;
        return [
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'content'    => $post->post_content,
            'excerpt'    => $post->post_excerpt,
            'status'     => $post->post_status,
            'parent'     => (int) $post->post_parent,
            'menu_order' => (int) $post->menu_order,
            'type'       => $post->post_type,
            'author'     => (int) $post->post_author,
            'date'       => $post->post_date,
            'modified'   => $post->post_modified,
            'permalink'  => get_permalink( $post->ID ),
        ];
    }
}
