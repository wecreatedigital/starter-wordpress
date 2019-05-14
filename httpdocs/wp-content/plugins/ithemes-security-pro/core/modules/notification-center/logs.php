<?php

class ITSEC_Notification_Center_Logs {

	public function __construct() {
		add_filter( 'itsec_logs_prepare_notification_center_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ), 10, 3 );
		add_filter( 'itsec_logs_prepare_notification_center_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry, $code, $data ) {

		$entry['module_display'] = esc_html__( 'Notification Center', 'it-l10n-ithemes-security-pro' );

		switch ( $code ) {
			case 'send':
				list ( $notification ) = $data;

				if ( $strings = ITSEC_Core::get_notification_center()->get_notification_strings( $notification ) ) {
					$notification = $strings['label'];
				}

				$entry['description'] = sprintf( esc_html__( 'Sending %s', 'it-l10n-ithemes-security-pro' ), $notification );
				break;
			case 'send_failed':
				list ( $notification ) = $data;

				if ( $strings = ITSEC_Core::get_notification_center()->get_notification_strings( $notification ) ) {
					$notification = $strings['label'];
				}

				$entry['description'] = sprintf( esc_html__( 'Sending %s Failed', 'it-l10n-ithemes-security-pro' ), $notification );
				break;
			case 'send_scheduled':
				$entry['description'] = esc_html__( 'Sending scheduled notifications', 'it-l10n-ithemes-security-pro' );
				break;
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {

		$details['module']['content'] = esc_html__( 'Notification Center', 'it-l10n-ithemes-security-pro' );

		switch ( $code ) {
			case 'send':
				list ( $notification ) = $code_data;

				if ( $strings = ITSEC_Core::get_notification_center()->get_notification_strings( $notification ) ) {
					$notification = $strings['label'];
				}

				$details['description']['content'] = esc_html__( 'Sending Notification', 'it-l10n-ithemes-security-pro' );
				$details['notification']           = array(
					'header'  => esc_html__( 'Notification', 'it-l10n-ithemes-security-pro' ),
					'content' => $notification,
					'order'   => 21,
				);
				break;
			case 'send_failed':
				list ( $notification ) = $code_data;

				if ( $strings = ITSEC_Core::get_notification_center()->get_notification_strings( $notification ) ) {
					$notification = $strings['label'];
				}

				$details['description']['content'] = esc_html__( 'Sending Notification Failed', 'it-l10n-ithemes-security-pro' );
				$details['notification']           = array(
					'header'  => esc_html__( 'Notification', 'it-l10n-ithemes-security-pro' ),
					'content' => $notification,
					'order'   => 21,
				);
				$details['error_message']          = array(
					'header'  => esc_html__( 'Error', 'it-l10n-ithemes-security-pro' ),
					'content' => wp_sprintf( '%l', ITSEC_Response::get_error_strings( $entry['data']['error'] ) ),
					'order'   => 22,
				);
				break;
			case 'send_scheduled':
				$details['description']['content'] = esc_html__( 'Sending Scheduled Notification', 'it-l10n-ithemes-security-pro' );
				$details['notifications']          = array(
					'header'  => esc_html__( 'Notifications', 'it-l10n-ithemes-security-pro' ),
					'content' => wp_sprintf( '%l', $entry['data']['notifications'] ),
					'order'   => 21,
				);
				break;
				break;
		}

		return $details;
	}
}

new ITSEC_Notification_Center_Logs();