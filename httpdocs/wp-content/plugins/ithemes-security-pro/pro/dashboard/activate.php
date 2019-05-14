<?php

if ( version_compare( $GLOBALS['wp_version'], '5.0.0', '<' ) ) {
	return new WP_Error( 'itsec-dashboard-wp-requirements', esc_html__( 'The Dashboard module requires WordPress 5.0.0 or later.', 'it-l10n-ithemes-security-pro' ) );
}

ITSEC_Core::get_scheduler()->schedule( ITSEC_Scheduler::S_DAILY, 'dashboard-consolidate-events' );

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
global $wpdb;

dbDelta( "
CREATE TABLE {$wpdb->base_prefix}itsec_dashboard_events (
  event_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  event_slug varchar(128) NOT NULL DEFAULT '',
  event_time datetime NOT NULL,
  event_count int(11) unsigned NOT NULL DEFAULT '1',
  event_consolidated tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`event_id`),
  UNIQUE KEY `event_slug__time__consolidated` (event_slug,event_time,event_consolidated)
) {$wpdb->get_charset_collate()};" );

if ( ! ITSEC_Modules::get_setting( 'dashboard', 'migrated' ) ) {
	require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );
	ITSEC_Dashboard_Util::migrate();
	ITSEC_Modules::set_setting( 'dashboard', 'migrated', true );
}
