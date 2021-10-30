<?php
MCSF::createSection( $prefix, array(
    'parent'      => 'tencentvod',
    'title'       => __('Video head and foot', 'mine-cloudvod'),//'片头片尾',
    'icon'        => 'fas fa-user-plus',
    'description' => '',
    'fields'      => array(
        array(
        'type'    => 'submessage',
        'style'   => 'danger',
        'content' => __('This function consumes transcoding time and is not recommended', 'mine-cloudvod'),//此功能会消耗转码时长，不推荐使用
        ),
        array(
            'id'        => 'tcvodpiantou',
            'type'      => 'fieldset',
            'title'     => __('Video head', 'mine-cloudvod'),//'片头',
            'subtitle'     => __('When enabled, the video file will be automatically attached before the uploaded video', 'mine-cloudvod'),//'启用后会自动在上传的视频前接上片头视频文件',
            'fields'    => array(
                array(
                    'id'    => 'status',
                    'type'  => 'switcher',
                    'title' => __('State', 'mine-cloudvod'),//'状态',
                    'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                    'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                    'default' => false
                ),
                array(
                  'id'          => 'fileid',
                  'type'        => 'text',
                  'title'       => '片头FileId',
                  'placeholder' => '请填写片头FileId',
                ),
            )
        ),
        array(
            'id'        => 'tcvodpianwei',
            'type'      => 'fieldset',
            'title'     => '片尾',
            'subtitle'     => '启用后会自动在上传的视频后接上片尾视频文件',
            'fields'    => array(
                array(
                    'id'    => 'status',
                    'type'  => 'switcher',
                    'title' => __('State', 'mine-cloudvod'),//'状态',
                    'text_on'    => __('Enable', 'mine-cloudvod'),//'启用',
                    'text_off'   => __('Disable', 'mine-cloudvod'),//'禁用',
                    'default' => false
                ),
                array(
                  'id'          => 'fileid',
                  'type'        => 'text',
                  'title'       => '片尾FileId',
                  'placeholder' => '请填写片尾FileId',
                ),
            )
        ),
    )
));