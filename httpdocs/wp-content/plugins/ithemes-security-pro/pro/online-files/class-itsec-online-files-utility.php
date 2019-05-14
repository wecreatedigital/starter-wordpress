<?php

/**
 * Class ITSEC_Online_Files_Utility
 */
class ITSEC_Online_Files_Utility {

	const STORAGE = 'itsec_online_files_hashes';

	/**
	 * Is the given plugin slug likely a WordPress.org plugin.
	 *
	 * This only checks the API as a last resort. It is possible that the plugin might mistakenly
	 * be identified as a WordPress.org plugin if it has a readme.txt file that resembles the .org format.
	 * However, that should be cleaned up automatically when trying to fetch hashes for it.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function is_likely_wporg_plugin( $slug ) {

		if ( null !== ( $pre = apply_filters( 'itsec_is_wporg_plugin', null, $slug ) ) ) {
			return $pre;
		}

		if ( null !== self::is_cached_wporg_plugin( $slug ) ) {
			return true;
		}

		$readme_file = trailingslashit( WP_PLUGIN_DIR ) . $slug . '/readme.txt';

		if ( file_exists( $readme_file ) && is_readable( $readme_file ) ) {
			$contents = trim( file_get_contents( $readme_file ) );

			if ( strpos( $contents, '===' ) === 0 ) {
				return true;
			}

			if ( strpos( $contents, '#' ) === 0 ) {
				return true;
			}
		}

		return self::query_is_wporg_plugin( $slug );
	}

	/**
	 * Check the cache for whether this plugin slug corresponds to a WordPress.org plugin.
	 *
	 * @param string $slug
	 *
	 * @return bool|null
	 */
	public static function is_cached_wporg_plugin( $slug ) {

		$plugins = ITSEC_Modules::get_setting( 'online-files', 'valid_wporg_plugins', array() );

		if ( is_array( $plugins ) && isset( $plugins[ $slug ] ) ) {

			if ( $plugins[ $slug ]['checked_at'] + WEEK_IN_SECONDS < ITSEC_Core::get_current_time_gmt() ) {
				ITSEC_Core::get_scheduler()->schedule_soon( 'confirm-valid-wporg-plugin', compact( 'slug' ) );
			}

			return $plugins[ $slug ]['valid'];
		}

		return null;
	}

	/**
	 * Make an API request to check if the given plugin slug corresponds to a WordPress.org plugin.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function query_is_wporg_plugin( $slug ) {

		if ( ! self::is_valid_wporg_slug( $slug ) ) {
			return false;
		}

		$is_valid = self::query_plugin_exists( $slug );

		if ( is_wp_error( $is_valid ) ) {
			return false;
		}

		$plugins = ITSEC_Modules::get_setting( 'online-files', 'valid_wporg_plugins', array() );

		if ( ! is_array( $plugins ) ) {
			$plugins = array();
		}

		$plugins[ $slug ] = array(
			'valid'      => $is_valid,
			'checked_at' => ITSEC_Core::get_current_time_gmt(),
		);

		ITSEC_Modules::set_setting( 'online-files', 'valid_wporg_plugins', $plugins );

		return $is_valid;
	}

	/**
	 * Query the WordPress.org Plugin Information API to determine if the given slug
	 * exists on WordPress.org.
	 *
	 * @param string $slug
	 *
	 * @return bool|WP_Error
	 */
	private static function query_plugin_exists( $slug ) {

		$url = 'https://api.wordpress.org/plugins/info/1.0/';

		$response = wp_remote_post( $url, array(
			'timeout' => 15,
			'body'    => array(
				'action'  => 'plugin_information',
				'request' => serialize( (object) compact( 'slug' ) ),
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( 'N;' === $body ) {
			return false;
		}

		$data = maybe_unserialize( $body );

		if ( ! is_object( $data ) && ! is_array( $data ) ) {
			return new WP_Error();
		}

		return true;
	}

	/**
	 * Clear whether the given plugin slug is a valid WordPress.org plugin.
	 *
	 * @param string $slug
	 */
	public static function clear_is_wporg_plugin( $slug ) {

		$plugins = ITSEC_Modules::get_setting( 'online-files', 'valid_wporg_plugins', array() );

		if ( is_array( $plugins ) && isset( $plugins[ $slug ] ) ) {
			unset( $plugins[ $slug ] );

			ITSEC_Modules::set_setting( 'online-files', 'valid_wporg_plugins', $plugins );
		}
	}

	/**
	 * Get the hashes for a WordPress.org plugin.
	 *
	 * @param string $slug
	 * @param string $version
	 *
	 * @return array
	 */
	public static function get_wporg_plugin_hashes( $slug, $version ) {

		if ( false !== ( $cached = self::get_cached_wporg_plugin_hashes( $slug, $version ) ) ) {
			return $cached;
		}

		return self::load_wporg_plugin_hashes( $slug, $version );
	}

	/**
	 * Get the cached hashes for a plugin on WordPress.org.
	 *
	 * @param string $slug
	 * @param string $version
	 *
	 * @return array|false The hashes stored, which may be an empty array, or False if no value in the cache.
	 */
	public static function get_cached_wporg_plugin_hashes( $slug, $version ) {

		$all_hashes = self::get_setting( 'wporg_plugin_hashes', array() );

		if ( ! isset( $all_hashes[ $slug ][ $version ] ) ) {
			return false;
		}

		$hashes     = $all_hashes[ $slug ][ $version ]['hashes'];
		$checked_at = $all_hashes[ $slug ][ $version ]['checked_at'];

		// If the hashes are expired, schedule a background update. If no hashes were found, we check sooner.
		if (
			( empty( $hashes ) && $checked_at + HOUR_IN_SECONDS < ITSEC_Core::get_current_time_gmt() ) ||
			$checked_at + WEEK_IN_SECONDS < ITSEC_Core::get_current_time_gmt()
		) {
			ITSEC_Core::get_scheduler()->schedule_soon( 'preload-plugin-hashes', compact( 'slug', 'version' ) );
		}

		return $hashes;
	}

	/**
	 * Fetch hashes for a WordPress.org plugin and store them for later use.
	 *
	 * @param string $slug
	 * @param string $version
	 *
	 * @return array
	 */
	public static function load_wporg_plugin_hashes( $slug, $version ) {

		if ( ! self::is_valid_wporg_slug( $slug ) ) {
			return array();
		}

		$all_hashes = self::get_setting( 'wporg_plugin_hashes', array() );

		$hashes = self::query_wporg_plugin_hashes( $slug, $version );

		if ( isset( $all_hashes[ $slug ] ) ) {
			$plugin_hashes = $all_hashes[ $slug ];
		} else {
			$plugin_hashes = array();
		}

		if ( $plugin_hashes ) {
			uksort( $plugin_hashes, 'version_compare' );
			array_shift( $plugin_hashes );
		}

		$plugin_hashes[ $version ] = array(
			'hashes'     => $hashes,
			'checked_at' => ITSEC_Core::get_current_time_gmt(),
		);

		$all_hashes[ $slug ] = $plugin_hashes;
		self::set_setting( 'wporg_plugin_hashes', $all_hashes );

		return $hashes;
	}

	/**
	 * Clear the stored hashes for a WordPress.org plugin.
	 *
	 * @param string $slug
	 */
	public static function clear_wporg_plugin_hashes( $slug ) {

		$all_hashes = self::get_setting( 'wporg_plugin_hashes', array() );

		if ( isset( $all_hashes[ $slug ] ) ) {
			unset( $all_hashes[ $slug ] );
			self::set_setting( 'wporg_plugin_hashes', $all_hashes );
		}
	}

	/**
	 * Query the WordPress.org checksum API.
	 *
	 * @param string $slug
	 * @param string $version
	 *
	 * @return array
	 */
	private static function query_wporg_plugin_hashes( $slug, $version ) {

		if ( ! self::is_valid_wporg_slug( $slug ) ) {
			return array();
		}

		$url = "https://downloads.wordpress.org/plugin-checksums/{$slug}/{$version}.json";

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 404 === (int) $code ) {
			ITSEC_Core::get_scheduler()->schedule_soon( 'confirm-valid-wporg-plugin', compact( 'slug' ) );

			return array();
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body ) {
			return array();
		}

		$data = json_decode( $body, true );

		if ( ! $data || empty( $data['files'] ) ) {
			return array();
		}

		return wp_list_pluck( $data['files'], 'md5' );
	}

	/**
	 * Get the installed WordPress Core hashes.
	 *
	 * @return array
	 */
	public static function get_current_core_hashes() {
		return self::get_core_hashes( $GLOBALS['wp_version'], get_locale() );
	}

	/**
	 * Get the WordPress core hashes.
	 *
	 * @param string $version
	 * @param string $locale
	 *
	 * @return array
	 */
	public static function get_core_hashes( $version, $locale ) {

		if ( false !== ( $cached = self::get_cached_core_hashes( $version, $locale ) ) ) {
			return $cached;
		}

		return self::load_core_hashes( $version, $locale );
	}

	/**
	 * Load WordPress Core hashes.
	 *
	 * @param string $version
	 * @param string $locale
	 *
	 * @return array
	 */
	public static function load_core_hashes( $version, $locale ) {

		$hashes = self::query_core_hashes( $version, $locale );

		$all_hashes = array(
			"{$version}-{$locale}" => array(
				'hashes'     => $hashes,
				'checked_at' => ITSEC_Core::get_current_time_gmt(),
			)
		);

		self::set_setting( 'core_hashes', $all_hashes );

		return $hashes;
	}

	/**
	 * Get the cached WordPress core hashes.
	 *
	 * @param string $version
	 * @param string $locale
	 *
	 * @return array|false The hashes stored, which may be an empty array, or False if no value in the cache.
	 */
	public static function get_cached_core_hashes( $version, $locale ) {

		$all_hashes = self::get_setting( 'core_hashes', array() );

		if ( ! isset( $all_hashes["{$version}-{$locale}"] ) ) {
			return false;
		}

		$hashes     = $all_hashes["{$version}-{$locale}"]['hashes'];
		$checked_at = $all_hashes["{$version}-{$locale}"]['checked_at'];

		if (
			( empty( $hashes ) && $checked_at + HOUR_IN_SECONDS < ITSEC_Core::get_current_time_gmt() ) ||
			$checked_at + WEEK_IN_SECONDS < ITSEC_Core::get_current_time_gmt()
		) {
			ITSEC_Core::get_scheduler()->schedule_soon( 'preload-core-hashes', compact( 'version', 'locale' ) );
		}

		return $hashes;
	}

	/**
	 * Query the WordPress.org API to get checksums for WordPress core.
	 *
	 * @param string $version
	 * @param string $locale
	 *
	 * @return array
	 */
	public static function query_core_hashes( $version, $locale ) {
		$results = wp_remote_get( "https://api.wordpress.org/core/checksums/1.0/?version=$version&locale=$locale" );

		if ( is_wp_error( $results ) ) {
			return array();
		}

		$body = json_decode( $results['body'], true );

		if ( empty( $body['checksums'] ) || ! is_array( $body['checksums'] ) ) {
			return array();
		}

		return $body['checksums'];
	}

	/**
	 * Get the hashes for an iThemes Package.
	 *
	 * @param string $package
	 * @param string $version
	 *
	 * @return array
	 */
	public static function get_ithemes_hashes( $package, $version ) {

		if ( false !== ( $cached = self::get_cached_ithemes_hashes( $package, $version ) ) ) {
			return $cached;
		}

		return self::load_ithemes_hashes( $package, $version );
	}

	/**
	 * Retrieve an iThemes Package's hash from the cache.
	 *
	 * @param string $package
	 * @param string $version
	 *
	 * @return array|false The hashes stored, which may be an empty array, or False if no value in the cache.
	 */
	public static function get_cached_ithemes_hashes( $package, $version ) {

		$all_hashes = self::get_setting( 'ithemes_hashes', array() );

		if ( ! isset( $all_hashes[ $package ][ $version ] ) ) {
			return false;
		}

		$hashes     = $all_hashes[ $package ][ $version ]['hashes'];
		$checked_at = $all_hashes[ $package ][ $version ]['checked_at'];

		if (
			( empty( $hashes ) && $checked_at + HOUR_IN_SECONDS < ITSEC_Core::get_current_time_gmt() ) ||
			$checked_at + WEEK_IN_SECONDS < ITSEC_Core::get_current_time_gmt()
		) {
			ITSEC_Core::get_scheduler()->schedule_soon( 'preload-ithemes-hashes', compact( 'package', 'version' ) );
		}

		return $hashes;
	}

	/**
	 * Load the file hashes for an iThemes Package into the cache.
	 *
	 * @param string $package
	 * @param string $version
	 *
	 * @return array
	 */
	public static function load_ithemes_hashes( $package, $version ) {

		$hashes = self::query_ithemes_file_hashes( $package, $version );

		$all_hashes = self::get_setting( 'ithemes_hashes', array() );

		if ( ! isset( $all_hashes[ $package ] ) ) {
			$all_hashes[ $package ] = array();
		}

		$all_hashes[ $package ][ $version ] = array(
			'hashes'     => $hashes,
			'checked_at' => ITSEC_Core::get_current_time_gmt(),
		);
		self::set_setting( 'ithemes_hashes', $all_hashes );

		return $hashes;
	}

	/**
	 * Query S3 for file hashes for an iThemes Package.
	 *
	 * @param string $package
	 * @param string $version
	 *
	 * @return array
	 */
	public static function query_ithemes_file_hashes( $package, $version ) {
		$results = wp_remote_get( "https://s3.amazonaws.com/package-hash.ithemes.com/$package/$version.json" );

		if ( is_wp_error( $results ) ) {
			return array();
		}

		$body = json_decode( $results['body'], true );

		if ( empty( $body ) || ! is_array( $body ) ) {
			return array();
		}

		// iThemes packages have their package directory included in the hash list.
		$cleaned = array();

		foreach ( $body as $file => $hash ) {
			$cleaned[ self::strip_first_directory( $file ) ] = $hash;
		}

		return $cleaned;
	}

	/**
	 * Clear the stored hashes for a WordPress.org plugin.
	 *
	 * @param string $package
	 */
	public static function clear_ithemes_hashes( $package ) {

		$all_hashes = self::get_setting( 'ithemes_hashes', array() );

		if ( isset( $all_hashes[ $package ] ) ) {
			unset( $all_hashes[ $package ] );
			self::set_setting( 'ithemes_hashes', $all_hashes );
		}
	}

	/**
	 * Strip the first directory from a path.
	 *
	 * For example:
	 *
	 * ithemes-security-pro/ithemes-security-pro.php -> ithemes-security-pro.php
	 * ithemes-security-pro/core/notify.php -> core/notify.php
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	private static function strip_first_directory( $path ) {
		$parts = explode( '/', $path, 2 );

		return isset( $parts[1] ) ? $parts[1] : '';
	}

	/**
	 * Check if the slug is a valid WordPress.org slug.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	private static function is_valid_wporg_slug( $slug ) {
		return ! empty( $slug ) && '.' !== $slug;
	}

	/**
	 * Retrieve a setting.
	 *
	 * @param string $setting
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	private static function get_setting( $setting, $default = null ) {

		$settings = get_site_option( self::STORAGE, array() );

		if ( ! is_array( $settings ) || ! isset( $settings[ $setting ] ) ) {
			return $default;
		}

		return $settings[ $setting ];
	}

	/**
	 * Set a setting.
	 *
	 * @param string $setting
	 * @param mixed  $values
	 */
	private static function set_setting( $setting, $values ) {

		$settings = get_site_option( self::STORAGE, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings[ $setting ] = $values;
		update_site_option( self::STORAGE, $settings );
	}
}