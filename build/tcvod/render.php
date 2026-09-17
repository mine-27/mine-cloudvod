<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
// if( !$attributes['videoId'] ) return;
global $ActiveAddons;

$player = MINECLOUDVOD_SETTINGS['tcvod']['player']??'default';
// if($player == 'dplayer'){
//     $pcfg = $attributes['pcfg'] ?? MINECLOUDVOD_SETTINGS['tcvod']['plyrconfig'] ?? 'default';
//     $taskid = $attributes['taskid'] ?? MINECLOUDVOD_SETTINGS['tcvod']['transcode'] ?? 'default';
//     $appID = $attributes['appId'] ?? MINECLOUDVOD_SETTINGS['tcvod']['appid'] ?? '0';
//     $psign = $mcv_classes->Tcvod->mcv_generate_psign(0, $appID, $attributes['videoId'], $pcfg, $taskid);
//     $dplayer_attrs = [
//         'cover'     => $attributes['cover'] ?? false,
//         'captions'  => $attributes['captions'] ?? false,
//         'markers'   => $attributes['markers'] ?? false,
//         'height'   => $attributes['height'] ?? false,
//         'minecloudvod' => [
//             'tcvod'     => [
//                 'fileID' => $attributes['videoId'],
//                 'appID' => $appID,
//                 'psign' => $psign['psign'],
//                 'type'  => 'tcvod',
//                 'defaultQuality' => 0
//             ],
//         ],
//     ];
//     if($psign['sprite']){
//         $dplayer_attrs['minecloudvod']['thumbnails_rowcol'] = [
//             'row'   => $psign['sprite']['RowCount'],
//             'col'   => $psign['sprite']['ColumnCount'],
//         ];
//     }
//     wp_register_style( 'mcv-inline-style', false );
//     wp_enqueue_style( 'mcv-inline-style' );
//     wp_add_inline_style( 'mcv-inline-style', 'img.tcp-vtt-thumbnail-img{max-width:unset !important;max-height:unset !important;}'.html_entity_decode(MINECLOUDVOD_SETTINGS['tcplayercss']) );

//     $video = do_blocks('<!-- wp:mine-cloudvod/dplayer '.json_encode($dplayer_attrs).' /-->');
    
//     echo $video;
// }
// else{
    $tcplayer = $ActiveAddons['qcloud']->Tcplayer;
    
    $video_safe = $ActiveAddons['qcloud']->Tcplayer->mcv_block_tcplayer($block->parsed_block);
    echo $video_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Tcplayer 区块渲染输出已转义
// }