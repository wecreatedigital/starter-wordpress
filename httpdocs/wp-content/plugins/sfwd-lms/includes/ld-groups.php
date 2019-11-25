<?php
/**
 * Group functions
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Groups
 */



/**
 * Email message to group
 * 
 * @since 2.1.0
 */
function learndash_group_emails() {
	if ( ( isset( $_POST['action'] ) ) && ( $_POST['action'] == 'learndash_group_emails' ) && ( isset( $_POST['group_email_data'] ) ) && ( !empty( $_POST['group_email_data'] ) ) ) {

		if ( !is_user_logged_in() ) exit;
		$current_user = wp_get_current_user();
		if ( ( !learndash_is_group_leader_user( $current_user->ID ) ) && ( !learndash_is_admin_user( $current_user->ID ) ) ) {
			exit;
		}

		$group_email_data = json_decode( stripslashes( $_POST['group_email_data'] ), true );
		
		if ( ( !isset( $group_email_data['group_id'] ) ) || ( empty( $group_email_data['group_id'] ) ) )
			die();
		$group_email_data['group_id'] = intval( $group_email_data['group_id'] );
		
		if ( ( !isset( $group_email_data['email_subject'] ) ) || ( empty( $group_email_data['email_subject'] ) ) )
			die();
		$group_email_data['email_subject'] = wp_strip_all_tags( stripcslashes( $group_email_data['email_subject'] ) );

		if ( ( !isset( $group_email_data['email_message'] ) ) || ( empty( $group_email_data['email_message'] ) ) )
			die();
		$group_email_data['email_message'] = wpautop( stripcslashes( $group_email_data['email_message'] ) );
		
		if ( ( !isset( $_POST['nonce'] ) ) || ( empty( $_POST['nonce'] ) ) 
		  || ( !wp_verify_nonce( $_POST['nonce'], 'group_email_nonce_'. $group_email_data['group_id'] .'_'. $current_user->ID ) ) ) {
			die();
		} 

		$group_admin_ids = learndash_get_groups_administrator_ids( $group_email_data['group_id'] );
		if ( in_array( $current_user->ID, $group_admin_ids ) === false ) {
			die();
		}

		$mail_args = array(
			'to' 			=> 	$current_user->user_email,
			'subject'		=>	$group_email_data['email_subject'],
			'message'		=>	$group_email_data['email_message'],
			'attachments'	=>	'',
			'headers' 		=> 	array(
				'MIME-Version: 1.0',
				'content-type: text/html',
				'From: '. $current_user->display_name . ' <' . $current_user->user_email . '>',
				'Reply-to: '.  $current_user->display_name . ' <' . $current_user->user_email . '>'
			)
		);

		$group_user_ids		= learndash_get_groups_user_ids( $group_email_data['group_id'] );
		if ( !empty( $group_user_ids ) ) {
			$email_addresses = array();
		
			foreach ( $group_user_ids as $user_id ) {
				$user = get_user_by( 'id', $user_id );

				if ( $user ) {
					$email_addresses[] = 'Bcc: '. sanitize_email( $user->user_email );
				}
			}
			

			$group_email_error = null;
			add_action('wp_mail_failed', function ( $mail_error ) {
			    global $group_email_error;
				$group_email_error = $mail_error;
			});

			if ( $email_addresses ) {
				$mail_args['headers'] = array_merge( $mail_args['headers'], $email_addresses );

				$mail_args = apply_filters( 'ld_group_email_users_args', $mail_args );
				if ( !empty( $mail_args ) ) {
					do_action( 'ld_group_email_users_before', $mail_args );
					
					$mail_ret = wp_mail( $mail_args['to'], $mail_args['subject'], $mail_args['message'], $mail_args['headers'], $mail_args['attachments'] );
					
					do_action( 'ld_group_email_users_after', $mail_args, $mail_ret );
					
					if ( !$mail_ret ) {
						$group_email_error_message = '';
						
						if ( is_wp_error( $group_email_error ) ) {
							$group_email_error_message = $group_email_error->get_error_message();
						}
						wp_send_json_error( 
							array( 
								'message' => sprintf( wp_kses_post( __( '<span style="color:red">Error: Email(s) not sent. Please try again or check with your hosting provider.<br />wp_mail() returned %d.<br />Error: %s</span>', 'learndash' ) ), $mail_ret, $group_email_error_message )
							) 
						);
					} else {
						wp_send_json_success(
							array( 
								'message' => sprintf( wp_kses_post( __( '<span style="color:green">Success: Email sent to %d group users.</span>', 'learndash' ) ), count( $email_addresses ) )
							) 
						);
					}
				} else {
					wp_send_json_error( 
						array( 
							'message' => __( '<span style="color:red">Mail Args empty. Unepected condition from filter: ld_group_email_users_args</span>', 'learndash' )
						) 
					);
				}
			} else {
				wp_send_json_error( 
					array( 
						'message' => esc_html__( 'No users in group.', 'learndash' )
					) 
				);
			}
		} 
		wp_send_json_error();
		die();
	}
}
add_action( 'wp_ajax_learndash_group_emails', 'learndash_group_emails' );


/**
 * Register groups post type
 * 
 * @since 2.1.0
 */
function learndash_groups_post_content() {

	$labels = array(
		'name'               => esc_html__( 'Groups', 'learndash' ),
		'singular_name'      => esc_html__( 'Group', 'learndash' ),
		'add_new'            => esc_html__( 'Add New', 'learndash' ),
		'add_new_item'       => esc_html__( 'Add New Group', 'learndash' ),
		'edit_item'          => esc_html__( 'Edit Group', 'learndash' ),
		'new_item'           => esc_html__( 'New Group', 'learndash' ),
		'all_items'          => esc_html__( 'Groups', 'learndash' ),
		'updated'            => esc_html__( 'Group Updated.', 'learndash' ),
		'view_item'          => esc_html__( 'View Group', 'learndash' ),
		'search_items'       => esc_html__( 'Search Group', 'learndash' ),
		'not_found'          => esc_html__( 'No Group found', 'learndash' ),
		'not_found_in_trash' => esc_html__( 'No Group found in the Trash', 'learndash' ),
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__( 'Groups', 'learndash' ),
		'item_published'	 =>	esc_html__( 'Group Published', 'learndash' ),
		'item_published_privately' => esc_html__( 'Group Published Privately', 'learndash' ),
		'item_reverted_to_draft' => esc_html__( 'Group Reverted to Draft', 'learndash' ),
		'item_scheduled'	 =>	esc_html__( 'Group Scheduled', 'learndash' ),
		'item_updated'		 =>	esc_html__( 'Group Updated', 'learndash' ),
	);

	$capabilities = array(
		'read_post'              => 'read_group',
		'publish_posts'          => 'publish_groups',
		'edit_posts'             => 'edit_groups',
		'edit_others_posts'      => 'edit_others_groups',
		'delete_posts'           => 'delete_groups',
		'delete_others_posts'    => 'delete_others_groups',
		'read_private_posts'     => 'read_private_groups',
		'delete_post'            => 'delete_group',
		'edit_published_posts'   => 'edit_published_groups',
		'delete_published_posts' => 'delete_published_groups',
	);

	if ( is_admin() ) {
		$admin_role = get_role( 'administrator' );
		if ( ( $admin_role ) && ( $admin_role instanceof WP_Role ) ) {
			if ( ! $admin_role->has_cap( 'edit_groups' ) ) {
				foreach ( $capabilities as $key => $cap ) {
					$admin_role->add_cap( $cap );
				}
			}
		}
	}

	$args = array(
		'labels'              	=> $labels,
		'description'         	=> esc_html__( 'Holds LearnDash user Groups', 'learndash' ),
		'public'              	=> false,
		'menu_position'       	=> 10,
		'show_in_menu'        	=> true,
		'show_in_nav_menus'   	=> false,
		'supports'            	=> array( 'title', 'editor' ), //, 'custom-fields', 'author'
		'has_archive'         	=> false,
		'exclude_from_search' 	=> true,
		'publicly_queryable'  	=> false,
		'show_ui'             	=> true,
		'capability_type'       => 'group', 
		'capabilities'        	=> $capabilities,
		'map_meta_cap'        	=> true,
		'show_in_rest' 			=> LearnDash_REST_API::enabled( 'groups' ) || LearnDash_REST_API::gutenberg_enabled( 'groups' ),
		//'rest_base' 			=> LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'courses' ),
		'rest_controller_class' => LearnDash_REST_API::get_controller( 'groups' ),
	);

	/**
	 * Filter post type registration args
	 * 
	 * @var array $args
	 */
	$args = apply_filters( 'learndash_post_args_groups', $args );
	$args = apply_filters( 'learndash-cpt-options', $args, 'groups' );
	
	/**
	 * Filter via the common 'learndash_post_args' used for most all other 
	 * post_types in LearnDash
	 *
	 * @since 2.5.8
	 */
	// Removed. The world is not ready for this filter. 
	//$post_args = array( 'groups' => $args );
	//$post_args = apply_filters( 'learndash_post_args', $post_args );
	//if ( isset( $post_args['groups'] ) ) 
	//	$args = $post_args['groups'];
	
	register_post_type( 'groups', $args );
}

add_action( 'init', 'learndash_groups_post_content' );


/**
 * Add Group Leader role
 * 
 * @since 2.1.0
 */
function learndash_add_group_admin_role() {
	$group_leader = get_role( 'group_leader' );

	if ( is_null( $group_leader ) ) {
		$group_leader = add_role( 'group_leader', 'Group Leader', array( 'read' => true, 'group_leader' => true ) );
	} else {
		$group_leader->add_cap( 'group_leader', true ); 
	} 
}

//add_action( 'init', 'learndash_add_group_admin_role' );
add_action( 'learndash_activated', 'learndash_add_group_admin_role' );


/**
 * All group leader access to backend
 *
 * WooCommerce prevent access to dashboard for all non-admin user roles. This filter allows 
 * us to check if the current user is group_leader and override WC access
 *
 * @since 2.2.0.1
 * @param bool $prevent_access value from WC.
 * @return bool adjusted value based on user's access/role
 */
function learndash_check_group_leader_access( $prevent_access ) {
	if (learndash_is_group_leader_user('group_leader' ) ) {
		
		if (defined('LEARNDASH_GROUP_LEADER_DASHBOARD_ACCESS')) {
			if (LEARNDASH_GROUP_LEADER_DASHBOARD_ACCESS == true) {
				$prevent_access = false;
			} else if (LEARNDASH_GROUP_LEADER_DASHBOARD_ACCESS == false) {
				$prevent_access = true;
			}
		} else { 
			$prevent_access = false;
		}
	}

    return $prevent_access;
}
add_filter( 'woocommerce_prevent_admin_access', 'learndash_check_group_leader_access', 20, 1 );


/**
 * Get all group leaders user ids
 *
 * @return array of group leaders user ids
 * @since 2.1.2
 */
function learndash_all_group_leader_ids() {
	$group_leader_user_ids = array();
	$group_leader_users = learndash_all_group_leaders();
	if (!empty($group_leader_users)) {
		$group_leader_user_ids = wp_list_pluck( $group_users, 'ID' );
	}
	return $group_leader_user_ids;
}

/**
 * Get all group leaders
 *
 * @return array of group leaders user objects
 */
function learndash_all_group_leaders() {
	$transient_key = "learndash_group_leaders";
	//$group_user_objects = get_transient( $transient_key );
	$group_user_objects = LDLMS_Transients::get( $transient_key );
	if ( $group_user_objects === false ) {
	
		$user_query_args = array(
			'role'		=>	'group_leader',
			'orderby'	=>	'display_name',
			'order'		=>	'ASC'
		);

		$user_query = new WP_User_Query( $user_query_args );
		if ( isset( $user_query->results ) ) {
			$group_user_objects = $user_query->results;
		} else {
			$group_user_objects = array();
		}
		
		LDLMS_Transients::set( $transient_key, $group_user_objects, MINUTE_IN_SECONDS );
	}
	return $group_user_objects;
}

/**
 * Outputs list of group users in a table
 *
 * @since 2.1.0
 * 
 * @param  int 		$group_id
 * @return string
 */
function learndash_group_user_list( $group_id ) {
	global $wpdb;
	
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	$current_user = wp_get_current_user();

	if ( ( !learndash_is_admin_user( $current_user ) ) && ( !learndash_is_group_leader_user( $current_user ) ) ) {
		return esc_html__( 'Please login as a Group Administrator', 'learndash' );
	}

	$users = learndash_get_groups_users( $group_id );
	if ( ! empty( $users ) ) {
		?>
		<table cellspacing="0" class="wp-list-table widefat fixed groups_user_table">
		<thead>
			<tr>
				<th class="manage-column column-sno " id="sno" scope="col" ><?php esc_html_e( 'S. No.', 'learndash' );?></th>
				<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Name', 'learndash' );?></th>
				<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Username', 'learndash' );?></th>
				<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Email', 'learndash' );?></th>
				<th class="manage-column column-action" id="action" scope="col"><?php esc_html_e( 'Action', 'learndash' );?></span></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th class="manage-column column-sno " id="sno" scope="col" ><?php esc_html_e( 'S. No.', 'learndash' );?></th>
				<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Name', 'learndash' );?></th>
				<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Username', 'learndash' );?></th>
				<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Email', 'learndash' );?></th>
				<th class="manage-column column-action" id="action" scope="col"><?php esc_html_e( 'Action', 'learndash' );?></span></th>
			</tr>
		</tfoot>
		<tbody>
			<?php $sn = 1;
				foreach ( $users as $user ) {
					$name = isset( $user->display_name) ? $user->display_name : $user->user_nicename;
					?>
						<tr>
							<td><?php echo $sn++;?></td>
							<td><?php echo $name;?></td>
							<td><?php echo $user->user_login;?></td>
							<td><?php echo $user->user_email;?></td>
							<td><a href="<?php echo admin_url( 'edit.php?post_type=sfwd-courses&page=group_admin_page&group_id=' . $group_id . '&user_id=' . $user->ID );?>"><?php esc_html_e( 'Report', 'learndash' );?></a></td>
						</tr>
					<?php
				}
			?>
		</tbody>
		</table>
		<?php
	} else {
		return esc_html__( 'No users.', 'learndash' );
	}

}
// FPM: This is registered but is anyone using it? The related function takes a 
// group_id int. For a proper shortcode handler is should take an array where
// group_id is passed as in [learndash_group_user_list group_id="123"]
add_shortcode( 'learndash_group_user_list', 'learndash_group_user_list' );



/**
 * Get list of enrolled courses for a group
 *
 * @since 2.1.0
 * 
 * @param  int 		$group_id
 * @return array 	list of courses
 */
function learndash_group_enrolled_courses( $group_id = 0, $bypass_transient = false ) {
	global $wpdb;

	$group_courses_ids = array();
	
	if ( !empty( $group_id ) ) {

		$transient_key = "learndash_group_courses_" . $group_id;
		
		if (!$bypass_transient) {
			$group_courses_ids_transient = LDLMS_Transients::get( $transient_key );
	
		} else {
			$group_courses_ids_transient = false;
		}

		if ( $group_courses_ids_transient === false ) {
			$sql_str = $wpdb->prepare("SELECT post_id FROM ". $wpdb->postmeta ." as postmeta INNER JOIN ". $wpdb->posts ." as posts ON posts.ID=postmeta.post_id
				WHERE posts.post_type = %s AND posts.post_status = %s AND meta_key = %s", 'sfwd-courses', 'publish', 'learndash_group_enrolled_' . $group_id);
			$group_courses_ids = $wpdb->get_col( $sql_str );
			LDLMS_Transients::set( $transient_key, $group_courses_ids, MINUTE_IN_SECONDS );
			
		} else {
			$group_courses_ids = $group_courses_ids_transient;
		}
	}
	
	return $group_courses_ids;
}

/**
 * Set list of enrolled courses for a group
 *
 * @since 2.2.1
 * 
 * @param  int 		$group_id
 * @param  array 	$group_courses_new
 * @return none
 */
function learndash_set_group_enrolled_courses( $group_id = 0, $group_courses_new = array() ) {

	if (!empty( $group_id )) {

		$group_courses_old = learndash_group_enrolled_courses( $group_id, true );
	
		$group_courses_intersect = array_intersect( $group_courses_new, $group_courses_old );
		//error_log('group_courses_intersect<pre>'. print_r( $group_courses_intersect, true) .'</pre>');

		$group_courses_add = array_diff( $group_courses_new, $group_courses_intersect );
		//error_log('group_courses_add<pre>'. print_r($group_courses_add, true) .'</pre>');
		if ( !empty( $group_courses_add ) ) {
			foreach ( $group_courses_add as $course_id ) {
				//update_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, $group_id );
				ld_update_course_group_access( $course_id, $group_id, false );
			}
		}

		$group_courses_remove = array_diff( $group_courses_old, $group_courses_intersect );
		//error_log('group_courses_remove<pre>'. print_r($group_courses_remove, true) .'</pre>');
		if ( !empty( $group_courses_remove ) ) {
			foreach ( $group_courses_remove as $course_id ) {
				//update_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, $group_id );
				ld_update_course_group_access( $course_id, $group_id, true );
			}
		}
		
		// Finally clear our cache for other services 
		$transient_key = "learndash_group_courses_" . $group_id;
		delete_transient( $transient_key );
	}
}
	
/**
 * Group all the related course ids for a set of groups_ids
 *
 * @since 2.3
 * 
 * @param  int $user_id he user_id to get the associated groups then course ids. If not provided will user current user id
 * @param  array $group_ids an array of group ids to source the course ids from. If not provided will use group ids based on user_id access.
 * @return array an array of course_ids.
 */
	
function learndash_get_groups_courses_ids( $user_id = 0, $group_ids = array() ) {
	$course_ids = array();
	
	if ( empty( $user_id ) ) {
		// If the current user is not able to be determined. Then abort. 
		if ( !is_user_logged_in() ) return $course_ids;
		
		$user_id = get_current_user_id();
	}

	if ( learndash_is_group_leader_user( $user_id ) ) {
		$group_leader_group_ids = learndash_get_administrators_group_ids( $user_id );

		// If user is group leader and the group ids is empty, nothing else to do. abort. 
		if ( empty( $group_leader_group_ids ) ) return $course_ids;
		
		if ( empty( $group_ids ) ) {
			$group_ids = $group_leader_group_ids;
		} else {
			$group_ids = array_intersect( $group_leader_group_ids, $group_ids );
		}
	} else if ( learndash_is_admin_user( $user_id ) ) {
	} else {
		return $course_ids;
	}
	
	if ( !empty( $group_ids ) ) {	
	
		foreach ( $group_ids as $group_id ) {
			$group_course_ids = learndash_group_enrolled_courses( $group_id );
			if ( !empty( $group_course_ids ) ) {
				$course_ids = array_merge( $course_ids, $group_course_ids );
			}
		}
	}
	
	if ( !empty( $course_ids ) ) {
		$course_ids = array_unique( $course_ids );
	}
	
	return $course_ids;
}

/**
 * Is a group enrolled in a certain course
 *
 * @since 2.1.0
 * 
 * @param  int $group_id
 * @param  int $course_id
 * @return bool
 */
function learndash_group_has_course( $group_id, $course_id ) {
	return get_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, true );
}


/**
 * Gets timestamp of when course is available to group
 *
 * @since 2.1.0
 * 
 * @param  int 		$group_id
 * @param  int 		$course_id
 * @return string   time stamp
 */
function learndash_group_course_access_from( $group_id, $course_id ) {
	return get_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, true );
}



/**
 * Does a course belong to a group the user is in
 *
 * @since 2.1.0
 * 
 * @param  int $user_id
 * @param  int $course_id
 * @return bool
 */
function learndash_user_group_enrolled_to_course( $user_id, $course_id ) {
	$group_ids = learndash_get_users_group_ids( $user_id );
	if ( !empty( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			if ( learndash_group_has_course( $group_id, $course_id ) ) {
				return true;
			}
		}
	}
	return false;
}



/**
 * Gets timestamp of when course is available to a user in a group
 *
 * @since 2.1.0
 * 
 * @param  int 		$user_id
 * @param  int 		$course_id
 * @return string   timestamp
 */
function learndash_user_group_enrolled_to_course_from( $user_id, $course_id ) {
	$group_ids = learndash_get_users_group_ids( $user_id );
	
	$enrolled_from = time() + 10000;

	foreach ( $group_ids as $group_id ) {
		$enrolled_from_temp = learndash_group_course_access_from( $group_id, $course_id );

		if ( ! empty( $enrolled_from_temp ) && $enrolled_from_temp < $enrolled_from ) {
			$enrolled_from = $enrolled_from_temp;
		}
	}

	if ( $enrolled_from <= time() ) {
		$user = get_userdata( $user_id );
		$user_registered = strtotime( $user->user_registered );

		if ( $user_registered > $enrolled_from ) {
			return $user_registered;
		} else {
			return $enrolled_from;
		}
	} else {
		return null;
	}
}



/**
 * Get group ids that the user is an administrator of
 *
 * @since 2.1.0
 * 
 * @param  int 		$user_id
 * @return array 	list of group ids
 */
function learndash_get_administrators_group_ids( $user_id, $_MENU = false ) {
	global $wpdb;

	$group_ids = array();
	
	if ( !empty( $user_id ) ) {
		if ( ( learndash_is_admin_user( $user_id ) ) && ( $_MENU != true ) ) {
			$group_ids = learndash_get_groups( true, $user_id );
		} else {
			// LD 2.3 rewrote this double query to be a simple join
			$sql_str = $wpdb->prepare( "SELECT usermeta.meta_value as group_ids FROM ". $wpdb->usermeta ." as usermeta INNER JOIN ". $wpdb->posts ." as posts ON posts.ID=usermeta.meta_value WHERE  user_id = %d  AND meta_key LIKE %s AND posts.post_status = 'publish'", $user_id, 'learndash_group_leaders_%');
			$group_ids = $wpdb->get_col( $sql_str );
		}
	}

	return $group_ids;	
}

/**
 * Set group ids that the user is an administrator of
 *
 * @since 2.2.1
 * 
 * @param  int 		$user_id
 * @param  array 	list of group ids
 */
function learndash_set_administrators_group_ids( $user_id, $leader_groups_new = array() ) {
	global $wpdb;

	if ( !empty($user_id ) ) {
		
		$leader_groups_old = learndash_get_administrators_group_ids( $user_id, true );
		$leader_groups_intersect = array_intersect( $leader_groups_new, $leader_groups_old );

		$leader_groups_add = array_diff( $leader_groups_new, $leader_groups_intersect );
		if ( !empty( $leader_groups_add ) ) {
			foreach ( $leader_groups_add as $group_id ) {
				ld_update_leader_group_access( $user_id, $group_id, false);
			}
		}

		$leader_groups_remove = array_diff( $leader_groups_old, $leader_groups_intersect );
		if ( !empty( $leader_groups_remove ) ) {
			foreach ( $leader_groups_remove as $group_id ) {
				ld_update_leader_group_access( $user_id, $group_id, true);
			}
		}
	
		// Finally clear our cache for other services 
		//$transient_key = "learndash_user_groups_" . $user_id;
		//delete_transient( $transient_key );
	}
	return array();	
}



/**
 * Get all groups
 * 
 * @since 2.1.0
 * 
 * @param  bool 	$id_only 	return id's only
 * @return array         		groups
 */
function learndash_get_groups( $id_only = false, $current_user_id = 0 ) {

	if ( empty( $current_user_id ) ) {
		if ( !is_user_logged_in() ) return array();
		$current_user_id = get_current_user_id();
	}

	if ( learndash_is_group_leader_user( $current_user_id ) ) {
		return learndash_get_administrators_group_ids( $current_user_id );
	} else if ( learndash_is_admin_user( $current_user_id ) ) {
		
		$groups_query_args = array(
			'post_type'		=>	'groups',
			'nopaging'		=>	true
		);
	
		if ( $id_only ) {
			$groups_query_args['fields'] = 'ids';
		}
	
		$groups_query = new WP_Query( $groups_query_args );
		return $groups_query->posts;
	}
}

/**
 * Get a users group id's
 * 
 * @since 2.1.0
 * 
 * @param  int 		$user_id
 * @return array    list of groups user belongs to
 */
function learndash_get_users_group_ids( $user_id = 0, $bypass_transient = false ) {
	global $wpdb;

	$user_group_ids = array();
	
	if ( !empty( $user_id ) ) {
		$transient_key = "learndash_user_groups_" . $user_id;

		if (!$bypass_transient) {
			$user_group_ids_transient = LDLMS_Transients::get( $transient_key );
		} else {
			$user_group_ids_transient = false;
		}
		
		if ( $user_group_ids_transient === false ) {
			$sql_str = $wpdb->prepare("SELECT meta_value FROM ". $wpdb->usermeta ." WHERE meta_key LIKE %s AND user_id = %d and meta_value != ''", 
				'learndash_group_users_%', $user_id );

			$col = $wpdb->get_col( $sql_str );
			
			if ( ! empty( $col) && ! empty( $col[0] ) ) {
				$sql_str = "SELECT ID FROM $wpdb->posts WHERE post_type='groups' AND post_status = 'publish' AND ID IN (" . implode( ',', $col ) . ')';
				
				$user_group_ids = $wpdb->get_col( $sql_str );
			}

			LDLMS_Transients::set( $transient_key, $user_group_ids, MINUTE_IN_SECONDS );
			
		} else {
			$user_group_ids = $user_group_ids_transient;
		}
	}

	return $user_group_ids;
}

function learndash_set_users_group_ids( $user_id = 0, $user_groups_new = array() ) {

	if ( !empty($user_id ) ) {
		
		$user_groups_old = learndash_get_users_group_ids( $user_id, true );
		
		$user_groups_intersect = array_intersect( $user_groups_new, $user_groups_old );

		$user_groups_add = array_diff( $user_groups_new, $user_groups_intersect );
		if ( !empty( $user_groups_add ) ) {
			foreach ( $user_groups_add as $group_id ) {
				ld_update_group_access( $user_id, $group_id, false);
			}
		}

		$user_groups_remove = array_diff( $user_groups_old, $user_groups_intersect );
		if ( !empty( $user_groups_remove ) ) {
			foreach ( $user_groups_remove as $group_id ) {
				ld_update_group_access( $user_id, $group_id, true);
			}
		}
	
		// Finally clear our cache for other services 
		$transient_key = "learndash_user_groups_" . $user_id;
		delete_transient( $transient_key );
	}
}

/**
 * Get a Course group id's
 * 
 * @since 2.2.1
 * 
 * @param  int 		$course_id
 * @return array    list of groups user belongs to
 */
function learndash_get_course_groups( $course_id = 0, $bypass_transient = false ) {
	global $wpdb;

	$course_groups_ids = array();
	
	if ( !empty( $course_id ) ) {
		$transient_key = "learndash_course_groups_" . $course_id;
		if (!$bypass_transient) {
			$course_groups_ids_transient = LDLMS_Transients::get( $transient_key );
		} else {
			$course_groups_ids_transient = false;
		}
		
		if ( $course_groups_ids_transient === false ) {
			// For Groups the meta_value is a datetime. So we need mysql to parse the meta_key to get the group_id
			$sql_str = $wpdb->prepare("SELECT DISTINCT REPLACE(meta_key, 'learndash_group_enrolled_', '') FROM ". $wpdb->postmeta ." WHERE meta_key LIKE %s AND post_id = %d and meta_value != ''", 'learndash_group_enrolled_%', $course_id );
			//error_log('sql_str['. $sql_str .']');
			
			$col = $wpdb->get_col( $sql_str );
			//error_log('col<pre>'. print_r($col, true) .'</pre>');
			
			if ( ! empty( $col) && ! empty( $col[0] ) ) {
				
				$sql_str = "SELECT ID FROM $wpdb->posts WHERE post_type='groups' AND post_status = 'publish' AND ID IN (" . implode( ',', $col ) . ')';
				$course_groups_ids = $wpdb->get_col( $sql_str );
				LDLMS_Transients::set( $transient_key, $course_groups_ids, MINUTE_IN_SECONDS );
			}
		} else {
			$course_groups_ids = $course_groups_ids_transient;
		}
	}

	return $course_groups_ids;
}

function learndash_set_course_groups( $course_id = 0, $course_groups_new = array() ) {

	if ( !empty($course_id ) ) {
		
		$course_groups_old = learndash_get_course_groups( $course_id, true );
		//error_log('course_groups_old<pre>'. print_r($course_groups_old, true) .'</pre>');
		
		$course_groups_intersect = array_intersect( $course_groups_new, $course_groups_old );
		//error_log('course_groups_intersect<pre>'. print_r($course_groups_intersect, true) .'</pre>');
		
		$course_groups_add = array_diff( $course_groups_new, $course_groups_intersect );
		//error_log('course_groups_add<pre>'. print_r($course_groups_add, true) .'</pre>');
		
		if ( !empty( $course_groups_add ) ) {
			foreach ( $course_groups_add as $group_id ) {
				//ld_update_group_access( $user_id, $group_id, false);
				ld_update_course_group_access( $course_id, $group_id, false );
			}
		}

		$course_groups_remove = array_diff( $course_groups_old, $course_groups_intersect );
		//error_log('course_groups_remove<pre>'. print_r($course_groups_remove, true) .'</pre>');
		if ( !empty( $course_groups_remove ) ) {
			foreach ( $course_groups_remove as $group_id ) {
				//ld_update_group_access( $user_id, $group_id, true);
				ld_update_course_group_access( $course_id, $group_id, true );
			}
		}
	
		// Finally clear our cache for other services 
		$transient_key = "learndash_course_groups_" . $course_id;
		delete_transient( $transient_key );
	}
}


/**
 * Get users ids that belong to a group
 * 
 * @since 2.1.0
 * 
 * @param  int 		$group_id
 * @param  bool 	$bypass_transient to ignore transient cache
 * @return array 	array of user ids that belong to group
 */
function learndash_get_groups_user_ids( $group_id = 0, $bypass_transient = false) {
	$group_user_ids = array();
	if (!empty( $group_id ) ) {
		$group_users = learndash_get_groups_users( $group_id, $bypass_transient );
		if (!empty( $group_users ) ) {
			$group_user_ids = wp_list_pluck( $group_users, 'ID' );
		}
	}
	return $group_user_ids;
}

/**
 * Get users objects that belong to a group
 * 
 * @since 2.1.2
 * 
 * @param  int 		$group_id
 * @param  bool 	$bypass_transient to ignore transient cache
 * @return array 	list of users objects that belong to group
 */
function learndash_get_groups_users( $group_id, $bypass_transient = false ) {
	//echo "group_id[". $group_id ."] [". $bypass_transient ."]<br />";
	
	if (!$bypass_transient) {
		$transient_key = "learndash_group_users_" . $group_id;
		$group_users_objects = LDLMS_Transients::get( $transient_key );
	} else {
		$group_users_objects = false;
	}
	//echo "group_users_objects<pre>"; print_r($group_users_objects); echo "</pre>";
	
	if ( $group_users_objects === false ) {
	
		// Changed in v2.3 we no longer exclude ALL group leaders from groups. 
		// A group leader CAN be a member of a group user list.
		
		// For this group get the group leaders. They will be excluded from the regular users. 
		//$group_leader_user_ids = learndash_get_groups_administrator_ids( $group_id );
		
		$user_query_args = array(
			//'exclude' 	=> 	$group_leader_user_ids,
			'orderby' 	=>	'display_name',
			'order'	 	=>	'ASC',
			'meta_query' => array(
				array(
					'key'     	=> 	'learndash_group_users_'. intval( $group_id ),
					'compare' 	=> 	'EXISTS',
				)
			)
		);
		$user_query = new WP_User_Query( $user_query_args );
		if ( isset( $user_query->results ) ) {
			$group_users_objects = $user_query->results;
		} else {
			$group_users_objects = array();
		}
		
		if (!$bypass_transient) {
			LDLMS_Transients::set( $transient_key, $group_users_objects, MINUTE_IN_SECONDS );
		}
	}

	return $group_users_objects;
}


/**
 * Set Group Users users 
 * 
 * @since 2.1.2
 * 
 * @param  int 		$group_id
 * @param  array 	list of users objects
 * @return none
 */
function learndash_set_groups_users( $group_id = 0, $group_users_new = array() ) {

	update_post_meta( $group_id, 'learndash_group_users_' . $group_id, $group_users_new);

	$group_users_old = learndash_get_groups_user_ids( $group_id, true );
	
	$group_users_intersect = array_intersect( $group_users_new, $group_users_old );

	$group_users_add = array_diff( $group_users_new, $group_users_intersect );
	if ( !empty( $group_users_add ) ) {
		foreach ( $group_users_add as $user_id ) {
			//update_user_meta( $user_id, 'learndash_group_users_' . $group_id, $group_id );
			ld_update_group_access( $user_id, $group_id, false);
		}
	}

	$group_users_remove = array_diff( $group_users_old, $group_users_intersect );
	if ( !empty( $group_users_remove ) ) {
		foreach ( $group_users_remove as $user_id ) {
			//delete_user_meta( $user_id, 'learndash_group_users_' . $group_id, null );
			ld_update_group_access( $user_id, $group_id, true);
		}

		/**
		 * Action hook for remove group users
		 *
		 * $group_id 			int 	ID of the group
		 * $group_users_remove 	array 	User IDs that are removed from group
		 */
		do_action( 'learndash_remove_group_users', $group_id, $group_users_remove );
	}
	
	// Finally clear our cache for other services 
	$transient_key = "learndash_group_users_" . $group_id;
	delete_transient( $transient_key );
}

/**
 * Get admin id's that belong to group
 * 
 * @since 2.1.0
 * 
 * @param  int 		$group_id
 * @param  bool 	$bypass_transient to ignore transient cache
 * @return array 	array of goup leader user ids
 */
function learndash_get_groups_administrator_ids( $group_id, $bypass_transient = false ) {
	/*
	global $wpdb;

	if ( is_numeric( $group_id ) ) {
		return $wpdb->get_col( " SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'learndash_group_leaders_" . $group_id . "' AND meta_value = '" . $group_id . "'" );
	} else {
		return array();
	}
	*/
	
	$group_leader_user_ids = array();
	
	if ( !empty( $group_id ) ) {
		$group_leader_users = learndash_get_groups_administrators( $group_id, $bypass_transient );
		if ( !empty( $group_leader_users ) ) {
			$group_leader_user_ids = wp_list_pluck( $group_leader_users, 'ID' );
		}
	}
	return $group_leader_user_ids;
}

/**
 * Get group_leader user objects that belong to group
 * 
 * @since 2.1.2
 * 
 * @param  int 		$group_id
 * @param  bool 	$bypass_transient to ignore transient cache
 * @return array 	array of group leader user objects
 */
function learndash_get_groups_administrators( $group_id, $bypass_transient = false ) {
	
	$transient_key = "learndash_group_leaders_" . $group_id;

	if ( !$bypass_transient ) {
		$group_user_objects = LDLMS_Transients::get( $transient_key );
	} else {
		$group_user_objects = false;
	}
	if ( $group_user_objects === false ) {
		
		$user_query_args = array(
			//'role'		=>	'group_leader',
			'orderby'	=>	'display_name',
			'order'		=>	'ASC',
			'meta_query' => array(
				array(
					'key'     	=> 	'learndash_group_leaders_'. intval( $group_id ),
					'value'   	=> 	intval( $group_id ),
					'compare' 	=> 	'=',
					'type'		=>	'NUMERIC'
				)
			)
		);
		$user_query = new WP_User_Query( $user_query_args );
		if ( isset( $user_query->results ) ) {
			$group_user_objects = $user_query->results;
		} else {
			$group_user_objects = array();
		}
		
		if ( !$bypass_transient ) {
			LDLMS_Transients::set( $transient_key, $group_user_objects, MINUTE_IN_SECONDS );
		}
	}
	
	return $group_user_objects;
}

/**
 * Set Group Leader/Administrator users 
 * 
 * @since 2.1.2
 * 
 * @param  int 		$group_id
 * @param  array 	list of users objects
 * @return none
 */
function learndash_set_groups_administrators( $group_id = 0, $group_leaders_new = array() ) {
	
	if (!empty( $group_id )) {
	
		$group_leaders_old = learndash_get_groups_administrator_ids( $group_id, true );

		$group_leaders_intersect = array_intersect( $group_leaders_new, $group_leaders_old );
		//error_log('$group_leaders_intersect<pre>'. print_r( $group_leaders_intersect, true) .'</pre>');

		$group_leaders_add = array_diff( $group_leaders_new, $group_leaders_intersect );
		//error_log('group_leaders_add<pre>'. print_r($group_leaders_add, true) .'</pre>');
		if ( !empty( $group_leaders_add ) ) {
			foreach ( $group_leaders_add as $user_id ) {
				update_user_meta( $user_id, 'learndash_group_leaders_' . $group_id, $group_id );
			}
		}

		$group_leaders_remove = array_diff( $group_leaders_old, $group_leaders_intersect );
		//error_log('group_leaders_remove<pre>'. print_r( $group_leaders_remove, true) .'</pre>');
		if ( !empty( $group_leaders_remove ) ) {
			foreach ( $group_leaders_remove as $user_id ) {
				delete_user_meta( $user_id, 'learndash_group_leaders_' . $group_id, $group_id );
			}
		}
		
		// Finally clear our cache for other services 
		$transient_key = "learndash_group_leaders_" . $group_id;
		delete_transient( $transient_key );
	}
}

/**
 * Is user a group leader
 * Replaces is_group_leader
 * @since 2.3
 * 
 * @param  int|object $user
 * @return bool true if user is group leader, else false
 */
function learndash_is_group_leader_user( $user = 0 ) {
	$user_id = 0;
	
	if ( ( is_numeric( $user ) ) && ( !empty( $user ) ) ) {
		$user_id = $user;
	} else if ($user instanceof WP_User) {
		$user_id = $user->ID;
	} else {
		$user_id = get_current_user_id();
	}
	
	if ( ( !empty( $user_id ) ) && ( !learndash_is_admin_user( $user_id ) ) && ( defined( 'LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK' ) ) && ( LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK != '' ) ) {
		return user_can( $user_id, LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK );
	}	
}


function learndash_is_admin_user( $user = 0 ) {
	$user_id = 0;
	
	if ( ( is_numeric( $user ) ) && ( !empty( $user ) ) ) {
		$user_id = $user;
	} else if ($user instanceof WP_User) {
		$user_id = $user->ID;
	} else {
		$user_id = get_current_user_id();
	}
	
	if ( ( !empty( $user_id ) ) && ( defined( 'LEARNDASH_ADMIN_CAPABILITY_CHECK' ) ) && ( LEARNDASH_ADMIN_CAPABILITY_CHECK != '' ) ) {
		return user_can( $user_id, LEARNDASH_ADMIN_CAPABILITY_CHECK );
	}	
}





/**
 * Is group leader an admin of a group this user belongs to
 *
 * @since 2.1.0
 * 
 * @param  int 	$group_leader_id
 * @param  int 	$user_id
 * @return bool
 */
function learndash_is_group_leader_of_user( $group_leader_id, $user_id ) {
	$admin_groups     = learndash_get_administrators_group_ids( $group_leader_id );
	$has_admin_groups = ! empty( $admin_groups ) && is_array( $admin_groups ) && ! empty( $admin_groups[0] );

	foreach ( $admin_groups as $group_id ) {
		$learndash_is_user_in_group = learndash_is_user_in_group( $user_id, $group_id );

		if ( $learndash_is_user_in_group ) {
			return true;
		}
	}

	return false;
}



/**
 * Does user belong to group
 *
 * @since 2.1.0
 * 
 * @param  int 	$user_id
 * @param  int 	$group_id
 * @return bool
 */
function learndash_is_user_in_group( $user_id, $group_id ) {
	return get_user_meta( $user_id, 'learndash_group_users_' . $group_id, true );
}



/**
 * Shortcode to HTML output of display a users list of groups
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 	shortcode attributes
 * @return string           shortcode output
 */
function learndash_user_groups( $attr ) {

	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts = shortcode_atts(
		array(
			'user_id' => '',
		), 
		$attr
	);

	extract( $shortcode_atts );

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return '';
	}

	$admin_groups = learndash_get_administrators_group_ids( $user_id );
	$user_groups = learndash_get_users_group_ids( $user_id );
	$has_admin_groups = ! empty( $admin_groups ) && is_array( $admin_groups ) && ! empty( $admin_groups[0] );
	$has_user_groups  = ! empty( $user_groups ) && is_array( $user_groups ) && ! empty( $user_groups[0] );

	return SFWD_LMS::get_template('user_groups_shortcode', array(
			'admin_groups'     => $admin_groups,
			'user_groups'      => $user_groups,
			'has_admin_groups' => $has_admin_groups,
			'has_user_groups'  => $has_user_groups,
		)
	);
}

add_shortcode( 'user_groups', 'learndash_user_groups' );



/**
 * Delete group id from all users meta when group is deleted
 *
 * @todo  restrict function to only run if post type is grou
 *        will run against db everytime a post is deleted
 * 
 * @since 2.1.0
 * 
 * @param  int 	$pid  id of group
 * @return bool       successful deletion
 */
function learndash_delete_group( $pid ) {
	global $wpdb;

	if ( ! empty( $pid ) && is_numeric( $pid ) ) {
		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'learndash_group_users_' . $pid, 'meta_value' => $pid ) );
		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'learndash_group_leaders_' . $pid, 'meta_value' => $pid ) );
	}

	return true;
}

add_action( 'delete_post', 'learndash_delete_group', 10 );



/**
 * Update a users group access
 * 
 * @since 2.1.0
 * 
 * @param  int  $user_id
 * @param  int  $group_id
 * @param  bool $remove
 */
function ld_update_group_access( $user_id, $group_id, $remove = false ) {
	if ( $remove ) {

		delete_user_meta( $user_id, 'learndash_group_users_' . $group_id );

		/**
		 * Run actions after group access is removed for user
		 * 
		 * @since 2.1.0
		 * 
		 * @param  int  $user_id
		 */
		do_action( 'ld_removed_group_access', $user_id, $group_id );

	} else {
		
		update_user_meta( $user_id, 'learndash_group_users_' . $group_id, $group_id );

		/**
		 * Run actions after group access is added for user
		 * 
		 * @since 2.1.0
		 * 
		 * @param  int  $user_id
		 */
		do_action( 'ld_added_group_access', $user_id, $group_id );
	}
}


/**
 * Update a users group access
 * 
 * @since 2.1.0
 * 
 * @param  int  $user_id
 * @param  int  $group_id
 * @param  bool $remove
 */
function ld_update_course_group_access( $course_id, $group_id, $remove = false ) {
	if ( $remove ) {

		delete_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id );

		/**
		 * Run actions after group access is removed for user
		 * 
		 * @since 2.1.0
		 * 
		 * @param  int  $user_id
		 */
		do_action( 'ld_removed_course_group_access', $course_id, $group_id );

	} else {
		
		update_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, time() );

		/**
		 * Run actions after group access is added for user
		 * 
		 * @since 2.1.0
		 * 
		 * @param  int  $user_id
		 */
		do_action( 'ld_added_course_group_access', $course_id, $group_id );

	}
}


/**
 * Update a group leader group access
 * 
 * @since 2.2.1
 * 
 * @param  int  $user_id
 * @param  int  $group_id
 * @param  bool $remove
 */
function ld_update_leader_group_access( $user_id, $group_id, $remove = false ) {
	if ( $remove ) {

		delete_user_meta( $user_id, 'learndash_group_leaders_' . $group_id );

		/**
		 * Run actions after group access is removed for user
		 * 
		 * @since 2.1.0
		 * 
		 * @param  int  $user_id
		 */
		do_action( 'ld_removed_leader_group_access', $user_id, $group_id );

	} else {
		
		update_user_meta( $user_id, 'learndash_group_leaders_' . $group_id, $group_id );

		/**
		 * Run actions after group access is added for user
		 * 
		 * @since 2.1.0
		 * 
		 * @param  int  $user_id
		 */
		do_action( 'ld_added_leader_group_access', $user_id, $group_id );
	}
}


/**
 * For a given Course and if course is associated with a group(s) get all user_ids
 * 
 * @since 2.3
 * 
 * @param  int  $course_id
 * @return array 	array of user_ids
 */
function get_course_groups_users_access( $course_id = 0 ) {
	$user_ids = array();
	
	if ( !empty( $course_id ) ) {
		$course_groups = learndash_get_course_groups( $course_id );
		//error_log('course_groups<pre>'. print_r($course_groups, true) .'</pre>');
		
		if ( !empty( $course_groups ) ) {
			foreach( $course_groups as $group_id ) {
				$group_users_ids = learndash_get_groups_user_ids( $group_id );
				if ( !empty( $group_users_ids ) ) {
					$user_ids = array_merge( $user_ids, $group_users_ids );
				}
			}
		}
	}

	if ( !empty( $user_ids ) ) {
		$user_ids = array_unique( $user_ids );
	}
	
	return $user_ids;	
}


/**
 * Get all Quizzes related to Group Courses
 * 
 * Given a group ID will determine all Quizzes associated with Courses of the group
 *
 * @since 2.3
 * 
 * @param  int  $group_id
 * @return array 	array of quiz_ids
 */
function learndash_get_group_course_quiz_ids( $group_id = 0 ) {
	$group_quiz_ids = array();
	if ( !empty( $group_id ) ) {
		
		$group_course_ids = learndash_group_enrolled_courses( intval( $group_id ) );
		if ( !empty( $group_course_ids ) ) {
			foreach( $group_course_ids as $course_id ) {
				$group_quiz_query_args = array(
					'post_type'		=>	'sfwd-quiz',
					'nopaging'		=>	true,
					'fields'		=>	'ids',
					'meta_query' => array(
						'relation' 	=> 'OR',
						array(
							'key'     => 'course_id',
							'value'   => $course_id,
							'compare' => '=',
						),
						array(
							'key'     => 'ld_course_' . $course_id,
							'value'   => $course_id,
							'compare' => '=',
						)
					)
				);

				$group_quiz_query = new WP_Query( $group_quiz_query_args );
				if ( ! empty( $group_quiz_query->posts ) ) {
					$group_quiz_ids = array_merge( $group_quiz_ids, $group_quiz_query->posts );
					$group_quiz_ids = array_unique( $group_quiz_ids );
				}
			}
		}
	}
	
	return $group_quiz_ids;
}