<?php

/**
 * Class ITSEC_Global_Logs
 */
class ITSEC_Global_Logs {

	public function __construct() {
		add_filter( 'itsec_logs_prepare_core_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ), 10, 3 );
		add_filter( 'itsec_logs_prepare_core_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
		add_filter( 'itsec_logs_prepare_core_filter_row_action_for_code', array( $this, 'code_row_action' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry, $code, $data ) {
		$entry['module_display'] = esc_html__( 'Core', 'better-wp-security' );


		if ( $description = $this->get_description( $entry, $code, $data ) ) {
			$entry['description'] = $description;
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$details['module']['content'] = esc_html__( 'Core', 'better-wp-security' );

		if ( $description = $this->get_description( $entry, $code, $code_data ) ) {
			$details['description']['content'] = $description;
		}

		return $details;
	}

	public function code_row_action( $vars, $entry, $code, $data ) {

		return $vars;
	}

	private function get_description( $entry, $code, $data ) {
		switch ( $code ) {
			case 'itsec-config-file-update-empty':
				list( $type ) = $data;

				return sprintf( esc_html__( 'Empty file encountered when attempting to update %s config file.', 'better-wp-security' ), '<code>' . esc_html( $type ) . '</code>' );
		}

		return null;
	}
}

new ITSEC_Global_Logs();
