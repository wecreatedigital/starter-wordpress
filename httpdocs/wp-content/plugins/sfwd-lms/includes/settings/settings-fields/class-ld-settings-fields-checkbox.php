<?php
/**
 * LearnDash Settings field Checkbox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Checkbox' ) ) ) {

	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Checkbox extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'checkbox';

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

			$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {
				if ( ( isset( $field_args['desc'] ) ) && ( ! empty( $field_args['desc'] ) ) ) {
					$html .= $field_args['desc'];
				}

				if ( ! isset( $field_args['class'] ) ) {
					$field_args['class'] = '';
				}
				$field_args['class'] .= ' ld-checkbox-input';

				$html .= '<fieldset>';
				$html .= $this->get_field_legend( $field_args );

				$checkbox_multiple = '';
				if ( count( $field_args['options'] ) > 1 ) {
					$checkbox_multiple = '[]';
				}
				foreach ( $field_args['options'] as $option_key => $option_label ) {

					$html .= '<p class="learndash-section-field-checkbox-p">';
					$html .= '<input autocomplete="off" ';

					$html .= $this->get_field_attribute_type( $field_args );
					$html .= ' id="' . $this->get_field_attribute_id( $field_args, false ) . '-' . $option_key . '"';

					$html .= ' name="' . $this->get_field_attribute_name( $field_args, false ) . $checkbox_multiple . '"';
					$html .= $this->get_field_attribute_class( $field_args );
					$html .= $this->get_field_attribute_misc( $field_args );
					$html .= $this->get_field_attribute_required( $field_args );

					$html .= ' value="' . $option_key . '" ';

					if ( ( is_array( $field_args['value'] ) ) && ( in_array( $option_key, $field_args['value'] ) ) ) {
						$html .= ' ' . checked( $option_key, $option_key, false ) . ' ';
					} else if ( is_string( $field_args['value'] ) ) {
						$html .= ' ' . checked( $option_key, $field_args['value'], false ) . ' ';
					}

					$html .= ' />';

					$html .= '<label class="ld-checkbox-input__label" for="' . $field_args['id'] . '-' . $option_key . '" >';
					if ( is_string( $option_label ) ) {
						$html .= '<span>' . $option_label . '</span></label></p>';
					} elseif ( ( is_array( $option_label ) ) && ( ! empty( $option_label ) ) ) {
						if ( ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
							$html .= '<span>' . $option_label['label'] . '</span></label>';
						}
						$html .= '</p>';
						if ( ( isset( $option_label['description'] ) ) && ( ! empty( $option_label['description'] ) ) ) {
							$html .= '<p class="ld-checkbox-description">' . $option_label['description'] . '</p>';
						}
					} else {
						$html .= '</p>';
					}
				}

				//$html .= $this->get_field_attribute_input_label( $field_args );
				$html .= '</fieldset>';

			}

			$html = apply_filters( 'learndash_settings_field_html_after', $html, $field_args );

			echo $html;
		}

		/**
		 * Validate field
		 *
		 * @since 2.6.0
		 *
		 * @param mixed  $val Value to validate.
		 * @param string $key Key of value being validated.
		 * @param array  $args Array of field args.
		 *
		 * @return integer value.
		 */
		public function validate_section_field( $val, $key, $args = array() ) {
			if ( ( isset( $args['field']['type'] ) ) && ( $this->field_type === $args['field']['type'] ) ) {	
				if ( is_array( $val ) ) {
					foreach ( $val as $val_idx => $val_val ) {
						if ( ! isset( $args['field']['options'][ $val_val ] ) ) {
							unset( $val[ $val_val ] );
						}
					}
					return $val;
				} else if ( is_string( $val ) ) {
					if ( ( '' === $val ) || ( isset( $args['field']['options'][ $val ] ) ) ) {
						return $val;
					} elseif ( isset( $args['field']['default'] ) ) {
						return $args['field']['default'];
					} else {
						return '';
					}
				}
			}

			return false;
		}
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Checkbox::add_field_instance( 'checkbox' );
	}
);
