<?php
/**
 * Displays the Course Lesson Not Available	message
 *
 * Available Variables:
 * user_id : (integer) The user_id whose points to show
 * course_id : (integer) The ID of the couse shown
 * lesson_id: (integer) The Of of the lesson not available
 * ld_lesson_access_from_int : (integer) timestamp when lesson will become available
 * ld_lesson_access_from_date : (string) Formatted human readable date/time of ld_lesson_access_from_int
 * context : (string) The context will be set based on where this message is shown. course, lesson, loop, etc.
 *
 * @since 3.0
 *
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// First generate the message
$message = sprintf( wp_kses_post( __( '<span class="ld-display-label">Available on:</span> <span class="ld-display-date">%s</span>', 'learndash' ) ), learndash_adjust_date_time_display( $lesson_access_from_int ) );

$button = false;

// The figure out how to display it
if ( $context == 'lesson' ) {

	$button = array(
		'url' 	        => get_permalink($course_id),
		'label'	        => learndash_get_label_course_step_back( learndash_get_post_type_slug( 'course' ) ),
		'icon'  		=> 'arrow-left',
		'icon-location' => 'left'
	); // On the lesson single we display additional information.

} ?>

<div class="learndash-wrapper">
	<?php
	learndash_get_template_part( 'modules/alert.php', array(
	    'type'      =>  'info',
	    'icon'      =>  'calendar',
		'button'	=>	$button,
	    'message'   =>  apply_filters( 'learndash_lesson_available_from_text', $message, get_post( $lesson_id ), $lesson_access_from_int )
	), true ); ?>
</div>
