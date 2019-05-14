<?php

class ITSEC_Malware {

	function run() {
		add_action( 'ithemes_sync_register_verbs', array( $this, 'register_sync_verbs' ) );
		add_filter( 'itsec-filter-itsec-get-everything-verbs', array( $this, 'register_sync_get_everything_verbs' ) );
	}

	/**
	 * Register verbs for Sync.
	 *
	 * @since 3.6.0
	 *
	 * @param Ithemes_Sync_API $api Sync API object.
	 */
	public function register_sync_verbs( $api ) {
		$api->register( 'itsec-do-malware-scan', 'Ithemes_Sync_Verb_ITSEC_Malware_Do_Scan', dirname( __FILE__ ) . '/sync-verbs/itsec-do-malware-scan.php' );
		$api->register( 'itsec-get-malware-scan-log', 'Ithemes_Sync_Verb_ITSEC_Get_Malware_Scan_Log', dirname( __FILE__ ) . '/sync-verbs/itsec-get-malware-scan-log.php' );
	}

	/**
	 * Filter to add verbs to the response for the itsec-get-everything verb.
	 *
	 * @since 3.6.0
	 *
	 * @param  array Array of verbs.
	 *
	 * @return array Array of verbs.
	 */
	public function register_sync_get_everything_verbs( $verbs ) {
		$verbs['malware'][] = 'itsec-get-malware-scan-log';

		return $verbs;
	}

}
