<?php

final class ITSEC_File_Change_Scanner {

	/**
	 * Files and directories to be excluded from the scan
	 *
	 * @since  4.0.0
	 * @access private
	 * @var array
	 */
	private $excludes;

	/**
	 * The module's saved options
	 *
	 * @since  4.0.0
	 * @access private
	 * @var array
	 */
	private $settings;

	private $home_path;

	private static $instance = false;


	private function __construct() {}

	/**
	 * Executes file checking
	 *
	 * Performs the actual execution of a file scan after determining that such an execution is needed.
	 *
	 * @since 4.0.0
	 *
	 * @static
	 *
	 * @param bool $scheduled_call [optional] true if this is an automatic check
	 * @param bool $return_data    [optional] whether to return a data array (true) or not (false)
	 *
	 * @return mixed
	 */
	public static function run_scan( $scheduled_call = true, $return_data = false ) {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance->execute_file_check( $scheduled_call, $return_data );
	}

	private function execute_file_check( $scheduled_call, $return_data ) {

		if ( ! ITSEC_Lib::get_lock( 'file_change', 300 ) ) {
			return -1;
		}


		$process_id = ITSEC_Log::add_process_start( 'file_change', 'scan' );


		$this->home_path = untrailingslashit( ITSEC_Lib::get_home_path() );

		$this->settings = ITSEC_Modules::get_settings( 'file-change' );

		if ( ! in_array( '.lock', $this->settings['types'] ) ) {
			$this->settings['types'][] = '.lock';
		}

		foreach ( $this->settings['file_list'] as $index => $path ) {
			$this->settings['file_list'][$index] = untrailingslashit( $path );
		}

		$this->excludes = array(
			ITSEC_Modules::get_setting( 'backup', 'location' ),
			ITSEC_Modules::get_setting( 'global', 'log_location' ),
		);


		$send_email = true;

		ITSEC_Lib::set_minimum_memory_limit( '512M' );

		define( 'ITSEC_DOING_FILE_CHECK', true );

		//figure out what chunk we're on
		if ( $this->settings['split'] ) {

			if ( false === $this->settings['last_chunk'] || $this->settings['last_chunk'] > 5 ) {
				$chunk = 0;
			} else {
				$chunk = $this->settings['last_chunk'] + 1;
			}

			$db_field = 'itsec_local_file_list_' . $chunk;


			$content_dir = explode( '/', WP_CONTENT_DIR );
			$plugin_dir  = explode( '/', WP_PLUGIN_DIR );
			$wp_upload_dir = ITSEC_Core::get_wp_upload_dir();

			$dirs = array(
				'wp-admin',
				WPINC,
				WP_CONTENT_DIR,
				ITSEC_Core::get_wp_upload_dir(),
				WP_CONTENT_DIR . '/themes',
				WP_PLUGIN_DIR,
				''
			);

			$path = $dirs[ $chunk ];

			unset( $dirs[ $chunk ] );

			$this->excludes = array_merge( $this->excludes, $dirs );

			foreach ( $this->excludes as $index => $path ) {
				$path = untrailingslashit( $path );
				$path = preg_replace( '/^' . preg_quote( ABSPATH, '/' ) . '/', '', $path );

				$this->excludes[$index] = $path;
			}

		} else {

			$chunk = false;
			$db_field = 'itsec_local_file_list';
			$path = '';

		}


		$memory_used = @memory_get_peak_usage();

		$logged_files = get_site_option( $db_field );

		//if there are no old files old file list is an empty array
		if ( false === $logged_files ) {

			$send_email = false;

			$logged_files = array();

			if ( is_multisite() ) {

				add_site_option( $db_field, $logged_files );

			} else {

				add_option( $db_field, $logged_files, '', 'no' );

			}

		}

		ITSEC_Log::add_process_update( $process_id, array( 'status' => 'init_complete', 'settings' => $this->settings, 'excludes' => $this->excludes, 'path' => $path, 'scheduled_call' => $scheduled_call, 'chunk' => $chunk ) );

		do_action( 'itsec-file-change-start-scan' );
		$current_files = $this->scan_files( $path );
		do_action( 'itsec-file-change-end-scan' );

		ITSEC_Log::add_process_update( $process_id, array( 'status' => 'file_scan_complete' ) );


		$files_added          = @array_diff_assoc( $current_files, $logged_files ); //files added
		$files_removed        = @array_diff_assoc( $logged_files, $current_files ); //files deleted
		$current_minus_added  = @array_diff_key( $current_files, $files_added ); //remove all added files from current filelist
		$logged_minus_deleted = @array_diff_key( $logged_files, $files_removed ); //remove all deleted files from old file list
		$files_changed        = array(); //array of changed files

		do_action( 'itsec-file-change-start-hash-comparisons' );

		//compare file hashes and mod dates
		foreach ( $current_minus_added as $current_file => $current_attr ) {

			if ( array_key_exists( $current_file, $logged_minus_deleted ) ) {

				//if attributes differ added to changed files array
				if (
					(
						(
							isset( $current_attr['mod_date'] ) &&
							0 != strcmp( $current_attr['mod_date'], $logged_minus_deleted[ $current_file ]['mod_date'] )
						) ||
						0 != strcmp( $current_attr['d'], $logged_minus_deleted[ $current_file ]['d'] )
					) ||
					(
						(
							isset( $current_attr['hash'] ) &&
							0 != strcmp( $current_attr['hash'], $logged_minus_deleted[ $current_file ]['hash'] ) ) ||
						0 != strcmp( $current_attr['h'], $logged_minus_deleted[ $current_file ]['h'] )
					)
				) {

					$remote_check = apply_filters( 'itsec_process_changed_file', true, $current_file, $current_attr['h'] ); //hook to run actions on a changed file at time of discovery

					if ( true === $remote_check ) { //don't list the file if it matches the WordPress.org hash

						$files_changed[ $current_file ]['h'] = isset( $current_attr['hash'] ) ? $current_attr['hash'] : $current_attr['h'];
						$files_changed[ $current_file ]['d'] = isset( $current_attr['mod_date'] ) ? $current_attr['mod_date'] : $current_attr['d'];

					}

				}

			}

		}


		//get count of changes
		$files_added_count   = count( $files_added );
		$files_deleted_count = count( $files_removed );
		$files_changed_count = count( $files_changed );

		if ( $files_added_count > 0 ) {

			$files_added       = apply_filters( 'itsec_process_added_files', $files_added ); //hook to run actions on all files added
			$files_added_count = count( $files_added );

		}

		if ( $files_deleted_count > 0 ) {
			do_action( 'itsec_process_removed_files', $files_removed ); //hook to run actions on all files removed
		}

		do_action( 'itsec-file-change-end-hash-comparisons' );

		ITSEC_Log::add_process_update( $process_id, array( 'status' => 'hash_comparisons_complete' ) );


		//create single array of all changes
		$full_change_list = array(
			'added'   => $files_added,
			'removed' => $files_removed,
			'changed' => $files_changed,
		);

		$this->settings['latest_changes'] = array(
			'added' => count( $files_added ),
			'removed' => count( $files_removed ),
			'changed' => count( $files_changed ),
		);

		update_site_option( $db_field, $current_files );


		//Cleanup variables when we're done with them
		unset( $files_added );
		unset( $files_removed );
		unset( $files_changed );
		unset( $current_files );

		$this->settings['last_run']   = ITSEC_Core::get_current_time();
		$this->settings['last_chunk'] = $chunk;

		ITSEC_Modules::set_settings( 'file-change', $this->settings );

		//get new max memory
		$check_memory = @memory_get_peak_usage();
		if ( $check_memory > $memory_used ) {
			$memory_used = $check_memory - $memory_used;
		}

		$full_change_list['memory'] = round( ( $memory_used / 1000000 ), 2 );

		if ( $files_added_count > 0 || $files_changed_count > 0 || $files_deleted_count > 0 ) {
			$found_changes = true;
		} else {
			$found_changes = false;
		}

		if (
			$found_changes &&
			$send_email &&
			! $scheduled_call &&
			$this->settings['email']
		) {

			$email_details = array(
				$files_added_count,
				$files_deleted_count,
				$files_changed_count,
				$full_change_list
			);

			$this->send_notification_email( $email_details );
		}

		if (
			$found_changes &&
			$this->settings['notify_admin'] &&
			function_exists( 'get_current_screen' ) &&
			(
				! isset( get_current_screen()->id ) ||
				false === strpos( get_current_screen()->id, 'security_page_toplevel_page_itsec_logs' )
			)
		) {
			ITSEC_Modules::set_setting( 'file-change', 'show_warning', true );
		}

		if ( $found_changes ) {
			ITSEC_Log::add_warning( 'file_change', "changes-found::$files_added_count,$files_deleted_count,$files_changed_count", $full_change_list );
		} else {
			ITSEC_Log::add_notice( 'file_change', 'no-changes-found', $full_change_list );
		}

		ITSEC_Lib::release_lock( 'file_change' );


		ITSEC_Log::add_process_stop( $process_id );

		if ( $files_added_count > 0 || $files_changed_count > 0 || $files_deleted_count > 0 ) {

			//There were changes found
			if ( $return_data ) {

				return $full_change_list;

			} else {

				return true;

			}

		} else {

			return false; //No changes were found

		}

	}

	/**
	 * Get Report Details
	 *
	 * Creates the HTML markup for the email that is to be built
	 *
	 * @since 4.0.0
	 *
	 * @param array $email_details array of details to build email
	 *
	 * @return string report details
	 */
	public function get_email_report( $email_details ) {
		_deprecated_function( __METHOD__, '3.9.0' );

		return $this->generate_notification_email( $email_details )->get_content();
	}

	/**
	 * Builds table section for file report
	 *
	 * Builds the individual table areas for files added, changed and deleted that goes in the file
	 * change notification emails.
	 *
	 * @since  4.6.0
	 *
	 * @access private
	 *
	 * @param string $title User readable title to display
	 * @param array  $files array of files to build the report on
	 *
	 * @return string the markup with the given files to be added to the report
	 */
	private function build_table_section( $title, $files ) {

		$section = '<h4>' . __( 'Files', 'better-wp-security' ) . ' ' . $title . '</h4>';
		$section .= '<table border="1" style="width: 100%; text-align: center;">' . PHP_EOL;
		$section .= '<tr>' . PHP_EOL;
		$section .= '<th>' . __( 'File', 'better-wp-security' ) . '</th>' . PHP_EOL;
		$section .= '<th>' . __( 'Modified', 'better-wp-security' ) . '</th>' . PHP_EOL;
		$section .= '<th>' . __( 'File Hash', 'better-wp-security' ) . '</th>' . PHP_EOL;
		$section .= '</tr>' . PHP_EOL;

		if ( empty( $files ) ) {

			$section .= '<tr>' . PHP_EOL;
			$section .= '<td colspan="3">' . __( 'No files were changed.', 'better-wp-security' ) . '</td>' . PHP_EOL;
			$section .= '</tr>' . PHP_EOL;

		} else {

			foreach ( $files as $item => $attr ) {

				$section .= '<tr>' . PHP_EOL;
				$section .= '<td>' . $item . '</td>' . PHP_EOL;
				$section .= '<td>' . date( 'l F jS, Y \a\t g:i a e', ( isset( $attr['mod_date'] ) ? $attr['mod_date'] : $attr['d'] ) ) . '</td>' . PHP_EOL;
				$section .= '<td>' . ( isset( $attr['hash'] ) ? $attr['hash'] : $attr['h'] ) . '</td>' . PHP_EOL;
				$section .= '</tr>' . PHP_EOL;

			}

		}

		$section .= '</table>' . PHP_EOL;

		return $section;
	}

	/**
	 * Scans all files in a given path
	 *
	 * Scans all items in a given path recursively building an array of items including
	 * hashes, filenames and modification dates
	 *
	 * @since  4.0.0
	 *
	 * @access private
	 *
	 * @param string $path Path to scan. Defaults to WordPress root
	 *
	 * @return array array of files found and their information
	 *
	 */
	private function scan_files( $path ) {
		if ( in_array( $path, $this->excludes ) ) {
			return array();
		}


		$abspath = "{$this->home_path}/$path";
		$data = array();

		if ( false === ( $dh = @opendir( $abspath ) ) ) {
			return $data;
		}


		while ( false !== ( $item = @readdir( $dh ) ) ) {

			if ( '.' === $item || '..' === $item ) {
				continue;
			}


			$relname = "$path/$item";
			$absname = "$abspath/$item";


			// Efficient but difficult to grock way to skip an item if it is in the file_list and the method is
			// exclude or if it is not in the file_list and the method is include.
			if ( in_array( $relname, $this->settings['file_list'] ) xor 'include' === $this->settings['method'] ) {
				continue;
			}


			if ( is_dir( $absname ) && 'dir' === filetype( $absname ) ) {

				$data = array_merge( $data, $this->scan_files( $relname ) );

			} else {
				if ( in_array( '.' . pathinfo( $item, PATHINFO_EXTENSION ), $this->settings['types'] ) ) {
					continue;
				}

				$data[ substr( $relname, 1 ) ] = array(
					'd' => @filemtime( $absname ),
					'h' => @md5_file( $absname ),
				);
			}

		}

		@closedir( $dh );

		return $data;

	}

	/**
	 * Builds and sends notification email
	 *
	 * Sends the notication email too all applicable administrative users notifying them
	 * that file changes have been detected
	 *
	 * @since  4.0.0
	 *
	 * @access private
	 *
	 * @param array $email_details array of details for the email messge
	 *
	 * @return void
	 */
	private function send_notification_email( $email_details ) {

		$changed = $email_details[0] + $email_details[1] + $email_details[2];

		if ( $changed <= 0 ) {
			return;
		}

		$nc = ITSEC_Core::get_notification_center();

		if ( $nc->is_notification_enabled( 'digest' ) ) {
			$nc->enqueue_data( 'digest', array( 'type' => 'file-change' ) );
		}

		if ( $nc->is_notification_enabled( 'file-change' ) ) {
			$mail = $this->generate_notification_email( $email_details );
			$nc->send( 'file-change', $mail );
		}
	}

	/**
	 * Generate the notification email.
	 *
	 * @param array $email_details
	 *
	 * @return ITSEC_Mail
	 */
	private function generate_notification_email( $email_details ) {
		$mail = ITSEC_Core::get_notification_center()->mail();

		$mail->add_header(
			esc_html__( 'File Change Warning', 'better-wp-security' ),
			sprintf( esc_html__( 'File Scan Report for %s', 'better-wp-security' ), '<b>' . date_i18n( get_option( 'date_format' ) ) . '</b>' )
		);
		$mail->add_text( esc_html__( 'A file (or files) on your site have been changed. Please review the report below to verify changes are not the result of a compromise.', 'better-wp-security' ) );

		$mail->add_section_heading( esc_html__( 'Scan Summary', 'better-wp-security' ) );
		$mail->add_file_change_summary( $email_details[0], $email_details[1], $email_details[2] );

		$mail->add_section_heading( esc_html__( 'Scan Details', 'better-wp-security' ) );

		$headers = array( esc_html__( 'File', 'better-wp-security' ), esc_html__( 'Modified', 'better-wp-security' ), esc_html__( 'File Hash', 'better-wp-security' ) );

		if ( $email_details[0] ) {
			$mail->add_large_text( esc_html__( 'Added Files', 'better-wp-security' ) );
			$mail->add_table( $headers, $this->generate_email_rows( $email_details[3]['added'] ) );
		}

		if ( $email_details[1] ) {
			$mail->add_large_text( esc_html__( 'Removed Files', 'better-wp-security' ) );
			$mail->add_table( $headers, $this->generate_email_rows( $email_details[3]['removed'] ) );
		}

		if ( $email_details[2] ) {
			$mail->add_large_text( esc_html__( 'Changed Files', 'better-wp-security' ) );
			$mail->add_table( $headers, $this->generate_email_rows( $email_details[3]['changed'] ) );
		}

		$mail->add_footer();

		return $mail;
	}

	/**
	 * Generate email report rows for a series of files.
	 *
	 * @param array $files
	 *
	 * @return array
	 */
	private function generate_email_rows( $files ) {
		$rows = array();

		foreach ( $files as $item => $attr ) {
			$time = isset( $attr['mod_date'] ) ? $attr['mod_date'] : $attr['d'];

			$rows[] = array(
				$item,
				ITSEC_Lib::date_format_i18n_and_local_timezone( $time ),
				isset( $attr['hash'] ) ? $attr['hash'] : $attr['h']
			);
		}

		return $rows;
	}
}
