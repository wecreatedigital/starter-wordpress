<?php

/**
 * Perform import and exports.
 */
class ITSEC_Import_Export_Command extends WP_CLI_Command {

	/**
	 * Import settings from an settings export.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the export.json file or the export.zip file.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function import( $args, $assoc_args ) {

		list( $file ) = $args;

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( __( 'Invalid settings file. File does not exists.', 'it-l10n-ithemes-security-pro' ) );
		}

		$type = function_exists( 'mime_content_type' ) ? mime_content_type( $file ) : '';

		ITSEC_Modules::load_module_file( 'importer.php', 'import-export' );

		$imported = ITSEC_Import_Export_Importer::import_from_file_path( $file, $type );

		if ( is_wp_error( $imported ) ) {
			WP_CLI::error( $imported );
		}

		WP_CLI::success( __( 'Import complete', 'it-l10n-ithemes-security-pro' ) );
	}

	/**
	 * Perform a settings export.
	 *
	 * ## OPTIONS
	 *
	 * --email=<email>
	 * : Email address to send the export file to.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function export( $args, $assoc_args ) {

		if ( empty( $assoc_args['email'] ) ) {
			WP_CLI::error( __( 'Email address is required.' ) );
		}

		ITSEC_Modules::load_module_file( 'exporter.php', 'import-export' );

		$export = ITSEC_Import_Export_Exporter::create( $assoc_args['email'] );

		if ( is_wp_error( $export ) ) {
			WP_CLI::error( $export );
		}

		WP_CLI::success( __( 'Export complete', 'it-l10n-ithemes-security-pro' ) );
	}
}

WP_CLI::add_command( 'itsec import-export', 'ITSEC_Import_Export_Command' );