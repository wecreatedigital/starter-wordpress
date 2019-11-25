<?php
/**
 * LearnDash Settings Section for Per Page Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_General_Per_Page' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_General_Per_Page extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_settings';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_per_page';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_per_page';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_per_page';

			// Section label/header.
			$this->settings_section_label       = esc_html__( 'Global Pagination Settings', 'learndash' );
			$this->settings_section_description = esc_html__( 'Specify the default number of items displayed per page for various listing outputs.', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['progress_num'] ) ) {
				$this->setting_option_values['progress_num'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
			}

			if ( ! isset( $this->setting_option_values['quiz_num'] ) ) {
				$this->setting_option_values['quiz_num'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
			}

			if ( ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $this->setting_option_values['progress_num'] ) && ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $this->setting_option_values['quiz_num'] ) ) {
				$this->setting_option_values['profile_enabled'] = '';
			} else {
				$this->setting_option_values['profile_enabled'] = 'yes';
			}

			if ( ! isset( $this->setting_option_values['per_page'] ) ) {
				$this->setting_option_values['per_page'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
			}

			if ( ! isset( $this->setting_option_values['question_num'] ) ) {
				$this->setting_option_values['question_num'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
			}
		}

		/**
		 * Validate settings field.
		 *
		 * @param string $val Value to be validated.
		 * @param string $key settings fields key.
		 * @param array  $args Settings field args array.
		 *
		 * @return integer $val.
		 */
		public function validate_section_field_per_page( $val, $key, $args = array() ) {
			// Get the digits only.
			if ( ( isset( $args['field']['validate_args']['allow_empty'] ) ) && ( true === $args['field']['validate_args']['allow_empty'] ) ) {
				$val = preg_replace( '/[^0-9]/', '', $val );
			}

			if ( '' === $val ) {
				switch ( $key ) {
					case 'per_page':
						$val = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
						break;

					case 'progress_num':
						$val = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
						break;

					case 'quiz_num':
						$val = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
						break;

					case 'question_num':
						$val = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
						break;
				}
			}

			// IF profile is NOT enabled we make sure to clear the quiz and progress values.
			if ( ! isset( $args['post_fields']['profile_enabled'] ) ) {
				if ( ( 'quiz_num' === $key ) || ( 'progress_num' === $key ) ) {
					return LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
				}
			}

			return intval( $val );
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'profile_enabled' => array(
					'name'                => 'profile_enabled',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'WP Profile', 'learndash' ),
					'help_text'           => esc_html__( 'Controls the pagination for the WordPress Profile LearnDash elements.', 'learndash' ),
					'value'               => $this->setting_option_values['profile_enabled'],
					'options'             => array(
						'yes' => '',
						''    => sprintf(
							// translators: placeholder: default per page number.
							esc_html_x( 'Pagination defaults to %d', 'placeholder: default per page number', 'learndash' ),
							LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE
						),
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['profile_enabled'] ) ? 'open' : 'closed',
				),
				'progress_num'    => array(
					'name'              => 'progress_num',
					'type'              => 'number',
					'label'             => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Progress', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'             => $this->setting_option_values['progress_num'],
					'attrs'             => array(
						'step' => 1,
						'min'  => 0,
					),
					'input_label'       => sprintf(
						// translators: placeholder: courses.
						esc_html_x( '%s per page', 'placeholder: courses', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'validate_callback' => array( $this, 'validate_section_field_per_page' ),
					'validate_args'     => array(
						'allow_empty' => 1,
					),
					'parent_setting'    => 'profile_enabled',
				),
				'quiz_num'        => array(
					'name'              => 'quiz_num',
					'type'              => 'number',
					'label'             => sprintf(
						// translators: placeholders: Quiz.
						esc_html_x( '%s Attempts', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'             => $this->setting_option_values['quiz_num'],
					'attrs'             => array(
						'step' => 1,
						'min'  => 0,
					),
					'input_label'       => sprintf(
						// translators: placeholder: quizzes.
						esc_html_x( '%s per page', 'placeholder: quizzes', 'learndash' ),
						learndash_get_custom_label_lower( 'quizzes' )
					),
					'validate_callback' => array( $this, 'validate_section_field_per_page' ),
					'validate_args'     => array(
						'allow_empty' => 1,
					),
					'parent_setting'    => 'profile_enabled',
				),

				'per_page'        => array(
					'name'              => 'per_page',
					'type'              => 'number',
					'label'             => esc_html__( 'Shortcodes & Widgets', 'learndash' ),
					'help_text'         => esc_html__( 'Controls the global pagination for the LD shortcodes as well as courseinfo widget. These can be overridden individually.', 'learndash' ),
					'value'             => $this->setting_option_values['per_page'],
					'attrs'             => array(
						'step' => 1,
						'min'  => 0,
					),
					'validate_callback' => array( $this, 'validate_section_field_per_page' ),
					'validate_args'     => array(
						'allow_empty' => 1,
					),
				),
				'question_num'    => array(
					'name'              => 'question_num',
					'type'              => 'number',
					'label'             => sprintf(
						// translators: placeholders: Question.
						esc_html_x( 'Backend %s Widget', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'help_text'         => sprintf(
						// translators: placeholders: Questions, quiz, question.
						esc_html_x( 'Controls the pagination for the %1$s admin widget when editing a %2$s or %3$s.', 'Questions, quiz, question', 'learndash' ),
						learndash_get_custom_label( 'questions' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label_lower( 'question' )
					),
					'value'             => $this->setting_option_values['question_num'],
					'attrs'             => array(
						'step' => 1,
						'min'  => 0,
					),
					'validate_callback' => array( $this, 'validate_section_field_per_page' ),
					'validate_args'     => array(
						'allow_empty' => 1,
					),
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_General_Per_Page::add_section_instance();
	}
);
