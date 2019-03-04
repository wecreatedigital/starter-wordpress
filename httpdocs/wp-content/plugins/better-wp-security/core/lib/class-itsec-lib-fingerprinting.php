<?php

require_once( dirname( __FILE__ ) . '/fingerprinting/class-itsec-fingerprint.php' );
require_once( dirname( __FILE__ ) . '/fingerprinting/class-itsec-fingerprint-comparison.php' );
require_once( dirname( __FILE__ ) . '/fingerprinting/class-itsec-fingerprint-value.php' );
require_once( dirname( __FILE__ ) . '/fingerprinting/interface-itsec-fingerprint-source.php' );

class ITSEC_Lib_Fingerprinting {

	/** @var ITSEC_Fingerprint_Source[] */
	private static $sources;

	/** @var ITSEC_Fingerprint */
	private static $_current_fingerprint = false;

	/**
	 * Check if the global fingerprint has a matching fingerprint.
	 *
	 * @param WP_User|int|string|false $user WP User instance, User ID, Username, or false for current user.
	 *
	 * @return ITSEC_Fingerprint_Comparison|null Null if user has no fingerprints stored.
	 */
	public static function check_global_state_fingerprint_for_match( $user = false ) {

		$fingerprint = self::calculate_fingerprint_from_global_state( $user );

		return self::check_for_match( $fingerprint );
	}

	/**
	 * Calculate the current fingerprint from global state.
	 *
	 * @param WP_User|int|string|false $user WP User instance, User ID, Username, or false for current user.
	 *
	 * @return ITSEC_Fingerprint
	 */
	public static function calculate_fingerprint_from_global_state( $user = false ) {

		$values = array();

		foreach ( self::get_sources() as $source ) {
			if ( $value = $source->calculate_value_from_global_state() ) {
				$values[] = $value;
			}
		}

		return new ITSEC_Fingerprint(
			ITSEC_Lib::get_user( $user ),
			new DateTime( '@' . ITSEC_Core::get_current_time_gmt(), new DateTimeZone( 'UTC' ) ),
			$values
		);
	}

	/**
	 * Get the current *stored* fingerprint for this page load.
	 *
	 * This function is cached for the duration of the request.
	 *
	 * @return ITSEC_Fingerprint|null
	 */
	public static function get_current_fingerprint() {

		if ( ! self::applies_to_user() ) {
			return null;
		}

		if ( false === self::$_current_fingerprint ) {
			self::$_current_fingerprint = self::get_stored_fingerprint( self::calculate_fingerprint_from_global_state() );
		}

		return self::$_current_fingerprint;
	}

	/**
	 * Check if their is an approved or auto-approved fingerprint matching the current fingerprint for this page load.
	 *
	 * @return bool
	 */
	public static function is_current_fingerprint_safe() {

		$fingerprint = self::get_current_fingerprint();

		return $fingerprint && ( $fingerprint->is_approved() || $fingerprint->is_auto_approved() );
	}

	/**
	 * Get the matching stored fingerprint for a fingerprint built from global state.
	 *
	 * @param ITSEC_Fingerprint $global_state_fingerprint
	 *
	 * @return ITSEC_Fingerprint|null
	 */
	public static function get_stored_fingerprint( ITSEC_Fingerprint $global_state_fingerprint ) {

		if ( ! $global_state_fingerprint->calculate_hash() ) {
			return null;
		}

		return ITSEC_Fingerprint::get_by_hash( $global_state_fingerprint->get_user(), $global_state_fingerprint->calculate_hash() );
	}

	/**
	 * Check if there is a match for the given fingerprint.
	 *
	 * @param ITSEC_Fingerprint $maybe_fingerprint
	 *
	 * @return ITSEC_Fingerprint_Comparison|null Null if user has no safe fingerprints.
	 */
	public static function check_for_match( ITSEC_Fingerprint $maybe_fingerprint ) {

		$fingerprints = self::get_user_fingerprints( $maybe_fingerprint->get_user(), array(
			'status' => array( ITSEC_Fingerprint::S_AUTO_APPROVED, ITSEC_Fingerprint::S_APPROVED ),
		) );

		/** @var ITSEC_Fingerprint_Comparison|null $max */
		$max = null;

		foreach ( $fingerprints as $fingerprint ) {
			$comparison = $fingerprint->compare( $maybe_fingerprint );

			if ( ! $max || $comparison->get_match_percent() > $max->get_match_percent() ) {
				$max = $comparison;
			}
		}

		return $max;
	}

	/**
	 * Get a user's fingerprints.
	 *
	 * @param WP_User|int|string|false $user WP User instance, User ID, Username, or false for current user.
	 * @param array                    $args Additional args.
	 *
	 * @return ITSEC_Fingerprint[]
	 */
	public static function get_user_fingerprints( $user = false, $args = array() ) {

		if ( ! $user = ITSEC_Lib::get_user( $user ) ) {
			return array();
		}

		if ( ! is_array( $args ) ) {
			return array();
		}

		return ITSEC_Fingerprint::get_all_for_user( $user, $args );
	}

	/**
	 * Whether fingerprinting applies to the given user.
	 *
	 * @param WP_User|int|string|false $user
	 *
	 * @return bool
	 */
	public static function applies_to_user( $user = false ) {

		if ( ! $role = ITSEC_Modules::get_setting( 'fingerprinting', 'role' ) ) {
			return false;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-canonical-roles.php' );

		$had_filter = remove_filter( 'user_has_cap', array( 'ITSEC_Fingerprinting', 'restrict_capabilities' ), 10 );
		$applies    = ITSEC_Lib_Canonical_Roles::is_user_at_least( $role, $user );

		if ( $had_filter ) {
			add_filter( 'user_has_cap', array( 'ITSEC_Fingerprinting', 'restrict_capabilities' ), 10, 4 );
		}

		return $applies;
	}

	/**
	 * Get the fingerprint sources.
	 *
	 * @internal
	 *
	 * @return ITSEC_Fingerprint_Source[]
	 */
	public static function get_sources() {
		if ( ! self::$sources ) {
			$sources = array();

			/**
			 * Filter the available fingerprint sources.
			 *
			 * @param ITSEC_Fingerprint_Source[] $sources
			 */
			$sources = apply_filters( 'itsec_fingerprint_sources', $sources );

			foreach ( $sources as $source ) {
				self::$sources[ $source->get_slug() ] = $source;
			}
		}

		return self::$sources;
	}
}
