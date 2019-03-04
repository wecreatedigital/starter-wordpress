<?php

function itsec_global_filter_whitelisted_ips( $whitelisted_ips ) {
	return array_merge( $whitelisted_ips, ITSEC_Modules::get_setting( 'global', 'lockout_white_list', array() ) );
}
add_action( 'itsec_white_ips', 'itsec_global_filter_whitelisted_ips', 0 );


function itsec_global_add_notice() {

	if ( ! defined( 'ITSEC_USE_CRON' ) && ITSEC_Core::current_user_can_manage() ) {
		ITSEC_Core::add_notice( 'itsec_show_disable_cron_constants_notice' );
	}

	if ( ITSEC_Core::is_temp_disable_modules_set() && ITSEC_Core::current_user_can_manage() ) {
		ITSEC_Core::add_notice( 'itsec_show_temp_disable_modules_notice', true );
	}

}
add_action( 'admin_init', 'itsec_global_add_notice', 0 );

function itsec_network_brute_force_add_notice() {
	if ( ITSEC_Modules::get_setting( 'network-brute-force', 'api_nag' ) && current_user_can( ITSEC_Core::get_required_cap() ) ) {
		ITSEC_Core::add_notice( 'itsec_network_brute_force_show_notice' );
	}
}
add_action( 'admin_init', 'itsec_network_brute_force_add_notice' );

function itsec_network_brute_force_show_notice() {
	echo '<div id="itsec-notice-network-brute-force" class="updated itsec-notice"><span class="it-icon-itsec"></span>'
		 . __( 'New! Take your site security to the next level by activating iThemes Brute Force Network Protection.', 'better-wp-security' )
		 . '<a class="itsec-notice-button" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'module' => 'network-brute-force', 'enable' => 'network-brute-force' ), ITSEC_Core::get_settings_page_url() ), 'itsec-enable-network-brute-force', 'itsec-enable-nonce' ) ) . '" onclick="document.location.href=\'?itsec_no_api_nag=off&_wpnonce=' . wp_create_nonce( 'itsec-nag' ) . '\';">' . __( 'Get Free API Key', 'better-wp-security' ) . '</a>'
		 . '<button class="itsec-notice-hide" data-nonce="' . wp_create_nonce( 'dismiss-brute-force-network-notice' ) . '" data-source="brute_force_network">&times;</button>'
		 . '</div>';
}

function itsec_network_brute_force_dismiss_notice() {
	if ( wp_verify_nonce( $_REQUEST['notice_nonce'], 'dismiss-brute-force-network-notice' ) ) {
		ITSEC_Modules::set_setting( 'network-brute-force', 'api_nag', false );
		wp_send_json_success();
	}
	wp_send_json_error();
}
add_action( 'wp_ajax_itsec-dismiss-notice-brute_force_network', 'itsec_network_brute_force_dismiss_notice' );

function itsec_show_temp_disable_modules_notice() {
	ITSEC_Lib::show_error_message( esc_html__( 'The ITSEC_DISABLE_MODULES define is set. All iThemes Security protections are disabled. Please make the necessary settings changes and remove the define as quickly as possible.', 'better-wp-security' ) );
}

function itsec_show_disable_cron_constants_notice() {

	$check = array( 'ITSEC_BACKUP_CRON', 'ITSEC_FILE_CHECK_CRON' );
	$using = array();

	foreach ( $check as $constant ) {
		if ( defined( $constant ) && constant( $constant ) ) {
			$using[] = "<span class='code'>{$constant}</span>";
		}
	}

	if ( $using ) {
		$message = wp_sprintf( esc_html(
			_n( 'The %l define is deprecated. Please use %s instead.', 'The %l defines are deprecated. Please use %s instead.', count( $using ), 'better-wp-security' )
		), $using, '<span class="code">ITSEC_USE_CRON</span>' );

		echo "<div class='notice notice-error'><p>{$message}</p></div>";
	}
}

/**
 * On every page load, check if the cron test has successfully fired in time.
 *
 * If not, update the cron status and turn off using cron.
 */
function itsec_cron_test_fail_safe() {

	if ( defined( 'ITSEC_DISABLE_CRON_TEST' ) && ITSEC_DISABLE_CRON_TEST ) {
		return;
	}

	$time = ITSEC_Modules::get_setting( 'global', 'cron_test_time' );

	if ( ! $time ) {
		if ( ITSEC_Lib::get_lock( 'cron_test_fail_safe' ) ) {
			ITSEC_Lib::schedule_cron_test();
			ITSEC_Lib::release_lock( 'cron_test_fail_safe' );
		}

		return;
	}

	$threshold = HOUR_IN_SECONDS + DAY_IN_SECONDS;

	if ( ITSEC_Core::get_current_time_gmt() <= $time + $threshold + 5 * MINUTE_IN_SECONDS ) {
		return;
	}

	if ( ! ITSEC_Lib::get_lock( 'cron_test_fail_safe' ) ) {
		return;
	}

	$uncached = ITSEC_Lib::get_uncached_option( 'itsec-storage' );
	$time     = $uncached['global']['cron_test_time'];

	if ( ITSEC_Core::get_current_time_gmt() > $time + $threshold + 5 * MINUTE_IN_SECONDS ) {
		if ( ( ! defined( 'ITSEC_USE_CRON' ) || ! ITSEC_USE_CRON ) && ITSEC_Lib::use_cron() ) {
			ITSEC_Modules::set_setting( 'global', 'use_cron', false );
		}

		ITSEC_Modules::set_setting( 'global', 'cron_status', 0 );
	}

	ITSEC_Lib::schedule_cron_test();
	ITSEC_Lib::release_lock( 'cron_test_fail_safe' );
}

add_action( 'init', 'itsec_cron_test_fail_safe' );

/**
 * Callback for testing whether we should suggest the cron scheduler be enabled.
 *
 * @param int $time
 */
function itsec_cron_test_callback( $time ) {

	$threshold = HOUR_IN_SECONDS + DAY_IN_SECONDS;

	if ( empty( $time ) || ITSEC_Core::get_current_time_gmt() > $time + $threshold ) {
		// Disable cron if the user hasn't set the use cron constant to true.
		if ( ( ! defined( 'ITSEC_USE_CRON' ) || ! ITSEC_USE_CRON ) && ITSEC_Lib::use_cron() ) {
			ITSEC_Modules::set_setting( 'global', 'use_cron', false );
		}

		ITSEC_Modules::set_setting( 'global', 'cron_status', 0 );
	} elseif ( ! ITSEC_Lib::use_cron() ) {
		ITSEC_Modules::set_setting( 'global', 'cron_status', 1 );
		ITSEC_Modules::set_setting( 'global', 'use_cron', true );
	} else {
		ITSEC_Modules::set_setting( 'global', 'cron_status', 1 );
	}

	ITSEC_Lib::schedule_cron_test();
}

add_action( 'itsec_cron_test', 'itsec_cron_test_callback' );

/**
 * Record that a user has logged-in.
 *
 * @param string  $username
 * @param WP_User $user
 */
function itsec_record_first_login( $username, $user ) {

	if ( ! get_user_meta( $user->ID, '_itsec_has_logged_in', true ) ) {
		update_user_meta( $user->ID, '_itsec_has_logged_in', ITSEC_Core::get_current_time_gmt() );
	}
}

add_action( 'wp_login', 'itsec_record_first_login', 15, 2 );

/**
 * Basename the 'thumb' for attachments to prevent directory traversal
 * when deleting the main attachment.
 *
 * @param array $data
 *
 * @return array
 */
function itsec_basename_attachment_thumbs( $data ) {

	if ( isset( $data['thumb'] ) && ITSEC_Modules::get_setting( 'wordpress-tweaks', 'patch_thumb_file_traversal' ) ) {
		$data['thumb'] = basename( $data['thumb'] );
	}

	return $data;
}

add_filter( 'wp_update_attachment_metadata', 'itsec_basename_attachment_thumbs' );