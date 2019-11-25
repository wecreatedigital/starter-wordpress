<?php
/**
 * LearnDash Settings Page Quizzes Options.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Side_Submit' ) ) ) {
	/**
	 * Class to create the settings metabox.
	 */
	class LearnDash_Settings_Section_Side_Submit extends LearnDash_Settings_Section {

		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of class args.
		 */
		public function __construct( $args = array() ) {

			if ( ( isset( $args['settings_screen_id'] ) ) && ( ! empty( $args['settings_screen_id'] ) ) ) {
				$this->settings_screen_id = $args['settings_screen_id'];
			}

			if ( ( isset( $args['settings_page_id'] ) ) && ( ! empty( $args['settings_page_id'] ) ) ) {
				$this->settings_page_id = $args['settings_page_id'];
			}

			if ( ( ! empty( $this->settings_screen_id ) ) && ( ! empty( $this->settings_page_id ) ) ) {

				// This is the 'option_name' key used in the wp_options table.
				$this->setting_option_key = 'submitdiv';

				// Section label/header.
				$this->settings_section_label = esc_html__( 'Save Options', 'learndash' );

				$this->metabox_context  = 'side';
				$this->metabox_priority = 'high';

				parent::__construct();

				// We override the parent value set for $this->metabox_key because we want the div ID to match the details WordPress
				// value so it will be hidden.
				$this->metabox_key = 'submitdiv';
			}
		}

		/**
		 * Primary function to show the metabox output
		 */
		public function show_meta_box() {

			?>
			<div id="submitpost" class="submitbox">

				<div id="major-publishing-actions">

					<div id="publishing-action">
						<span class="spinner"></span>
						<?php submit_button( esc_html__( 'Save', 'learndash' ), 'primary', 'submit', false ); ?>
					</div>

					<div class="clear"></div>

				</div><!-- #major-publishing-actions -->

			</div><!-- #submitpost -->
			<?php
		}

		// This is a requires function
		public function load_settings_fields() {

		}
	}
}
