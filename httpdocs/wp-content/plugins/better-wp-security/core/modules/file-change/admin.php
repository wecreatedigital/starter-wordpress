<?php

final class ITSEC_File_Change_Admin {

	const AJAX = 'itsec_file_change_dismiss_warning';

	private $script_version = 2;
	private $dismiss_nonce;


	public function __construct() {

		if ( ITSEC_Modules::get_setting( 'file-change', 'show_warning' ) ) {
			add_action( 'init', array( $this, 'init' ) );
		}
	}

	public static function enqueue_scanner() {
		$logs_page_url = ITSEC_Core::get_logs_page_url( 'file_change' );

		ITSEC_Lib::enqueue_util();
		wp_enqueue_script( 'itsec-file-change-scanner', plugins_url( 'js/file-scanner.js', __FILE__ ), array( 'jquery', 'heartbeat', 'itsec-util' ), ITSEC_Core::get_plugin_build(), true );
		wp_localize_script( 'itsec-file-change-scanner', 'ITSECFileChangeScannerl10n', array(
			'button_text'          => __( 'Scan Files Now', 'better-wp-security' ),
			'scanning_button_text' => __( 'Scanning...', 'better-wp-security' ),
			'no_changes'           => __( 'No changes were detected.', 'better-wp-security' ),
			'found_changes'        => sprintf( __( 'Changes were detected. Please check the <a href="%s" target="_blank" rel="noopener noreferrer">logs</a> for details.', 'better-wp-security' ), esc_url( add_query_arg( 'id', '#REPLACE_ID#', $logs_page_url ) ) ),
			'unknown_error'        => __( 'An unknown error occured. Please try again later', 'better-wp-security' ),
			'already_running'      => sprintf( __( 'A scan is already in progress. Please check the <a href="%s" target="_blank" rel="noopener noreferrer">logs page</a> at a later time for the results of the scan.', 'better-wp-security' ), esc_url( $logs_page_url ) ),
		) );
	}

	public function init() {

		if ( ! ITSEC_Core::current_user_can_manage() ) {
			return;
		}

		add_action( 'wp_ajax_' . self::AJAX, array( $this, 'dismiss_file_change_warning_ajax' ) );

		if ( ! empty( $_GET['file_change_dismiss_warning'] ) ) {
			$this->dismiss_file_change_warning();
		} else {
			add_action( 'admin_print_scripts', array( $this, 'add_scripts' ) );
			$this->dismiss_nonce = wp_create_nonce( 'itsec-file-change-dismiss-warning' );

			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'show_file_change_warning' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'show_file_change_warning' ) );
			}
		}
	}

	public function add_scripts() {
		$vars = array(
			'ajax_action' => self::AJAX,
			'ajax_nonce'  => $this->dismiss_nonce
		);

		wp_enqueue_script( 'itsec-file-change-script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery', 'common' ), $this->script_version, true );
		wp_localize_script( 'itsec-file-change-script', 'itsec_file_change', $vars );
	}

	public function dismiss_file_change_warning_ajax() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'itsec-file-change-dismiss-warning' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Request expired. Please refresh and try again.', 'better-wp-security' ),
			) );
		}

		$status = ITSEC_Modules::set_setting( 'file-change', 'show_warning', false );

		if ( ! $status || empty( $status['saved'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to dismiss warning.', 'better-wp-security' ),
			) );
		}

		wp_send_json_success( array(
			'message' => __( 'Warning dismissed.', 'better-wp-security' ),
		) );
	}

	public function dismiss_file_change_warning() {
		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'itsec-file-change-dismiss-warning' ) ) {
			return;
		}

		ITSEC_Modules::set_setting( 'file-change', 'show_warning', false );
	}

	public function show_file_change_warning() {

		$args = array(
			'file_change_dismiss_warning' => '1',
			'nonce'                       => $this->dismiss_nonce,
		);

		if ( $log_id = ITSEC_Modules::get_setting( 'file-change', 'last_scan' ) ) {
			$args['id'] = $log_id;
		}

		$logs_url = add_query_arg( $args, ITSEC_Core::get_logs_page_url() );
		$message  = sprintf(
			esc_html__( 'iThemes Security noticed file changes in your WordPress site. Please %1$s review the logs %2$s to make sure your system has not been compromised.', 'better-wp-security' ),
			'<a href="' . esc_url( $logs_url ) . '">',
			'</a>'
		);
		?>
		<div id="itsec-file-change-warning-dialog" class="notice notice-error is-dismissible">
			<p><?php echo $message; ?></p>
		</div>
		<?php
	}
}

new ITSEC_File_Change_Admin();
