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
			// translators: placeholder: Courses.
			esc_html_x( 'You don\'t have any %s yet', 'placeholder: Courses', 'learndash' ),
			\LearnDash_Custom_Label::get_label( 'courses' )
		); ?></h2>
		<p>
			<?php printf(
				// translators: placeholder: %1$s: Courses, %2$s: Course, %3$s: Lessons, %4$s: Topics, %5$s: Quizzes
				esc_html_x( '%1$s offer a convenient and organized way for you to deliver training content. Create a %2$s and include %3$s, %4$s, %5$s, Assignments, and more!', 'placeholder: %1$s: Courses, %2$s: Course, %3$s: Lessons, %4$s: Topics, %5$s: Quizzes', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'courses' ),
				\LearnDash_Custom_Label::get_label( 'course' ),
				\LearnDash_Custom_Label::get_label( 'lessons' ),
				\LearnDash_Custom_Label::get_label( 'topics' ),
				\LearnDash_Custom_Label::get_label( 'quizzes' )
			); ?>
		</p>
		<a href="<?php echo admin_url('post-new.php?post_type=sfwd-courses'); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php printf(
				// translators: placeholder: Course.
				esc_html_x( 'Add your first %s', 'placeholder: Course', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'course' )
			); ?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
			<div class="ld-onboarding-col">
				<h3><?php printf(
					// translators: Courses.
					esc_html_x( 'Getting started with LearnDash %s', 'placeholder: Courses', 'learndash'),
					\LearnDash_Custom_Label::get_label( 'courses' )
				); ?></h3>
				<div class="ld-bootcamp__embed">
					<iframe width="560" height="315" src="https://www.youtube.com/embed/cZ61RgRUXnw" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
			</div>
			<div class="ld-onboarding-col">
				<h3><?php esc_html_e( 'Related help and documentation', 'learndash' ); ?></h3>
				<ul>
					<li><a href="https://www.learndash.com/support/docs/core/courses/course-builder/" target="_blank" rel="noopener noreferrer">Course Builder [Article]</a></li>
					<li><a href="https://www.learndash.com/support/docs/core/courses/" target="_blank" rel="noopener noreferrer">Courses Documentation</a></li>
				</ul>
			</div>
		</div>

	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
