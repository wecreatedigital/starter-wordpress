<?php

/**
 * Class ITSEC_Geolocator_MaxMind_DB
 */
final class ITSEC_Geolocator_MaxMind_DB implements ITSEC_Geolocator {

	// https://dev.maxmind.com/geoip/geoip2/geolite2/
	const URL = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz';
	const NAME = 'GeoLite2-City.mmdb';

	/** @var ITSEC_MaxMind_DB_Reader */
	private static $reader;

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {

		try {
			$decoder = self::get_reader();
			$data    = $decoder->get( $ip );
		} catch ( \Exception $e ) {
			return new WP_Error( 'itsec-geolocate-maxmind-instantiation-failed', __( 'MaxMind Db Exception', 'it-l10n-ithemes-security-pro' ), compact( 'e', 'ip' ) );
		}

		$label = $data['country']['names']['en'];

		if ( ! empty( $data['city']['names']['en'] ) ) {
			/* translators: 1. City Name, 2. Country Name */
			$label = sprintf( _x( '%1$s, %2$s', 'Location', 'it-l10n-ithemes-security-pro' ), $data['city']['names']['en'], $label );
		}

		return array(
			'lat'    => (float) $data['location']['latitude'],
			'long'   => (float) $data['location']['longitude'],
			'label'  => esc_html( $label ),
			/* translators: 1. Opening Link, 2. Closing Link */
			'credit' => sprintf( esc_html__( 'Location data provided by Geolite 2 from %1$sMaxMind%2$s', 'it-l10n-ithemes-security-pro' ), '<a href="https://www.maxmind.com">', '</a>' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return file_exists( self::get_db_file() );
	}

	/**
	 * Download the MaxMind db.
	 *
	 * @return WP_Error|true
	 */
	public static function download() {

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-directory.php' );

		if ( ! function_exists( 'download_url' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		if ( ! function_exists( 'download_url' ) ) {
			return new WP_Error( 'itsec-maxmind-db-download-download_url', esc_html__( 'The download_url function is undefined.', 'it-l10n-ithemes-security-pro' ) );
		}

		$temp_file = download_url( self::URL );

		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		$path = self::get_db_path();

		$extract_to = trailingslashit( ITSEC_Core::get_storage_dir( 'maxmind' ) ) . 'temp/';

		if ( is_dir( $extract_to ) ) {
			ITSEC_Lib_Directory::remove( $extract_to ); // We want to make sure this temp directory is clean.
		}

		ITSEC_Lib_Directory::create( $extract_to );

		if ( class_exists( 'PharData' ) ) {
			try {
				$data = new PharData( $temp_file );
				$data->extractTo( $extract_to );
				$unzipped = $data->getBasename();
			} catch ( Exception $e ) {
				if ( isset( $data ) ) {
					return new WP_Error( 'itsec-phar-data-constructor', esc_html( sprintf( __( 'PharData constructor failed: %s', 'it-l10n-ithemes-security-pro' ), $e->getMessage() ) ) );
				}

				return new WP_Error( 'itsec-phar-data-extract', esc_html( sprintf( __( 'PharData extraction failed: %s', 'it-l10n-ithemes-security-pro' ), $e->getMessage() ) ) );
			}

			unset( $data );
		} elseif ( ITSEC_Lib::is_func_allowed( 'shell_exec' ) ) {

			// Extract, gzip, verbose to get list of files, don't overwrite
			$o = @shell_exec( 'tar -xzvkf ' . escapeshellarg( $temp_file ) . ' -C ' . escapeshellarg( $extract_to ) . ' 2>&1' );

			if ( ! $o ) {
				return new WP_Error( 'itsec-maxmind-db-download-shell_exec', esc_html__( 'Your server cannot extract the MaxMind DB.', 'it-l10n-ithemes-security-pro' ) );
			}

			$extracted = explode( PHP_EOL, $o );

			list( , $unzipped ) = explode( ' ', untrailingslashit( $extracted[0] ) );
		} else {
			return new WP_Error( 'itsec-maxmind-db-download-phar_data', esc_html__( 'Your server cannot extract the MaxMind DB.', 'it-l10n-ithemes-security-pro' ) );
		}

		@unlink( $temp_file );

		foreach ( scandir( $extract_to . $unzipped, SCANDIR_SORT_NONE ) as $move ) {
			if ( '.' !== $move && '..' !== $move ) {
				if ( file_exists( $path . $move ) ) {
					@unlink( $path . $move );
				}

				rename( $extract_to . $unzipped . '/' . $move, $path . $move );
			}
		}

		ITSEC_Lib_Directory::remove( $extract_to );

		if ( ! file_exists( $path . self::NAME ) ) {
			return new WP_Error( 'itsec-maxmind-db-download-cannot-rename', esc_html__( 'Failed to rename unzipped files.', 'it-l10n-ithemes-security-pro' ) );
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-file.php' );
		ITSEC_Lib_File::chmod( self::get_db_file(), ITSEC_Lib_File::get_default_permissions() );

		return true;
	}

	/**
	 * Get the DB Reader.
	 *
	 * @return bool|ITSEC_MaxMind_DB_Reader
	 * @throws ITSEC_MaxMind_DB_InvalidDatabaseException
	 */
	private static function get_reader() {
		if ( ! self::$reader ) {
			require_once( dirname( dirname( __FILE__ ) ) . '/maxmind/Decoder.php' );
			require_once( dirname( dirname( __FILE__ ) ) . '/maxmind/InvalidDatabaseException.php' );
			require_once( dirname( dirname( __FILE__ ) ) . '/maxmind/Metadata.php' );
			require_once( dirname( dirname( __FILE__ ) ) . '/maxmind/Reader.php' );
			require_once( dirname( dirname( __FILE__ ) ) . '/maxmind/Util.php' );

			self::$reader = new ITSEC_MaxMind_DB_Reader( self::get_db_file() );
		}

		return self::$reader;
	}

	/**
	 * Get the path to the MaxMind database.
	 *
	 * @return string With a trailing slash.
	 */
	public static function get_db_path() {

		$parent = trailingslashit( ITSEC_Core::get_storage_dir( 'maxmind' ) );

		$dirs = glob( $parent . 'db-*', GLOB_ONLYDIR );

		if ( $dirs ) {
			return trailingslashit( $dirs[0] );
		}

		$rdm = wp_generate_password( 32, false, false );
		$dir = "{$parent}db-{$rdm}";

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-directory.php' );
		ITSEC_Lib_Directory::create( $dir );

		return trailingslashit( $dir );
	}

	private static function get_db_file() {
		return self::get_db_path() . self::NAME;
	}

	/**
	 * Close the DB reader.
	 */
	private static function close() {
		if ( self::$reader ) {
			try {
				self::$reader->close();
			} catch ( \Exception $e ) {
			}

			self::$reader = null;
		}
	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		self::close();
	}
}