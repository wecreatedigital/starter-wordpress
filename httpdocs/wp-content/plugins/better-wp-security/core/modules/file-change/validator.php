<?php

class ITSEC_File_Change_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'file-change';
	}

	protected function sanitize_settings() {

		unset( $this->settings['latest_changes'] );

		$this->set_previous_if_empty( array( 'show_warning', 'expected_hashes', 'last_scan' ) );
		$this->preserve_setting_if_exists( array( 'email', 'split', 'last_run', 'last_chunk', 'method' ) );
		$this->vars_to_skip_validate_matching_fields = array( 'email', 'split', 'last_run', 'last_chunk', 'method', 'latest_changes' );

		$this->sanitize_setting( 'newline-separated-array', 'file_list', __( 'Files and Folders List', 'better-wp-security' ) );
		$this->sanitize_setting( 'newline-separated-extensions', 'types', __( 'Ignore File Types', 'better-wp-security' ) );
		$this->sanitize_setting( 'bool', 'notify_admin', __( 'Display File Change Admin Warning', 'better-wp-security' ) );

		$this->settings = apply_filters( 'itsec-file-change-sanitize-settings', $this->settings );
	}
}

ITSEC_Modules::register_validator( new ITSEC_File_Change_Validator() );
