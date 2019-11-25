<?php
/**
 * Handles assignment uploads and includes helper functions for assignments
 *
 * @since 2.1.0
 *
 * @package LearnDash\Assignments
 */

/**
 * Upload, delete, and mark assignments as complete
 *
 * @since 2.1.0
 */
function learndash_assignment_process_init() {

	if ( ( isset( $_POST['uploadfile'] ) ) && ( ! empty( $_POST['uploadfile'] ) ) && ( isset( $_POST['post'] ) ) && ( ! empty( $_POST['post'] ) ) && ( isset( $_POST['course_id'] ) && ( ! empty( $_POST['course_id'] ) ) ) ) {
		$course_id = intval( $_POST['course_id'] );
		$post_id   = intval( $_POST['post'] );

		// 1. Verify nonce
		if ( ! wp_verify_nonce( $_POST['uploadfile'], 'uploadfile_' . get_current_user_id() . '_' . $post_id ) ) {
			return;
		}

		// 2. Verify lesson/topic is set to accept assignment uploads. The 'lesson_assignment_upload'
		// should return 'on' if assignment uploads are enabled
		if ( 'on' !== learndash_get_setting( $post_id, 'lesson_assignment_upload' ) ) {
			return;
		}

		// 3. Verify the lesson/topic is from the correct course.
		$courses = learndash_get_courses_for_step( $post_id, true );
		if ( ( empty( $courses ) ) || ( ! isset( $courses[ $course_id ] ) ) ) {
			return;
		}

		// 4. Verify the user is logged in or allow external filtering
		if ( ! is_user_logged_in() ) {
			if ( ! apply_filters( 'learndash_assignment_upload_user_check', false, $course_id, $post_id ) ) {
				return;
			}
		}

		$file = $_FILES['uploadfiles'];

		if ( ( ! empty( $file['name'][0] ) ) && ( learndash_check_upload( $file, $post_id ) ) ) {
			$file_desc = learndash_fileupload_process( $file, $post_id );
			$file_name = $file_desc['filename'];
			$file_link = $file_desc['filelink'];
			$params    = array(
				'filelink' => $file_link,
				'filename' => $file_name,
			);
		}
	}

	if ( ! empty( $_GET['learndash_delete_attachment'] ) ) {
		$assignment_post = get_post( intval( $_GET['learndash_delete_attachment'] ) );
		if ( ( isset( $assignment_post ) ) && ( $assignment_post instanceof WP_Post ) && ( learndash_get_post_type_slug( 'assignment' ) === $assignment_post->post_type ) ) {
			$current_user_id = get_current_user_id();

			if ( ( $assignment_post->post_author == $current_user_id ) || ( learndash_is_admin_user( $current_user_id ) ) || ( learndash_is_group_leader_of_user( $current_user_id, $assignment_post->post_author ) ) ) {

				$course_id = get_post_meta( $assignment_post->ID, 'course_id', true );
				if ( empty( $course_id ) ) {
					$course_id = learndash_get_course_id( $assignment_post->ID );
				}
				$course_step_id = get_post_meta( $assignment_post->ID, 'lesson_id', true );

				learndash_process_mark_incomplete( $current_user_id, $course_id, $course_step_id );

				wp_delete_post( $assignment_post->ID, apply_filters( 'learndash_assignment_force_delete', true, $assignment_post->ID, $assignment_post ) );

				update_user_meta(
					get_current_user_id(),
					'ld_assignment_message',
					array(
						array(
							'type'    => 'success',
							'message' => esc_html__( 'Assignment successfully deleted.', 'learndash' ),
						),
					)
				);

				$return_url = remove_query_arg( 'learndash_delete_attachment' );
				wp_safe_redirect( $return_url );
				die();
			}
		}
	}

	if ( ! empty( $_POST['attachment_mark_complete'] ) && ! empty( $_POST['userid'] ) ) {
		$lesson_id       = $_POST['attachment_mark_complete'];
		$current_user_id = get_current_user_id();
		$user_id         = $_POST['userid'];

		if ( ( learndash_is_admin_user( $current_user_id ) ) || ( learndash_is_group_leader_of_user( $current_user_id, $user_id ) ) ) {
			learndash_approve_assignment( $user_id, $lesson_id );
		}
	}
}

add_action( 'parse_request', 'learndash_assignment_process_init', 1 );

/**
 * Get a users assignments
 *
 * @since 2.1.0
 *
 * @param  int   $post_id
 * @param  int   $user_id
 * @return array WP_Post Assigment objects
 */
function learndash_get_user_assignments( $post_id, $user_id, $course_id = 0 ) {
	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
	}

	$opt = array(
		'post_type'      => 'sfwd-assignment',
		'posts_per_page' => - 1,
		'author'         => $user_id,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => 'lesson_id',
				'value'   => $post_id,
				'compare' => '=',
			),
			array(
				'key'     => 'course_id',
				'value'   => $course_id,
				'compare' => '=',
			),
		),
	);
	return get_posts( $opt );
}

/**
 * Migrate assignments from post meta to Assignments custom post type
 *
 * @since 2.1.0
 */
function learndash_assignment_migration() {
	if ( ! learndash_is_admin_user() ) {
		return;
	}

	global $wpdb;
	$old_assignment_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'sfwd_lessons-assignment'" );

	if ( ! empty( $old_assignment_ids ) && ! empty( $old_assignment_ids[0] ) ) {

		foreach ( $old_assignment_ids as $post_id ) {
			$assignment_meta_data = get_post_meta( $post_id, 'sfwd_lessons-assignment', true );

			if ( ! empty( $assignment_meta_data ) && ! empty( $assignment_meta_data['assignment'] ) ) {
				$assignment_data      = $assignment_meta_data['assignment'];
				$post                 = get_post( $post_id );
				$assignment_posts_ids = array();

				if ( ! empty( $assignment_data ) ) {
					$error = false;

					foreach ( $assignment_data as $k => $v ) {

						if ( empty( $v['file_name'] ) ) {
							continue;
						}

						$fname     = $v['file_name'];
						$dest      = $v['file_link'];
						$username  = $v['user_name'];
						$dispname  = $v['disp_name'];
						$file_path = $v['file_path'];

						if ( ! empty( $v['user_name'] ) ) {
							$user = get_user_by( 'login', $v['user_name'] );
						}

						$course_id = learndash_get_course_id( $post->ID );

						$assignment_meta = array(
							'file_name'    => $fname,
							'file_link'    => $dest,
							'user_name'    => $username,
							'disp_name'    => $dispname,
							'file_path'    => $file_path,
							'user_id'      => @$user->ID,
							'lesson_id'    => $post->ID,
							'course_id'    => $course_id,
							'lesson_title' => $post->post_title,
							'lesson_type'  => $post->post_type,
							'migrated'     => '1',
						);

						$assignment = array(
							'post_title'   => $fname,
							'post_type'    => learndash_get_post_type_slug( 'assignment' ),
							'post_status'  => 'publish',
							'post_content' => "<a href='" . $dest . "' target='_blank'>" . $fname . '</a>',
							'post_author'  => @$user->ID,
						);

						$assignment_post_id = wp_insert_post( $assignment );

						if ( $assignment_post_id ) {
							$assignment_posts_ids[] = $assignment_post_id;

							foreach ( $assignment_meta as $key => $value ) {
								update_post_meta( $assignment_post_id, $key, $value );
							}

							if ( learndash_is_assignment_approved( $assignment_post_id ) === true ) {
								learndash_approve_assignment_by_id( $assignment_post_id );
							}
						} else {
							$error = true;

							foreach ( $assignment_posts_ids as $assignment_posts_id ) {
								wp_delete_post( $assignment_posts_id, true );
							}

							break;
						}
					}

					if ( ! $error ) {
						global $wpdb;
						$wpdb->query(
							$wpdb->prepare(
								"UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s AND post_id = %d",
								'sfwd_lessons-assignment_migrated',
								'sfwd_lessons-assignment',
								$post_id
							)
						);
					}
				}
			}
		}
	}
}

add_action( 'admin_init', 'learndash_assignment_migration' );

/**
 * Get list of all assignments
 *
 * @todo  first argument not used
 * @since 2.1.0
 *
 * @param  object    $post  not used
 * @return array     $posts array of post objects
 */
function learndash_get_assignments_list( $post ) {
	$posts = get_posts( 'post_type=sfwd-assignment&posts_per_page=-1' );

	if ( ! empty( $posts ) ) {

		foreach ( $posts as $key => $p ) {
			$meta = get_post_meta( $p->ID, '', true );

			foreach ( $meta as $meta_key => $value ) {

				if ( is_string( $value ) || is_numeric( $value ) ) {
					$posts[ $key ]->{$meta_key} = $value;
				} elseif ( is_string( $value[0] ) || is_numeric( $value[0] ) ) {
					$posts[ $key ]->{$meta_key} = $value[0];
				}

				if ( 'file_path' === $meta_key ) {
					$posts[ $key ]->{$meta_key} = rawurldecode( $posts[ $key ]->{$meta_key} );
				}
			}
		}
	}

	return $posts;
}

/**
 * Function to handle assignment uploads
 * Takes Post ID, filename as arguments(We don't want to store BLOB data there)
 *
 * @todo  How is this different from learndash_assignment_process_init() ?
 *
 * @since 2.1.0
 *
 * @param  int $post_id
 * @param  int $fname    filename
 */
function learndash_upload_assignment_init( $post_id, $fname ) {
	//Initialize an empty array
	global $wp;

	if ( ! function_exists( 'wp_get_current_user' ) ) {
		include ABSPATH . 'wp-includes/pluggable.php';
	}

	$new_assignmnt_meta = array();
	$current_user       = wp_get_current_user();
	$username           = $current_user->user_login;
	$dispname           = $current_user->display_name;
	$userid             = $current_user->ID;
	$url_link_arr       = wp_upload_dir();
	$url_link           = $url_link_arr['baseurl'];
	$dir_link           = $url_link_arr['basedir'];
	$file_path          = $dir_link . '/assignments/';
	$url_path           = $url_link . '/assignments/' . $fname;

	if ( file_exists( $file_path . $fname ) ) {
		$dest = $url_path;
	} else {
		return;
	}

	update_post_meta( $post_id, 'sfwd_lessons-assignment', $new_assignmnt_meta );
	$post      = get_post( $post_id );
	$course_id = learndash_get_course_id( $post->ID );

	$assignment_meta = array(
		'file_name'    => $fname,
		'file_link'    => $dest,
		'user_name'    => $username,
		'disp_name'    => $dispname,
		'file_path'    => rawurlencode( $file_path . $fname ),
		'user_id'      => $current_user->ID,
		'lesson_id'    => $post->ID,
		'course_id'    => $course_id,
		'lesson_title' => $post->post_title,
		'lesson_type'  => $post->post_type,
	);

	$points_enabled = learndash_get_setting( $post, 'lesson_assignment_points_enabled' );

	if ( 'on' === $points_enabled ) {
		$assignment_meta['points'] = 'pending';
	}

	$assignment = array(
		'post_title'   => $fname,
		'post_type'    => learndash_get_post_type_slug( 'assignment' ),
		'post_status'  => 'publish',
		'post_content' => "<a href='" . $dest . "' target='_blank'>" . $fname . '</a>',
		'post_author'  => $current_user->ID,
	);

	$assignment_post_id = wp_insert_post( $assignment );
	$auto_approve       = learndash_get_setting( $post, 'auto_approve_assignment' );

	if ( $assignment_post_id ) {
		foreach ( $assignment_meta as $key => $value ) {
			update_post_meta( $assignment_post_id, $key, $value );
		}

		/**
		 * Run action hook after assignment is uploaded
		 *
		 * @since 2.2
		 *
		 * @param int       $assignment_post_id     Newly created assignment post ID which the
		 *                                          assignment is uploaded to
		 * @param array     $assignment_meta        Assignment meta data
		 */
		do_action( 'learndash_assignment_uploaded', $assignment_post_id, $assignment_meta );

		if ( empty( $auto_approve ) ) {

			update_user_meta(
				get_current_user_id(),
				'ld_assignment_message',
				array(
					array(
						'type'    => 'success',
						'message' => esc_html__( 'Assignment successfully uploaded.', 'learndash' ),
					),
				)
			);

			wp_safe_redirect( get_permalink( $post->ID ), 303 );
			exit();
		}
	}

	if ( ! empty( $auto_approve ) ) {
		learndash_approve_assignment( $current_user->ID, $post_id, $assignment_post_id );

		// assign full points if auto approve & points are enabled
		if ( 'on' === $points_enabled ) {
			$points = learndash_get_setting( $post, 'lesson_assignment_points_amount' );
			update_post_meta( $assignment_post_id, 'points', intval( $points ) );
		}

		learndash_get_next_lesson_redirect( $post );
	}
}

/**
 * Ensure comments are open for assignments
 *
 * @since 2.1.0
 *
 * @param bool          $open    Whether the current post is open for comments.
 * @param int|obj       $post_id The post ID or WP_Post object.
 * @return int|obj      $post_id The post ID or WP_Post object.
 */
function learndash_assignments_comments_open( $open, $post_id ) {
	if ( learndash_get_post_type_slug( 'assignment' ) === get_post_type( $post_id ) ) {
		$comment_status = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Assignments_CPT', 'comment_status' );
		if ( 'yes' === $comment_status ) {

			if ( empty( $open ) ) {
				if ( is_numeric( $post_id ) ) {
					global $wpdb;
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE $wpdb->posts SET comment_status = %s WHERE ID = %d",
							'open',
							$post_id
						)
					);
					$open = true;
				}
			}
		} else {
			$open = false;
		}
	}

	return $open;
}

add_filter( 'comments_open', 'learndash_assignments_comments_open', 10, 2 );

/**
 * Enable comments when adding new assignment
 *
 * @since 2.1.0
 *
 * @param  array $data post data
 * @return array $data post data
 */
function learndash_assignments_comments_on( $data ) {
	if ( learndash_get_post_type_slug( 'assignment' ) === $data['post_type'] ) {
		$data['comment_status'] = 'open';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'learndash_assignments_comments_on' );

/**
 * Ensure clean filename on upload
 *
 * @since 2.1.0
 *
 * @param  string $string file name
 * @return string         clean file name
 */
function learndash_clean_filename( $string ) {
	$string = htmlentities( $string, ENT_QUOTES, 'UTF-8' );
	$string = preg_replace( '~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $string );
	$string = html_entity_decode( $string, ENT_QUOTES, 'UTF-8' );
	$string = preg_replace( array( '~[^0-9a-z.]~i', '~[ -]+~' ), ' ', $string );
	$string = str_replace( ' ', '_', $string );
	return trim( $string, ' -' );
}

/**
 * Upload files
 *
 * @since 2.1.0
 *
 * @param  array    $uploadfiles
 * @param  int      $post_id     assignment id
 * @return array    file description
 */
function learndash_fileupload_process( $uploadfiles, $post_id ) {

	if ( is_array( $uploadfiles ) ) {

		foreach ( $uploadfiles['name'] as $key => $value ) {
			// look only for uploded files
			if ( 0 == $uploadfiles['error'][ $key ] ) {

				$filetmp = $uploadfiles['tmp_name'][ $key ];

				//clean filename
				$filename = learndash_clean_filename( $uploadfiles['name'][ $key ] );

				//extract extension
				if ( ! function_exists( 'wp_get_current_user' ) ) {
					include ABSPATH . 'wp-includes/pluggable.php';
				}

				// Before this function we have already validated the file extention/type via the function learndash_check_upload
				// @2.5.4
				$file_title = pathinfo( basename( $filename ), PATHINFO_FILENAME );
				$file_ext   = pathinfo( basename( $filename ), PATHINFO_EXTENSION );

				$upload_dir      = wp_upload_dir();
				$upload_dir_base = str_replace( '\\', '/', $upload_dir['basedir'] );
				$upload_url_base = $upload_dir['baseurl'];
				$upload_dir_path = $upload_dir_base . '/assignments';
				$upload_url_path = $upload_url_base . '/assignments/';

				if ( ! file_exists( $upload_dir_path ) ) {
					if ( is_writable( dirname( $upload_dir_path ) ) ) {
						wp_mkdir_p( $upload_dir_path );
					} else {
						die( esc_html__( 'Unable to write to UPLOADS directory. Is this directory writable by the server?', 'learndash' ) );
						return;
					}
				}

				// Add an index.php file to prevent directory browesing
				$_index = trailingslashit( $upload_dir_path ) . 'index.php';
				if ( ! file_exists( $_index ) ) {
					file_put_contents( $_index, '//LearnDash is THE Best LMS' );
				}

				/**
				 * Check if the filename already exist in the directory and rename the
				 * file if necessary
				 */
				$i = 0;

				while ( file_exists( $upload_dir_path . '/' . $filename ) ) {
					$i++;
					$filename = $file_title . '_' . $i . '.' . $file_ext;
				}

				$filedest    = $upload_dir_path . '/' . $filename;
				$destination = $upload_url_path . $filename;

				/**
				 * Check write permissions
				 */
				if ( ! is_writeable( $upload_dir_path ) ) {
					die( esc_html__( 'Unable to write to directory. Is this directory writable by the server?', 'learndash' ) );
					return;
				}

				/**
				 * Save temporary file to uploads dir
				 */
				if ( ! @move_uploaded_file( $filetmp, $filedest ) ) {
					echo( "Error, the file $filetmp could not moved to : $filedest " );
					continue;
				}

				/**
				 * Add upload meta to database
				 *
				 */
				learndash_upload_assignment_init( $post_id, $filename, $filedest );
				$file_desc             = array();
				$file_desc['filename'] = $filename;
				$file_desc['filelink'] = $destination;
				return $file_desc;
			}
		}
	}
}



/**
 * Does lesson have assignments
 *
 * @since 2.1.0
 *
 * @param  object $post WP_Post assignment
 * @return bool
 */
function lesson_hasassignments( $post ) {
	$post_id     = $post->ID;
	$assign_meta = get_post_meta( $post_id, '_' . $post->post_type, true );

	if ( ! empty( $assign_meta[ $post->post_type . '_lesson_assignment_upload' ] ) ) {
		$val = $assign_meta[ $post->post_type . '_lesson_assignment_upload' ];

		if ( 'on' === $val ) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Add 'Approve' next to certain selects on assignment edit screen in admin
 *
 * @todo  check if needed, jQuery selector seems incorrect
 *
 * @since 2.1.0
 */
function learndash_assignment_bulk_actions() {
	global $post;
	if ( ! empty( $post->post_type ) && learndash_get_post_type_slug( 'assignment' ) === $post->post_type ) {
		$approve_text = esc_html__( 'Approve', 'learndash' );

		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('<option>').val('approve_assignment').text('<?php echo $approve_text; ?>').appendTo("select[name='action']");
					jQuery('<option>').val('approve_assignment').text('<?php echo $approve_text; ?>').appendTo("select[name='action2']");
				});
			</script>
		<?php
	}
}

add_action( 'admin_footer', 'learndash_assignment_bulk_actions' );

/**
 * Handle approval of assignments in bulk
 *
 * @since 2.1.0
 */
function learndash_assignment_bulk_actions_approve() {

	if ( ( ( isset( $_REQUEST['post'] ) ) && ( ! empty( $_REQUEST['post'] ) ) && ( is_array( $_REQUEST['post'] ) ) )
	  && ( ( isset( $_REQUEST['post_type'] ) ) && ( $_REQUEST['post_type'] == learndash_get_post_type_slug( 'assignment' ) ) ) ) {

		$action = '';
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
			$action = esc_attr( $_REQUEST['action'] );

		} elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
			$action = esc_attr( $_REQUEST['action2'] );

		} elseif ( ( isset( $_REQUEST['ld_action'] ) ) && ( $_REQUEST['ld_action'] == 'approve_assignment' ) ) {
			  $action = 'approve_assignment';
		}

		//error_log('_REQUEST<pre>'. print_r($_REQUEST, true) .'</pre>');

		if ( $action == 'approve_assignment' ) {
			if ( ( isset( $_REQUEST['post'] ) ) && ( ! empty( $_REQUEST['post'] ) ) ) {
				if ( ! is_array( $_REQUEST['post'] ) ) {
					$assignments = array( $_REQUEST['post'] );
				} else {
					$assignments = $_REQUEST['post'];
				}

				//error_log('assignments<pre>'. print_r($assignments, true) .'</pre>');
				//return;

				foreach ( $assignments as $assignment_id ) {

					$assignment_post = get_post( $assignment_id );
					if ( ( ! empty( $assignment_post ) ) && ( $assignment_post instanceof WP_Post ) && ( $assignment_post->post_type == learndash_get_post_type_slug( 'assignment' ) ) ) {

						$user_id   = $assignment_post->post_author;
						$lesson_id = get_post_meta( $assignment_post->ID, 'lesson_id', true );

						if ( learndash_assignment_is_points_enabled( $assignment_id ) === true ) {

							if ( ( isset( $_REQUEST['assignment_points'] ) ) && ( isset( $_REQUEST['assignment_points'][ $assignment_id ] ) ) ) {
								$assignment_points = abs( intval( $_REQUEST['assignment_points'][ $assignment_id ] ) );

								$assignment_settings_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
								if ( ! empty( $assignment_settings_id ) ) {
									$max_points = learndash_get_setting( $assignment_settings_id, 'lesson_assignment_points_amount' );
								}

								// Double check the assiged points is NOT larger than max points.
								if ( $assignment_points > $max_points ) {
									$assignment_points = $max_points;
								}

								//error_log('assignment_id['. $assignment_id .'] points['. $assignment_points .']');

								update_post_meta( $assignment_id, 'points', $assignment_points );
							}
						}

						learndash_approve_assignment( $user_id, $lesson_id, $assignment_id );
					}
				}
			}

			if ( ! empty( $_REQUEST['ret_url'] ) ) {
				header( 'Location: ' . rawurldecode( $_REQUEST['ret_url'] ) );
				exit;
			}
		}
	}
}

add_action( 'load-edit.php', 'learndash_assignment_bulk_actions_approve' );



/**
 * Approve assignment by id for user
 *
 * @since 2.1.0
 *
 * @param  int $assignment_id
 * @return bool
 */
function learndash_approve_assignment_by_id( $assignment_id ) {
	$assignment_post = get_post( $assignment );
	$user_id         = $assignment_post->post_author;
	$lesson_id       = get_post_meta( $assignment_post->ID, 'lesson_id', true );
	return learndash_approve_assignment( $user_id, $lesson_id, $assignment_id );
}



/**
 * Mark assignment true with user id and lesson id
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $lesson_id
 * @return bool
 */
function learndash_approve_assignment( $user_id, $lesson_id, $assignment_post_id = 0 ) {

	/**
	 * Filter whether assignmnent should be approved or not
	 *
	 * @since 2.1.0
	 *
	 * $assignment_post_id add v2.5.5
	 *
	 * @param  bool
	 */
	$learndash_approve_assignment = apply_filters( 'learndash_approve_assignment', true, $user_id, $lesson_id, $assignment_post_id );

	if ( $learndash_approve_assignment ) {
		$assignment_course_id            = get_post_meta( $assignment_post_id, 'course_id', true );
		$learndash_process_mark_complete = learndash_process_mark_complete( $user_id, $lesson_id, null, $assignment_course_id );
		if ( $learndash_process_mark_complete ) {
			/**
			 * @TODO This query needs to be reworked to NOT query all posts with that meta_key. Better off using WP_Query.
			 */
			global $wpdb;
			$assignment_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", 'lesson_id', $lesson_id ) );

			foreach ( $assignment_ids as $assignment_id ) {
				if ( ( intval( $assignment_post_id ) != 0 ) && ( intval( $assignment_post_id ) != intval( $assignment_id ) ) ) {
					continue;
				}

				$assignment = get_post( $assignment_id );
				if ( $assignment->post_author == $user_id ) {
					learndash_assignment_mark_approved( $assignment_id );

					/**
					 * Run action hook after assignment is approved
					 *
					 * @since 2.2
					 *
					 * @param int $assignment_id    Newly created assignment post ID which
					 *                              the assignment is uploaded to
					 */
					do_action( 'learndash_assignment_approved', $assignment_id );
				}
			}
		}

		return $learndash_process_mark_complete;
	}
}



/**
 * Update assignments post meta with approval status
 *
 * @since 2.1.0
 *
 * @param  int $assignment_id
 */
function learndash_assignment_mark_approved( $assignment_id ) {
	update_post_meta( $assignment_id, 'approval_status', 1 );
}



/**
 * Get assignments approval status
 *
 * @since 2.1.0
 *
 * @param  int $assignment_id
 * @return bool
 */
function learndash_is_assignment_approved_by_meta( $assignment_id ) {
	return get_post_meta( $assignment_id, 'approval_status', true );
}



/**
 * Adds inline actions to assignments on post listing hover in admin
 *
 * @since 2.1.0
 *
 * @param  array    $actions    post actions
 * @param  object   $post       WP_Post assignment
 * @return array    $actions    post actions
 */
function learndash_assignment_inline_actions( $actions, $post ) {
	if ( learndash_get_post_type_slug( 'assignment' ) === $post->post_type ) {
		$download_link                      = get_post_meta( $post->ID, 'file_link', true );
		$actions['download_assignment']     = "<a href='" . $download_link . "' target='_blank'>" . esc_html__( 'Download', 'learndash' ) . '</a>';
		$learndash_assignment_approval_link = learndash_assignment_approval_link( $post->ID );

		$points_enabled = learndash_assignment_is_points_enabled( $post->ID );

		if ( $learndash_assignment_approval_link && ! $points_enabled ) {
			$actions['approve_assignment'] = "<a href='" . $learndash_assignment_approval_link . "' >" . esc_html__( 'Approve', 'learndash' ) . '</a>';
		}
	}

	return $actions;
}

add_filter( 'post_row_actions', 'learndash_assignment_inline_actions', 10, 2 );

/**
 * Restrict assignment listings view to group leader only
 *
 * @since 2.1.0
 *
 * @param  object   $query  WP_Query
 * @return object   $query  WP_Query
 */
function learndash_restrict_assignment_listings( $query ) {
	global $pagenow, $typenow;

	$q_vars = & $query->query_vars;

	if ( ! is_admin() ) {
		return;
	}
	if ( 'edit.php' !== $pagenow ) {
		return;
	}
	if ( ! $query->is_main_query() ) {
		return;
	}
	if ( empty( $typenow ) ) {
		return;
	}

	if ( learndash_get_post_type_slug( 'assignment' ) === $typenow ) {

		$user_id = get_current_user_id();

		if ( learndash_is_group_leader_user( $user_id ) ) {

			$group_ids  = learndash_get_administrators_group_ids( $user_id );
			$course_ids = array();
			$lesson_ids = array();
			$user_ids   = array();

			if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {
				foreach ( $group_ids as $group_id ) {
					$group_course_ids = learndash_group_enrolled_courses( $group_id );
					if ( ! empty( $group_course_ids ) && is_array( $group_course_ids ) ) {
						$course_ids = array_merge( $course_ids, $group_course_ids );
					}

					$group_users = learndash_get_groups_user_ids( $group_id );
					if ( ! empty( $group_users ) && is_array( $group_users ) ) {
						foreach ( $group_users as $group_user_id ) {
							$user_ids[ $group_user_id ] = $group_user_id;
						}
					}
				}
			}

			if ( ! empty( $course_ids ) ) {
				$course_ids = array_map( 'absint', $course_ids );
			
				if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
					$course_id = absint( $_GET['course_id'] );
					if ( in_array( $course_id, $course_ids ) ) {
						$course_ids = array( $course_id );

						if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id'] ) ) ) {
							$lesson_id = absint( $_GET['lesson_id'] );
							$lesson_courses = learndash_get_courses_for_step( $lesson_id, true );
							if ( ! is_array( $lesson_courses ) ) {
								$lesson_courses = array();
							}
							if ( ( ! empty( $lesson_courses ) ) && ( isset( $lesson_courses[ $course_id ] ) ) ) {
								$lesson_ids = array( $lesson_id );
							} else {
								$course_ids = array();
								$lesson_ids = array();
							}
						}

					} else {
						$course_ids = array();
					}
				}
			}

			if ( ( empty( $course_ids ) ) && ( empty( $user_ids ) ) ) {
				$course_ids = array(0);
				$user_ids = array(0);
			}

			if ( ! isset( $q_vars['meta_query'] ) ) {
				$q_vars['meta_query'] = array();
			}

			$q_vars['meta_query'][] = array(
				'key'     => 'course_id',
				'value'   => $course_ids,
				'compare' => 'IN',
			);

			if ( ( ! empty( $course_ids ) ) && ( ! empty( $lesson_ids ) ) ) {
				$lesson_ids = array_map( 'absint', $lesson_ids );
				$q_vars['meta_query'][] = array(
					'key'     => 'lesson_id',
					'value'   => $lesson_ids,
					'compare' => 'IN',
				);
				$q_vars['meta_query']['relation'] = 'AND';
			}
			
			if ( ! empty( $user_ids ) ) {
				$user_ids = array_map( 'absint', $user_ids );

				if ( ( isset( $_GET['author'] ) ) && ( ! empty( $_GET['author'] ) ) ) {
					if ( in_array( absint( $_GET['author'] ), $user_ids ) ) {
						$user_ids = array( absint( $_GET['author'] ) );
					} else {
						$user_ids = array();
					}
				} 
			}

			if ( ! empty( $user_ids ) ) {
				$q_vars['author__in'] = $user_ids;
			} else {
				$q_vars['author__in'] = array(0);
			}
		}
	}
}
add_filter( 'parse_query', 'learndash_restrict_assignment_listings' );

/**
 * Check if assignment is completed
 *
 * @since 2.1.0
 *
 * @param  int      $assignment_id
 * @return bool
 */
function learndash_is_assignment_approved( $assignment_id ) {
	$assignment = get_post( $assignment_id );

	if ( empty( $assignment->ID ) ) {
		return '';
	}

	$lesson_id = learndash_get_lesson_id( $assignment->ID );

	if ( empty( $lesson_id ) ) {
		return '';
	}

	$lesson_completed = learndash_is_lesson_notcomplete( $assignment->post_author, array( $lesson_id => 1 ) );

	if ( empty( $lesson_completed ) ) {
		return true;
	} else {
		return false;
	}
}



/**
 * Get assignment approval url
 *
 * @since 2.1.0
 *
 * @param  int $assignment_id
 * @return string assignment approval url
 */
function learndash_assignment_approval_link( $assignment_id ) {
	if ( ! learndash_is_assignment_approved_by_meta( $assignment_id ) ) {
		$approve_url = admin_url( 'edit.php?post_type=' . learndash_get_post_type_slug( 'assignment' ) . '&ld_action=approve_assignment&post[]=' . $assignment_id . '&ret_url=' . rawurlencode( @$_SERVER['REQUEST_URI'] ) );
		return $approve_url;
	} else {
		return '';
	}
}



/**
 * Register assignment metabox
 *
 * @since 2.1.0
 */
function learndash_assignment_metabox() {
	add_meta_box( 'learndash_assignment_metabox', esc_html__( 'Assignment', 'learndash' ), 'learndash_assignment_metabox_content', learndash_get_post_type_slug( 'assignment' ), 'advanced', 'high' );
}

add_action( 'add_meta_boxes', 'learndash_assignment_metabox' );



/**
 * Add Approval Link to assignment metabox
 *
 * @since 2.1.0
 */
function learndash_assignment_metabox_content() {
	global $post, $sfwd_lms;

	$assignment_course_id = intval( get_post_meta( $post->ID, 'course_id', true ) );
	$assignment_lesson_id = intval( get_post_meta( $post->ID, 'lesson_id', true ) );

	wp_nonce_field( 'ld-assignment-nonce-' . $post->ID, 'ld-assignment-nonce' );

	?>
	<div class="sfwd sfwd_options sfwd-assignment_settings">
		<div class="sfwd_input " id="sfwd-assignment_course">
			<span class="sfwd_option_label" style="text-align:right;vertical-align:top;"><a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e( 'Click for Help!', 'learndash' ); ?>" onclick="toggleVisibility('sfwd-assignment_course_tip');"><img src="<?php echo LEARNDASH_LMS_PLUGIN_URL; ?>/assets/images/question.png" /><label class="sfwd_label textinput"><?php echo sprintf( esc_html_x( 'Associated %s', 'Associated Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?></label></a></span>
			<span class="sfwd_option_input"><div class="sfwd_option_div">
			<?php
			if ( empty( $assignment_course_id ) ) {
				?>
				<select name="sfwd-assignment_course">
					<option value=""><?php echo sprintf( esc_html_x( '-- Select a %s --', 'Select a Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?></option>
					<?php
						$cb_courses = array();
					if ( ! empty( $assignment_lesson_id ) ) {
						$cb_courses = learndash_get_courses_for_step( $assignment_lesson_id, true );
						if ( ! empty( $cb_courses ) ) {
							$cb_courses = array_keys( $cb_courses );
						}
					}

						$query_courses_args = array(
							'post_type'      => 'sfwd-courses',
							'post_status'    => 'any',
							'posts_per_page' => -1,
							'post__in'       => $cb_courses,
							'orderby'        => 'title',
							'order'          => 'ASC',
						);

						$query_courses = new WP_Query( $query_courses_args );

					if ( ! empty( $query_courses->posts ) ) {
						foreach ( $query_courses->posts as $p ) {
							?>
								<option value="<?php echo $p->ID; ?>"><?php echo $p->post_title; ?></option>
								<?php
						}
					}
						?>
					</select>
					<?php
			} else {
				echo '<p>' . get_the_title( $assignment_course_id ) . ' (<a href="' . get_permalink( $assignment_course_id ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>)' . '</p>';

			}
			?>
		</div><div class="sfwd_help_text_div" style="display:none" id="sfwd-assignment_course_tip"><label class="sfwd_help_text"><?php echo sprintf( esc_html_x( 'Associate with a %s.', 'Associate with a course.', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?></label></div></span><p style="clear:left"></p></div>
	</div>

	<div class="sfwd sfwd_options sfwd-assignment_settings">
		<div class="sfwd_input " id="sfwd-assignment_lesson">
			<span class="sfwd_option_label" style="text-align:right;vertical-align:top;"><a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e( 'Click for Help!', 'learndash' ); ?>" onclick="toggleVisibility('sfwd-assignment_lesson_tip');"><img src="<?php echo LEARNDASH_LMS_PLUGIN_URL; ?>/assets/images/question.png" /><label class="sfwd_label textinput"><?php echo sprintf( esc_html_x( 'Associated %s', 'Associated Lesson Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ); ?></label></a></span>
			<span class="sfwd_option_input"><div class="sfwd_option_div">
			<?php
			if ( empty( $assignment_lesson_id ) ) {
				?>
				<select name="sfwd-assignment_lesson">
					<option value=""><?php echo sprintf( esc_html_x( '-- Select a %s --', 'Select a Lesson Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ); ?></option>
					<?php
					if ( ! empty( $assignment_course_id ) ) {
						$course_lessons = $sfwd_lms->select_a_lesson_or_topic( $assignment_course_id, true );
						if ( ! empty( $course_lessons ) ) {
							foreach ( $course_lessons as $l_id => $l_label ) {
								?>
									<option value="<?php echo $l_id; ?>"><?php echo $l_label; ?></option>
									<?php
							}
						}
					}
					?>
					</select>
					<?php
			} else {
				echo '<p>' . get_the_title( $assignment_lesson_id ) . ' (<a href="' . get_permalink( $assignment_lesson_id ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>)' . '</p>';
			}
			?>
		</div><div class="sfwd_help_text_div" style="display:none" id="sfwd-assignment_lesson_tip"><label class="sfwd_help_text"><?php echo sprintf( esc_html_x( 'Associate with a %s.', 'Associate with a lesson.', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ); ?></label></div></span><p style="clear:left"></p></div>
	</div>
		
	<div class="sfwd sfwd_options sfwd-assignment_settings">
		<div class="sfwd_input " id="sfwd-assignment_status">
			<span class="sfwd_option_label" style="text-align:right;vertical-align:top;"><a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e( 'Click for Help!', 'learndash' ); ?>" onclick="toggleVisibility('sfwd-assignment_status_tip');"><img src="<?php echo LEARNDASH_LMS_PLUGIN_URL; ?>/assets/images/question.png" /><label class="sfwd_label textinput"><?php _e( 'Status', 'learndash' ); ?></label></a></span>
			<span class="sfwd_option_input"><div class="sfwd_option_div">
			<?php
				$approval_status_flag = learndash_is_assignment_approved_by_meta( $post->ID );
			if ( $approval_status_flag == 1 ) {
				$approval_status_label = esc_html__( 'Approved', 'learndash' );
				echo '<p>' . $approval_status_label . '</p>';
			} else {
				if ( ( learndash_get_setting( $assignment_lesson_id, 'lesson_assignment_points_enabled' ) === 'on' ) && ( intval( learndash_get_setting( $assignment_lesson_id, 'lesson_assignment_points_amount' ) ) > 0 ) ) {
					$approval_status_label = esc_html__( 'Not Approved', 'learndash' );
					echo '<p>' . $approval_status_label . '</p>';
				} else {
					$approve_text = esc_html__( 'Approve', 'learndash' );
					echo '<p><input name="assignment-status" type="submit" class="button button-primary button-large" id="publish" value="' . $approve_text . '"></p>';
				}
			}
			?>
		</div><div class="sfwd_help_text_div" style="display:none" id="sfwd-assignment_lesson_tip"><label class="sfwd_help_text"><?php echo sprintf( esc_html_x( 'Associate with a %s.', 'Associate with a lesson.', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ); ?></label></div></span><p style="clear:left"></p></div>
	</div>

	<div class="sfwd sfwd_options sfwd-assignment_settings">
		<div class="sfwd_input " id="sfwd-assignment_points">
			<span class="sfwd_option_label" style="text-align:right;vertical-align:top;"><a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e( 'Click for Help!', 'learndash' ); ?>" onclick="toggleVisibility('sfwd-assignment_points_tip');"><img src="<?php echo LEARNDASH_LMS_PLUGIN_URL; ?>/assets/images/question.png" /><label class="sfwd_label textinput"><?php _e( 'Points', 'learndash' ); ?></label></a></span>
			<span class="sfwd_option_input"><div class="sfwd_option_div">
			<?php
			if ( ( ! empty( $assignment_course_id ) ) && ( ! empty( $assignment_lesson_id ) ) ) {
				//$points_enabled = learndash_get_setting( $assignment_lesson_id, 'lesson_assignment_points_enabled' );

				//if ( $points_enabled == 'on' ) {
				if ( ( learndash_get_setting( $assignment_lesson_id, 'lesson_assignment_points_enabled' ) === 'on' ) && ( intval( learndash_get_setting( $assignment_lesson_id, 'lesson_assignment_points_amount' ) ) > 0 ) ) {
					$max_points     = intval( learndash_get_setting( $assignment_lesson_id, 'lesson_assignment_points_amount' ) );
					$current_points = intval( get_post_meta( $post->ID, 'points', true ) );
					$update_text    = learndash_is_assignment_approved_by_meta( $post->ID ) ? esc_html__( 'Update', 'learndash' ) : esc_html__( 'Update & Approve', 'learndash' );

					echo '<p>';
					echo "<label for='assignment-points'>" . sprintf( esc_html__( 'Awarded Points (Out of %d):', 'learndash' ), $max_points ) . '</label><br />';
					echo "<input name='assignment-points' type='number' min=0 max='{$max_points}' value='{$current_points}'>";
					echo "<p><input name='save' type='submit' class='button button-primary button-large' id='publish' value='{$update_text}'></p>";
					echo '</p>';
				} else {
					echo '<p>' . esc_html__( 'Points not enabled', 'learndash' ) . '</p>';
				}
			}
			?>
		</div><div class="sfwd_help_text_div" style="display:none" id="sfwd-assignment_points_tip"><label class="sfwd_help_text"><?php _e( 'Assignment Points.', 'learndash' ); ?></label></div></span><p style="clear:left"></p></div>
	</div>

	<?php
		$file_link = get_post_meta( $post->ID, 'file_link', true );
	if ( ! empty( $file_link ) ) {
		?>
		<div class="sfwd sfwd_options sfwd-assignment_settings">
			<div class="sfwd_input " id="sfwd-assignment_download">
				<span class="sfwd_option_label" style="text-align:right;vertical-align:top;"><a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e( 'Click for Help!', 'learndash' ); ?>" onclick="toggleVisibility('sfwd-assignment_download_tip');"><img src="<?php echo LEARNDASH_LMS_PLUGIN_URL; ?>/assets/images/question.png" /><label class="sfwd_label textinput"><?php _e( 'Actions', 'learndash' ); ?></label></a></span>
				<span class="sfwd_option_input"><div class="sfwd_option_div">
				<?php

					// link handling
					$file_link = get_post_meta( $post->ID, 'file_link', true );

					echo "<a href='" . $file_link . "' target='_blank' class='button'>" . esc_html__( 'Download', 'learndash' ) . '</a>';
				?>
				</div><div class="sfwd_help_text_div" style="display:none" id="sfwd-assignment_download_tip"><label class="sfwd_help_text"><?php esc_html_e( 'Assignment download.', 'learndash' ); ?></label></div></span><p style="clear:left"></p></div>
			</div>
			<?php
	}
}

/**
 * Update assignment points and approval status
 *
 * @since 2.1.0
 *
 * @param $assignment_id
 */
function learndash_assignment_save_metabox_content( $assignment_id ) {
	if ( ! isset( $_POST['ld-assignment-nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['ld-assignment-nonce'], 'ld-assignment-nonce-' . $assignment_id ) ) {
		return;
	}

	$assignment_course_id = intval( get_post_meta( $assignment_id, 'course_id', true ) );
	if ( ( empty( $assignment_course_id ) ) && ( isset( $_POST['sfwd-assignment_course'] ) ) && ( ! empty( $_POST['sfwd-assignment_course'] ) ) ) {
		update_post_meta( $assignment_id, 'course_id', intval( $_POST['sfwd-assignment_course'] ) );
	}

	$assignment_lesson_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
	if ( ( empty( $assignment_lesson_id ) ) && ( isset( $_POST['sfwd-assignment_lesson'] ) ) && ( ! empty( $_POST['sfwd-assignment_lesson'] ) ) ) {
		update_post_meta( $assignment_id, 'lesson_id', intval( $_POST['sfwd-assignment_lesson'] ) );
	}

	if ( isset( $_POST['assignment-points'] ) ) {

		// update points
		$points = intval( $_POST['assignment-points'] );
		update_post_meta( $assignment_id, 'points', $points );

		// approve assignment
		$assignment_post = get_post( $assignment_id );
		$lesson_id       = get_post_meta( $assignment_id, 'lesson_id', true );
		learndash_approve_assignment( $assignment_post->post_author, $lesson_id, $assignment_post->ID );
	} elseif ( ( isset( $_POST['assignment-status'] ) ) && ( $_POST['assignment-status'] == esc_html__( 'Approve', 'learndash' ) ) ) {

		// approve assignment
		$assignment_post = get_post( $assignment_id );
		$lesson_id       = get_post_meta( $assignment_id, 'lesson_id', true );
		learndash_approve_assignment( $assignment_post->post_author, $lesson_id, $assignment_post->ID );
	}
}

add_action( 'save_post', 'learndash_assignment_save_metabox_content' );



/**
 * Only allow admins, group leaders, and assignment owners to see assignment
 *
 * @since 2.1.0
 */
function learndash_assignment_permissions() {
	global $post;

	if ( ! empty( $post->post_type ) && $post->post_type === learndash_get_post_type_slug( 'assignment' ) && is_singular() ) {
		$user_id = get_current_user_id();

		if ( learndash_is_admin_user( $user_id ) ) {
			return;
		}

		if ( absint( $user_id ) === absint( $post->post_author ) ) {
			return;
		} elseif ( learndash_is_group_leader_of_user( $user_id, $post->post_author ) ) {
			return;
		} else {
			wp_safe_redirect( apply_filters( 'learndash_assignment_permissions_redirect_url', get_bloginfo( 'url' ) ) );
			exit;
		}
	}
}

add_action( 'wp', 'learndash_assignment_permissions' ); //, 0, 3 );



/**
 * Register Assignments custom post type
 *
 * @since 2.1.0
 */
function learndash_register_assignment_upload_type() {

	$exclude_from_search = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Assignments_CPT', 'exclude_from_search' );
	if ( 'yes' === $exclude_from_search ) {
		$exclude_from_search = true;
	} else {
		$exclude_from_search = false;
	}
	$publicly_queryable = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Assignments_CPT', 'publicly_queryable' );
	if ( 'yes' === $publicly_queryable ) {
		$publicly_queryable = true;
	} else {
		$publicly_queryable = false;
	}
	$comment_status = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Assignments_CPT', 'comment_status' );
	if ( 'yes' === $comment_status ) {
		$comment_status = true;
	} else {
		$comment_status = false;
	}

	$labels = array(
		'name'               => esc_html__( 'Assignments', 'learndash' ),
		'singular_name'      => esc_html__( 'Assignment', 'learndash' ),
		'edit_item'          => esc_html__( 'Edit Assignment', 'learndash' ),
		'view_item'          => esc_html__( 'View Assignment', 'learndash' ),
		'search_items'       => esc_html__( 'Search Assignments', 'learndash' ),
		'not_found'          => esc_html__( 'No assignment found', 'learndash' ),
		'not_found_in_trash' => esc_html__( 'No assignment found in Trash', 'learndash' ),
		'parent_item_colon'  => esc_html__( 'Parent:', 'learndash' ),
		'menu_name'          => esc_html__( 'Assignments', 'learndash' ),
	);

	if ( learndash_is_admin_user() ) {
		$show_in_admin_bar = false;
	} elseif ( learndash_is_group_leader_user() ) {
		$show_in_admin_bar = false;
	} else {
		$show_in_admin_bar = false;
	}

	$supports = array( 'title', 'comments', 'author' );
	if ( true !== $comment_status ) {
		$supports = array_diff( $supports, array( 'comments' ) );
	}

	$rewrite = array( 'slug' => 'assignment' );
	if ( true !== $publicly_queryable ) {
		$rewrite = false;
	}

	$args = array(
		'labels'              => $labels,
		'hierarchical'        => false,
		'supports'            => $supports,
		'public'              => $publicly_queryable,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => $show_in_admin_bar,
		'publicly_queryable'  => $publicly_queryable,
		'exclude_from_search' => $exclude_from_search,
		'has_archive'         => false,
		'show_in_rest'        => false,
		'query_var'           => $publicly_queryable,
		'rewrite'             => $rewrite,
		'capability_type'     => 'assignment',
		'capabilities'        => array(
			'read_post'              => 'read_assignment',
			'publish_posts'          => 'publish_assignments',
			'edit_posts'             => 'edit_assignments',
			'edit_others_posts'      => 'edit_others_assignments',
			'delete_posts'           => 'delete_assignments',
			'delete_others_posts'    => 'delete_others_assignments',
			'read_private_posts'     => 'read_private_assignments',
			'edit_post'              => 'edit_assignment',
			'delete_post'            => 'delete_assignment',
			'edit_published_posts'   => 'edit_published_assignments',
			'delete_published_posts' => 'delete_published_assignments',
		),
		'map_meta_cap'        => true,
	);

	$args = apply_filters( 'learndash-cpt-options', $args, 'sfwd-assignment' );

	register_post_type( 'sfwd-assignment', $args );
}

add_action( 'init', 'learndash_register_assignment_upload_type' );



/**
 * Setup capabilities for Assignments custom post type
 *
 * @since 2.1.0
 */
function learndash_add_assignment_caps() {
	$admin_role = get_role( 'administrator' );
	if ( ( $admin_role ) && ( $admin_role instanceof WP_Role ) ) {
		$cap = $admin_role->has_cap( 'delete_others_assignments' );

		if ( empty( $cap ) ) {
			$admin_role->add_cap( 'edit_assignment' );
			$admin_role->add_cap( 'edit_assignments' );
			$admin_role->add_cap( 'edit_others_assignments' );
			$admin_role->add_cap( 'publish_assignments' );
			$admin_role->add_cap( 'read_assignment' );
			$admin_role->add_cap( 'read_private_assignments' );
			$admin_role->add_cap( 'delete_assignment' );
			$admin_role->add_cap( 'edit_published_assignments' );
			$admin_role->add_cap( 'delete_others_assignments' );
			$admin_role->add_cap( 'delete_published_assignments' );
		}
	}

	$group_leader_role = get_role( 'group_leader' );
	if ( ( $group_leader_role ) && ( $group_leader_role instanceof WP_Role ) ) {
		$group_leader_role->add_cap( 'read_assignment' );
		$group_leader_role->add_cap( 'edit_assignments' );
		$group_leader_role->add_cap( 'edit_others_assignments' );
		$group_leader_role->add_cap( 'edit_published_assignments' );
		$group_leader_role->add_cap( 'delete_others_assignments' );
		$group_leader_role->add_cap( 'delete_published_assignments' );
	}
}

add_action( 'admin_init', 'learndash_add_assignment_caps' );



/**
 * Delete assignment file when assignment post is deleted
 *
 * @since 2.1.0
 *
 * @param  int $post_id
 */
function learndash_before_delete_assignment( $post_id ) {

	if ( ( ! empty( $post_id ) ) && ( learndash_get_post_type_slug( 'assignment' ) === get_post_type( $post_id ) ) ) {
		$file_path = get_post_meta( $post_id, 'file_path', true );
		if ( ! empty( $file_path ) ) {
			$file_path = rawurldecode( $file_path );

			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}
		}
	}
}

add_action( 'before_delete_post', 'learndash_before_delete_assignment' );

/**
 * Echo the number of points awarded on the front end
 *
 * Displayed on single lessons under the submitted assignment
 *
 * @param int $assignment_id ID of the assignment.
 *
 * @return string
 */
function learndash_assignment_points_awarded( $assignment_id ) {
	$points_enabled = learndash_assignment_is_points_enabled( $assignment_id );

	if ( $points_enabled ) {
		$current = learndash_get_assignment_points_awarded( $assignment_id );

		/**
		 * Filter the output of the awarded points of an assignment.
		 *
		 * @param string $current Points awarded values or translatable string.
		 */
		return apply_filters(
			'learndash_points_awarded_output',
			sprintf(
				// translators: placeholdeR: points awarded values (30/100) 30%.
				esc_html_x( 'Points Awarded: %s', 'placeholder: points awarded values (30/100) 30%', 'learndash' ),
				$current
			),
			$current
		);
	}
}

/**
 * Get the value of the awarded assignment points.
 *
 * If the assignment hasn't been approved or graded, the translatable string 'Pending' is returned.
 * Otherwise, the awarded points and percentage achieved are returned.
 *
 * @since 2.6.4
 *
 * @param int $assignment_id ID of the assignment.
 *
 * @return string
 */
function learndash_get_assignment_points_awarded( $assignment_id ) {
	$current = get_post_meta( $assignment_id, 'points', true );

	// We can't compare against the actual post meta value because it was a translatable string until 2.6.4
	if ( ( ! empty( $current ) ) && ( ! is_numeric( $current ) ) ) {
		return esc_html_x( 'Pending', 'Assignment upload default value for points', 'learndash' );
	}

	if ( is_numeric( $current ) ) {
		$assignment_settings_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
		$max_points             = learndash_get_setting( $assignment_settings_id, 'lesson_assignment_points_amount' );
		$max_points             = intval( $max_points );
		if ( ! empty( $max_points ) ) {
			$percentage = ( intval( $current ) / intval( $max_points ) ) * 100;
			$percentage = round( $percentage, 2 );
		} else {
			$percentage = 0.00;
		}

		/**
		 * Filter the output format of the awarded points of an assignment.
		 *
		 * @param string $current    Achieved points.
		 * @param int    $max_points Maximum points.
		 * @param int    $percentage Percentage of achieved points/maximum points.
		 */
		return apply_filters(
			'learndash_points_awarded_output_format',
			sprintf(
				'(%1$d/%2$d) %3$d&#37; ',
				$current,
				$max_points,
				$percentage
			),
			$current,
			$max_points,
			$percentage
		);
	}
}


function learndash_assignment_is_points_enabled( $assignment ) {
	if ( is_a( $assignment, 'WP_Post' ) ) {
		$assignment_id = $assignment->ID;
	} else {
		$assignment_id = intval( $assignment );
	}

	$assignment_settings_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
	$points_enabled         = learndash_get_setting( $assignment_settings_id, 'lesson_assignment_points_enabled' );

	if ( 'on' === $points_enabled ) {
		return true;
	}

	return false;
}

function learndash_return_bytes_from_shorthand( $val = 0 ) {

	$units = array(
		'KB' => 1,
		'MB' => 2,
		'GB' => 3,
		'K'  => 1,
		'M'  => 2,
		'G'  => 3,
		'B'  => 0,
	);

	if ( ! empty( $val ) ) {
		$val = trim( $val );

		foreach ( $units as $unit_notation => $unit_multiplier ) {
			$val_unit = substr( $val, -( strlen( $unit_notation ) ) );
			if ( strtoupper( $val_unit ) == $unit_notation ) {
				$val_number = substr( $val, 0, strlen( $val ) - strlen( $unit_notation ) );

				$val_bytes = $val_number * pow( 1024, $unit_multiplier );

				return $val_bytes;
			}
		}
	}

	return $val;
}

function learndash_check_upload( $uploadfiles = array(), $post_id = 0 ) {

	if ( ( is_array( $uploadfiles ) ) && ( ! empty( $post_id ) ) ) {
		$limit_file_exts = array();
		$limit_file_size = 0;

		$post_settings = learndash_get_setting( $post_id );
		if ( ( isset( $post_settings['assignment_upload_limit_size'] ) ) && ( ! empty( $post_settings['assignment_upload_limit_size'] ) ) ) {
			$limit_file_size = $post_settings['assignment_upload_limit_size'];
			$limit_file_size = learndash_return_bytes_from_shorthand( $limit_file_size );

		} else {
			$limit_file_size = wp_max_upload_size();
		}

		if ( ( empty( $limit_file_size ) ) || ( intval( $uploadfiles['size'][0] ) > $limit_file_size ) ) {
			update_user_meta(
				get_current_user_id(),
				'ld_assignment_message',
				array(
					array(
						'type'    => 'error',
						'message' => esc_html__( 'Uploaded file size exceeds allowed limit.', 'learndash' ),
					)
				)
			);
			return false;
		}

		if ( ( isset( $post_settings['assignment_upload_limit_extensions'] ) ) && ( ! empty( $post_settings['assignment_upload_limit_extensions'] ) ) ) {
			$assignment_upload_limit_extensions = learndash_validate_extensions( $post_settings['assignment_upload_limit_extensions'] );

			$allowed_extensions    = array();
			$wp_allowed_extensions = get_allowed_mime_types();
			foreach ( $wp_allowed_extensions as $extension => $mime ) {

				$extension_split = explode( '|', $extension );
				foreach ( $extension_split as $e_split ) {
					$allowed_extensions[ $e_split ] = $mime;
				}
			}

			foreach ( $assignment_upload_limit_extensions as $assignment_upload_limit_extension ) {
				if ( isset( $allowed_extensions[ $assignment_upload_limit_extension ] ) ) {
					$limit_file_exts[ $assignment_upload_limit_extension ] = $allowed_extensions[ $assignment_upload_limit_extension ];
				}
			}
		} else {
			$limit_file_exts = get_allowed_mime_types();
		}

		if ( ! empty( $limit_file_exts ) ) {
			$filetype_mime = wp_check_filetype( $uploadfiles['name'][0], $limit_file_exts );
			if ( ( empty( $filetype_mime ) ) || ( empty( $filetype_mime['ext'] ) ) || ( empty( $filetype_mime['type'] ) ) ) {
				//$filename_ext = pathinfo( $uploadfiles['name'][0], PATHINFO_EXTENSION );
				//if ( !empty( $filename_ext ) ) $filename_ext = strtolower( $filename_ext );

				update_user_meta(
					get_current_user_id(),
					'ld_assignment_message',
					array(
						array(
							'type'    => 'error',
							'message' => esc_html__( 'The uploaded file type is not allowed.', 'learndash' ),
						),
					)
				);
				return false;
			}
		}

		if ( isset( $post_settings['assignment_upload_limit_count'] ) ) {
			$assignment_upload_limit_count = intval( $post_settings['assignment_upload_limit_count'] );
			if ( $assignment_upload_limit_count > 0 ) {
				$assignments = learndash_get_user_assignments( $post_id, get_current_user_id() );
				if ( ( ! empty( $assignments ) ) && ( count( $assignments ) >= $assignment_upload_limit_count ) ) {
					update_user_meta(
						get_current_user_id(),
						'ld_assignment_message',
						array(
							array(
								'type'    => 'error',
								'message' => esc_html__( 'Number of allowed assignment uploads reached.', 'learndash' ),
							),
						)
					);
					return false;
				}
			}
		}
	}

	return true;
}
