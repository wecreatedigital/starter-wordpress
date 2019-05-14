<?php

/**
 * Configure dashboards.
 */
class ITSEC_Dashboard_Command extends WP_CLI_Command {

	/**
	 * Migrate data to the events table.
	 */
	public function migrate( $args, $assoc_args ) {

		$results = ITSEC_Dashboard_Util::migrate();

		if ( is_wp_error( $results ) ) {
			WP_CLI::error( $results );
		}

		$formatted = array();

		foreach ( $results as $event => $result ) {
			$formatted[] = array(
				'event'  => $event,
				'result' => is_wp_error( $result ) ? $result->get_error_message() : $result,
			);
		}

		\WP_CLI\Utils\format_items( 'table', $formatted, array( 'event', 'result' ) );
	}

	/**
	 * List dashboards.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user ID to fetch dashboards for.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		list ( $user_id ) = $args;

		$user = get_userdata( $user_id );

		if ( ! $user || ! $user->exists() ) {
			WP_CLI::error( 'User not found.' );
		}

		$assoc_args = wp_parse_args( $assoc_args, array(
			'fields' => array( 'id', 'created_by', 'created_at', 'label', 'sharing', 'primary' ),
			'format' => 'table'
		) );

		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'GET', '/ithemes-security/v1/dashboards' );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$dashboards = rest_do_request( $request )->get_data();

		if ( 'ids' === $assoc_args['format'] ) {
			echo implode( ' ', wp_list_pluck( $dashboards, 'id' ) );
		} elseif ( 'count' === $assoc_args['format'] ) {
			echo count( $dashboards );
		} else {
			$formatter = new \WP_CLI\Formatter( $assoc_args );
			$formatter->display_items( array_map( array( $this, 'format_dashboard' ), $dashboards ) );
		}

		wp_set_current_user( 0 );
	}

	/**
	 * Export dashboard cards.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The dashboard ID to export.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: json
	 * options:
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * @subcommand export-cards
	 */
	public function export_cards( $args, $assoc_args ) {

		list( $dashboard_id ) = $args;

		if ( get_post_type( $dashboard_id ) !== ITSEC_Dashboard::CPT_DASHBOARD ) {
			WP_CLI::error( 'No dashboard found with that id.' );
		}
		$assoc_args = wp_parse_args( $assoc_args, array(
			'fields' => array( 'type', 'size', 'position' ),
			'format' => 'json'
		) );

		$formatter = new \WP_CLI\Formatter( $assoc_args );
		$formatter->display_items( ITSEC_Dashboard_Util::export_cards( $dashboard_id ) );
	}

	/**
	 * Import dashboard cards from a JSON export.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The dashboard ID to import the cards into.
	 *
	 * [<file>]
	 * : Source file of the cards. If omitted, will read from STDIN.
	 *
	 * [--clear]
	 * : Whether to remove all cards from the dashboard first.
	 * ---
	 * default: true
	 * ---
	 *
	 * @subcommand import-cards
	 */
	public function import_cards( $args, $assoc_args ) {

		list( $dashboard_id ) = $args;

		if ( get_post_type( $dashboard_id ) !== ITSEC_Dashboard::CPT_DASHBOARD ) {
			WP_CLI::error( 'No dashboard found with that id.' );
		}

		$contents = $this->read_from_file_or_stdin( isset( $args[1] ) ? $args[1] : false );
		$decoded  = json_decode( $contents, true );

		if ( false === $decoded ) {
			WP_CLI::error( 'Unable to parse JSON.' );
		}

		$schema = array(
			'type'  => 'array',
			'items' => array(
				'type'                 => 'object',
				'properties'           => array(
					'type'     => array(
						'type' => 'string',
					),
					'size'     => array(
						'type' => 'object',
					),
					'position' => array(
						'type' => 'object',
					),
					'settings' => array(
						'type' => 'object',
					),
				),
				'additionalProperties' => false,
			)
		);

		$valid = rest_validate_value_from_schema( $decoded, $schema );

		if ( is_wp_error( $valid ) ) {
			WP_CLI::error( $valid );
		}

		$import_args = array();

		if ( isset( $assoc_args['clear'] ) ) {
			$import_args['clear'] = $assoc_args['clear'];
		}

		ITSEC_Dashboard_Util::import_cards( $dashboard_id, $decoded, $import_args );
	}

	/**
	 * Format a dashboard.
	 *
	 * @param array $dashboard
	 *
	 * @return array
	 */
	private function format_dashboard( $dashboard ) {
		$formatted = $dashboard;

		$formatted['label']   = $dashboard['label']['raw'];
		$formatted['primary'] = (int) get_user_meta( get_current_user_id(), ITSEC_Dashboard::META_PRIMARY, true ) === $dashboard['id'];

		return $formatted;
	}

	/**
	 * Read post content from file or STDIN
	 *
	 * @param string|false $arg Supplied argument
	 *
	 * @return string
	 */
	private function read_from_file_or_stdin( $arg ) {
		if ( false !== $arg ) {
			$readfile = $arg;
			if ( ! file_exists( $readfile ) || ! is_file( $readfile ) ) {
				\WP_CLI::error( "Unable to read content from '$readfile'." );
			}
		} else {
			$readfile = 'php://stdin';
		}

		return file_get_contents( $readfile );
	}
}

WP_CLI::add_command( 'itsec dashboard', 'ITSEC_Dashboard_Command', array(
	'before_invoke' => function () {
		ITSEC_Modules::load_module_file( 'class-itsec-dashboard-util.php', 'dashboard' );
	}
) );
