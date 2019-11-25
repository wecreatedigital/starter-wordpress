<?php
/**
 * LearnDash Settings administration field Select.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Select' ) ) ) {
	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Select extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'select';

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

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {
				$html .= '<span class="ld-select">';
				$html .= '<select autocomplete="off" ';
				$html .= $this->get_field_attribute_type( $field_args );
				$html .= $this->get_field_attribute_name( $field_args );
				$html .= $this->get_field_attribute_id( $field_args );
				$html .= $this->get_field_attribute_class( $field_args );

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

				$html .= $this->get_field_sub_trigger( $field_args );
				$html .= $this->get_field_inner_trigger( $field_args );

				$html .= ' >';

				$html_sub_fields = '';
				foreach ( $field_args['options'] as $option_key => $option_label ) {
					$selected_item = '';

					if ( is_array( $field_args['value'] ) ) {
						if ( in_array( $option_key, $field_args['value'] ) ) {
							$selected_item = ' selected="" ';
						}
					} else {
						$selected_item = selected( $option_key, $field_args['value'], false );
					}

					if ( is_array( $option_label ) ) {
						if ( ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
							$html .= '<option value="' . $option_key . '" ' . $selected_item . '>' . $option_label['label'] . '</option>';
						}

						if ( ( isset( $option_label['inline_fields'] ) ) && ( ! empty( $option_label['inline_fields'] ) ) ) {
							foreach ( $option_label['inline_fields'] as $sub_field_key => $sub_fields ) {
								$html .= ' data-settings-inner-trigger="ld-settings-inner-' . $sub_field_key . '" ';

								if ( ( isset( $option_label['inner_section_state'] ) ) && ( 'open' === $option_label['inner_section_state'] ) ) {
									$inner_section_state = 'open';
								} else {
									$inner_section_state = 'closed';
								}
								$html_sub_fields .= '<div class="ld-settings-inner ld-settings-inner-' . $sub_field_key . ' ld-settings-inner-state-' . $inner_section_state . '">';

								$level = ob_get_level();
								ob_start();
								foreach ( $sub_fields as $sub_field ) {
									self::show_section_field_row( $sub_field );
								}
								$html_sub_fields .= learndash_ob_get_clean( $level );
								$html_sub_fields .= '</div>';
							}
						}
					} elseif ( is_string( $option_label ) ) {
						$html .= '<option value="' . $option_key . '" ' . $selected_item . '>' . $option_label . '</option>';
					}
				}
				$html .= '</select>';
				$html .= '</span>';
				$html .= $this->get_field_attribute_input_label( $field_args );

				$html .= $html_sub_fields;
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
				if ( ! empty( $val ) ) {
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
		LearnDash_Settings_Fields_Select::add_field_instance( 'select' );
	}
);
