<?php
/**
 * LearnDash Settings Metabox for Topic Access Settings.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Topic_Access_Settings' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Topic_Access_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-topic';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-topic-access-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Topic.
				esc_html_x( '%s Access Settings', 'placeholder: Topic', 'learndash' ),
				learndash_get_custom_label( 'topic' )
			);

			$this->settings_section_description = sprintf(
				// translators: placeholder: topic.
				esc_html_x( 'Controls how, where, and when the %s can be accessed.', 'placeholder: topic', 'learndash' ),
				learndash_get_custom_label( 'topic' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );
			add_filter( 'learndash_show_metabox', array( $this, 'check_show_metabox' ), 50, 2 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'course' => 'course',
				'lesson' => 'lesson',
			);

			parent::__construct();
		}

		/**
		 * Hook into filter before the metabox is registered in WP.
		 *
		 * @since 3.0
		 * @param boolean $show_metabox True or False to show metabox.
		 * @param string  $settings_metabox_key metabox key.
		 * @return boolean $show_metabox
		 */
		public function check_show_metabox( $show_metabox, $settings_metabox_key = '' ) {
			if ( $settings_metabox_key === $this->settings_metabox_key ) {
				// IF Course shared Steps is enabled we don't show this metabox.
				if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
					$show_metabox = false;
				}
			}

			return $show_metabox;
		}
		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();
			if ( true === $this->settings_values_loaded ) {
				if ( ! isset( $this->setting_option_values['course'] ) ) {
					$this->setting_option_values['course'] = '';
				}
				if ( ! isset( $this->setting_option_values['lesson'] ) ) {
					$this->setting_option_values['lesson'] = '';
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$select_course_options = $sfwd_lms->select_a_course();
			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
				$select_course_options_default = array(
					'-1' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Search or select a %s…', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
				);
			} else {
				$select_course_options_default = array(
					'' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Select %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
				);
			}
			$select_course_options = $select_course_options_default + $select_course_options;

			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
				$select_lesson_options_default = array(
					'-1' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Search or select a %s…', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
				);
			} else {
				$select_lesson_options_default = array(
					'' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Select %s', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
				);
			}

			$select_lesson_options = array();
			if ( ( isset( $this->setting_option_values['course'] ) ) && ( ! empty( $this->setting_option_values['course'] ) ) ) {
				$select_lesson_options = $sfwd_lms->select_a_lesson( absint( $this->setting_option_values['course'] ) );
				if ( ( is_array( $select_lesson_options ) ) && ( ! empty( $select_lesson_options ) ) ) {
					if ( isset( $select_lesson_options[0] ) ) {
						unset( $select_lesson_options[0] );
					}

					$select_lesson_options = $select_lesson_options_default + $select_lesson_options;
				} else {
					$select_lesson_options = $select_lesson_options_default;
				}
			} else {
				$select_lesson_options = $select_lesson_options_default;
			}

			$this->setting_option_fields = array(
				'course' => array(
					'name'      => 'course',
					'label'     => sprintf(
						// translators: placeholders: course.
						esc_html_x( 'Associated %s', 'Associated Course Label', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'      => 'select',
					'lazy_load' => true,
					'help_text' => sprintf(
						// translators: placeholders: Topic, Course.
						esc_html_x( 'Associate this %1$s with a %2$s.', 'placeholder: Topic, Course.', 'learndash' ),
						learndash_get_custom_label( 'topic' ),
						learndash_get_custom_label( 'course' )
					),
					'default'   => '',
					'value'     => $this->setting_option_values['course'],
					'options'   => $select_course_options,
					'attrs'     => array(
						'data-ld_selector_nonce'   => wp_create_nonce( 'sfwd-courses' ),
						'data-ld_selector_default' => '1',
					),
				),
				'lesson' => array(
					'name'      => 'lesson',
					'label'     => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Associated %s', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'type'      => 'select',
					'lazy_load' => true,
					'help_text' => sprintf(
						// translators: placeholders: Lesson, Course.
						esc_html_x( 'Associate this %1$s with a %2$s.', 'placeholders: Lesson, Course', 'learndash' ),
						learndash_get_custom_label( 'lesson' ),
						learndash_get_custom_label( 'course' )
					),
					'default'   => '',
					'value'     => $this->setting_option_values['lesson'],
					'options'   => $select_lesson_options,
					'attrs'     => array(
						'data-ld_selector_nonce'   => wp_create_nonce( 'sfwd-lessons' ),
						'data-ld_selector_default' => '1',
					),
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Filter settings values for metabox before save to database.
		 *
		 * @param array $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( $settings_metabox_key === $this->settings_metabox_key ) ) {

				if ( ( ! isset( $settings_values['course'] ) ) || ( '-1' === $settings_values['course'] ) ) {
					$settings_values['course'] = '';
				}

				if ( ( ! isset( $settings_values['lesson'] ) ) || ( '-1' === $settings_values['lesson'] ) ) {
					$settings_values['lesson'] = '';
				}
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'topic' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Topic_Access_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Topic_Access_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Topic_Access_Settings'] = LearnDash_Settings_Metabox_Topic_Access_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}

