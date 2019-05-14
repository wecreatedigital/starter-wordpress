<?php

/**
 * Class ITSEC_Grading_System_Validator
 */
class ITSEC_Grading_System_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'grade-report';
	}

	protected function sanitize_settings() {
		$this->sanitize_setting( array_map( 'strval', array_keys( $this->get_users() ) ), 'disabled_users', __( 'Disabled Users', 'it-l10n-ithemes-security-pro' ) );
	}

	protected function validate_settings() {
		// Only validate settings if the data was successfully sanitized.
		if ( ! $this->can_save() ) {
			return;
		}

		if ( in_array( get_current_user_id(), $this->settings['disabled_users'], false ) ) {

			$this->add_error( new WP_Error( "itsec-validator-{$this->get_id()}-cannot-disable-for-self", esc_html__( 'You cannot disable Grade Report for your user.', 'LIOn' ) ) );

			if ( ITSEC_Core::is_interactive() ) {
				$this->set_can_save( false );
			}
		}
	}

	public function get_users() {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-canonical-roles.php' );

		$users  = ITSEC_Lib_Canonical_Roles::get_users_with_canonical_role( 'administrator' );
		$mapped = array();

		foreach ( $users as $user ) {
			$mapped[ $user->ID ] = $user->user_login;
		}

		return $mapped;
	}
}

ITSEC_Modules::register_validator( new ITSEC_Grading_System_Validator() );