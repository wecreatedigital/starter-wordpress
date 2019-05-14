<?php
/**
 * Load CLI Commands.
 */

/**
 * Load WP-CLI Commands.
 */
function itsec_load_wp_cli() {

	require_once( dirname( __FILE__ ) . '/class-itsec-wp-cli-command-itsec.php' );
	WP_CLI::add_command( 'itsec', 'ITSEC_WP_CLI_Command_ITSEC' );

	require_once( dirname( __FILE__ ) . '/geo.php' );
	require_once( dirname( __FILE__ ) . '/map.php' );
	require_once( dirname( __FILE__ ) . '/scheduler.php' );
	require_once( dirname( __FILE__ ) . '/modules.php' );
	require_once( dirname( __FILE__ ) . '/settings.php' );
	require_once( dirname( __FILE__ ) . '/lockout.php' );

	$always_active = ITSEC_Modules::get_always_active_modules();

	foreach ( $always_active as $module ) {
		if ( file_exists( dirname( __FILE__ ) . "/{$module}.php" ) ) {
			require_once( dirname( __FILE__ ) . "/{$module}.php" );
		}
	}

	$active = ITSEC_Modules::get_active_modules();

	foreach ( $active as $module ) {
		if ( file_exists( dirname( __FILE__ ) . "/{$module}.php" ) ) {
			require_once( dirname( __FILE__ ) . "/{$module}.php" );
		}
	}
}

add_action( 'itsec_initialized', 'itsec_load_wp_cli' );
