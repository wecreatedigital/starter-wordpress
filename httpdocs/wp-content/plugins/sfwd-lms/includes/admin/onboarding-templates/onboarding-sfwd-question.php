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
			// translators: placeholder: Questions.
			esc_html_x( 'You don\'t have any %s yet', 'placeholder: Questions', 'learndash' ),
			\LearnDash_Custom_Label::get_label( 'questions' )
		); ?></h2>
		<p>
		<?php
				printf(
					// translators: placeholder: %1$s: Questions, %2$s: Quiz, %3$s: Questions, %4$s: Quiz
					esc_html_x( 'You can add %1$s when you create a %2$s, or you can choose to add %3$s at any time and add them to a %4$s later.', 'placeholder: %1$s: Questions, %2$s: Quiz, %3$s: Questions, %4$s: Quiz', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'questions' ),
					\LearnDash_Custom_Label::get_label( 'quiz' ),
					\LearnDash_Custom_Label::get_label( 'questions' ),
					\LearnDash_Custom_Label::get_label( 'quiz' )
				);
			?>
		</p>
		<a href="<?php echo admin_url('post-new.php?post_type=sfwd-question'); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php printf(
				// translators: placeholder: Question.
				esc_html_x( 'Add your first %s', 'placeholder: Question', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'question' )
			); ?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
			<div class="ld-onboarding-col">
				<h3><?php esc_html_e( 'Related help and documentation', 'learndash' ); ?></h3>
				<ul>
					<li><a href="https://www.learndash.com/support/docs/core/courses/course-builder/" target="_blank" rel="noopener noreferrer">Course Builder [Article]</a></li>
					<li><a href="https://www.learndash.com/support/docs/core/quizzes/question-types/" target="_blank" rel="noopener noreferrer">Quiz Question Types [Article]</a></li>
					<li><a href="https://www.learndash.com/support/docs/core/quizzes/" target="_blank" rel="noopener noreferrer">Questions Documentation</a></li>
				</ul>
			</div>
		</div>
	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
