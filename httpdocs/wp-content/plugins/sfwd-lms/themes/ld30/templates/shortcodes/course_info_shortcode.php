<?php
/**
 * Displays course information for a user
 *
 * Available:
 * $user_id
 * $courses_registered: course
 * $course_progress: Progress in courses
 * $quizzes
 *
 * @since 3.0
 *
 * @package LearnDash\Course
 */

/**
 * Course registered
 */

global $pagenow;

$shortcode_atts_json = htmlspecialchars( json_encode( $shortcode_atts ) );
?>

<div class="learndash-wrapper">
    <div class="ld-course-info" data-shortcode-atts="<?php echo $shortcode_atts_json; ?>">

        <?php
        if ( $pagenow != 'profile.php' && $pagenow != 'user-edit.php' && $courses_registered ): ?>
            <div class="ld-course-info-courses">
                <span class="ld-section-heading"><?php echo sprintf( _x( 'You are registered for the following %s', 'You are registered for the following courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ); ?></span>
                <div class="ld-item-list">
                    <div class="ld-item-list-items">
                        <?php
                        foreach( $courses_registered as $course_id ):
                            learndash_get_template_part( 'shortcodes/course-info/course-row.php',
                                array(
                                    'user_id'            => $user_id,
                                    'courses_registered' => $courses_registered,
                                    'shortcode_atts'     => $shortcode_atts,
                                    'course_progress'    => $course_progress,
                                    'shortcode_atts'     => $shortcode_atts,
                                    'course_id'          => $course_id,
                                ), true );
                        endforeach; ?>
                    </div> <!--/.ld-item-list-items-->
                </div> <!--/.ld-item-list-->
            </div> <!--/.ld-course-info-courses-->
            <?php
			echo SFWD_LMS::get_template(
				'learndash_pager.php',
				array(
				'pager_results' => $courses_registered_pager,
				'pager_context' => 'course_info_courses'
				)
			);
        endif; ?>
	<!-- End Course info shortcode -->
    </div> <!--/.ld-course-info-->
</div>
