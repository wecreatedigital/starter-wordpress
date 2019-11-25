<?php
/**
 * LearnDash Data Upgrades for User Courses
 *
 * @package LearnDash
 * @subpackage Data Upgrades
 */

if ( ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) && ( ! class_exists( 'Learndash_Admin_Data_Upgrades_User_Meta_Courses' ) ) ) {
	/**
	 * Class to create the Data Upgrade for Courses.
	 */
	class Learndash_Admin_Data_Upgrades_User_Meta_Courses extends Learndash_Admin_Data_Upgrades {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->data_slug = 'user-meta-courses';
			parent::__construct();
			parent::register_upgrade_action();
		}

		/**
		 * Show data upgrade row for this instance.
		 *
		 * @since 2.3
		 */
		public function show_upgrade_action() {
			?>
			<tr id="learndash-data-upgrades-container-<?php echo esc_attr( $this->data_slug ); ?>" class="learndash-data-upgrades-container">
				<td class="learndash-data-upgrades-button-container">
					<button class="learndash-data-upgrades-button button button-primary" data-nonce="<?php echo wp_create_nonce( 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ); ?>" data-slug="<?php echo esc_attr( $this->data_slug ); ?>">
					<?php
						esc_html_e( 'Upgrade', 'learndash' );
					?>
					</button>
				</td>
				<td class="learndash-data-upgrades-status-container">
					<span class="learndash-data-upgrades-name">
					<?php
					printf(
						// translators: placeholder: Course.
						esc_html_x( 'Upgrade User %s Data', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					?>
					</span>
					<p>
					<?php
					printf(
						// translators: placeholder: Course, course.
						esc_html_x( 'This upgrade will sync your existing user data for %s into a new database table for better reporting. (Required)', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					);
					?>
					</p>
					<p class="description"><?php echo $this->get_last_run_info(); ?></p>	

					<?php
					$show_progess = false;
					$this->transient_key = $this->data_slug;
					$this->transient_data = $this->get_transient( $this->transient_key );
					if ( ! empty( $this->transient_data ) ) {
						if ( isset( $this->transient_data['result_count'] ) ) {
							$this->transient_data['result_count'] = intval( $this->transient_data['result_count'] );
						} else {
							$this->transient_data['result_count'] = 0;
						}
						if ( isset( $this->transient_data['total_count'] ) ) {
							$this->transient_data['total_count'] = intval( $this->transient_data['total_count'] );
						} else {
							$this->transient_data['total_count'] = 0;
						}

						if ( ( ! empty( $this->transient_data['result_count'] ) ) && ( ! empty( $this->transient_data['total_count'] ) ) && ( $this->transient_data['result_count'] != $this->transient_data['total_count'] ) ) {

							$show_progess = true;
							?>
							<p id="learndash-data-upgrades-continue-<?php echo esc_attr( $this->data_slug ); 
							?>" class="learndash-data-upgrades-continue"><input type="checkbox" name="learndash-data-upgrades-continue" value="1" /> <?php esc_html_e( 'Continue previous upgrade processing?', 'learndash' ); ?></p>
							<?php
						}
					}

					$progress_style       = 'display:none;';
					$progress_meter_style = '';
					$progress_label       = '';
					$progress_slug        = '';

					if ( true === $show_progess ) {
						$progress_style = '';
						$data = $this->transient_data;
						$data = $this->build_progress_output( $data );
						if ( ( isset( $data['progress_percent'] ) ) && ( ! empty( $data['progress_percent'] ) ) ) {
							$progress_meter_style = 'width: ' . $data['progress_percent'] . '%';
						}

						if ( ( isset( $data['progress_label'] ) ) && ( ! empty( $data['progress_label'] ) ) ) {
							$progress_label = $data['progress_label'];
						}

						if ( ( isset( $data['progress_slug'] ) ) && ( ! empty( $data['progress_slug'] ) ) ) {
							$progress_slug = 'progress-label-' . $data['progress_slug'];
						}
					}
					?>
					<div style="<?php echo esc_attr( $progress_style ); ?>" class="meter learndash-data-upgrades-status">
						<div class="progress-meter">
							<span class="progress-meter-image" style="<?php echo esc_attr( $progress_meter_style ); ?>"></span>
						</div>
						<div class="progress-label <?php echo esc_attr( $progress_slug ); ?>"><?php echo esc_attr( $progress_label ); ?></div>
					</div>
				</td>
			</tr>
			<?php
		}

		/**
		 * Class method for the AJAX update logic
		 * This function will determine what users need to be converted. Then the course and quiz functions
		 * will be called to convert each individual user data set.
		 *
		 * @since 2.3
		 *
		 * @param  array $data Post data from AJAX call.
		 * @return array $data Post data from AJAX call.
		 */
		public function process_upgrade_action( $data = array() ) {
			global $wpdb;

			$this->init_process_times();

			if ( ( isset( $data['nonce'] ) ) && ( ! empty( $data['nonce'] ) ) ) {
				if ( ( wp_verify_nonce( $data['nonce'], 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ) ) && ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) ) {
					$this->transient_key = $this->data_slug;

					if ( ( isset( $data['init'] ) ) && ( '1' === $data['init'] ) ) {
						unset( $data['init'] );

						if ( ( ! isset( $data['continue'] ) ) || ( 'true' != $data['continue'] ) ) {
							learndash_activity_clear_mismatched_users();
							learndash_activity_clear_mismatched_posts();

							/**
							 * Transient_data is used to store the local server state information and will
							 * saved in a transient type options variable.
							 */
							$this->transient_data = array();
							// Hold the number of completed/processed items.
							$this->transient_data['result_count']     = 0;
							$this->transient_data['current_user']     = array();
							$this->transient_data['progress_started'] = time();
							$this->transient_data['progress_user']    = get_current_user_id();

							$this->query_items();
						} else {
							$this->transient_data = $this->get_transient( $this->transient_key );
						}
						$this->set_option_cache( $this->transient_key, $this->transient_data );
					} else {

						$this->transient_data = $this->get_transient( $this->transient_key );
						if ( ( ! isset( $this->transient_data['process_users'] ) ) || ( empty( $this->transient_data['process_users'] ) ) ) {
							$this->query_items();
						}

						if ( ( isset( $this->transient_data['process_users'] ) ) && ( ! empty( $this->transient_data['process_users'] ) ) ) {
							foreach ( $this->transient_data['process_users'] as $user_idx => $user_id ) {
								$user_id = intval( $user_id );
								if ( ( ! isset( $this->transient_data['current_user']['user_id'] ) ) || ( $this->transient_data['current_user']['user_id'] !== $user_id ) ) {
									$this->transient_data['current_user'] = array(
										'user_id' => $user_id,
										'item_idx' => 0,
									);
								}

								$user_complete = $this->convert_user_meta_courses_progress_to_activity( intval( $user_id ) );
								if ( true === $user_complete ) {
									$this->transient_data['current_user'] = array();
									unset( $this->transient_data['process_users'][ $user_idx ] );
									$this->transient_data['result_count'] = (int) $this->transient_data['result_count'] + 1;
								}

								$this->set_option_cache( $this->transient_key, $this->transient_data );

								if ( $this->out_of_timer() ) {
									break;
								}
							}
						}
					}
				}
			}

			$data = $this->build_progress_output( $data );

			// If we are at 100% then we update the internal data settings so other parts of LD know the upgrade has been run.
			if ( ( isset( $data['progress_percent'] ) ) && ( 100 == $data['progress_percent'] ) ) {

				$this->set_last_run_info( $data );
				$data['last_run_info'] = $this->get_last_run_info();

				$this->remove_transient( $this->transient_key );
			}

			return $data;
		}

		/**
		 * Common function to query needed items.
		 *
		 * @since 2.6.0
		 *
		 * @param boolean $increment_paged default true to increment paged.
		 */
		protected function query_items( $increment_paged = true ) {
			// Initialize or increment the current paged or items.
			if ( ! isset( $this->transient_data['paged'] ) ) {
				$this->transient_data['paged'] = 1;
			} else {
				if ( true === $increment_paged ) {
					$this->transient_data['paged'] = (int) $this->transient_data['paged'] + 1;
				}
			}

			$this->transient_data['query_args'] = array(
				'fields' => 'ID',
				'paged' => $this->transient_data['paged'],
				'number' => LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE,
			);
			$this->transient_data['query_args'] = apply_filters( 'learndash_data_upgrade_query', $this->transient_data['query_args'], $this->data_slug );
			$user_query = new WP_User_Query( $this->transient_data['query_args'] );
			if ( is_a( $user_query, 'WP_User_Query' ) ) {
				$this->transient_data['total_count'] = $user_query->get_total();
				$this->transient_data['process_users'] = $user_query->get_results();
			}
		}

		/**
		 * Common function to build the returned data progress output.
		 *
		 * @since 2.6.0
		 *
		 * @param array $data Array of existing data elements.
		 * @return array or data.
		 */
		protected function build_progress_output( $data = array() ) {
			if ( isset( $this->transient_data['result_count'] ) ) {
				$data['result_count'] = intval( $this->transient_data['result_count'] );
			} else {
				$data['result_count'] = 0;
			}

			if ( isset( $this->transient_data['total_count'] ) ) {
				$data['total_count'] = intval( $this->transient_data['total_count'] );
			} else {
				$data['total_count'] = 0;
			}

			if ( ! empty( $data['total_count'] ) ) {
				$data['progress_percent'] = ( $data['result_count'] / $data['total_count'] ) * 100;
			} else {
				$data['progress_percent'] = 0;
			}

			if ( 100 == $data['progress_percent'] ) {
					$progress_status = __( 'Complete', 'learndash' );
					$data['progress_slug'] = 'complete';
			} else {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					$progress_status = __( 'In Progress', 'learndash' );
					$data['progress_slug'] = 'in-progress';
				} else {
					$progress_status = __( 'Incomplete', 'learndash' );
					$data['progress_slug'] = 'in-complete';
				}
			}

			$data['progress_label'] = sprintf(
				// translators: placeholders: result count, total count.
				esc_html_x( '%1$s: %2$d of %3$d Users', 'placeholders: progress status, result count, total count', 'learndash' ), $progress_status, $data['result_count'], $data['total_count']
			);

			return $data;
		}

		/**
		 * Convert single user quiz attempts to Activity DB entries.
		 *
		 * @since 2.3
		 *
		 * @param int $user_id User ID of user to convert.
		 * @return boolean true if complete, false if not.
		 */
		private function convert_user_meta_courses_progress_to_activity( $user_id = 0 ) {
			global $wpdb;

			if ( ( empty( $user_id ) ) || ( ! isset( $this->transient_data['current_user']['user_id'] ) ) || ( $user_id !== $this->transient_data['current_user']['user_id'] ) ) {
				return;
			}

			delete_user_meta( $user_id, $this->meta_key );

			if ( isset( $this->transient_data['current_user']['activity_ids'] ) ) {
				$activity_ids = $this->transient_data['current_user']['activity_ids'];
			} else {
				$activity_ids = array();
			}

			if ( ! isset( $activity_ids['last_course_id'] ) ) {
				$activity_ids['last_course_id'] = 0;
			} else {
				$activity_ids['last_course_id'] = intval( $activity_ids['last_course_id'] );
			}

			if ( ! isset( $activity_ids['existing'] ) ) {
				$activity_ids['existing'] = array();
			}
			if ( ! isset( $activity_ids['current'] ) ) {
				$activity_ids['current'] = array();
			}
			if ( ! isset( $activity_ids['course_ids_used'] ) ) {
				$activity_ids['course_ids_used'] = array();
			}

			$user_meta_courses_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
			if ( ( ! empty( $user_meta_courses_progress ) ) && ( is_array( $user_meta_courses_progress ) ) ) {
				/**
				 * We sort the course progress array because we may need to save our place and
				 * need to knowwhere we left off.
				 */
				ksort( $user_meta_courses_progress );

				foreach ( $user_meta_courses_progress as $course_id => $course_data ) {
					// Need a way to seek to a specific key starting point in an array.
					if ( $activity_ids['last_course_id'] >= $course_id ) {
						continue;
					}

					$course_post = get_post( $course_id );
					if ( ( $course_post ) && is_a( $course_post, 'WP_Post' ) ) {

						$total_activity_items = 0;
						$user_course_access_from = 0;
						$user_course_completed = 0;

						// Then loop over Lessons.
						if ( ( isset( $course_data['lessons'] ) ) && ( ! empty( $course_data['lessons'] ) ) ) {
							foreach ( $course_data['lessons'] as $lesson_id => $lesson_complete ) {
								$lesson_post = get_post( $lesson_id );
								if ( ( $lesson_post ) && is_a( $lesson_post, 'WP_Post' ) ) {

									$lesson_args = array(
										'course_id'     => $course_id,
										'post_id'       => $lesson_id,
										'user_id'       => $user_id,
										'activity_type' => 'lesson',
										'data_upgrade'  => true,
										'activity_meta' => array(),
									);

									if ( ! empty( $user_course_access_from ) ) {
										$lesson_args['activity_started']	= $user_course_access_from;
									}

									if ( true == $lesson_complete ) {
										$lesson_args['activity_status'] = true;
										if ( ! empty( $user_course_completed ) ) {
											$lesson_args['activity_completed'] = $user_course_completed;
										}
									}
									$activity_id = learndash_update_user_activity( $lesson_args );
									if ( ! empty( $activity_id ) ) {
										$activity_ids['current'][] = $activity_id;
									}

									$total_activity_items++;
								}
							}
						}

						// Then loop over Topics.
						if ( ( isset( $course_data['topics'] ) ) && ( ! empty( $course_data['topics'] ) ) ) {
							foreach ( $course_data['topics'] as $lesson_id => $lessons_topics ) {
								if ( ! empty( $lessons_topics ) ) {
									foreach ( $lessons_topics as $topic_id => $topic_complete ) {
										$topic_post = get_post( $topic_id );
										if ( ( $lesson_post ) && is_a( $topic_post, 'WP_Post' ) ) {

											$topic_args = array(
												'course_id'     => $course_id,
												'post_id'       => $topic_id,
												'user_id'       => $user_id,
												'activity_type' => 'topic',
												'data_upgrade'  => true,
												'activity_meta' => array(),
											);

											if ( ! empty( $user_course_access_from ) ) {
												$topic_args['activity_started'] = $user_course_access_from;
											}

											if ( $topic_complete == true )  {
												$topic_args['activity_status'] = true;
												if ( ! empty( $user_course_completed ) ) {
													$topic_args['activity_completed'] = $user_course_completed;
												}
											}

											$activity_id = learndash_update_user_activity( $topic_args );
											if ( ! empty( $activity_id ) ) {
												$activity_ids['current'][] = $activity_id;
											}
											$total_activity_items++;
										}
									}
								}
							}
						}

						$user_course_completed   = get_user_meta( $user_id, 'course_completed_' . $course_id, true );
						$user_course_access_from = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );

						if ( ! empty( $user_course_access_from ) ) {
							$activity_id = learndash_update_user_activity(
								array(
									'course_id'        => $course_id,
									'post_id'          => $course_id,
									'user_id'          => $user_id,
									'activity_type'    => 'access',
									'activity_started' => $user_course_access_from,
									'data_upgrade'     => true,
								)
							);
							if ( ! empty( $activity_id ) ) {
								$activity_ids['current'][] = $activity_id;
							}
						}

						$user_course_access_from = 0;

						// First add the main Course entry.
						$course_args = array(
							'course_id'     => $course_id,
							'post_id'       => $course_id,
							'activity_type' => 'course',
							'user_id'       => $user_id,
							'data_upgrade'  => true,
							'activity_meta' => array(
								'steps_total'     => intval( $course_data['total'] ),
								'steps_completed' => intval( $course_data['completed'] ),
							),
						);

						$steps_completed = intval( $course_data['completed'] );
						if ( ( ! empty( $steps_completed ) ) && ( $steps_completed >= intval( $course_data['total'] ) ) ) {
							$course_args['activity_status'] = true;

							// Finally if there is a Course Complete date we add it.
							if ( ! empty( $user_course_completed ) ) {
								$course_args['activity_completed'] = $user_course_completed;
							}
						} else if ( ! empty( $steps_completed ) ) {
							$course_args['activity_status'] = false;
						}

						if ( isset( $course_data['last_id'] ) ) {
							$course_args['activity_meta']['steps_last_id'] = intval( $course_data['last_id'] );
						}

						$activity_id = learndash_update_user_activity( $course_args ); 
						if ( ! empty( $activity_id ) ) {
							$activity_ids['current'][] = $activity_id;
						}
					}

					$activity_ids['last_course_id'] = $course_id;
					$activity_ids['course_ids_used'][ $course_id ] = $course_id;
					//update_user_meta( $user_id, $this->meta_key, $activity_ids );
					$this->transient_data['current_user']['activity_ids'] = $activity_ids;

					if ( $this->out_of_timer() ) {
						return;
					}
				}
			}

			/**
			 * Finally we go through the user's meta again to grab the random course access items. These
			 * would be there If the user was granted access but didn't actually start a lesson/quiz etc.
			 */
			$user_courses_access_sql = $wpdb->prepare( 'SELECT user_id, meta_key, meta_value as course_access_from FROM '. $wpdb->usermeta .' WHERE user_id=%d', $user_id );
			$user_courses_access_sql .= " AND meta_key LIKE 'course_%_access_from'";
			$user_courses_access = $wpdb->get_results( $user_courses_access_sql );

			if ( ! empty( $user_courses_access ) ) {
				foreach ( $user_courses_access as $user_course_access ) {

					if ( ( property_exists( $user_course_access, 'meta_key' ) ) && ( ! empty( $user_course_access->meta_key ) ) ) {
						$user_course_access->course_id = str_replace( 'course_', '', $user_course_access->meta_key );
						$user_course_access->course_id = str_replace( '_access_from', '', $user_course_access->course_id );

						if ( ! isset( $activity_ids['course_ids_used'][ $user_course_access->course_id ] ) ) {

							$activity_id = learndash_update_user_activity(
								array(
									'course_id'     => $user_course_access->course_id,
									'post_id'       => $user_course_access->course_id,
									'user_id'       => $user_id,
									'activity_type' => 'access',
									'data_upgrade'  => true,
								)
							);

							if ( ! empty( $activity_id ) ) {
								$activity_ids['current'][] = $activity_id;
							}
						}
					}
				}
			}

			// Here we purge items from the Activity DB where we don't have a match to processed 'current' course items.
			$activity_ids['existing'] = learndash_report_get_activity_by_user_id( $user_id, array( 'access', 'course', 'lesson', 'topic' ) );
			if ( empty( $activity_ids['existing'] ) ) {
				$activity_ids['existing'] = array();
			}

			if ( ( ! empty( $activity_ids['existing'] ) ) && ( ! empty( $activity_ids['current'] ) ) ) {

				$activity_ids['existing'] = array_map( 'intval', $activity_ids['existing'] );
				sort( $activity_ids['existing'] );

				$activity_ids['current'] = array_map( 'intval', $activity_ids['current'] );
				sort( $activity_ids['current'] );

				$activity_ids_delete = array_diff( $activity_ids['existing'], $activity_ids['current'] );

				if ( ! empty( $activity_ids_delete ) ) {
					learndash_report_clear_by_activity_ids( $activity_ids_delete );
				}
			}

			//update_user_meta( $user_id, $this->meta_key, 'COMPLETE' );

			return true;
		}
	}
}

add_action( 'learndash_data_upgrades_init', function() {
	Learndash_Admin_Data_Upgrades_User_Meta_Courses::add_instance();
} );
