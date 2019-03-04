<?php

class ITSEC_Login_Interstitial_Session {

	const META_KEY = '_itsec_login_interstitial_state';

	/** @var int */
	private $id;

	/** @var WP_User */
	private $user;

	/** @var array */
	private $data;

	/**
	 * ITSEC_Lib_Login_Interstitial_State constructor.
	 *
	 * @param WP_User $user
	 * @param int     $id
	 * @param array   $data
	 */
	public function __construct( WP_User $user, $id, $data ) {
		$this->user = $user;
		$this->id   = $id;
		$this->data = $data;
	}

	/**
	 * Set the interstitial that is currently being processed.
	 *
	 * @param string $action
	 *
	 * @return $this
	 */
	public function set_current_interstitial( $action ) {
		$this->data['current'] = $action;
		$this->data['state']   = array();

		return $this;
	}

	/**
	 * Get the current interstitial being processed.
	 *
	 * @return string
	 */
	public function get_current_interstitial() {
		return $this->data['current'];
	}

	/**
	 * Mark that this session completed an interstitial.
	 *
	 * @param string $action
	 *
	 * @return $this
	 */
	public function add_completed_interstitial( $action ) {
		$this->data['completed'][] = $action;

		return $this;
	}

	/**
	 * Get the completed interstitials.
	 *
	 * @return string[]
	 */
	public function get_completed_interstitials() {
		return $this->data['completed'];
	}

	/**
	 * Add an interstitial to display after the user finishes all required interstitials.
	 *
	 * @param string $action
	 *
	 * @return $this
	 */
	public function add_show_after( $action ) {
		$this->data['show_after'][] = $action;

		return $this;
	}

	/**
	 * Get the interstitials to display after the user finishes all required interstitials.
	 *
	 * @return string[]
	 */
	public function get_show_after() {
		return $this->data['show_after'];
	}

	/**
	 * Is remember me enabled.
	 *
	 * @return bool
	 */
	public function is_remember_me() {
		return ! empty( $this->data['remember_me'] );
	}

	/**
	 * Set the remember me value.
	 *
	 * @param bool $remember
	 *
	 * @return $this
	 */
	public function set_remember_me( $remember = true ) {
		$this->data['remember_me'] = $remember;

		return $this;
	}

	/**
	 * Get the redirect URI.
	 *
	 * @return string
	 */
	public function get_redirect_to() {
		return empty( $this->data['redirect_to'] ) ? '' : $this->data['redirect_to'];
	}

	/**
	 * Set the redirect URI.
	 *
	 * @param string $redirect
	 *
	 * @return $this
	 */
	public function set_redirect_to( $redirect ) {
		$this->data['redirect_to'] = $redirect;

		return $this;
	}

	/**
	 * Is this an interim login.
	 *
	 * @return bool
	 */
	public function is_interim_login() {
		return ! empty( $this->data['interim_login'] );
	}

	/**
	 * Set whether this is an interim login.
	 *
	 * @param bool $is_interim
	 *
	 * @return $this
	 */
	public function set_interim_login( $is_interim = true ) {
		$this->data['interim_login'] = $is_interim;

		return $this;
	}

	/**
	 * Get state for the current interstitial.
	 *
	 * @return array
	 */
	public function get_state() {
		return $this->data['state'];
	}

	/**
	 * Set the public state.
	 *
	 * This is only around for the duration of this interstitial.
	 *
	 * @param array $state
	 *
	 * @return $this
	 */
	public function set_state( array $state ) {
		$this->data['state'] = $state;

		return $this;
	}

	/**
	 * Verify the session.
	 *
	 * @param int    $user_id
	 * @param string $signature
	 *
	 * @return true|WP_Error
	 */
	public function verify( $user_id, $signature ) {
		if ( $this->is_expired() ) {
			return new WP_Error( 'itsec-lib-login-interstitial-verify-failed-session-expired', esc_html__( 'Session expired.', 'better-wp-security' ) );
		}

		$signature_verified = $this->verify_signature( $signature );

		if ( is_wp_error( $signature_verified ) ) {
			return $signature_verified;
		}

		if ( true !== $signature_verified ) {
			return new WP_Error( 'itsec-lib-login-interstitial-verify-failed-invalid-signature', esc_html__( 'Invalid signature.', 'better-wp-security' ) );
		}

		if ( ! $user_id || $this->get_user()->ID !== $user_id ) {
			return new WP_Error( 'itsec-lib-login-interstitial-verify-failed-invalid-user', esc_html__( 'Invalid user.', 'better-wp-security' ) );
		}

		return true;
	}

	/**
	 * Verify the session for a given payload.
	 *
	 * @param string $payload
	 * @param int    $user_id
	 * @param string $signature
	 *
	 * @return true|WP_Error
	 */
	public function verify_for_payload( $payload, $user_id, $signature ) {
		if ( $this->is_expired() ) {
			return new WP_Error( 'itsec-lib-login-interstitial-verify-failed-session-expired', esc_html__( 'Session expired.', 'better-wp-security' ) );
		}

		$signature_verified = $this->verify_signature_for_payload( $payload, $signature );

		if ( is_wp_error( $signature_verified ) ) {
			return $signature_verified;
		}

		if ( true !== $signature_verified ) {
			return new WP_Error( 'itsec-lib-login-interstitial-verify-failed-invalid-signature', esc_html__( 'Invalid signature.', 'better-wp-security' ) );
		}

		if ( ! $user_id || $this->get_user()->ID !== $user_id ) {
			return new WP_Error( 'itsec-lib-login-interstitial-verify-failed-invalid-user', esc_html__( 'Invalid user.', 'better-wp-security' ) );
		}

		return true;
	}

	/**
	 * Is the session expired.
	 *
	 * @return bool
	 */
	public function is_expired() {
		return $this->data['created_at'] + HOUR_IN_SECONDS < ITSEC_Core::get_current_time_gmt();
	}

	/**
	 * Verify the signature.
	 *
	 * @param string $actual
	 *
	 * @return bool|WP_Error
	 */
	public function verify_signature( $actual ) {
		$expected = $this->get_signature();

		if ( is_wp_error( $expected ) ) {
			return $expected;
		}

		return hash_equals( $expected, $actual );
	}

	/**
	 * Get the signature for the session state.
	 *
	 * @return string|WP_Error
	 */
	public function get_signature() {
		$to_hash = sprintf(
			'%s|%s|%s|%s',
			$this->get_user()->ID,
			$this->get_id(),
			$this->data['created_at'],
			$this->data['uuid']
		);

		$hash = hash_hmac( 'sha1', $to_hash, wp_salt() );

		if ( ! $hash ) {
			return new WP_Error( 'itsec-lib-login-interstitial-signature-failed', esc_html__( 'Could not calculate signature.', 'better-wp-security' ) );
		}

		return $hash;
	}

	/**
	 * Verify the signature for a given async action.
	 *
	 * @param string $payload
	 * @param string $actual
	 *
	 * @return bool|WP_Error
	 */
	public function verify_signature_for_payload( $payload, $actual ) {
		$expected = $this->get_signature_for_payload( $payload );

		if ( is_wp_error( $expected ) ) {
			return $expected;
		}

		return hash_equals( $expected, $actual );
	}

	/**
	 * Get the signature for a payload.
	 *
	 * @param string $payload
	 *
	 * @return string|WP_Error
	 */
	public function get_signature_for_payload( $payload ) {
		$to_hash = sprintf(
			'%s|%s|%s|%s|%s',
			$this->get_user()->ID,
			$this->get_id(),
			$this->data['created_at'],
			$this->data['uuid'],
			$payload
		);

		$hash = hash_hmac( 'sha1', $to_hash, wp_salt() );

		if ( ! $hash ) {
			return new WP_Error( 'itsec-lib-login-interstitial-signature-failed', esc_html__( 'Could not calculate signature.', 'better-wp-security' ) );
		}

		return $hash;
	}

	/**
	 * Was the given interstitial completed.
	 *
	 * @param string $interstitial
	 *
	 * @return bool
	 */
	public function is_interstitial_completed( $interstitial ) {
		return in_array( $interstitial, $this->get_completed_interstitials(), true );
	}

	/**
	 * Is the given interstitial forced.
	 *
	 * @param string $interstitial
	 *
	 * @return bool
	 */
	public function is_interstitial_requested( $interstitial ) {
		return in_array( $interstitial, $this->get_show_after(), true );
	}

	/**
	 * Is the current interstitial forced to display.
	 *
	 * @return bool
	 */
	public function is_current_requested() {
		return $this->is_interstitial_requested( $this->get_current_interstitial() );
	}

	/**
	 * Get the session ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the session's user.
	 *
	 * @return WP_User
	 */
	public function get_user() {
		return $this->user;
	}

	/**
	 * Save the session.
	 *
	 * @return bool
	 */
	public function save() {
		return update_metadata_by_mid( 'user', $this->get_id(), $this->data, self::META_KEY );
	}

	/**
	 * Delete the session state.
	 *
	 * @return bool
	 */
	public function delete() {
		$deleted = delete_metadata_by_mid( 'user', $this->get_id() );

		foreach ( get_user_meta( $this->get_user()->ID, self::META_KEY ) as $entry ) {
			if ( ! isset( $entry['created_at'] ) || $entry['created_at'] + HOUR_IN_SECONDS < ITSEC_Core::get_current_time_gmt() ) {
				delete_user_meta( $this->get_user()->ID, self::META_KEY, $entry );
			}
		}

		return $deleted;
	}

	/**
	 * Create a new state session.
	 *
	 * @param WP_User $user    The user to create the session for.
	 * @param string  $current The current interstitial.
	 *
	 * @return ITSEC_Login_Interstitial_Session|WP_Error
	 */
	public static function create( WP_User $user, $current = '' ) {

		$data = array(
			'uuid'          => wp_generate_uuid4(),
			'current'       => $current,
			'completed'     => array(),
			'created_at'    => ITSEC_Core::get_current_time_gmt(),
			'show_after'    => array(),
			'redirect_to'   => '',
			'remember_me'   => false,
			'interim_login' => false,
			'state'         => array(),
		);

		if ( ! $mid = add_user_meta( $user->ID, self::META_KEY, $data ) ) {
			return new WP_Error( 'itsec-lib-login-interstitial-save-failed', esc_html__( 'Failed to create interstitial state.', 'better-wp-security' ) );
		}

		return new self( $user, $mid, $data );
	}

	/**
	 * Get a state session.
	 *
	 * @param int $id
	 *
	 * @return ITSEC_Login_Interstitial_Session|WP_Error
	 */
	public static function get( $id ) {

		$row = get_metadata_by_mid( 'user', $id );

		if (
			! $row ||
			$row->meta_key !== self::META_KEY ||
			! self::validate_meta( $row->meta_value ) ||
			! $user = get_userdata( $row->user_id )
		) {
			return new WP_Error( 'itsec-lib-login-interstitial-not-found', esc_html__( 'Interstitial state not found.', 'better-wp-security' ) );
		}

		return new self( $user, $id, $row->meta_value );
	}

	/**
	 * Get all interstitials for a user.
	 *
	 * @param WP_User $user
	 *
	 * @return ITSEC_Login_Interstitial_Session[]
	 */
	public static function get_all( WP_User $user ) {

		global $wpdb;

		$mids = $wpdb->get_col( $wpdb->prepare(
			"SELECT `umeta_id` FROM {$wpdb->usermeta} WHERE `meta_key` = %s AND `user_id` = %d",
			self::META_KEY,
			$user->ID
		) );

		$sessions = array();

		foreach ( $mids as $meta_id ) {
			if ( ! is_wp_error( $session = self::get( $meta_id ) ) ) {
				$sessions[] = $session;
			}
		}

		return $sessions;
	}

	/**
	 * Validate the meta value is valid.
	 *
	 * @param mixed $meta_value
	 *
	 * @return bool
	 */
	private static function validate_meta( $meta_value ) {
		return is_array( $meta_value ) && isset( $meta_value['uuid'], $meta_value['created_at'], $meta_value['completed'], $meta_value['current'], $meta_value['show_after'] );
	}
}
