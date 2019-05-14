<?php

/**
 * Class ITSEC_Password_Requirements_Settings
 */
class ITSEC_Password_Requirements_Settings extends ITSEC_Settings {

	public function get_id() {
		return 'password-requirements';
	}

	public function get_defaults() {
		return array(
			'enabled_requirements' => array(),
			'requirement_settings' => array(),
		);
	}

	public function load() {

		$this->settings = ITSEC_Storage::get( $this->get_id() );
		$defaults       = $this->get_defaults();

		if ( ! is_array( $this->settings ) ) {
			$this->settings = array();
		}

		$this->settings = array_merge( $defaults, $this->settings );

		foreach ( ITSEC_Lib_Password_Requirements::get_registered() as $code => $requirement ) {

			if ( ! isset( $this->settings['enabled_requirements'][ $code ] ) ) {
				$this->settings['enabled_requirements'][ $code ] = false;
			}

			if ( null === $requirement['defaults'] ) {
				continue;
			}

			if ( isset( $this->settings['requirement_settings'][ $code ] ) ) {
				$current = $this->settings['requirement_settings'][ $code ];
			} else {
				$current = array();
			}

			$this->settings['requirement_settings'][ $code ] = wp_parse_args( $current, $requirement['defaults'] );
		}
	}
}

ITSEC_Modules::register_settings( new ITSEC_Password_Requirements_Settings() );