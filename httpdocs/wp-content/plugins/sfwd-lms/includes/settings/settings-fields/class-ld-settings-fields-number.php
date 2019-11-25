<?php
/**
 * LearnDash Settings field input Number.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Number' ) ) ) {

	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Number extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'number';

			parent::__construct();
		}

		/**
		 * Function to crete the settiings field.
		 *
		 * @since 2.4
		 *
		 * @param array $field_args An array of field arguments used to process the ouput.
		 * @return void
		 */
		public function create_section_field( $field_args = array() ) {
			$field_args = apply_filters( 'learndash_settings_field', $field_args );
			$html       = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			$html .= '<input autocomplete="off" ';
			$html .= $this->get_field_attribute_type( $field_args );
			$html .= $this->get_field_attribute_name( $field_args );
			$html .= $this->get_field_attribute_id( $field_args );
			$html .= $this->get_field_attribute_class( $field_args );
			$html .= $this->get_field_attribute_placeholder( $field_args );
			$html .= $this->get_field_attribute_misc( $field_args );
			$html .= $this->get_field_attribute_required( $field_args );

			if ( isset( $field_args['value'] ) ) {
				$html .= ' value="' . $field_args['value'] . '" ';
			} else {
				$html .= ' value="" ';
			}

			$html .= ' />';

			$html .= $this->get_field_attribute_input_label( $field_args );
			$html .= $this->get_field_error_message( $field_args );

			$html = apply_filters( 'learndash_settings_field_html_after', $html, $field_args );

			echo $html;
		}

		/**
		 * Validate field
		 *
		 * @since 2.4
		 *
		 * @param mixed  $val Value to validate.
		 * @param string $key Key of value being validated.
		 * @param array  $args Array of field args.
		 *
		 * @return integer value.
		 */
		public function validate_section_field( $val, $key = '', $args = array() ) {
			if ( ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {
				// If empty check our settings.
				if ( ( '' === $val ) && ( isset( $args['field']['attrs']['can_empty'] ) ) && ( true === $args['field']['attrs']['can_empty'] ) ) {
					return $val;
				}

				if ( ! isset( $args['field']['attrs']['can_decimal'] ) ) {
					$args['field']['attrs']['can_decimal'] = 0;
				}

				if ( $args['field']['attrs']['can_decimal'] > 0 ) {
					$val = floatval( $val );
					//$val = number_format ( $val, absint( $args['field']['attrs']['can_decimal'] ) );
				} else {
					$val = intval( $val );
				}

				if ( ( isset( $args['field']['attrs']['min'] ) ) && ( ! empty( $args['field']['attrs']['min'] ) ) && ( $val < $args['field']['attrs']['min'] ) ) {
					return false;
				} elseif ( ( isset( $args['field']['attrs']['max'] ) ) && ( ! empty( $args['field']['attrs']['max'] ) ) && ( $val > $args['field']['attrs']['max'] ) ) {
					return false;
				}

				return $val;
			}

			return false;
		}
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Number::add_field_instance( 'number' );
	}
);
