<?php
/**
 * Course Builder Helpers.
 *
 * Used to provide proper data to Course Builder app.
 *
 * @package LearnDash
 */

namespace LearnDash\Admin\CourseBuilderHelpers;

/**
 * Provide Course Data to Builder.
 *
 * @param Object $data The data passed down to front-end.
 * @return Object
 */
function get_course_data( $data ) {
	global $pagenow, $typenow;

	$output_lessons = array();
	$output_quizzes = array();
	$sections       = array();

if ( ( 'post.php' === $pagenow ) && ( learndash_get_post_type_slug( 'course' ) === $typenow ) ) {
		$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : get_the_ID();
		if ( ! empty( $course_id ) ) {
			// Get a list of lessons to loop.
			$lessons        = learndash_get_course_lessons_list( $course_id, null, array( 'num' => 0 ) );
			$output_lessons = [];
			$lesson_topics  = [];

			if ( ( is_array( $lessons ) )  && ( ! empty( $lessons ) ) ) {
				// Loop course's lessons.
				foreach ( $lessons as $lesson ) {
					$post          = $lesson['post'];
					// Get lesson's topics.
					$topics        = learndash_topic_dots( $post->ID, false, 'array', null, $course_id );
					$output_topics = [];

					if ( ( is_array( $topics ) )  && ( ! empty( $topics ) ) ) {
						// Loop Topics.
						foreach ( $topics as $topic ) {
							// Get topic's quizzes.
							$topic_quizzes        = learndash_get_lesson_quiz_list( $topic->ID, null, $course_id );
							$output_topic_quizzes = [];

							if ( ( is_array( $topic_quizzes ) )  && ( ! empty( $topic_quizzes ) ) ) {
								// Loop Topic's Quizzes.
								foreach ( $topic_quizzes as $quiz ) {
									$quiz_post = $quiz['post'];

									$output_topic_quizzes[] = [
										'ID'         => $quiz_post->ID,
										'expanded'   => true,
										'post_title' => $quiz_post->post_title,
										'type'       => $quiz_post->post_type,
										'url'        => learndash_get_step_permalink( $quiz_post->ID, $course_id ),
										'edit_link'  => get_edit_post_link( $quiz_post->ID, '' ),
										'tree'       => [],
									];
								}
							}

							$output_topics[] = [
								'ID'         => $topic->ID,
								'expanded'   => true,
								'post_title' => $topic->post_title,
								'type'       => $topic->post_type,
								'url'        => learndash_get_step_permalink( $topic->ID, $course_id ),
								'edit_link'  => get_edit_post_link( $topic->ID, '' ),
								'tree'       => $output_topic_quizzes,
							];
						}
					}

					// Get lesson's quizzes.
					$quizzes        = learndash_get_lesson_quiz_list( $post->ID, null, $course_id );
					$output_quizzes = [];
					
					if ( ( is_array( $quizzes ) )  && ( ! empty( $quizzes ) ) ) {
						// Loop lesson's quizzes.
						foreach ( $quizzes as $quiz ) {
							$quiz_post = $quiz['post'];

							$output_quizzes[] = [
								'ID'         => $quiz_post->ID,
								'expanded'   => true,
								'post_title' => $quiz_post->post_title,
								'type'       => $quiz_post->post_type,
								'url'        => learndash_get_step_permalink( $quiz_post->ID, $course_id ),
								'edit_link'  => get_edit_post_link( $quiz_post->ID, '' ),
								'tree'       => [],
							];
						}
					}

					// Output lesson with child tree.
					$output_lessons[] = [
						'ID'         => $post->ID,
						'expanded'   => false,
						'post_title' => $post->post_title,
						'type'       => $post->post_type,
						'url'        => $lesson['permalink'],
						'edit_link'  => get_edit_post_link( $post->ID, '' ),
						'tree'       => array_merge( $output_topics, $output_quizzes ),
					];
				}
			}

			// Get a list of quizzes to loop.
			$quizzes        = learndash_get_course_quiz_list( $course_id );
			$output_quizzes = [];
			
			if ( ( is_array( $quizzes ) )  && ( ! empty( $quizzes ) ) ) {
				// Loop course's quizzes.
				foreach ( $quizzes as $quiz ) {
					$post = $quiz['post'];

					$output_quizzes[] = [
						'ID'         => $post->ID,
						'expanded'   => true,
						'post_title' => $post->post_title,
						'type'       => $post->post_type,
						'url'        => learndash_get_step_permalink( $post->ID, $course_id ),
						'edit_link'  => get_edit_post_link( $post->ID, '' ),
						'tree'       => [],
					];
				}
			}

			// Merge sections at Outline.
			$sections_raw = get_post_meta( $course_id, 'course_sections', true );
			$sections     = ! empty( $sections_raw ) ? json_decode( $sections_raw ) : [];

			if ( ( is_array( $sections ) ) && ( ! empty( $sections ) ) ) {
				foreach ( $sections as $section ) {
					array_splice( $output_lessons, (int) $section->order, 0, [ $section ] );
				}
			}
		}
	}

	// Output data.
	$data['outline'] = [
		'lessons' => $output_lessons,
		'quizzes' => $output_quizzes,
		'sections' => $sections,
	];

	return $data;
}
//add_filter( 'learndash_header_data', 'LearnDash\Admin\CourseBuilderHelpers\get_course_data', 100 );

/**
 * Checks if course builder should be enqueued.
 *
 * @return bool
 */
function should_enqueue_assets() {
	$screen        = get_current_screen();
	$course_id     = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : get_the_ID();

	// Enqueue course builder assets only when required.
	if ( ( 'post' === $screen->base && 'sfwd-courses' === get_post_type( $course_id ) ) ||
		'sfwd-courses_page_courses-builder' === $screen->id ) {
		return true;
	}

	return false;
}
