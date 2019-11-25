<?php
/**
 * Learndash Reports functions
 * 
 * @since 2.3
 * 
 * @package LearnDash\Reports
 */



/**
 * Utility function to query Users
 * This function is basically a wrapper to the WP_User_Query functions provided by WP
 *
 * @param  array 	$query_args override query arguments
 * @return mixed   	query results Depends on the 'fields' parameter.
 * 
 * @since 2.3
 */
function learndash_get_users_query( $query_args = array() ) {
	
	$default_args = array(
		'fields' =>	'ID'
	);
	
	$query_args = wp_parse_args( $query_args, $default_args );
	$query_args = apply_filters('learndash_get_users_query_args', $query_args);
	if ( !empty( $query_args ) ) {
		$user_query = new WP_User_Query( $query_args );
		return $user_query->get_results();
	}
}

/**
 * Returns an array of user_id to report on. 
 * This function will determined the user the viewing user can see. For example if 
 * group leaser it will shwo only user within the leaders groups. If admin will 
 * show all users
 *
 * @param  int  	$user_id optional user_id to use as source. Default is current user id
 * @param  bool  	$include_admin_users flag to include administrator users in return. Default is false
 * @return array   	array of user_ids
 * 
 * @since 2.3
 */

function learndash_get_report_user_ids( $user_id = 0, $query_args = array() ) {
	if ( empty( $user_id ) ) {
		// If the current user is not able to be determined. Then abort. 
		if ( !is_user_logged_in() ) return;
		
		$user_id = get_current_user_id();
	}

	$default_args = array(
		'fields' 		=>	'ID',
		'orderby'		=>	'display_name',
		'order'			=>	'ASC'
	);
	
	$query_args = wp_parse_args( $query_args, $default_args );

	if ( learndash_is_admin_user( $user_id ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'reports_include_admin_users' ) != 'yes' ) {
			$query_args['role__not_in'] = 'administrator';
		}
	} else if (learndash_is_group_leader_user( $user_id ) ) {
		$include_user_ids = learndash_get_group_leader_groups_users( $user_id );
		
		// Even though we have the users ids from the learndash_get_group_leader_groups_users() we need to validate them
		// by running them against the WP_User_Query 
		if (!empty( $include_user_ids ) )
			$query_args['include'] = $include_user_ids;
	} else {
		$query_args['include'] = array( $user_id );
	} 

	$query_args = apply_filters('learndash_get_report_users_query_args', $query_args);
	$report_user_ids = learndash_get_users_query( $query_args );
	return apply_filters('learndash_get_report_user_ids', $report_user_ids);
}


function learndash_get_report_user_ids_NEW_PP21( $user_id = 0, $query_args = array() ) {
	if ( empty( $user_id ) ) {
		// If the current user is not able to be determined. Then abort. 
		if ( !is_user_logged_in() ) return array();
		
		$user_id = get_current_user_id();
	}

	$default_args = array(
		'fields' 		=>	'ID',
		'orderby'		=>	'display_name',
		'order'			=>	'ASC'
	);
		
	$query_args = wp_parse_args( $query_args, $default_args );

	if ( learndash_is_group_leader_user( $user_id ) ) {

		$include_user_ids = learndash_get_group_leader_groups_users( $user_id );
		
		// Even though we have the users ids from the learndash_get_group_leader_groups_users() we need to validate them
		// by running them against the WP_User_Query 
		if (!empty( $include_user_ids ) )
			$query_args['include'] = $include_user_ids;
		else 
			$query_args = array();
		
	} else if ( learndash_is_admin_user( $user_id ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'reports_include_admin_users' ) != 'yes' ) {
			$query_args['role__not_in'] = 'administrator';
		}

	} else {
		$query_args['include'] = array( $user_id );
	}

	if ( !empty( $query_args ) ) {
		$query_args = apply_filters( 'learndash_get_report_users_query_args', $query_args );
		$report_user_ids = learndash_get_users_query( $query_args );
		$report_user_ids = apply_filters('learndash_get_report_user_ids', $report_user_ids );
	} else {
		$report_user_ids = array();
	}
	
	if ( version_compare( '2.0.2', LD_PP_VERSION, '<=' ) ) {
		return $report_user_ids;
	} else {
		
		$return  = array(
			'user_ids_action' => 'IN',
			'user_ids' => $report_user_ids
		);

		if ( learndash_is_admin_user( $user_id ) ) {
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'reports_include_admin_users' ) != 'yes' ) {
				$return['user_ids_action'] = 'NOT IN';
			}
		}
	}
	
	return $return;
}

	
/**
 * Get count of active / pubished Courses
 *
 * @param  array 	$courses_query_args override query arguments
 * @param  string 	$return_field specific field from WP_Query to return. Default is 'found_posts'
 * @return mixed   	$courses_return if $return_field is empty then return is WP_Query instance. Otherwise specific field from WP_Query returned
 * 
 * @since 2.3
 */
function learndash_get_courses_count( $query_args = array(), $return_field = 'found_posts' ) {
	$return = 0;
	
	$default_args = array(
		'post_type'			=>	'sfwd-courses',
		'post_status'		=>	'publish',
		'fields'			=>	'ids'
	);
	
	$query_args = wp_parse_args( $query_args, $default_args );
	$query_args = apply_filters('learndash_courses_count_args', $query_args);

	if ( $return_field == 'found_posts' ) {
		$query_args['posts_per_page'] = 1;
		$query_args['paged'] = 1;
	}
	
	if ( ( is_array( $query_args ) ) && ( !empty( $query_args ) ) ) {
		$query = new WP_Query( $query_args );
		if ( $query instanceof WP_Query) {
			if ( ( !empty( $return_field ) ) && ( property_exists( $query, $return_field ) ) ) {
				$return = $query->$return_field;
			} else {
				$return = $query;
			}
		}
	}
	
	return $return;
}


/**
 * Get count of pending Assignments posts ( sfwd-assignment )
 *
 * @param  array 	$assigments_query_args override query arguments
 * @param  string 	$return_field specific field from WP_Query to return. Default is 'found_posts'
 * @return mixed   	$assignments_return if $return_field is empty then return is WP_Query instance. Otherwise specific field from WP_Query returned
 * 
 * @since 2.3
 */
function learndash_get_assignments_pending_count( $query_args = array(), $return_field = 'found_posts' ) {
	$return = 0;

	$default_args = array(
		'post_type'		=>	'sfwd-assignment',
		'post_status'	=>	'publish',
		'fields'		=>	'ids',
		'meta_query' 	=> array(
			array(
				'key' 		=> 	'approval_status',
				'compare' 	=> 	'NOT EXISTS',
			),
		),
	);
	
	// added logic for non-admin user like group leaders who will only see a sub-set of assignments
	$user_id = get_current_user_id();
	if ( learndash_is_group_leader_user( $user_id ) ) {
		$group_ids = learndash_get_administrators_group_ids( $user_id );
		$user_ids = array();
		$course_ids = array();
		
		if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {			
			foreach( $group_ids as $group_id ) {
				$group_users = learndash_get_groups_user_ids( $group_id );

				if ( ! empty( $group_users ) && is_array( $group_users ) ) {
					foreach( $group_users as $group_user_id ) {
						$user_ids[ $group_user_id ] = $group_user_id;
					}
				}
				
				$group_course_ids = learndash_group_enrolled_courses( $group_id );
				if ( ( !empty( $group_course_ids ) ) && (is_array( $group_course_ids ) ) ) {
					$course_ids = array_merge( $course_ids, $group_course_ids );
				}
				
			}
		} else {
			return $return;
		}
		
		if ( ! empty( $course_ids ) && count( $course_ids ) ) {
			$default_args['meta_query'][] = array(
				'key'     => 'course_id',
				'value'   => $course_ids,
				'compare' => 'IN',
			);
		} else {
			return $return;
		}
	
		if ( ! empty( $user_ids ) && count( $user_ids ) ) {
			$default_args['author__in'] = $user_ids;
		} else {
			return $return;
		}
	}
	
	$query_args = wp_parse_args( $query_args, $default_args );
	$query_args = apply_filters( 'learndash_get_assignments_pending_count_query_args', $query_args );

	if ( $return_field == 'found_posts' ) {
		$query_args['posts_per_page'] = 1;
		$query_args['paged'] = 1;
	}

	if ( ( is_array( $query_args ) ) && ( !empty( $query_args ) ) ) {
		$query = new WP_Query( $query_args );

		if ( ( !empty( $return_field ) ) && ( property_exists( $query, $return_field ) ) ) {
			$return = $query->$return_field;
		} else {
			$return = $query;
		}
	}	
	
	return $return;
}

/**
 * Get link to admin Assignments posts ( sfwd-assignment ) listing
 *
 * @param  array 	$link_args to override or supplement query string
 * @return string   URL to assignment admin page with filters
 * 
 * @since 2.3
 */
function learndash_admin_get_assignments_listing_link( $link_args = array() ) {

	$default_args = array(
		'post_type'			=>	'sfwd-assignment',
		'post_status'		=>	'all',
	);

	$link_args = wp_parse_args( $link_args, $default_args );

	// Just in case someone tried to insert action/actions triggers. Remove them
	if ( isset( $link_args['action'] ) ) unset( $link_args['action'] );
	if ( isset( $link_args['action2'] ) ) unset( $link_args['action2'] );

	// Then remove any empty items. Less URL space.
	foreach( $link_args as $l_key => $l_val ) {
		if ( $l_val == '' ) unset( $link_args[$l_key] );
	}

	if ( !empty( $link_args ) ) {
		return add_query_arg( $link_args, admin_url( 'edit.php' ) );
	}
}

/**
 * Get link to admin pending Assignments posts ( sfwd-assignment ) listing
 *
 * @param  none
 * @return string   URL to assignment admin page with filters
 * 
 * @since 2.3
 */
function learndash_admin_get_assignments_pending_listing_link() {
	return learndash_admin_get_assignments_listing_link( 'approval_status=0' );
}


/**
 * Get count of pending Essays posts ( sfwd-essays )
 *
 * @param  array 	$essays_query_args override query arguments
 * @param  string 	$return_field specific field from WP_Query to return. Default is 'found_posts'
 * @return mixed   	$assignments_return if $return_field is empty then return is WP_Query instance. Otherwise specific field from WP_Query returned
 * 
 * @since 2.3
 */
function learndash_get_essays_pending_count( $query_args = array(), $return_field = 'found_posts' ) {
	$return = 0;

	$default_args = array(
		'post_type'		=>	'sfwd-essays',
		'post_status'	=>	'not_graded',
		'fields'		=>	'ids',
	);
	
	// added logic for non-admin user like group leaders who will only see a sub-set of assignments
	$user_id = get_current_user_id();
	if ( learndash_is_group_leader_user( $user_id ) ) {
		$group_ids = learndash_get_administrators_group_ids( $user_id );
		$user_ids = array();
		$course_ids = array();

		if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {
			foreach( $group_ids as $group_id ) {
				$group_users = learndash_get_groups_user_ids( $group_id );

				if ( ! empty( $group_users ) && is_array( $group_users ) ) {
					foreach( $group_users as $group_user_id ) {
						$user_ids[ $group_user_id ] = $group_user_id;
					}
				}

				$group_course_ids = learndash_group_enrolled_courses( $group_id );
				if ( ( !empty( $group_course_ids ) ) && (is_array( $group_course_ids ) ) ) {
					$course_ids = array_merge( $course_ids, $group_course_ids );
				}
			}
		} else {
			return $return;
		}
	
		if ( ! empty( $course_ids ) && count( $course_ids ) ) {
			$default_args['meta_query'][] = array(
				'key'     => 'course_id',
				'value'   => $course_ids,
				'compare' => 'IN',
			);
		} else {
			return $return;
		}
	
		if ( ! empty( $user_ids ) && count( $user_ids ) ) {
			$default_args['author__in'] = $user_ids;
		} else {
			return $return;
		}
	}
	
	$query_args = wp_parse_args( $query_args, $default_args );
	$query_args = apply_filters( 'learndash_get_essays_pending_count_query_args', $query_args );

	if ( $return_field == 'found_posts' ) {
		$query_args['posts_per_page'] = 1;
		$query_args['paged'] = 1;
	}

	if ( ( is_array( $query_args ) ) && ( !empty( $query_args ) ) ) {
		$query = new WP_Query( $query_args );
		
		if ( ( !empty( $return_field ) ) && ( property_exists( $query, $return_field ) ) ) {
			$return = $query->$return_field;
		} else {
			$return = $query;
		}
	}	
	
	return $return;
}

/**
 * Get link to admin Essays posts ( sfwd-essays ) listing
 *
 * @param  array 	$link_args to override or supplement query string
 * @return string   URL to essays admin page with filters
 * 
 * @since 2.3
 */
function learndash_admin_get_essays_listing_link( $link_args = array() ) {

	$default_args = array(
		'post_type'			=>	'sfwd-essays',
		'post_status'		=>	'all',
	);

	$link_args = wp_parse_args( $link_args, $default_args );

	// Just in case someone tried to insert action/actions triggers. Remove them
	if ( isset( $link_args['action'] ) ) unset( $link_args['action'] );
	if ( isset( $link_args['action2'] ) ) unset( $link_args['action2'] );

	// Then remove any empty items. Less URL space.
	foreach( $link_args as $l_key => $l_val ) {
		if ( $l_val == '' ) unset( $link_args[$l_key] );
	}
	
	if ( !empty( $link_args ) )
		return add_query_arg( $link_args, admin_url( 'edit.php' ) );
}

/**
 * Get link to admin pending Essays posts ( sfwd-essays ) listing
 *
 * @param  none
 * @return string   URL to essays admin page with filters
 * 
 * @since 2.3
 */
function learndash_admin_get_essays_pending_listing_link() {
	return learndash_admin_get_essays_listing_link( 'post_status=not_graded' );
}


/**
 * Get the count of users in the system. 
 *
 * This will automatically exclude the count of 'administrator' role
 *
 * @param  array $user_query_args Refer to WP_User_Quert query args for details
 *
 * @return int count of users (default).
 * 
 * @since 2.3
 */
function learndash_students_enrolled_count( $user_query_args = array() ) {
	
	$return_total_users = 0;
	
	$default_args = array(
		'role__not_in'	=>	'Administrator',
		'count_total'	=>	true,
		'fields'		=>	'ID'
	);
	$user_query_args = apply_filters('learndash_students_enrolled_count_qrgs', wp_parse_args( $user_query_args, $default_args ) );
	if ( !empty( $user_query_args ) ) {
		$user_query = new WP_User_Query( $user_query_args );

		$return_total_users = $user_query->get_total();
	}
	return $return_total_users;
}

function learndash_get_group_leader_groups_users( $user_id = 0, $by_group = false, $totals_only = false ) {
	
	if ($by_group == false) {
		if ($totals_only == true) {
			$user_ids = 0;
		} else {
			$user_ids = array();
		}
	} else {
		if ($totals_only == true) {
			$user_ids = array();
		} else {
			$user_ids = 0;
		}
	}
	
	if ( empty( $user_id ) ) {
		// If the current user is not able to be determined. Then abort. 
		if ( !is_user_logged_in() ) return;
		
		$user_id = get_current_user_id();
	}
	
	if ( learndash_is_group_leader_user( $user_id ) ) {
		
		$group_ids = learndash_get_administrators_group_ids( $user_id );
		//error_log('group_ids<pre>'. print_r($group_ids, true) .'</pre>');
		if ( !empty( $group_ids ) ) {
			
			foreach ( $group_ids as $group_id ) {
				$group_user_ids = learndash_get_groups_user_ids( $group_id );
				//error_log('group_user_ids<pre>'. print_r($group_user_ids, true) .'</pre>');
				
				if ($by_group == true) {
					if ( $totals_only == true) {
						$user_ids[$group_id] = count( $group_user_ids );
					} else {
						$user_ids[$group_id] = $group_user_ids;
					}
				} else {
					if ( $totals_only == true) {
						$user_ids += count( $group_user_ids );
					} else {
						$user_ids = array_merge( $user_ids, $group_user_ids );
					}
				}
			}
		}
	}
	
	if ( !empty( $user_ids ) ) {
		if ($by_group == false) {
			$user_ids = array_unique( $user_ids );
		}
	}

	return $user_ids;
	
}

function learndash_get_group_leader_groups_courses( $group_leader_user_id = 0, $by_group = false, $totals_only = false ) {
				
	if ($by_group == false) {
		if ($totals_only == true) {
			$course_ids = 0;
		} else {
			$course_ids = array();
		}
	} else {
		if ($totals_only == true) {
			$course_ids = array();
		} else {
			$course_ids = 0;
		}
	}

	if ( empty( $group_leader_user_id ) )
		$group_leader_user_id = get_current_user_id();
	
	if (learndash_is_group_leader_user( $group_leader_user_id ) ) {
		
		$group_ids = learndash_get_administrators_group_ids( $group_leader_user_id );
		
		if ( !empty( $group_ids ) ) {
			
			foreach ( $group_ids as $group_id ) {
				$group_course_ids = learndash_group_enrolled_courses( $group_id );
				
				if ($by_group == true) {
					if ( $totals_only == true) {
						$course_ids[$group_id] = count( $group_course_ids );
					} else {
						$course_ids[$group_id] = $group_course_ids;
					}
				} else {
					if ( $totals_only == true) {
						$course_ids += count( $group_course_ids );
					} else {
						$course_ids = array_merge( $course_ids, $group_course_ids );
					}
				}
			}
		}
	}
	
	if ( !empty( $course_ids ) ) {
		if ($by_group == false) {
			$course_ids = array_unique( $course_ids );
		}
	}

	return $course_ids;
	
}



/**
 * Activity Query main function
 *
 * This function will query the new learndash_course_user_activity table for user / Course Activity
 *
 * @param  array $query_args usd to override default query args
 * @param  int $current_user_id The user to run the query as. Will user current user id if not provided.
 *
 * @return array Returns query reqults. 
 * 
 * @since 2.3
 */
function learndash_reports_get_activity( $query_args = array(), $current_user_id = 0 ) {
	global $wpdb, $learndash_post_types;
	
	$activity_results = array();
	
	$ACTIVITY_STATUS_HAS_NULL = false;
	
	$defaults = array(
		// array or comma lst of group ids to use in query. Default is all groups
		'group_ids'						=>	'',
		
		// array or comma list of course.
		'course_ids'					=>	'',
		'course_ids_action'				=>	'IN',
		
		// array or comma list of course, lesson, topic, etc. Default is all posts
		'post_ids'						=>	'',
		'post_ids_action'				=>	'IN',
		
		// array or comma list of LD specific post types. See $learndash_post_types for possible values 
		'post_types'					=>	'',
		
		// array or comma list of user ids. Defaults to all user ids. 
		'user_ids'						=>	'',
		'user_ids_action'				=>	'IN',
		
		// An array of activity_type values to filter. Default is all types
		'activity_types'				=>	'',
		
		// An array of activity_status values to filter. Possible values 'NOT_STARTED' , 'IN_PROGRESS', 'COMPLETED'
		'activity_status'				=>	'',
		
		// controls number of items to return for request. Pass 0 for ALL items
		'per_page'						=>	10,
		
		// Used in combination with 'per_page' to set the page set of items to return.
		'paged'							=>	1,			
		
		// order by fields AND order (DESC, ASC) combined to allow multiple fields and directions
		'orderby_order'					=>	'GREATEST(ld_user_activity.activity_started, ld_user_activity.activity_completed) DESC',	
		
		// Search value. See 'search_context' for specifying search fields. 
		's'								=>	'',	
		
		// Limit search to 'post_title' OR 'display_name'. If empty will include both
		's_context'						=>	'',	
		
		// start and/or end time filtering. Should be date format strings 'YYYY-MM-DD HH:mm:ss' or 'YYYY-MM-DD'. 
		'time_start'					=>	0,
		'time_end'						=>	0,
		
		// Indicators to tell the logic if the values passed via 'time_start' and 'time_end' are GMT or local (timezone offset),  
		'time_start_is_gmt'				=>	false,
		'time_end_is_gmt'				=>	false,
		
		// date values returned from the query will be a gmt timestamp int. If the 'date_format' value is provided
		// a new field will be include 'activity_date_time_formatted' using the format specifyers provided in this field. 
		'date_format'					=>	apply_filters('learndash_date_time_formats', get_option('date_format') .' '. get_option('time_format') ),
		
		'include_meta'					=>	true,
		'meta_fields'					=>	array(),
		
		// controls if the queries are actually executed. You can pass in true or 1 to have the logic tested without running the actual query
		'dry_run'						=>	0,
		
		// Supress ALL filters. This include both the query_args and query_str filters
		'suppress_filters_all'			=>	0,
		
		// If the 'suppress_filters_all' is NOT set you can set this to control just filters for the final query_args;
		'suppress_filters_query_args'	=>	0,

		// If the 'suppress_filters_all' is NOT set you can set this to control just filters for the final query_str;
		'suppress_filters_query_str'	=>	0
	);

	if ( empty( $current_user_id ) ) {
		if ( !is_user_logged_in() ) return $activity_results;
		$current_user_id = get_current_user_id();
	}	
	
	$query_args = wp_parse_args( $query_args, $defaults );
	//error_log('query_args<pre>'. print_r($query_args, true) .'</pre>');

	// We save a opy of the original query_args to compare later after we have filled in some default values. 
	$query_args_org = $query_args;
	

	// Clean the group_ids arg. 
	if ( $query_args['group_ids'] != '' ) {
		if ( !is_array( $query_args['group_ids'] ) ) {
			$query_args['group_ids'] = explode(',', $query_args['group_ids'] );
		}
		$query_args['group_ids'] = array_map( 'trim', $query_args['group_ids'] );
	}

	// Clean the course_ids arg. 
	if ( $query_args['course_ids'] != '' ) {
		if ( !is_array( $query_args['course_ids'] ) ) {
			$query_args['course_ids'] = explode(',', $query_args['course_ids'] );
		}
		$query_args['course_ids'] = array_map( 'trim', $query_args['course_ids'] );
	}

	// Clean the post_ids arg. 
	if ( $query_args['post_ids'] != '' ) {
		if ( !is_array( $query_args['post_ids'] ) ) {
			$query_args['post_ids'] = explode(',', $query_args['post_ids'] );
		}
		$query_args['post_ids'] = array_map( 'trim', $query_args['post_ids'] );
	}
	
	// Clean the post_types arg. 
	if ( $query_args['post_types'] != '' ) {
		if ( is_string( $query_args['post_types'] ) ) {
			$query_args['post_types'] = explode(',', $query_args['post_types'] );
		}
		$query_args['post_types'] = array_map( 'trim', $query_args['post_types'] );
		
		$query_args['post_types'] = array_intersect( $query_args['post_types'], $learndash_post_types );
	} else {
		// If not provides we set this to our internal defined learndash_post_types.
		$query_args['post_types'] = $learndash_post_types;
	}

	// Clean the user_ids arg. 
	if ( $query_args['user_ids'] != '' ) {
		if ( !is_array( $query_args['user_ids'] ) ) {
			$query_args['user_ids'] = explode(',', $query_args['user_ids'] );
		}
		$query_args['user_ids'] = array_map( 'trim', $query_args['user_ids'] );
	}

	if ( $query_args['activity_types'] != '' ) {
		if ( is_string( $query_args['activity_types'] ) ) {
			$query_args['activity_types'] = explode(',', $query_args['activity_types'] );
		}
		$query_args['activity_types'] = array_map( 'trim', $query_args['activity_types'] );
	}
	
	if ( $query_args['activity_status'] != '' ) {
		if ( is_string( $query_args['activity_status'] ) ) {
			$query_args['activity_status'] = explode(',', $query_args['activity_status'] );
		}
		$query_args['activity_status'] = array_map( 'trim', $query_args['activity_status'] );

		$not_started_idx = array_search( 'NOT_STARTED', $query_args['activity_status'] );
		 if ( $not_started_idx !== false ) {
			$ACTIVITY_STATUS_HAS_NULL = true;
			unset( $query_args['activity_status'][$not_started_idx] );
		}
		
		foreach( $query_args['activity_status'] as $idx => $value ) {
			if ($value == 'COMPLETED')
				$query_args['activity_status'][$idx] = '1';
			else
				$query_args['activity_status'][$idx] = '0';
		}
	}	

	if ( ( $query_args['group_ids'] == '' ) && ( $query_args['post_ids'] == '' ) && ( $query_args['user_ids'] == '' ) ) {
		// If no filters were provided.
		// If the view user is a group leader we just return all the activity for all the managed users. 
		if (learndash_is_group_leader_user( $current_user_id ) ) {
			$query_args['user_ids'] = learndash_get_group_leader_groups_users( $current_user_id );
		}
		
	} else {
		if ( learndash_is_group_leader_user( $current_user_id ) ) {
			/*
			if  ( $query_args['group_ids'] != '' )  {
				$group_ids = learndash_get_administrators_group_ids( $current_user_id );
				if ( empty( $group_ids ) )
					return $activity_results;
			
				$query_args['group_ids'] = array_intersect( $query_args['group_ids'], $group_ids );
			} 
			
			if  ( $query_args['post_ids'] != '' )  {
				$course_ids = learndash_get_groups_courses_ids( $current_user_id, $query_args['group_ids'] );
				if ( empty( $course_ids ) )
					return $activity_results;

				$query_args['post_ids'] = array_intersect( $query_args['post_ids'], $course_ids );
			}

			if  ( $query_args['user_ids'] != '' )  {
			
				$user_ids = array();		
				
				if ( !empty( $query_args['group_ids'] ) ) {
					$group_ids = $query_args['group_ids'];
				} else {
					$group_ids = learndash_get_administrators_group_ids( $current_user_id );
				}
				
				if ( !empty( $group_ids ) ) {
					foreach ( $group_ids as $group_id ) {
						$group_user_ids = learndash_get_groups_user_ids( $group_id );
						$user_ids = array_merge( $user_ids, $group_user_ids );
					}
				}
				
				if ( empty( $user_ids ) )
					return $activity_results;

				$query_args['user_ids'] = array_intersect( $query_args['user_ids'], $user_ids );
			}
			*/
		} else if ( learndash_is_admin_user( $current_user_id ) ) {			
			// If the group_ids parameter is passed in we need to determine the course_ids contains in the group_ids
			if  ( $query_args['group_ids'] != '' )  {
				$query_args['post_ids'] = learndash_get_groups_courses_ids( $current_user_id, $query_args['group_ids'] );
			}
			
		} else {
			// If the user if not a group leader and not admin then abort until we have added support for those roles. 
			//return $activity_results;
			if ( empty( $query_args['user_ids'] ) ) {
				$query_args['user_ids'] = array( get_current_user_id() );
			}
			
			if ( empty( $query_args['post_ids'] ) ) {
				$query_args['post_ids'] = learndash_user_get_enrolled_courses( get_current_user_id() );
				if ( empty( $query_args['post_ids'] ) ) {
					return $activity_results;
				}
			}
		}
	} 
	
	// We need a timestamp (long int) for the query. Most likely there will be a date string passed to up. 
	$time_items = array('time_start', 'time_end');
	foreach( $time_items as $time_item ) {
		if ( !empty( $query_args[$time_item] ) ) {
			if ( !is_string( $query_args[$time_item] ) ) {
				$time_yymmdd = date('Y-m-d H:i:s', $query_args[$time_item] );
			} else {
				$time_yymmdd = date('Y-m-d H:i:s', strtotime( $query_args[$time_item] ) );
			}
		
			if ( $query_args[$time_item .'_is_gmt'] != true ) {
				$time_yymmdd = get_gmt_from_date( $time_yymmdd );
			}
		
			$time_yymmdd = strtotime( $time_yymmdd );
			
			if ( $time_yymmdd ) {
				$query_args[$time_item .'_gmt_timestamp'] = $time_yymmdd;
				
			}
		}
	}

	// Check that the start and end dates are not backwards. 
	if ( ( isset( $query_args['time_start_gmt_timestamp'] ) ) && ( !empty( $query_args['time_start_gmt_timestamp'] ) ) 
	  && ( isset( $query_args['time_end_gmt_timestamp'] ) ) && ( !empty( $query_args['time_end_gmt_timestamp'] ) ) ) {
		if ( $query_args['time_start_gmt_timestamp'] > $query_args['time_end_gmt_timestamp'] ) {
			$time_save = $query_args['time_start_gmt_timestamp'];
			$query_args['time_start_gmt_timestamp'] = $query_args['time_end_gmt_timestamp'];
			$query_args['time_end_gmt_timestamp'] = $time_save;
		}
	}
	
	if ( ( $query_args['suppress_filters_all'] != true ) && ( $query_args['suppress_filters_query_args'] != true ) ) {
		$query_args = apply_filters('learndash_get_activity_query_args', $query_args);
	}
	
	//error_log('FINAL: query_args<pre>'. print_r($query_args, true) .'</pre>');
	//return;
		
	$sql_str_fields = " 
	users.ID as user_id,
	users.display_name as user_display_name, 
	users.user_email as user_email, 
	posts.ID as post_id,
	posts.post_title post_title, 
	posts.post_type as post_type, 
	ld_user_activity.activity_id as activity_id, 
	ld_user_activity.course_id as activity_course_id, 
	ld_user_activity.activity_type as activity_type,
	ld_user_activity.activity_started as activity_started, 
	ld_user_activity.activity_completed as activity_completed,  
	ld_user_activity.activity_updated as activity_updated,  
	ld_user_activity.activity_status as activity_status";
	
	$sql_str_tables = " FROM ". $wpdb->users ." as users ";


	// Some funky logic on the activity status. If the 'activity_status' is empty of the activity has NULL means we are looking for the 
	// 'NOT_STARTED'. In order to find users that have not started courses we need to do the INNER JOIN on the wp_posts table. This 
	// means for every combination of users AND posts (courses) we will fill out row. This can be expensive when you have thousands
	// of users and courses. 
	if ( ( empty( $query_args['activity_status'] ) ) || ( $ACTIVITY_STATUS_HAS_NULL === true ) 
	  && ( ( !empty( $query_args['post_ids'] ) ) || ( !empty( $query_args['user_ids'] ) ) ) ) {

		$sql_str_joins 	= " INNER JOIN ". $wpdb->posts ." as posts ";
		$sql_str_joins  .= " LEFT JOIN " . LDLMS_DB::get_table_name( 'user_activity' ) . " as ld_user_activity ON users.ID=ld_user_activity.user_id AND posts.ID=ld_user_activity.post_id ";	
		
//		if ( $ACTIVITY_STATUS_HAS_NULL === true ) {
//			// AND ( ld_user_activity.activity_status IN (0,1)  OR ld_user_activity.activity_status IS NULL )  
//			if ( !empty( $query_args['activity_status'] ) ) {
//				$sql_str_joins .= " AND ( ld_user_activity.activity_status IS NULL OR ld_user_activity.activity_status IN (". implode(',', $query_args['activity_status'] ). ") ) ";
//			} else {
//				$sql_str_joins .= " AND ( ld_user_activity.activity_status IS NULL ) ";
//			}
		
			// AND (ld_user_activity.activity_type IS NULL OR ld_user_activity.activity_type IN ('course') ) 
			if ( !empty( $query_args['activity_types'] ) ) {
				$sql_str_joins .= " AND (ld_user_activity.activity_type IS NULL OR ld_user_activity.activity_type IN (". "'" . implode("','", $query_args['activity_types'] ) . "'" .") )";
			}
//		}
		
	} else {
		$sql_str_joins  = " LEFT JOIN " . LDLMS_DB::get_table_name( 'user_activity' ) . " as ld_user_activity ON users.ID=ld_user_activity.user_id ";	
		$sql_str_joins  .= " LEFT JOIN ". $wpdb->posts ." as posts ON posts.ID=ld_user_activity.post_id ";	
	}
			
	$sql_str_where  = " WHERE 1=1 ";
	
	if ( !empty( $query_args['user_ids'] ) ) {
		$sql_str_where .= " AND users.ID ". $query_args['user_ids_action'] . " (". implode(',', $query_args['user_ids'] ) .") ";
	}
	if ( !empty( $query_args['post_ids'] ) ) {
		$sql_str_where .= " AND posts.ID ". $query_args['post_ids_action'] ." (". implode(',', $query_args['post_ids'] ) .") ";
	}
	$sql_str_where .= " AND posts.post_status='publish' ";
	
	if ( !empty( $query_args['post_types'] ) ) {
		$sql_str_where .= " AND posts.post_type IN (". "'" . implode("','", $query_args['post_types'] ) . "'" .") ";
	}

	if ( $ACTIVITY_STATUS_HAS_NULL !== true ) {
	
		if ( !empty( $query_args['activity_types'] ) ) {
			$sql_str_where .= " AND ld_user_activity.activity_type IN (". "'" . implode("','", $query_args['activity_types'] ) . "'" .") ";
		}

		if ( !empty( $query_args['activity_status'] ) ) {
			$sql_str_where .= " AND ld_user_activity.activity_status IN (". implode(',', $query_args['activity_status'] ). ") ";
			
			
			//$activity_status_timestamp_filter = '';
			//if ( in_array( '1', $query_args['activity_status'] ) ) {
			//	if ( !empty( $activity_status_timestamp_filter ) ) $activity_status_timestamp_filter .= " OR ";
			//	$activity_status_timestamp_filter .= " ld_user_activity.activity_completed != 0 ";
			//} 

			//if ( in_array( '0', $query_args['activity_status'] ) ) {
			//	if ( !empty( $activity_status_timestamp_filter ) ) $activity_status_timestamp_filter .= " OR ";
			//	$activity_status_timestamp_filter .= " ld_user_activity.activity_started != 0 ";
			//} 
			//if ( !empty( $activity_status_timestamp_filter ) ) {
			//	$sql_str_where .= " AND (". $activity_status_timestamp_filter .") ";
			//}
			
		} 
	} else {

		if ( !empty( $query_args['activity_status'] ) ) {
			$sql_str_where .= " AND (ld_user_activity.activity_status IS NULL OR ld_user_activity.activity_status IN (". "'" . implode("','", $query_args['activity_status'] ) . "'" .") ) ";
		} else {
			$sql_str_where .= " AND ( ld_user_activity.activity_status IS NULL OR ld_user_activity.activity_started = 0 ) ";
		}
		
	}
	
	if ( !empty( $query_args['course_ids'] ) ) {
		$sql_str_where .= " AND ld_user_activity.course_id ". $query_args['course_ids_action'] ." (". implode(',', $query_args['course_ids'] ). ") ";
	}
	
	if ( !empty( $query_args['s'] ) ) {
		if ($query_args['s_context'] == 'post_title' ) {
			$sql_str_where .= " AND posts.post_title LIKE '". $query_args['s'] ."' ";
		} else if ($query_args['s_context'] == 'display_name' ) {
			$sql_str_where .= " AND users.display_name LIKE '". $query_args['s'] ."' ";
		} else {
			$sql_str_where .= " AND (posts.post_title LIKE '". $query_args['s'] ."' OR users.display_name LIKE '". $query_args['s'] ."') ";
		}
	}
	
	if ( !empty( $query_args['orderby_order'] ) )	
		$sql_str_order = " ORDER BY ". $query_args['orderby_order'] ." ";
	else
		$sql_str_order = '';
	
	if ( !empty( $query_args['per_page'] ) ) {
		if ( empty( $query_args['paged'] ) ) $query_args['paged'] = 1;
		$sql_str_limit = " LIMIT ". $query_args['per_page'] ." OFFSET ". (abs(intval($query_args['paged'])) - 1) * $query_args['per_page'];
	} else {
		$sql_str_limit = '';
	}
	
	if ( ( $query_args['suppress_filters_all'] != true ) && ( $query_args['suppress_filters_query_str'] != true ) ) {
		$sql_str_fields = apply_filters( 'learndash_user_activity_query_fields', $sql_str_fields, $query_args );
		$sql_str_tables = apply_filters( 'learndash_user_activity_query_tables', $sql_str_tables, $query_args );
		$sql_str_joins 	= apply_filters( 'learndash_user_activity_query_tables', $sql_str_joins, $query_args );
		$sql_str_where 	= apply_filters( 'learndash_user_activity_query_where', $sql_str_where, $query_args );
		$sql_str_order 	= apply_filters( 'learndash_user_activity_query_order', $sql_str_order, $query_args );
		$sql_str_limit 	= apply_filters( 'learndash_user_activity_query_limit', $sql_str_limit, $query_args );
	}
	
	$sql_str = "SELECT " . $sql_str_fields . $sql_str_tables . $sql_str_joins . $sql_str_where . $sql_str_order . $sql_str_limit;
	//error_log('sql_str['. $sql_str .']');
	
	if ( $query_args['suppress_filters_query_str'] != true ) {
		$sql_str = apply_filters( 'learndash_user_activity_query_str', $sql_str, $query_args );
	}
	
	$activity_results['query_str'] 				= $sql_str;
	$activity_results['query_args'] 			= $query_args;
	$activity_results['results'] 				= array();
	$activity_results['pager'] 					= array();
	$activity_results['pager']['total_items'] 	= 0;
	$activity_results['pager']['per_page']		= intval( $query_args['per_page'] );
	$activity_results['pager']['total_pages'] 	= 0;
	
	if ( ( !empty( $sql_str ) ) && ( $query_args['dry_run'] != 1 ) ) {
		$activity_query_results = $wpdb->get_results( $sql_str );
		//error_log('activity_query_results<pre>'. print_r($activity_query_results, true) .'</pre>');

		if ( ( !is_wp_error( $activity_query_results ) ) && ( count($activity_query_results ) ) ) {
			$activity_results['results'] = $activity_query_results;
		
			// Need to convert the item date. Actually add a new property which is the formatted date.
			foreach( $activity_results['results'] as &$result_item ) {
				// There are three date fields we need format. 
				// 1. activity_started
				if ( ( property_exists($result_item, 'activity_started') ) && ( !empty( $result_item->activity_started ) ) ) {
					$result_item->activity_started_formatted = get_date_from_gmt( date( 'Y-m-d H:i:s', $result_item->activity_started ), $query_args['date_format'] ); 
				}

				// 2. activity_completed
				if ( ( property_exists($result_item, 'activity_completed') ) && ( !empty( $result_item->activity_completed ) ) ) {
					$result_item->activity_completed_formatted = get_date_from_gmt( date( 'Y-m-d H:i:s', $result_item->activity_completed ), $query_args['date_format'] ); 
				}

				// 3. activity_completed
				if ( ( property_exists($result_item, 'activity_updated') )  && ( !empty( $result_item->activity_updated ) ) ) {
					$result_item->activity_updated_formatted = get_date_from_gmt( date( 'Y-m-d H:i:s', $result_item->activity_updated ), $query_args['date_format'] ); 
				}
				
				if ( $query_args['include_meta'] == true ) {
					$result_item->activity_meta = learndash_get_activity_meta_fields( $result_item->activity_id, $query_args['meta_fields'] );
				}
			}
		} else {
			$activity_results['results_error'] = $activity_query_results;
		}
	}

	if ( ( $query_args['dry_run'] != 1 ) && ( isset( $activity_results['results'] ) ) && ( !empty( $activity_results['results'] ) ) && ( !empty( $query_args['per_page'] ) ) ) {
		$query_str_count = "SELECT SQL_CALC_FOUND_ROWS count(*) as count " . $sql_str_tables . $sql_str_joins ." ". $sql_str_where;
		//error_log('query_str_count['. $query_str_count .']');

		$activity_query_count = $wpdb->get_row( $query_str_count );
		//error_log('activity_query_count<pre>'. print_r($activity_query_count, true) .'</pre>');
		if ( ( !is_wp_error( $activity_query_count ) ) && ( property_exists($activity_query_count, 'count') ) ) {
	
			$activity_results['pager'] = array();
			$activity_results['pager']['total_items'] 	= intval( $activity_query_count->count );
			$activity_results['pager']['per_page']		= intval($query_args['per_page']);
			if ( $activity_results['pager']['total_items'] > 0 ) {
				$activity_results['pager']['total_pages'] = ceil( intval( $activity_results['pager']['total_items'] ) / intval( $activity_results['pager']['per_page'] ) );
			} else {
				$activity_results['pager']['total_pages'] = 0;
			}
		} else {
			$activity_results['pager_error'] = $activity_query_count;
		}	
	}

	//error_log('activity_results<pre>'. print_r($activity_results, true) .'</pre>');

	return $activity_results;
}


/**
 * Primary function to report on the users progress for a given course ID
 *
 * @param  int $course_id This is a single course to report user progress on. 
 * @param  array $user_query_args used when determining users to report on. Support normal WP_User_Query parameters. 
 * @param  array $activity_query_args passed to the learndash_reports_get_activity() function to perform the activity query.
 *
 * @return array Returns $course_progress reqults. 
 * 
 * @since 2.3
 */

function learndash_report_course_users_progress( $course_id = 0, $user_query_args = array(), $activity_query_args = array() ) {
	$course_user_progress_data = array();
	
	//error_log('in '. __FUNCTION__ );
	//error_log('course_id['. $course_id .']');
	//error_log('user_query_args<pre>'. print_r($user_query_args, true) .'</pre>');
	//error_log('activity_query_args<pre>'. print_r($activity_query_args, true) .'</pre>');
	
	if ( !empty( $course_id ) ) {
		
		// If the user_ids was not passed from the caller then we need to do that work
		if ( ( !isset( $activity_query_args['user_ids'] ) ) || ( empty( $activity_query_args['user_ids'] ) ) ) {
			//error_log('user_ids is EMPTY, calling learndash_get_users_for_course');
			$course_user_query = learndash_get_users_for_course( intval( $course_id ), $user_query_args );
			if ( $course_user_query instanceof WP_User_Query ) {	
				$activity_query_args['user_ids'] = $course_user_query->get_results();
			}
		}
		
		if ( !empty( $activity_query_args['user_ids'] ) ) {
			$activity_query_defaults = array(
				'post_ids'			=>	intval( $course_id ),
				'post_types' 		=> 	'sfwd-courses',
				'activity_types'	=>	'course',
				'activity_status'	=>	'',
				'orderby_order'		=>	'users.display_name, posts.post_title',
				'date_format'		=>	'F j, Y H:i:s',
				'paged'				=>	1,
				'per_page'			=>	10,
			);
			$activity_query_args = wp_parse_args( $activity_query_args, $activity_query_defaults );
						
			$activity = learndash_reports_get_activity( $activity_query_args );

			$report_course = get_post( $course_id );

			if (!empty( $activity['results'] ) ) {

				$course_user_progress_data = $activity;
				//$results = $course_user_progress_data['results'];
				//$course_user_progress_data['results'] = $activity['results'];
				/*						
				foreach( $results as $result ) {
					$row 						= 	array();
					$row['user_id']  			= 	$result->user_id;
					$row['name'] 				= 	$result->user_display_name;
					$row['email'] 				=	$result->user_email;
					$row['course_id'] 			= 	$result->post_id;
					$row['course_title'] 		= 	$result->post_title;
					$row['steps_total'] 		= 	'';
					$row['steps_completed'] 	= 	'';
					$row['course_completed'] 	= 	''; 
					$row['course_completed_on']	=	'';
					$row['activity_status']		=	'';
			
					$row['activity_status'] 	= 	$result->activity_status;
				
					if ( ( property_exists( $result, 'steps_total' ) ) && ( !is_null( $result->steps_total ) ) ) {
						$row['steps_total'] = $result->steps_total;
					} else {
						$row['steps_total'] = '';
					}

					if ( ( property_exists( $result, 'steps_completed' ) ) && ( !is_null( $result->steps_completed ) ) ) {
						$row['steps_completed'] = $result->steps_completed;
					} else {
						$row['steps_completed'] = '';
					}

					if ( ( !empty( $row['steps_total'] ) ) && ( !empty( $row['steps_completed'] ) ) && ( $row['steps_completed'] >= $row['steps_total'] )) {
						$row['course_completed'] = esc_html_x('YES', 'Course Complete Report label: YES', 'learndash');
			
						if ( property_exists( $result, 'activity_completed_formatted' ) ) {
							$row['course_completed_on'] = $result->activity_completed_formatted;
						}
					} else {
						$row['course_completed'] = esc_html_x('NO', 'Course Complete Report label: NO', 'learndash');
					} 

					if ( !empty($row ) ) {
						$course_user_progress_data['results'][] = $row;
					}
				}
				*/
			}
		}
	}

	return $course_user_progress_data;
}

/**
 * Clear report actvity by user id and type
 * 
 * @since 2.5
 * 
 * @param  int $user_id (required)
 * @param  array $activity_types (optional) Controls the 'type' or activity. Any combination of the following: 'access', 'course', 'lesson', 'topic', 'quiz'
 * @return none
 */

function learndash_report_clear_user_activity_by_types( $user_id = 0, $activity_types = '' ) {
	$activity_ids = learndash_report_get_activity_by_user_id( $user_id, $activity_types);	
	if ( !empty( $activity_ids ) ) {
		learndash_report_clear_by_activity_ids( $activity_ids );
	}
}		

/**
 * Clear report actvity by post id and type
 * 
 * @since 2.5
 * 
 * @param  int $post_id (required)
 * @param  array $activity_types (optional) Controls the 'type' or activity. Any combination of the following: 'access', 'course', 'lesson', 'topic', 'quiz'
 * @return none
 */

function learndash_report_clear_post_activity_by_types( $post_id = 0, $activity_types = '' ) {
	$activity_ids = learndash_report_get_activity_by_post_id( $post_id, $activity_types);	
	if ( !empty( $activity_ids ) ) {
		learndash_report_clear_by_activity_ids( $activity_ids );
	}
}		

/**
 * Delete report activity rows by activity_id
 * 
 * @since 2.5
 * 
 * @param  array $activity_ids (required) array of activity_ids to delete
 * @return none
 */

function learndash_report_clear_by_activity_ids( $activity_ids = array() ) {
	global $wpdb;

	if ( !empty( $activity_ids ) ) {
		$sql_str = "DELETE FROM " . LDLMS_DB::get_table_name( 'user_activity_meta' ) . " WHERE activity_id IN (". implode(',', $activity_ids) .") ";
		//error_log('sql_str['. $sql_str .']');
		$wpdb->query( $sql_str );
		
		$sql_str = "DELETE FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE activity_id IN (". implode(',', $activity_ids) .") ";
		//error_log('sql_str['. $sql_str .']');
		$wpdb->query( $sql_str );
	}
}



/**
 * Compares user_id field from report activity DB table to WP users rows. Entries NOT found in report activity will be removed. 
 * 
 * @since 2.5
 * 
 * @param  none
 * @return none
 */
function learndash_activity_clear_mismatched_users() {
	global $wpdb;
	
	$sql_str = "SELECT DISTINCT lua.user_id 
	FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " as lua
	LEFT JOIN {$wpdb->usermeta} as um1 ON lua.user_id = um1.user_id AND um1.meta_key = '{$wpdb->prefix}capabilities'
	LEFT JOIN {$wpdb->users} as users ON lua.user_id = users.ID
	WHERE 1=1
	AND ( um1.meta_key IS NULL OR users.ID is NULL )";
	
	$process_users = $wpdb->get_col( $sql_str );
	if ( !empty( $process_users ) ) {
		foreach( $process_users as $user_id ) {
			learndash_report_clear_user_activity_by_types( $user_id );
		}
	}
}

/**
 * Compares post_id field from report activity DB table to WP posts rows. Entries NOT found in report activity will be removed. 
 * 
 * @since 2.5
 * 
 * @param  none
 * @return none
 */
function learndash_activity_clear_mismatched_posts() {
	global $wpdb;
	
	$sql_str = "SELECT DISTINCT " . LDLMS_DB::get_table_name( 'user_activity' ) . ".post_id 
	FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " LEFT JOIN ". $wpdb->posts . " ON " . LDLMS_DB::get_table_name( 'user_activity' ) . ".post_id=" . $wpdb->posts .".ID
	WHERE ". $wpdb->posts .".ID is NULL";
	//error_log('sql_str['. $sql_str .']');
	
	$process_posts = $wpdb->get_col( $sql_str );
	if ( !empty( $process_posts ) ) {
		foreach( $process_posts as $post_id ) {
			learndash_report_clear_post_activity_by_types( $post_id );
		}
	}
}

/**
 * Get a report actvity by user id
 * 
 * @since 2.5
 * 
 * @param  int $user_id (required)
 * @param  array $activity_types (required) Controls the 'type' or activity. Any combination of the following: 'access', 'course', 'lesson', 'topic', 'quiz'
 * @return array returns 'activity_id' of rows found
 */
function learndash_report_get_activity_by_user_id( $user_id = 0, $activity_types = '' ) {
	global $wpdb;
	
	if ( empty( $user_id ) ) return;

	$sql_str = "SELECT activity_id FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=". intval( $user_id );

	if ( !empty( $activity_types ) ) {
		if ( !is_array( $activity_types ) ) {
			$activity_types = explode(',', $activity_types );
		}
		$activity_types = array_map( 'trim', $activity_types );

		$sql_str .= " AND activity_type IN (". "'" . implode("','", $activity_types ) . "'" .")";
		
	}
	//error_log('sql_str['. $sql_str .']');

	return $wpdb->get_col( $sql_str );
}

/**
 * Get a report actvity by post id
 * 
 * @since 2.5
 * 
 * @param  int $post_id (required)
 * @param  array $activity_types (required) Controls the 'type' or activity. Any combination of the following: 'access', 'course', 'lesson', 'topic', 'quiz'
 * @return array returns 'activity_id' of rows found
 */
function learndash_report_get_activity_by_post_id( $post_id = 0, $activity_types = '' ) {
	global $wpdb;
	
	if ( empty( $post_id ) ) return;

	$sql_str = "SELECT activity_id FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE post_id=". intval( $post_id );

	if ( !empty( $activity_types ) ) {
		if ( !is_array( $activity_types ) ) {
			$activity_types = explode(',', $activity_types );
		}
		$activity_types = array_map( 'trim', $activity_types );

		$sql_str .= " AND activity_type IN (". "'" . implode("','", $activity_types ) . "'" .")";
		
	}
	return $wpdb->get_col( $sql_str );
}


/**
 * Get a report by user for all Course they have access to. 
 * 
 * @since 2.3
 * 
 * @param  int $user_id
 * @param  array $course_query_args  passed to the function learndash_user_get_enrolled_courses and used to control what courses are reported for user.
 * @param  array $activity_query_args passed to learndash_reports_get_activity to query activity table
 * @return array If course query and activirty query were successfule this should be a multi-dimensional array showing 'results', 'pager', 'query_args', 'query_str'
 */
function learndash_report_user_courses_progress( $user_id = 0, $course_query_args = array(), $activity_query_args = array() ) {
	$user_courses_progress_data = array();
	
	if ( empty( $user_id ) ) {
		if ( !is_user_logged_in() ) return $course_progress_data;
		$user_id = get_current_user_id();
	}
	
	// If the post_ids (Course ids) was not passed from the caller then we need to do that work
	if ( ( !isset( $activity_query_args['post_ids'] ) ) || ( empty( $activity_query_args['post_ids'] ) ) ) {
		$activity_query_args['post_ids'] = learndash_user_get_enrolled_courses( intval( $user_id ), $course_query_args );
	}
	
	if ( !empty( $activity_query_args['post_ids'] ) ) {
		
		$activity_query_defaults = array(
			'user_ids' 			=> 	intval( $user_id ),
			'post_types' 		=> 	'sfwd-courses',
			'activity_types'	=>	'course',
			'activity_status'	=>	'',
			'orderby_order'		=>	'users.display_name, posts.post_title',
			'date_format'		=>	'F j, Y H:i:s',
			'paged'				=>	1,
			'per_page'			=>	10
		);

		$activity_query_args = wp_parse_args( $activity_query_args, $activity_query_defaults );

		$report_user = get_user_by('id', $user_id);

		$activity = learndash_reports_get_activity( $activity_query_args );
		if (!empty( $activity['results'] ) ) {
			
			$user_courses_progress_data = $activity;
			//$results = $user_courses_progress_data['results'];
			//$user_courses_progress_data['results'] = $activity['results'];
			
			/*
			foreach( $results as $result ) {
				$row 						= 	array();
				$row['user_id']  			= 	$result->user_id;
				$row['name'] 				= 	$result->user_display_name;
				$row['email'] 				=	$result->user_email;
				$row['course_id'] 			= 	$result->post_id;
				$row['course_title'] 		= 	$result->post_title;
				$row['steps_total'] 		= 	'';
				$row['steps_completed'] 	= 	'';
				$row['course_completed'] 	= 	''; 
				$row['course_completed_on']	=	'';
				$row['activity_status']		=	'';
		
				$row['activity_status'] 	= 	$result->activity_status;
			
				if ( ( property_exists( $result, 'steps_total' ) ) && ( !is_null( $result->steps_total ) ) ) {
					$row['steps_total'] = $result->steps_total;
				} else {
					$row['steps_total'] = '';
				}

				if ( ( property_exists( $result, 'steps_completed' ) ) && ( !is_null( $result->steps_completed ) ) ) {
					$row['steps_completed'] = $result->steps_completed;
				} else {
					$row['steps_completed'] = '';
				}

				if ( ( !empty( $row['steps_total'] ) ) && ( !empty( $row['steps_completed'] ) ) && ( $row['steps_completed'] >= $row['steps_total'] )) {
					$row['course_completed'] = esc_html_x('YES', 'Course Complete Report label: YES', 'learndash');
		
					if ( property_exists( $result, 'activity_completed_formatted' ) ) {
						$row['course_completed_on'] = $result->activity_completed_formatted;
					}
				} else {
					$row['course_completed'] = esc_html_x('NO', 'Course Complete Report label: NO', 'learndash');
				} 

				if ( !empty($row ) ) {
					$user_courses_progress_data['results'][] = (object)$row; // Return objects not array
				}
			}
			*/
		}
	}
	//error_log('results<pre>'. print_r($user_courses_progress_data['results'], true) .'</pre>');
	
	return $user_courses_progress_data;
}

/**
 * Get a user quiz attempts
 * 
 * @since 2.3
 * 
 * @param  int $user_id
 * @param  int $quiz_id
 * @return array of activity_id and timestamps or quizzes found
 */
function learndash_get_user_quiz_attempts( $user_id = 0, $quiz_id = 0) {
	global $wpdb;
	
	if ( ( !empty( $user_id ) ) || ( !empty( $quiz_id ) ) ) {
		$sql_str = $wpdb->prepare( "SELECT activity_id, activity_started, activity_completed FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND post_id=%d and activity_type=%s ORDER BY activity_id, activity_started ASC", $user_id, $quiz_id, 'quiz' );
		//error_log('sql_str['. $sql_str .']');
		return $wpdb->get_results( $sql_str );
	}
}

/**
 * Get a user quiz attempts count
 * 
 * @since 2.3
 * 
 * @param  int $user_id
 * @param  int $quiz_id
 * @return int count of quiz entries
 */
function learndash_get_user_quiz_attempts_count( $user_id, $quiz_id ) {
	$quiz_attempts = learndash_get_user_quiz_attempts( $user_id, $quiz_id );
	if ( ( !empty( $quiz_attempts ) ) && ( is_array( $quiz_attempts ) ) ) {
		return count( $quiz_attempts );
	}
}

/**
 * Get a user quiz time spent. Total of each started/complete time set
 * 
 * @since 2.3
 * 
 * @param  int $user_id
 * @param  int $quiz_id
 * @return int total number of seconds spent
 */
function learndash_get_user_quiz_attempts_time_spent( $user_id, $quiz_id ) {
	$total_time_spent = 0;
	
	$attempts = learndash_get_user_quiz_attempts( $user_id, $quiz_id );
	if ( ( !empty( $attempts ) ) && ( is_array( $attempts ) ) ) {
		foreach( $attempts as $attempt )	{
			$total_time_spent += ( $attempt->activity_completed - $attempt->activity_started );
		}
	}
	
	return $total_time_spent;
}



/**
 * Get a user course attempts
 * 
 * @since 2.3
 * 
 * @param  int $user_id
 * @param  int $course_id
 * @return array of activity_id and timestamps or quizzes found
 */
function learndash_get_user_course_attempts( $user_id = 0, $course_id = 0) {
	global $wpdb;
	
	if ( ( !empty( $user_id ) ) || ( !empty( $course_id ) ) ) {
		$sql_str = $wpdb->prepare( "SELECT activity_id, activity_started, activity_completed, activity_updated FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND post_id=%d and activity_type=%s ORDER BY activity_id, activity_started ASC", $user_id, $course_id, 'course' );
		//error_log('sql_str['. $sql_str .']');
		return $wpdb->get_results( $sql_str );
	}
}


/**
 * Get a user course time spent. Total of each started/complete time set
 * 
 * @since 2.3
 * 
 * @param  int $user_id
 * @param  int $course_id
 * @return int total number of seconds spent
 */
function learndash_get_user_course_attempts_time_spent( $user_id = 0, $course_id = 0 ) {
	$total_time_spent = 0;
	
	$attempts = learndash_get_user_course_attempts( $user_id, $course_id );
	//error_log('attempts<pre>'. print_r($attempts, true) .'</pre>');
	
	// We should only ever have one entry for a user+course_id. But still we are returned an array of objects
	if ( ( !empty( $attempts ) ) && ( is_array( $attempts ) ) ) {
		foreach( $attempts as $attempt ) {
			
			if ( !empty( $attempt->activity_completed ) ) {
				// If the Course is complete then we take the time as the completed - started times. 
				$total_time_spent += ( $attempt->activity_completed - $attempt->activity_started );
			} else {
				// But if the Course is not complete we calculate the time based on the updated timestamp
				// This is updated on the course for each lesson, topic, quiz 
				$total_time_spent += ( $attempt->activity_updated - $attempt->activity_started );
			}
		}
	}
	
	return $total_time_spent;
}


function learndash_get_activity_meta_fields( $activity_id = 0, $activity_meta_keys = array() ) {
	global $wpdb;

	$activity_meta = array();
	
	if ( !empty( $activity_id ) ) {
	
		$sql_str = $wpdb->prepare( "SELECT activity_meta_key, activity_meta_value FROM ". LDLMS_DB::get_table_name( 'user_activity_meta' ) . " WHERE activity_id=%d", $activity_id );
		$activity_meta_raw = $wpdb->get_results( $sql_str );

		// If we have some rows returned we want to restructure the meta to be proper key => value array pairs. 
		if ( !empty( $activity_meta_raw ) ) {
			foreach( $activity_meta_raw as $activity_meta_item ) {
				if ( ( empty( $activity_meta_keys ) ) || ( in_array( $activity_meta_item->activity_meta_key, $activity_meta_keys ) ) ) {
					$activity_meta[$activity_meta_item->activity_meta_key] = $activity_meta_item->activity_meta_value;
				}
			}
		}
	}
	
	return $activity_meta;
	
}

/**
 * Calculate the human readable time spent on activity. 
 * 
 * @since 2.3
 * 
 * @param  int $activity_started The start timestamp to compare (required)
 * @param  int $activity_completed The completed timestamp to compare (required)
 * @param  int $minumim_diff The minumm difference between started and completed time (optional)
 * @return string The human readable time difference. 
 * @uses human_time_diff
 * @since 2.3.0.3
 */

function learndash_get_activity_human_time_diff( $activity_started = 0, $activity_completed = 0, $minumim_diff = 60 ) {
	if ( empty( $activity_started ) ) return;
	if ( empty( $activity_completed ) ) return;
	
	$activity_diff = abs( $activity_completed - $activity_started );
	if ( $activity_diff < $minumim_diff ) return;
	
	return human_time_diff( $activity_started, $activity_completed );
}