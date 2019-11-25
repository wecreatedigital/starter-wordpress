<?php
/**
 * LearnDash Settings Section for Admin Users Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_General_Admin_User' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_General_Admin_User extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_settings';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_admin_user';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_admin_user';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_admin_user';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Admin User Settings', 'learndash' );

			$this->settings_section_description = sprintf(
				// translators: placeholder: courses.
				esc_html_x( 'Controls the admin user-experience navigating %s.', 'placeholder: courses', 'learndash' ),
				learndash_get_custom_label_lower( 'courses' )
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$_init = false;
			if ( false === $this->setting_option_values ) {
				$_init                       = true;
				$this->setting_option_values = array();
			}

			$this->setting_option_values = wp_parse_args(
				$this->setting_option_values,
				array(
					'courses_autoenroll_admin_users'   => ( true === $_init ) ? 'yes' : '',
					'bypass_course_limits_admin_users' => ( true === $_init ) ? 'yes' : '',
				)
			);

			if ( ! isset( $this->setting_option_values['courses_autoenroll_admin_users'] ) ) {
				$this->setting_option_values['courses_autoenroll_admin_users'] = '';
			}

			if ( ! isset( $this->setting_option_values['bypass_course_limits_admin_users'] ) ) {
				$this->setting_option_values['bypass_course_limits_admin_users'] = '';
			}

			if ( ! isset( $this->setting_option_values['reports_include_admin_users'] ) ) {
				$this->setting_option_values['reports_include_admin_users'] = '';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'courses_autoenroll_admin_users'   => array(
					'name'      => 'courses_autoenroll_admin_users',
					'type'      => 'checkbox-switch',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Auto-enrollment', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text' => sprintf(
						// translators: placeholder: courses, course.
						esc_html_x( 'Allow admin users to have access to %1$s automatically without requiring %2$s enrollment.', 'placeholder: courses, course', 'learndash' ),
						learndash_get_custom_label_lower( 'courses' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => $this->setting_option_values['courses_autoenroll_admin_users'],
					'options'   => array(
						''    => sprintf(
							// translators: placeholder: courses.
							esc_html_x( 'Admin has access to enrolled %s only', 'placeholder: courses', 'learndash' ),
							learndash_get_custom_label_lower( 'courses' )
						),
						'yes' => sprintf(
							// translators: placeholder: courses.
							esc_html_x( 'Admin has access to all %s automatically', 'placeholder: courses', 'learndash' ),
							learndash_get_custom_label_lower( 'courses' )
						),
					),
				),
				'bypass_course_limits_admin_users' => array(
					'name'      => 'bypass_course_limits_admin_users',
					'type'      => 'checkbox-switch',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Bypass %s limits', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text' => sprintf(
						// translators: placeholder:  course.
						esc_html_x( 'Allow admin users to access %s content in any order bypassing progression and access limitations', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => $this->setting_option_values['bypass_course_limits_admin_users'],
					'options'   => array(
						''    => esc_html__( 'Admin must follow the progression and access rules', 'learndash' ),
						'yes' => sprintf(
							// translators: placeholder:  course.
							esc_html_x( 'Admin can access %s content in any order', 'placeholder: course', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						),
					),
				),
				'reports_include_admin_users'      => array(
					'name'      => 'reports_include_admin_users',
					'type'      => 'checkbox-switch',
					'label'     => esc_html__( 'Include in Reports', 'learndash' ),
					'help_text' => esc_html__( ' Include admin users in reports, including ProPanel reporting.', 'learndash' ),
					'default'   => '',
					'value'     => $this->setting_option_values['reports_include_admin_users'],
					'options'   => array(
						''    => esc_html__( 'Admin is not included in reports', 'learndash' ),
						'yes' => esc_html__( 'Admin is included in reports', 'learndash' ),
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
		LearnDash_Settings_Section_General_Admin_User::add_section_instance();
	}
);
