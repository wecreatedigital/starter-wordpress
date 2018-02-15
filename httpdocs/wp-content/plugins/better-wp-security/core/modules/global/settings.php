<?php

final class ITSEC_Global_Settings_New extends ITSEC_Settings {
	public function get_id() {
		return 'global';
	}

	public function get_defaults() {
		return array(
			'lockout_message'           => __( 'error', 'better-wp-security' ),
			'user_lockout_message'      => __( 'You have been locked out due to too many invalid login attempts.', 'better-wp-security' ),
			'community_lockout_message' => __( 'Your IP address has been flagged as a threat by the iThemes Security network.', 'better-wp-security' ),
			'blacklist'                 => true,
			'blacklist_count'           => 3,
			'blacklist_period'          => 7,
			'lockout_period'            => 15,
			'lockout_white_list'        => array(),
			'log_rotation'              => 60,
			'log_type'                  => 'database',
			'log_location'              => ITSEC_Core::get_storage_dir( 'logs' ),
			'log_info'                  => '',
			'allow_tracking'            => false,
			'write_files'               => true,
			'nginx_file'                => ABSPATH . 'nginx.conf',
			'infinitewp_compatibility'  => false,
			'did_upgrade'               => false,
			'lock_file'                 => false,
			'proxy_override'            => false,
			'hide_admin_bar'            => false,
			'show_error_codes'          => false,
			'show_new_dashboard_notice' => true,
			'show_security_check'       => true,
			'build'                     => 0,
			'activation_timestamp'      => 0,
			'cron_status'               => - 1,
			'use_cron'                  => true,
			'cron_test_time'            => 0,
		);
	}

	protected function handle_settings_changes( $old_settings ) {
		if ( $this->settings['write_files'] && ! $old_settings['write_files'] ) {
			ITSEC_Response::regenerate_server_config();
			ITSEC_Response::regenerate_wp_config();
		}

		if ( $this->settings['use_cron'] !== $old_settings['use_cron'] ) {
			$this->handle_cron_change( $this->settings['use_cron'] );
		}
	}

	private function handle_cron_change( $new_use_cron ) {
		$class = $new_use_cron ? 'ITSEC_Scheduler_Cron' : 'ITSEC_Scheduler_Page_Load';
		$this->handle_scheduler_change( $class );
	}

	private function handle_scheduler_change( $new_class ) {
		$choices = array(
			'ITSEC_Scheduler_Cron'      => ITSEC_Core::get_core_dir() . 'lib/class-itsec-scheduler-cron.php',
			'ITSEC_Scheduler_Page_Load' => ITSEC_Core::get_core_dir() . 'lib/class-itsec-scheduler-page-load.php',
		);

		require_once( $choices[ $new_class ] );

		/** @var ITSEC_Scheduler $new */
		$new     = new $new_class();
		$current = ITSEC_Core::get_scheduler();

		$new->uninstall();

		foreach ( $current->get_recurring_events() as $event ) {
			$new->schedule( $event['schedule'], $event['id'], $event['data'], array(
				'fire_at' => $event['fire_at'],
			) );
		}

		foreach ( $current->get_single_events() as $event ) {
			$new->schedule_once( $event['fire_at'], $event['id'], $event['data'] );
		}

		$new->run();
		ITSEC_Core::set_scheduler( $new );
		$current->uninstall();
	}
}

ITSEC_Modules::register_settings( new ITSEC_Global_Settings_New() );
