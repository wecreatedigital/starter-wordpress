<?php

final class ITSEC_Security_Check_Pro {
	public function __construct() {}

	public function run() {
		if ( defined( 'ITSEC_DISABLE_SECURITY_CHECK_PRO' ) && ITSEC_DISABLE_SECURITY_CHECK_PRO ) {
			return;
		}

		if ( isset( $_POST['itsec-security-check'] ) ) {
			require_once( dirname( __FILE__ ) . '/utility.php' );

			ITSEC_Security_Check_Pro_Utility::handle_scan_request();
		}

		add_action( 'itsec-security-check-before-default-checks', array( $this, 'run_scan' ), 10, 2 );
		add_action( 'itsec-security-check-enable-ssl', array( $this, 'handle_enable_ssl' ) );

		add_filter( 'itsec-ssl-support-probability', array( $this, 'filter_ssl_support_probability' ) );

		if ( ! defined( 'ITSEC_DISABLE_AUTOMATIC_REMOTE_IP_DETECTION' ) || ! ITSEC_DISABLE_AUTOMATIC_REMOTE_IP_DETECTION ) {
			add_filter( 'itsec-get-ip', array( $this, 'get_ip' ) );
		}
	}

	public function run_scan( $feedback, $available_modules ) {
		require_once( dirname( __FILE__ ) . '/utility.php' );

		ITSEC_Security_Check_Pro_Utility::run_scan( $feedback, $available_modules );
	}

	public function handle_enable_ssl( $data ) {
		require_once( dirname( __FILE__ ) . '/utility.php' );

		ITSEC_Security_Check_Pro_Utility::handle_enable_ssl( $data );
	}

	public function filter_ssl_support_probability( $probability ) {
		if ( ITSEC_Modules::get_setting( 'security-check-pro', 'ssl_supported' ) ) {
			$probability = 100;
		}

		return $probability;
	}

	public function get_ip( $ip, $index = false ) {
		if ( false === $index ) {
			$index = ITSEC_Modules::get_setting( 'security-check-pro', 'remote_ip_index' );
		}

		if ( empty( $index ) ) {
			return $ip;
		}

		if ( is_string( $index ) ) {
			if ( empty( $_SERVER[$index] ) ) {
				return $ip;
			} else {
				return $_SERVER[$index];
			}
		}

		if ( is_array( $index ) && 2 === count( $index ) && ! empty( $_SERVER[$index[0]] ) ) {
			if ( preg_match_all( '{(?:for)=(?:"?\[?)([a-z0-9\.:_\-/]*)}i', $_SERVER[$index[0]], $matches, PREG_SET_ORDER ) ) {
				if ( ! empty( $matches[$index[1]][1] ) ) {
					return $matches[$index[1]][1];
				}
			}

			$parts = preg_split( '/[, ]/', $_SERVER[$index[0]] );

			if ( ! empty( $parts[$index[1]] ) ) {
				return $parts[$index[1]];
			}
		}


		return $ip;
	}
}
