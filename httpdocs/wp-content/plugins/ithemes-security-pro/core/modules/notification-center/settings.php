<?php

class ITSEC_Notification_Center_Settings extends ITSEC_Settings {

	/**
	 * ITSEC_Notification_Center_Settings constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'itsec_notification_center_continue_upgrade', array( $this, 'continue_upgrade' ) );
	}


	public function get_id() {
		return 'notification-center';
	}

	public function get_defaults() {
		return array(
			'last_sent'          => array(),
			'resend_at'          => array(),
			'data'               => array(),
			'last_mail_error'    => '',
			'notifications'      => array(),
			'admin_emails'       => array(),
			'from_email'         => '',
			'default_recipients' => array(
				'user_list' => array( 'role:administrator' )
			),
		);
	}

	public function load() {
		if ( ! class_exists( 'ITSEC_Notification_Center' ) ) {
			ITSEC_Modules::load_module_file( 'active.php', 'notification-center' );
		}

		$this->settings = ITSEC_Storage::get( $this->get_id() );
		$defaults       = $this->get_defaults();

		if ( ! is_array( $this->settings ) ) {
			$this->settings = array();
		}

		$this->settings = array_merge( $defaults, $this->settings );

		$notifications = ITSEC_Core::get_notification_center()->get_notifications();

		foreach ( $notifications as $slug => $notification ) {
			if ( ! isset( $this->settings['notifications'][ $slug ] ) ) {
				$value = $this->get_notification_defaults( $notification, true );
			} else {
				$value = wp_parse_args( $this->settings['notifications'][ $slug ], $this->get_notification_defaults( $notification ) );
			}

			$this->settings['notifications'][ $slug ] = $value;
		}

		unset( $this->settings['mail_errors'] );
	}

	protected function handle_settings_changes( $old_settings ) {

		$nc = ITSEC_Core::get_notification_center();

		foreach ( $this->settings['notifications'] as $slug => $notification ) {
			if ( ! isset( $old_settings['notifications'][ $slug ] ) ) {
				continue;
			}

			$config = $nc->get_notification( $slug );

			if ( empty( $config['optional'] ) ) {
				continue;
			}

			if ( $notification['enabled'] && ! $old_settings['notifications'][ $slug ]['enabled'] ) {
				do_action( "itsec_notification_center_{$slug}_notification_enabled", $slug );
				do_action( 'itsec_notification_center_notification_enabled', $slug );
			} elseif ( ! $notification['enabled'] && $old_settings['notifications'][ $slug ]['enabled'] ) {
				do_action( "itsec_notification_center_{$slug}_notification_disabled", $slug );
				do_action( 'itsec_notification_center_notification_disabled', $slug );
			}
		}
	}

	public function refresh_notification_settings( $save = true ) {

		$nc = ITSEC_Core::get_notification_center();

		foreach ( $this->settings['notifications'] as $slug => $notification ) {
			$this->settings['notifications'][ $slug ] = array_merge( $this->get_notification_defaults( $nc->get_notification( $slug ), true ), $notification );
		}

		if ( $save ) {
			$this->set_all( $this->settings );
		}
	}

	public function continue_upgrade( $old_version ) {

		$nc = ITSEC_Core::get_notification_center();

		if ( $old_version < 4076 ) {
			$nc->clear_notifications_cache();
			$this->refresh_notification_settings( false );

			$admin_users  = array();
			$admin_emails = array();

			foreach ( $this->settings['admin_emails'] as $admin_email ) {
				$user = get_user_by( 'email', $admin_email );

				if ( $user && $user->has_cap( 'manage_options' ) ) {
					$admin_users[] = $user->ID;
				} else {
					$admin_emails[] = $admin_email;
				}
			}

			foreach ( $nc->get_notifications() as $slug => $notification ) {

				if ( ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE === $notification['recipient'] ) {
					if ( $admin_users ) {
						$this->settings['notifications'][ $slug ]['user_list'] = $admin_users;
					} elseif ( $admin_emails ) {
						$this->settings['notifications'][ $slug ]['user_list'] = array();
					}

					$this->settings['notifications'][ $slug ]['previous_emails'] = $admin_emails;
				}
			}

			$this->set_all( $this->settings );
		}

		if ( $old_version < 4101 ) {

			$settings   = $this->get_all();
			$user_lists = array();

			foreach ( $settings['notifications'] as $slug => $setting ) {
				if ( ! $config = $nc->get_notification( $slug ) ) {
					$settings['notifications'][ $slug ]['recipient_type'] = 'custom';

					continue;
				}

				if ( ITSEC_Notification_Center::R_USER_LIST !== $config['recipient'] && ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE !== $config['recipient'] ) {
					continue;
				}

				$user_lists[ $slug ] = $setting['user_list'];
			}

			$unique_lists = ITSEC_Lib::non_scalar_array_unique( $user_lists );

			if ( 1 === count( $unique_lists ) ) {
				$settings['default_recipients'] = array( 'user_list' => reset( $unique_lists ) );

				foreach ( $user_lists as $notification_slug => $_ ) {
					$settings['notifications'][ $notification_slug ]['recipient_type'] = 'default';
				}
			} else {
				foreach ( $user_lists as $notification_slug => $_ ) {
					$settings['notifications'][ $notification_slug ]['recipient_type'] = 'custom';
				}
			}

			$this->set_all( $settings );
		}
	}

	/**
	 * Get the defaults for a notification.
	 *
	 * @param array $notification
	 * @param bool  $include_strings Whether to include translated strings. Used for default subject and message.
	 *                               Defaults to false for performance reasons.
	 *
	 * @return array
	 */
	private function get_notification_defaults( $notification, $include_strings = false ) {

		$strings  = $include_strings ? ITSEC_Core::get_notification_center()->get_notification_strings( $notification['slug'] ) : array();
		$defaults = array();

		if ( is_array( $notification['schedule'] ) ) {
			$defaults['schedule'] = $notification['schedule']['default'];
		}

		if ( ! empty( $strings['subject'] ) ) {
			$defaults['subject'] = $strings['subject'];
		}

		if ( ! empty( $strings['message'] ) ) {
			$defaults['message'] = $strings['message'];
		}

		if ( ! empty( $notification['optional'] ) ) {
			$defaults['enabled'] = $notification['optional']['default'];
		}

		if ( ITSEC_Notification_Center::R_USER_LIST === $notification['recipient'] ) {
			$defaults['user_list']      = array( 'role:administrator' );
			$defaults['recipient_type'] = 'default';
		}

		if ( ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE === $notification['recipient'] ) {
			$defaults['user_list']      = array( 'role:administrator' );
			$defaults['recipient_type'] = 'default';
		}

		if ( ITSEC_Notification_Center::R_EMAIL_LIST === $notification['recipient'] ) {
			$defaults['email_list'] = array( get_option( 'admin_email' ) );
		}

		return $defaults;
	}
}

ITSEC_Modules::register_settings( new ITSEC_Notification_Center_Settings() );