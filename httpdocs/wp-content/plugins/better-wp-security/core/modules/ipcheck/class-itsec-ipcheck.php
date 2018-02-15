<?php

/**
 * iThemes IPCheck API Wrapper.
 *
 * Provides static calls to the iThemes IPCheck API
 *
 * @package iThemes_Security
 *
 * @since   4.5
 *
 */
class ITSEC_IPCheck {
	private $endpoint = 'http://ipcheck-api.ithemes.com/?action=';
	private $settings;

	public function run() {
		add_filter( 'authenticate', array( $this, 'filter_authenticate' ), 10000, 3 ); // Set a very late priority so that we run after actual authentication takes place.
	}

	private function load_settings() {
		if ( ! isset( $this->settings ) ) {
			$this->settings = ITSEC_Modules::get_settings( 'network-brute-force' );
		}
	}

	public function filter_authenticate( $user, $username, $password ) {
		global $itsec_lockout;

		if ( is_wp_error( $user ) && $user->get_error_codes() == array( 'empty_username', 'empty_password' ) ) {
			// This is not an authentication attempt. It is simply the login page loading.
			return $user;
		}

		$this->load_settings();

		if ( is_wp_error( $user ) || null == $user ) {
			if ( $this->report_ip() && $this->settings['enable_ban'] ) {
				ITSEC_Log::add_notice( 'ipcheck', 'failed-login-by-blocked-ip', array( 'details' => ITSEC_Lib::get_login_details() ) );
				$itsec_lockout->execute_lock( array( 'network_lock' => true ) );
			}
		} else if ( $this->settings['enable_ban'] && $this->is_ip_banned() ) {
			ITSEC_Log::add_critical_issue( 'ipcheck', 'successful-login-by-blocked-ip', array( 'details' => ITSEC_Lib::get_login_details() ) );
			$itsec_lockout->execute_lock( array( 'network_lock' => true ) );
		}

		return $user;
	}

	/**
	 * Check visitor IP to see if it is banned by IPCheck.
	 *
	 * @since 3.0.0
	 *
	 * @return bool true if banned, false otherwise.
	 */
	private function is_ip_banned() {
		return $this->get_server_response( 'check-ip' );
	}

	/**
	 * Report visitor IP for blacklistable-offense to IPCheck.
	 *
	 * @since 3.0.0
	 *
	 * @return bool true if banned, false otherwise.
	 */
	private function report_ip() {
		return $this->get_server_response( 'report-ip' );
	}

	private function get_server_response( $action ) {
		$this->load_settings();

		if ( empty( $this->settings['api_key'] ) || empty( $this->settings['api_secret'] ) ) {
			return false;
		}


		$ip = ITSEC_Lib::get_ip();

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-ip-tools.php' );

		if ( ! ITSEC_Lib_IP_Tools::validate( $ip ) || ITSEC_Lib::is_ip_whitelisted( $ip ) ) {
			return false;
		}


		$cache = $this->get_cache( $ip );

		if ( 'check-ip' === $action ) {
			if ( $cache['cache_ttl'] >= ITSEC_Core::get_current_time_gmt() ) {
				return $cache['block'];
			}
		} else if ( 'report-ip' === $action ) {
			if ( $cache['report_ttl'] >= ITSEC_Core::get_current_time_gmt() ) {
				return $cache['block'];
			}
		}


		$args = json_encode(
			array(
				'apikey'    => $this->settings['api_key'],
				'behavior'  => 'brute-force-login',
				'ip'        => $ip,
				'site'      => home_url( '', 'http' ),
				'timestamp' => ITSEC_Core::get_current_time_gmt(),
			)
		);

		$request = array(
			'body' => array(
				'request'   => $args,
				'signature' => $this->hmac_sha1( $this->settings['api_secret'], $action . $args ),
			),
		);


		$response = wp_remote_post( $this->endpoint . $action, $request );

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			return false;
		}


		$response = json_decode( $response['body'], true );

		if ( ! is_array( $response ) || ! isset( $response['success'] ) || ! $response['success'] ) {
			return false;
		}


		$this->set_cache( $ip, $response );

		$cache_seconds = isset( $response['cache_ttl'] ) ? absint( $response['cache_ttl'] ) : 3600;

		if ( isset( $response['block'] ) && $response['block'] ) {
			$data = array(
				'expires'     => date( 'Y-m-d H:i:s', ITSEC_Core::get_current_time() + $cache_seconds ),
				'expires_gmt' => date( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() + $cache_seconds ),
				'type'        => 'host',
			);

			ITSEC_Log::add_action( 'ipcheck', 'ip-blocked', $data );

			return true;
		}

		return false;
	}

	private function set_cache( $ip, $response ) {
		$cache = $this->get_cache( $ip );
		$time = ITSEC_Core::get_current_time_gmt();

		if ( isset( $response['block'] ) ) {
			$cache['block'] = (boolean) $response['block'];
		}

		if ( isset( $response['cache_ttl'] ) ) {
			$cache['cache_ttl'] = intval( $response['cache_ttl'] ) + $time;
		} else if ( 0 === $cache['cache_ttl'] ) {
			$cache['cache_ttl'] = $time + HOUR_IN_SECONDS;
		}

		if ( isset( $response['report_ttl'] ) ) {
			$cache['report_ttl'] = intval( $response['report_ttl'] ) + $time;
		}

		$transient_time = max( $cache['cache_ttl'], $cache['report_ttl'] ) - $time;


		set_site_transient( "itsec_ipcheck_$ip", $cache, $transient_time );
	}

	private function get_cache( $ip ) {
		$cache = get_site_transient( "itsec_ipcheck_$ip" );

		$defaults = array(
			'block'      => false,
			'cache_ttl'  => 0,
			'report_ttl' => 0,
		);

		if ( ! is_array( $cache ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $cache );
	}

	/**
	 * Calculates the HMAC of a string using SHA1.
	 *
	 * there is a native PHP hmac function, but we use this one for
	 * the widest compatibility with older PHP versions
	 *
	 * @param   string $key  the shared secret key used to generate the mac
	 * @param   string $data data to be signed
	 *
	 *
	 * @return  string    base64 encoded hmac
	 */
	private function hmac_sha1( $key, $data ) {
		if ( strlen( $key ) > 64 ) {
			$key = pack( 'H*', sha1( $key ) );
		}

		$key = str_pad( $key, 64, chr( 0x00 ) );
		$ipad = str_repeat( chr( 0x36 ), 64 );
		$opad = str_repeat( chr( 0x5c ), 64 );
		$hmac = pack( 'H*', sha1( ( $key ^ $opad ) . pack( 'H*', sha1( ( $key ^ $ipad ) . $data ) ) ) );

		return base64_encode( $hmac );
	}

}
