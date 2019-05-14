<?php

/**
 * Class ITSEC_Dashboard_Validator
 */
class ITSEC_Dashboard_Validator extends ITSEC_Validator {

	public function get_id() {
		return 'dashboard';
	}

	protected function validate_settings() {

		if ( $this->settings['disabled_users'] !== $this->previous_settings['disabled_users'] && ! current_user_can( 'itsec_create_dashboards' ) ) {
			$this->add_error( new WP_Error( 'itsec-validator-dashboard-permissions-error', __( "You don't have permission to edit dashboard settings.", 'it-l10n-ithemes-security-pro' ) ) );
			$this->set_can_save( false );

			return;
		}

		if ( in_array( get_current_user_id(), $this->settings['disabled_users'], false ) ) {

			$this->add_error( new WP_Error( "itsec-validator-{$this->get_id()}-cannot-disable-for-self", esc_html__( 'You cannot disable creating dashboards for your user.', 'LIOn' ) ) );

			if ( ITSEC_Core::is_interactive() ) {
				$this->set_can_save( false );
			}
		}

	}

	protected function sanitize_settings() {
		$this->set_previous_if_empty( array( 'migrated' ) );
		$this->sanitize_setting( 'bool', 'migrated', esc_html__( 'Is the event migration complete.', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( array_map( 'strval', array_keys( $this->get_users() ) ), 'disabled_users', __( 'Disabled Users', 'it-l10n-ithemes-security-pro' ) );
	}

	public function get_users() {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-canonical-roles.php' );

		$users  = ITSEC_Lib_Canonical_Roles::get_users_with_canonical_role( 'administrator' );
		$mapped = array();

		foreach ( $users as $user ) {
			if ( user_can( $user, ITSEC_Core::get_required_cap() ) ) {
				$mapped[ $user->ID ] = $user->user_login;
			}
		}

		return $mapped;
	}
}

ITSEC_Modules::register_validator( new ITSEC_Dashboard_Validator() );
