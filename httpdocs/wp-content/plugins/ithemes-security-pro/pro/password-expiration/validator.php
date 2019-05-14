<?php

class ITSEC_Password_Expiration_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'password-expiration';
	}
	
	protected function sanitize_settings() {

		$this->vars_to_skip_validate_matching_fields = array( 'expire_role', 'expire_max' );
		$this->preserve_setting_if_exists( array( 'expire_role', 'expire_max' ) );

		if ( ! empty( $this->settings['expire_force'] ) ) {
			$this->settings['expire_force'] = ITSEC_Core::get_current_time_gmt();
		} elseif ( false === $this->settings['expire_force'] ) {
			$this->settings['expire_force'] = 0;
			ITSEC_Lib_Password_Requirements::global_clear_required_password_change( 'force' );
		} else {
			$this->settings['expire_force'] = $this->previous_settings['expire_force'];
		}
	}
}

ITSEC_Modules::register_validator( new ITSEC_Password_Expiration_Validator() );
