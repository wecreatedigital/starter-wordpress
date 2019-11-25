<?php
/**
 * LearnDash Settings field Date Entry.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Date_Entry' ) ) ) {

	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Date_Entry extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'date-entry';

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

			$date_value = '';
			if ( isset( $field_args['value'] ) ) {
				if ( ! empty( $field_args['value'] ) ) {
					if ( ! is_numeric( $field_args['value'] ) ) {
						$date_value = learndash_get_timestamp_from_date_string( $value );
					} else {
						// If we have a timestamp we assume it is GMT. So we need to convert it to local.
						$value_ymd  = get_date_from_gmt( date( 'Y-m-d H:i:s', $field_args['value'] ), 'Y-m-d H:i:s' );
						$date_value = strtotime( $value_ymd );
					}
				}
			}

			if ( ! empty( $date_value ) ) {
				$value_jj = gmdate( 'd', $date_value );
				$value_mm = gmdate( 'm', $date_value );
				$value_aa = gmdate( 'Y', $date_value );
				$value_hh = gmdate( 'H', $date_value );
				$value_mn = gmdate( 'i', $date_value );
			} else {
				$value_jj = '';
				$value_mm = '';
				$value_aa = '';
				$value_hh = '';
				$value_mn = '';
			}

			$field_name  = $this->get_field_attribute_name( $field_args, false );
			$field_class = $this->get_field_attribute_class( $field_args, false );
			$field_id    = $this->get_field_attribute_id( $field_args, false );

			$month_field = '<span class="screen-reader-text">' . esc_html__( 'Month', 'learndash' ) . '</span><select class="ld_date_mm ' . $field_class . '" name="' . $field_name . '[mm]" ><option value="">' . esc_html__( 'MM', 'learndash' ) . '</option>';
			for ( $i = 1; $i < 13; $i = $i + 1 ) {
				$monthnum     = zeroise( $i, 2 );
				$monthtext    = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
				$month_field .= "\t\t\t" . '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected( $monthnum, $value_mm, false ) . '>';
				/* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
				$month_field .= sprintf( esc_html_x( '%1$s-%2$s', 'placeholder: month number, month text', 'learndash' ), $monthnum, $monthtext ) . "</option>\n";
			}
				$month_field .= '</select>';

			$day_field    = '<span class="screen-reader-text">' . esc_html__( 'Day', 'learndash' ) . '</span><input type="number" placeholder="DD" min="1" max="31" class="ld_date_jj ' . $field_class . '" name="' . $field_name . '[jj]" value="' . $value_jj . '" size="2" maxlength="2" autocomplete="off" />';
			$year_field   = '<span class="screen-reader-text">' . esc_html__( 'Year', 'learndash' ) . '</span><input  type="number" placeholder="YYYY" min="0000" max="9999" class="ld_date_aa ' . $field_class . '" name="' . $field_name . '[aa]" value="' . $value_aa . '" size="4" maxlength="4" autocomplete="off" />';
			$hour_field   = '<span class="screen-reader-text">' . esc_html__( 'Hour', 'learndash' ) . '</span><input type="number" min="0" max="23" placeholder="HH" class="ld_date_hh ' . $field_class . '" name="' . $field_name . '[hh]" value="' . $value_hh . '" size="2" maxlength="2" autocomplete="off" />';
			$minute_field = '<span class="screen-reader-text">' . esc_html__( 'Minute', 'learndash' ) . '</span><input type="number" min="0" max="59" placeholder="MN" class="ld_date_mn ' . $field_class . '" name="' . $field_name . '[mn]" value="' . $value_mn . '" size="2" maxlength="2" autocomplete="off" />';

			$html .= '<div class="ld_date_selector">' . sprintf(
				// Translators: placeholders Month, Day, Year, Hour, Minute
				esc_html__( '%1$s %2$s, %3$s @ %4$s:%5$s' ),
				$month_field,
				$day_field,
				$year_field,
				$hour_field,
				$minute_field
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
				if ( isset( $val['aa'] ) ) {
					$val['aa'] = intval( $val['aa'] );
				} else {
					$val['aa'] = 0;
				}

				if ( isset( $val['mm'] ) ) {
					$val['mm'] = intval( $val['mm'] );
				} else {
					$val['mm'] = 0;
				}

				if ( isset( $val['jj'] ) ) {
					$val['jj'] = intval( $val['jj'] );
				} else {
					$val['jj'] = 0;
				}

				if ( isset( $val['hh'] ) ) {
					$val['hh'] = intval( $val['hh'] );
				} else {
					$val['hh'] = 0;
				}

				if ( isset( $val['mn'] ) ) {
					$val['mn'] = intval( $val['mn'] );
				} else {
					$val['mn'] = 0;
				}

				if ( ( ! empty( $val['aa'] ) ) && ( ! empty( $val['mm'] ) ) && ( ! empty( $val['jj'] ) ) ) {
					$date_string = sprintf(
						'%04d-%02d-%02d %02d:%02d:00',
						intval( $val['aa'] ),
						intval( $val['mm'] ),
						intval( $val['jj'] ),
						intval( $val['hh'] ),
						intval( $val['mn'] )
					);

					$date_string_gmt = get_gmt_from_date( $date_string, 'Y-m-d H:i:s' );
					$val             = strtotime( $date_string_gmt );
				} else {
					$val = 0;
				}
				return $val;
			}

			return false;
		}
		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Date_Entry::add_field_instance( 'date-entry' );
	}
);
