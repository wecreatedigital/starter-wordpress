<?php
/**
 * Quiz Metaboxes.
 *
 * Introduces metaboxes at Add/Edit Quiz page to be used as
 * a wrapper by the React application at front-end.
 *
 * @package LearnDash
 */

namespace LearnDash\Quiz\Metaboxes;

/**
 * Add the metaboxes to Quiz post type.
 *
 * @return void
 */
function add_meta_boxes() {

	$screen = get_current_screen();

	if ( 'sfwd-quiz' !== get_post_type( get_the_ID() ) &&
		'sfwd-quiz_page_quizzes-builder' !== $screen->id  ) {
		return;
	}

	add_meta_box(
		'sfwd-quiz-questions',
		sprintf( '%s', \LearnDash_Custom_Label::get_label( 'questions' ) ),
		'LearnDash\Quiz\Metaboxes\meta_box_questions_callback',
		null,
		'side'
	);

/*
	add_meta_box(
		'learndash_admin_quiz_navigation',
		sprintf(
			// translators: placeholders: Quiz, Questions.
			esc_html_x( '%1$s %2$s', 'placeholders: Quiz, Questions', 'learndash' ),
			\LearnDash_Custom_Label::get_label( 'quiz' ), \LearnDash_Custom_Label::get_label( 'questions' )
		),
		'learndash_quiz_navigation_admin_box_content',
		null,
		'side'
	);
*/
}
add_action( 'add_meta_boxes_sfwd-quiz', 'LearnDash\Quiz\Metaboxes\add_meta_boxes' );
add_action( 'learndash_add_meta_boxes', 'LearnDash\Quiz\Metaboxes\add_meta_boxes' );

/**
 * Callback to render questions metabox.
 *
 * @return void
 */
function meta_box_questions_callback() {
	?>
	<div id="sfwd-questions-app"></div>
	<?php
}
