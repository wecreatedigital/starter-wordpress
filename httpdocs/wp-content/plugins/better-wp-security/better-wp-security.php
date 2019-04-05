<?php

/*
 * Plugin Name: iThemes Security
 * Plugin URI: https://ithemes.com/security
 * Description: Take the guesswork out of WordPress security. iThemes Security offers 30+ ways to lock down WordPress in an easy-to-use WordPress security plugin.
 * Author: iThemes
 * Author URI: https://ithemes.com
 * Version: 7.3.3
 * Text Domain: better-wp-security
 * Network: True
 * License: GPLv2
 */

function itsec_load_textdomain() {

	if ( function_exists( 'determine_locale' ) ) {
		$locale = determine_locale();
	} elseif ( function_exists( 'get_user_locale' ) && is_admin() ) {
		$locale = get_user_locale();
	} else {
		$locale = get_locale();
	}

	$locale = apply_filters( 'plugin_locale', $locale, 'better-wp-security' );

	load_textdomain( 'better-wp-security', WP_LANG_DIR . "/plugins/better-wp-security/better-wp-security-$locale.mo" );
	load_plugin_textdomain( 'better-wp-security' );
}

add_action( 'plugins_loaded', 'itsec_load_textdomain' );

if ( isset( $itsec_dir ) || class_exists( 'ITSEC_Core' ) ) {
	include( dirname( __FILE__ ) . '/core/show-multiple-version-notice.php' );
	return;
}


$itsec_dir = dirname( __FILE__ );

if ( is_admin() ) {
	require( "$itsec_dir/lib/icon-fonts/load.php" );
}

require( "$itsec_dir/core/core.php" );
$itsec_core = ITSEC_Core::get_instance();
$itsec_core->init( __FILE__,  'iThemes Security' );
