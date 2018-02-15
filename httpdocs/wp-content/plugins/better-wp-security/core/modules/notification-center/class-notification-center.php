<?php

/**
 * Class ITSEC_Notification_Center
 */
final class ITSEC_Notification_Center {

	const R_USER = 'user'; // Goes to an end user. Two Factor or Magic Links.
	const R_ADMIN = 'admin'; // Emails currently listed in Global Settings -> Notification Email
	const R_USER_LIST = 'user-list'; // Can select users who should receive the email. For example Malware Scheduling.
	const R_EMAIL_LIST = 'email-list'; // List of email addresses.
	const R_PER_USE = 'per-use'; // Email address is selected before performing the action. For example Import/Export.
	const R_USER_LIST_ADMIN_UPGRADE = 'user-list-admin-upgrade'; // Can select users/roles, but was previously the admin email list. Contains upgrade functionality

	const S_NONE = 'none';
	const S_DAILY = 'daily';
	const S_WEEKLY = 'weekly';
	const S_MONTHLY = 'monthly';
	const S_CONFIGURABLE = 'configurable';

	// If this is updated, make sure to update setup.php as well
	const CRON_ACTION = 'itsec-send-scheduled-notifications';

	/** @var bool */
	private $use_cron;

	/**
	 * Array of notification configs, keyed by notification slug.
	 *
	 * Lazily computed, see ::get_notifications().
	 *
	 * @var array
	 */
	private $notifications;

	/**
	 * Array of notification strings, keyed by notification slug.
	 * Separated from regular configuration due to gettext perforamnce.
	 *
	 * Lazily computed, see ::get_notification_strings().
	 *
	 * @var array
	 */
	private $strings = array();

	/**
	 * The current notification being sent by ::send().
	 *
	 * Used for providing additional information when capturing mail errors.
	 *
	 * This could be replaced with closure scope if migrated to PHP 5.3.
	 *
	 * @var string
	 */
	private $_sending_notification = '';

	/**
	 * ITSEC_Notification_Center constructor.
	 */
	public function __construct() {
		$this->use_cron = defined( 'ITSEC_NOTIFY_USE_CRON' ) && ITSEC_NOTIFY_USE_CRON;
	}

	/**
	 * Get registered notifications.
	 *
	 * This value is cached.
	 *
	 * @return array
	 */
	public function get_notifications() {

		if ( null === $this->notifications ) {
			/**
			 * Filter the registered notifications.
			 *
			 * Do not conditionally register the filter, instead perform any conditional registration in the callback,
			 * so the cache can be properly cleared on settings changes.
			 *
			 * @param array                     $notifications
			 * @param ITSEC_Notification_Center $this
			 */
			$notifications = apply_filters( 'itsec_notifications', array(), $this );

			foreach ( $notifications as $slug => $notification ) {
				$notification                 = $this->notification_defaults( $notification );
				$notification['slug']         = $slug;
				$this->notifications[ $slug ] = $notification;
			}
		}

		return $this->notifications;
	}

	/**
	 * Clear the notifications cache.
	 *
	 * This shouldn't be necessary in the vast majority of cases.
	 */
	public function clear_notifications_cache() {
		$this->notifications = null;
	}

	/**
	 * Get enabled notifications.
	 *
	 * @return array
	 */
	public function get_enabled_notifications() {
		$notifications = $this->get_notifications();
		$enabled       = array();

		foreach ( $notifications as $slug => $notification ) {
			if ( $this->is_notification_enabled( $slug ) ) {
				$enabled[ $slug ] = $notification;
			}
		}

		return $enabled;
	}

	/**
	 * Check if a notification is enabled.
	 *
	 * @param string $notification
	 *
	 * @return bool
	 */
	public function is_notification_enabled( $notification ) {

		$config = $this->get_notification( $notification );

		if ( ! $config ) {
			return false;
		}

		if ( empty( $config['optional'] ) ) {
			return true;
		}

		$settings = $this->get_notification_settings( $notification );

		return ! empty( $settings['enabled'] );
	}

	/**
	 * Parse notification defaults.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	private function notification_defaults( $args ) {
		$args = wp_parse_args( $args, array(
			'recipient'        => self::R_ADMIN,
			'schedule'         => self::S_NONE,
			'subject_editable' => false,
			'message_editable' => false,
			'optional'         => false,
			'tags'             => array(),
			'module'           => '',
		) );

		$schedules = self::get_schedule_order();
		$schedule  = array(
			'min'     => $schedules[0],
			'max'     => $schedules[ count( $schedules ) - 1 ],
			'default' => self::S_DAILY,
		);

		if ( $args['schedule'] === self::S_CONFIGURABLE ) {
			$args['schedule'] = $schedule;
		} elseif ( is_array( $args['schedule'] ) ) {
			$args['schedule'] = wp_parse_args( $args['schedule'], $schedule );
		}

		return $args;
	}

	/**
	 * Get the notification config.
	 *
	 * @param string $slug
	 *
	 * @return array|null
	 */
	public function get_notification( $slug ) {
		$notifications = $this->get_notifications();

		return isset( $notifications[ $slug ] ) ? $notifications[ $slug ] : null;
	}

	/**
	 * Get strings for a notification.
	 *
	 * @param string $slug
	 *
	 * @return array
	 */
	public function get_notification_strings( $slug ) {

		if ( ! isset( $this->strings[ $slug ] ) ) {
			$this->strings[ $slug ] = apply_filters( "itsec_{$slug}_notification_strings", array() );
		}

		return $this->strings[ $slug ];
	}

	/**
	 * Get the configured subject for a notification.
	 *
	 * @param string $notification
	 *
	 * @return string
	 */
	public function get_subject( $notification ) {

		$config = $this->get_notification( $notification );

		if ( ! $config ) {
			return '';
		}

		$settings = $this->get_notification_settings( $notification );

		if ( ! empty( $config['subject_editable'] ) && ! empty( $settings['subject'] ) ) {
			return $settings['subject'];
		}

		$strings = $this->get_notification_strings( $notification );

		return isset( $strings['subject'] ) ? $strings['subject'] : '';
	}

	/**
	 * Get the configured main message for a notification.
	 *
	 * @param string $notification
	 * @param string $format Either 'raw' or 'display'. If 'display', the message will have wpautop. Defaults to 'display'.
	 *
	 * @return string
	 */
	public function get_message( $notification, $format = 'display' ) {

		$config = $this->get_notification( $notification );

		if ( ! $config ) {
			return '';
		}

		$settings = $this->get_notification_settings( $notification );

		if ( ! empty( $config['message_editable'] ) && ! empty( $settings['message'] ) ) {
			return 'display' === $format ? wpautop( $settings['message'] ) : $settings['message'];
		}

		$strings = $this->get_notification_strings( $notification );

		if ( isset( $strings['message'] ) ) {
			return 'display' === $format ? wpautop( $strings['message'] ) : $strings['message'];
		}

		return '';
	}

	/**
	 * Get the selected schedule for a notification.
	 *
	 * @param string $notification
	 *
	 * @return string
	 */
	public function get_schedule( $notification ) {

		$config = $this->get_notification( $notification );

		if ( ! $config ) {
			return self::S_NONE;
		}

		if ( self::S_CONFIGURABLE !== $config['schedule'] && ! is_array( $config['schedule'] ) ) {
			return $config['schedule'];
		}

		$settings = $this->get_notification_settings( $notification );

		if ( ! empty( $settings['schedule'] ) ) {
			return $settings['schedule'];
		}

		return $config['schedule']['min'];
	}

	/**
	 * Get the email addresses a notification should be sent to.
	 *
	 * @param string $notification
	 *
	 * @return string[]
	 */
	public function get_recipients( $notification ) {

		$config = $this->get_notification( $notification );

		if ( self::R_ADMIN === $config['recipient'] ) {
			return array( get_option( 'admin_email' ) );
		}

		if ( self::R_EMAIL_LIST === $config['recipient'] ) {
			$settings = $this->get_notification_settings( $notification );

			return ! empty( $settings['email_list'] ) ? $settings['email_list'] : array();
		}

		if ( self::R_USER_LIST !== $config['recipient'] && self::R_USER_LIST_ADMIN_UPGRADE !== $config['recipient'] ) {
			return array();
		}

		$settings = $this->get_notification_settings( $notification );
		$contacts = $settings['user_list'];

		$addresses = array();

		foreach ( $contacts as $contact ) {
			if ( (string) $contact === (string) intval( $contact ) ) {
				$users = array( get_userdata( $contact ) );
			} else {
				list( $prefix, $role ) = explode( ':', $contact, 2 );

				if ( empty( $role ) ) {
					continue;
				}

				$users = get_users( array( 'role' => $role ) );
			}

			foreach ( $users as $user ) {
				if ( is_object( $user ) && ! empty( $user->user_email ) ) {
					$addresses[] = $user->user_email;
				}
			}
		}

		if ( self::R_USER_LIST_ADMIN_UPGRADE === $config['recipient'] && ! empty( $settings['previous_emails'] ) ) {
			$addresses = array_merge( $addresses, $settings['previous_emails'] );
		}

		return array_unique( $addresses );
	}

	/**
	 * Get the time the notification was last sent.
	 *
	 * @param string $notification
	 *
	 * @return int
	 */
	public function get_last_sent( $notification ) {
		$last_sent = $this->get_all_last_sent();

		return isset( $last_sent[ $notification ] ) ? $last_sent[ $notification ] : 0;
	}

	/**
	 * Get the time that the notification should next be sent.
	 *
	 * @param string $notification The notification slug.
	 *
	 * @return int|false False if invalid notification or invalid notification schedule. Unix time otherwise.
	 */
	public function get_next_send_time( $notification ) {
		return $this->calculate_next_send_time( $notification, $this->get_last_sent( $notification ) );
	}

	/**
	 * Enqueue some data a scheduled notification should have access to when sending.
	 *
	 * @param string $notification
	 * @param mixed  $data
	 * @param bool   $enforce_unique Whether to enforce all the data for that notification is unique. Only set to false if you are sure data is already unique.
	 */
	public function enqueue_data( $notification, $data, $enforce_unique = true ) {
		$all_data = ITSEC_Modules::get_setting( 'notification-center', 'data' );

		$notification_data   = isset( $all_data[ $notification ] ) ? $all_data[ $notification ] : array();
		$notification_data[] = $data;

		if ( $enforce_unique ) {
			$notification_data = array_unique( $notification_data );
		}

		$all_data[ $notification ] = $notification_data;

		ITSEC_Modules::set_setting( 'notification-center', 'data', $all_data );
	}

	/**
	 * Get the data for a notification.
	 *
	 * @param string $notification
	 *
	 * @return array
	 */
	public function get_data( $notification ) {

		$all_data = ITSEC_Modules::get_setting( 'notification-center', 'data' );

		return isset( $all_data[ $notification ] ) ? $all_data[ $notification ] : array();
	}

	/**
	 * Initialize a Mail instance.
	 *
	 * @return ITSEC_Mail
	 */
	public function mail() {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-mail.php' );

		return new ITSEC_Mail();
	}

	/**
	 * Send an email.
	 *
	 * This will set the subject and recipients configured for the notification if they have not been set.
	 *
	 * Additionally, will log any errors encountered while sending.
	 *
	 * @param string     $notification
	 * @param ITSEC_Mail $mail
	 *
	 * @return bool
	 */
	public function send( $notification, $mail ) {

		if ( ! $mail->get_subject() ) {
			$mail->set_subject( $this->get_subject( $notification ) );
		}

		if ( ! $mail->get_recipients() ) {
			$mail->set_recipients( $this->get_recipients( $notification ) );
		}

		add_action( 'wp_mail_failed', array( $this, 'capture_mail_fail' ) );

		$this->_sending_notification = $notification;
		$result                      = $mail->send();
		$this->_sending_notification = '';

		remove_action( 'wp_mail_failed', array( $this, 'capture_mail_fail' ) );

		return $result;
	}

	/**
	 * Dismiss an error encountered while sending a notification with wp_mail().
	 *
	 * @param string $error_id
	 */
	public function dismiss_mail_error( $error_id ) {
		$errors = ITSEC_Modules::get_setting( 'notification-center', 'mail_errors', array() );
		unset( $errors[ $error_id ] );
		ITSEC_Modules::set_setting( 'notification-center', 'mail_errors', $errors );
	}

	/**
	 * Get the loggged mail errors keyed by id.
	 *
	 * @return array
	 */
	public function get_mail_errors() {
		return ITSEC_Modules::get_setting( 'notification-center', 'mail_errors', array() );
	}

	/**
	 * Initialize the module.
	 */
	public function run() {
		add_action( 'itsec_change_admin_user_id', array( $this, 'update_notification_user_id_on_admin_change' ) );
		add_action( 'itsec_module_settings_after_title', array( $this, 'display_notification_center_link_for_module' ) );
		$this->setup_scheduling();
	}

	/**
	 * Capture whenever an error occurs in wp_mail() while sending a notification so it can be displayed later in the Notification Center.
	 *
	 * @param WP_Error $error
	 */
	public function capture_mail_fail( $error ) {

		$errors = ITSEC_Modules::get_setting( 'notification-center', 'mail_errors', array() );

		$errors[ uniqid() ] = array(
			'error'        => array( 'message' => $error->get_error_message(), 'code' => $error->get_error_code() ),
			'time'         => ITSEC_Core::get_current_time_gmt(),
			'notification' => $this->_sending_notification,
		);

		ITSEC_Modules::set_setting( 'notification-center', 'mail_errors', $errors );

		if ( ITSEC_Core::is_interactive() ) {
			ITSEC_Response::reload_module( 'notification-center' );
		}
	}

	/**
	 * Update the notification settings when the admin user id changes.
	 *
	 * @since 4.1.0
	 *
	 * @param int $new_user_id
	 */
	public function update_notification_user_id_on_admin_change( $new_user_id ) {

		$settings      = ITSEC_Modules::get_settings_obj( 'notification-center' );
		$notifications = $settings->get( 'notifications' );

		if ( empty( $notifications ) ) {
			return;
		}

		$changed = false;

		foreach ( $notifications as $slug => $notification ) {

			if ( empty( $notification['user_list'] ) ) {
				continue;
			}

			$user_list = $notification['user_list'];

			foreach ( $user_list as $i => $contact ) {
				if ( is_numeric( $contact ) && 1 === (int) $contact ) {
					$notifications[ $slug ]['user_list'][ $i ] = $new_user_id;

					$changed = true;
					break;
				}
			}
		}

		if ( $changed ) {
			$settings->set( 'notifications', $notifications );
		}
	}

	/**
	 * Display a link to the notification center for any modules that have an associated notification.
	 *
	 * @param string $module_slug
	 */
	public function display_notification_center_link_for_module( $module_slug ) {

		$display = false;

		foreach ( $this->get_notifications() as $slug => $notification ) {
			if ( $module_slug === $notification['module'] ) {
				$display = $slug;
				break;
			}
		}

		if ( $display ) {
			$href = esc_attr( "#itsec-notification-center-notification-settings--{$display}" );
			echo '<a href="' . $href .'" class="itsec-notification-center-link" data-module-link="notification-center">' . esc_html__( 'Notification Center', 'better-wp-security' ) . '</a>';
		}
	}

	/**
	 * Setup scheduling actions.
	 */
	private function setup_scheduling() {
		if ( $this->use_cron ) {
			if ( ! wp_next_scheduled( self::CRON_ACTION ) ) {
				wp_schedule_event( time(), 'daily', self::CRON_ACTION );
			}

			// We can afford the more expensive check when running cron.
			add_action( self::CRON_ACTION, array( $this, 'check_notification_schedule_accurate' ) );
		} else {
			add_action( 'init', array( $this, 'check_notification_schedule_fast' ), 20 );
		}
	}

	/**
	 * This runs on every page load, so we only use the cached last sent options and don't get a lock unless we think some notifications
	 * need to be run.
	 *
	 * @return array|WP_Error
	 */
	public function check_notification_schedule_fast() {

		$last_sent = $this->get_all_last_sent();
		$resend_at = $this->get_all_resend_at();
		$to_send   = array();

		foreach ( $this->get_enabled_notifications() as $slug => $notification ) {

			$time = $resend_at[ $slug ] > $last_sent[ $slug ] ? $resend_at[ $slug ] : $last_sent[ $slug ];

			if ( $this->is_time_to_send_notification( $slug, $time ) ) {
				$to_send[ $slug ] = $notification;
			}
		}

		if ( $to_send ) {
			return $this->check_notification_schedule_accurate( $to_send );
		}

		return array();
	}

	/**
	 * This checks against the uncached last sent times.
	 *
	 * @param array[] $notifications Notifications to check. Leave empty to check against all.
	 *
	 * @return array|WP_Error
	 */
	public function check_notification_schedule_accurate( $notifications = array() ) {

		$notifications = $notifications && is_array( $notifications ) ? $notifications : $this->get_enabled_notifications();

		if ( ! ITSEC_Lib::get_lock( 'notification-center', 120 ) ) {
			return new WP_Error( 'itsec-notification-center-cannot-get-lock', esc_html__( 'Cannot get lock.', 'better-wp-security' ) );
		}

		$last_sent = $this->get_all_last_sent_uncached();
		$resend_at = $this->get_all_resend_at_uncached();
		$to_send   = array();

		foreach ( $notifications as $slug => $notification ) {
			$time = $resend_at[ $slug ] > $last_sent[ $slug ] ? $resend_at[ $slug ] : $last_sent[ $slug ];

			if ( $this->is_time_to_send_notification( $slug, $time ) ) {
				$to_send[] = $slug;
			}
		}

		$ret = array();

		if ( $to_send ) {
			$ret = $this->send_scheduled_notifications( $to_send );
		}

		ITSEC_Lib::release_lock( 'notification-center' );

		return $ret;
	}

	/**
	 * Send scheduled notifications.
	 *
	 * @param string[] $notification_slugs The notification slugs to send.
	 * @param bool     $silent             If true, will not update last sent times or destroy data. Defaults to false.
	 *
	 * @return array Notification slugs keyed to send success.
	 */
	public function send_scheduled_notifications( $notification_slugs, $silent = false ) {

		@set_time_limit( 120 );
		$sent = array();

		foreach ( $notification_slugs as $notification_slug ) {
			$sent[ $notification_slug ] = $this->send_scheduled_notification( $notification_slug );
		}

		if ( $silent ) {
			return $sent;
		}

		$settings = ITSEC_Modules::get_settings( 'notification-center' );

		foreach ( $notification_slugs as $slug ) {

			// Only clear queued data if the notification was actually able to be sent.
			if ( ! empty( $sent[ $slug ] ) ) {
				$settings['data'][ $slug ]      = array();
				$settings['last_sent'][ $slug ] = ITSEC_Core::get_current_time_gmt();
			} else {
				// Retry sending the notification in 6 hours.
				$settings['resend_at'][ $slug ] = ITSEC_Core::get_current_time_gmt() + 6 * HOUR_IN_SECONDS;
			}
		}

		ITSEC_Modules::set_settings( 'notification-center', $settings );
		ITSEC_Storage::save();

		return $sent;
	}

	/**
	 * Send a scheduled notification.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	private function send_scheduled_notification( $slug ) {

		$last_sent = $this->get_last_sent( $slug );
		$data      = $this->get_data( $slug );

		if ( ! has_filter( "itsec_send_notification_{$slug}" ) ) {
			return $this->default_send( $slug, $last_sent, $data );
		}

		/**
		 * Fire an action to send the requested notification.
		 *
		 * @param bool  $sent      Whether the notification has been successfully sent.
		 * @param int   $last_sent The time this notification was last sent to the user.
		 * @param array $data      Queued data.
		 */
		return apply_filters( "itsec_send_notification_{$slug}", false, $last_sent, $data );
	}

	/**
	 * Default schedule notification handler.
	 *
	 * @param string $notification
	 * @param int    $last_sent
	 * @param array  $data
	 *
	 * @return bool
	 */
	private function default_send( $notification, $last_sent, $data ) {

		$config = $this->get_notification( $notification );

		$mail = $this->mail();

		if ( ! isset( $config['template'] ) ) {
			$mail->add_header( $this->get_subject( $notification ), '' );
			$mail->add_text( $this->get_message( $notification ) );
			$mail->add_footer();
		} elseif ( $data ) {
			foreach ( $config['template'] as $part ) {
				$this->render_template_part( $mail, $data, $part );
			}
		} else {
			return true;
		}

		$this->replace_computed_tags( $mail, $last_sent, $config );

		return $this->send( $notification, $mail );
	}

	/**
	 * Render a template part.
	 *
	 * @param ITSEC_Mail $mail
	 * @param array[]    $data
	 * @param array      $part
	 */
	private function render_template_part( $mail, $data, $part ) {

		if ( empty( $part ) || ! is_array( $part ) || empty( $part[0] ) ) {
			return;
		}

		switch ( $part[0] ) {
			case 'header':
				$func = 'add_header';
				break;
			case 'footer':
				$func = 'add_footer';
				break;
			case 'table':

				if ( ! isset( $part[1], $part[2] ) ) {
					return;
				}

				$paths   = $part[2];
				$columns = array();

				foreach ( $paths as $path ) {
					$resolved = $this->resolve_data_path( $data, $path );

					if ( $resolved === false ) {
						$columns[] = array_fill( 0, count( $data ), $path );
					} else {
						$columns[] = $resolved;
					}
				}

				$rows = self::flip_2d_array( $columns );
				$mail->add_table( $part[1], $rows );

				return;
			default:
				return;

		}

		$args = array_slice( $part, 1 );
		call_user_func_array( array( $mail, $func ), $args );
	}

	/**
	 * Replace the computed tags.
	 *
	 * @param ITSEC_Mail $mail
	 * @param int        $last_sent
	 * @param array      $config
	 */
	private function replace_computed_tags( $mail, $last_sent, $config ) {

		$df = get_option( 'date_format' );

		if ( self::S_DAILY === $config['schedule'] ) {
			$_period = ITSEC_Lib::date_format_i18n_and_local_timezone( ITSEC_Core::get_current_time_gmt(), $df );
		} else {
			$_period = sprintf(
				'%s - %s',
				ITSEC_Lib::date_format_i18n_and_local_timezone( $last_sent, $df ),
				ITSEC_Lib::date_format_i18n_and_local_timezone( ITSEC_Core::get_current_time_gmt(), $df )
			);
		}

		$tags = compact( '_period' );

		$mail->set_content( ITSEC_Lib::replace_tags( $mail->get_content(), $tags ) );
	}

	/**
	 * Resolve a data path from stored data.
	 *
	 * @param array  $data
	 * @param string $path
	 *
	 * @return array|false
	 */
	private function resolve_data_path( $data, $path ) {

		if ( strpos( $path, ':data' ) !== 0 ) {
			return false;
		}

		$path   = substr( $path, 6 );
		$values = array();

		foreach ( $data as $entry ) {
			$values[] = ITSEC_Lib::array_get( $entry, $path );
		}

		return $values;
	}

	/**
	 * Check if enough time has elapsed for a scheduled notification to be sent.
	 *
	 * @param string $notification Notification slug.
	 * @param int    $last_sent
	 *
	 * @return bool False if not time, the notification isn't scheduled, or it has an unknown period.
	 */
	private function is_time_to_send_notification( $notification, $last_sent ) {

		$next = $this->calculate_next_send_time( $notification, $last_sent );

		return $next && $next < ITSEC_Core::get_current_time_gmt();
	}

	/**
	 * Calculate the next time a notification should be sent.
	 *
	 * @param string $notification The notification slug.
	 * @param int    $last_sent    Time to calculate from.
	 *
	 * @return int|false
	 */
	private function calculate_next_send_time( $notification, $last_sent ) {
		$schedule = $this->get_schedule( $notification );

		if ( self::S_NONE === $schedule ) {
			return false; // This is an on-demand
		}

		switch ( $schedule ) {
			case self::S_DAILY:
				$period = DAY_IN_SECONDS;
				break;
			case self::S_WEEKLY:
				$period = WEEK_IN_SECONDS;
				break;
			case self::S_MONTHLY:
				$period = MONTH_IN_SECONDS;
				break;
			default:
				return false;
		}

		return $last_sent + $period;
	}

	/**
	 * Get the settings for a notification.
	 *
	 * @param string $notification
	 *
	 * @return array|null
	 */
	private function get_notification_settings( $notification ) {
		$settings = ITSEC_Modules::get_setting( 'notification-center', 'notifications' );

		return isset( $settings[ $notification ] ) ? $settings[ $notification ] : null;
	}

	/**
	 * Get the cached value that all notifications have last been sent.
	 *
	 * @return int[]
	 */
	private function get_all_last_sent() {

		$last_sent = ITSEC_Modules::get_setting( 'notification-center', 'last_sent' );

		if ( ! is_array( $last_sent ) || empty( $last_sent ) ) {
			return $this->fill_last_sent();
		}

		return $this->fill_last_sent( $last_sent );
	}

	/**
	 * Get the time scheduled notifications were last sent directly from the database.
	 *
	 * @return int[]
	 */
	private function get_all_last_sent_uncached() {

		$storage = $this->get_uncached_options();

		if ( isset( $storage['last_sent'] ) ) {
			$last_sent = $storage['last_sent'];

			if ( count( $last_sent ) === count( $this->get_notifications() ) ) {
				return $last_sent;
			}

			return $this->fill_last_sent( $last_sent );
		}

		return $this->fill_last_sent();
	}

	/**
	 * Fill the last sent array with the time the plugin was activated for notifications that haven't been sent yet.
	 *
	 * @param array $last_sent
	 *
	 * @return array
	 */
	private function fill_last_sent( $last_sent = array() ) {
		$activated = ITSEC_Modules::get_setting( 'global', 'activation_timestamp' );

		if ( $last_sent ) {
			return array_merge( array_fill_keys( array_keys( $this->get_notifications() ), $activated ), $last_sent );
		}

		return array_fill_keys( array_keys( $this->get_notifications() ), $activated );
	}

	/**
	 * Get the cached value that all notification should be resent at.
	 *
	 * @return int[]
	 */
	private function get_all_resend_at() {

		$resend_at = ITSEC_Modules::get_setting( 'notification-center', 'resend_at' );

		if ( ! is_array( $resend_at ) || empty( $resend_at ) ) {
			$resend_at = array();
		}

		return array_merge( array_fill_keys( array_keys( $this->get_notifications() ), 0 ), $resend_at );
	}

	/**
	 * Get the time scheduled notifications are scheduled to be resent at.
	 *
	 * @return int[]
	 */
	private function get_all_resend_at_uncached() {

		$storage = $this->get_uncached_options();

		if ( isset( $storage['resend_at'] ) ) {
			$resend_at = $storage['resend_at'];

			if ( count( $resend_at ) === count( $this->get_notifications() ) ) {
				return $resend_at;
			}

			return array_merge( array_fill_keys( array_keys( $this->get_notifications() ), 0 ), $resend_at );
		}

		return array_fill_keys( array_keys( $this->get_notifications() ), 0 );
	}

	/**
	 * Get the uncached options storage.
	 *
	 * @return array
	 */
	private function get_uncached_options() {
		/** @var $wpdb \wpdb */
		global $wpdb;

		$option  = 'itsec-storage';
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

		if ( ! isset( $storage['notification-center'] ) ) {
			return array();
		}

		return $storage['notification-center'];
	}

	/**
	 * Get labels for the different schedule options.
	 *
	 * @return array
	 */
	public static function get_schedule_labels() {
		return array(
			self::S_DAILY   => esc_html__( 'Daily', 'better-wp-security' ),
			self::S_WEEKLY  => esc_html__( 'Weekly', 'better-wp-security' ),
			self::S_MONTHLY => esc_html__( 'Monthly', 'better-wp-security' ),
		);
	}

	/**
	 * Get the order of schedules from smallest to largest.
	 *
	 * @return array
	 */
	public static function get_schedule_order() {
		return array( self::S_DAILY, self::S_WEEKLY, self::S_MONTHLY );
	}

	/**
	 * Flip a 2-dimensional array.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	private static function flip_2d_array( $array ) {
		$out = array();

		foreach ( $array as $row => $columns ) {
			foreach ( $columns as $new_row => $new_column ) {
				$out[ $new_row ][ $row ] = $new_column;
			}
		}

		return $out;
	}
}