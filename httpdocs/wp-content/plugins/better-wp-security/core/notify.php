<?php

/**
 * Handles sending notifications to users
 *
 * @package iThemes-Security
 * @since   4.5
 */
class ITSEC_Notify {

	public function __construct() {

		if ( ! ITSEC_Modules::get_setting( 'global', 'digest_email' ) ) {
			return;
		}

		if ( defined( 'ITSEC_NOTIFY_USE_CRON' ) && true === ITSEC_NOTIFY_USE_CRON ) {

			add_action( 'itsec_digest_email', array( $this, 'init' ) ); //Action to execute during a cron run.

			//schedule digest email
			if ( false === wp_next_scheduled( 'itsec_digest_email' ) ) {
				wp_schedule_event( time(), 'daily', 'itsec_digest_email' );
			}

		} else {
			add_action( 'init', array( $this, 'init' ) );
		}

	}

	/**
	 * Processes and sends daily digest message
	 *
	 * @since 4.5
	 *
	 * @return bool
	 */
	public function init() {

		if ( is_404() ) {
			return false;
		}

		$use_cron   = defined( 'ITSEC_NOTIFY_USE_CRON' ) && ITSEC_NOTIFY_USE_CRON;
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;

		if ( $doing_cron && ! $use_cron ) {
			return false;
		}

		// Check the cached digest_last_sent value. This will be fast but may be inaccurate.
		if ( ! $use_cron ) {
			$last_sent = ITSEC_Modules::get_setting( 'global', 'digest_last_sent' );
			$yesterday = ITSEC_Core::get_current_time_gmt() - DAY_IN_SECONDS;

			if ( $last_sent > $yesterday ) {
				return false;
			}
		}

		// Attempt to acquire a lock so only one process can send the daily digest at a time.
		if ( ! ITSEC_Lib::get_lock( 'daily-digest' ) ) {
			return false;
		}

		if ( ! $use_cron ) {
			// This prevents errors where the last sent value is loaded in memory early in the request, before another process has finished sending the value.
			$last_sent = $this->get_last_sent_uncached();

			// Send digest if it has been 24 hours
			if ( $last_sent > $yesterday ) {

				return false;
			}
		}

		$result = $this->send_daily_digest();

		ITSEC_Lib::release_lock( 'daily-digest' );

		return $result;
	}

	/**
	 * Get the time the daily digest was last sent directly from the database.
	 *
	 * @return int
	 */
	private function get_last_sent_uncached() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$option = 'itsec-storage';
		$storage = array();

		if ( is_multisite() ) {
			$network_id = get_current_site()->id;
			$row        = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", $option, $network_id ) );

			if ( is_object( $row ) ) {
				$storage = maybe_unserialize( $row->meta_value );
			}
		} else {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

			if ( is_object( $row ) ) {
				$storage = maybe_unserialize( $row->option_value );
			}
		}

		return isset( $storage['global'], $storage['global']['digest_last_sent'] ) ? $storage['global']['digest_last_sent'] : 0;
	}

	/**
	 * Send the daily digest email.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	public function send_daily_digest() {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;


		$send_email = false;


		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-mail.php' );
		$mail = new ITSEC_Mail();
		$mail->add_header( esc_html__( 'Daily Security Digest', 'better-wp-security' ), sprintf( wp_kses( __( 'Your Daily Security Digest for <b>%s</b>', 'better-wp-security' ), array( 'b' => array() ) ), date_i18n( get_option( 'date_format' ) ) ) );
		$mail->add_info_box( sprintf( wp_kses( __( 'The following is a summary of security related activity on your site: <b>%s</b>', 'better-wp-security' ), array( 'b' => array() ) ), get_option( 'siteurl' ) ) );


		$mail->add_section_heading( esc_html__( 'Lockouts', 'better-wp-security' ), 'lock' );

		$user_count = sizeof( $itsec_lockout->get_lockouts( 'user', true ) );
		$host_count = sizeof( $itsec_lockout->get_lockouts( 'host', true ) );

		if ( $host_count > 0 || $user_count > 0 ) {
			$mail->add_lockouts_summary( $user_count, $host_count );
			$send_email = true;
		} else {
			$mail->add_text( esc_html__( 'No lockouts since the last email check.', 'better-wp-security' ) );
		}


		$messages = ITSEC_Modules::get_setting( 'global', 'digest_messages' );

		if ( in_array( 'file-change', $messages ) ) {
			$mail->add_section_heading( esc_html__( 'File Changes', 'better-wp-security' ), 'folder' );
			$mail->add_text( esc_html__( 'File changes detected on the site.', 'better-wp-security' ) );
			$send_email = true;

			foreach ( $messages as $index => $message ) {
				if ( 'file-change' === $message ) {
					unset( $messages[$index] );
				}
			}
		}

		if ( ! empty( $messages ) ) {
			$mail->add_section_heading( esc_html__( 'Messages', 'better-wp-security' ), 'message' );

			foreach ( $messages as $message ) {
				$mail->add_text( $message );
			}

			$send_email = true;
		}


		if ( ! $send_email ) {
			return;
		}


		$mail->add_details_box( sprintf( wp_kses( __( 'For more details, <a href="%s"><b>visit your security logs</b></a>', 'better-wp-security' ), array( 'a' => array( 'href' => array() ), 'b' => array() ) ), ITSEC_Mail::filter_admin_page_url( ITSEC_Core::get_logs_page_url() ) ) );
		$mail->add_divider();
		$mail->add_large_text( esc_html__( 'Is your site as secure as it could be?', 'better-wp-security' ) );
		$mail->add_text( esc_html__( 'Ensure your site is using recommended settings and features with a security check.', 'better-wp-security' ) );
		$mail->add_button( esc_html__( 'Run a Security Check ✓', 'better-wp-security' ), ITSEC_Mail::filter_admin_page_url( ITSEC_Core::get_security_check_page_url() ) );

		if ( defined( 'ITSEC_DEBUG' ) && true === ITSEC_DEBUG ) {
			$mail->add_text( sprintf( esc_html__( 'Debug info (source page): %s', 'better-wp-security' ), esc_url( $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ) ) );
		}

		$mail->add_footer();


		ITSEC_Modules::set_setting( 'global', 'digest_last_sent', ITSEC_Core::get_current_time_gmt() );
		ITSEC_Modules::set_setting( 'global', 'digest_messages', array() );
		ITSEC_Storage::save();

		$subject = esc_html__( 'Daily Security Digest', 'better-wp-security' );
		$mail->set_subject( $subject );

		return $mail->send();
	}

	/**
	 * Used by the File Change Detection module to tell the notification system about found file changes.
	 *
	 * @since 2.6.0
	 *
	 * @return null
	 */
	public function register_file_change() {
		// Until a better system can be devised, use the message queue to store this flag.

		$messages = ITSEC_Modules::get_setting( 'global', 'digest_messages' );

		if ( in_array( 'file-change', $messages ) ) {
			return;
		}

		$messages[] = 'file-change';

		ITSEC_Modules::set_setting( 'global', 'digest_messages', $messages );
	}

	/**
	 * Enqueue or send notification accordingly
	 *
	 * @since 4.5
	 *
	 * @param null|array $body Custom message information to send
	 *
	 * @return bool whether the message was successfully enqueue or sent
	 */
	public function notify( $body = null ) {

		$allowed_tags = array(
			'a'      => array(
				'href' => array(),
			),
			'em'     => array(),
			'p'      => array(),
			'strong' => array(),
			'table'  => array(
				'border' => array(),
				'style'  => array(),
			),
			'tr'     => array(),
			'td'     => array(
				'colspan' => array(),
			),
			'th'     => array(),
			'br'     => array(),
			'h4'     => array(),
		);

		if ( ITSEC_Modules::get_setting( 'global', 'digest_email' ) ) {

			$messages = ITSEC_Modules::get_setting( 'global', 'digest_messages' );

			if ( ! in_array( wp_kses( $body, $allowed_tags ), $messages ) ) {

				$messages[] = wp_kses( $body, $allowed_tags );

				ITSEC_Modules::set_setting( 'global', 'digest_messages', $messages );

			}

			return true;

		} else if ( ITSEC_Modules::get_setting( 'global', 'email_notifications', true ) ) {

			$subject = trim( sanitize_text_field( $body['subject'] ) );
			$message = wp_kses( $body['message'], $allowed_tags );

			if ( isset( $body['headers'] ) ) {

				$headers = $body['headers'];

			} else {

				$headers = '';

			}

			$attachments = isset( $body['attachments'] ) && is_array( $body['attachments'] ) ? $body['attachments'] : array();

			return $this->send_mail( $subject, $message, $headers, $attachments );

		}

		return true;

	}

	/**
	 * Sends email to recipient
	 *
	 * @since 4.5
	 *
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	private function send_mail( $subject, $message, $headers = '', $attachments = array() ) {

		$recipients  = ITSEC_Modules::get_setting( 'global', 'notification_email' );
		$all_success = true;

		add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );

		foreach ( $recipients as $recipient ) {

			if ( is_email( trim( $recipient ) ) ) {

				if ( defined( 'ITSEC_DEBUG' ) && ITSEC_DEBUG === true ) {
					$message .= '<p>' . __( 'Debug info (source page): ' . esc_url( $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ) ) . '</p>';
				}

				$success = wp_mail( trim( $recipient ), $subject, '<html>' . $message . '</html>', $headers );

				if ( $all_success === true && $success === false ) {
					$all_success = false;
				}

			}

		}

		remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );

		return $all_success;

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
