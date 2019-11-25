<?php
if ( ( !class_exists( 'LD_REST_Courses_Steps_Controller_V1' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V1' ) ) ) {
	class LD_REST_Courses_Steps_Controller_V1 extends LD_REST_Posts_Controller_V1 {
		
		private $supported_collection_params = array(
			'filter'	=> 'filter'
		);

		public function __construct( $post_type = '' ) {
			$this->post_type = 'sfwd-courses';
			$this->taxonomies = array();
			
			parent::__construct( $this->post_type );
			$this->namespace = LEARNDASH_REST_API_NAMESPACE .'/'. $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_REST_API', 'sfwd-courses' );
		}
		
	    public function register_routes() {
			$this->register_fields();

			//parent::register_routes_wpv2( );
									
			//$collection_params = $this->get_collection_params();
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
				'/' . $this->rest_base . '/(?P<id>[\d]+)/steps', 
				array(
					'args' => array(
						'id' => array(
							'description' => esc_html__( 'Course ID to enroll user into.', 'learndash' ),
							'required' => true,
							'type' => 'integer',
						),
					),
					array(
						'methods'             	=> WP_REST_Server::READABLE,
						'callback'            	=> array( $this, 'get_course_steps' ),
						'permission_callback' 	=> array( $this, 'get_course_steps_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             	=> WP_REST_Server::EDITABLE,
						'callback'            	=> array( $this, 'update_course_steps' ),
						'permission_callback' 	=> array( $this, 'update_course_steps_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
				) 
			);
	    }
		
		public function get_collection_params() {
			$query_params_default = parent::get_collection_params();
						
			$query_params_default['context']['default'] = 'view';
			
			$query_params = array();
			$query_params['context'] = $query_params_default['context'];
			$query_params['type'] = array(
				'description' => __('Filter returned results by step type.', 'learndash' ),
				'type'        => 'string',
				'default'     => 'all',
				'enum'        => array(
					'all',
					'h',
					'l',
					't',
					'r',
				),				
			);

			return $query_params;
		}
		
		function get_course_steps_permissions_check( $request ) {
			if ( is_user_logged_in() ) {
				if ( learndash_is_admin_user( ) ) {
					if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) {
						return true;
					}
				}
				
				$this->enrolled_courses = learndash_user_get_enrolled_courses( get_current_user_id() );
				
				// Ensure the user has some courses.
				if ( !empty( $this->enrolled_courses ) ) {
					
					/// Secondary check if they are wanting steps for a specific course ID. 
					$course_id = $request['id'];
					if ( !empty( $course_id ) ) {
						// And if that course ID is in their enrolled courses. 
						if ( in_array( $course_id, $this->enrolled_courses ) ) {
							$this->enrolled_courses = array( $course_id );
							return true;
						}
					} else {
						// If user has enrolled courses but not requesting a specific course then good to go.
						return true;
					}
				}
			}
		}
								
		function get_course_steps( $request ) {
			$current_user_id = get_current_user_id();
			if ( empty( $current_user_id ) ) {
				return new WP_Error( 'rest_not_logged_in', esc_html__( 'You are not currently logged in.', 'learndash' ), array( 'status' => 401 ) );
			}
			$current_user = wp_get_current_user();

			$course = $this->get_post( $request['id'] );
			if ( is_wp_error( $course ) ) {
				return $course;
			}

			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course->ID ) );
			$course_steps = $ld_course_steps_object->get_steps( $request['type'] );
			
			$data = $course_steps;

			// Create the response object
			$response = rest_ensure_response( $data );

			// Add a custom status code
			$response->set_status( 200 );

			return $response;
		}

		function update_course_steps_permissions_check( $request ) {
			if ( learndash_is_admin_user( ) ) {
				return true;
			}
		}
		function update_course_steps( $request ) {
			$current_user_id = get_current_user_id();
			if ( empty( $current_user_id ) ) {
				return new WP_Error( 'rest_not_logged_in', esc_html__( 'You are not currently logged in.', 'learndash' ), array( 'status' => 401 ) );
			}
			$current_user = wp_get_current_user();
			
			$course = $this->get_post( $request['id'] );
			if ( is_wp_error( $course ) ) {
				return $course;
			}
			
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course->ID ) );
			
			$body = $request->get_body();
			if ( !empty( $body ) ) {
				$body = json_decode( $body, true );
				if ( ( $body ) && ( json_last_error() == JSON_ERROR_NONE ) ) {
					$steps = array();
					
					$steps['sfwd-lessons'] = array();
					$steps['sfwd-quiz'] = array();
					
					if ( ( isset( $body['sfwd-lessons'] ) ) && ( !empty( $body['sfwd-lessons'] ) ) ) {
						foreach( $body['sfwd-lessons'] as $lesson_id => $lesson_set ) {
							$steps['sfwd-lessons'][$lesson_id] = array();
							$steps['sfwd-lessons'][$lesson_id]['sfwd-topic'] = array();
							$steps['sfwd-lessons'][$lesson_id]['sfwd-quiz'] = array();
							
							if ( ( isset( $lesson_set['sfwd-topic'] ) ) && ( !empty( $lesson_set['sfwd-topic'] ) ) ) {
								
								foreach( $lesson_set['sfwd-topic'] as $topic_id => $topic_set ) {
									$steps['sfwd-lessons'][$lesson_id]['sfwd-topic'][$topic_id] = array();
									$steps['sfwd-lessons'][$lesson_id]['sfwd-topic'][$topic_id]['sfwd-quiz'] = array();
									
									if ( ( isset( $topic_set['sfwd-quiz'] ) ) && ( !empty( $topic_set['sfwd-quiz'] ) ) ) {
										foreach( $topic_set['sfwd-quiz'] as $quiz_id => $quiz_set ) {
											$steps['sfwd-lessons'][$lesson_id]['sfwd-topic'][$topic_id]['sfwd-quiz'][$quiz_id] = array();
										}
									} 
								}
							}

							if ( ( isset( $lesson_set['sfwd-quiz'] ) ) && ( !empty( $lesson_set['sfwd-quiz'] ) ) ) {
								foreach( $lesson_set['sfwd-quiz'] as $quiz_id => $quiz_set ) {
									$steps['sfwd-lessons'][$lesson_id]['sfwd-quiz'][$quiz_id] = array();
								}
							}
						}
					}
					
					if ( ( isset( $body['sfwd-quiz'] ) ) && ( !empty( $body['sfwd-quiz'] ) ) ) {
						$steps['sfwd-quiz'] = $body['sfwd-quiz'];
					}

					$ld_course_steps_object->set_steps( $steps );
				} 
			}
			
			$ld_course_steps_object->load_steps();
			$course_steps = $ld_course_steps_object->get_steps( 'h' );
			$data = $course_steps;

			// Create the response object
			$response = rest_ensure_response( $data );

			// Add a custom status code
			$response->set_status( 200 );

			return $response;
		}
		
		// End of functions
	}
}
