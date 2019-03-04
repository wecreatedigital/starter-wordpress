<?php

final class ITSEC_Network_Bruteforce_Privacy {
	private $settings;

	public function __construct() {
		$this->settings = ITSEC_Modules::get_settings( 'network-brute-force' );

		if ( empty( $this->settings['api_key'] ) || empty( $this->settings['api_secret'] ) ) {
			return;
		}

		add_filter( 'itsec_get_privacy_policy_for_sending', array( $this, 'get_privacy_policy_for_sending' ) );
	}

	public function get_privacy_policy_for_sending( $policy ) {
		$suggested_text = '<strong class="privacy-policy-tutorial">' . __( 'Suggested text:' ) . ' </strong>';

		/* Translators: 1: URL to the iThemes privacy policy */
		$policy .= "<p>$suggested_text " . sprintf( wp_kses( __( 'This site is part of a network of sites that protect against distributed brute force attacks. To enable this protection, the IP address of visitors attempting to log into the site is shared with a service provided by ithemes.com. For privacy policy details, please see the <a href="%1$s">iThemes Privacy Policy</a>.', 'better-wp-security' ), array( 'a' => array( 'href' => array() ) ) ), 'https://ithemes.com/privacy-policy' ) . "</p>\n";

		return $policy;
	}
}
new ITSEC_Network_Bruteforce_Privacy();
