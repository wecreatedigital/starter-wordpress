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
		} elseif ( 'skipping-recovery' === $code ) {
			$code_specific = isset( $code_data[0] ) ? $code_data[0] : '';

			if ( 'no-lock' === $code_specific ) {
				$entry['description'] = esc_html__( 'Skipping Recovery: No Lock', 'better-wp-security' );
			} elseif ( 'empty-storage' === $code_specific ) {
				$entry['description'] = esc_html__( 'Skipping Recovery: No Lock', 'better-wp-security' );
			} else {
				$entry['description'] = esc_html__( 'Skipping Recovery', 'better-wp-security' );
			}
		} elseif ( 'attempting-recovery' === $code ) {
			if ( array( 'no-job-step' ) === $code_data ) {
				$entry['description'] = esc_html__( 'Attempting Recovery: Invalid Job', 'better-wp-security' );
			} else {
				$entry['description'] = esc_html__( 'Attempting Recovery', 'better-wp-security' );
			}
		} elseif ( 'recovery-failed-no-step' === $code ) {
			$entry['description'] = esc_html__( 'Recovery Failed: No Step', 'better-wp-security' );
		} elseif ( 'recovery-failed-too-many-retries' === $code ) {
			$entry['description'] = esc_html__( 'Recovery Failed: Retry Limit', 'better-wp-security' );
		} elseif ( 'recovery-failed-first-loop' === $code ) {
			$entry['description'] = esc_html__( 'Recovery Failed: First Loop', 'better-wp-security' );
		} elseif ( 'recovery-scheduled' === $code ) {
			$entry['description'] = esc_html__( 'Recovery Scheduled', 'better-wp-security' );
		} elseif ( 'file-scan-aborted' === $code ) {
			if  ( ! empty( $code_data[0] ) ) {
				if ( $user = get_userdata( $code_data[0] ) ) {
					$by = $user->display_name;
				} else {
					$by = "#{$code_data[0]}";
				}

				$entry['description'] = sprintf( esc_html__( 'Scan Cancelled by %s', 'better-wp-security' ), $by );
			} else {
				$entry['description'] = esc_html__( 'Scan Failed', 'better-wp-security' );
			}
		} elseif ( 'rescheduling' === $code ) {
			if ( isset( $code_data[0] ) && 'no-lock' === $code_data[0] ) {
				$entry['description'] = esc_html__( 'Rescheduling: No Lock', 'better-wp-security' );
			} else {
				$entry['description'] = esc_html__( 'Rescheduling', 'better-wp-security' );
			}
		}

		$entry['remote_ip'] = '';

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$entry = $this->filter_entry_for_list_display( $entry, $code, $code_data );

		$details['module']['content'] = $entry['module_display'];
		$details['description']['content'] = $entry['description'];

		if ( 'changes-found' === $code || 'no-changes-found' === $code ) {
			$details['memory'] = array(
				'header'  => esc_html__( 'Memory Used', 'better-wp-security' ),
				'content' => sprintf( esc_html_x( '%s MB', 'Megabytes of memory used', 'better-wp-security' ), $entry['data']['memory'] ),
			);

			if ( ! empty( $entry['data']['memory_peak'] ) ) {
				$details['memory_total'] = array(
					'header'  => esc_html__( 'Total Memory', 'better-wp-security' ),
					'content' => sprintf( esc_html_x( '%s MB', 'Megabytes of memory used', 'better-wp-security' ), $entry['data']['memory_peak'] ),
				);
			}

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

		unset( $details['host'] );

		return $details;
	}
}
new ITSEC_File_Change_Logs();
