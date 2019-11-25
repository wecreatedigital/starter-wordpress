<?php
/**
 * LearnDash Data Upgrades for User Quizzes
 *
 * @package LearnDash
 * @subpackage Data Upgrades
 */

if ( ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) && ( ! class_exists( 'Learndash_Admin_Data_Upgrades_User_Meta_Quizzes' ) ) ) {
	/**
	 * Class to create the Data Upgrade for Quizzes.
	 */
	class Learndash_Admin_Data_Upgrades_User_Meta_Quizzes extends Learndash_Admin_Data_Upgrades {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {

			$this->data_slug = 'user-meta-quizzes';
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
						// translators: placeholder: Quiz.
						esc_html_x( 'Upgrade User %s Data', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					); 
					?>
					</span>
					<p>
					<?php
					printf(
						// translators: placeholder: quiz.
						esc_html_x( 'This upgrade will sync your existing user data for %s into a new database table for better reporting. (Required)', 'placeholder: quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' )
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
								<p id="learndash-data-upgrades-continue-<?php echo $this->data_slug; ?>" class="learndash-data-upgrades-continue"><input type="checkbox" name="learndash-data-upgrades-continue" value="1" /> <?php esc_html_e( 'Continue previous upgrade processing?', 'learndash' ); ?></p>
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
		 * @return array $data Post data from AJAX call
		 */
		public function process_upgrade_action( $data = array() ) {
			global $wpdb;

			$this->init_process_times();

			if ( ( isset( $data['nonce'] ) ) && ( ! empty( $data['nonce'] ) ) ) {
				if ( ( wp_verify_nonce( $data['nonce'], 'learndash-data-upgrades-' . $this->data_slug . '-' . get_current_user_id() ) ) && ( current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) ) {
					$this->transient_key = $this->data_slug;

					if ( ( isset( $data['init'] ) ) && ( '1' === $data['init'] ) ) {
						unset( $data['init'] );

						if ( ( ! isset( $data['continue'] ) ) || ( 'true' !== $data['continue'] ) ) {
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

								$user_complete = $this->convert_user_meta_quizzes_progress_to_activity( intval( $user_id ) );
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
				$data['progress_percent'] = ceil( ( intval( $data['result_count'] ) / intval( $data['total_count'] ) ) * 100 );
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
		protected function convert_user_meta_quizzes_progress_to_activity( $user_id = 0 ) {
			global $wpdb;

			if ( ( empty( $user_id ) ) || ( ! isset( $this->transient_data['current_user']['user_id'] ) ) || ( $user_id !== $this->transient_data['current_user']['user_id'] ) ) {
				return;
			}

			delete_user_meta( $user_id, $this->meta_key );

			if ( empty( $this->transient_data['current_user']['item_idx'] ) ) {
				learndash_report_clear_user_activity_by_types( $user_id, array( 'quiz' ) );
			}

			$user_course_ids_used = array();

			$user_meta_quizzes_progress = get_user_meta( $user_id, '_sfwd-quizzes', true );
			if ( ( ! empty( $user_meta_quizzes_progress ) ) && ( is_array( $user_meta_quizzes_progress ) ) ) {
				$user_meta_quizzes_progress_changed = false;

				foreach ( $user_meta_quizzes_progress as $idx => $quiz_data ) {

					// Need a way to seek to a specific key starting point in an array.
					if ( $this->transient_data['current_user']['item_idx'] > intval( $idx ) ) {
						continue;
					}

					$this->transient_data['current_user']['item_idx'] = $idx;

					if ( $this->out_of_timer() ) {
						return;
					}

					$quiz_post = get_post( intval( $quiz_data['quiz'] ) );
					if ( ( ! $quiz_post ) || ( ! is_a( $quiz_post, 'WP_Post' ) ) || ( 'sfwd-quiz' !== $quiz_post->post_type ) ) {
						continue;
					}

					if ( ! isset( $quiz_data['course'] ) ) {
						$quiz_data['course'] = learndash_get_course_id( intval( $quiz_data['quiz'] ) );
						$user_meta_quizzes_progress[ $idx ]['course'] = $quiz_data['course'];
						$user_meta_quizzes_progress_changed = true;
					}

					// LEARNDASH-2744 : Not sure why these lines are here. We shoul be use the original started/completed dates
					// ----------------
					//unset( $quiz_data['started'] );
					//unset( $quiz_data['completed'] );

					if ( ( ! isset( $quiz_data['completed'] ) ) || ( empty( $quiz_data['completed'] ) ) ) {
						if ( ( isset( $quiz_data['time'] ) ) && ( ! empty( $quiz_data['time'] ) ) ) {
							$quiz_data['completed'] = $quiz_data['time'];
						} else {
							$quiz_data['completed'] = 0;
						}
					}

					if ( ( ! isset( $quiz_data['started'] ) ) || ( empty( $quiz_data['started'] ) ) ) {
						if ( ( isset( $quiz_data['time'] ) ) && ( ! empty( $quiz_data['time'] ) ) && ( isset( $quiz_data['timespent'] ) ) && ( ! empty( $quiz_data['timespent'] ) ) ) {
							$quiz_data['started'] = abs( intval( $quiz_data['time'] - round( $quiz_data['timespent'], 0 ) ) );
						} elseif ( isset( $quiz_data['completed'] ) ) {
							$quiz_data['started'] = $quiz_data['completed'];
						} else {
							$quiz_data['started'] = 0;
						}
					}

					$quiz_data_meta = $quiz_data;

					// Remove many fields that we either don't need or are duplicate of the main table columns.
					unset( $quiz_data_meta['quiz'] );
					unset( $quiz_data_meta['pro_quizid'] );
					unset( $quiz_data_meta['time'] );
					unset( $quiz_data_meta['completed'] );
					unset( $quiz_data_meta['started'] );
					if ( isset( $quiz_data_meta['course'] ) ) {
						unset( $quiz_data_meta['course'] );
					}
					if ( isset( $quiz_data_meta['lesson'] ) ) {
						unset( $quiz_data_meta['lesson'] );
					}
					if ( isset( $quiz_data_meta['topic'] ) ) {
						unset( $quiz_data_meta['topic'] );
					}


					if ( '-' == $quiz_data_meta['rank'] ) {
						unset( $quiz_data_meta['rank'] );
					}

					if ( true == $quiz_data['pass'] ) {
						$quiz_data_pass = true;
					} else {
						$quiz_data_pass = false;
					}

					$activity_id = learndash_update_user_activity(
						array(
							'course_id'          => $quiz_data['course'],
							'post_id'            => $quiz_data['quiz'],
							'user_id'            => $user_id,
							'activity_type'      => 'quiz',
							'activity_status'    => $quiz_data_pass,
							'activity_started'   => $quiz_data['started'],
							'activity_completed' => $quiz_data['completed'],
							'activity_meta'      => $quiz_data_meta,
						)
					);
				}

				if ( true === $user_meta_quizzes_progress_changed ) {
					update_user_meta( $user_id, '_sfwd-quizzes', $user_meta_quizzes_progress );
				}
			}

			return true;
		}
	}
}

add_action( 'learndash_data_upgrades_init', function() {
	Learndash_Admin_Data_Upgrades_User_Meta_Quizzes::add_instance();
} );
