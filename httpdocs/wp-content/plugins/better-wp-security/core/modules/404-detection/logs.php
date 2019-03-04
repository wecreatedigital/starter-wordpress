<?php

final class ITSEC_Four_Oh_Four_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_four_oh_four_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ) );
		add_filter( 'itsec_logs_prepare_four_oh_four_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
		add_filter( 'itsec_logs_prepare_four_oh_four_filter_row_action_for_code', array( $this, 'code_row_action' ), 10, 2 );
	}

	public function filter_entry_for_list_display( $entry ) {
		$entry['module_display'] = esc_html__( '404 Detection', 'better-wp-security' );

		if ( 'found_404' === $entry['code'] ) {
			$entry['description'] = esc_html( $entry['url'] );
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$entry = $this->filter_entry_for_list_display( $entry, $code, $code_data );

		$details['module']['content'] = $entry['module_display'];
		$details['description']['content'] = $entry['description'];

		return $details;
	}

	public function code_row_action( $vars, $entry ) {
		return array( 'filters[10]' => "url|{$entry['url']}", 'filters[11]' => 'module|four_oh_four' );
	}
}
new ITSEC_Four_Oh_Four_Logs();
