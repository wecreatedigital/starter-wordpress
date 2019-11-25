<?php
/**
 * Displays a quiz.
 *
 * Available Variables:
 *
 * $course_id       : (int) ID of the course
 * $course      : (object) Post object of the course
 * $course_settings : (array) Settings specific to current course
 * $course_status   : Course Status
 * $has_access  : User has access to course or is enrolled.
 *
 * $courses_options : Options/Settings as configured on Course Options page
 * $lessons_options : Options/Settings as configured on Lessons Options page
 * $quizzes_options : Options/Settings as configured on Quiz Options page
 *
 * $user_id         : (object) Current User ID
 * $logged_in       : (true/false) User is logged in
 * $current_user    : (object) Currently logged in user object
 * $post            : (object) The quiz post object () (Deprecated in LD 3.1. User $quiz_post instead).
 * $quiz_post       : (object) The quiz post object ().
 * $lesson_progression_enabled  : (true/false)
 * $show_content    : (true/false) true if user is logged in and lesson progression is disabled or if previous lesson and topic is completed.
 * $attempts_left   : (true/false)
 * $attempts_count : (integer) No of attempts already made
 * $quiz_settings   : (array)
 *
 * Note:
 *
 * To get lesson/topic post object under which the quiz is added:
 * $lesson_post = !empty($quiz_settings["lesson"])? get_post($quiz_settings["lesson"]):null;
 *
 * @since 2.1.0
 *
 * @package LearnDash\Quiz
 */
?>
<?php
if ( ( ! isset( $quiz_post ) ) || ( ! is_a( $quiz_post, 'WP_Post' ) ) ) {
    return;
}
?>
<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
<?php
    /**
     * Action to add custom content before the quiz content starts
     *
     * @since 3.0
     */
	do_action( 'learndash-quiz-before', $quiz_post->ID, $course_id, $user_id );

	learndash_get_template_part(
		'modules/infobar.php',
		array(
			'context'   => 'quiz',
			'course_id' => $course_id,
			'user_id'   => $user_id,
			'post'      => $quiz_post,
		),
		true
	);

	if ( ! empty( $lesson_progression_enabled ) ) :

		$last_incomplete_step = is_quiz_accessable( null, $quiz_post, true, $course_id );
		if ( 1 !== $last_incomplete_step ) :

			/**
		 * Action to add custom content before the quiz progression
		 *
		 * @since 3.0
		 */
			do_action( 'learndash-quiz-progression-before', $quiz_post->ID, $course_id, $user_id );

			if ( is_a( $last_incomplete_step, 'WP_Post' ) ) {

				learndash_get_template_part(
					'modules/messages/lesson-progression.php',
					array(
						'previous_item' => $last_incomplete_step,
						'course_id'     => $course_id,
						'user_id'       => $user_id,
						'context'       => 'quiz',
					),
					true
				);

			}

			/**
		 * Action to add custom content after the quiz progress
		 *
		 * @since 3.0
		 */
			do_action( 'learndash-quiz-progression-after', $quiz_post->ID, $course_id, $user_id );

		endif;
endif;

	if ( $show_content ) :

		/**
	  * Content and/or tabs
	  */
		learndash_get_template_part(
			'modules/tabs.php',
			array(
				'course_id' => $course_id,
				'post_id'   => $quiz_post->ID,
				'user_id'   => $user_id,
				'content'   => $content,
				'materials' => $materials,
				'context'   => 'quiz',
			),
			true
		);

		if ( $attempts_left ) :

			/**
		 * Action to add custom content before the actual quiz content (not WP_Editor content)
		 *
		 * @since 3.0
		 */
			do_action( 'learndash-quiz-actual-content-before', $quiz_post->ID, $course_id, $user_id );

			echo $quiz_content;

			/**
		 * Action to add custom content after the actual quiz content (not WP_Editor content)
		 *
		 * @since 3.0
		 */
			do_action( 'learndash-quiz-actual-content-after', $quiz_post->ID, $course_id, $user_id );

	   else :

			/**
		 * Display an alert
		 */

			/**
		  * Action to add custom content before the quiz attempts alert
		  *
		  * @since 3.0
		  */
			do_action( 'learndash-quiz-attempts-alert-before', $quiz_post->ID, $course_id, $user_id );

			learndash_get_template_part(
				'modules/alert.php',
				array(
					'type'    => 'warning',
					'icon'    => 'alert',
					'message' => sprintf(
						// translators: placeholders: quiz, attempts count.
						   esc_html_x( 'You have already taken this %1$s %2$d time(s) and may not take it again.', 'placeholders: quiz, attempts count', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' ),
						$attempts_count
					),
				),
				true
			);

			/**
		 * Action to add custom content after the quiz attempts alert
		 *
		 * @since 3.0
		 */
			do_action( 'learndash-quiz-attempts-alert-after', $quiz_post->ID, $course_id, $user_id );

	   endif;

endif;

/**
 * Action to add custom content before the quiz content starts
 *
 * @since 3.0
 */
do_action( 'learndash-quiz-after', $quiz_post->ID, $course_id, $user_id );
?>

</div> <!--/.learndash-wrapper-->
