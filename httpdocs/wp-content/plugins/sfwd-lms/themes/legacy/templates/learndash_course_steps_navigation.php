<?php
/**
 * Displays a Course Prev/Next navigation.
 *
 * Available Variables:
 * 
 * $course_id 		: (int) ID of Course
 * $course_step_post : (int) ID of the lesson/topic post
 * $user_id 		: (int) ID of User
 * $course_settings : (array) Settings specific to current course
 * 
 * @since 2.5.8
 * 
 * @package LearnDash
 */

$learndash_previous_nav = learndash_previous_post_link();
$learndash_next_nav = '';

/*
 * See details for filter 'learndash_show_next_link' https://bitbucket.org/snippets/learndash/5oAEX
 *
 * @since version 2.3
 */

$current_complete = false;

if ( ( isset( $course_settings['course_disable_lesson_progression'] ) ) && ( $course_settings['course_disable_lesson_progression'] === 'on' ) ) {
	$current_complete = true;
} else {

	if ( $course_step_post->post_type == 'sfwd-topic' ) {
		$current_complete = learndash_is_topic_complete( $user_id, $course_step_post->ID );
	} else if ( $course_step_post->post_type == 'sfwd-lessons' ) {
		$current_complete = learndash_is_lesson_complete( $user_id, $course_step_post->ID );
	}

	if ( ( $current_complete !== true) && ( learndash_is_admin_user( $user_id ) ) ) {
		$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );
		if ( $bypass_course_limits_admin_users == 'yes' ) $current_complete = true;
	}
}

if ( apply_filters( 'learndash_show_next_link', $current_complete, $user_id, $course_step_post->ID ) ) {
	 $learndash_next_nav = learndash_next_post_link();
}

if ( ( !empty( $learndash_previous_nav ) ) || ( !empty( $learndash_next_nav ) ) ) {
	?><p id="learndash_next_prev_link"><?php echo $learndash_previous_nav; ?> <?php echo $learndash_next_nav; ?></p><?php
}
