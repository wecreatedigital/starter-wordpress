<?php
/**
 * LearnDash Settings Section for Lessons Taxonomies Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Lessons_Taxonomies' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Lessons_Taxonomies extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_screen_id = 'sfwd-lessons_page_lessons-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'lessons-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_lessons_taxonomies';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_lessons_taxonomies';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'taxonomies';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Lesson.
				esc_html_x( '%s Taxonomies', 'placeholder: Lesson', 'learndash' ),
				learndash_get_custom_label( 'lesson' )
			);

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: lessons.
				esc_html_x( 'Control which taxonomies can be used to better organize your LearnDash %s.', 'placeholder: Lessons', 'learndash' ),
				learndash_get_custom_label_lower( 'lessons' )
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
				$__init                      = true;
				$this->setting_option_values = array(
					'ld_lesson_category' => 'yes',
					'ld_lesson_tag'      => 'yes',
					'wp_post_category'   => 'yes',
					'wp_post_tag'        => 'yes',
				);

				// If this is a new install we want to turn off WP Post Category/Tag.
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php';
				$this->ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();

				$ld_prior_version = $this->ld_admin_data_upgrades->get_data_settings( 'prior_version' );
				if ( 'new' === $ld_prior_version ) {
					$this->setting_option_values['wp_post_category'] = '';
					$this->setting_option_values['wp_post_tag']      = '';
				}
			}

			$this->setting_option_values = wp_parse_args(
				$this->setting_option_values,
				array(
					'ld_lesson_category' => '',
					'ld_lesson_tag'      => '',
					'wp_post_category'   => '',
					'wp_post_tag'        => '',
				)
			);
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'ld_lesson_category' => array(
					'name'    => 'ld_lesson_category',
					'type'    => 'checkbox-switch',
					'label'   => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( '%s Categories', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'value'   => $this->setting_option_values['ld_lesson_category'],
					'options' => array(
						''    => '',
						'yes' => sprintf(
							// translators: placeholder: Lesson.
							esc_html_x( 'Manage %s Categories via the Actions dropdown', 'placeholder: Lesson', 'learndash' ),
							learndash_get_custom_label( 'lesson' )
						),
					),
				),
				'ld_lesson_tag'      => array(
					'name'    => 'ld_lesson_tag',
					'type'    => 'checkbox-switch',
					'label'   => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( '%s Tags', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'value'   => $this->setting_option_values['ld_lesson_tag'],
					'options' => array(
						''    => '',
						'yes' => sprintf(
							// translators: placeholder: Lesson.
							esc_html_x( 'Manage %s Tags via the Actions dropdown', 'placeholder: Lesson', 'learndash' ),
							learndash_get_custom_label( 'lesson' )
						),
					),
				),
				'wp_post_category'   => array(
					'name'    => 'wp_post_category',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'WP Post Categories', 'learndash' ),
					'value'   => $this->setting_option_values['wp_post_category'],
					'options' => array(
						''    => '',
						'yes' => esc_html__( 'Manage WP Categories via the Actions dropdown', 'learndash' ),
					),
				),
				'wp_post_tag'        => array(
					'name'    => 'wp_post_tag',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'WP Post Tags', 'learndash' ),
					'value'   => $this->setting_option_values['wp_post_tag'],
					'options' => array(
						''    => '',
						'yes' => esc_html__( 'Manage WP Tags via the Actions dropdown', 'learndash' ),
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
		LearnDash_Settings_Lessons_Taxonomies::add_section_instance();
	}
);
