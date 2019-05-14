<?php

class ITSEC_Lockout_Command extends WP_CLI_Command {

	/** @var ITSEC_Lockout */
	private $lockout;
	private $default_fields;

	public function __construct() {
		$this->lockout        = $GLOBALS['itsec_lockout'];
		$this->default_fields = array(
			'id',
			'type',
			'start_gmt',
			'start_relative',
			'expire_gmt',
			'expire_relative',
			'host',
			'user',
			'username',
			'active'
		);

		parent::__construct();
	}

	/**
	 * List lockouts
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Only return lockouts of the given type.
	 * ---
	 * default: all
	 * options:
	 *  - all
	 *  - host
	 *  - user
	 *  - username
	 *
	 * [--current]
	 * : Only include lockouts that are active and not expired. Defaults to true.
	 *
	 * [--limit=<limit>]
	 * : Limit to a certain number of results.
	 *
	 * [--after=<after>]
	 * : Include lockouts that took place after a certain time. Any strtotime compatible string in GMT.
	 *
	 * [--search=<search>]
	 * : Search lockouts by username, email, nicename, display name or IP address.
	 *
	 * [--orderby=<orderby>]
	 * : Order results by a custom field.
	 * ---
	 * default: lockout_id
	 * options:
	 *  - lockout_id
	 *  - lockout_start
	 *  - lockout_expire
	 *
	 * [--order=<order>]
	 * : The order of results.
	 * ---
	 * default: desc
	 * options
	 *  - desc
	 *  - asc
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
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		$lockout_args = array(
			'current' => \WP_CLI\Utils\get_flag_value( $assoc_args, 'current', true ),
			'orderby' => \WP_CLI\Utils\get_flag_value( $assoc_args, 'orderby', 'lockout_id' ),
			'order'   => strtoupper( \WP_CLI\Utils\get_flag_value( $assoc_args, 'order', 'desc' ) ),
		);

		$lockout_args['current'] = \WP_CLI\Utils\get_flag_value( $assoc_args, 'current', true );

		if ( $limit = \WP_CLI\Utils\get_flag_value( $assoc_args, 'limit' ) ) {
			$lockout_args['limit'] = $limit;
		}

		if ( $after = \WP_CLI\Utils\get_flag_value( $assoc_args, 'after' ) ) {
			$lockout_args['after'] = $after;
		}

		if ( $search = \WP_CLI\Utils\get_flag_value( $assoc_args, 'search' ) ) {
			$lockout_args['search'] = $search;
		}

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		if ( 'count' === $format ) {
			$lockout_args['return'] = 'count';
		}

		$lockouts = $this->lockout->get_lockouts(
			\WP_CLI\Utils\get_flag_value( $assoc_args, 'type', 'all' ),
			$lockout_args
		);

		if ( 'ids' === $format ) {
			echo implode( ' ', wp_list_pluck( $lockouts, 'lockout_id' ) );
		} elseif ( 'count' === $assoc_args['format'] ) {
			echo $lockouts;
		} else {
			$assoc_args = wp_parse_args( $assoc_args, array(
				'fields' => $this->default_fields,
			) );

			$formatter = new \WP_CLI\Formatter( $assoc_args );
			$formatter->display_items( array_map( array( $this, 'format_lockout' ), $lockouts ) );
		}
	}


	/**
	 * Get a lockout.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The id of the lockout.
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

		list( $id ) = $args;

		if ( ! $lockout = $this->lockout->get_lockout( $id ) ) {
			WP_CLI::error( 'Lockout not found.' );
		}

		$assoc_args = wp_parse_args( $assoc_args, array(
			'fields' => $this->default_fields,
			'format' => 'table'
		) );

		$formatter = new WP_CLI\Formatter( $assoc_args );
		$formatter->display_item( $this->format_lockout( $lockout ) );
	}

	/**
	 * Release a lockout.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The id of the lockout
	 */
	public function release( $args ) {
		list( $id ) = $args;

		if ( ! $lockout = $this->lockout->get_lockout( $id ) ) {
			WP_CLI::error( 'Lockout not found.' );
		}

		if ( $this->lockout->release_lockout( $id ) ) {
			WP_CLI::success( 'Lockout released.' );
		} else {
			WP_CLI::error( 'Failed to release lockout.' );
		}
	}

	/**
	 * Create a lockout.
	 *
	 * ## OPTIONS
	 *
	 * <module>
	 * : The module responsible for the lockout.
	 *
	 * [<subject>]
	 * : The subject being locked out. Can be an IP address, user ID, or a username of a non-existing user.
	 *
	 * [--host=<host>]
	 * : The host to lockout.
	 *
	 * [--user=<user>]
	 * : The user ID to lockout.
	 *
	 * [--username=<username>]
	 * : The username to lockout.
	 *
	 * ## EXAMPLES
	 *
	 *      # Lockout a username called "5" that would conflict with a user id if auto-detected.
	 *      wp itsec lockout create your_module --username=5
	 */
	public function create( $args, $assoc_args ) {

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-ip-tools.php' );

		list( $module ) = $args;

		$host = $user = $username = false;

		if ( isset( $args[1] ) ) {
			if ( ctype_digit( $args[1] ) ) {
				$user = (int) $args[1];
			} elseif ( ITSEC_Lib_IP_Tools::validate( $args[1] ) ) {
				$host = $args[1];
			} else {
				$username = $args[1];
			}
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'host' ) ) {
			$host = \WP_CLI\Utils\get_flag_value( $assoc_args, 'host' );
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'user' ) ) {
			$user = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'user' );
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'username' ) ) {
			$username = \WP_CLI\Utils\get_flag_value( $assoc_args, 'username' );
		}

		if ( $host && ! ITSEC_Lib_IP_Tools::validate( $host ) ) {
			WP_CLI::error( 'Invalid host.' );
		}

		if ( $user && ! get_userdata( $user ) ) {
			WP_CLI::error( 'No user found with that id.' );
		}

		if ( $username && ! validate_username( $username ) ) {
			WP_CLI::error( 'Invalid username.' );
		}

		$created = $this->lockout->create_lockout( array(
			'module'   => $module,
			'host'     => $host,
			'user_id'  => $user,
			'username' => $username,
		) );

		if ( $created['id'] ) {
			WP_CLI::success( "Lockout created {$created['id']}." );
		} else {
			WP_CLI::error( 'Failed to create lockout.' );
		}
	}

	private function format_lockout( $lockout ) {
		$now    = ITSEC_Core::get_current_time_gmt();
		$start  = strtotime( $lockout['lockout_start_gmt'] );
		$expire = strtotime( $lockout['lockout_expire_gmt'] );

		return array(
			'id'              => $lockout['lockout_id'],
			'type'            => $lockout['lockout_type'],
			'start'           => $lockout['lockout_start'],
			'start_gmt'       => $lockout['lockout_start_gmt'],
			'start_relative'  => human_time_diff( $start ) . ( $start < $now ? ' ago' : ' from now' ),
			'expire'          => $lockout['lockout_expire'],
			'expire_gmt'      => $lockout['lockout_expire_gmt'],
			'expire_relative' => human_time_diff( $expire ) . ( $expire < $now ? ' ago' : ' from now' ),
			'host'            => $lockout['lockout_host'] ?: '',
			'user'            => $lockout['lockout_user'] ? (int) $lockout['lockout_user'] : '',
			'username'        => $lockout['lockout_username'] ?: '',
			'active'          => (bool) $lockout['lockout_active'],
		);
	}
}

WP_CLI::add_command( 'itsec lockout', 'ITSEC_Lockout_Command' );
