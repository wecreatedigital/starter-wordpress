<?php
if ( ( !class_exists( 'LD_REST_Users_Controller_V1' ) ) && ( class_exists( 'WP_REST_Users_Controller' ) ) ) {
	class LD_REST_Users_Controller_V1 extends WP_REST_Users_Controller {

		protected $version = 'v1';

		protected $sub_controllers = array();

		public function __construct() {
			parent::__construct();

			$this->namespace = LEARNDASH_REST_API_NAMESPACE . '/' . $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 4.7.0
		 *
		 * @see register_rest_route()
		 */
		public function register_routes() {

			$collection_params = $this->get_collection_params();
			$schema = $this->get_item_schema();

			$get_item_args = array(
				'context'  => $this->get_context_param( array( 'default' => 'view' ) ),
			);
		}
	}
}
