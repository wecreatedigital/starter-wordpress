<?php

final class ITSEC_Security_Check_Scanner {
	private static $available_modules;
	private static $feedback;


	public static function get_supported_modules() {
		$available_modules = ITSEC_Modules::get_available_modules();

		$modules = array(
			'ban-users'           => __( 'Banned Users', 'better-wp-security' ),
			'backup'              => __( 'Database Backups', 'better-wp-security' ),
			'brute-force'         => __( 'Local Brute Force Protection', 'better-wp-security' ),
			'magic-links'         => __( 'Magic Links', 'better-wp-security' ),
			'malware-scheduling'  => __( 'Malware Scan Scheduling', 'better-wp-security' ),
			'network-brute-force' => __( 'Network Brute Force Protection', 'better-wp-security' ),
			'strong-passwords'    => __( 'Strong Passwords', 'better-wp-security' ),
			'two-factor'          => __( 'Two-Factor Authentication', 'better-wp-security' ),
			'user-logging'        => __( 'User Logging', 'better-wp-security' ),
			'wordpress-tweaks'    => __( 'WordPress Tweaks', 'better-wp-security' ),
		);

		foreach ( $modules as $module => $val ) {
			if ( ! in_array( $module, $available_modules ) ) {
				unset( $modules[$module] );
			}
		}

		return $modules;
	}

	public static function get_results() {
		self::run_scan();

		return self::$feedback->get_raw_data();
	}

	public static function run_scan() {
		require_once( dirname( __FILE__ ) . '/feedback.php' );

		self::$feedback = new ITSEC_Security_Check_Feedback();
		self::$available_modules = ITSEC_Modules::get_available_modules();

		do_action( 'itsec-security-check-before-default-checks', self::$feedback, self::$available_modules );

		self::enforce_activation( 'ban-users', __( 'Banned Users', 'better-wp-security' ) );
		self::enforce_setting( 'ban-users', 'enable_ban_lists', true, __( 'Enabled the Enable Ban Lists setting in Banned Users.', 'better-wp-security' ) );

		self::enforce_activation( 'backup', __( 'Database Backups', 'better-wp-security' ) );
		self::enforce_activation( 'brute-force', __( 'Local Brute Force Protection', 'better-wp-security' ) );
		self::enforce_activation( 'magic-links', __( 'Magic Links', 'better-wp-security' ) );
		self::enforce_activation( 'malware-scheduling', __( 'Malware Scan Scheduling', 'better-wp-security' ) );
		self::enforce_setting( 'malware-scheduling', 'email_notifications', true, __( 'Enabled the Email Notifications setting in Malware Scan Scheduling.', 'better-wp-security' ) );

		self::add_network_brute_force_signup();

		self::enforce_activation( 'strong-passwords', __( 'Strong Password Enforcement', 'better-wp-security' ) );
		self::enforce_activation( 'two-factor', __( 'Two-Factor Authentication', 'better-wp-security' ) );
		self::enforce_setting( 'two-factor', 'available_methods', 'all', esc_html__( 'Changed the Authentication Methods Available to Users setting in Two-Factor Authentication to "All Methods".', 'better-wp-security' ) );
		self::enforce_setting( 'two-factor', 'protect_user_type', 'privileged_users', esc_html__( 'Changed the User Type Protection setting in Two-Factor Authentication to "Privileged Users".', 'better-wp-security' ) );
		self::enforce_setting( 'two-factor', 'protect_vulnerable_users', true, esc_html__( 'Enabled the Vulnerable User Protection setting in Two-Factor Authentication.', 'better-wp-security' ) );
		self::enforce_setting( 'two-factor', 'protect_vulnerable_site', true, esc_html__( 'Enabled the Vulnerable Site Protection setting in Two-Factor Authentication.', 'better-wp-security' ) );

		self::enforce_activation( 'user-logging', __( 'User Logging', 'better-wp-security' ) );
		self::enforce_activation( 'wordpress-tweaks', __( 'WordPress Tweaks', 'better-wp-security' ) );
		self::enforce_setting( 'wordpress-tweaks', 'file_editor', true, __( 'Disabled the File Editor in WordPress Tweaks.', 'better-wp-security' ) );
		self::enforce_setting( 'wordpress-tweaks', 'allow_xmlrpc_multiauth', false, __( 'Changed the Multiple Authentication Attempts per XML-RPC Request setting in WordPress Tweaks to "Block".', 'better-wp-security' ) );
		self::enforce_setting( 'wordpress-tweaks', 'rest_api', 'restrict-access', __( 'Changed the REST API setting in WordPress Tweaks to "Restricted Access".', 'better-wp-security' ) );

		self::enforce_setting( 'global', 'write_files', true, __( 'Enabled the Write to Files setting in Global Settings.', 'better-wp-security' ) );

		do_action( 'itsec-security-check-after-default-checks', self::$feedback, self::$available_modules );
	}

	private static function add_network_brute_force_signup() {
		if ( ! in_array( 'network-brute-force', self::$available_modules ) ) {
			return;
		}


		$settings = ITSEC_Modules::get_settings( 'network-brute-force' );

		if ( ! empty( $settings['api_key'] ) && ! empty( $settings['api_secret'] ) ) {
			self::enforce_activation( 'network-brute-force', __( 'Network Brute Force Protection', 'better-wp-security' ) );
			return;
		}


		self::$feedback->add_section( 'network-brute-force-signup', array( 'interactive' => true, 'status' => 'call-to-action' ) );
		self::$feedback->add_text( __( 'With Network Brute Force Protection, your site is protected against attackers found by other sites running iThemes Security. If your site identifies a new attacker, it automatically notifies the network so that other sites are protected as well. To join this site to the network and enable the protection, click the button below.', 'better-wp-security' ) );
		self::$feedback->add_input( 'text', 'email', array(
			'format'      => __( 'Email Address: %1$s', 'better-wp-security' ),
			'value_alias' => 'email',
			'style_class' => 'regular-text',
		) );
		self::$feedback->add_input( 'select', 'updates_optin', array(
			'format'  => __( 'Receive email updates about WordPress Security from iThemes: %1$s', 'better-wp-security' ),
			'options' => array( 'true' => __( 'Yes', 'better-wp-security' ), 'false' => __( 'No', 'better-wp-security' ) ),
			'value'   => 'true',
		) );
		self::$feedback->add_input( 'hidden', 'method', array(
			'value' => 'activate-network-brute-force',
		) );
		self::$feedback->add_input( 'submit', 'enable_network_brute_force', array(
			'value'       => __( 'Activate Network Brute Force Protection', 'better-wp-security' ),
			'style_class' => 'button-primary',
			'data'        => array(
				'clicked-value' => __( 'Activating Network Brute Force Protection...', 'better-wp-security' ),
			),
		) );
	}

	private static function enforce_setting( $module, $setting_name, $setting_value, $description ) {
		if ( ! in_array( $module, self::$available_modules ) ) {
			return;
		}

		if ( ITSEC_Modules::get_setting( $module, $setting_name ) === $setting_value ) {
			return;
		}


		ITSEC_Modules::set_setting( $module, $setting_name, $setting_value );

		self::$feedback->add_section( "enforce-setting-$module-$setting_name", array( 'status' => 'action-taken' ) );
		self::$feedback->add_text( $description );

		ITSEC_Response::reload_module( $module );
	}

	private static function enforce_activation( $module, $name ) {
		if ( ! in_array( $module, self::$available_modules ) ) {
			return;
		}

		self::$feedback->add_section( "$module-activation" );

		if ( ITSEC_Modules::is_active( $module ) ) {
			/* Translators: 1: feature name */
			$text = __( '%1$s is enabled as recommended.', 'better-wp-security' );
		} else {
			ITSEC_Modules::activate( $module );
			ITSEC_Response::add_js_function_call( 'setModuleToActive', $module );

			/* Translators: 1: feature name */
			$text = __( 'Enabled %1$s.', 'better-wp-security' );

			self::$feedback->set_section_arg( 'status', 'action-taken' );
		}

		self::$feedback->add_text( sprintf( $text, $name ) );
	}

	public static function activate_network_brute_force( $data ) {
		if ( ! isset( $data['email'] ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-security-check-missing-email', __( 'The email value is missing.', 'better-wp-security' ) ) );
			return;
		}

		if ( ! isset( $data['updates_optin'] ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-security-check-missing-updates_optin', __( 'The updates_optin value is missing.', 'better-wp-security' ) ) );
			return;
		}


		$settings = ITSEC_Modules::get_settings( 'network-brute-force' );

		$settings['email'] = $data['email'];
		$settings['updates_optin'] = $data['updates_optin'];
		$settings['api_nag'] = false;

		$results = ITSEC_Modules::set_settings( 'network-brute-force', $settings );

		if ( is_wp_error( $results ) ) {
			ITSEC_Response::add_error( $results );
		} else if ( $results['saved'] ) {
			ITSEC_Modules::activate( 'network-brute-force' );
			ITSEC_Response::add_js_function_call( 'setModuleToActive', 'network-brute-force' );
			ITSEC_Response::set_response( '<p>' . __( 'Your site is now using Network Brute Force Protection.', 'better-wp-security' ) . '</p>' );
		}
	}
}
