<?php
/**
 * LearnDash Settings Metabox for Lesson Access Settings.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Lesson_Access_Settings' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Lesson_Access_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-lessons';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-lesson-access-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Lesson.
				esc_html_x( '%s Access Settings', 'placeholder: Lesson', 'learndash' ),
				learndash_get_custom_label( 'lesson' )
			);

			$this->settings_section_description = sprintf(
				// translators: placeholder: lessons.
				esc_html_x( 'Controls the timing and way %s can be accessed.', 'placeholder: lessons', 'learndash' ),
				learndash_get_custom_label_lower( 'lessons' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'lesson_schedule'             => 'lesson_schedule',

				'course'                      => 'course',
				'sample_lesson'               => 'sample_lesson',
				'visible_after'               => 'visible_after',
				'visible_after_specific_date' => 'visible_after_specific_date',
			);
			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				unset( $this->settings_fields_map['course'] );
			}

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( true === $this->settings_values_loaded ) {

				if ( ! isset( $this->setting_option_values['lesson_materials_enabled'] ) ) {
					$this->setting_option_values['lesson_materials_enabled'] = '';
					if ( ( isset( $this->setting_option_values['lesson_materials'] ) ) && ( ! empty( $this->setting_option_values['lesson_materials'] ) ) ) {
						$this->setting_option_values['lesson_materials_enabled'] = 'on';
					}
				}

				if ( ! isset( $this->setting_option_values['course'] ) ) {
					$this->setting_option_values['course'] = '';
				}

				if ( ! isset( $this->setting_option_values['sample_lesson'] ) ) {
					$this->setting_option_values['sample_lesson'] = '';
				}

				if ( ! isset( $this->setting_option_values['visible_after'] ) ) {
					$this->setting_option_values['visible_after'] = '';
				}

				if ( ! isset( $this->setting_option_values['visible_after_specific_date'] ) ) {
					$this->setting_option_values['visible_after_specific_date'] = '';
				}

				if ( ! empty( $this->setting_option_values['visible_after'] ) ) {
					$this->setting_option_values['lesson_schedule'] = 'visible_after';
				} else if ( ! empty( $this->setting_option_values['visible_after_specific_date'] ) ) {
					$this->setting_option_values['lesson_schedule'] = 'visible_after_specific_date';
				} else {
					$this->setting_option_values['lesson_schedule'] = '';
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$billing_cycle_html = $sfwd_lms->learndash_course_price_billing_cycle_html();

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

			$this->setting_option_fields = array(
				'visible_after' => array(
					'name'        => 'visible_after',
					'type'        => 'number',
					'value'       => $this->setting_option_values['visible_after'],
					'class'       => 'small-text',
					'label_none'  => true,
					'input_full'  => true,
					'input_label' => esc_html__( 'day(s)', 'learndash' ),
					'attrs'       => array(
						'step' => 1,
						'min'  => 0,
					),
					'default'     => 0,
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['lesson_schedule_visible_after_days_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'visible_after_specific_date' => array(
					'name'       => 'visible_after_specific_date',
					'value'      => $this->setting_option_values['visible_after_specific_date'],
					'label_none' => true,
					'input_full' => true,
					'type'       => 'date-entry',
					'class'      => 'learndash-datepicker-field',
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['visible_after_specific_date_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'course'          => array(
					'name'      => 'course',
					'label'     => sprintf(
						// Translators: placeholder: Course.
						esc_html_x( 'Associated %s', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'      => 'select',
					'default'   => '',
					'value'     => $this->setting_option_values['course'],
					'lazy_load' => true,
					'default'   => '',
					'options'   => $select_course_options,
				),
				'sample_lesson'   => array(
					'name'    => 'sample_lesson',
					'label'   => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Sample %s', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'type'    => 'checkbox-switch',
					'value'   => $this->setting_option_values['sample_lesson'],
					'options' => array(
						'on' => sprintf(
							// Translators: placeholder: lesson, course.
							esc_html_x( 'This %1$s is accessible to all visitors regardless of %2$s enrollment', 'placeholder: lesson, course', 'learndash' ),
							learndash_get_custom_label_lower( 'lesson' ),
							learndash_get_custom_label_lower( 'course' )
						),
						''   => '',
					),
				),
				'lesson_schedule' => array(
					'name'    => 'lesson_schedule',
					'label'   => sprintf(
						// Translators: placeholder: Lesson.
						esc_html_x( '%s Release Schedule', 'placeholder: Lessons', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'type'    => 'radio',
					'value'   => $this->setting_option_values['lesson_schedule'],
					'options' => array(
						''                            => array(
							'label'       => esc_html__( 'Immediately', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: lesson, course.
								esc_html_x( 'The %1$s is made available on %2$s enrollment.', 'placeholder: lesson, course', 'learndash' ),
								learndash_get_custom_label_lower( 'lesson' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'visible_after'               => array(
							'label'               => esc_html__( 'Enrollment-based', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: lesson, course.
								esc_html_x( 'The %1$s will be available X days after %2$s enrollment.', 'placeholder: lesson, course.', 'learndash' ),
								learndash_get_custom_label_lower( 'lesson' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => array(
								'lesson_schedule_visible_after_days' => $this->settings_sub_option_fields['lesson_schedule_visible_after_days_fields'],
							),
							'inner_section_state' => ( 'visible_after' === $this->setting_option_values['lesson_schedule'] ) ? 'open' : 'closed',
						),
						'visible_after_specific_date' => array(
							'label'               => esc_html__( 'Specific date', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholders: lesson.
								esc_html_x( 'The %s will be available on a specific date.', 'placeholders: lesson', 'learndash' ),
								learndash_get_custom_label_lower( 'lesson' )
							),
							'inline_fields'       => array(
								'visible_after_specific_date' => $this->settings_sub_option_fields['visible_after_specific_date_fields'],
							),
							'inner_section_state' => ( 'visible_after_specific_date' === $this->setting_option_values['lesson_schedule'] ) ? 'open' : 'closed',
						),
					),
				),
			);

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				unset( $this->setting_option_fields['course'] );
			}

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
				/**
				 * Check the Course Materials set course_points_enabled/course_points/course_points_access. If 'course_points_enabled' setting is
				 * 'on' then make sure 'course_points' and 'course_points_access' are not empty.
				 */
				if ( isset( $settings_values['lesson_schedule'] ) ) {
					switch ( $settings_values['lesson_schedule'] ) {
						case 'visible_after':
							$settings_values['visible_after_specific_date'] = '';
							break;

						case 'visible_after_specific_date':
							$settings_values['visible_after'] = '';
							break;

						case '':
						default:
							$settings_values['visible_after']               = '';
							$settings_values['visible_after_specific_date'] = '';
							break;
					}
				}

				if ( ( ! isset( $settings_values['course'] ) ) || ( '-1' === $settings_values['course'] ) ) {
					$settings_values['course'] = '';
				}
				if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
					unset( $settings_values['course'] );
				}
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'lesson' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Lesson_Access_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Lesson_Access_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Lesson_Access_Settings'] = LearnDash_Settings_Metabox_Lesson_Access_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
