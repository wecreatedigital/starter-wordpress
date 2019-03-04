<?php

/**
 * Handles sending notifications to users
 *
 * @package iThemes-Security
 * @since   4.5
 */
class ITSEC_Notify {

	public function __construct() {
		add_filter( 'itsec_notifications', array( $this, 'register_notification' ) );
		add_filter( 'itsec_digest_notification_strings', array( $this, 'notification_strings' ) );
		add_filter( 'itsec_send_notification_digest', array( $this, 'send_daily_digest' ), 10, 3 );
	}

	/**
	 * Register the digest notification.
	 *
	 * @param array $notifications
	 *
	 * @return array
	 */
	public function register_notification( $notifications ) {
		$notifications['digest'] = array(
			'slug'             => 'digest',
			'recipient'        => ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE,
			'schedule'         => array(
				'min' => ITSEC_Notification_Center::S_DAILY,
				'max' => ITSEC_Notification_Center::S_WEEKLY,
			),
			'subject_editable' => true,
			'optional'         => true,
		);

		return $notifications;
	}

	/**
	 * Get the digest notification strings.
	 *
	 * @return array
	 */
	public function notification_strings() {
		$description = esc_html__( 'During periods of heavy attack, iThemes Security can generate a LOT of email.', 'better-wp-security' );

		if ( ITSEC_Core::is_pro() ) {
			$features = esc_html__( 'The Security Digest reduces the number of emails sent so you can receive a summary of lockouts, file change detection scans, and privilege escalations.' );
		} else {
			$features = esc_html__( 'The Security Digest reduces the number of emails sent so you can receive a summary of lockouts and file change detection scans.' );
		}

		return array(
			'label'       => esc_html__( 'Security Digest', 'better-wp-security' ),
			'description' => $description . ' ' . $features,
			'subject'     => esc_html__( 'Daily Security Digest', 'better-wp-security' ), // Default schedule is Daily
		);
	}

	/**
	 * Send the daily digest email.
	 *
	 * @since 2.6.0
	 *
	 * @param bool  $sent
	 * @param int   $last_sent
	 * @param array $data
	 *
	 * @return bool
	 */
	public function send_daily_digest( $sent, $last_sent, $data ) {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$send_email = false;

		$df = get_option( 'date_format' );
		$nc = ITSEC_Core::get_notification_center();

		switch ( $nc->get_schedule( 'digest' ) ) {
			case ITSEC_Notification_Center::S_DAILY:
				$title = esc_html__( 'Daily Security Digest', 'better-wp-security' );
				$banner_title = sprintf( esc_html__( 'Your Daily Security Digest for %s', 'better-wp-security' ), '<b>' . date_i18n( $df ) . '</b>' );
				break;
			case ITSEC_Notification_Center::S_WEEKLY:
				$period = sprintf(
					'%s - %s',
					ITSEC_Lib::date_format_i18n_and_local_timezone( $last_sent, $df ),
					ITSEC_Lib::date_format_i18n_and_local_timezone( ITSEC_Core::get_current_time_gmt(), $df )
				);

				$title = esc_html__( 'Weekly Security Digest', 'better-wp-security' );
				$banner_title = sprintf( esc_html__( 'Your Weekly Security Digest for %s', 'better-wp-security' ), '<b>' . $period . '</b>' );
				break;
			case ITSEC_Notification_Center::S_MONTHLY:

				$this_day = (int) date( 'j', ITSEC_Core::get_current_time_gmt() );

				if ( $this_day <= 3 ) {
					$period = date_i18n('F Y', $last_sent );
				} else {
					$period = sprintf(
						'%s - %s',
						ITSEC_Lib::date_format_i18n_and_local_timezone( $last_sent, $df ),
						ITSEC_Lib::date_format_i18n_and_local_timezone( ITSEC_Core::get_current_time_gmt(), $df )
					);
				}

				$title = esc_html__( 'Monthly Security Digest', 'better-wp-security' );
				$banner_title = sprintf( esc_html__( 'Your Monthly Security Digest for %s', 'better-wp-security' ), '<b>' . $period . '</b>' );
				break;
			default:
				$period = sprintf(
					'%s - %s',
					ITSEC_Lib::date_format_i18n_and_local_timezone( $last_sent, $df ),
					ITSEC_Lib::date_format_i18n_and_local_timezone( ITSEC_Core::get_current_time_gmt(), $df )
				);

				$title = esc_html__( 'Security Digest', 'better-wp-security' );
				$banner_title = sprintf( esc_html__( 'Your Security Digest for %s', 'better-wp-security' ), '<b>' . $period . '</b>' );
				break;
		}

		$data_proxy = new ITSEC_Notify_Data_Proxy( $data );

		$mail = $nc->mail( 'digest' );
		$mail->add_header( $title, $banner_title );
		$mail->start_group( 'intro' );
		$mail->add_info_box( sprintf( esc_html__( 'The following is a summary of security related activity on your site: %s', 'better-wp-security' ), '<b>' . $mail->get_display_url() . '</b>' ) );
		$mail->end_group();

		$content = $mail->get_content();

		/**
		 * Fires before the main content of the Security Digest is added.
		 *
		 * @param ITSEC_Mail              $mail
		 * @param ITSEC_Notify_Data_Proxy $data_proxy
		 * @param int                     $last_sent
		 */
		do_action( 'itsec_security_digest_before', $mail, $data_proxy, $last_sent );

		if ( $content !== $mail->get_content() ) {
			$send_email = true;
		}

		$mail->add_section_heading( esc_html__( 'Lockouts', 'better-wp-security' ), 'lock' );

		$user_count = $itsec_lockout->get_lockouts( 'user', array( 'after' => $last_sent, 'current' => false, 'return' => 'count' ) );
		$host_count = $itsec_lockout->get_lockouts( 'host', array( 'after' => $last_sent, 'current' => false, 'return' => 'count' ) );

		if ( $host_count > 0 || $user_count > 0 ) {
			$mail->add_lockouts_summary( $user_count, $host_count );
			$send_email = true;
		} else {
			$mail->add_text( esc_html__( 'No lockouts since the last email check.', 'better-wp-security' ) );
		}

		if ( $data_proxy->has_message( 'file-change' ) ) {
			$mail->add_section_heading( esc_html__( 'File Changes', 'better-wp-security' ), 'folder' );
			$mail->add_text( esc_html__( 'File changes detected on the site.', 'better-wp-security' ) );
			$send_email = true;
		}

		if ( ! $send_email ) {
			$content = $mail->get_content();
		}

		/**
		 * Fires when additional info should be attached to the Security Digest.
		 *
		 * @since 3.9.0
		 *
		 * @param ITSEC_Mail              $mail
		 * @param ITSEC_Notify_Data_Proxy $data_proxy
		 * @param int                     $last_sent
		 */
		do_action( 'itsec_security_digest_attach_additional_info', $mail, $data_proxy, $last_sent );

		if ( ! $send_email && $content !== $mail->get_content() ) {
			$send_email = true;
		}

		$messages = $this->get_general_messages( $data );

		if ( $messages ) {
			$mail->add_section_heading( esc_html__( 'Messages', 'better-wp-security' ), 'message' );

			foreach ( $messages as $message ) {
				$mail->add_text( $message );
			}

			$send_email = true;
		}


		if ( ! $send_email ) {
			return true;
		}


		$mail->add_details_box( sprintf(
			esc_html__( 'For more details, %1$svisit your security logs%2$s', 'better-wp-security' ),
			'<a href="' . ITSEC_Mail::filter_admin_page_url( ITSEC_Core::get_logs_page_url() ) . '"><b>',
			'</b></a>'
		) );

		if ( apply_filters( 'itsec_security_digest_include_security_check', true ) ) {
			$mail->add_divider();
			$mail->add_large_text( esc_html__( 'Is your site as secure as it could be?', 'better-wp-security' ) );
			$mail->add_text( esc_html__( 'Ensure your site is using recommended settings and features with a security check.', 'better-wp-security' ) );
			$mail->add_button( esc_html__( 'Run a Security Check ✓', 'better-wp-security' ), ITSEC_Mail::filter_admin_page_url( ITSEC_Core::get_security_check_page_url() ) );
		}

		$mail->add_footer();

		return $nc->send( 'digest', $mail );
	}

	/**
	 * Get general digest messages.
	 *
	 * @param array $data
	 *
	 * @return string[]
	 */
	private function get_general_messages( $data ) {

		$messages = array();

		foreach ( $data as $datum ) {

			if ( ! is_array( $datum ) || ! isset( $datum['message'] ) ) {
				continue;
			}

			if ( isset( $datum['type'] ) && 'general' !== $datum['type'] ) {
				continue;
			}

			$messages[] = $datum['message'];
		}

		return $messages;
	}

	/**
	 * Used by the File Change Detection module to tell the notification system about found file changes.
	 *
	 * @since 2.6.0
	 *
	 * @return null
	 */
	public function register_file_change() {
		_deprecated_function( __METHOD__, '3.9.0', 'ITSEC_Notification_Center::enqueue_data' );
		ITSEC_Core::get_notification_center()->enqueue_data( 'digest', array( 'type' => 'file-change' ) );
	}

	/**
	 * Set HTML content type for email
	 *
	 * @since 4.5
	 *
	 * @return string html content type
	 */
	public function wp_mail_content_type() {

		return 'text/html';

	}
}

class ITSEC_Notify_Data_Proxy {

	/** @var array */
	private $data;

	/**
	 * ITSEC_Notify_Data_Proxy constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data ) { $this->data = $data; }

	/**
	 * Check for a queued message.
	 *
	 * @param string $type
	 *
	 * @return array|null
	 */
	public function has_message( $type ) {

		foreach ( $this->data as $datum ) {

			if ( ! is_array( $datum ) ) {
				continue;
			}

			if ( isset( $datum['type'] ) && $type === $datum['type'] ) {
				return $datum;
			}
		}

		return null;
	}

	/**
	 * Get all messages of a given type.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function get_messages_of_type( $type ) {

		$of_type = array();

		foreach ( $this->data as $datum ) {
			if ( ! is_array( $datum ) ) {
				continue;
			}

			if ( isset( $datum['type'] ) && $type === $datum['type'] ) {
				$of_type[] = $datum;
			}
		}

		return $of_type;
	}

}