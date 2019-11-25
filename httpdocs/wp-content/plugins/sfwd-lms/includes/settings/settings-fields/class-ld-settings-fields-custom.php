<?php
/**
 * LearnDash Settings field Custom.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Custom' ) ) ) {
	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Custom extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'custom';

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

			if ( isset( $field_args['html'] ) ) {
				$html .= $field_args['html'];
			}

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
		public function validate_section_field( $val, $key, $args = array() ) {
			if ( ! empty( $val ) ) {
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
		LearnDash_Settings_Fields_Custom::add_field_instance( 'custom' );
	}
);
