<?php
if ( ( !class_exists( 'LD_REST_Courses_Users_Controller_V1' ) ) && ( class_exists( 'LD_REST_Users_Controller_V1' ) ) ) {
	class LD_REST_Courses_Users_Controller_V1 extends LD_REST_Users_Controller_V1 {
		
		private $supported_collection_params = array(
			'exclude'  => 'exclude',
			'include'  => 'include',
			'offset'	=> 'offset',
			'order'    => 'order',
			'page'		=> 'paged',
			'per_page' => 'number',
			'search'   => 'search',
			'roles'    => 'role__in',
			'slug'     => 'nicename__in',
		);

		public function __construct( ) {
			
			parent::__construct( );
			$this->namespace = LEARNDASH_REST_API_NAMESPACE .'/'. $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_REST_API', 'sfwd-courses' );
		}
		
	    public function register_routes() {
			$this->meta = new WP_REST_User_Meta_Fields();

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
				'/' . $this->rest_base . '/(?P<id>[\d]+)/users', 
				array(
					'args' => array(
						'id' => array(
							'description' => esc_html__( 'Course ID.', 'learndash' ),
    							'required' => true,
							'type' => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_courses_users' ),
						'permission_callback' => array( $this, 'get_courses_users_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
                    array(
                        'methods'             => WP_REST_Server::EDITABLE,
                        'callback'            => array( $this, 'update_courses_users' ),
                        'permission_callback' => array( $this, 'update_courses_users_permissions_check' ),
						'args' => array(
							'user_ids' => array(
								'description' => esc_html__( 'User IDs to enroll into Course. Limit 50 per request.', 'learndash' ),
								'required' => true,
								'type' => 'array',
								'items' => array(
									'type' => 'integer'
								),
							),
						),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_courses_users' ),
						'permission_callback' => array( $this, 'delete_courses_users_permissions_check' ),
						'args' => array(
							'user_ids' => array(
								'description' => esc_html__( 'User IDs to remove from Group. Limit 50 per request.', 'learndash' ),
	    						'required' => true,
								'type' => 'array',
								'items' => array(
									'type' => 'integer'
								),
							),
						),
					),
				) 
			);
	    }
		
		
		function update_courses_users_permissions_check( $request ) {
			if ( learndash_is_admin_user( ) ) {
				return true;
			}
		}

		function delete_courses_users_permissions_check( $request ) {
			if ( learndash_is_admin_user( ) ) {
				return true;
			}
		}

		function get_courses_users_permissions_check( $request ) {
			if ( learndash_is_admin_user( ) ) {
				return true;
			}
		}

		function update_courses_users( $request ) {
			$course_id = $request['id'];
			if ( empty( $course_id ) ) {
				return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid Course ID.', 'learndash' ), array( 'status' => 404 ) );
			}

			$user_ids = $request['user_ids'];
			if ( ( !is_array( $user_ids ) ) || ( empty( $user_ids ) ) ) {
				return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Missing User IDs.', 'learndash' ), array( 'status' => 404 ) );
			} else {
				$user_ids = array_map( 'intval', $user_ids );
			}

			foreach( $user_ids as $user_id ) {
				ld_update_course_access( $user_id, $course_id );
			}

			$data = array( );

			// Create the response object
			$response = rest_ensure_response( $data );

			// Add a custom status code
			$response->set_status( 200 );

			return $response;
		}

		function delete_courses_users( $request ) {
			$course_id = $request['id'];
			if ( empty( $course_id ) ) {
				return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid Course ID.', 'learndash' ), array( 'status' => 404 ) );
			}

			$user_ids = $request['user_ids'];
			if ( ( !is_array( $user_ids ) ) || ( empty( $user_ids ) ) ) {
				return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Missing User IDs.', 'learndash' ), array( 'status' => 404 ) );
			} else {
				$user_ids = array_map( 'intval', $user_ids );
			}

			foreach( $user_ids as $user_id ) {
				ld_update_course_access( $user_id, $course_id, true );
			}

			$data = array( );

			// Create the response object
			$response = rest_ensure_response( $data );

			// Add a custom status code
			$response->set_status( 200 );

			return $response;
		}

		public function get_courses_users( $request ) {

			$course_id = $request['id'];
			if ( empty( $course_id ) ) {
				return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid Course ID.', 'learndash' ), array( 'status' => 404 ) );
			}

			if ( is_user_logged_in() )
				$current_user_id = get_current_user_id();
			else
				$current_user_id = 0;

			// Retrieve the list of registered collection query parameters.
			$registered = $this->get_collection_params();	

			$prepared_args = array();

			/*
			 * For each known parameter which is both registered and present in the request,
			 * set the parameter's value on the query $prepared_args.
			 */
			foreach ( $this->supported_collection_params as $api_param => $wp_param ) {
				if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
					$prepared_args[ $wp_param ] = $request[ $api_param ];
				}
			}

			if ( isset( $registered['offset'] ) && ! empty( $request['offset'] ) ) {
				$prepared_args['offset'] = $request['offset'];
			} else {
				$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
			}

			if ( isset( $registered['orderby'] ) ) {
				$orderby_possibles        = array(
					'id'              => 'ID',
					'include'         => 'include',
					'name'            => 'display_name',
					'registered_date' => 'registered',
					'slug'            => 'user_nicename',
					'include_slugs'   => 'nicename__in',
					'email'           => 'user_email',
					'url'             => 'user_url',
				);
				$prepared_args['orderby'] = $orderby_possibles[ $request['orderby'] ];
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) {
				$exclude_admin = true;
			} else {
				$exclude_admin = false;
			}

			$course_users_query = learndash_get_users_for_course( $course_id, array(), $exclude_admin );
			if ( $course_users_query instanceof WP_User_Query ) {
				$course_user_ids = $course_users_query->get_results();
				$prepared_args['include'] = $course_user_ids;
			} else {
				$prepared_args['include'] = array( 0 );
			}

			if ( ! empty( $prepared_args['search'] ) ) {
				$prepared_args['search'] = '*' . $prepared_args['search'] . '*';
			}

			if ( ! isset( $prepared_args['fields'] ) ) {
				$prepared_args['fields'] = array('ID');
			} 

			/**
			 * Filters WP_User_Query arguments when querying users via the REST API.
			 *
			 * @link https://developer.wordpress.org/reference/classes/wp_user_query/
			 *
			 * @since 4.7.0
			 *
			 * @param array           $prepared_args Array of arguments for WP_User_Query.
			 * @param WP_REST_Request $request       The current request.
			 */
			$prepared_args = apply_filters( 'learndash_rest_courses_users_query', $prepared_args, $request );

			$query = new WP_User_Query( $prepared_args );

			$users = array();

			foreach ( $query->results as $user ) {
				if ( is_a( $user, 'WP_User' ) ) {
					$data    = $this->prepare_item_for_response( $user, $request );
					$users[] = $this->prepare_response_for_collection( $data );
				} else {
					$users[] = $user->ID;
				}
			}

			$response = rest_ensure_response( $users );

			// Store pagination values for headers then unset for count query.
			$per_page = (int) $prepared_args['number'];
			$page     = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );

			$prepared_args['fields'] = 'ID';

			$total_users = $query->get_total();

			if ( $total_users < 1 ) {
				// Out-of-bounds, run the query again without LIMIT for total count.
				unset( $prepared_args['number'], $prepared_args['offset'] );
				$count_query = new WP_User_Query( $prepared_args );
				$total_users = $count_query->get_total();
			}

			$response->header( 'X-WP-Total', (int) $total_users );

			$max_pages = ceil( $total_users / $per_page );

			$response->header( 'X-WP-TotalPages', (int) $max_pages );

			$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );
			if ( $page > 1 ) {
				$prev_page = $page - 1;

				if ( $prev_page > $max_pages ) {
					$prev_page = $max_pages;
				}

				$prev_link = add_query_arg( 'page', $prev_page, $base );
				$response->link_header( 'prev', $prev_link );
			}
			if ( $max_pages > $page ) {
				$next_page = $page + 1;
				$next_link = add_query_arg( 'page', $next_page, $base );

				$response->link_header( 'next', $next_link );
			}

			return $response;
		}

		public function get_collection_params() {
			$query_params_default = parent::get_collection_params();

			$query_params_default['context']['default'] = 'view';

			$query_params = array();
			$query_params['context'] = $query_params_default['context'];
			$query_params['fields'] = array(
				'description' => __( 'Returned values.', 'learndash' ),
				'type' => 'string',
				'type' => 'string',
				'default' => 'ids',
				'enum' => array(
					'ids',
					'objects',
				),				
			);
			foreach ( $this->supported_collection_params as $external_key => $internal_key ) {
				if ( isset( $query_params_default[ $external_key ] ) ) {
					$query_params[ $external_key ] = $query_params_default[ $external_key ];
				}
			}
			return $query_params;

		}

		// End of functions
	}
}
