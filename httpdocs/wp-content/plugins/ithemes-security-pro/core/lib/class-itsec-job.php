<?php

class ITSEC_Job {

	/** @var ITSEC_Scheduler */
	private $scheduler;

	/** @var string */
	private $id;

	/** @var array */
	private $data;

	/** @var array */
	private $opts;

	/**
	 * ITSEC_Job constructor.
	 *
	 * @param ITSEC_Scheduler $scheduler
	 * @param string          $id
	 * @param array           $data
	 * @param array           $opts
	 */
	public function __construct( ITSEC_Scheduler $scheduler, $id, $data = array(), $opts = array() ) {
		$this->scheduler = $scheduler;
		$this->id        = $id;
		$this->data      = $data;
		$this->opts      = $opts;
	}

	/**
	 * Reschedule a job in some number of seconds.
	 *
	 * The original event will not fire while a reschedule is pending.
	 *
	 * @param int   $seconds
	 * @param array $data Additional data to attach to the rescheduled event.
	 */
	public function reschedule_in( $seconds, $data = array() ) {
		$data = array_merge( $this->data, $data );

		if ( isset( $data['retry_count'] ) ) {
			$data['retry_count'] ++;
		} else {
			$data['retry_count'] = 1;
		}

		$this->scheduler->schedule_once( ITSEC_Core::get_current_time_gmt() + $seconds, $this->id, $data );
	}

	/**
	 * Schedule the next loop item.
	 *
	 * @param array $data Data to provide to the next event.
	 */
	public function schedule_next_in_loop( $data = array() ) {
		if ( ! $config = $this->scheduler->get_loop( $this->get_id() ) ) {
			return;
		}

		$data = array_merge( $this->get_data(), $data, array(
			'loop_item' => $this->data['loop_item'] + 1,
		) );

		$this->scheduler->schedule_once( ITSEC_Core::get_current_time_gmt() + $config['wait'], $this->get_id(), $data );
	}

	/**
	 * Schedule the loop to start over again.
	 *
	 * @param array $data
	 */
	public function schedule_new_loop( $data = array() ) {

		if ( ! $config = $this->scheduler->get_loop( $this->get_id() ) ) {
			return;
		}

		$start    = $this->data['loop_start'];
		$interval = $this->scheduler->get_schedule_interval( $config['schedule'] );
		$now      = ITSEC_Core::get_current_time_gmt();

		$next = $start + $interval < $now ? $now + $config['wait'] : $start + $interval;

		$this->scheduler->schedule_loop( $this->get_id(), $data, array(
			'fire_at' => $next,
		) );
	}

	/**
	 * Get the retry count for this job.
	 *
	 * @return int|false
	 */
	public function is_retry() {
		$data = $this->get_data();

		if ( empty( $data['retry_count'] ) ) {
			return false;
		}

		return $data['retry_count'];
	}

	/**
	 * Get the ID of this job.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the data attached to the job.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Is this a single event.
	 *
	 * @return bool
	 */
	public function is_single() {
		return ! empty( $this->opts['single'] );
	}
}