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
			// translators: placeholder: Topics.
			esc_html_x( 'You don\'t have any %s yet', 'placeholder: Topics', 'learndash' ),
			\LearnDash_Custom_Label::get_label( 'topics' )
		); ?></h2>
		<p>
		<?php
				printf(
					// translators: placeholder: %1$s: Lessons, %2$s: Course, %3$s: Topics, %4$s: Topics, %5$s: Lesson
					esc_html_x( 'When you have %1$s in your %2$s, you can break them up into separate %3$s. You can add %4$s using the Course Builder, or you can create them individually and assign them to a %5$s later.', 'placeholder: %1$s: Lessons, %2$s: Course, %3$s: Topics, %4$s: Topics, %5$s: Lesson', 'learndash' ),
					\LearnDash_Custom_Label::get_label( 'lessons' ),
					\LearnDash_Custom_Label::get_label( 'course' ),
					\LearnDash_Custom_Label::get_label( 'topics' ),
					\LearnDash_Custom_Label::get_label( 'topics' ),
					\LearnDash_Custom_Label::get_label( 'lesson' )
				);
		?>
		</p>
		<a href="<?php echo admin_url('post-new.php?post_type=sfwd-topic'); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php printf(
				// translators: placeholder: Topic.
				esc_html_x( 'Add your first %s', 'placeholder: Topic', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'topic' )
			); ?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
			<div class="ld-onboarding-col">
				<h3>
				<?php
				printf(
					// translators: placeholder: %1$s: Topics
					esc_html_x( 'Creating %1$s', 'placeholder: %1$s: Topics', 'learndash'),
					\LearnDash_Custom_Label::get_label( 'topics' )
				);
				?>
				</h3>
				<div class="ld-bootcamp__embed">
					<iframe width="560" height="315" src="https://www.youtube.com/embed/PD1KKzdakHw" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
			</div>
			<div class="ld-onboarding-col">
				<h3><?php esc_html_e( 'Related help and documentation', 'learndash' ); ?></h3>
					<ul>
						<li><a href="https://www.learndash.com/support/docs/core/courses/course-builder/" target="_blank" rel="noopener noreferrer">Course Builder [Article]</a></li>
						<li><a href="https://www.learndash.com/support/docs/core/topics/" target="_blank" rel="noopener noreferrer">Topics Documentation</a></li>
					</ul>

			</div>
		</div>
	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
