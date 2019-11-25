<?php
/**
 * Course Progress Functions
 *
 * @since 2.1.0
 *
 * @package LearnDash\Course
 */

/**
 * Output HTML output to mark a course complete
 *
 * Must meet requirements of course
 *
 * @since 2.1.0
 *
 * @param  object $post WP_Post lesson, topic.
 * @return string HTML output to mark course complete
 */
function learndash_mark_complete( $post, $atts = array() ) {

	if ( ! is_user_logged_in() ) {
		return '';
	}
	
	$user_id = get_current_user_id();

	if ( isset( $_POST['sfwd_mark_complete'] ) && isset( $_POST['post'] ) && $post->ID == intval( $_POST['post'] ) ) {
		return '';
	}

	$bypass_course_limits_admin_users = false;


	if ( learndash_is_admin_user( $user_id ) ) {
		$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );
		if ( 'yes' === $bypass_course_limits_admin_users ) {
			$bypass_course_limits_admin_users = true;
		} else {
			$bypass_course_limits_admin_users = false;
		}
	} else {
		$bypass_course_limits_admin_users = false;
	}

	// For logged in users to allow an override filter.
	$bypass_course_limits_admin_users = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $user_id, $post->ID, $post );

	$course_id = learndash_get_course_id( $post->ID );

	if ( ( learndash_lesson_progression_enabled() ) && ( ! $bypass_course_limits_admin_users ) ) {

		if ( 'sfwd-lessons' === $post->post_type ) {
			$progress = learndash_get_course_progress( null, $post->ID );

			if ( ! empty( $progress['this']->completed ) ) {
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['this']->ID, $user_id ) ) {
					return '';
				}
			}

			if ( ! empty( $progress['prev'] ) && empty( $progress['prev']->completed ) && learndash_lesson_progression_enabled() ) {
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['prev']->ID, $user_id ) ) {
					return '';
				}
			}

			if ( ! learndash_lesson_topics_completed( $post->ID ) ) {
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $post->ID, $user_id ) ) {
					return '';
				}
			}
		}

		if ( 'sfwd-topic' === $post->post_type ) {
			$progress = learndash_get_course_progress( null, $post->ID );

			if ( ! empty( $progress['this']->completed ) ) {
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['this']->ID, $user_id ) ) {
					return '';
				}
			}

			if ( ! empty( $progress['prev'] ) && empty( $progress['prev']->completed ) && learndash_lesson_progression_enabled() ) {
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['prev']->ID, $user_id ) ) {
					return '';
				}
			}

			if ( learndash_lesson_progression_enabled() ) {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					// $course_id = learndash_get_course_id( $post->ID );
					$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
				} else {
					$lesson_id = learndash_get_setting( $post, 'lesson' );
				}
				$lesson = get_post( $lesson_id );

				if ( ! is_previous_complete( $lesson ) ) {
					if ( ! apply_filters( 'learndash_previous_step_completed', false, $lesson->ID, $user_id ) ) {
						return '';
					}
				}
			}
		}
	} else {
		$progress = learndash_get_course_progress( null, $post->ID );

		if ( ! empty( $progress['this']->completed ) ) {
			return '';
		}
	}

	if ( lesson_hasassignments( $post ) ) {
		global $learndash_assignment_upload_message;

		$ret = SFWD_LMS::get_template(
			'learndash_lesson_assignment_upload_form.php',
			array(
				'course_step_post'                => $post,
				'user_id'                         => $user_id,
				'assignment_upload_error_message' => $learndash_assignment_upload_message,
			)
		);

		return $ret;

	} else {
		$return          = '';
		$button_disabled = '';
		$time            = 0;
		$timeval         = learndash_forced_lesson_time( $post );

		if ( ! empty( $timeval ) ) {
			$time = learndash_convert_lesson_time_time( $timeval );
			/*
			$time_sections = explode( ' ', $timeval );
			$h             = $m = $s = 0;

			foreach ( $time_sections as $k => $v ) {
				$value = trim( $v );

				if ( strpos( $value, 'h' ) ) {
					$h = intVal( $value );
				} elseif ( strpos( $value, 'm' ) ) {
					$m = intVal( $value );
				} elseif ( strpos( $value, 's' ) ) {
					$s = intVal( $value );
				}
			}

			$time = $h * 60 * 60 + $m * 60 + $s;

			if ( $time == 0 ) {
				$time = (int) $timeval;
			}
			*/
		}

		if ( ( ! learndash_is_admin_user( $user_id ) ) || ( ! $bypass_course_limits_admin_users ) ) {

			if ( ! empty( $time ) ) {
				$time_cookie_key = learndash_forced_lesson_time_cookie_key( $post );

				// Set the mark complete button disabled.
				$button_disabled = " disabled='disabled' ";

				wp_enqueue_script(
					'jquery-cookie',
					plugins_url( 'js/jquery.cookie' . leardash_min_asset() . '.js', WPPROQUIZ_FILE ),
					array( 'jquery' ),
					'1.4.0',
					true
				);
				global $learndash_assets_loaded;
				$learndash_assets_loaded['scripts']['jquery-cookie'] = __FUNCTION__;
			}
		}

		/**
		 * Allow the outside world to filter the form/button atts array
		 *
		 * @since 3.0
		 * @param array $atts Arry of form, button, and timer attributes to override id and class.
		 * @param object $post WP_Post object being displayed.
		 */
		$atts = apply_filters( 'learndash_mark_complete_form_atts', $atts, $post );

		if ( isset( $atts['form']['id'] ) ) {
			$form_id = ' id="' . esc_attr( $atts['form']['id'] ) . '" ';
		} else {
			$form_id = '';
		}

		if ( isset( $atts['form']['class'] ) ) {
			$form_class = ' class="sfwd-mark-complete ' . esc_attr( $atts['form']['class'] ) . '" ';
		} else {
			$form_class = ' class="sfwd-mark-complete" ';
		}

		if ( isset( $atts['button']['id'] ) ) {
			$button_id = ' id="' . esc_attr( $atts['button']['id'] ) . '" ';
		} else {
			$button_id = '';
		}

		if ( isset( $atts['button']['class'] ) ) {
			$button_class = ' class="learndash_mark_complete_button ' . esc_attr( $atts['button']['class'] ) . '" ';
		} else {
			$button_class = ' class="learndash_mark_complete_button" ';
		}

		$form_fields = '<input type="hidden" value="' . $post->ID . '" name="post" />
					<input type="hidden" value="' . learndash_get_course_id( $post->ID ) . '" name="course_id" />
					<input type="hidden" value="' . wp_create_nonce( 'sfwd_mark_complete_' . get_current_user_id() . '_' . $post->ID ) . '" name="sfwd_mark_complete" />
					<input type="submit" ' . $button_id . ' value="' . esc_html( LearnDash_Custom_Label::get_label( 'button_mark_complete' ) ) . '" ' . $button_class . ' ' . $button_disabled . '/>';
		/**
		 * Allow the outside world to filter the form fields.
		 *
		 * @since 3.0
		 * @param string $form_fields.
		 * @param object $post WP_Post object being displayed.
		 */
		$form_fields = apply_filters( 'learndash_mark_complete_form_fields', $form_fields, $post );

		$return .= '<form ' . $form_id . ' ' . $form_class . ' method="post" action="">' . $form_fields . '</form>';

		if ( ( ! learndash_is_admin_user( $user_id ) ) || ( ! $bypass_course_limits_admin_users ) ) {
			if ( ! empty( $time ) ) {
				if ( isset( $atts['timer']['id'] ) ) {
					$timer_id = ' id="' . esc_attr( $atts['timer']['id'] ) . '" ';
				} else {
					$timer_id = '';
				}

				$timer_class = ' class="learndash_timer';
				if ( isset( $atts['timer']['class'] ) ) {
					$timer_class .= ' ' . esc_attr( $atts['timer']['class'] );
				}
				$timer_class .= '" ';

				$return .= '<span ' . $timer_id . ' ' . $timer_class . ' data-timer-seconds="' . $time . '" data-button="input.learndash_mark_complete_button" data-cookie-key="' . $time_cookie_key . '"></span>';
			}
		}
	}

	/**
	 * Filter HTML output to mark course complete
	 *
	 * @since 2.1.0
	 *
	 * @param  string  $return
	 */
	return apply_filters( 'learndash_mark_complete', $return, $post );
}


function learndash_ajax_mark_complete( $quiz_id = null, $lesson_id = null ) {

	if ( empty( $quiz_id ) || empty( $lesson_id ) ) {
		return;
	}

	global $post;

	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;

	$can_attempt_again = learndash_can_attempt_again( $user_id, $quiz_id );

	if ( $can_attempt_again ) {
		$link = learndash_next_lesson_quiz( false, $user_id, $lesson_id, null );
	} else {
		$link = learndash_next_lesson_quiz( false, $user_id, $lesson_id, array( $quiz_id ) );
	}

}

/**
 * Are topics completed for lesson
 *
 * @since 2.1.0
 *
 * @param  int  $lesson_id Lesson ID.
 * @param  bool $mark_lesson_complete Should we mark lesson complete.
 * @return bool
 */
function learndash_lesson_topics_completed( $lesson_id, $mark_lesson_complete = false ) {
	$topics = learndash_get_topic_list( $lesson_id );

	if ( empty( $topics[0]->ID ) ) {
		return true;
	}

	$progress = learndash_get_course_progress( null, $topics[0]->ID );

	if ( empty( $progress['posts'] ) || ! is_array( $progress['posts'] ) ) {
		return false;
	}

	foreach ( $progress['posts'] as $topic ) {
		if ( empty( $topic->completed ) ) {
			return false;
		}
	}

	if ( $mark_lesson_complete ) {
		$user_id = get_current_user_id();
		learndash_process_mark_complete( null, $lesson_id );
	}

	// learndash_get_next_lesson_redirect(); .
	return true;
}

/**
 * Process request to mark a course complete
 *
 * @since 2.1.0
 *
 * @param  int $post WP_Post object.
 */
function learndash_mark_complete_process( $post = null ) {
	// This is wrong. This function hooks into the 'wp' action. That action doesn't pass a post object or post_id.
	// The $post object set hwere is not even used. We only need the _POST[post] (post_id) variable from the form.
	if ( empty( $post ) ) {
		global $post;
	}

	if ( ( isset( $_POST['sfwd_mark_complete'] ) ) && ( ! empty( $_POST['sfwd_mark_complete'] ) ) && ( isset( $_POST['post'] ) ) && ( ! empty( $_POST['post'] ) ) ) {
		if ( empty( $post ) || empty( $post->ID ) ) {
			$post = get_post();
			if ( empty( $post ) || empty( $post->ID ) ) {
				return;
			}
		}

		$post_id = intval( $_POST['post'] );

		if ( isset( $_POST['course_id'] ) ) {
			$course_id = intval( $_POST['course_id'] );
		} else {
			$course_id = learndash_get_course_id( $post_id );
		}

		if ( isset( $_POST['userid'] ) ) {
			$userid = intval( $_POST['userid'] );
		} else {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$userid = get_current_user_id();
		}

		/**
		 * Verify the form is valid
		 *
		 * @since 2.2.1.2
		 */
		if ( ! wp_verify_nonce( $_POST['sfwd_mark_complete'], 'sfwd_mark_complete_' . $userid . '_' . $post_id ) ) {
			return;
		}

		/**
		 * Added logic to enroll the student in the course if the course price type is open and not a sample.
		 *
		 * @since 2.4.4
		 */
		// $course_id = learndash_get_course_id( $post_id );
		// if ( !empty( $course_id ) ) {
		// $user_course_access_time = get_user_meta( $userid, "course_".$course_id."_access_from", true );
		// if ( empty( $user_course_access_time ) ) {
		// ld_update_course_access( $userid, $course_id );
		// }
		// }
		$return = learndash_process_mark_complete( $userid, $post_id, false, $course_id );

		if ( $return ) {
			// Remove the lesson/topic timer cookie once the step have been completed.
			$timer_cookie_key = learndash_forced_lesson_time_cookie_key( $post_id );
			if ( ! empty( $timer_cookie_key ) ) {
				if ( isset( $_COOKIE[ 'learndash_timer_cookie_' . $timer_cookie_key ] ) ) {
					unset( $_COOKIE[ 'learndash_timer_cookie_' . $timer_cookie_key ] );
				}
				// empty value and expiration one hour before
				$res = setcookie( 'learndash_timer_cookie_' . $timer_cookie_key, '', time() - 3600);
			}

			$nextlessonredirect = learndash_get_next_lesson_redirect();
		} else {
			$nextlessonredirect = get_permalink();
		}

		if ( ! empty( $nextlessonredirect ) ) {

			/**
			 * Filter url to redirect to on next lesson
			 *
			 * @param string $nextlessonredirect
			 */
			$nextlessonredirect = apply_filters( 'learndash_completion_redirect', $nextlessonredirect, $post_id );
			wp_redirect( $nextlessonredirect );
			exit;
		}
	}
}

add_action( 'wp', 'learndash_mark_complete_process' );



/**
 * Get a courses permalink
 *
 * @since 2.1.0
 *
 * @param  int $id     course, topic, lesson, quiz, etc.
 * @return string           course permalink
 */
function learndash_get_course_url( $id = null ) {

	if ( empty( $id ) ) {
		$id = learndash_get_course_id();
	}

	return get_permalink( $id );
}



/**
 * Redirect user to next lesson
 *
 * @since 2.1.0
 *
 * @param  object $post
 */
function learndash_get_next_lesson_redirect( $post = null ) {
	if ( empty( $post->ID ) ) {
		global $post;
	}

	$next = learndash_next_post_link( '', true, $post );

	if ( ! empty( $next ) ) {
		$link = $next;
	} else {
		if ( 'sfwd-topic' === $post->post_type ) {
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$course_id = learndash_get_course_id( $post->ID );
				$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
			} else {
				$lesson_id = learndash_get_setting( $post, 'lesson' );
			}
			$link = get_permalink( $lesson_id );
		} else {
			$course_id = learndash_get_course_id( $post );
			$link      = learndash_next_global_quiz( true, null, $course_id );
		}
	}

	if ( ! empty( $link ) ) {

		/**
		 * Filter where user should be redirected to for next lesson
		 *
		 * @since 2.1.0
		 *
		 * @var $link   redirect url
		 */
		$link = apply_filters( 'learndash_completion_redirect', $link, @$post->ID );
		wp_redirect( $link );
		exit;
	} else {
		return '';
	}
}



/**
 * Redirect user after quiz completion
 *
 * @since 2.1.0
 */
function learndash_quiz_redirect() {
	global $post;

	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;

	if ( ! empty( $_GET['quiz_redirect'] ) && ! empty( $_GET['quiz_id'] ) && ! empty( $_GET['quiz_type'] ) && ! empty( $_GET['course_id'] ) && $_GET['quiz_type'] == 'global' ) {

		$quiz_id           = intval( $_GET['quiz_id'] );
		$can_attempt_again = learndash_can_attempt_again( $user_id, $quiz_id );

		if ( $can_attempt_again ) {
			$link = learndash_next_global_quiz();
		} else {
			$link = learndash_next_global_quiz( true, null, null, array( $quiz_id ) );
		}

		learndash_update_completion( $user_id );

		/**
		 * Filter where user should be redirected
		 *
		 * @since 2.1.0
		 *
		 * @var $link   redirect url
		 */
		$link = apply_filters( 'learndash_completion_redirect', $link, $quiz_id );
		wp_redirect( $link );
		exit;

	} else {

		if ( ! empty( $_GET['quiz_redirect'] ) && ! empty( $_GET['quiz_id'] ) && ! empty( $_GET['quiz_type'] ) && ! empty( $_GET['lesson_id'] ) && $_GET['quiz_type'] == 'lesson' ) {
			$quiz_id   = intval( $_GET['quiz_id'] );
			$lesson_id = intval( $_GET['lesson_id'] );

			// We don't need to check if the quiz can be retaken because the learndash_next_lesson_quiz() function does that for us.
			// if ( $can_attempt_again ) {
			// $link = learndash_next_lesson_quiz( true, $user_id, $lesson_id, null );
			// } else {
			// $link = learndash_next_lesson_quiz( true, $user_id, $lesson_id, array($quiz_id) );
			// }
			$link = learndash_next_lesson_quiz( true, $user_id, $lesson_id, null );
			if ( empty( $link ) ) {
				$link = learndash_next_post_link( '', true );
			}

			if ( empty( $link ) ) {
				$post = get_post( $lesson_id );
				if ( $post->post_type == 'sfwd-topic' ) {
					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
						$course_id = learndash_get_course_id( $post->ID );
						$lesson    = learndash_course_get_single_parent_step( $course_id, $post->ID );
					} else {
						$lesson = learndash_get_setting( $post, 'lesson' );
					}
					$link = get_permalink( $lesson );
				} else {
					$link = learndash_next_global_quiz();
				}
			}

			// v2.3: Removed this and moved to an earlier step
			// learndash_update_completion( $user_id );
			if ( ! empty( $link ) ) {

				/**
				 * Filter where user should be redirected
				 *
				 * @since 2.1.0
				 *
				 * @var $link   redirect url
				 */
				$link = apply_filters( 'learndash_completion_redirect', $link, $quiz_id );
				wp_redirect( $link );
				exit;
			}
		}
	}
}

add_action( 'wp', 'learndash_quiz_redirect' );



/**
 * Can the user attempt the quiz again
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $quiz_id
 * @return bool
 */
function learndash_can_attempt_again( $user_id, $quiz_id ) {
	$quizmeta = get_post_meta( $quiz_id, '_sfwd-quiz', true );

	if ( isset( $quizmeta['sfwd-quiz_repeats'] ) ) {
		$repeats = $quizmeta['sfwd-quiz_repeats'];
	} else {
		$repeats = '';
	}

	/**
	 * Number of repeats for quiz
	 *
	 * @param int $repeats
	 */
	$repeats = apply_filters( 'learndash_allowed_repeats', $repeats, $user_id, $quiz_id );

	if ( $repeats == '' ) {
		return true;
	}

	$quiz_results = get_user_meta( $user_id, '_sfwd-quizzes', true );

	$count = 0;

	if ( ! empty( $quiz_results ) ) {
		foreach ( $quiz_results as $quiz ) {
			if ( $quiz['quiz'] == $quiz_id ) {
				$count++;
			}
		}
	}

	if ( $repeats > $count - 1 ) {
		return true;
	} else {
		return false;
	}
}



/**
 * Is previous topic, lesson complete
 *
 * @since 2.1.0
 *
 * @param  object $post  WP_Post
 * @return bool
 */
function is_previous_complete( $post ) {
	$progress = learndash_get_course_progress( null, $post->ID );

	if ( empty( $progress ) ) {
		return 1;
	}

	if ( ! empty( $progress['prev'] ) && empty( $progress['prev']->completed ) ) {
		return 0;
	} else {
		return 1;
	}
}


/**
 * Returns the previous lesson/topic to be completed.
 *
 * @since 2.2.1.1
 *
 * @param  object $post  WP_Post
 * @return object  WP_Post object
 */
function learndash_get_previous( $post ) {
	$progress = learndash_get_course_progress( null, $post->ID );
	if ( ! empty( $progress['prev'] ) ) {
		return $progress['prev'];
	}
}



/**
 * Update user meta with completion status for any resource
 *
 * @since 2.1.0
 *
 * @param  int     $user_id
 * @param  int     $postid         course, lesson, topic
 * @param  boolean $onlycalculate
 * @return bool                     if user meta was updated
 */
function learndash_process_mark_complete( $user_id = null, $postid = null, $onlycalculate = false, $course_id = 0 ) {
	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		} else {
			return false;
		}
	} else {
		$current_user = get_user_by( 'id', $user_id );
	}

	$post = get_post( $postid );
	if ( ! ( $post instanceof WP_Post ) ) {
		return false;
	}

	if ( ! $onlycalculate ) {

		/**
		 * Filter if this should be marked completed
		 *
		 * @since 2.1.0
		 *
		 * @param  bool
		 */
		$process_completion = apply_filters( 'learndash_process_mark_complete', true, $post, $current_user );

		if ( ! $process_completion ) {
			return false;
		}
	}

	if ( $post->post_type == 'sfwd-topic' ) {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $post->ID );
			}
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post, 'lesson' );
		}
		// $lesson_topics = learndash_get_topic_list( $lesson_id);
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $postid );
	}

	if ( empty( $course_id ) ) {
		return false;
	}

	$lessons = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );

	if ( has_global_quizzes( $postid ) ) {
		$globalquiz = 1;
	} else {
		$globalquiz = 0;
	}

	if ( $globalquiz && is_all_global_quizzes_complete( $user_id, $postid ) ) {
		$globalquizcompleted = 1;
	} else {
		$globalquizcompleted = 0;
	}

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

	if ( ( empty( $course_progress ) ) || ( ! is_array( $course_progress ) ) ) {
		$course_progress = array();
	}

	if ( ( ! isset( $course_progress[ $course_id ] ) ) || ( empty( $course_progress[ $course_id ] ) ) ) {
		$course_progress[ $course_id ] = array(
			'lessons' => array(),
			'topics'  => array(),
		);
	}

	if ( ( ! isset( $course_progress[ $course_id ]['lessons'] ) ) || ( empty( $course_progress[ $course_id ]['lessons'] ) ) ) {
		$course_progress[ $course_id ]['lessons'] = array();
	}

	if ( ( ! isset( $course_progress[ $course_id ]['topics'] ) ) || ( empty( $course_progress[ $course_id ]['topics'] ) ) ) {
		$course_progress[ $course_id ]['topics'] = array();
	}

	if ( $post->post_type == 'sfwd-topic' && empty( $course_progress[ $course_id ]['topics'][ $lesson_id ] ) ) {
		$course_progress[ $course_id ]['topics'][ $lesson_id ] = array();
	}

	$lesson_completed = false;
	$topic_completed  = false;

	if ( ! $onlycalculate && $post->post_type == 'sfwd-lessons' && empty( $course_progress[ $course_id ]['lessons'][ $postid ] ) ) {
		$course_progress[ $course_id ]['lessons'][ $postid ] = 1;
		$lesson_completed                                    = true;
	}

	if ( ! $onlycalculate && $post->post_type == 'sfwd-topic' && empty( $course_progress[ $course_id ]['topics'][ $lesson_id ][ $postid ] ) ) {
		$course_progress[ $course_id ]['topics'][ $lesson_id ][ $postid ] = 1;
		$topic_completed = true;
	}

	$completed_old = isset( $course_progress[ $course_id ]['completed'] ) ? $course_progress[ $course_id ]['completed'] : 0;

	// $course_progress[ $course_id ]['completed'] = count( $course_progress[ $course_id ]['lessons'] ) + $globalquizcompleted;
	$completed = learndash_course_get_completed_steps( $user_id, $course_id, $course_progress[ $course_id ] );

	$course_progress[ $course_id ]['completed'] = $completed;
	// 2016-07-16 v2.3 Changed the logic on the count here. In the previous logic the count of lessons and 1 or 0 for global quiz.
	// $course_progress[ $course_id ]['total'] = count( $lessons ) + $globalquiz;
	// New logic includes lessons and topics.
		$course_progress[ $course_id ]['total'] = learndash_get_course_steps_count( $course_id );

	/**
	 * Track the last post_id (Lesson, Topic, Quiz) seen by user.
	 *
	 * @since 2.1.0
	 */
	$course_progress[ $course_id ]['last_id'] = $post->ID;

	$course_completed_time = time();
	// If course is completed
	if ( ( $course_progress[ $course_id ]['completed'] >= $completed_old ) && ( $course_progress[ $course_id ]['completed'] >= $course_progress[ $course_id ]['total'] ) ) {

		/**
		 * Run actions before course is completed
		 *
		 * @since 2.1.0
		 */
		do_action(
			'learndash_before_course_completed', array(
				'user'     => $current_user,
				'course'   => get_post( $course_id ),
				'progress' => $course_progress,
				'completed_time' => $course_completed_time,
			)
		);
		add_user_meta( $current_user->ID, 'course_completed_' . $course_id, $course_completed_time, true );
	} else {
		delete_user_meta( $current_user->ID, 'course_completed_' . $course_id );
	}

	update_user_meta( $user_id, '_sfwd-course_progress', $course_progress );

	if ( ! empty( $topic_completed ) ) {

		/**
		 * Run actions after topic is completed
		 *
		 * @since 2.1.0
		 */
		do_action(
			'learndash_topic_completed', array(
				'user'     => $current_user,
				'course'   => get_post( $course_id ),
				'lesson'   => get_post( $lesson_id ),
				'topic'    => $post,
				'progress' => $course_progress,
			)
		);

		learndash_update_user_activity(
			array(
				'course_id'          => $course_id,
				'user_id'            => $current_user->ID,
				'post_id'            => $post->ID,
				'activity_type'      => 'topic',
				'activity_status'    => true,
				'activity_completed' => time(),
				'activity_meta'      => array(
					'steps_total'     => $course_progress[ $course_id ]['total'],
					'steps_completed' => $course_progress[ $course_id ]['completed'],
				),

			)
		);

		$course_args     = array(
			'course_id'     => $course_id,
			'user_id'       => $current_user->ID,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);
		$course_activity = learndash_get_user_activity( $course_args );
		if ( ! $course_activity ) {
			learndash_update_user_activity(
				array(
					'course_id'       => $course_id,
					'user_id'         => $current_user->ID,
					'post_id'         => $course_id,
					'activity_type'   => 'course',
					'activity_status' => false,
					'activity_meta'   => array(
						'steps_total'     => $course_progress[ $course_id ]['total'],
						'steps_completed' => $course_progress[ $course_id ]['completed'],
						'steps_last_id'   => $post->ID,
					),
				)
			);
		} else {
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_total', $course_progress[ $course_id ]['total'] );
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_completed', $course_progress[ $course_id ]['completed'] );
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_last_id', $post->ID );
		}
	}

	if ( ! empty( $lesson_completed ) ) {

		/**
		 * Run actions lesson is completed
		 *
		 * @since 2.1.0
		 */
		do_action(
			'learndash_lesson_completed', array(
				'user'     => $current_user,
				'course'   => get_post( $course_id ),
				'lesson'   => $post,
				'progress' => $course_progress,
			)
		);

		learndash_update_user_activity(
			array(
				'course_id'          => $course_id,
				'user_id'            => $current_user->ID,
				'post_id'            => $post->ID,
				'activity_type'      => 'lesson',
				'activity_status'    => true,
				'activity_completed' => time(),
				'activity_meta'      => array(
					'steps_total'     => $course_progress[ $course_id ]['total'],
					'steps_completed' => $course_progress[ $course_id ]['completed'],
				),

			)
		);

		$course_args     = array(
			'course_id'     => $course_id,
			'user_id'       => $current_user->ID,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);
		$course_activity = learndash_get_user_activity( $course_args );
		if ( ! $course_activity ) {

			learndash_update_user_activity(
				array(
					'course_id'       => $course_id,
					'user_id'         => $current_user->ID,
					'post_id'         => $course_id,
					'activity_type'   => 'course',
					'activity_status' => false,
					'activity_meta'   => array(
						'steps_total'     => $course_progress[ $course_id ]['total'],
						'steps_completed' => $course_progress[ $course_id ]['completed'],
						'steps_last_id'   => $post->ID,
					),
				)
			);
		} else {
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_total', $course_progress[ $course_id ]['total'] );
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_completed', $course_progress[ $course_id ]['completed'] );
			learndash_update_user_activity_meta( $course_activity->activity_id, 'steps_last_id', $post->ID );
		}
	}

	if ( $course_progress[ $course_id ]['completed'] >= $completed_old && $course_progress[ $course_id ]['total'] == $course_progress[ $course_id ]['completed'] ) {
		$do_course_complete_action = false;

		$course_args = array(
			'course_id'     => $course_id,
			'user_id'       => $current_user->ID,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);

		$course_activity = learndash_get_user_activity( $course_args );
		if ( ! empty( $course_activity ) ) {
			$course_args = json_decode( json_encode( $course_activity ), true );

			if ( $course_activity->activity_status != true ) {
				$course_args['activity_status']    = true;
				$course_args['activity_completed'] = time();
				$course_args['activity_updated']   = time();

				$do_course_complete_action = true;
			}
		} else {
			// If no activity record found.
			$course_args['activity_status']    = true;
			$course_args['activity_started']   = time();
			$course_args['activity_completed'] = time();
			$course_args['activity_updated']   = time();

			$do_course_complete_action = true;
		}

		$course_args['activity_meta'] = array(
			'steps_total'     => $course_progress[ $course_id ]['total'],
			'steps_completed' => $course_progress[ $course_id ]['completed'],
			'steps_last_id'   => $post->ID,
		);

		learndash_update_user_activity( $course_args );

		if ( $do_course_complete_action == true ) {

			/**
			 * Run actions after course is completed
			 *
			 * @since 2.1.0
			 */
			do_action(
				'learndash_course_completed', array(
					'user'     => $current_user,
					'course'   => get_post( $course_id ),
					'progress' => $course_progress,
					'course_completed' => $course_completed_time,
				)
			);
		}
	} else {

		$course_args     = array(
			'course_id'     => $course_id,
			'user_id'       => $current_user->ID,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);
		$course_activity = learndash_get_user_activity( $course_args );
		if ( $course_activity ) {
			$course_args['activity_completed'] = 0;
			$course_args['activity_status']    = false;

			if ( empty( $course_progress[ $course_id ]['completed'] ) ) {
				$course_args['activity_updated'] = 0;
			}
			$course_args['activity_meta'] = array(
				'steps_total'     => $course_progress[ $course_id ]['total'],
				'steps_completed' => $course_progress[ $course_id ]['completed'],
				'steps_last_id'   => $post->ID,
			);
			learndash_update_user_activity( $course_args );
		}
	}

	return true;

}



/**
 * Helper to update completion resource
 *
 * @todo  seems redundant, function already exists
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $postid
 * @return bool if user meta was updated
 */
function learndash_update_completion( $user_id = null, $postid = null ) {
	if ( empty( $postid ) ) {
		global $post;
		if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) ) {
			$postid = $post->ID;
		}
	}

	if ( ! empty( $postid ) ) {
		learndash_process_mark_complete( $user_id, $postid, true );
	}
}



/**
 * Is quiz complete
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $quiz_id
 * @return bool
 */
function learndash_is_quiz_complete( $user_id = null, $quiz_id, $course_id = 0 ) {
	return ! learndash_is_quiz_notcomplete( $user_id, array( $quiz_id => 1 ), false, $course_id );
}



/**
 * Is quiz not complete
 *
 * Checks against quizzes in user meta and passing percentage of the quiz itself
 *
 * @since 2.1.0
 *
 * @param int     $user_id User ID for quizzes.
 * @param array   $quizes Quiz ID to search user quizzes.
 * @param bool    $return_incomplete_quiz_ids if true will return the array of incomplete quizes. Default is false ( added v2.3.1 ).
 * @param integer $course_id match course id. If -1 is passed then course match is not performed.
 *
 * @return bool     true is quiz(es) NOT complete. false is quiz(es) all complete
 */
function learndash_is_quiz_notcomplete( $user_id = null, $quizes = null, $return_incomplete_quiz_ids = false, $course_id = 0 ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	$quiz_results = get_user_meta( $user_id, '_sfwd-quizzes', true );

	if ( ! empty( $quiz_results ) && is_array( $quiz_results ) ) {
		foreach ( $quiz_results as $quiz ) {
			if ( ! empty( $quizes[ $quiz['quiz'] ] ) ) {
				if ( ( -1 !== $course_id ) && ( empty( $course_id ) ) ) {
					$course_id = learndash_get_course_id( intval( $quiz['quiz'] ) );
				}

				if ( ! isset( $quiz['course'] ) ) {
					$quiz['course'] = $course_id;
				}

				$quiz['course'] = intval( $quiz['course'] );
				if ( -1 !== $course_id ) {
					$course_id = intval( $course_id );
				}

				$pass = false;

				if ( ( -1 === $course_id ) || ( $course_id == $quiz['course'] ) ) {
					if ( isset( $quiz['pass'] ) ) {
						$pass = ( $quiz['pass'] == 1 ) ? 1 : 0;
					} else {
						$quizmeta          = get_post_meta( $quiz['quiz'], '_sfwd-quiz', true );
						$passingpercentage = intVal( $quizmeta['sfwd-quiz_passingpercentage'] );
						$pass              = ( ! empty( $quiz['count'] ) && $quiz['score'] * 100 / $quiz['count'] >= $passingpercentage ) ? 1 : 0;
					}
				}

				if ( $pass ) {
					unset( $quizes[ $quiz['quiz'] ] );
				}
			}
		}
	}

	if ( empty( $quizes ) ) {
		return 0;
	} else {
		if ( $return_incomplete_quiz_ids == true ) {
			return $quizes;
		} else {
			return 1;
		}
	}
}



/**
 * Return array of where the user currently is in course
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $postid
 * @param  int $course_id
 * @return array    list of courses, topics, lessons
 *                  current, previous, next
 */
function learndash_get_course_progress( $user_id = null, $postid = null, $course_id = null ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return null;
		}

		$user_id = $current_user->ID;
	}
	
	$posts = array();

	$posts = array();

	if ( is_null( $course_id ) ) {
		$course_id = learndash_get_course_id( $postid );
	}

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
	$this_post       = get_post( $postid );

	if ( empty( $course_progress ) ) {
		// learndash_update_completion( $user_id, $postid );
		// $course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
		$course_progress = array();
	}

	if ( $this_post->post_type == 'sfwd-lessons' ) {
		$posts = learndash_get_lesson_list( $postid, array( 'num' => 0 ) );

		if ( empty( $course_progress ) || empty( $course_progress[ $course_id ]['lessons'] ) ) {
			$completed_posts = array();
		} else {
			$completed_posts = $course_progress[ $course_id ]['lessons'];
		}
	} elseif ( $this_post->post_type == 'sfwd-topic' ) {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $this_post->ID );
		} else {
			$lesson_id = learndash_get_setting( $this_post, 'lesson' );
		}
		$posts = learndash_get_topic_list( $lesson_id, $course_id );

		if ( empty( $course_progress ) || empty( $course_progress[ $course_id ]['topics'][ $lesson_id ] ) ) {
			$completed_posts = array();
		} else {
			$completed_posts = $course_progress[ $course_id ]['topics'][ $lesson_id ];
		}
	}

	$temp = $prev_p = $next_p = $this_p = '';

	if ( ! empty( $posts ) ) {
		foreach ( $posts as $k => $post ) {

			if ( $post instanceof WP_Post ) {

				if ( ! empty( $completed_posts[ $post->ID ] ) ) {
					$posts[ $k ]->completed = 1;
				} else {
					$posts[ $k ]->completed = 0;
				}

				if ( $post->ID == $postid ) {
					$this_p = $post;
					$prev_p = $temp;
				}

				if ( ! empty( $temp->ID ) && $temp->ID == $postid ) {
					$next_p = $post;
				}

				$temp = $post;
			}
		}
	} else {
		$posts = array();
	}

	return array(
		'posts' => $posts,
		'this'  => $this_p,
		'prev'  => $prev_p,
		'next'  => $next_p,
	);
}



/**
 * Is lesson complete
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $lesson_id
 * @return bool
 */
function learndash_is_lesson_complete( $user_id = null, $lesson_id, $course_id = 0 ) {
	return ! learndash_is_lesson_notcomplete( $user_id, array( $lesson_id => 1 ), $course_id );
}



/**
 * Is lesson not complete
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $lesson_id
 * @return bool
 */
function learndash_is_lesson_notcomplete( $user_id = null, $lessons, $course_id = 0 ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

	if ( ! empty( $lessons ) ) {
		foreach ( $lessons as $lesson => $v ) {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $lesson );
			}

			if ( ! empty( $course_progress[ $course_id ] ) && ! empty( $course_progress[ $course_id ]['lessons'] ) && ! empty( $course_progress[ $course_id ]['lessons'][ $lesson ] ) ) {
				unset( $lessons[ $lesson ] );
			}
		}
	}

	if ( empty( $lessons ) ) {
		return 0;
	} else {
		return 1;
	}
}

/**
 * Is topic complete
 *
 * @since 2.3.0.2
 *
 * @param  int $user_id
 * @param  int $topic_id
 * @return bool
 */
function learndash_is_topic_complete( $user_id = null, $topic_id ) {
	return ! learndash_is_topic_notcomplete( $user_id, array( $topic_id => 1 ) );
}



/**
 * Is topic not complete
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $lesson_id
 * @return bool
 */
function learndash_is_topic_notcomplete( $user_id = null, $topics ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

	if ( ! empty( $topics ) ) {
		foreach ( $topics as $topic_id => $v ) {
			$course_id = learndash_get_course_id( $topic_id );
			$lesson_id = learndash_get_lesson_id( $topic_id );

			if ( ( isset( $course_progress[ $course_id ] ) )
				&& ( ! empty( $course_progress[ $course_id ] ) )
				&& ( isset( $course_progress[ $course_id ]['topics'] ) )
				&& ( ! empty( $course_progress[ $course_id ]['topics'] ) )
				&& ( isset( $course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] ) )
				&& ( ! empty( $course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] ) ) ) {
				unset( $topics[ $topic_id ] );
			}
		}
	}

	if ( empty( $topics ) ) {
		return 0;
	} else {
		return 1;
	}
}



/**
 * Output current status of course
 *
 * @since 2.1.0
 * @since 2.5.8 added $return_slug parameter
 *
 * @param  int  $id Course ID to get status of
 * @param  int  $user_id User ID
 * @param  bool $return_slug If false will return transatable string. If false status slug.
 *
 * @return string   output of current course status
 */
function learndash_course_status( $id, $user_id = null, $return_slug = false ) {
	$course_status_str = '';

	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return $course_status_str;
		}

		$user_id = get_current_user_id();
	} else {
		$user_id = intval( $user_id );
	}

	$completed_on = get_user_meta( $user_id, 'course_completed_' . $id, true );
	if ( ! empty( $completed_on ) ) {
		if ( true === $return_slug ) {
			$course_status_str = 'completed';
		} else {
			$course_status_str = esc_html__( 'Completed', 'learndash' );
		}
	} else {
		$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

		$has_completed_topic = false;

		if ( ! empty( $course_progress[ $id ] ) && ! empty( $course_progress[ $id ]['topics'] ) && is_array( $course_progress[ $id ]['topics'] ) ) {
			foreach ( $course_progress[ $id ]['topics'] as $lesson_topics ) {
				if ( ! empty( $lesson_topics ) && is_array( $lesson_topics ) ) {
					foreach ( $lesson_topics as $topic ) {
						if ( ! empty( $topic ) ) {
							$has_completed_topic = true;
							break;
						}
					}
				}

				if ( $has_completed_topic ) {
					break;
				}
			}
		}

		$quiz_notstarted = true;
		$quizzes         = learndash_get_global_quiz_list( $id );

		if ( ! empty( $quizzes ) ) {
			foreach ( $quizzes as $quiz ) {
				if ( ! learndash_is_quiz_notcomplete( $user_id, array( $quiz->ID => 1 ), false, $id ) ) {
					$quiz_notstarted = false;
				}
			}
		}

		if ( ( empty( $course_progress[ $id ] ) || empty( $course_progress[ $id ]['lessons'] ) && ! $has_completed_topic ) && $quiz_notstarted ) {
			if ( true === $return_slug ) {
				$course_status_str = 'not-started';
			} else {
				$course_status_str = esc_html__( 'Not Started', 'learndash' );
			}
		} elseif ( empty( $course_progress[ $id ] ) || @$course_progress[ $id ]['completed'] < @$course_progress[ $id ]['total'] ) {
			if ( true === $return_slug ) {
				$course_status_str = 'in-progress';
			} else {
				$course_status_str = esc_html__( 'In Progress', 'learndash' );
			}
		}
	}

	if ( true === $return_slug ) {
		return $course_status_str;
	} else {
		return apply_filters(
			'learndash_course_status',
			$course_status_str,
			$id,
			$user_id,
			isset( $course_progress[ $id ] ) ? $course_progress[ $id ] : array()
		);
	}
}


/**
 * Get the course status idex from the course status label
 *
 * In various places with LD the course status is expressed as a string as in 'Not Started', 'In Progress' or 'Complete'.
 * the problem with using this string is it will be translated depending on the locale(). This means comparative logic can
 * possible fails.
 *
 * The purpose of this function is to help use an internal key to keep track of the course status value
 *
 * @since 2.3
 *
 * @uses $learndash_course_statuses
 * @param  string $course_status_label Current translatable text for Course Status
 * @return string   The index/key of the course status string if found in the $learndash_course_statuses array
 */
function learndash_course_status_idx( $course_status_label = '' ) {
	global $learndash_course_statuses;

	return array_search( $course_status_label, $learndash_course_statuses );
}


/**
 * Output HTML template of users course progress
 *
 * @since 2.1.0
 *
 * @param  array $atts shortcode attributes
 * @return string      shortcode output
 */
function learndash_course_progress( $atts ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	extract(
		shortcode_atts(
			array(
				'course_id' => 0,
				'user_id'   => 0,
				'array'     => false,
			), $atts
		)
	);

	if ( empty( $user_id ) ) {
		// $current_user = wp_get_current_user();
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id();
	}

	if ( empty( $course_id ) ) {
		return '';
	}

	$completed = 0;
	$total     = false;

	if ( ! empty( $user_id ) ) {

		$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

		$percentage = 0;
		$message    = '';

		if ( ( ! empty( $course_progress ) ) && ( isset( $course_progress[ $course_id ] ) ) && ( ! empty( $course_progress[ $course_id ] ) ) ) {
			if ( isset( $course_progress[ $course_id ]['completed'] ) ) {
				$completed = absint( $course_progress[ $course_id ]['completed'] );
			}

			if ( isset( $course_progress[ $course_id ]['total'] ) ) {
				$total = absint( $course_progress[ $course_id ]['total'] );
			}
		}
	}

	// If $total is still false we calculate the total from course steps.
	if ( false === $total ) {
		$total = learndash_get_course_steps_count( $course_id );
	}

	if ( $total > 0 ) {
		$percentage = intval( $completed * 100 / $total );
		$percentage = ( $percentage > 100 ) ? 100 : $percentage;
	} else {
		$percentage = 0;
	}
	// translators: placeholder: completed steps, total steps.
	$message    = sprintf( esc_html_x( '%1$d out of %2$d steps completed', 'placeholder: completed steps, total steps', 'learndash' ), $completed, $total );

	if ( $array ) {
		return array(
			'percentage' => isset( $percentage ) ? $percentage : 0,
			'completed'  => isset( $completed ) ? $completed : 0,
			'total'      => isset( $total ) ? $total : 0,
		);
	}

	return SFWD_LMS::get_template(
		'course_progress_widget', array(
			'user_id'    => $user_id,
			'course_id'  => $course_id,
			'message'    => $message,
			'percentage' => isset( $percentage ) ? $percentage : 0,
			'completed'  => isset( $completed ) ? $completed : 0,
			'total'      => isset( $total ) ? $total : 0,
		)
	);
}

add_shortcode( 'learndash_course_progress', 'learndash_course_progress' );



/**
 * Is quiz accessible to user
 *
 * @since 2.1.0
 *
 * @param int     $user_id $user_id ID of User to check.
 * @param object  $post WP_Post quiz.
 * @param boolean $return_incomplete default false. true to return last imcomplete step.
 * @return mixed boolean default. If $return_incomplete is true will return WP_Post object.
 */
function is_quiz_accessable( $user_id = null, $post = null, $return_incomplete = false, $course_id = 0 ) {

	// Allow using the legacy function in case of issues with new logic.
	if ( ( defined( 'LEARNDASH_IS_QUIZ_ACCESSABLE_LEGACY' ) && ( LEARNDASH_IS_QUIZ_ACCESSABLE_LEGACY === true ) ) ) {
		return is_quiz_accessable_legacy( $user_id, $post, $course_id );
	}

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return 1;
		}

		$user_id = $current_user->ID;
	}

	if ( learndash_is_admin_user( $user_id ) ) {
		$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );
		if ( 'yes' === $bypass_course_limits_admin_users ) {
			return 1;
		}
	}

	if ( ( empty( $post ) ) || ( ! is_a( $post, 'WP_Post' ) ) ) {
		return;
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $post );
	} 
	$course_id = absint( $course_id );

	// If we have a Quiz but the Quiz is not part of a course then return 1 for valid.
	if ( empty( $course_id ) ) {
		return 1;
	}

	if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
		$quiz_parent_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
	} else {
		$quiz_parent_id = learndash_get_setting( $post, 'lesson' );
	}
	$quiz_parent_id = absint( $quiz_parent_id );
	if ( ! empty( $quiz_parent_id ) ) {
		$quiz_parent_post = get_post( $quiz_parent_id );
		if ( is_a( $quiz_parent_post, 'WP_Post' ) ) {
			if ( $quiz_parent_post->post_type == learndash_get_post_type_slug( 'topic' ) ) {
				$quiz_parent_topic_post = $quiz_parent_post;
				$quiz_parent_lesson_id = learndash_get_setting( $quiz_parent_topic_post, 'lesson' );
				$quiz_parent_lesson_post = get_post( $quiz_parent_lesson_id );

				$parent_topic_quizzes = learndash_get_lesson_quiz_list( $quiz_parent_topic_post, $user_id, $course_id );
				if ( ! empty( $parent_topic_quizzes ) ) {
					// loop until we get to the first status == 'notcompleted'.
					foreach ( $parent_topic_quizzes as $quiz ) {
						if ( $quiz['post']->ID === $post->ID ) {
							//return 1;
							break;
						} elseif ( 'completed' !== $quiz['status'] ) {
							if ( true === $return_incomplete ) {
								return $quiz['post'];
							} else {
								return 0;
							}
						}
					}
				}

				$lesson_topics_progress = learndash_get_course_progress( null, $quiz_parent_topic_post->ID );
				if ( ( isset( $lesson_topics_progress['posts'] ) ) && ( ! empty( $lesson_topics_progress['posts'] ) ) ) {
					foreach ( $lesson_topics_progress['posts'] as $topic ) {
						if ( $topic->ID === $quiz_parent_topic_post->ID ) {
							break;
						}
						if ( empty( $topic->completed ) ) {
							if ( true === $return_incomplete ) {
								return $topic;
							} else {
								return 0;
							}
							break;
						}
					}
				}

				$lesson_progress = learndash_get_course_progress( null, $quiz_parent_lesson_post->ID );
				if ( ( isset( $lesson_progress['posts'] ) ) && ( ! empty( $lesson_progress['posts'] ) ) ) {
					foreach ( $lesson_progress['posts'] as $lesson ) {
						if ( $lesson->ID === $quiz_parent_lesson_post->ID ) {
							break;
						}
						if ( empty( $lesson->completed ) ) {
							if ( true === $return_incomplete ) {
								return $lesson;
							} else {
								return 0;
							}
							break;
						}
					}
				}

				return 1;

			} elseif ( $quiz_parent_post->post_type == learndash_get_post_type_slug( 'lesson' ) ) {
				$quiz_parent_lesson_post = $quiz_parent_post;

				$lesson_topics = learndash_get_topic_list( $quiz_parent_lesson_post->ID );
				if ( ! empty( $lesson_topics ) ) {
					$lesson_topics_progress = learndash_get_course_progress( null, $lesson_topics[0]->ID );
					if ( ( isset( $lesson_topics_progress['posts'] ) ) && ( ! empty( $lesson_topics_progress['posts'] ) ) ) {
						foreach ( $lesson_topics_progress['posts'] as $topic ) {
							if ( empty( $topic->completed ) ) {
								if ( true === $return_incomplete ) {
									return $topic;
								} else {
									return 0;
								}	
								break;
							}
						}
					}
				}

				$quizzes = learndash_get_lesson_quiz_list( $quiz_parent_lesson_post, $user_id, $course_id );
				if ( ! empty( $quizzes ) ) {
					// loop until we get to the first status == 'notcompleted'.
					foreach( $quizzes as $quiz ) {
						if ( $quiz['post']->ID === $post->ID ) {
							break;
						}

						if ( 'completed' !== $quiz['status'] ) {
							if ( true === $return_incomplete ) {
								return $quiz['post'];
							} else {
								return 0;
							}
						}
					}
				}

				//$lessons = learndash_get_course_lessons_list( $course_id, $user_id, array( 'num' => 0 ) );
				$lesson_progress = learndash_get_course_progress( null, $quiz_parent_lesson_post->ID );
				if ( ( isset( $lesson_progress['posts'] ) ) && ( ! empty( $lesson_progress['posts'] ) ) ) {
					foreach ( $lesson_progress['posts'] as $lesson ) {
						if ( $lesson->ID === $quiz_parent_lesson_post->ID ) {
							break;
						}
						if ( empty( $lesson->completed ) ) {
							if ( true === $return_incomplete ) {
								return $lesson;
							} else {
								return 0;
							}	
							break;
						}
					}
				}

				return 1;
			}
		}
	} else {
		// First we check if all course lessons are completed.
		$lessons = learndash_get_course_lessons_list( $course_id, $user_id, array( 'num' => 0 ) );
		if ( ! empty( $lessons ) ) {
			foreach ( $lessons as $lesson ) {
				//if ( ( $lesson['sample'] === 'is_not_sample' ) && ( 'completed' !== $lesson['status'] ) ) {
				if ( 'completed' !== $lesson['status'] ) {
					if ( true === $return_incomplete ) {
						return $lesson['post'];
					} else {
						return 0;
					}
				}
			}
		}

		// Next we check if other global quizzes are completed.
		$quizzes = learndash_get_global_quiz_list( $course_id );
		if ( ! empty( $quizzes ) ) {
			// loop until we get to the first status == 'notcompleted'.
			foreach( $quizzes as $quiz ) {
				if ( $quiz->ID === $post->ID ) {
					return 1;
				} elseif ( ! learndash_is_quiz_complete( $user_id, $quiz->ID, $course_id ) ) {
					if ( true === $return_incomplete ) {
						return $quiz;
					} else {
						return 0;
					}
				}
			}
		}
	}
	return 0;
}

function is_quiz_accessable_legacy( $user_id = null, $post = null, $course_id = 0 ) {
	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return 1;
		}

		$user_id = $current_user->ID;
	}

	if ( ( ! empty( $post ) ) && ( $post instanceof WP_Post ) ) {
		if ( empty( $course_id ) ) {
			$course_id = learndash_get_course_id( $post );
		} 
		$course_id = absint( $course_id );

		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$quiz_lesson = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$quiz_lesson = learndash_get_setting( $post, 'lesson' );
		}

		if ( ! empty( $quiz_lesson ) ) {
			$quiz_lesson_post = get_post( $quiz_lesson );
			if ( ( $quiz_lesson_post instanceof WP_Post ) && ( $quiz_lesson_post->post_type == 'sfwd-topic' ) ) {
				return 1;
			} elseif ( learndash_lesson_topics_completed( $quiz_lesson ) ) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

			if ( ! empty( $course_progress ) && ! empty( $course_progress[ $course_id ] ) && ! empty( $course_progress[ $course_id ]['total'] ) ) {
				$completed = intVal( $course_progress[ $course_id ]['completed'] );
				$total     = intVal( $course_progress[ $course_id ]['total'] );

				if ( $completed >= $total - 1 ) {
					return 1;
				}
			}

			$lessons = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );

			if ( empty( $lessons ) ) {
				return 1;
			}
		}
	}
	return 0;
}


/**
 * Check if all quizzes for course are complete for user
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $id
 * @return bool
 */
function is_all_global_quizzes_complete( $user_id = null, $id = null ) {
	$quizzes = learndash_get_global_quiz_list( $id );
	$return  = true;

	if ( ! empty( $quizzes ) ) {
		foreach ( $quizzes as $quiz ) {
			if ( learndash_is_quiz_notcomplete( $user_id, array( $quiz->ID => 1 ), false, $id ) ) {
				$return = false;
			}
		}
	}

	return $return;
}



/**
 * Get next quiz for course
 *
 * @since 2.1.0
 *
 * @param  bool  $url        return a url
 * @param  int   $user_id
 * @param  int   $id
 * @param  array $exclude    excluded quiz id's
 * @return int|string               id of quiz or url of quiz
 */
function learndash_next_global_quiz( $url = true, $user_id = null, $id = null, $exclude = array() ) {
	if ( empty( $id ) ) {
		$id = learndash_get_course_id();
	}

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	$quizzes = learndash_get_global_quiz_list( $id );
	$return  = get_permalink( $id );

	if ( ! empty( $quizzes ) ) {
		foreach ( $quizzes as $quiz ) {
			if ( ! in_array( $quiz->ID, $exclude ) && learndash_is_quiz_notcomplete( $user_id, array( $quiz->ID => 1 ), false, $id ) && learndash_can_attempt_again( $user_id, $quiz->ID ) ) {
				if ( $url ) {
					return get_permalink( $quiz->ID );
				} else {
					return $quiz->ID;
				}
			}
		}
	}

	/**
	 * Filter return value of next global quiz
	 *
	 * @todo  filter name does not seem correct
	 *        in context of function
	 *
	 * @since 2.1.0
	 *
	 * @var id|string   $return
	 */
	$return = apply_filters( 'learndash_course_completion_url', $return, $id );
	return $return;
}



/**
 * Get next quiz for current lesson for current user
 *
 * @since 2.1.0
 *
 * @param  bool  $url        return a url
 * @param  int   $user_id
 * @param  int   $lesson_id
 * @param  array $exclude    excluded quiz id's
 * @return int|string               id of quiz or url of quiz
 */
function learndash_next_lesson_quiz( $url = true, $user_id = null, $lesson_id = null, $exclude = array() ) {
	global $post;

	$return = false;

	if ( empty( $lesson_id ) ) {
		$lesson_id = $post->ID;
	}

	if ( empty( $exclude ) ) {
		$exclude = array();
	}

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	// Assumption here is the learndash_get_lesson_quiz_list returns the quizzes in the order they should be taken.
	$quizzes = learndash_get_lesson_quiz_list( $lesson_id, $user_id );
	if ( ( ! empty( $quizzes ) ) && ( is_array( $quizzes ) ) ) {
		foreach ( $quizzes as $quiz ) {
			// The logic here is we need to check all the quizzes in this lesson. If all the quizzes are complete
			// (including the current one) then we set the parent (lesson) to complete.
			if ( $quiz['status'] == 'completed' ) {
				continue;
			}

			// If not complete AND the user CAN take the quiz again...
			if ( learndash_can_attempt_again( $user_id, $quiz['post']->ID ) ) {
				$return = ( $url ) ? get_permalink( $quiz['post']->ID ) : $quiz['post']->ID;
				break;
			}

			$return = ( $url ) ? get_permalink( $quiz['post']->ID ) : $quiz['post']->ID;
			// $return = ( $url ) ? get_permalink( $lesson_id ) : $lesson_id;
			break;
		}
	}

	if ( empty( $return ) ) {
		learndash_process_mark_complete( $user_id, $lesson_id );
	} else {
		return $return;
	}
}



/**
 * Does resource have quizzes?
 *
 * @since 2.1.0
 *
 * @param  int $id
 * @return bool
 */
function has_global_quizzes( $id = null ) {
	$quizzes = learndash_get_global_quiz_list( $id );
	return ! empty( $quizzes );
}



/**
 * Course Progress Widget
 */
class LearnDash_Course_Progress_Widget extends WP_Widget {

	/**
	 * Set up course project widget
	 */
	function __construct() {
		$widget_ops  = array(
			'classname'   => 'widget_ldcourseprogress',
			'description' => sprintf( esc_html_x( 'LearnDash %s progress bar', 'placeholders: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
		);
		$control_ops = array(); // 'width' => 400, 'height' => 350);
		parent::__construct( 'ldcourseprogress', sprintf( esc_html_x( '%s Progress Bar', 'Course Progress Bar Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), $widget_ops, $control_ops );
	}



	/**
	 * Displays widget
	 *
	 * @since 2.1.0
	 *
	 * @param  array $args     widget arguments
	 * @param  array $instance widget instance
	 * @return string          widget output
	 */
	function widget( $args, $instance ) {
		global $learndash_shortcode_used;

		extract( $args );
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance );

		if ( ! is_singular() ) {
			return;
		}

		$progressbar = learndash_course_progress( $args );

		if ( empty( $progressbar ) ) {
			return;
		}

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		echo $progressbar;
		echo $after_widget;

		$learndash_shortcode_used = true;
	}



	/**
	 * Handles widget updates in admin
	 *
	 * @since 2.1.0
	 *
	 * @param  array $new_instance
	 * @param  array $old_instance
	 * @return array $instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}



	/**
	 * Display widget form in admin
	 *
	 * @since 2.1.0
	 *
	 * @param  array $instance widget instance
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title    = strip_tags( $instance['title'] );
		// $text = format_to_edit( $instance['text'] );
		?>
			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'learndash' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		<?php
	}

}

add_action(
	'widgets_init', function() {
		return register_widget( 'LearnDash_Course_Progress_Widget' );
	}
);



/**
 * Output HTML of course progress for user
 *
 * @todo consider for deprecation, not in use
 *
 * @since 2.1.0
 *
 * @param  $atts
 */
function learndash_course_progress_widget( $atts ) {
	echo learndash_course_progress( $atts );
}



/**
 * Is progression enabled for lesson
 *
 * @since 2.1.0
 *
 * @since 2.5.9
 * @param integer $course_id Course ID to check.
 *
 * @return bool
 */
function learndash_lesson_progression_enabled( $course_id = 0 ) {
	$course_id = intval( $course_id );

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id();
	}

	if ( ! empty( $course_id ) ) {
		$setting = learndash_get_setting( $course_id, 'course_disable_lesson_progression' );
		return empty( $setting );
	}
}

/**
 * Get lesson time for lesson if it exists
 *
 * @since 2.1.0
 */
function learndash_forced_lesson_time( $lesson_topic_post = '' ) {

	if ( empty( $lesson_topic_post ) ) {
		global $post;
		$lesson_topic_post = $post;
	}

	if ( ! is_a( $lesson_topic_post, 'WP_Post' ) ) {
		$post_id = absint( $lesson_topic_post );
		if ( empty( $post_id ) ) {
			return 0;
		}
		$lesson_topic_post = get_post( $post_id );
		if ( ( ! $lesson_topic_post ) || ( ! is_a( $lesson_topic_post, 'WP_Post' ) ) ) {
			return 0;
		}
	}

	if ( ! in_array( $lesson_topic_post->post_type, array( learndash_get_post_type_slug( 'lesson' ), learndash_get_post_type_slug( 'topic' ) ) ) ) {
		return 0;
	}

	$meta = get_post_meta( $lesson_topic_post ->ID, '_' . $lesson_topic_post->post_type, true );

	if ( ! isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] ) ) {
		if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) ) {
			$meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] = 'on';
		} else {
			$meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] = '';
		}
	}	
	
	if ( 'on' === $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] ) {
		if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) ) {
			return $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ];
		} 
	}

	return 0;
}

/**
 * Get lesson time cookie key for lesson/topic
 *
 * @since 3.0
 * @param WP_Post $lesson_topic_post WP_Post object or post_id.
 * @return string cookie key value or empty.
 */
function learndash_forced_lesson_time_cookie_key( $lesson_topic_post = '' ) {

	if ( empty( $lesson_topic_post ) ) {
		global $post;
		$lesson_topic_post = $post;
	}

	if ( ! is_a( $lesson_topic_post, 'WP_Post' ) ) {
		$post_id = absint( $lesson_topic_post );
		if ( empty( $post_id ) ) {
			return 0;
		}
		$lesson_topic_post = get_post( $post_id );
		if ( ( ! $lesson_topic_post ) || ( ! is_a( $lesson_topic_post, 'WP_Post' ) ) ) {
			return 0;
		}
	}

	if ( ! in_array( $lesson_topic_post->post_type, array( learndash_get_post_type_slug( 'lesson' ), learndash_get_post_type_slug( 'topic' ) ) ) ) {
		return 0;
	}

	$meta = get_post_meta( $lesson_topic_post ->ID, '_' . $lesson_topic_post->post_type, true );

	if ( ! isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] ) ) {
		if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) ) {
			$meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] = 'on';
		} else {
			$meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] = '';
		}
	}	
	
	if ( 'on' === $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] ) {
		if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) ) {
			$cookie_key = get_current_user_id() . '_' . learndash_get_course_id( $lesson_topic_post ) . '_' . $lesson_topic_post->ID;

			if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_cookie_key' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_cookie_key' ] ) ) ) {
				$cookie_key .= '_' . $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_cookie_key' ];
			} 
			return $cookie_key;
		} 
	}

	return '';
}


/**
 * Is course completed for user
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $course_id
 * @return bool
 */
function learndash_course_completed( $user_id, $course_id ) {
	if ( learndash_course_status( $course_id, $user_id ) == esc_html__( 'Completed', 'learndash' ) ) {
		return true;
	} else {
		return false;
	}
}



/**
 * Add course completion date to user meta
 *
 * @since 2.1.0
 *
 * @param  array $data
 */
function learndash_course_completed_store_time( $data ) {
	$user_id    = $data['user']->ID;
	$course_id  = $data['course']->ID;
	$meta_key   = 'course_completed_' . $course_id;
	$meta_value = time();

	$course_completed = get_user_meta( $user_id, $meta_key );

	if ( empty( $course_completed ) ) {
		update_user_meta( $user_id, $meta_key, $meta_value );
	}
}

add_action( 'learndash_before_course_completed', 'learndash_course_completed_store_time', 10, 1 );



/**
 * Delete course progress for user
 *
 * @since 2.1.0
 *
 * @param  int $course_id
 * @param  int $user_id
 */
function learndash_delete_course_progress( $course_id, $user_id ) {
	global $wpdb;
	$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );

	if ( isset( $usermeta[ $course_id ] ) ) {
		unset( $usermeta[ $course_id ] );
		update_user_meta( $user_id, '_sfwd-course_progress', $usermeta );
	}

	delete_user_meta( $user_id, 'course_completed_' . $course_id );

	// The reason we don't use the methods above is we want to ensure all old data is removed
	// from the quiz attempt history not just for quizzes currently associated with the course.
	$quizzes          = array();
	$usermeta_quizzes = get_user_meta( $user_id, '_sfwd-quizzes', true );
	if ( ! is_array( $usermeta_quizzes ) ) {
		$usermeta_quizzes = array();
	}
	if ( ! empty( $usermeta_quizzes ) ) {
		foreach ( $usermeta_quizzes as $quiz_item ) {
			if ( ( isset( $quiz_item['course'] ) ) && ( intval( $course_id ) == intval( $quiz_item['course'] ) ) ) {
				if ( isset( $quiz_item['quiz'] ) ) {
					$quiz_id             = intval( $quiz_item['quiz'] );
					$quizzes[ $quiz_id ] = $quiz_id;
				}
			}
		}
	}

	if ( ! empty( $quizzes ) ) {
		foreach ( $quizzes as $quiz_id ) {
			learndash_delete_quiz_progress( $user_id, $quiz_id );
		}
	}
}



/**
 * Delete quiz progress for user
 *
 * @since 2.1.0
 *
 * @param  int $user_id
 * @param  int $quiz_id
 */
function learndash_delete_quiz_progress( $user_id, $quiz_id ) {
	global $wpdb;

	// Clear User Meta
	$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );

	if ( ! empty( $usermeta ) && is_array( $usermeta ) ) {
		$usermeta_new = array();
		foreach ( $usermeta as $key => $quizmeta ) {
			if ( $quizmeta['quiz'] != $quiz_id ) {
				$usermeta_new[] = $quizmeta;
			}
		}
		update_user_meta( $user_id, '_sfwd-quizzes', $usermeta_new );
	}

	// Lesson/Topic
	/*
	$lesson_id = learndash_get_setting( $quiz_id, "lesson");
	if(!empty( $lesson_id )) {
	$lesson_post = get_post( $lesson_id );
	if( $lesson_post->post_type == "sfwd-lessons")
		learndash_specific_mark_lesson_incomplete( $user_id, $lesson_id );
	else if( $lesson_post->post_type == "sfwd-topic")
		learndash_specific_mark_topic_incomplete( $user_id, $lesson_id );
	}*/

	// Pro Quiz Data
	$pro_quiz_id = learndash_get_setting( $quiz_id, 'quiz_pro' );

	$ref_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT statistic_ref_id FROM ' . LDLMS_DB::get_table_name( 'quiz_statistic_ref' ) . " WHERE  user_id = '%d' AND quiz_id = '%d' ", $user_id, $pro_quiz_id ) );

	if ( ! empty( $ref_ids[0] ) ) {
		$wpdb->delete(
			LDLMS_DB::get_table_name( 'quiz_statistic_ref' ), array(
				'user_id' => $user_id,
				'quiz_id' => $pro_quiz_id,
			)
		);
		$wpdb->query( 'DELETE FROM ' . LDLMS_DB::get_table_name( 'quiz_statistic' ) . ' WHERE statistic_ref_id IN (' . implode( ',', $ref_ids ) . ')' );
	}

	// $wpdb->query("DELETE FROM ".$wpdb->usermeta." WHERE meta_key LIKE 'completed_%' AND user_id = '".$user->ID."'");
	$wpdb->delete(
		LDLMS_DB::get_table_name( 'quiz_toplist' ), array(
			'user_id' => $user_id,
			'quiz_id' => $pro_quiz_id,
		)
	);
}

function learndash_quiz_remove_user_statistics_by_ref( $ref_id = 0 ) {
	global $wpdb;

	if ( ! empty( $ref_id ) ) {
		$wpdb->delete( LDLMS_DB::get_table_name( 'quiz_statistic_ref' ), array( 'statistic_ref_id' => $ref_id ) );
		$wpdb->query( 'DELETE FROM ' . LDLMS_DB::get_table_name( 'quiz_statistic' ) . ' WHERE statistic_ref_id =' . $ref_id );
	}
}


function learndash_quiz_remove_user_toplist( $user_id = 0, $quiz_time = 0, $pro_quizid = 0) {
	global $wpdb;

	if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_time ) ) && ( ! empty( $pro_quizid ) ) ) {
		return $wpdb->delete(
			LDLMS_DB::get_table_name( 'quiz_toplist' ),
			array(
				'user_id' => $user_id,
				'date'    => $quiz_time,
				'quiz_id' => $pro_quizid,
			),
			array( '%d', '%d', '%d' )
		);
	}
}


// Used to set a Course step ( Lesson or Topic only ) back to NOT complete status
function learndash_process_mark_incomplete( $user_id = 0, $course_id = 0, $step_id = 0, $step_complete = false ) {
	if ( empty( $user_id ) ) {
		return;
	}

	if ( empty( $course_id ) ) {
		return;
	}

	// Ensure the course is not completed
	// $course_status = learndash_course_status( $course_id, $user_id );
	// if ( $course_status == esc_html__( 'Completed', 'learndash' ) ) {
	// return;
	// }
	if ( empty( $step_id ) ) {
		global $post;
		if ( ( isset( $post ) ) && ( $post instanceof WP_Post ) && ( ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) ) {
			$step_id = $post->ID;
		} else {
			return;
		}
	}

	$subtracted_completed_steps = 0;

	$course_step_parents = learndash_course_get_all_parent_step_ids( $course_id, $step_id );

	$user_course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

	$step_post_type = get_post_type( $step_id );
	if ( $step_post_type === 'sfwd-quiz' ) {
		if ( ! empty( $course_step_parents ) ) {
			if ( ( sizeof( $course_step_parents ) == 2 ) && ( 'sfwd-lessons' == get_post_type( $course_step_parents[0] ) ) && ( 'sfwd-topic' == get_post_type( $course_step_parents[1] ) ) ) {
				$lesson_id = $course_step_parents[0];
				$topic_id  = $course_step_parents[1];

				if ( ( isset( $user_course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] ) ) && ( $user_course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] == true ) ) {

					$user_course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] = 0;
					$user_course_progress[ $course_id ]['completed']                        -= 1;
					$topic_args     = array(
						'course_id'     => $course_id,
						'user_id'       => $user_id,
						'post_id'       => $topic_id,
						'activity_type' => 'topic',
					);
					$topic_activity = learndash_get_user_activity( $topic_args );
					if ( $topic_activity ) {
						$topic_activity = (array) $topic_activity;

						$topic_activity['activity_status']    = false;
						$topic_activity['activity_completed'] = false;
						learndash_update_user_activity( $topic_activity );
					}
				}

				if ( ( isset( $user_course_progress[ $course_id ]['lessons'][ $lesson_id ] ) ) && ( $user_course_progress[ $course_id ]['lessons'][ $lesson_id ] == true ) ) {
					$user_course_progress[ $course_id ]['lessons'][ $lesson_id ] = 0;
					$user_course_progress[ $course_id ]['completed']            -= 1;

					$lesson_args     = array(
						'course_id'     => $course_id,
						'user_id'       => $user_id,
						'post_id'       => $lesson_id,
						'activity_type' => 'lesson',
					);
					$lesson_activity = learndash_get_user_activity( $lesson_args );
					if ( $lesson_args ) {
						$lesson_args                       = (array) $lesson_args;
						$lesson_args['activity_status']    = false;
						$lesson_args['activity_completed'] = false;
						learndash_update_user_activity( $lesson_args );
					}
				}
			} elseif ( ( sizeof( $course_step_parents ) == 1 ) && ( 'sfwd-lessons' == get_post_type( $course_step_parents[0] ) ) ) {
				$lesson_id = $course_step_parents[0];

				if ( ( isset( $user_course_progress[ $course_id ]['lessons'][ $lesson_id ] ) ) && ( $user_course_progress[ $course_id ]['lessons'][ $lesson_id ] == true ) ) {
					$user_course_progress[ $course_id ]['lessons'][ $lesson_id ] = 0;
					$user_course_progress[ $course_id ]['completed']            -= 1;

					$lesson_args     = array(
						'course_id'     => $course_id,
						'user_id'       => $user_id,
						'post_id'       => $lesson_id,
						'activity_type' => 'lesson',
					);
					$lesson_activity = learndash_get_user_activity( $lesson_args );
					if ( $lesson_args ) {
						$lesson_args                       = (array) $lesson_args;
						$lesson_args['activity_status']    = false;
						$lesson_args['activity_completed'] = false;
						learndash_update_user_activity( $lesson_args );
					}
				}
			}
		}
	} elseif ( $step_post_type === 'sfwd-topic' ) {
		$step_parent_id = learndash_course_get_single_parent_step( $course_id, $step_id );
		if ( ! empty( $step_parent_id ) ) {
			if ( ( isset( $user_course_progress[ $course_id ]['topics'][ $step_parent_id ][ $step_id ] ) ) && ( $user_course_progress[ $course_id ]['topics'][ $step_parent_id ][ $step_id ] == true ) ) {
				$user_course_progress[ $course_id ]['topics'][ $step_parent_id ][ $step_id ] = 0;
				$user_course_progress[ $course_id ]['completed']                            -= 1;

				$topic_args     = array(
					'course_id'     => $course_id,
					'user_id'       => $user_id,
					'post_id'       => $step_id,
					'activity_type' => 'topic',
				);
				$topic_activity = learndash_get_user_activity( $topic_args );
				if ( $topic_activity ) {
					$topic_activity = (array) $topic_activity;

					$topic_activity['activity_status']    = false;
					$topic_activity['activity_completed'] = false;
					learndash_update_user_activity( $topic_activity );
				}
			}
			if ( ( isset( $user_course_progress[ $course_id ]['lessons'][ $step_parent_id ] ) ) && ( $user_course_progress[ $course_id ]['lessons'][ $step_parent_id ] == true ) ) {
				$user_course_progress[ $course_id ]['lessons'][ $step_parent_id ] = 0;
				$user_course_progress[ $course_id ]['completed']                 -= 1;

				$lesson_args     = array(
					'course_id'     => $course_id,
					'user_id'       => $user_id,
					'post_id'       => $step_parent_id,
					'activity_type' => 'topic',
				);
				$lesson_activity = learndash_get_user_activity( $lesson_args );
				if ( $lesson_args ) {
					$lesson_args                       = (array) $lesson_args;
					$lesson_args['activity_status']    = false;
					$lesson_args['activity_completed'] = false;
					learndash_update_user_activity( $lesson_args );
				}
			}
		}
	} elseif ( $step_post_type === 'sfwd-lessons' ) {
		if ( ( isset( $user_course_progress[ $course_id ]['lessons'][ $step_id ] ) ) && ( $user_course_progress[ $course_id ]['lessons'][ $step_id ] == true ) ) {
			$user_course_progress[ $course_id ]['lessons'][ $step_id ] = 0;
			$user_course_progress[ $course_id ]['completed']          -= 1;

			$lesson_args     = array(
				'course_id'     => $course_id,
				'user_id'       => $user_id,
				'post_id'       => $step_id,
				'activity_type' => 'lesson',
			);
			$lesson_activity = learndash_get_user_activity( $lesson_args );
			if ( $lesson_args ) {
				$lesson_args                       = (array) $lesson_args;
				$lesson_args['activity_status']    = false;
				$lesson_args['activity_completed'] = false;
				learndash_update_user_activity( $lesson_args );
			}
		}
	}

	update_user_meta( $user_id, '_sfwd-course_progress', $user_course_progress );
}


/**
 * Utility function to retreive a specific user quiz meta attempt.
 *
 * @param $args array of items to match.
 *
 * @since 2.5
 */
function learndash_get_user_quiz_attempt( $user_id = 0, $args = array() ) {
	if ( ( ! empty( $user_id ) ) && ( ! empty( $args ) ) ) {
		$user_quizzes = get_user_meta( $user_id, '_sfwd-quizzes', true );
		if ( ! empty( $user_quizzes ) ) {
			foreach ( $user_quizzes as $idx => $user_quiz ) {
				foreach ( $args as $arg_key => $arg_val ) {
					if ( ( ! isset( $user_quiz[ $arg_key ] ) ) || ( $user_quiz[ $arg_key ] != $arg_val ) ) {
						unset( $user_quizzes[ $idx ] );
					}
				}
			}
		}

		return $user_quizzes;
	}
}


function learndash_remove_user_quiz_attempt( $user_id = 0, $args = array() ) {
	if ( ( ! empty( $user_id ) ) && ( ! empty( $args ) ) ) {

		$CHANGES      = false;
		$user_quizzes = get_user_meta( $user_id, '_sfwd-quizzes', true );

		if ( ! empty( $user_quizzes ) ) {
			$changed_user_quizzes = array();

			foreach ( $user_quizzes as $idx => $user_quiz ) {
				$MATCH_FOUND = true;

				foreach ( $args as $arg_key => $arg_val ) {
					if ( ( ! isset( $user_quiz[ $arg_key ] ) ) || ( $user_quiz[ $arg_key ] != $arg_val ) ) {
						$MATCH_FOUND = false;
						break;
					}
				}

				if ( $MATCH_FOUND === true ) {
					if ( ( isset( $user_quiz['time'] ) ) && ( ! empty( $user_quiz['time'] ) ) ) {
						if ( ( isset( $user_quiz['pro_quizid'] ) ) && ( ! empty( $user_quiz['pro_quizid'] ) ) ) {
							learndash_quiz_remove_user_toplist( $user_id, $user_quiz['time'], $user_quiz['pro_quizid'] );
						}
					}
					
					// If we have a statistics reference we need to remove this set of records.
					if ( ( isset( $user_quiz['statistic_ref_id'] ) ) && ( ! empty( $user_quiz['statistic_ref_id'] ) ) ) {
						learndash_quiz_remove_user_statistics_by_ref( $user_quiz['statistic_ref_id'] );
					}

					if ( ( ! isset( $user_quiz['course'] ) ) || ( empty( $user_quiz['course'] ) ) ) {
						$user_quiz['course'] = learndash_get_course_id( $user_quiz['quiz'] );
					}

					// If this quiz has graded items they all need to be moved to trash or deleted.
					if ( ( isset( $user_quiz['graded'] ) ) && ( ! empty( $user_quiz['graded'] ) ) ) {
						foreach ( $user_quiz['graded'] as $question_id => $graded_set ) {
							if ( ( isset( $graded_set['post_id'] ) ) && ( ! empty( $graded_set['post_id'] ) ) ) {
								wp_delete_post( $graded_set['post_id'], true );
							}
						}
					}

					// Remove the user activity record.
					$quiz_args     = array(
						'course_id'          => $user_quiz['course'],
						'user_id'            => $user_id,
						'post_id'            => $user_quiz['quiz'],
						'activity_type'      => 'quiz',
						'activity_completed' => $user_quiz['completed'],
					);
					$quiz_activity = learndash_get_user_activity( $quiz_args );
					if ( ! empty( $quiz_activity ) ) {
						learndash_delete_user_activity( $quiz_activity->activity_id );
					}

					$changed_user_quizzes[] = $user_quiz;

					unset( $user_quizzes[ $idx ] );
					$CHANGES = true;
				}
			}

			if ( true === $CHANGES ) {
				// If not empty then we reset the keys.
				if ( ! empty( $user_quizzes ) ) {
					$user_quizzes = array_values( $user_quizzes );
				}

				update_user_meta( $user_id, '_sfwd-quizzes', $user_quizzes );

				if ( ! empty( $changed_user_quizzes ) ) {
					foreach ( $changed_user_quizzes as $user_quiz ) {

						if ( ! learndash_is_quiz_complete( $user_id, $user_quiz['quiz'], $user_quiz['course'] ) ) {
							learndash_process_mark_incomplete( $user_id, $user_quiz['course'], $user_quiz['quiz'], false );
						}
						learndash_process_mark_complete( $user_id, $user_quiz['quiz'], false, $user_quiz['course'] );
					}
				}
			}
		}

		return $user_quizzes;
	}
}
