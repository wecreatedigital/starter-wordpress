<?php

abstract class ITSEC_Scheduler {

	const S_HOURLY = 'hourly';
	const S_FOUR_DAILY = 'four-daily';
	const S_TWICE_DAILY = 'twice-daily';
	const S_DAILY = 'daily';
	const S_WEEKLY = 'weekly';
	const S_MONTHLY = 'monthly';

	const LOCK_SCHEDULING = 'scheduling';

	/** @var array */
	protected $custom_schedules = array();

	/**
	 * Schedule a recurring event.
	 *
	 * Only one event with the given id can be scheduled at a time.
	 *
	 * @param string $schedule
	 * @param string $id
	 * @param array  $data
	 * @param array  $opts
	 *  - fire_at: Manually specify the first time the event should be fired.
	 *
	 * @return bool
	 */
	abstract public function schedule( $schedule, $id, $data = array(), $opts = array() );

	/**
	 * Schedule a single event.
	 *
	 * Only one event with the given id and same set of data can be scheduled at the same time.
	 *
	 * Unlike Core's CRON implementation, event if a single event is more than 10 minutes in the future, it cannot be scheduled.
	 *
	 * @param int    $at
	 * @param string $id
	 * @param array  $data
	 *
	 * @return bool
	 */
	abstract public function schedule_once( $at, $id, $data = array() );

	/**
	 * Schedule a single event to run soon.
	 *
	 * @param string $id
	 * @param array  $data
	 *
	 * @return bool
	 */
	public function schedule_soon( $id, $data = array() ) {
		return $this->schedule_once( ITSEC_Core::get_current_time_gmt() + 60 * mt_rand( 1, 10 ), $id, $data );
	}

	/**
	 * Is a recurring event scheduled.
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	abstract public function is_recurring_scheduled( $id );

	/**
	 * Is a single event scheduled with the given data.
	 *
	 * @param string     $id   The event ID to check.
	 * @param array|null $data The event data. Pass null to check if any event is scheduled with that ID,
	 *                         regardless of the data.
	 *
	 * @return bool
	 */
	abstract public function is_single_scheduled( $id, $data = array() );

	/**
	 * Unschedule a recurring event.
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	abstract public function unschedule( $id );

	/**
	 * Unschedule a single event.
	 *
	 * The data specified needs to be identical to the data the single event was scheduled with.
	 *
	 * @param string $id
	 * @param array  $data
	 *
	 * @return bool
	 */
	abstract public function unschedule_single( $id, $data = array() );

	/**
	 * Get all of the scheduled recurring events.
	 *
	 * Each event is an array with the following properties.
	 *  - id: The ID the event was scheduled with.
	 *  - data: The data the event was scheduled with.
	 *  - fire_at: The next time the event should be fired.
	 *  - schedule: The selected schedule. See S_HOURLY, etc...
	 *
	 * @return array
	 */
	abstract public function get_recurring_events();

	/**
	 * Get all of the single events.
	 *
	 * Each event is an array with the following properties.
	 *  - id: The ID the event was scheduled with.
	 *  - data: The data the event was scheduled with.
	 *  - fire_at: The time the event should be fired.
	 *
	 * @return array
	 */
	abstract public function get_single_events();

	/**
	 * Run a recurring event, even if it is not time to.
	 *
	 * This will _not_ update the last fired time.
	 *
	 * @param string $id
	 *
	 * @return void
	 */
	abstract public function run_recurring_event( $id );

	/**
	 * Run a single event, even if it is not time to.
	 *
	 * This will clear the event from the schedule.
	 *
	 * @param string $id
	 * @param array  $data
	 *
	 * @return void
	 */
	abstract public function run_single_event( $id, $data = array() );

	/**
	 * Code executed on every page load to setup the scheduler.
	 *
	 * @return void
	 */
	abstract public function run();

	/**
	 * Manually trigger modules to register their scheduled events.
	 *
	 * @return void
	 */
	public function register_events() {
		/**
		 * Register scheduled events.
		 *
		 * Events should be registered in response to a user action, for example activating a module or changing a setting.
		 * Occasionally, iThemes Security will manually ask for all events to be scheduled.
		 *
		 * @param ITSEC_Scheduler $this
		 */
		do_action( 'itsec_scheduler_register_events', $this );
	}

	/**
	 * Register a custom schedule.
	 *
	 * @param string $slug
	 * @param int $interval
	 */
	public function register_custom_schedule( $slug, $interval ) {
		$this->custom_schedules[ $slug ] = $interval;
	}

	/**
	 * Get a lock to be used for scheduling events.
	 *
	 * @return bool
	 */
	protected function scheduling_lock() {
		return ITSEC_Lib::get_lock( self::LOCK_SCHEDULING, 5 );
	}

	/**
	 * Release the lock used for scheduling events.
	 */
	protected function scheduling_unlock() {
		ITSEC_Lib::release_lock( self::LOCK_SCHEDULING );
	}

	/**
	 * Make a job object.
	 *
	 * @param string $id
	 * @param array  $data
	 * @param array  $opts
	 *
	 * @return ITSEC_Job
	 */
	protected function make_job( $id, $data, $opts = array() ) {
		return new ITSEC_Job( $this, $id, $data, $opts );
	}

	/**
	 * Dispatch the action to execute the scheduled job.
	 *
	 * @param ITSEC_Job $job
	 */
	protected final function call_action( ITSEC_Job $job ) {
		/**
		 * Fires when a scheduled job should be executed.
		 *
		 * @param ITSEC_Job $job
		 */
		do_action( "itsec_scheduled_{$job->get_id()}", $job );
	}

	/**
	 * Generate a unique hash of the data.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	protected function hash_data( $data ) {
		return md5( serialize( $data ) );
	}

	/**
	 * Get the interval for the schedule.
	 *
	 * @param string $schedule
	 *
	 * @return int
	 */
	protected final function get_schedule_interval( $schedule ) {
		switch ( $schedule ) {
			case self::S_HOURLY:
				return HOUR_IN_SECONDS;
			case self::S_FOUR_DAILY:
				return DAY_IN_SECONDS / 4;
			case self::S_TWICE_DAILY:
				return DAY_IN_SECONDS / 2;
			case self::S_DAILY:
				return DAY_IN_SECONDS;
			case self::S_WEEKLY:
				return WEEK_IN_SECONDS;
			case self::S_MONTHLY:
				return MONTH_IN_SECONDS;
			default:
				return isset( $this->custom_schedules[ $schedule ] ) ? $this->custom_schedules[ $schedule ] : false;
		}
	}

	/**
	 * Run code when the plugin is uninstalled.
	 */
	public function uninstall() {

	}
}