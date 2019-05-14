<?php

final class ITSEC_File_Change_Logs {
	public function __construct() {
		add_filter( 'itsec_logs_prepare_file_change_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ), 10, 3 );
		add_filter( 'itsec_logs_prepare_file_change_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry, $code, $code_data ) {
		$entry['module_display'] = esc_html__( 'File Change', 'it-l10n-ithemes-security-pro' );

		if ( 'scan' === $code && 'process-start' === $entry['type'] ) {
			$entry['description'] = esc_html__( 'Scan Performance', 'it-l10n-ithemes-security-pro' );
		} else if ( 'no-changes-found' === $code ) {
			$entry['description'] = esc_html__( 'No Changes Found', 'it-l10n-ithemes-security-pro' );
		} else if ( 'changes-found' === $code ) {
			if ( isset( $code_data[0] ) ) {
				$entry['description'] = sprintf( esc_html__( '%1$d Added, %2$d Removed, %3$d Changed', 'it-l10n-ithemes-security-pro' ), $code_data[0], $code_data[1], $code_data[2] );
			} else {
				$entry['description'] = esc_html__( 'Changes Found', 'it-l10n-ithemes-security-pro' );
			}
		} elseif ( 'skipping-recovery' === $code ) {
			$code_specific = isset( $code_data[0] ) ? $code_data[0] : '';

			if ( 'no-lock' === $code_specific ) {
				$entry['description'] = esc_html__( 'Skipping Recovery: No Lock', 'it-l10n-ithemes-security-pro' );
			} elseif ( 'empty-storage' === $code_specific ) {
				$entry['description'] = esc_html__( 'Skipping Recovery: No Lock', 'it-l10n-ithemes-security-pro' );
			} else {
				$entry['description'] = esc_html__( 'Skipping Recovery', 'it-l10n-ithemes-security-pro' );
			}
		} elseif ( 'attempting-recovery' === $code ) {
			if ( array( 'no-job-step' ) === $code_data ) {
				$entry['description'] = esc_html__( 'Attempting Recovery: Invalid Job', 'it-l10n-ithemes-security-pro' );
			} else {
				$entry['description'] = esc_html__( 'Attempting Recovery', 'it-l10n-ithemes-security-pro' );
			}
		} elseif ( 'recovery-failed-no-step' === $code ) {
			$entry['description'] = esc_html__( 'Recovery Failed: No Step', 'it-l10n-ithemes-security-pro' );
		} elseif ( 'recovery-failed-too-many-retries' === $code ) {
			$entry['description'] = esc_html__( 'Recovery Failed: Retry Limit', 'it-l10n-ithemes-security-pro' );
		} elseif ( 'recovery-failed-first-loop' === $code ) {
			$entry['description'] = esc_html__( 'Recovery Failed: First Loop', 'it-l10n-ithemes-security-pro' );
		} elseif ( 'recovery-scheduled' === $code ) {
			$entry['description'] = esc_html__( 'Recovery Scheduled', 'it-l10n-ithemes-security-pro' );
		} elseif ( 'file-scan-aborted' === $code ) {
			if  ( ! empty( $code_data[0] ) ) {
				if ( $user = get_userdata( $code_data[0] ) ) {
					$by = $user->display_name;
				} else {
					$by = "#{$code_data[0]}";
				}

				$entry['description'] = sprintf( esc_html__( 'Scan Cancelled by %s', 'it-l10n-ithemes-security-pro' ), $by );
			} else {
				$entry['description'] = esc_html__( 'Scan Failed', 'it-l10n-ithemes-security-pro' );
			}
		} elseif ( 'rescheduling' === $code ) {
			if ( isset( $code_data[0] ) && 'no-lock' === $code_data[0] ) {
				$entry['description'] = esc_html__( 'Rescheduling: No Lock', 'it-l10n-ithemes-security-pro' );
			} else {
				$entry['description'] = esc_html__( 'Rescheduling', 'it-l10n-ithemes-security-pro' );
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
				'header'  => esc_html__( 'Memory Used', 'it-l10n-ithemes-security-pro' ),
				'content' => sprintf( esc_html_x( '%s MB', 'Megabytes of memory used', 'it-l10n-ithemes-security-pro' ), $entry['data']['memory'] ),
			);

			if ( ! empty( $entry['data']['memory_peak'] ) ) {
				$details['memory_total'] = array(
					'header'  => esc_html__( 'Total Memory', 'it-l10n-ithemes-security-pro' ),
					'content' => sprintf( esc_html_x( '%s MB', 'Megabytes of memory used', 'it-l10n-ithemes-security-pro' ), $entry['data']['memory_peak'] ),
				);
			}

			$types = array(
				'added'   => esc_html__( 'Added', 'it-l10n-ithemes-security-pro' ),
				'removed' => esc_html__( 'Removed', 'it-l10n-ithemes-security-pro' ),
				'changed' => esc_html__( 'Changed', 'it-l10n-ithemes-security-pro' ),
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
