<?php

final class ITSEC_Hide_Backend_Privacy {
	private $settings;

	public function __construct() {
		$this->settings = ITSEC_Modules::get_settings( 'hide-backend' );

		if ( ! $this->settings['enabled'] ) {
			return;
		}

		add_filter( 'itsec_get_privacy_policy_for_cookies', array( $this, 'get_privacy_policy_for_cookies' ) );
	}

	public function get_privacy_policy_for_cookies( $policy ) {
		$suggested_text = '<strong class="privacy-policy-tutorial">' . __( 'Suggested text:' ) . ' </strong>';

		$policy .= "<p>$suggested_text " . esc_html__( 'Visiting the login page sets a temporary cookie that aids compatibility with some alternate login methods. This cookie contains no personal data and expires after 1 hour.', 'better-wp-security' ) . "</p>\n";

		return $policy;
	}
}
new ITSEC_Hide_Backend_Privacy();
