<?php
/**
 * Displays the course progress widget.
 *
 * @since 3.0
 *
 * @package LearnDash\Course
 */

 if( !isset($user_id) ) {
     $cuser = wp_get_current_user();
     $user_id = $cuser->ID;
 }

if( !isset($course_id) ) {
    $course_id = ( get_post_type() == 'sfwd-courses' ? get_the_ID() : learndash_get_course_id(get_the_ID()) );
} ?>

<div class="learndash-wrapper learndash-widget">
    <?php
    learndash_get_template_part( 'modules/progress.php', array(
        'context'   => 'course',
        'course_id' => $course_id,
        'user_id'   => $user_id
    ), true ); ?>
</div>
