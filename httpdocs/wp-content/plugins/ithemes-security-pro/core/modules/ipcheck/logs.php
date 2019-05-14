<?php

final class ITSEC_IPCheck_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_ipcheck_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ) );
		add_filter( 'itsec_logs_prepare_ipcheck_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry ) {
		$entry['module_display'] = esc_html__( 'Network Brute Force', 'it-l10n-ithemes-security-pro' );

		if ( 'ip-blocked' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'IP Blocked', 'it-l10n-ithemes-security-pro' );
		} else if ( 'successful-login-by-blocked-ip' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Blocked Host Attempted Login With Good Credentials', 'it-l10n-ithemes-security-pro' );
		} else if ( 'failed-login-by-blocked-ip' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Blocked Host Attempted Login', 'it-l10n-ithemes-security-pro' );
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$entry = $this->filter_entry_for_list_display( $entry, $code, $code_data );

		$details['module']['content'] = $entry['module_display'];
		$details['description']['content'] = $entry['description'];

		if ( isset( $entry['data']['expires_gmt'] ) ) {
			$timestamp = strtotime( $entry['data']['expires_gmt'] );
			$datetime = date( 'Y-m-d H:i:s', $timestamp + ITSEC_Core::get_time_offset() );

			$details['expiration'] = array(
				'header'  => esc_html__( 'Block Expiration', 'it-l10n-ithemes-security-pro' ),
				'content' => $datetime,
			);
		}

		if ( isset( $entry['data']['details'] ) && isset( $entry['data']['details']['source'] ) ) {
			if ( 'xmlrpc' === $entry['data']['details']['source'] ) {
				$source = esc_html__( 'XMLRPC Authentication', 'it-l10n-ithemes-security-pro' );
			} else if ( 'rest_api' === $entry['data']['details']['source'] ) {
				$source = esc_html__( 'REST API Authentication', 'it-l10n-ithemes-security-pro' );
			} else {
				$source = esc_html__( 'Login Page', 'it-l10n-ithemes-security-pro' );
			}

			$details['source'] = array(
				'header'  => esc_html__( 'Login Source' ),
				'content' => $source,
			);
		}

		return $details;
	}
}
new ITSEC_IPCheck_Logs();
