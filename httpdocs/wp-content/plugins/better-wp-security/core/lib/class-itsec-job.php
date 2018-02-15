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
	 * @param int $seconds
	 */
	public function reschedule_in( $seconds ) {
		$data = $this->get_data();

		if ( isset( $data['retry_count'] ) ) {
			$data['retry_count'] ++;
		} else {
			$data['retry_count'] = 1;
		}

		$this->scheduler->schedule_once( ITSEC_Core::get_current_time_gmt() + $seconds, $this->id, $data );
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