<?php

/**
 * File Change Detection Execution and Processing
 *
 * Handles all file change detection execution once the feature has been
 * enabled by the user.
 *
 * @since   4.0.0
 *
 * @package iThemes_Security
 */
class ITSEC_File_Change {

	/**
	 * Setup the module's functionality
	 *
	 * Loads the file change detection module's unpriviledged functionality including
	 * performing the scans themselves
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	function run() {

		add_action( 'itsec_execute_file_check_cron', array( $this, 'run_scan' ) ); //Action to execute during a cron run.

		add_action( 'ithemes_sync_register_verbs', array( $this, 'register_sync_verbs' ) );
		add_filter( 'itsec_notifications', array( $this, 'register_notification' ) );
		add_filter( 'itsec_file-change_notification_strings', array( $this, 'register_notification_strings' ) );

		add_action( 'itsec_scheduler_register_events', array( $this, 'register_event' ) );
		add_action( 'itsec_scheduled_file-change', array( $this, 'run_scan' ) );
	}

	public function run_scan() {
		require_once( dirname( __FILE__ ) . '/scanner.php' );

		return ITSEC_File_Change_Scanner::run_scan();
	}

	/**
	 * Register the file change scan event.
	 *
	 * @param ITSEC_Scheduler $scheduler
	 */
	public function register_event( $scheduler ) {

		// If we're splitting the file check run it every 6 hours.
		$split    = ITSEC_Modules::get_setting( 'file-change', 'split', false );
		$interval = $split ? ITSEC_Scheduler::S_FOUR_DAILY : ITSEC_Scheduler::S_DAILY;

		$scheduler->schedule( $interval, 'file-change' );
	}

	/**
	 * Register verbs for Sync.
	 *
	 * @since 3.6.0
	 *
	 * @param Ithemes_Sync_API $api Sync API object.
	 */
	public function register_sync_verbs( $api ) {
		$api->register( 'itsec-perform-file-scan', 'Ithemes_Sync_Verb_ITSEC_Perform_File_Scan', dirname( __FILE__ ) . '/sync-verbs/itsec-perform-file-scan.php' );
	}

	/**
	 * Register the file change notification.
	 *
	 * @param array $notifications
	 *
	 * @return array
	 */
	public function register_notification( $notifications ) {
		$notifications['file-change'] = array(
			'recipient'        => ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE,
			'schedule'         => ITSEC_Notification_Center::S_NONE,
			'subject_editable' => true,
			'optional'         => true,
			'module'           => 'file-change',
		);

		return $notifications;
	}

	/**
	 * Register the file change notification strings.
	 *
	 * @return array
	 */
	public function register_notification_strings() {
		return array(
			'label'       => esc_html__( 'File Change', 'better-wp-security' ),
			'description' => sprintf( esc_html__( 'The %1$sFile Change Detection%2$s module will email a file scan report after changes have been detected.', 'better-wp-security' ), '<a href="#" data-module-link="file-change">', '</a>' ),
			'subject'     => esc_html__( 'File Change Warning', 'better-wp-security' ),
		);
	}
}
