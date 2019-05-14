<?php

final class ITSEC_Online_Files_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'online-files';
	}
	
	protected function sanitize_settings() {
		$this->set_previous_if_empty( array( 'valid_wporg_plugins' ) );
		$this->sanitize_setting( 'bool', 'compare_file_hashes', __( 'Compare Files Online', 'it-l10n-ithemes-security-pro' ) );
	}
}

ITSEC_Modules::register_validator( new ITSEC_Online_Files_Validator() );
