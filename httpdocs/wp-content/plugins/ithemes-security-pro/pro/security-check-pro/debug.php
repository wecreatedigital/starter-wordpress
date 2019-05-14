<?php

/**
 * Class ITSEC_Security_Check_Pro_Debug
 */
class ITSEC_Security_Check_Pro_Debug {

	public function __construct() {
		add_action( 'itsec_debug_page', array( $this, 'render' ) );
		add_action( 'itsec_debug_page_enqueue', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'itsec_debug_module_request_security-check-pro', array( $this, 'handle_ajax_request' ) );
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_script( 'itsec-security-check-pro-debug', plugins_url( 'js/debug.js', __FILE__ ), array( 'itsec-util' ), ITSEC_Core::get_plugin_build() );
	}

	public function handle_ajax_request( $data ) {

		ITSEC_Modules::load_module_file( 'feedback.php', 'security-check' );
		ITSEC_Modules::load_module_file( 'scanner.php', 'security-check' );

		require_once( dirname( __FILE__ ) . '/utility.php' );

		$feedback = new ITSEC_Security_Check_Feedback();
		$response = ITSEC_Security_Check_Pro_Utility::run_scan( $feedback, ITSEC_Security_Check_Scanner::get_supported_modules() );

		$raw_data = $feedback->get_raw_data();

		if ( is_wp_error( $response ) ) {
			ITSEC_Response::add_error( $response );
		} else {
			ITSEC_Response::add_message( esc_html__( 'Scan Complete', 'it-l10n-ithemes-security-pro' ) );
		}

		foreach ( $raw_data['sections'] as $id => $section ) {
			foreach ( $section['entries'] as $entry ) {
				if ( 'text' === $entry['type'] ) {
					ITSEC_Response::add_info( $entry['value'] );
				}
			}
		}
	}

	/**
	 * Render our data to the Debug Page.
	 */
	public function render() {
		?>

		<div>
			<h2><?php esc_html_e( 'Security Check Pro', 'it-l10n-ithemes-security-pro' ); ?></h2>
			<button class="button" id="itsec-debug-run-security-check-pro"><?php esc_html_e( 'Run', 'it-l10n-ithemes-security-pro' ); ?></button>
		</div>

		<?php
	}
}

new ITSEC_Security_Check_Pro_Debug();