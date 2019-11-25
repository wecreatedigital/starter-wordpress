<?php
/**
 * LearnDash LearnDash 3.0 Theme Register.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Theme_Register' ) ) && ( ! class_exists( 'LearnDash_Theme_Register_LD30' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Theme_Register_LD30 extends LearnDash_Theme_Register {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->theme_key          = 'ld30';
			$this->theme_name         = esc_html__( 'LearnDash 3.0', 'learndash' );
			$this->theme_base_dir     = trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'themes/' . $this->theme_key;
			$this->theme_base_url     = trailingslashit( LEARNDASH_LMS_PLUGIN_URL ) . 'themes/' . $this->theme_key;
			$this->theme_template_dir = $this->theme_base_dir . '/templates';
			$this->theme_template_url = $this->theme_base_url . '/templates';
		}
	}
}

add_action(
	'learndash_themes_init',
	function() {
		LearnDash_Theme_Register_LD30::add_theme_instance( 'ld30' );
	}
);


if ( learndash_is_active_theme( 'ld30' ) ) {
	// Include the helper functions
	include_once __DIR__ . '/helpers.php';
}
