<?php

final class ITSEC_Privacy {
	public function run() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	public function admin_init() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( 'iThemes Security', $this->get_privacy_policy_content() );
		}
	}

	private function get_privacy_policy_content() {
		require_once( dirname( __FILE__ ) . '/util.php' );

		return ITSEC_Privacy_Util::get_privacy_policy_content();
	}

	public function register_exporter( $exporters ) {
		$exporters['ithemes-security'] = array(
			'exporter_friendly_name' => __( 'iThemes Security Plugin', 'it-l10n-ithemes-security-pro' ),
			'callback'               => array( $this, 'export' ),
		);

		return $exporters;
	}

	public function export( $email, $page = 1 ) {
		require_once( dirname( __FILE__ ) . '/util.php' );

		return ITSEC_Privacy_Util::export( $email, (int) $page );
	}

	public function register_eraser( $erasers ) {
		$erasers['ithemes-security'] = array(
			'eraser_friendly_name' => __( 'iThemes Security Plugin', 'it-l10n-ithemes-security-pro' ),
			'callback'             => array( $this, 'erase' ),
		);

		return $erasers;
	}

	public function erase( $email, $page = 1 ) {
		require_once( dirname( __FILE__ ) . '/util.php' );

		return ITSEC_Privacy_Util::erase( $email, (int) $page );
	}
}
