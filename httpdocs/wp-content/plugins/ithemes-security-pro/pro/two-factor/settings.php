<?php

final class ITSEC_Two_Factor_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'two-factor';
	}

	public function get_defaults() {
		return array(
			'available_methods'           => 'all',
			'custom_available_methods'    => array(
				'Two_Factor_Totp',
				'Two_Factor_Email',
				'Two_Factor_Backup_Codes',
			),
			'protect_user_type'           => 'disabled',
			'protect_user_type_roles'     => array(),
			'protect_vulnerable_users'    => false,
			'protect_vulnerable_site'     => false,
			'disable_first_login'         => false,
			'application_passwords_type'  => 'enabled',
			'application_passwords_roles' => array(),
			'on_board_welcome'            => '',
			'exclude_type'                => 'disabled',
			'exclude_roles'               => array(),
			'allow_remember'              => 'none',
			'allow_remember_roles'        => array(),
		);
	}

	public function load() {
		parent::load();

		if ( empty( $this->settings['on_board_welcome'] ) ) {
			$this->settings['on_board_welcome'] = $this->get_default_on_board_welcome();
		}
	}

	private function get_default_on_board_welcome() {
		$welcome = esc_html__( 'When you login using Two-factor authenticator you’ll be prompted to enter a secondary Authentication Code from your Phone or Email.', 'it-l10n-ithemes-security-pro' );
		$welcome .= "\n\n";
		$welcome .= esc_html__( 'Two-Factor authentication adds an important extra layer of protection to your login by combining something you know, your password, with something you have, your Phone or Email, preventing attackers from gaining access to your account even if you lose control of your password.', 'it-l10n-ithemes-security-pro' );

		return $welcome;
	}
}

ITSEC_Modules::register_settings( new ITSEC_Two_Factor_Settings() );
