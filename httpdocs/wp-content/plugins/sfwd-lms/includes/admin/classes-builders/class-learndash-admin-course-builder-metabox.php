<?php
/**
 * LearnDash Course Builder Metabox Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ( ! class_exists( 'Learndash_Admin_Metabox_Course_Builder' ) ) && ( class_exists( 'Learndash_Admin_Builder' ) ) ) {
	/**
	 * Class for LearnDash Course Builder.
	 */
	class Learndash_Admin_Metabox_Course_Builder extends Learndash_Admin_Builder {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->builder_post_type = 'sfwd-courses';
			$this->selector_post_types = array(
				learndash_get_post_type_slug( 'lesson' ),
				learndash_get_post_type_slug( 'topic' ),
				learndash_get_post_type_slug( 'quiz' ),
			);
			$this->builder_init();
			parent::__construct();
		}

		/**
		 * Iniitialize builder for specific Course Item.
		 *
		 * @since 2.6.0
		 * @param integer $post_id Post ID to load.
		 */
		public function builder_init( $post_id = 0 ) {
			if ( ! empty( $post_id ) ) {
				$this->builder_post_id = intval( $post_id );
				$this->ld_course_steps_object = LDLMS_Factory_Post::course_steps( $this->builder_post_id );
			}
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 */
		public function builder_on_load() {
			parent::builder_on_load();
		}

		/**
		 * Prints content for Course Builder meta box for admin
		 * This function is called from other add_meta_box functions
		 *
		 * @since 2.5
		 *
		 * @param object $post WP_Post.
		 */
		public function show_builder_box( $post ) {
			if ( ( is_a( $post, 'WP_Post' ) ) && ( $this->builder_post_type === $post->post_type ) ) {
				$this->builder_init( $post->ID );
				parent::show_builder_box( $post );
				?>
				<style>
					#learndash_builder_box_wrap .learndash_selectors #learndash-selector-post-listing-sfwd-lessons:empty::after {
						content: "<?php echo sprintf(
							// translators: placeholder: Lesson.
							_x( 'Click the \'+\' to add a new %s', 'placeholder: Lesson', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'Lesson' )
						); ?>";
					}
					#learndash_builder_box_wrap .learndash_selectors #learndash-selector-post-listing-sfwd-topic:empty::after {
						content: "<?php echo sprintf(
							// translators: placeholder: Topic.
							_x( 'Click the \'+\' to add a new %s', 'placeholder: Topic', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'Topic' )
						); ?>";
					}
					#learndash_builder_box_wrap .learndash_selectors #learndash-selector-post-listing-sfwd-quiz:empty::after {
						content: "<?php echo sprintf(
							// translators: placeholder: Quiz.
							_x( 'Click the \'+\' to add a new %s', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'Quiz' )
						); ?>";
					}

					#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-lesson-items:empty:after {
						content: "<?php echo sprintf(
							// translators: placeholder: Lessons.
							esc_html_x( 'Drop %s Here', 'placeholder: Lessons', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lessons' )
						); ?>";
					}
					#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-topic-items:empty:after {
						content: "<?php echo sprintf(
							// translators: placeholder: Lesson, Topics.
							esc_html_x( 'Drop %1$s %2$s Here', 'placeholder: Lesson, Topics', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lesson' ),
							LearnDash_Custom_Label::get_label( 'topics' )
						); ?>";
					}
					#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-quiz-items:empty:after {
						content: "<?php echo sprintf(
							// translators: placeholder: Quizzes.
							esc_html_x( 'Drop Global %s Here', 'placeholder: Quizzes', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quizzes' )
						); ?>";
					}

					#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-lesson-items .ld-course-builder-quiz-items:empty:after {
						content: "<?php echo sprintf(
							// translators: placeholder: Lesson, Quizzes.
							esc_html_x( 'Drop %1$s %2$s Here', 'placeholder: Lesson, Quizzes', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'quizzes' )
						); ?>";
					}
					#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-lesson-items .ld-course-builder-topic-items .ld-course-builder-quiz-items:empty:after {
						content: "<?php echo sprintf(
							// translators: placeholder: Topic, Quizzes.
							esc_html_x( 'Drop %1$s %2$s Here', 'placeholder: Topic, Quizzes', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topic' ), LearnDash_Custom_Label::get_label( 'quizzes' )
						); ?>";
					}
				</style>
				<?php
			}
		}

		/**
		 * Get the selected items for a post type.
		 *
		 * @since 2.6.0
		 * @param string $selector_post_type Post Type is selector being processed.
		 * @return array Selector post IDs.
		 */
		public function get_selector_selected_steps( $selector_post_type = '' ) {
			$selector_post_type_steps = array();
			if ( ! empty( $selector_post_type ) ) {
				$course_steps = $this->ld_course_steps_object->get_steps( 't' );
				if ( ( isset( $course_steps[ $selector_post_type ] ) ) && ( !empty( $course_steps[ $selector_post_type ] ) ) ) {
					$selector_post_type_steps = $course_steps[ $selector_post_type ];
				}
			}
			return $selector_post_type_steps;
		}

		/**
		 * Get the number of current items in the builder.
		 */
		public function get_build_items_count() {
			?>
			<span class="learndash_builder_items_total">
			<?php
				printf(
					// translators: placeholder: number of steps.
					esc_html_x( 'Total Steps: %s', 'placeholder: number of steps', 'learndash' ),
					'<span class="learndash_builder_items_total_value">' . intval( $this->ld_course_steps_object->get_steps_count() ) . '</span>'
				);
			?>
			</span>
			<?php
		}

		/**
		 * Call via the WordPress admin_footer action hook.
		 */
		public function builder_admin_footer() {
			$builder_post_type_label = $this->get_label_for_post_type( $this->builder_post_type );

			$this->builder_assets[ $this->builder_post_type ]['messages']['learndash_unload_message'] = sprintf(
				// translators: placeholder: Course.
				esc_html_x( 'You have unsaved %s Builder changes. Are you sure you want to leave?', 'placeholder: Course' ),
				LearnDash_Custom_Label::get_label( $builder_post_type_label )
			);

			foreach ( $this->selector_post_types as $selector_post_type ) {
				$post_type_object = get_post_type_object( $selector_post_type );
				if ( is_a( $post_type_object, 'WP_Post_Type' ) ) {

					$this->builder_assets[ $this->builder_post_type ]['messages'][ 'confirm_remove_' . $selector_post_type ] = sprintf(
						// translators: 'placeholders: will be post type labels like Course, Lesson, Topic'.
						esc_html_x( 'Are you sure you want to remove this %1$s from the %2$s? (This will also remove all sub-items)', 'placeholders: will be post type labels like Course, Lesson, Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( $this->get_label_for_post_type( $selector_post_type ) ), LearnDash_Custom_Label::get_label( $builder_post_type_label )
					);

					$this->builder_assets[ $this->builder_post_type ]['messages'][ 'confirm_trash_' . $selector_post_type ] = sprintf(
						// translators: placeholder: will be post type label like Course, Lesson, Topic.
						esc_html_x( 'Are you sure you want to move this %s to Trash?', 'placeholder: will be post type label like Course, Lesson, Topic', 'learndash' ), LearnDash_Custom_Label::get_label( $this->get_label_for_post_type( $selector_post_type ) )
					);
				}
			}

			parent::builder_admin_footer();
		}

		/**
		 * Utility function to get the label for Post Type.
		 *
		 * @since 2.5.0
		 *
		 * @param string  $post_type Post Type slug.
		 * @param boolean $singular True if singular label needed. False for plural.
		 * @return string.
		 */
		public function get_label_for_post_type( $post_type = '', $singular = true ) {
			switch ( $post_type ) {
				case 'sfwd-courses':
					if ( true === $singular ) {
						return 'course';
					} else {
						return 'courses';
					}
					break;

				case 'sfwd-lessons':
					if ( true === $singular ) {
						return 'lesson';
					} else {
						return 'lessons';
					}
					break;

				case 'sfwd-topic':
					if ( true === $singular ) {
						return 'topic';
					} else {
						return 'topics';
					}
					break;

				case 'sfwd-quiz':
					if ( true === $singular ) {
						return 'quiz';
					} else {
						return 'quizzes';
					}
					break;
			}
		}

		/** Utility function to build the selector query args array.
		 *
		 * @since 2.5.0
		 *
		 * @param array $args Array of query args.
		 * @return array
		 */
		public function build_selector_query( $args = array() ) {
			$per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'per_page' );
			if ( empty( $per_page ) ) {
				$per_page = 10;
			}

			$defaults = array(
				'post_status'    => array( 'publish' ),
				'posts_per_page' => $per_page,
				'paged'          => 1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			);

			$args = wp_parse_args( $args, $defaults );

			/**
			 * If we are not sharing steps then we limit the query results to only show items associated with the course or items
			 * not associated with any course.
			 */
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) !== 'yes' ) {

				$m_include_ids = array();
				$m_args        = array( 'posts_per_page' => -1 );

				if ( isset( $args['post_type'] ) ) {
					$m_args['post_type'] = $args['post_type'];
				}
				if ( isset( $args['post_status'] ) ) {
					$m_args['post_status'] = $args['post_status'];
				} else {
					$m_args['post_status'] = array( 'public' );
				}
				$m_args['fields'] = 'ids';

				if ( ( isset( $args['post__not_in'] ) ) && ( ! empty( $args['post__not_in'] ) ) ) {
					$m_args['post__not_in'] = $args['post__not_in'];
					unset( $args['post__not_in'] );
				}

				// First get all the items related to the course ID or if course_id is present but zero.
				$m_args['meta_query'] = array(
					array(
						'key'     => 'course_id',
						'value'   => $this->builder_post_id,
						'compare' => '=',
					),
				);

				$m_post_type_query = new WP_Query( $m_args );
				if ( ( property_exists( $m_post_type_query, 'posts' ) ) && ( ! empty( $m_post_type_query->posts ) ) ) {
					$m_include_ids = array_merge( $m_include_ids, $m_post_type_query->posts );
					if ( ! isset( $m_args['post__not_in'] ) ) {
						$m_args['post__not_in'] = array();
					} 
					$m_args['post__not_in'] = array_merge( $m_args['post__not_in'], $m_include_ids );
				}

				//if ( isset( $m_args['post__not_in'] ) ) {
				//	unset( $m_args['post__not_in'] );
				//}

				/**
				 * Allow externals to control inclusion of orphaned steps.
				 * Orphaned steps are those not attached to a course.
				 *
				 * @since 2.5.9
				 *
				 * @param boolean true The default value is true to include orphaned steps.
				 * @param array $args The current query args array.
				 *
				 * @return the external filters should return:
				 *  true  - Yes include orphaned steps.
				 *  false - No do not inclide orphaned steps.
				 */
				$include_orphaned_steps = apply_filters( 'learndash_course_builder_include_orphaned_steps', true, $args );
				if ( true === $include_orphaned_steps ) {
					$m_args['meta_query'] = array(
						'relation' => 'OR',
						array(
							'key'     => 'course_id',
							'value'   => 0,
							'compare' => '=',
						),
						array(
							'key'     => 'course_id',
							'value'   => -1,
							'compare' => '=',
						),
					);

					$m_post_type_query = new WP_Query( $m_args );
					if ( ( property_exists( $m_post_type_query, 'posts' ) ) && ( ! empty( $m_post_type_query->posts ) ) ) {
						$m_include_ids = array_merge( $m_include_ids, $m_post_type_query->posts );
						if ( ! isset( $m_args['post__not_in'] ) ) {
							$m_args['post__not_in'] = array();
						}
						$m_args['post__not_in'] = array_merge( $m_args['post__not_in'], $m_include_ids );
					}

					$m_args['meta_query'] = array(
						array(
							'key'     => 'course_id',
							'compare' => 'NOT EXISTS',
						),
					);
					$m_post_type_query = new WP_Query( $m_args );
					if ( ( property_exists( $m_post_type_query, 'posts' ) ) && ( ! empty( $m_post_type_query->posts ) ) ) {
						$m_include_ids = array_merge( $m_include_ids, $m_post_type_query->posts );
						if ( ! isset( $m_args['post__not_in'] ) ) {
							$m_args['post__not_in'] = array();
						}
						$m_args['post__not_in'] = array_merge( $m_args['post__not_in'], $m_include_ids );
					}
				}

				if ( ! empty( $m_include_ids ) ) {
					$args['post__in'] = $m_include_ids;
				} else {
					$args['post__in'] = array( 0 );
				}
			}
			return apply_filters( 'learndash_course_builder_selector_args', $args );
		}

		/**
		 * Common function to show Selector pager buttons.
		 *
		 * @since 2.5.0
		 * @param object $post_type_query WP_Query instance.
		 * @return string Button(s) HTML.
		 */
		public function build_selector_pages_buttons( $post_type_query ) {
			$pager_buttons = '';

			if ( $post_type_query instanceof WP_Query ) {
				$first_page = 1;

				$current_page = intval( $post_type_query->query['paged'] );
				$last_page = intval( $post_type_query->max_num_pages );
				if ( empty( $last_page ) ) {
					$last_page = 1;
				}

				if ( $current_page <= 1 ) {
					$prev_page = 1;
					$prev_disabled = ' disabled="disabled" ';
				} else {
					$prev_page = $current_page - 1;
					$prev_disabled = '';
				}

				if ( $current_page >= $last_page ) {
					$next_page = $last_page;
					$next_disabled = ' disabled="disabled" ';
				} else {
					$next_page = $current_page + 1;
					$next_disabled = '';
				}

				$pager_buttons .= '<button ' . $prev_disabled . ' class="button button-simple first" data-page="' . $first_page . '" title="' . esc_attr__( 'First Page', 'learndash' ) . '">&laquo;</button>';
				$pager_buttons .= '<button ' . $prev_disabled . ' class="button button-simple prev" data-page="' . $prev_page . '" title="' . esc_attr__( 'Previous Page', 'learndash' ) . '">&lsaquo;</button>';
				$pager_buttons .= '<span><span class="pagedisplay"><span class="current_page">' . $current_page . '</span> / <span class="total_pages">' . $last_page . '</span></span></span>';
				$pager_buttons .= '<button ' . $next_disabled . ' class="button button-simple next" data-page="' . $next_page . '" title="' . esc_attr__( 'Next Page', 'learndash' ) . '">&rsaquo;</button>';
				$pager_buttons .= '<button ' . $next_disabled . ' class="button button-simple last" data-page="' . $last_page . '" title="' . esc_attr__( 'Last Page', 'learndash' ) . '" >&raquo;</button>';
			}

			return $pager_buttons;
		}

		/**
		 * Common function to show Selector pager buttons.
		 *
		 * @since 2.5.0
		 * @param object $post_type_query WP_Query instance.
		 * @return string Button(s) HTML.
		 */
		public function build_selector_pages_buttons_json( $post_type_query ) {
			$pager_buttons = [
				'first_page' => 1,
				'last_page' => 1,
				'prev_page' => null,
				'prev_disabled' => false,
				'next_page' => null,
				'next_disabled' => false,
				'current_page' => null,
			];

			if ( $post_type_query instanceof WP_Query ) {
				$pager_buttons['first_page'] = 1;

				$current_page = intval( $post_type_query->query['paged'] );
				$last_page = intval( $post_type_query->max_num_pages );

				$pager_buttons['current_page'] = $current_page;
				if ( empty( $last_page ) ) {
					$pager_buttons['last_page'] = 1;
				}

				if ( $current_page <= 1 ) {
					$pager_buttons['prev_page'] = 1;
					$pager_buttons['has_prev'] = false;
				} else {
					$pager_buttons['prev_page'] = $current_page - 1;
					$pager_buttons['has_prev'] = true;
				}

				if ( $current_page >= $last_page ) {
					$pager_buttons['next_page'] = $last_page;
					$pager_buttons['has_next'] = false;
				} else {
					$pager_buttons['next_page'] = $current_page + 1;
					$pager_buttons['has_next'] = true;
				}
			}

			return $pager_buttons;
		}

		/**
		 * Show selector rows.
		 *
		 * @since 2.5.0
		 * @param object $post_type_query WP_Query instance.
		 */
		public function build_selector_rows( $post_type_query ) {
			$selector_rows = '';

			if ( $post_type_query instanceof WP_Query ) {
				$selector_post_type = $post_type_query->query['post_type'];
				$selector_post_type_object = get_post_type_object( $selector_post_type );

				$selector_label = $selector_post_type_object->label;
				$selector_slug = $this->get_label_for_post_type( $selector_post_type );

				foreach ( $post_type_query->posts as $p ) {
					$selector_rows .= $this->build_selector_row_single( $p, $selector_post_type );
				}
			}

			return $selector_rows;
		}

		/**
		 * Show selector rows.
		 *
		 * @since 2.5.0
		 * @param object $post_type_query WP_Query instance.
		 */
		public function build_selector_rows_json( $post_type_query ) {
			$selector_rows = [];

			if ( $post_type_query instanceof WP_Query ) {
				$selector_post_type = $post_type_query->query['post_type'];

				foreach ( $post_type_query->posts as $p ) {
					$selector_rows[] = [ 'ID' => $p->ID, 'post_title' => get_the_title( $p->ID ), 'type' => $selector_post_type, 'edit_link' => get_edit_post_link( $p->ID, '' ) ];
				}
			}

			return $selector_rows;
		}

		/**
		 * Show selector single row.
		 *
		 * @since 2.5.0
		 * @param object $p WP_Post object to show.
		 * @param string $selector_post_type Post type slug.
		 * @return string Row HTML.
		 */
		protected function build_selector_row_single( $p = null, $selector_post_type = '' ) {
			$selector_row = '';

			if ( empty( $selector_post_type ) ) {
				return $selector_row;
			}

			$selector_post_type_object = get_post_type_object( $selector_post_type );

			$selector_label = $selector_post_type_object->label;
			$selector_slug = $this->get_label_for_post_type( $selector_post_type );

			$selector_sub_actions = '';

			$p_id           = '';
			$p_title        = '';
			$edit_post_link = '';
			$view_post_link = '';

			if ( $p ) {
				$p_id = $p->ID;
				$p_title = get_the_title( $p->ID );
				//$view_post_link = learndash_get_step_permalink( $p->ID, $this->builder_post_id );

				/**
				 * We add this to force the course_id to zero for the selectors as we don't
				 * want the the 'view' URL to reflect the nested course.
				 */
				add_filter( 'learndash_post_link_course_id', function( $course_id ) {
					return 0;
				} );

				$view_post_link = get_permalink( $p->ID );
				if ( current_user_can( 'edit_courses' ) ) {
					$edit_post_link = get_edit_post_link( $p->ID );
					$edit_post_link = remove_query_arg( 'course_id', $edit_post_link );
				}
			} else {
				// We need a unique ID.
				$p_id = $selector_post_type . '-placeholder';
				$p_title = $selector_post_type_object->labels->singular_name;
			}

			$selector_sub_actions .= '<a target="_blank" class="ld-course-builder-action ld-course-builder-action-edit ld-course-builder-action-' . $selector_slug . '-edit dashicons" href="' . $edit_post_link . '"><span class="screen-reader-text">' . sprintf(
				// translators: placeholder: will contain post type label.
				esc_html_x( 'Edit %s Settings (new window)', 'placeholder: will contain post type label', 'learndash' ),
				LearnDash_Custom_Label::get_label( $selector_slug )
			) . '</span></a>';

			$selector_sub_actions .= '<a target="_blank" class="ld-course-builder-action ld-course-builder-action-view ld-course-builder-action-' . $selector_slug . '-view dashicons" href="' . $view_post_link . '"><span class="screen-reader-text">' . sprintf(
				// translators: placeholder: will contain post type label.
				esc_html_x( 'View %s (new window)', 'placeholder: will contain post type label', 'learndash' ),
				LearnDash_Custom_Label::get_label( $selector_slug )
			) . '</span></a>';

			if ( current_user_can( 'delete_courses' ) ) {

				$selector_sub_actions .= '<span class="ld-course-builder-action ld-course-builder-action-trash ld-course-builder-action-' . $selector_slug . '-trash dashicons" title="' . sprintf(
					// translators: placeholder: will contain post type label.
					esc_html_x( 'Move %s to Trash', 'placeholder: will contain post type label', 'learndash' ),
					LearnDash_Custom_Label::get_label( $selector_slug )
				) . '"></span>';
			}
			$selector_sub_actions .= '<span class="ld-course-builder-action ld-course-builder-action-remove ld-course-builder-action-' . $selector_slug . '-remove dashicons" title="' . sprintf(
				// translators: placeholders: will contain post type label, Course.
				esc_html_x( 'Remove %1$s from %2$s', 'placeholders: will contain post type label, Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( $selector_slug ), LearnDash_Custom_Label::get_label( 'Course' )
			) . '"></span>';

			$selector_sub_items	= '';
			$selector_action_expand = '';
			if ( 'sfwd-lessons' === $selector_post_type ) {
				$selector_sub_items .= '<div class="ld-course-builder-topic-items ld-course-builder-lesson-topic-items"></div>';
				$selector_sub_items .= '<div class="ld-course-builder-quiz-items ld-course-builder-lesson-quiz-items"></div>';

				$selector_action_expand = '<span class="ld-course-builder-action ld-course-builder-action-show-hide ld-course-builder-action-show ld-course-builder-action-' . $selector_slug . '-show dashicons" title="' . esc_html__( 'Expand/Collape Section', 'learndash' ) . '"></span>';

			} elseif ( 'sfwd-topic' === $selector_post_type ) {
				$selector_sub_items    .= '<div class="ld-course-builder-quiz-items ld-course-builder-topic-quiz-items"></div>';
				$selector_action_expand = '<span class="ld-course-builder-action ld-course-builder-action-show-hide ld-course-builder-action-show ld-course-builder-action-' . $selector_slug . '-show dashicons" title="' . esc_html__( 'Expand/Collape Section', 'learndash' ) . '"></span>';

			} elseif ( 'sfwd-quiz' === $selector_post_type ) {
				// Nothing here.
			}

			$selector_row .= '<li id="ld-post-' . $p_id . '" class="ld-course-builder-item ld-course-builder-' . $selector_slug . '-item " data-ld-type="' . $selector_post_type . '" data-ld-id="' . $p_id . '">
				<div class="ld-course-builder-' . $selector_slug . '-header ld-course-builder-header">
					<span class="ld-course-builder-actions">
						<span class="ld-course-builder-action ld-course-builder-action-move ld-course-builder-action-' . $selector_slug . '-move dashicons" title="' . sprintf(
				// translators: placeholder: will contain post type label.
				esc_html_x( 'Move %s', 'placeholder: will contain post type label', 'learndash' ),
				LearnDash_Custom_Label::get_label( $selector_slug )
			) . '"></span>
						<span class="ld-course-builder-sub-actions">' . $selector_sub_actions . '</span>
					</span>
					<span class="ld-course-builder-title"><span class="ld-course-builder-title-text">' . $p_title . '</span>
						<span class="ld-course-builder-action ld-course-builder-edit-title-pencil dashicons" title="' . esc_html__( 'Edit Title', 'learndash' ) . '" ></span>
						<span class="ld-course-builder-action ld-course-builder-edit-title-ok dashicons" title="' . esc_html__( 'Ok', 'learndash' ) . '" ></span>
						<span class="ld-course-builder-action ld-course-builder-edit-title-cancel dashicons" title="' . esc_html__( 'Cancel', 'learndash' ) . '" ></span>
					</span>
					' . $selector_action_expand . '
				</div>
				<div class="ld-course-builder-sub-items" style="display:none">' . $selector_sub_items . '</div>
				</li>';

			return $selector_row;
		}

		/**
		 * Build Course Steps HTML.
		 */
		public function build_course_steps_html() {
			$steps_html = '';

			$course_steps = $this->ld_course_steps_object->get_steps();

			//if ( ! empty( $course_steps ) ) {
				$steps_html .= $this->process_course_steps( $course_steps );
			//}
			return $steps_html;
		}

		/**
		 * Build course steps HTML.
		 *
		 * @since 2.5.0
		 * @param array  $steps Array of current course steps.
		 * @param string $steps_parent_type Parent post type slug. Default is 'sfwd-courses'.
		 * @return string Steps HTML.
		 */
		protected function process_course_steps( $steps = array(), $steps_parent_type = 'sfwd-courses' ) {
			$steps_section_html = '';

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_items ) {
					$steps_section_items_html = '';
					if ( ! empty( $steps_items ) ) {
						foreach ( $steps_items as $steps_id => $steps_set ) {
							$steps_section_item_html = $this->process_course_steps( $steps_set, $steps_type );
							$edit_post_link = get_edit_post_link( $steps_id );
							$edit_post_link = add_query_arg( 'course_id', $this->builder_post_id, $edit_post_link );

							$view_post_link = learndash_get_step_permalink( $steps_id, $this->builder_post_id );

							if ( $steps_type == 'sfwd-lessons' ) {
								$steps_section_item_html = '<div id="ld-post-' . $steps_id . '" class="ld-course-builder-item ld-course-builder-lesson-item" data-ld-type="' . $steps_type . '" data-ld-id="' . $steps_id . '">
									<div class="ld-course-builder-lesson-header ld-course-builder-header">
										<span class="ld-course-builder-actions">
											<span class="ld-course-builder-action ld-course-builder-action-move ld-course-builder-action-lesson-move dashicons" title="' .
											// translators: placeholder: Lesson.
											sprintf( esc_html_x( 'Move %s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'Lesson' ) ) . '"></span>
											<span class="ld-course-builder-sub-actions">
												<a target="_blank" class="ld-course-builder-action ld-course-builder-action-edit ld-course-builder-action-lesson-edit dashicons" href="' . $edit_post_link . '"><span class="screen-reader-text">' .
												// translators: placeholder: Lesson.
												sprintf( esc_html_x( 'Edit %s Settings (new window)', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'Lesson' ) ) . '</span></a>

												<a target="_blank" class="ld-course-builder-action ld-course-builder-action-view ld-course-builder-action-lesson-view dashicons" href="' . $view_post_link . '"><span class="screen-reader-text">' .
												// translators: placeholder: Lesson.
												sprintf( esc_html_x( 'View %s (new window)', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'Lesson' ) ) . '"</span></a>

												<span class="ld-course-builder-action ld-course-builder-action-remove ld-course-builder-action-lesson-remove dashicons" title="' .
												// translators: placeholders: Lesson, Course.
												sprintf( esc_html_x( 'Remove %1$s from %2$s', 'placeholders: Lesson, Course', 'learndash' ), LearnDash_Custom_Label::get_label('Lesson'), LearnDash_Custom_Label::get_label( 'Course' ) ) . '"></span>
											</span>
										</span>
										<span class="ld-course-builder-title"><span class="ld-course-builder-title-text">' . get_the_title( $steps_id ) . '</span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-pencil dashicons" title="' . esc_html__( 'Edit Title', 'learndash' ) . '" ></span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-ok dashicons" title="'. esc_html__( 'Ok', 'learndash' ) . '" ></span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-cancel dashicons" title="' . esc_html__( 'Cancel', 'learndash' ) . '" ></span>
										</span>

										<span class="ld-course-builder-action ld-course-builder-action-show-hide ld-course-builder-action-show ld-course-builder-action-lesson-show dashicons" title="' . esc_html__( 'Expand/Collape Section', 'learndash' ) . '"></span>

									</div>
									<div class="ld-course-builder-sub-items" style="display:none">' . $steps_section_item_html . '</div>
								</div>';
							} else if ( 'sfwd-topic' === $steps_type ) {
								$steps_section_item_html = '<div id="ld-post-' . $steps_id . '" class="ld-course-builder-item ld-course-builder-topic-item" data-ld-type="' . $steps_type . '" data-ld-id="' . $steps_id . '">
									<div class="ld-course-builder-topic-header ld-course-builder-header">
										<span class="ld-course-builder-actions">
											<span class="ld-course-builder-action ld-course-builder-action-move ld-course-builder-action-topic-move dashicons" title="' . esc_html__( 'Move', 'learndash' ) . '"></span>
											<span class="ld-course-builder-sub-actions">
												<a target="_blank" class="ld-course-builder-action ld-course-builder-action-edit ld-course-builder-action-topic-edit dashicons" href="' . $edit_post_link . '"><span class="screen-reader-text">' .
												// translators: placeholder: Topic.
												sprintf( esc_html_x( 'Edit %s Settings (new window)', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'Topic' ) ) . '" ></span></a>
												<a target="_blank" class="ld-course-builder-action ld-course-builder-action-view ld-course-builder-action-topic-edit dashicons" href="' . $view_post_link . '"><span class="screen-reader-text">' .
												// translators: placeholder: Topic.
												sprintf( esc_html_x( 'View %s (new window)', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'Topic' ) ) . '</span></a>
												<span class="ld-course-builder-action ld-course-builder-action-remove ld-course-builder-action-topic-remove dashicons" title="' .
												// translators: placeholders: Lesson, Course.
												sprintf( esc_html_x( 'Remove %1$s from %2$s', 'placeholders: Lesson, Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'Topic' ), LearnDash_Custom_Label::get_label( 'Course' ) ) . '"></span>
											</span>
										</span>
										<span class="ld-course-builder-title"><span class="ld-course-builder-title-text">' . get_the_title( $steps_id ) . '</span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-pencil dashicons" title="' . esc_html__( 'Edit Title', 'learndash' ) . '" ></span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-ok dashicons" title="' . esc_html__( 'Ok', 'learndash' ) . '" ></span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-cancel dashicons" title="' . esc_html__( 'Cancel', 'learndash' ) . '" ></span>
										</span>

										<span class="ld-course-builder-action ld-course-builder-action-show-hide ld-course-builder-action-show ld-course-builder-action-topic-show dashicons" title="' . esc_html__( 'Expand/Collape Section', 'learndash' ) . '"></span>
									</div>
									<div class="ld-course-builder-sub-items" style="display:none">' . $steps_section_item_html . '</div>
								</div>';
							} else if ( 'sfwd-quiz' === $steps_type ) {
								$steps_section_item_html = '<div id="ld-post-' . $steps_id . '" class="ld-course-builder-item ld-course-builder-quiz-item" data-ld-type="' . $steps_type . '" data-ld-id="' . $steps_id . '">
									<div class="ld-course-builder-quiz-header ld-course-builder-header">
										<span class="ld-course-builder-actions">
											<span class="ld-course-builder-action ld-course-builder-action-move ld-course-builder-action-quiz-move dashicons" title="' . esc_html__( 'Move', 'learndash' ) . '"></span>
											<span class="ld-course-builder-sub-actions">
												<a target="_blank" class="ld-course-builder-action ld-course-builder-action-edit ld-course-builder-action-quiz-edit dashicons" href="' . $edit_post_link . '"><span class="screen-reader-text">' .
												// translators: placeholder: placeholder: Topic.
												sprintf( esc_html_x( 'Edit %s Settings (new window)', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quiz' ) ) . '" ></span></a>
												<a target="_blank" class="ld-course-builder-action ld-course-builder-action-view ld-course-builder-action-quiz-view dashicons" href="' . $view_post_link . '"><span class="screen-reader-text" >' .
												// translators: placeholder: placeholder: Quiz.
												sprintf( esc_html_x( 'View %s (new window)', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quiz' ) ) . '"></span></a>
												<span class="ld-course-builder-action ld-course-builder-action-remove ld-course-builder-action-quiz-remove dashicons" title="' .
												// translators: placeholders: Lesson, Course.
												sprintf( esc_html_x( 'Remove %1$s from %2$s', 'placeholders: Lesson, Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quiz' ), LearnDash_Custom_Label::get_label( 'Course' ) ) . '"></span>
											</span>
										</span>
										<span class="ld-course-builder-title"><span class="ld-course-builder-title-text">' . get_the_title( $steps_id ) . '</span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-pencil dashicons" title="' . esc_html__( 'Edit Title', 'learndash' ) . '" ></span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-ok dashicons" title="' . esc_html__( 'Ok', 'learndash' ) . '" ></span>
											<span class="ld-course-builder-action ld-course-builder-edit-title-cancel dashicons" title="' . esc_html__( 'Cancel', 'learndash' ) . '" ></span>
										</span>
									</div>
									<div class="ld-course-builder-sub-items"  style="display:none">' . $steps_section_item_html . '</div>
								</div>';
							}

							$steps_section_items_html .= $steps_section_item_html;
						}
					}

					if ( 'sfwd-courses' === $steps_parent_type ) {
						if ( 'sfwd-lessons' === $steps_type ) {
							$steps_section_html = '<div class="ld-course-builder-lesson-items">' . $steps_section_items_html . '</div>';
						} elseif ( 'sfwd-quiz' === $steps_type ) {
							$steps_section_html .= '<div class="ld-course-builder-quiz-items ld-course-builder-course-quiz-items">' . $steps_section_items_html . '</div>';
						}
					} else if ( 'sfwd-lessons' === $steps_parent_type ) {
						if ( $steps_type == 'sfwd-topic' ) {
							$steps_section_html = '<div class="ld-course-builder-topic-items ld-course-builder-lesson-topic-items">' . $steps_section_items_html . '</div>';

						} else if ( 'sfwd-quiz' === $steps_type ) {
							$steps_section_html .= '<div class="ld-course-builder-quiz-items ld-course-builder-lesson-quiz-items">' . $steps_section_items_html . '</div>';
						}
					} else if ( 'sfwd-topic' === $steps_parent_type ) {
						if ( 'sfwd-quiz' === $steps_type ) {
							$steps_section_html = '<div class="ld-course-builder-quiz-items ld-course-builder-topic-quiz-items">' . $steps_section_items_html . '</div>';
						}
					}
				}
			} else {
				if ( 'sfwd-courses' === $steps_parent_type ) {
					$steps_section_html .= '<div class="ld-course-builder-lesson-items"></div>';
					$steps_section_html .= '<div class="ld-course-builder-quiz-items"></div>';
				}
			}

			return $steps_section_html;
		}

		/** Save Course Builder steps
		 *
		 * @since 2.5.0
		 * @param integer $post_id Post ID of course being saved.
		 * @param object  $post WP_Post object instance being saved.
		 * @param boolean $update False is an update. True if new post.
		 */
		public function save_course_builder( $post_id, $post, $update ) {
			$return_status = false;

			$cb_nonce_key = $this->builder_prefix . '_nonce';
			$cb_nonce_value = $this->builder_prefix . '_' . $post->post_type . '_' . $post_id . '_nonce';

			if ( ( isset( $_POST[ $cb_nonce_key ] ) ) && ( wp_verify_nonce( $_POST[ $cb_nonce_key ], $cb_nonce_value ) ) ) {
				if ( isset( $_POST[ $this->builder_prefix ][ $this->builder_post_type ][ $post_id ] ) ) {
					$course_builder_data = $_POST[ $this->builder_prefix ][ $this->builder_post_type ][ $post_id ];

					if ( '' !== $course_builder_data ) {
						$this->ld_course_steps_object = LDLMS_Factory_Post::course_steps( $post_id );

						$course_steps = (array) json_decode( stripslashes( $course_builder_data ), true );

						if ( ( is_array( $course_steps ) ) && ( ! empty( $course_steps ) ) ) {
							$course_steps_split = LDLMS_Course_Steps::steps_split_keys( $course_steps );
						} else {
							$course_steps_split = array();
						}
						$this->ld_course_steps_object->set_steps( $course_steps_split );
						$return_status = true;
					}
				}
			}

			return $return_status;
		}

		/**
		 * Handle AJAX pager requests.
		 *
		 * @param array $query_args array of values for AJAX request.
		 */
		public function learndash_builder_selector_pager( $query_args = array() ) {

			$reply_data = array();

			if ( isset( $query_args['format'] ) && 'json' === $query_args['format'] ) {
				$reply_data['selector_pager'] = [];
				$reply_data['selector_rows'] = [];
			}
			else {
				$reply_data['selector_pager'] = '';
				$reply_data['selector_rows'] = '';
			}

			if ( ! empty( $query_args ) ) {
				$post_type_query_args = $this->build_selector_query( $query_args );
				if ( ! empty( $post_type_query_args ) ) {
					$post_type_query = new WP_Query( $post_type_query_args );
					if ( $post_type_query->have_posts() ) {

						if ( isset( $query_args['format'] ) && 'json' === $query_args['format'] ) {
							$reply_data['selector_pager'] = $this->build_selector_pages_buttons_json( $post_type_query );
							$reply_data['selector_rows'] = $this->build_selector_rows_json( $post_type_query );
						}
						else {
							$reply_data['selector_pager'] = $this->build_selector_pages_buttons( $post_type_query );
							$reply_data['selector_rows'] = $this->build_selector_rows( $post_type_query );
						}
					}
				}
			}

			echo json_encode( $reply_data );

			wp_die();
		}

		/**
		 * Handle AJAX search requests.
		 *
		 * @param array $query_args array of values for AJAX request.
		 */
		public function learndash_builder_selector_search( $query_args = array() ) {

			$reply_data = array();

			if ( isset( $query_args['format'] ) && 'json' === $query_args['format'] ) {
				$reply_data['selector_pager'] = [];
				$reply_data['selector_rows'] = [];
			}
			else {
				$reply_data['selector_pager'] = '';
				$reply_data['selector_rows'] = '';
			}

			if ( ! empty( $query_args ) ) {
				$post_type_query_args = $this->build_selector_query( $query_args );
				if ( ! empty( $post_type_query_args ) ) {
					$post_type_query = new WP_Query( $post_type_query_args );
					if ( $post_type_query->have_posts() ) {
						if ( isset( $query_args['format'] ) && 'json' === $query_args['format'] ) {
							$reply_data['selector_pager'] = $this->build_selector_pages_buttons_json( $post_type_query );
							$reply_data['selector_rows'] = $this->build_selector_rows_json( $post_type_query );
						}
						else {
							$reply_data['selector_pager'] = $this->build_selector_pages_buttons( $post_type_query );
							$reply_data['selector_rows'] = $this->build_selector_rows( $post_type_query );
						}
					}
				}
			}

			echo json_encode( $reply_data );

			wp_die();
		}

		/**
		 * Handle AJAX new step requests.
		 *
		 * @param array $query_args array of values for AJAX request.
		 */
		public function learndash_builder_selector_step_new( $query_args = array() ) {
			global $wpdb;

			$reply_data = array();
			$reply_data['new_steps'] = array();

			if ( ( isset( $query_args['new_steps'] ) ) && ( ! empty( $query_args['new_steps'] ) ) ) {
				foreach ( $query_args['new_steps'] as $old_step_id => $step_set ) {
					if ( ( isset( $step_set['post_type'] ) ) && ( ! empty( $step_set['post_type'] ) ) && ( false !== in_array( $step_set['post_type'], array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
						$post_args = array(
							'post_type'    => esc_attr( $step_set['post_type'] ),
							'post_status'  => 'publish',
							'post_title'   => '',
							'post_content' => '',
						);

						if ( ( isset( $step_set['post_title'] ) ) && ( ! empty( $step_set['post_title'] ) ) ) {
							$post_args['post_title'] = $step_set['post_title'];
						} else {
							$post_type_object = get_post_type_object( $step_set['post_type'] );
							if ( $post_type_object ) {
								$post_args['post_title'] = $post_type_object->labels->singular_name;
							}
						}
						$new_step_id = wp_insert_post( apply_filters( 'course_builder_selector_new_step_post_args', $post_args ) );
						if ( $new_step_id ) {
							/**
							 * We have to set the guid manually because the one assigned within wp_insert_post is non-unique. 
							 * See LEARNDASH-3853
							 */ 
							$wpdb->update(
								$wpdb->posts, 
								array( 'guid' => add_query_arg( array( 'post_type' => $step_set['post_type'], 'p' => $new_step_id ), home_url() ) ),  
								array( 'ID' => $new_step_id )
							);

							$reply_data['status'] = true;

							$reply_data['new_steps'][ $old_step_id ] = array();
							$reply_data['new_steps'][ $old_step_id ]['post_id'] = $new_step_id;
							$reply_data['new_steps'][ $old_step_id ]['view_url'] = get_permalink( $new_step_id );
							$reply_data['new_steps'][ $old_step_id ]['edit_url'] = get_edit_post_link( $new_step_id );

							if ( $post_args['post_type'] == 'sfwd-quiz' ) {

								// This form element is required when creating a new Quiz in WPProQuiz. Don't ask.
								//$_POST['form'] = array();
								//$_POST['name'] = $post_args['post_title'];
								//$_POST['text'] = 'AAZZAAZZ';

								//$pro_quiz = new WpProQuiz_Controller_Quiz();
								//ob_start();
								//$pro_quiz->route(
								//	array(
								//		'action'  => 'addEdit',
								//		'quizId'  => 0,
								//		'post_id' => $new_step_id,
								//	)
								//);
								//ob_get_clean();

								//$quiz_id = learndash_get_setting( $new_step_id, 'quiz_pro' );

								//$quiz_meta = SFWD_CPT_Instance::$instances['sfwd-quiz']->get_settings_values( 'sfwd-quiz' );
								//if ( ! empty( $quiz_meta ) ) {
								//	$quiz_meta_values = wp_list_pluck( $quiz_meta, 'value' );
								//	if ( ! empty( $quiz_id ) ) {
								//		$quiz_meta_values['sfwd-quiz_quiz_pro'] = intval( $quiz_id );
								//		//update_post_meta( $new_step_id, 'quiz_pro_id_' . $quiz_id, $quiz_id );
								//		//update_post_meta( $new_step_id, 'quiz_pro_id', $quiz_id );
								//		learndash_update_setting( $new_step_id, 'quiz_pro', $quiz_id );
								//
								//		// Set the 'View Statistics on Profile' for the new quiz.
								//		update_post_meta( $new_step_id, '_viewProfileStatistics', 1 );
								//	}
								//	update_post_meta( $new_step_id, '_sfwd-quiz', $quiz_meta_values );
								//}

								$quiz_mapper = new WpProQuiz_Model_QuizMapper();
								$quiz_pro = new WpProQuiz_Model_Quiz();
								$quiz_pro->setName( $post_args['post_title'] );
								$quiz_pro->setText( 'AAZZAAZZ' );
								$quiz_pro = $quiz_mapper->save( $quiz_pro );
								$quiz_pro_id = $quiz_pro->getId();
								$quiz_pro_id = absint( $quiz_pro_id );
								learndash_update_setting( $new_step_id, 'quiz_pro', $quiz_pro_id );

								// Set the 'View Statistics on Profile' for the new quiz.
								update_post_meta( $new_step_id, '_viewProfileStatistics', 1 );
							}

							learndash_update_setting( $new_step_id, 'course', '0' );
							update_post_meta( $new_step_id, 'course_id', '0' );
							if ( in_array( $step_set['post_type'], array( 'sfwd-topic', 'sfwd-quiz' ) ) ) {
								learndash_update_setting( $new_step_id, 'lesson', '0' );
								update_post_meta( $new_step_id, 'lesson_id', '0' );
							}
						}
					}
				}
			}
			echo json_encode( $reply_data );

			wp_die();
		}

		/**
		 * Handle AJAX trash step requests.
		 *
		 * @param array $query_args array of values for AJAX request.
		 */
		public function learndash_builder_selector_step_trash( $query_args = array() ) {
			$reply_data = array();

			$post_args = array(
				'post_id'   => 0,
				'post_type' => '',
			);

			$post_args = wp_parse_args( $query_args, $post_args );

			$post_args['post_id'] = intval( $query_args['post_id'] );
			$post_args['post_type'] = esc_attr( $query_args['post_type'] );

			if ( ( empty( $post_args['post_type'] ) ) || ( empty( $post_args['post_id'] ) ) ) {
				$reply_data['status'] = false;
				$reply_data['error_message'] = esc_html__( '#1: Invalid post data', 'learndash' );
			} else if ( in_array( $post_args['post_type'], $this->selector_post_types ) === false ) {
				$reply_data['status'] = false;
				$reply_data['error_message'] = esc_html__( '#2: Invalid post data', 'learndash' );
			} else {
				$new_step_id = wp_trash_post( $post_args['post_id'] );
				$reply_data['status'] = true;
			}
			echo json_encode( $reply_data );

			wp_die();
		}

		/**
		 * Handle AJAX set title requests.
		 *
		 * @param array $query_args array of values for AJAX request.
		 */
		public function learndash_builder_selector_step_title( $query_args = array() ) {

			$reply_data = array();

			$post_args = array(
				'post_title' => '',
				'post_id'    => 0,
				'post_type'  => '',
			);
			$post_args = wp_parse_args( $query_args, $post_args );

			$post_args['post_id'] = absint( $query_args['post_id'] );
			$post_args['post_type'] = esc_attr( $query_args['post_type'] );
			$post_args['post_title'] = sanitize_post_field( 'post_title', $query_args['new_title'], $post_args['post_id'], 'db' );

			if ( ( empty( $post_args['post_title'] ) ) || ( empty( $post_args['post_type'] ) ) || ( empty( $post_args['post_id'] ) ) ) {
				$reply_data['status'] = false;
				$reply_data['error_message'] = esc_html__( '#1: Invalid post data', 'learndash' );
			} else if ( in_array( $post_args['post_type'], $this->selector_post_types ) === false ) {
				$reply_data['status'] = false;
				$reply_data['error_message'] = esc_html__( '#2: Invalid post data', 'learndash' );
			} else {
				$edit_post = array(
					'ID'         => $post_args['post_id'],
					'post_title' => $post_args['post_title'],
					'post_name'  => '',
				);
				wp_update_post( $edit_post );
				$reply_data['status'] = true;

				if ( 'sfwd-quiz' === $post_args['post_type'] ) {
					$quiz_id = get_post_meta( $post_args['post_id'], 'quiz_pro_id', true );
					if ( ! empty( $quiz_id ) ) {
						$quizMapper = new WpProQuiz_Model_QuizMapper();
						$quiz = $quizMapper->fetch( $quiz_id );
						if ( is_a( $quiz, 'WpProQuiz_Model_Quiz' ) ) {
							$quiz->setName( $post_args['post_title'] );
							$quizMapper->save( $quiz );
						}
					}
				}
			}

			echo json_encode( $reply_data );

			wp_die();
		}

		// End of functions.
	}
}
add_action( 'learndash_builders_init', function() {
	Learndash_Admin_Metabox_Course_Builder::add_instance();
} );

