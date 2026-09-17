<?php
// phpcs:ignoreFile -- 第三方库，豁免 PHPCS 检查
 if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Array search key & value
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
// if ( ! function_exists( 'csf_array_search' ) ) {
//   function csf_array_search( $array, $key, $value ) {

//     $results = array();

//     if ( is_array( $array ) ) {
//       if ( isset( $array[$key] ) && $array[$key] == $value ) {
//         $results[] = $array;
//       }

//       foreach ( $array as $sub_array ) {
//         $results = array_merge( $results, csf_array_search( $sub_array, $key, $value ) );
//       }

//     }

//     return $results;

//   }
// }

/**
 *
 * Between Microtime
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
// if ( ! function_exists( 'csf_timeout' ) ) {
//   function csf_timeout( $timenow, $starttime, $timeout = 30 ) {
//     return ( ( $timenow - $starttime ) < $timeout ) ? true : false;
//   }
// }

/**
 *
 * Check for wp editor api
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! function_exists( 'csf_wp_editor_api' ) ) {
  function csf_wp_editor_api() {
    global $wp_version;
    return version_compare( $wp_version, '4.8', '>=' );
  }
}


if ( ! function_exists( 'mcsf_allowed_html' ) ) {
  function mcsf_allowed_html() {
    $allowed_html = [
      'br'=>[],
      'small'=>[],
      'p'=>[],
      'i'=>[
        'class' => [],
      ],
      'span'=>[
        'class' => [],
        'style' => [],
        'data-url' =>[],
      ],
      'strong'=>[],
      'em'=>[],
      'a'=>[
        'href' => [],
        'data-op'  => [],
        'data-tid'  => [],
        'class' => [],
        'target' => [],
        'id' =>[],
      ],
      'script'=>[
        'src'=>[]
      ],
      'div' => [
        'class' => [],
        'style' => [],
        'data-controller' =>[],
        'data-condition' => [],
        'data-value' => [],
        'id' =>[],
      ],
      'button' => [
        'class' => [],
        'style' => [],
        'onclick' =>[],
      ],
      'input' => [
        'id' => [],
        'class' => [],
        'style' => [],
        'value' =>[],
        'type' =>[],
      ],
      'table' => [
        'class' => [],
        'style' => [],
      ],
      'tbody' => [
        'class' => [],
        'style' => [],
      ],
      'thead' => [
        'class' => [],
        'style' => [],
      ],
      'tr' => [
        'class' => [],
        'style' => [],
      ],
      'td' => [
        'class' => [],
        'id' => [],
        'colspan' => [],
      ],
      'ul' => [
        'class' => [],
        'style' => [],
      ],
      'li' => [
        'class' => [],
        'style' => [],
      ],
      'ol' => [
        'class' => [],
        'style' => [],
      ],
    ];
    return $allowed_html;
  }
}
