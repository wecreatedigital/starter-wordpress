<?php

/**
 * Perform a Security Check scan.
 */
class ITSEC_Security_Check_Command extends WP_CLI_Command {

	/**
	 * Perform a Security Check scan.
	 *
	 * ## OPTIONS
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
	public function __invoke( $args, $assoc_args ) {

		ITSEC_Modules::load_module_file( 'scanner.php', 'security-check' );

		ITSEC_Security_Check_Scanner::run_scan();

		ITSEC_Modules::set_setting( 'global', 'show_security_check', false );

		$results = ITSEC_Security_Check_Scanner::get_results();
		$messages = array();

		foreach ( $results['sections'] as $slug => $args ) {
			foreach ( $args['entries'] as $entry ) {
				if ( 'text' === $entry['type'] ) {
					$messages[] = array(
						'section' => $slug,
						'message' => $entry['value']
					);
				}
			}
		}

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		\WP_CLI\Utils\format_items( $format, $messages, array( 'section', 'message' ) );
	}
}

WP_CLI::add_command( 'itsec security-check', 'ITSEC_Security_Check_Command' );