<?php
/**
 * Onboarding Template.
 *
 * Displayed when no entities were added to help the user.
 *
 * @package LearnDash
 */

?>
<section class="ld-onboarding-screen">
	<div class="ld-onboarding-main">
		<span class="dashicons dashicons-welcome-add-page"></span>
		<h2><?php printf(
			// translators: placeholder: Lessons.
			esc_html_x( 'You don\'t have any %s yet', 'placeholder: Lessons', 'learndash' ),
			\LearnDash_Custom_Label::get_label( 'lessons' )
		); ?></h2>
		<p>
			<?php
				printf(
					// translators: placeholder: %1$s: Lessons, %2$s: Course, %3$s: Lessons, %4$s: Course
					esc_html_x( '%1$s are where you add your content for your %2$s. You can add %3$s using the Course Builder, or you can create them individually and assign them to a %4$s later', 'placeholder: %1$s: Lessons, %2$s: Course, %3$s: Lessons, %4$s: Course', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'lessons' ),
					\LearnDash_Custom_Label::get_label( 'course' ),
					\LearnDash_Custom_Label::get_label( 'lessons' ),
					\LearnDash_Custom_Label::get_label( 'course' )
				);
			?>
		</p>
		<a href="<?php echo admin_url('post-new.php?post_type=sfwd-lessons'); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php printf(
				// translators: placeholder: Lesson.
				esc_html_x( 'Add your first %s', 'placeholder: Lesson', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'lesson' )
			); ?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
			<div class="ld-onboarding-col">
				<h3><?php printf(
					// translators: placeholder: %1$s: Lessons, %2$s: Course
					esc_html_x( 'Creating %1$s for Your %2$s', 'placeholder: %1$s: Lessons, %2$s: Course', 'learndash'),
					\LearnDash_Custom_Label::get_label( 'lessons' ),
					\LearnDash_Custom_Label::get_label( 'course' )
				); ?></h3>
				<div class="ld-bootcamp__embed">
					<iframe width="560" height="315" src="https://www.youtube.com/embed/PD1KKzdakHw" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
			</div>
			<div class="ld-onboarding-col">
				<h3><?php esc_html_e( 'Related help and documentation', 'learndash' ); ?></h3>
				<ul>
					<li><a href="https://www.learndash.com/support/docs/core/courses/course-builder/" target="_blank" rel="noopener noreferrer">Course Builder [Article]</a></li>
					<li><a href="https://www.learndash.com/support/docs/core/lessons/">Lessons Documentation</a></li>
				</ul>
				<p><a href="https://www.learndash.com/support/"><?php esc_html_e( 'View all docs', 'learndash' ); ?></a></p>
			</div>
		</div>

	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
