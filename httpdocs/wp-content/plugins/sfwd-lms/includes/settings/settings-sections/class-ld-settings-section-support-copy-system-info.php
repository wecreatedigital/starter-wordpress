<?php
/**
 * LearnDash Settings Section for Support Copy System Info Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_System_Info' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Support_System_Info extends LearnDash_Settings_Section {

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
			$this->setting_option_key = 'ld_copy_export';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_copy_export';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Copy System Info', 'learndash' );

			$this->metabox_context = 'side';

			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function show_support_section( $settings_section_key = '', $settings_screen_id = '' ) {
			if ( $settings_section_key === $this->settings_section_key ) {
				$support_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash_Settings_Page_Support' );
				if ( $support_page_instance ) {
					?>
					<textarea id="ld-system-info-text" style="width: 100%; min-height: 80px; font-family: monospace"><?php 
						foreach( $support_page_instance->get_support_sections() as $_key => $_section ) {
							$support_page_instance->show_support_section( $_key, 'text' );
						}
					
					//echo $this->show_system_info( 'text' ); ?></textarea><br />
					<p><button id="ld-system-info-copy-button"><?php esc_html_e( 'Copy to Clipboard', 'learndash' ); ?></button><span style="display:none" id="ld-copy-status-success"><?php esc_html_e( 'Copy Success', 'learndash' ); ?></span><span style="display:none" id="ld-copy-status-failed"><?php esc_html_e( 'Copy Failed', 'learndash' ); ?></span></p>
					<script>
						var copyBtn = document.querySelector('#ld-system-info-copy-button');
						copyBtn.addEventListener('click', function(event) {
							// Select the email link anchor text
							var copy_text = document.querySelector('#ld-system-info-text');
							var range = document.createRange();
							range.selectNode(copy_text);
							window.getSelection().addRange(range);

							try {
								// Now that we've selected the anchor text, execute the copy command
								var successful = document.execCommand('copy');
								if ( successful ) {
									jQuery( '#ld-copy-status-success').show();
								}
							} catch(err) {
									console.log('Oops, unable to copy');
							}

							// Remove the selections - NOTE: Should use
							// removeRange(range) when it is supported
							window.getSelection().removeAllRanges();

							event.preventDefault()
						});
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
		LearnDash_Settings_Section_Support_System_Info::add_section_instance();
	}
);
