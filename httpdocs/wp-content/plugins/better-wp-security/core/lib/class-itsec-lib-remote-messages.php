<?php

class ITSEC_Lib_Remote_Messages {

	const URL = 'https://ithemes.com/api/itsec-service-status.json';
	const OPTION = 'itsec_remote_messages';
	const EVENT = 'remote-messages';

	/** @var array */
	private static $_response;

	/**
	 * Initialize the Remote Messages library.
	 */
	public static function init() {
		if ( ITSEC_Core::is_pro() ) {
			add_action( 'itsec_scheduled_' . self::EVENT, array( __CLASS__, 'run_event' ) );
		}
	}

	public static function get_actions() {

		$response = self::get_response();

		return isset( $response['actions'] ) ? $response['actions'] : array();
	}

	public static function has_action( $action ) {
		return in_array( $action, self::get_actions(), true );
	}

	public static function get_raw_messages() {
		$response = self::get_response();

		return isset( $response['messages'] ) ? $response['messages'] : array();
	}

	public static function get_messages_for_placement( $placement ) {

		$matched = array();

		foreach ( self::get_raw_messages() as $message ) {
			if ( in_array( $placement, $message['placement'], true ) ) {
				$matched[] = array(
					'message' => $message['message'],
					'type'    => $message['type'],
				);
			}
		}

		return $matched;
	}

	/**
	 * Run the event to fetch the data.
	 *
	 * @param ITSEC_Job $job
	 */
	public static function run_event( $job ) {

		$response = wp_remote_get( self::URL, array(
			'user-agent' => 'WordPress',
		) );

		if ( is_wp_error( $response ) ) {
			$job->reschedule_in( 5 * MINUTE_IN_SECONDS );

			return;
		}

		$data = wp_remote_retrieve_body( $response );

		if ( ! $data ) {
			$job->reschedule_in( 5 * MINUTE_IN_SECONDS );

			return;
		}

		$json = json_decode( $data, true );

		if ( ! $json ) {
			$job->reschedule_in( 5 * MINUTE_IN_SECONDS );

			return;
		}

		$json = wp_parse_args( $json, array(
			'ttl'      => HOUR_IN_SECONDS,
			'messages' => array(),
			'actions'  => array(),
		) );

		$sanitized = array(
			'messages' => array(),
			'actions'  => wp_parse_slug_list( $json['actions'] ),
		);

		foreach ( $json['messages'] as $message ) {
			$sanitized['messages'][] = array(
				'message'   => self::sanitize_message( $message['message'] ),
				'type'      => self::sanitize_type( $message['type'] ),
				'placement' => $message['placement'],
			);
		}

		update_site_option( self::OPTION, array(
			'response'  => $sanitized,
			'ttl'       => $json['ttl'],
			'requested' => ITSEC_Core::get_current_time_gmt(),
		) );
	}

	private static function sanitize_message( $message ) {
		return wp_kses( $message, array( 'a' => array( 'href' => true ) ) );
	}

	private static function sanitize_type( $type ) {
		if ( in_array( $type, array( 'success', 'info', 'warning', 'error' ), true ) ) {
			return $type;
		}

		return 'info';
	}

	private static function get_response() {

		if ( ! ITSEC_Core::is_pro() ) {
			return array();
		}

		if ( isset( self::$_response ) ) {
			return self::$_response;
		}

		$data = get_site_option( self::OPTION, array() );
		$data = wp_parse_args( $data, array(
			'response'  => array(),
			'requested' => 0,
			'ttl'       => 0,
		) );

		if ( ! $data['response'] ) {
			self::schedule_check();

			return self::$_response = array();
		}

		if ( $data['requested'] + $data['ttl'] < ITSEC_Core::get_current_time_gmt() ) {
			self::schedule_check();
			$events = ITSEC_Core::get_scheduler()->get_single_events();

			foreach ( $events as $event ) {
				if ( self::EVENT === $event['id'] && $event['fire_at'] + HOUR_IN_SECONDS > ITSEC_Core::get_current_time_gmt() ) {
					return self::$_response = $data['response'];
				}
			}

			return self::$_response = array();
		}

		return self::$_response = $data['response'];
	}

	private static function schedule_check() {
		$s = ITSEC_Core::get_scheduler();

		if ( ! $s->is_single_scheduled( self::EVENT, null ) ) {
			$s->schedule_once( ITSEC_Core::get_current_time_gmt() + 60, self::EVENT );
		}
	}
}
