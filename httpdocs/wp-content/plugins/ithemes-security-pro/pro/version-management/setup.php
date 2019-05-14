<?php

if ( ! class_exists( 'ITSEC_Version_Management_Setup' ) ) {
	final class ITSEC_Version_Management_Setup {
		public function __construct() {
			add_action( 'itsec_modules_do_plugin_activation', array( $this, 'execute_activate' ) );
			add_action( 'itsec_modules_do_plugin_deactivation', array( $this, 'execute_deactivate' ) );
			add_action( 'itsec_modules_do_plugin_uninstall', array( $this, 'execute_uninstall' ) );
			add_action( 'itsec_modules_do_plugin_upgrade', array( $this, 'execute_upgrade' ) );
		}

		/**
		 * Execute module activation.
		 *
		 * @since 2.9.0
		 *
		 * @return void
		 */
		public function execute_activate() {
			require_once( dirname( __FILE__ ) . '/class-itsec-version-management.php' );

			ITSEC_Version_Management::activate();
		}

		/**
		 * Execute module deactivation
		 *
		 * @since 2.9.0
		 *
		 * @return void
		 */
		public function execute_deactivate() {
			delete_site_option( 'itsec_vm_wp_releases' );

			require_once( dirname( __FILE__ ) . '/class-itsec-version-management.php' );
			ITSEC_Version_Management::deactivate();
		}

		/**
		 * Execute module uninstall
		 *
		 * @since 2.9.0
		 *
		 * @return void
		 */
		public function execute_uninstall() {
			$this->execute_deactivate();
		}

		/**
		 * Execute upgrade routine.
		 *
		 * @param int $old_version
		 */
		public function execute_upgrade( $old_version ) {

			if ( $old_version < 4079 ) {
				wp_clear_scheduled_hook( 'itsec_vm_outdated_wp_check' );
				wp_clear_scheduled_hook( 'itsec_vm_outdated_check' );
				wp_clear_scheduled_hook( 'itsec_vm_scan_for_old_sites' );
			}

			if ( $old_version < 4098 ) {
				$settings = ITSEC_Modules::get_settings( 'version-management' );

				if ( is_bool( $settings['plugin_automatic_updates'] ) ) {
					$settings['plugin_automatic_updates'] = $settings['plugin_automatic_updates'] ? 'all' : 'none';
				}

				if ( is_bool( $settings['theme_automatic_updates'] ) ) {
					$settings['theme_automatic_updates'] = $settings['theme_automatic_updates'] ? 'all' : 'none';
				}

				ITSEC_Modules::set_settings( 'version-management', $settings );
			}
		}
	}
}

new ITSEC_Version_Management_Setup();
