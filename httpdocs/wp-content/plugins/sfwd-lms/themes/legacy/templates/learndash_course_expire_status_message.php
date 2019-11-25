<?php
/**
 * Displays the Course Expire Status shortcode message.
 * This template is called from the [ld_course_expire_status] shortcode.
 *
 * @param array $shortcode_atts {
 *   integer $course_id Course ID context for message shown.
 *   integer $user_id User ID context for message shown.
 *   string  $label_before Label shown before expire date.
 *   string  $label_after Label shown after expire date.
 *   string  $format Date/time format string.
 *   boolean $autop True to filter message via wpautop() function.
 * }
 *
 * @since 2.5.9
 *
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ( isset( $shortcode_atts['content'] ) ) && ( ! empty( $shortcode_atts['content'] ) ) ) {
	?><div class="learndash-course-expire-status-message"><?php
		if ( ( isset( $shortcode_atts['autop'] ) ) && ( true === $shortcode_atts['autop'] ) ) {
			echo wpautop( $shortcode_atts['content'] );
		} else {
			echo $shortcode_atts['content'];
		}
	?></div><?php
}
