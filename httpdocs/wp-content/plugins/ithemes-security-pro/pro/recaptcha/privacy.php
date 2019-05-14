<?php

final class ITSEC_Recaptcha_Privacy {
	public function __construct() {
		add_filter( 'itsec_get_privacy_policy_for_cookies', array( $this, 'get_privacy_policy_for_cookies' ) );
		add_filter( 'itsec_get_privacy_policy_for_sharing', array( $this, 'get_privacy_policy_for_sharing' ) );
	}

	public function get_privacy_policy_for_cookies( $policy ) {
		$suggested_text = '<strong class="privacy-policy-tutorial">' . __( 'Suggested text:' ) . ' </strong>';

		$policy .= "<p>$suggested_text " . esc_html__( 'Some forms on this site require the use of Google\'s reCAPTCHA service before they can be submitted. If you consent to use Google\'s reCAPTCHA service, a cookie is created that stores your consent. This cookie deletes itself after thirty days.', 'it-l10n-ithemes-security-pro' ) . "</p>\n";

		return $policy;
	}
	public function get_privacy_policy_for_sharing( $policy ) {
		$suggested_text = '<strong class="privacy-policy-tutorial">' . __( 'Suggested text:' ) . ' </strong>';

		/* Translators: 1: Link to Google's privacy policy, 2: Link to Google's Terms of Use */
		$policy .= "<p>$suggested_text " . sprintf( wp_kses( __( 'Some forms on this site require the use of Google\'s reCAPTCHA service before they can be submitted. Use of the reCAPTCHA service is subject to the Google <a href="%1$s">Privacy Policy</a> and <a href="%2$s">Terms of Use</a>.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), 'https://policies.google.com/privacy', 'https://policies.google.com/terms' ) . "</p>\n";

		return $policy;
	}
}
new ITSEC_Recaptcha_Privacy();
