<?php
if ( ( !class_exists( 'LD_REST_Users_Course_Progress_Controller_V1' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V1' ) ) ) {
	class LD_REST_Users_Course_Progress_Controller_V1 extends LD_REST_Posts_Controller_V1 {

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
				'/' . $this->rest_base . '/(?P<id>[\d]+)/course-progress/', 
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
						'callback'            => array( $this, 'get_users_progress' ),
						'permission_callback' => array( $this, 'get_users_progress_permissions_check' ),
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

		function get_users_progress_permissions_check( $request ) {
			$user_id = $request['id'];
			
			if ( learndash_is_admin_user() ) {
				return true;
			} else if ( get_current_user_id() === $user_id ) {
				return true;
			} else if ( learndash_is_group_leader_user() ) {
				if ( learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) {
					return true;
				}
			}
		}

		function get_users_progress( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid user ID. #1', 'learndash' ), array( 'status' => 404 ) );
			}

			if ( is_user_logged_in() )
				$current_user_id = get_current_user_id();
			else
				$current_user_id = 0;

			$data = array();

			$user_course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
			$user_course_progress = !empty( $user_course_progress ) ? $user_course_progress : array();

			$courses_registered = ld_get_mycourses( $user_id );
			$courses_registered = !empty( $courses_registered ) ? $courses_registered : array();

			$user_course_ids = array_keys( $user_course_progress );
			$user_course_ids = array_merge( $user_course_ids, $courses_registered );
			$user_course_ids = array_unique( $user_course_ids );

			if ( ( !empty( $user_course_ids ) ) && ( learndash_is_group_leader_user() ) ) {
				$gl_groups_corses = learndash_get_group_leader_groups_courses( get_current_user_id() );
				error_log('gl_groups_corses<pre>'. print_r($gl_groups_corses, true) .'</pre>');

				if ( !empty( $gl_groups_corses ) ) {
					$user_course_ids = array_intersect( $gl_groups_corses, $user_course_ids );
				}
			}

			if ( !empty( $user_course_ids ) ) {

				// Ensure a search string is set in case the orderby is set to 'relevance'.
				if ( ! empty( $request['orderby'] ) && 'relevance' === $request['orderby'] && empty( $request['search'] ) ) {
					return new WP_Error( 'rest_no_search_term_defined', __( 'You need to define a search term to order by relevance.', 'learndash' ), array( 'status' => 400 ) );
				}

				// Ensure an include parameter is set in case the orderby is set to 'include'.
				if ( ! empty( $request['orderby'] ) && 'include' === $request['orderby'] && empty( $request['include'] ) ) {
					return new WP_Error( 'rest_orderby_include_missing_include', __( 'You need to define an include parameter to order by include.', 'learndash' ), array( 'status' => 400 ) );
				}

				// Retrieve the list of registered collection query parameters.
				$registered = $this->get_collection_params();
				$args       = array();

				/*
				 * This array defines mappings between public API query parameters whose
				 * values are accepted as-passed, and their internal WP_Query parameter
				 * name equivalents (some are the same). Only values which are also
				 * present in $registered will be set.
				 */
				$parameter_mappings = array(
					'author'         => 'author__in',
					'author_exclude' => 'author__not_in',
					'exclude'        => 'post__not_in',
					'include'        => 'post__in',
					'menu_order'     => 'menu_order',
					'offset'         => 'offset',
					'order'          => 'order',
					'orderby'        => 'orderby',
					'page'           => 'paged',
					'parent'         => 'post_parent__in',
					'parent_exclude' => 'post_parent__not_in',
					'search'         => 's',
					'slug'           => 'post_name__in',
					'status'         => 'post_status',
				);

				/*
				 * For each known parameter which is both registered and present in the request,
				 * set the parameter's value on the query $args.
				 */
				foreach ( $parameter_mappings as $api_param => $wp_param ) {
					if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
						$args[ $wp_param ] = $request[ $api_param ];
					}
				}

				// Check for & assign any parameters which require special handling or setting.
				$args['date_query'] = array();

				// Set before into date query. Date query must be specified as an array of an array.
				if ( isset( $registered['before'], $request['before'] ) ) {
					$args['date_query'][0]['before'] = $request['before'];
				}

				// Set after into date query. Date query must be specified as an array of an array.
				if ( isset( $registered['after'], $request['after'] ) ) {
					$args['date_query'][0]['after'] = $request['after'];
				}

				// Ensure our per_page parameter overrides any provided posts_per_page filter.
				if ( isset( $registered['per_page'] ) ) {
					$args['posts_per_page'] = $request['per_page'];
				}

				if ( isset( $registered['sticky'], $request['sticky'] ) ) {
					$sticky_posts = get_option( 'sticky_posts', array() );
					if ( ! is_array( $sticky_posts ) ) {
						$sticky_posts = array();
					}
					if ( $request['sticky'] ) {
						/*
						 * As post__in will be used to only get sticky posts,
						 * we have to support the case where post__in was already
						 * specified.
						 */
						$args['post__in'] = $args['post__in'] ? array_intersect( $sticky_posts, $args['post__in'] ) : $sticky_posts;

						/*
						 * If we intersected, but there are no post ids in common,
						 * WP_Query won't return "no posts" for post__in = array()
						 * so we have to fake it a bit.
						 */
						if ( ! $args['post__in'] ) {
							$args['post__in'] = array( 0 );
						}
					} elseif ( $sticky_posts ) {
						/*
						 * As post___not_in will be used to only get posts that
						 * are not sticky, we have to support the case where post__not_in
						 * was already specified.
						 */
						$args['post__not_in'] = array_merge( $args['post__not_in'], $sticky_posts );
					}
				}

				// Force the post_type argument, since it's not a user input variable.
				$args['post_type'] = $this->post_type;
				$args['post__in'] = $user_course_ids;
				$args['fields'] = 'ids';
				
				/**
				 * Filters the query arguments for a request.
				 *
				 * Enables adding extra arguments or setting defaults for a post collection request.
				 *
				 * @since 4.7.0
				 *
				 * @link https://developer.wordpress.org/reference/classes/wp_query/
				 *
				 * @param array           $args    Key value array of query var to query value.
				 * @param WP_REST_Request $request The request used.
				 */
				$args       = apply_filters( "learndash_rest_users_course_progress_query", $args, $request );
				$query_args = $this->prepare_items_query( $args, $request );

				$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

				foreach ( $taxonomies as $taxonomy ) {
					$base        = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
					$tax_exclude = $base . '_exclude';

					if ( ! empty( $request[ $base ] ) ) {
						$query_args['tax_query'][] = array(
							'taxonomy'         => $taxonomy->name,
							'field'            => 'term_id',
							'terms'            => $request[ $base ],
							'include_children' => false,
						);
					}

					if ( ! empty( $request[ $tax_exclude ] ) ) {
						$query_args['tax_query'][] = array(
							'taxonomy'         => $taxonomy->name,
							'field'            => 'term_id',
							'terms'            => $request[ $tax_exclude ],
							'include_children' => false,
							'operator'         => 'NOT IN',
						);
					}
				}
				error_log('query_args<pre>'. print_r($query_args, true) .'</pre>');
				
				$posts_query  = new WP_Query();
				$query_result = $posts_query->query( $query_args );
				error_log('query_result<pre>'. print_r($query_result, true) .'</pre>');
				
				
				// Allow access to all password protected posts if the context is edit.
				if ( 'edit' === $request['context'] ) {
					add_filter( 'post_password_required', '__return_false' );
				}

				foreach ( $query_result as $course_id ) {
					$data[$course_id] = array();
					
					if ( isset( $user_course_progress[$course_id] ) ) {
						$converted = $this->user_meta_progress_normalized( $user_course_progress[$course_id] );
					} else {
						$converted = array();
					}
				
					$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
					$ld_course_steps_object->load_steps();
					$course_steps_l = $ld_course_steps_object->get_steps( 'l' );
					if ( !empty( $course_steps_l ) ) {
						foreach( $course_steps_l as $step_key ) {
							list( $step_type, $step_id ) = explode( ':', $step_key );
							if ( isset( $converted[$step_key] ) ) {
								$completed = $converted[$step_key];
							} else {
								$completed = 0;
							}
							$data[$course_id][$step_id] = $completed;
						}
					}
				}
				
				// Reset filter.
				if ( 'edit' === $request['context'] ) {
					remove_filter( 'post_password_required', '__return_false' );
				}

				$page        = (int) $query_args['paged'];
				$total_posts = $posts_query->found_posts;

				if ( $total_posts < 1 ) {
					// Out-of-bounds, run the query again without LIMIT for total count.
					unset( $query_args['paged'] );

					$count_query = new WP_Query();
					$count_query->query( $query_args );
					$total_posts = $count_query->found_posts;
				}

				$max_pages = ceil( $total_posts / (int) $posts_query->query_vars['posts_per_page'] );

				if ( $page > $max_pages && $total_posts > 0 ) {
					return new WP_Error( 'rest_post_invalid_page_number', __( 'The page number requested is larger than the number of pages available.', 'learndash' ), array( 'status' => 400 ) );
				}

				$response = rest_ensure_response( $data );

				$response->header( 'X-WP-Total', (int) $total_posts );
				$response->header( 'X-WP-TotalPages', (int) $max_pages );

				$request_params = $request->get_query_params();
				$base           = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

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
			return $response;
		}
		
		function user_meta_progress_normalized( $progress = array() ) {
			$converted = array();
			
			if ( ( isset( $progress['lessons'] ) ) && ( !empty( $progress['lessons'] ) ) ) {
				foreach( $progress['lessons']  as $lesson_id => $lesson_complete ) {
					$converted['sfwd-lessons:' . $lesson_id] = $lesson_complete;
					if ( ( isset( $progress['topics'][$lesson_id] ) ) && ( !empty( $progress['topics'][$lesson_id] ) ) ) {
						foreach( $progress['topics'][$lesson_id]  as $topic_id => $topic_complete ) {
							$converted['sfwd-topic:' . $topic_id] = $topic_complete;
						}
					}
				}
			}
			//error_log('converted<pre>'. print_r($converted, true) .'</pre>');
			
			return $converted;
		}
		
		
		/*
		function set_items( $request ) {
			$data = array();

			// Create the response object
			$response = rest_ensure_response( $data );

			// Add a custom status code
			$response->set_status( 200 );

			return $response;
		}
		*/

		/*
		function lesson_mark_complete( $request ) {
			$course_id = $request['course'];
			$lesson_id = $request['id'];
			if ( empty( $course_id ) ) {
				return new WP_Error( 'rest_post_invalid_id_X', esc_html__( 'Invalid Course ID.', 'learndash' ), array( 'status' => 404 ) );
			}
			
			if ( empty( $lesson_id ) ) {
				return new WP_Error( 'rest_post_invalid_id_Y', esc_html__( 'Invalid Lesson ID.', 'learndash' ), array( 'status' => 404 ) );
			}

			$current_user_id = get_current_user_id();
			if ( empty( $current_user_id ) ) {
				return new WP_Error( 'rest_not_logged_in', esc_html__( 'You are not currently logged in.', 'learndash' ), array( 'status' => 401 ) );
			}
			//$current_user = wp_get_current_user();

			$has_access = sfwd_lms_has_access( $course->ID, $current_user->ID );
			if ( ( ! $has_access ) && ( $course_price_type != 'open' ) ) {
				return new WP_Error( 'rest_cannot_view', esc_html__( 'Sorry, you are not allowed view items.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}

			$return = learndash_process_mark_complete( $current_user_id, $lesson_id );
			if ( $return === true ) {
				$data = array( 
					'completed_status' => true,
					'completed_date_gmt' => $this->prepare_date_response( current_time( 'mysql' ) ) 
				);

				// Create the response object
				$response = rest_ensure_response( $data );

				// Add a custom status code
				$response->set_status( 200 );

				return $response;
			}
		}
		*/
	}
}
