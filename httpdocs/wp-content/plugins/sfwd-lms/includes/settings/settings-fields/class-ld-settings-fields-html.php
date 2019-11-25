<?php
/**
 * LearnDash Settings field HTML.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Html' ) ) ) {
	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Html extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'html';

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

			$field_type = apply_filters( 'learndash_settings_field_element_html', 'div' );
			$html      .= '<' . $field_type . ' ';
			$html      .= $this->get_field_attribute_id( $field_args );
			$html      .= $this->get_field_attribute_class( $field_args );
			$html      .= $this->get_field_attribute_misc( $field_args );
			$html      .= '>';

			if ( isset( $field_args['value'] ) ) {
				$html .= wptexturize( do_shortcode( $field_args['value'] ) );
			}

			$html .= '</' . $field_type . '>';

			echo $html;
		}

		/**
		 * Default validation function. Should be overriden in Field subclass.
		 *
		 * @since 2.4
		 *
		 * @param mixed  $val Value to validate.
		 * @param string $key Key of value being validated.
		 * @param array  $args Array of field args.
		 *
		 * @return mixed $val validated value.
		 */
		public function validate_section_field( $val, $key = '', $args = array() ) {
			if ( ( ! empty( $val ) ) && ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {
				return sanitize_textarea_field( $val );
			}

			return $val;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Html::add_field_instance( 'html' );
	}
);
