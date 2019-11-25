<?php
/**
 * LearnDash Settings Section for Data Upgrades Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Data_Upgrades' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Data_Upgrades extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_data_upgrades';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_data_upgrades';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_data_upgrades';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_data_upgrades';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Data Upgrades', 'learndash' );

			parent::__construct();
		}

		/**
		 * Show Settings Section meta box.
		 */
		public function show_meta_box() {
			$ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();
			$ld_admin_data_upgrades->admin_page();
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Data_Upgrades::add_section_instance();
	}
);
