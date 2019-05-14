<?php

final class ITSEC_Security_Check_Pro_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'security-check-pro';
	}

	protected function sanitize_settings() {
		$this->set_previous_if_empty( array_keys( $this->defaults ) );
		$this->vars_to_skip_validate_matching_fields = array_keys( $this->defaults );
		$this->vars_to_skip_validate_matching_types = array_keys( $this->defaults );

		if ( ! is_null( $this->settings['last_scan_timestamp'] ) && ! is_int( $this->settings['last_scan_timestamp'] ) || $this->settings['last_scan_timestamp'] < 0 ) {
			$this->settings['last_scan_timestamp'] = $this->defaults['last_scan_timestamp'];
		}

		if ( is_array( $this->settings['remote_ip_index'] ) ) {
			if ( 2 !== count( $this->settings['remote_ip_index'] ) || ! is_string( $this->settings['remote_ip_index'][0] ) || ! is_int( $this->settings['remote_ip_index'][1] ) ) {
				$this->settings['remote_ip_index'] = $this->defaults['remote_ip_index'];
			}
		} else if ( ! is_null( $this->settings['remote_ip_index'] ) && ! is_string( $this->settings['remote_ip_index'] ) ) {
			$this->settings['remote_ip_index'] = $this->defaults['remote_ip_index'];
		}

		if ( ! is_null( $this->settings['ssl_supported'] ) && ! is_bool( $this->settings['ssl_supported'] ) ) {
			$this->settings['ssl_supported'] = $this->defaults['ssl_supported'];
		}

		if ( ! is_int( $this->settings['remote_ips_timestamp'] ) ) {
			$this->settings['remote_ips_timestamp'] = $this->defaults['remote_ips_timestamp'];
		}

		if ( ! is_array( $this->settings['remote_ips'] ) ) {
			$this->settings['remote_ips'] = $this->defaults['remote_ips'];
		}

		if ( ! is_string( $this->settings['key_salt'] ) ) {
			$this->settings['key_salt'] = $this->defaults['key_salt'];
		}
	}
}

ITSEC_Modules::register_validator( new ITSEC_Security_Check_Pro_Validator() );
