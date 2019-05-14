<?php

final class ITSEC_Away_Mode_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_away_mode_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ) );
	}

	public function filter_entry_for_list_display( $entry ) {
		$entry['module_display'] = esc_html__( 'Away Mode', 'it-l10n-ithemes-security-pro' );

		if ( 'away-mode-active' === $entry['code'] ) {
			$entry['description'] = esc_html__( 'Access Blocked', 'it-l10n-ithemes-security-pro' );
		}

		return $entry;
	}
}
new ITSEC_Away_Mode_Logs();
