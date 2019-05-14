<?php

class ITSEC_Grading_System_Section_Software extends ITSEC_Grading_System_Section {
	private $php_version_support;
	private $wordpress_version_support;

	public function get_id() {
		return 'software';
	}

	public function get_name() {
		return esc_html__( 'Software', 'it-l10n-ithemes-security-pro' );
	}

	public function get_description() {
		return esc_html__( 'WordPress, PHP, plugin, and theme versions', 'it-l10n-ithemes-security-pro' );
	}

	public function get_weights() {
		return array(
			'wordpress' => 500,
			'php'       => 100,
			'plugin'    => 25,
			'theme'     => 25,
		);
	}

	public function resolve_issue( $id ) {
		if ( 'wordpress' === $id ) {
			$this->update_wordpress();
		} else if ( 'php' === $id ) {
			$this->update_php();
		} else {
			list( $type, $slug ) = explode( ':', $id, 2 );

			if ( 'theme' === $type ) {
				$this->update_theme( $slug );
			}
		}
	}

	public function after_resolve_issues( $issues ) {

		$permissions_checked = false;

		$names          = array();
		$plugins        = array();
		$update_plugins = get_site_transient( 'update_plugins' );

		foreach ( $issues as $issue ) {
			if ( 0 !== strpos( $issue, 'plugin:' ) ) {
				continue;
			}

			list( , $slug ) = explode( ':', $issue, 2 );

			if ( ! file_exists( WP_PLUGIN_DIR . "/$slug" ) ) {
				ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-unable-to-update-plugin-missing', sprintf( __( 'Unable to update the requested plugin: <code>%s</code>. The plugin could not be found.', 'it-l10n-ithemes-security-pro' ), $slug ) ) );
				continue;
			}

			$plugin = get_plugin_data( WP_PLUGIN_DIR . "/$slug" );

			if ( ! empty( $plugin['Name'] ) ) {
				$name = $plugin['Name'];
			} else {
				$name = dirname( $slug );
			}

			if ( ! $permissions_checked ) {
				$permissions_checked = true;

				if ( ! current_user_can( 'update_plugins' ) ) {
					ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-insufficient-permissions-update_plugins', sprintf( __( 'Unable to update the %s plugin. Your user does not have the necessary permissions to update plugins. Please log in as a user with permission to update plugins or contact someone that does, and try again.', 'it-l10n-ithemes-security-pro' ), $name ) ) );

					return;
				}
			}

			if ( ! is_object( $update_plugins ) || ! isset( $update_plugins->response[ $slug ] ) ) {
				ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-invalid-update_plugins-data', sprintf( __( 'Unable to update the %s plugin. The update data for the plugin is missing or corrupted.', 'it-l10n-ithemes-security-pro' ), $name ) ) );
				continue;
			}

			$plugins[]      = $slug;
			$names[ $slug ] = $name;
		}

		if ( ! $plugins ) {
			return;
		}

		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		require_once( dirname( __FILE__ ) . '/upgrader-skin.php' );

		$skin     = new ITSEC_Upgrader_Skin( array( 'add_to_response' => true ) );
		$upgrader = new Plugin_Upgrader( $skin );
		$results  = $upgrader->bulk_upgrade( $plugins, array( 'clear_update_cache' => false ) );

		if ( is_wp_error( $results ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-plugin-update-failed', sprintf( __( 'Unable to update plugins successfully. %s', 'it-l10n-ithemes-security-pro' ), $results->get_error_message() ) ) );

			return;
		}

		if ( ! is_array( $results ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-plugin-update-failed-unknown', __( 'An unknown issue prevented plugins from updating successfully. Please try again at a later time.', 'it-l10n-ithemes-security-pro' ) ) );

			return;
		}

		foreach ( $results as $file => $result ) {
			if ( is_wp_error( $result ) ) {
				ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-plugin-update-failed', sprintf( __( 'Unable to update the %1$s plugin. %2$s', 'it-l10n-ithemes-security-pro' ), $names[ $file ], $result->get_error_message() ) ) );
			} else if ( false === $result ) {
				ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-plugin-update-failed-unknown', sprintf( __( 'An unknown issue prevented the %s plugin from updating successfully. Please try again at a later time.', 'it-l10n-ithemes-security-pro' ), $names[ $file ] ) ) );
			} else if ( null === $result ) {
				continue; // Null result means that the error was handled by the skin.
			} else {
				ITSEC_Response::add_message( sprintf( __( 'The %s plugin updated successfully.', 'it-l10n-ithemes-security-pro' ), $names[ $file ] ) );
			}
		}

		wp_clean_plugins_cache();
		wp_update_plugins();
	}

	private function update_theme( $slug ) {
		$theme_obj = wp_get_theme( $slug );

		if ( ! $theme_obj->exists() ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-unable-to-update-theme-missing', sprintf( __( 'Unable to update the requested theme: <code>%s</code>. The theme could not be found.', 'it-l10n-ithemes-security-pro' ), $slug ) ) );
			return;
		}


		$name = $theme_obj->name;

		if ( ! current_user_can( 'update_themes' ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-insufficient-permissions-update_themes', sprintf( __( 'Unable to update the %s theme. Your user does not have the necessary permissions to update themes. Please log in as a user with permission to update themes or contact someone that does, and try again.', 'it-l10n-ithemes-security-pro' ), $name ) ) );
			return;
		}


		$update_themes = get_site_transient( 'update_themes' );

		if ( ! is_object( $update_themes ) || ! isset( $update_themes->response ) || ! isset( $update_themes->response[$slug] ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-invalid-update_themes-data', sprintf( __( 'Unable to update the %s theme. The update data for the theme is missing or corrupted.', 'it-l10n-ithemes-security-pro' ), $name ) ) );
			return;
		}


		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		require_once( dirname( __FILE__ ) . '/upgrader-skin.php' );

		$upgrader = new Theme_Upgrader( new ITSEC_Upgrader_Skin() );
		$result = $upgrader->upgrade( $slug, array( 'clear_update_cache' => false ) );

		if ( is_wp_error( $result ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-theme-update-failed', sprintf( __( 'Unable to update the %1$s theme. %2$s', 'it-l10n-ithemes-security-pro' ), $name, $result->get_error_message() ) ) );
		} else if ( false === $result ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-theme-update-failed-unknown', sprintf( __( 'An unknown issue prevented the %s theme from updating successfully. Please try again at a later time.', 'it-l10n-ithemes-security-pro' ), $name ) ) );
		} else {
			wp_clean_themes_cache();
			wp_update_themes();
			ITSEC_Response::add_message( sprintf( __( 'The %s theme updated successfully.', 'it-l10n-ithemes-security-pro' ), $name ) );
		}
	}

	private function get_supported_php_versions() {
		$version_support = $this->get_php_version_support();
		$versions = array();

		foreach ( $version_support['branches'] as $branch => $details ) {
			if ( strtotime( $details['eol'] ) < time() ) {
				continue;
			}

			$versions[$branch] = $details['latest'];
		}

		return $versions;
	}

	private function update_php() {
		$supported_versions = $this->get_supported_php_versions();

		if ( empty( $supported_versions ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-unable-to-update-php', sprintf( wp_kses( __( 'Unable to update PHP. Updating PHP requires more privileges than iThemes Security has. To update PHP, contact your host and ask them to help you update to the latest supported PHP version. <a href="%s">This page</a> lists the support status of different PHP versions. Ensure that the version that they update to still receives security support. Ideally, you want a version that has both active support and security support for at least the next year. If your host is unable to update your PHP version, you should find a host that is able to provide the latest version of PHP.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), 'https://secure.php.net/supported-versions.php' ) ) );
		} else {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-unable-to-update-php', sprintf( wp_kses( __( 'Unable to update PHP. Updating PHP requires more privileges than iThemes Security has. To update PHP, contact your host and ask them to help you update to the latest supported PHP version (currently: %1$s). <a href="%2$s">This page</a> lists the support status of different PHP versions. Ensure that the version that they update to still receives security support. Ideally, you want a version that has both active support and security support for at least the next year. If your host is unable to update your PHP version, you should find a host that is able to provide the latest version of PHP.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), implode( ', ', $supported_versions ), 'https://secure.php.net/supported-versions.php' ) ) );
		}
	}

	private function update_wordpress() {
		if ( ! current_user_can( 'update_core' ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-insufficient-permissions-update_core', __( 'Unable to update WordPress. Your user does not have the necessary permissions to update WordPress. Please log in as a user with permission to update WordPress or contact someone that does, and try again.', 'it-l10n-ithemes-security-pro' ) ) );
			return;
		}


		$update_core = get_site_transient( 'update_core' );

		if ( ! is_array( $update_core ) || ! isset( $update_core['updates'] ) || ! is_array( $update_core['updates'] ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-invalid-update_core-data', __( 'Unable to update WordPress. The data WordPress uses to update itself is missing or corrupted.', 'it-l10n-ithemes-security-pro' ) ) );
			return;
		}


		$updates = $update_core['updates'];
		$locale = get_locale();
		$best_fit = false;

		foreach ( $updates as $index => $update ) {
			$normal_release = (bool) preg_match( '/^\d+\.\d+(?\.\d+)?$/', $update->version );

			if ( ! $normal_release ) {
				continue;
			}

			if ( false === $best_fit ) {
				$best_fit = $index;
				continue;
			}

			$locale_match = ( $locale === $update->locale );

			if ( version_compare( $update->version, $updates[$best_fit]->version, '>' ) ) {
				$best_fit = $index;
				continue;
			} else if ( version_compare( $update->version, $updates[$best_fit]->version, '<' ) ) {
				continue;
			}

			if ( $locale_match ) {
				$best_fit = $index;
			} else if ( 'en_US' === $update->locale && $locale !== $updates[$best_fit]->locale ) {
				$best_fit = $index;
			}
		}

		if ( false === $best_fit ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-cannot-find-wordpress-candidate', __( 'Unable to update WordPress. The data WordPress uses to update itself did not provide a suitable version to install.', 'it-l10n-ithemes-security-pro' ) ) );
			return;
		}


		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

		$upgrader = new Core_Upgrader();
		$result = $upgrader->upgrade( $updates[$best_fit] );

		if ( is_wp_error( $result ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-wordpress-update-failed', sprintf( __( 'Unable to update WordPress. %s', 'it-l10n-ithemes-security-pro' ), $result->get_error_message() ) ) );
		} else if ( false === $result ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-wordpress-update-failed-unknown', __( 'An unknown issue prevented WordPress from updating successfully. Please try again at a later time.', 'it-l10n-ithemes-security-pro' ) ) );
		} else {
			ITSEC_Response::add_message( __( 'WordPress updated successfully. You should log out and log back in to complete the update.', 'it-l10n-ithemes-security-pro' ) );
		}
	}

	public function get_criteria() {
		$criteria = array(
			'wordpress' => $this->get_wordpress_report( $this->get_wordpress_version() ),
			'php'       => $this->get_php_report( phpversion() ),
		);

		$criteria = array_merge( $criteria, $this->get_plugins_criteria() );
		$criteria = array_merge( $criteria, $this->get_themes_criteria() );

		return $criteria;
	}

	private function get_plugins_criteria() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugins = get_plugins();
		$update_plugins = get_site_transient( 'update_plugins' );
		$criteria = array();

		foreach ( $plugins as $plugin => $data ) {
			$report = array(
				'name'    => sprintf( esc_html__( 'Plugin: %s', 'it-l10n-ithemes-security-pro' ), $data['Name'] ),
				'percent' => 100,
				'details' => '',
				'fixable' => true,
				'issue'   => false,
			);

			$id = "plugin:$plugin";

			if ( ! empty( $update_plugins ) && isset( $update_plugins->response ) && isset( $update_plugins->response[$plugin] ) ) {
				$report['percent'] = 0;
				$report['cap']     = 75;
				$report['issue']   = true;

				$api = $update_plugins->response[ $plugin ];

				if ( function_exists( 'is_php_version_compatible' ) ) {
					$compatible_php = is_php_version_compatible( isset( $api->requires_php ) ? $api->requires_php : null );
				} else {
					$compatible_php = true;
				}

				if ( $compatible_php ) {
					$report['details'] = sprintf( esc_html__( 'An update for plugin %1$s from version %2$s to %3$s is available. You should create a backup of the site and update the plugin as soon as possible.', 'it-l10n-ithemes-security-pro' ), $data['Name'], $data['Version'], $update_plugins->response[ $plugin ]->new_version );
				} else {
					$report['fixable'] = false;
					$report['details'] = sprintf(
						__( 'An update for plugin %1$s from version %2$s to %3$s is available, but it doesn&#8217;t work with your version of PHP. <a href="%4$s">Learn more about updating PHP</a>.', 'it-l10n-ithemes-security-pro' ),
						$data['Name'],
						$data['Version'],
						$update_plugins->response[ $plugin ]->new_version,
						esc_url( wp_get_update_php_url() )
					);
					$report['details'] .= '<em>' . wp_get_update_php_annotation() . '</em>';
				}
			} else {
				$report['details'] = sprintf( esc_html__( 'Plugin %1$s is running version %2$s, the latest version available to the site.', 'it-l10n-ithemes-security-pro' ), $data['Name'], $data['Version'] );
			}

			$criteria[$id] = $report;
		}

		return $criteria;
	}

	private function get_themes_criteria() {
		$themes = wp_get_themes();
		$update_themes = get_site_transient( 'update_themes' );
		$criteria = array();


		foreach ( $themes as $theme => $data ) {
			$report = array(
				'name'    => sprintf( esc_html__( 'Theme: %s', 'it-l10n-ithemes-security-pro' ), $data->name ),
				'percent' => 100,
				'details' => '',
				'fixable' => true,
				'issue'  => false,
			);

			$id = "theme:$theme";

			if ( ! empty( $update_themes ) && isset( $update_themes->response ) && isset( $update_themes->response[$theme] ) ) {
				$report['percent'] = 0;
				$report['cap'] = 75;
				$report['issue'] = true;

				$report['details'] = sprintf( esc_html__( 'An update for theme %1$s from version %2$s to %3$s is available. You should create a backup of the site and update the theme as soon as possible.', 'it-l10n-ithemes-security-pro' ), $data->name, $data->version, $update_themes->response[$theme]['new_version'] );
			} else {
				$report['details'] = sprintf( esc_html__( 'Theme %1$s is running version %2$s, the latest version available to the site.', 'it-l10n-ithemes-security-pro' ), $data->name, $data->version );
			}

			$criteria[$id] = $report;
		}

		return $criteria;
	}

	private function get_wordpress_report( $raw_version ) {
		$report = array(
			'name'    => esc_html__( 'WordPress Version', 'it-l10n-ithemes-security-pro' ),
			'percent' => 100,
			'details' => '',
			'fixable' => true,
			'issue'   => false,
		);

		if ( preg_match( '/^\d+\.\d+/', $raw_version, $match ) ) {
			$branch = $match[0];
		} else {
			$report['percent'] = 0;
			$report['issue'] = true;
			$report['details'] = new WP_Error( 'itsec-grade-report-software-invalid-wordpress-branch', sprintf( esc_html__( 'Unable to parse the WordPress version to find out which WordPress branch it is from. This version is treated as out-of-date. Found the following version: %s', 'it-l10n-ithemes-security-pro' ), $raw_version ) );

			return $report;
		}

		$version_support = $this->get_wordpress_version_support();

		if ( preg_match( '/^\d+\.\d+(?:\.\d+)?/', $raw_version, $match ) ) {
			$version = $match[0];
		} else {
			$report['percent'] = 0;
			$report['issue'] = true;
			$report['details'] = new WP_Error( 'itsec-grade-report-software-invalid-wordpress-version', sprintf( esc_html__( 'Unable to parse the WordPress version to determine if it is current. This version is treated as out-of-date. Found the following version: %s', 'it-l10n-ithemes-security-pro' ), $raw_version ) );

			return $report;
		}


		end( $version_support['branches'] );
		$newest_branch = key( $version_support['branches'] );

		if ( isset( $version_support['branches'][$branch] ) ) {
			$branch_details = $version_support['branches'][$branch];
		} else {
			$report['percent'] = 0;
			$report['cap'] = 0;
			$report['issue'] = true;

			$report['details'] = new WP_Error( 'itsec-grade-report-software-unknown-wordpress-branch', sprintf( esc_html__( 'No data is available for the reported WordPress version (%s). This could be due to running a development version of WordPress or some potentially-malicious modification of the WordPress files to pretend that the WordPress version is newer than it actually is. This version is treated as out-of-date since both situations are more risky than running the latest release.', 'it-l10n-ithemes-security-pro' ), $raw_version ) );

			return $report;
		}

		$need_upgrade = false;
		$age = 0;
		$human_time_age = 0;

		if ( version_compare( $version, $version_support['branches'][$branch]['latest'], '<' ) ) {
			$need_upgrade = true;
		}

		if ( isset( $version_support['versions'][$version] ) ) {
			$age = time() - $version_support['versions'][$version];
			$human_time_age = human_time_diff( ITSEC_Core::get_current_time(), $version_support['versions'][$version] );
		}

		if ( $branch_details['dangerous'] ) {
			$report['percent'] = 0;
			$report['cap'] = '0';
			$report['issue'] = true;

			if ( empty( $human_time_age ) ) {
				$report['details'] = esc_html__( 'The site is running a very old version of WordPress. This version is highly dangerous as it is old enough to have known security vulnerabilities. You must create a full backup of the site immediately and upgrade WordPress to the latest version as quickly as possible. Even if you need to stop using a plugin or a theme that is not compatible with the latest version of WordPress, making that change will be easier than cleaning up after a hack.', 'it-l10n-ithemes-security-pro' );
			} else {
				$report['details'] = sprintf( esc_html__( 'The site is running a very old version of WordPress that was released %s ago. This version is highly dangerous as it is old enough to have known security vulnerabilities. You must create a full backup of the site immediately and upgrade WordPress to the latest version as quickly as possible. Even if you need to stop using a plugin or a theme that is not compatible with the latest version of WordPress, making that change will be easier than cleaning up after a hack.', 'it-l10n-ithemes-security-pro' ), $human_time_age );
			}
		} else if ( $branch_details['old_branch'] ) {
			if ( $need_upgrade ) {
				$report['percent'] = 0;
				$report['cap'] = '0';
				$report['issue'] = true;

				if ( empty( $human_time_age ) ) {
					$report['details'] = esc_html__( 'The site is running an old version of WordPress and is not receiving critical security updates. You must create a full backup of the site immediately and upgrade WordPress to the latest version as quickly as possible. Even if you need to stop using a plugin or a theme that is not compatible with the latest version of WordPress, making that change will be easier than cleaning up after a hack.', 'it-l10n-ithemes-security-pro' );
				} else {
					$report['details'] = sprintf( esc_html__( 'The site is running an old version of WordPress that was released %s ago and is not receiving critical security updates. You must create a full backup of the site immediately and upgrade WordPress to the latest version as quickly as possible. Even if you need to stop using a plugin or a theme that is not compatible with the latest version of WordPress, making that change will be easier than cleaning up after a hack.', 'it-l10n-ithemes-security-pro' ), $human_time_age );
				}
			} else {
				$report['percent'] = 60;
				$report['issue'] = true;

				$report['details'] = sprintf( esc_html__( 'The site is running an old version of WordPress that will stop receiving critical security updates in the future. You should create a full backup of the site immediately and upgrade WordPress to the latest version as quickly as possible. Even if you need to stop using a plugin or a theme that is not compatible with the latest version of WordPress, making that change will be easier than cleaning up after a hack.', 'it-l10n-ithemes-security-pro' ), $human_time_age );
			}
		} else if ( $branch_details['eol'] ) {
			if ( $need_upgrade ) {
				$report['percent'] = 0;
				$report['cap'] = '0';
				$report['issue'] = true;

				$report['details'] = esc_html__( 'The site is running the previous version of WordPress and is not receiving critical security updates. You must update to the lastest security bug fix release immediately and make plans to update to the current version as quickly as possible. Even if you need to stop using a plugin or a theme that is not compatible with the latest version of WordPress, making that change will be easier than cleaning up after a hack.', 'it-l10n-ithemes-security-pro' );
			} else {
				$report['percent'] = 80;
				$report['issue'] = true;

				$report['details'] = esc_html__( 'The site is running the previous version of WordPress. You should make plans to update to the current version as quickly as possible. Even if you need to stop using a plugin or a theme that is not compatible with the latest version of WordPress, making that change will be easier than cleaning up after a hack once the version becomes stale.', 'it-l10n-ithemes-security-pro' );
			}
		} else if ( $need_upgrade ) {
			$report['percent'] = 0;
			$report['issue'] = true;
			$report['details'] = esc_html__( 'The site is running WordPress without the latest critical security updates. You must create a full backup of the site immediately and upgrade WordPress to the latest version as quickly as possible.', 'it-l10n-ithemes-security-pro' );
		} else {
			$report['details'] = esc_html__( 'The site runs the latest WordPress version.', 'it-l10n-ithemes-security-pro' );
		}


		return $report;
	}

	private function get_php_report( $raw_version ) {
		$report = array(
			'name'    => esc_html__( 'PHP Version', 'it-l10n-ithemes-security-pro' ),
			'percent' => 100,
			'details' => '',
			'fixable' => false,
			'issue'   => false,
		);

		$branch = substr( $raw_version, 0, strpos( $raw_version, '.', 3 ) );
		$version_support = $this->get_php_version_support();

		if ( preg_match( '/^\d+\.\d+\.\d+/', $raw_version, $match ) ) {
			$version = $match[0];
		} else {
			$report['percent'] = 0;
			$report['issue'] = true;
			$report['details'] = new WP_Error( 'itsec-grade-report-software-invalid-php-version', sprintf( esc_html__( 'Unable to parse the PHP version to determine if it is current. This version is treated as out-of-date. Found the following version: %s', 'it-l10n-ithemes-security-pro' ), $raw_version ) );

			return $report;
		}


		$eol = false;
		$eol_time = '';
		$need_upgrade = false;
		$last_update_time = '';

		end( $version_support['branches'] );
		$newest_branch = key( $version_support['branches'] );

		if ( isset( $version_support['branches'][$branch] ) ) {
			if ( time() > strtotime( $version_support['branches'][$branch]['eol'] ) ) {
				$eol = true;
				$eol_time = $version_support['branches'][$branch]['eol'];
			} else if ( version_compare( $version, $version_support['branches'][$branch]['latest'], '<' ) ) {
				$need_upgrade = true;
			}
		}

		if ( isset( $version_support['versions'][$version] ) ) {
			$last_update_time = $version_support['versions'][$version];
		}

		if ( $eol ) {
			$report['percent'] = 0;
			$report['cap'] = '85';
			$report['issue'] = true;

			$human_time_age = human_time_diff( ITSEC_Core::get_current_time(), strtotime( $eol_time ) );
			$report['details'] = sprintf( esc_html__( 'Support for PHP %1$s ended %2$s ago. Your site\'s security and performance would be greatly improved by upgrading to the latest version of PHP %3$s. It is highly recommended that you contact your host and request that your server\'s PHP version is upgraded.', 'it-l10n-ithemes-security-pro' ), $branch, $human_time_age, $newest_branch );
		} else if ( $need_upgrade ) {
			if ( empty( $last_update_time ) && ! empty( $eol_time ) ) {
				$last_update_time = $eol_time;
			}

			if ( empty( $last_update_time ) ) {
				$report['details'] = sprintf( esc_html__( 'PHP version %1$s is out of date. To improve site security, is recommended that you contact your host and request that your server\'s PHP version is upgraded to the current release version of PHP.', 'it-l10n-ithemes-security-pro' ), $version );
			} else {
				$age = ITSEC_Core::get_current_time() - strtotime( $last_update_time );
				$human_time_age = human_time_diff( ITSEC_Core::get_current_time(), strtotime( $last_update_time ) );

				if ( $age > 3 * YEAR_IN_SECONDS ) {
					$report['percent'] = 50;
				} else if ( $age > 2 * YEAR_IN_SECONDS ) {
					$report['percent'] = 60;
				} else if ( $age > YEAR_IN_SECONDS ) {
					$report['percent'] = 70;
				} else if ( $age > MONTH_IN_SECONDS ) {
					$report['percent'] = 80;
				} else {
					$report['percent'] = 85;
				}

				if ( $branch !== $newest_branch && isset( $latest_branch_version ) ) {
					$report['details'] = sprintf( esc_html__( 'PHP version %1$s was released %2$s ago and is now out of date. To improve site security, is recommended that you contact your host and request that your server\'s PHP version is upgraded to %3$s or the current release version of %4$s if possible. Note: It is possible that your host installs an older version of PHP that includes newer security patches.', 'it-l10n-ithemes-security-pro' ), $version, $human_time_age, $latest_branch_version, $newest_branch );
				} else if ( isset( $latest_branch_version ) ) {
					$report['details'] = sprintf( esc_html__( 'PHP version %1$s was released %2$s ago and is now out of date. To improve site security, is recommended that you contact your host and request that your server\'s PHP version is upgraded to %3$s. Note: It is possible that your host installs an older version of PHP that includes newer security patches.', 'it-l10n-ithemes-security-pro' ), $version, $human_time_age, $latest_branch_version );
				} else {
					$report['details'] = sprintf( esc_html__( 'PHP version %1$s was released %2$s ago and is now out of date. To improve site security, is recommended that you contact your host and request that your server\'s PHP version is upgraded to the latest version. Note: It is possible that your host installs an older version of PHP that includes newer security patches.', 'it-l10n-ithemes-security-pro' ), $version, $human_time_age );
				}
			}
		} else {
			$report['details'] = esc_html__( 'The site runs an up-to-date, supported version of PHP.', 'it-l10n-ithemes-security-pro' );
		}


		return $report;
	}

	private function get_php_version_support() {
		if ( isset( $this->php_version_support ) ) {
			return $this->php_version_support;
		}

		$response = wp_remote_get( 'https://s3.amazonaws.com/downloads.ithemes.com/public/php-version-support.json' );

		if ( ! is_wp_error( $response ) ) {
			$json = wp_remote_retrieve_body( $response );
			$data = @json_decode( $json, true );
		}

		if ( ! isset( $data ) || ! is_array( $data ) || ! isset( $data['branches'] ) || ! isset( $data['versions'] ) ) {
			$json = file_get_contents( dirname( __FILE__ ) . '/data/php-version-support.json' );
			$data = @json_decode( $json, true );
		}

		if ( ! isset( $data ) || ! is_array( $data ) || ! isset( $data['branches'] ) || ! isset( $data['versions'] ) ) {
			return array(
				'branches' => array(
					'5.2' => array(
						'eol'    => '2011-01-06',
						'latest' => '5.2.17'
					),
					'5.3' => array(
						'eol'    => '2014-08-14',
						'latest' => '5.3.29'
					),
					'5.4' => array(
						'eol'    => '2015-09-03',
						'latest' => '5.4.45'
					),
					'5.5' => array(
						'eol'    => '2016-07-21',
						'latest' => '5.5.38'
					),
					'5.6' => array(
						'eol'    => '2018-12-31',
						'latest' => '5.6.39'
					),
					'7.0' => array(
						'eol'    => '2018-12-03',
						'latest' => '7.0.33'
					),
					'7.1' => array(
						'eol'    => '2019-12-01',
						'latest' => '7.1.24'
					),
					'7.2' => array(
						'eol'    => '2020-11-30',
						'latest' => '7.2.13'
					),
					'7.3' => array(
						'eol'    => '2021-12-06',
						'latest' => '7.3.0',
					),
				),
				'versions' => array(),
			);
		}

		$this->php_version_support = $data;

		return $data;
	}

	private function get_wordpress_version_support() {
		if ( isset( $this->wordpress_version_support ) ) {
			return $this->wordpress_version_support;
		}

		$response = wp_remote_get( 'https://s3.amazonaws.com/downloads.ithemes.com/public/wordpress-version-support.json' );

		if ( ! is_wp_error( $response ) ) {
			$json = wp_remote_retrieve_body( $response );
			$data = @json_decode( $json, true );
		}

		if ( ! isset( $data ) || ! is_array( $data ) || ! isset( $data['branches'] ) || ! isset( $data['versions'] ) ) {
			$json = file_get_contents( dirname( __FILE__ ) . '/data/wordpress-version-support.json' );
			$data = @json_decode( $json, true );
		}

		if ( ! isset( $data ) || ! is_array( $data ) || ! isset( $data['branches'] ) || ! isset( $data['versions'] ) ) {
			return array(
				'branches' => array(
					'1.5' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1124508646,
						'latest' => '1.5.2'
					),
					'2.0' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1186292608,
						'latest' => '2.0.11'
					),
					'2.1' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1175624112,
						'latest' => '2.1.3'
					),
					'2.2' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1189196519,
						'latest' => '2.2.3'
					),
					'2.3' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1202186673,
						'latest' => '2.3.3'
					),
					'2.5' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1209138128,
						'latest' => '2.5.1'
					),
					'2.6' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1227632172,
						'latest' => '2.6.5'
					),
					'2.7' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1234294760,
						'latest' => '2.7.1'
					),
					'2.8' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1258049170,
						'latest' => '2.8.6'
					),
					'2.9' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1266255539,
						'latest' => '2.9.2'
					),
					'3.0' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1303843197,
						'latest' => '3.0.6'
					),
					'3.1' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1309372729,
						'latest' => '3.1.4'
					),
					'3.2' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1310495472,
						'latest' => '3.2.1'
					),
					'3.3' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1340825609,
						'latest' => '3.3.3'
					),
					'3.4' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1346960566,
						'latest' => '3.4.2'
					),
					'3.5' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1371843156,
						'latest' => '3.5.2'
					),
					'3.6' => array(
						'dangerous' => true,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1378928120,
						'latest' => '3.6.1'
					),
					'3.7' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814586,
						'latest' => '3.7.27'
					),
					'3.8' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814575,
						'latest' => '3.8.27'
					),
					'3.9' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814564,
						'latest' => '3.9.25'
					),
					'4.0' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814549,
						'latest' => '4.0.24'
					),
					'4.1' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814535,
						'latest' => '4.1.24'
					),
					'4.2' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814517,
						'latest' => '4.2.21'
					),
					'4.3' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814460,
						'latest' => '4.3.17'
					),
					'4.4' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814439,
						'latest' => '4.4.16'
					),
					'4.5' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814420,
						'latest' => '4.5.15'
					),
					'4.6' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814401,
						'latest' => '4.6.12'
					),
					'4.7' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814382,
						'latest' => '4.7.11'
					),
					'4.8' => array(
						'dangerous' => false,
						'old_branch' => true,
						'eol' => true,
						'last_release' => 1530814364,
						'latest' => '4.8.7'
					),
					'4.9' => array(
						'dangerous' => false,
						'old_branch' => false,
						'eol' => false,
						'last_release' => 1533245593,
						'latest' => '4.9.6'
					),
					'5.0' => array(
						'dangerous' => false,
						'old_branch' => false,
						'eol' => false,
						'last_release' => 1544123596,
						'latest' => '5.0.0'
					)
				),
				'versions' => array(),
			);
		}

		return $data;
	}

	private function get_wordpress_version() {
		$version_file_path = ABSPATH . WPINC . '/version.php';

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
}
