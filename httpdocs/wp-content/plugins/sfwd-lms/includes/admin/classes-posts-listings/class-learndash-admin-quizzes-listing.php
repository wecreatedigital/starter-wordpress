<?php
/**
 * LearnDash Quizzes (sfwd-quiz) Posts Listing Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Quizzes_Listing' ) ) ) {
	/**
	 * Class for LearnDash Quizzes Listing Pages.
	 */
	class Learndash_Admin_Quizzes_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = 'sfwd-quiz';

			parent::__construct();
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 */
		public function on_load_edit() {
			global $typenow, $post;

			if ( ( empty( $typenow ) ) || ( $typenow !== $this->post_type ) ) {
				return;
			}

			add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 20, 2 );

			$this->post_type_selectors = array(
				'course_id' => array(
					'query_args'       => array(
						'post_type' => learndash_get_post_type_slug( 'course' ),
					),
					'query_arg'        => 'course_id',
					'selected'         => 0,
					'field_name'       => 'course_id',
					'field_id'         => 'course_id',
					'show_all_value'   => '',
					'show_all_label'   => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'Show All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'lazy_load'        => true,
					//'show_empty_value' => 'empty',
					//'show_empty_label' => sprintf(
					//	// translators: placeholder: Courses.
					//	esc_html_x( '-- No %s --', 'placeholder: Courses', 'learndash' ),
					//	LearnDash_Custom_Label::get_label( 'courses' )
					//),
				),

				'lesson_id' => array(
					'query_args'       => array(
						'post_type' => learndash_get_post_type_slug( 'lesson' ),
					),
					'query_arg'        => 'lesson_id',
					'selected'         => 0,
					'field_name'       => 'lesson_id',
					'field_id'         => 'lesson_id',
					'show_all_value'   => '',
					'show_all_label'   => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( 'Show All %s', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'lazy_load'        => false,
					//'show_empty_value' => 'empty',
					/*'show_empty_label' => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( '-- No %s --', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),*/
				),
			);
			parent::on_load_edit();

			add_filter( 'learndash_show_post_type_selector_filter', array( $this, 'filter_quiz_lesson_selector' ), 30, 2 );
			add_action( 'learndash_post_listing_after_option', array( $this, 'learndash_post_listing_after_option' ), 30, 3 );
		}

		/**
		 * Add Course Builder link to Courses row action array.
		 *
		 * @since 2.5.0
		 *
		 * @param array   $row_actions Existing Row actions for course.
		 * @param WP_Post $course_post Course Post object for current row.
		 *
		 * @return array $row_actions
		 */
		public function post_row_actions( $row_actions = array(), $quiz_post = null ) {
			global $typenow, $post;

			if ( ( $typenow === $this->post_type ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) ) && ( ! isset( $row_actions['ld-course-builder'] ) ) ) {
				if ( ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) ) && ( ! isset( $row_actions['ld-quiz-builder'] ) ) ) {
					if ( apply_filters( 'learndash_show_quiz_builder_row_actions', true, $quiz_post ) === true ) {
						$quiz_label = sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Use %s Builder', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						);

						$builder_link = add_query_arg(
							array(
								'currentTab' => 'learndash_quiz_builder',
							),
							get_edit_post_link( $quiz_post->ID )
						);

						$row_actions['ld-quiz-builder'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							$builder_link,
							esc_attr( $quiz_label ),
							esc_html__( 'Builder', 'learndash' )
						);
					}
				}

				$pro_quiz_id = learndash_get_setting( $quiz_post, 'quiz_pro', true );
				if ( ! empty( $pro_quiz_id ) ) {

					if ( ( ! isset( $row_actions['questions'] ) ) || ( empty( $row_actions['questions'] ) ) ) {

						if ( ( true === is_data_upgrade_quiz_questions_updated() ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ) {
							$questions_link = add_query_arg(
								array(
									'post_type' => learndash_get_post_type_slug( 'question' ),
									'quiz_id'   => $quiz_post->ID,
								),
								admin_url( 'edit.php' )
							);
						} else {
							$questions_link = add_query_arg(
								array(
									'page'    => 'ldAdvQuiz',
									'module'  => 'question',
									'quiz_id' => $pro_quiz_id,
									'post_id' => $post->ID,
								),
								admin_url( 'admin.php' )
							);
						}

						$questions_label = sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Show %s Questions', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						);

						$row_actions['questions'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							$questions_link,
							esc_attr( $questions_label ),
							esc_html__( 'Questions', 'learndash' )
						);
					}

					if ( ( ! isset( $row_actions['statistics'] ) ) || ( empty( $row_actions['statistics'] ) ) ) {
						$statistics_link = add_query_arg(
							array(
								'page'       => 'ldAdvQuiz',
								'module'     => 'statistics',
								'id'         => $pro_quiz_id,
								'post_id'    => $quiz_post->ID,
								'currentTab' => 'statistics',
							),
							admin_url( 'admin.php?' )
						);

						$statistics_label = sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Show %s Statistics', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						);

						$row_actions['statistics'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							$statistics_link,
							esc_attr( $statistics_label ),
							esc_html__( 'Statistics', 'learndash' )
						);
					}

					if ( ( ! isset( $row_actions['leaderboard'] ) ) || ( empty( $row_actions['leaderboard'] ) ) ) {
						$leaderboard_link = add_query_arg(
							array(
								'page'       => 'ldAdvQuiz',
								'module'     => 'toplist',
								'id'         => $pro_quiz_id,
								'post_id'    => $post->ID,
								'currentTab' => 'leaderboard',
							),
							admin_url( 'admin.php' )
						);

						$leaderboard_label = sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Show %s Leaderboard', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						);

						$row_actions['leaderboard'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							$leaderboard_link,
							esc_attr( $leaderboard_label ),
							esc_html__( 'Leaderboard', 'learndash' )
						);
					}

					if ( ( current_user_can('wpProQuiz_export') ) && ( ! isset( $row_actions['export'] ) ) || ( empty( $row_actions['export'] ) ) ) {
						$export_link = add_query_arg(
							array(
								'page'       => 'ldAdvQuiz',
								'quiz_id'    => $post->ID,
							),
							admin_url( 'admin.php' )
						);

						$export_label = sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Export %s', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						);

						$row_actions['export'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							$export_link,
							esc_attr( $export_label ),
							esc_html__( 'Export', 'learndash' )
						);
					}
				}
			}

			return $row_actions;
		}

		/**
		 * Filter the selector filters. 
		 *
		 * @param array $query_args Query Args for Selector.
		 * @param string $post_type Post Type slug for selector.
		 */
		public function filter_quiz_lesson_selector( $query_args = array(), $post_type = '' ) {
			global $sfwd_lms;

			// Check that the selector post type matches for out listing post type.
			if ( $post_type === $this->post_type ) {
				if ( isset( $query_args['post_type'] ) ) {
					if ( ( ( is_string( $query_args['post_type'] ) ) && ( learndash_get_post_type_slug( 'lesson' ) === $query_args['post_type'] ) ) || ( ( is_array( $query_args['post_type'] ) ) && ( in_array( learndash_get_post_type_slug( 'lesson' ), $query_args['post_type'] ) ) ) ) {

						if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
							$lessons_items = $sfwd_lms->select_a_lesson_or_topic( absint( $_GET['course_id'] ), false, false );
							if ( ! empty( $lessons_items ) ) {
								$query_args['post__in'] = array_keys( $lessons_items );
								$query_args['orderby'] = 'post__in';
							} else {
								$query_args['post__in'] = array( 0 );
							}
						} else {
							$query_args['post__in'] = array( 0 );
						}
					}
				}
			}

			return $query_args;
		}

		public function learndash_post_listing_after_option( $post, $query_args = array(), $post_type = '' ) {
			global $sfwd_lms;

			// Check that the selector post type matches for out listing post type.
			if ( $post_type === $this->post_type ) {
				if ( ( ( is_string( $query_args['post_type'] ) ) && ( learndash_get_post_type_slug( 'lesson' ) === $query_args['post_type'] ) ) || ( ( is_array( $query_args['post_type'] ) ) && ( in_array( learndash_get_post_type_slug( 'lesson' ), $query_args['post_type'] ) ) ) ) {
					if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
						$lessons_topics = learndash_get_topic_list( $post->ID, absint( $_GET['course_id'] ) );
						if ( ! empty( $lessons_topics ) ) {
							foreach ( $lessons_topics as $topic ) {
								$selected = '';
								if ( ( isset( $_GET['lesson_id'] ) ) && ( ! empty( $_GET['lesson_id']) ) ) {
									$selected = selected( absint( $_GET['lesson_id'] ), $topic->ID, false );
								}
								echo '<option value="' . $topic->ID . '" ' . $selected . '>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $topic->post_title . '</option>';
							}
						}
					}
				}
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Quizzes_Listing();
