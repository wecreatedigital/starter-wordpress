<?php

final class ITSEC_File_Change_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_file_change_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ), 10, 3 );
		add_filter( 'itsec_logs_prepare_file_change_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry, $code, $code_data ) {
		$entry['module_display'] = esc_html__( 'File Change', 'better-wp-security' );

		if ( 'scan' === $code && 'process-start' === $entry['type'] ) {
			$entry['description'] = esc_html__( 'Scan Performance', 'better-wp-security' );
		} else if ( 'no-changes-found' === $code ) {
			$entry['description'] = esc_html__( 'No Changes Found', 'better-wp-security' );
		} else if ( 'changes-found' === $code ) {
			if ( isset( $code_data[0] ) ) {
				$entry['description'] = sprintf( esc_html__( '%1$d Added, %2$d Removed, %3$d Changed', 'better-wp-security' ), $code_data[0], $code_data[1], $code_data[2] );
			} else {
				$entry['description'] = esc_html__( 'Changes Found', 'better-wp-security' );
			}
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$entry = $this->filter_entry_for_list_display( $entry, $code, $code_data );

		$details['module']['content'] = $entry['module_display'];
		$details['description']['content'] = $entry['description'];

		if ( 'process-start' !== $entry['type'] ) {
			$details['memory'] = array(
				'header'  => esc_html__( 'Memory Used', 'better-wp-security' ),
				'content' => sprintf( esc_html_x( '%s MB', 'Megabytes of memory used', 'better-wp-security' ), $entry['data']['memory'] ),
			);

			$types = array(
				'added'   => esc_html__( 'Added', 'better-wp-security' ),
				'removed' => esc_html__( 'Removed', 'better-wp-security' ),
				'changed' => esc_html__( 'Changed', 'better-wp-security' ),
			);

			foreach ( $types as $type => $header ) {
				$details[$type] = array(
					'header'  => $header,
					'content' => '<pre>' . implode( "\n", array_keys( $entry['data'][$type] ) ) . '</pre>',
				);
			}
		}

		return $details;
	}
}
new ITSEC_File_Change_Logs();
