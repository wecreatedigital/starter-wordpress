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
			// translators: placeholder: Quizzes.
			esc_html_x( 'You don\'t have any %s yet', 'placeholder: Quizzes', 'learndash' ),
			\LearnDash_Custom_Label::get_label( 'quizzes' )
		); ?></h2>
		<p>
			<?php
				printf(
					// translators: placeholder: %1$s: Quizzes, %2$s: Course, %3$s: Quiz, %4$s: Course
					esc_html_x( '%1$s are a great way to check if your learners are understanding the %2$s content. You can have a %3$s in the middle of a %4$s, or you can put it at the end', 'placeholder: %1$s: Quizzes, %2$s: Course, %3$s: Quiz, %4$s: Course', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'quizzes' ),
					\LearnDash_Custom_Label::get_label( 'course' ),
					\LearnDash_Custom_Label::get_label( 'quiz' ),
					\LearnDash_Custom_Label::get_label( 'course' )
				);
			?>
		</p>
		<a href="<?php echo admin_url('post-new.php?post_type=sfwd-quiz'); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php printf(
				// translators: placeholder: Quiz.
				esc_html_x( 'Add your first %s', 'placeholder: Quiz', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'quiz' )
			); ?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
			<div class="ld-onboarding-col">
				<h3><?php printf(
					// translators: placeholder: %1$s: Quiz
					esc_html_x( 'Creating a %1$s', 'placeholder: %1$s: Quiz', 'learndash'),
					\LearnDash_Custom_Label::get_label( 'quiz' )
					); ?></h3>
					<div class="ld-bootcamp__embed">
						<iframe width="560" height="315" src="https://www.youtube.com/embed/eqH-gSum-qA" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
					</div>
					<span>&nbsp;</span>
					<div class="ld-bootcamp__embed">
						<iframe width="560" height="315" src="https://www.youtube.com/embed/sr24gWa1SbE" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
					</div>
			</div>
			<div class="ld-onboarding-col">
				<h3><?php esc_html_e( 'Related help and documentation', 'learndash' ); ?></h3>
					<ul>
						<li><a href="https://www.learndash.com/support/docs/core/courses/course-builder/" target="_blank" rel="noopener noreferrer">Course Builder [Article]</a></li>
						<li><a href="https://www.learndash.com/support/docs/core/quizzes/" target="_blank" rel="noopener noreferrer">Quizzes Documentation</a></li>
					</ul>
			</div>
		</div>

	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
