<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- mcv_ 前缀为历史遗留，保留兼容

declare( strict_types=1 );
defined( 'ABSPATH' ) || exit;
global $post;
if( !isset( $attributes['questions'] ) ) return;

$answers = [];
$user_id = get_current_user_id();
if( $user_id ){
    $answers = get_user_meta( $user_id, 'mcv_quiz_answers_'.$post->ID, true );
}

$questions = $attributes['questions'];
$questionsShow = [];
$refAnswers = [];
foreach( $questions as $q ){
    $tmp = [
        'type' => $q['type'],
        'title' => $q['title'],
    ];
    switch( $q['type'] ){
        case 'select':
            if( is_array( $q['options'] ) ){
                $rightOpts = array_filter( $q['options'], function($item){return $item['right'];} );
                $rans = []; // 正确答案
                foreach( $q['options'] as $idx => $opt ){
                    if( $opt['right'] ) $rans[] = $idx;
                    $tmp['options'][] = [
                        'title' => $opt['title']
                    ];
                }
            }
            $tmp['tip'] = $q['tip'];
            if( count( $rans ) <= 1 ){
                $tmp['single'] = true;
                $refAnswers[] = isset($rans[0])?$rans:[0];
            }
            else{
                $tmp['single'] = false;
                $refAnswers[] = $rans;
            }
            break;
        case 'boolean':
            $tmp = $q;
            $refAnswers[] = $tmp['answer'];
            break;
        case 'textarea':
            $tmp = $q;
            $refAnswers[] = $tmp['tip'];
            break;

    }
    unset( $tmp['answer'] );
    if( empty($answers) ) unset( $tmp['tip'] );
    $questionsShow[] = $tmp;
}

$mcv_quiz_data = [
    'questions' => $questionsShow,
    'answers' => $answers,
];
if( $answers ){
    $mcv_quiz_data['refAnswers'] = $refAnswers;
}
wp_localize_script( 'mine-cloudvod-quiz-view-script', 'mcv_quiz_data', $mcv_quiz_data);

?>
<div id="mcv_quiz_wrap"></div>