<?php

/**
 * Class ITSEC_Password_Requirements_Validator
 */
class ITSEC_Password_Requirements_Validator extends ITSEC_Validator {

	/** @var string */
	private $current_requirement;

	public function get_id() {
		return 'password-requirements';
	}

	protected function sanitize_settings() {
		$this->sanitize_setting( 'array', 'enabled_requirements', __( 'Enabled Requirements', 'better-wp-security' ) );
		$this->sanitize_setting( 'array', 'requirement_settings', __( 'Requirement Settings', 'better-wp-security' ) );

		$requirements = ITSEC_Lib_Password_Requirements::get_registered();

		$settings = $this->settings;

		foreach ( $requirements as $code => $requirement ) {
			if ( null === $requirement['settings_config'] ) {
				continue;
			}

			$config   = call_user_func( $requirement['settings_config'] );
			$sanitize = call_user_func( $config['sanitize'], $this->settings );

			if ( is_wp_error( $sanitize ) ) {
				$this->add_error( $sanitize );

				if ( ITSEC_Core::is_interactive() ) {
					$this->set_can_save( false );
				}
			} elseif ( is_array( $sanitize ) ) {
				$this->settings            = isset( $settings['requirement_settings'][ $code ] ) ? $settings['requirement_settings'][ $code ] : $requirement['defaults'];
				$this->current_requirement = $code;

				foreach ( $sanitize as $args ) {
					call_user_func_array( array( $this, 'sanitize_setting' ), $args );
				}

				$settings['requirement_settings'][ $code ] = $this->settings;
				$this->settings                            = $settings;
				$this->current_requirement                 = null;
			}

		}
	}

	protected function generate_error( $id, $var, $type, $error ) {
		if ( null === $this->current_requirement ) {
			return parent::generate_error( $id, $var, $type, $error );
		}

		return new WP_Error( "itsec-validator-$id-invalid-type-enabled_requirements-{$this->current_requirement}-$var-$type", $error );
	}
}

ITSEC_Modules::register_validator( new ITSEC_Password_Requirements_Validator() );