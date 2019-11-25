<?php
if ( ( !class_exists( 'LD_REST_Users_Quiz_Attempts_Controller_V1' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V1' ) ) ) {
	class LD_REST_Users_Quiz_Attempts_Controller_V1 extends LD_REST_Posts_Controller_V1 {

		private $supported_collection_params = array(
			'offset'	=> 'offset',
			'order'		=> 'order',
			//'orderby'	=> 'orderby',
			'per_page'	=> 'posts_per_page',
			'page'		=> 'paged',
			'search'	=> 's',
		);

		public function __construct( ) {
			$this->post_type = 'sfwd-courses';
			$this->taxonomies = array();
			
			parent::__construct( $this->post_type );
			$this->namespace = LEARNDASH_REST_API_NAMESPACE .'/'. $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_REST_API', 'users' );
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

			register_rest_route( 
				$this->namespace, 
				'/' . $this->rest_base . '/(?P<id>[\d]+)/quiz-attempts/', 
				array(
					'args' => array(
						'id' => array(
							'description' => esc_html__( 'User ID to show course progress', 'learndash' ),
    							'required' => true,
							'type' => 'integer',
						),
					),
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_quiz_attempts' ),
						'permission_callback' => array( $this, 'get_quiz_attempts_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
				) 
			);
			/*
			register_rest_route( 
				$this->namespace, 
				'/' . $this->rest_base . '/(?P<id>[\d]+)/course-progress/(?P<course_id>[\d]+)', 
				array(
					'args' => array(
						'id' => array(
							'description' => esc_html__( 'User ID to enroll user into.', 'learndash' ),
    							'required' => true,
							'type' => 'integer',
						),
						'course_id' => array(
							'description' => esc_html__( 'Course ID to enroll.', 'learndash' ),
    							'required' => false,
								'items'             => array(
									'type'          => 'integer',
								),
						),
					),
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'set_items' ),
					),
				) 
			);
			*/
		}
		
		function get_quiz_attempts_permissions_check( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid user ID. #1', 'learndash' ), array( 'status' => 404 ) );
			}

			if ( is_user_logged_in() )
				$current_user_id = get_current_user_id();
			else
				$current_user_id = 0;

			if ( empty( $current_user_id ) ) {
				if ( ! current_user_can( 'edit_user', $user_id ) ) {
					return new WP_Error( 'rest_user_invalid_id', __( 'Invalid user ID.', 'learndash' ), array( 'status' => 404 ) );
				}
			}

			if ( ( $user_id != $current_user_id ) && ( ! learndash_is_admin_user( $current_user_id ) ) ) {
				if ( ! current_user_can( 'edit_user', $user_id ) ) {
					return new WP_Error( 'rest_cannot_edit', __( 'Sorry, you are not allowed to edit this user.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
				}
			}
			
			return true;
		}
		
		
		function get_quiz_attempts( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid user ID. #1', 'learndash' ), array( 'status' => 404 ) );
			}
			
			// Retrieve the list of registered collection query parameters.
			$registered = $this->get_collection_params();
			$args       = array();


			/*
			 * For each known parameter which is both registered and present in the request,
			 * set the parameter's value on the query $args.
			 */
			foreach ( $this->supported_collection_params as $api_param => $wp_param ) {
				if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
					$args[ $wp_param ] = $request[ $api_param ];
				}
			}
			//error_log( 'args<pre>'. print_r( $args, true ) .'</pre>' );
			
			$atts = array(
				'return' 		=> true,
				'type' 			=> array( 'quiz' ), 
				'quiz_num' 		=> $args['posts_per_page'],
				'quiz_orderby' 	=> 'taken',
				'quiz_order' 	=> 'DESC'
			);
			
			//$atts = apply_filters('learndash_profile_course_info_atts', $atts, $user );
			
			$course_info = SFWD_LMS::get_course_info( $user_id, $atts );
			//error_log('course_info<pre>'. print_r($course_info, true) .'</pre>');
	
			if ( ( isset( $course_info['quizzes'] ) ) && ( !empty( $course_info['quizzes'] ) ) ) {
				$course_info['quizzes'] = array_values( $course_info['quizzes'] );
				// Need to convert the timestamp integer value to proper YYYY-MM-DD HH:MM:SS values for response. 
				foreach( $course_info['quizzes'] as &$quiz ) {
					if ( ( isset( $quiz['time'] ) ) && ( !empty( $quiz['time'] ) ) ) {
						$quiz['time'] = $this->prepare_date_response( date('Y-m-d h:i:s', $quiz['time'] ) );
					}

					if ( ( isset( $quiz['m_edit_time'] ) ) && ( !empty( $quiz['m_edit_time'] ) ) ) {
						$quiz['m_edit_time'] = $this->prepare_date_response( date('Y-m-d h:i:s', $quiz['m_edit_time'] ) );
					}
				}
				$response = rest_ensure_response( $course_info['quizzes'] );

				if ( isset( $course_info['quizzes_pager'] ) ) {
					$response->header( 'X-WP-Total', (int) $course_info['quizzes_pager']['total_items'] );
					$response->header( 'X-WP-TotalPages', (int) $course_info['quizzes_pager']['total_pages'] );

					$request_params = $request->get_query_params();
					$base           = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

					$max_pages = (int) $course_info['quizzes_pager']['total_pages'];
					$page = 	(int) $course_info['quizzes_pager']['paged'];

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
				}
			} else {
				$response = rest_ensure_response( array() );
			}
			
			return $response;
		}
		
		public function get_collection_params() {
			$query_params_default = parent::get_collection_params();
			//error_log('query_params_default<pre>'. print_r($query_params_default, true) .'</pre>');
			
			$query_params_default['context']['default'] = 'view';
			
			$query_params = array();
			$query_params['context'] = $query_params_default['context'];

			/*
			$query_params['include'] = array(
				'description' 	=> __('Fitler results by quiz IDs', 'learndash' ),
				'required'		=> false,
				'type' 			=> 'array',
				'default'		=> [],
				'items'			=> array(
					'type' => 'integer'
				)
			);
			*/
			
			$query_params['orderby']['default'] = 'taken';
			$query_params['orderby']['enum'] = array(
				'taken',
				'title',
				'id',
				'date',
				'menu_order'
			);
			/*
			$query_params['course'] = array(
				'description' 	=> __('Fitler results by course ID', 'learndash' ),
				'required'		=> false,
				'type' 			=> 'integer',
			);
			*/
			foreach( $this->supported_collection_params as $external_key => $internal_key ) {
				if ( isset( $query_params_default[$external_key] ) ) {
					$query_params[$external_key] = $query_params_default[$external_key];
				}
			}
			return $query_params;
		}
		
		// End of functions
	}
}
