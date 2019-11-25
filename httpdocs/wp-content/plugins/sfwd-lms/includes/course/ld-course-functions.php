<?php
/**
 * Course Functions
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Course
 */



/**
 * Get course ID for resource.
 * 
 * Determine type of ID is being passed in.  Should be the ID of
 * anything that belongs to a course (Lesson, Topic, Quiz, etc)
 *
 * @since 2.1.0
 * 
 * @param  obj|int 	$id 	id of resource
 * @param  bool 	$bypass_cb 	if true will bypass course_builder logic @since 2.5
 *
 * @return string    		id of course
 */
function learndash_get_course_id( $id = null, $bypass_cb = false ) {
	//global $post;

	if ( is_object( $id ) && $id->ID ) {
		$p = $id;
		$id = $p->ID;
	} else if ( is_numeric( $id ) ) {
		$p = get_post( $id );
	}

	if ( empty( $id ) ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			//return false;
		} else {
			if ( is_admin() ) {
				global $parent_file, $post_type, $pagenow;
				if ( ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) || ( ! in_array( $post_type, array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
					return false;
				}

			} else if ( ! is_single() || is_home() ) {
				return false;
			}
		}

		$post = get_post( get_the_id() );
		if ( ( $post ) && ( $post instanceof WP_Post ) ) {
			$id = $post->ID;
			$p = $post;
		} else {
			return false;
		}
	}

	if ( empty( $p->ID ) ) {
		return 0;
	}

	if ( $p->post_type == 'sfwd-courses' ) {
		return $p->ID;
	}

	// Somewhat a kludge. Here we try ans assume the course_id being handled. 
	if ( ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) && ( $bypass_cb === false ) ) {	
		if ( ! is_admin() ) {
			$course_slug = get_query_var( 'sfwd-courses' );
			if ( ! empty( $course_slug ) ) {
				//$course_post = get_page_by_path( $course_slug, OBJECT, 'sfwd-courses' );
				$course_post = learndash_get_page_by_path( $course_slug, 'sfwd-courses' );
				if ( ( $course_post ) && ( $course_post instanceof WP_Post ) ) {
					return $course_post->ID;
				}
			}
		}

		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			return intval( $_GET['course_id'] );
		} else if ( ( isset( $_GET['course'] ) ) && ( ! empty( $_GET['course'] ) ) ) {
			return intval( $_GET['course'] );
		} else if ( ( isset( $_POST['course_id'] ) ) && ( ! empty( $_POST['course_id'] ) ) ) {
			return intval( $_POST['course_id'] );
		} else if ( ( isset( $_POST['course'] ) ) && ( ! empty( $_POST['course'] ) ) ) {
			return intval( $_POST['course'] );
		} else if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
			if ( get_post_type( intval( $_GET['post'] ) ) == 'sfwd-courses' ) {
				return intval( $_GET['post'] );
			}
		}
	}

	return (int)get_post_meta( $id, 'course_id', true );
}



/**
 * Get course ID for resource (legacy users)
 * 
 * Determine type of ID is being passed in.  Should be the ID of
 * anything that belongs to a course (Lesson, Topic, Quiz, etc)
 * 
 * @since 2.1.0
 * 
 * @param  obj|int 	$id 	id of resource
 * @return string    		id of course
 */
function learndash_get_legacy_course_id( $id = null ){
	global $post;

	if ( empty( $id ) ) {
		if ( ! is_single() || is_home() ) {
			return false;
		}

		$id = $post->ID;
	}

	$terms = wp_get_post_terms( $id, 'courses' );

	if ( empty( $terms) || empty( $terms[0] ) || empty( $terms[0]->slug) ) {
		return 0;
	}

	$courseslug = $terms[0]->slug;

	global $wpdb;

	$term_taxonomy_id = $wpdb->get_var(
		$wpdb->prepare(
			"
		 SELECT `term_taxonomy_id` FROM $wpdb->term_taxonomy tt, $wpdb->terms t 
		 WHERE slug = %s 
		 AND t.term_id = tt.term_id
		 AND tt.taxonomy = 'courses'
		",
			$courseslug
		)
	);

	$course_id = $wpdb->get_var(
		$wpdb->prepare(
			"
		 SELECT `ID` FROM $wpdb->term_relationships, $wpdb->posts 
		 WHERE `ID` = `object_id`
		 AND `term_taxonomy_id` = %d
		 AND `post_type` = 'sfwd-courses'
		 AND `post_status` = 'publish' 
		",
			$term_taxonomy_id
		)
	);

	return $course_id;
}



/**
 * Get lesson id of resource
 *
 * @since 2.1.0
 * 
 * @param  int 		$id  post id of resource
 * @return string     	 lesson id
 */
function learndash_get_lesson_id( $post_id = null, $course_id = null ) {
	global $post;

	if ( empty( $post_id ) ) {
		if ( ! is_single() || is_home() ) {
			return false;
		}

		$post_id = $post->ID;
	}

	if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {	
		$lesson_slug = get_query_var( 'sfwd-lessons' );
		if ( !empty( $lesson_slug ) ) {
			//$lesson_post = get_page_by_path( $lesson_slug, OBJECT, 'sfwd-lessons' );
			$lesson_post = learndash_get_page_by_path( $lesson_slug, 'sfwd-lessons' );
			if ( ( $lesson_post ) && ( $lesson_post instanceof WP_Post ) ) {
				return $lesson_post->ID;
			}
		} else {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $post_id );
			}
			
			if ( !empty( $course_id ) ) {
				return learndash_course_get_single_parent_step( $course_id, $post_id );
			} 
		}
	}
	
	return get_post_meta( $post_id, 'lesson_id', true );
}


/**
 * Get array of courses that user has access to
 *
 * @since 2.1.0
 * 
 * @param  int 		$user_id
 * @param array    	array attributes ('order', 'orderby')
 * @return array    array of courses that user has access to
 */
function ld_get_mycourses( $user_id = null, $atts = array() ) {

	$defaults = array(
		'order' 	=> 'DESC', 
		'orderby' => 'ID', 
		's'       => '',
	);
	$atts = wp_parse_args( $atts, $defaults );
	
	return learndash_user_get_enrolled_courses( 
		$user_id, 
		$atts, 
		true
   );
}


/**
 * Does user have access to course (houses filter)
 * 
 * @since 2.1.0
 * 
 * @param  int 	$post_id 	id of resource
 * @param  int 	$user_id
 * @return bool       
 */
function sfwd_lms_has_access( $post_id, $user_id = null ) {

	 /**
	 * Filter if user has access to course
	 *
	 * Calls sfwd_lms_has_access_fn() to determine if user has access to course
	 * 
	 * @since 2.1.0
	 * 
	 * @param  bool
	 */
	return apply_filters( 'sfwd_lms_has_access', sfwd_lms_has_access_fn( $post_id, $user_id ), $post_id, $user_id );
}



/**
 * Does user have access to course
 * 
 * Check's if user has access to course when they try to access a resource that
 * belong to that course (Lesson, Topic, Quiz, etc.)
 *
 * @since 2.1.0
 * 
 * @param  int 	$post_id 	id of resource
 * @param  int 	$user_id
 * @return bool  
 */
function sfwd_lms_has_access_fn( $post_id, $user_id = null ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( learndash_is_admin_user( $user_id ) ) {
		/**
		 * See example if 'learndash_override_course_auto_enroll' filter 
		 * https://bitbucket.org/snippets/learndash/kon6y
		 *
		 * @since 2.3
		 */
		
		$course_autoenroll_admin = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
		if ( $course_autoenroll_admin == 'yes' ) {
			$course_autoenroll_admin = true;
		} else {
			$course_autoenroll_admin = false;
		}
		
		if ( apply_filters('learndash_override_course_auto_enroll', $course_autoenroll_admin, $user_id ) ) {
			return true;
		}
	}

	$course_id = learndash_get_course_id( $post_id );

	if ( empty( $course_id ) ) {
		return true;
	}

	if ( ! empty( $post_id ) && learndash_is_sample( $post_id ) ) {
		return true;
	}

	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	
	if ( @$meta['sfwd-courses_course_price_type'] == 'open' || @$meta['sfwd-courses_course_price_type'] == 'paynow' && empty( $meta['sfwd-courses_course_join'] ) && empty( $meta['sfwd-courses_course_price'] ) ) {
		return true;
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	if ( true === learndash_use_legacy_course_access_list() ) {
		if ( ! empty( $meta['sfwd-courses_course_access_list'] ) ) {
			//$course_access_list = explode( ',', $meta['sfwd-courses_course_access_list'] );
			$course_access_list = learndash_convert_course_access_list( $meta['sfwd-courses_course_access_list'], true );
		} else {
			$course_access_list = array();
		}
		if ( ( in_array( $user_id, $course_access_list ) ) || ( learndash_user_group_enrolled_to_course( $user_id, $course_id ) ) ) {
			$expired = ld_course_access_expired( $course_id, $user_id );
			return ! $expired; //True if not expired.
		} else {
			return false;
		}
	} else {
		$course_user_meta = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( ( ! empty( $course_user_meta ) ) || ( learndash_user_group_enrolled_to_course( $user_id, $course_id ) ) ) {
			$expired = ld_course_access_expired( $course_id, $user_id );
			return ! $expired; //True if not expired.
		} else {
			return false;
		}
	}		
	
}



/**
 * Redirect user to course
 *
 * @since 2.1.0
 * 
 * @param  int 	$post_id  id of resource that belongs to a course
 */
function sfwd_lms_access_redirect( $post_id ) {
	$access = sfwd_lms_has_access( $post_id );
	if ( $access === true ) {
		return true;
	}

	$link = get_permalink( learndash_get_course_id( $post_id ) );
	$link = apply_filters( 'learndash_access_redirect' , $link, $post_id );
	if ( !empty( $link ) ) {
		wp_redirect( $link );
		exit();
	}
}



/**
 * Is users access to course expired
 *
 * @since 2.1.0
 * 
 * @param  int 	$course_id
 * @param  int 	$user_id  
 * @return bool           
 */
function ld_course_access_expired( $course_id, $user_id ) {
	$course_access_upto = ld_course_access_expires_on( $course_id, $user_id );
	
	if ( empty( $course_access_upto ) ) {
		return false;
	} else {

		if ( time() >= $course_access_upto ) {
			/**
			 * Filter to control processing the user course expiration.
			 *
			 * @since 2.6.2
			 * @param boolean true.
			 * @param integer $user_id User ID.
			 * @param integer $course_id, Course ID.
			 * @param integer $course_access_upto Timestamp when user course access is to expire.
			 *
			 * If return true then processing will continue. If false returned then abort and false returned to calling function.
			 */
			if ( apply_filters( 'learndash_process_user_course_access_expire', true, $user_id, $course_id, $course_access_upto ) ) { 

				/**
				 * As of LearnDash 2.3.0.3 we store the GMT timestamp as the meta value. In prior versions we stored 1
				*/
				update_user_meta( $user_id, 'learndash_course_expired_' . $course_id, time() );
				ld_update_course_access( $user_id, $course_id, true );

				/**
				 * Action fired when the user course access expired.
				 *
				 * @since 2.6.2
				 *
				 * @param integer $user_id User ID.
			 	 * @param integer $course_id, Course ID.
				 */
				do_action( 'learndash_user_course_access_expired', $user_id, $course_id );

				$delete_course_progress = learndash_get_setting( $course_id, 'expire_access_delete_progress' );
				if ( ! empty( $delete_course_progress) ) {
					learndash_delete_course_progress( $course_id, $user_id );
				}
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}

	}	 
}



/**
 * Generate alert in wp_head that users access to course is expired
 *
 * @since 2.1.0
 */
function ld_course_access_expired_alert() {
	global $post;

	if ( ! is_singular() || empty( $post->ID ) || $post->post_type != 'sfwd-courses' ) {
		return;
	}

	$user_id = get_current_user_id();

	if ( empty( $user_id ) ) {
		return;
	}
	
	$expired = get_user_meta( $user_id, 'learndash_course_expired_'.$post->ID, true );
	
	if ( empty( $expired) ) {
		return;
	}

	$has_access = sfwd_lms_has_access( $post->ID, $user_id );

	if ( $has_access ) {
		delete_user_meta( $user_id, 'learndash_course_expired_'.$post->ID );
		return;
	} else	{
		?>
		<script>
			setTimeout(function() {
				alert("<?php echo sprintf( esc_html_x( 'Your access to this %s has expired.', 'Your access to this course has expired.', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' )); ?>")
			}, 2000);
		</script>
		<?php
	}
}

add_action( 'wp_head', 'ld_course_access_expired_alert', 1 );



/**
 * Get amount of time until users course access expires for user
 *
 * @since 2.1.0
 * 
 * @param  int 	$course_id
 * @param  int 	$user_id  
 * @return int  
 */
function ld_course_access_expires_on( $course_id, $user_id ) {
	// Set a default return var. 
	$course_access_upto = 0;
	
	// Check access to course_id + user_id
	$courses_access_from = ld_course_access_from( $course_id, $user_id );

	// If the course_id + user_id is not set we check the group courses.
	if ( empty( $courses_access_from ) ) {
		$courses_access_from = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
	}
	
	// If we have a non-empty access from...
	if (  abs( intval( $courses_access_from ) ) ) {
		
		// Check the course is using expire access
		$expire_access = learndash_get_setting( $course_id, 'expire_access' );
		// The value stored in the post meta for 'expire_access' is 'on' not true/false 1 or 0. The string 'on'.
		if ( !empty( $expire_access) ) {
			$expire_access_days = learndash_get_setting( $course_id, 'expire_access_days' );
			if ( abs( intval( $expire_access_days ) )  > 0 ) {
				$course_access_upto = abs( intval( $courses_access_from ) ) + ( abs( intval( $expire_access_days ) ) * DAY_IN_SECONDS );
			}
		}
	}
	
	/**
	 * Filter for 'ld_course_access_expires_on'.
	 * 
	 * @since 3.0.7
	 * @param integer $course_access_upto timestamp.
	 * @param integer $course_id Course ID.
	 * @param integer $user_id User ID.
	 */
	return apply_filters( 'ld_course_access_expires_on', $course_access_upto, $course_id, $user_id );
}



/**
 * Get amount of time when lesson becomes available to user
 *
 * @since 2.1.0
 * 
 * @param int $course_id Course ID to check.
 * @param int $user_id User ID to check.
 * @return int
 */
function ld_course_access_from( $course_id = 0, $user_id = 0 ) {
	static $courses = array();

	$course_id = absint( $course_id );
	$user_id = absint( $user_id );

	// If Shared Steps enabled we need to ensure both Course ID and User ID and not empty.
	if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
		if ( ( empty( $course_id ) ) || ( empty( $user_id ) ) ) {
			return false;
		}
	}

	if ( ! isset( $courses[ $course_id ][ $user_id ] ) ) {
		if ( ! isset( $courses[ $course_id ] ) ) {
			$courses[ $course_id ] = array();
		}
		$courses[ $course_id ][ $user_id ] = false;

		$courses[ $course_id ][ $user_id ] = (int) get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( empty( $courses[ $course_id ][ $user_id ] ) ) {
			if ( ( 'open' === learndash_get_course_meta_setting( $course_id, 'course_price_type' ) ) && ( apply_filters( 'learndash_course_open_set_user_access_from', true, $user_id, $course_id ) ) ) {
				$enrolled_groups = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
				if ( ! empty( $enrolled_groups ) ) {
					$courses[ $course_id ][ $user_id ] = absint( $enrolled_groups );
				}
			}
		}
		if ( empty( $courses[ $course_id ][ $user_id ] ) ) {
			$course_activity_args = array(
				//'course_id'        => $course_id,
				'user_id'          => $user_id,
				'post_id'          => $course_id,
				'activity_type'    => 'access',
			);

			$course_activity = learndash_get_user_activity( $course_activity_args );
			if ( ( ! empty( $course_activity ) ) && ( is_object( $course_activity ) ) ) {
				if ( ( property_exists( $course_activity, 'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
					$courses[ $course_id ][ $user_id ] = intval( $course_activity->activity_started );
					update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $courses[ $course_id ][ $user_id ] );
				}
			}
		}
	}

	/**
	 * Filter for 'ld_course_access_from'.
	 * 
	 * @since 3.0.7
	 * @param integer timestamp
	 * @param integer $course_id
	 * @param integer $user_id
	 */
	return apply_filters( 'ld_course_access_from', $courses[ $course_id ][ $user_id ], $course_id, $user_id );
}

/**
 * Update the course access time for a user.
 *
 * @since 2.6.0
 *
 * @param int   $course_id Course ID for update.
 * @param int   $user_id User ID for update.
 * @param mixed $access Value can be a date string (YYYY-MM-DD hh:mm:ss or integer value.
 * @param bool  $is_gmt If $access value is GMT (true) or relative to site timezone (false).
 *
 * @return bool Returns true if success.
 */
function ld_course_access_from_update( $course_id, $user_id, $access = '', $is_gmt = false ) {
	if ( ( ! empty( $course_id ) ) && ( ! empty( $user_id ) ) && ( ! empty( $access ) ) ) {

		if ( ! is_numeric( $access ) ) {
			// If we a non-numberic value like a date stamp Y-m-d hh:mm:ss we want to convert it to a GMT timestamp.
			$access_time = learndash_get_timestamp_from_date_string( $access, !$is_gmt );
		} elseif ( is_string( $access ) ) {
			if ( ! $is_gmt ) {
				$access = get_gmt_from_date( $access, 'Y-m-d H:i:s' );
			}
			$access_time = strtotime( $access );
		} else {
			return false;
		}

		if ( ( ! empty( $access_time ) ) && ( $access_time > 0 ) ) {
			// We don't allow dates greater than now.
			if ( $access_time > time() ) {
				$access_time = time();
			}
			
			$course_args = array(
				'course_id'     => $course_id,
				'post_id'       => $course_id,
				'activity_type' => 'course',
				'user_id'       => $user_id,
				'activity_started' => $access_time,
			);
			$activity_id = learndash_update_user_activity( $course_args ); 

			return update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $access_time );
		}
	}
}

/**
 * Update list of courses users has access to
 *
 * @since 2.1.0
 * 
 * @param  int 		$user_id   
 * @param  int 	 	$course_id 
 * @param  bool 	$remove    
 * @return array   list of courses users has access to
 */
function ld_update_course_access( $user_id, $course_id, $remove = false ) {
	$action_success = false;

	$user_id = absint( $user_id );
	$course_id = absint( $course_id );
	$course_access_list = null;

	if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
		return;
	}

	if ( true === learndash_use_legacy_course_access_list() ) {
		$course_access_list = learndash_get_setting( $course_id, 'course_access_list' );
		$course_access_list = learndash_convert_course_access_list( $course_access_list, true );

		if ( empty( $remove ) ) {
			$course_access_list[] = $user_id;
			$course_access_list = array_unique( $course_access_list );
			$action_success = true;
		} else {
			$course_access_list = array_diff( $course_access_list, array( $user_id ) );
			$action_success = true;
		}
		$course_access_list = learndash_convert_course_access_list( $course_access_list );
		learndash_update_setting( $course_id, 'course_access_list', $course_access_list );
	}

	$user_course_access_time = 0;
	if ( empty( $remove ) ) {
		$user_course_access_time = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( empty( $user_course_access_time ) ) {
			$user_course_access_time = time();
			update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $user_course_access_time );
			$action_success = true;
		}
	} else {
		delete_user_meta( $user_id, 'course_'. $course_id .'_access_from' );
		$action_success = true;
	}

	$course_activity_args = array(
		'activity_type'    => 'access',
		'user_id'          => $user_id,
		'post_id'          => $course_id,
		'course_id'        => $course_id,
	);
	$course_activity = learndash_get_user_activity( $course_activity_args );
	if ( is_null( $course_activity ) ) {
		$course_activity_args['course_id'] = 0;
		$course_activity = learndash_get_user_activity( $course_activity_args );
	}

	if ( is_object( $course_activity ) ) {
		$course_activity_args = json_decode( json_encode( $course_activity ), true );
		$course_activity_args['changed'] = false;
	} else {
		$course_activity_args['changed'] = true;
		$course_activity_args['activity_started'] = 0;
	}

	if ( ( empty( $course_activity_args['course_id'] ) ) || ( $course_activity_args['course_id'] !== $course_activity_args['post_id'] ) ) {
		$course_activity_args['course_id'] = $course_activity_args['post_id'];
		$course_activity_args['changed'] = true;
	}

	if ( empty( $remove ) ) {
		if ( $user_course_access_time !== absint( $course_activity_args['activity_started'] ) ) {
			$course_activity_args['activity_started'] = $user_course_access_time;
			$course_activity_args['changed'] = true;
		}
	} else {
		$course_activity_args['activity_started'] = $user_course_access_time;
		$course_activity_args['changed'] = true;
	}
	
	if ( true === $course_activity_args['changed'] ) {
		$skip = false;
		if ( ( ! empty( $remove ) ) && ( ! isset( $course_activity_args['activity_id'] ) ) ) {
			$skip = true;
		}
		if ( true !== $skip ) {
			$course_activity_args['data_upgrade'] = true;
			learndash_update_user_activity( $course_activity_args );
		}
	}

	/**
	 * Run actions after a users list of courses is updated
	 * 
	 * @since 2.1.0
	 * 
	 * @param  int  	$user_id 		
	 * @param  int  	$course_id
	 * @param  array  	$course_access_list
	 * @param  bool  	$remove
	 */
	do_action( 'learndash_update_course_access', $user_id, $course_id, $course_access_list, $remove );
	
	return $action_success;
}


/**
 * Get timestamp of when user has access to lesson
 *
 * @since 2.1.0
 * 
 * @param  int 	$lesson_id
 * @param  int 	$user_id  
 * @return int  timestamp
 */
function ld_lesson_access_from( $lesson_id, $user_id, $course_id = null ) {
	$return = null;

	if ( is_null( $course_id ) ) {
		$course_id = learndash_get_course_id( $lesson_id );
	}
	
	$courses_access_from = ld_course_access_from( $course_id, $user_id );
	if ( empty( $courses_access_from ) ) {
		$courses_access_from = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
	}

	$visible_after = learndash_get_setting( $lesson_id, 'visible_after' );
	if ( $visible_after > 0 ) {
		
		// Adjust the Course acces from by the number of days. Use abs() to ensure no negative days.
		$lesson_access_from = $courses_access_from + abs($visible_after) * 24 * 60 * 60;
		$lesson_access_from = apply_filters( 'ld_lesson_access_from__visible_after', $lesson_access_from, $lesson_id, $user_id );

		$current_timestamp = time();
		if ( $current_timestamp < $lesson_access_from ) {
			$return = $lesson_access_from;
		}		

	} else {
		$visible_after_specific_date = learndash_get_setting( $lesson_id, 'visible_after_specific_date' );
		if ( !empty( $visible_after_specific_date ) ) {
			if ( !is_numeric( $visible_after_specific_date ) ) {
				// If we a non-numberic value like a date stamp Y-m-d hh:mm:ss we want to convert it to a GMT timestamp
				$visible_after_specific_date = learndash_get_timestamp_from_date_string( $visible_after_specific_date, true );
			} 

			$current_time = time();
			
			if ( $current_time < $visible_after_specific_date ) {
				$return = apply_filters( 'ld_lesson_access_from__visible_after_specific_date', $visible_after_specific_date, $lesson_id, $user_id );
			}
		}
	}

	return apply_filters( 'ld_lesson_access_from', $return, $lesson_id, $user_id );
}



/**
 * Display when lesson will be available
 *
 * @since 2.1.0
 * 
 * @param  string $content content of lesson
 * @param  object $post    WP_Post object
 * @return string          when lesson will be available
 */
function lesson_visible_after( $content, $post ) {	
	if ( empty( $post->post_type ) ) {
		return $content; 
	}

	if ( $post->post_type == 'sfwd-lessons' ) {
		$lesson_id = $post->ID; 
	} else {
		if ( $post->post_type == 'sfwd-topic' || $post->post_type == 'sfwd-quiz' ) {
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$course_id = learndash_get_course_id( $post );
				$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
			} else {
				$lesson_id = learndash_get_setting( $post, 'lesson' );	
			}
		} else {
			return $content; 
		}
	}

	if ( empty( $lesson_id ) ) {
		return $content; 
	}

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		return $content; 
	}

	if ( learndash_is_admin_user( $user_id ) ) {
		$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );
		if ( $bypass_course_limits_admin_users == 'yes' ) $bypass_course_limits_admin_users = true;
		else $bypass_course_limits_admin_users = false;
								
	} else {
		$bypass_course_limits_admin_users = false;
	}
		
	// For logged in users to allow an override filter. 
	$bypass_course_limits_admin_users = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $user_id, $post->ID, $post );

	$lesson_access_from = ld_lesson_access_from( $lesson_id, get_current_user_id() );
	if ( ( empty( $lesson_access_from ) ) || ( $bypass_course_limits_admin_users ) ) {
		return $content; 
	} else {
		$content = SFWD_LMS::get_template( 
			'learndash_course_lesson_not_available', 
			array(
				'user_id'					=>	get_current_user_id(),
				'course_id'					=>	learndash_get_course_id( $lesson_id ),
				'lesson_id'					=>	$lesson_id,
				'lesson_access_from_int'	=>	$lesson_access_from,
				'lesson_access_from_date'	=>	learndash_adjust_date_time_display( $lesson_access_from ),
				'context'					=>	'lesson'
			), false
		);
		return $content;
	}

	return $content;
}

add_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );



/**
 * Is users course prerequisites completed for a given course
 *
 * @since 2.1.0
 * 
 * @param  int  	$id  course id
 * @return boolean 
 */
function is_course_prerequities_completed( $post_id = 0 ) {
	$course_pre_complete = true;
	
	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( !empty( $course_id ) ) && ( learndash_get_course_prerequisite_enabled( $course_id ) ) ) {
		
			$course_pre = learndash_get_course_prerequisites( $course_id );
			if ( ! empty( $course_pre ) ) {
				$course_pre_compare = learndash_get_course_prerequisite_compare( $course_id );
				if ( $course_pre_compare == 'ANY' ) {
					$s_pre = array_search( true, $course_pre );
					if ( $s_pre !== false ) 
						$course_pre_complete = true;
					else
						$course_pre_complete = false;
				
				} else if ( $course_pre_compare == 'ALL' ) {
					$s_pre = array_search( false, $course_pre );
					if ( array_search( false, $course_pre ) === false ) 
						$course_pre_complete = true;
					else
						$course_pre_complete = false;
				}
			}
		}
	}

	return $course_pre_complete;
}

/**
 * Given a course ID will return an array of the prereq item and the status 
 *
 * @since 2.4.0
 * 
 * @param  int  	$id  course id
 * @return array 
 */
function learndash_get_course_prerequisites( $post_id = 0 ) {
	$courses_status_array = array();

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( !empty( $course_id ) ) && ( learndash_get_course_prerequisite_enabled( $course_id ) ) ) {
		
			$course_pre = learndash_get_course_prerequisite( $course_id );
			if ( ! empty( $course_pre ) ) {
				$course_pre_compare = learndash_get_course_prerequisite_compare( $course_id );
			
				if ( is_string( $course_pre ) ) $course_pre = array( $course_pre );
			
				foreach( $course_pre as $c_id ) {
					//Now check if the prerequities course is completed by user or not
					$course_status = learndash_course_status( $c_id, null );
					if ( $course_status == esc_html__( 'Completed','learndash' ) ) { 
						$courses_status_array[$c_id] = true;
					} else {
						$courses_status_array[$c_id] = false;
					}
				}
			}
		}
	}
	return $courses_status_array;
}

/**
 * Get list of course prerequisites for a given course
 *
 * @since 2.1.0
 * 
 * @param  int 	 $id  course id
 * @return array      list of courses
 */
function learndash_get_course_prerequisite( $course_id = 0 ) {
	$course_pre = learndash_get_setting( $course_id, 'course_prerequisite' );
	if ( empty( $course_pre ) ) $course_pre = array();
	
	return $course_pre;
}

function learndash_set_course_prerequisite( $course_id = 0, $course_prerequisites = array() ) {
	if ( !empty( $course_id ) ) {
		if ( ( !empty( $course_prerequisites ) ) && ( is_array( $course_prerequisites ) ) ) {
			$course_prerequisites = array_unique( $course_prerequisites );
		}
		
		return learndash_update_setting( $course_id, 'course_prerequisite', (array)$course_prerequisites );
	}
}



/**
 * Given a course ID will return true or false if prereq is enabled
 *
 * @since 2.4.0
 * 
 * @param  int  	$id  course id
 * @return bool 	true is prereq is enabled false if not 
 */
function learndash_get_course_prerequisite_enabled( $course_id ) {
	$course_pre_enabled = false;
	
	$course_id = learndash_get_course_id( $course_id );
	if (!empty( $course_id ) ) {
		$post_options = get_post_meta( $course_id, '_sfwd-courses', true );

		if ( ( isset( $post_options['sfwd-courses_course_prerequisite_enabled'] ) ) && ( $post_options['sfwd-courses_course_prerequisite_enabled'] == 'on' ) ) {
			$course_pre_enabled = true;
		} else if ( !isset( $post_options['sfwd-courses_course_prerequisite_enabled'] ) ) {
			// If the 'course_prerequisite_enabled' setting is not found we check the 'sfwd-courses_course_prerequisite'
			if ( ( isset( $post_options['sfwd-courses_course_prerequisite'] ) ) && ( !empty( $post_options['sfwd-courses_course_prerequisite'] ) ) ) {
				$course_pre_enabled = true;
				$post_options['sfwd-courses_course_prerequisite_enabled'] = 'on';
			} else {
				$post_options['sfwd-courses_course_prerequisite_enabled'] = '';
			}
			update_post_meta( $course_id, '_sfwd-courses', $post_options );
		}
	}
	
	return $course_pre_enabled;
}

function learndash_set_course_prerequisite_enabled( $course_id, $enabled = true ) {
	if ( $enabled === true ) 
		$enabled = 'on';
	
	if ( $enabled != 'on' )
		$enabled = '';
	
	return learndash_update_setting( $course_id, 'course_prerequisite_enabled', $enabled );
}

/**
 * Given a course ID will return the compare value 'ALL' or 'ANY' (default)
 *
 * @since 2.4.0
 * 
 * @param  int  	$id  course id
 * @return string 	'ALL' or 'ANY' default
 */
function learndash_get_course_prerequisite_compare( $post_id ) {

	$course_pre_compare = 'ANY';

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( !empty( $course_id ) ) {
			$course_prerequisite_compare = learndash_get_setting( $course_id, 'course_prerequisite_compare' );
			if ( ( $course_prerequisite_compare == 'ANY') || ( $course_prerequisite_compare == 'ALL' ) )  {
				$course_pre_compare = $course_prerequisite_compare;
			}
		}
	}
	return $course_pre_compare;
}

/**
 * Given a course ID will return true or false if course points  enabled
 *
 * @since 2.4.0
 * 
 * @param  int  	$id  course id
 * @return bool 	true is prereq is enabled false if not 
 */
function learndash_get_course_points_enabled( $post_id = 0 ) {
	$course_points_enabled = false;

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( !empty( $course_id ) ) {
			$course_points_enabled = learndash_get_setting( $course_id, 'course_points_enabled' );
			if ( $course_points_enabled == 'on' )
				$course_points_enabled = true;
		}
	}
	
	return $course_points_enabled;
}

/**
 * Given a course ID will return the course points
 *
 * @since 2.4.0
 * 
 * @param  int  $post_id Course Step ir Course post ID.
 * @param  int  $decimals  Number of decimal places to round. 
 * @return bool false - course points not enabled, int 0 or greater course points
 */
function learndash_get_course_points( $post_id = 0, $decimals = 1 ) {
	$course_points = false;

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( !empty( $course_id ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_points = 0;
		
				$course_points = learndash_get_setting( $course_id, 'course_points' );
				if ( !empty( $course_points ) ) {
					$course_points = learndash_format_course_points( $course_points, $decimals );
				}
			}
		}
	}
	
	return $course_points;
}

/**
 * Given a course ID will return the course points for access
 *
 * @since 2.4.0
 * 
 * @param  int  	$id  course id
 * @return bool 	false - course point not enabled, int 0 or greater access points
 */
function learndash_get_course_points_access( $post_id = 0 ) {
	$course_points_access = false;

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( !empty( $course_id ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_points_access = 0;

				$course_points_access = learndash_format_course_points( learndash_get_setting( $course_id, 'course_points_access' ) );
			}
		}
	}
	
	return $course_points_access;
}

function learndash_check_user_course_points_access( $post_id, $user_id = 0 ) {
	$user_can_access = true;

	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			return false;
		}
	}

	if ( !empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( !empty( $course_id ) ) && ( !empty( $user_id ) ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_access_points = learndash_get_course_points_access( $course_id );

				if ( !empty( $course_access_points ) ) {
					$user_course_points = learndash_get_user_course_points( $user_id );
					
					if ( floatval( $user_course_points ) >= floatval( $course_access_points ) ) 
						return true;
					else
					    return false;
				}
			}
		}
	}
	
	return true;
}

/**
 * Handles actions to be made when user joins a course
 *
 * Redirects user to login url, adds course access to user
 * 
 * @since 2.1.0
 */
function learndash_process_course_join(){
	if ( ( ! isset( $_POST['course_join'] ) ) || ( ! isset( $_POST['course_id'] ) ) )  {
		return;
	}

	$user_id = get_current_user_id();
	$course_id = intval( $_POST['course_id'] );

	if ( empty( $user_id ) ) {
		$redirect_url = get_permalink( $course_id );
		//$redirect_url = add_query_arg('course_join', $_POST['course_join'], $redirect_url );
		//$redirect_url = add_query_arg('course_id', $course_id, $redirect_url );
		$login_url = wp_login_url( $redirect_url );
		
		 /**
		 * Filter URL of where user should be redirected to
		 * 
		 * @since 2.1.0
		 * 
		 * @param  login_url  $login_url
		 */
		$login_url = apply_filters( 'learndash_course_join_redirect', $login_url, $course_id );
		wp_redirect( $login_url );
		exit;
	}
	
	/**
	 * Verify the form is valid
	 * @since 2.2.1.2
	 */
	if ( !wp_verify_nonce( $_POST['course_join'], 'course_join_'. $user_id .'_'. $course_id ) ) {
		return;
	}
	
	$meta = get_post_meta( $course_id, '_sfwd-courses', true );

	if ( @$meta['sfwd-courses_course_price_type'] == 'free' || @$meta['sfwd-courses_course_price_type'] == 'paynow' && empty( $meta['sfwd-courses_course_price'] ) && ! empty( $meta['sfwd-courses_course_join'] ) || sfwd_lms_has_access( $course_id, $user_id ) ) {
		ld_update_course_access( $user_id, $course_id );
	}
}

add_action( 'wp', 'learndash_process_course_join' );

/*
global $learndash_after_login;
//$learndash_after_login = false;
function learndash_wp_login_process_course_join( $user_login = '', $user = '' ) {
	if ( !empty( $user_login ) ) {
		if ( !( $user instanceof WP_User ) ) {
			$user = get_user_by('login', $user_login );
		}

		if ( $user instanceof WP_User ) {
			global $learndash_after_login;
            $learndash_after_login = true;
		}
	}
}
add_action('wp_login', 'learndash_wp_login_process_course_join', 99, 2);
*/
/*
function learndash_course_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	global $learndash_after_login;

	if ( $learndash_after_login ) {
		if ( ( isset( $redirect_to ) ) && ( !empty( $redirect_to ) ) ) {
			$url = parse_url( $redirect_to );
			if ( ( isset( $url['query'] ) ) && ( !empty( $url['query'] ) ) ) {
				parse_str( $url['query'], $url_elements );

				if ( ( isset( $url_elements['course_id'] ) ) && ( !empty( $url_elements['course_id'] ) ) && ( isset( $url_elements['course_join'] ) ) && ( !empty( $url_elements['course_join'] ) ) ) {

					// sort of a hack. If we are here then the user clicked on a Course 'Take This Course' form button. At the time the user was not known to WP which means
					// the nonce used in the form will be different than a nonce for an authentcated user. So we need to reseed the nonce so when we get to the form processing
					// in learndash_process_course_join() it will verify. 
	
					$redirect_to = add_query_arg( 'course_join', wp_create_nonce( 'course_join_'. $user->ID .'_'. $url_elements['course_id'] ), $redirect_to );
				}
			}
		} 
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'learndash_course_login_redirect', 10, 3 );
*/

/**
 * Shortcode to output course content
 *
 * @since 2.1.0
 * 
 * @param  array 	$atts 	shortcode attributes
 * @return string       	output of shortcode
 */
function learndash_course_content_shortcode( $atts ) {
	
	global $learndash_shortcode_used;
	
	$atts_defaults = array(
		'course_id' => 0,
		'num' => false
	);
	$atts = shortcode_atts( $atts_defaults, $atts );
	
	if ( empty( $atts['course_id'] ) ) {
		$course_id = learndash_get_course_id();
		if ( empty( $course_id ) ) {
			return '';
		}
		$atts['course_id'] = intval( $course_id );
	}

	if ( isset( $_GET['ld-courseinfo-lesson-page'] ) ) {
		$atts['paged'] = intval( $_GET['ld-courseinfo-lesson-page'] );
	} 

	$course_id = intval( $atts['course_id'] );

	$course = $post = get_post( $course_id );

//	if ( ! is_singular() || $post->post_type != 'sfwd-courses' ) {
//		return '';
//	}

	if ( is_user_logged_in() )
		$user_id = get_current_user_id();
	else
		$user_id = 0;
	
	$logged_in = ! empty( $user_id );
	$lesson_progression_enabled = false;

	$course_settings = learndash_get_setting( $course );
	$lesson_progression_enabled  = learndash_lesson_progression_enabled( $course_id );
	$courses_options = learndash_get_option( 'sfwd-courses' );
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	$quizzes_options = learndash_get_option( 'sfwd-quiz' );
	$course_status = learndash_course_status( $course_id, null );
	$has_access = sfwd_lms_has_access( $course_id, $user_id );

	$lessons = learndash_get_course_lessons_list( $course, $user_id, $atts );
	$quizzes = learndash_get_course_quiz_list( $course );
	$has_course_content = ( ! empty( $lessons ) || ! empty( $quizzes ) );

	$has_topics = false;

	if ( ! empty( $lessons) ) {
		foreach ( $lessons as $lesson ) {
			$lesson_topics[ $lesson['post']->ID ] = learndash_topic_dots( $lesson['post']->ID, false, 'array', $user_id, $course_id );
			if ( ! empty( $lesson_topics[ $lesson['post']->ID ] ) ) {
				$has_topics = true;
			}
		}
	}

	$level = ob_get_level();
	ob_start();
	$template_file = SFWD_LMS::get_template( 'course_content_shortcode', null, null, true );
	if ( ! empty( $template_file ) ) {
		include $template_file;
	}
	
	$content = learndash_ob_get_clean( $level );
	$content = str_replace( array("\n", "\r"), ' ', $content );
	$user_has_access = $has_access? 'user_has_access':'user_has_no_access';

	$learndash_shortcode_used = true;

	// Prevent the shortcoce page from showing when used on a course (sfwd-courses) single page 
	// as it will conflict with pager from the templates/course.php output. 
	$queried_object = get_queried_object();
	if ( ( is_a( $queried_object, 'WP_Post' ) ) && ( $queried_object->post_type == 'sfwd-courses' ) ) {
		global $course_pager_results;
		$course_pager_results = null;
	}

	
	/**
	 * Filter course content shortcode
	 * 
	 * @since 2.1.0
	 */
	return '<div class="learndash '.$user_has_access.'" id="learndash_post_'.$course_id.'">'.apply_filters( 'learndash_content', $content, $post ).'</div>';
}

add_shortcode( 'course_content', 'learndash_course_content_shortcode' );


function learndash_update_user_activity( $args = array() ) {

	global $wpdb;

	$default_args = array(
		// Can be passed in if we are updating a specific existing activity row.
		'activity_id'						=>	0,
		
		// Required. This is the ID of the Course. Unique key part 1/4
		'course_id'							=>	0,

		// Required. This is the ID of the Course, Lesson, Topic, Quiz item. Unique key part 2/4
		'post_id'							=>	0,
		
		// Optional. Will use get_current_user_id() if left 0. Unique key part 3/4
		'user_id'							=>	0,

		// Will be the token stats that described the status_times array (next argument) Can be most anything. 
		// From 'course', 'lesson', 'topic', 'access' or 'expired'. Unique key part 4/4.
		'activity_type'						=>	'',

		// true if the lesson, topic, course, quiz is complete. False if not complete. null if not started
		'activity_status'					=>	'',

		// Should be the timstamp when the 'status' started
		'activity_started'					=>	'',

		// Should be the timstamp when the 'status' completed
		'activity_completed'				=>	'',

		// Should be the timstamp when the activity record was last updated. Used as a sort column for ProPanel and other queries
		'activity_updated'					=>	'',
		
		// Flag to indicate what we are 'update', 'insert', 'delete'. The default action 'update' will cause this function
		// to check for an existing record to update (if found) 
		'activity_action'					=>	'update',	
		
		'activity_meta'						=>	''
	);
	
	$args = wp_parse_args( $args, $default_args );
	if ( empty( $args['activity_id'] ) ) {
		if ( ( empty( $args['post_id'] ) ) || ( empty( $args['activity_type'] ) ) ) {
			//error_log('ABORT #1');
			return;
		}
	}
	
	//if ( empty( $args['course_id'] ) ) {
	//	error_log('here');
	//}
	
	if ( empty( $args['user_id'] ) ) {
		// If we don't have a user_id passed via args
		if ( !is_user_logged_in() ) 
			return; // If not logged in, abort
		 
		// Else use the logged in user ID as the args user_id 
		$args['user_id'] = get_current_user_id();
	} 
	
	// End of args processing. Finally after we have applied all the logic we go out for filters. 
	$args = apply_filters('learndash_update_user_activity_args', $args);
	if ( empty( $args ) ) return;
	
	$values_array = array(
		'user_id' 			=> 	$args['user_id'],
		'course_id' 		=> 	$args['course_id'], 
		'post_id' 			=> 	$args['post_id'],
		'activity_type'		=>	$args['activity_type'],
	);
	
	$types_array = array(
		'%d', // user_id
		'%d', // course_id
		'%d', // post_id
		'%s', // activity_type
	);

	if ( ( $args['activity_status'] === true ) || ( $args['activity_status'] === false ) ) {
		$values_array['activity_status'] = $args['activity_status'];
		$types_array[] = '%d';
	}
	
	//if ( ( $args['activity_status'] == true ) && ( !empty( $args['activity_completed'] ) ) ) {
	if ( $args['activity_completed'] !== '' ) {
		$values_array['activity_completed'] = $args['activity_completed'];
		$types_array[] = '%d';
	}

	if ( $args['activity_started'] !== '' ) {
		$values_array['activity_started'] = $args['activity_started'];
		$types_array[] = '%d';
	}

	if ( $args['activity_updated'] !== '' ) {
		$values_array['activity_updated'] = $args['activity_updated'];
		$types_array[] = '%d';
	} else {
		if ( ( empty( $args['activity_started'] ) ) && ( empty( $args['activity_completed'] ) ) ) {
			if ( !isset( $args['data_upgrade'] ) ) {
				$values_array['activity_updated'] = time();
				$types_array[] = '%d';
			} 
		} else if ( $args['activity_started'] == $args['activity_completed'] ) {
			$values_array['activity_updated'] = $args['activity_completed'];
			$types_array[] = '%d';
		} else {
			if ( $args['activity_started'] > $args['activity_completed'] ) {
				$values_array['activity_updated'] = $args['activity_started'];
				$types_array[] = '%d';
			} else if ( $args['activity_completed'] > $args['activity_started'] ) {
				$values_array['activity_updated'] = $args['activity_completed'];
				$types_array[] = '%d';
			}
		}
	}
		
	$update_ret = false;
	
	if ( $args['activity_action'] == 'update' ) {

		if ( empty( $args['activity_id'] ) ) {
			$activity = learndash_get_user_activity( $args );
			if ( null !== $activity ) {
			
				$args['activity_id'] = $activity->activity_id;
			}
		}
		
		if ( !empty( $args['activity_id'] ) ) {
			
			$update_values_array = $values_array;
			$update_types_array = $types_array;

			$update_ret = $wpdb->update( 
				LDLMS_DB::get_table_name( 'user_activity' ), 
				$update_values_array,
				array(
					'activity_id' => $args['activity_id']
				),
				$update_types_array,
				array( 
					'%d' // activity_id
				)
			);
			
		} else {
			$args['activity_action'] = 'insert';
		}
	}
	
	if ( $args['activity_action'] == 'insert' ) {
			
		$values_array['activity_updated'] = time();
		$types_array[] = '%d';
				
		$insert_ret = $wpdb->insert( 
			LDLMS_DB::get_table_name( 'user_activity' ), 
			$values_array,
			$types_array
		);
		
		if ( $insert_ret !== false) {
			$args['activity_id'] = $wpdb->insert_id;
		}
	}

	// Finally for the course we update the activity meta
	if ( ( !empty( $args['activity_id'] ) ) && ( !empty( $args['activity_meta'] ) ) )  {
		foreach( $args['activity_meta'] as $meta_key => $meta_value ) {
			learndash_update_user_activity_meta( $args['activity_id'], $meta_key, $meta_value);
		}
	}

	do_action( 'learndash_update_user_activity', $args );
	
	return $args['activity_id'];
}

function learndash_get_user_activity( $args = array() ) {
	global $wpdb;
	
	$element = Learndash_Admin_Data_Upgrades::get_instance();
	
	if ( !isset( $args['course_id'] ) )
		$args['course_id'] = 0;
	
	if ( $args['activity_type'] == 'quiz' ) {
		$data_settings_quizzes = $element->get_data_settings('user-meta-quizzes');
		if ( version_compare( $data_settings_quizzes['version'], '2.5', '>=') ) {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_completed=%d LIMIT 1", $args['user_id'], $args['course_id'], $args['post_id'], $args['activity_type'], $args['activity_completed'] );
		} else {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND post_id=%d AND activity_type=%s AND activity_completed=%d LIMIT 1", $args['user_id'], $args['post_id'], $args['activity_type'], $args['activity_completed'] );
		}
	} else {
		$data_settings_courses = $element->get_data_settings('user-meta-courses');
		if ( version_compare( $data_settings_courses['version'], '2.5', '>=') ) {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s LIMIT 1", $args['user_id'], $args['course_id'], $args['post_id'], $args['activity_type'] );
		} else {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND post_id=%d AND activity_type=%s LIMIT 1", $args['user_id'], $args['post_id'], $args['activity_type'] );
		}
	}
	//error_log('sql_str['. $sql_str .']');
	$activity = $wpdb->get_row( $sql_str );		
	if ( $activity ) {
		//error_log('activity<pre>'. print_r($activity, true) .'</pre>');
		if ( property_exists( $activity, 'activity_status' ) ) {
			if ( $activity->activity_status == true )
				$activity->activity_status = true;
			else if ( $activity->activity_status == false )
				$activity->activity_status = false;
		}
	}
	return $activity;	
}

function learndash_get_user_activity_meta( $activity_id = 0, $activity_meta_key = '', $return_activity_meta_value_only = true ) {

	global $wpdb;

	if ( empty( $activity_id ) )
		return;
	
	if ( !empty( $activity_meta_key ) ) {
	
		$meta_sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity_meta' ) . " WHERE activity_id=%d AND activity_meta_key=%s", $activity_id, $activity_meta_key);
		$activity_meta = $wpdb->get_row( $meta_sql_str );
		if ( !empty($activity_meta ) ) {
			if ( $return_activity_meta_value_only == true ) {
				if ( property_exists( $activity_meta, 'activity_meta_value' ) ) {
					return $activity_meta->activity_meta_value;
				}
			} 
		}
		return $activity_meta;
	} else {
		// Here we return ALL meta for the given activity_id
		$meta_sql_str = $wpdb->prepare( "SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity_meta' ) . " WHERE activity_id=%d", $activity_id);
		return $wpdb->get_results( $meta_sql_str );
	}
}

function learndash_update_user_activity_meta( $activity_id = 0, $meta_key = '', $meta_value = null) {
	global $wpdb;

	if ( ( empty( $activity_id ) ) || ( empty( $meta_key ) ) || ( $meta_value === null ) )
		return;
	
	$activity = learndash_get_user_activity_meta( $activity_id, $meta_key, false);	
	if ( null !== $activity ) {
		$wpdb->update( 
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array(
				'activity_id'			=>	$activity_id,
				'activity_meta_key'		=>	$meta_key,
				'activity_meta_value'	=>	maybe_serialize( $meta_value )
			),
			array(
				'activity_meta_id'			=>	$activity->activity_meta_id
			),
			array(
				'%d',	// activity_id
				'%s',	// meta_key
				'%s'	// meta_value	
			),
			array(
				'%d'	// activity_meta_id
			)
		);
		
	} else {
		$wpdb->insert( 
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array(
				'activity_id'			=>	$activity_id,
				'activity_meta_key'		=>	$meta_key,
				'activity_meta_value'	=>	maybe_serialize( $meta_value )
			),
			array(
				'%d',	// activity_id
				'%s',	// meta_key
				'%s'	// meta_value	
			)
		);
	}
}

function learndash_delete_user_activity( $activity_id = 0 ) {
	global $wpdb;
	
	if ( !empty( $activity_id ) ) {
		$wpdb->delete( 
			LDLMS_DB::get_table_name( 'user_activity' ),
			array( 'activity_id' => $activity_id ),
			array( '%d' )
		);

		$wpdb->delete( 
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array( 'activity_id' => $activity_id ),
			array( '%d' )
		);
	}
}
/**
 * Utility function to return all the courses that are price_type: open 
 * Logic for this query was taken from the sfwd_lms_has_access_fn() function 
 * @since 2.3
 * 
 * @param  bool 	$bypass_transient 	Set to true to bypass transient cache. 
 * @return array    array of post_ids (course ids) found
 */
function learndash_get_open_courses( $bypass_transient = false ) {
	global $wpdb;
	
	$transient_key = "learndash_open_courses";

	if (!$bypass_transient) {
		$courses_ids_transient = LDLMS_Transients::get( $transient_key );
	} else {
		$courses_ids_transient = false;
	}
	
	if ( $courses_ids_transient === false ) {
	
		$sql_str = "SELECT postmeta.post_id as post_id FROM ". $wpdb->postmeta ." as postmeta INNER JOIN ". $wpdb->posts ." as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND ( postmeta.meta_value REGEXP '\"sfwd-courses_course_price_type\";s:4:\"open\";' )";
		$course_ids = $wpdb->get_col( $sql_str );
	
		LDLMS_Transients::set( $transient_key, $course_ids, MINUTE_IN_SECONDS );
	
	} else {
		$course_ids = $courses_ids_transient;
	}
	return $course_ids;
}

/**
 * Utility function to return all the courses that are price_type: paynow with empty price
 * Logic for this query was taken from the sfwd_lms_has_access_fn() function 
 * @since 2.3
 * 
 * @param  bool 	$bypass_transient 	Set to true to bypass transient cache. 
 * @return array    array of post_ids (course ids) found
 */
function learndash_get_paynow_courses( $bypass_transient = false ) {
	global $wpdb;
	
	$transient_key = "learndash_paynow_courses";

	if (!$bypass_transient) {
		$courses_ids_transient = LDLMS_Transients::get( $transient_key );
	} else {
		$courses_ids_transient = false;
	}
	
	if ( $courses_ids_transient === false ) {
	
		$sql_str = "SELECT postmeta.post_id FROM ". $wpdb->postmeta ." as postmeta INNER JOIN ". $wpdb->posts ." as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND (( postmeta.meta_value REGEXP 's:30:\"sfwd-courses_course_price_type\";s:6:\"paynow\";' ) AND ( postmeta.meta_value REGEXP 's:25:\"sfwd-courses_course_price\";s:0:\"\";' ))";
		//error_log('sql_str['. $sql_str .']');
		$course_ids = $wpdb->get_col( $sql_str );
		LDLMS_Transients::set( $transient_key, $course_ids, MINUTE_IN_SECONDS );
	
	} else {
		$course_ids = $courses_ids_transient;
	}
	return $course_ids;
}

// Gets ALL users that have access to given course_id.
// Optional bool flag to exclude admin roles
function learndash_get_users_for_course( $course_id = 0, $query_args = array(), $exclude_admin = true ) {
	$course_user_ids = array();
	
	if ( empty( $course_id ) ) return $course_user_ids;

	$defaults = array(
		// By default WP_User_Query will return ALL users. Strange.
		'fields'		=>	'ID',
	);
	
	$query_args = wp_parse_args( $query_args, $defaults );
	
	if ( $exclude_admin == true ) {
		$query_args['role__not_in'] = array('administrator');
	}
	
	$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
	
	if ($course_price_type == 'open') {
		
		$user_query = new WP_User_Query( $query_args );
		return $user_query;
		
	} else {
	
		$course_access_list = learndash_get_course_meta_setting( $course_id, 'course_access_list');
		$course_user_ids = array_merge( $course_user_ids, $course_access_list );

		$course_access_users = learndash_get_course_users_access_from_meta( $course_id );
		$course_user_ids = array_merge( $course_user_ids, $course_access_users );
		
		$course_groups_users = get_course_groups_users_access( $course_id );
		$course_user_ids = array_merge( $course_user_ids, $course_groups_users );

		if ( !empty( $course_user_ids ) )
			$course_user_ids = array_unique( $course_user_ids );

		$course_expired_access_users = learndash_get_course_expired_access_from_meta( $course_id );
		if ( !empty( $course_expired_access_users ) )
			$course_user_ids = array_diff( $course_access_list, $course_expired_access_users );

		if ( !empty( $course_user_ids ) ) {
			$query_args['include'] = $course_user_ids;
			
			$user_query = new WP_User_Query( $query_args );
			
			//$course_user_ids = $user_query->get_results();
			return $user_query;
		}
	}
	
	/*
	if ( !empty( $course_user_ids ) ) {
		
		// Finally we spin through this list of user_ids and check for expired access. 
		$course_expire_access = learndash_get_course_meta_setting( $course_id, 'expire_access' );
		if ( !empty( $course_expire_access ) ) {
		
			$expired_user_ids = array();
			foreach( $course_user_ids as $user_id ) {
				if ( ld_course_access_expired( $course_id, $user_id ) )
					$expired_user_ids[] = $user_id;
				
			}
			
			if ( !empty( $expired_user_ids ) ) {
				$course_user_ids = array_diff( $course_user_ids, $expired_user_ids );
			}
		}
	}
	*/
		
	return $course_user_ids;
}


function learndash_set_users_for_course( $course_id = 0, $course_users_new = array() ) {

	if (!empty( $course_id ) ) {

		if ( ! empty( $course_users_new ) ) {
			$course_users_new = learndash_convert_course_access_list( $course_users_new, true );
		} else {
			$course_users_new = array();
		}

		$course_users_old = learndash_get_course_users_access_from_meta( $course_id );
		if ( ! empty( $course_users_old ) ) {
			$course_users_old = learndash_convert_course_access_list( $course_users_old, true );
		} else {
			$course_users_old = array();
		}


		$course_users_intersect = array_intersect( $course_users_new, $course_users_old );

		$course_users_add = array_diff( $course_users_new, $course_users_intersect );
		if ( ! empty( $course_users_add ) ) {
			foreach ( $course_users_add as $user_id ) {
				ld_update_course_access( $user_id, $course_id, false );
			}
		}
		
		$course_users_remove = array_diff( $course_users_old, $course_users_intersect );
		if ( ! empty( $course_users_remove ) ) {
			foreach ( $course_users_remove as $user_id ) {
				ld_update_course_access( $user_id, $course_id, true );
			}
		}
				
		// Finally clear our cache for other services 
		//$transient_key = "learndash_group_courses_" . $group_id;
		//delete_transient( $transient_key );
	}
}


// Get all users with explicit 'course_XX_access_from' access
function learndash_get_course_users_access_from_meta( $course_id = 0 ) {
	global $wpdb;
	
	$course_user_ids = array();
	
	if ( !empty( $course_id ) ) {
		// We have to do it this was because WP_User_Query cannot handle on meta EXISTS and another 'NOT EXISTS' in the same query. 
		$sql_str = $wpdb->prepare( "SELECT user_id FROM ". $wpdb->usermeta ." as usermeta WHERE meta_key = %s", 'course_'. $course_id .'_access_from');
	
		$course_user_ids = $wpdb->get_col( $sql_str );
	}
	return $course_user_ids;
}

// Get all the users for a given course_id that have 'learndash_course_expired_XX' user meta records. 
function learndash_get_course_expired_access_from_meta( $course_id = 0 ) {
	global $wpdb;
	
	$expired_user_ids = array();
	
	if ( !empty( $course_id ) ) {
		$sql_str = $wpdb->prepare( "SELECT user_id FROM ". $wpdb->usermeta ." as usermeta WHERE meta_key = %s", 'learndash_course_expired_'. $course_id);
	
		$expired_user_ids = $wpdb->get_col( $sql_str );
	}
	
	return $expired_user_ids;
}


// Utility function to att the course settings in meta. Better than having this over inline over and over again. 
// @TODO Need to convert all references to get_post_meta for '_sfwd-courses' to use this function.
function learndash_get_course_meta_setting( $course_id = 0, $setting_key = '' ) {
	$course_settings = array();
	
	if ( empty( $course_id ) ) return $course_settings;
	
	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	if ( ( is_null( $meta ) ) || ( !is_array( $meta ) ) ) $meta = array();
	
	// we only want/need to reformat the access list of we are returning ALL setting or just the access list
	if ( ( empty( $setting_key ) ) || ( $setting_key == 'course_access_list' ) ) {
		if ( !isset( $meta['sfwd-courses_course_access_list'] ) ) {
			$meta['sfwd-courses_course_access_list'] = '';
		}
		$meta['sfwd-courses_course_access_list'] = array_map( 'intVal', explode( ',', $meta['sfwd-courses_course_access_list'] ) );
				
		// Need to remove the empty '0' items
		$meta['sfwd-courses_course_access_list'] = array_diff($meta['sfwd-courses_course_access_list'], array(0, ''));
	}

	if ( empty( $setting_key ) ) {
		return $meta;
	} else if ( isset( $meta['sfwd-courses_'. $setting_key] ) ) {
		return $meta['sfwd-courses_'. $setting_key];
	}
}

function learndash_get_course_steps_ORG( $course_id = 0, $include_post_types = array( 'sfwd-lessons', 'sfwd-topic' ) ) {
	$steps = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $include_post_types) ) ) {
	
		$steps_query_args = array(
			'post_type' 		=> $include_post_types, 
			'posts_per_page' 	=> 	-1, 
			'post_status' 		=> 	'publish',
			'fields'			=>	'ids',
			'meta_query' 		=> 	array(
										array(
											'key'     	=> 'course_id',
											'value'   	=> intval($course_id),
											'compare' 	=> '=',
											'type'		=>	'NUMERIC'
										)
									)
		);

		//error_log('steps_query_args<pre>'. print_r($steps_query_args, true) .'</pre>');
		
		$steps_query = new WP_Query( $steps_query_args );
		if ($steps_query->have_posts())
			$steps = $steps_query->posts;
	}
	
	return $steps;
}

// Get the total number of Lessons + Topics for a given course_id. For now excludes quizzes at lesson and topic level. 
function learndash_get_course_steps( $course_id = 0, $include_post_types = array( 'sfwd-lessons', 'sfwd-topic' ) ) {

	// The steps array will hold all the individual step counts for each post_type.
	$steps = array();
	
	// This will hold the combined steps post ids once we have run all queries. 
	$steps_all = array();
	
	if ( !empty( $course_id ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			foreach( $include_post_types as $post_type ) {
				$steps[$post_type] = learndash_course_get_steps_by_type( $course_id, $post_type );
			}
		} else {
			if ( ( in_array( 'sfwd-lessons', $include_post_types ) ) || ( in_array( 'sfwd-topic', $include_post_types ) ) ) {
				$lesson_steps_query_args = array(
					'post_type' 		=> 'sfwd-lessons',
					'posts_per_page' 	=> 	-1,
					'post_status' 		=> 	'publish',
					'fields'			=>	'ids',
					'meta_query' 		=> 	array(
						array(
							'key'     	=> 'course_id',
							'value'   	=> intval($course_id),
							'compare' 	=> '=',
							'type'		=>	'NUMERIC'
						)
					)
				);

				$lesson_steps_query = new WP_Query( $lesson_steps_query_args );
				if ($lesson_steps_query->have_posts()) {
					$steps['sfwd-lessons'] = $lesson_steps_query->posts;
				}
			} 

			// For Topics we still require the parent lessons items
			if ( in_array( 'sfwd-topic', $include_post_types ) ) {
			
				if ( !empty( $steps['sfwd-lessons'] ) ) {
					$topic_steps_query_args = array(
						'post_type' 		=> 'sfwd-topic',
						'posts_per_page' 	=> 	-1,
						'post_status' 		=> 	'publish',
						'fields'			=>	'ids',
						'meta_query' 		=> 	array(
							array(
								'key'     	=> 'course_id',
								'value'   	=> intval($course_id),
								'compare' 	=> '=',
								'type'		=>	'NUMERIC'
							)
						)
					);

					if ( ( isset( $steps['sfwd-lessons'] ) ) && ( !empty( $steps['sfwd-lessons'] ) ) ) {
						$topic_steps_query_args['meta_query'][] = array(
							'key'     	=> 'lesson_id',
							'value'   	=> $steps['sfwd-lessons'],
							'compare' 	=> 'IN',
							'type'		=>	'NUMERIC'
						);
					}

					$topic_steps_query = new WP_Query( $topic_steps_query_args );
					if ($topic_steps_query->have_posts()) {
						$steps['sfwd-topic'] = $topic_steps_query->posts;
					}
				} else {
					$steps['sfwd-topic'] = array();
				}
			}
		}
	}
	
	foreach( $include_post_types as $post_type ) {
		if ( ( isset( $steps[$post_type] ) ) && ( !empty( $steps[$post_type] ) ) ) {
			$steps_all = array_merge( $steps_all, $steps[$post_type] );
		}
	}
	
	return $steps_all;
}

function learndash_get_course_steps_count( $course_id = 0 ) {

	$course_steps_count = 0;
	$course_steps = learndash_get_course_steps( $course_id );
	if ( !empty( $course_steps ) )
		$course_steps_count = count( $course_steps );

	if ( has_global_quizzes( $course_id ) )
		$course_steps_count += 1;	
		
	return $course_steps_count;
}

// Get total completed steps for a given course_progress array structure. 
function learndash_course_get_completed_steps( $user_id = 0, $course_id = 0, $course_progress = array() ) {
	$steps_completed_count = 0;

	if ( ( !empty( $user_id ) ) && ( !empty( $course_id ) ) ) {
		
		if ( empty( $course_progress ) ) {
			$course_progress_all = get_user_meta( $user_id, '_sfwd-course_progress', true );
			if ( isset( $course_progress_all[$course_id] ) ) $course_progress = $course_progress_all[$course_id];
		}

		$course_lessons = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
		if ( !empty( $course_lessons ) ) {
			if ( isset( $course_progress['lessons'] ) ) {
				foreach( $course_progress['lessons'] as $lesson_id => $lesson_completed ) {
					if ( in_array( $lesson_id, $course_lessons ) ) {
						$steps_completed_count += intval($lesson_completed);
					}
				}
			}
		} 
		
		$course_topics = learndash_course_get_steps_by_type( $course_id, 'sfwd-topic' );
		if ( isset( $course_progress['topics'] ) ) {
			foreach( $course_progress['topics'] as $lesson_id => $lesson_topics ) {
				if ( in_array( $lesson_id, $course_lessons ) ) {
					if ( ( is_array( $lesson_topics ) ) && ( !empty( $lesson_topics ) ) ) {
						foreach( $lesson_topics as $topic_id => $topic_completed ) {
							if ( in_array( $topic_id, $course_topics ) ) {
								$steps_completed_count += intval($topic_completed);
							}
						}
					}
				}
			}
		}

		if ( has_global_quizzes( $course_id ) ) {
			if ( is_all_global_quizzes_complete( $user_id, $course_id ) ) {
				$steps_completed_count += 1;
			} 
		}
	}
	
	return $steps_completed_count;
}

add_filter('sfwd-courses_display_options', function( $options, $location ) {
	if ( ( !isset( $options[$location.'_course_prerequisite_enabled'] ) ) || ( empty( $options[$location.'_course_prerequisite_enabled'] ) )) {
		global $post;
		if ( $post instanceof WP_Post ) {
			$settings = get_post_meta( $post->ID, '_sfwd-courses', true);
			
			if ( ( isset( $settings[$location .'_course_prerequisite'] ) ) && ( !empty( $settings[$location .'_course_prerequisite'] ) ) ) {
				$options[$location.'_course_prerequisite_enabled'] = 'on';
				$settings[$location.'_course_prerequisite_enabled'] = 'on';
				update_post_meta( $post->ID, '_sfwd-courses', $settings);
			}
		}
	}
	
	return $options;
}, 1, 2);

function learndash_update_course_users_groups( $user_id, $course_id, $access_list, $remove ) {
	if ( ( !empty( $user_id ) ) && ( !empty( $course_id ) ) && ( $remove !== true ) ) {
		
		$course_groups = learndash_get_course_groups( $course_id, true );
		if ( !empty( $course_groups ) ) {
			foreach( $course_groups as $course_group_id ) {
				$ld_auto_enroll_group_courses = get_post_meta( $course_group_id, 'ld_auto_enroll_group_courses', true );
				if ( $ld_auto_enroll_group_courses == 'yes' ) {
					ld_update_group_access( $user_id, $course_group_id );
				}
			}
		}
	}
}
add_action( 'learndash_update_course_access', 'learndash_update_course_users_groups', 50, 4 );


function learndash_user_get_course_completed_date( $user_id = 0, $course_id = 0 ) {
	$completed_on_timestamp = 0;
	if ( ( ! empty( $user_id ) ) && ( !empty( $course_id ) ) ) {
		$completed_on_timestamp = get_user_meta( $user_id, 'course_completed_' . $course_id, true );

		if ( empty( $completed_on_timestamp ) ) {
			$activity_query_args = array(
				'post_ids'		=>	$course_id,
				'user_ids'		=>	$user_id,
				'activity_type'	=>	'course',
				'per_page'		=>	1,
			);
			
			$activity = learndash_reports_get_activity( $activity_query_args );
			if ( ! empty( $activity['results'] ) ) {
				foreach( $activity['results'] as $activity_item ) {
					if ( property_exists( $activity_item, 'activity_completed' ) ) {
						$completed_on_timestamp = $activity_item->activity_completed;

						// To make the next check easier we update the user meta.
						update_user_meta( $user_id, 'course_completed_' . $course_id, $completed_on_timestamp );
						break;
					}
				}
			}
		}
	}
	
	return $completed_on_timestamp;
}

function learndash_course_get_all_parent_step_ids( $course_id = 0, $step_id = 0 ) {
	$step_parents = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_id ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
			if ( $ld_course_steps_object ) {
				$step_parents = $ld_course_steps_object->get_item_parent_steps( $step_id );
				if ( !empty( $step_parents ) ) {
					$step_parents_2 = array();
					foreach( $step_parents as $step_parent ) {
						list( $parent_post_type, $parent_post_id ) = explode(':', $step_parent );
						$step_parents_2[] = intval( $parent_post_id );
					}
					$step_parents = array_reverse($step_parents_2);
				}
			}
		} else {
			$parent_step_id	= get_post_meta( $step_id, 'lesson_id', true );
			if ( ! empty( $parent_step_id ) ) {
				$step_parents[] = $parent_step_id;
				if ( 'sfwd-topic' === get_post_type( $parent_step_id ) ) {
					$parent_step_id	= get_post_meta( $parent_step_id, 'lesson_id', true );
					if ( ! empty( $parent_step_id ) ) {
						$step_parents[] = $parent_step_id;
					}
				} 
			}
			if ( ! empty( $step_parents ) ) {
				$step_parents = array_reverse( $step_parents );
			}
		} 
	}
	
	if ( ! empty( $step_parents ) ) {
		$step_parents = array_map( 'intval', $step_parents );
	}
	
	return $step_parents;
}

function learndash_course_get_single_parent_step( $course_id = 0, $step_id = 0, $step_type = '' ) {
	$parent_step_id = 0;
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_id ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
			if ( $ld_course_steps_object ) {
				$parent_step_id = $ld_course_steps_object->get_parent_step_id( $step_id, $step_type );
			}
		} else {
			if ( empty( $step_type ) ) {
				$parent_step_id	= get_post_meta( $step_id, 'lesson_id', true );
			} else {
				// We only have two nested post types: Topics and quizzes. 
				$step_id_post_type = get_post_type( $step_id );
			
				// A topic only has one parent, a lesson.
				if ( $step_id_post_type == 'sfwd-topic' ) {
					$parent_step_id	= get_post_meta( $step_id, 'lesson_id', true );
					
				} else if ( $step_id_post_type == 'sfwd-quiz' ) {
					$lesson_id = $topic_id = 0;
					$parent_step_id = get_post_meta( $step_id, 'lesson_id', true );
					if ( !empty( $parent_step_id ) ) {
						$parent_step_id_post_type = get_post_type( $parent_step_id );
						if ( $parent_step_id_post_type == 'sfwd-topic' ) {
							$topic_id = $parent_step_id;
							$lesson_id = get_post_meta( $topic_id, 'lesson_id', true );
						} else if ( $parent_step_id_post_type == 'sfwd-lessons' ) {
							$lesson_id = $parent_step_id;
						}

						if ( $step_type == 'sfwd-lessons' ) {
							$parent_step_id = $lesson_id;
						} else if ( $step_type == 'sfwd-topic' ) {
							$parent_step_id = $topic_id;
						} else {
							$parent_step_id = 0;
						}
					} 
				}
			}
		}
	}
	
	return $parent_step_id;
}

function learndash_course_get_steps_by_type_ORG1( $course_id = 0, $step_type = '' ) {
	$course_steps_return = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_type ) ) ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
		if ( $ld_course_steps_object ) {
			$course_steps_t = $ld_course_steps_object->get_steps('t');
			if ( ( isset( $course_steps_t[$step_type] ) ) && ( !empty( $course_steps_t[$step_type] ) ) ) {
				$course_steps_return = $course_steps_t[$step_type];
			}
		}
	}
		
	return $course_steps_return;	
}

function learndash_course_get_steps_by_type( $course_id = 0, $step_type = '' ) {
	$course_steps_return = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_type ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
		
			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
			if ( $ld_course_steps_object ) {
				$course_steps_t = $ld_course_steps_object->get_steps('t');
				if ( ( isset( $course_steps_t[$step_type] ) ) && ( !empty( $course_steps_t[$step_type] ) ) ) {
					$course_steps_return = $course_steps_t[$step_type];
				}
			}
		} else {
			$transient_key = "learndash_course_". $course_id .'_'. $step_type;
			$course_steps_return = LDLMS_Transients::get( $transient_key );
			if ( $course_steps_return === false ) {
				$lesson_order = learndash_get_course_lessons_order( $course_id );
				$steps_query_args = array(
					'post_type' 		=> $step_type, 
					'posts_per_page' 	=> 	-1, 
					'post_status' 		=> 	'publish',
					'fields'			=>	'ids',
					'order'             =>  isset( $lesson_order['order'] ) ? $lesson_order['order'] : false,
					'orderby'           =>  isset( $lesson_order['orderby'] ) ? $lesson_order['orderby'] : false,
					'meta_query' 		=> 	array(
												array(
													'key'     	=> 'course_id',
													'value'   	=> intval( $course_id ),
													'compare' 	=> '=',
												)
											)
				);
				/**
				 * Filter to allow override of query.
				 * 
				 * @since 2.6.0
				 * 
				 * @param array   $steps_query_args Array of WP_Query args.
				 * @param integer $course_id Course ID to get steps for.
				 * @param string  $step_type Steps post type. Could be 'sfwd-lessons', 'sfwd-topics' etc.
				 * 
				 * @return Array of query args.
				 */
				$steps_query_args = apply_filters( 'learndash_course_steps_by_type', $steps_query_args, $course_id, $step_type );
				if ( ! empty( $steps_query_args ) ) {
					$steps_query = new WP_Query( $steps_query_args );

					if ( $steps_query->have_posts() ) {
						$course_steps_return = $steps_query->posts;
					} else {
						$course_steps_return = array();
					}
					LDLMS_Transients::set( $transient_key, $course_steps_return, MINUTE_IN_SECONDS );
				}
			}
		}
	}
		
	return $course_steps_return;
}

function learndash_course_get_children_of_step( $course_id = 0, $step_id = 0, $child_type = '' ) {
	$children_steps = array();
	
	if ( ( !empty( $course_id ) ) && ( !empty( $step_id ) ) ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( intval( $course_id ) );
		if ( $ld_course_steps_object ) {
			$children_steps = $ld_course_steps_object->get_children_steps( $step_id, $child_type );
		}
	}
	
	return $children_steps;
	
}

function learndash_get_courses_for_step( $step_id = 0, $return_flat_array = false ) {
	global $wpdb;
	
	$course_ids = array();
	if ( $return_flat_array !== true ) {
		$course_ids['primary'] = array();
		$course_ids['secondary'] = array();
	}
	
	if ( !empty( $step_id ) ) {
		$sql_str = $wpdb->prepare( "SELECT postmeta.meta_value as course_id, posts.post_title as course_title FROM ". $wpdb->postmeta ." AS postmeta 
				INNER JOIN ". $wpdb->posts ." AS posts ON postmeta.meta_value = posts.ID WHERE postmeta.post_id = ". $step_id ." AND postmeta.meta_key LIKE %s ORDER BY course_title ASC", 'course_id' );
		$course_ids_primary = $wpdb->get_results( $sql_str );
		if ( !empty( $course_ids_primary ) ) {
			foreach( $course_ids_primary as $course_set ) {
				if ( $return_flat_array === true ) {
					$course_ids[$course_set->course_id] = $course_set->course_title;
				} else {
					$course_ids['primary'][$course_set->course_id] = $course_set->course_title;
				}
			}
		}
		
		$sql_str = $wpdb->prepare( "SELECT postmeta.meta_value as course_id, posts.post_title as course_title FROM ". $wpdb->postmeta ." AS postmeta 
			INNER JOIN ". $wpdb->posts ." AS posts ON postmeta.meta_value = posts.ID WHERE postmeta.post_id = ". $step_id ." AND postmeta.meta_key LIKE %s ORDER BY course_title ASC", 'ld_course_%' );
		//$sql_str = $wpdb->prepare( "SELECT meta_value as course_id FROM ". $wpdb->postmeta ." WHERE post_id = ". $step_id ." AND meta_key LIKE %s", 'ld_course_%' );
		$course_ids_secondary = $wpdb->get_results( $sql_str );
		if ( !empty( $course_ids_secondary ) ) {
			foreach( $course_ids_secondary as $course_set ) {
				if ( $return_flat_array === true ) {
					if ( !isset( $course_ids[$course_set->course_id] ) ) {
						$course_ids[$course_set->course_id] = $course_set->course_title;
					}
				} else {
					if ( ( !isset( $course_ids['primary'][$course_set->course_id] ) ) && ( !isset( $course_ids['secondary'][$course_set->course_id] ) ) ) {
						$course_ids['secondary'][$course_set->course_id] = $course_set->course_title;
					}
				}
				
			}
		}

		return $course_ids;
	}
}

function learndash_filter_lesson_options( $options, $location, $values ) {
	//error_log('options<pre>'. print_r($options, true) .'</pre>');
	//error_log('location<pre>'. print_r($location, true) .'</pre>');
	//error_log('values<pre>'. print_r($values, true) .'</pre>');
	
	if ( ( isset( $_GET['course_id'] ) ) && ( !empty( $_GET['course_id'] ) ) ) {
		$viewed_course_id = intval( $_GET['course_id'] );
		
		if ( ( isset( $values[$location .'_course' ] ) ) && ( !empty( $values[$location .'_course' ] ) ) && ( intval( $values[$location .'_course' ] ) !== intval( $_GET['course_id'] ) ) ) {
			if ( isset( $options[$location .'_course'] ) ) 
				unset( $options[$location .'_course'] );
			if ( isset( $options[$location .'_lesson'] ) )
				unset( $options[$location .'_lesson'] );
		}
	} 
	
	return $options;
}
//add_filter( 'sfwd-lessons_display_settings', 'learndash_filter_lesson_options', 10, 3 );
//add_filter( 'sfwd-topic_display_settings', 'learndash_filter_lesson_options', 10, 3 );
//add_filter( 'sfwd-quiz_display_settings', 'learndash_filter_lesson_options', 10, 3 );

/**
 * Action hook called when a post is moved to trash or untrashed. 
 * 
 * @since 2.5.0
 * 
 * @param  int $post_id 
 */
function  learndash_transition_course_step_post_status( $new_status, $old_status, $post ) {
	global $wpdb;
	
	if ( $new_status !== $old_status ) {
		if ( ( !empty( $post ) ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) === true ) {
			$sql_str = "SELECT meta_value FROM " . $wpdb->postmeta . " WHERE post_id = " . $post->ID . " AND (meta_key = 'course_id' OR meta_key LIKE 'ld_course_%')";

			$course_ids = $wpdb->get_col( $sql_str );
			if ( !empty( $course_ids ) ) {
				$course_ids = array_unique( $course_ids );
				foreach( $course_ids as $course_id ) {
					$course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
					if ( ( is_object( $course_steps_object ) ) && (is_a( $course_steps_object, 'LDLMS_Course_Steps' ) ) ) {
						$course_steps_object->set_steps_dirty();
					}
				}
			}
		}
	}
}
add_action( 'transition_post_status', 'learndash_transition_course_step_post_status', 10, 3 ); 


/**
 * Need to validate URL requests when Nested URL permalinks are used. 
 * @since 2.5
 */
function learndash_check_course_step( $wp ) {	
	if ( is_single() ) {
		global $post;
		if ( ( in_array( $post->post_type, array('sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) === true ) 
		  && ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) ) {
			$course_slug = get_query_var('sfwd-courses');
			
			// Check first if there is an existing course part of the URL. Maybe the student is trying to user a lesson URL part
			// for a differen course. 
			if ( ! empty( $course_slug ) ) {
				$course_post = learndash_get_page_by_path( $course_slug, 'sfwd-courses' );
				if ( ( ! empty( $course_post ) ) && ( is_a( $course_post, 'WP_Post' ) ) && ( 'sfwd-courses' === $course_post->post_type ) ) {
					$step_courses = learndash_get_courses_for_step( $post->ID, true );
					if ( ( !empty( $step_courses ) ) && ( isset( $step_courses[$course_post->ID] ) ) ) {

						if ( in_array( $post->post_type, array( 'sfwd-topic', 'sfwd-quiz' ) ) === true ) {

							$parent_steps = learndash_course_get_all_parent_step_ids( $course_post->ID, $post->ID );

							if ( 'sfwd-quiz' === $post->post_type ) {
								$topic_slug = get_query_var( 'sfwd-topic' );
								if ( ! empty( $topic_slug ) ) {
									$topic_post = learndash_get_page_by_path( $topic_slug, 'sfwd-topic' );
									if ( ( ! empty( $topic_post ) ) && ( is_a( $topic_post, 'WP_Post' ) ) && ( 'sfwd-topic' === $topic_post->post_type ) ) {
										if ( ! in_array( $topic_post->ID, $parent_steps ) ) {
											$course_link = get_permalink( $course_post->ID );
											wp_redirect( $course_link );
											die();				
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
											wp_redirect( $course_link );
											die();
									}
								}
								$lesson_slug = get_query_var( 'sfwd-lessons' );
								if ( ! empty( $lesson_slug ) ) {
									$lesson_post = learndash_get_page_by_path( $lesson_slug, 'sfwd-lessons' );
									if ( ( ! empty( $lesson_post ) ) && ( is_a( $lesson_post, 'WP_Post' ) ) && ( 'sfwd-lessons' === $lesson_post->post_type ) ) {
										if ( ! in_array( $lesson_post->ID, $parent_steps ) ) {
											$course_link = get_permalink( $course_post->ID );
											wp_redirect( $course_link );
											die();				
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
										wp_redirect( $course_link );
										die();
									}
								}
							} else if ( 'sfwd-topic' === $post->post_type ) {
								$lesson_slug = get_query_var( 'sfwd-lessons' );
								if ( ! empty( $lesson_slug ) ) {
									$lesson_post = learndash_get_page_by_path( $lesson_slug, 'sfwd-lessons' );
									if ( ( ! empty( $lesson_post ) ) && ( is_a( $lesson_post, 'WP_Post' ) ) && ( 'sfwd-lessons' === $lesson_post->post_type ) ) {
										if ( ! in_array( $lesson_post->ID, $parent_steps ) ) {
											$course_link = get_permalink( $course_post->ID );
											wp_redirect( $course_link );
											die();
										}
									} else {
										$course_link = get_permalink( $course_post->ID );
										wp_redirect( $course_link );
										die();
									}
								}
							}
						} 

						// All is ok to return.
						return;
					} else {
						//global $wp_query;
						//$wp_query->is_404 = true;
						$course_link = get_permalink( $course_post->ID );
						wp_redirect( $course_link );
						die();
					}
				} else {
					// If we don't have a valid Course post 
			    global $wp_query;
    			$wp_query->set_404();
  
			    // 3. Throw 404
    			//status_header( 404 );
    			//nocache_headers();
 
    			// 4. Show 404 template
    			require get_404_template();
 
    			// 5. Stop execution
    			exit;
				}
			} else {
				if ( learndash_is_admin_user() ) {
					return;
				} else {
					// If we don't have a course part of the URL then we check if the step has a primary (legacy) course
					$step_courses = learndash_get_courses_for_step( $post->ID, false );
				
					// If we do have a primary (legacy) then we redirect the user there. 
					if ( !empty( $step_courses['primary'] ) ) {
						$primary_courses = array_keys($step_courses['primary'] );
						$step_permalink = learndash_get_step_permalink( $post->ID, $primary_courses[0] );
						if ( !empty( $step_permalink ) ) {
							wp_redirect( $step_permalink );
							die();
						} else {
							//global $wp_query;
							//$wp_query->is_404 = true;
							$courses_archive_link = get_post_type_archive_link( 'sfwd-courses' );
							wp_redirect( $courses_archive_link );
							die();
						}
					} else {
						if ( learndash_is_admin_user() ) {
							// Alow the admin to view the lesson/topic before it is added to a course
							return;
						} else if ( ( $post->post_type == 'sfwd-quiz' ) && ( empty( $step_courses['secondary'] ) ) ) {
							// If here we have a quiz with no primary or secondary courses. So it is standalone and allowed. 
							return;
						} else {
							//global $wp_query;
							//$wp_query->is_404 = true;
							$courses_archive_link = get_post_type_archive_link( 'sfwd-courses' );
							wp_redirect( $courses_archive_link );
							die();
						}
					}
				}
			}
		}
	}
}

add_action( 'wp', 'learndash_check_course_step' );

function learndash_get_page_by_path( $slug = '', $post_type = '' ) {
	$course_post = null;
	
	if ( ( !empty( $slug ) ) && ( !empty( $post_type ) ) ) {
		
		$course_post = get_page_by_path( $slug, OBJECT, $post_type );
		
		if ( ( defined( 'ICL_LANGUAGE_CODE' ) ) && ( ICL_LANGUAGE_CODE != '' ) ) {
			if ( function_exists( 'icl_object_id' ) ) {
				$course_post = get_page( icl_object_id( $course_post->ID, $post_type, true, ICL_LANGUAGE_CODE ) );
			}
		}
	}
	
	return $course_post;
}

/**
 * Utility function to get the Course Lessons per page setting. This function
 * will initially source the per_page from the course. But if we are using the 
 * default lessons options setting we will use that. Then if the lessons options
 * is not set for some reason we use the default system option 'posts_per_page'. 
 *
 * @param $course_id int the course_id to get the per_page value from
 * @return $course_lessons_per_page int will be the calculated lessons per page or zero
 *
 * @since 2.5.4
 */
function learndash_get_course_lessons_per_page( $course_id = 0 ) {
	$course_lessons_per_page = 0;
	
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	if ( isset( $lessons_options['posts_per_page'] ) ) {
		$course_lessons_per_page = intval( $lessons_options['posts_per_page'] );
	}
	
	if ( !empty( $course_id ) ) {
		$course_settings = learndash_get_setting( intval( $course_id ) );
		
		if ( ( isset( $course_settings['course_lesson_per_page'] ) ) && ( $course_settings['course_lesson_per_page'] == 'CUSTOM' ) && ( isset( $course_settings['course_lesson_per_page_custom'] ) ) ) {
			$course_lessons_per_page = intval( $course_settings['course_lesson_per_page_custom'] );
		} else {
			if ( ( ! isset( $lessons_options['posts_per_page'] ) ) || ( is_null( $lessons_options['posts_per_page'] ) ) ) {
				$course_lessons_per_page = get_option( 'posts_per_page' );
			} else {
				$course_lessons_per_page = intval( $lessons_options['posts_per_page'] ) ;
			}
		}
	}
	
	return $course_lessons_per_page;
}


/**
 * When Course Lessons pagnination is enabled we want to advance the page to the next avaailable lesson page.
 *
 * For example we have a course with 100 lessons and the course has per page set to 10. The student can completed 
 * up to lesson 73. When the student returns to the course we don't want to default to show the first page 
 * (lessons 1-10). Instead we want to redirect the user to page 7 showing lessons 71-80. 
 * 
 * @since 2.5.4
 */
function learndash_course_set_lessons_start_page( ) {
	// Last minute change to not use this for the v2.5.5 release. 
	return;
	if ( ( !is_admin() ) && ( is_single() ) ) {
		$queried_object = get_queried_object();
		if ( ( is_a( $queried_object, 'WP_Post' ) ) && ( is_user_logged_in() ) && ( !isset( $_GET['ld-lesson-page'] ) ) ) {
			if ( $queried_object->post_type == 'sfwd-courses' ) {
				if ( apply_filters( 'learndash_course_lessons_advance_progress_page', true, $queried_object->ID, get_current_user_id() ) ) {
					$course_lessons_per_page = learndash_get_course_lessons_per_page( $queried_object->ID );
					if ( $course_lessons_per_page > 0 ) {
						$user_courses = get_user_meta( get_current_user_id(), '_sfwd-course_progress', true );
						if ( ( isset( $user_courses[$queried_object->ID]['lessons'] ) ) && ( !empty( $user_courses[$queried_object->ID]['lessons'] ) ) ) {
							$lesson_paged = ceil( ( count( $user_courses[$queried_object->ID]['lessons'] ) + 1 ) / $course_lessons_per_page );
							if ( $lesson_paged > 1 ) {
								$redirect_url = add_query_arg( 'ld-lesson-page', $lesson_paged );
								wp_redirect( $redirect_url );
								die();
							}
						}
					}
				}
			} 
		}
	} 
}
//add_action( 'wp', 'learndash_course_set_lessons_start_page', 1 );

/**
 * Called from within the Coure Lessons List processing query SFWD_CPT::loop_shortcode.
 * This action will setup a global pager array to be used in templates.  
 */

$course_pager_results = array( 'pager' => array( ) );
global $course_pager_results;

function learndash_course_lessons_list_pager( $query_result = null, $pager_context = '' ) {
	global $course_pager_results;

	$course_pager_results['pager']['paged'] = 1;
	if ( ( isset( $query_result->query_vars['paged'] ) ) && ( $query_result->query_vars['paged'] > 1 ) ) {
		$course_pager_results['pager']['paged'] = $query_result->query_vars['paged'];
	}
	
	$course_pager_results['pager']['total_items'] = $query_result->found_posts;
	$course_pager_results['pager']['total_pages'] = $query_result->max_num_pages;
}
add_action( 'learndash_course_lessons_list_pager', 'learndash_course_lessons_list_pager', 10, 2 );

/**
 * Determine pager settings based on _GET vars
 */
function learndash_get_lesson_topic_paged_values() {
	$paged_values = array(
		'lesson' => 0,
		'paged' => 1
	);
	if ( ( isset( $_GET['ld-topic-page'] ) ) && ( ! empty( $_GET['ld-topic-page'] ) ) ) {
		list( $paged_values['lesson'], $paged_values['paged'] ) = explode( '-', $_GET['ld-topic-page'] );
		$paged_values['lesson'] = absint( $paged_values['lesson'] );
		$paged_values['paged'] = absint( $paged_values['paged'] );
		if ( $paged_values['paged'] < 1 ) {
			$paged_values['paged'] = 1;
		}
		if ( ( empty( $paged_values['lesson'] ) ) || ( empty( $paged_values['paged'] ) ) ) {
			$paged_values = array(
				'lesson' => 0,
				'paged' => 1
			);
		}
	}

	return $paged_values;
}

function learndash_process_lesson_topics_pager( $topics = array(), $args = array() ) {
	global $course_pager_results;

	$paged_values = learndash_get_lesson_topic_paged_values();

	if ( ! empty( $topics ) ) {
		$topics_per_page = learndash_get_course_topics_per_page( $args['course_id'], $args['lesson_id'] );
		if ( ( $topics_per_page > 0 ) && ( count( $topics ) > $topics_per_page ) ) {
			$topics_chunks = array_chunk( $topics, $topics_per_page );

			$course_pager_results[ $args['lesson_id'] ] = array();
			$course_pager_results[ $args['lesson_id'] ]['pager'] = array();

			$topics_paged = 1;									
			if ( ( ! empty($paged_values['lesson'] ) ) && ( $paged_values['lesson'] == $args['lesson_id'] ) ) {
				$topics_paged = $paged_values['paged'];
			} else if ( get_post_type() === learndash_get_post_type_slug( 'topic' ) ) {
				/**
				 * If we are viewing a Topic and the page is empty we load the 
				 * paged set to show the current topic item.
				 */
				foreach( $topics_chunks as $topics_chunk_page => $topics_chunk_set ) {
					$topics_ids = array_values( wp_list_pluck( $topics_chunk_set, 'ID' ) );
					if ( ( ! empty( $topics_ids ) ) && ( in_array( get_the_ID(), $topics_ids ) ) ) {
						$topics_paged = ++$topics_chunk_page;
						break;
					}
				}
			}
	
			$course_pager_results[ $args['lesson_id'] ]['pager']['paged'] = $topics_paged;

			$course_pager_results[ $args['lesson_id'] ]['pager']['total_items'] = count( $topics );
			$course_pager_results[ $args['lesson_id'] ]['pager']['total_pages'] = count( $topics_chunks );

			$topics = $topics_chunks[ $topics_paged - 1 ];
		}
	}

	return $topics;
}

/**
 * Utility function to get the Course Lessons order. 
 * The course lessons order can be set in the course or globally defined in 
 * the lesson options. This function will check all logic and return the 
 * correct setting.
 *
 * @param $course_id int the course_id to get the per_page value from
 * @return $course_lessons_order int will be the calculated lessons per page or zero
 *
 * @since 2.5.4
 */
function learndash_get_course_lessons_order( $course_id = 0 ) {
	$course_lessons_args = array( 'order' => '', 'orderby' => '' );
	
	if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {	
		$course_lessons_args['orderby'] = 'post__in';
		return $course_lessons_args;
		
	} else {
		$lessons_options = learndash_get_option( 'sfwd-lessons' );
		if ( ( isset( $lessons_options['order'] ) ) && ( !empty( $lessons_options['order'] ) ) ) 
			$course_lessons_args['order'] = $lessons_options['order'];

		if ( ( isset( $lessons_options['orderby'] ) ) && ( !empty( $lessons_options['orderby'] ) ) ) 
			$course_lessons_args['orderby'] = $lessons_options['orderby'];
	}

	if ( !empty( $course_id ) ) {
		$course_settings = learndash_get_setting( $course_id );
		if ( ( isset( $course_settings['course_lesson_order'] ) ) && ( !empty( $course_settings['course_lesson_order'] ) ) ) 
			$course_lessons_args['order'] = $course_settings['course_lesson_order'];

		if ( ( isset( $course_settings['course_lesson_orderby'] ) ) && ( !empty( $course_settings['course_lesson_orderby'] ) ) ) 
			$course_lessons_args['orderby'] = $course_settings['course_lesson_orderby'];
	}	
	
	return apply_filters( 'learndash_course_lessons_order', $course_lessons_args, $course_id );
}

/**
 * Utility function to convert the standard comma separated list of user IDs
 * used for the course_access_list field. The conversion is to trim and ensure
 * the values are integer and not empty. 
 * 
 * @since 2.5.9
 * @param string $course_access_list_str String of comma separated user IDs. 
 * @param boolean $return_array True/False bool to return string or array. 
 * 
 * @return string $course_access_list_str
 */
function learndash_convert_course_access_list( $course_access_list = '', $return_array = false ) {
	if ( ! empty( $course_access_list ) ) {
		
		// Convert the comma separated list into an array.
		if ( is_string( $course_access_list ) ) {
			$course_access_list = explode( ',', $course_access_list );
		} 

		// Now normalize the array elements.
		if ( is_array( $course_access_list ) ) {
			$course_access_list = array_map( 'intval', $course_access_list );
			$course_access_list = array_unique( $course_access_list, SORT_NUMERIC );
			$course_access_list = array_diff( $course_access_list, array( 0 ) );
		}

		// Prepare the return value.
		if ( true !== $return_array ) {
			$course_access_list = implode( ',', $course_access_list );
		}
	} else if ( true === $return_array ) {
		$course_access_list = array();
	}

	return $course_access_list;
}

/**
 * Utility function to determine the number of lesson topics to display per page.
 * 
 * @since 3.0
 * @param integer $course_id Parent Course ID.
 * @param integer $lesson_id Parent Lesson ID.
 */
function learndash_get_course_topics_per_page( $course_id = 0, $lesson_id = 0 ) {
	$course_topics_per_page = 0;
	
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	if ( isset( $lessons_options['posts_per_page'] ) ) {
		$course_topics_per_page = intval( $lessons_options['posts_per_page'] );
	}
	
	if ( !empty( $course_id ) ) {
		$course_settings = learndash_get_setting( intval( $course_id ) );
		
		if ( ( isset( $course_settings['course_lesson_per_page'] ) ) && ( $course_settings['course_lesson_per_page'] == 'CUSTOM' ) && ( isset( $course_settings['course_topic_per_page_custom'] ) ) ) {
			$course_topics_per_page = intval( $course_settings['course_topic_per_page_custom'] );
		} 
	}
	
	return $course_topics_per_page;
}

/**
 * Transition the Course steps logic from using Shared Steps to legacy.
 *
 * @since 3.0
 * @param integer $course_id Course ID to process.
 */
function learndash_transition_course_shared_steps( $course_id = 0 ) {
	if ( ! empty( $course_id ) ) {
		if ( 'yes' !== LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' )  ) {	
					$course_steps = get_post_meta( $course_id, 'ld_course_steps', true );
					if ( isset( $course_steps['h'] ) ) {
						// If here then Shared Steps was enabled 

						$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
						$ld_course_steps_object->set_steps( $course_steps['h'] );
					}
				}
	}
}

function learndash_use_legacy_course_access_list() {
	$use_legacy_course_access_list = true;

	$element = Learndash_Admin_Data_Upgrades::get_instance();
	$data_course_access_convert = $element->get_data_settings( 'course-access-lists-convert' );
	if ( $data_course_access_convert ) {
		$use_legacy_course_access_list = false;

	}
	return apply_filters( 'learndash_use_legacy_course_access_list', $use_legacy_course_access_list );
}