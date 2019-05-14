<?php

/**
 * Access fingerprinting functionality.
 */
class ITSEC_Fingerprinting_Command extends WP_CLI_Command {

	/**
	 * Compare two fingerprints.
	 *
	 * ## OPTIONS
	 *
	 * <known>
	 * : The UUID of the known fingerprint.
	 *
	 * <unknown>
	 * : The UUID of the unknown fingerprint.
	 *
	 * [--scores]
	 * : Include the scores.
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
	public function compare( $args, $assoc_args ) {

		list( $known_uuid, $unknown_uuid ) = $args;

		$known   = ITSEC_Fingerprint::get_by_uuid( $known_uuid );
		$unknown = ITSEC_Fingerprint::get_by_uuid( $unknown_uuid );

		if ( ! $known ) {
			WP_CLI::error( 'Known fingerprint not found.' );
		}

		if ( ! $unknown ) {
			WP_CLI::error( 'Unknown fingerprint not found.' );
		}

		$comparison = $known->compare( $unknown );

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'scores' ) ) {

			$scores         = array();
			$known_values   = $known->get_values();
			$unknown_values = $unknown->get_values();

			foreach ( $comparison->get_scores() as $source => $score ) {
				$scores[] = array(
					'source'  => $source,
					'score'   => $score['score'],
					'weight'  => $score['weight'],
					'known'   => isset( $known_values[ $source ] ) ? $known_values[ $source ]->get_value() : null,
					'unknown' => isset( $unknown_values[ $source ] ) ? $unknown_values[ $source ]->get_value() : null,
				);
			}

			$assoc_args = wp_parse_args( $assoc_args, array(
				'fields' => array( 'source', 'score', 'weight', 'known', 'unknown' ),
				'format' => 'table'
			) );

			$formatter = new WP_CLI\Formatter( $assoc_args );
			$formatter->display_items( $scores );
		}

		WP_CLI::success( 'Match Percentage: ' . $comparison->get_match_percent() );
	}

	/**
	 * Gets a list of fingerprints.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user ID to retrieve fingerprints for.
	 *
	 * [--<field>=<value>]
	 * : One or more args to customize returned fingerprints
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each fingerprint..
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

		list( $user_id ) = $args;

		$user = get_userdata( $user_id );

		if ( ! $user || ! $user->exists() ) {
			WP_CLI::error( 'User not found.' );
		}

		$assoc_args = wp_parse_args( $assoc_args, array(
			'fields' => array( 'uuid', 'status', 'approved', 'last_seen', 'uses' ),
			'format' => 'table'
		) );

		$query = array();

		if ( isset( $assoc_args['status'] ) ) {
			$query['status'] = wp_parse_slug_list( $assoc_args['status'] );
		}

		if ( isset( $assoc_args['exclude'] ) ) {
			$query['exclude'] = wp_parse_slug_list( $assoc_args['exclude'] );
		}

		$fingerprints = ITSEC_Lib_Fingerprinting::get_user_fingerprints( $user, $query );

		if ( 'ids' === $assoc_args['format'] ) {
			echo implode( ' ', array_map( function ( ITSEC_Fingerprint $fingerprint ) { return $fingerprint->get_uuid(); }, $fingerprints ) );
		} elseif ( 'count' === $assoc_args['format'] ) {
			echo count( $fingerprints );
		} else {
			$formatter = new \WP_CLI\Formatter( $assoc_args );
			$formatter->display_items( array_map( array( $this, 'format_fingerprint' ), $fingerprints ) );
		}
	}

	/**
	 * Get a fingerprint.
	 *
	 * ## OPTIONS
	 *
	 * <uuid>
	 * : The UUID of the fingerprint.
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
	public function get( $args, $assoc_args ) {

		list( $uuid ) = $args;

		if ( ! $fingerprint = ITSEC_Fingerprint::get_by_uuid( $uuid ) ) {
			WP_CLI::error( 'Fingerprint not found.' );
		}

		$assoc_args = wp_parse_args( $assoc_args, array(
			'fields' => array( 'uuid', 'status', 'user', 'approved', 'last_seen', 'uses' ),
			'format' => 'table'
		) );

		$formatter = new WP_CLI\Formatter( $assoc_args );
		$formatter->display_item( $this->format_fingerprint( $fingerprint ) );
	}

	/**
	 * Approve a fingerprint.
	 *
	 * ## OPTIONS
	 *
	 * <uuid>
	 * : The UUID of the fingerprint.
	 */
	public function approve( $args, $assoc_args ) {
		list( $uuid ) = $args;

		if ( ! $fingerprint = ITSEC_Fingerprint::get_by_uuid( $uuid ) ) {
			WP_CLI::error( 'Fingerprint not found.' );
		}

		if ( $fingerprint->can_change_status() ) {
			$saved = $fingerprint->approve();
		} else {
			$saved = $fingerprint->_set_status( ITSEC_Fingerprint::S_APPROVED );
		}

		if ( ! $saved ) {
			WP_CLI::error( 'Failed to approve fingerprint.' );
		}

		WP_CLI::success( 'Fingerprint approved.' );
	}

	/**
	 * Block a fingerprint.
	 *
	 * ## OPTIONS
	 *
	 * <uuid>
	 * : The UUID of the fingerprint.
	 */
	public function block( $args, $assoc_args ) {
		list( $uuid ) = $args;

		if ( ! $fingerprint = ITSEC_Fingerprint::get_by_uuid( $uuid ) ) {
			WP_CLI::error( 'Fingerprint not found.' );
		}

		if ( $fingerprint->can_change_status() ) {
			$saved = $fingerprint->deny();
		} else {
			$saved = $fingerprint->_set_status( ITSEC_Fingerprint::S_DENIED );
		}

		if ( ! $saved ) {
			WP_CLI::error( 'Failed to block fingerprint.' );
		}

		WP_CLI::success( 'Fingerprint approved.' );
	}

	private function format_fingerprint( ITSEC_Fingerprint $fingerprint ) {
		return array(
			'uuid'      => $fingerprint->get_uuid(),
			'status'    => $fingerprint->get_status(),
			'user'      => $fingerprint->get_user()->ID,
			'created'   => $fingerprint->get_created_at()->format( 'Y-m-d H:i:s' ),
			'approved'  => $fingerprint->get_approved_at() ? $fingerprint->get_approved_at()->format( 'Y-m-d H:i:s' ) : null,
			'last_seen' => $fingerprint->get_last_seen()->format( 'Y-m-d H:i:s' ),
			'uses'      => $fingerprint->get_uses(),
			'values'    => array_map( array( $this, 'format_value' ), $fingerprint->get_values() ),
			'summary'   => (string) $fingerprint,
		);
	}

	/**
	 * Permanently delete fingerprints.
	 *
	 * [<user>...]
	 * : Limit to the given user ID.
	 *
	 * [--yes]
	 * : Answer yet to any confirmation prompts.
	 */
	public function clear( $args, $assoc_args ) {

		global $wpdb;

		if ( ! $args ) {
			WP_CLI::confirm( 'Are you sure you want to delete the fingerprints for all users?' );

			$r = $wpdb->query( "TRUNCATE {$wpdb->base_prefix}itsec_fingerprints" );
		} else {
			$user_ids = array_unique( array_map( 'absint', $args ) );

			$in = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
			$r  = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}itsec_fingerprints WHERE `fingerprint_user` IN ($in)", $user_ids ) );
		}

		if ( false === $r ) {
			WP_CLI::error( 'Failed to delete fingerprints.' );
		}

		WP_CLI::success( 'Fingerprints deleted.' );
	}

	/**
	 * Get a fingerprint's values.
	 *
	 * ## OPTIONS
	 *
	 * <uuid>
	 * : The UUID of the fingerprint.
	 *
	 * [--thing=<val>]
	 * : Test thing
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
	 *
	 * @subcommand get-values
	 */
	public function get_values( $args, $assoc_args ) {

		list( $uuid ) = $args;

		if ( ! $fingerprint = ITSEC_Fingerprint::get_by_uuid( $uuid ) ) {
			WP_CLI::error( 'Fingerprint not found.' );
		}

		$values = array_map( array( $this, 'format_value' ), $fingerprint->get_values() );

		$assoc_args = wp_parse_args( $assoc_args, array(
			'fields' => array( 'slug', 'value' ),
			'format' => 'table'
		) );

		$formatter = new WP_CLI\Formatter( $assoc_args );
		$formatter->display_items( $values );
	}

	private function format_value( ITSEC_Fingerprint_Value $value ) {
		return array(
			'slug'  => $value->get_source()->get_slug(),
			'value' => $value->get_value(),
		);
	}
}

WP_CLI::add_command( 'itsec fingerprint', 'ITSEC_Fingerprinting_Command', array(
	'before_invoke' => function () {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );
	}
) );

require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

$args = array(
	'shortdesc' => 'Create a fingerprint.',
	'synopsis'  => array(
		array(
			'type'        => 'positional',
			'name'        => 'user',
			'description' => 'The user ID to create a fingerprint for.',
			'optional'    => false,
			'repeating'   => false,
		),
	)
);

foreach ( ITSEC_Lib_Fingerprinting::get_sources() as $source ) {
	$args['synopsis'][] = array(
		'type'        => 'assoc',
		'name'        => $source->get_slug(),
		'description' => "Value for the {$source->get_slug()} source.",
		'optional'    => true,
		'value'       => array(
			'name' => 'value',
		)
	);
}

$args['synopsis'][] = array(
	'type'        => 'assoc',
	'name'        => 'status',
	'description' => 'Status for the fingerprint.',
	'optional'    => true,
	'default'     => 'pending',
	'options'     => array( ITSEC_Fingerprint::S_APPROVED, ITSEC_Fingerprint::S_AUTO_APPROVED, ITSEC_Fingerprint::S_PENDING, ITSEC_Fingerprint::S_PENDING_AUTO_APPROVE, ITSEC_Fingerprint::S_DENIED )
);

$args['synopsis'][] = array(
	'type'        => 'flag',
	'name'        => 'porcelain',
	'description' => 'Output just the new fingerprint uuid.',
	'optional'    => true,
);

WP_CLI::add_command( 'itsec fingerprint create', function ( $args, $assoc_args ) {

	list( $user_id ) = $args;

	$user = get_userdata( $user_id );

	if ( ! $user || ! $user->exists() ) {
		WP_CLI::error( 'User not found.' );
	}

	$sources = ITSEC_Lib_Fingerprinting::get_sources();
	$values  = array();

	foreach ( $sources as $source ) {
		if ( isset( $assoc_args[ $source->get_slug() ] ) ) {
			$values[] = new ITSEC_Fingerprint_Value( $source, $assoc_args[ $source->get_slug() ] );
		}
	}

	if ( ! $values ) {
		WP_CLI::error( 'At least one value is required.' );
	}

	if ( ! isset( $assoc_args['ip'] ) && isset( $sources['ip'] ) ) {
		WP_CLI::warning( 'Including the IP is highly recommended.' );
	}

	if ( ! isset( $assoc_args['header-user-agent'] ) && isset( $sources['header-user-agent'] ) ) {
		WP_CLI::warning( 'Including the User Agent is recommended.' );
	}

	$fingerprint = new ITSEC_Fingerprint(
		$user,
		new DateTime( '@' . ITSEC_Core::get_current_time_gmt(), new DateTimeZone( 'UTC' ) ),
		$values
	);

	switch ( $assoc_args['status'] ) {
		case ITSEC_Fingerprint::S_APPROVED:
			$fingerprint->approve();
			break;
		case ITSEC_Fingerprint::S_AUTO_APPROVED:
			$fingerprint->auto_approve();
			break;
		case ITSEC_Fingerprint::S_PENDING_AUTO_APPROVE:
			$fingerprint->delay_auto_approve();
			break;
		case ITSEC_Fingerprint::S_DENIED:
			$fingerprint->deny();
			break;
	}

	if ( ! $fingerprint->create() ) {
		WP_CLI::error( 'Failed to save fingerprint' );
	}

	if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
		WP_CLI::line( $fingerprint->get_uuid() );
	} else {
		WP_CLI::success( 'Created fingerprint ' . $fingerprint->get_uuid() );
	}
}, $args );