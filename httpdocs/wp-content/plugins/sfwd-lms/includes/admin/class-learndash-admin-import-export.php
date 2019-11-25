<?php
/**
 * LearnDash Settings Page Add-ons.
 *
 * @package LearnDash
 * @subpackage Add-on Updates
 */

if ( ! class_exists( 'Learndash_Admin_Import_Export' ) ) {
	/**
	 * Class to create Addons list table.
	 */
	class Learndash_Admin_Import_Export {

		/**
		 * List table constructor.
		 */
		public function __construct() {
		}

		/**
		 * Show the Import/Export module UI.
		 */
		public function show() {
			/*
			$export_post_types = array(
				'sfwd-courses' => array(
					'post_type' => 'sfwd-courses',
					''
					'label' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ),
					),
				)
			);
			*/
		}
		// End of functions.
	}
	// End of Class.
}