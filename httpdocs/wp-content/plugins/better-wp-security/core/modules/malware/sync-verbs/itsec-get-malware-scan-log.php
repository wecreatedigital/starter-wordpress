<?php

class Ithemes_Sync_Verb_ITSEC_Get_Malware_Scan_Log extends Ithemes_Sync_Verb {
	public static $name = 'itsec-get-malware-scan-log';
	public static $description = '';

	public $default_arguments = array(
		'count' => 10,
		'page'  => 1,
	);

	public function run( $arguments ) {
		$arguments = Ithemes_Sync_Functions::merge_defaults( $arguments, $this->default_arguments );

		return ITSEC_Log::get_entries( array( 'module' => 'malware' ), $arguments['count'], $arguments['page'], 'timestamp', 'DESC', 'all' );
	}
}
