<?php

/**
 * Class ITSEC_Fingerprint
 */
class ITSEC_Fingerprint {

	const S_APPROVED = 'approved';
	const S_AUTO_APPROVED = 'auto-approved';
	const S_PENDING_AUTO_APPROVE = 'pending-auto-approve';
	const S_PENDING = 'pending';
	const S_DENIED = 'denied';

	/** @var WP_User */
	private $user;

	/** @var DateTime */
	private $created_at;

	/** @var ITSEC_Fingerprint_Value[] */
	private $values = array();

	/** @var int */
	private $_id;

	/** @var int */
	private $_uses = 0;

	/** @var string */
	private $_status = self::S_PENDING;

	/** @var string */
	private $_uuid;

	/** @var DateTime */
	private $_last_seen;

	/** @var DateTime */
	private $_approved_at;

	/** @var string */
	private $_hash;

	/** @var array */
	private $_snapshot = array();

	/**
	 * ITSEC_Fingerprint constructor.
	 *
	 * @param WP_User                   $user
	 * @param DateTime                  $time
	 * @param ITSEC_Fingerprint_Value[] $values
	 */
	public function __construct( WP_User $user, DateTime $time, array $values ) {
		$this->user       = $user;
		$this->created_at = $this->_last_seen = $time;

		foreach ( $values as $value ) {
			$this->values[ $value->get_source()->get_slug() ] = $value;
		}
	}

	/**
	 * Compare this fingerprint with another fingerprint.
	 *
	 * The operation is not commutative, if a source is missing in the given fingerprint that is present in the current fingerprint,
	 * it will count as a 0 score, whereas when the given fingerprint has extra source values, those will not impact the score.
	 *
	 * @param ITSEC_Fingerprint $fingerprint
	 *
	 * @return ITSEC_Fingerprint_Comparison
	 */
	public function compare( ITSEC_Fingerprint $fingerprint ) {
		$scores       = array();
		$total_weight = 0;

		foreach ( $this->values as $value ) {
			$source = $value->get_source();
			$other  = $fingerprint->values[ $source->get_slug() ];
			$weight = $source->get_weight( $value );

			if ( $other ) {
				$scores[ $source->get_slug() ] = array(
					'score'  => $source->compare( $value, $other ),
					'weight' => $weight,
				);
			} else {
				$scores[ $source->get_slug() ] = array(
					'score'  => 0,
					'weight' => $weight,
				);
			}

			$total_weight += $weight;
		}

		$final_score = 0;

		foreach ( $scores as $score ) {
			$percent     = $score['weight'] / $total_weight;
			$final_score += $score['score'] * $percent;
		}

		return new ITSEC_Fingerprint_Comparison( $this, $fingerprint, $final_score, $scores );
	}

	/**
	 * Is the fingerprint approved.
	 *
	 * @return bool
	 */
	public function is_approved() { return self::S_APPROVED === $this->_status; }

	/**
	 * Is the fingerprint auto-approved.
	 *
	 * @return bool
	 */
	public function is_auto_approved() { return self::S_AUTO_APPROVED === $this->_status; }

	/**
	 * Is the fingerprint pending auto-approval.
	 *
	 * @return bool
	 */
	public function is_pending_auto_approval() { return self::S_PENDING_AUTO_APPROVE === $this->_status; }

	/**
	 * Is the fingerprint in pending status.
	 *
	 * @return bool
	 */
	public function is_pending() { return self::S_PENDING === $this->_status; }

	/**
	 * Is the fingerprint denied.
	 *
	 * @return bool
	 */
	public function is_denied() { return self::S_DENIED === $this->_status; }

	/**
	 * Can the fingerprint's status be changed.
	 *
	 * @return bool
	 */
	public function can_change_status() { return $this->is_auto_approved() || $this->is_pending_auto_approval() || $this->is_pending(); }

	/**
	 * Get the number of times the fingerprint was used.
	 *
	 * @return int
	 */
	public function get_uses() {
		return $this->_uses;
	}

	/**
	 * Get the WordPress user this fingerprint is for.
	 *
	 * @return WP_User
	 */
	public function get_user() {
		return $this->user;
	}

	/**
	 * Get the time the fingerprint was created.
	 *
	 * @return DateTime
	 */
	public function get_created_at() {
		return $this->created_at;
	}

	/**
	 * Get the values making up this fingerprint.
	 *
	 * @return ITSEC_Fingerprint_Value[]
	 */
	public function get_values() {
		return $this->values;
	}

	/**
	 * Get the UUID associated with this fingerprint.
	 *
	 * @return string
	 */
	public function get_uuid() {
		return $this->_uuid;
	}

	/**
	 * Get the status of the fingerprint.
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->_status;
	}

	/**
	 * Get the time the fingerprint was approved at.
	 *
	 * @return DateTime|null
	 */
	public function get_approved_at() {
		return $this->_approved_at;
	}

	/**
	 * Get the date the fingerprint was last seen.
	 *
	 * @return DateTime
	 */
	public function get_last_seen() {
		return $this->_last_seen;
	}

	/**
	 * Get a snapshot of user or system configuration values at the time this fingerprint was created.
	 *
	 * @return array
	 */
	public function get_snapshot() {
		return $this->_snapshot;
	}

	/**
	 * Get a hash uniquely identifying the collected data.
	 *
	 * @return string
	 */
	public function calculate_hash() {
		if ( $this->_hash ) {
			return $this->_hash;
		}

		if ( ! $serialized = $this->serialize_values() ) {
			return null;
		}

		return md5( $serialized );
	}

	/**
	 * Set the last seen time for the Fingerprint.
	 *
	 * @return bool
	 */
	public function was_seen() {

		$this->_uses ++;
		$this->_last_seen = new DateTime( '@' . ITSEC_Core::get_current_time_gmt(), new DateTimeZone( 'UTC' ) );

		if ( ! $this->_id ) {
			return true;
		}

		return $this->save( 'was_seen' );
	}

	/**
	 * Approve this fingerprint.
	 *
	 * @return bool
	 */
	public function approve() {

		if ( self::S_APPROVED === $this->_status ) {
			return true;
		}

		if ( ! $this->can_change_status() ) {
			return false;
		}

		$this->_status      = self::S_APPROVED;
		$this->_approved_at = new DateTime( '@' . ITSEC_Core::get_current_time_gmt(), new DateTimeZone( 'UTC' ) );

		return $this->_id ? $this->save( $this->get_status_action( self::S_APPROVED ) ) : true;
	}

	/**
	 * Approve this fingerprint.
	 *
	 * @return bool
	 */
	public function auto_approve() {

		if ( self::S_AUTO_APPROVED === $this->_status ) {
			return true;
		}

		if ( ! $this->can_change_status() ) {
			return false;
		}

		$this->_status      = self::S_AUTO_APPROVED;
		$this->_approved_at = new DateTime( '@' . ITSEC_Core::get_current_time_gmt(), new DateTimeZone( 'UTC' ) );

		return $this->_id ? $this->save( $this->get_status_action( self::S_AUTO_APPROVED ) ) : true;
	}

	/**
	 * Delay auto-approval for a few days.
	 *
	 * @return bool
	 */
	public function delay_auto_approve() {
		if ( self::S_PENDING_AUTO_APPROVE === $this->_status ) {
			return true;
		}

		if ( ! $this->is_pending() ) {
			return false;
		}

		$this->_status = self::S_PENDING_AUTO_APPROVE;

		return $this->_id ? $this->save( $this->get_status_action( self::S_PENDING_AUTO_APPROVE ) ) : true;
	}

	/**
	 * Deny this fingerprint.
	 *
	 * @return bool
	 */
	public function deny() {

		if ( self::S_DENIED === $this->_status ) {
			return true;
		}

		if ( ! $this->can_change_status() ) {
			return false;
		}

		$this->_status = self::S_DENIED;

		return $this->_id ? $this->save( $this->get_status_action( self::S_DENIED ) ) : true;
	}

	/**
	 * Set the fingerprint's status.
	 *
	 * This should almost never be used. Instead use the status-specific methods above.
	 *
	 * @internal
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	public function _set_status( $status ) {
		if ( $status === $this->_status ) {
			return true;
		}

		if ( ! $this->_id ) {
			return false;
		}

		$this->_status = $status;

		return $this->save( $this->get_status_action( $status ), 'override' );
	}

	/**
	 * Get the action suffix to use when changing a status.
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	private function get_status_action( $status ) {
		switch ( $status ) {
			case self::S_APPROVED:
				return 'approved';
			case self::S_AUTO_APPROVED:
				return 'auto_approved';
			case self::S_PENDING_AUTO_APPROVE:
				return 'auto_approve_delayed';
			case self::S_DENIED:
				return 'denied';
			default:
				return $status;
		}
	}

	/**
	 * Create the fingerprint in storage.
	 *
	 * @return bool
	 */
	public function create() {

		if ( $this->_id ) {
			return false;
		}

		if ( ! $data = $this->serialize_values() ) {
			return false;
		}

		global $wpdb;

		$this->_uuid = wp_generate_uuid4();
		$this->generate_snapshot();

		$insert_id = $wpdb->insert( $wpdb->base_prefix . 'itsec_fingerprints', array(
			'fingerprint_user'        => $this->get_user()->ID,
			'fingerprint_hash'        => md5( $data ),
			'fingerprint_data'        => $data,
			'fingerprint_uses'        => 1,
			'fingerprint_status'      => $this->_status,
			'fingerprint_uuid'        => $this->_uuid,
			'fingerprint_created_at'  => $this->get_created_at()->format( 'Y-m-d H:i:s' ),
			'fingerprint_last_seen'   => $this->get_last_seen()->format( 'Y-m-d H:i:s' ),
			'fingerprint_approved_at' => $this->get_approved_at() ? $this->get_approved_at()->format( 'Y-m-d H:i:s' ) : '',
			'fingerprint_snapshot'    => wp_json_encode( $this->_snapshot ),
		), array(
			'fingerprint_user'        => '%d',
			'fingerprint_hash'        => '%s',
			'fingerprint_data'        => '%s',
			'fingerprint_uses'        => '%d',
			'fingerprint_status'      => '%s',
			'fingerprint_uuid'        => '%s',
			'fingerprint_created_at'  => '%s',
			'fingerprint_last_seen'   => '%s',
			'fingerprint_approved_at' => '%s',
			'fingerprint_snapshot'    => '%s',
		) );

		if ( $insert_id ) {
			$this->_id = $insert_id;

			/**
			 * Fires when a fingerprint is created.
			 *
			 * @param ITSEC_Fingerprint $this
			 */
			do_action( 'itsec_fingerprint_created', $this );

			if ( self::S_PENDING !== $this->_status ) {
				$action = $this->get_status_action( $this->_status );
				do_action( "itsec_fingerprint_{$action}", $this, $action );
			}
		}

		return (bool) $insert_id;
	}

	/**
	 * Serialize the values for storage.
	 *
	 * @return false|string
	 */
	private function serialize_values() {
		$data = array();

		foreach ( $this->get_values() as $value ) {
			$data[ $value->get_source()->get_slug() ] = $value->get_value();
		}

		return wp_json_encode( $data );
	}

	/**
	 * Generate the snapshot of user/system configuration.
	 */
	private function generate_snapshot() {
		if ( ! $this->_snapshot ) {
			$this->_snapshot = array(
				'user_email' => $this->get_user()->user_email,
			);
		}
	}

	/**
	 * Save the current state.
	 *
	 * @param string $action
	 * @param mixed  $additional,...
	 *
	 * @return bool
	 */
	private function save( $action = '', $additional = null ) {

		global $wpdb;

		$updated = (bool) $wpdb->update(
			$wpdb->base_prefix . 'itsec_fingerprints',
			array(
				'fingerprint_last_seen'   => $this->get_last_seen()->format( 'Y-m-d H:i:s' ),
				'fingerprint_uses'        => $this->get_uses(),
				'fingerprint_status'      => $this->get_status(),
				'fingerprint_approved_at' => $this->get_approved_at() ? $this->get_approved_at()->format( 'Y-m-d H:i:s' ) : '',
			),
			array( 'fingerprint_id' => $this->_id ),
			array( 'fingerprint_last_seen' => '%s', 'fingerprint_uses' => '%d', 'fingerprint_status' => '%s', 'fingerprint_approved_at' => '%s' ),
			array( 'fingerprint_id' => '%d' )
		);

		if ( $updated && $action ) {
			$args = array_merge( array( $this, $action ), array_slice( func_get_args(), 1 ) );

			/**
			 * Fires when the fingerprint is saved.
			 *
			 * @param ITSEC_Fingerprint $this
			 * @param string            $action
			 */
			do_action_ref_array( "itsec_fingerprint_{$action}", $args );
		}

		return $updated;
	}

	/**
	 * Get a user's fingerprints.
	 *
	 * @param WP_User $user
	 * @param array   $args
	 *
	 * @return ITSEC_Fingerprint[]
	 */
	public static function get_all_for_user( WP_User $user, array $args ) {

		global $wpdb;

		$sql     = "SELECT * FROM {$wpdb->base_prefix}itsec_fingerprints WHERE `fingerprint_user` = %s";
		$prepare = array( $user->ID );

		if ( ! empty( $args['status'] ) ) {
			if ( is_array( $args['status'] ) ) {
				$sql     .= ' AND `fingerprint_status` IN (' . implode( ', ', array_fill( 0, count( $args['status'] ), '%s' ) ) . ')';
				$prepare = array_merge( $prepare, $args['status'] );
			} else {
				$sql       .= ' AND `fingerprint_status` = %s';
				$prepare[] = $args['status'];
			}
		}

		if ( ! empty( $args['exclude'] ) ) {
			$sql     .= ' AND `fingerprint_uuid` NOT IN (' . implode( ', ', array_fill( 0, count( $args['exclude'] ), '%s' ) ) . ')';
			$prepare = array_merge( $prepare, wp_parse_slug_list( $args['exclude'] ) );
		}

		$sql .= ' ORDER BY `fingerprint_last_seen` DESC';

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $prepare ) );

		$fingerprints = array();

		foreach ( $rows as $row ) {
			if ( $fingerprint = self::_hydrate_fingerprint( $row ) ) {
				$fingerprints[] = $fingerprint;
			}
		}

		return $fingerprints;
	}

	/**
	 * Get a fingerprint by its UUID.
	 *
	 * @param string $uuid
	 *
	 * @return ITSEC_Fingerprint|null
	 */
	public static function get_by_uuid( $uuid ) {

		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}itsec_fingerprints WHERE `fingerprint_uuid` = %s", $uuid ) );

		if ( ! $row ) {
			return null;
		}

		return self::_hydrate_fingerprint( $row );
	}

	/**
	 * Get a fingerprint by its data hash.
	 *
	 * @param WP_User $user
	 * @param string  $hash
	 *
	 * @return ITSEC_Fingerprint|null
	 */
	public static function get_by_hash( WP_User $user, $hash ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->base_prefix}itsec_fingerprints WHERE `fingerprint_hash` = %s AND `fingerprint_user` = %s",
			$hash,
			$user->ID
		) );

		if ( ! $row ) {
			return null;
		}

		return self::_hydrate_fingerprint( $row );
	}

	/**
	 * Hydrate a fingerprint with data from the database.
	 *
	 * @internal
	 *
	 * @param object $row
	 *
	 * @return ITSEC_Fingerprint|null
	 */
	public static function _hydrate_fingerprint( $row ) {
		$sources = ITSEC_Lib_Fingerprinting::get_sources();
		$values  = array();

		foreach ( json_decode( $row->fingerprint_data, true ) as $slug => $value ) {
			if ( isset( $sources[ $slug ] ) ) {
				$values[] = new ITSEC_Fingerprint_Value( $sources[ $slug ], $value );
			}
		}

		if ( ! $user = get_userdata( $row->fingerprint_user ) ) {
			return null;
		}

		$fingerprint = new ITSEC_Fingerprint(
			$user,
			new DateTime( $row->fingerprint_created_at, new DateTimeZone( 'UTC' ) ),
			$values
		);

		$approved_at = $row->fingerprint_approved_at && $row->fingerprint_approved_at !== '0000-00-00 00:00:00' ? $row->fingerprint_approved_at : null;

		$fingerprint->_id          = $row->fingerprint_id;
		$fingerprint->_uses        = $row->fingerprint_uses;
		$fingerprint->_status      = $row->fingerprint_status;
		$fingerprint->_uuid        = $row->fingerprint_uuid;
		$fingerprint->_hash        = $row->fingerprint_hash;
		$fingerprint->_last_seen   = new DateTime( $row->fingerprint_last_seen, new DateTimeZone( 'UTC' ) );
		$fingerprint->_approved_at = $approved_at ? new DateTime( $approved_at, new DateTimeZone( 'UTC' ) ) : null;

		if ( $row->fingerprint_snapshot ) {
			$fingerprint->_snapshot = json_decode( $row->fingerprint_snapshot, true );
		}

		return $fingerprint;
	}

	/**
	 * Get a summary of this fingerprint.
	 *
	 * @return string
	 */
	public function __toString() {

		$location = $browser = $platform = $ip = '';

		if ( isset( $this->values['ip'] ) ) {
			$ip = $this->values['ip']->get_value();

			require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-geolocation.php' );

			if ( ! is_wp_error( $geolocate = ITSEC_Lib_Geolocation::geolocate( $ip ) ) ) {
				$location = $geolocate['label'];
			}
		}

		if ( isset( $this->values['header-user-agent'] ) ) {
			require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-browser.php' );
			$browser_lib = new ITSEC_Lib_Browser( $this->values['header-user-agent']->get_value() );

			$browser  = "{$browser_lib->getBrowser() } ({$browser_lib->getVersion()})";
			$platform = $browser_lib->getPlatform();
		}

		if ( $location && $browser ) {
			$str = sprintf( esc_html__( 'Device running %1$s on %2$s near %3$s', 'better-wp-security' ), $browser, $platform, $location );
		} elseif ( $location ) {
			$str = sprintf( esc_html__( 'Device near %1$s', 'better-wp-security' ), $location );
		} elseif ( $browser ) {
			$str = sprintf( esc_html__( 'Device running %1$s on %2$s', 'better-wp-security' ), $browser, $platform );
		} else {
			$str = '';
		}

		if ( $ip ) {
			$str .= " ($ip)";
		}

		return trim( $str );
	}
}