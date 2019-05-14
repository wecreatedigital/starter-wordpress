<?php

final class ITSEC_Online_Files_Privacy {
	public function __construct() {
		if ( ! ITSEC_Modules::get_setting( 'online-files', 'compare_file_hashes' ) ) {
			return;
		}

		add_filter( 'itsec_get_privacy_policy_for_sharing', array( $this, 'get_privacy_policy_for_sharing' ) );
	}

	public function get_privacy_policy_for_sharing( $policy ) {
		/* Translators: 1: Link to WordPress's privacy policy, 2: Link to iThemes' privacy policy, 3: Link to Amazon AWS's privacy policy */
		$policy .= "<p class=\"privacy-policy-tutorial\">" . sprintf( wp_kses( __( 'In order to ensure file integrity, iThemes Security pulls data from wordpress.org, ithemes.com, and amazonaws.com. No personal data is sent to these sites. Requests to wordpress.org include the WordPress version, the site\'s locale, a list of installed plugins, and a list of each plugin\'s version. Requests to ithemes.com and amazonaws.com include the installed iThemes products and their versions. For wordpress.org privacy policy details, please see the <a href="%1$s">WordPress Privacy Policy</a>. For ithemes.com privacy policy details, please see the <a href="%2$s">iThemes Privacy Policy</a>. Requests to amazonaws.com are to content added and managed by iThemes which is covered by the <a href="%3$s">Amazon Web Services Data Privacy policy</a>.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), 'https://wordpress.org/about/privacy/', 'https://ithemes.com/privacy-policy/', 'https://aws.amazon.com/compliance/data-privacy-faq/' ) . "</p>\n";

		return $policy;
	}
}
new ITSEC_Online_Files_Privacy();
