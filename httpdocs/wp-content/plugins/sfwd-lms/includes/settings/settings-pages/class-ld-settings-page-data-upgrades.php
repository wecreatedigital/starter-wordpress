<?php
/**
 * LearnDash Settings Page Data Upgrades].
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Data_Upgrades' ) ) ) {
	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Data_Upgrades extends LearnDash_Settings_Page {
		/**
		 * Private instance to our Data Upgrade object.
		 *
		 * @var object ld_admin_data_upgrades Instance of object.
		 */
		private $ld_admin_data_upgrades;

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_settings';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash_data_upgrades';
			$this->settings_page_title   = esc_html__( 'Data Upgrades', 'learndash' );
			$this->settings_tab_title    = $this->settings_page_title;
			$this->settings_tab_priority = 30;
			$this->settings_columns      = 1;
			$this->show_submit_meta      = false;
			$this->show_quick_links_meta = false;

			parent::__construct();
		}

		/**
		 * Action function called when Add-ons page is loaded.
		 *
		 * @since 2.5.5
		 */
		public function load_settings_page() {
			global $learndash_assets_loaded;

			parent::load_settings_page();

			wp_enqueue_style(
				'learndash-admin-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-style' . leardash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash-admin-style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash-admin-style'] = __FUNCTION__;

			wp_enqueue_script(
				'learndash-admin-settings-data-upgrades-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-settings-data-upgrades' . leardash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);

			$learndash_assets_loaded['scripts']['learndash-admin-settings-data-upgrades-script'] = __FUNCTION__;

			$ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Data_Upgrades::add_page_instance();
	}
);



