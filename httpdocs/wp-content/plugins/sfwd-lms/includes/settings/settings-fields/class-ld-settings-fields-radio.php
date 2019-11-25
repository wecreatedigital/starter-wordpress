<?php
/**
 * LearnDash Settings administration field Radio.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Radio' ) ) ) {

	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Radio extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'radio';

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

				if ( ( isset( $field_args['desc'] ) ) && ( ! empty( $field_args['desc'] ) ) ) {
					$html .= $field_args['desc'];
				}

				if ( ! isset( $field_args['class'] ) ) {
					$field_args['class'] = '';
				}
				$field_args['class'] .= ' ld-radio-input';

				$html .= '<fieldset>';
				$html .= $this->get_field_legend( $field_args );

				foreach ( $field_args['options'] as $option_key => $option_label ) {

					$html .= '<p class="ld-radio-input-wrapper">';
					$html .= '<input autocomplete="off" ';

					$html .= $this->get_field_attribute_type( $field_args );
					//$html .= $this->get_field_attribute_id( $field_args );
					$html .= ' id="' . $this->get_field_attribute_id( $field_args, false ) . '-' . $option_key . '"';

					$html .= $this->get_field_attribute_name( $field_args );
					$html .= $this->get_field_attribute_class( $field_args );
					$html .= $this->get_field_attribute_misc( $field_args );
					$html .= $this->get_field_attribute_required( $field_args );

					$html .= ' value="' . $option_key . '" ';

					$html .= ' ' . checked( $option_key, $field_args['value'], false ) . ' ';

					$html_sub_fields = '';
					if ( ( is_array( $option_label ) ) && ( ! empty( $option_label ) ) ) {
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
					}

					$html .= ' />';

					$html .= '<label class="ld-radio-input__label" for="' . $field_args['id'] . '-' . $option_key . '" >';
					if ( is_string( $option_label ) ) {
						$html .= '<span>' . $option_label . '</span></label><p>';
					} elseif ( ( is_array( $option_label ) ) && ( ! empty( $option_label ) ) ) {
						if ( ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
							$html .= '<span>' . $option_label['label'] . '</span></label>';
						}
						$html .= '</p>';

						if ( ( isset( $option_label['description'] ) ) && ( ! empty( $option_label['description'] ) ) ) {
							$html .= '<p class="ld-radio-description">' . $option_label['description'] . '</p>';
						}
					}

					$html .= $html_sub_fields;
				}

				$html .= '</fieldset>';
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
				if ( isset( $args['field']['options'][ $val ] ) ) {
					return $val;
				}
			}
			return false;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Radio::add_field_instance( 'radio' );
	}
);
