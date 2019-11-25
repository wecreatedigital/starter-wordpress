<?php
/**
 * Displays a Course Prev/Next navigation.
 *
 * Available Variables:
 *
 * $course_id        : (int) ID of Course
 * $course_step_post : (int) ID of the lesson/topic post
 * $user_id          : (int) ID of User
 * $course_settings  : (array) Settings specific to current course
 * $can_complete     : (bool) Can the user mark this lesson/topic complete?
 *
 * @since 3.0
 *
 * @package LearnDash
 */

// TODO @37designs this is a bit confusing still, as you can still navigate left / right on lessons even with topics
$parent_id = ( get_post_type() == 'sfwd-lessons' ? $course_id : learndash_course_get_single_parent_step( $course_id, get_the_ID() ) );
$learndash_previous_step_id = learndash_previous_post_link( null, 'id', $course_step_post );
if ( ( empty( $learndash_previous_step_id ) ) && ( $course_step_post->post_type === learndash_get_post_type_slug( 'topic' ) ) ) {
	if ( apply_filters( 'learndash_show_parent_previous_link', true, $course_step_post, $user_id, $course_id ) ) {
		$learndash_previous_step_id = learndash_previous_post_link( null, 'id', get_post( $parent_id ) );
	}
}

$learndash_next_step_id = '';
$button_class           = 'ld-button ' . ( $context == 'focus' ? 'ld-button-transparent' : '' );

/*
 * See details for filter 'learndash_show_next_link' https://bitbucket.org/snippets/learndash/5oAEX
 *
 * @since version 2.3
 */

$current_complete = false;

if ( ( empty( $course_settings ) ) && ( ! empty( $course_id ) ) ) {
	$course_settings = learndash_get_setting( $course_id );
}

if ( ( isset( $course_settings['course_disable_lesson_progression'] ) ) && ( $course_settings['course_disable_lesson_progression'] === 'on' ) ) {
	$current_complete = true;
} else {

	if ( $course_step_post->post_type == 'sfwd-topic' ) {
		$current_complete = learndash_is_topic_complete( $user_id, $course_step_post->ID );
	} elseif ( $course_step_post->post_type == 'sfwd-lessons' ) {
		$current_complete = learndash_is_lesson_complete( $user_id, $course_step_post->ID );
	}

	if ( ( $current_complete !== true ) && ( learndash_is_admin_user( $user_id ) ) ) {
		$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );

		if ( $bypass_course_limits_admin_users == 'yes' ) {
			$current_complete = true;
		}
	}
}
if ( true === apply_filters( 'learndash_show_next_link', $current_complete, $user_id, $course_step_post->ID ) ) {
	$learndash_next_step_id = learndash_next_post_link( null, 'id', $course_step_post );
	if ( ( empty( $learndash_next_step_id ) ) && ( $course_step_post->post_type === learndash_get_post_type_slug( 'topic' ) ) ) {
		if ( learndash_is_lesson_complete( $user_id, $parent_id ) ) {
			if ( apply_filters( 'learndash_show_parent_next_link', true, $course_step_post, $user_id, $course_id ) ) {
				$learndash_next_step_id = learndash_next_post_link( null, 'id', get_post( $parent_id ) );
			}
		}
	}
} else if ( ( ! is_user_logged_in() ) && ( ( isset( $course_settings['course_price_type'] ) ) && ( 'open' === $course_settings['course_price_type'] ) ) ) {
	$learndash_next_step_id = learndash_next_post_link( null, 'id', $course_step_post );
}

$complete_button = learndash_mark_complete( $course_step_post );
//if ( ! empty( $learndash_previous_nav ) || ! empty( $learndash_next_nav ) || ! empty( $complete_button ) ) : ?>

<div class="ld-content-actions">

	<?php
	/**
	 * Action to add custom content before the course steps (all locations)
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-all-course-steps-before', get_post_type(), $course_id, $user_id );
	do_action( 'learndash-' . $context . '-course-steps-before', get_post_type(), $course_id, $user_id );
	$learndash_current_post_type = get_post_type();
	?>
	<div class="ld-content-action<?php if ( ! $learndash_previous_step_id ) : ?> ld-empty<?php endif; ?>">
	<?php if ( $learndash_previous_step_id ) : ?>
		<a class="<?php echo esc_attr( $button_class ); ?>" href="<?php echo esc_attr( learndash_get_step_permalink( $learndash_previous_step_id, $course_id ) ); ?>">
			<?php if ( is_rtl() ) { ?>
			<span class="ld-icon ld-icon-arrow-right"></span>
			<?php } else { ?>
			<span class="ld-icon ld-icon-arrow-left"></span>
			<?php } ?>
			<span class="ld-text"><?php echo learndash_get_label_course_step_previous( get_post_type( $learndash_previous_step_id ) ); ?></span>
		</a>
	<?php endif; ?>
	</div>

	<?php
	//$parent_id = ( get_post_type() == 'sfwd-lessons' ? $course_id : learndash_course_get_single_parent_step( $course_id, get_the_ID() ) );

	if ( $parent_id && $context != 'focus' ) :
		?>
		<a href="<?php echo esc_attr( learndash_get_step_permalink( $parent_id, $course_id ) ); ?>" class="ld-primary-color"><?php
		echo learndash_get_label_course_step_back( get_post_type( $parent_id ) );
		?></a>
	<?php endif; ?>

	<div class="ld-content-action<?php if ( ( ! $can_complete ) && ( ! $learndash_next_step_id ) ) : ?> ld-empty<?php endif; ?>">
		<?php
		if ( isset( $can_complete ) && $can_complete && ! empty( $complete_button ) ) :
			echo learndash_mark_complete( $course_step_post );
		elseif ( $learndash_next_step_id ) : ?>
			<a class="<?php echo esc_attr( $button_class ); ?>" href="<?php echo esc_attr( learndash_get_step_permalink( $learndash_next_step_id, $course_id ) ); ?>">
				<span class="ld-text"><?php echo learndash_get_label_course_step_next( get_post_type( $learndash_next_step_id ) ); ?></span>
				<?php if ( is_rtl() ) { ?>
				<span class="ld-icon ld-icon-arrow-left"></span></a>
				<?php } else { ?>
				<span class="ld-icon ld-icon-arrow-right"></span></a>
				<?php } ?>
			</a>
		<?php endif; ?>
	</div>

	<?php
	/**
	 * Action to add custom content after the course steps (all locations)
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-all-course-steps-after', get_post_type(), $course_id, $user_id );
	do_action( 'learndash-' . $context . '-course-steps-after', get_post_type(), $course_id, $user_id );
	?>

</div> <!--/.ld-topic-actions-->

	<?php
//endif;
