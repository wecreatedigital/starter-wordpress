<?php

class ITSEC_Magic_Links_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'magic-links';
	}

	protected function sanitize_settings() {
		parent::sanitize_settings();

		$this->sanitize_setting( 'bool', 'brute_force_login_page', __( 'Email Login Link During Brute Force', 'it-l10n-ithemes-security-pro' ) );
	}
}

ITSEC_Modules::register_validator( new ITSEC_Magic_Links_Validator() );
