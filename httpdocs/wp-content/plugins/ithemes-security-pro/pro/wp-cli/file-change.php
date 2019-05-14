<?php

/**
 * Manage File Change detection.
 */
class ITSEC_File_Change_Command extends WP_CLI_Command {

	public $scan_finished = false;

	/**
	 * Perform a file change scan.
	 *
	 * ## OPTIONS
	 *
	 * [--porcelain]
	 * : Only output changes. No status messages.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 */
	public function scan( $args, $assoc_args ) {

		$porcelain = \WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' );

		ITSEC_Modules::load_module_file( 'scanner.php', 'file-change' );
		$error = ITSEC_File_Change_Scanner::schedule_start();

		if ( is_wp_error( $error ) ) {
			WP_CLI::error( $error );
		}

		if ( ! $error ) {
			WP_CLI::error( 'Unknown error.' );
		}

		$scheduler = ITSEC_Core::get_scheduler();

		$status  = ITSEC_File_Change_Scanner::get_status( false );
		$message = $status['message'];

		if ( $porcelain ) {
			$bar      = new \WP_CLI\NoOp();
			$property = new \WP_CLI\NoOp();
		} else {
			/** @var \cli\Progress $bar */
			$bar = \WP_CLI\Utils\make_progress_bar( $message, 12 );

			try {
				$property = new ReflectionProperty( get_class( $bar ), '_message' );
				$property->setAccessible( true );
			} catch ( \ReflectionException $e ) {
				$property = new \WP_CLI\NoOp();
			}
		}

		$failed = false;

		$this->scan_finished = false;
		$self                = $this;
		add_action( 'shutdown', function () use ( $self ) {
			if ( ! $self->scan_finished ) {
				ITSEC_File_Change_Scanner::abort();
			}
		} );

		// Don't refactor to use ::get_current_time_gmt() so that scheduled events keep firing.

		while ( $status['running'] ) {
			$scheduler->run_due_now( time() );

			$status = ITSEC_File_Change_Scanner::get_status();

			if ( $status['message'] !== $message ) {
				$bar->tick();
				$property->setValue( $bar, $message );
				$message = $status['message'];
			}

			if (
				isset( $status['health'] ) &&
				$status['health'] + 180 < time() &&
				! $scheduler->is_single_scheduled( 'file-change-fast', null ) &&
				! ITSEC_File_Change_Scanner::recover()
			) {
				$failed = true;
				$status = ITSEC_File_Change_Scanner::get_status();
				break;
			}
		}

		$bar->finish();
		$this->scan_finished = true;

		if ( $failed ) {
			WP_CLI::error( $status['message'] );
		}

		if ( ! $porcelain && ! empty( $status['found_changes'] ) ) {
			WP_CLI::line( 'Changes found.' );
		} elseif ( ! $porcelain ) {
			WP_CLI::success( 'No changes found.' );
		}

		$this->latest( array(), $assoc_args );
	}

	/**
	 * Get the status of a File Change scan.
	 */
	public function status() {

		ITSEC_Modules::load_module_file( 'scanner.php', 'file-change' );
		$status = ITSEC_File_Change_Scanner::get_status();

		if ( ! empty( $status['message'] ) ) {
			return WP_CLI::line( $status['message'] );
		}

		if ( empty( $status['running'] ) ) {
			return WP_CLI::line( 'No scan running.' );
		}

		return WP_CLI::error( 'Unknown status.' );
	}

	/**
	 * Manually try to trigger the file change recovery.
	 */
	public function recover() {
		ITSEC_Modules::load_module_file( 'scanner.php', 'file-change' );

		if ( ITSEC_File_Change_Scanner::recover() ) {
			WP_CLI::success( 'File change scan recovered.' );
		} else {
			WP_CLI::error( 'File change scan failed to recover.' );
		}
	}

	/**
	 * Abort a File Change scan.
	 *
	 * # OPTIONS
	 *
	 * [--hard]
	 * : Hard abort the file scan. Will cause events to be reset and the abort message to be suppressed.
	 */
	public function abort( $args, $assoc_args ) {

		ITSEC_Modules::load_module_file( 'scanner.php', 'file-change' );
		ITSEC_File_Change_Scanner::abort();

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'hard' ) ) {
			ITSEC_File_Change_Scanner::get_status();

			ITSEC_Core::get_scheduler()->uninstall();
			ITSEC_Core::get_scheduler()->register_events();
		}

		WP_CLI::success( 'Scan aborted.' );
	}

	/**
	 * Get the latest File Change report.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 */
	public function latest( $args, $assoc_args ) {
		ITSEC_Modules::load_module_file( 'scanner.php', 'file-change' );

		$changes   = ITSEC_File_Change::get_latest_changes();
		$formatted = array();

		foreach ( array( 'added', 'changed', 'removed' ) as $type ) {
			foreach ( $changes[ $type ] as $file => $attr ) {
				$formatted[] = array(
					'type'     => $this->type_label( $attr['t'] ),
					'file'     => $file,
					'severity' => $attr['s'],
					'package'  => $attr['p'],
				);
			}
		}

		$formatter = $this->get_scan_formatter( $assoc_args );
		$formatter->display_items( $formatted );
	}

	private function type_label( $type ) {
		switch ( $type ) {
			case ITSEC_File_Change_Scanner::T_ADDED:
				return 'added';
			case ITSEC_File_Change_Scanner::T_REMOVED:
				return 'removed';
			case ITSEC_File_Change_Scanner::T_CHANGED:
				return 'changed';
			default:
				return $type;
		}
	}

	/**
	 * Get a list of all tracked files and their hashes.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *
	 * [--sort]
	 * : Sort the output.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 */
	public function files( $args, $assoc_args ) {

		ITSEC_Modules::load_module_file( 'scanner.php', 'file-change' );
		$list = ITSEC_File_Change_Scanner::get_file_list_to_compare();

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'sort' ) ) {
			ksort( $list );
		}

		$formatted = array();

		foreach ( $list as $file => $attr ) {
			$formatted[] = array(
				'file' => $file,
				'hash' => $attr['h'],
				'date' => $attr['d'],
			);
		}

		$this->get_files_formatter( $assoc_args )->display_items( $formatted );
	}

	private function get_scan_formatter( $assoc_args ) {
		if ( ! empty( $assoc_args['fields'] ) ) {
			if ( is_string( $assoc_args['fields'] ) ) {
				$fields = explode( ',', $assoc_args['fields'] );
			} else {
				$fields = $assoc_args['fields'];
			}
		} else {
			$fields = array(
				'type',
				'file',
				'severity',
				'package',
			);
		}

		return new \WP_CLI\Formatter( $assoc_args, $fields );
	}

	private function get_files_formatter( $assoc_args ) {
		if ( ! empty( $assoc_args['fields'] ) ) {
			if ( is_string( $assoc_args['fields'] ) ) {
				$fields = explode( ',', $assoc_args['fields'] );
			} else {
				$fields = $assoc_args['fields'];
			}
		} else {
			$fields = array(
				'file',
				'hash',
				'date',
			);
		}

		return new \WP_CLI\Formatter( $assoc_args, $fields );
	}
}

WP_CLI::add_command( 'itsec file-change', 'ITSEC_File_Change_Command' );