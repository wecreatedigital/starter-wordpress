<?php

final class ITSEC_Recaptcha_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_recaptcha_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ) );
	}

	public function filter_entry_for_list_display( $entry ) {
		$entry['module_display'] = esc_html__( 'reCAPTCHA', 'it-l10n-ithemes-security-pro' );

		if ( 'failed-validation' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Failed Validation', 'it-l10n-ithemes-security-pro' );
		}

		return $entry;
	}
}
new ITSEC_Recaptcha_Logs();
