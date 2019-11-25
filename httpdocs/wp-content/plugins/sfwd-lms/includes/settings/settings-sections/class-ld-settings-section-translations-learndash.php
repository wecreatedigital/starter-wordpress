<?php
/**
 * LearnDash Settings Section for Translations LearnDash Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Translations_LearnDash' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Translations_LearnDash extends LearnDash_Settings_Section {

		/**
		 * Must match the Text Domain.
		 *
		 * @var string $project_slug String for project.
		 */
		private $project_slug = 'learndash';

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_translations';

			$this->setting_option_key = 'learndash';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_translations_' . $this->project_slug;

			// Section label/header.
			$this->settings_section_label = esc_html__( 'LearnDash LMS', 'learndash' );

			LearnDash_Translations::register_translation_slug( $this->project_slug, LEARNDASH_LMS_PLUGIN_DIR . 'languages/' );

			parent::__construct();
		}

		/**
		 * Custom function to metabox.
		 *
		 * @since 2.4.0
		 */
		public function show_meta_box() {
			$ld_translations = new LearnDash_Translations( $this->project_slug );
			$ld_translations->show_meta_box();
		}
	}

	add_action(
		'init',
		function() {
			LearnDash_Settings_Section_Translations_LearnDash::add_section_instance();
		},
		1
	);
}
