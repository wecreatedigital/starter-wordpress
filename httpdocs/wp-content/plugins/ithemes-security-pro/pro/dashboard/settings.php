<?php

/**
 * Class ITSEC_Dashboard_Settings
 */
class ITSEC_Dashboard_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'dashboard';
	}

	public function get_defaults() {
		return array(
			'migrated'       => false,
			'disabled_users' => array(),
		);
	}
}

ITSEC_Modules::register_settings( new ITSEC_Dashboard_Settings() );
