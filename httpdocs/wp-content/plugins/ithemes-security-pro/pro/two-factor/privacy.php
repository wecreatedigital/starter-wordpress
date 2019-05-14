<?php

final class ITSEC_Two_Factor_Privacy {
	public function __construct() {
		add_filter( 'itsec_get_privacy_policy_for_sharing', array( $this, 'get_privacy_policy_for_sharing' ) );
	}

	public function get_privacy_policy_for_sharing( $policy ) {
		$suggested_text = '<strong class="privacy-policy-tutorial">' . __( 'Suggested text:' ) . ' </strong>';

		/* Translators: 1: Link to WordPress's privacy policy, 2: Link to iThemes' privacy policy, 3: Link to Amazon AWS's privacy policy */
		$policy .= "<p>$suggested_text " . sprintf( wp_kses( __( 'A QR code image is generated for users that set up two-factor authentication for this site. This image is generated using an iThemes hosted API. As part of generating this image, your username is sent to the API. This data is not logged. For privacy policy details, please see the <a href="%1$s">iThemes Privacy Policy</a>.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), 'https://ithemes.com/privacy-policy' ) . "</p>\n";

		if ( ITSEC_Modules::get_setting( 'two-factor', 'allow_remember' ) ) {
			$policy .= '<p>' . esc_html__( 'When using the Remember Device for Two-Factor, a cookie (itsec_remember_2fa) will be set with a secure token that expires in 30 days.', 'it-l10n-ithemes-security-pro' ) . '</p>';
		}

		return $policy;
	}
}
new ITSEC_Two_Factor_Privacy();
