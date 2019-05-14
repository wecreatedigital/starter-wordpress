<?php

class ITSEC_Grading_System_Section_Security_Settings extends ITSEC_Grading_System_Section {
	public function get_id() {
		return 'security_settings';
	}

	public function get_name() {
		return esc_html__( 'Security Settings', 'it-l10n-ithemes-security-pro' );
	}

	public function get_description() {
		return esc_html__( 'iThemes Security recommended settings', 'it-l10n-ithemes-security-pro' );
	}

	public function get_weights() {
		$weights = array();

		$recommendations = $this->get_recommendations();

		foreach ( $recommendations as $id => $recommendation ) {
			if ( isset( $recommendation['weight'] ) ) {
				$weights[$id] = $recommendation['weight'];
			} else {
				$weights[$id] = 100;
			}
		}

		return $weights;
	}

	public function resolve_issue( $id ) {
		$recommendations = $this->get_recommendations();

		if ( ! isset( $recommendations[$id] ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-report-security-settings-invalid-issue-id', sprintf( __( 'Unable to find the requested fix: %s. This may be due to a temporary issue or a bug in iThemes Security. Please ensure that iThemes Security is updated to the latest version and try again.', 'it-l10n-ithemes-security-pro' ), $id ) ) );
		} else if ( 'module' === $recommendations[$id]['type'] ) {
			ITSEC_Modules::activate( $recommendations[$id]['module'] );

			ITSEC_Response::add_message( sprintf( __( 'Activated the %s module.', 'it-l10n-ithemes-security-pro' ), $recommendations[$id]['name'] ) );
		} else if ( 'setting' === $recommendations[$id]['type'] ) {
			if ( is_string( $recommendations[$id]['setting'] ) ) {
				ITSEC_Modules::set_setting( $recommendations[$id]['module'], $recommendations[$id]['setting'], $recommendations[$id]['recommendation'] );
			} else {
				$settings = ITSEC_Modules::get_settings( $recommendations[$id]['module'] );
				$settings = $this->set_array_value_at_index( $settings, $recommendations[$id]['setting'], $recommendations[$id]['recommendation'] );

				ITSEC_Modules::set_settings( $recommendations[$id]['module'], $settings );
			}

			ITSEC_Response::add_message( sprintf( __( 'Fixed the %s setting.', 'it-l10n-ithemes-security-pro' ), $recommendations[$id]['name'] ) );
		} else {
			ITSEC_Response::add_error( new WP_error( 'itsec-grading-report-invalid-security-settings-type', sprintf( __( 'Unable to find requested fix: %s. This may be due to a temporary issue or a bug in iThemes Security. Please ensure that iThemes Security is updated to the latest version and try again.', 'it-l10n-ithemes-security-pro' ), $id ) ) );
		}
	}

	public function get_criteria() {
		$criteria = array();

		$recommendations = $this->get_recommendations();

		foreach ( $recommendations as $id => $recommendation ) {
			if ( isset( $recommendation['score-callback'] ) ) {
				$score = call_user_func( $recommendation['score-callback'] );
			} else if ( 'module' === $recommendation['type'] ) {
				$score = $recommendation['scores'][ITSEC_Modules::is_active( $recommendation['module'] )];
			} else {
				if ( is_string( $recommendation['setting'] ) ) {
					$setting = ITSEC_Modules::get_setting( $recommendation['module'], $recommendation['setting'] );
				} else {
					$settings = ITSEC_Modules::get_settings( $recommendation['module'] );
					$setting = $this->get_array_value_at_index( $settings, $recommendation['setting'] );
				}

				$score = $recommendation['scores'][$setting];
			}

			$report = array(
				'name'    => $recommendation['name'],
				'percent' => $score['percent'],
				'details' => $score['description'],
				'fixable' => true,
				'issue'   => ( $score['percent'] < 100 ),
			);

			if ( ! empty( $score['cap'] ) ) {
				$report['cap'] = $score['cap'];
			}

			$criteria[$id] = $report;
		}

		return $criteria;
	}

	private function get_array_value_at_index( $array, $index ) {
		if ( is_string( $index ) ) {
			$index = array( $index );
		}

		if ( 1 === count( $index ) ) {
			if ( isset( $array[$index[0]] ) ) {
				return $array[$index[0]];
			} else {
				return null;
			}
		}

		$current_index = array_shift( $index );

		if ( isset( $array[$current_index] ) && is_array( $array[$current_index] ) ) {
			return $this->get_array_value_at_index( $array[$current_index], $index );
		}

		return null;
	}

	private function set_array_value_at_index( $array, $index, $value ) {
		if ( is_string( $index ) ) {
			$index = array( $index );
		}

		if ( 1 === count( $index ) ) {
			$array[$index[0]] = $value;
			return $array;
		}

		$current_index = array_shift( $index );

		if ( ! is_array( $array[$current_index] ) ) {
			$array[$current_index] = array();
		}

		$child_array = $this->set_array_value_at_index( $array[$current_index], $index, $value );

		$array[$current_index] = array_merge( $array[$current_index], $child_array );

		return $array;
	}

	private function get_recommendations() {
		$logs_page_url = network_admin_url( 'admin.php?page=itsec-logs' );


		return array(
			'ban-users' => array(
				'type'   => 'module',
				'name'   => __( 'Banned Users', 'it-l10n-ithemes-security-pro' ),
				'module' => 'ban-users',
				'weight' => 100,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. This module offers a recommended setting.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Banned Users. This is highly recommended as it offers a recommended setting.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'ban-users-ban-lists' => array(
				'type'           => 'setting',
				'name'           => __( 'Banned Users: Ban Lists', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'ban-users',
				'setting'        => 'enable_ban_lists',
				'weight'         => 100,
				'recommendation' => true,
				'scores'  => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. This allows other features to block attackers by IP.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable the Ban Lists feature of Banned Users. This is highly recommended as it allows other features to block attackers by IP.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'backup' => array(
				'type'           => 'module',
				'name'           => __( 'Database Backups', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'backup',
				'weight'         => 100,
				'score-callback' => array( $this, 'get_backup_score' ),
			),
			'brute-force' => array(
				'type'   => 'module',
				'name'   => __( 'Local Brute Force Protection', 'it-l10n-ithemes-security-pro' ),
				'module' => 'brute-force',
				'weight' => 100,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. This helps protect against attackers that use a large number of attacks to compromise site security.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Local Brute Force Protection. This is highly recommended as it helps protect against attackers that use a large number of attacks to compromise site security.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'magic-links' => array(
				'type'   => 'module',
				'name'   => __( 'Magic Links', 'it-l10n-ithemes-security-pro' ),
				'module' => 'magic-links',
				'weight' => 100,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. This allows users to log into their account even when their username is under a brute force attack.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Magic Links. This is highly recommended as it allows users to log into their account even when their username is under a brute force attack.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'malware-scheduling' => array(
				'type'   => 'module',
				'name'   => __( 'Malware Scan: Scheduling', 'it-l10n-ithemes-security-pro' ),
				'module' => 'malware-scheduling',
				'weight' => 100,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. Automated scanning detects issues on the site.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Malware Scan Scheduling. This is highly recommended as it will automatically scan your site for issues.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'malware-scheduling-notifications' => array(
				'type'           => 'setting',
				'name'           => __( 'Malware Scan Scheduling Notifications', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'notification-center',
				'setting'        => array( 'notifications', 'malware-scheduling', 'enabled' ),
				'weight'         => 100,
				'recommendation' => true,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. Notifications are sent when issues are found.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable notifications for Malware Scan Scheduling. This is highly recommended as detected issues could go unnoticed by users for days or weeks without notifications.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'network-brute-force' => array(
				'type'   => 'module',
				'name'   => __( 'Network Brute Force Protection', 'it-l10n-ithemes-security-pro' ),
				'module' => 'network-brute-force',
				'weight' => 100,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. This helps protect against attackers that use a large number of attacks from multiple locations to compromise site security.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Database Backups. This is highly recommended as it helps protect against attackers that use a large number of attacks from multiple locations to compromise site security.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'strong-passwords' => array(
				'type'           => 'setting',
				'name'           => __( 'Strong Password Enforcement', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'password-requirements',
				'setting'        => array( 'enabled_requirements', 'strength' ),
				'recommendation' => true,
				'weight'         => 500,
				'scores'         => array(
					true  => array(
						'percent'     => 100,
						'description' => __( 'Enabled as recommended. This ensures that users on your site are not compromising site security by choosing poor passwords.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Strong Password Enforcement. This is critically important as it ensures that users on your site are not compromising site security by choosing poor passwords.', 'it-l10n-ithemes-security-pro' ),
					),
					null => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Strong Password Enforcement. This is critically important as it ensures that users on your site are not compromising site security by choosing poor passwords.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'refuse-compromised-passwords' => array(
				'type'           => 'setting',
				'name'           => __( 'Refuse Compromised Passwords', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'password-requirements',
				'setting'        => array( 'enabled_requirements', 'hibp' ),
				'recommendation' => true,
				'weight'         => 500,
				'scores'         => array(
					true  => array(
						'percent'     => 100,
						'description' => __( 'Enabled as recommended. This ensures that users on your site are not compromising site security by choosing passwords that appear in data breaches.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Refusing Compromised Passwords. This is critically important as it ensures that users on your site are not compromising site security by choosing passwords that appear in data breaches.', 'it-l10n-ithemes-security-pro' ),
					),
					null => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Refusing Compromised Passwords. This is critically important as it ensures that users on your site are not compromising site security by choosing passwords that appear in data breaches.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'two-factor' => array(
				'type'   => 'module',
				'name'   => __( 'Two-Factor Authentication', 'it-l10n-ithemes-security-pro' ),
				'module' => 'two-factor',
				'weight' => 500,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. This allows users to greatly increase the security of their account.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'cap'         => '75',
						'description' => __( 'Fix to enable Two-Factor Authentication. This is critically important as it allows users to greatly increases the security of their account.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'two-factor-available-methods' => array(
				'type'           => 'setting',
				'name'           => __( 'Two-Factor Authentication: Available Methods', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'two-factor',
				'setting'        => 'available_methods',
				'weight'         => 500,
				'recommendation' => 'all',
				'score-callback' => array( $this, 'get_two_factor_available_methods_score' ),
			),
			'two-factor-protect-user-type' => array(
				'type'           => 'setting',
				'name'           => __( 'Two-Factor Authentication: User Type Protection', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'two-factor',
				'setting'        => 'protect_user_type',
				'weight'         => 500,
				'recommendation' => 'privileged_users',
				'score-callback' => array( $this, 'get_two_factor_protect_user_type_score' ),
			),
			'two-factor-exclude-type' => array(
				'type'           => 'setting',
				'name'           => __( 'Two-Factor Authentication: Disable Two-Factor for Certain Users', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'two-factor',
				'setting'        => 'exclude_type',
				'weight'         => 500,
				'recommendation' => 'disabled',
				'scores' => array(
					'disabled'  => array(
						'percent' => 100,
						'description' => __( 'Not disabled for any users as recommended.', 'it-l10n-ithemes-security-pro' ),
					),
					'custom' => array(
						'percent'     => 0,
						'description' => __( 'Fix to allow enforcing Two-Factor for all users. This is recommended as Two-Factor is one of the best ways to help protect your user\'s information.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'two-factor-protect-vulnerable-users' => array(
				'type'           => 'setting',
				'name'           => __( 'Two-Factor Authentication: Vulnerable User Protection', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'two-factor',
				'setting'        => 'protect_vulnerable_users',
				'weight'         => 500,
				'recommendation' => true,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. Users that have weak passwords or have been under recent attack are required to use two-factor authentication when logging in.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Vulnerable User Protection for Two-Factor Authentication. This is highly recommended as it requires users that have weak passwords or have been under recent attack to use two-factor authentication when logging in.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'two-factor-protect-vulnerable-site' => array(
				'type'           => 'setting',
				'name'           => __( 'Two-Factor Authentication: Vulnerable Site Protection', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'two-factor',
				'setting'        => 'protect_vulnerable_site',
				'weight'         => 500,
				'recommendation' => true,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. When the site is known to be vulnerable, all users will be required to use two-factor authentication when logging in.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Vulnerable Site Protection for Two-Factor Authentication. This is highly recommended as it requires all users to use two-factor authentication to log in when the site is known to be vulnerable.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'two-factor-application-password-type' => array(
				'type'           => 'setting',
				'name'           => __( 'Two-Factor Authentication: Application Passwords', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'two-factor',
				'setting'        => 'application_passwords_type',
				'weight'         => 200,
				'recommendation' => 'enabled',
				'scores'         => array(
					'enabled'  => array(
						'percent'     => 100,
						'description' => __( 'Enabled as recommended. Two-Factor users can configure Application Passwords when using API powered applications.', 'it-l10n-ithemes-security-pro' )
					),
					'disabled' => array(
						'percent'     => 100,
						'description' => __( 'Disable Application Passwords if API access is not required.', 'it-l10n-ithemes-security-pro' ),
					),
					'custom'   => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable Application Passwords for all users. If API access is available, disabling Application Passwords may dissuade API users from enabling Two-Factor.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'user-logging' => array(
				'type'   => 'module',
				'name'   => __( 'User Logging', 'it-l10n-ithemes-security-pro' ),
				'module' => 'user-logging',
				'weight' => 100,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => sprintf( __( 'Enabled as recommended. User actions for the selected roles can be found on the <a href="%s">Logs page</a>.', 'it-l10n-ithemes-security-pro' ), $logs_page_url ),
					),
					false => array(
						'percent'     => 0,
						'description' => sprintf( __( 'Fix to enable User Logging. This is recommended as it allows you to find past user actions for the selected roles in the <a href="%s">Logs page</a>.', 'it-l10n-ithemes-security-pro' ), $logs_page_url ),
					),
				),
			),
			'wordpress-tweaks' => array(
				'type'   => 'module',
				'name'   => __( 'WordPress Tweaks', 'it-l10n-ithemes-security-pro' ),
				'module' => 'wordpress-tweaks',
				'weight' => 200,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Enabled as recommended. This module offers some recommended settings.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable WordPress Tweaks. This module offers some recommended settings.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'wordpress-tweaks-file-editor' => array(
				'type'           => 'setting',
				'name'           => __( 'WordPress Tweaks: File Editor', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'wordpress-tweaks',
				'setting'        => 'file_editor',
				'weight'         => 100,
				'recommendation' => true,
				'scores' => array(
					true  => array(
						'percent' => 100,
						'description' => __( 'Disabled as recommended. The file editor offers an attacker that compromises a privileged user account a tool to completely compromise the site.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 90,
						'description' => __( 'Fix to disable the WordPress file editor. This is recommended as the file editor offers an attacker that compromises a privileged user account a tool to completely compromise the site.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'wordpress-tweaks-allow-xmlrpc-multiauth' => array(
				'type'           => 'setting',
				'name'           => __( 'WordPress Tweaks: XML-RPC Multiauth', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'wordpress-tweaks',
				'setting'        => 'allow_xmlrpc_multiauth',
				'weight'         => 500,
				'recommendation' => false,
				'scores' => array(
					false => array(
						'percent'     => 100,
						'description' => __( 'Blocked as recommended. This blocks a significant security vulnerability in the XML-RPC feature. Even with XML-RPC disabled, it is still recommended to use this setting.', 'it-l10n-ithemes-security-pro' ),
					),
					true => array(
						'percent'     => 0,
						'description' => __( 'Fix to block XML-RPC multiauth attempts. This is highly recommended as it blocks a significant security vulnerability in the XML-RPC feature. Even with XML-RPC disabled, it is still recommended to use this setting.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'wordpress-tweaks-rest-api' => array(
				'type'           => 'setting',
				'name'           => __( 'WordPress Tweaks: REST API', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'wordpress-tweaks',
				'setting'        => 'rest_api',
				'weight'         => 300,
				'recommendation' => 'restrict-access',
				'scores' => array(
					'restrict-access' => array(
						'percent'     => 100,
						'description' => __( 'Restricted Access as recommended. This requires authentication in order to read specific data about the site, its users, and its content from the REST API.', 'it-l10n-ithemes-security-pro' ),
					),
					'default-access'  => array(
						'percent'     => 65,
						'description' => __( 'Fix to restrict access. This is highly recommended as it requires authentication in order to read specific data about the site, its users, and its content from the REST API.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
			'global-write-files' => array(
				'type'           => 'setting',
				'name'           => __( 'Global: Write to Files', 'it-l10n-ithemes-security-pro' ),
				'module'         => 'global',
				'setting'        => 'write_files',
				'weight'         => 300,
				'recommendation' => true,
				'scores' => array(
					true  => array(
						'percent'     => 100,
						'description' => __( 'Enabled as recommended. Many iThemes Security features require writing to the wp-config.php and server config files in order to function. With this setting enabled, these features can work as designed.', 'it-l10n-ithemes-security-pro' ),
					),
					false => array(
						'percent'     => 0,
						'description' => __( 'Fix to enable. This is highly recommended as many iThemes Security features require writing to the wp-config.php and server config files in order to function. Enabling this setting, will allow these features to work as designed.', 'it-l10n-ithemes-security-pro' ),
					),
				),
			),
		);
	}

	private function get_role_id_from_display_name( $name ) {
		$roles = wp_roles()->roles;

		foreach ( $roles as $role => $data ) {
			if ( $name === $data['name'] ) {
				return $role;
			}
		}

		return false;
	}

	private function get_backup_score() {

		if ( $file = ITSEC_Lib::get_backup_plugin() ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			$name = "'{$file}'";

			if ( function_exists( 'get_plugin_data' ) ) {
				$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $file, false, true );

				if ( isset( $data['Name'] ) ) {
					$name = $data['Name'];
				}
			}

			return array(
				'percent'     => 100,
				'description' => sprintf( __( 'A 3rd-party Backup Plugin, %s, is being used. The backups can help recover the site in case an attack is successful.', 'it-l10n-ithemes-security-pro' ), $name ),
			);
		}

		if ( ITSEC_Modules::is_active( 'backup' ) ) {
			return array(
				'percent'     => 100,
				'description' => __( 'Enabled as recommended. The backups can help recover the site in case an attack is successful.', 'it-l10n-ithemes-security-pro' ),
			);
		}

		return array(
			'percent'     => 0,
			'description' => sprintf(
				__( 'Fix to enable Database Backups. This is highly recommended as backups can help recover the site in case an attack is successful. %1$sUsing a 3rd-party Backup Plugin%2$s?', 'it-l10n-ithemes-security-pro' ),
				'<a href="https://ithemeshelp.zendesk.com/hc/en-us/articles/360007626173" target="_blank">', '</a>'
			),
		);
	}

	private function get_two_factor_protect_user_type_score() {
		$protect_user_type = ITSEC_Modules::get_setting( 'two-factor', 'protect_user_type' );

		if ( 'privileged_users' === $protect_user_type ) {
			return array(
				'percent'     => 100,
				'description' => __( 'Privileged Users is selected as recommended. This ensures that all accounts that have privileges to change site settings, software, or content use two-factor authentication.', 'it-l10n-ithemes-security-pro' ),
			);
		} else if ( 'all_users' === $protect_user_type ) {
			return array(
				'percent'     => 100,
				'description' => __( 'All Users is selected. This is not recommended for sites that have large numbers of unprivileged users, but it does greatly increase the difficultly of breaking into user accounts.', 'it-l10n-ithemes-security-pro' ),
			);
		} else if ( 'disabled' === $protect_user_type ) {
			return array(
				'percent'     => 0,
				'cap'         => '75',
				'description' => __( 'Fix to change setting from Disabled to Privileged Users. The User Type Protection feature is highly recommended as it automatically enforces strong account security on accounts that have the most potential to harm the site if an attacker gained access to them.', 'it-l10n-ithemes-security-pro' ),
			);
		}


		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		$protect_user_type_roles = ITSEC_Modules::get_setting( 'two-factor', 'protect_user_type_roles' );
		$validator = ITSEC_Modules::get_validator( 'two-factor' );
		$available_protect_user_type_roles = $validator->get_protect_user_type_roles();
		$missing_roles = array();

		foreach ( $available_protect_user_type_roles as $role ) {
			if ( in_array( $role, $protect_user_type_roles ) ) {
				continue;
			}

			$role_id = $this->get_role_id_from_display_name( $role );
			$role_obj = get_role( $role_id );
			$canonical_role = ITSEC_Lib_Canonical_Roles::get_role_from_caps( $role_obj->capabilities );

			if ( ITSEC_Lib_Canonical_Roles::is_canonical_role_at_least( $canonical_role, 'contributor' ) ) {
				$missing_roles[] = $role;
			}
		}

		if ( empty( $missing_roles ) ) {
			return array(
				'percent'     => 100,
				'description' => __( 'The manually-selected roles protect all the privileged roles. The accounts of the selected roles are automatically protected by automatically requiring two-factor authentication when logging in.', 'it-l10n-ithemes-security-pro' ),
			);
		}

		return array(
			'percent'     => 0,
			'cap'         => '75',
			'description' => __( 'Fix to change setting from Select Roles Manually to Privileged Users. Not all of the privileged roles were manually-selected, thus opening up the possibility that some privileged accounts will not require two-factor authentication.', 'it-l10n-ithemes-security-pro' ),
		);
	}

	private function get_two_factor_available_methods_score() {
		$available_methods = ITSEC_Modules::get_setting( 'two-factor', 'available_methods' );

		if ( 'custom' === $available_methods ) {
			$custom_available_methods = ITSEC_Modules::get_setting( 'two-factor', 'custom_available_methods' );
			sort( $custom_available_methods );

			if ( 3 === count( $custom_available_methods ) ) {
				$available_methods = 'all';
			} else if ( 2 === count( $custom_available_methods ) ) {
				if ( array( 'Two_Factor_Backup_Codes', 'Two_Factor_Totp' ) === $custom_available_methods ) {
					$available_methods = 'not_email';
				} else if ( array( 'Two_Factor_Backup_Codes', 'Two_Factor_Email' ) === $custom_available_methods ) {
					return array(
						'percent'     => 80,
						'description' => __( 'Fix to change setting to All Methods as recommended. Currently the Mobile App method is disabled. The Mobile App is the strongest method as it rotates frequently and requires control of a device to generate.', 'it-l10n-ithemes-security-pro' ),
					);
				} else {
					return array(
						'percent'     => 85,
						'description' => __( 'Fix to change setting to All Methods as recommended. Currently the Backup Authentication Codes method is disabled. This method is important as it provides a way for users to regain access to their account in the event that their device is damaged/lost or they lose access to email.', 'it-l10n-ithemes-security-pro' ),
					);
				}
			} else if ( 'Two_Factor_Backup_Codes' === $custom_available_methods[0] ) {
				return array(
					'percent'     => 0,
					'cap'         => '85',
					'description' => __( 'Fix to change setting to All Methods as recommended. Currently only the Backup Authentication Codes method is available. This method is only useful as a fallback in the event that the Mobile App device is damaged/lost or email access is unavailable.', 'it-l10n-ithemes-security-pro' ),
				);
			} else if ( 'Two_Factor_Email' === $custom_available_methods[0] ) {
				return array(
					'percent'     => 60,
					'cap'         => '85',
					'description' => __( 'Fix to change setting to All Methods as recommended. Currently only the Email method is available. This is very limiting and could result in users not using two-factor due to lack of Mobile App support or frustration due to not having the Backup Authentication Codes fallback option.', 'it-l10n-ithemes-security-pro' ),
				);
			} else if ( 'Two_Factor_Totp' === $custom_available_methods[0] ) {
				return array(
					'percent'     => 60,
					'cap'         => '85',
					'description' => __( 'Fix to change setting to All Methods as recommended. Currently only the Mobile App method is available. This may seem like a very strong setting, but taking away the Email method disables other valuable Two-Factor Authentication features offered by iThemes Security and taking away the Backup Authentication Codes method makes logging in after loss or damage of the device very difficult.', 'it-l10n-ithemes-security-pro' ),
				);
			}
		}

		if ( 'all' === $available_methods ) {
			return array(
				'percent' => 100,
				'description' => __( 'All Methods selected as recommended. This allows users to select settings that work for them and provides support for other recommended Two-Factor Authentication features.', 'it-l10n-ithemes-security-pro' ),
			);
		} else if ( 'not_email' === $available_methods ) {
			return array(
				'percent'     => 70,
				'cap'         => '85',
				'description' => __( 'Fix to change setting to All Methods. With email two-factor disabled, iThemes Security cannot provide other recommended Two-Factor Authentication features.', 'it-l10n-ithemes-security-pro' ),a
			);
		} else {
			return array(
				'percent'     => 0,
				'cap'         => '75',
				'description' => __( 'Fix to change setting to All Methods as recommended. Currently all the methods are disabled, effectively disabling Two-Factor Authentication.', 'it-l10n-ithemes-security-pro' ),
			);
		}
	}
}
