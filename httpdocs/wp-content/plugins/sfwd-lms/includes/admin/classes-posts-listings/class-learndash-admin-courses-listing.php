<?php
/**
 * LearnDash Courses (sfwd-courses) Posts Listing Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Courses_Listing' ) ) ) {
	/**
	 * Class for LearnDash Courses Listing Pages.
	 */
	class Learndash_Admin_Courses_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = 'sfwd-courses';

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

			parent::on_load_edit();
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
		public function post_row_actions( $row_actions = array(), $course_post = null ) {
			global $typenow, $post;

			if ( ( $typenow === $this->post_type ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) == 'yes' ) && ( ! isset( $row_actions['ld-course-builder'] ) ) ) {
				if ( apply_filters( 'learndash_show_course_builder_row_actions', true, $course_post ) === true ) {
					$course_label = sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Use %s Builder', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					);

					$row_actions['ld-course-builder'] = sprintf(
						'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
						add_query_arg(
							array(
								'currentTab' => 'learndash_course_builder',
							),
							get_edit_post_link( $course_post->ID )
						),
						esc_attr( $course_label ),
						esc_html__( 'Builder', 'learndash' )
					);
				}
			}

			return $row_actions;
		}

		// End of functions.
	}
}
new Learndash_Admin_Courses_Listing();
