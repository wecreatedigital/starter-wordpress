<?php

class ITSEC_Scheduler_Page_Load extends ITSEC_Scheduler {

	const OPTION = 'itsec_scheduler_page_load';

	private $operating_data;

	public function schedule( $schedule, $id, $data = array(), $opts = array() ) {

		if ( ! $this->scheduling_lock() ) {
			return false;
		}

		if ( $this->is_recurring_scheduled( $id ) ) {
			$this->scheduling_unlock();

			return false;
		}

		if ( isset( $opts['fire_at'] ) ) {
			$last_fired = $opts['fire_at'];
		} else {
			// Prevent an event stampede
			$last_fired = ITSEC_Core::get_current_time_gmt() + 60 * mt_rand( 1, 30 );
		}

		$last_fired -= $this->get_schedule_interval( $schedule );

		$options = $this->operating_data ? $this->operating_data : $this->get_options();

		$options['recurring'][ $id ] = array(
			'schedule'   => $schedule,
			'last_fired' => $last_fired,
			'data'       => $data,
		);

		$set = $this->set_options( $options );
		$this->scheduling_unlock();

		return $set;
	}

	public function schedule_once( $at, $id, $data = array() ) {

		if ( ! $this->scheduling_lock() ) {
			return false;
		}

		if ( $this->is_single_scheduled( $id, $data ) ) {
			$this->scheduling_unlock();

			return false;
		}

		$hash    = $this->hash_data( $data );
		$options = $this->operating_data ? $this->operating_data : $this->get_options();

		if ( ! isset( $options['single'][ $id ] ) ) {
			$options['single'][ $id ] = array();
		}

		$options['single'][ $id ][ $hash ] = array(
			'data'    => $data,
			'fire_at' => $at,
		);

		$set = $this->set_options( $options );
		$this->scheduling_unlock();

		return $set;
	}

	public function is_recurring_scheduled( $id ) {
		$options = $this->get_options();

		return ! empty( $options['recurring'][ $id ] );
	}

	public function is_single_scheduled( $id, $data = array() ) {

		$options = $this->get_options();

		if ( empty( $options['single'][ $id ] ) ) {
			return false;
		}

		if ( null !== $data ) {
			$hash = $this->hash_data( $data );

			if ( empty( $options['single'][ $id ][ $hash ] ) ) {
				return false;
			}
		}

		return true;
	}

	public function unschedule( $id ) {

		$options = $this->operating_data ? $this->operating_data : $this->get_options();

		if ( isset( $options['recurring'][ $id ] ) ) {
			unset( $options['recurring'][ $id ] );

			return $this->set_options( $options );
		}

		return false;
	}

	public function unschedule_single( $id, $data = array() ) {

		$options = $this->operating_data ? $this->operating_data : $this->get_options();

		if ( ! isset( $options['single'][ $id ] ) ) {
			return false;
		}

		if ( null === $data ) {
			unset( $options['single'][ $id ] );
		} else {
			$hash = $this->hash_data( $data );

			if ( ! isset( $options['single'][ $id ][ $hash ] ) ) {
				return false;
			}

			unset( $options['single'][ $id ][ $hash ] );
		}

		return $this->set_options( $options );
	}

	public function get_recurring_events() {
		$options = $this->get_options();
		$events  = array();

		foreach ( $options['recurring'] as $id => $event ) {
			$events[] = array(
				'id'       => $id,
				'data'     => $event['data'],
				'schedule' => $event['schedule'],
				'fire_at'  => $event['last_fired'] + $this->get_schedule_interval( $event['schedule'] ),
			);
		}

		return $events;
	}

	public function get_single_events() {

		$options = $this->get_options();
		$events  = array();

		foreach ( $options['single'] as $id => $hashes ) {
			foreach ( $hashes as $hash => $event ) {
				$events[] = array(
					'id'      => $id,
					'data'    => $event['data'],
					'fire_at' => $event['fire_at'],
					'hash'    => $hash,
				);
			}
		}

		return $events;
	}

	public function run() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {

		if ( ITSEC_Core::is_api_request() ) {
			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		$this->run_due_now();
	}

	public function run_due_now( $now = 0 ) {
		$now     = $now ? $now : ITSEC_Core::get_current_time_gmt();
		$options = $this->get_options();

		$to_process = array();

		foreach ( $options['single'] as $id => $hashes ) {
			foreach ( $hashes as $hash => $event ) {
				if ( $event['fire_at'] < $now ) {
					$to_process[] = array_merge( $event, array( 'id' => $id ) );
				}
			}
		}

		foreach ( $options['recurring'] as $id => $event ) {
			if ( $this->is_time_to_send( $event['schedule'], $event['last_fired'] ) ) {
				$to_process[] = array_merge( $event, array( 'id' => $id ) );
			}
		}

		if ( ! $to_process ) {
			return;
		}

		if ( ! ITSEC_Lib::get_lock( 'scheduler', 120 ) ) {
			return;
		}

		$raw = $this->operating_data = ITSEC_Lib::get_uncached_option( self::OPTION );

		foreach ( $to_process as $process ) {
			if ( isset( $process['fire_at'] ) ) {

				if ( ! isset( $raw['single'][ $process['id'] ][ $this->hash_data( $process['data'] ) ] ) ) {
					continue; // Another process already fired this single event.
				}

				$event = $raw['single'][ $process['id'] ][ $this->hash_data( $process['data'] ) ];

				if ( $event['fire_at'] < $now ) {
					$this->run_single_event( $process['id'], $event['data'] );
				}
			} else {
				$event = $raw['recurring'][ $process['id'] ];

				if ( $this->is_time_to_send( $event['schedule'], $event['last_fired'] ) ) {
					$this->run_recurring_event( $process['id'] );
					$this->update_last_fired( $process['id'] );
				}
			}
		}

		$this->operating_data = null;
		ITSEC_Lib::release_lock( 'scheduler' );
	}

	public function run_recurring_event( $id ) {

		if ( $this->operating_data ) {
			$clear_operating_data = false;
			$storage              = $this->operating_data;
		} else {
			$clear_operating_data = true;
			$storage              = $this->operating_data = $this->get_options();
		}

		$event = $storage['recurring'][ $id ];

		$job = $this->make_job( $id, $event['data'] );

		if ( $this->is_retry_scheduled( $id, $event['data'] ) ) {
			return;
		}

		$this->call_action( $job );

		if ( $clear_operating_data ) {
			$this->operating_data = null;
		}
	}

	public function run_single_event( $id, $data = array() ) {
		$this->run_single_event_by_hash( $id, $this->hash_data( $data ) );
	}

	/**
	 * @inheritDoc
	 */
	public function run_single_event_by_hash( $id, $hash ) {

		if ( $this->operating_data ) {
			$clear_operating_data = false;
			$storage              = $this->operating_data;
		} else {
			$clear_operating_data = true;
			$storage              = $this->operating_data = $this->get_options();
		}

		if ( ! isset( $storage['single'][ $id ][ $hash ] ) ) {
			if ( $clear_operating_data ) {
				$this->operating_data = null;
			}

			return;
		}

		$event = $storage['single'][ $id ][ $hash ];

		$job = $this->make_job( $id, $event['data'], array( 'single' => true ) );

		$this->unschedule_single( $id, $event['data'] );
		$this->call_action( $job );

		if ( $clear_operating_data ) {
			$this->operating_data = null;
		}
	}

	/**
	 * Update the time the job was last fired to now.
	 *
	 * @param string $id
	 */
	private function update_last_fired( $id ) {

		$storage = $this->operating_data ? $this->operating_data : $this->get_options();

		$storage['recurring'][ $id ]['last_fired'] = ITSEC_Core::get_current_time_gmt();

		$this->set_options( $storage );
	}

	/**
	 * Is a retry of the given job scheduled.
	 *
	 * @param string $id
	 * @param array  $data
	 *
	 * @return bool
	 */
	private function is_retry_scheduled( $id, $data ) {
		$options = $this->operating_data ? $this->operating_data : $this->get_options();

		if ( ! isset( $options['single'][ $id ] ) ) {
			return false;
		}

		foreach ( $options['single'][ $id ] as $hash => $event ) {

			$event_data = $event['data'];

			if ( ! isset( $event_data['retry_count'] ) ) {
				continue;
			}

			unset( $event_data['retry_count'] );

			if ( $this->hash_data( $event_data ) === $this->hash_data( $data ) ) {
				return true;
			}
		}

		return false;
	}

	private function is_time_to_send( $schedule, $last_sent ) {

		if ( ! $last_sent ) {
			return true;
		}

		$period = $this->get_schedule_interval( $schedule );

		if ( ! $period ) {
			return false;
		}

		return ( $last_sent + $period ) < ITSEC_Core::get_current_time_gmt();
	}

	private function get_options() {
		return wp_parse_args( get_site_option( self::OPTION, array() ), array(
			'single'    => array(),
			'recurring' => array(),
		) );
	}

	private function set_options( $options ) {

		if ( $this->operating_data !== null ) {
			$this->operating_data = $options;
		}

		return update_site_option( self::OPTION, $options );
	}

	public function uninstall() {
		delete_site_option( self::OPTION );
	}
}
