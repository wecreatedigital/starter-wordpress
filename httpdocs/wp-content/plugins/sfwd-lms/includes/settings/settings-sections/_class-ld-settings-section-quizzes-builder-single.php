<?php
/**
 * LearnDash Settings Section for Quiz Builder Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Quizzes_Builder_Single' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Quizzes_Builder_Single extends LearnDash_Settings_Section {

		private $cb;

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz_page_quizzes-builder';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'quizzes-builder';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_quizzes_builder';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_quizzes_builder';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'quizzes_builder';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Builder', 'Quiz Builder', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);

			// Add a cstom callback for our metabox as we don't display a list of settings fields.
			$this->settings_fields_callback = array( $this, 'display_meta_box_inside' );

			parent::__construct();
		}

		/**
		 * Display metabox content.
		 */
		public function display_meta_box_inside() {
			if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
				$quiz_id   = intval( $_GET['quiz_id'] );
				$quiz_post = get_post( $quiz_id );
				if ( ( is_a( $quiz_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $quiz_post->post_type ) ) {
					$this->cb = Learndash_Admin_Metabox_Quiz_Builder::add_instance( 'Learndash_Admin_Metabox_Quiz_Builder' );
					$this->cb->show_builder_box( $quiz_post );
				}
			}
		}

		// End of functions.
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Quizzes_Builder_Single::add_section_instance();
	}
);
