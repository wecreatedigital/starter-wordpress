<?php
/**
 * LearnDash Settings Section for Quizzes Builder Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Quizzes_Builder' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Quizzes_Builder extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {

			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz_page_quizzes-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'quizzes-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_quizzes_builder';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_quizzes_builder';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'quiz_builder';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Builder', 'Quiz Builder', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);

			$this->settings_section_description = sprintf(
				// translators: placeholder: Course, Lessons, Topics, Quizzes, Course.
				esc_html_x(
					'Enables the %1$s Builder interface. This will allow you to manage %2$s within the %3$s editor screen.',
					'placeholder: Quiz, Questions, Quiz',
					'learndash'
				),
				LearnDash_Custom_Label::get_label( 'quiz' ),
				LearnDash_Custom_Label::get_label( 'questions' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);

			parent::__construct();

			add_filter( 'pre_update_option_' . $this->setting_option_key, array( $this, 'pre_update_options' ), 20, 3 );
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			// If the settings set as a whole is empty then we set a default.
			if ( empty( $this->setting_option_values ) ) {
				if ( true === is_data_upgrade_quiz_questions_updated() ) {
					$this->setting_option_values['enabled'] = 'yes';
				} else {
					$this->setting_option_values['enabled'] = '';
				}
				$this->setting_option_values['shared_questions'] = '';
			}

			if ( ! isset( $this->setting_option_values['enabled'] ) ) {
				$this->setting_option_values['enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['per_page'] ) ) {
				$this->setting_option_values['per_page'] = 25;
			} else {
				$this->setting_option_values['per_page'] = absint( $this->setting_option_values['per_page'] );
			}

			if ( empty( $this->setting_option_values['per_page'] ) ) {
				$this->setting_option_values['per_page'] = 25;
			}

			if ( ! isset( $this->setting_option_values['force_quiz_builder'] ) ) {
				$this->setting_option_values['force_quiz_builder'] = '';
			}
			if ( ! isset( $this->setting_option_values['force_shared_questions'] ) ) {
				$this->setting_option_values['force_shared_questions'] = '';
			}

			if ( true !== is_data_upgrade_quiz_questions_updated() ) {
				$this->setting_option_values['enabled']                = '';
				$this->setting_option_values['shared_questions']       = '';
				$this->setting_option_values['force_quiz_builder']     = '';
				$this->setting_option_values['force_shared_questions'] = '';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			$desc_before_enabled = '';
			if ( true !== is_data_upgrade_quiz_questions_updated() ) {
				// Used to show the section description above the fields. Can be empty.
				$desc_before_enabled = '<span class="error">' . sprintf(
					// translators: placeholder: Link to Data Upgrade page.
					_x( 'The Data Upgrade %s must be run to enable the following settings.', 'placeholder: Link to Data Upgrade page', 'learndash' ),
					'<strong><a href="' . add_query_arg( 'page', 'learndash_data_upgrades', 'admin.php' ) . '">Upgrade WPProQuiz Question</a></strong>'
				) . '</span>';
			}

			$this->setting_option_fields = array(
				'enabled'                => array(
					'name'                => 'enabled',
					'type'                => 'checkbox-switch',
					'desc_before'         => $desc_before_enabled,
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Builder Interface', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'               => isset( $this->setting_option_values['enabled'] ) ? $this->setting_option_values['enabled'] : '',
					'options'             => array(
						'yes' => esc_html__( 'Enabled', 'learndash' ),
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['enabled'] ) ? 'open' : 'closed',
				),
				'per_page'               => array(
					'name'           => 'per_page',
					'type'           => 'number',
					'label'          => esc_html__( 'Selector Items Per Page', 'learndash' ),
					'value'          => $this->setting_option_values['per_page'],
					'class'          => 'small-text',
					'attrs'          => array(
						'step' => 1,
						'min'  => 0,
					),
					'parent_setting' => 'enabled',
				),
				'shared_questions'       => array(
					'name'           => 'shared_questions',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
						// translators: placeholder: Quiz, Questions.
						esc_html_x( 'Shared %1$s %2$s', 'placeholder: Quiz, Questions', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' ),
						LearnDash_Custom_Label::get_label( 'questions' )
					),
					'value'          => isset( $this->setting_option_values['shared_questions'] ) ? $this->setting_option_values['shared_questions'] : '',
					'options'        => array(
						'yes' => esc_html__( 'Enabled', 'learndash' ),
					),
					'parent_setting' => 'enabled',
				),
				'force_quiz_builder'     => array(
					'name'  => 'force_quiz_builder',
					'label' => 'force_quiz_builder',
					'type'  => 'hidden',
					'value' => $this->setting_option_values['force_quiz_builder'],
				),
				'force_shared_questions' => array(
					'name'  => 'force_shared_questions',
					'label' => 'force_shared_questions',
					'type'  => 'hidden',
					'value' => $this->setting_option_values['force_shared_questions'],
				),
			);

			if ( true !== is_data_upgrade_quiz_questions_updated() ) {
				$this->setting_option_fields['enabled']['attrs'] = array(
					'disabled' => 'disabled',
				);

				$this->setting_option_fields['per_page']['attrs']         = array(
					'disabled' => 'disabled',
				);
				$this->setting_option_fields['shared_questions']['attrs'] = array(
					'disabled' => 'disabled',
				);
			}

			if ( 'yes' === $this->setting_option_values['force_quiz_builder'] ) {
				$this->setting_option_fields['enabled']['attrs'] = array(
					'disabled' => 'disabled',
				);
			}

			if ( 'yes' === $this->setting_option_values['force_shared_questions'] ) {
				$this->setting_option_fields['shared_questions']['attrs'] = array(
					'disabled' => 'disabled',
				);
			}

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Custom save function because we need to update the WPProQuiz settings with the saved value.
		 */
		public function pre_update_options( $current_values = '', $old_values = '', $option = '' ) {
			if ( $option === $this->setting_option_key ) {
				if ( ( isset( $current_values['force_quiz_builder'] ) ) && ( 'yes' === $current_values['force_quiz_builder'] ) ) {
					$current_values['enabled'] = 'yes';
				}
				if ( ( isset( $current_values['force_shared_questions'] ) ) && ( 'yes' === $current_values['force_shared_questions'] ) ) {
					$current_values['shared_questions'] = 'yes';
				}

				if ( ( isset( $current_values['shared_questions'] ) ) && ( 'yes' === $current_values['shared_questions'] ) ) {
					if ( ( ! isset( $current_values['enabled'] ) ) || ( 'yes' !== $current_values['enabled'] ) ) {
						$current_values['shared_questions'] = 'no';
					}
				}
			}

			return $current_values;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Quizzes_Builder::add_section_instance();
	}
);
