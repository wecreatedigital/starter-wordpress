<?php
/**
 * This file contains the code that displays the quiz navigation admin.
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Quiz
 */

?>
<?php
global $pagenow;
global $typenow;
global $quiz_navigation_admin_pager;

if ( ( isset( $quiz_id ) ) && ( ! empty( $quiz_id ) ) ) {

	if ( ! isset( $widget ) ) {
		$widget = array(
			'show_widget_wrapper' => true,
			'current_question_id' => 0,
		);
	}

	$widget_json = htmlspecialchars( json_encode( $widget ) );

	if ( ( isset( $widget['show_widget_wrapper'] ) ) && ( $widget['show_widget_wrapper'] == 'true' ) ) { ?>
		<div id="quiz_navigation-<?php echo $quiz_id ?>" class="quiz_navigation quiz_navigation_app" data-widget_instance="<?php echo $widget_json; ?>">
	<?php } ?>

	<div class="learndash_navigation_questions_list">
	<?php
	
	if ( ( isset( $questions_list ) ) && ( ! empty( $questions_list ) ) ) {

		$question_label_idx = 1;
		if ( ( isset( $quiz_navigation_admin_pager ) ) && ( ! empty( $quiz_navigation_admin_pager ) ) ) {
			if ( ( isset( $quiz_navigation_admin_pager['paged'] ) ) && ( $quiz_navigation_admin_pager['paged'] > 1 ) ) {
				$question_label_idx = ( absint( $quiz_navigation_admin_pager['paged'] ) - 1 ) * $quiz_navigation_admin_pager['per_page'] + 1;
			}
		}

		?><ul class="learndash-quiz-questions" class="ld-question-overview-widget-list learndash-quiz-questions-<?php echo absint( $quiz_id ); ?>"><?php
		foreach ( $questions_list as $q_post_id => $q_pro_id ) {
			if ( absint( $q_post_id ) === absint( $widget['current_question_id'] ) ) {
				$selected_class = 'ld-question-overview-widget-item-current';
			} else {
				$selected_class = '';
			}
			$question_edit_link = get_edit_post_link( $q_post_id );
			$question_edit_link = add_query_arg('quiz_id', $quiz_id, $question_edit_link );

			?><li class="learndash-quiz-question-item ld-question-overview-widget-item <?php echo $selected_class; ?>"></span> <a href="<?php echo $question_edit_link; ?>"><?php echo get_the_title( $q_post_id ); ?></a></li><?php
			$question_label_idx += 1;
		}
		?></ul><?php
	}
	if ( ( isset( $quiz_navigation_admin_pager ) ) && ( ! empty( $quiz_navigation_admin_pager ) ) ) {
		echo SFWD_LMS::get_template(
			'learndash_pager.php',
			array(
				'pager_results' => $quiz_navigation_admin_pager,
				'pager_context' => 'quiz_navigation_admin'
			)
		);
	}
	?>
	<a href="<?php echo add_query_arg( 'currentTab', 'learndash_quiz_builder', get_edit_post_link( $quiz_id ) ); ?>" class="ld-question-overview-widget-add"><?php echo sprintf(
		// translators: placeholder: Questions.
		esc_html_x( 'Manage %s in builder', 'placeholder: Questions', 'learndash' ),
		learndash_get_custom_label( 'questions' )
		); ?></a>
	<?php
	?></div><?php
	if ( ( isset( $widget['show_widget_wrapper'] ) ) && ( $widget['show_widget_wrapper'] == 'true' ) ) { ?>
		</div> <!-- Closing <div id='course_navigation'> -->

		<?php
		if ( ( isset( $quiz_navigation_admin_pager ) ) && ( ! empty( $quiz_navigation_admin_pager ) ) ) {
			?>
			<script>
				jQuery(document).ready(function() {
					jQuery('#learndash_admin_quiz_navigation h2.hndle span.questions-count').html('('+<?php echo $quiz_navigation_admin_pager['total_items'] ?>+')');
				});
			</script>
			<?php
		}
		?>
	<?php } ?>
	<?php
}

