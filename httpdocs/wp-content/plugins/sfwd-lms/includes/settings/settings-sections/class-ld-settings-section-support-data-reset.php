<?php
/**
 * LearnDash Settings Section for Support Data Reset Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Data_Reset' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Data_Reset extends LearnDash_Settings_Section {

		/**
		 * Settings set array for this section.
		 *
		 * @var array $settings_set Array of settings used by this section.
		 */
		protected $settings_set = array();

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_support';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'ld_data_reset';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_data_reset';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Reset ALL LearnDash Data', 'learndash' );

			$this->metabox_context  = 'side';
			$this->metabox_priority = 'high';


			add_action( 'learndash-settings-page-load', array( $this, 'on_settings_page_load' ), 10, 2 );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function on_settings_page_load( $settings_screen_id = '', $settings_page_id = '' ) {
			global $sfwd_lms;
			
			if ( $settings_page_id === $this->settings_page_id ) {
				if ( learndash_is_admin_user() ) {
					
					if ( ( isset( $_POST['ld_data_remove_nonce'] ) ) && ( ! empty( $_POST['ld_data_remove_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_data_remove_nonce'], 'ld_data_remove_' . get_current_user_id() ) ) ) {

						if ( ( isset( $_POST['ld_data_remove_verify'] ) ) && ( ! empty( $_POST['ld_data_remove_verify'] ) ) && ( wp_verify_nonce( $_POST['ld_data_remove_verify'], 'ld_data_remove_' . get_current_user_id() ) ) ) {
							learndash_delete_all_data();

							$active_plugins = (array) get_option( 'active_plugins', array() );
							if ( ! empty( $active_plugins ) ) {
								$active_plugins = array_diff( $active_plugins, array( LEARNDASH_LMS_PLUGIN_KEY ) );
								update_option( 'active_plugins', $active_plugins );

								// Hook into our own deactivate function.
								$sfwd_lms->deactivate();

								// finally redirect the admin to the plugins listing.
								wp_redirect( admin_url( 'plugins.php' ) );

								die();
							}
						}
					}
				}
			}
		}

		public function show_support_section( $settings_section_key = '', $settings_screen_id = '' ) {
			if ( $settings_section_key === $this->settings_section_key ) {
				if ( learndash_is_admin_user() ) {
					$remove_nonce = wp_create_nonce( 'ld_data_remove_' . get_current_user_id() );
					?>
					<hr style="margin-top: 30px; border-top: 5px solid red;"/>
					<div class="learndash-support-settings-desc"><p><?php _e( '<span style="color:red;">Warning: This will remove ALL LearnDash data including any custom database tables.</style></span>', 'learndash' ); ?></p></div>
					<hr style="margin-top: 0px; border-top: 5px solid red;"/>
					<form id="ld_data_remove_form" method="POST">
						<input type="hidden" name="ld_data_remove_nonce" value="<?php echo $remove_nonce; ?>" />
						<p>
							<label for="ld_data_remove_verify"><?php _e( '<strong>Confirm the data deletion</strong>', 'learndash' ); ?></label><br />
							<input id="ld_data_remove_verify" name="ld_data_remove_verify" type="text" size="50" style="width: 100%;" value="" data-confirm="<?php esc_html_e( 'Are you sure that you want to remove ALL LearnDash data?', 'learndash' ) ?>" /><br />
							<span class="description">
							<?php
							printf(
								// translators: placeholder: secret generated code.
								_x( 'Enter <code>%s</code> in the above field and click the submit button', 'placeholder: secret generated code', 'learndash' ),
								$remove_nonce
							);
						?>
						</span></p>
						<p><input type="submit" value="<?php esc_html_e( 'Submit', 'learndash' ); ?>" /></p>
					</form>
					<?php
						$js_confirm_message = esc_html__( 'Are you sure that you want to remove ALL LearnDash data?', 'learndash' );
					?>
					<script>
						
					</script>
					<?php
				}
			}
		}

		// End of functions.
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Data_Reset::add_section_instance();
	}
);
