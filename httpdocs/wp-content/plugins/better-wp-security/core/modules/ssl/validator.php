<?php

class ITSEC_SSL_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'ssl';
	}

	protected function sanitize_settings() {
		$this->sanitize_setting( array( 'disabled', 'enabled', 'advanced' ), 'require_ssl', esc_html__( 'Require SSL', 'better-wp-security' ) );
		$this->sanitize_setting( 'positive-int', 'frontend', esc_html__( 'Front End SSL Mode', 'better-wp-security' ) );
		$this->sanitize_setting( array( 0, 1, 2 ), 'frontend', esc_html__( 'Front End SSL Mode', 'better-wp-security' ) );
		$this->sanitize_setting( 'bool', 'admin', esc_html__( 'SSL for Dashboard', 'better-wp-security' ) );
	}

	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}


		$previous_settings = ITSEC_Modules::get_settings( $this->get_id() );

		$regenerate_wp_config = false;
		$force_logout = false;

		if ( $this->settings['require_ssl'] !== $previous_settings['require_ssl'] ) {
			$regenerate_wp_config = true;
		}

		if ( $this->settings['admin'] !== $previous_settings['admin'] ) {
			$regenerate_wp_config = true;
		}

		if (
			( 'enabled' === $this->settings['require_ssl'] && 'enabled' !== $previous_settings['require_ssl'] ) ||
			( 'advanced' === $this->settings['require_ssl'] && 'advanced' !== $previous_settings['require_ssl'] && $this->settings['admin'] ) ||
			( 'advanced' === $this->settings['require_ssl'] && $this->settings['admin'] && ! $previous_settings['admin'] )
		) {
			$force_logout = true;
		}


		if ( $regenerate_wp_config ) {
			ITSEC_Response::regenerate_wp_config();
		}

		if ( $force_logout && ! is_ssl() ) {
			ITSEC_Response::force_logout();
		}
	}
}

ITSEC_Modules::register_validator( new ITSEC_SSL_Validator() );
