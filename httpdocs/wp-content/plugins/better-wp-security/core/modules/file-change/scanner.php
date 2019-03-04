<?php

require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( dirname( __FILE__ ) . '/class-itsec-file-change.php' );
require_once( dirname( __FILE__ ) . '/lib/chunk-scanner.php' );
require_once( dirname( __FILE__ ) . '/lib/hash-comparator.php' );
require_once( dirname( __FILE__ ) . '/lib/hash-comparator-loadable.php' );
require_once( dirname( __FILE__ ) . '/lib/hash-comparator-chain.php' );
require_once( dirname( __FILE__ ) . '/lib/hash-comparator-managed-files.php' );
require_once( dirname( __FILE__ ) . '/lib/hash-loading-failed-exception.php' );
require_once( dirname( __FILE__ ) . '/lib/package.php' );
require_once( dirname( __FILE__ ) . '/lib/package-core.php' );
require_once( dirname( __FILE__ ) . '/lib/package-factory.php' );
require_once( dirname( __FILE__ ) . '/lib/package-plugin.php' );
require_once( dirname( __FILE__ ) . '/lib/package-system.php' );
require_once( dirname( __FILE__ ) . '/lib/package-theme.php' );
require_once( dirname( __FILE__ ) . '/lib/package-unknown.php' );

do_action( 'itsec_load_file_change_scanner' );

class ITSEC_File_Change_Scanner {

	const DESTROYED = 'itsec_file_change_scan_destroyed';

	const C_ADMIN = 'admin';
	const C_INCLUDES = 'includes';
	const C_CONTENT = 'content';
	const C_UPLOADS = 'uploads';
	const C_THEMES = 'themes';
	const C_PLUGINS = 'plugins';
	const C_OTHERS = 'others';

	const S_NONE = 0;
	const S_NORMAL = 1;
	const S_BAD_CHANGE = 2;
	const S_UNKNOWN_FILE = 3;

	const T_ADDED = 'a';
	const T_CHANGED = 'c';
	const T_REMOVED = 'r';

	/** @var ITSEC_File_Change_Hash_Comparator */
	private $comparator;

	/** @var ITSEC_File_Change_Package_Factory */
	private $package_factory;

	/** @var ITSEC_Lib_Distributed_Storage */
	private $storage;

	/** @var array */
	private $settings;

	/** @var array */
	private $chunk_order;

	/** @var ITSEC_File_Change_Chunk_Scanner */
	private $chunk_scanner;

	/**
	 * ITSEC_New_File_Change_Scanner constructor.
	 *
	 * @param ITSEC_File_Change_Chunk_Scanner   $chunk_scanner
	 * @param ITSEC_File_Change_Hash_Comparator $comparator
	 * @param ITSEC_File_Change_Package_Factory $package_factory
	 * @param ITSEC_Lib_Distributed_Storage     $storage
	 */
	public function __construct(
		ITSEC_File_Change_Chunk_Scanner $chunk_scanner = null,
		ITSEC_File_Change_Hash_Comparator $comparator = null,
		ITSEC_File_Change_Package_Factory $package_factory = null,
		ITSEC_Lib_Distributed_Storage $storage = null
	) {
		$this->chunk_scanner   = $chunk_scanner;
		$this->comparator      = $comparator;
		$this->package_factory = $package_factory;
		$this->storage         = $storage;
		$this->settings        = ITSEC_Modules::get_settings( 'file-change' );

		$this->chunk_order = array(
			self::C_ADMIN,
			self::C_INCLUDES,
			self::C_CONTENT,
			self::C_UPLOADS,
			self::C_THEMES,
			self::C_PLUGINS,
			self::C_OTHERS,
		);
	}

	/**
	 * Schedule a scan to start.
	 *
	 * @param bool            $user_initiated
	 * @param ITSEC_Scheduler $scheduler
	 *
	 * @return bool|WP_Error
	 */
	public static function schedule_start( $user_initiated = true, $scheduler = null ) {

		$scheduler = $scheduler ? $scheduler : ITSEC_Core::get_scheduler();

		if ( self::is_running( $scheduler, $user_initiated ) ) {
			return new WP_Error( 'itsec-file-change-scan-already-running', __( 'A File Change scan is currently in progress.', 'better-wp-security' ) );
		}

		if ( $user_initiated ) {
			$id   = 'file-change-fast';
			$opts = array( 'fire_at' => ITSEC_Core::get_current_time_gmt() );
		} else {
			$id   = 'file-change';
			$opts = array();
		}

		$scheduler->schedule_loop( $id, array(
			'step'  => 'get-files',
			'chunk' => self::C_ADMIN,
		), $opts );

		return true;
	}

	/**
	 * Check if a scan is running.
	 *
	 * @param ITSEC_Scheduler
	 * @param bool $user_initiated Whether the user initiated run is running for the scheduled loop scan.
	 *
	 * @return bool
	 */
	public static function is_running( $scheduler = null, $user_initiated = null ) {

		$storage   = ITSEC_File_Change::make_progress_storage();
		$id 			 = $storage->get( 'id' );

		$scheduler = $scheduler ? $scheduler : ITSEC_Core::get_scheduler();
		$scheduled = self::is_scheduled( $scheduler, $user_initiated );

		if ( null === $user_initiated ) {
			if ( ! $storage->is_empty() ) {
				return true;
			}

			return $scheduled === 'user';
		}

		if ( true === $user_initiated ) {
			return 'user' === $scheduled || $id === 'file-change-fast';
		}

		if ( false === $user_initiated ) {
			return 'scheduled' === $scheduled || $id === 'file-change';
		}

		return false;
	}

	/**
	 * Is there a scan scheduled.
	 *
	 * @param ITSEC_Scheduler $scheduler The scheduler to use.
	 * @param bool $user_initiated 			 Whether the user initiated scan is running or the scheduled loop scan. 
	 * 																	 Null to check either.
	 * 
	 * @return bool Is it scheduled.
	 */
	private static function is_scheduled( $scheduler, $user_initiated = null ) {

		if ( true === $user_initiated ) {
			return $scheduler->is_single_scheduled( 'file-change-fast', null ) ? 'user' : false;
		}

		if ( false === $user_initiated ) {
			return $scheduler->is_single_scheduled( 'file-change', null ) ? 'scheduled' : false;
		}

		if ( $scheduler->is_single_scheduled( 'file-change-fast', null ) ) {
			return 'user';
		}

		if ( $scheduler->is_single_scheduled( 'file-change', null ) ) {
			return 'scheduled';
		}

		return false;
	}

	/**
	 * Get the scan status.
	 *
	 * @param bool $is_running
	 *
	 * @return array
	 */
	public static function get_status( $is_running = true ) {
		$scheduler = ITSEC_Core::get_scheduler();

		$storage = ITSEC_File_Change::make_progress_storage();

		if ( ! $storage->is_empty() ) {
			switch ( $storage->get( 'step' ) ) {
				case 'get-files':
					switch ( $storage->get( 'chunk' ) ) {
						case self::C_ADMIN:
							$message = esc_html__( 'Scanning admin files...', 'better-wp-security' );
							break;
						case self::C_INCLUDES:
							$message = esc_html__( 'Scanning includes files...', 'better-wp-security' );
							break;
						case self::C_THEMES:
							$message = esc_html__( 'Scanning theme files...', 'better-wp-security' );
							break;
						case self::C_PLUGINS:
							$message = esc_html__( 'Scanning plugin files...', 'better-wp-security' );
							break;
						case self::C_CONTENT:
							$message = esc_html__( 'Scanning content files...', 'better-wp-security' );
							break;
						case self::C_UPLOADS:
							$message = esc_html__( 'Scanning media files...', 'better-wp-security' );
							break;
						case self::C_OTHERS:
						default:
							$message = esc_html__( 'Scanning files...', 'better-wp-security' );
							break;
					}
					break;
				case 'compare-files':
					$message = esc_html__( 'Comparing files...', 'better-wp-security' );
					break;
				case 'check-hashes':
					$message = esc_html__( 'Verifying file changes...', 'better-wp-security' );
					break;
				case 'scan-files':
					$message = esc_html__( 'Checking for malware...', 'better-wp-security' );
					break;
				case 'complete':
					$message = esc_html__( 'Wrapping up...', 'better-wp-security' );
					break;
				default:
					$message = esc_html__( 'Scanning...', 'better-wp-security' );
					break;
			}

			$status = array(
				'running' => true,
				'step'    => $storage->get( 'step' ),
				'chunk'   => $storage->get( 'chunk' ),
				'health'  => $storage->health_check(),
				'message' => $message,
			);
		} elseif ( get_site_option( self::DESTROYED ) ) {
			delete_site_option( self::DESTROYED );
			$status = array(
				'running' => false,
				'aborted' => true,
				'message' => esc_html__( 'Scan could not be completed. Please contact support if this error persists.', 'better-wp-security' ),
			);
		} elseif ( self::is_running( $scheduler ) ) {
			$status = array(
				'running' => true,
				'message' => esc_html__( 'Preparing...', 'better-wp-security' ),
			);
		} elseif ( $is_running ) {
			ITSEC_Storage::save();
			ITSEC_Storage::reload();
			ITSEC_Modules::get_settings_obj( 'file-change' )->load();

			$status = array(
				'running'       => false,
				'complete'      => true,
				'message'       => esc_html__( 'Complete!', 'better-wp-security' ),
				'found_changes' => ITSEC_Modules::get_setting( 'file-change', 'last_scan' ),
			);
		} else {
			$status = array(
				'running' => false,
				'message' => '',
			);
		}

		return $status;
	}

	/**
	 * Recover from a failed health check.
	 *
	 * @return bool Whether the scan was recovered. Will return false if aborted.
	 */
	public static function recover() {

		if ( ! ITSEC_Lib::get_lock( 'file-change' ) ) {
			ITSEC_Log::add_debug( 'file_change', 'skipping-recovery::no-lock' );

			return false;
		}

		$storage = ITSEC_File_Change::make_progress_storage();

		if ( $storage->is_empty() ) {
			ITSEC_Lib::release_lock( 'file-change' );
			ITSEC_Log::add_debug( 'file_change', 'skipping-recovery::empty-storage', array(
				'backtrace' => debug_backtrace()
			) );

			return false;
		}

		$scheduler = ITSEC_Core::get_scheduler();

		$store = array(
			'step'         => $storage->get( 'step' ),
			'chunk'        => $storage->get( 'chunk' ),
			'id'           => $storage->get( 'id' ),
			'data'         => $storage->get( 'data' ),
			'memory'       => $storage->get( 'memory' ),
			'memory_peak'  => $storage->get( 'memory_peak' ),
			'health_check' => $storage->health_check(),
		);

		ITSEC_Log::add_debug( 'file_change', 'attempting-recovery', array( 'storage' => $store ) );

		if ( empty( $store['step'] ) ) {
			ITSEC_Log::add_debug( 'file_change', 'recovery-failed-no-step' );

			self::abort();

			ITSEC_Lib::release_lock( 'file-change' );

			return false;
		}

		$job_data          = $store['data'];
		$job_data['step']  = $store['step'];
		$job_data['chunk'] = $store['chunk'];

		if ( 1 === $job_data['loop_item'] || ( 'get-files' === $job_data['step'] && self::C_ADMIN === $job_data['chunk'] ) ) {
			ITSEC_Log::add_debug( 'file_change', 'recovery-failed-first-loop' );

			self::abort();

			ITSEC_Lib::release_lock( 'file-change' );

			return false;
		}

		$job = new ITSEC_Job( $scheduler, $store['id'], $job_data, array( 'single' => true ) );

		if ( 5 < $job->is_retry() ) {
			ITSEC_Log::add_debug( 'file_change', 'recovery-failed-too-many-retries' );

			self::abort();

			ITSEC_Lib::release_lock( 'file-change' );

			return false;
		}

		$job->reschedule_in( 30 );

		ITSEC_Log::add_debug( 'file_change', 'recovery-scheduled', compact( 'job' ) );
		ITSEC_Lib::release_lock( 'file-change' );

		return true;
	}

	/**
	 * Abort an in-progress scan.
	 *
	 * @param bool $user_initiated
	 */
	public static function abort( $user_initiated = false ) {
		$storage = ITSEC_File_Change::make_progress_storage();

		if ( 'file-change-fast' === $storage->get( 'id' ) ) {
			ITSEC_Core::get_scheduler()->unschedule_single( 'file-change-fast', null );
		} else {
			ITSEC_Core::get_scheduler()->unschedule_single( 'file-change', null );
			self::schedule_start( false );
		}

		if ( $process = $storage->get( 'process' ) ) {
			ITSEC_Log::add_process_stop( $process, array( 'aborted' => true ) );
		}

		if ( $user_initiated ) {
			$user = get_current_user_id();
			ITSEC_Log::add_warning( 'file_change', "file-scan-aborted::{$user}", array(
				'id'    => $storage->get( 'id' ),
				'step'  => $storage->get( 'step' ),
				'chunk' => $storage->get( 'chunk' ),
			) );
		} else {
			ITSEC_Log::add_fatal_error( 'file_change', 'file-scan-aborted', array(
				'id'    => $storage->get( 'id' ),
				'step'  => $storage->get( 'step' ),
				'chunk' => $storage->get( 'chunk' ),
			) );
		}

		$storage->clear();
		update_site_option( self::DESTROYED, ITSEC_Core::get_current_time_gmt() );
	}

	/**
	 * Handle a Job.
	 *
	 * @param ITSEC_Job $job
	 */
	public function run( ITSEC_Job $job ) {

		$data = $job->get_data();

		if ( empty( $data['step'] ) ) {
			ITSEC_Log::add_debug( 'file_change', 'attempting-recovery::no-job-step', array( 'job' => $data ) );
			self::recover();

			return;
		}

		if ( ! ITSEC_Lib::get_lock( 'file-change', 5 * MINUTE_IN_SECONDS ) ) {
			ITSEC_Log::add_debug( 'file_change', 'rescheduling::no-lock', array( 'job' => $data, 'id' => $job->get_id() ) );
			$job->reschedule_in( 2 * MINUTE_IN_SECONDS );

			return;
		}

		if ( ! $this->allow_to_run( $job ) ) {
			ITSEC_Lib::release_lock( 'file-change' );
			ITSEC_Log::add_debug( 'file_change', 'rescheduling', array( 'job' => $data, 'id' => $job->get_id() ) );
			$job->reschedule_in( 10 * MINUTE_IN_SECONDS );

			return;
		}

		ITSEC_Lib::set_minimum_memory_limit( '512M' );
		@set_time_limit( 0 );

		if ( ! defined( 'ITSEC_DOING_FILE_CHECK' ) ) {
			define( 'ITSEC_DOING_FILE_CHECK', true );
		}

		if ( 1 === $data['loop_item'] ) {
			$settings = $this->settings;

			$process = ITSEC_Log::add_process_start( 'file_change', 'scan', array(
				'settings'       => $settings,
				'scheduled_call' => 'file-change' === $job->get_id(),
			) );
			$this->get_storage()->set( 'process', $process );
			$this->get_storage()->set( 'id', $job->get_id() );
			delete_site_option( self::DESTROYED );
		}

		$this->get_storage()->set( 'data', $data );
		$this->get_storage()->set( 'step', $data['step'] );

		$memory_used = @memory_get_peak_usage();

		switch ( $data['step'] ) {
			case 'get-files':
				$this->get_files( $job );
				break;
			case 'compare-files':
				$this->compare_files( $job );
				break;
			case 'check-hashes':
				$this->check_hashes( $job );
				break;
			case 'complete':
				$this->complete( $job );
				break;
		}

		if ( $this->get_storage()->is_empty() ) {
			ITSEC_Lib::release_lock( 'file-change' );

			return;
		}

		$check_memory = @memory_get_peak_usage();

		if ( $check_memory > $memory_used ) {
			$memory_used = $check_memory - $memory_used;
		}

		if ( $memory_used > $this->get_storage()->get( 'memory' ) ) {
			$this->get_storage()->set( 'memory', $memory_used );
			$this->get_storage()->set( 'memory_peak', $check_memory );
		}

		ITSEC_Lib::release_lock( 'file-change' );
	}

	/**
	 * Should we allow a scan to be run now.
	 *
	 * This is used to block a scheduled scan from running while a user initiated scan is currently processing.
	 *
	 * @param ITSEC_Job $job
	 *
	 * @return bool
	 */
	private function allow_to_run( ITSEC_Job $job ) {

		if ( 'file-change' !== $job->get_id() ) {
			return true;
		}

		if ( ITSEC_Core::get_scheduler()->is_single_scheduled( 'file-change-fast', null ) ) {
			return false;
		}

		$data = $job->get_data();

		// Don't allow starting a slow file change scan if one is already in progress and running.
		if ( 1 === $data['loop_item'] && ! $this->get_storage()->is_empty() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the hashes and date modify times for all files in the requested chunk.
	 *
	 * This will write the file list to step storage and schedule the next chunk.
	 * If last chunk, will schedule the compare-files step.
	 *
	 * @param ITSEC_Job $job
	 */
	private function get_files( ITSEC_Job $job ) {

		$data = $job->get_data();
		$this->get_storage()->set( 'chunk', $data['chunk'] );

		$this->add_process_update( array(
			'status' => 'get_chunk_files',
			'chunk'  => $data['chunk'],
		) );

		if ( self::C_PLUGINS === $data['chunk'] ) {
			list( $file_list, $do_same_chunk ) = $this->get_files_plugins();
		} else {
			$file_list     = $this->get_chunk_scanner()->scan( $data['chunk'] );
			$do_same_chunk = false;
		}

		$this->get_storage()->append( 'file_list', $file_list );
		$pos = array_search( $data['chunk'], $this->chunk_order, true );

		if ( $do_same_chunk ) {
			$job->schedule_next_in_loop( array( 'chunk' => $data['chunk'] ) );
		} elseif ( isset( $this->chunk_order[ $pos + 1 ] ) ) {
			$this->get_storage()->set( 'chunk', $this->chunk_order[ $pos + 1 ] );
			$job->schedule_next_in_loop( array(
				'chunk' => $this->chunk_order[ $pos + 1 ],
			) );
		} else {
			$this->add_process_update( array( 'status' => 'file_scan_complete' ) );
			$job->schedule_next_in_loop( array(
				'step' => 'compare-files'
			) );
		}
	}

	/**
	 * Handler for plugins so we don't try to scan more than 10 plugins in a process.
	 *
	 * @return array
	 */
	private function get_files_plugins() {

		$excludes = $this->get_storage()->get( 'done_plugins' );
		$this->add_process_update( array( 'status' => 'get_chunk_files_plugins', 'excludes' => $excludes ) );
		$file_list = $this->get_chunk_scanner()->scan( self::C_PLUGINS, 10, $excludes );

		$scanned = array();

		foreach ( $file_list as $file => $attr ) {
			$trimmed = ITSEC_Lib::replace_prefix( $file, WP_PLUGIN_DIR . '/', '' );
			list( $top_dir ) = explode( '/', $trimmed );

			$scanned[ WP_PLUGIN_DIR . '/' . $top_dir ] = 1;
		}

		$this->add_process_update( array( 'status' => 'get_chunk_files_plugins_scanned', 'scanned' => $scanned ) );

		$this->get_storage()->set( 'done_plugins', array_merge( $this->get_storage()->get( 'done_plugins' ), array_keys( $scanned ) ) );

		return array( $file_list, count( $scanned ) >= 10 );
	}

	/**
	 * Compare the list of file hashes to determine what files have been added/changed/removed.
	 *
	 * If there are no file changes, the scan will be completed. Otherwise it will schedule a job
	 * to check the hashes.
	 *
	 * @param ITSEC_Job $job
	 */
	private function compare_files( ITSEC_Job $job ) {

		$excludes = array();

		foreach ( $this->settings['file_list'] as $file ) {
			$cleaned              = untrailingslashit( get_home_path() . ltrim( $file, '/' ) );
			$excludes[ $cleaned ] = 1;
		}

		$types = array_flip( $this->settings['types'] );

		$this->add_process_update( array( 'status' => 'file_comparisons_start', 'excludes' => $excludes, 'types' => $types ) );

		$current_files = $this->get_storage()->get_cursor( 'file_list' );
		$prev_files    = self::get_file_list_to_compare();

		$report = array();

		foreach ( $current_files as $file => $attr ) {
			if ( ! isset( $prev_files[ $file ] ) ) {
				$attr['t']       = self::T_ADDED;
				$report[ $file ] = $attr;
			} elseif ( $prev_files[ $file ]['h'] !== $attr['h'] ) {
				$attr['t']       = self::T_CHANGED;
				$report[ $file ] = $attr;
			}

			unset( $prev_files[ $file ] );
		}

		foreach ( $prev_files as $file => $attr ) {

			if ( isset( $excludes[ $file ] ) ) {
				continue;
			}

			foreach ( $excludes as $exclude => $_ ) {
				if ( 0 === strpos( $file, trailingslashit( $exclude ) ) ) {
					continue 2;
				}
			}

			$extension = '.' . pathinfo( $file, PATHINFO_EXTENSION );

			if ( isset( $types[ $extension ] ) ) {
				continue;
			}

			$attr['t']       = self::T_REMOVED;
			$report[ $file ] = $attr;
		}

		$this->add_process_update( array( 'status' => 'file_comparisons_complete' ) );

		if ( ! $report ) {
			$this->add_process_update( array( 'status' => 'file_comparisons_complete_no_changes' ) );
			$this->complete( $job );

			return;
		}

		$this->get_storage()->set( 'files', $report );
		$job->schedule_next_in_loop( array( 'step' => 'check-hashes' ) );
	}

	/**
	 * Check the file changes with each package's hashes to determine whether the change was expected or not.
	 *
	 * @param ITSEC_Job $job
	 */
	private function check_hashes( ITSEC_Job $job ) {

		$this->add_process_update( array( 'status' => 'hash_comparisons_start' ) );

		do_action( 'itsec-file-change-start-hash-comparisons' );

		$factory    = $this->get_package_factory();
		$comparator = $this->get_comparator();
		$packages   = $factory->find_packages_for_files( $this->get_storage()->get_cursor( 'files' ) );

		foreach ( $packages as $root => $group ) {
			/** @var ITSEC_File_Change_Package $package */
			$package = $group['package'];
			$files   = $group['files'];

			if ( ! $comparator->supports_package( $package ) ) {
				$packages[ $root ]['files'] = $this->set_default_severity( $files );
				continue;
			}

			if ( $comparator instanceof ITSEC_File_Change_Hash_Comparator_Loadable ) {
				try {
					$comparator->load( $package );
				} catch ( ITSEC_File_Change_Hash_Loading_Failed_Exception $e ) {
					$packages[ $root ]['files'] = $this->set_default_severity( $files );
					$this->add_process_update( array( 'status' => 'hash_load_failed', 'e' => (string) $e ) );
					continue;
				}
			}

			// $file is a relative path to the package.
			// $attr contains 'h' for the hash, and 'd' for the date modified.
			foreach ( $files as $file => $attr ) {
				switch ( $attr['t'] ) {
					case self::T_ADDED:
						if ( ! $comparator->has_hash( $file, $package ) ) {
							$attr['s'] = self::S_UNKNOWN_FILE;
							break;
						}

						if ( ! $comparator->hash_matches( $attr['h'], $file, $package ) ) {
							// This isn't exactly an unknown file, or a bad change, but it fits more with bad change,
							// and is unlikely to occur so not worth a separate report type.
							$attr['s'] = self::S_BAD_CHANGE;
							break;
						}

						$attr['s'] = self::S_NONE;
						break;
					case self::T_CHANGED:
						if ( ! $comparator->has_hash( $file, $package ) ) {
							break;
						}

						if ( ! $comparator->hash_matches( $attr['h'], $file, $package ) ) {
							$attr['s'] = self::S_BAD_CHANGE;
							break;
						}
						$attr['s'] = self::S_NONE;
						break;
					case self::T_REMOVED:
						if ( ! $comparator->has_hash( $file, $package ) ) {
							$attr['s'] = self::S_NONE;
						}
						break;
				}

				if ( ! isset( $attr['s'] ) ) {
					$attr['s'] = self::S_NORMAL;
				}

				$files[ $file ] = $attr;
			}

			$packages[ $root ]['files'] = $files;
		}

		do_action( 'itsec-file-change-end-hash-comparisons' );

		$this->add_process_update( array( 'status' => 'hash_comparisons_complete' ) );
		$this->storage->set( 'max_severity', $this->get_max_severity( $packages ) );
		$this->storage->set( 'change_list', $this->build_change_list( $packages ) );

		$job->schedule_next_in_loop( array( 'step' => 'complete' ) );
	}

	/**
	 * Run the completion routine.
	 *
	 * @param ITSEC_Job $job
	 */
	private function complete( ITSEC_Job $job ) {

		$this->add_process_update( array( 'status' => 'start_complete' ) );

		$storage = $this->get_storage();
		self::record_file_list( $storage->get_cursor( 'file_list' ) );

		$list = $storage->get( 'change_list' );

		$list['memory']      = round( ( $storage->get( 'memory' ) / 1000000 ), 2 );
		$list['memory_peak'] = round( ( $storage->get( 'memory_peak' ) / 1000000 ), 2 );

		$c_added   = count( $list['added'] );
		$c_changed = count( $list['changed'] );
		$c_removed = count( $list['removed'] );

		$found_changes = $c_added || $c_changed || $c_removed;

		if ( $found_changes ) {

			$severity = $storage->get( 'max_severity' );

			if ( $severity > self::S_UNKNOWN_FILE ) {
				$method = 'add_critical_issue';
			} else {
				$method = 'add_warning';
			}

			$id = ITSEC_Log::$method( 'file_change', "changes-found::{$c_added},{$c_removed},{$c_changed}", $list );
		} else {
			$id = ITSEC_Log::add_notice( 'file_change', 'no-changes-found', $list );
		}

		ITSEC_Modules::set_setting( 'file-change', 'last_scan', $found_changes ? $id : 0 );
		update_site_option( 'itsec_file_change_latest', $list );

		if ( $found_changes && $this->settings['notify_admin'] ) {
			ITSEC_Modules::set_setting( 'file-change', 'show_warning', true );
		}

		if ( $process = $storage->get( 'process' ) ) {
			ITSEC_Log::add_process_stop( $process );
		}

		$storage->clear();

		if ( 'file-change' === $job->get_id() ) {
			$job->schedule_new_loop( array(
				'step'  => 'get-files',
				'chunk' => self::C_ADMIN,
			) );
		}

		$this->send_notification_email( array( $c_added, $c_removed, $c_changed, $list ) );
	}

	/**
	 * Get the comparator to use to check if changes are expected.
	 *
	 * Handles lazily setting the comparator since it is not needed for all stages of the file change scan.
	 *
	 * @return ITSEC_File_Change_Hash_Comparator
	 */
	private function get_comparator() {
		if ( ! $this->comparator ) {
			$comparators = array(
				new ITSEC_File_Change_Hash_Comparator_Managed_Files(),
			);

			/**
			 * Filter the list of comparators to use.
			 */
			$comparators = apply_filters( 'itsec_file_change_comparators', $comparators );

			$this->comparator = new ITSEC_File_Change_Hash_Comparator_Chain( $comparators );
		}

		return $this->comparator;
	}

	/**
	 * Get the Package factory.
	 *
	 * @return ITSEC_File_Change_Package_Factory
	 */
	private function get_package_factory() {
		if ( ! $this->package_factory ) {
			$this->package_factory = new ITSEC_File_Change_Package_Factory();
		}

		return $this->package_factory;
	}

	/**
	 * Get the Chunk Scanner.
	 *
	 * @return ITSEC_File_Change_Chunk_Scanner
	 */
	private function get_chunk_scanner() {
		if ( ! $this->chunk_scanner ) {
			$this->chunk_scanner = new ITSEC_File_Change_Chunk_Scanner( $this->settings );
		}

		return $this->chunk_scanner;
	}

	/**
	 * Get the main storage mechanism.
	 *
	 * @return ITSEC_Lib_Distributed_Storage
	 */
	private function get_storage() {

		if ( null === $this->storage ) {
			$this->storage = ITSEC_File_Change::make_progress_storage();
		}

		return $this->storage;
	}

	/**
	 * Set the default severity for a list of files.
	 *
	 * @param array $files
	 *
	 * @return array
	 */
	private function set_default_severity( $files ) {
		foreach ( $files as $file => $attr ) {
			$files[ $file ]['s'] = self::S_NORMAL;
		}

		return $files;
	}

	/**
	 * Get the maximum severity level of a file change.
	 *
	 * @param array $packaged
	 *
	 * @return int
	 */
	private function get_max_severity( $packaged ) {

		$severity = self::S_NONE;

		foreach ( $packaged as $root => $group ) {
			foreach ( $group['files'] as $attr ) {
				if ( $attr['s'] > $severity ) {
					$severity = $attr['s'];
				}
			}
		}

		return $severity;
	}

	/**
	 * Convert a list of packages and their files to a list of the file change types.
	 *
	 * @param array $packaged
	 *
	 * @return array
	 */
	private function build_change_list( $packaged ) {

		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		$home = get_home_path();

		$list = array(
			'added'   => array(),
			'removed' => array(),
			'changed' => array(),
		);

		foreach ( $packaged as $root => $group ) {
			/** @var ITSEC_File_Change_Package $package */
			$package = $group['package'];

			foreach ( $group['files'] as $file => $attr ) {
				if ( $attr['s'] > self::S_NONE && ! empty( $attr['t'] ) ) {
					$path = $package->get_root_path() . $file;

					if ( 0 === strpos( $path, $home ) ) {
						$path = substr( $path, strlen( $home ) );
					}

					$attr['p'] = (string) $package;

					switch ( $attr['t'] ) {
						case self::T_ADDED:
							$list['added'][ $path ] = $attr;
							break;
						case self::T_CHANGED:
							$list['changed'][ $path ] = $attr;
							break;
						case self::T_REMOVED:
							$list['removed'][ $path ] = $attr;
					}
				}
			}
		}

		return $list;
	}

	private function add_process_update( $data = false ) {
		if ( $process = $this->get_storage()->get( 'process' ) ) {
			ITSEC_Log::add_process_update( $process, $data );
		}
	}

	/**
	 * Make the storage for recording the static list of files and their hashes.
	 *
	 * @return ITSEC_Lib_Distributed_Storage
	 */
	public static function make_file_list_storage() {
		return new ITSEC_Lib_Distributed_Storage( 'file-list', array(
			'home'  => array(),
			'files' => array(
				'split'       => true,
				'chunk'       => 2500,
				'serialize'   => 'wp_json_encode',
				'unserialize' => 'ITSEC_File_Change::_json_decode_associative',
			),
		) );
	}

	/**
	 * Record a list of file hashes and change times.
	 *
	 * This should not be done until the whole scan process is complete.
	 *
	 * @param iterable $file_list
	 *
	 * @return bool
	 */
	public static function record_file_list( $file_list ) {

		$storage = self::make_file_list_storage();
		$storage->set( 'home', get_home_path() );

		if ( is_array( $file_list ) ) {
			return $storage->set( 'files', $file_list );
		}

		return $storage->set_from_iterator( 'files', $file_list );
	}

	/**
	 * Get the file list we want to compare our newly compared files to.
	 *
	 * This is in effect the last change list recorded.
	 *
	 * @return array
	 */
	public static function get_file_list_to_compare() {

		$storage = self::make_file_list_storage();
		$files   = $storage->get( 'files' );

		if ( ! $files ) {
			return array();
		}

		$home = $storage->get( 'home' );

		if ( $home === get_home_path() ) {
			return $files;
		}

		$new_home = get_home_path();
		$updated  = array();

		foreach ( $files as $file => $attr ) {
			$updated[ ITSEC_Lib::replace_prefix( $file, $home, $new_home ) ] = $attr;
		}

		$storage->set( 'files', $updated );
		$storage->set( 'home', $new_home );

		return $updated;
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

		if ( ! $changed ) {
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