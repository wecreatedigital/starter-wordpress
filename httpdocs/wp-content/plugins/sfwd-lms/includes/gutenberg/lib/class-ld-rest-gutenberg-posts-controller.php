<?php
/**
 * LearnDash Gutenberg Posts Controller.
 *
 * @package LearnDash
 * @since 2.5.9
 */

 if ( ! class_exists( 'LD_REST_Posts_Gutenberg_Controller' ) ) {
	/**
	 * LearnDash Gutenberg Posts Controller.
	 *
	 * @package LearnDash
	 * @since 2.5.9
	 */
	class LD_REST_Posts_Gutenberg_Controller extends WP_REST_Posts_Controller {

		public function __construct( $post_type = '' ) {
			parent::__construct( $post_type );
		}		

		public function register_routes() {
			$namespace = 'wp/v2';
			$schema = $this->get_item_schema();
			$get_item_args = array(
				'context'  => $this->get_context_param( array( 'default' => 'view' ) ),
			);
			if ( isset( $schema['properties']['password'] ) ) {
				$get_item_args['password'] = array(
					'description' => __( 'The password for the post if it is password protected.', 'learndash' ),
					'type'        => 'string',
				);
			}

			register_rest_route( $namespace, '/' . $this->post_type . '/(?P<id>[\d]+)', array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.', 'learndash' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $get_item_args,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'learndash' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			) );
		}

		// End of functions
	}
}
