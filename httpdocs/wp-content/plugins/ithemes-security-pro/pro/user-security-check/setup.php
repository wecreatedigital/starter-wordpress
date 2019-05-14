<?php

if ( ! class_exists( 'ITSEC_User_Security_Check_Setup' ) ) {

	class ITSEC_User_Security_Check_Setup {

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
		}

		/**
		 * Execute module uninstall
		 *
		 * @return void
		 */
		public function execute_uninstall() {
			$this->execute_deactivate();
		}

		/**
		 * Execute module upgrade
		 *
		 * @param int $old
		 * @param int $new
		 *
		 * @return void
		 */
		public function execute_upgrade( $old, $new ) {

			if ( $old < 4079 ) {
				wp_clear_scheduled_hook( 'itsec_check_inactive_accounts' );
			}
		}

	}

}

new ITSEC_User_Security_Check_Setup();
