<?php

final class ITSEC_Backup_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'backup';
	}
	
	public function get_defaults() {
		return array(
			'all_sites' => false,
			'method'    => 1,
			'location'  => ITSEC_Core::get_storage_dir( 'backups' ),
			'retain'    => 0,
			'zip'       => true,
			'exclude'   => array(
				'itsec_log',
				'itsec_temp',
				'itsec_lockouts',
			),
			'enabled'   => false,
			'interval'  => 3,
			'last_run'  => 0,
		);
	}

	protected function handle_settings_changes( $old_settings ) {

		if ( $old_settings['enabled'] !== $this->settings['enabled'] ) {
			if ( $this->settings['enabled'] ) {
				ITSEC_Core::get_scheduler()->schedule( 'backup', 'backup' );
			} else {
				ITSEC_Core::get_scheduler()->unschedule( 'backup' );
			}
		}
	}
}

ITSEC_Modules::register_settings( new ITSEC_Backup_Settings() );
