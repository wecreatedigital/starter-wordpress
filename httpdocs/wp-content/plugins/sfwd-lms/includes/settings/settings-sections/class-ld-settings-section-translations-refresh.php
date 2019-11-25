<?php
/**
 * LearnDash Settings Section for Translations Refresh Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Translations_Refresh' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Translations_Refresh extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {

			$this->settings_page_id = 'learndash_lms_translations';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'submitdiv';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Refresh Translations', 'learndash' );

			$this->metabox_context  = 'side';
			$this->metabox_priority = 'high';

			parent::__construct();

			// We override the parent value set for $this->metabox_key because we want the div ID to match the details WordPress
			// value so it will be hidden.
			$this->metabox_key = 'submitdiv';
		}

		/**
		 * Custom function to metabox.
		 *
		 * @since 2.4.0
		 */
		public function show_meta_box() {
			?>
			<div id="submitpost" class="submitbox">

				<div id="major-publishing-actions">
					<div id="publishing-action">
						<span class="spinner"></span>
						<input type="hidden" name="translations" value="refresh" />

						<?php
							$last_update_time = LearnDash_Translations::get_last_update();
						?>
						<?php if ( ! is_null( $last_update_time ) ) { ?>
							<p class="learndash-translations-last-update"><span class="label"><?php esc_html_e( 'Updated', 'learndash' ); ?></span>: <span class="value"><?php echo learndash_adjust_date_time_display( $last_update_time, 'M d, Y h:ia' ); ?></span></p>
						<?php } ?>
						<a id="learndash-translation-refresh" class="button button-primary learndash-translations-refresh" href="<?php echo LearnDash_Translations::get_action_url( 'refresh' ); ?> "><?php esc_html_e( 'Refresh', 'learndash' ); ?></a>
					</div>

					<div class="clear"></div>

				</div><!-- #major-publishing-actions -->

			</div><!-- #submitpost -->
			<?php
		}

		/**
		 * This is a requires function.
		 */
		public function load_settings_fields() {

		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Translations_Refresh::add_section_instance();
	}
);
