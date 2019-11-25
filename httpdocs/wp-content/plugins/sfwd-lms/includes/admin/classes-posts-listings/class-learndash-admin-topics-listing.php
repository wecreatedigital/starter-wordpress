<?php
/**
 * LearnDash Topics (sfwd-topic) Posts Listing Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Topics_Listing' ) ) ) {
	/**
	 * Class for LearnDash Topics Listing Pages.
	 */
	class Learndash_Admin_Topics_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = 'sfwd-topic';

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
				),

			);
			parent::on_load_edit();

			add_filter( 'learndash_show_post_type_selector_filter', array( $this, 'filter_quiz_lesson_selector' ), 30, 2 );
			//add_action( 'learndash_post_listing_after_option', array( $this, 'learndash_post_listing_after_option' ), 30, 3 );
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
new Learndash_Admin_Topics_Listing();
