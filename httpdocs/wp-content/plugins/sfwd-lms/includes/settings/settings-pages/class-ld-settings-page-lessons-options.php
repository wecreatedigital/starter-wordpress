<?php
/**
 * LearnDash Settings Page Lessons Options.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Lessons_Options' ) ) ) {
	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Lessons_Options extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 */
		public function __construct() {

			$this->parent_menu_page_url = 'edit.php?post_type=sfwd-lessons';
			$this->menu_page_capability = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id     = 'lessons-options';
			$this->settings_page_title  = esc_html_x( 'Settings', 'Lesson Settings', 'learndash' );

			parent::__construct();
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Lessons_Options::add_page_instance();
	}
);
