<?php
/**
 * Displays a topic.
 *
 * Available Variables:
 *
 * $course_id 		: (int) ID of the course
 * $course 		: (object) Post object of the course
 * $course_settings : (array) Settings specific to current course
 * $course_status 	: Course Status
 * $has_access 	: User has access to course or is enrolled.
 *
 * $courses_options : Options/Settings as configured on Course Options page
 * $lessons_options : Options/Settings as configured on Lessons Options page
 * $quizzes_options : Options/Settings as configured on Quiz Options page
 *
 * $user_id 		: (object) Current User ID
 * $logged_in 		: (true/false) User is logged in
 * $current_user 	: (object) Currently logged in user object
 * $quizzes 		: (array) Quizzes Array
 * $post 			: (object) The topic post object
 * $lesson_post 	: (object) Lesson post object in which the topic exists
 * $topics 		: (array) Array of Topics in the current lesson
 * $all_quizzes_completed : (true/false) User has completed all quizzes on the lesson Or, there are no quizzes.
 * $lesson_progression_enabled 	: (true/false)
 * $show_content	: (true/false) true if lesson progression is disabled or if previous lesson and topic is completed.
 * $previous_lesson_completed 	: (true/false) true if previous lesson is completed
 * $previous_topic_completed	: (true/false) true if previous topic is completed
 *
 * @since 3.0
 *
 * @package LearnDash\Topic
 */ ?>

 <div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
    <?php
     /**
      * Action to add custom content before the topic
      *
      * @since 3.0
      */
     do_action( 'learndash-topic-before', get_the_ID(), $course_id, $user_id );

    learndash_get_template_part( 'modules/infobar.php', array(
        'context'   => 'topic',
        'course_id' => $course_id,
        'user_id'   => $user_id
    ), true );


    /**
     * If the user needs to complete the previous lesson AND topic display an alert
     *
     */
    if( $lesson_progression_enabled && ( ! $previous_topic_completed || ! $previous_lesson_completed ) ):

    	$previous_item = learndash_get_previous( $post );

    	if (empty($previous_item)) {
    		$previous_item = learndash_get_previous( $lesson_post );
    	}

        learndash_get_template_part('modules/messages/lesson-progression.php', array(
            'previous_item' => $previous_item,
            'course_id'     => $course_id,
            'context'       => 'topic'
        ), true );

    endif;

    if( $show_content ):

        learndash_get_template_part( 'modules/tabs.php', array(
            'course_id' => $course_id,
            'post_id'   => get_the_ID(),
            'user_id'   => $user_id,
            'content'   => $content,
            'materials' => $materials,
            'context'   => 'topic'
        ), true );

        if( !empty($quizzes) ):

            learndash_get_template_part( 'quiz/listing.php', array(
                'user_id'   => $user_id,
                'course_id' => $course_id,
                'lesson_id' => $lesson_id,
                'quizzes'   => $quizzes,
                'context'   => 'topic'
            ), true );

        endif;

    	if( lesson_hasassignments($post) && !empty($user_id) ):

            learndash_get_template_part(
                'assignment/listing.php',
                array(
                    'user_id'          => $user_id,
                    'course_step_post' => $post,
                    'course_id'        => $course_id,
                    'context'          => 'topic'
                ), true );

    	endif;


    endif; // $show_content

    $can_complete = false;

    if( $all_quizzes_completed && $logged_in && !empty($course_id) ):
        $can_complete = apply_filters( 'learndash-lesson-can-complete', true, get_the_ID(), $course_id, $user_id );
    endif;

    learndash_get_template_part(
    		'modules/course-steps.php',
    		array(
    			'course_id'              => $course_id,
    			'course_step_post'       => $post,
                'all_quizzes_completed'  => $all_quizzes_completed,
    			'user_id'                => $user_id,
    			'course_settings'        => isset($course_settings) ? $course_settings : array(),
                'context'                => 'topic',
                'can_complete'           => $can_complete
    		),
            true
    	);

    /**
     * Action to add custom content after the topic
     *
     * @since 3.0
     */
    do_action( 'learndash-topic-after', get_the_ID(), $course_id, $user_id ); ?>
</div> <!--/.learndash-wrapper-->
