<?php
/**
 * LearnDash Settings field Checkbox Switch / Toggle.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Checkbox_Switch' ) ) ) {

	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Checkbox_Switch extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'checkbox-switch';

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
				$field_args['class'] .= ' ld-switch__input';

				$html .= '<fieldset>';
				$html .= $this->get_field_legend( $field_args );

				$sel_option_key   = $field_args['value'];
				$sel_option_label = '';
				if ( count( $field_args['options'] ) > 1 ) {
					if ( isset( $field_args['options'][ $sel_option_key ] ) ) {
						$sel_option_label = $field_args['options'][ $sel_option_key ];
					}
				} else {
					foreach ( $field_args['options'] as $option_key => $option_label ) {
						if ( is_string( $option_label ) ) {
							$sel_option_label = $option_label;
						} elseif ( ( is_array( $option_label ) ) && ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
							$sel_option_label = $option_label['label'];
						}
					}
				}

				$html .= ' <label for="' . $field_args['id'] . '" >';
				$html .= '<div class="ld-switch-wrapper">';
				$html .= '<span class="ld-switch';
				if ( isset( $field_args['attrs']['disabled'] ) ) {
					$html .= ' -disabled';
				}
				foreach ( $field_args['options'] as $option_key => $option_label ) {
					if ( ( ! empty( $option_key ) ) && ( isset( $option_label['tooltip'] ) ) && ( ! empty( $option_label['tooltip'] ) ) ) {
						$html .= ' tooltip';
					}
				}
				$html .= '">';

				$html .= '<input ';
				$html .= ' type="checkbox" autocomplete="off" ';
				$html .= $this->get_field_attribute_id( $field_args );
				$html .= $this->get_field_attribute_name( $field_args );
				$html .= $this->get_field_attribute_class( $field_args );
				$html .= $this->get_field_attribute_misc( $field_args );
				$html .= $this->get_field_attribute_required( $field_args );

				foreach ( $field_args['options'] as $option_key => $option_label ) {
					if ( ! empty( $option_key ) ) {
						$html .= ' value="' . $option_key . '" ';
						break;
					}
				}

				if ( ! empty( $sel_option_key ) ) {
					$html .= ' ' . checked( $sel_option_key, $field_args['value'], false ) . ' ';
				}

				$html_sub_fields = '';
				if ( ( isset( $field_args['inline_fields'] ) ) && ( ! empty( $field_args['inline_fields'] ) ) ) {
					foreach ( $field_args['inline_fields'] as $sub_field_key => $sub_fields ) {
						$html .= ' data-settings-inner-trigger="ld-settings-inner-' . $sub_field_key . '" ';

						if ( ( isset( $field_args['inner_section_state'] ) ) && ( 'open' === $field_args['inner_section_state'] ) ) {
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
				} else {
					$html .= ' data-settings-sub-trigger="ld-settings-sub-' . $field_args['name'] . '" ';
				}
				$html .= ' />';

				$html .= '<span class="ld-switch__track"></span>';
				$html .= '<span class="ld-switch__thumb"></span>';
				$html .= '<span class="ld-switch__on-off"></span>';

				foreach ( $field_args['options'] as $option_key => $option_label ) {
					if ( ( ! empty( $option_key ) ) && ( isset( $option_label['tooltip'] ) ) && ( ! empty( $option_label['tooltip'] ) ) ) {
						$html .= '<span class="tooltiptext">' . $option_label['tooltip'] . '</span>';
						break;
					}
				}
				$html .= '</span>'; // end of ld-switch

				$html .= '<span class="label-text';
				if ( count( $field_args['options'] ) > 1 ) {
					$html .= ' label-text-multple';
				}
				$html .= '">';

				if ( count( $field_args['options'] ) > 1 ) {

					foreach ( $field_args['options'] as $option_key => $option_label ) {
						$label_display_state = '';
						if ( $option_key !== $sel_option_key ) {
							$label_display_state = ' style="display:none;" ';
						}
						if ( is_string( $option_label ) ) {
							$html .= '<span class="ld-label-text ld-label-text-' . $option_key . '"' . $label_display_state . '>' . $option_label . '</span>';
						} elseif ( ( is_array( $option_label ) ) && ( isset( $option_label['label'] ) ) && ( ! empty( $option_label['label'] ) ) ) {
							$html .= '<span class="ld-label-text ld-label-text-' . $option_key . '"' . $label_display_state . '>' . $option_label['label'] . '</span>';
						}
					}
				} else {
					if ( is_string( $sel_option_label ) ) {
							$html .= $sel_option_label;
					} elseif ( ( is_array( $sel_option_label ) ) && ( isset( $sel_option_label['label'] ) ) && ( ! empty( $sel_option_label['label'] ) ) ) {
						$html .= $sel_option_label['label'];
					}
				}
				$html .= '</span>';
				$html .= '</div></label>';

				$html .= $html_sub_fields;
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
			if ( ( ! empty( $val ) ) && ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {
				if ( isset( $args['field']['options'][ $val ] ) ) {
					return $val;
				} elseif ( isset( $args['field']['default'] ) ) {
					return $args['field']['default'];
				} else {
					return '';
				}
			}

			return $val;
		}
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Checkbox_Switch::add_field_instance( 'checkbox-switch' );
	}
);
