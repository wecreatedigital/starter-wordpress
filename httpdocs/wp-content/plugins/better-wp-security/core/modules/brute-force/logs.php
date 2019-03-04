<?php

final class ITSEC_Brute_Force_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_brute_force_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ), 10, 2 );
		add_filter( 'itsec_logs_prepare_brute_force_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
		add_filter( 'itsec_logs_prepare_brute_force_filter_row_action_for_code', array( $this, 'code_row_action' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry, $code ) {
		$entry['module_display'] = esc_html__( 'Brute Force', 'better-wp-security' );

		if ( 'invalid-login' === $code ) {
			$entry['description'] = esc_html__( 'Invalid Login', 'better-wp-security' );
		} else if ( 'auto-ban-admin-username' === $code ) {
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

	public function code_row_action( $vars, $entry, $code, $data ) {
		if ( 'invalid-login' === $code ) {
			$vars = array( 'filters[10]' => 'code|invalid-login%' );
		}

		return $vars;
	}
}
new ITSEC_Brute_Force_Logs();
