<?php
/**
 * Course Metaboxes.
 *
 * Introduces metaboxes at Add/Edit Course page to be used as
 * a wrapper by the React application at front-end.
 *
 * @package LearnDash
 */

namespace LearnDash\Course\Metaboxes;

/**
 * Add the metaboxes to course post type.
 *
 * @return void
 */
function add_meta_boxes() {

	$screen = get_current_screen();

	if ( 'sfwd-courses' !== get_post_type( get_the_ID() ) &&
		'sfwd-courses_page_courses-builder' !== $screen->id ) {
		return;
	}

	add_meta_box(
		'sfwd-course-lessons',
		sprintf( '%s', \LearnDash_Custom_Label::get_label( 'lessons' ) ),
		'LearnDash\Course\Metaboxes\meta_box_lessons_callback',
		null,
		'side'
	);

	add_meta_box(
		'sfwd-course-topics',
		sprintf( '%s', \LearnDash_Custom_Label::get_label( 'topics' ) ),
		'LearnDash\Course\Metaboxes\meta_box_topics_callback',
		null,
		'side'
	);

	add_meta_box(
		'sfwd-course-quizzes',
		sprintf( '%s', \LearnDash_Custom_Label::get_label( 'quizzes' ) ),
		'LearnDash\Course\Metaboxes\meta_box_quizzes_callback',
		null,
		'side'
	);

}
add_action( 'add_meta_boxes_sfwd-courses', 'LearnDash\Course\Metaboxes\add_meta_boxes' );
add_action( 'learndash_add_meta_boxes', 'LearnDash\Course\Metaboxes\add_meta_boxes' );

/**
 * Callback to render lessons metabox.
 *
 * @return void
 */
function meta_box_lessons_callback() {
	?>
	<div id="sfwd-lessons-app"></div>
	<?php
}

/**
 * Callback to render topics metabox.
 *
 * @return void
 */
function meta_box_topics_callback() {
	?>
	<div id="sfwd-topics-app"></div>
	<?php
}

/**
 * Callback to render quizzes metabox.
 *
 * @return void
 */
function meta_box_quizzes_callback() {
	?>
	<div id="sfwd-quizzes-app"></div>
	<?php
}
