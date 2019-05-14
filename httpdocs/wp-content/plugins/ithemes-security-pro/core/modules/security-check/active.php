<?php

function itsec_security_check_register_sync_verbs( $api ) {
	$api->register( 'itsec-do-security-check', 'Ithemes_Sync_Verb_ITSEC_Do_Security_Check', dirname( __FILE__ ) . '/sync-verbs/itsec-do-security-check.php' );
	$api->register( 'itsec-get-security-check-feedback-response', 'Ithemes_Sync_Verb_ITSEC_Get_Security_Check_Feedback_Response', dirname( __FILE__ ) . '/sync-verbs/itsec-get-security-check-feedback-response.php' );
	$api->register( 'itsec-get-security-check-modules', 'Ithemes_Sync_Verb_ITSEC_Get_Security_Check_Modules', dirname( __FILE__ ) . '/sync-verbs/itsec-get-security-check-modules.php' );
}
add_action( 'ithemes_sync_register_verbs', 'itsec_security_check_register_sync_verbs' );

/**
 * Handle the loopback callback test.
 */
function itsec_security_check_loopback_callback() {
	if ( ! isset( $_POST['hash'], $_POST['exp'] ) ) {
		wp_die();
	}

	$hash = $_POST['hash'];
	$exp  = $_POST['exp'];

	$expected = hash_hmac( 'sha1', "itsec-check-loopback|{$exp}", wp_salt() );

	if ( ! hash_equals( $hash, $expected ) ) {
		wp_die();
	}

	if ( $exp < ITSEC_Core::get_current_time_gmt() ) {
		wp_die();
	}

	echo ITSEC_Lib::get_ip();
	die;
}

add_action( 'admin_post_nopriv_itsec-check-loopback', 'itsec_security_check_loopback_callback' );
