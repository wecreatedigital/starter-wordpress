<?php
/**
 * LearnDash Settings administration field Multiselect.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Multiselect' ) ) ) {
	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Multiselect extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'multiselect';

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
			// Force multiple.
			$field_args['multiple'] = true;
			
			$field_args = apply_filters( 'learndash_settings_field', $field_args );
			$html       = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {
				$html .= '<span class="ld-select ld-select-multiple">';
				$html .= '<select multiple autocomplete="off" ';
				//$html .= $this->get_field_attribute_type( $field_args );
				$html .= $this->get_field_attribute_name( $field_args );
				$html .= $this->get_field_attribute_id( $field_args );
				$html .= $this->get_field_attribute_class( $field_args );
				$html .= $this->get_field_attribute_placeholder( $field_args );

				if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === LEARNDASH_SELECT2_LIB ) ) {
					if ( ! isset( $field_args['attrs']['data-ld-select2'] ) ) {
						$html .= ' data-ld-select2="1" ';
					}
				}

				$html .= $this->get_field_attribute_misc( $field_args );
				$html .= $this->get_field_attribute_required( $field_args );

				//if ( ( isset( $field_args['multiple'] ) ) && ( true === $field_args['multiple'] ) ) {
				//	$html .= ' multiple="multiple" ';
				//}
				$html .= ' >';

				foreach ( $field_args['options'] as $option_key => $option_label ) {
					if ( ( '' === $option_key ) && ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === LEARNDASH_SELECT2_LIB ) ) {
						continue;
					}
					$selected_item = '';
					if ( is_string( $field_args['value'] ) ) {
						$selected_item = selected( $option_key, $field_args['value'], false );
					} elseif ( is_array( $field_args['value'] ) ) {
						if ( in_array( $option_key, $field_args['value'] ) ) {
							$selected_item = ' selected="" ';
						}
					}

					$html .= '<option value="' . $option_key . '" ' . $selected_item . '>' . $option_label . '</option>';
				}
				$html .= '</select>';
				$html .= '</span>';
			}

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

				if ( ( is_array( $val ) ) && ( ! empty( $val ) ) ) {
					$val = array_map( $args['field']['value_type'], $val );
				} elseif ( ! empty( $val ) ) {
					$val = call_user_func( $args['field']['value_type'], $val );
				} else {
					$val = '';
				}

				return $val;
			}
			return false;
		}

		// end of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Multiselect::add_field_instance( 'multiselect' );
	}
);
