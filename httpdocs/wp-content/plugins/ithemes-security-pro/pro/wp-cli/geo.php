<?php

/**
 * Access geolocation functionality.
 */
class ITSEC_Geo_Command extends WP_CLI_Command {

	/**
	 * Geolocate an IP address.
	 *
	 * ## OPTIONS
	 *
	 * <ip>
	 * : The IP address to locate.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 */
	public function locate( $args, $assoc_args ) {

		list( $ip ) = $args;

		$located = ITSEC_Lib_Geolocation::geolocate( $ip );

		if ( is_wp_error( $located ) ) {
			WP_CLI::error( $located );
		}

		$assoc_args = wp_parse_args( $assoc_args, array(
			'fields' => array( 'lat', 'long', 'label', 'credit' ),
			'format' => 'table'
		) );

		$formatter = new WP_CLI\Formatter( $assoc_args );
		$formatter->display_item( $located );
	}

	/**
	 * Download the MaxMind Database.
	 *
	 * This command may take a long time.
	 *
	 * @subcommand maxmind-download
	 */
	public function maxmind_download() {

		$res = ITSEC_Geolocator_MaxMind_DB::download();

		if ( is_wp_error( $res ) ) {
			WP_CLI::error( $res );
		}

		WP_CLI::success( 'Download success.' );
	}
}

WP_CLI::add_command( 'itsec geo', 'ITSEC_Geo_Command', array(
	'before_invoke' => function () {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-geolocation.php' );
	}
) );