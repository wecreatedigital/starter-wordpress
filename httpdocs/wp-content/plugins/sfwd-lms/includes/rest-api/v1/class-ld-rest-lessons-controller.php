<?php
if ( ( !class_exists( 'LD_REST_Lessons_Controller_V1' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V1' ) ) ) {
	class LD_REST_Lessons_Controller_V1 extends LD_REST_Posts_Controller_V1 {

		public function __construct( $post_type = '' ) {
			$this->post_type = 'sfwd-lessons';

			parent::__construct( $this->post_type );
			$this->namespace = LEARNDASH_REST_API_NAMESPACE .'/'. $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_REST_API', $this->post_type );
		}

		public function register_routes() {
			parent::register_routes_wpv2();

			$this->register_fields();

			$collection_params = $this->get_collection_params();

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
				'/' . $this->rest_base . '/(?P<id>[\d]+)', 
				array(
					'args' => array(
						'id' => array(
							'description' => esc_html__( 'Unique identifier for the object.', 'learndash' ),
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
								'description' => esc_html__( 'Whether to bypass trash and force deletion.', 'learndash' ),
							),
						),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		function rest_collection_params_filter( $query_params, $post_type ) {
			$query_params = parent::rest_collection_params_filter( $query_params, $post_type );

			if ( ! isset( $query_params['course'] ) ) {
				$query_params['course'] = array(
					'description'	=> sprintf(
						// translators: placeholder: course.
						esc_html_x(
							'Limit results to be within a specific %s. Required for non-admin users.',
							'placeholder: course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'type'			=> 'integer',
				);
			}

			return $query_params;
		}

		function get_item_permissions_check( $request ) {
			$return = parent::get_item_permissions_check( $request );
			if ( ( true === $return ) && ( ! learndash_is_admin_user() ) ) {

				$course_id = (int) $request['course'];

				// If we don't have a course parameter we need to get all the courses the user has access to and all
				// the courses the lesson is avaiable in and compare.
				if ( empty( $course_id ) ) {
					$user_enrolled_courses = learndash_user_get_enrolled_courses( get_current_user_id() );
					if ( empty( $user_enrolled_courses ) ) {
						return new WP_Error( 'ld_rest_cannot_view', __( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}

					$step_courses = learndash_get_courses_for_step( $request['id'], true );
					if ( empty( $step_courses ) ) {
						return new WP_Error( 'ld_rest_cannot_view', __( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
					$user_enrolled_courses = array_intersect( $user_enrolled_courses, array_keys( $step_courses ) );

					if ( empty( $user_enrolled_courses ) ) {
						return new WP_Error( 'ld_rest_cannot_view', __( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
				} else {
					/**
					 * But if the course parameter is provided we need to check the user has access and
					 * also check the step is part of that course.
					 */
					$this->course_post = get_post( $course_id );
					if ( ( ! $this->course_post ) || ( ! is_a( $this->course_post, 'WP_Post' ) ) || ( 'sfwd-courses' !== $this->course_post->post_type ) ) {
						return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid Course ID.', 'learndash' ), array( 'status' => 404 ) );
					}

					if ( ! sfwd_lms_has_access( $this->course_post->ID ) ) {
						return new WP_Error( 'ld_rest_cannot_view', __( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
					$this->ld_course_steps_object = LDLMS_Factory_Post::course_steps( $this->course_post->ID );	
					$this->ld_course_steps_object->load_steps();
					$lesson_ids = $this->ld_course_steps_object->get_children_steps( $this->course_post->ID, $this->post_type );
					if ( empty( $lesson_ids ) ) {
						return new WP_Error( 'ld_rest_cannot_view', __( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}

					if ( ! in_array( $request['id'], $lesson_ids ) ) {
						return new WP_Error( 'ld_rest_cannot_view', __( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
				}
			}

			return $return;
		}

		function get_item( $request ) {
			return parent::get_item( $request );
		}

		function get_items_permissions_check( $request ) {
			$return = parent::get_items_permissions_check( $request );
			if ( ( true === $return ) && ( 'view' === $request['context'] ) ) {
				$course_id = (int) $request['course'];
				if ( ! empty( $course_id ) ) {
					$this->course_post = get_post( $course_id );
					if ( ( ! $this->course_post ) || ( ! is_a( $this->course_post, 'WP_Post' ) ) || ( 'sfwd-courses' !== $this->course_post->post_type ) ) {
						return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid Course ID.', 'learndash' ), array( 'status' => 404 ) );
					}
				}

				if ( ! learndash_is_admin_user() ) {
					if ( ! $this->course_post ) {
						return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid Course ID.', 'learndash' ), array( 'status' => 404 ) );
					} else if ( ! sfwd_lms_has_access( $this->course_post->ID ) ) {
						return new WP_Error( 'ld_rest_cannot_view', __( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					} 
				}
			}

			return $return;
		}

		function get_items( $request ) {
			return parent::get_items( $request );
		}

		function rest_query_filter( $args, $request ) {

			// The course_post should be set in the local method get_items_permissions_check()
			if ( ( $this->course_post ) && ( is_a( $this->course_post, 'WP_Post' ) ) && ( 'sfwd-courses' === $this->course_post->post_type ) ) {
				$step_ids = learndash_course_get_steps_by_type( $this->course_post->ID, $this->post_type );
				if ( ! empty( $step_ids ) ) {
					$args['post__in'] = $args[ 'post__in' ] ? array_intersect( $step_ids, $args[ 'post__in' ] ) : $step_ids;

					$course_lessons_args = learndash_get_course_lessons_order( $this->course_post->ID );
					if ( !isset( $_GET[ 'orderby' ] ) ) {
						if ( isset( $course_lessons_args[ 'orderby' ] ) )
							$args['orderby'] = $course_lessons_args['orderby'];
						else
							$args['orderby'] = 'title';
					}

					if ( !isset( $_GET['order'] ) ) {
						if ( isset( $course_lessons_args[ 'order' ] ) )
							$args['order'] = $course_lessons_args['order'];
						else
							$args['order'] = 'ASC';
					}
				} else {
					$args['post__in'] = array(0 );
				}
			}

			return $args;
		}

		// End of functions
	}
}
