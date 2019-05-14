<?php

class ITSEC_Grading_System_Setup {
	public function __construct() {
		add_action( 'itsec_modules_do_plugin_activation', array( $this, 'execute_activate' ) );
		add_action( 'itsec_modules_do_plugin_deactivation', array( $this, 'execute_deactivate' ) );
		add_action( 'itsec_modules_do_plugin_uninstall', array( $this, 'execute_uninstall' ) );
		add_action( 'itsec_modules_do_plugin_upgrade', array( $this, 'execute_upgrade' ), 10, 2 );
	}

	/**
	 * Execute module activation.
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function execute_activate() {

	}

	/**
	 * Execute module deactivation
	 *
	 * @return void
	 */
	public function execute_deactivate() {

	}

	/**
	 * Execute module uninstall
	 *
	 * @return void
	 */
	public function execute_uninstall() {

	}

	/**
	 * Execute module upgrade
	 *
	 * @param int $itsec_old_version
	 */
	public function execute_upgrade( $itsec_old_version ) {

		if ( $itsec_old_version < 4102 ) {
			add_action( 'itsec_notification_center_continue_upgrade', array( $this, 'maybe_disable_grade_change' ), 100 );
		}

		if ( $itsec_old_version < 4106 ) {
			$time = get_site_option( 'itsec_grade_report_last_sent' );

			if ( $time ) {
				$last_sent                        = ITSEC_Modules::get_setting( 'notification-center', 'last_sent', array() );
				$last_sent['grade-report-change'] = $time;

				ITSEC_Modules::set_setting( 'notification-center', 'last_sent', $last_sent );
			}

			delete_site_option( 'itsec_grade_report_last_sent' );
		}
	}

	public function maybe_disable_grade_change() {
		if ( count( ITSEC_Core::get_notification_center()->get_recipients( 'grade-report-change' ) ) > 1 ) {
			$notifications = ITSEC_Modules::get_setting( 'notification-center', 'notifications' );

			if ( ! empty( $notifications['grade-report-change']['enabled'] ) ) {
				$notifications['grade-report-change']['enabled'] = false;
				ITSEC_Modules::set_setting( 'notification-center', 'notifications', $notifications );
			}
		}
	}
}

new ITSEC_Grading_System_Setup();