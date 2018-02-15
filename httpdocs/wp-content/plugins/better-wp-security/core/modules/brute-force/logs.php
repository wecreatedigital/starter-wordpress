<?php

final class ITSEC_Brute_Force_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_brute_force_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ) );
		add_filter( 'itsec_logs_prepare_brute_force_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry ) {
		$entry['module_display'] = esc_html__( 'Brute Force', 'better-wp-security' );

		if ( 'invalid-login' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Invalid Login', 'better-wp-security' );
		} else if ( 'auto-ban-admin-username' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Banned Use of "admin" Username', 'better-wp-security' );
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$entry = $this->filter_entry_for_list_display( $entry, $code, $code_data );

		$details['module']['content'] = $entry['module_display'];
		$details['description']['content'] = $entry['description'];

		if ( isset( $entry['data']['details'] ) ) {
			if ( 'xmlrpc' === $entry['data']['details']['source'] ) {
				$source = esc_html__( 'XMLRPC Authentication', 'better-wp-security' );
			} else if ( 'rest_api' === $entry['data']['details']['source'] ) {
				$source = esc_html__( 'REST API Authentication', 'better-wp-security' );
			}
		}

		if ( ! isset( $source ) ) {
			$source = esc_html__( 'Login Page', 'better-wp-security' );
		}

		$details['source'] = array(
			'header'  => esc_html__( 'Login Source' ),
			'content' => $source,
		);

		return $details;
	}
}
new ITSEC_Brute_Force_Logs();
