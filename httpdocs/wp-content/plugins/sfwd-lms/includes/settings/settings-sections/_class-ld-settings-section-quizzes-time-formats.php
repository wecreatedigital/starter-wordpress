<?php
/**
 * LearnDash Settings Section Quiz Time Formats.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Quizzes_Time_Formats' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Quizzes_Time_Formats extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz_page_quizzes-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'quizzes-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_quizzes_time_formats';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_quizzes_time_formats';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'quizzes_time_formats';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Time Formats', 'placeholder: Quiz', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);

			parent::__construct();

			$this->save_settings_fields();

			add_filter( 'learndash_settings_field_html_after', array( $this, 'learndash_settings_field_html_after' ), 10, 2 );
		}

		/**
		 * Load the field settings values
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$this->setting_option_values = array(
				'toplist_time_format'    => get_option( 'wpProQuiz_toplistDataFormat' ),
				'statistics_time_format' => get_option( 'wpProQuiz_statisticTimeFormat' ),
			);
		}

		/**
		 * Load the field settings fields
		 */
		public function load_settings_fields() {
			$wp_date_format = get_option( 'date_format' );
			$wp_time_format = get_option( 'time_format' );

			$date_time_formats = array_unique(
				apply_filters(
					'learndash_quiz_date_time_formats',
					array(
						'd.m.Y H:i',
						'Y/m/d g:i A',
						'Y/m/d \a\t g:i A',
						'Y/m/d \a\t g:ia',
						__( 'F j, Y g:i a' ),
						__( 'M j, Y @ G:i' ),
					)
				)
			);

			if ( ! empty( $date_time_formats ) ) {
				$options = array(
					'' => '<span class="date-time-text format-i18n">' . date_i18n( $wp_date_format . ' ' . $wp_time_format ) . '</span><code>' . $wp_date_format . ' ' . $wp_time_format . '</code> - ' . __( 'WordPress default', 'learndash' ),
				);

				foreach ( $date_time_formats as $format ) {
					$options[ $format ] = '<span class="date-time-text format-i18n">' . date_i18n( $format ) . '</span><code>' . $format . '</code>';
				}
			}

			$this->setting_option_fields = array();

			$options['custom'] = '<span class="date-time-text format-i18n">' . esc_html__( 'Custom', 'learndash' ) . '</span><input type="text" class="small-text" name="toplist_date_format_custom" id="toplist_time_format_custom" value="' . $this->setting_option_values['toplist_time_format'] . '">';

			$this->setting_option_fields['toplist_time_format'] = array(
				'name'      => 'toplist_time_format',
				'type'      => 'radio',
				'label'     => esc_html__( 'Leaderboard time format', 'learndash' ),
				'help_text' => esc_html__( 'Leaderboard time format', 'learndash' ),
				'value'     => $this->setting_option_values['toplist_time_format'],
				'options'   => $options,
			);

			$options['custom'] = '<span class="date-time-text format-i18n">' . esc_html__( 'Custom', 'learndash' ) . '</span><input type="text" class="small-text" name="statistics_time_format_custom" id="statistics_time_format_custom" value="' . $this->setting_option_values['toplist_time_format'] . '">';

			$this->setting_option_fields['statistics_time_format'] = array(
				'name'      => 'statistics_time_format',
				'type'      => 'radio',
				'label'     => esc_html__( 'Statistic time format ', 'learndash' ),
				'help_text' => esc_html__( 'Statistic time format ', 'learndash' ),
				'value'     => $this->setting_option_values['statistics_time_format'],
				'options'   => $options,
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Hook into action after the fieldset is output. This allows adding custom content like JS/CSS.
		 *
		 * @since 2.5.9
		 *
		 * @param string $html This is the field output which will be send to the screen.
		 * @param array  $field_args Array of field args used to build the field HTML.
		 *
		 * @return string $html.
		 */
		public function learndash_settings_field_html_after( $html = '', $field_args = array() ) {
			/**
			 * Here we hook into the bottom of the field HTML output and add some inline JS to handle the
			 * change event on the radio buttons. This is really just to update the 'custom' input field
			 * display.
			 */
			if ( ( isset( $field_args['setting_option_key'] ) ) && ( $this->setting_option_key === $field_args['setting_option_key'] ) ) {
				if ( ( isset( $field_args['name'] ) ) && ( in_array( $field_args['name'], array( 'toplist_time_format', 'statistics_time_format' ) ) ) ) {
					$html .= "<p class='date-time-doc'>" . __( '<a href="https://codex.wordpress.org/Formatting_Date_and_Time">Documentation on date and time formatting</a>.', 'default' ) . "</p>\n";
					$html .= "
					<script>
					jQuery(window).ready(function() {
						var inputs_selector = '#" . $this->setting_field_prefix . '_' . $field_args['name'] . "_field fieldset input[type=\"radio\"]';
						//console.log('inputs_selector[%o]', inputs_selector );
						var inputs = document.querySelectorAll( inputs_selector );
						//console.log('inputs[%o]', inputs);
						var inputs_length = inputs.length;
						while( inputs_length-- ) {
							inputs[inputs_length].addEventListener('change', function() {
								if ( this.value != 'custom' ) {
									document.getElementById('" . $field_args['name'] . "_custom').value = this.value;
								} 
							}, 0 );
						}
					});
					</script>";
				}
			}
			return $html;
		}

		/**
		 * Custom save function because we need to update the WPProQuiz settings with the saved value.
		 */
		public function save_settings_fields() {
			if ( isset( $_POST[ $this->setting_option_key ] ) ) {
				if ( isset( $_POST[ $this->setting_option_key ]['toplist_time_format'] ) ) {
					update_option( 'wpProQuiz_toplistDataFormat', esc_attr( $_POST[ $this->setting_option_key ]['toplist_time_format'] ) );
				}

				if ( isset( $_POST[ $this->setting_option_key ]['statistics_time_format'] ) ) {
					update_option( 'wpProQuiz_statisticTimeFormat', esc_attr( $_POST[ $this->setting_option_key ]['statistics_time_format'] ) );
				}
			}
		}
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Quizzes_Time_Formats::add_section_instance();
	}
);
