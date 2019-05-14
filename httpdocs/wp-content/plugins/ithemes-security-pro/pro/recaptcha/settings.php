<?php

final class ITSEC_Recaptcha_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'recaptcha';
	}

	public function get_defaults() {
		return array(
			'type'                => 'v2',
			'site_key'            => '',
			'secret_key'          => '',
			'login'               => false,
			'register'            => false,
			'comments'            => false,
			'language'            => '',
			'theme'               => false,
			'error_threshold'     => 7,
			'check_period'        => 5,
			'validated'           => false,
			'last_error'          => '',
			'invis_position'      => 'bottomright',
			'gdpr'                => true,
			'on_page_opt_in'      => true,
			'v3_threshold'        => 0.50,
			'v3_include_location' => 'required',
		);
	}
}

ITSEC_Modules::register_settings( new ITSEC_Recaptcha_Settings() );
