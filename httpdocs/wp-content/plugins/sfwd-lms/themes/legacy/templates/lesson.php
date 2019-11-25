<?php
/**
 * Displays a lesson.
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
 * 
 * $quizzes 		: (array) Quizzes Array
 * $post 			: (object) The lesson post object
 * $topics 		: (array) Array of Topics in the current lesson
 * $all_quizzes_completed : (true/false) User has completed all quizzes on the lesson Or, there are no quizzes.
 * $lesson_progression_enabled 	: (true/false)
 * $show_content	: (true/false) true if lesson progression is disabled or if previous lesson is completed. 
 * $previous_lesson_completed 	: (true/false) true if previous lesson is completed
 * $lesson_settings : Settings specific to the current lesson.
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Lesson
 */
?>
<?php if ( @$lesson_progression_enabled && ! @$previous_lesson_completed ) : ?>
	<span id="learndash_complete_prev_lesson">
	<?php
		$previous_item = learndash_get_previous( $post );
		if ( ( !empty( $previous_item ) ) && ( $previous_item instanceof WP_Post ) ) {
			if ( $previous_item->post_type == 'sfwd-quiz') {
				echo sprintf( esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: quiz URL', 'learndash' ), 
				'<a class="learndash-link-previous-incomplete" href="'. learndash_get_step_permalink( $previous_item->ID, $course_id ) .'">'. learndash_get_custom_label_lower('quiz') .'</a>' );
				
			} else if ( $previous_item->post_type == 'sfwd-topic') {
				echo sprintf( esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: topic URL, topic label', 'learndash' ), 
				'<a class="learndash-link-previous-incomplete" href="'. learndash_get_step_permalink( $previous_item->ID, $course_id ) .'">'. learndash_get_custom_label_lower('topic') .'</a>' );
			} else {
				echo sprintf( esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: lesson URL, lesson label', 'learndash' ), 
				'<a class="learndash-link-previous-incomplete" href="'. learndash_get_step_permalink( $previous_item->ID, $course_id ) .'">'. learndash_get_custom_label_lower('lesson') .'</a>' );
			}
			
		} else {
			echo sprintf( esc_html_x( 'Please go back and complete the previous %s.', 'placeholder lesson', 'learndash' ), learndash_get_custom_label_lower('lesson') );
		}
	?>
	</span><br />
	<?php add_filter( 'comments_array', 'learndash_remove_comments', 1, 2 ); ?>
<?php endif; ?>

<?php if ( $show_content ) : ?>

	<?php if ( ( isset( $materials ) ) && ( !empty( $materials ) ) ) : ?>
		<div id="learndash_lesson_materials" class="learndash_lesson_materials">
			<h4><?php printf( esc_html_x( '%s Materials', 'Lesson Materials Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ); ?></h4>
			<p><?php echo $materials; ?></p>
		</div>
	<?php endif; ?>

	<div class="learndash_content"><?php echo $content; ?></div>
	<?php
    /**
     * Lesson Topics
     */
    ?>
	<?php if ( ! empty( $topics ) ) : ?>
		<div id="learndash_lesson_topics_list" class="learndash_lesson_topics_list">
            <div id='learndash_topic_dots-<?php echo esc_attr( $post->ID ); ?>' class="learndash_topic_dots type-list">
                <strong><?php printf( esc_html_x( '%1$s %2$s', 'Lesson Topics Label', 'learndash'), LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'topics' ) ); ?></strong>
                <ul>
                    <?php $odd_class = ''; ?>

                    <?php foreach ( $topics as $key => $topic ) : ?>

                        <?php $odd_class = empty( $odd_class ) ? 'nth-of-type-odd' : ''; ?>
                        <?php $completed_class = empty( $topic->completed ) ? 'topic-notcompleted' : 'topic-completed'; ?>

                        <li class='<?php echo esc_attr( $odd_class ); ?>'>
                            <span class="topic_item">
                                <a class='<?php echo esc_attr( $completed_class ); ?>' href='<?php echo learndash_get_step_permalink( $topic->ID, $course_id ); ?>' title='<?php echo esc_attr( $topic->post_title ); ?>'>
                                    <span><?php echo $topic->post_title; ?></span>
                                </a>
                            </span>
                        </li>

                    <?php endforeach; ?>

                </ul>
            </div>
		</div>
		<?php
		global $course_pager_results;
		if ( isset( $course_pager_results[ $post->ID ]['pager'] ) ) {
			echo SFWD_LMS::get_template( 
				'learndash_pager.php', 
				array(
					'pager_results' => $course_pager_results[ $post->ID ]['pager'], 
					'pager_context' => 'course_topics',
					'href_query_arg' => 'ld-topic-page',
					'href_val_prefix' => $post->ID . '-'
				)
			);
		}
		?>
	<?php endif; ?>


	<?php
    /**
     * Show Quiz List
     */
    ?>
	<?php if ( ! empty( $quizzes ) ) : ?>
		<div id="learndash_quizzes" class="learndash_quizzes">
			<div id="quiz_heading"><span><?php echo LearnDash_Custom_Label::get_label( 'quizzes' ); ?></span><span class="right"><?php esc_html_e( 'Status', 'learndash' ); ?></span></div>
			<div id="quiz_list" class="quiz_list">

			<?php foreach ( $quizzes as $quiz ) : ?>
				<div id='post-<?php echo esc_attr( $quiz['post']->ID ); ?>' class='<?php echo esc_attr( $quiz['sample'] ); ?>'>
					<div class="list-count"><?php echo esc_attr( $quiz['sno'] ); ?></div>
					<h4>
						<a class='<?php echo esc_attr( $quiz['status'] ); ?>' href='<?php echo esc_attr( $quiz['permalink'] ); ?>'><?php echo $quiz['post']->post_title; ?></a>
					</h4>
				</div>
			<?php endforeach; ?>

			</div>
		</div>
	<?php endif; ?>


	<?php
    /**
     * Display Lesson Assignments
     */
    ?>
	<?php if ( ( lesson_hasassignments( $post ) ) && ( !empty( $user_id ) ) ) : ?>
		<?php
			$ret = SFWD_LMS::get_template( 
					'learndash_lesson_assignment_uploads_list.php', 
					array(
						'course_step_post' => $post,
						'user_id' => $user_id
					)
				);
			echo $ret;	
		?>
		
	<?php endif; ?>


	<?php
    /**
     * Display Mark Complete Button
     */
    ?>
	<?php if ( $all_quizzes_completed && $logged_in && !empty( $course_id ) ) : ?>
		<br />
		<?php
		echo learndash_mark_complete(
			$post,
			array(
				'form' => array(
					'id' => 'sfwd-mark-complete',
				),
				'button' => array(
					'id' => 'learndash_mark_complete_button',
				),
				'timer' => array(
					'id' => 'learndash_timer',
				),
			)
		);
		?>
	<?php endif; ?>
	
<?php endif; ?>

<br />

<?php
$ret = SFWD_LMS::get_template( 
		'learndash_course_steps_navigation.php', 
		array(
			'course_id' => $course_id,
			'course_step_post' => $post,
			'user_id' => $user_id,
			'course_settings' => isset( $course_settings ) ? $course_settings : array()
		)
	);
echo $ret;	
