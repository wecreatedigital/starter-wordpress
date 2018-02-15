<?php

class ITSEC_Scheduler_Cron extends ITSEC_Scheduler {

	const HOOK = 'itsec_cron';
	const OPTION = 'itsec_cron';

	public function run() {
		add_action( self::HOOK, array( $this, 'process' ), 10, 2 );
		add_filter( 'cron_schedules', array( $this, 'register_cron_schedules' ) );
	}

	public function register_cron_schedules( $schedules ) {

		$schedules[ 'itsec-' . self::S_FOUR_DAILY ] = array(
			'display'  => esc_html__( 'Four Times per Day', 'better-wp-security' ),
			'interval' => DAY_IN_SECONDS / 4,
		);
		$schedules[ 'itsec-' . self::S_WEEKLY ]     = array(
			'display'  => esc_html__( 'Weekly', 'better-wp-security' ),
			'interval' => WEEK_IN_SECONDS,
		);
		$schedules[ 'itsec-' . self::S_MONTHLY ]    = array(
			'display'  => esc_html__( 'Monthly', 'better-wp-security' ),
			'interval' => MONTH_IN_SECONDS,
		);

		foreach ( $this->custom_schedules as $schedule => $interval ) {
			$schedules[ 'itsec-' . $schedule ] = array(
				'display'  => ucfirst( $schedule ),
				'interval' => $interval,
			);
		}

		return $schedules;
	}

	public function process( $id, $hash = null ) {

		if ( $hash ) {
			$this->run_single_event_by_hash( $id, $hash );
		} else {
			$this->run_recurring_event( $id );
		}
	}

	public function run_recurring_event( $id ) {

		$storage = $this->get_options();
		$data    = $storage['recurring'][ $id ]['data'];

		$job = $this->make_job( $id, $data );

		if ( $this->is_retry_scheduled( $id, $data ) ) {
			return;
		}

		$this->call_action( $job );
	}

	public function run_single_event( $id, $data = array() ) {
		$this->run_single_event_by_hash( $id, $this->hash_data( $data ) );
	}

	/**
	 * Run a single event.
	 *
	 * @param string $id
	 * @param string $hash
	 */
	private function run_single_event_by_hash( $id, $hash ) {

		$opts = array( 'single' => true );

		$storage = $this->get_options();
		$data    = $storage['single'][ $id ][ $hash ]['data'];

		$job = $this->make_job( $id, $data, $opts );

		$this->call_action( $job );
		$this->unschedule_single( $id, $data );
	}

	public function is_recurring_scheduled( $id ) {
		return (bool) wp_next_scheduled( self::HOOK, array( $id ) );
	}

	public function is_single_scheduled( $id, $data = array() ) {

		if ( null === $data ) {
			$options = $this->get_options();

			if ( ! isset( $options['single'][ $id ] ) ) {
				return false;
			}

			foreach ( $options['single'][ $id ] as $hash => $event ) {
				if ( wp_next_scheduled( self::HOOK, array( $id, $hash ) ) ) {
					return true;
				}
			}

			return false;
		}

		return (bool) wp_next_scheduled( self::HOOK, array( $id, $this->hash_data( $data ) ) );
	}

	public function schedule( $schedule, $id, $data = array(), $opts = array() ) {

		if ( ! $this->scheduling_lock() ) {
			return false;
		}

		if ( $this->is_recurring_scheduled( $id ) ) {
			$this->scheduling_unlock();

			return false;
		}

		$options = $this->get_options();

		$options['recurring'][ $id ] = array( 'data' => $data );
		$this->set_options( $options );

		$args = array( $id );

		// Prevent a flood of cron events all occurring at the same time.
		$time      = isset( $opts['fire_at'] ) ? $opts['fire_at'] : ITSEC_Core::get_current_time_gmt() + 60 * mt_rand( 1, 30 );
		$scheduled = wp_schedule_event( $time, $this->cron_name_for_schedule( $schedule ), self::HOOK, $args );
		$this->scheduling_unlock();

		if ( false === $scheduled ) {
			return false;
		}

		return true;
	}

	public function schedule_once( $at, $id, $data = array() ) {

		if ( ! $this->scheduling_lock() ) {
			return false;
		}

		if ( $this->is_single_scheduled( $id, $data ) ) {
			$this->scheduling_unlock();

			return false;
		}

		$hash = $this->hash_data( $data );
		$args = array( $id, $hash );

		$options                           = $this->get_options();
		$options['single'][ $id ][ $hash ] = array( 'data' => $data );
		$this->set_options( $options );

		$scheduled = wp_schedule_single_event( $at, self::HOOK, $args );
		$this->scheduling_unlock();

		if ( false === $scheduled ) {
			return false;
		}

		return true;
	}

	public function unschedule( $id ) {

		$hash = $this->make_cron_hash( $id );

		if ( $this->unschedule_by_hash( $hash ) ) {

			$options = $this->get_options();
			unset( $options['recurring'][ $id ] );
			$this->set_options( $options );

			return true;
		}

		return false;
	}

	public function unschedule_single( $id, $data = array() ) {
		$data_hash = $this->hash_data( $data );
		$hash      = $this->make_cron_hash( $id, $data );

		if ( $this->unschedule_by_hash( $hash ) ) {

			$options = $this->get_options();
			unset( $options['single'][ $id ][ $data_hash ] );
			$this->set_options( $options );

			return true;
		}

		return false;
	}

	private function unschedule_by_hash( $hash ) {

		$crons = _get_cron_array();
		$found = false;

		foreach ( $crons as $timestamp => $hooks ) {
			if ( isset( $hooks[ self::HOOK ][ $hash ] ) ) {
				$found = true;
				unset( $crons[ $timestamp ][ self::HOOK ][ $hash ] );
				break;
			}
		}

		if ( $found ) {
			_set_cron_array( $crons );
		}

		return $found;
	}

	public function get_recurring_events() {

		$crons   = _get_cron_array();
		$options = $this->get_options();
		$events  = array();

		foreach ( $crons as $timestamp => $hooks ) {

			if ( ! isset( $hooks[ self::HOOK ] ) ) {
				continue;
			}

			foreach ( $hooks[ self::HOOK ] as $key => $cron_event ) {

				list( $id ) = $cron_event['args'];

				if ( ! isset( $options['recurring'][ $id ] ) || isset( $cron_event['args'][1] ) ) {
					continue;
				}

				$events[] = array(
					'id'       => $id,
					'data'     => $options['recurring'][ $id ]['data'],
					'fire_at'  => $timestamp,
					'schedule' => $this->get_api_schedule_from_cron_schedule( $cron_event['schedule'] ),
				);
			}
		}

		return $events;
	}

	public function get_single_events() {

		$crons   = _get_cron_array();
		$options = $this->get_options();
		$events  = array();

		foreach ( $crons as $timestamp => $hooks ) {

			if ( ! isset( $hooks[ self::HOOK ] ) ) {
				continue;
			}

			foreach ( $hooks[ self::HOOK ] as $key => $cron_event ) {

				$id = $cron_event['args'][0];

				if ( ! isset( $options['single'][ $id ], $cron_event['args'][1] ) ) {
					continue;
				}

				$hash = $cron_event['args'][1];

				if ( ! isset( $options['single'][ $id ][ $hash ] ) ) {
					continue; // Sanity check
				}

				$events[] = array(
					'id'      => $id,
					'data'    => $options['single'][ $id ][ $hash ]['data'],
					'fire_at' => $timestamp,
				);
			}
		}

		return $events;
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
		$options = $this->get_options();

		if ( ! isset( $options['single'][ $id ] ) ) {
			return false;
		}

		foreach ( $options['single'][ $id ] as $hash => $event ) {
			$maybe_data = $event['data'];

			if ( ! isset( $maybe_data['retry_count'] ) ) {
				continue;
			}

			unset( $maybe_data['retry_count'] );

			if ( $this->hash_data( $maybe_data ) === $this->hash_data( $data ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Make the Cron hash that WordPress uses to uniquely identify cron events by.
	 *
	 * @param string $id
	 * @param array  $data
	 *
	 * @return string
	 */
	private function make_cron_hash( $id, $data = null ) {

		if ( func_num_args() === 1 ) {
			return md5( serialize( array( $id ) ) );
		}

		return md5( serialize( array( $id, $this->hash_data( $data ) ) ) );
	}

	private function cron_name_for_schedule( $schedule ) {
		switch ( $schedule ) {
			case self::S_HOURLY:
			case self::S_DAILY:
				return $schedule;
			case self::S_TWICE_DAILY:
				return 'twicedaily';
			default:
				return "itsec-{$schedule}";
		}
	}

	/**
	 * Get the API schedule name from the Cron schedule name.
	 *
	 * @param string $cron_schedule
	 *
	 * @return string
	 */
	private function get_api_schedule_from_cron_schedule( $cron_schedule ) {
		$api_schedule = str_replace( 'itsec-', '', $cron_schedule );

		if ( $api_schedule === 'twicedaily' ) {
			$api_schedule = 'twice-daily';
		}

		return $api_schedule;
	}

	private function get_options() {
		return wp_parse_args( get_site_option( self::OPTION, array() ), array(
			'single'    => array(),
			'recurring' => array(),
		) );
	}

	private function set_options( $options ) {
		update_site_option( self::OPTION, $options );
	}

	public function uninstall() {

		$crons = _get_cron_array();

		foreach ( $crons as $timestamp => $args ) {
			unset( $crons[ $timestamp ][ self::HOOK ] );

			if ( empty( $crons[ $timestamp ] ) ) {
				unset( $crons[ $timestamp ] );
			}
		}

		_set_cron_array( $crons );

		delete_site_option( self::OPTION );
	}
}