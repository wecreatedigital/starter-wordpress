<?php

/**
 * Class Ithemes_Sync_Verb_ITSEC_Ping_File_Scan
 */
class Ithemes_Sync_Verb_ITSEC_Ping_File_Scan extends Ithemes_Sync_Verb {

	public static $name = 'itsec-ping-file-scan';
	public static $description = 'Ping the file scan for a status update.';

	public function run( $arguments ) {

		require_once( dirname( dirname( __FILE__ ) ) . '/scanner.php' );

		if ( ITSEC_Core::get_scheduler()->is_single_scheduled( 'file-change-fast', null ) ) {
			ITSEC_Core::get_scheduler()->run_due_now();
		}

		$status = ITSEC_File_Change_Scanner::get_status();

		if ( ! empty( $status['complete'] ) ) {
			$status['change_list'] = ITSEC_File_Change::get_latest_changes();
		}

		return $status;
	}
}