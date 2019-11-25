<?php
/**
 * LearnDash Settings Page Quizzes Options.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Quizzes_Options' ) ) ) {

	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Quizzes_Options extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 */
		public function __construct() {

			$this->parent_menu_page_url  = 'edit.php?post_type=sfwd-quiz';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'quizzes-options';
			$this->settings_tab_priority = 10;
			$this->settings_page_title   = esc_html_x( 'Settings', 'Quiz Settings', 'learndash' );
			$this->show_submit_meta      = true;
			$this->show_quick_links_meta = true;

			parent::__construct();
		}

		/**
		 * Action hook to handle admin_tabs processing from LearnDash.
		 *
		 * @param string $admin_menu_section Current admin menu section.
		 */
		public function admin_tabs( $admin_menu_section ) {
			if ( ( $admin_menu_section === $this->parent_menu_page_url ) || ( 'edit.php?post_type=sfwd-essays' ) ) {
				learndash_add_admin_tab_item(
					$this->parent_menu_page_url,
					array(
						'id'   => $this->settings_screen_id,
						'link' => add_query_arg( array( 'page' => $this->settings_page_id ), 'admin.php' ),
						'name' => ! empty( $this->settings_tab_title ) ? $this->settings_tab_title : $this->settings_page_title,
					),
					$this->settings_tab_priority
				);
			}
		}
		// End of functions.
	}
}

add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Quizzes_Options::add_page_instance();
	}
);
