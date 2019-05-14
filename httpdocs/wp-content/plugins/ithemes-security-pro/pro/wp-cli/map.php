<?php

/**
 * Access the static map image API.
 */
class ITSEC_Map_Command extends WP_CLI_Command {

	/**
	 * Get a map of a Lat/Long coordinate.
	 *
	 * ## OPTIONS
	 *
	 * --lat=<lat>
	 * : The latitude coordinate.
	 *
	 * --long=<long>
	 * : The longitude coordinate.
	 *
	 * [--<field>=<value>]
	 * : Additional configuration options.
	 */
	public function get( $args, $assoc_args ) {

		$map = ITSEC_Lib_Static_Map_API::get_map( $assoc_args );

		if ( is_wp_error( $map ) ) {
			WP_CLI::error( $map );
		}

		WP_CLI::success( 'Retrieved Map: ' . $map );
	}
}

WP_CLI::add_command( 'itsec map', 'ITSEC_Map_Command', array(
	'before_invoke' => function () {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-static-map-api.php' );
	}
) );