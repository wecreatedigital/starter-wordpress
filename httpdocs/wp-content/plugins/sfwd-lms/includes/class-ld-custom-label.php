<?php
/**
* LearnDash Custom Label class.
*/
class LearnDash_Custom_Label {
	/**
	 * Construct
	 */
	public function __construct() {
	}

	/**
	 * Get label based on key name
	 *
	 * @param  string $key Key name of setting field.
	 * @return string Label entered on settings page.
	 */
	public static function get_label( $key ) {
		$labels = array();
		$key    = strtolower( $key );

		// The Setting logic for custom labels moved to includes/settings/class-ld-settings-section-custom-labels.php as of V2.4.
		$labels[ $key ] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Custom_Labels', $key );

		switch ( $key ) {
			case 'course':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Course', 'learndash' );
				break;

			case 'courses':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Courses', 'learndash' );
				break;

			case 'lesson':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Lesson', 'learndash' );
				break;

			case 'lessons':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Lessons', 'learndash' );
				break;

			case 'topic':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Topic', 'learndash' );
				break;

			case 'topics':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Topics', 'learndash' );
				break;

			case 'quiz':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Quiz', 'learndash' );
				break;

			case 'quizzes':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Quizzes', 'learndash' );
				break;

			case 'question':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Question', 'learndash' );
				break;

			case 'questions':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Questions', 'learndash' );
				break;

			case 'button_take_this_course':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Take this Course', 'learndash' );
				break;

			case 'button_mark_complete':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Mark Complete', 'learndash' );
				break;

			case 'button_click_here_to_continue':
				$label = ! empty( $labels[ $key ] ) ? $labels[ $key ] : esc_html__( 'Click Here to Continue', 'learndash' );
				break;

			default:
				$label = '';
		}

		return apply_filters( 'learndash_get_label', $label, $key );
	}

	/**
	 * Get slug-ready string
	 *
	 * @param  string $key Key name of setting field.
	 * @return string      Lowercase string
	 */
	public static function label_to_lower( $key ) {
		$label = strtolower( self::get_label( $key ) );
		return apply_filters( 'learndash_label_to_lower', $label, $key );
	}

	/**
	 * Get slug-ready string
	 *
	 * @param  string $key Key name of setting field.
	 * @return string      Slug-ready string
	 */
	public static function label_to_slug( $key ) {
		$label = sanitize_title( self::get_label( $key ) );
		return apply_filters( 'label_to_slug', $label, $key );
	}
}

add_action(
	'plugins_loaded',
	function() {
		new LearnDash_Custom_Label();
	}
);

/**
 * Utility function to get a custom field label.
 *
 * @since 2.6.0
 * @param string $field Field label to retreive.
 * @return string Field label. Empty of none found.
 */
function learndash_get_custom_label( $field = '' ) {
	return LearnDash_Custom_Label::get_label( $field );
}

/**
 * Utility function to get a custom field label lowercase.
 *
 * @since 2.6.0
 * @param string $field Field label to retreive.
 * @return string Field label. Empty of none found.
 */
function learndash_get_custom_label_lower( $field = '' ) {
	return LearnDash_Custom_Label::label_to_lower( $field );
}

/**
 * Utility function to get a custom field label slug.
 *
 * @since 2.6.0
 * @param string $field Field label to retreive.
 * @return string Field label. Empty of none found.
 */
function learndash_get_custom_label_slug( $field = '' ) {
	return LearnDash_Custom_Label::label_to_slug( $field );
}

/**
 * Get Course Step "Back to ..." label.
 * 
 * @since 3.0.7
 * @param string $step_post_type The post_type slug of the post to return label for.
 * @param boolean $plural True if the label should be the plural label. Default is false for single.
 * @return string label
 */
function learndash_get_label_course_step_back( $step_post_type = 0, $plural = false ) {
	$step_label = '';

	$post_type_object = get_post_type_object( $step_post_type );
	if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {
		switch( $step_post_type ) {
			case learndash_get_post_type_slug( 'course' ):
				if ( true === $plural ) {
					// translators: placeholder: Courses.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Courses', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Course.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Course', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;

			case learndash_get_post_type_slug( 'lesson' ):
				if ( true === $plural ) {
					// translators: placeholder: Lessons.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Lessons', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Lesson.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Lesson', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;

			case learndash_get_post_type_slug( 'topic' ):
				if ( true === $plural ) {
					// translators: placeholder: Topics.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Topics', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Topic.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Topic', 'learndash' ),
						$post_type_object->labels->singular_name
					);

				}
				break;

			case learndash_get_post_type_slug( 'quiz' ):
				if ( true === $plural ) {
					// translators: placeholder: Quizzes.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Quizzes', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Quiz.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Quiz', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;

			case learndash_get_post_type_slug( 'question' ):
				if ( true === $plural ) {
					// translators: placeholder: Questions.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Questions', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Question.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Question', 'learndash' ),
						$post_type_object->labels->singular_name
					);

				}
				break;

			case learndash_get_post_type_slug( 'transaction' ):
				if ( true === $plural ) {
					// translators: placeholder: Transactions.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Transactions', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Transaction.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Transaction', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;

			case learndash_get_post_type_slug( 'group' ):
				if ( true === $plural ) {
					// translators: placeholder: Groups.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Groups', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Group.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Group', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;

			case learndash_get_post_type_slug( 'assignment' ):
				if ( true === $plural ) {
					// translators: placeholder: Assignments.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Assignments', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Assignment.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Assignment', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;

			case learndash_get_post_type_slug( 'essay' ):
				if ( true === $plural ) {
					// translators: placeholder: Essays.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Essays', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Essay.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Essay', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;

			case learndash_get_post_type_slug( 'certificate' ):
				if ( true === $plural ) {
					// translators: placeholder: Certificates.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Certificates', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Certificate.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Certificate', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;

			default:
				if ( true === $plural ) {
					// translators: placeholder: Post Type Plural label.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Post Type Plural label', 'learndash' ),
						$post_type_object->labels->name
					);
				} else {
					// translators: placeholder: Post Type Singular label.
					$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Post Type Singular label', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}
				break;	
		}
	} else {
		// translators: placeholder: Post Type slug.
		$step_label = sprintf( esc_html_x( 'Back to %s', 'placeholder: Post Type slug', 'learndash' ),
			$step_post_type
		);
	}

	return apply_filters( 'learndash_get_label_course_step_back' , $step_label, $step_post_type, $plural );
}

/**
 * Get Course Step "Previous ..." label.
 * 
 * @since 3.0.7
 * @param string $step_post_type The post_type slug of the post to return label for.
 * @return string label
 */
function learndash_get_label_course_step_previous( $step_post_type = 0 ) {
	$step_label = '';
	
	$post_type_object = get_post_type_object( $step_post_type );
	if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {	
		switch( $step_post_type ) {
			case learndash_get_post_type_slug( 'course' ):
				// translators: placeholder: Course.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Course', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'lesson' ):
				// translators: placeholder: Lesson.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Lesson', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'topic' ):
				// translators: placeholder: Topic.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Topic', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'quiz' ):
				// translators: placeholder: Quiz.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Quiz', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'question' ):
				// translators: placeholder: Question.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Question', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'transaction' ):
				// translators: placeholder: Transaction.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Transaction', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'group' ):
				// translators: placeholder: Group.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Group', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'assignment' ):
				// translators: placeholder: Assignment.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Assignment', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'essay' ):
				// translators: placeholder: Essay.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Essay', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'certificate' ):
				// translators: placeholder: Certificate.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Certificate', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;
	
			default:
				// translators: placeholder: Post Type Singular label.
				$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Post Type Singular label', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;	
		}
	} else {
		// translators: placeholder: Post Type slug.
		$step_label = sprintf( esc_html_x( 'Previous %s', 'placeholder: Post Type slug', 'learndash' ),
			$step_post_type
		);
	}

	return apply_filters( 'learndash_get_label_course_step_previous', $step_label, $step_post_type );
}

/**
 * Get Course Step "Next ..." label.
 * 
 * @since 3.0.7
 * @param string $step_post_type The post_type slug of the post to return label for.
 * @return string label
 */
function learndash_get_label_course_step_next( $step_post_type = 0 ) {
	$step_label = '';

	$post_type_object = get_post_type_object( $step_post_type );
	if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {
		switch( $step_post_type ) {
			case learndash_get_post_type_slug( 'course' ):
				// translators: placeholder: Course.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Course', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'lesson' ):
				// translators: placeholder: Lesson.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Lesson', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'topic' ):
				// translators: placeholder: Topic.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Topic', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'quiz' ):
				// translators: placeholder: Quiz.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Quiz', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'question' ):
				// translators: placeholder: Question.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Question', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'transaction' ):
				// translators: placeholder: Transaction.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Transaction', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'group' ):
				// translators: placeholder: Group.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Group', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'assignment' ):
				// translators: placeholder: Assignment.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Assignment', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'essay' ):
				// translators: placeholder: Essay.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Essay', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'certificate' ):
				// translators: placeholder: Certificate.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Certificate', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			default:
				// translators: placeholder: Post Type Singular label.
				$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Post Type Singular label', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;	
		}
	} else {
		// translators: placeholder: Post Type slug.
		$step_label = sprintf( esc_html_x( 'Next %s', 'placeholder: Post Type slug', 'learndash' ),
			$step_post_type
		);
	}

	return apply_filters( 'learndash_get_label_course_step_next' , $step_label, $step_post_type );
}

/**
 * Get Course Step "... Page" label.
 * 
 * This is used on the Admin are when editing a post type. There is a return link in the top-left.
 * @since 3.0.7
 * @param string $step_post_type The post_type slug of the post to return label for.
 * @return string label
 */
function learndash_get_label_course_step_page( $step_post_type = 0 ) {
	$step_label = '';

	$post_type_object = get_post_type_object( $step_post_type );
	if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {
		switch( $step_post_type ) {
			case learndash_get_post_type_slug( 'course' ):
				// translators: placeholder: Course.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Course', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'lesson' ):
				// translators: placeholder: Lesson.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Lesson', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'topic' ):
				// translators: placeholder: Topic.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Topic', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'quiz' ):
				// translators: placeholder: Quiz.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Quiz', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'question' ):
				// translators: placeholder: Question.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Question', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'transaction' ):
				// translators: placeholder: Transaction.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Transaction', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'group' ):
				// translators: placeholder: Group.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Group', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'assignment' ):
				// translators: placeholder: Assignment.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Assignment', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'essay' ):
				// translators: placeholder: Essay.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Essay', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;

			case learndash_get_post_type_slug( 'certificate' ):
				// translators: placeholder: Certificate.
				$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Certificate', 'learndash' ),
					$post_type_object->labels->singular_name
				);
				break;
	
			default:
				$post_type_object = get_post_type_object( $step_post_type );
				if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {
					// translators: placeholder: Post Type Singular label.
					$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Post Type Singular label', 'learndash' ),
						$post_type_object->labels->singular_name
					);
				}

				break;	
		}
	} else {
		// translators: placeholder: Post Type slug.
		$step_label = sprintf( esc_html_x( '%s page', 'placeholder: Post Type slug', 'learndash' ),
			$step_post_type
		);
	}

	return apply_filters( 'learndash_get_label_course_step_page' , $step_label, $step_post_type );
}
