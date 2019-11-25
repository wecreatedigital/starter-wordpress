<?php
/**
 * LearnDash Data Upgrades for Group Leader Role
 *
 * @package LearnDash
 * @subpackage Data Upgrades
 */

if ( ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) && ( ! class_exists( 'Learndash_Admin_Data_Upgrades_Group_Leader_Role' ) ) ) {
	/**
	 * Class to create the Data Upgrade for Group Leader Role.
	 */
	class Learndash_Admin_Data_Upgrades_Group_Leader_Role extends Learndash_Admin_Data_Upgrades {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->data_slug = 'group-leader-role';
			parent::__construct();
			add_action( 'init', array( $this, 'create_group_leader_role' ) );
			parent::register_upgrade_action();
		}

		/**
		 * Create Group Leader Role
		 *
		 * Checks to see if settings needs to be updated.
		 *
		 * @since 2.5.6
		 */
		public function create_group_leader_role() {

			if ( is_admin() ) {
				$gl_role_created = $this->get_data_settings( 'gl_role' );
				if ( ( defined( 'LEARNDASH_ACTIVATED' ) && LEARNDASH_ACTIVATED ) || ( ! $gl_role_created ) ) {

					learndash_add_group_admin_role();

					$this->set_data_settings( 'gl_role_created', time() );
				}
			}
		}

		// End of functions.
	}
}

add_action( 'learndash_data_upgrades_init', function() {
	Learndash_Admin_Data_Upgrades_Group_Leader_Role::add_instance();
} );
