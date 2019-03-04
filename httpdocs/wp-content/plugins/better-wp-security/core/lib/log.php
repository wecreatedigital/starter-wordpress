<?php

final class ITSEC_Log {
	/* Critical issues are very important events that administrators should be notified about, such as finding malware
	 * on the site or detecting a security breach.
	 */
	public static function add_critical_issue( $module, $code, $data = false, $overrides = array() ) {
		return self::add( $module, $code, $data, 'critical-issue', 0, $overrides );
	}

	/* Actions are noteworthy automated events that change the functionality of the site based upon certain criteria,
	 * such as locking out an IP address due to bruteforce attempts.
	 */
	public static function add_action( $module, $code, $data = false, $overrides = array() ) {
		return self::add( $module, $code, $data, 'action', 0, $overrides );
	}

	/* Fatal errors are critical problems detected in the code that could and should be reserved for very rare but
	 * highly problematic situations, such as a catch handler in a try/catch block or a shutdown handler running before
	 * a process finishes.
	 */
	public static function add_fatal_error( $module, $code, $data = false, $overrides = array() ) {
		return self::add( $module, $code, $data, 'fatal', 0, $overrides );
	}

	/* Errors are events that indicate a failure of some sort, such as failure to write to a file or an inability to
	 * request a remote URL.
	 */
	public static function add_error( $module, $code, $data = false, $overrides = array() ) {
		return self::add( $module, $code, $data, 'error', 0, $overrides );
	}

	/* Warnings are noteworthy events that might indicate an issue, such as finding changed files.
	 */
	public static function add_warning( $module, $code, $data = false, $overrides = array() ) {
		return self::add( $module, $code, $data, 'warning', 0, $overrides );
	}

	/* Notices keep track of events that should be tracked but do not necessarily indicate an issue, such as requests
	 * for files that do not exist and completed scans that did not find any issues.
	 */
	public static function add_notice( $module, $code, $data = false, $overrides = array() ) {
		return self::add( $module, $code, $data, 'notice', 0, $overrides );
	}

	/* Debug events are to be used in situations where extra information about a specific process could be helpful to
	 * have when investigating an issue but the information would typically be uninteresting to the user, such as
	 * noting the use of a compatibility function.
	 */
	public static function add_debug( $module, $code, $data = false, $overrides = array() ) {
		return self::add( $module, $code, $data, 'debug', 0, $overrides );
	}

	/* Process events allow for creating single entries that have a start, zero or more updates, and a stopping point.
	 * This allows for benchmarking performance of long-running code in addition to finding issues such as terminated
	 * execution due to the missing process-stop entry.
	 */
	public static function add_process_start( $module, $code, $data = false, $overrides = array() ) {
		$id = self::add( $module, $code, $data, 'process-start', 0, $overrides );

		return compact( 'module', 'code', 'id' );
	}

	public static function add_process_update( $reference, $data = false, $overrides = array() ) {
		self::add( $reference['module'], $reference['code'], $data, 'process-update', $reference['id'], $overrides );
	}

	public static function add_process_stop( $reference, $data = false, $overrides = array() ) {
		self::add( $reference['module'], $reference['code'], $data, 'process-stop', $reference['id'], $overrides );
	}

	private static function add( $module, $code, $data, $type, $parent_id = 0, $overrides = array() ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$url = 'wp-cli';
		} else if ( ( is_callable( 'wp_doing_cron' ) && wp_doing_cron() ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			$url = 'wp-cron';
		} else if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
			$url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		} else {
			$url = 'unknown';
		}

		$data = array_merge( array(
			'parent_id'      => $parent_id,
			'module'         => $module,
			'code'           => $code,
			'data'           => $data,
			'type'           => $type,
			'timestamp'      => gmdate( 'Y-m-d H:i:s' ),
			'init_timestamp' => gmdate( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() ),
			'memory_current' => memory_get_usage(),
			'memory_peak'    => memory_get_peak_usage(),
			'url'            => $url,
			'blog_id'        => get_current_blog_id(),
			'user_id'        => get_current_user_id(),
			'remote_ip'      => ITSEC_Lib::get_ip(),
		), $overrides );

		$log_type = ITSEC_Modules::get_setting( 'global', 'log_type' );

		if ( 'database' === $log_type ) {
			$id = self::add_to_db( $data );
		} else if ( 'file' === $log_type ) {
			$id = self::add_to_file( $data );
		} else {
			$id = self::add_to_db( $data );
			self::add_to_file( $data, $id );
		}

		do_action( 'itsec_log_add', $data, $id, $log_type );

		return $id;
	}

	private static function add_to_db( $data ) {
		global $wpdb;

		$format = array();

		foreach ( $data as $key => $value ) {
			if ( is_int( $value ) ) {
				$format[] = '%d';
			} else {
				$format[] = '%s';

				if ( ! is_string( $value ) ) {
					$data[$key] = serialize( $value );
				}
			}
		}

		$result = $wpdb->insert( "{$wpdb->base_prefix}itsec_logs", $data, $format );

		if ( false === $result ) {
			error_log( "Failed to insert log entry: {$wpdb->last_error}" );
			return new WP_Error( 'itsec-log-failed-db-insert', sprintf( esc_html__( 'Failed to insert log entry: %s', 'better-wp-security' ), $wpdb->last_error ) );
		}

		return $wpdb->insert_id;
	}

	private static function add_to_file( $data, $id = false ) {
		if ( false === $id ) {
			$id = microtime( true );
		}


		$file = self::get_log_file_path();

		if ( is_wp_error( $file ) ) {
			return $file;
		}


		$entries = array();

		foreach ( $data as $value ) {
			if ( is_object( $value ) || is_array( $value ) ) {
				$value = serialize( $value );
			} else {
				$value = (string) $value;
			}

			$value = str_replace( '"', '""', $value );

			if ( preg_match( '/[", ]/', $value ) ) {
				$value = "\"$value\"";
			}

			$entries[] = $value;
		}

		$entry = implode( ',', $entries ) . "\n";


		$result = file_put_contents( $file, $entry, FILE_APPEND );

		if ( false === $result ) {
			return new WP_Error( 'itsec-log-failed-to-write-to-file', __( 'Unable to write to the log file. This could indicate that there is no space available, that there is a permissions issue, or that the server is not configured properly.', 'better-wp-security' ) );
		}


		return $id;
	}

	public static function get_log_file_path() {
		static $log_file = false;

		if ( false !== $log_file ) {
			return $log_file;
		}

		$log_location = ITSEC_Modules::get_setting( 'global', 'log_location' );
		$log_info = ITSEC_Modules::get_setting( 'global', 'log_info' );

		if ( empty( $log_info ) ) {
			$log_info = substr( sanitize_title( get_bloginfo( 'name' ) ), 0, 20 ) . '-' . wp_generate_password( 30, false );

			ITSEC_Modules::set_setting( 'global', 'log_info', $log_info );
		}

		$log_file = "$log_location/event-log-$log_info.log";

		if ( ! file_exists( $log_file ) ) {
			$header = "parent_id,module,code,data,type,timestamp,init_timestamp,memory_current,memory_peak,user_id,remote_ip\n";

			file_put_contents( $log_file, $header );
		}

		return $log_file;
	}

	public static function get_entries( $filters = array(), $limit = 0, $page = 1, $sort_by_column = 'id', $sort_direction = 'DESC', $columns = false ) {
		require_once( dirname( __FILE__ ) . '/log-util.php' );

		return ITSEC_Log_Util::get_entries( $filters, $limit, $page, $sort_by_column, $sort_direction, $columns );
	}

	public static function get_entry( $id ) {
		require_once( dirname( __FILE__ ) . '/log-util.php' );

		$entries = ITSEC_Log_Util::get_entries( array( 'id' => $id ), 0, 1, 'id', 'DESC', 'all' );

		return $entries[0];
	}

	public static function get_number_of_entries( $filters = array() ) {
		$filters['__get_count'] = true;
		return self::get_entries( $filters );
	}

	public static function get_type_counts( $min_timestamp = 0 ) {
		require_once( dirname( __FILE__ ) . '/log-util.php' );

		return ITSEC_Log_Util::get_type_counts( $min_timestamp );
	}

	public static function get_types_for_display() {
		return array(
			'critical-issue' => esc_html__( 'Critical Issue', 'better-wp-security' ),
			'action'         => esc_html__( 'Action', 'better-wp-security' ),
			'fatal'          => esc_html__( 'Fatal Error', 'better-wp-security' ),
			'error'          => esc_html__( 'Error', 'better-wp-security' ),
			'warning'        => esc_html__( 'Warning', 'better-wp-security' ),
			'notice'         => esc_html__( 'Notice', 'better-wp-security' ),
			'debug'          => esc_html__( 'Debug', 'better-wp-security' ),
			'process-start'  => esc_html__( 'Process', 'better-wp-security' ),
		);
	}

	public static function register_events( $scheduler ) {
		$scheduler->schedule( ITSEC_Scheduler::S_DAILY, 'purge-log-entries' );
	}

	public static function purge_entries() {
		global $wpdb;

		$database_entry_expiration = date( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() - ( ITSEC_Modules::get_setting( 'global', 'log_rotation' ) * DAY_IN_SECONDS ) );
		$query = $wpdb->prepare( "DELETE FROM `{$wpdb->base_prefix}itsec_logs` WHERE timestamp<%s", $database_entry_expiration );
		$wpdb->query( $query );


		$log_type = ITSEC_Modules::get_setting( 'global', 'log_type' );

		if ( 'database' !== $log_type ) {
			self::rotate_log_files();
		}
	}

	public static function rotate_log_files() {

		if ( $days_to_keep = ITSEC_Modules::get_setting( 'global', 'file_log_rotation' ) ) {
			self::delete_old_logs( $days_to_keep );
		}

		$log = self::get_log_file_path();
		$max_file_size = 10 * 1024 * 1024; // 10MiB

		if ( ! file_exists( $log ) || filesize( $log ) < $max_file_size ) {
			return;
		}


		$files = glob( "$log.*" );

		foreach ( $files as $index => $file ) {
			if ( ! preg_match( '/^' . preg_quote( $log, '/' ) . '\.\d+$/', $file ) ) {
				unset( $files[$index] );
			}
		}

		natsort( $files );
		$files = array_values( $files );

		$files_to_delete = array();
		$files_to_rotate = array();
		$max_files = apply_filters( 'itsec_log_max_log_files', 100 );

		foreach ( $files as $index => $file ) {
			$number = intval( pathinfo( $file, PATHINFO_EXTENSION ) );

			if ( $number > $max_files ) {
				$files_to_delete[] = $file;
			} else if ( $number === $index + 1 && $number !== $max_files ) {
				$files_to_rotate[] = $file;
			}
		}

		array_unshift( $files_to_rotate, $log );
		krsort( $files_to_rotate );

		foreach ( $files_to_rotate as $index => $file ) {
			rename( $file, "$log." . ( $index + 1 ) );
		}

		touch( $log );

		foreach ( $files_to_delete as $file ) {
			unlink( $file );
		}
	}

	private static function delete_old_logs( $days_to_keep ) {

		$log = self::get_log_file_path();

		if ( ! file_exists( $log ) ) {
			return;
		}

		$seconds = $days_to_keep * DAY_IN_SECONDS;

		$files = glob( "$log.*" );

		foreach ( $files as $file ) {
			if ( ! $time = self::get_latest_write_for_file( $file ) ) {
				continue;
			}

			if ( $time + $seconds > ITSEC_Core::get_current_time_gmt() ) {
				continue;
			}

			unlink( $file );
		}
	}

	private static function get_latest_write_for_file( $file ) {

		$line = self::tail( $file );

		if ( ! $line ) {
			return false;
		}

		return self::get_date( $line );
	}

	private static function get_date( $line ) {
		if ( ! $parsed = self::parse_csv_line( $line ) ) {
			return false;
		}

		if ( ! isset( $parsed[5] ) ) {
			return false;
		}

		return strtotime( $parsed[5] );
	}

	private static function parse_csv_line( $line ) {

		if ( function_exists( 'str_getcsv' ) ) {
			return str_getcsv( $line );
		}

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-file.php' );

		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$temp = wp_tempnam();

		$success = ITSEC_Lib_File::write( $temp, $line );

		if ( true !== $success ) {
			return false;
		}

		if ( ! $fh = fopen( $temp, 'rb' ) ) {
			return false;
		}

		$parsed = fgetcsv( $fh );

		if ( ! is_array( $parsed ) ) {
			return false;
		}

		return $parsed;
	}

	/**
	 * Get the last n lines of a file.
	 *
	 * @link https://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
	 *
	 * @param string $filename
	 * @param int    $lines
	 * @param int    $buffer
	 *
	 * @return bool|string
	 */
	private static function tail( $filename, $lines = 1, $buffer = 4096 ) {
		// Open the file
		$f = fopen( $filename, "rb" );

		// Jump to last character
		fseek( $f, - 1, SEEK_END );

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if ( fread( $f, 1 ) !== "\n" ) {
			-- $lines;
		}

		// Start reading
		$output = '';
		$chunk  = '';

		// While we would like more
		while ( ftell( $f ) > 0 && $lines >= 0 ) {
			// Figure out how far back we should jump
			$seek = min( ftell( $f ), $buffer );

			// Do the jump (backwards, relative to where we are)
			fseek( $f, - $seek, SEEK_CUR );

			// Read a chunk and prepend it to our output
			$output = ( $chunk = fread( $f, $seek ) ) . $output;

			// Jump back to where we started reading
			fseek( $f, - mb_strlen( $chunk, '8bit' ), SEEK_CUR );

			// Decrease our line counter
			$lines -= substr_count( $chunk, "\n" );
		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ( $lines ++ < 0 ) {
			// Find first newline and remove all text before that
			$output = substr( $output, strpos( $output, "\n" ) + 1 );
		}

		// Close file and return
		fclose( $f );

		return $output;
	}
}

add_action( 'itsec_scheduler_register_events', array( 'ITSEC_Log', 'register_events' ) );
add_action( 'itsec_scheduled_purge-log-entries', array( 'ITSEC_Log', 'purge_entries' ) );
