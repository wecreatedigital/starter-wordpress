<?php

final class ITSEC_Security_Check_Pro_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'security-check-pro';
	}

	public function get_defaults() {
		return array(
			'last_scan_timestamp'  => null,
			'remote_ip_index'      => null,
			'ssl_supported'        => null,
			'remote_ips_timestamp' => null,
			'remote_ips'           => array(),
			'key_salt'             => '',
		);
	}
}

ITSEC_Modules::register_settings( new ITSEC_Security_Check_Pro_Settings() );
