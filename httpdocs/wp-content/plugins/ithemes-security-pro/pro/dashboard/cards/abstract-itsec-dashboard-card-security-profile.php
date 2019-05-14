<?php

/**
 * Class ITSEC_Dashboard_Card_Security_Profile
 */
abstract class ITSEC_Dashboard_Card_Security_Profile extends ITSEC_Dashboard_Card {

	/**
	 * Build the UI data for a user.
	 *
	 * @param WP_User $user
	 *
	 * @return array
	 */
	protected function build_user_data( $user ) {
		$last_seen             = ITSEC_Lib_User_Activity::get_instance()->get_last_seen( $user->ID );
		$password_last_changed = ITSEC_Lib_Password_Requirements::password_last_changed( $user );

		return array(
			'id'                    => $user->ID,
			'name'                  => ! empty( $user->display_name ) ? $user->display_name : $user->user_login,
			'avatar'                => get_avatar_url( $user ),
			'role'                  => $user->roles ? translate_user_role( wp_roles()->role_names[ $user->roles[0] ] ) : '',
			'two_factor'            => $this->get_two_factor( $user ),
			'last_active'           => ! $last_seen ? array() : array(
				'time' => ITSEC_Lib::to_rest_date( (int) $last_seen ),
				/* translators: 1. Human Time Diff */
				'diff' => ITSEC_Core::get_current_time_gmt() - HOUR_IN_SECONDS < $last_seen ? sprintf( __( 'Within %s', 'it-l10n-ithemes-security-pro' ), human_time_diff( $last_seen ) ) : sprintf( __( '%s ago', 'it-l10n-ithemes-security-pro' ), human_time_diff( $last_seen ) ),
			),
			'password_strength'     => $this->get_password_strength( $user ),
			'password_last_changed' => array(
				'time' => ITSEC_Lib::to_rest_date( $password_last_changed ),
				/* translators: 1. Human Time Diff */
				'diff' => sprintf( __( '%s old', 'it-l10n-ithemes-security-pro' ), human_time_diff( $password_last_changed ) ),
			),
		);
	}

	/**
	 * Get the two-factor configuration for a user.
	 *
	 * @param WP_User $user
	 *
	 * @return string
	 */
	private function get_two_factor( $user ) {
		if ( ! class_exists( 'ITSEC_Two_Factor' ) ) {
			return 'not-enabled';
		}

		if ( ITSEC_Two_Factor::get_instance()->get_available_providers_for_user( $user, false ) ) {
			return 'enabled';
		}

		if ( ITSEC_Two_Factor::get_instance()->get_available_providers_for_user( $user, true ) ) {
			return 'enforced-not-configured';
		}

		return 'not-enabled';
	}

	/**
	 * Get the password strength for a user.
	 *
	 * @param WP_User $user
	 *
	 * @return int
	 */
	private function get_password_strength( $user ) {
		$password_strength = get_user_meta( $user->ID, 'itsec-password-strength', true );

		// If the password strength wasn't retrieved or isn't 0-4, set it to -1 for "Unknown"
		if ( false === $password_strength || '' === $password_strength || ! in_array( $password_strength, range( 0, 4 ) ) ) {
			$password_strength = - 1;
		}

		return (int) $password_strength;
	}
}
