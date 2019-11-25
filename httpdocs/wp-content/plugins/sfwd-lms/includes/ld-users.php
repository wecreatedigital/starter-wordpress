<?php
/**
 * User functions
 *
 * @since 2.1.0
 *
 * @package LearnDash\Users
 */

/**
 * Delete user data
 *
 * @since 2.1.0
 *
 * @param int $user_id user id.
 */
function learndash_delete_user_data( $user_id ) {
	global $wpdb;

	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {
		$user = get_user_by( 'id', $user_id );

		$ref_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT statistic_ref_id FROM ' . LDLMS_DB::get_table_name( 'quiz_statistic_ref' ) . ' WHERE  user_id = %d ', $user->ID ) );

		if ( ! empty( $ref_ids[0] ) ) {
			$wpdb->delete( LDLMS_DB::get_table_name( 'quiz_statistic_ref' ), array( 'user_id' => $user->ID ) );
			$wpdb->query( 'DELETE FROM ' . LDLMS_DB::get_table_name( 'quiz_statistic' ) . ' WHERE statistic_ref_id IN (' . implode( ',', $ref_ids ) . ')' );
		}

		$wpdb->delete(
			$wpdb->usermeta, array(
				'meta_key' => '_sfwd-quizzes',
				'user_id'  => $user->ID,
			)
		);
		$wpdb->delete(
			$wpdb->usermeta, array(
				'meta_key' => '_sfwd-course_progress',
				'user_id'  => $user->ID,
			)
		);
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'completed_%' AND user_id = " . $user->ID );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'course_%_access_from' AND user_id = " . $user->ID );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'course_completed_%' AND user_id = " . $user->ID );
		$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'learndash_course_expired_%' AND user_id = " . $user->ID );

		// Added in v2.3.1 to remove the quiz locks for user.
		$wpdb->query( 'DELETE FROM ' . LDLMS_DB::get_table_name( 'quiz_lock' ) . ' WHERE user_id = ' . $user->ID );

		learndash_report_clear_user_activity_by_types( $user_id );

		$wpdb->delete( LDLMS_DB::get_table_name( 'quiz_lock' ), array( 'user_id' => $user->ID ) );
		$wpdb->delete( LDLMS_DB::get_table_name( 'quiz_toplist' ), array( 'user_id' => $user->ID ) );

		// Move user uploaded Assignements to Trash.
		$user_assignements_query_args = array(
			'post_type'   => 'sfwd-assignment',
			'post_status' => 'publish',
			'nopaging'    => true,
			'author'      => $user->ID,
		);

		$user_assignements_query = new WP_Query( $user_assignements_query_args );
		if ( $user_assignements_query->have_posts() ) {

			foreach ( $user_assignements_query->posts as $assignment_post ) {
				wp_trash_post( $assignment_post->ID );
			}
		}
		wp_reset_postdata();

		// Move user uploaded Essay to Trash.
		$user_essays_query_args = array(
			'post_type' => 'sfwd-essays',
			'nopaging'  => true,
			'author'    => $user->ID,
		);

		$user_essays_query = new WP_Query( $user_essays_query_args );
		if ( $user_essays_query->have_posts() ) {

			foreach ( $user_essays_query->posts as $essay_post ) {
				wp_trash_post( $essay_post->ID );
			}
		}
		wp_reset_postdata();

		do_action( 'learndash_delete_user_data', $user_id );
	}
}

add_action( 'delete_user', 'learndash_delete_user_data' );


/**
 * Get all Courses enrolled by User
 *
 * @since 2.2.1
 *
 * @param int   $user_id User ID.
 * @param array $course_query_args Array of WP_Query type arguments.
 * @param bool  $bypass_transient To bypass transient or not.
 */
function learndash_user_get_enrolled_courses( $user_id = 0, $course_query_args = array(), $bypass_transient = false ) {

	$course_ids = array();

	if ( empty( $user_id ) ) {
		return $course_ids;
	}

	$bypass_transient = true;
	$transient_key    = 'learndash_user_courses_' . $user_id;

	if ( ! $bypass_transient ) {
		$course_ids_transient = LDLMS_Transients::get( $transient_key );
	} else {
		$course_ids_transient = false;
	}

	if ( false === $course_ids_transient ) {

		$course_autoenroll_admin = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
		if ( 'yes' === $course_autoenroll_admin ) {
			$course_autoenroll_admin = true;
		} else {
			$course_autoenroll_admin = false;
		}

		if ( ( learndash_is_admin_user( $user_id ) ) && ( apply_filters( 'learndash_override_course_auto_enroll', $course_autoenroll_admin, $user_id ) ) ) {

			$defaults = array(
				'post_type' => 'sfwd-courses',
				'fields'    => 'ids',
				'nopaging'  => true,
			);

			$course_query_args = wp_parse_args( $course_query_args, $defaults );
			$course_query      = new WP_Query( $course_query_args );
			if ( ( isset( $course_query->posts ) ) && ( ! empty( $course_query->posts ) ) ) {
				$course_ids = $course_query->posts;
			}
		} else {

			$course_ids_open = learndash_get_open_courses();
			if ( ! empty( $course_ids_open ) ) {
				$course_ids = array_merge( $course_ids, $course_ids_open );
			}

			$course_ids_paynow = learndash_get_paynow_courses();
			if ( ! empty( $course_ids_paynow ) ) {
				$course_ids = array_merge( $course_ids, $course_ids_paynow );
			}

			if ( true === learndash_use_legacy_course_access_list() ) {
				$course_ids_access = learndash_get_user_course_access_list( $user_id );
				if ( ! empty( $course_ids_access ) ) {
					$course_ids = array_merge( $course_ids, $course_ids_access );
				}
			}

			$course_ids_meta = learndash_get_user_courses_from_meta( $user_id );
			if ( ! empty( $course_ids_meta ) ) {
				$course_ids = array_merge( $course_ids, $course_ids_meta );
			}

			$course_ids_groups = learndash_get_user_groups_courses_ids( $user_id );
			if ( ! empty( $course_ids_groups ) ) {
				$course_ids = array_merge( $course_ids, $course_ids_groups );
			}

			if ( ! empty( $course_ids ) ) {
				$course_ids = array_unique( $course_ids );

				$defaults = array(
					'post_type' => 'sfwd-courses',
					'fields'    => 'ids',
					'nopaging'  => true,
				);

				$course_query_args = wp_parse_args( $course_query_args, $defaults );
				$course_query_args['post__in'] = $course_ids;

				$course_query = new WP_Query( $course_query_args );
				if ( property_exists( $course_query, 'posts' ) ) {
					$course_ids = $course_query->posts;
				}
			}
		}

		LDLMS_Transients::set( $transient_key, $course_ids, MINUTE_IN_SECONDS );

	} else {
		$course_ids = $course_ids_transient;
	}

	return $course_ids;
}

/**
 * Set Courses enrolled by User
 *
 * @since 2.2.1
 *
 * @param   int   $user_id user id.
 * @param   array $user_courses_new An array of new course ids.
 */
function learndash_user_set_enrolled_courses( $user_id = 0, $user_courses_new = array() ) {

	if ( ! empty( $user_id ) ) {

		$user_courses_old = learndash_user_get_enrolled_courses( $user_id, true );
		if ( ( empty( $user_courses_old ) ) && ( ! is_array( $user_courses_old ) ) ) {
			$user_courses_old = array();
		}
		$user_courses_intersect = array_intersect( $user_courses_new, $user_courses_old );

		$user_courses_add = array_diff( $user_courses_new, $user_courses_intersect );
		if ( ! empty( $user_courses_add ) ) {
			foreach ( $user_courses_add as $course_id ) {
				ld_update_course_access( $user_id, $course_id );
			}
		}
		$user_courses_remove = array_diff( $user_courses_old, $user_courses_intersect );
		if ( ! empty( $user_courses_remove ) ) {
			foreach ( $user_courses_remove as $course_id ) {
				ld_update_course_access( $user_id, $course_id, true );
			}
		}

		// Finally clear our cache for other services.
		$transient_key = 'learndash_user_courses_' . $user_id;
		delete_transient( $transient_key );
	}
}

/**
 * Get all courses for the user via the user meta 'course_XXX_access_from'
 *
 * @since 2.2.1
 *
 * @param int $user_id user id.
 */
function learndash_get_user_courses_from_meta( $user_id = 0 ) {
	global $wpdb;

	$user_course_ids = array();

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {
		$sql_str = $wpdb->prepare( "SELECT REPLACE( REPLACE(meta_key, 'course_', ''), '_access_from', '' ) FROM " . $wpdb->usermeta . ' as usermeta WHERE user_id=%d AND meta_key LIKE %s ', $user_id, 'course_%_access_from' );

		$user_course_ids = $wpdb->get_col( $sql_str );
		if ( ! empty( $user_course_ids ) ) {
			$user_course_ids = array_map( 'intval', $user_course_ids );
		}
	}
	return $user_course_ids;
}

/**
 * Check if we show user course complete
 *
 * @param int $user_id User ID.
 * @return bool true or false
 */
function learndash_show_user_course_complete( $user_id = 0 ) {

	$show_options = false;

	if ( ! empty( $user_id ) ) {

		global $pagenow;

		if ( ( ( 'profile.php' == $pagenow ) || ( 'user-edit.php' == $pagenow ) ) && ( current_user_can( 'edit_users' ) ) ) {
			$show_options = true;
		} elseif ( 'admin.php' == $pagenow ) {
			if ( ( isset( $_GET['page'] ) ) && ( 'group_admin_page' == $_GET['page'] ) ) {
				if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {
					$show_options = true;
				}
			}
		}
	}

	// See example snippet of this filter https://bitbucket.org/snippets/learndash/bMA7r .
	return apply_filters( 'learndash_show_user_course_complete_options', $show_options, $user_id );
}

/**
 * Save User Courses Complete date
 *
 * @since 2.3
 *
 * @param int $user_id User ID.
 */
function learndash_save_user_course_complete( $user_id = 0 ) {

	// Hate this cross-logic. But here it is.
	// If we are clearing out the user's LD data then we abort this function. Now use going through the update.
	if ( ( isset( $_POST['learndash_delete_user_data'] ) ) && ( ! empty( $_POST['learndash_delete_user_data'] ) ) ) {
		return;
	}

	if ( ( ! empty( $user_id ) ) && ( current_user_can( 'edit_users' ) ) ) {
		if ( ( isset( $_POST['user_progress'] ) ) && ( isset( $_POST['user_progress'][ $user_id ] ) ) && ( ! empty( $_POST['user_progress'][ $user_id ] ) ) ) {
			if ( ( isset( $_POST[ 'user_progress-' . $user_id . '-nonce' ] ) ) && ( ! empty( $_POST[ 'user_progress-' . $user_id . '-nonce' ] ) ) ) {
				if ( wp_verify_nonce( $_POST[ 'user_progress-' . $user_id . '-nonce' ], 'user_progress-' . $user_id ) ) {
					$user_progress = (array) json_decode( stripslashes( $_POST['user_progress'][ $user_id ] ) );
					$user_progress = json_decode( json_encode( $user_progress ), true );

					$processed_course_ids = array();

					if ( ( isset( $user_progress['course'] ) ) && ( ! empty( $user_progress['course'] ) ) ) {

						$usermeta        = get_user_meta( $user_id, '_sfwd-course_progress', true );
						$course_progress = empty( $usermeta ) ? array() : $usermeta;

						$_COURSE_CHANGED = false; // Simple flag to let us know we changed the quiz data so we can save it back to user meta.

						foreach ( $user_progress['course'] as $course_id => $course_data_new ) {

							$processed_course_ids[ intval( $course_id ) ] = intval( $course_id );

							if ( isset( $course_progress[ $course_id ] ) ) {
								$course_data_old = $course_progress[ $course_id ];
							} else {
								$course_data_old = array();
							}

							$course_data_new = learndash_course_item_to_activity_sync( $user_id, $course_id, $course_data_new, $course_data_old );

							$course_progress[ $course_id ] = $course_data_new;

							$_COURSE_CHANGED = true;
						}

						if ( $_COURSE_CHANGED === true ) {
							update_user_meta( $user_id, '_sfwd-course_progress', $course_progress );
						}
					}

					if ( ( isset( $user_progress['quiz'] ) ) && ( ! empty( $user_progress['quiz'] ) ) ) {

						$usermeta       = get_user_meta( $user_id, '_sfwd-quizzes', true );
						$quizz_progress = empty( $usermeta ) ? array() : $usermeta;
						$_QUIZ_CHANGED  = false; // Simple flag to let us know we changed the quiz data so we can save it back to user meta.

						foreach ( $user_progress['quiz'] as $course_id => $course_quiz_set ) {
							foreach ( $course_quiz_set as  $quiz_id => $quiz_new_status ) {
								$quiz_meta = get_post_meta( $quiz_id, '_sfwd-quiz', true );

								if ( ! empty( $quiz_meta ) ) {
									$quiz_old_status = ! learndash_is_quiz_notcomplete( $user_id, array( $quiz_id => 1 ), false, $course_id );

									// For Quiz if the admin marks a qiz complete we don't attempt to update an existing attempt for the user quiz.
									// Instead we add a new entry. LD doesn't care as it will take the complete one for calculations where needed.
									if ( $quiz_new_status == true ) {
										if ( $quiz_old_status != true ) {

											// If the admin is marking the quiz complete AND the quiz is NOT already complete...
											// Then we add the minimal quiz data to the user profile.
											$quizdata = array(
												'quiz'   => $quiz_id,
												'score'  => 0,
												'count'  => 0,
												'pass'   => true,
												'rank'   => '-',
												'time'   => time(),
												'pro_quizid' => $quiz_meta['sfwd-quiz_quiz_pro'],
												'course' => $course_id,
												'points' => 0,
												'total_points' => 0,
												'percentage' => 0,
												'timespent' => 0,
												'has_graded' => false,
												'statistic_ref_id' => 0,
												'm_edit_by' => get_current_user_id(),  // Manual Edit By ID.
												'm_edit_time' => time(),          // Manual Edit timestamp.
											);

											$quizz_progress[] = $quizdata;

											if ( $quizdata['pass'] == true ) {
												$quizdata_pass = true;
											} else {
												$quizdata_pass = false;
											}

											// Then we add the quiz entry to the activity database.
											learndash_update_user_activity(
												array(
													'course_id'             => $course_id,
													'user_id'               => $user_id,
													'post_id'               => $quiz_id,
													'activity_type'         => 'quiz',
													'activity_action'       => 'insert',
													'activity_status'       => $quizdata_pass,
													'activity_started'      => $quizdata['time'],
													'activity_completed'    => $quizdata['time'],
													'activity_meta'         => $quizdata,
												)
											);

											$_QUIZ_CHANGED = true;

											do_action( 'learndash_quiz_completed', $quizdata, get_user_by( 'ID', $user_id ) );

										}
									} elseif ( $quiz_new_status !== true ) {
										// If we are unsetting a quiz ( changing from complete to incomplete). We need to do some complicated things...
										if ( $quiz_old_status === true ) {

											if ( ! empty( $quizz_progress ) ) {
												foreach ( $quizz_progress as $quiz_idx => $quiz_item ) {

													if ( ( $quiz_item['quiz'] == $quiz_id ) && ( $quiz_item['pass'] == true ) ) {
														$quizz_progress[ $quiz_idx ]['pass'] = false;

														// We need to update the activity database records for this quiz_id
														$activity_query_args = array(
															'post_ids'      => $quiz_id,
															'user_ids'      => $user_id,
															'activity_type' => 'quiz',
														);
														$quiz_activity       = learndash_reports_get_activity( $activity_query_args );
														if ( ( isset( $quiz_activity['results'] ) ) && ( ! empty( $quiz_activity['results'] ) ) ) {
															foreach ( $quiz_activity['results'] as $result ) {
																if ( ( isset( $result->activity_meta['pass'] ) ) && ( $result->activity_meta['pass'] == true ) ) {

																	// If the activity meta 'pass' element is set to true we want to update it to false.
																	learndash_update_user_activity_meta( $result->activity_id, 'pass', false );

																	// Also we need to update the 'activity_status' for this record
																	learndash_update_user_activity(
																		array(
																			'activity_id' => $result->activity_id,
																			'course_id' => $course_id,
																			'user_id' => $user_id,
																			'post_id' => $quiz_id,
																			'activity_type' => 'quiz',
																			'activity_action' => 'update',
																			'activity_status' => false,
																			// 'activity_started'        =>  $result->activity_started,
																		)
																	);
																}
															}
														}

														$_QUIZ_CHANGED = true;
													}

													/**
													 * Remove the quiz lock.
													 *
													 * @since 2.3.1
													 */
													if ( ( isset( $quiz_item['pro_quizid'] ) ) && ( ! empty( $quiz_item['pro_quizid'] ) ) ) {
														learndash_remove_user_quiz_locks( $user_id, $quiz_item['quiz'] );
													}
												}
											}
										}
									}

									// $course_id = learndash_get_course_id( $quiz_id );
									// if ( !empty( $course_id ) ) {
										$processed_course_ids[ intval( $course_id ) ] = intval( $course_id );
									// }
								}
							}
						}

						if ( $_QUIZ_CHANGED == true ) {
							update_user_meta( $user_id, '_sfwd-quizzes', $quizz_progress );
						}
					}

					if ( ! empty( $processed_course_ids ) ) {
						foreach ( array_unique( $processed_course_ids ) as $course_id ) {
							learndash_process_mark_complete( $user_id, $course_id );
						}
					}
				}
			}
		}
	}
}


/**
 * We need to compare the new course item progress array to the existing one. Also update the new activity db table
 *
 * @since 2.3
 *
 * @param  int   $user_id The user ID related to this course entry
 * @param  int   $course_id The course ID related to this user course entry
 * @param  array $course_data_new The new course data item
 * @param  array $course_data_old The old course data item
 * @return null
 */
function learndash_course_item_to_activity_sync( $user_id = 0, $course_id = 0, $course_data_new = array(), $course_data_old = array() ) {
	if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) || ( empty( $course_data_new ) ) ) {
		return;
	}

	// If we don't have the old course data we can get it.
	if ( empty( $course_data_old ) ) {
		$user_course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
		if ( isset( $user_course_progress[ $course_id ] ) ) {
			$course_data_old = $user_course_progress[ $course_id ];
		} else {
			$course_data_old = array();
		}
	}

	// First we loop over the new Course data lessons. We add any items not in the old array to the activity table
	if ( ( isset( $course_data_new['lessons'] ) ) && ( ! empty( $course_data_new['lessons'] ) ) ) {
		foreach ( $course_data_new['lessons'] as $lesson_id => $lesson_complete ) {
			if ( ! isset( $course_data_old['lessons'][ $lesson_id ] ) ) {
				$lesson_args = array(
					'course_id'     => $course_id,
					'user_id'       => $user_id,
					'post_id'       => $lesson_id,
					'activity_type' => 'lesson',
				);

				$lesson_activity = learndash_get_user_activity( $lesson_args );
				if ( ! $lesson_activity ) {
					if ( $lesson_complete == true ) {
						$lesson_args['activity_started']   = time();
						$lesson_args['activity_completed'] = time();
					} else {
						$lesson_args['activity_started']   = 0;
						$lesson_args['activity_completed'] = 0;
					}
				} else {
					if ( $lesson_complete == true ) {
						if ( empty( $lesson_activity->activity_started ) ) {
							$lesson_args['activity_started'] = time();
						}
						if ( empty( $lesson_activity->activity_completed ) ) {
							$lesson_args['activity_completed'] = time();
						}
					} else {
						$lesson_args['activity_started']   = 0;
						$lesson_args['activity_completed'] = 0;
					}
				}

				if ( $lesson_complete == true ) {
					$lesson_args['activity_status'] = true;
				} else {
					$lesson_args['activity_status'] = false;
				}
				learndash_update_user_activity( $lesson_args );
			}
		}
	}

	// Next we loop over the lesson topics. We add any new items not in the old array to the activity table.
	if ( ( isset( $course_data_new['topics'] ) ) && ( ! empty( $course_data_new['topics'] ) ) ) {
		foreach ( $course_data_new['topics'] as $lesson_id => $lesson_topics ) {
			if ( ! empty( $lesson_topics ) ) {
				foreach ( $lesson_topics as $topic_id => $topic_complete ) {
					if ( ! isset( $course_data_old['topics'][ $lesson_id ][ $topic_id ] ) ) {

						$topic_args = array(
							'course_id'     => $course_id,
							'user_id'       => $user_id,
							'post_id'       => $topic_id,
							'activity_type' => 'topic',
						);

						$topic_activity = learndash_get_user_activity( $topic_args );
						if ( ! $topic_activity ) {
							if ( $topic_complete == true ) {
								$topic_args['activity_started']   = time();
								$topic_args['activity_completed'] = time();
							} else {
								$topic_args['activity_started']   = 0;
								$topic_args['activity_completed'] = 0;
							}
						} else {
							if ( $topic_complete == true ) {
								if ( empty( $topic_activity->activity_started ) ) {
									$topic_args['activity_started'] = time();
								}
								if ( empty( $topic_activity->activity_completed ) ) {
									$topic_args['activity_completed'] = time();
								}
							} else {
								$topic_args['activity_started']   = 0;
								$topic_args['activity_completed'] = 0;
							}
						}

						if ( $topic_complete == true ) {
							$topic_args['activity_status'] = true;
						} else {
							$topic_args['activity_status'] = false;
						}

						learndash_update_user_activity( $topic_args );
					}
				}
			}
		}
	}

	// Then we loop over the old course lessons. Here if the lesson is NOT in the new course lessons we need to change the 'activity_status' to false.
	if ( ( isset( $course_data_old['lessons'] ) ) && ( ! empty( $course_data_old['lessons'] ) ) ) {
		foreach ( $course_data_old['lessons'] as $lesson_id => $lesson_complete ) {
			if ( ! isset( $course_data_new['lessons'][ $lesson_id ] ) ) {
				learndash_update_user_activity(
					array(
						'course_id'          => $course_id,
						'user_id'            => $user_id,
						'post_id'            => $lesson_id,
						'activity_type'      => 'lesson',
						'activity_status'    => false,
						'activity_started'   => 0,
						'activity_completed' => 0,
						'activity_updated'   => 0,
					)
				);
			}
		}
	}

	// Then we loop over the old course topics. Here if the lesson is NOT in the new course topics we need to change the 'activity_status' to false.
	if ( ( isset( $course_data_old['topics'] ) ) && ( ! empty( $course_data_old['topics'] ) ) ) {
		foreach ( $course_data_old['topics'] as $lesson_id => $lesson_topics ) {
			if ( ! empty( $lesson_topics ) ) {
				foreach ( $lesson_topics as $topic_id => $topic_complete ) {
					if ( ! isset( $course_data_new['topics'][ $lesson_id ][ $topic_id ] ) ) {
						learndash_update_user_activity(
							array(
								'course_id'          => $course_id,
								'user_id'            => $user_id,
								'post_id'            => $topic_id,
								'activity_type'      => 'topic',
								'activity_status'    => false,
								'activity_started'   => 0,
								'activity_completed' => 0,
								'activity_updated'   => 0,
							)
						);
					}
				}
			}
		}
	}

	// Finally we recalculate the course completed steps from the new course data.
	$completed_steps = learndash_course_get_completed_steps( $user_id, $course_id, $course_data_new );
	if ( ( ! isset( $course_data_new['completed'] ) ) || ( $completed_steps != $course_data_new['completed'] ) ) {
		$course_args = array(
			'course_id'     => $course_id,
			'user_id'       => $user_id,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);

		if ( empty( $completed_steps ) ) {
			$course_args['activity_status']    = false;
			$course_args['activity_started']   = 0;
			$course_args['activity_completed'] = 0;
			$course_args['activity_updated']   = 0;
		} else {
			$course_activity = learndash_get_user_activity( $course_args );
			if ( $course_activity ) {
				if ( intval( $course_activity->activity_started ) ) {
					$course_args['activity_started'] = intval( $course_activity->activity_started );
				} else {
					$course_args['activity_started'] = time();
				}
			}
		}

		$course_args['activity_meta'] = array(
			'steps_completed' => $completed_steps,
		);

		learndash_update_user_activity( $course_args );
	}

	// Then return the new course data to the caller.
	return $course_data_new;
}

/**
 * Get all Courses where the User ID in the course meta 'access_list' field.
 *
 * @since 2.3
 *
 * @param int $user_id User ID.
 * @return array an array of course_ids.
 */
function learndash_get_user_course_access_list( $user_id = 0 ) {
	global $wpdb;
	$user_course_ids = array();

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {
		if ( true === learndash_use_legacy_course_access_list() ) {
			$element = Learndash_Admin_Data_Upgrades::get_instance();
			$data_settings_courses = $element->get_data_settings( 'course-access-lists' );
			if ( version_compare( $data_settings_courses['version'], LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION, '>=') ) {

				$is_like = " postmeta.meta_value = '". $user_id ."'
					OR postmeta.meta_value REGEXP '^". $user_id .",' 
					OR postmeta.meta_value REGEXP ',". $user_id .",' 
					OR postmeta.meta_value REGEXP  ',". $user_id ."$'";

				$sql_str = "SELECT post_id FROM ". $wpdb->prefix ."postmeta as postmeta INNER JOIN ". $wpdb->prefix ."posts as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='course_access_list' AND (". $is_like .")";
			} else {
				// OR the access list is not empty.
				$not_like = " postmeta.meta_value NOT REGEXP '\"sfwd-courses_course_access_list\";s:0:\"\";' ";

				// OR the user ID is found in the access list. Note this pattern is four options
				// 1. The user ID is the only entry.
				// 1a. The single entry could be an int
				// 1b. Ot the single entry could be an string
				// 2. The user ID is at the front of the list as in "sfwd-courses_course_access_list";*:"X,*";
				// 3. The user ID is in middle "sfwd-courses_course_access_list";*:"*,X,*";
				// 4. The user ID is at the end "sfwd-courses_course_access_list";*:"*,X";.
				$is_like = " 
					postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";i:" . $user_id . ";s:34:\"sfwd-courses_course_lesson_orderby\"' 
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";i:" . $user_id . ";s:40:\"sfwd-courses_course_prerequisite_compare\"' 
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";i:" . $user_id . ";s:35:\"sfwd-courses_course_lesson_per_page\"' 
				
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . "\";s:34:\"sfwd-courses_course_lesson_orderby\"' 
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . "\";s:40:\"sfwd-courses_course_prerequisite_compare\"' 
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . "\";s:35:\"sfwd-courses_course_lesson_per_page\"' 

					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . ",(.*)\";s:34:\"sfwd-courses_course_lesson_orderby\"' 
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . ",(.*)\";s:40:\"sfwd-courses_course_prerequisite_compare\"' 
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . ",(.*)\";s:35:\"sfwd-courses_course_lesson_per_page\"' 

					OR postmeta.meta_value REGEXP  's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . ",(.*)\";s:34:\"sfwd-courses_course_lesson_orderby\"' 
					OR postmeta.meta_value REGEXP  's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . ",(.*)\";s:40:\"sfwd-courses_course_prerequisite_compare\"' 
					OR postmeta.meta_value REGEXP  's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . ",(.*)\";s:35:\"sfwd-courses_course_lesson_per_page\"' 

					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . "\";s:34:\"sfwd-courses_course_lesson_orderby\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . "\";s:40:\"sfwd-courses_course_prerequisite_compare\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . "\";s:35:\"sfwd-courses_course_lesson_per_page\"'
					";

				$sql_str = 'SELECT post_id FROM ' . $wpdb->postmeta . ' as postmeta INNER JOIN ' . $wpdb->posts . " as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND ( " . $not_like . ' AND (' . $is_like . '))';	
			}
			$user_course_ids = $wpdb->get_col( $sql_str );
		} else {
			$user_course_ids = learndash_user_get_enrolled_courses( $user_id );
		}
	}	
	return $user_course_ids;
}

/**
 * Get all Courses within all the Groups the user has access.
 *
 * @since 2.3
 *
 * @param int $user_id User ID.
 * @return array an array of course_ids.
 */
function learndash_get_user_groups_courses_ids( $user_id = 0 ) {
	$user_group_course_ids = array();

	if ( empty( $user_id ) ) {
		return $user_group_course_ids;
	}

	// Next we grap all the groups the user is a member of.
	$users_group_ids = learndash_get_users_group_ids( $user_id );

	if ( ! empty( $users_group_ids ) ) {
		// $user_group_course_ids = learndash_get_groups_courses_ids( $user_id, $users_group_ids );
		foreach ( $users_group_ids as $group_id ) {
			$group_course_ids = learndash_group_enrolled_courses( $group_id );
			if ( ! empty( $group_course_ids ) ) {
				$user_group_course_ids = array_merge( $user_group_course_ids, $group_course_ids );
			}
		}
	}

	return $user_group_course_ids;
}


/**
 * Record the last login time for the user.
 *
 * @since 2.3
 *
 * @param string $user_login login name.
 * @param object $user Object WP_User with user details.
 */
function learndash_wp_login( $user_login = '', $user = '' ) {
	if ( ! empty( $user_login ) ) {
		if ( ! ( $user instanceof WP_User ) ) {
			$user = get_user_by( 'login', $user_login );
		}

		if ( $user instanceof WP_User ) {
			update_user_meta( $user->ID, 'learndash-last-login', time() );
		}
	}
}
add_action( 'wp_login', 'learndash_wp_login', 99, 1 );


/**
 * Remove Quiz lock for specific User and Quiz
 *
 * @since 2.3.1
 * @param int $user_id the User ID.
 * @param int $quiz_id the Quiz ID (post ID).
 */
function learndash_remove_user_quiz_locks( $user_id = 0, $quiz_id = 0 ) {
	global $wpdb;

	if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_id ) ) ) {
		$pro_quiz_id = get_post_meta( $quiz_id, 'quiz_pro_id', true );
		if ( ! empty( $pro_quiz_id ) ) {
			$sql_str = $wpdb->prepare( 'DELETE FROM ' . LDLMS_DB::get_table_name( 'quiz_lock' ) . ' WHERE quiz_id = %d AND user_id = %s', $pro_quiz_id, $user_id );
			$wpdb->query( $sql_str );
		}
	}
}


/**
 * Given a User ID will retreive and return the calculated course points plus
 * the optional 'course_points' user meta.
 *
 * The course points calculation is based on all completed courses by the user. From
 * these completed courses we get any with assigned course points into a total
 *
 * Then we et the optional 'course_points' user meta value if present. This is a value the
 * admin can set to help increase the students point total.
 *
 * The calculate courses points plus user meta course points are added together and returned.
 *
 * @since 2.4.0
 *
 * @param int $user_id user id.
 *
 * @return bool 0 or greater course points.
 */
function learndash_get_user_course_points( $user_id = 0 ) {
	global $wpdb;

	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id = get_current_user_id();
	}

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {

		$sql_str = $wpdb->prepare(
			'SELECT DISTINCT postmeta.post_id as post_id, postmeta.meta_value as points
			FROM ' . $wpdb->postmeta . " as postmeta 
			WHERE postmeta.post_id IN 
			(
				SELECT DISTINCT REPLACE(user_meta.meta_key, 'course_completed_', '') as course_id 
				FROM " . $wpdb->usermeta . " as user_meta 
				WHERE user_meta.meta_key LIKE %s 
					AND user_meta.user_id = %d and user_meta.meta_value != ''
			) 
			AND postmeta.meta_key=%s 
			AND postmeta.meta_value != ''", 'course_completed_%', $user_id, 'course_points'
		);
		$course_points_results = $wpdb->get_results( $sql_str );

		$course_points_sum = 0;
		if ( ! empty( $course_points_results ) ) {
			foreach ( $course_points_results as $course_points ) {
				$course_points_sum += learndash_format_course_points( $course_points->points );
			}
		}

		$user_course_points = get_user_meta( $user_id, 'course_points', true );
		$user_course_points = learndash_format_course_points( $user_course_points );

		return learndash_format_course_points( $course_points_sum + $user_course_points );
	}
}


function learndash_get_quiz_statistics_ref_for_quiz_attempt( $user_id = 0, $quiz_attempt = array() ) {
	global $wpdb;

	if ( empty( $user_id ) ) {
		return 0;
	}
	
	if ( ( ! isset( $quiz_attempt['pro_quizid'] ) ) || ( empty( $quiz_attempt['pro_quizid'] ) ) ) {
		return 0;
	} 

	if ( ( ! isset( $quiz_attempt['time'] ) ) || ( empty( $quiz_attempt['time'] ) ) ) {
		return 0;
	} 

	$sql_str = $wpdb->prepare(
		'SELECT statistic_ref_id FROM ' . LDLMS_DB::get_table_name( 'quiz_statistic_ref' )  . ' as stat
		INNER JOIN ' . LDLMS_DB::get_table_name( 'quiz_master' ) . ' as master ON stat.quiz_id=master.id
		WHERE  user_id = %d AND quiz_id = %d AND create_time = %d AND master.statistics_on=1 LIMIT 1', $user_id, $quiz_attempt['pro_quizid'], $quiz_attempt['time']
	);

	$ref_id = $wpdb->get_var( $sql_str );
	return $ref_id;
}

/**
 * Get the shortcode [usermeta] available fields.
 *
 * @since 2.4.0
 *
 * @param array $attr An array of attributes to provide context for filter.
 *
 * @return array An array of available usermeta fields.
 */
function learndash_get_usermeta_shortcode_available_fields( $attr = array() ) {

	/**
	 * Added logic to allow protect certain user meta fields. The default
	 * fields is based on some of the fields returned via get_userdata().
	 *
	 * @since 2.4
	 */
	return apply_filters(
		'learndash_usermeta_shortcode_available_fields',
		array(
			'user_login'    => esc_html__( 'User Login', 'learndash' ),
			'display_name'  => esc_html__( 'User Display Name', 'learndash' ),
			'user_nicename' => esc_html__( 'User Nicename', 'learndash' ),
			'first_name'    => esc_html__( 'User First Name', 'learndash' ),
			'last_name'     => esc_html__( 'User Last Name', 'learndash' ),
			'nickname'      => esc_html__( 'User Nickname', 'learndash' ),
			'user_email'    => esc_html__( 'User Email', 'learndash' ),
			'user_url'      => esc_html__( 'User URL', 'learndash' ),
			'description'   => esc_html__( 'User Description', 'learndash' ),
		), $attr
	);
}
