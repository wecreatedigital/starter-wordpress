<?php
/**
 * LearnDash Settings Section for Course Builder Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Courses_Builder' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Courses_Builder extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {

			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-courses_page_courses-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'courses-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_courses_builder';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_courses_builder';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'course_builder';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Builder', 'placeholder: Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);

			// Used to show the section description above the fields. Can be empty
			$this->settings_section_description = sprintf(
				// translators: placeholder: course.
				esc_html_x( 'Control settings for %s creation, and visual organization', 'placeholder: course', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
			);

			parent::__construct();

			$this->save_settings_fields();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			// If the settings set as a whole is empty then we set a default.
			if ( empty( $this->setting_option_values ) ) {
				$this->setting_option_values['enabled']      = 'yes';
				$this->setting_option_values['shared_steps'] = '';
			}

			if ( ! isset( $this->setting_option_values['enabled'] ) ) {
				$this->setting_option_values['enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['shared_steps'] ) ) {
				$this->setting_option_values['shared_steps'] = '';
			}

			if ( ! isset( $this->setting_option_values['per_page'] ) ) {
				$this->setting_option_values['per_page'] = 25;
			} else {
				$this->setting_option_values['per_page'] = intval( $this->setting_option_values['per_page'] );
			}

			if ( empty( $this->setting_option_values['per_page'] ) ) {
				$this->setting_option_values['per_page'] = 25;
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array(
				'enabled'      => array(
					'name'                => 'enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Builder', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text'           => sprintf(
						// translators: placeholder: Lesson, Topic, Quizze, Course.
						esc_html_x( 'Manage all %1$s, %2$s, and %3$s associations within the %4$s Builder.', 'placeholder: Lesson, Topic, Quizze, Course.', 'learndash' ),
						learndash_get_custom_label( 'lesson' ),
						learndash_get_custom_label( 'topic' ),
						learndash_get_custom_label( 'quiz' ),
						learndash_get_custom_label( 'course' )
					),
					'value'               => isset( $this->setting_option_values['enabled'] ) ? $this->setting_option_values['enabled'] : '',
					'options'             => array(
						'yes' => '',
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['enabled'] ) ? 'open' : 'closed',
				),
				'per_page'     => array(
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
				'shared_steps' => array(
					'name'           => 'shared_steps',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Shared %s Steps', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'          => isset( $this->setting_option_values['shared_steps'] ) ? $this->setting_option_values['shared_steps'] : '',
					'options'        => array(
						'yes' => esc_html__( 'Enabled', 'learndash' ),
					),
					'parent_setting' => 'enabled',
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			global $wp_rewrite;
			if ( ! $wp_rewrite->using_permalinks() ) {
				$this->setting_option_fields['shared_steps']['value'] = '';
				$this->setting_option_fields['shared_steps']['attrs'] = array( 'disabled' => 'disabled' );
			}
			parent::load_settings_fields();
		}

		/**
		 * Save metabox values.
		 */
		public function save_settings_fields() {
			if ( isset( $_POST[ $this->setting_field_prefix ] ) ) {
				if ( ( isset( $_POST[ $this->setting_field_prefix ]['enabled'] ) ) && ( 'yes' === $_POST[ $this->setting_field_prefix ]['enabled'] ) && ( isset( $_POST[ $this->setting_field_prefix ]['shared_steps'] ) ) && ( 'yes' === $_POST[ $this->setting_field_prefix ]['shared_steps'] ) ) {

					$ld_permalink_options = get_option( 'learndash_settings_permalinks', array() );
					if ( ! isset( $ld_permalink_options['nested_urls'] ) ) {
						$ld_permalink_options['nested_urls'] = 'no';
					}

					if ( 'yes' !== $ld_permalink_options['nested_urls'] ) {
						$ld_permalink_options['nested_urls'] = 'yes';

						update_option( 'learndash_settings_permalinks', $ld_permalink_options );

						learndash_setup_rewrite_flush();
					}
				} else {
					$_POST[ $this->setting_field_prefix ]['shared_steps'] = '';
				}
			}
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Courses_Builder::add_section_instance();
	}
);
