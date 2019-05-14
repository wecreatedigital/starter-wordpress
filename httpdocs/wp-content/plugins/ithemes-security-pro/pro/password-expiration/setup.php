<?php

if ( ! class_exists( 'ITSEC_Password_Setup' ) ) {

	class ITSEC_Password_Setup {

		public function __construct() {

			add_action( 'itsec_modules_do_plugin_activation',   array( $this, 'execute_activate'   )          );
			add_action( 'itsec_modules_do_plugin_deactivation', array( $this, 'execute_deactivate' )          );
			add_action( 'itsec_modules_do_plugin_uninstall',    array( $this, 'execute_uninstall'  )          );
			add_action( 'itsec_modules_do_plugin_upgrade',      array( $this, 'execute_upgrade'    ), null, 2 );

		}

		/**
		 * Execute module activation.
		 *
		 * @since 4.0
		 *
		 * @return void
		 */
		public function execute_activate() {
		}

		/**
		 * Execute module deactivation
		 *
		 * @return void
		 */
		public function execute_deactivate() {

			delete_metadata( 'user', null, 'itsec_password_change_required', null, true );

		}

		/**
		 * Execute module uninstall
		 *
		 * @return void
		 */
		public function execute_uninstall() {

			$this->execute_deactivate();

			delete_site_option( 'itsec_password' );
			delete_metadata( 'user', null, 'itsec_last_password_change', null, true );

		}

		/**
		 * Execute module upgrade
		 *
		 * @return void
		 */
		public function execute_upgrade( $itsec_old_version ) {

			if ( $itsec_old_version < 4041 ) {
				$current_options = get_site_option( 'itsec_password' );

				// If there are no current options, go with the new defaults by not saving anything
				if ( is_array( $current_options ) ) {
					$settings = ITSEC_Modules::get_defaults( 'password-expiration' );

					// Fill all new settings from existing old
					foreach ( $settings as $name => $value ) {
						if ( isset( $current_options[ $name ] ) ) {
							$settings[ $name ] = $current_options[ $name ];
						}
					}

					// Make sure the new module is properly activated or deactivated
					if ( isset( $current_options['expire'] ) && $current_options['expire'] ) {
						ITSEC_Modules::activate( 'password-expiration' );
					} else {
						ITSEC_Modules::deactivate( 'password-expiration' );
					}

					ITSEC_Modules::set_settings( 'password-expiration', $settings );
				}
			}

			if ( $itsec_old_version < 4096 ) {
				$active = get_site_option( 'itsec_active_modules', array() );

				if ( ! empty( $active['password-expiration'] ) ) {
					$active_requirements = ITSEC_Modules::get_setting( 'password-requirements', 'enabled_requirements' );
					$active_requirements['age'] = true;
					ITSEC_Modules::set_setting( 'password-requirements', 'enabled_requirements', $active_requirements );
				} else {
					ITSEC_Modules::set_setting( 'password-expiration', 'expire_force', false );
				}

				$requirement_settings = ITSEC_Modules::get_setting( 'password-requirements', 'requirement_settings' );
				$requirement_settings['age']['role'] = ITSEC_Modules::get_setting( 'password-expiration', 'expire_role', 'administrator' );
				$requirement_settings['age']['expire_max'] = ITSEC_Modules::get_setting( 'password-expiration', 'expire_max', 120 );
				ITSEC_Modules::set_setting( 'password-requirements', 'requirement_settings', $requirement_settings );

				unset( $active['password-expiration'] );

				// Need to do this directly to be able to remove a module from the list entirely.
				if ( is_multisite() ) {
					update_site_option( 'itsec_active_modules', $active );
				} else {
					update_option( 'itsec_active_modules', $active );
				}
			}
		}

	}

}

new ITSEC_Password_Setup();
