<?php

/**
 * Miscellaneous plugin-wide functions.
 *
 * Various static functions to provide information to modules and other areas throughout the plugin.
 *
 * @package iThemes_Security
 *
 * @since   4.0.0
 */
final class ITSEC_Lib {
	/**
	 * Clear caches.
	 *
	 * Clears popular WordPress caching mechanisms.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $page [optional] true to clear page cache
	 *
	 * @return void
	 */
	public static function clear_caches( $page = false ) {

		//clear APC Cache
		if ( function_exists( 'apc_store' ) ) {
			apc_clear_cache(); //Let's clear APC (if it exists) when big stuff is saved.
		}

		//clear w3 total cache or wp super cache
		if ( function_exists( 'w3tc_pgcache_flush' ) ) {

			if ( true == $page ) {
				w3tc_pgcache_flush();
				w3tc_minify_flush();
			}

			w3tc_dbcache_flush();
			w3tc_objectcache_flush();

		} else if ( function_exists( 'wp_cache_clear_cache' ) && true == $page ) {

			wp_cache_clear_cache();

		}


		do_action( 'itsec-lib-clear-caches' );
	}

	/**
	 * Creates appropriate database tables.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function create_database_tables() {
		require_once( ITSEC_Core::get_core_dir() . '/lib/schema.php' );

		ITSEC_Schema::create_database_tables();
	}

	/**
	 * Gets location of wp-config.php.
	 *
	 * Finds and returns path to wp-config.php
	 *
	 * @since 4.0.0
	 *
	 * @return string path to wp-config.php
	 * */
	public static function get_config() {
		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-config-file.php' );

		return ITSEC_Lib_Config_File::get_wp_config_file_path();
	}

	/**
	 * Return primary domain from given url.
	 *
	 * Returns primary domain name (without subdomains) of given URL.
	 *
	 * @since 4.0.0
	 *
	 * @param string $url          URL to filter
	 *
	 * @return string domain name or '*' on error or domain mapped multisite
	 * */
	public static function get_domain( $url ) {
		if ( is_multisite() && function_exists( 'domain_mapping_warning' ) ) {
			return '*';
		}


		$host = parse_url( $url, PHP_URL_HOST );

		if ( false === $host ) {
			return '*';
		}
		if ( 'www.' == substr( $host, 0, 4 ) ) {
			return substr( $host, 4 );
		}

		$host_parts = explode( '.', $host );

		if ( count( $host_parts ) > 2 ) {
			$host_parts = array_slice( $host_parts, -2, 2 );
		}

		return implode( '.', $host_parts );
	}

	/**
	 * Get path to WordPress install.
	 *
	 * Get the absolute filesystem path to the root of the WordPress installation.
	 *
	 * @since 4.3.0
	 *
	 * @return string Full filesystem path to the root of the WordPress installation
	 */
	public static function get_home_path() {

		$home    = set_url_scheme( get_option( 'home' ), 'http' );
		$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );

		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {

			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos                 = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );

			if ( $pos === false ) {

				$home_path = dirname( $_SERVER['SCRIPT_FILENAME'] );

			} else {

				$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );

			}

		} else {

			$home_path = ABSPATH;

		}

		return trailingslashit( str_replace( '\\', '/', $home_path ) );

	}

	/**
	 * Returns the root of the WordPress install.
	 *
	 * Gets the URI path to the WordPress installation.
	 *
	 * @since 4.0.6
	 *
	 * @return string the root folder
	 */
	public static function get_home_root() {
		if ( isset( $GLOBALS['__itsec_lib_get_home_root'] ) ) {
			return $GLOBALS['__itsec_lib_get_home_root'];
		}

		$url_parts = parse_url( site_url() );

		if ( isset( $url_parts['path'] ) ) {
			$GLOBALS['__itsec_lib_get_home_root'] = trailingslashit( $url_parts['path'] );
		} else {
			$GLOBALS['__itsec_lib_get_home_root'] = '/';
		}

		return $GLOBALS['__itsec_lib_get_home_root'];
	}

	/**
	 * Gets location of .htaccess
	 *
	 * Finds and returns path to .htaccess or nginx.conf if appropriate
	 *
	 * @since 4.0.0
	 *
	 * @return string path to .htaccess
	 */
	public static function get_htaccess() {
		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-config-file.php' );

		return ITSEC_Lib_Config_File::get_server_config_file_path();
	}

	/**
	 * Returns the actual IP address of the user.
	 *
	 * Determines the user's IP address by returning the forwarded IP address if present or
	 * the direct IP address if not.
	 *
	 * @since 4.0.0
	 *
	 * @return  String The IP address of the user
	 */
	public static function get_ip( $use_cache = true ) {
		if ( isset( $GLOBALS['__itsec_remote_ip'] ) && $use_cache ) {
			return $GLOBALS['__itsec_remote_ip'];
		}


		$ip = apply_filters( 'itsec-get-ip', false );

		if ( false !== $ip ) {
			$ip = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE );

			if ( ! empty( $ip ) ) {
				$GLOBALS['__itsec_remote_ip'] = $ip;
				return $ip;
			}
		}

		unset( $ip );


		if ( ITSEC_Modules::get_setting( 'global', 'proxy_override' ) ) {
			$GLOBALS['__itsec_remote_ip'] = $_SERVER['REMOTE_ADDR'];
			return $GLOBALS['__itsec_remote_ip'];
		}

		$headers = array(
			'HTTP_CF_CONNECTING_IP', // CloudFlare
			'HTTP_X_FORWARDED_FOR',  // Squid and most other forward and reverse proxies
			'REMOTE_ADDR',           // Default source of remote IP
		);

		$headers = apply_filters( 'itsec_filter_remote_addr_headers', $headers );

		$headers = (array) $headers;

		if ( ! in_array( 'REMOTE_ADDR', $headers ) ) {
			$headers[] = 'REMOTE_ADDR';
		}

		// Loop through twice. The first run won't accept a reserved or private range IP. If an acceptable IP is not
		// found, try again while accepting reserved or private range IPs.
		for ( $x = 0; $x < 2; $x++ ) {
			foreach ( $headers as $header ) {
				if ( ! isset( $_SERVER[$header] ) ) {
					continue;
				}

				$ip = trim( $_SERVER[$header] );

				if ( empty( $ip ) ) {
					continue;
				}

				if ( false !== ( $comma_index = strpos( $_SERVER[$header], ',' ) ) ) {
					$ip = substr( $ip, 0, $comma_index );
				}

				if ( 0 === $x ) {
					// First run through. Only accept an IP not in the reserved or private range.
					$ip = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE );
				} else {
					$ip = filter_var( $ip, FILTER_VALIDATE_IP );
				}

				if ( ! empty( $ip ) ) {
					break;
				}
			}

			if ( ! empty( $ip ) ) {
				break;
			}
		}

		if ( empty( $ip ) ) {
			// If an IP is not found, force it to a localhost IP that would not be blacklisted as this typically
			// indicates a local request that does not provide the localhost IP.
			$ip = '127.0.0.1';
		}

		$GLOBALS['__itsec_remote_ip'] = (string) $ip;

		return $GLOBALS['__itsec_remote_ip'];
	}

	/**
	 * Returns the server type of the plugin user.
	 *
	 * Attempts to figure out what http server the visiting user is running.
	 *
	 * @since 4.0.0
	 *
	 * @return string|bool server type the user is using of false if undetectable.
	 */
	public static function get_server() {
		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-utility.php' );

		return ITSEC_Lib_Utility::get_web_server();
	}

	public static function get_whitelisted_ips() {
		return apply_filters( 'itsec_white_ips', array() );
	}

	/**
	 * Determines whether a given IP address is whiteliste
	 *
	 * @param  string  $ip              ip to check (can be in CIDR notation)
	 * @param  array   $whitelisted_ips ip list to compare to if not yet saved to options
	 * @param  boolean $current         whether to whitelist the current ip or not (due to saving, etc)
	 *
	 * @return boolean true if whitelisted or false
	 */
	public static function is_ip_whitelisted( $ip, $whitelisted_ips = null, $current = false ) {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$ip = sanitize_text_field( $ip );

		if ( ITSEC_Lib::get_ip() === $ip && $itsec_lockout->is_visitor_temp_whitelisted() ) {
			return true;
		}

		if ( ! class_exists( 'ITSEC_Lib_IP_Tools' ) ) {
			require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-ip-tools.php' );
		}

		if ( is_null( $whitelisted_ips ) ) {
			$whitelisted_ips = self::get_whitelisted_ips();
		}

		if ( $current ) {
			$whitelisted_ips[] = ITSEC_Lib::get_ip(); //add current user ip to whitelist
		}

		if ( ! empty( $_SERVER['SERVER_ADDR'] ) ) {
			$whitelisted_ips[] = $_SERVER['SERVER_ADDR'];
		}

		if ( ! empty( $_SERVER['LOCAL_ADDR'] ) ) {
			$whitelisted_ips[] = $_SERVER['LOCAL_ADDR'];
		}

		foreach ( $whitelisted_ips as $whitelisted_ip ) {
			if ( ITSEC_Lib_IP_Tools::intersect( $ip, ITSEC_Lib_IP_Tools::ip_wild_to_ip_cidr( $whitelisted_ip ) ) ) {
				return true;
			}
		}

		return false;

	}

	public static function get_blacklisted_ips() {
		return apply_filters( 'itsec_filter_blacklisted_ips', array() );
	}

	/**
	 * Determines whether a given IP address is blacklisted
	 *
	 * @param string $ip              ip to check (can be in CIDR notation)
	 * @param array  $blacklisted_ips ip list to compare to if not yet saved to options
	 *
	 * @return boolean true if blacklisted or false
	 */
	public static function is_ip_blacklisted( $ip = null, $blacklisted_ips = null ) {
		$ip = sanitize_text_field( $ip );

		if ( empty( $ip ) ) {
			$ip = ITSEC_Lib::get_ip();
		}

		if ( ! class_exists( 'ITSEC_Lib_IP_Tools' ) ) {
			require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-ip-tools.php' );
		}

		if ( is_null( $blacklisted_ips ) ) {
			$blacklisted_ips = self::get_blacklisted_ips();
		}

		foreach ( $blacklisted_ips as $blacklisted_ip ) {
			if ( ITSEC_Lib_IP_Tools::intersect( $ip, ITSEC_Lib_IP_Tools::ip_wild_to_ip_cidr( $blacklisted_ip ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Set a 404 error.
	 *
	 * Forces the given page to a WordPress 404 error.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function set_404() {

		global $wp_query;

		status_header( 404 );

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}

		$wp_query->set_404();
		$page_404 = get_404_template();

		if ( 1 < strlen( $page_404 ) ) {

			include( $page_404 );

		} else {

			include( get_query_template( 'index' ) );

		}

		die();

	}

	/**
	 * Increases minimum memory limit.
	 *
	 * This function, adopted from builder, attempts to increase the minimum
	 * memory limit before heavy functions.
	 *
	 * @since 4.0.0
	 *
	 * @param int $new_memory_limit what the new memory limit should be
	 *
	 * @return void
	 */
	public static function set_minimum_memory_limit( $new_memory_limit ) {

		$memory_limit = @ini_get( 'memory_limit' );

		if ( - 1 < $memory_limit ) {

			$unit = strtolower( substr( $memory_limit, - 1 ) );
			$memory_limit = (int) $memory_limit;

			$new_unit = strtolower( substr( $new_memory_limit, - 1 ) );
			$new_memory_limit = (int) $new_memory_limit;

			if ( 'm' == $unit ) {

				$memory_limit *= 1048576;

			} else if ( 'g' == $unit ) {

				$memory_limit *= 1073741824;

			} else if ( 'k' == $unit ) {

				$memory_limit *= 1024;

			}

			if ( 'm' == $new_unit ) {

				$new_memory_limit *= 1048576;

			} else if ( 'g' == $new_unit ) {

				$new_memory_limit *= 1073741824;

			} else if ( 'k' == $new_unit ) {

				$new_memory_limit *= 1024;

			}

			if ( (int) $memory_limit < (int) $new_memory_limit ) {
				@ini_set( 'memory_limit', $new_memory_limit );
			}

		}

	}

	/**
	 * Checks if user exists.
	 *
	 * Checks to see if WordPress user with given id exists.
	 *
	 * @since 4.0.0
	 *
	 * @param int $user_id user id of user to check
	 *
	 * @return bool true if user exists otherwise false
	 *
	 * */
	public static function user_id_exists( $user_id ) {

		global $wpdb;

		//return false if username is null
		if ( '' == $user_id ) {
			return false;
		}

		//queary the user table to see if the user is there
		$saved_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM `" . $wpdb->users . "` WHERE ID= %d;", $user_id ) );

		if ( $saved_id == $user_id ) {

			return true;

		} else {

			return false;

		}

	}

	public static function show_status_message( $message ) {
		echo "<div class=\"updated fade\"><p><strong>$message</strong></p></div>\n";
	}

	public static function show_error_message( $message ) {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		}

		if ( ! is_string( $message ) ) {
			return;
		}

		echo "<div class=\"error\"><p><strong>$message</strong></p></div>\n";
	}

	public static function show_inline_status_message( $message ) {
		echo "<div class=\"updated fade inline\"><p><strong>$message</strong></p></div>\n";
	}

	public static function show_inline_error_message( $message ) {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		}

		if ( ! is_string( $message ) ) {
			return;
		}

		echo "<div class=\"error inline\"><p><strong>$message</strong></p></div>\n";
	}

	/**
	 * Get a WordPress user object.
	 *
	 * @param int|string|WP_User|bool $user Either the user ID ( must be an int ), the username, a WP_User object,
	 *                                      or false to retrieve the currently logged-in user.
	 *
	 * @return WP_User|false
	 */
	public static function get_user( $user = false ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}

		if ( false === $user ) {
			$user = wp_get_current_user();
		} else if ( is_int( $user ) ) {
			$user = get_user_by( 'id', $user );
		} else if ( is_string( $user ) ) {
			$user = get_user_by( 'login', $user );
		} else if ( is_object( $user ) && isset( $user->ID ) ) {
			$user = get_user_by( 'id', $user->ID );
		} else {
			if ( is_object( $user ) ) {
				$type = 'object(' . get_class( $user ) . ')';
			} else {
				$type = gettype( $user );
			}

			trigger_error( "ITSEC_Lib::get_user() called with an invalid \$user argument. Received \$user variable of type: $type", E_USER_ERROR );

			return false;
		}

		if ( $user instanceof WP_User ) {
			return $user;
		}

		return false;
	}

	/**
	 * Evaluate a password's strength.
	 *
	 * @param string $password
	 * @param array  $penalty_strings Additional strings that if found within the password, will decrease the strength.
	 *
	 * @return ITSEC_Zxcvbn_Results
	 */
	public static function get_password_strength_results( $password, $penalty_strings = array() ) {
		if ( ! isset( $GLOBALS['itsec_zxcvbn'] ) ) {
			require_once( ITSEC_Core::get_core_dir() . '/lib/itsec-zxcvbn-php/zxcvbn.php' );
			$GLOBALS['itsec_zxcvbn'] = new ITSEC_Zxcvbn();
		}

		return $GLOBALS['itsec_zxcvbn']->test_password( $password, $penalty_strings );
	}

	/**
	 * Retrieve the URL to a website to lookup the location of an IP address.
	 *
	 * @param string|bool $ip IP address to lookup, or false to return a URL to their home page.
	 *
	 * @return string
	 */
	public static function get_trace_ip_link( $ip = false ) {
		if ( empty( $ip ) ) {
			return 'http://www.traceip.net/';
		} else {
			return 'http://www.traceip.net/?query=' . urlencode( $ip );
		}
	}

	/**
	 * Whenever a login fails, collect details of the attempt, and forward them to modules.
	 *
	 * @param string $username
	 */
	public static function handle_wp_login_failed( $username ) {
		$details = self::get_login_details();

		do_action( 'itsec-handle-failed-login', $username, $details );
	}

	public static function get_login_details() {
		$authentication_types = array();

		if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$http_auth_type = substr( $_SERVER['HTTP_AUTHORIZATION'], 0, 6 );

			if ( 'Basic ' === $http_auth_type ) {
				$authentication_types[] = 'header_http_basic_auth';
			} else if ( 'OAuth ' === $http_auth_type ) {
				$authentication_types[] = 'header_http_oauth';
			}
		}

		if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
			$authentication_types[] = 'header_http_basic_auth';
		}

		if ( ! empty( $_GET['oauth_consumer_key'] ) ) {
			$authentication_types[] = 'query_oauth';
		}

		if ( ! empty( $_POST['oauth_consumer_key'] ) ) {
			$authentication_types[] = 'post_oauth';
		}

		if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
			$source = 'xmlrpc';
			$authentication_types = array( 'username_and_password' );
		} else if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$source = 'rest_api';
			$authentication_types[] = 'cookie';
		} else {
			$source = 'wp-login.php';
			$authentication_types = array( 'username_and_password' );
		}

		$details = compact( 'source', 'authentication_types' );

		return apply_filters( 'itsec-filter-failed-login-details', $details );
	}

	/**
	 * Reliably provides the URL path.
	 *
	 * It optionally takes a prefix that will be stripped from the path, if present. This is useful for use to get site
	 * URL paths without the site's subdirectory.
	 *
	 * Trailing slashes are not preserved.
	 *
	 * @param string $url    The URL to pull the path from.
	 * @param string $prefix [optional] A string prefix to be removed from the path.
	 *
	 * @return string The URL path.
	 */
	public static function get_url_path( $url, $prefix = '' ) {
		$path = (string) parse_url( $url, PHP_URL_PATH );
		$path = untrailingslashit( $path );

		if ( ! empty( $prefix ) && 0 === strpos( $path, $prefix ) ) {
			return substr( $path, strlen( $prefix ) );
		}

		return '';
	}

	/**
	 * Returns the current request path without the protocol, domain, site subdirectories, or query args.
	 *
	 * This function returns "wp-login.php" when requesting http://example.com/site-path/wp-login.php?action=register.
	 *
	 * @return string The requested site path.
	 */
	public static function get_request_path() {
		if ( ! isset( $GLOBALS['__itsec_lib_get_request_path'] ) ) {
			$request_uri = preg_replace( '|//+|', '/', $_SERVER['REQUEST_URI'] );
			$GLOBALS['__itsec_lib_get_request_path'] = self::get_url_path( $request_uri, self::get_home_root() );
		}

		return $GLOBALS['__itsec_lib_get_request_path'];
	}

	/**
	 * Acquire a lock.
	 *
	 * @since 6.3.0
	 *
	 * @param string $name       Lock name.
	 * @param int    $expires_in Number of seconds to hold the lock for.
	 *
	 * @return bool
	 */
	public static function get_lock( $name, $expires_in = 30 ) {

		/** @var \wpdb $wpdb */
		global $wpdb;
		$main_options = $wpdb->base_prefix . 'options';

		$lock = "itsec-lock-{$name}";
		$now = ITSEC_Core::get_current_time_gmt();
		$release_at = $now + $expires_in;

		if ( is_multisite() ) {
			$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `{$main_options}` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'no') /* LOCK */", $lock, $release_at ) );
		} else {
			$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'no') /* LOCK */", $lock, $release_at ) );
		}

		// The lock exists. See if it has expired.
		if ( ! $result ) {

			if ( is_multisite() && get_current_blog_id() !== 1 ) {
				$locked_until = $wpdb->get_var( $wpdb->prepare( "SELECT `option_value` FROM {$main_options} WHERE `option_name` = %s", $main_options ) );
			} else {
				$locked_until = get_option( $lock );
			}

			if ( ! $locked_until ) {
				// Can't write or read the lock. Bail due to an unknown and hopefully temporary error.
				return false;
			}

			if ( $locked_until > $now ) {
				// The lock still exists and has not expired.
				return false;
			}
		}

		// Ensure that the lock is set properly by triggering all the regular actions and filters.
		if ( ! is_multisite() || get_current_blog_id() === 1 ) {
			update_option( $lock, $release_at );
		} else {
			$wpdb->update( $main_options, array( 'option_value' => $release_at ), array( 'option_name' => $lock ) );

			if ( function_exists( 'wp_cache_switch_to_blog' ) ) {
				// Update persistent object caches
				$current = get_current_blog_id();
				wp_cache_switch_to_blog( 1 );

				$alloptions = wp_cache_get( 'alloptions' );

				if ( is_array( $alloptions ) && isset( $alloptions[ $lock ] ) ) {
					$alloptions[ $lock ] = $release_at;
					wp_cache_set( 'alloptions', $alloptions, 'options' );
				} else {
					wp_cache_set( $lock, $release_at, 'options' );
				}

				wp_cache_switch_to_blog( $current );
			}
		}

		return true;
	}

	/**
	 * Release a lock.
	 *
	 * @since 6.3.0
	 *
	 * @param string $name The lock name.
	 */
	public static function release_lock( $name ) {

		$lock = "itsec-lock-{$name}";

		if ( is_multisite() && get_current_blog_id() !== 1 ) {

			/** @var \wpdb $wpdb */
			global $wpdb;
			$main_options = $wpdb->base_prefix . 'options';

			$wpdb->delete( $main_options, array( 'option_name' => $lock ) );

			if ( function_exists( 'wp_cache_switch_to_blog' ) ) {
				// Update persistent object caches
				$current = get_current_blog_id();
				wp_cache_switch_to_blog( 1 );

				$alloptions = wp_cache_get( 'alloptions' );

				if ( is_array( $alloptions ) && isset( $alloptions[ $lock ] ) ) {
					unset( $alloptions[$lock] );
					wp_cache_set( 'alloptions', $alloptions, 'options' );
				} else {
					wp_cache_delete( $lock, 'options' );
				}

				wp_cache_switch_to_blog( $current );
			}
		} else {
			delete_option( $lock );
		}
	}

	/**
	 * Clear any expired locks.
	 *
	 * The vast majority of locks should be cleared by the same process that acquires them, however, this will clear locks that remain
	 * due to a time out or fatal error.
	 *
	 * @since 3.8.0
	 */
	public static function delete_expired_locks() {

		/** @var \wpdb $wpdb */
		global $wpdb;
		$main_options = $wpdb->base_prefix . 'options';

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT `option_name` FROM {$main_options} WHERE `option_name` LIKE %s AND `option_value` < %d",
			$wpdb->esc_like( 'itsec-lock-' ) . '%', ITSEC_Core::get_current_time_gmt()
		) );

		if ( $rows ) {
			if ( is_multisite() && get_current_blog_id() !== 1 ) {
				if ( function_exists( 'wp_cache_switch_to_blog' ) ) {
					// Update persistent object caches
					$current = get_current_blog_id();
					wp_cache_switch_to_blog( 1 );

					$alloptions = wp_cache_get( 'alloptions' );
					$set_all = false;

					foreach ( $rows as $row ) {
						$lock = $row->option_name;

						if ( is_array( $alloptions ) && isset( $alloptions[ $lock ] ) ) {
							unset( $alloptions[$lock] );
							$set_all = true;
						} else {
							wp_cache_delete( $lock, 'options' );
						}
					}

					if ( $set_all ) {
						wp_cache_set( 'alloptions', $alloptions );
					}

					wp_cache_switch_to_blog( $current );
				}

				$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$main_options} WHERE `option_name` LIKE %s AND `option_value` < %d",
					$wpdb->esc_like( 'itsec-lock-' ) . '%', ITSEC_Core::get_current_time_gmt()
				) );
			} else {
				foreach ( $rows as $row ) {
					delete_option( $row->option_name );
				}
			}
		}
	}

	/**
	 * Replace a tag with a given value.
	 *
	 * Will look in the content for a tag matching the {{ $tag_name }} pattern.
	 *
	 * @param string $content
	 * @param string $tag
	 * @param string $replacement
	 *
	 * @return string
	 */
	public static function replace_tag( $content, $tag, $replacement ) {
		return preg_replace( '/{{ \$' . preg_quote( $tag, '/' ) . ' }}/', $replacement, $content );
	}

	/**
	 * Replace multiple tags.
	 *
	 * @param string $content
	 * @param array  $tags Array of tag names to replacements.
	 *
	 * @return string
	 */
	public static function replace_tags( $content, $tags ) {
		foreach ( $tags as $tag => $replacement ) {
			$content = self::replace_tag( $content, $tag, $replacement );
		}

		return $content;
	}

	/**
	 * Get a percentage value indicating the probability that the site supports SSL.
	 *
	 * The need for a probability value is that a site could appear to support SSL yet the certificate is self-signed.
	 *
	 * @return int
	 */
	public static function get_ssl_support_probability() {
		if ( is_ssl() ) {
			$probability = 50; // The site appears to be on an SSL connection but it could be self-signed or otherwise
			                   // not valid to a visitor.
		} else {
			$probability = 0;
		}

		return apply_filters( 'itsec-ssl-support-probability', $probability );
	}

	/**
	 * Format a date using date_i18n and convert the time from GMT to local.
	 *
	 * @author Modified from ticket #25331
	 *
	 * @param int    $timestamp
	 * @param string $format Specify the format. If blank, will default to the date and time format settings.
	 *
	 * @return string
	 */
	public static function date_format_i18n_and_local_timezone( $timestamp, $format = '' ) {

		if ( ! $format ) {
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		}

		return date_i18n( $format, strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ) ) ) );
	}

	/**
	 * Get the value of an option directly from the database, bypassing any caching.
	 *
	 * @param string $option
	 *
	 * @return array|mixed
	 */
	public static function get_uncached_option( $option ) {
		/** @var $wpdb \wpdb */
		global $wpdb;

		$storage = array();

		if ( is_multisite() ) {
			$network_id = get_current_site()->id;
			$row        = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", $option, $network_id ) );

			if ( is_object( $row ) ) {
				$storage = maybe_unserialize( $row->meta_value );
			}
		} else {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

			if ( is_object( $row ) ) {
				$storage = maybe_unserialize( $row->option_value );
			}
		}

		return $storage;
	}

	public static function array_get( $array, $key, $default = null ) {

		if ( ! is_array( $array ) ) {
			return $default;
		}

		if ( null === $key ) {
			return $array;
		}

		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		}

		if ( strpos( $key, '.' ) === false ) {
			return isset( $array[ $key ] ) ? $array[ $key ] : $default;
		}

		foreach ( explode( '.', $key ) as $segment ) {
			if ( is_array( $array ) && isset( $array[ $segment ] ) ) {
				$array = $array[ $segment ];
			} else {
				return $default;
			}
		}

		return $array;
	}

	public static function print_r( $data, $args = array() ) {
		require_once( ITSEC_Core::get_core_dir() . '/lib/debug.php' );

		ITSEC_Debug::print_r( $data, $args );
	}

	public static function get_print_r( $data, $args = array() ) {
		require_once( ITSEC_Core::get_core_dir() . '/lib/debug.php' );

		return ITSEC_Debug::get_print_r( $data, $args );
	}

	/**
	 * Check if WP Cron appears to be running properly.
	 *
	 * @return bool
	 */
	public static function is_cron_working() {
		$working = ITSEC_Modules::get_setting( 'global', 'cron_status' );

		return $working === 1;
	}

	/**
	 * Should we be using Cron.
	 *
	 * @return bool
	 */
	public static function use_cron() {
		return ITSEC_Modules::get_setting( 'global', 'use_cron' );
	}

	/**
	 * Schedule a test to see if a user should be suggested to enable the Cron scheduler.
	 */
	public static function schedule_cron_test() {

		if ( defined( 'ITSEC_DISABLE_CRON_TEST' ) && ITSEC_DISABLE_CRON_TEST ) {
			return;
		}

		$crons = _get_cron_array();

		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron['itsec_cron_test'] ) ) {
				return;
			}
		}

		// Get a random time in the next 6-18 hours on a random minute.
		$time = ITSEC_Core::get_current_time_gmt() + mt_rand( 6, 18 ) * HOUR_IN_SECONDS + mt_rand( 1, 60 ) * MINUTE_IN_SECONDS;
		wp_schedule_single_event( $time, 'itsec_cron_test', array( $time ) );
		ITSEC_Modules::set_setting( 'global', 'cron_test_time', $time );
	}
}
