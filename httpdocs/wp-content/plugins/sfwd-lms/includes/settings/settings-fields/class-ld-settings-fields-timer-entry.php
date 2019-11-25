<?php
/**
 * LearnDash Settings field Timer Entry.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Timer_Entry' ) ) ) {

	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Timer_Entry extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'timer-entry';

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
			global $wp_locale;

			$field_args = apply_filters( 'learndash_settings_field', $field_args );
			$html       = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			if ( ( isset( $field_args['value'] ) ) && ( ! empty( $field_args['value'] ) ) ) {
				$field_args['value'] = learndash_convert_lesson_time_time( $field_args['value'] );
				$value_hh            = gmdate( 'H', $field_args['value'] );
				$value_mn            = gmdate( 'i', $field_args['value'] );
				$value_ss            = gmdate( 's', $field_args['value'] );
			} else {
				$value_hh = '';
				$value_mn = '';
				$value_ss = '';
			}

			$field_name  = $this->get_field_attribute_name( $field_args, false );
			$field_class = $this->get_field_attribute_class( $field_args, false );
			$field_id    = $this->get_field_attribute_id( $field_args, false );

			$hour_field = '<span class="screen-reader-text">' . esc_html__( 'Hour', 'learndash' ) . '</span><input type="number" min="0" max="23" placeholder="HH" class="ld_date_hh ' . $field_class . '" name="' . $field_name . '[hh]" value="' . $value_hh . '" size="2" maxlength="2" autocomplete="off" />';

			$minute_field = '<span class="screen-reader-text">' . esc_html__( 'Minute', 'learndash' ) . '</span><input type="number" min="0" max="59" placeholder="MM" class="ld_date_mn ' . $field_class . '" name="' . $field_name . '[mn]" value="' . $value_mn . '" size="2" maxlength="2" autocomplete="off" />';

			$second_field = '<span class="screen-reader-text">' . esc_html__( 'Seconds', 'learndash' ) . '</span><input type="number" min="0" max="59" placeholder="SS" class="ld_date_ss ' . $field_class . '" name="' . $field_name . '[ss]" value="' . $value_ss . '" size="2" maxlength="2" autocomplete="off" />';

			$html .= '<div class="ld_timer_selector">' . sprintf(
				// translators: placeholders: Hour, Minute, Second.
				esc_html__( '%1$s:%2$s:%3$s' ),
				$hour_field,
				$minute_field,
				$second_field
			) . '</div>';

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
			return sanitize_text_field( $val );
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
		public function value_section_field( $val = '', $key = '', $args = array(), $post_args = array() ) {
			if ( ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {

				if ( isset( $val['hh'] ) ) {
					$val['hh'] = absint( $val['hh'] );
				} else {
					$val['hh'] = 0;
				}

				if ( isset( $val['mn'] ) ) {
					$val['mn'] = absint( $val['mn'] );
				} else {
					$val['mn'] = 0;
				}

				if ( isset( $val['ss'] ) ) {
					$val['ss'] = absint( $val['ss'] );
				} else {
					$val['ss'] = 0;
				}

				$val_seconds = $val['ss'] + ( $val['mn'] * 60 ) + ( $val['hh'] * 60 * 60 );
				return $val_seconds;
			}

			return false;
		}
		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Timer_Entry::add_field_instance( 'timer-entry' );
	}
);
