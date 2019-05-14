<?php

/**
 * Manage and configure all the notifications sent by iThemes Security. View any errors that arise during the sending of notifications.
 */
class ITSEC_Notification_Center_Command extends WP_CLI_Command {

	/** @var ITSEC_Notification_Center */
	private $center;

	/**
	 * ITSEC_Notification_Center_Command constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->center = ITSEC_Core::get_notification_center();
	}

	/**
	 * List the available notifications.
	 *
	 * ## OPTIONS
	 *
	 * [--enabled]
	 * : Only list enabled notifications.
	 *
	 * [--include-emails]
	 * : Include the email addresses for the recipients.
	 *
	 * [--format=<format>]
	 * : Choose the format to output notifications as.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 *
	 * @subcommand list
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function list_( $args, $assoc_args ) {

		$assoc_args = wp_parse_args( $assoc_args, array( 'format' => 'table' ) );

		if ( ! empty( $assoc_args['enabled'] ) ) {
			$notifications = $this->center->get_enabled_notifications();
		} else {
			$notifications = $this->center->get_notifications();
		}

		$data = array();

		foreach ( $notifications as $slug => $notification ) {
			if ( ITSEC_Notification_Center::S_NONE === $notification['schedule'] ) {
				$last_sent = '-';
				$next_send = '-';
			} else {
				$last_sent = date( 'Y-m-d H:i:s', $this->center->get_last_sent( $slug ) );
				$next_send = date( 'Y-m-d H:i:s', $this->center->get_next_send_time( $slug ) );
			}

			$item = array(
				'id'        => $slug,
				'recipient' => $notification['recipient'],
				'schedule'  => $this->center->get_schedule( $slug ),
				'last_sent' => $last_sent,
				'next_send' => $next_send,
			);

			$include_emails = $item['recipient'] !== ITSEC_Notification_Center::R_USER && $item['recipient'] !== ITSEC_Notification_Center::R_PER_USE;

			if ( $include_emails && ! empty( $assoc_args['include-emails'] ) ) {
				$item['recipient'] = implode( ', ', $this->center->get_recipients( $slug ) );
			}

			$data[] = $item;
		}

		if ( 'id' === $assoc_args['format'] ) {
			$data = wp_list_pluck( $data, 'id' );
		}

		\WP_CLI\Utils\format_items( $assoc_args['format'], $data, array( 'id', 'recipient', 'schedule', 'last_sent', 'next_send' ) );
	}

	/**
	 * Check the notification schedule and send any notifications that are due.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Choose the format to output the result of the schedule check.
	 *
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function check( $args, $assoc_args ) {

		$assoc_args = wp_parse_args( $assoc_args, array( 'format' => 'table' ) );

		$sent = $this->center->check_notification_schedule_accurate();

		if ( is_wp_error( $sent ) ) {
			WP_CLI::error( $sent );
		}

		$notifications = $this->center->get_notifications();

		$data = array();

		foreach ( $notifications as $slug => $notification ) {

			if ( ! $this->center->is_notification_enabled( $slug ) ) {
				$status = esc_html_x( 'Disabled', 'Notification is disabled', 'it-l10n-ithemes-security-pro' );
			} elseif ( ! array_key_exists( $slug, $sent ) ) {
				$status = esc_html_x( 'Not ready', 'Notification is not ready to be sent', 'it-l10n-ithemes-security-pro' );
			} elseif ( empty( $sent[ $slug ] ) ) {
				$status = esc_html_x( 'Failed', 'Notification failed to send', 'it-l10n-ithemes-security-pro' );
			} else {
				$status = esc_html__( 'Sent', 'it-l10n-ithemes-security-pro' );
			}

			$data[] = array(
				'id'     => $slug,
				'status' => $status,
			);
		}

		\WP_CLI\Utils\format_items( $assoc_args['format'], $data, array( 'id', 'status' ) );
	}

	/**
	 * Send a scheduled notification.
	 *
	 * ## OPTIONS
	 *
	 * <notification>
	 * : The notification to send.
	 *
	 * [--force]
	 * : Force sending it even if it is not time.
	 *
	 * [--silent]
	 * : Don't update last sent time and don't destroy queued data. Only supported with force.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function send( $args, $assoc_args ) {

		list( $slug ) = $args;

		if ( empty( $assoc_args['force'] ) ) {
			$sent = $this->center->check_notification_schedule_accurate( array( $slug => $this->center->get_notification( $slug ) ) );

			if ( is_wp_error( $sent ) ) {
				WP_CLI::error( $sent );
			}

			if ( ! $sent ) {
				WP_CLI::warning( esc_html__( 'Not time to send.', 'it-l10n-ithemes-security-pro' ) );

				return;
			}

			$sent = $sent[ $slug ];
		} else {
			$sent = $this->center->send_scheduled_notifications( array( $slug ), ! empty( $assoc_args['silent'] ) );
			$sent = $sent[ $slug ];
		}

		if ( is_wp_error( $sent ) ) {
			WP_CLI::error( $sent );
		}

		if ( ! $sent ) {
			WP_CLI::error( esc_html__( 'Failed to send notification.', 'it-l10n-ithemes-security-pro' ) );
		}

		WP_CLI::success( esc_html__( 'Notification sent.', 'it-l10n-ithemes-security-pro' ) );
	}
}

WP_CLI::add_command( 'itsec nc', 'ITSEC_Notification_Center_Command' );