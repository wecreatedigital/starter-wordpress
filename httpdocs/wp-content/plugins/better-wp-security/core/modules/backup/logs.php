<?php

final class ITSEC_Backup_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_backup_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ) );
		add_filter( 'itsec_logs_prepare_backup_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry ) {
		$entry['module_display'] = esc_html__( 'Database Backups', 'better-wp-security' );

		if ( 'email-failed-file-stored' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'File Created but Email Send Failed', 'better-wp-security' );
		} else if ( 'email-succeeded-file-stored' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'File Created and Emails Sent', 'better-wp-security' );
		} else if ( 'email-failed' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Email Send Failed', 'better-wp-security' );
		} else if ( 'email-succeeded' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Email Send Succeeded', 'better-wp-security' );
		} else if ( 'file-stored' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'File Created', 'better-wp-security' );
		} else if ( 'details' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Details', 'better-wp-security' );
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$entry = $this->filter_entry_for_list_display( $entry, $code, $code_data );

		$details['module']['content'] = $entry['module_display'];
		$details['description']['content'] = $entry['description'];

		return $details;
	}
}
new ITSEC_Backup_Logs();
