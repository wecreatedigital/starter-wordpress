<?php

final class ITSEC_Security_Check_Pro_Utility {
	private static $api_url = 'https://itsec-ssl-proxy-detect.ithemes.com/';
	private static $config_url = 'https://itsec-ssl-proxy-detect.ithemes.com/config.json';

	public static function run_scan( $feedback, $available_modules ) {
		$response = self::get_server_response();

		if ( ! is_array( $response ) ) {
			$settings = ITSEC_Modules::get_settings( 'security-check-pro' );

			if ( ! is_int( $settings['last_scan_timestamp'] ) || time() > $settings['last_scan_timestamp'] + HOUR_IN_SECONDS ) {
				return $response;
			}

			$response = array(
				'remote_ip'     => ! empty( $settings['remote_ip_index'] ),
				'ssl_supported' => $settings['ssl_supported'],
			);
		}


		if ( ! defined( 'ITSEC_DISABLE_AUTOMATIC_REMOTE_IP_DETECTION' ) || ! ITSEC_DISABLE_AUTOMATIC_REMOTE_IP_DETECTION ) {
			if ( isset( $response['remote_ip'] ) && $response['remote_ip'] ) {
				$feedback->add_section( 'security-check-pro-remote-ip', array( 'status' => 'action-taken' ) );
				$feedback->add_text( __( 'Identified remote IP entry to protect against IP spoofing.', 'it-l10n-ithemes-security-pro' ) );
			}
		}

		if ( isset( $response['ssl_supported'] ) && $response['ssl_supported'] ) {
			ITSEC_Response::reload_module( 'ssl' );

			$ssl_settings = ITSEC_Modules::get_settings( 'ssl' );

			if ( 'enabled' === $ssl_settings['require_ssl'] || ( 'advanced' === $ssl_settings['require_ssl'] && $ssl_settings['admin'] ) ) {
				$feedback->add_section( 'security-check-pro-ssl' );
				$feedback->add_text( __( 'Requests for http pages are redirected to https as recommended.', 'it-l10n-ithemes-security-pro' ) );
			} else {
				$feedback->add_section( 'security-check-pro-ssl', array( 'interactive' => true, 'status' => 'call-to-action' ) );
				$feedback->add_text( __( 'Your site supports SSL. Redirecting all http page requests to https is highly recommended as it protects login details from being stolen when using public WiFi or insecure networks.', 'it-l10n-ithemes-security-pro' ) );

				if ( ! is_ssl() ) {
					$feedback->add_text( __( 'Please note that you will have to log back in after enabling this.', 'it-l10n-ithemes-security-pro' ) );
				}

				$feedback->add_input( 'submit', 'enable_ssl', array(
					'value'       => __( 'Redirect HTTP Requests to HTTPS', 'it-l10n-ithemes-security-pro' ),
					'style_class' => 'button-primary',
					'data'        => array(
						'clicked-value' => __( 'Updating Site Configuration...', 'it-l10n-ithemes-security-pro' ),
					),
				) );
				$feedback->add_input( 'hidden', 'method', array(
					'value' => 'enable-ssl',
				) );
			}
		}

		return $response;
	}

	public static function handle_enable_ssl( $data ) {
		$settings = ITSEC_Modules::get_settings( 'ssl' );

		$settings['require_ssl'] = 'enabled';

		$results = ITSEC_Modules::set_settings( 'ssl', $settings );

		if ( is_wp_error( $results ) ) {
			ITSEC_Response::add_error( $results );
		} else if ( $results['saved'] ) {
			ITSEC_Modules::activate( 'ssl' );
			ITSEC_Response::add_js_function_call( 'setModuleToActive', 'ssl' );
			ITSEC_Response::set_response( '<p>' . __( 'Your site now redirects http page requests to https.', 'it-l10n-ithemes-security-pro' ) . '</p>' );
			ITSEC_Response::reload_module( 'ssl' );
		}
	}

	public static function handle_scan_request() {
		if ( ! isset( $_POST['itsec-security-check'] ) || 'scan' !== $_POST['itsec-security-check'] ) {
			return;
		}

		if ( ! isset( $_POST['site'], $_POST['key'], $_POST['expect'], $_POST['scheme'] ) ) {
			return;
		}

		if ( ! self::validate_key( $_POST['key'] ) ) {
			return;
		}


		if ( defined( 'ITSEC_DISABLE_AUTOMATIC_REMOTE_IP_DETECTION' ) && ITSEC_DISABLE_AUTOMATIC_REMOTE_IP_DETECTION ) {
			$remote_ip_index = '';
		} else {
			$remote_ip_index = self::get_remote_ip_index();

			if ( false === $remote_ip_index ) {
				$remote_ip_index = '';
			}
		}

		if ( 'https' === $_POST['scheme'] && is_ssl() ) {
			$ssl_supported = true;
		} else {
			$ssl_supported = false;
		}

		$settings = array(
			'last_scan_timestamp' => time(),
			'remote_ip_index'     => $remote_ip_index,
			'ssl_supported'        => $ssl_supported,
		);

		ITSEC_Modules::set_settings( 'security-check-pro', $settings );


		header( 'Content-Type: text/plain' );
		echo "<response>{$_POST['expect']}:" . ( empty( $remote_ip_index ) ? 'false' : 'true' ) . ':' . ( $ssl_supported ? 'true' : 'false' ) . '</response>';
		exit();
	}

	public static function get_remote_ip_index() {
		$remote_ips = self::get_remote_ips();


		$standard_indexes = array(
			'REMOTE_ADDR',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CLIENT_IP',
		);

		foreach ( $remote_ips as $ip ) {
			foreach ( $standard_indexes as $standard_index ) {
				$index = self::get_index( $ip, $standard_index );

				if ( false !== $index ) {
					return $index;
				}
			}
		}


		foreach ( $remote_ips as $ip ) {
			foreach ( array_keys( $_SERVER ) as $var ) {
				$index = self::get_index( $ip, $var );

				if ( false !== $index ) {
					return $index;
				}
			}
		}


		return false;
	}

	public static function get_index( $ip, $var ) {
		if ( ! isset( $_SERVER[$var] ) ) {
			return false;
		}

		if ( $_SERVER[$var] === $ip ) {
			return $var;
		}

		$value = trim( $_SERVER[$var] );
		$ip_regex_pattern = '/' . preg_quote( $ip, '/' ) . '/';

		if ( preg_match( $ip_regex_pattern, $value ) ) {
			$potential_ips = preg_split( '/[, ]+/', $value );

			foreach ( $potential_ips as $index => $potential_ip ) {
				if ( $ip === $potential_ip ) {
					return array( $var, $index );
				}
			}

			if ( preg_match_all( '{(?:for)=(?:"?\[?)([a-z0-9\.:_\-/]*)}i', $value, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $index => $match ) {
					if ( $ip === $match[1] ) {
						return array( $var, $index );
					}
				}
			}
		}

		return false;
	}

	private static function get_server_response() {
		$data = array(
			'site' => get_home_url(),
			'key'  => self::get_key(),
		);

		$remote_post_args = array(
			'timeout' => 60,
			'body'    => $data,
		);

		$response = wp_remote_post( self::$api_url, $remote_post_args );

		if ( is_wp_error( $response ) && ( 'connect() timed out!' !== $response->get_error_message() ) ) {
			$url = preg_replace( '|^https://|', 'http://', self::$api_url );
			$response = wp_remote_post( $url, $remote_post_args );
		}

		if ( is_wp_error( $response ) ) {
			if ( 'connect() timed out!' === $response->get_error_message() ) {
				return new WP_Error( 'http_request_failed', __( 'The server was unable to be contacted.', 'it-l10n-ithemes-security-pro' ) );
			}

			return $response;
		}

		if ( '' === trim( $response['body'] ) ) {
			return new WP_Error( 'empty-response', __( 'An error occurred when communicating with the iThemes Security Check server: The server returned a blank response.', 'it-l10n-ithemes-security-pro' ) );
		}


		$body = json_decode( $response['body'], true );

		if ( is_null( $body ) ) {
			return new WP_Error( 'non-json-response', __( 'An error occurred when communicating with the iThemes Security Check server: The server did not return JSON data when JSON data was expected.', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( empty ( $response['body'] ) ) {
			if ( isset( $body['error'] ) && isset( $body['error']['message'], $body['error']['message'] ) ) {
				return new WP_Error( $body['error']['code'], sprintf( __( 'An error occurred when communicating with the iThemes Security Check server: %s (%s)', 'it-l10n-ithemes-security-pro' ), $body['error']['message'], $body['error']['code'] ) );
			} else {
				return new WP_Error( $body['error']['code'], __( 'An error occurred when communicating with the iThemes Security Check server: The server could not be contacted. Please wait a few minutes and try again.', 'it-l10n-ithemes-security-pro' ) );
			}
		}


		return $body;
	}

	public static function validate_key( $key, $expires = false ) {
		$salt = ITSEC_Modules::get_setting( 'security-check-pro', 'key_salt' );
		$key = trim( $key );

		if ( empty( $salt ) ) {
			return false; // Only validate if a salt has been stored.
		}

		if ( ! preg_match( '/^(\d+):([a-f0-9]+)$/', $key, $matches ) ) {
			return false;
		}

		if ( false === $expires ) {
			$expires = 2 * MINUTE_IN_SECONDS; // keys expire every 2 minutes by default.
		}

		$time = $matches[1];
		$hash = $matches[2];

		if ( time() > $time + $expires ) {
			return false;
		}

		$calculated_hash = hash_hmac( 'md5', $time, $salt );

		return hash_equals( $calculated_hash, $hash );
	}

	public static function get_key() {
		$salt = ITSEC_Modules::get_setting( 'security-check-pro', 'key_salt' );

		if ( empty( $salt ) ) {
			$salt = wp_generate_password( 60, true, true );
			$result = ITSEC_Modules::set_setting( 'security-check-pro', 'key_salt', $salt );
		}

		$time = time();
		$hash = hash_hmac( 'md5', $time, $salt );

		$key = "$time:$hash";

		return $key;
	}

	public static function get_remote_ips() {
		$remote_ips = apply_filters( 'itsec-security-check-pro-remote-ips', array() );

		if ( is_array( $remote_ips ) && ! empty( $remote_ips ) ) {
			return $remote_ips;
		}


		$settings = ITSEC_Modules::get_settings( 'security-check-pro' );

		if ( $settings['remote_ips_timestamp'] + ( 5 * MINUTE_IN_SECONDS ) > time() && ! empty( $settings['remote_ips'] ) ) {
			return $settings['remote_ips'];
		}


		$response = wp_remote_get( self::$config_url );

		if ( is_wp_error( $response ) ) {
			return array();
		}


		$body = $response['body'];
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || ! isset( $data['ips'] ) || ! is_array( $data['ips'] ) ) {
			return array();
		}


		$settings['remote_ips_timestamp'] = time();
		$settings['remote_ips'] = $data['ips'];

		ITSEC_Modules::set_settings( 'security-check-pro', $settings );

		return $data['ips'];
	}
}
