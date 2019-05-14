<?php

/**
 * Class ITSEC_Dashboard_Util
 */
class ITSEC_Dashboard_Util {

	const P_24_HOURS = '24-hours';
	const P_WEEK = 'week';
	const P_30_DAYS = '30-days';

	/** @var string[] WordPress breakpoints. */
	public static $breakpoints = array( 'huge', 'wide', 'large', 'medium', 'small', 'mobile' );

	/** @var ITSEC_Dashboard_Card[] */
	private static $registered_cards;

	/** @var array */
	private static $_query_cache = array();

	/**
	 * Get all registered cards.
	 *
	 * @return ITSEC_Dashboard_Card[]
	 */
	public static function get_registered_cards() {
		if ( ! isset( self::$registered_cards ) ) {
			require_once( dirname( __FILE__ ) . '/cards/abstract-class-itsec-dashboard-card.php' );
			require_once( dirname( __FILE__ ) . '/cards/class-itsec-dashboard-card-pie-chart.php' );
			require_once( dirname( __FILE__ ) . '/cards/class-itsec-dashboard-card-line-graph.php' );
			require_once( dirname( __FILE__ ) . '/cards/class-itsec-dashboard-card-malware-scan.php' );
			require_once( dirname( __FILE__ ) . '/cards/class-itsec-dashboard-card-security-profile-list.php' );
			require_once( dirname( __FILE__ ) . '/cards/class-itsec-dashboard-card-security-profile-pinned.php' );
			require_once( dirname( __FILE__ ) . '/cards/class-itsec-dashboard-card-active-lockouts.php' );

			$cards = array(
				new ITSEC_Dashboard_Card_Malware_Scan(),
				new ITSEC_Dashboard_Card_Security_Profile_List(),
				new ITSEC_Dashboard_Card_Security_Profile_Pinned(),
				new ITSEC_Dashboard_Card_Active_Lockouts(),
				new ITSEC_Dashboard_Card_Line_Graph( 'fingerprinting', __( 'Trusted Devices', 'it-l10n-ithemes-security-pro' ), array(
					array(
						'events' => 'fingerprint-status-approved',
						'label'  => __( 'Approved', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'fingerprint-status-approved',
						'label'  => __( 'Approved', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'fingerprint-status-auto-approved',
						'label'  => __( 'Auto-Approved', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'fingerprint-status-denied',
						'label'  => __( 'Blocked', 'it-l10n-ithemes-security-pro' ),
					),
				) ),
				new ITSEC_Dashboard_Card_Line_Graph( 'brute-force', __( 'Brute Force Attacks', 'it-l10n-ithemes-security-pro' ), array(
					array(
						'events' => array( 'local-brute-force', 'network-brute-force' ),
						'label'  => __( 'Attacks', 'it-l10n-ithemes-security-pro' ),
					),
				) ),
				new ITSEC_Dashboard_Card_Pie_Chart( 'four-oh-four', __( '404s', 'it-l10n-ithemes-security-pro' ), array(
					array(
						'events' => 'four-oh-four-logged-in',
						'label'  => __( 'Logged-in Users', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'four-oh-four-bot',
						'label'  => __( 'Crawlers', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'four-oh-four',
						'label'  => __( 'Other', 'it-l10n-ithemes-security-pro' ),
					),
				) ),
				new ITSEC_Dashboard_Card_Pie_Chart( 'lockout', __( 'Lockouts', 'it-l10n-ithemes-security-pro' ), array(
					array(
						'events' => 'lockout-host',
						'label'  => __( 'Hosts', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'lockout-user',
						'label'  => __( 'Users', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'lockout-username',
						'label'  => __( 'Usernames', 'it-l10n-ithemes-security-pro' ),
					),
				), array(
					'circle_callback' => array( __CLASS__, '_get_lockout_count' ),
					'circle_label'    => _x( 'Total', 'Total Lockouts', 'it-l10n-ithemes-security-pro' ),
				) ),
				new ITSEC_Dashboard_Card_Pie_Chart( 'banned-users', __( 'Banned Users', 'it-l10n-ithemes-security-pro' ), array(
					array(
						'events' => 'blacklist-four_oh_four',
						'label'  => __( '404', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'blacklist-brute_force',
						'label'  => __( 'Login Attempts', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'blacklist-brute_force_admin_user',
						'label'  => __( 'Login Using "admin"', 'it-l10n-ithemes-security-pro' ),
					),
					array(
						'events' => 'blacklist-recaptcha',
						'label'  => __( 'Recaptcha', 'it-l10n-ithemes-security-pro' ),
					),
				), array(
					'circle_callback' => array( __CLASS__, '_get_banned_count' ),
					'circle_label'    => _x( 'Banned', 'Total Banned IPs', 'it-l10n-ithemes-security-pro' ),
				) ),
			);

			if ( ITSEC_Modules::is_active( 'backup' ) ) {
				require_once( dirname( __FILE__ ) . '/cards/class-itsec-dashboard-card-database-backup.php' );
				$cards[] = new ITSEC_Dashboard_Card_Database_Backup();
			}

			if ( ITSEC_Modules::is_active( 'version-management' ) ) {
				require_once( dirname( __FILE__ ) . '/cards/class-itsec-dashboard-card-version-management.php' );
				$cards[] = new ITSEC_Dashboard_Card_Version_Management();
			}

			self::$registered_cards = apply_filters( 'itsec_dashboards_cards', $cards );
		}

		return self::$registered_cards;
	}

	/**
	 * Get the card definition.
	 *
	 * @param string $slug
	 *
	 * @return ITSEC_Dashboard_Card|null
	 */
	public static function get_card( $slug ) {

		foreach ( self::get_registered_cards() as $card ) {
			if ( $card->get_slug() === $slug ) {
				return $card;
			}
		}

		return null;
	}

	public static function _get_lockout_count() {
		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		return $itsec_lockout->get_lockouts( 'all', array( 'return' => 'count', 'current' => false ) );
	}

	public static function _get_banned_count() {
		return count( ITSEC_Modules::get_setting( 'ban-users', 'host_list', array() ) );
	}

	/**
	 * Get the dashboards that are owned by the given user.
	 *
	 * @param WP_User|int|string|false $user
	 * @param string                   $return
	 *
	 * @return WP_Post[]|int[]
	 */
	public static function get_owned_dashboards( $user = false, $return = 'posts' ) {
		$user = ITSEC_Lib::get_user( $user );

		if ( isset( self::$_query_cache['owned'][ $user->ID ] ) ) {
			$ids = self::$_query_cache['owned'][ $user->ID ];

			if ( 'ids' === $return ) {
				return $ids;
			}

			return array_map( 'get_post', $ids );
		}

		$query = new WP_Query( array(
			'fields'         => $return === 'ids' ? 'ids' : '',
			'post_type'      => ITSEC_Dashboard::CPT_DASHBOARD,
			'no_found_rows'  => true,
			'posts_per_page' => - 1,
			'author'         => $user->ID,
		) );

		self::$_query_cache['owned'][ $user->ID ] = array();

		foreach ( $query->posts as $post ) {
			self::$_query_cache['owned'][ $user->ID ][] = is_numeric( $post ) ? $post : $post->ID;
		}

		return $query->posts;
	}

	/**
	 * Get the dashboards that have been shared with the given user.
	 *
	 * @param WP_User|int|string|false $user
	 * @param string                   $return
	 *
	 * @return WP_Post[]|int[]
	 */
	public static function get_shared_dashboards( $user = false, $return = 'posts' ) {
		$user = ITSEC_Lib::get_user( $user );

		if ( isset( self::$_query_cache['shared'][ $user->ID ] ) ) {
			$ids = self::$_query_cache['shared'][ $user->ID ];

			if ( 'ids' === $return ) {
				return $ids;
			}

			return array_map( 'get_post', $ids );
		}

		$query = new WP_Query( array(
			'fields'         => $return === 'ids' ? 'ids' : '',
			'post_type'      => ITSEC_Dashboard::CPT_DASHBOARD,
			'no_found_rows'  => true,
			'posts_per_page' => - 1,
			'author__not_in' => $user->ID,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'   => ITSEC_Dashboard::META_SHARE_USER,
					'value' => $user->ID,
				),
				array(
					'key'   => ITSEC_Dashboard::META_SHARE_ROLE,
					'value' => $user->roles,
				),
			),
		) );

		self::$_query_cache['shared'][ $user->ID ] = array();

		foreach ( $query->posts as $post ) {
			self::$_query_cache['shared'][ $user->ID ][] = is_numeric( $post ) ? $post : $post->ID;
		}

		return $query->posts;
	}

	/**
	 * Can the given user access the configuration for the given card.
	 *
	 * This checks that they have a dashbaord
	 *
	 * @param string                   $card
	 * @param WP_User|int|string|false $user
	 *
	 * @return bool
	 */
	public static function can_access_card( $card, $user = false ) {
		$user = ITSEC_Lib::get_user( $user );

		if ( user_can( $user, 'itsec_create_dashboards' ) ) {
			return true;
		}

		if ( ! isset( self::$_query_cache['allowed_cards'][ $user->ID ] ) ) {
			$ids      = self::get_shared_dashboards( $user, 'ids' );
			$id_where = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

			global $wpdb;

			$cards = $wpdb->get_col( $wpdb->prepare(
				"SELECT DISTINCT `meta_value` FROM {$wpdb->postmeta} AS t1 JOIN {$wpdb->posts} AS t2 ON (t1.`post_id` = t2.`ID`) WHERE t1.`meta_key` = %s AND t2.`post_parent` IN ({$id_where})",
				array_merge( array( ITSEC_Dashboard::META_CARD ), $ids )
			) );

			if ( false === $cards ) {
				self::$_query_cache['allowed_cards'][ $user->ID ] = array();
			} else {
				self::$_query_cache['allowed_cards'][ $user->ID ] = array_flip( $cards );
			}
		}

		return isset( self::$_query_cache['allowed_cards'][ $user->ID ][ $card ] );
	}

	/**
	 * Get cards for a dashboard.
	 *
	 * @param int $dashboard_id
	 *
	 * @return WP_Post[]
	 */
	public static function get_dashboard_cards( $dashboard_id ) {

		if ( isset( self::$_query_cache['cards'][ $dashboard_id ] ) ) {
			return array_map( 'get_post', self::$_query_cache['cards'][ $dashboard_id ] );
		}

		$query = new WP_Query( array(
			'post_type'      => ITSEC_Dashboard::CPT_CARD,
			'post_parent'    => $dashboard_id,
			'posts_per_page' => - 1,
		) );

		self::$_query_cache['cards'][ $dashboard_id ] = array();

		foreach ( $query->posts as $post ) {
			self::$_query_cache['cards'][ $dashboard_id ][] = $post->ID;
		}

		return $query->posts;
	}

	/**
	 * Get the primary dashboard for the given user.
	 *
	 * Will set if none specified and user has available dashboards.
	 *
	 * @param WP_User|string|int|false $user
	 *
	 * @return int
	 */
	public static function get_primary_dashboard_id( $user = false ) {
		$user       = ITSEC_Lib::get_user( $user );
		$primary_id = (int) get_user_meta( $user->ID, ITSEC_Dashboard::META_PRIMARY, true );

		if ( ! $primary_id || ITSEC_Dashboard::CPT_DASHBOARD !== get_post_type( $primary_id ) ) {
			require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );

			if ( $owned = self::get_owned_dashboards() ) {
				$primary_id = (int) $owned[0]->ID;
			} elseif ( $shared = self::get_shared_dashboards() ) {
				$primary_id = (int) $shared[0]->ID;
			} else {
				$primary_id = 0;
			}

			if ( $primary_id ) {
				update_user_meta( $user->ID, ITSEC_Dashboard::META_PRIMARY, $primary_id );
			}
		}

		return $primary_id;
	}

	/**
	 * Record an occurrence of an event.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function record_event( $slug ) {

		$hour_time = date( 'Y-m-d H:00:00', ITSEC_Core::get_current_time_gmt() );

		global $wpdb;
		$r = $wpdb->query( $wpdb->prepare(
			"INSERT INTO {$wpdb->base_prefix}itsec_dashboard_events (`event_slug`,`event_time`) VALUES (%s, %s) ON DUPLICATE KEY UPDATE `event_count` = `event_count` + 1",
			$slug, $hour_time
		) );

		return false !== $r;
	}

	/**
	 * Consolidate events.
	 *
	 * We initially track events hourly for 24 hours, and then consolidate the events into a single day entry.
	 */
	public static function consolidate_events() {

		// We want to ensure we can show the past 24 hours of events.

		$now = ITSEC_Core::get_current_time_gmt(); // 2018-10-05 6:30:00
		$max = $now - 2 * DAY_IN_SECONDS; // 2018-10-03 6:30:00

		$consolidate_before = date( 'Y-m-d 23:59:59', $max ); // 2018-10-03 23:59:59

		global $wpdb;

		$r = $wpdb->query( $wpdb->prepare(
			"INSERT INTO {$wpdb->base_prefix}itsec_dashboard_events ( `event_slug`, `event_time`, `event_count`, `event_consolidated`)
SELECT 
  `event_slug`, 
  str_to_date(concat(year(`event_time`), '-', month(`event_time`), '-', day(`event_time`),'-'), '%%Y-%%m-%%d') as `event_time`, 
  sum(`event_count`) as `event_count`, 
  1 as `event_consolidated` 
FROM {$wpdb->base_prefix}itsec_dashboard_events 
WHERE 
  `event_consolidated` = 0 AND 
  `event_time` < %s 
GROUP BY `event_slug`, year(`event_time`), month(`event_time`), day(`event_time`)
ON DUPLICATE KEY UPDATE `event_slug` = `event_slug`",
			$consolidate_before
		) );

		if ( false !== $r ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}itsec_dashboard_events WHERE `event_consolidated` = 0 AND `event_time` < %s", $consolidate_before ) );
		}
	}

	/**
	 * Count events.
	 *
	 * @param array|string       $slug_or_slugs
	 * @param array|string|false $period
	 *
	 * @return array|int[]|WP_Error
	 */
	public static function count_events( $slug_or_slugs, $period = false ) {

		if ( false === $period ) {
			$period = array(
				'start' => date( 'Y-m-d', ITSEC_Core::get_current_time_gmt() - 2 * MONTH_IN_SECONDS ),
				'end'   => date( 'Y-m-d', ITSEC_Core::get_current_time_gmt() ),
			);
		}

		$slugs = (array) $slug_or_slugs;

		if ( is_wp_error( $range = self::_get_range( $period ) ) ) {
			return $range;
		}

		list( $start, $end ) = $range;

		$prepare = array(
			date( 'Y-m-d H:i:s', $start ),
			date( 'Y-m-d H:i:s', $end ),
		);

		$slug_where = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );
		$prepare    = array_merge( $prepare, $slugs );

		global $wpdb;
		$r = $wpdb->get_results( $wpdb->prepare(
			"SELECT sum(`event_count`) as `c`, `event_slug` as `s` FROM {$wpdb->base_prefix}itsec_dashboard_events WHERE `event_time` BETWEEN %s AND %s AND `event_slug` IN ({$slug_where}) GROUP BY `event_slug` ORDER BY `event_time` DESC",
			$prepare
		) );

		if ( false === $r ) {
			return new WP_Error( 'itsec-dashboard-query-count-events-db-error', __( 'Error when querying the database for counting events.', 'it-l10n-ithemes-security-pro' ) );
		}

		$events = array();

		foreach ( $r as $row ) {
			$events[ $row->s ] = (int) $row->c;
		}

		foreach ( $slugs as $slug ) {
			if ( ! isset( $events[ $slug ] ) ) {
				$events[ $slug ] = 0;
			}
		}

		return $events;
	}

	/**
	 * Retrieve events.
	 *
	 * @param array|string       $slug_or_slugs
	 * @param array|string|false $period
	 *
	 * @return array|int[]|WP_Error
	 */
	public static function query_events( $slug_or_slugs, $period = false ) {

		if ( false === $period ) {
			$period = array(
				'start' => date( 'Y-m-d 00:00:00', ITSEC_Core::get_current_time_gmt() - 2 * MONTH_IN_SECONDS ),
				'end'   => date( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() ),
			);
		}

		$slugs = (array) $slug_or_slugs;

		if ( is_wp_error( $range = self::_get_range( $period ) ) ) {
			return $range;
		}

		list( $start, $end ) = $range;

		$prepare = array(
			date( 'Y-m-d H:i:s', $start ),
			date( 'Y-m-d H:i:s', $end ),
		);

		$slug_where = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );
		$prepare    = array_merge( $prepare, $slugs );

		global $wpdb;
		$r = $wpdb->get_results( $wpdb->prepare(
			"SELECT `event_time` as `t`, `event_count` as `c`, `event_slug` as `s` FROM {$wpdb->base_prefix}itsec_dashboard_events WHERE `event_time` BETWEEN %s AND %s AND `event_slug` IN ({$slug_where}) ORDER BY `event_time` DESC",
			$prepare
		) );

		if ( false === $r ) {
			return new WP_Error( 'itsec-dashboard-query-events-db-error', __( 'Error when querying the database for events.', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( self::P_24_HOURS === $period ) {
			$format    = 'Y-m-d H:00:00';
			$increment = '+1 hour';
		} else {
			$format    = 'Y-m-d';
			$increment = '+1 day';
		}

		$events = array_combine( $slugs, array_pad( array(), count( $slugs ), array() ) );

		foreach ( $r as $row ) {
			$key = date( $format, strtotime( $row->t ) );

			if ( isset( $events[ $row->s ][ $key ] ) ) {
				$events[ $row->s ][ $key ] += $row->c; // Handle unconsolidated rows.
			} else {
				$events[ $row->s ][ $key ] = (int) $row->c;
			}
		}

		$retval = array();

		foreach ( $events as $slug => $slug_events ) {
			$slug_events = self::fill_gaps( $slug_events, $start, $end, $format, $increment );

			foreach ( $slug_events as $time => $count ) {
				$retval[ $slug ][] = array(
					'time'  => $time,
					'count' => $count,
				);
			}
		}

		return $retval;
	}

	/**
	 * Retrieve the total number of events.
	 *
	 * @param array|string|false $period
	 *
	 * @return int|WP_Error
	 */
	public static function total_events( $period = false ) {

		if ( false === $period ) {
			$period = array(
				'start' => date( 'Y-m-d', ITSEC_Core::get_current_time_gmt() - 2 * MONTH_IN_SECONDS ),
				'end'   => date( 'Y-m-d', ITSEC_Core::get_current_time_gmt() ),
			);
		}

		if ( is_wp_error( $range = self::_get_range( $period ) ) ) {
			return $range;
		}

		list( $start, $end ) = $range;

		$prepare = array(
			date( 'Y-m-d H:i:s', $start ),
			date( 'Y-m-d H:i:s', $end ),
		);

		global $wpdb;
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT sum(`event_count`) as `c` FROM {$wpdb->base_prefix}itsec_dashboard_events WHERE `event_time` BETWEEN %s AND %s",
			$prepare
		) );

		if ( false === $count ) {
			return new WP_Error( 'itsec-dashboard-total-events-db-error', __( 'Error when querying the database for total events.', 'it-l10n-ithemes-security-pro' ) );
		}

		return (int) $count;
	}

	/**
	 * Count the total IPs we are monitoring in the log.
	 *
	 * @param array|string|false $period
	 *
	 * @return int|WP_Error
	 */
	public static function total_ips( $period = false ) {

		if ( false === $period ) {
			$period = array(
				'start' => date( 'Y-m-d', ITSEC_Core::get_current_time_gmt() - 2 * MONTH_IN_SECONDS ),
				'end'   => date( 'Y-m-d', ITSEC_Core::get_current_time_gmt() ),
			);
		}

		if ( is_wp_error( $range = self::_get_range( $period ) ) ) {
			return $range;
		}

		list( $start, $end ) = $range;

		$prepare = array(
			date( 'Y-m-d H:i:s', $start ),
			date( 'Y-m-d H:i:s', $end ),
		);

		global $wpdb;
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT( DISTINCT( `remote_ip` ) ) as c FROM {$wpdb->base_prefix}itsec_logs WHERE `timestamp` BETWEEN %s and %s",
			$prepare )
		);

		if ( false === $count ) {
			return new WP_Error( 'itsec-dashboard-total-ips-db-error', __( 'Error when querying the database for total IPs tracked.', 'it-l10n-ithemes-security-pro' ) );
		}

		return (int) $count;
	}

	/**
	 * Fill the gaps in a range of days
	 *
	 * @param array  $events
	 * @param int    $start
	 * @param int    $end
	 * @param string $format
	 * @param string $increment
	 *
	 * @return array
	 */
	private static function fill_gaps( $events, $start, $end, $format = 'Y-m-d', $increment = '+1 day' ) {

		$now   = date( $format, $start );
		$end_d = date( $format, $end );

		while ( $now < $end_d ) {
			if ( ! isset( $events[ $now ] ) ) {
				$events[ $now ] = 0;
			}

			$now = date( $format, strtotime( "{$now} {$increment}" ) );
		}

		ksort( $events );

		return $events;
	}

	/**
	 * Get the date range for the report query.
	 *
	 * @param string|array $period
	 *
	 * @return int[]|WP_Error
	 */
	public static function _get_range( $period ) {
		if ( is_array( $period ) ) {
			if ( ! isset( $period['start'], $period['end'] ) ) {
				return new WP_Error( 'itsec-dashboard-events-invalid-period', __( 'Invalid Period', 'it-l10n-ithemes-security-pro' ) );
			}

			if ( false === ( $s = strtotime( $period['start'] ) ) || false === ( $e = strtotime( $period['end'] ) ) ) {
				return new WP_Error( 'itsec-dashboard-events-invalid-period', __( 'Invalid Period', 'it-l10n-ithemes-security-pro' ) );
			}

			return array( $s, $e );
		}

		switch ( $period ) {
			case self::P_24_HOURS:
				return array(
					( ITSEC_Core::get_current_time_gmt() - DAY_IN_SECONDS ) - ( ( ITSEC_Core::get_current_time_gmt() - DAY_IN_SECONDS ) % HOUR_IN_SECONDS ),
					ITSEC_Core::get_current_time_gmt(),
				);
			case self::P_WEEK:
				return array(
					strtotime( '-1 week', ITSEC_Core::get_current_time_gmt() ),
					ITSEC_Core::get_current_time_gmt(),
				);
			case self::P_30_DAYS:
				return array(
					strtotime( '-30 days', ITSEC_Core::get_current_time_gmt() ),
					ITSEC_Core::get_current_time_gmt(),
				);
		}

		return new WP_Error( 'itsec-dashboard-events-invalid-period', __( 'Invalid Period', 'it-l10n-ithemes-security-pro' ) );
	}

	/**
	 * Bulk insert events.
	 *
	 * @param string $slug
	 * @param array  $day_to_count
	 *
	 * @return int|WP_Error
	 */
	public static function bulk_insert( $slug, $day_to_count ) {

		global $wpdb;

		$prepare = array();
		$query   = "INSERT INTO {$wpdb->base_prefix}itsec_dashboard_events ( `event_slug`, `event_time`, `event_count`, `event_consolidated` ) VALUES ";

		foreach ( $day_to_count as $time => $count ) {
			$query     .= '(%s,%s,%d,1),';
			$prepare[] = array( $slug, $time, $count );
		}

		$query = substr( $query, 0, - 1 );

		if ( $prepare ) {
			$prepare = call_user_func_array( 'array_merge', $prepare );
		}

		$r = $wpdb->query( $wpdb->prepare( $query, $prepare ) );

		if ( false === $r ) {
			return new WP_Error( 'itsec-dashboard-bulk-insert-query-failed', __( 'Failed to bulk insert events.', 'it-l10n-ithemes-security-pro' ) );
		}

		return $r;
	}

	/**
	 * Backfill dashboard events from log entries.
	 *
	 * @param string $event Event slug.
	 * @param array  $filters
	 *
	 * @return int|WP_Error
	 */
	public static function backfill_events_from_logs( $event, $filters ) {
		if ( ITSEC_Modules::get_setting( 'global', 'log_type' ) === 'file' ) {
			return new WP_Error( 'itsec-dashboard-backfill-logs-invalid-log-type', __( 'Cannot backfill logs if DB logs are not available.', 'it-l10n-ithemes-security-pro' ) );
		}

		$wheres = $prepare = array();

		global $wpdb;

		$prepare[] = date( 'Y-m-d H:i:s', strtotime( '-60 days' ) );
		$wheres[]  = '`timestamp` > %s';

		if ( isset( $filters['module'] ) ) {
			$prepare[] = $filters['module'];
			$wheres[]  = '`module` = %s';
		}

		if ( isset( $filters['code'] ) ) {
			if ( empty( $filters['code_like'] ) ) {
				if ( is_array( $filters['code'] ) ) {
					$wheres[] = '`code` IN (' . implode( ', ', array_fill( 0, count( $filters['code'] ), '%s' ) ) . ')';
					$prepare  = array_merge( $prepare, $filters['code'] );
				} else {
					$prepare[] = $filters['code'];
					$wheres[]  = '`code` = %s';
				}
			} else {
				$prepare[] = $wpdb->esc_like( $filters['code'] ) . '%';
				$wheres[]  = '`code` LIKE %s';
			}
		}

		if ( isset( $filters['where'] ) ) {
			$wheres = array_merge( $wheres, $filters['where'] );
		}

		$where = 'WHERE ' . implode( $wheres, ' AND ' );

		$r = $wpdb->get_results( $wpdb->prepare(
			"SELECT count(`id`) as c, `timestamp` FROM {$wpdb->base_prefix}itsec_logs {$where} GROUP BY MONTH(`timestamp`), DAY(`timestamp`)",
			$prepare
		) );

		if ( false === $r ) {
			return new WP_Error( 'itsec-dashboard-backfill-logs-query-error', __( 'Database error when backfilling logs.', 'it-l10n-ithemes-security-pro' ) );
		}

		$to_insert = array();

		foreach ( $r as $row ) {
			$to_insert[ date( 'Y-m-d', strtotime( $row->timestamp ) ) . ' 00:00:00' ] = $row->c;
		}

		if ( ! $to_insert ) {
			return 0;
		}

		return self::bulk_insert( $event, $to_insert );
	}

	/**
	 * Migrate log table data to the events table.
	 *
	 * @return int[]|WP_Error[]|WP_Error
	 */
	public static function migrate() {

		if ( ITSEC_Modules::get_setting( 'global', 'log_type' ) === 'file' ) {
			return new WP_Error( 'itsec-dashboard-backfill-logs-invalid-log-type', __( 'Cannot backfill logs if DB logs are not available.', 'it-l10n-ithemes-security-pro' ) );
		}

		$list = array(
			'local-brute-force'                    => array(
				'module' => 'brute_force',
				'code'   => array( 'auto-ban-admin-username', 'invalid-login' ),
			),
			'network-brute-force'                  => array(
				'module' => 'ipcheck',
				'code'   => array( 'failed-login-by-blocked-ip', 'successful-login-by-blocked-ip' ),
			),
			'blacklist-four_oh_four'               => array(
				'module' => 'lockout',
				'code'   => 'host-triggered-blacklist',
				'where'  => array(
					'`data` LIKE \'":four_oh_four";\'',
				),
			),
			'blacklist-brute_force'                => array(
				'module' => 'lockout',
				'code'   => 'host-triggered-blacklist',
				'where'  => array(
					'`data` LIKE \'":brute_force";\'',
				),
			),
			'blacklist-brute_force_admin_user'     => array(
				'module' => 'lockout',
				'code'   => 'host-triggered-blacklist',
				'where'  => array(
					'`data` LIKE \'":brute_force_admin_user";\'',
				),
			),
			'blacklist-recaptcha'                  => array(
				'module' => 'lockout',
				'code'   => 'host-triggered-blacklist',
				'where'  => array(
					'`data` LIKE \'":recaptcha";\'',
				),
			),
			'lockout-host'                         => array(
				'module'    => 'lockout',
				'code'      => 'host-lockout::',
				'code_like' => true,
			),
			'lockout-user'                         => array(
				'module'    => 'lockout',
				'code'      => 'user-lockout::',
				'code_like' => true,
			),
			'lockout-username'                     => array(
				'module'    => 'lockout',
				'code'      => 'username-lockout::',
				'code_like' => true,
			),
			'vm-update-plugin'                     => array(
				'module'    => 'version_management',
				'code'      => 'update::plugin,',
				'code_like' => true,
			),
			'vm-update-theme'                      => array(
				'module'    => 'version_management',
				'code'      => 'update::theme,',
				'code_like' => true,
			),
			'vm-update-core'                       => array(
				'module' => 'version_management',
				'code'   => 'update-core',
			),
			'four-oh-four-logged-in'               => array(
				'module' => 'four_oh_four',
				'code'   => 'found_404',
				'where'  => array(
					'`user_id` > 0'
				)
			),
			'four-oh-four'                         => array(
				'module' => 'four_oh_four',
				'code'   => 'found_404',
				'where'  => array(
					'`user_id` = 0'
				)
			),
			'fingerprint-login-blocked'            => array(
				'module' => 'fingerprinting',
				'code'   => 'denied_fingerprint_blocked',
			),
			'fingerprint-status-approved'          => array(
				'module'    => 'fingerprinting',
				'code'      => 'status::approved',
				'code_like' => true,
			),
			'fingerprint-status-auto-approved'     => array(
				'module'    => 'fingerprinting',
				'code'      => 'status::auto-approved',
				'code_like' => true,
			),
			'fingerprint-status-denied'            => array(
				'module'    => 'fingerprinting',
				'code'      => 'status::denied',
				'code_like' => true,
			),
			'fingerprint-session-destroyed'        => array(
				'module' => 'fingerprinting',
				'code'   => 'session_destroyed',
			),
			'fingerprint-session-switched-unknown' => array(
				'module' => 'fingerprinting',
				'code'   => 'session_switched_unknown',
			),
			'fingerprint-session-switched-known'   => array(
				'module' => 'fingerprinting',
				'code'   => 'session_switched_known',
			),
		);

		$results = array();

		foreach ( $list as $event => $filters ) {
			$results[ $event ] = self::backfill_events_from_logs( $event, $filters );
		}

		return $results;
	}

	/**
	 * Export dashboard cards.
	 *
	 * @param int   $dashboard_id
	 * @param array $args
	 *
	 * @return array
	 */
	public static function export_cards( $dashboard_id, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'include' => array( 'size', 'position' )
		) );

		$include = wp_parse_slug_list( $args['include'] );

		$exported = array();

		foreach ( self::get_dashboard_cards( $dashboard_id ) as $card ) {
			$export = array(
				'type' => get_post_meta( $card->ID, ITSEC_Dashboard::META_CARD, true ),
			);

			if ( in_array( 'size', $include, true ) ) {
				$export['size'] = get_post_meta( $card->ID, ITSEC_Dashboard::META_CARD_SIZE, true );
			}

			if ( in_array( 'position', $include, true ) ) {
				$export['position'] = get_post_meta( $card->ID, ITSEC_Dashboard::META_CARD_POSITION, true );
			}

			if ( in_array( 'settings', $include, true ) ) {
				$export['settings'] = get_post_meta( $card->ID, ITSEC_Dashboard::META_CARD_SETTINGS, true );
			}

			$exported[] = $export;
		}

		return $exported;
	}

	/**
	 * Import cards into a dashboard.
	 *
	 * @param int   $dashboard_id
	 * @param array $cards
	 * @param array $args
	 */
	public static function import_cards( $dashboard_id, $cards, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'clear'        => true,
			'skip_unknown' => false,
		) );

		if ( $args['clear'] ) {
			foreach ( self::get_dashboard_cards( $dashboard_id ) as $post ) {
				wp_delete_post( $post->ID );
			}
		}

		$author_id = get_post( $dashboard_id )->post_author;

		foreach ( $cards as $card ) {
			if ( $args['skip_unknown'] && ! self::get_card( $card['type'] ) ) {
				continue;
			}

			$post = array(
				'post_type'   => ITSEC_Dashboard::CPT_CARD,
				'post_parent' => $dashboard_id,
				'post_status' => 'publish',
				'post_author' => $author_id,
				'meta_input'  => array(
					ITSEC_Dashboard::META_CARD          => $card['type'],
					ITSEC_Dashboard::META_CARD_SIZE     => isset( $card['size'] ) ? $card['size'] : array(),
					ITSEC_Dashboard::META_CARD_POSITION => isset( $card['position'] ) ? $card['position'] : array(),
					ITSEC_Dashboard::META_CARD_SETTINGS => isset( $card['settings'] ) ? $card['settings'] : array(),
				),
			);

			wp_insert_post( $post );
		}
	}
}
