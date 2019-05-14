<?php

final class ITSEC_Grading_System_Active {
	private static $instance = false;


	private function __construct() {
		$this->add_hooks();
	}

	public static function init() {
		if ( self::$instance ) {
			return;
		}

		self::$instance = new self();
	}

	private function add_hooks() {
		add_action( 'init', array( $this, 'redirect_from_grading' ) );
		add_action( 'wp_ajax_itsec_grade_report_page', array( $this, 'handle_ajax_request' ) );
		add_filter( 'itsec-admin-page-file-path-grade-report', array( $this, 'get_admin_page_file' ) );
		add_filter( 'itsec-admin-page-refs', array( $this, 'filter_admin_page_refs' ), 10, 3 );
		add_filter( 'itsec_notifications', array( $this, 'register_notification' ) );
		add_filter( 'itsec_grade-report-change_notification_strings', array( $this, 'notification_strings' ) );
		add_action( 'itsec_scheduler_register_events', array( $this, 'register_events' ) );
		add_action( 'itsec_scheduled_check-grade-report', array( $this, 'check_grade_report' ) );
		add_action( 'itsec_grade_report_changed', array( $this, 'grade_report_changed' ), 10, 2 );
		add_action( 'itsec_scheduled_send-grade-report', array( $this, 'maybe_send_grade_report' ) );
		add_filter( 'itsec_mail_digest', array( $this, 'customize_digest' ), 10, 3 );
		add_filter( 'itsec_security_digest_include_security_check', '__return_false' );
	}

	public function redirect_from_grading() {
		if ( is_admin() && isset( $_GET['page'] ) && 'itsec-grade-report' === $_GET['page'] ) {
			if ( ITSEC_Core::current_user_can_manage() && in_array( get_current_user_id(), ITSEC_Modules::get_setting( 'grade-report', 'disabled_users' ), false ) ) {
				wp_redirect( ITSEC_Core::get_settings_page_url() );
				die;
			}
		}
	}

	public function get_admin_page_file( $file ) {
		return dirname( __FILE__ ) . '/admin-page/page.php';
	}

	public function filter_admin_page_refs( $page_refs, $capability, $callback ) {

		$users = ITSEC_Modules::get_setting( 'grade-report', 'disabled_users' );

		if ( ! in_array( get_current_user_id(), $users, false ) ) {
			$page_refs[] = add_submenu_page( 'itsec', '', __( 'Grade Report', 'it-l10n-ithemes-security-pro' ), $capability, 'itsec-grade-report', $callback );
		}

		return $page_refs;
	}

	public function handle_ajax_request() {
		do_action( 'wp_ajax_itsec_settings_page' );
	}

	/**
	 * Register the "Grade Report Change" notification.
	 *
	 * @param array $notifications
	 *
	 * @return array
	 */
	public function register_notification( $notifications ) {

		$notifications['grade-report-change'] = array(
			'subject_editable' => true,
			'recipient'        => ITSEC_Notification_Center::R_USER_LIST,
			'schedule'         => array( 'min' => ITSEC_Notification_Center::S_DAILY, 'max' => ITSEC_Notification_Center::S_WEEKLY, 'setting_only' => true ),
			'optional'         => true,
		);

		return $notifications;
	}

	/**
	 * Get the strings for the "Grade Report Change" notification.
	 *
	 * @return array
	 */
	public function notification_strings() {
		return array(
			'label'       => esc_html__( 'Grade Report Change', 'it-l10n-ithemes-security-pro' ),
			'description' => esc_html__( 'Receive a notification whenever your Security Grade Report changes.', 'it-l10n-ithemes-security-pro' ),
			'subject'     => esc_html__( 'Your Security Grade has Changed', 'it-l10n-ithemes-security-pro' ),
		);
	}

	/**
	 * Register the event to detect when the grade report has changed.
	 *
	 * @param ITSEC_Scheduler $scheduler
	 */
	public function register_events( $scheduler ) {
		$scheduler->schedule( ITSEC_Scheduler::S_TWICE_HOURLY, 'check-grade-report' );
	}

	/**
	 * Check whether a grade report has changed since the last check.
	 */
	public function check_grade_report() {
		require_once( dirname( __FILE__ ) . '/report.php' );

		$previous = get_site_option( 'itsec_last_grade_report', array() );
		$current  = ITSEC_Grading_System::get_report();

		if ( ! $previous ) {
			update_site_option( 'itsec_last_grade_report', $current );

			return;
		}

		if ( $previous['hash'] !== $current['hash'] ) {
			/**
			 * Fires when the grade report has changed.
			 *
			 * @param array $current
			 * @param array $previous
			 */
			do_action( 'itsec_grade_report_changed', $current, $previous );

			update_site_option( 'itsec_last_grade_report', $current );
		}
	}

	/**
	 * When the grade report changes, schedule an event to send a notification.
	 *
	 * @param array $report
	 * @param array $previous
	 */
	public function grade_report_changed( $report, $previous ) {

		if ( ! ITSEC_Core::get_notification_center()->is_notification_enabled( 'grade-report-change' ) ) {
			return;
		}

		if ( ! ITSEC_Core::get_scheduler()->is_single_scheduled( 'send-grade-report', null ) ) {
			$at = ITSEC_Core::get_current_time_gmt() + HOUR_IN_SECONDS;
			ITSEC_Core::get_scheduler()->schedule_once( $at, 'send-grade-report', compact( 'report', 'previous', 'at' ) );
		}
	}

	/**
	 * Send the grade report email if the report hasn't changed.
	 *
	 * @param ITSEC_Job $job
	 */
	public function maybe_send_grade_report( $job ) {

		switch ( ITSEC_Core::get_notification_center()->get_schedule( 'grade-report-change' ) ) {
			case ITSEC_Notification_Center::S_WEEKLY:
				$delay = WEEK_IN_SECONDS;
				break;
			case ITSEC_Notification_Center::S_DAILY:
			default:
				$delay = DAY_IN_SECONDS;
		}

		$data = $job->get_data();

		require_once( dirname( __FILE__ ) . '/report.php' );
		$report_now = ITSEC_Grading_System::get_report();

		if ( $report_now['hash'] === $data['previous']['hash'] ) {
			return;
		}

		// If we've been trying to send this report for 24 hours, then just send it.
		if ( $data['at'] + $delay < ITSEC_Core::get_current_time_gmt() ) {
			$this->send_grade_report( $report_now, $data['previous'] );

			return;
		}

		// If the report is different from when we were trying to send it, then assume
		// that the report is being actively effected and wait until it calms down.
		if ( $report_now['hash'] !== $data['report']['hash'] ) {
			$job->reschedule_in( HOUR_IN_SECONDS, array( 'report' => $report_now ) );

			return;
		}

		$last_sent  = ITSEC_Core::get_notification_center()->get_last_sent( 'grade-report-change' );
		$not_before = $last_sent + $delay;

		// If the not before date is in the future, then schedule the change event for that time.
		if ( $not_before > ITSEC_Core::get_current_time_gmt() ) {
			$job->reschedule_in( $not_before - ITSEC_Core::get_current_time_gmt() );

			return;
		}

		$this->send_grade_report( $report_now, $data['previous'] );
	}

	/**
	 * Send the Grade Report to the user.
	 *
	 * @param array $report
	 * @param array $previous
	 */
	private function send_grade_report( $report, $previous ) {

		$nc = ITSEC_Core::get_notification_center();

		if ( ! $nc->is_notification_enabled( 'grade-report-change' ) ) {
			return;
		}

		$mail = $nc->mail();

		$mail->add_header( __( 'Grade Report Update', 'it-l10n-ithemes-security-pro' ), __( 'Update: Your Security Grade has changed', 'it-l10n-ithemes-security-pro' ) );
		$mail->add_html( $this->get_grade_section_html( $mail, $report ), 'grade-summary' );

		$issues_table_header = array( esc_html__( 'Issue', 'it-l10n-ithemes-security-pro' ), esc_html__( 'Grade', 'it-l10n-ithemes-security-pro' ), esc_html__( 'Description', 'it-l10n-ithemes-security-pro' ) );

		$issues = $this->get_issues( $report, $previous );

		if ( $issues['new'] ) {
			$mail->add_large_text( esc_html__( 'New Issues That Need Your Attention', 'it-l10n-ithemes-security-pro' ) );

			$table = array();

			foreach ( $issues['new'] as $issue ) {
				$table[] = array( $issue['name'], $issue['grade'], $issue['details'] );
			}

			$mail->add_table( $issues_table_header, $table );
		}

		if ( $issues['existing'] ) {
			$mail->add_large_text( esc_html__( 'Existing Issues Impacting Your Grade', 'it-l10n-ithemes-security-pro' ) );

			$table = array();

			foreach ( $issues['existing'] as $issue ) {
				$table[] = array( $issue['name'], $issue['grade'], $issue['details'] );
			}

			$mail->add_table( $issues_table_header, $table );
		}

		if ( ! $issues['new'] && ! $issues['existing'] ) {
			return;
		}

		$mail->add_footer();

		$nc->send( 'grade-report-change', $mail );
		$nc->update_last_sent( 'grade-report-change' );
	}

	private function get_issues( $report, $previous ) {
		$new = $existing = array();

		foreach ( $report['sections'] as $i => $section ) {
			foreach ( $section['criteria'] as $id => $criterion ) {
				if ( ! $criterion['issue'] ) {
					continue;
				}

				if ( ! isset( $previous['sections'][ $i ]['criteria'][ $id ]['percent'] ) ) {
					$new[] = $criterion;
				} elseif ( $criterion['percent'] !== $previous['sections'][ $i ]['criteria'][ $id ]['percent'] ) {
					$new[] = $criterion;
				} else {
					$existing[] = $criterion;
				}
			}
		}

		return compact( 'new', 'existing' );
	}

	/**
	 * Customize the Daily Digest email to include the current grade.
	 *
	 * @param array      $content
	 * @param ITSEC_Mail $mail
	 * @param string     $recipient
	 *
	 * @return array
	 */
	public function customize_digest( $content, $mail, $recipient ) {

		if ( ! isset( $content['intro'] ) ) {
			return $content;
		}

		if ( $recipient && ( $user = get_user_by( 'email', $recipient ) ) && in_array( $user->ID, ITSEC_Modules::get_setting( 'grade-report', 'disabled_users' ), false ) ) {
			return $content;
		}

		require_once( dirname( __FILE__ ) . '/report.php' );
		$report = ITSEC_Grading_System::get_report();

		$summary = $this->get_grade_section_html( $mail, $report );
		$content = ITSEC_Lib::array_insert_after( 'intro', $content, 'grade-summary', $summary );

		return $content;
	}

	/**
	 * Get the grade overview HTML.
	 *
	 * @param ITSEC_Mail $mail
	 * @param array      $report
	 *
	 * @return string
	 */
	private function get_grade_section_html( $mail, $report ) {
		$grade = $report['grade']['real'];

		switch ( $grade[0] ) {
			case 'A':
				$color = '#00C778';
				break;
			case 'B':
				$color = '#00A0D2';
				break;
			case 'C':
				$color = '#FA9408';
				break;
			case 'D':
				$color = '#E7635D';
				break;
			case 'F':
				$color = '#98030E';
				break;
			default:
				$color = '';
				break;
		}

		return $this->get_grade_summary_html( $grade, $color, $this->get_summary( $report ) ) . $mail->get_divider();
	}

	private function get_grade_summary_html( $grade, $color, $summary ) {

		$template = file_get_contents( dirname( __FILE__ ) . '/mail-templates/grade-overview.html' );

		$tags = array(
			'grade'       => $grade,
			'grade_color' => $color,
			'summary'     => $summary,
			'title'       => esc_html__( 'Your Current WordPress Security Grade', 'it-l10n-ithemes-security-pro' ),
			'button_text' => esc_html__( 'See Your Grade Report →', 'it-l10n-ithemes-security-pro' ),
			'button_link' => esc_url( network_admin_url( 'admin.php?page=itsec-grade-report' ) ),
		);

		return ITSEC_Lib::replace_tags( $template, $tags );
	}

	private function get_summary( $report ) {

		if ( 0 === $report['issues'] ) {
			return esc_html__( 'Great work! Based on your current security settings and software, your website has gotten the top WordPress security grade possible.', 'it-l10n-ithemes-security-pro' );
		}

		if ( 0 === $report['fixable_issues'] ) {
			return sprintf(
				esc_html__( 'Your WordPress Security Grade is based on your current security settings and software. %1$sView details%2$s about your WordPress security grade.', 'it-l10n-ithemes-security-pro' ),
				'<a href="' . esc_attr( ITSEC_Mail::filter_admin_page_url( network_admin_url( 'admin.php?page=itsec-grade-report' ) ) ) . '">',
				'</a>'
			);
		}

		return sprintf(
			esc_html__( 'Your WordPress Security Grade is based on your current security settings and software. Resolve %1$sthese issues now%2$s to raise your WordPress security grade.', 'it-l10n-ithemes-security-pro' ),
			'<a href="' . esc_attr( ITSEC_Mail::filter_admin_page_url( network_admin_url( 'admin.php?page=itsec-grade-report' ) ) ) . '">',
			'</a>'
		);
	}
}

ITSEC_Grading_System_Active::init();
