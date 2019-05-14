<?php

final class ITSEC_VM_Utility {
	private static $wordpress_release_dates = false;

	public static function get_email_addresses() {

		_deprecated_function( __METHOD__, '3.9.0', 'ITSEC_Notification_Center::get_recipients' );

		$nc = ITSEC_Core::get_notification_center();

		if ( $nc->is_notification_enabled( 'old-site-scan' ) ) {
			return $nc->get_recipients( 'old-site-scan' );
		}

		if ( $nc->is_notification_enabled( 'automatic-updates-debug' ) ) {
			return $nc->get_recipients( 'automatic-updates-debug' );
		}

		return array();
	}

	public static function is_wordpress_version_outdated( $version = false ) {
		if ( false === $version ) {
			$version = self::get_wordpress_version();
		}

		$version = self::get_clean_version( $version );

		if ( false === $version ) {
			// If the version is invalid, assume that it is outdated since the version file has likely been modified.
			return true;
		}

		$release_dates = self::get_wordpress_release_dates();

		if ( empty( $release_dates ) ) {
			// If release data is missing, the tests cannot proceed.
			return false;
		}

		uksort( $release_dates, 'version_compare' );

		$latest_timestamp = end( $release_dates );
		$latest_version = key( $release_dates );

		$previous_timestamp = prev( $release_dates );
		$previous_version = key( $release_dates );

		// If this version is the previous release version and the latest release version has been out for less than a
		// month, do not list this version as outdated.
		if ( $version === $previous_version && $latest_timestamp > time() - MONTH_IN_SECONDS ) {
			return false;
		}

		if ( ! isset( $release_dates[$version] ) ) {
			$latest_major_version = self::get_major_version( $latest_version );
			$current_major_version = self::get_major_version( $version );

			if ( $latest_major_version === $current_major_version && version_compare( $version, $latest_version, '>=' ) ) {
				// Looks like a new minor release that hasn't come through in the release dates details yet.
				return false;
			}

			$next_major_version = self::get_next_major_version( $latest_version );

			if ( false !== $next_major_version && ( $version === $next_major_version || $version === "$next_major_version.0" ) ) {
				// This version is a development version.
				return false;
			}

			$next_development_version = self::get_next_major_version( $next_major_version );

			if ( false !== $next_development_version && ( $version === $next_development_version || $version === "$next_development_version.0" ) ) {
				// This version is the latest development version shortly after a new major version is released. It's
				// also possible that it is a fake version, but we'll assume that it's a development version.
				return false;
			}

			// Return true since the version is likely fake to fool automatic upgrades.
			return true;
		}

		if ( version_compare( $version, $latest_version, '>=' ) ) {
			// Running a current version.
			return false;
		}

		$current_version_timestamp = $release_dates[$version];
		$timestamp_diff = $latest_timestamp - $current_version_timestamp;

		if ( $timestamp_diff >= MONTH_IN_SECONDS ) {
			// If a month or more of time spans between the release of this version and the latest version, this version
			// is outdated.
			return true;
		}

		$latest_major_version = self::get_major_version( $latest_version );

		// Tests when the version is an older major version.
		if ( false !== $latest_major_version && version_compare( $version, $latest_major_version, '<' ) ) {
			if ( isset( $release_dates[$latest_major_version] ) ) {
				$latest_major_timestamp = $release_dates[$latest_major_version];
			} else if ( isset( $release_dates["$latest_major_version.0"] ) ) {
				$latest_major_timestamp = $release_dates["$latest_major_version.0"];
			}

			$latest_major_age = time() - $latest_major_timestamp;

			if ( isset( $latest_major_timestamp ) && $latest_major_age >= MONTH_IN_SECONDS ) {
				// If the latest major version has been out for a month or more and this version is an older major
				// major version, this version is outdated.
				return true;
			}

			return false;
		}

		// This version is not the latest release, but it is not old enough to be considered outdated.
		return false;
	}

	public static function get_major_version( $version ) {
		if ( ! preg_match( '/^(\d+)\.(\d+)/', $version, $match ) ) {
			return false;
		}

		return $match[1] . '.' . $match[2];
	}

	public static function get_next_major_version( $version ) {
		if ( ! preg_match( '/^(\d+)\.(\d+)/', $version, $match ) ) {
			return false;
		}

		if ( $match[2] > 8 ) {
			return ( $match[1] + 1 ) . '.0';
		}

		return $match[1] . '.' . ( $match[2] + 1 );
	}

	public static function get_clean_version( $version ) {
		if ( preg_match( '/^(\d+\.\d+(?:\.\d+)?)/', $version, $match ) ) {
			return $match[1];
		}

		return false;
	}

	public static function get_wordpress_version( $version_file_path = false ) {
		if ( false === $version_file_path ) {
			$version_file_path = ABSPATH . WPINC . '/version.php';
		}

		$fh = fopen( $version_file_path, 'r' );

		if ( false === $fh || feof( $fh ) ) {
			return false;
		}

		$content = fread( $fh, 2048 );
		fclose( $fh );

		if ( preg_match( '/\\$wp_version = \'([^\']+)\';/', $content, $match ) ) {
			return $match[1];
		}

		return false;
	}

	public static function get_wordpress_release_dates() {
		if ( is_array( self::$wordpress_release_dates ) ) {
			return self::$wordpress_release_dates;
		}

		$data = get_site_option( 'itsec_vm_wp_releases' );

		if ( is_array( $data ) && isset( $data['expires'] ) && $data['expires'] > time() && isset( $data['dates'] ) ) {
			self::$wordpress_release_dates = $data['dates'];
			return $data['dates'];
		}

		$data = array(
			'expires' => time() + DAY_IN_SECONDS,
			'dates'   => isset( $data['dates'] ) ? $data['dates'] : array(),
		);

		$https_url = 'https://s3.amazonaws.com/downloads.ithemes.com/public/wordpress-release-dates.json';
		$http_url = 'http://downloads.ithemes.com/public/wordpress-release-dates.json';

		if ( wp_http_supports( array( 'ssl' ) ) ) {
			$response = wp_remote_get( $https_url );
		}

		if ( ! isset( $response ) || is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$response = wp_remote_get( $http_url );
		}

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$dates = json_decode( $response['body'], true );

			if ( is_array( $dates ) ) {
				uksort( $dates, 'version_compare' );
				$data['dates'] = $dates;
			}
		}

		// Refresh more quickly if something went wrong with loading the data.
		if ( empty( $data['dates'] ) ) {
			$data['expires'] = time() + HOUR_IN_SECONDS;
		}

		update_site_option( 'itsec_vm_wp_releases', $data );

		self::$wordpress_release_dates = $data['dates'];

		return $data['dates'];
	}

	public static function get_automatic_update_statuses() {
		global $wp_theme_directories;

		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );


		$errors = array();

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			$errors[] = new WP_Error( 'itsec-vm-cron-disabled-by-define', wp_kses( __( 'The <code>DISABLE_WP_CRON</code> define is set to a true value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
		}

		if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
			$errors[] = new WP_Error( 'itsec-vm-file-mods-disabled-by-define', wp_kses( __( 'The <code>DISALLOW_FILE_MODS</code> define is set to a true value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
		}

		if ( false === apply_filters( 'file_mod_allowed', true, 'automatic_updater' ) ) {
			$errors[] = new WP_Error( 'itsec-vm-file-mods-disabled-by-filter', wp_kses( __( 'The <code>file_mod_allowed</code> filter returned a false value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
		}

		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			$errors[] = new WP_Error( 'itsec-vm-wp-installing-define-set', wp_kses( __( 'The <code>WP_INSTALLING</code> define is set to a true value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
		}

		if ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ) {
			$errors[] = new WP_Error( 'itsec-vm-automatic-updater-disabled-by-define', wp_kses( __( 'The <code>AUTOMATIC_UPDATER_DISABLED</code> define is set to a true value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
		}

		if ( apply_filters( 'automatic_updater_disabled', false ) ) {
			$errors[] = new WP_Error( 'itsec-vm-automatic-updater-disabled-by-filter', wp_kses( __( 'The <code>automatic_updater_disabled</code> filter returned a false value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
		}

		if ( is_multisite() ) {
			$errors[] = new WP_Error( 'itsec-vm-site-is-multisite', __( 'This site is a multisite installation. Automatic updates run on the cron system and will only run when the cron system is triggered by a request to the main site. This means that the main site must receive periodic page requests or automatic updates will not run.', 'it-l10n-ithemes-security-pro' ) );
		}


		WP_Upgrader::release_lock( 'auto_updater' );

		if ( ! WP_Upgrader::create_lock( 'auto_updater' ) ) {
			$errors[] = new WP_Error( 'itsec-vm-could-not-create-lock', __( 'Part of the update process is creating a lock that prevents multiple automatic updates from running at the same time. Your site may have issue creating these locks which could prevent automatic updates from running successfully.', 'it-l10n-ithemes-security-pro' ) );
		}

		WP_Upgrader::release_lock( 'auto_updater' );


		$statuses['all'] = $errors;
		$statuses['core'] = self::get_automatic_update_status_for_type( 'core', ABSPATH );
		$statuses['plugin'] = self::get_automatic_update_status_for_type( 'plugin', WP_PLUGIN_DIR );
		$statuses['theme'] = array();
		$statuses['translation'] = self::get_automatic_update_status_for_type( 'translation', WP_CONTENT_DIR );

		foreach ( $wp_theme_directories as $directory ) {
			$statuses['theme'] = array_merge( $statuses['theme'], self::get_automatic_update_status_for_type( 'theme', $directory ) );
		}


		return $statuses;
	}

	private static function get_automatic_update_status_for_type( $type, $context ) {
		global $wp_version, $wpdb;

		$skin = new Automatic_Upgrader_Skin();
		$upgrader = new WP_Automatic_Updater();

		$errors = array();


		if ( 'core' === $type ) {
			$item = (object) array(
				'response'        => 'latest',
				'download'        => 'https://downloads.wordpress.org/release/wordpress-4.8.zip',
				'locale'          => 'en_US',
				'packages'        => (object) array(
					'full'        => 'https://downloads.wordpress.org/release/wordpress-4.8.zip',
					'no_content'  => 'https://downloads.wordpress.org/release/wordpress-4.8-no-content.zip',
					'new_bundled' => 'https://downloads.wordpress.org/release/wordpress-4.8-new-bundled.zip',
					'partial'     => false,
					'rollback'    => false,
				),
				'current'         => '4.8',
				'version'         => '4.8',
				'php_version'     => '5.2.4',
				'mysql_version'   => '5.0',
				'new_bundled'     => '4.7',
				'partial_version' => false,
			);
		} else if ( 'plugin' === $type ) {
			$item = (object) array(
				'id'          => 'w.org/plugins/hello-dolly',
				'slug'        => 'hello-dolly',
				'plugin'      => 'hello.php',
				'new_version' => '1.6',
				'url'         => 'https://wordpress.org/plugins/hello-dolly/',
				'package'     => 'https://downloads.wordpress.org/plugin/hello-dolly.1.6.zip',
			);
		} else if ( 'theme' === $type ) {
			$item = (object) array(
				'theme'       => 'twentyfifteen',
				'new_version' => '1.7',
				'url'         => 'https://wordpress.org/themes/twentyfifteen/',
				'package'     => 'https://downloads.wordpress.org/theme/twentyfifteen.1.7.zip',
			);
		} else if ( 'translation' === $type ) {
			$item = (object) array(
				'type'     => 'theme',
				'slug'     => 'twentyfifteen',
				'language' => 'en_GB',
				'version'  => '1.6',
				'updated'  => '2015-08-18 16:52:12',
				'packages' => 'https://downloads.wordpress.org/translation/theme/twentyfifteen/1.6/en_GB.zip',
			);
		} else {
			$item = (object) array();
		}


		if ( ! $skin->request_filesystem_credentials( false, $context ) ) {
			$errors[] = new WP_Error( 'itsec-vm-updates-disabled-by-file-permissions', __( 'WordPress is unable to modify the necessary files. This is often caused by a server configuration issue that has PHP run as a different user than the user that owns the files.', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( $upgrader->is_vcs_checkout( $context ) ) {
			if ( apply_filters( 'automatic_updates_is_vcs_checkout', false, $context ) ) {
				$errors[] = new WP_Error( 'itsec-vm-updates-disabled-by-version-control-filter', wp_kses( __( 'The <code>automatic_updates_is_vcs_checkout</code> filter returned a true value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
			} else {
				$errors[] = new WP_Error( 'itsec-vm-updates-disabled-by-version-control', __( 'The files are under version control such as being part of a SVN, Git, Mercurial, Bazaar, or other VCS repository. This disables automatic updates since presence of version control indicates that the files are managed via a non-standard process.', 'it-l10n-ithemes-security-pro' ) );
			}
		}

		if ( ! apply_filters( "auto_update_{$type}", true, $item ) ) {
			$errors[] = new WP_Error( "itsec-vm-auto-updates-$type-disabled-by-filter", sprintf( wp_kses( __( 'The <code>%s</code> filter returned a false value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ), "auto_update_$type" ) );
		}

		if ( 'core' === $type ) {
			if ( defined( 'WP_AUTO_UPDATE_CORE' ) && ! WP_AUTO_UPDATE_CORE ) {
				$errors[] = new WP_Error( 'itsec-vm-updates-disabled-by-wp-auto-update-core-define', wp_kses( __( 'The <code>WP_AUTO_UPDATE_CORE</code> define is set to a false value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
			}

			if ( (bool) strpos( $wp_version, '-' ) ) {
				if ( defined( 'WP_AUTO_UPDATE_CORE' ) && true !== WP_AUTO_UPDATE_CORE ) {
					$errors[] = new WP_Error( 'itsec-vm-updates-disabled-by-wp-auto-update-core-define', wp_kses( __( 'The <code>WP_AUTO_UPDATE_CORE</code> define is present and not set to <code>true</code>.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
				}

				if ( ! apply_filters( 'allow_dev_auto_core_updates', true ) ) {
					$errors[] = new WP_Error( 'itsec-vm-core-dev-auto-updates-disabled-by-filter', wp_kses( __( 'The <code>allow_dev_auto_core_updates</code> filter returned a false value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
				}
			} else {
				$message = '';

				if ( defined( 'WP_AUTO_UPDATE_CORE' ) && ! WP_AUTO_UPDATE_CORE ) {
					$errors[] = new WP_Error( 'itsec-vm-updates-disabled-by-wp-auto-update-core-define', wp_kses( __( 'The <code>WP_AUTO_UPDATE_CORE</code> define is present and set to a false value.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
				}

				if ( ! apply_filters( 'allow_minor_auto_core_updates', true ) ) {
					$errors[] = new WP_Error( 'itsec-vm-core-minor-auto-updates-disabled-by-filter', wp_kses( __( 'The <code>allow_minor_auto_core_updates</code> filter returned a false value. This prevents automatically updating to new minor versions of WordPress, such as updating from 4.0 to 4.0.1.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
				}

				if ( ! apply_filters( 'allow_major_auto_core_updates', true ) ) {
					$errors[] = new WP_Error( 'itsec-vm-core-major-auto-updates-disabled-by-filter', wp_kses( __( 'The <code>allow_major_auto_core_updates</code> filter returned a false value. This prevents automatically updating to new major versions of WordPress, such as updating from 4.0 to 4.1.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ) );
				}
			}


			if ( version_compare( phpversion(), '5.2.4', '<' ) ) {
				$errors[] = new WP_Error( 'itsec-vm-core-failed-php-compatibility', sprintf( wp_kses( __( "The server's PHP version (<code>%s</code>) is too old and is incompatible with newer versions of WordPress. This is a critical issue as older versions of PHP can be vulnerable to security issues.", 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ), phpversion() ) );
			}

			if ( ( ! file_exists( WP_CONTENT_DIR . '/db.php' ) || ! empty( $wpdb->is_mysql ) ) && version_compare( $wpdb->db_version(), '5.0', '<' ) ) {
				$errors[] = new WP_Error( 'itsec-vm-core-failed-mysql-compatibility', sprintf( wp_kses( __( "The server's MySQL version (<code>%s</code>) is too old and is incompatible with newer versions of WordPress. This is a critical issue as older versions of MySQL can be vulnerable to security issues.", 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ), $wpdb->db_version() ) );
			}
		}


		return $errors;
	}

	public static function should_auto_update_plugin( $file, $version ) {
		return self::should_auto_update_package( 'plugin', $file, $version );
	}

	public static function should_auto_update_theme( $file, $version ) {
		return self::should_auto_update_package( 'theme', $file, $version );
	}

	private static function should_auto_update_package( $type, $file, $version ) {

		$global = ITSEC_Modules::get_setting( 'version-management', "{$type}_automatic_updates" );

		if ( 'none' === $global ) {
			return false;
		}

		if ( 'all' === $global ) {
			return true;
		}

		$packages = ITSEC_Modules::get_setting( 'version-management', 'packages' );

		if ( ! isset( $packages["{$type}:{$file}"] ) ) {
			return false;
		}

		$config = $packages["{$type}:{$file}"];

		if ( 'disabled' === $config['type'] ) {
			return false;
		}

		if ( 'enabled' === $config['type'] ) {
			return true;
		}

		$first_seen = ITSEC_Modules::get_setting( 'version-management', 'first_seen' );

		if ( ! isset( $first_seen[ $type ][ $file ][ $version ] ) ) {
			return false;
		}

		$seconds_available = ITSEC_Core::get_current_time_gmt() - $first_seen[ $type ][ $file ][ $version ];

		return $seconds_available > DAY_IN_SECONDS * $config['delay'];
	}
}
