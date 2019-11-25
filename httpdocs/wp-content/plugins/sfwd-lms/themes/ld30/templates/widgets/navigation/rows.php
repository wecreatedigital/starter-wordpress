<?php
/**
 * Displays the course navigation widget.
 *
 * @since 3.0
 *
 * @package LearnDash\Course
 */

if( !empty($lessons) ):

	$sections = learndash_30_get_course_sections($course_id);
	$i = 0;

	foreach( $lessons as $course_lesson ):

		$is_current_lesson = false;

		if( isset($widget_instance['current_lesson_id']) && $widget_instance['current_lesson_id'] == $course_lesson['post']->ID ) {
			$is_current_lesson = true;
		} elseif( $is_current_lesson == get_the_ID() ) {
			$is_current_lesson = true;
		}

		$all_topics = learndash_topic_dots( $course_lesson['post']->ID, false, 'array' );

		$topic_pager_args = apply_filters( 'ld30_ajax_topic_pager_args', array(
			'course_id' => $course_id,
			'lesson_id' => $course_lesson['post']->ID
		) );

		$lesson_topics = learndash_process_lesson_topics_pager( $all_topics, $topic_pager_args );

        learndash_get_template_part('widgets/navigation/lesson-row.php', array(
			'count'				=> $i,
			'sections'			=> $sections,
            'lesson'            => $course_lesson,
            'course_id'         => $course_id,
            'user_id'           => $user_id,
            'lesson_topics'     => $lesson_topics,
            'widget_instance'   => $widget_instance,
            'is_current_lesson' => $is_current_lesson,
			'has_access'		=> $has_access
        ), true );

    $i++; endforeach;

endif;

/**
 * Should we show quizzes in the course navigation based on pagination?
 * @var [type]
 */


$show_course_quizzes = true;

if( isset($course_pager_results['pager']) && !empty($course_pager_results['pager'] ) ) {
	$show_course_quizzes = ( $course_pager_results['pager']['paged'] == $course_pager_results['pager']['total_pages'] ? true : false );
}

if ( $show_course_quizzes == true && isset($widget_instance['show_course_quizzes']) && $widget_instance['show_course_quizzes'] == true ):

	$course_quiz_list = learndash_get_course_quiz_list( $course_id, get_current_user_id() );

    if( !empty($course_quiz_list) ):
        foreach( $course_quiz_list as $quiz ):

            learndash_get_template_part( 'widgets/navigation/quiz-row.php', array(
                'quiz'      => $quiz,
                'user_id'   => $user_id,
                'course_id' => $course_id,
                'context'   => 'course'
            ), true );

        endforeach;
    endif;

endif;

if( isset($course_pager_results['pager']) ):
	learndash_get_template_part(
		'modules/pagination.php',
		array(
			'pager_results' => $course_pager_results['pager'],
			'pager_context' => 'course_lessons',
			'course_id'		=> $course_id,
		), true
	);
endif;

?>
