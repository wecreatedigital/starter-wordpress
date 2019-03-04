<?php

class ITSEC_Notification_Center_Setup {

	private $old_version;

	public function __construct() {
		add_action( 'itsec_modules_do_plugin_uninstall', array( $this, 'execute_uninstall' ) );
		add_action( 'itsec_modules_do_plugin_upgrade', array( $this, 'execute_upgrade' ), -100 );
	}

	/**
	 * Execute module uninstall
	 *
	 * @return void
	 */
	public function execute_uninstall() {
		$scheduled = wp_next_scheduled( 'itsec-send-scheduled-notifications' );

		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, 'itsec-send-scheduled-notifications' );
		}
	}

	/**
	 * Execute module upgrade
	 *
	 * @param int $itsec_old_version
	 */
	public function execute_upgrade( $itsec_old_version ) {
		if ( $itsec_old_version < 4076 ) {

			ITSEC_Modules::load_module_file( 'active.php', 'notification-center' );

			$settings = ITSEC_Modules::get_settings( 'notification-center' );

			$global = ITSEC_Modules::get_settings( 'global' );

			$settings['admin_emails'] = $global['notification_email'];

			$settings['notifications']['digest']['enabled']    = $global['digest_email'];
			$settings['notifications']['backup']['email_list'] = $global['backup_email'];
			$settings['notifications']['lockout']['enabled']   = $global['email_notifications'] && ! $global['digest_email'];

			$settings['last_sent']['digest'] = $global['digest_last_sent'];

			if ( $global['digest_messages'] ) {
				$settings['data']['digest'] = array();

				foreach ( $global['digest_messages'] as $message ) {
					if ( 'file-change' === $message ) {
						$settings['data']['digest'][] = array( 'type' => 'file-change' );
					} else {
						$settings['data']['digest'][] = array( 'type' => 'general', 'message' => $message );
					}
				}
			}

			if ( $malware = ITSEC_Modules::get_settings( 'malware-scheduling' ) ) {
				$settings['notifications']['malware-scheduling']['enabled']   = ! empty( $malware['email_notifications'] );
				$settings['notifications']['malware-scheduling']['user_list'] = ! empty( $malware['email_contacts'] ) ? $malware['email_contacts'] : array( 'role:administrator' );
			}

			if ( $vm = ITSEC_Modules::get_settings( 'version-management' ) ) {
				$settings['notifications']['automatic-updates-debug']['enabled']   = ! empty( $vm['automatic_update_emails'] );
				$settings['notifications']['automatic-updates-debug']['user_list'] = ! empty( $vm['email_contacts'] ) ? $vm['email_contacts'] : array( 'role:administrator' );
				$settings['notifications']['old-site-scan']['user_list']           = ! empty( $vm['email_contacts'] ) ? $vm['email_contacts'] : array( 'role:administrator' );
			}

			if ( $file_change = ITSEC_Modules::get_settings( 'file-change' ) ) {
				$settings['notifications']['file-change']['enabled'] = ! empty( $file_change['email'] ) && ! $global['digest_email'];
			}

			ITSEC_Modules::set_settings( 'notification-center', $settings );

			$this->old_version = $itsec_old_version;
			add_action( 'itsec_initialized', array( $this, 'fire_continue_upgrade' ) );
		} elseif ( $itsec_old_version < 4077 ) { // Only run this if user is updating from 4076 -> 4077
			ITSEC_Modules::load_module_file( 'active.php', 'notification-center' );

			$settings = ITSEC_Modules::get_settings( 'notification-center' );
			$global   = ITSEC_Modules::get_settings( 'global' );

			$settings['notifications']['lockout']['enabled'] = $global['email_notifications'] && ! $global['digest_email'];

			if ( $file_change = ITSEC_Modules::get_settings( 'file-change' ) ) {
				$settings['notifications']['file-change']['enabled'] = ! empty( $file_change['email'] ) && ! $global['digest_email'];
			}

			foreach ( $settings['notifications'] as $slug => $notification ) {
				if ( empty( $notification['previous_emails'] ) ) {
					continue;
				}

				if ( ! isset( $notification['user_list'] ) || $notification['user_list'] === array( 'role:administrator' ) ) {
					$notification['user_list'] = array();

					$settings['notifications'][ $slug ] = $notification;
				}
			}

			ITSEC_Modules::set_settings( 'notification-center', $settings );
		} elseif ( $itsec_old_version < 4078 ) { // Only run if user updating from 4077 -> 4078
			ITSEC_Modules::load_module_file( 'active.php', 'notification-center' );

			$settings = ITSEC_Modules::get_settings( 'notification-center' );

			if ( ! isset( $settings['notifications']['file-change']['enabled'] ) || $settings['notifications']['file-change']['enabled'] ) {

				$global = ITSEC_Modules::get_settings( 'global' );

				if ( $file_change = ITSEC_Modules::get_settings( 'file-change' ) ) {
					$settings['notifications']['file-change']['enabled'] = ! empty( $file_change['email'] ) && ! $global['digest_email'];
				}

				ITSEC_Modules::set_settings( 'notification-center', $settings );
			}
		}

		if ( $itsec_old_version < 4099 ) {
			ITSEC_Modules::load_module_file( 'active.php', 'notification-center' );

			$settings = ITSEC_Modules::get_settings( 'notification-center' );
			unset( $settings['mail_errors'] );
			ITSEC_Modules::set_settings( 'notification-center', $settings );
		}

		if ( $itsec_old_version < 4101 ) {
			$this->old_version = $itsec_old_version;
			add_action( 'itsec_initialized', array( $this, 'fire_continue_upgrade' ) );
		}
	}

	public function fire_continue_upgrade() {
		ITSEC_Modules::load_module_file( 'settings.php', 'notification-center' );
		do_action( 'itsec_notification_center_continue_upgrade', $this->old_version );
	}
}

new ITSEC_Notification_Center_Setup();