<?php
/**
 * LearnDash Settings Page Questions Options.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Questions_Options' ) ) ) {

	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Questions_Options extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 */
		public function __construct() {

			$this->parent_menu_page_url  = 'edit.php?post_type=sfwd-question';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'questions-options';
			$this->settings_tab_priority = 10;
			$this->settings_page_title   = esc_html_x( 'Settings', 'Question Settings', 'learndash' );
			$this->show_submit_meta      = true;
			$this->show_quick_links_meta = true;

			parent::__construct();
		}
	}
}

add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Questions_Options::add_page_instance();
	}
);
