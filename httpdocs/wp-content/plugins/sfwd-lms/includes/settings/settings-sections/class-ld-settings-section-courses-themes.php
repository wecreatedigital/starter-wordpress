<?php
/**
 * LearnDash Settings Section for Course Themes Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Courses_Themes' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Courses_Themes extends LearnDash_Settings_Section {

		private $themes_list = array();
		/**
		 * Protected constructor for class
		 */
		protected function __construct() {

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'learndash_lms_settings';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_courses_themes';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_courses_themes';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'themes';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Design & Content Elements', 'learndash' );

			// Used to show the section description above the fields. Can be empty
			$this->settings_section_description = esc_html__( 'Alter the look and feel of your Learning Management System', 'learndash' );

			add_action( 'learndash_section_fields_after', array( $this, 'learndash_section_fields_after' ), 10, 2 );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$themes = LearnDash_Theme_Register::get_themes();

			$this->themes_list = array();
			foreach ( $themes as $theme ) {
				$this->themes_list[ $theme['theme_key'] ] = $theme['theme_name'];
			}

			if ( ( ! isset( $this->setting_option_values['active_theme'] ) ) || ( empty( $this->setting_option_values['active_theme'] ) ) ) {
				$ld_prior_version = learndash_get_prior_installed_version();
				if ( ( ! $ld_prior_version ) || ( 'new' === $ld_prior_version ) ) {
					$this->setting_option_values['active_theme'] = LEARNDASH_DEFAULT_THEME;
				} else {
					$this->setting_option_values['active_theme'] = LEARNDASH_LEGACY_THEME;
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array(
				'active_theme' => array(
					'name'      => 'active_theme',
					'type'      => 'select',
					'label'     => esc_html__( 'Active Template', 'learndash' ),
					'help_text' => esc_html__( 'New front-end design options and settings can be used when the LearnDash 3.0 template is activated.', 'learndash' ),
					'value'     => $this->setting_option_values['active_theme'],
					'options'   => $this->themes_list,
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		public function learndash_section_fields_after( $settings_section_key = '', $settings_screen_id ) {
			if ( $settings_section_key === $this->settings_section_key ) {

				$themes = LearnDash_Theme_Register::get_themes();
				if ( ! empty( $themes ) ) {
					global $wp_settings_sections;
					global $wp_settings_fields;

					$active_theme_key = LearnDash_Theme_Register::get_active_theme_key();

					foreach ( $themes as $theme ) {
						$theme_instance          = LearnDash_Theme_Register::get_theme_instance( $theme['theme_key'] );
						$theme_settings_sections = $theme_instance->get_theme_settings_sections();
						if ( ! empty( $theme_settings_sections ) ) {
							foreach ( $theme_settings_sections as $section_key => $section_instance ) {
								if ( isset( $wp_settings_fields[ $section_instance->settings_page_id ][ $section_key ] ) ) {
									$theme_state = 'closed';
									if ( $active_theme_key === $theme_instance->get_theme_key() ) {
										$theme_state = 'open';
									}
									echo '<div id="learndash_theme_settings_section_' . $theme_instance->get_theme_key() . '" class="ld-theme-settings-section ld-theme-settings-section-' . $theme_instance->get_theme_key() . ' ld-theme-settings-section-state-' . $theme_state . '">';
									$this->show_settings_section_fields( $section_instance->settings_page_id, $section_key );
									echo '</div>';
								}
							}
						}
					}
				}
			}
		}

		// End of functions.
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Courses_Themes::add_section_instance();
	}
);
