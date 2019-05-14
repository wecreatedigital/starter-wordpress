<?php

final class ITSEC_Two_Factor_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'two-factor';
	}

	protected function sanitize_settings() {

		$this->set_previous_if_empty( array( 'allow_remember', 'allow_remember_roles' ) );

		if ( $this->sanitize_setting( 'string', 'available_methods', esc_html__( 'Authentication Methods Available to Users', 'it-l10n-ithemes-security-pro' ) ) ) {
			$this->sanitize_setting( array_keys( $this->get_available_methods() ), 'available_methods', esc_html__( 'Authentication Methods Available to Users', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( $this->sanitize_setting( 'array', 'custom_available_methods', esc_html__( 'Select Available Providers', 'it-l10n-ithemes-security-pro' ) ) ) {
			$this->sanitize_setting( array_keys( $this->get_methods() ), 'custom_available_methods', esc_html__( 'Select Available Providers', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( $this->sanitize_setting( 'string', 'protect_user_type', esc_html__( 'User Type Protection', 'it-l10n-ithemes-security-pro' ) ) ) {
			$this->sanitize_setting( array_keys( $this->get_protect_user_types() ), 'protect_user_type', esc_html__( 'User Type Protection', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( $this->sanitize_setting( 'array', 'protect_user_type_roles', esc_html__( 'Select Roles to Protect', 'it-l10n-ithemes-security-pro' ) ) ) {
			$this->sanitize_setting( array_keys( $this->get_protect_user_type_roles() ), 'protect_user_type_roles', esc_html__( 'Select Roles to Protect', 'it-l10n-ithemes-security-pro' ) );
		}

		$this->sanitize_setting( array_keys( $this->get_remember_types() ), 'allow_remember', esc_html__( 'Allow Remembering Device', 'it-l10n-ithemes-security-pro' ) );

		if ( $this->sanitize_setting( 'array', 'allow_remember_roles', __( 'Select Roles to Allow Remembering Device', 'it-l10n-ithemes-security-pro' ) ) ) {
			$this->sanitize_setting( array_keys( $this->get_protect_user_type_roles() ), 'allow_remember_roles', __( 'Select Roles to Allow Remembering Device', 'it-l10n-ithemes-security-pro' ) );
		}

		$this->sanitize_setting( 'bool', 'protect_vulnerable_users', esc_html__( 'Vulnerable User Protection', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'bool', 'protect_vulnerable_site', esc_html__( 'Vulnerable Site Protection', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'bool', 'disable_first_login', esc_html__( 'Disable on First Login', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'non-empty-text', 'on_board_welcome', esc_html__( 'On-board Welcome Text', 'it-l10n-ithemes-security-pro' ) );

		if ( $this->sanitize_setting( 'string', 'application_passwords_type', esc_html__( 'Application Passwords', 'it-l10n-ithemes-security-pro' ) ) ) {
			$this->sanitize_setting( array_keys( $this->get_app_passwords_types() ), 'application_passwords_type', esc_html__( 'Application Passwords', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( $this->sanitize_setting( 'array', 'application_passwords_roles', esc_html__( 'Select Roles for Application Passwords', 'it-l10n-ithemes-security-pro' ) ) ) {
			$this->sanitize_setting( array_keys( $this->get_app_passwords_roles() ), 'application_passwords_roles', esc_html__( 'Select Roles for Application Passwords', 'it-l10n-ithemes-security-pro' ) );
		}

		$this->sanitize_setting( array_keys( $this->get_exclusion_types() ), 'exclude_type', esc_html__( 'Disable Forced Two-Factor Authentication for Certain Roles', 'it-l10n-ithemes-security-pro' ) );

		if ( $this->sanitize_setting( 'array', 'exclude_roles', __( 'Select Roles to Disable Two-Factor', 'it-l10n-ithemes-security-pro' ) ) ) {
			$this->sanitize_setting( array_keys( $this->get_protect_user_type_roles() ), 'exclude_roles', __( 'Select Roles to Disable Two-Factor', 'it-l10n-ithemes-security-pro' ) );
		}
	}

	public function get_available_methods() {
		$types = array(
			'all'       => esc_html__( 'All Methods (recommended)', 'it-l10n-ithemes-security-pro' ),
			'not_email' => esc_html__( 'All Except Email', 'it-l10n-ithemes-security-pro' ),
			'custom'    => esc_html__( 'Select Methods Manually', 'it-l10n-ithemes-security-pro' ),
		);

		return $types;
	}

	public function get_methods() {
		require_once( dirname( __FILE__ ) . '/class-itsec-two-factor-helper.php' );
		$helper = ITSEC_Two_Factor_Helper::get_instance();

		return $helper->get_all_provider_instances();
	}

	public function get_protect_user_types() {
		$methods = array(
			'privileged_users' => esc_html__( 'Privileged Users (recommended)', 'it-l10n-ithemes-security-pro' ),
			'all_users'        => esc_html__( 'All Users (not recommended)', 'it-l10n-ithemes-security-pro' ),
			'custom'           => esc_html__( 'Select Roles Manually', 'it-l10n-ithemes-security-pro' ),
			'disabled'         => esc_html__( 'Disabled', 'it-l10n-ithemes-security-pro' ),
		);

		return $methods;
	}

	public function get_app_passwords_types() {
		return array(
			'enabled'  => esc_html__( 'Enabled (recommended)', 'it-l10n-ithemes-security-pro' ),
			'disabled' => esc_html__( 'Disabled', 'it-l10n-ithemes-security-pro' ),
			'custom'   => esc_html__( 'Select Roles Manually (not recommended)', 'it-l10n-ithemes-security-pro' ),
		);
	}

	public function get_app_passwords_roles() {
		return array(
			'administrator' => translate_user_role( 'Administrator' ),
			'editor'        => translate_user_role( 'Editor' ),
			'author'        => translate_user_role( 'Author' ),
			'contributor'   => translate_user_role( 'Contributor' ),
			'subscriber'    => translate_user_role( 'Subscriber' ),
		);
	}

	public function get_protect_user_type_roles() {
		$wp_roles = wp_roles();

		return $wp_roles->get_names();
	}

	public function get_exclusion_types() {
		return array(
			'disabled' => esc_html__( 'None (recommended)', 'it-l10n-ithemes-security-pro' ),
			'custom'   => esc_html__( 'Select Roles Manually (not recommended)', 'it-l10n-ithemes-security-pro' ),
		);
	}

	public function get_remember_types() {
		return array(
			'none'           => esc_html__( 'None', 'it-l10n-ithemes-security-pro' ),
			'non-privileged' => esc_html__( 'Non-Privileged Users (recommended)', 'it-l10n-ithemes-security-pro' ),
			'custom'         => esc_html__( 'Select Roles Manually', 'it-l10n-ithemes-security-pro' ),
			'all'            => esc_html__( 'All Users (not recommended)', 'it-l10n-ithemes-security-pro' ),
		);
	}
}

ITSEC_Modules::register_validator( new ITSEC_Two_Factor_Validator() );
