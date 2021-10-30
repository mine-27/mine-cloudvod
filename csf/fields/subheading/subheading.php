<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: subheading
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'MCSF_Field_subheading' ) ) {
  class MCSF_Field_subheading extends MCSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      echo ( ! empty( $this->field['content'] ) ) ? $this->field['content'] : '';

    }

  }
}
