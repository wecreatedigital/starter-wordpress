<?php

class ITSEC_Version_Management_Validator extends ITSEC_Validator {

	public function get_id() {
		return 'version-management';
	}

	protected function sanitize_settings() {
		$this->vars_to_skip_validate_matching_fields[] = 'update_details';
		$this->vars_to_skip_validate_matching_fields[] = 'is_software_outdated';
		$this->vars_to_skip_validate_matching_fields[] = 'old_site_details';
		$this->vars_to_skip_validate_matching_fields[] = 'email_contacts';
		$this->vars_to_skip_validate_matching_fields[] = 'automatic_update_emails';
		$this->vars_to_skip_validate_matching_fields[] = 'first_seen';

		$this->set_previous_if_empty( array( 'first_seen' ) );
		$this->preserve_setting_if_exists( array( 'email_contacts', 'automatic_update_emails' ) );

		$this->sanitize_setting( 'bool', 'wordpress_automatic_updates', __( 'WordPress Automatic Updates', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'bool', 'strengthen_when_outdated', __( 'Strengthen Site When Running Outdated Software', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'bool', 'scan_for_old_wordpress_sites', __( 'Scan For Old WordPress Sites', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( array_keys( $this->get_update_types() ), 'plugin_automatic_updates', __( 'Plugin Automatic Updates', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( array_keys( $this->get_update_types() ), 'theme_automatic_updates', __( 'Theme Automatic Updates', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'cb-items:validate_package', 'packages', __( 'Plugin/Theme Configuration', 'it-l10n-ithemes-security-pro' ) );
	}

	public function get_update_types() {
		return array(
			'none'   => esc_html_x( 'None', 'Plugins/Themes', 'it-l10n-ithemes-security-pro' ),
			'custom' => esc_html_x( 'Custom', 'Custom list of Plugins/Themes.', 'it-l10n-ithemes-security-pro' ),
			'all'    => esc_html_x( 'All', 'Plugins/Themes', 'it-l10n-ithemes-security-pro' ),
		);
	}

	public function get_package_types() {
		return array(
			'enabled'  => esc_html__( 'Enabled', 'it-l10n-ithemes-security-pro' ),
			'delay'    => esc_html__( 'Delay', 'it-l10n-ithemes-security-pro' ),
			'disabled' => esc_html__( 'Disabled', 'it-l10n-ithemes-security-pro' ),
		);
	}

	protected function validate_package( $value, $package ) {
		if ( ! is_string( $package ) ) {
			return new WP_Error( 'invalid-key', __( "The 'package' key must be a string.", 'it-l10n-ithemes-security-pro' ) );
		}

		$parts = explode( ':', $package, 2 );

		if ( ! is_array( $parts ) || 2 !== count( $parts ) ) {
			return new WP_Error( 'invalid-key', __( "The 'package' key must have a format of '(type):(file)'.", 'it-l10n-ithemes-security-pro' ) );
		}

		list( $type, $file ) = $parts;

		if ( 'plugin' !== $type && 'theme' !== $type ) {
			return new WP_Error( 'invalid-entry', __( "Invalid package type. Must be 'plugin' or 'theme'.", 'it-l10n-ithemes-security-pro' ) );
		}

		if ( ! is_array( $value ) ) {
			return new WP_Error( 'invalid-entry', __( 'Value must be an array.', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( ! isset( $value['type'] ) ) {
			return new WP_Error( 'invalid-entry', __( "Invalid entry. The 'type' property is required.", 'it-l10n-ithemes-security-pro' ) );
		}

		if ( ! array_key_exists( $value['type'], $this->get_package_types() ) ) {
			return new WP_Error( 'invalid-entry', wp_sprintf( __( 'Invalid entry type. Must be %l', 'it-l10n-ithemes-security-pro' ), array_keys( $this->get_package_types() ) ) );
		}

		if ( $value['type'] === 'custom' && ( empty( $value['delay'] ) || ! is_numeric( $value['delay'] ) || $value['delay'] < 0 ) ) {
			return new WP_Error( 'invalid-entry', __( 'Invalid entry delay. Must be a number greater than 0.', 'it-l10n-ithemes-security-pro' ) );
		}

		$value['delay'] = (int) $value['delay'];

		return $value;
	}

	public function get_validated_contact( $contact ) {
		_deprecated_function( __METHOD__, '3.9.0' );

		return false;
	}

	public function get_available_admin_users_and_roles() {
		_deprecated_function( __METHOD__, '3.9.0' );

		return array(
			'users' => array(),
			'roles' => array(),
		);
	}
}

ITSEC_Modules::register_validator( new ITSEC_Version_Management_Validator() );
