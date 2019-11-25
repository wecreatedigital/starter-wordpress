<?php
/**
 * LearnDash Admin Group Post Edit Class.
 *
 * @package LearnDash
 * @subpackage Admin
 */

if ( ! class_exists( 'Learndash_Admin_Groups_Edit' ) ) {
	/**
	 * Class for LearnDash Admin Group Post Edit.
	 */
	class Learndash_Admin_Groups_Edit {
		/**
		 * Set our post type.
		 *
		 * @var string $post_type Post Type used in this class.
		 */
		private $groups_type = 'groups';

		/**
		 * Public constructor for class.
		 */
		public function __construct() {
			// Hook into the on-load action for our post_type editor.
			add_action( 'load-post.php', array( $this, 'on_load_groups' ) );
			add_action( 'load-post-new.php', array( $this, 'on_load_groups' ) );

			add_filter( 'manage_groups_posts_columns', array( $this, 'set_groups_columns' ) );
			add_action( 'manage_groups_posts_custom_column', array( $this, 'display_groups_columns' ), 10, 2 );
		}

		/**
		 * Function called when WP load the page.
		 * Fires on action 'load-post.php'
		 * Fires on action 'load-post-new.php'
		 */
		public function on_load_groups() {
			global $typenow;

			if ( ( empty( $typenow ) ) || ( $typenow != $this->groups_type ) ) {
				return;
			}

			wp_enqueue_script(
				'learndash-admin-binary-selector-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-binary-selector' . leardash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);

			wp_enqueue_style(
				'learndash-admin-binary-selector-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-binary-selector' . leardash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash-admin-binary-selector-style', 'rtl', 'replace' );
			
			// Add Metabox and hook for saving post metabox.
			add_action( 'add_meta_boxes', array( $this, 'learndash_groups_add_custom_box' ) );
			add_action( 'save_post', array( $this, 'learndash_groups_save_postdata' ) );

		}

		/**
		 * Register Groups meta box for admin
		 *
		 * Managed enrolled groups, users and group leaders
		 *
		 * @since 2.1.2
		 */
		public function learndash_groups_add_custom_box() {
			add_meta_box(
				'learndash_groups',
				esc_html__( 'LearnDash Group Admin', 'learndash' ),
				array( $this, 'learndash_groups_page_box' ),
				$this->groups_type
			);
		}


		/**
		 * Prints content for Groups meta box for admin
		 *
		 * @since 2.1.2
		 *
		 * @param WP_Post $post WP_Post object of group.
		 * @return string meta box HTML output.
		 */
		public function learndash_groups_page_box( WP_Post $post ) {
			global $wpdb;

			$post_id = $post->ID;

			// Use nonce for verification.
			wp_nonce_field( plugin_basename( __FILE__ ), 'learndash_groups_nonce' );

			?>
			<div id="learndash_groups_page_box" class="learndash_groups_page_box">
			<?php
				$ld_auto_enroll_group_courses = get_post_meta( $post_id, 'ld_auto_enroll_group_courses', true );
				?>
				<p><input type="checkbox" id="learndash_auto_enroll_group_courses" name="learndash_auto_enroll_group_courses" value="yes" <?php checked( $ld_auto_enroll_group_courses, 'yes' ); 
				?> 
				/> <?php
				printf( 
					// translators: placeholder: course.
					esc_html_x( 'Enable automatic group enrollment when a user enrolls into any associated group %s', 'placeholder: course', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				); 
				?></p><?php

				$ld_binary_selector_group_courses = new Learndash_Binary_Selector_Group_Courses(
					array(
						'group_id'     => $post_id,
						'selected_ids' => learndash_group_enrolled_courses( $post_id, true ),
					)
				);

				$ld_binary_selector_group_courses->show();

				/**
				 * Set the included users IDs to be user for the Group Leader selector
				 * As of LD v2.3 we include users in the group_leader and administrator roles.
				 */
				$gl_included_ids = array();
				$group_leader_query = new WP_User_Query(
					array(
						'role__in' => array( 'group_leader', 'administrator' ),
						'fields'   => 'ID'
					)
				);
				$gl_included_ids = $group_leader_query->get_results();
				if ( ! empty( $gl_included_ids ) ) {

					$ld_binary_selector_group_leaders = new Learndash_Binary_Selector_Group_Leaders(
						array(
							'group_id'     => $post_id,
							'selected_ids' => learndash_get_groups_administrator_ids( $post_id, true ),
							'included_ids' => $gl_included_ids,
						)
					);
					$ld_binary_selector_group_leaders->show();
				}

				$ld_binary_selector_group_users = new Learndash_Binary_Selector_Group_Users(
					array(
						'group_id'	   => $post_id,
						'selected_ids' => learndash_get_groups_user_ids( $post_id, true ),
					)
				);
				$ld_binary_selector_group_users->show();
			?>

			</div>
			<?php 
		}

		/**
		 * When the post is saved, save the data in the Groups custom metabox
		 *
		 * @since 2.1.0
		 *
		 * @param  int $post_id
		 */
		public function learndash_groups_save_postdata( $post_id ) {
			/**
			 * verify if this is an auto save routine. If it is our 
			 * form has not been submitted, so we dont want to do anything.
			 */
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			/**
			 * Verify this came from the our screen and with proper authorization,
			 * because save_post can be triggered at other times.
			 */
			if ( ! isset( $_POST['learndash_groups_nonce'] ) || ! wp_verify_nonce( $_POST['learndash_groups_nonce'], plugin_basename( __FILE__ ) ) ) {
				return;
			}

			// Check permissions.
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}

			if ( 'groups' != $_POST['post_type'] ) {
				return;
			}

			$group_leaders = array();
			if ( ( isset( $_POST['learndash_group_leaders'] ) ) && ( isset( $_POST['learndash_group_leaders'][ $post_id ] ) ) && ( ! empty( $_POST['learndash_group_leaders'][ $post_id ] ) ) ) {
				if ( ( isset( $_POST['learndash_group_leaders-' . $post_id . '-changed'] ) ) && ( ! empty( $_POST['learndash_group_leaders-' . $post_id . '-changed'] ) ) ) {
					if ( ( isset( $_POST['learndash_group_leaders-' . $post_id . '-nonce'] ) ) && ( ! empty( $_POST['learndash_group_leaders-' . $post_id . '-nonce'] ) ) ) {
						$group_leaders = (array) json_decode( stripslashes( $_POST['learndash_group_leaders'][ $post_id ] ) );
						learndash_set_groups_administrators( $post_id, $group_leaders );
					}
				}
			}

			$group_users = array();
			if ( ( isset( $_POST['learndash_group_users'] ) ) && ( isset( $_POST['learndash_group_users'][ $post_id ] ) ) && ( ! empty( $_POST['learndash_group_users'][ $post_id ] ) ) ) {
				if ( ( isset( $_POST['learndash_group_users-' . $post_id . '-changed'] ) ) && ( ! empty( $_POST['learndash_group_users-' . $post_id . '-changed'] ) ) ) {
					if ( ( isset( $_POST['learndash_group_users-' . $post_id . '-nonce'] ) ) && ( ! empty( $_POST['learndash_group_users-' . $post_id . '-nonce'] ) ) ) {
						$group_users = (array) json_decode( stripslashes( $_POST['learndash_group_users'][ $post_id ] ) );
						learndash_set_groups_users( $post_id, $group_users );
					}
				}
			}

			$group_courses = array();
			if ( ( isset( $_POST['learndash_group_courses'] ) ) && ( isset( $_POST['learndash_group_courses'][ $post_id ] ) ) && ( ! empty( $_POST['learndash_group_courses'][ $post_id ] ) ) ) {
				if ( ( isset( $_POST['learndash_group_courses-' . $post_id . '-changed'] ) ) && ( ! empty( $_POST['learndash_group_courses-' . $post_id . '-changed'] ) ) ) {
					if ( ( isset( $_POST['learndash_group_courses-' . $post_id . '-nonce'] ) ) && ( ! empty( $_POST['learndash_group_courses-' . $post_id . '-nonce'] ) ) ) {
						$group_courses = (array) json_decode( stripslashes( $_POST['learndash_group_courses'][ $post_id ] ) );
						learndash_set_group_enrolled_courses( $post_id, $group_courses );
					}
				}
			}

			if ( ( isset( $_POST['learndash_auto_enroll_group_courses'] ) ) && ( 'yes' == $_POST['learndash_auto_enroll_group_courses'] ) ) {
				update_post_meta( $post_id, 'ld_auto_enroll_group_courses', 'yes' );
			} else {
				delete_post_meta( $post_id, 'ld_auto_enroll_group_courses' );
			}

			/**
			 * Hook when group postdata is updated
			 *
			 * $post_id       int 	Post ID of the group
			 * $group_leaders array 	Group leaders
			 * $group_users   array 	Group users
			 * $group_courses array 	Group courses
			 */
			do_action( 'ld_group_postdata_updated', $post_id, $group_leaders, $group_users, $group_courses );
		}

		/**
		 * Add columns to Group table listing.
		 *
		 * @param array $columns Array of columns.
		 *
		 * @return array $columns.
		 */
		public function set_groups_columns( $columns = array() ) {

			$columns_new = array();

			foreach ( $columns as $col_key => $col_label ) {
				if ( 'date' == $col_key ) {
					$columns_new['groups_group_leaders'] = esc_html__( 'Group Leaders', 'learndash' );
					$columns_new['groups_group_courses'] = sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'Group %s', 'Group Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					);
					$columns_new['groups_group_users'] = esc_html__( 'Group Users', 'learndash' );
				}
				$columns_new[ $col_key ] = $col_label;
			}
			return $columns_new;
		}

		/**
		 * Display Group columns.
		 *
		 * @param string  $column_name Column being displayed.
		 * @param integer $group_id ID of Group (post) being displayed.
		 */
		public function display_groups_columns( $column_name = '', $group_id = 0 ) {
			switch ( $column_name ) {

				case 'groups_group_leaders':
					$group_leaders = learndash_get_groups_administrator_ids( $group_id );
					if ( ( empty( $group_leaders ) ) || ( ! is_array( $group_leaders ) ) ) {
						$group_leaders = array();
					}

					printf(
						// translators: placeholder: Group Leaders Count.
						esc_html_x( 'Total %d', 'Group Leaders Count', 'learndash' ),
						count( $group_leaders )
					);

					if ( ! empty( $group_leaders ) ) {
						$user_names = '';

						if ( count( $group_leaders ) > 5 ) {
							$group_leaders = array_slice( $group_leaders, 0, 5 );
						}

						foreach ( $group_leaders as $user_id ) {
							$user = get_user_by( 'id', $user_id );
							if ( ! empty( $user_names ) ) {
								$user_names .= ', ';
							}
							$user_names .= '<a href="' . get_edit_user_link( $user_id ) . '">' . $user->display_name . ' (' . $user->user_login . ')' . '</a>';
						}

						if ( ! empty( $user_names ) ) {
							echo '<br />' . $user_names;
						}
					}
					break;

				case 'groups_group_users':
					$group_users = learndash_get_groups_user_ids( $group_id );
					if ( ( empty( $group_users ) ) || ( ! is_array( $group_users ) ) ) {
						$group_users = array();
					}

					echo sprintf(
						// translators: placeholder: Group Users Count.
						esc_html_x( 'Total %d', 'Group Users Count', 'learndash' ),
						count( $group_users )
					);

					if ( ! empty( $group_users ) ) {
						$user_names = '';

						if ( count( $group_users ) > 5 ) {
							$group_users = array_slice( $group_users, 0, 5 );
						}

						foreach ( $group_users as $user_id ) {
							$user = get_user_by( 'id', $user_id );
							if ( ! empty( $user_names ) ) {
								$user_names .= ', ';
							}
							$user_names .= '<a href="' . get_edit_user_link( $user_id ) . '">' . $user->display_name . ' (' . $user->user_login . ')' . '</a>';
						}

						if ( ! empty( $user_names ) ) {
							echo '<br />' . $user_names;
						}
					}
					break;

				case 'groups_group_courses':
					$group_courses = learndash_group_enrolled_courses( $group_id );
					if ( ( empty( $group_courses ) ) || ( ! is_array( $group_courses ) ) ) {
						$group_courses = array();
					}

					echo sprintf(
						// translators: placeholder: Goup Courses Count.
						esc_html_x( 'Total %d', 'Group Courses Count', 'learndash' ),
						count( $group_courses )
					);

					if ( ! empty( $group_courses ) ) {

						$course_names = '';
						if ( count( $group_courses ) > 5 ) {
							$group_courses = array_slice( $group_courses, 0, 5 );
						}

						foreach ( $group_courses as $course_id ) {

							if ( ! empty( $course_names ) ) {
								$course_names .= ', ';
							}
							$course_names .= '<a href="' . get_edit_post_link( $course_id ) . '">' . get_the_title( $course_id ) . '</a>';
						}

						if ( ! empty( $course_names ) ) {
							echo '<br />' . $course_names;
						}
					}
					break;
			}
		}

		// End of functions.
	}
}
