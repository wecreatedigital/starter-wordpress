<?php

/**
 * Class ITSEC_Fingerprinting_Logs
 */
class ITSEC_Fingerprinting_Logs {

	public function __construct() {
		add_filter( 'itsec_logs_prepare_fingerprinting_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ), 10, 3 );
		add_filter( 'itsec_logs_prepare_fingerprinting_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry, $code, $data ) {

		$entry['module_display'] = esc_html__( 'Trusted Devices', 'it-l10n-ithemes-security-pro' );

		if ( $description = $this->get_description( $entry, $code, $data ) ) {
			$entry['description'] = $description;
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$details['module']['content'] = esc_html__( 'Trusted Devices', 'it-l10n-ithemes-security-pro' );

		if ( $description = $this->get_description( $entry, $code, $code_data ) ) {
			$details['description']['content'] = $description;
		}

		if ( 'comparison' === $code ) {
			$details['match_percent'] = array(
				'header'  => esc_html__( 'Match', 'it-l10n-ithemes-security-pro' ),
				'content' => number_format_i18n( $entry['data']['percent'], 2 ) . '%',
				'order'   => 21,
			);
			$details['scores']        = array(
				'header'  => esc_html__( 'Scores', 'it-l10n-ithemes-security-pro' ),
				'content' => $this->get_scores( $entry ),
				'order'   => 100,
			);
		}

		if ( isset( $entry['data']['uuid'] ) ) {
			$details['device'] = array(
				'header'  => esc_html__( 'Device', 'it-l10n-ithemes-security-pro' ),
				'content' => $this->get_fingerprint_label( $entry['data']['uuid'] ),
				'order'   => 22,
			);
		}

		if ( isset( $entry['data']['from'] ) ) {
			$details['device_from'] = array(
				'header'  => esc_html__( 'From Device', 'it-l10n-ithemes-security-pro' ),
				'content' => $this->get_fingerprint_label( $entry['data']['from'] ),
				'order'   => 22,
			);
		}

		if ( isset( $entry['data']['to'] ) ) {
			$details['device_to'] = array(
				'header'  => esc_html__( 'To Device', 'it-l10n-ithemes-security-pro' ),
				'content' => $this->get_fingerprint_label( $entry['data']['to'] ),
				'order'   => 23,
			);
		}

		if ( isset( $entry['data']['known'] ) ) {
			$details['device_known'] = array(
				'header'  => esc_html__( 'Known Device', 'it-l10n-ithemes-security-pro' ),
				'content' => $this->get_fingerprint_label( $entry['data']['known'] ),
				'order'   => 22,
			);
		}

		if ( isset( $entry['data']['unknown'] ) ) {
			$details['device_unknown'] = array(
				'header'  => esc_html__( 'Unknown Device', 'it-l10n-ithemes-security-pro' ),
				'content' => $this->get_fingerprint_label( $entry['data']['unknown'] ),
				'order'   => 23,
			);
		}

		return $details;
	}

	private function get_fingerprint_label( $uuid ) {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		$fingerprint = ITSEC_Fingerprint::get_by_uuid( $uuid );

		return $fingerprint ? (string) $fingerprint : esc_html__( '(deleted)', 'it-l10n-ithemes-security-pro' );
	}

	private function get_description( $entry, $code, $data ) {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		switch ( $code ) {
			case 'created':
				return esc_html__( 'New Device Created', 'it-l10n-ithemes-security-pro' );
			case 'status':
				switch ( $data[0] ) {
					case ITSEC_Fingerprint::S_APPROVED:
						$desc = esc_html__( 'Device Approved', 'it-l10n-ithemes-security-pro' );
						break;
					case ITSEC_Fingerprint::S_AUTO_APPROVED:
						$desc = esc_html__( 'Device Auto-approved', 'it-l10n-ithemes-security-pro' );
						break;
					case ITSEC_Fingerprint::S_PENDING_AUTO_APPROVE:
						$desc = esc_html__( 'Device Auto-approval Delayed', 'it-l10n-ithemes-security-pro' );
						break;
					case ITSEC_Fingerprint::S_DENIED:
						$desc = esc_html__( 'Device Blocked', 'it-l10n-ithemes-security-pro' );
						break;
					default:
						$desc = sprintf( esc_html__( 'Device status updated to %s', 'it-l10n-ithemes-security-pro' ), $data[0] );
						break;
				}

				if ( isset( $data[1] ) && 'override' === $data[1] ) {
					/* translators: This action was manually overridden by a user. */
					$desc .= ' ' . esc_html__( '(override)', 'it-l10n-ithemes-security-pro' );
				}

				return $desc;
			case 'session_switched_known':
				return esc_html__( 'Session switched to a Known Device', 'it-l10n-ithemes-security-pro' );
			case 'session_switched_unknown':
				return esc_html__( 'Session switched to an Unknown Device', 'it-l10n-ithemes-security-pro' );
			case 'session_destroyed':
				return esc_html__( 'Session Hijack Protection destroyed session.', 'it-l10n-ithemes-security-pro' );
			case 'comparison':
				return esc_html__( 'Devices Compared', 'it-l10n-ithemes-security-pro' );
			case 'denied_fingerprint_blocked':
				return esc_html__( 'Blocked Device Prevented from Log-in', 'it-l10n-ithemes-security-pro' );
			default:
				return $code;
		}
	}

	/**
	 * Get a scores table for a fingerprint comparison.
	 *
	 * @param array $entry
	 *
	 * @return string
	 */
	private function get_scores( $entry ) {

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );

		$known   = ITSEC_Fingerprint::get_by_uuid( $entry['data']['known'] );
		$unknown = ITSEC_Fingerprint::get_by_uuid( $entry['data']['unknown'] );

		$scores = $entry['data']['scores'];
		$scores = wp_list_sort( $scores, 'weight', 'DESC', true );

		ob_start();
		?>
		<table class="widefat striped">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Source', 'it-l10n-ithemes-security-pro' ); ?></th>
				<th><?php esc_html_e( 'Score', 'it-l10n-ithemes-security-pro' ); ?></th>
				<th><?php esc_html_e( 'Weight', 'it-l10n-ithemes-security-pro' ); ?></th>
				<th><?php esc_html_e( 'Known', 'it-l10n-ithemes-security-pro' ); ?></th>
				<th><?php esc_html_e( 'Unknown', 'it-l10n-ithemes-security-pro' ); ?></th>
			</tr>
			</thead>
			<?php foreach ( $scores as $source => $score ): ?>
				<tr>
					<td><?php echo esc_html( $source ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $score['score'], 2 ) . '%' ); ?></td>
					<td><?php echo (int) $score['weight']; ?></td>
					<td><?php echo $this->get_fingerprint_value( $known, $source ); ?></td>
					<td><?php echo $this->get_fingerprint_value( $unknown, $source ); ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get a string for the fingerprint value.
	 *
	 * @param ITSEC_Fingerprint|null $fingerprint
	 * @param string                 $source
	 *
	 * @return string
	 */
	private function get_fingerprint_value( $fingerprint, $source ) {
		if ( ! $fingerprint ) {
			return '–';
		}

		$values = $fingerprint->get_values();

		if ( ! isset( $values[ $source ] ) ) {
			return '–';
		}

		$value = $values[ $source ]->get_value();

		if ( is_scalar( $value ) ) {
			return esc_html( $value );
		}

		ob_start();
		ITSEC_Lib::print_r( $value );

		return ob_get_clean();
	}
}

new ITSEC_Fingerprinting_Logs();
