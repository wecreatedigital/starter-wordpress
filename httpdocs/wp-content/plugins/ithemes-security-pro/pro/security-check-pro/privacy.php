<?php

final class ITSEC_Security_Check_Pro_Privacy {
	public function __construct() {
		add_filter( 'itsec_get_privacy_policy_for_sharing', array( $this, 'get_privacy_policy_for_sharing' ) );
	}

	public function get_privacy_policy_for_sharing( $policy ) {
		/* Translators: 1: Link to iThemes' privacy policy */
		$policy .= "<p class=\"privacy-policy-tutorial\">" . sprintf( wp_kses( __( 'When running Security Check, ithemes.com will be contacted as part of a process to determine if the site supports TLS/SSL requests. No personal data is sent to ithemes.com as part of this process. Requests to ithemes.com include the site\'s URL. For ithemes.com privacy policy details, please see the <a href="%1$s">iThemes Privacy Policy</a>.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), 'https://ithemes.com/privacy-policy/' ) . "</p>\n";

		return $policy;
	}
}
new ITSEC_Security_Check_Pro_Privacy();
