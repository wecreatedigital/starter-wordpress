<?php
if ( ( !class_exists( 'LD_REST_Groups_Controller_V1' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V1' ) ) ) {
	class LD_REST_Groups_Controller_V1 extends LD_REST_Posts_Controller_V1 {
		
		public function __construct( $post_type = '' ) {
			$this->post_type = 'groups';
			$this->taxonomies = array();

			parent::__construct( $this->post_type );
			$this->namespace = LEARNDASH_REST_API_NAMESPACE .'/'. $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_REST_API', $this->post_type );
		}

	    public function register_routes() {
			$this->register_fields();

			parent::register_routes_wpv2();

			$collection_params = $this->get_collection_params();
			$schema = $this->get_item_schema();

			$get_item_args = array(
				'context'  => $this->get_context_param( array( 'default' => 'view' ) ),
			);
			if ( isset( $schema['properties']['password'] ) ) {
				$get_item_args['password'] = array(
					'description' => esc_html__( 'The password for the post if it is password protected.', 'learndash' ),
					'type'        => 'string',
				);
			}

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_item' ),
						'permission_callback' => array( $this, 'create_item_permissions_check' ),
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);

			register_rest_route( 
				$this->namespace, 
				'/' . $this->rest_base . '/(?P<id>[\d]+)', 
				array(
					'args' => array(
						'id' => array(
							'description' 	=> esc_html__( 'Unique identifier for the object.', 'learndash' ),
							'required'		=> true,
							'type'        	=> 'integer',
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
								'description' => esc_html__( 'Whether to bypass trash and force deletion.', 'learndash' ),
							),
						),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				) 
			);

			include( LEARNDASH_REST_API_DIR . '/'. $this->version.'/class-ld-rest-groups-courses-controller.php' );
			$this->sub_controllers['class-ld-rest-groups-courses-controller'] = new LD_REST_Groups_Courses_Controller_V1();
			$this->sub_controllers['class-ld-rest-groups-courses-controller']->register_routes();

			include( LEARNDASH_REST_API_DIR . '/'. $this->version.'/class-ld-rest-groups-leaders-controller.php' );
			$this->sub_controllers['class-ld-rest-groups-leaders-controller'] = new LD_REST_Groups_Leaders_Controller_V1();
			$this->sub_controllers['class-ld-rest-groups-leaders-controller']->register_routes();

			include( LEARNDASH_REST_API_DIR . '/'. $this->version.'/class-ld-rest-groups-users-controller.php' );
			$this->sub_controllers['class-ld-rest-groups-users-controller'] = new LD_REST_Groups_Users_Controller_V1();
			$this->sub_controllers['class-ld-rest-groups-users-controller']->register_routes();

		}

		function get_items_permissions_check( $request ) {
			if ( ( learndash_is_admin_user( ) ) || ( learndash_is_group_leader_user() ) ) {
				return true;
			}
		}

		function get_items( $request ) {
			return parent::get_items( $request );
		}		


		function get_item_permissions_check( $request ) {
			if ( ( learndash_is_admin_user( ) ) || ( learndash_is_group_leader_user() ) ) {
				return true;
			}
		}

		function get_item( $request ) {
			return parent::get_item( $request );
		}

		function rest_query_filter( $args, $request ) {
			if ( learndash_is_group_leader_user() ) {
				$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
				if ( ! empty( $group_ids ) )
					$args['post__in'] = $group_ids;
				else
					$args['post__in'] = array(0);
			}

			return $args;
		}

		function rest_prepare_response( $response, $post, $request ) {

			$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

			// Entity meta.
			$links = array(
				'users'       => array(
					'href' => rest_url( trailingslashit( $base ) . $post->ID . '/users' ),
				),
				'leaders' => array(
					'href' => rest_url( trailingslashit( $base ) . $post->ID . '/leaders' ),
				),
				'courses'      => array(
					'href' => rest_url( trailingslashit( $base ) . $post->ID . '/courses' ),
				),
			);
			$response->add_links( $links );

			return $response;
		}

		// End of functions
	}
}
