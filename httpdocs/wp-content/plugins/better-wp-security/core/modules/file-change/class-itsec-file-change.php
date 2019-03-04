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
		add_action( 'init', array( $this, 'health_check' ) );
		add_action( 'ithemes_sync_register_verbs', array( $this, 'register_sync_verbs' ) );
		add_filter( 'itsec_notifications', array( $this, 'register_notification' ) );
		add_filter( 'itsec_file-change_notification_strings', array( $this, 'register_notification_strings' ) );

		add_action( 'itsec_lib_write_to_file', array( $this, 'write_to_file' ) );
		add_action( 'itsec_lib_delete_file', array( $this, 'delete_file' ) );

		add_filter( 'heartbeat_received', array( $this, 'heartbeat' ), 10, 2 );

		add_action( 'itsec_scheduler_register_events', array( $this, 'register_event' ) );
		add_action( 'itsec_scheduled_file-change', array( $this, 'run_scan' ) );
		add_action( 'itsec_scheduled_file-change-fast', array( $this, 'run_scan' ) );
		ITSEC_Core::get_scheduler()->register_loop( 'file-change', ITSEC_Scheduler::S_DAILY, 60 );
		ITSEC_Core::get_scheduler()->register_loop( 'file-change-fast', ITSEC_Scheduler::S_DAILY, 0 );
	}

	public function run_scan( $job ) {
		require_once( dirname( __FILE__ ) . '/scanner.php' );

		$scanner = new ITSEC_File_Change_Scanner();
		$scanner->run( $job );
	}

	public function health_check() {

		$storage = self::make_progress_storage();

		if ( ! $health_check = $storage->health_check() ) {
			return;
		}

		// No need to worry yet.
		if ( $health_check + 300 > ITSEC_Core::get_current_time_gmt() ) {
			return;
		}

		if ( ITSEC_Core::get_scheduler()->is_single_scheduled( $storage->get( 'id' ), null ) ) {
			return;
		}

		require_once( dirname( __FILE__ ) . '/scanner.php' );
		ITSEC_File_Change_Scanner::recover();
	}

	/**
	 * When iThemes Security writes to a file, store the file's hash so the change is not seen as unexpected.
	 *
	 * @param string $file
	 */
	public function write_to_file( $file ) {
		$hashes = ITSEC_Modules::get_setting( 'file-change', 'expected_hashes', array() );
		$hash   = @md5_file( $file );

		if ( $hash && ( ! isset( $hashes[ $file ] ) || $hashes[ $file ] !== $hash ) ) {
			$hashes[ $file ] = $hash;
			ITSEC_Modules::set_setting( 'file-change', 'expected_hashes', $hashes );
		}
	}

	/**
	 * When a file is deleted, remove its stored hash.
	 *
	 * @param string $file
	 */
	public function delete_file( $file ) {
		$hashes = ITSEC_Modules::get_setting( 'file-change', 'expected_hashes', array() );

		if ( isset( $hashes[ $file ] ) ) {
			unset( $hashes[ $file ] );

			ITSEC_Modules::set_setting( 'file-change', 'expected_hashes', $hashes );
		}
	}

	/**
	 * Register the file change scan event.
	 *
	 * @param ITSEC_Scheduler $scheduler
	 */
	public function register_event( $scheduler ) {
		require_once( dirname( __FILE__ ) . '/scanner.php' );
		ITSEC_File_Change_Scanner::schedule_start( false, $scheduler );
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
		$api->register( 'itsec-ping-file-scan', 'Ithemes_Sync_Verb_ITSEC_Ping_File_Scan', dirname( __FILE__ ) . '/sync-verbs/itsec-ping-file-scan.php' );
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

	/**
	 * Add status about the currently running file scan.
	 *
	 * @param array $response
	 * @param array $data
	 *
	 * @return array
	 */
	public function heartbeat( $response, $data ) {

		if ( ! empty( $data['itsec_file_change_scan_status'] ) && ITSEC_Core::current_user_can_manage() ) {
			require_once( dirname( __FILE__ ) . '/scanner.php' );

			if ( ITSEC_Core::get_scheduler()->is_single_scheduled( 'file-change-fast', null ) ) {
				ITSEC_Core::get_scheduler()->run_due_now();
			}

			$response['itsec_file_change_scan_status'] = ITSEC_File_Change_Scanner::get_status();
		}

		return $response;
	}

	/**
	 * Get the latest change list.
	 *
	 * @return array
	 */
	public static function get_latest_changes() {
		$changes = get_site_option( 'itsec_file_change_latest', array() );

		if ( ! is_array( $changes ) ) {
			$changes = array();
		}

		return $changes;
	}

	/**
	 * Make the progress torage container.
	 *
	 * @return ITSEC_Lib_Distributed_Storage
	 */
	public static function make_progress_storage() {
		return new ITSEC_Lib_Distributed_Storage( 'file-change-progress', array(
			'step'         => array( 'default' => '' ),
			'chunk'        => array( 'default' => '' ),
			'id'           => array( 'default' => '' ),
			'data'         => array( 'default' => array() ),
			'memory'       => array( 'default' => 0 ),
			'memory_peak'  => array( 'default' => 0 ),
			'process'      => array( 'default' => array() ),
			'done_plugins' => array( 'default' => array() ),
			'max_severity' => array( 'default' => 0 ),
			'file_list'    => array(
				'default'     => array(),
				'split'       => true,
				'chunk'       => 1000,
				'serialize'   => 'wp_json_encode',
				'unserialize' => 'ITSEC_File_Change::_json_decode_associative'
			),
			'files'        => array(
				'default'     => array(),
				'split'       => true,
				'chunk'       => 1000,
				'serialize'   => 'wp_json_encode',
				'unserialize' => 'ITSEC_File_Change::_json_decode_associative'
			),
			'change_list'  => array(
				'default' => array(
					'added'   => array(),
					'changed' => array(),
					'removed' => array(),
				),
				'split'   => true
			),
		) );
	}

	public static function _json_decode_associative( $value ) {
		return json_decode( $value, true );
	}
}
