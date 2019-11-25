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
		<h2>
		<?php
			echo esc_html_x( 'You don\'t have any Groups yet', 'Placeholder text when no Groups exist', 'learndash' );
		?>
		</h2>
		<p>
		<?php
			echo esc_html__( 'Users can be placed into Groups and assigned a Group Leader who can track the progress and performance of any user in the Group.', 'learndash' );
		?>
		</p>
		<a href="<?php echo admin_url('post-new.php?post_type='. learndash_get_post_type_slug( 'group' ) ); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php
				echo esc_html_x( 'Add your first Group', 'Button text to create a new group', 'learndash' );
			?>
		</a>
	</div> <!-- .ld-onboarding-main -->

	<div class="ld-onboarding-more-help">
		<div class="ld-onboarding-row">
		<div class="ld-onboarding-col">
				<h3>
				<?php
					echo esc_html_x( 'Creating a Group', 'Titel of tutorial video', 'learndash' );
				?>
				</h3>
				<img src="<?php echo LEARNDASH_LMS_PLUGIN_URL; ?>assets/images/post-type-empty-state.jpg" alt="" />
			</div>
			<div class="ld-onboarding-col">
				<h3><?php esc_html_e( 'Related help and documentation', 'learndash' ); ?></h3>
				<ul>
					<li><a href="https://www.learndash.com/support/docs/users-groups/" target="_blank" rel="noopener noreferrer">Users & Groups Documentation</a></li>
				</ul>
			</div>
		</div>

	</div> <!-- .ld-onboarding-more-help -->

</section> <!-- .ld-onboarding-screen -->
