<?php

final class ITSEC_Log {
	/* Critical issues are very important events that administrators should be notified about, such as finding malware
	 * on the site or detecting a security breach.
	 */
	public static function add_critical_issue( $module, $code, $data = false ) {
		return self::add( $module, $code, $data, 'critical-issue' );
	}

	/* Actions are noteworthy automated events that change the functionality of the site based upon certain criteria,
	 * such as locking out an IP address due to bruteforce attempts.
	 */
	public static function add_action( $module, $code, $data = false ) {
		return self::add( $module, $code, $data, 'action' );
	}

	/* Fatal errors are critical problems detected in the code that could and should be reserved for very rare but
	 * highly problematic situations, such as a catch handler in a try/catch block or a shutdown handler running before
	 * a process finishes.
	 */
	public static function add_fatal_error( $module, $code, $data = false ) {
		return self::add( $module, $code, $data, 'fatal' );
	}

	/* Errors are events that indicate a failure of some sort, such as failure to write to a file or an inability to
	 * request a remote URL.
	 */
	public static function add_error( $module, $code, $data = false ) {
		return self::add( $module, $code, $data, 'error' );
	}

	/* Warnings are noteworthy events that might indicate an issue, such as finding changed files.
	 */
	public static function add_warning( $module, $code, $data = false ) {
		return self::add( $module, $code, $data, 'warning' );
	}

	/* Notices keep track of events that should be tracked but do not necessarily indicate an issue, such as requests
	 * for files that do not exist and completed scans that did not find any issues.
	 */
	public static function add_notice( $module, $code, $data = false ) {
		return self::add( $module, $code, $data, 'notice' );
	}

	/* Debug events are to be used in situations where extra information about a specific process could be helpful to
	 * have when investigating an issue but the information would typically be uninteresting to the user, such as
	 * noting the use of a compatibility function.
	 */
	public static function add_debug( $module, $code, $data = false ) {
		return self::add( $module, $code, $data, 'debug' );
	}

	/* Process events allow for creating single entries that have a start, zero or more updates, and a stopping point.
	 * This allows for benchmarking performance of long-running code in addition to finding issues such as terminated
	 * execution due to the missing process-stop entry.
	 */
	public static function add_process_start( $module, $code, $data = false ) {
		$id = self::add( $module, $code, $data, 'process-start' );

		return compact( 'module', 'code', 'id' );
	}

	public static function add_process_update( $reference, $data = false ) {
		self::add( $reference['module'], $reference['code'], $data, 'process-update', $reference['id'] );
	}

	public static function add_process_stop( $reference, $data = false ) {
		self::add( $reference['module'], $reference['code'], $data, 'process-stop', $reference['id'] );
	}

	private static function add( $module, $code, $data, $type, $parent_id = 0 ) {
		$data = array(
			'parent_id'      => $parent_id,
			'module'         => $module,
			'code'           => $code,
			'data'           => $data,
			'type'           => $type,
			'timestamp'      => gmdate( 'Y-m-d H:i:s' ),
			'init_timestamp' => gmdate( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() ),
			'memory_current' => memory_get_usage(),
			'memory_peak'    => memory_get_peak_usage(),
			'url'            => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
			'blog_id'        => get_current_blog_id(),
			'user_id'        => get_current_user_id(),
			'remote_ip'      => ITSEC_Lib::get_ip(),
		);

		$log_type = ITSEC_Modules::get_setting( 'global', 'log_type' );

		if ( 'database' === $log_type ) {
			$id = self::add_to_db( $data );
		} else if ( 'file' === $log_type ) {
			$id = self::add_to_file( $data );
		} else {
			$id = self::add_to_db( $data );
			self::add_to_file( $data, $id );
		}

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
			'fatal-error'    => esc_html__( 'Fatal Error', 'better-wp-security' ),
			'error'          => esc_html__( 'Error', 'better-wp-security' ),
			'warning'        => esc_html__( 'Warning', 'better-wp-security' ),
			'notice'         => esc_html__( 'Notice', 'better-wp-security' ),
			'debug'          => esc_html__( 'Debug', 'better-wp-security' ),
			'process-start'  => esc_html__( 'Process', 'better-wp-security' ),
		);
	}
}
