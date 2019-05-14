<?php

/**
 * Manage the Brute Force Network.
 */
class ITSEC_Network_Brute_Force_Command extends WP_CLI_Command {

	/**
	 * Enroll in the Brute Force Network.
	 *
	 * ## OPTIONS
	 *
	 * <email>
	 * : The email address to use when enrolling.
	 *
	 * [--opt-in]
	 * : Whether to receive email updates about WordPress Security from iThemes.
	 */
	public function enroll( $args, $assoc_args ) {

		$settings = ITSEC_Modules::get_settings( 'network-brute-force' );

		if ( ! empty( $settings['api_key'] ) && ! empty( $settings['api_secret'] ) ) {
			WP_CLI::warning( 'Website is already enrolled in the Brute Force Network.' );

			return;
		}

		$opt_in = \WP_CLI\Utils\get_flag_value( $assoc_args, 'opt-in' );

		if ( null === $opt_in ) {
			$opt_in = \cli\confirm( 'Opt-in to receive email updates about WordPress Security from iThemes.' );
		}

		$settings['email']         = $args[0];
		$settings['updates_optin'] = $opt_in;

		$saved = ITSEC_Modules::set_settings( 'network-brute-force', $settings );

		if ( is_wp_error( $saved ) ) {
			WP_CLI::error( $saved );
		}

		if ( ! empty( $saved['errors'] ) ) {
			foreach ( $saved['errors'] as $error ) {
				WP_CLI::error( $error, false );
			}
		}

		if ( empty( $saved['saved'] ) ) {
			WP_CLI::error( 'Failed to enroll in the Brute Force Network.' );
		}

		WP_CLI::success( 'Enrolled in the Brute Force Network.' );
	}
}

WP_CLI::add_command( 'itsec network-brute-force', 'ITSEC_Network_Brute_Force_Command' );