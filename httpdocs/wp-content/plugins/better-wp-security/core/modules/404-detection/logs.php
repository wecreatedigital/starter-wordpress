<?php

final class ITSEC_Four_Oh_Four_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_four_oh_four_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ) );
		add_filter( 'itsec_logs_prepare_four_oh_four_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry ) {
		$entry['module_display'] = esc_html__( '404 Detection', 'better-wp-security' );

		if ( 'found_404' === $entry['code'] ) {
			$entry['description'] = $entry['url'];
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$entry = $this->filter_entry_for_list_display( $entry, $code, $code_data );

		$details['module']['content'] = $entry['module_display'];
		$details['description']['content'] = $entry['description'];

		return $details;
	}
}
new ITSEC_Four_Oh_Four_Logs();
