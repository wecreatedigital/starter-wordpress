<?php

final class ITSEC_Recaptcha_Settings_Page extends ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'recaptcha';
		$this->title = __( 'reCAPTCHA', 'it-l10n-ithemes-security-pro' );
		$this->description = __( 'Protect your site from bots by verifying that the person submitting comments or logging in is indeed human.', 'it-l10n-ithemes-security-pro' );
		$this->type = 'recommended';
		$this->pro = true;

		parent::__construct();
	}

	protected function render_description( $form ) {

?>
	<p><?php _e( 'Protect your site from bots by verifying that the person submitting comments or logging in is indeed human.', 'it-l10n-ithemes-security-pro' ); ?></p>
<?php

	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_script( 'itsec-recaptcha-settings-page', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery', 'itsec-util' ), ITSEC_Core::get_plugin_build(), true );
	}

	protected function render_settings( $form ) {
		$validator = ITSEC_Modules::get_validator( $this->id );
		$languages = $validator->get_valid_languages();
		$types = $validator->get_valid_types_with_description();
		$positions = $validator->get_valid_invisible_positions();
		$locations = $validator->get_valid_v3_include_locations();

?>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="itsec-recaptcha-type"><?php _e( 'Type', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php foreach ( $types as $type => $description ) : ?>
					<p>
						<?php $form->add_radio( 'type', $type ); ?>
						<label for="itsec-recaptcha-type-<?php echo esc_attr( $type ); ?>"><?php echo $description; ?></label>
					</p>
				<?php endforeach; ?>
				<p class="description"><?php printf( wp_kses( __( 'Only select the type associated with the generated keys. If you are unsure which type was selected when generating the keys, you should <a href="%1$s" target="_blank">generate new keys</a>. For details about the different types, see <a href="%2$s" target="_blank">this page</a>.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ), 'https://www.google.com/recaptcha/admin', 'https://developers.google.com/recaptcha/docs/versions' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-recaptcha-site_key"><?php _e( 'Site Key', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_text( 'site_key', array( 'class' => 'large-text' ) ); ?>
				<br />
				<label for="itsec-recaptcha-site_key"><?php printf( __( 'To use this feature you need a free site key and secret key from <a href="%s" target="_blank" rel="noopener noreferrer">Google reCAPTCHA</a>.', 'it-l10n-ithemes-security-pro' ), 'https://www.google.com/recaptcha/admin' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-recaptcha-secret_key"><?php _e( 'Secret Key', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_text( 'secret_key', array( 'class' => 'large-text' ) ); ?>
				<br />
				<label for="itsec-recaptcha-secret_key"><?php printf( __( 'To use this feature you need a free secret key and secret key from <a href="%s" target="_blank" rel="noopener noreferrer">Google reCAPTCHA</a>.', 'it-l10n-ithemes-security-pro' ), 'https://www.google.com/recaptcha/admin' ); ?></label>
			</td>
		</tr>
		<tr class="itsec-recaptcha-hide-for-type itsec-recaptcha-hide-for-type--type-v3">
			<th scope="row"><label for="itsec-recaptcha-gdpr"><?php _e( 'Enable GDPR Opt-in', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'gdpr' ); ?>
				<p class="description"><?php _e( 'To assist with GDPR compliance, iThemes Security can prompt the user to accept Google\'s Privacy Policy and Terms of Service before loading the reCAPTCHA API.', 'it-l10n-ithemes-security-pro' ); ?></p>
				<p class="description"><?php  esc_html_e( 'Note: This is not available when using reCAPTCHA v3 since the API is always loaded on every request.', 'it-l10n-ithemes-security-pro' )?></p>
			</td>
		</tr>
		<tr class="itsec-recaptcha-hide-for-type itsec-recaptcha-hide-for-type--type-v3">
			<th scope="row"><label for="itsec-recaptcha-on_page_opt_in"><?php _e( 'On Page Opt-in', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'on_page_opt_in' ); ?>
				<p class="description"><?php _e( 'Allow users to opt-in to reCAPTCHA without refreshing the page.', 'it-l10n-ithemes-security-pro' ); ?></p>
			</td>
		</tr>
		<tr class="itsec-recaptcha-show-for-type itsec-recaptcha-show-for-type--type-v3">
			<th scope="row"><label for="itsec-recaptcha-v3_threshold"><?php esc_html_e( 'Block Threshold', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_html5_input( 'v3_threshold', 'number', array( 'min' => '0.0', 'max' => '1.0', 'step' => '0.01' ) ); ?>
				<p class="description">
					<?php esc_html_e( 'Google reCAPTCHA assigns a score between 0 and 1 describing the legitimacy of the request. A score of 1 is most likely a human, and a score of 0 is most likely a bot.', 'it-l10n-ithemes-security-pro' ) ?>
					<?php printf( esc_html__( 'Google recommends using a default value of 0.5 and to adjust the threshold based off the score distribution in the %1$sreCAPTCHA Developer Console%2$s.', 'it-l10n-ithemes-security-pro' ), '<a href="https://www.google.com/recaptcha/admin" target="_blank">', '</a>' ); ?>
				</p>
			</td>
		</tr>
		<tr class="itsec-recaptcha-show-for-type itsec-recaptcha-show-for-type--type-v3">
			<th scope="row"><label for="itsec-recaptcha-v3_include_location"><?php _e( 'Include Script', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_select( 'v3_include_location', $locations ); ?>
				<p class="description">
					<?php esc_html_e( 'Specify where the reCAPTCHA script should be loaded. Google recommends including the script on all pages to increase accuracy.', 'it-l10n-ithemes-security-pro' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-recaptcha-login"><?php _e( 'Use on Login', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'login' ); ?>
				<label for="itsec-recaptcha-login"><?php _e( 'Use reCAPTCHA for user login.', 'it-l10n-ithemes-security-pro' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-recaptcha-register"><?php _e( 'Use on New User Registration', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'register' ); ?>
				<label for="itsec-recaptcha-register"><?php _e( 'Use reCAPTCHA for user registration.', 'it-l10n-ithemes-security-pro' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-recaptcha-comments"><?php _e( 'Use on Comments', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'comments' ); ?>
				<label for="itsec-recaptcha-comments"><?php _e( 'Use reCAPTCHA for new comments.', 'it-l10n-ithemes-security-pro' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-recaptcha-language"><?php _e( 'Language', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_select( 'language', $languages ); ?>
				<br />
				<label for="itsec-recaptcha-language"><?php _e( 'Select the language for the reCAPTCHA box (if autodetect is not working).', 'it-l10n-ithemes-security-pro' ); ?></label>
			</td>
		</tr>
		<tr class="itsec-recaptcha-show-for-type itsec-recaptcha-show-for-type--type-v2">
			<th scope="row"><label for="itsec-recaptcha-theme"><?php _e( 'Use Dark Theme', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'theme' ); ?>
				<label for="itsec-recaptcha-theme"><?php _e( 'Use dark theme.', 'it-l10n-ithemes-security-pro' ); ?></label>
				<p class="description"><?php esc_html_e( 'Note: Dark theme is only compatible with reCAPTCHA V2 and not Invisible reCAPTCHA.', 'it-l10n-ithemes-security-pro' ); ?></p>
			</td>
		</tr>
		<tr class="itsec-recaptcha-show-for-type itsec-recaptcha-hide-for-type--type-invisible">
			<th scope="row"><label for="itsec-recaptcha-invis_position"><?php esc_html_e( 'reCAPTCHA Position', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php foreach ( $positions as $position => $label ) : ?>
					<p>
						<?php $form->add_radio( 'invis_position', $position ); ?>
						<label for="itsec-recaptcha-invis_position-<?php echo esc_attr( $position ); ?>"><?php echo $label; ?></label>
					</p>
				<?php endforeach; ?>
				<p class="description"><?php esc_html_e( 'Note: reCAPTCHA Position is only compatible with Invisible reCAPTCHA and not reCAPTCHA V2.', 'it-l10n-ithemes-security-pro' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-recaptcha-error_threshold"><?php _e( 'Lockout Error Threshold', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_text( 'error_threshold', array( 'class' => 'small-text' ) ); ?>
				<label for="itsec-recaptcha-error_threshold"><?php _e( 'Errors', 'it-l10n-ithemes-security-pro' ); ?></label>
				<p class="description"><?php _e( 'The numbers of failed reCAPTCHA entries that will trigger a lockout. Set to zero (0) to record recaptcha errors without locking out users. This can be useful for troubleshooting content or other errors. The default is 7.', 'it-l10n-ithemes-security-pro' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-recaptcha-check_period"><?php _e( 'Lockout Check Period', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_text( 'check_period', array( 'class' => 'small-text' ) ); ?>
				<label for="itsec-recaptcha-check_period"><?php _e( 'Minutes', 'it-l10n-ithemes-security-pro' ); ?></label>
				<p class="description"><?php _e( 'How long the plugin will remember a bad captcha entry and count it towards a lockout.', 'it-l10n-ithemes-security-pro' ); ?></p>
			</td>
		</tr>
	</table>
<?php

	}
}

new ITSEC_Recaptcha_Settings_Page();
