<?php

final class ITSEC_Backup_Privacy {
	private $settings;

	public function __construct() {
		$this->settings = ITSEC_Modules::get_settings( 'backup' );

		add_filter( 'itsec_get_privacy_policy_for_retention', array( $this, 'get_privacy_policy_for_retention' ) );
		add_filter( 'itsec_get_privacy_policy_for_sending', array( $this, 'get_privacy_policy_for_sending' ) );
	}

	public function get_privacy_policy_for_retention( $policy ) {
		$suggested_text = '<strong class="privacy-policy-tutorial">' . __( 'Suggested text:' ) . ' </strong>';

		if ( $this->settings['enabled'] ) {
			if ( 1 !== $this->settings['method'] ) {
				$retention_days = $this->settings['interval'] * $this->settings['retain'];

				if ( $retention_days > 0 ) {
					/* Translators: 1: Number of days that backups are retained for */
					$policy .= "<p>$suggested_text " . sprintf( esc_html__( 'Backups of security log details are retained for %1$d days.', 'it-l10n-ithemes-security-pro' ), $retention_days ) . "</p>\n";
				} else {
					$policy .= "<p class=\"privacy-policy-tutorial\">" . esc_html__( 'Due to current settings, backups of security log details are retained indefinitely. If this is an issue for your site\'s compliance, you should change the settings in the Database Backups section of Security > Settings.', 'it-l10n-ithemes-security-pro' ) . "</p>\n";
				}
			}

			if ( 2 !== $this->settings['method'] ) {
				$policy .= "<p class=\"privacy-policy-tutorial\">" . esc_html__( 'Database backups are sent via email. You may need to note what the retention policy is of those emails.', 'it-l10n-ithemes-security-pro' ) . "</p>\n";
			}

			$policy .= "<p class=\"privacy-policy-tutorial\">" . esc_html__( 'Note that you may be required by some regulations to ensure that past personal data erasure requests are respected even in the event of restoring a backup of the site. You may need to set up an internal policy to ensure that previous personal data erasure requests are respected after restoring a database backup.', 'it-l10n-ithemes-security-pro' ) . "</p>\n";
		}

		return $policy;
	}

	public function get_privacy_policy_for_sending( $policy ) {
		if ( $this->settings['enabled'] && 2 !== $this->settings['method'] ) {
			$policy .= "<p class=\"privacy-policy-tutorial\">" . esc_html__( 'Database backups are sent via email. Depending on who hosts your email and your site\'s compliance needs, you may need to note that this information is sent to that host and link to their privacy policy.', 'it-l10n-ithemes-security-pro' ) . "</p>\n";
		}

		return $policy;
	}
}
new ITSEC_Backup_Privacy();
