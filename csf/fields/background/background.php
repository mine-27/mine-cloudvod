<?php
// phpcs:ignoreFile -- 第三方库，豁免 PHPCS 检查
 if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: background
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'MCSF_Field_background' ) ) {
  class MCSF_Field_background extends MCSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args                             = wp_parse_args( $this->field, array(
        'background_color'              => true,
        'background_image'              => true,
        'background_position'           => true,
        'background_repeat'             => true,
        'background_attachment'         => true,
        'background_size'               => true,
        'background_origin'             => false,
        'background_clip'               => false,
        'background_blend_mode'         => false,
        'background_gradient'           => false,
        'background_gradient_color'     => true,
        'background_gradient_direction' => true,
        'background_image_preview'      => true,
        'background_auto_attributes'    => false,
        'compact'                       => false,
        'background_image_library'      => 'image',
        'background_image_placeholder'  => esc_html__( 'Not selected', 'mine-cloudvod' ),
      ) );

      if ( $args['compact'] ) {
        $args['background_color']           = false;
        $args['background_auto_attributes'] = true;
      }

      $default_value                    = array(
        'background-color'              => '',
        'background-image'              => '',
        'background-position'           => '',
        'background-repeat'             => '',
        'background-attachment'         => '',
        'background-size'               => '',
        'background-origin'             => '',
        'background-clip'               => '',
        'background-blend-mode'         => '',
        'background-gradient-color'     => '',
        'background-gradient-direction' => '',
      );

      $default_value = ( ! empty( $this->field['default'] ) ) ? wp_parse_args( $this->field['default'], $default_value ) : $default_value;

      $this->value = wp_parse_args( $this->value, $default_value );

      echo $this->field_before();

      echo '<div class="csf--background-colors">';

      //
      // Background Color
      if ( ! empty( $args['background_color'] ) ) {

        echo '<div class="csf--color">';

        echo ( ! empty( $args['background_gradient'] ) ) ? '<div class="csf--title">'. esc_html__( 'From', 'mine-cloudvod' ) .'</div>' : '';

        MCSF::field( array(
          'id'      => 'background-color',
          'type'    => 'color',
          'default' => $default_value['background-color'],
        ), $this->value['background-color'], $this->field_name(), 'field/background' );

        echo '</div>';

      }

      //
      // Background Gradient Color
      if ( ! empty( $args['background_gradient_color'] ) && ! empty( $args['background_gradient'] ) ) {

        echo '<div class="csf--color">';

        echo ( ! empty( $args['background_gradient'] ) ) ? '<div class="csf--title">'. esc_html__( 'To', 'mine-cloudvod' ) .'</div>' : '';

        MCSF::field( array(
          'id'      => 'background-gradient-color',
          'type'    => 'color',
          'default' => $default_value['background-gradient-color'],
        ), $this->value['background-gradient-color'], $this->field_name(), 'field/background' );

        echo '</div>';

      }

      //
      // Background Gradient Direction
      if ( ! empty( $args['background_gradient_direction'] ) && ! empty( $args['background_gradient'] ) ) {

        echo '<div class="csf--color">';

        echo ( ! empty( $args['background_gradient'] ) ) ? '<div class="csf---title">'. esc_html__( 'Direction', 'mine-cloudvod' ) .'</div>' : '';

        MCSF::field( array(
          'id'          => 'background-gradient-direction',
          'type'        => 'select',
          'options'     => array(
            ''          => esc_html__( 'Gradient Direction', 'mine-cloudvod' ),
            'to bottom' => esc_html__( '&#8659; top to bottom', 'mine-cloudvod' ),
            'to right'  => esc_html__( '&#8658; left to right', 'mine-cloudvod' ),
            '135deg'    => esc_html__( '&#8664; corner top to right', 'mine-cloudvod' ),
            '-135deg'   => esc_html__( '&#8665; corner top to left', 'mine-cloudvod' ),
          ),
        ), $this->value['background-gradient-direction'], $this->field_name(), 'field/background' );

        echo '</div>';

      }

      echo '</div>';

      //
      // Background Image
      if ( ! empty( $args['background_image'] ) ) {

        echo '<div class="csf--background-image">';

        MCSF::field( array(
          'id'          => 'background-image',
          'type'        => 'media',
          'class'       => 'csf-assign-field-background',
          'library'     => $args['background_image_library'],
          'preview'     => $args['background_image_preview'],
          'placeholder' => $args['background_image_placeholder'],
          'attributes'  => array( 'data-depend-id' => $this->field['id'] ),
        ), $this->value['background-image'], $this->field_name(), 'field/background' );

        echo '</div>';

      }

      $auto_class   = ( ! empty( $args['background_auto_attributes'] ) ) ? ' csf--auto-attributes' : '';
      $hidden_class = ( ! empty( $args['background_auto_attributes'] ) && empty( $this->value['background-image']['url'] ) ) ? ' csf--attributes-hidden' : '';

      echo '<div class="csf--background-attributes'. esc_attr( $auto_class . $hidden_class ) .'">';

      //
      // Background Position
      if ( ! empty( $args['background_position'] ) ) {

        MCSF::field( array(
          'id'              => 'background-position',
          'type'            => 'select',
          'options'         => array(
            ''              => esc_html__( 'Background Position', 'mine-cloudvod' ),
            'left top'      => esc_html__( 'Left Top', 'mine-cloudvod' ),
            'left center'   => esc_html__( 'Left Center', 'mine-cloudvod' ),
            'left bottom'   => esc_html__( 'Left Bottom', 'mine-cloudvod' ),
            'center top'    => esc_html__( 'Center Top', 'mine-cloudvod' ),
            'center center' => esc_html__( 'Center Center', 'mine-cloudvod' ),
            'center bottom' => esc_html__( 'Center Bottom', 'mine-cloudvod' ),
            'right top'     => esc_html__( 'Right Top', 'mine-cloudvod' ),
            'right center'  => esc_html__( 'Right Center', 'mine-cloudvod' ),
            'right bottom'  => esc_html__( 'Right Bottom', 'mine-cloudvod' ),
          ),
        ), $this->value['background-position'], $this->field_name(), 'field/background' );

      }

      //
      // Background Repeat
      if ( ! empty( $args['background_repeat'] ) ) {

        MCSF::field( array(
          'id'          => 'background-repeat',
          'type'        => 'select',
          'options'     => array(
            ''          => esc_html__( 'Background Repeat', 'mine-cloudvod' ),
            'repeat'    => esc_html__( 'Repeat', 'mine-cloudvod' ),
            'no-repeat' => esc_html__( 'No Repeat', 'mine-cloudvod' ),
            'repeat-x'  => esc_html__( 'Repeat Horizontally', 'mine-cloudvod' ),
            'repeat-y'  => esc_html__( 'Repeat Vertically', 'mine-cloudvod' ),
          ),
        ), $this->value['background-repeat'], $this->field_name(), 'field/background' );

      }

      //
      // Background Attachment
      if ( ! empty( $args['background_attachment'] ) ) {

        MCSF::field( array(
          'id'       => 'background-attachment',
          'type'     => 'select',
          'options'  => array(
            ''       => esc_html__( 'Background Attachment', 'mine-cloudvod' ),
            'scroll' => esc_html__( 'Scroll', 'mine-cloudvod' ),
            'fixed'  => esc_html__( 'Fixed', 'mine-cloudvod' ),
          ),
        ), $this->value['background-attachment'], $this->field_name(), 'field/background' );

      }

      //
      // Background Size
      if ( ! empty( $args['background_size'] ) ) {

        MCSF::field( array(
          'id'        => 'background-size',
          'type'      => 'select',
          'options'   => array(
            ''        => esc_html__( 'Background Size', 'mine-cloudvod' ),
            'cover'   => esc_html__( 'Cover', 'mine-cloudvod' ),
            'contain' => esc_html__( 'Contain', 'mine-cloudvod' ),
            'auto'    => esc_html__( 'Auto', 'mine-cloudvod' ),
          ),
        ), $this->value['background-size'], $this->field_name(), 'field/background' );

      }

      //
      // Background Origin
      if ( ! empty( $args['background_origin'] ) ) {

        MCSF::field( array(
          'id'            => 'background-origin',
          'type'          => 'select',
          'options'       => array(
            ''            => esc_html__( 'Background Origin', 'mine-cloudvod' ),
            'padding-box' => esc_html__( 'Padding Box', 'mine-cloudvod' ),
            'border-box'  => esc_html__( 'Border Box', 'mine-cloudvod' ),
            'content-box' => esc_html__( 'Content Box', 'mine-cloudvod' ),
          ),
        ), $this->value['background-origin'], $this->field_name(), 'field/background' );

      }

      //
      // Background Clip
      if ( ! empty( $args['background_clip'] ) ) {

        MCSF::field( array(
          'id'            => 'background-clip',
          'type'          => 'select',
          'options'       => array(
            ''            => esc_html__( 'Background Clip', 'mine-cloudvod' ),
            'border-box'  => esc_html__( 'Border Box', 'mine-cloudvod' ),
            'padding-box' => esc_html__( 'Padding Box', 'mine-cloudvod' ),
            'content-box' => esc_html__( 'Content Box', 'mine-cloudvod' ),
          ),
        ), $this->value['background-clip'], $this->field_name(), 'field/background' );

      }

      //
      // Background Blend Mode
      if ( ! empty( $args['background_blend_mode'] ) ) {

        MCSF::field( array(
          'id'            => 'background-blend-mode',
          'type'          => 'select',
          'options'       => array(
            ''            => esc_html__( 'Background Blend Mode', 'mine-cloudvod' ),
            'normal'      => esc_html__( 'Normal', 'mine-cloudvod' ),
            'multiply'    => esc_html__( 'Multiply', 'mine-cloudvod' ),
            'screen'      => esc_html__( 'Screen', 'mine-cloudvod' ),
            'overlay'     => esc_html__( 'Overlay', 'mine-cloudvod' ),
            'darken'      => esc_html__( 'Darken', 'mine-cloudvod' ),
            'lighten'     => esc_html__( 'Lighten', 'mine-cloudvod' ),
            'color-dodge' => esc_html__( 'Color Dodge', 'mine-cloudvod' ),
            'saturation'  => esc_html__( 'Saturation', 'mine-cloudvod' ),
            'color'       => esc_html__( 'Color', 'mine-cloudvod' ),
            'luminosity'  => esc_html__( 'Luminosity', 'mine-cloudvod' ),
          ),
        ), $this->value['background-blend-mode'], $this->field_name(), 'field/background' );

      }

      echo '</div>';

      echo $this->field_after();

    }

    public function output() {

      $output    = '';
      $bg_image  = array();
      $important = ( ! empty( $this->field['output_important'] ) ) ? '!important' : '';
      $element   = ( is_array( $this->field['output'] ) ) ? join( ',', $this->field['output'] ) : $this->field['output'];

      // Background image and gradient
      $background_color        = ( ! empty( $this->value['background-color']              ) ) ? $this->value['background-color']              : '';
      $background_gd_color     = ( ! empty( $this->value['background-gradient-color']     ) ) ? $this->value['background-gradient-color']     : '';
      $background_gd_direction = ( ! empty( $this->value['background-gradient-direction'] ) ) ? $this->value['background-gradient-direction'] : '';
      $background_image        = ( ! empty( $this->value['background-image']['url']       ) ) ? $this->value['background-image']['url']       : '';


      if ( $background_color && $background_gd_color ) {
        $gd_direction   = ( $background_gd_direction ) ? $background_gd_direction .',' : '';
        $bg_image[] = 'linear-gradient('. $gd_direction . $background_color .','. $background_gd_color .')';
        unset( $this->value['background-color'] );
      }

      if ( $background_image ) {
        $bg_image[] = 'url('. $background_image .')';
      }

      if ( ! empty( $bg_image ) ) {
        $output .= 'background-image:'. implode( ',', $bg_image ) . $important .';';
      }

      // Common background properties
      $properties = array( 'color', 'position', 'repeat', 'attachment', 'size', 'origin', 'clip', 'blend-mode' );

      foreach ( $properties as $property ) {
        $property = 'background-'. $property;
        if ( ! empty( $this->value[$property] ) ) {
          $output .= $property .':'. $this->value[$property] . $important .';';
        }
      }

      if ( $output ) {
        $output = $element .'{'. $output .'}';
      }

      $this->parent->output_css .= $output;

      return $output;

    }

  }
}
