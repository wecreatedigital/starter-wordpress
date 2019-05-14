<?php

/**
 * Class ITSEC_Fingerprinting
 */
class ITSEC_Fingerprinting {

	const FORCE_CHANGE_META = '_itsec_password_fingerprint_force_changed';
	const AJAX_ACTION = 'itsec-fingerprint-action';
	const CONFIRM_ACTION = 'itsec-fingerprint-confirm';
	const HJP_COOKIE = 'itsec-fingerprint-shp';
	const PENDING_DAYS = 5;

	/** @var string */
	private $provider_class_2fa;

	/** @var string */
	private $login_message;

	/** @var array */
	private $show_admin_bar;

	/** @var bool */
	private $_authed;

	/**
	 * Run the Fingerprinting module.
	 */
	public function run() {

		add_filter( 'itsec_fingerprint_sources', array( $this, 'register_sources' ) );
		add_action( 'itsec_fingerprint_denied', array( $this, 'rescue_account' ) );
		add_action( 'deleted_user', array( $this, 'clear_fingerprints_on_user_delete' ) );

		if ( $this->should_run_fingerprint_checks_for_request() ) {
			add_action( 'wp_login', array( $this, 'handle_fingerprint' ), 100, 2 );
			add_filter( 'attach_session_information', array( $this, 'attach_fingerprint_to_session' ), 10, 2 );
			add_filter( 'authenticate', array( $this, 'block_denied_fingerprints' ), 0, 2 );
			add_filter( 'authenticate', array( $this, 'override_auth_error_when_forced_change' ), 1000, 2 );

			if ( isset( $GLOBALS['current_user'] ) && $GLOBALS['current_user'] instanceof WP_User && $GLOBALS['current_user']->exists() ) {
				add_action( 'itsec_initialized', array( $this, 'on_auth' ), 1000 );
			} else {
				add_action( 'set_current_user', array( $this, 'on_auth' ), - 1000 );
			}
		}

		if ( ITSEC_Modules::get_setting( 'fingerprinting', 'restrict_capabilities' ) ) {
			add_action( 'itsec_initialized', array( $this, 'run_restrict_capabilities' ) );
		}

		// Admin Bar
		add_action( 'wp_enqueue_scripts', array( $this, 'prepare_admin_bar' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'prepare_admin_bar' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 200 );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_ajax_action' ) );
		add_action( 'wp_ajax_' . self::CONFIRM_ACTION, array( $this, 'ajax_send_confirm_email' ) );
		add_filter( 'heartbeat_received', array( $this, 'admin_bar_heartbeat' ), 10, 2 );

		// Fingerprint actions
		add_action( 'login_form_itsec-approve-fingerprint', array( $this, 'handle_fingerprint_action_url' ) );
		add_action( 'login_form_itsec-deny-fingerprint', array( $this, 'handle_fingerprint_action_url' ) );

		// Profile
		add_action( 'show_user_profile', array( $this, 'render_user_profile_manager' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_profile_manager' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_profile_manager' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_manager' ) );

		// Logging
		add_action( 'itsec_fingerprint_created', array( $this, 'log_create' ), 10, 3 );
		add_action( 'itsec_fingerprint_approved', array( $this, 'log_status' ), 10, 3 );
		add_action( 'itsec_fingerprint_auto_approved', array( $this, 'log_status' ), 10, 3 );
		add_action( 'itsec_fingerprint_auto_approve_delayed', array( $this, 'log_status' ), 10, 3 );
		add_action( 'itsec_fingerprint_denied', array( $this, 'log_status' ), 10, 3 );

		// Scheduler
		add_action( 'itsec_scheduler_register_events', array( $this, 'register_events' ) );
		add_action( 'itsec_scheduled_approve-fingerprints', array( $this, 'approve_pending_fingerprints' ) );

		// Notifications
		add_filter( 'itsec_notifications', array( $this, 'register_notifications' ) );
		add_filter( 'itsec_unrecognized-login_notification_strings', array( $this, 'unrecognized_login_strings' ) );

		// Plumbing replaceable by closures
		add_filter( 'login_message', array( $this, 'login_message' ) );
		add_action( 'itsec-two-factor-successful-authentication', array( $this, 'record_2fa_provider' ), 10, 2 );
	}

	public function run_restrict_capabilities() {

		if ( ! ITSEC_Core::get_notification_center()->is_notification_enabled( 'unrecognized-login' ) ) {
			return;
		}

		add_filter( 'user_has_cap', array( __CLASS__, 'restrict_capabilities' ), 10, 4 );
		add_filter( 'wp_pre_insert_user_data', array( $this, 'prevent_updating_protected_user_fields' ), 10, 3 );
		add_action( 'personal_options_update', array( $this, 'block_profile_email_confirmation' ), 0 );
		add_action( 'user_profile_update_errors', array( $this, 'add_errors_when_updating_protected_user_fields' ), 10, 3 );
		add_action( 'admin_print_styles-profile.php', array( $this, 'style_profile_page_to_prevent_updating_protected_user_fields' ) );
	}

	/**
	 * Should fingerprint checks be run for the current request.
	 *
	 * @return bool
	 */
	private function should_run_fingerprint_checks_for_request() {

		if ( ITSEC_Lib::is_loopback_request() ) {
			return false;
		}

		return true;
	}

	/**
	 * Register sources with the Fingerprinting library.
	 *
	 * @param array $sources
	 *
	 * @return array
	 */
	public function register_sources( $sources ) {

		require_once( dirname( __FILE__ ) . '/sources/abstract-itsec-fingerprint-source-header.php' );
		require_once( dirname( __FILE__ ) . '/sources/class-itsec-fingerprint-source-accept-language.php' );
		require_once( dirname( __FILE__ ) . '/sources/class-itsec-fingerprint-source-header-basic.php' );
		require_once( dirname( __FILE__ ) . '/sources/class-itsec-fingerprint-source-ip.php' );
		require_once( dirname( __FILE__ ) . '/sources/class-itsec-fingerprint-source-user-agent.php' );

		$sources[] = new ITSEC_Fingerprint_Source_IP();
		$sources[] = new ITSEC_Fingerprint_Source_User_Agent();
		$sources[] = new ITSEC_Fingerprint_Source_Accept_Language();
		$sources[] = new ITSEC_Fingerprint_Source_Header_Basic( 'dnt', 5 );

		return $sources;
	}

	/**
	 * Check for an unrecognized login attempt.
	 *
	 * @param string  $username
	 * @param WP_User $user
	 */
	public function handle_fingerprint( $username, $user ) {

		ITSEC_Lib::clear_cookie( self::HJP_COOKIE );
		delete_user_meta( $user->ID, self::FORCE_CHANGE_META );

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user ) ) {
			return;
		}

		$fingerprint = $this->when_no_fingerprint( $user );

		/**
		 * Fires when a user logs in after the fingerprint has been determined.
		 *
		 * @param ITSEC_Fingerprint $fingerprint
		 * @param WP_User           $user
		 */
		do_action( 'itsec_login_with_fingerprint', $fingerprint, $user );
	}

	/**
	 * Attach the fingerprint hash to the session.
	 *
	 * @param array $info
	 * @param int   $user_id
	 *
	 * @return array
	 */
	public function attach_fingerprint_to_session( $info, $user_id ) {

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user_id ) ) {
			return $info;
		}

		$fingerprint = ITSEC_Lib_Fingerprinting::calculate_fingerprint_from_global_state( $user_id );

		if ( $hash = $fingerprint->calculate_hash() ) {
			$info['itsec_fingerprint_hash'] = $hash;
		}

		return $info;
	}

	/**
	 * When the user is authenticated with the session token, check if their fingerprint has changed.
	 */
	public function on_auth() {

		// We only want to run this once, even if set_current_user() is used later in the request.
		if ( $this->_authed ) {
			return;
		}

		$this->_authed = true;

		if ( ! $token = wp_get_session_token() ) {
			return;
		}

		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			return;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user ) ) {
			return;
		}

		$sm      = WP_Session_Tokens::get_instance( $user->ID );
		$session = $sm->get( $token );

		if ( ! isset( $session['itsec_fingerprint_hash'] ) ) {
			$fingerprint = $this->when_no_fingerprint( $user );

			$session['itsec_fingerprint_hash'] = $fingerprint->calculate_hash();
			$sm->update( $token, $session );

			return;
		}

		$hash = $session['itsec_fingerprint_hash'];

		$fingerprint = ITSEC_Lib_Fingerprinting::calculate_fingerprint_from_global_state( $user );

		if ( hash_equals( $hash, $fingerprint->calculate_hash() ) ) {
			return;
		}

		$shp  = ITSEC_Modules::get_setting( 'fingerprinting', 'session_hijacking_protection' );
		$prev = ITSEC_Fingerprint::get_by_hash( $user, $hash );

		// If there is another fingerprint with this hash, then just update the hash.
		if ( $stored = ITSEC_Lib_Fingerprinting::get_stored_fingerprint( $fingerprint ) ) {
			$session['itsec_fingerprint_hash'] = $stored->calculate_hash();
			$sm->update( $token, $session );
			$stored->was_seen();

			ITSEC_Log::add_debug( 'fingerprinting', 'session_switched_known', array(
				'to'    => $stored->get_uuid(),
				'from'  => $prev ? $prev->get_uuid() : '',
				'token' => $token,
			) );

			if ( $shp && $fingerprint->is_denied() ) {
				$this->destroy_session( $fingerprint, $prev );
			}

			return;
		}

		$match = ITSEC_Lib_Fingerprinting::check_for_match( $fingerprint );

		if ( ! $match ) {
			$fingerprint->create();

			if ( $shp ) {
				$this->destroy_session( $fingerprint, $prev );
			}

			return;
		}

		$this->handle_fingerprint_comparison( $match, false );

		if ( ! $shp || $match->get_match_percent() >= 50 ) {
			$session['itsec_fingerprint_hash'] = $fingerprint->calculate_hash();
			$sm->update( $token, $session );

			ITSEC_Log::add_debug( 'fingerprinting', 'session_switched_unknown', array(
				'to'    => $fingerprint->get_uuid(),
				'from'  => $prev ? $prev->get_uuid() : '',
				'match' => $match->get_match_percent(),
				'token' => $token,
			) );

			return;
		}

		if ( ! $shp ) {
			return;
		}

		$this->destroy_session( $fingerprint, $prev );
	}

	/**
	 * Destroy the current session.
	 *
	 * @param ITSEC_Fingerprint      $fingerprint $fingerprint
	 * @param ITSEC_Fingerprint|null $prev
	 */
	private function destroy_session( $fingerprint, $prev ) {

		ITSEC_Log::add_action( 'fingerprinting', 'session_destroyed', array(
			'to'    => $fingerprint->get_uuid(),
			'from'  => $prev ? $prev->get_uuid() : '',
			'token' => wp_get_session_token(),
		) );

		wp_clear_auth_cookie();
		wp_destroy_current_session();
		ITSEC_Lib::set_cookie( self::HJP_COOKIE, true );
		auth_redirect();
	}

	/**
	 * Trigger this when there is no fingerprint associated with the session and you need one to be.
	 *
	 * @param WP_User $user
	 *
	 * @return ITSEC_Fingerprint
	 */
	private function when_no_fingerprint( $user ) {

		$fingerprint = ITSEC_Lib_Fingerprinting::calculate_fingerprint_from_global_state( $user );

		if ( $known = ITSEC_Lib_Fingerprinting::get_stored_fingerprint( $fingerprint ) ) {
			$known->was_seen();

			return $known;
		}

		if ( ! ITSEC_Lib_Fingerprinting::get_user_fingerprints( $user ) ) {
			$fingerprint->approve();
			$fingerprint->create();

			return $fingerprint;
		}

		$match = ITSEC_Lib_Fingerprinting::check_for_match( $fingerprint );

		if ( $match ) {
			$this->handle_fingerprint_comparison( $match );
		} else {
			$fingerprint->create();
			$this->send_unrecognized_login( $fingerprint );
		}

		return $fingerprint;
	}

	/**
	 * Handle the fingerprint comparison.
	 *
	 * @param ITSEC_Fingerprint_Comparison $match
	 * @param bool                         $send_email Whether to send the email.
	 */
	private function handle_fingerprint_comparison( ITSEC_Fingerprint_Comparison $match, $send_email = true ) {
		switch ( true ) {
			case $match->get_match_percent() >= 85:
				$match->get_unknown()->auto_approve();
				$match->get_unknown()->create();
				break;
			case $match->get_match_percent() >= 50:
				$match->get_unknown()->delay_auto_approve();
				$match->get_unknown()->create();
				$send_email && $this->send_unrecognized_login( $match->get_unknown() );
				break;
			default:
				$match->get_unknown()->create();
				$send_email && $this->send_unrecognized_login( $match->get_unknown() );
				break;
		}

		ITSEC_Log::add_debug( 'fingerprinting', 'comparison', array(
			'known'   => $match->get_known()->get_uuid(),
			'unknown' => $match->get_unknown()->get_uuid(),
			'percent' => $match->get_match_percent(),
			'scores'  => $match->get_scores(),
			'action'  => current_action(),
		) );
	}

	/**
	 * Block a user from logging in if it is an exact match with a denied fingerprint.
	 *
	 * @param WP_User|WP_Error|null $user_or_error
	 * @param string                $username
	 *
	 * @return WP_User|WP_Error|null
	 */
	public function block_denied_fingerprints( $user_or_error, $username ) {

		if ( ! $user = get_user_by( 'login', $username ) ) {
			return $user_or_error;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user ) ) {
			return $user_or_error;
		}

		$fingerprint = ITSEC_Lib_Fingerprinting::calculate_fingerprint_from_global_state( $user );
		$stored      = ITSEC_Lib_Fingerprinting::get_stored_fingerprint( $fingerprint );

		if ( ! $stored || ! $stored->is_denied() ) {
			return $user_or_error;
		}

		$fingerprint->was_seen();
		ITSEC_Log::add_notice( 'fingerprinting', 'denied_fingerprint_blocked', array(
			'uuid' => $stored->get_uuid(),
		) );

		$error = is_wp_error( $user_or_error ) ? $user_or_error : new WP_Error();
		$error->add(
			'itsec-fingerprinting-authenticate-denied-fingerprint',
			__( 'This device is blacklisted from logging in to this account.', 'it-l10n-ithemes-security-pro' )
		);

		return $error;
	}

	/**
	 * When auth failed because
	 *
	 * @param WP_User|WP_Error|null $user_or_error
	 * @param string                $username
	 *
	 * @return WP_User|WP_Error|null
	 */
	public function override_auth_error_when_forced_change( $user_or_error, $username ) {

		if (
			! $user_or_error instanceof WP_User &&
			( $user = get_user_by( 'login', $username ) ) &&
			get_user_meta( $user->ID, self::FORCE_CHANGE_META, true )
		) {
			return new WP_Error(
				'itsec-fingerprinting-forced-change',
				sprintf(
					esc_html__( 'For security purposes, your password was reset. %1$sRequest a new password%2$s.', 'it-l10n-ithemes-security-pro' ),
					'<a href="' . esc_url( wp_lostpassword_url() ) . '">',
					'</a>'
				)
			);
		}

		return $user_or_error;
	}

	/**
	 * "rescue" an account by clearing all session tokens,
	 * changing the password, and forcing a password change.
	 *
	 * @param ITSEC_Fingerprint $fingerprint
	 */
	public function rescue_account( ITSEC_Fingerprint $fingerprint ) {
		$user = $fingerprint->get_user();
		$snap = $fingerprint->get_snapshot();

		WP_Session_Tokens::get_instance( $user->ID )->destroy_all();
		wp_set_password( wp_generate_password( 36, true, true ), $user->ID );
		update_user_meta( $user->ID, self::FORCE_CHANGE_META, true );

		if ( isset( $snap['user_email'] ) && $user->user_email !== $snap['user_email'] ) {
			wp_update_user( array( 'ID' => $user->ID, 'user_email' => $snap['user_email'] ) );
		}
	}

	/**
	 * Clear fingerprints when a user is deleted.
	 *
	 * @param int $user_id
	 */
	public function clear_fingerprints_on_user_delete( $user_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->base_prefix . 'itsec_fingerprints', array( 'fingerprint_user' => $user_id ), array( 'fingerprint_user' => '%d' ) );
	}

	/**
	 * Restrict capabilities when on an unrecognized device.
	 *
	 * @param array   $all_caps
	 * @param array   $required_caps
	 * @param array   $args
	 * @param WP_User $user
	 *
	 * @return array
	 */
	public static function restrict_capabilities( $all_caps, $required_caps, $args, $user ) {

		if ( get_current_user_id() !== $user->ID ) {
			return $all_caps;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		$applies = ITSEC_Lib_Fingerprinting::applies_to_user( $user );
		$current = ITSEC_Lib_Fingerprinting::get_current_fingerprint();
		$is_safe = ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe();

		if ( ! $applies || $is_safe ) {
			return $all_caps;
		}

		$to_remove = array(
			'activate_plugins',
			'create_users',
			'delete_plugins',
			'delete_users',
			'edit_files',
			'edit_plugins',
			'edit_users',
			'install_plugins',
			'install_themes',
			'level_8',
			'level_9',
			'level_10',
			'manage_options',
			'promote_users',
			'remove_users',
			'unfiltered_upload',
			ITSEC_Core::get_required_cap(),
		);

		/**
		 * Filter the capabilities to remove when a user is on an unrecognized device.
		 *
		 * @param array                  $to_remove
		 * @param WP_User                $user
		 * @param ITSEC_Fingerprint|null $fingerprint
		 */
		$to_remove = apply_filters( 'itsec_fingerprinting_caps_to_remove', $to_remove, $user, $current );

		return array_diff_key( $all_caps, array_flip( $to_remove ) );
	}

	/**
	 * Prevent a user updating their email or password when they are on a unrecognized device.
	 *
	 * @param array $data
	 * @param bool  $update
	 * @param int   $user_id
	 *
	 * @return array
	 */
	public function prevent_updating_protected_user_fields( $data, $update, $user_id ) {

		if ( ! $update || ! $user_id || (int) get_current_user_id() !== (int) $user_id ) {
			return $data;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user_id ) || ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			return $data;
		}

		$fields = $this->get_protected_user_fields( get_userdata( $user_id ), ITSEC_Lib_Fingerprinting::get_current_fingerprint() );

		$data = array_diff_key( $data, array_flip( $fields ) );

		return $data;
	}

	/**
	 * Block the confirm new email flow on the profile page. This overrides the default update user flow so needs to be removed
	 * for our error message to appear.
	 *
	 * @param int $user_id
	 */
	public function block_profile_email_confirmation( $user_id ) {

		if ( ! $user_id ) {
			return;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user_id ) || ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			return;
		}

		if ( ! in_array( 'user_email', $this->get_protected_user_fields( get_userdata( $user_id ), ITSEC_Lib_Fingerprinting::get_current_fingerprint() ), true ) ) {
			return;
		}

		remove_action( 'personal_options_update', 'send_confirmation_on_profile_email' );
	}

	/**
	 * Add errors if the user tries to update their email or password.
	 *
	 * @param WP_Error $errors
	 * @param bool     $update
	 * @param stdClass $user
	 */
	public function add_errors_when_updating_protected_user_fields( $errors, $update, $user ) {

		if ( ! $update ) {
			return;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user ) || ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			return;
		}

		if ( ! $_user = get_userdata( $user->ID ) ) {
			return;
		}

		foreach ( $this->get_protected_user_fields( $_user, ITSEC_Lib_Fingerprinting::get_current_fingerprint() ) as $field ) {
			if ( ! isset( $user->$field ) ) {
				continue;
			}

			if ( 'user_pass' === $field ) {
				$errors->add(
					'itsec_fingerprint_protected',
					esc_html__( 'You cannot update your password on an unrecognized device. Please check your email to confirm this new device.', 'it-l10n-ithemes-security-pro' ),
					compact( 'field' )
				);

				return;
			}

			if ( $user->$field === $_user->$field ) {
				continue;
			}

			switch ( $field ) {
				case 'user_email':
					$errors->add(
						'itsec_fingerprint_protected',
						esc_html__( 'You cannot update your email on an unrecognized device. Please check your email to confirm this new device.', 'it-l10n-ithemes-security-pro' ),
						compact( 'field' )
					);
					break;
				default:
					$errors->add(
						'itsec_fingerprint_protected',
						sprintf( esc_html__( 'You cannot update the "%s" field on an unrecognized device. Please check your email to confirm this new device.', 'it-l10n-ithemes-security-pro' ), str_replace( 'user_', '', $field ) ),
						compact( 'field' )
					);
					break;
			}
		}
	}

	/**
	 * Style the profile.php page to disable inputs that are protected.
	 */
	public function style_profile_page_to_prevent_updating_protected_user_fields() {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user() || ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			return;
		}

		$fields = $this->get_protected_user_fields( wp_get_current_user(), ITSEC_Lib_Fingerprinting::get_current_fingerprint() );

		echo '<style type="text/css">';

		if ( in_array( 'user_email', $fields, true ) ) {
			echo '.user-email-wrap { display: none; }';
		}

		if ( in_array( 'user_pass', $fields, true ) ) {
			echo '#password, .user-pass2-wrap { display: none;}';
		}

		echo '</style>';
	}

	/**
	 * Get the fields that should be protected from self-editing when on a unrecognized device.
	 *
	 * @param WP_User                $user
	 * @param ITSEC_Fingerprint|null $fingerprint
	 *
	 * @return array
	 */
	private function get_protected_user_fields( $user, $fingerprint ) {
		$fields = array( 'user_pass', 'user_email' );

		/**
		 * Filter the user fields that should be protected from self-editing when on a unrecognized device.
		 *
		 * @param array                  $fields
		 * @param WP_User                $user
		 * @param ITSEC_Fingerprint|null $fingerprint
		 */
		return apply_filters( 'itsec_fingerprinting_protected_user_fields', $fields, $user, $fingerprint );
	}

	/**
	 * Prepare the admin bar fingerprints manager by printing templates and enqueuing JavaScript.
	 */
	public function prepare_admin_bar() {
		if ( ! is_admin_bar_showing() || ! is_user_logged_in() ) {
			return;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user() ) {
			return;
		}

		if ( ! ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			if ( ITSEC_Modules::get_setting( 'fingerprinting', 'restrict_capabilities' ) && ITSEC_Core::get_notification_center()->is_notification_enabled( 'unrecognized-login' ) ) {
				$this->show_admin_bar = array( 'unknown' => true );
				echo '<style type="text/css">';
				echo '#wpadminbar #wp-admin-bar-itsec-fingerprinting-unknown div{width: 300px;white-space: normal;height: auto;line-height: 1.4em;}';
				echo '#wpadminbar #wp-admin-bar-itsec-fingerprinting-unknown .button-link{line-height: 1.4em;color: #00acff;}';
				echo '#wpadminbar #wp-admin-bar-itsec-fingerprinting-unknown .button-link[disabled]{color: #0073aa;}';
				echo '</style>';

				wp_enqueue_script( 'itsec-fingerprint-toolbar-confirm', plugins_url( 'js/toolbar-confirm.js', __FILE__ ), array( 'jquery', 'wp-util' ), 1 );
			}

			return;
		}

		$fingerprints = ITSEC_Lib_Fingerprinting::get_user_fingerprints( false, array(
			'status' => array( ITSEC_Fingerprint::S_PENDING, ITSEC_Fingerprint::S_PENDING_AUTO_APPROVE )
		) );

		$level     = 'info';
		$formatted = array();

		foreach ( $fingerprints as $fingerprint ) {
			if ( $fingerprint->is_pending() ) {
				$level = 'warning';
			}

			$formatted[] = $this->get_fingerprint_info( $fingerprint, array( 'maps' => array( 'small', 'large' ) ) );
		}

		$this->show_admin_bar = array(
			'count' => count( $fingerprints ),
			'level' => $level,
		);

		$message_detail = ITSEC_Core::get_notification_center()->get_message( 'unrecognized-login' );
		$message_detail = preg_replace( '/\{\{ \$([a-zA-Z_]+) \}\}/', '{{ data.m.$1 }}', $message_detail );
		$message_detail = wptexturize( wpautop( $message_detail ) );

		require_once( dirname( __FILE__ ) . '/templates/toolbar.php' );

		$deps = array( 'jquery', 'wp-backbone', 'underscore', 'wp-a11y', 'micromodal' );

		if ( is_admin() || wp_script_is( 'heartbeat' ) ) {
			$deps[] = 'heartbeat';
		}

		wp_register_script( 'micromodal', plugins_url( 'js/micromodal.js', __FILE__ ), array(), '0.3.2' );
		wp_enqueue_style( 'itsec-fingerprint-toolbar', plugins_url( 'css/toolbar.css', __FILE__ ), array( 'dashicons' ), 1 );
		wp_enqueue_script( 'itsec-fingerprint-toolbar', plugins_url( 'js/toolbar.js', __FILE__ ), $deps, 1 );
		wp_localize_script( 'itsec-fingerprint-toolbar', 'ITSECFingerprintToolbar', array(
			'nonce'        => wp_create_nonce( self::AJAX_ACTION ),
			'fingerprints' => $formatted,
		) );
	}

	/**
	 * Customize the admin bar to include tools for approving/denying a fingerprint.
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function admin_bar( $admin_bar ) {

		if ( ! $this->show_admin_bar ) {
			return;
		}

		if ( ! empty( $this->show_admin_bar['unknown'] ) ) {
			$user  = wp_get_current_user();
			$nonce = wp_create_nonce( 'itsec-fingerprint-confirm' );

			if ( in_array( 'administrator', $user->roles, true ) ) {
				$message = esc_html__( 'You are currently logged-in on an unknown device. You won\'t be able to edit sensitive account information or perform certain administrative tasks until you %1$sconfirm this device%2$s.', 'it-l10n-ithemes-security-pro' );
			} else {
				$message = esc_html__( 'You are currently logged-in on an unknown device. You won\'t be able to edit sensitive account information until you %1$sconfirm this device%2$s.', 'it-l10n-ithemes-security-pro' );
			}

			$admin_bar->add_menu( array(
				'parent' => 'top-secondary',
				'id'     => 'itsec-fingerprinting',
				'title'  => esc_html__( 'Login Alerts', 'it-l10n-ithemes-security-pro' ),
			) );
			$admin_bar->add_menu( array(
				'parent' => 'itsec-fingerprinting',
				'id'     => 'itsec-fingerprinting-unknown',
				'title'  => sprintf( $message, "<button class=\"button-link\" data-nonce=\"{$nonce}\">", '</button>' )
			) );

			return;
		}

		$level  = $this->show_admin_bar['level'];
		$count  = $this->show_admin_bar['count'];
		$hidden = $count ? '' : ' style="display:none"';

		$admin_bar->add_menu( array(
			'parent' => 'top-secondary',
			'id'     => 'itsec-fingerprinting',
			'title'  => sprintf(
				esc_html__( 'Login Alerts %s', 'it-l10n-ithemes-security-pro' ),
				"<span class='itsec-login-alert-bubble itsec-login-alert-bubble--level-{$level}'{$hidden}}><span class='itsec-login-alert-bubble__count'>{$count}</span></span>"
			),
		) );
		$admin_bar->add_menu( array(
			'parent' => 'itsec-fingerprinting',
			'id'     => 'itsec-fingerprinting-cards',
			'title'  => '',
		) );
	}

	/**
	 * Handle an Ajax action to approve or deny fingerprints.
	 */
	public function handle_ajax_action() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], self::AJAX_ACTION ) ) {
			wp_send_json_success( array(
				'message' => esc_html__( 'Request expired, please refresh and try again.', 'it-l10n-ithemes-security-pro' ),
			) );
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user() ) {
			wp_send_json_error( esc_html__( 'Trusted Devices is not enabled for your account.', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( ! ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			$current = ITSEC_Lib_Fingerprinting::get_current_fingerprint();

			if ( $current && ( $current->is_pending() || $current->is_pending_auto_approval() ) ) {
				$this->send_unrecognized_login( $current ); // Todo: Replace with dedicated confirm email.
			}

			wp_send_json_error( array(
				'message' => esc_html__( "Your current device is unconfirmed, so you do not have permission to approve new devices. Check your email for a link to approve this current device.", 'it-l10n-ithemes-security-pro' )
			) );
		}

		if ( ! isset( $_REQUEST['itsec_uuid'], $_REQUEST['itsec_action'] ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Invalid request format.', 'it-l10n-ithemes-security-pro' ),
			) );
		}

		$fingerprint = ITSEC_Fingerprint::get_by_uuid( $_REQUEST['itsec_uuid'] );

		if ( ! $fingerprint || $fingerprint->get_user()->ID !== get_current_user_id() || ! $fingerprint->can_change_status() ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Invalid Device', 'it-l10n-ithemes-security-pro' ),
			) );
		}

		switch ( $_REQUEST['itsec_action'] ) {
			case 'approve':
				if ( ! $fingerprint->approve() ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Failed to approve device. Please refresh and try again, then contact a site administrator.', 'it-l10n-ithemes-security-pro' ) ) );
				}

				wp_send_json_success( array(
					'message' => esc_html__( 'Device approved!', 'it-l10n-ithemes-security-pro' ),
				) );
			case 'deny':
				if ( ! $fingerprint->deny() ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Failed to block device. Please refresh and try again, then contact a site administrator.', 'it-l10n-ithemes-security-pro' ) ) );
				}

				wp_send_json_success( array(
					'message' => esc_html__( 'Device blocked. For security purposes you must reset your password immediately.', 'it-l10n-ithemes-security-pro' ),
					'url'     => $this->get_reset_pass_url( $fingerprint->get_user() ),
				) );
			default:
				wp_send_json_error( array(
					'message' => esc_html__( 'Invalid request format.', 'it-l10n-ithemes-security-pro' )
				) );
		}
	}

	/**
	 * Handle the ajax request to send the confirmation email.
	 */
	public function ajax_send_confirm_email() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], self::CONFIRM_ACTION ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Request expired. Please refresh and try again.', 'it-l10n-ithemes-security-pro' )
			) );
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user() ) {
			wp_send_json_error( esc_html__( 'Trusted Devices is not enabled for your account.', 'it-l10n-ithemes-security-pro' ) );
		}

		$current = ITSEC_Lib_Fingerprinting::get_current_fingerprint();

		if ( ! $current ) {
			$current = $this->when_no_fingerprint( wp_get_current_user() );
		}

		if ( ! $current->is_pending() && ! $current->is_pending_auto_approval() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No pending device found.', 'it-l10n-ithemes-security-pro' ) ) );
		}

		$this->send_unrecognized_login( $current ); // Todo: Replace with dedicated confirm email.

		wp_send_json_success( array(
			'message' => esc_html__( 'Confirmation email resent! Click the Confirm Device button to approve this device.', 'it-l10n-ithemes-security-pro' )
		) );
	}

	/**
	 * Heartbeat toolbar to provide new fingerprints.
	 *
	 * @param array $response
	 * @param array $request
	 *
	 * @return array
	 */
	public function admin_bar_heartbeat( $response, $request ) {

		if ( empty( $request['itsec_fingerprinting'] ) || empty( $request['itsec_fingerprinting']['request'] ) ) {
			return $response;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user() ) {
			return $response;
		}

		if ( ! ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			$response['itsec_fingerprinting']['unauthorized'] = true;

			return $response;
		}

		$uuids = isset( $request['itsec_fingerprinting']['uuids'] ) ? $request['itsec_fingerprinting']['uuids'] : array();

		$fingerprints = ITSEC_Lib_Fingerprinting::get_user_fingerprints( false, array(
			'exclude' => $uuids,
			'status'  => array( ITSEC_Fingerprint::S_PENDING, ITSEC_Fingerprint::S_PENDING_AUTO_APPROVE ),
		) );

		$new    = array();
		$remove = array();

		foreach ( $fingerprints as $fingerprint ) {
			$new[] = $this->get_fingerprint_info( $fingerprint, array( 'maps' => array( 'small', 'large' ) ) );
		}

		foreach ( $uuids as $uuid ) {
			// Ensure a user can only query for the existence of uuids that belong to their account.
			if ( ! ( $fingerprint = ITSEC_Fingerprint::get_by_uuid( $uuid ) ) || $fingerprint->get_user()->ID !== get_current_user_id() ) {
				$remove[] = $uuid;
			}
		}

		$response['itsec_fingerprinting']['new']    = $new;
		$response['itsec_fingerprinting']['remove'] = $remove;

		return $response;
	}

	/**
	 * Render the device fingerprint manager on the profile page for administrative use.
	 *
	 * @param WP_User $user
	 */
	public function render_user_profile_manager( $user ) {

		if ( ! ITSEC_Core::current_user_can_manage() ) {
			return;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user ) ) {
			return;
		}

		if ( ! ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			return;
		}

		wp_nonce_field( 'itsec_fingerprint_profile', 'itsec_fingerprint_profile' );
		?>

		<div class="itsec-profile-fingerprints">
			<h3><?php esc_html_e( 'Trusted Devices', 'it-l10n-ithemes-security-pro' ); ?></h3>

			<table class="widefat striped">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Location', 'it-l10n-ithemes-security-pro' ) ?></th>
					<th><?php esc_html_e( 'Browser', 'it-l10n-ithemes-security-pro' ) ?></th>
					<th><?php esc_html_e( 'Platform', 'it-l10n-ithemes-security-pro' ) ?></th>
					<th><?php esc_html_e( 'Created', 'it-l10n-ithemes-security-pro' ) ?></th>
					<th><?php esc_html_e( 'Status', 'it-l10n-ithemes-security-pro' ) ?></th>
					<th><?php esc_html_e( 'Last Seen', 'it-l10n-ithemes-security-pro' ) ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( ITSEC_Lib_Fingerprinting::get_user_fingerprints( $user ) as $fingerprint ): $info = $this->get_fingerprint_info( $fingerprint, array( 'maps' => false ) ); ?>
					<tr>
						<td><?php echo $info['location'] ? $info['location'] . '<br>' . $info['ip'] : $info['ip'] ?></td>
						<td><?php $info['browser_ver'] ? printf( '%s (%s)', $info['browser'], $info['browser_ver'] ) : $info['browser'] ?></td>
						<td><?php echo $info['platform'] ?></td>
						<td><?php echo $info['date-time'] ?></td>
						<td>
							<label class="screen-reader-text" for="itsec-fingerprint-status-<?php echo $fingerprint->get_uuid(); ?>">
								<?php esc_html_e( 'Change Fingerprint Status', 'it-l10n-ithemes-security-pro' ) ?>
							</label>
							<select id="itsec-fingerprint-status-<?php echo $fingerprint->get_uuid(); ?>" name="itsec_fingerprint_status[<?php echo $fingerprint->get_uuid() ?>]">
								<option value="<?php echo ITSEC_Fingerprint::S_APPROVED ?>" <?php selected( $fingerprint->get_status(), ITSEC_Fingerprint::S_APPROVED ) ?>>
									<?php esc_html_e( 'Approved', 'it-l10n-ithemes-security-pro' ) ?>
								</option>
								<option disabled value="<?php echo ITSEC_Fingerprint::S_AUTO_APPROVED ?>" <?php selected( $fingerprint->get_status(), ITSEC_Fingerprint::S_AUTO_APPROVED ) ?>>
									<?php esc_html_e( 'Auto-approved', 'it-l10n-ithemes-security-pro' ) ?>
								</option>
								<option disabled
										value="<?php echo ITSEC_Fingerprint::S_PENDING_AUTO_APPROVE ?>" <?php selected( $fingerprint->get_status(), ITSEC_Fingerprint::S_PENDING_AUTO_APPROVE ) ?>>
									<?php esc_html_e( 'Pending Auto-approval', 'it-l10n-ithemes-security-pro' ) ?>
								</option>
								<option disabled value="<?php echo ITSEC_Fingerprint::S_PENDING ?>" <?php selected( $fingerprint->get_status(), ITSEC_Fingerprint::S_PENDING ) ?>>
									<?php esc_html_e( 'Pending', 'it-l10n-ithemes-security-pro' ) ?>
								</option>
								<option value="<?php echo ITSEC_Fingerprint::S_DENIED ?>" <?php selected( $fingerprint->get_status(), ITSEC_Fingerprint::S_DENIED ) ?>>
									<?php esc_html_e( 'Denied', 'it-l10n-ithemes-security-pro' ) ?>
								</option>
							</select>
						</td>
						<td><?php echo ITSEC_Lib::date_format_i18n_and_local_timezone( $fingerprint->get_last_seen()->format( 'U' ) ) ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php
	}

	/**
	 * Save the device fingerprint manager.
	 *
	 * @param int $user_id
	 */
	public function save_user_profile_manager( $user_id ) {
		if ( ! ITSEC_Core::current_user_can_manage() ) {
			return;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user_id ) ) {
			return;
		}

		if ( ! ITSEC_Lib_Fingerprinting::is_current_fingerprint_safe() ) {
			return;
		}

		check_admin_referer( 'itsec_fingerprint_profile', 'itsec_fingerprint_profile' );

		if ( empty( $_POST['itsec_fingerprint_status'] ) ) {
			return;
		}

		foreach ( $_POST['itsec_fingerprint_status'] as $uuid => $status ) {
			$fingerprint = ITSEC_Fingerprint::get_by_uuid( $uuid );

			if ( ! $fingerprint || $fingerprint->get_user()->ID !== $user_id ) {
				continue;
			}

			if ( $fingerprint->get_status() === $status ) {
				continue;
			}

			switch ( $status ) {
				case ITSEC_Fingerprint::S_APPROVED:
					if ( $fingerprint->can_change_status() ) {
						$fingerprint->approve();
					} else {
						$fingerprint->_set_status( ITSEC_Fingerprint::S_APPROVED );
					}
					break;
				case ITSEC_Fingerprint::S_DENIED:
					if ( $fingerprint->can_change_status() ) {
						$fingerprint->deny();
					} else {
						$fingerprint->_set_status( ITSEC_Fingerprint::S_DENIED );
					}
					break;
			}
		}
	}

	/**
	 * Send the unrecognized login email.
	 *
	 * @param ITSEC_Fingerprint $fingerprint
	 */
	private function send_unrecognized_login( ITSEC_Fingerprint $fingerprint ) {

		$nc = ITSEC_Core::get_notification_center();

		if ( ! $nc->is_notification_enabled( 'unrecognized-login' ) ) {
			return;
		}

		$info = $this->get_fingerprint_info( $fingerprint, array( 'maps' => 'medium' ) );

		$headers = $row = array();

		if ( $info['ip'] ) {
			require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-geolocation.php' );
			$headers[] = esc_html__( 'Location', 'it-l10n-ithemes-security-pro' );

			if ( $info['location'] ) {
				$row[] = $info['location'] . '<br />' . "({$info['ip']})";
			} else {
				$row[] = $info['ip'];
			}
		}

		if ( ITSEC_Lib_Browser::BROWSER_UNKNOWN !== $info['browser'] ) {
			$headers[] = esc_html__( 'Browser', 'it-l10n-ithemes-security-pro' );
			$row[]     = $info['browser'];
		}

		if ( ITSEC_Lib_Browser::PLATFORM_UNKNOWN !== $info['platform'] ) {
			$headers[] = esc_html__( 'Platform', 'it-l10n-ithemes-security-pro' );
			$row[]     = $info['platform'];
		}

		if ( $this->provider_class_2fa ) {
			$instances = ITSEC_Two_Factor_Helper::get_instance()->get_all_provider_instances();

			$headers[] = esc_html__( 'Two-Factor', 'it-l10n-ithemes-security-pro' );
			$row[]     = isset( $instances[ $this->provider_class_2fa ] ) ? $instances[ $this->provider_class_2fa ]->get_label() : $this->provider_class_2fa;
		}

		$headers[] = esc_html__( 'Time', 'it-l10n-ithemes-security-pro' );
		$time      = ITSEC_Lib::date_format_i18n_and_local_timezone(
			$fingerprint->get_created_at()->format( 'U' ),
			get_option( 'date_format' ) . '|||' . get_option( 'time_format' )
		);

		$row[] = str_replace( '|||', '<br />', $time );

		$mail = $nc->mail( 'unrecognized-login' );

		$mail->add_header( esc_html__( 'Unrecognized Login', 'it-l10n-ithemes-security-pro' ), esc_html__( 'Unrecognized Login', 'it-l10n-ithemes-security-pro' ), true );
		$mail->add_large_text( esc_html__( 'Was this you?', 'it-l10n-ithemes-security-pro' ) );

		$mail->add_text( ITSEC_Lib::replace_tags( $nc->get_message( 'unrecognized-login' ), array(
			'display_name' => $fingerprint->get_user()->display_name,
			'username'     => $fingerprint->get_user()->user_login,
			'site_title'   => get_bloginfo( 'name', 'display' ),
			'location'     => "<b>{$info['location']}</b>",
			'ip'           => "<b>{$info['ip']}</b>",
			'browser'      => "<b>{$info['browser']}</b>",
			'platform'     => "<b>{$info['platform']}</b>",
			'time'         => '<b>' . $info['time'] . '</b>',
			'date'         => '<b>' . $info['date'] . '</b>',
		) ) );

		if ( $info['map-medium'] ) {
			$mail->add_image( $info['map-medium'], 560 );
		}

		$mail->add_table( $headers, array( $row ), true );

		$mail->add_divider();

		$mail->add_button( esc_html__( 'This Was Not Me', 'it-l10n-ithemes-security-pro' ), $this->get_fingerprint_action_link( $fingerprint, 'deny' ) );

		if ( $fingerprint->is_pending_auto_approval() ) {
			$confirm_message = esc_html__( 'This device will be automatically marked as trusted in a few days, but click the button below to do it immediately.', 'it-l10n-ithemes-security-pro' );
		} else {
			$confirm_message = esc_html__( 'If this was you, please confirm your device by clicking the button below.', 'it-l10n-ithemes-security-pro' );
		}

		$mail->add_text( '<i>' . $confirm_message . '</i>' );
		$mail->add_button( esc_html__( 'Confirm Device', 'it-l10n-ithemes-security-pro' ), $this->get_fingerprint_action_link( $fingerprint, 'approve' ), 'blue' );

		if ( $info['credit'] ) {
			$mail->add_divider();
			$mail->add_text( $info['credit'] );
		}

		$mail->add_user_footer();
		$mail->set_recipients( array( $fingerprint->get_user()->user_email ) );

		$nc->send( 'unrecognized-login', $mail );
	}

	/**
	 * Get information about a fingerprint.
	 *
	 * @param ITSEC_Fingerprint $fingerprint
	 * @param array             $args
	 *
	 * @return array
	 */
	private function get_fingerprint_info( ITSEC_Fingerprint $fingerprint, array $args = array() ) {

		$args = wp_parse_args( $args, array(
			'maps' => true,
		) );

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-browser.php' );

		$data = array(
			'uuid'        => $fingerprint->get_uuid(),
			'created_at'  => $fingerprint->get_created_at()->format( 'Y-m-d H:i:s' ),
			'browser'     => ITSEC_Lib_Browser::BROWSER_UNKNOWN,
			'browser_ver' => '',
			'platform'    => ITSEC_Lib_Browser::PLATFORM_UNKNOWN,
			'ip'          => '',
			'location'    => '',
			'map-small'   => '',
			'map-medium'  => '',
			'map-large'   => '',
			'credit'      => '',
			'date-time'   => ITSEC_Lib::date_format_i18n_and_local_timezone( $fingerprint->get_created_at()->format( 'U' ) ),
			'date'        => ITSEC_Lib::date_format_i18n_and_local_timezone( $fingerprint->get_created_at()->format( 'U' ), 'M j, Y' ),
			'time'        => ITSEC_Lib::date_format_i18n_and_local_timezone( $fingerprint->get_created_at()->format( 'U' ), 'g:ia' ),
			'title'       => esc_html__( 'Unrecognized Login', 'it-l10n-ithemes-security-pro' ), // __( 'Unrecognized login near New York, United States', 'it-l10n-ithemes-security-pro' ),
		);

		$values = $fingerprint->get_values();

		if ( isset( $values['ip'] ) ) {
			$ip = $data['ip'] = $values['ip']->get_value();

			require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-geolocation.php' );
			require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-static-map-api.php' );

			if ( ! is_wp_error( $geolocate = ITSEC_Lib_Geolocation::geolocate( $ip ) ) ) {
				/* translators: 1. Location Label */
				$data['title']    = sprintf( esc_html__( 'Unrecognized login near %s', 'it-l10n-ithemes-security-pro' ), $geolocate['label'] );
				$data['credit']   = $geolocate['credit'];
				$data['location'] = $geolocate['label'];

				$maps = $args['maps'];

				if ( true === $maps || ( is_array( $maps ) && in_array( 'small', $maps, true ) ) ) {
					if ( ! is_wp_error( $small = ITSEC_Lib_Static_Map_API::get_map( array(
						'lat'    => $geolocate['lat'],
						'long'   => $geolocate['long'],
						'width'  => '255',
						'height' => '115',
					) ) ) ) {
						$data['map-small'] = $small;
					}
				}

				if ( true === $maps || ( is_array( $maps ) && in_array( 'medium', $maps, true ) ) ) {
					if ( ! is_wp_error( $medium = ITSEC_Lib_Static_Map_API::get_map( array(
						'lat'    => $geolocate['lat'],
						'long'   => $geolocate['long'],
						'width'  => '560',
						'height' => '315',
					) ) ) ) {
						$data['map-medium'] = $medium;
					}
				}

				if ( true === $maps || ( is_array( $maps ) && in_array( 'large', $maps, true ) ) ) {
					if ( ! is_wp_error( $large = ITSEC_Lib_Static_Map_API::get_map( array(
						'lat'    => $geolocate['lat'],
						'long'   => $geolocate['long'],
						'width'  => '600',
						'height' => '200',
					) ) ) ) {
						$data['map-large'] = $large;
					}
				}
			}
		}

		if ( isset( $values['header-user-agent'] ) ) {
			$browser = new ITSEC_Lib_Browser( $values['header-user-agent']->get_value() );

			$data['browser']     = $browser->getBrowser();
			$data['platform']    = $browser->getPlatform();
			$data['browser_ver'] = $browser->getVersion();
		}

		return $data;
	}

	/**
	 * Handle an action on the WP Login page to either approve or deny a fingerprint.
	 */
	public function handle_fingerprint_action_url() {
		if ( ! isset( $_REQUEST['itsec_user'], $_REQUEST['itsec_uuid'], $_REQUEST['itsec_hash'] ) ) {
			return;
		}

		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-browser.php' );

			$browser = new ITSEC_Lib_Browser();

			if ( $browser->isRobot() ) {
				$html = '';

				if ( 'itsec-approve-fingerprint' === $_REQUEST['action'] ) {
					$button = esc_html__( 'Approve', 'it-l10n-ithemes-security-pro' );
					$html   .= '<p class="description">' . esc_html__( 'Are you sure you want to approve this device?', 'it-l10n-ithemes-security-pro' ) . '</p>';
				} elseif ( 'itsec-deny-fingerprint' === $_REQUEST['action'] ) {
					$button = esc_html__( 'Block', 'it-l10n-ithemes-security-pro' );
					$html   .= '<p class="description">' . esc_html__( 'Are you sure you want to block this device?', 'it-l10n-ithemes-security-pro' ) . '</p>';
				} else {
					$button = esc_html__( 'Confirm', 'it-l10n-ithemes-security-pro' );
					$html   .= '<p class="description">' . esc_html__( 'Are you sure you want to do this action?', 'it-l10n-ithemes-security-pro' ) . '</p>';
				}

				$html .= '<form action="' . esc_url( wp_login_url() ) . '" method="POST">';
				foreach ( array( 'itsec_user', 'itsec_uuid', 'itsec_hash', 'action' ) as $field ) {
					$value = isset( $_REQUEST[ $field ] ) ? $_REQUEST[ $field ] : '';
					$html  .= '<input type="hidden" name="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '"/>';
				}
				$html .= '<input type="submit" class="button" value="' . $button . '">';
				$html .= '</form>';

				wp_die( $html, esc_html__( 'Are You Sure?', 'it-l10n-ithemes-security-pro' ), array( 'status' => 400 ) );
			}
		}

		$user_id = (int) $_REQUEST['itsec_user'];
		$uuid    = $_REQUEST['itsec_uuid'];
		$actual  = $_REQUEST['itsec_hash'];

		$expected = hash_hmac( ITSEC_Lib::get_hash_algo(), "{$uuid}|{$user_id}", wp_salt() );

		if ( ! hash_equals( $actual, $expected ) ) {
			wp_die( __( 'Failed to confirm the device because the URL was invalid.', 'it-l10n-ithemes-security-pro' ), __( 'Invalid URL', 'it-l10n-ithemes-security-pro' ) );
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		if ( ! ITSEC_Lib_Fingerprinting::applies_to_user( $user_id ) ) {
			wp_die( __( 'Trusted Devices is not enabled for your account.', 'it-l10n-ithemes-security-pro' ) );
		}

		$fingerprint = ITSEC_Fingerprint::get_by_uuid( $uuid );

		if ( ! $fingerprint || $fingerprint->get_user()->ID !== $user_id ) {
			wp_die( esc_html__( 'Invalid device identifier.', 'it-l10n-ithemes-security-pro' ) );
		}

		switch ( $_REQUEST['action'] ) {
			case 'itsec-approve-fingerprint':
				if ( ! $fingerprint ) {
					return;
				}

				if ( ! $fingerprint->approve() ) {
					if ( ! $fingerprint->can_change_status() ) {
						wp_die( esc_html__( 'This device is no longer modifiable, please contact a site administrator.' ) );
					}

					wp_die( esc_html__( 'Failed to confirm the new device. Please contact a site administrator.', 'it-l10n-ithemes-security-pro' ) );
				}

				$this->login_message = esc_html__( 'The new device has been confirmed and added to your profile.', 'it-l10n-ithemes-security-pro' );
				break;
			case 'itsec-deny-fingerprint':
				if ( ! $fingerprint ) {
					return;
				}

				$user = $fingerprint->get_user();

				if ( ! $fingerprint->deny() ) {
					if ( ! $fingerprint->can_change_status() ) {
						wp_die( esc_html__( 'This device is no longer modifiable, please contact a site administrator.' ) );
					}

					wp_die( esc_html__( 'Failed to block the new device. Please contact a site administrator.', 'it-l10n-ithemes-security-pro' ) );
				}

				$url = $this->get_reset_pass_url( $user );
				wp_safe_redirect( $url );
				die;
		}
	}

	/**
	 * Get the URL to reset your password.
	 *
	 * @param WP_User $user
	 *
	 * @return string
	 */
	private function get_reset_pass_url( $user ) {
		return add_query_arg( array(
			'action'                 => 'rp',
			'key'                    => get_password_reset_key( $user ),
			'login'                  => rawurlencode( $user->user_login ),
			'itsec_from_fingerprint' => true,
		), wp_login_url() );
	}

	/**
	 * Get a link to wp-login.php that will perform an action on the fingerprint.
	 *
	 * @param ITSEC_Fingerprint $fingerprint Fingerprint to work on.
	 * @param string            $action      One of either 'approve' or 'deny'.
	 *
	 * @return string
	 */
	private function get_fingerprint_action_link( ITSEC_Fingerprint $fingerprint, $action ) {
		return add_query_arg( array(
			'action'     => "itsec-{$action}-fingerprint",
			'itsec_user' => $fingerprint->get_user()->ID,
			'itsec_uuid' => $fingerprint->get_uuid(),
			'itsec_hash' => hash_hmac( ITSEC_Lib::get_hash_algo(), "{$fingerprint->get_uuid()}|{$fingerprint->get_user()->ID}", wp_salt() ),
		), wp_login_url() );
	}

	/**
	 * Log fingerprint creation.
	 *
	 * @param ITSEC_Fingerprint $fingerprint
	 */
	public function log_create( $fingerprint ) {
		ITSEC_Log::add_debug( 'fingerprinting', 'created', array(
			'uuid'   => $fingerprint->get_uuid(),
			'status' => $fingerprint->get_status(),
		), array( 'user_id' => $fingerprint->get_user()->ID ) );
	}

	/**
	 * Log the fingerprint status changing.
	 *
	 * @param ITSEC_Fingerprint $fingerprint
	 * @param string            $suffix
	 * @param string            $context
	 */
	public function log_status( $fingerprint, $suffix, $context = '' ) {

		if ( ITSEC_Fingerprint::S_DENIED === $fingerprint->get_status() ) {
			$method = 'add_action';
		} else {
			$method = 'add_debug';
		}

		$code = "status::{$fingerprint->get_status()}";

		if ( 'override' === $context ) {
			$code .= ',override';
		}

		ITSEC_Log::$method( 'fingerprinting', $code, array(
			'uuid'      => $fingerprint->get_uuid(),
			'scheduled' => doing_action( 'itsec_scheduled_approve-fingerprints' ),
		) );
	}

	/**
	 * Register events on the scheduler.
	 *
	 * @param ITSEC_Scheduler $scheduler
	 */
	public function register_events( $scheduler ) {
		$scheduler->schedule( ITSEC_Scheduler::S_DAILY, 'approve-fingerprints' );
	}

	/**
	 * Auto approve any fingerprints that have been pending auto-approval for at least two days.
	 *
	 * @param ITSEC_Job $job
	 */
	public function approve_pending_fingerprints( $job ) {

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		$data  = $job->get_data();
		$after = isset( $data['after'] ) ? $data['after'] : 0;

		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->base_prefix}itsec_fingerprints WHERE `fingerprint_status` = %s AND `fingerprint_created_at` < %s AND `fingerprint_id` > %d LIMIT 100",
			ITSEC_Fingerprint::S_PENDING_AUTO_APPROVE,
			date( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() - self::PENDING_DAYS * DAY_IN_SECONDS ),
			$after
		) );

		$last_id = 0;

		foreach ( $rows as $row ) {
			if ( $fingerprint = ITSEC_Fingerprint::_hydrate_fingerprint( $row ) ) {
				$last_id = $row->fingerprint_id;
				$fingerprint->auto_approve();
			}
		}

		if ( count( $rows ) < 100 ) {
			return;
		}

		$job->reschedule_in( 300, array( 'after' => $last_id ) );
	}

	/**
	 * Register Fingerprint related notifications.
	 *
	 * @param array $notifications
	 *
	 * @return array
	 */
	public function register_notifications( $notifications ) {
		$notifications['unrecognized-login'] = array(
			'subject_editable' => true,
			'message_editable' => true,
			'schedule'         => ITSEC_Notification_Center::S_NONE,
			'recipient'        => ITSEC_Notification_Center::R_USER,
			'tags'             => array( 'username', 'display_name', 'location', 'ip', 'browser', 'platform', 'site_title', 'date', 'time' ),
			'module'           => 'fingerprinting',
			'optional'         => array( 'default' => false ),
		);

		return $notifications;
	}

	/**
	 * Get the notification strings for the Unrecognized Login.
	 *
	 * @return array
	 */
	public function unrecognized_login_strings() {
		return array(
			'label'       => esc_html__( 'Unrecognized Login', 'it-l10n-ithemes-security-pro' ),
			'description' => esc_html__( 'Users receive a notification if there is a login from an unrecognized device.', 'it-l10n-ithemes-security-pro' ),
			'subject'     => esc_html__( 'New Login from Unrecognized Device', 'it-l10n-ithemes-security-pro' ),
			'message'     => esc_html__( 'On {{ $date }} at {{ $time }} an unrecognized device successfully logged-in to your account. The device was running {{ $browser }} on a {{ $platform }} device. If this was not you click the "Block" button below to lock that user out of your account. We\'ll then have you immediately reset your password.

If this is a login that you recognize, then click the "Approve" button to stop receiving warnings for your new device.', 'it-l10n-ithemes-security-pro' ),
			'tags'        => array(
				'username'     => esc_html__( "The recipient's WordPress username.", 'it-l10n-ithemes-security-pro' ),
				'display_name' => esc_html__( "The recipient's WordPress display name.", 'it-l10n-ithemes-security-pro' ),
				'location'     => esc_html__( 'The approximate location of the login.', 'it-l10n-ithemes-security-pro' ),
				'ip'           => esc_html__( 'The IP address used when logging in.', 'it-l10n-ithemes-security-pro' ),
				'browser'      => esc_html__( 'The web browser used to login.', 'it-l10n-ithemes-security-pro' ),
				'platform'     => sprintf( esc_html__( 'The platform used to login (Apple, Windows, etc%s)', 'it-l10n-ithemes-security-pro' ), '&hellip;' ),
				'date'         => esc_html__( 'The date the login occurred.', 'it-l10n-ithemes-security-pro' ),
				'time'         => esc_html__( 'The time the login occurred.', 'it-l10n-ithemes-security-pro' ),
				'site_title'   => esc_html__( 'The WordPress Site Title. Can be changed under Settings -> General -> Site Title', 'it-l10n-ithemes-security-pro' ),
			)
		);
	}

	/**
	 * Record the Two-Factor provider class used to authenticate.
	 *
	 * @param int    $_
	 * @param string $provider
	 */
	public function record_2fa_provider( $_, $provider ) {
		$this->provider_class_2fa = $provider;
	}

	/**
	 * Add the desired login message above the login form.
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	public function login_message( $message ) {
		if ( $this->login_message ) {
			$message .= '<div class="message"><p>' . $this->login_message . '</p></div>';
		} elseif ( ! empty( $_GET['itsec_from_fingerprint'] ) ) {
			$message = '<div class="message"><p>' . esc_html__( 'Device blocked. For security purposes you must reset your password immediately.', 'it-l10n-ithemes-security-pro' ) . '</p></div>';
		} elseif ( ! empty( $_COOKIE[ self::HJP_COOKIE ] ) ) {
			$message .= '<div class="message"><p>' . esc_html__( 'For security purposes you must login again.', 'it-l10n-ithemes-security-pro' ) . '</p></div>';
		}

		return $message;
	}
}
