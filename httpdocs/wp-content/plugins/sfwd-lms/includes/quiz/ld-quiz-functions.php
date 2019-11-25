<?php
/**
 * LearnDash Quiz and Question related functions.
 *
 * @package LearnDash
 * @subpackage Quiz
 */

/**
 * Get a Quiz Pro's quiz ID
 *
 * @todo   purpose of this function and how quiz pro id's relate to quizzes
 *
 * @since 2.1.0
 *
 * @param int $quiz_pro_id quiz pro id.
 * @return int quiz post id
 */
function learndash_get_quiz_id_by_pro_quiz_id( $quiz_pro_id = 0 ) {
	global $wpdb;

	global $learndash_shortcode_atts;

	static $quiz_post_ids = array();

	if ( empty( $quiz_pro_id ) ) {
		return;
	}
	$quiz_pro_id = absint( $quiz_pro_id );

	if ( ( isset( $quiz_post_ids[ $quiz_pro_id ] ) ) && ( ! empty( $quiz_post_ids[ $quiz_pro_id ] ) ) ) {
		return $quiz_post_ids[ $quiz_pro_id ];
	} else {
		$quiz_post_ids[ $quiz_pro_id ] = false;

		global $learndash_shortcode_atts;
		if ( ! empty( $learndash_shortcode_atts ) ) {
			foreach ( array_reverse( $learndash_shortcode_atts ) as $shortcode_tag => $shortcode_atts ) {
				if ( in_array( $shortcode_tag, array('LDAdvQuiz', 'ld_quiz' ) ) ) {
					if ( ( isset( $shortcode_atts['quiz_post_id'] ) ) && ( ! empty( $shortcode_atts['quiz_post_id'] ) ) ) {
						$quiz_post_ids[ $quiz_pro_id ] = absint( $shortcode_atts['quiz_post_id'] );
						return $quiz_post_ids[ $quiz_pro_id ];
					} elseif ( ( isset( $shortcode_atts['quiz_id'] ) ) && ( ! empty( $shortcode_atts['quiz_id'] ) ) ) {
						$quiz_post_ids[ $quiz_pro_id ] = absint( $shortcode_atts['quiz_id'] );
						return $quiz_post_ids[ $quiz_pro_id ];
					} elseif ( ( isset( $shortcode_atts['quiz'] ) ) && ( ! empty( $shortcode_atts['quiz'] ) ) ) {
						$quiz_post_ids[ $quiz_pro_id ] = absint( $shortcode_atts['quiz'] );
						return $quiz_post_ids[ $quiz_pro_id ];
					}
				}
			}
		}

		// Before we run all the queries we check the global $post and see if we are showing a Quiz Post.
		$queried_object = get_queried_object();
		if ( ( is_a( $queried_object, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $queried_object->post_type ) ) {
			$quiz_post_ids[ $quiz_pro_id ] = absint( $queried_object->ID );
			return $quiz_post_ids[ $quiz_pro_id ];
		}

		/*
		$post_id = get_the_ID();
		if ( ! empty( $post_id ) ) {
			$quiz_post = get_post( $post_id );
			if ( ( $quiz_post instanceof WP_Post ) && ( $quiz_post->post_type == 'sfwd-quiz' ) ) {
				//$quiz_post_id = $quiz_post->ID;
				$quiz_pro_id_tmp = learndash_get_setting( $quiz_post->ID, 'quiz_pro' );
				if ( ( $quiz_pro_id_tmp ) && ( absint( $quiz_pro_id_tmp ) === $quiz_pro_id ) ) {
					$quiz_post_ids[ $quiz_pro_id ] = absint( $quiz_pro_id_tmp );
					return $quiz_post_ids[ $quiz_pro_id ];
				}
			}
		}
		*/


		$sql_str = $wpdb->prepare( "SELECT post_id FROM " . $wpdb->postmeta . " as postmeta INNER JOIN " . $wpdb->posts . " as posts ON posts.ID=postmeta.post_id
			WHERE posts.post_type = %s AND posts.post_status = %s AND postmeta.meta_key = %s", 'sfwd-quiz', 'publish', 'quiz_pro_id_' . absint( $quiz_pro_id ) );
		$quiz_post_id = $wpdb->get_var( $sql_str );
		if ( ! empty( $quiz_post_id ) ) {
			$quiz_post_ids[ $quiz_pro_id ] = absint( $quiz_post_id );
			return $quiz_post_ids[ $quiz_pro_id ];
		}

		$sql_str = $wpdb->prepare( "SELECT post_id FROM " . $wpdb->postmeta . " as postmeta INNER JOIN " . $wpdb->posts . " as posts ON posts.ID=postmeta.post_id
			WHERE posts.post_type = %s AND posts.post_status = %s AND meta_key = %s AND meta_value = %d", 'sfwd-quiz', 'publish', 'quiz_pro_id', absint( $quiz_pro_id ));
		$quiz_post_id = $wpdb->get_var( $sql_str );
		if ( ! empty( $quiz_post_id ) ) {
			update_post_meta( absint( $quiz_post_id ), 'quiz_pro_id_' . absint( $quiz_pro_id ), absint( $quiz_pro_id ) );
			$quiz_post_ids[ $quiz_pro_id ] = absint( $quiz_post_id );
			return $quiz_post_ids[ $quiz_pro_id ];
		}

		// Because we seem to have a mix of int and string values when these are serialized the format to look for end up being somewhat kludge-y.
		$quiz_pro_id_str = sprintf( '%s', absint( $quiz_pro_id ) );
		$quiz_pro_id_len = strlen( $quiz_pro_id_str );

		$like_i = 'sfwd-quiz_quiz_pro";i:' . absint( $quiz_pro_id ) . ';';
		$like_s = '"sfwd-quiz_quiz_pro";s:' . $quiz_pro_id_len . ':"' . $quiz_pro_id_str . '"';

		// Using REGEX because it is slightly faster then OR on text fields pattern search.
		$sql_str = $wpdb->prepare( "SELECT post_id FROM " . $wpdb->postmeta . " as postmeta INNER JOIN " . $wpdb->posts . " as posts ON posts.ID=postmeta.post_id WHERE posts.post_type = %s AND posts.post_status = %s AND postmeta.meta_key=%s AND postmeta.meta_value REGEXP '" . $like_i . "|" . $like_s . "'", 'sfwd-quiz', 'publish', '_sfwd-quiz' );
		$quiz_post_id = $wpdb->get_var( $sql_str );
		if ( ! empty( $quiz_post_id ) ) {
			$quiz_post_id = absint( $quiz_post_id );
			update_post_meta( $quiz_post_id, 'quiz_pro_id_' . absint( $quiz_pro_id ), absint( $quiz_pro_id ) );
			update_post_meta( $quiz_post_id, 'quiz_pro_id', absint( $quiz_pro_id ) );
			$quiz_post_ids[ $quiz_pro_id ] = $quiz_post_id;
			return $quiz_post_ids[ $quiz_pro_id ];
		}
	}
}

/**
 * Get a Question Post ID from the Proquiz ID
 *
 * @since 2.6.0
 *
 * @param int $question_pro_id ProQuiz Question id.
 * @return int quiz post id
 */
function learndash_get_question_post_by_pro_id( $question_pro_id = 0 ) {
	global $wpdb;

	if ( empty( $question_pro_id ) ) {
		return;
	}

	$question_pro_args = array(
		'post_type'      => learndash_get_post_type_slug( 'question' ),
		'posts_per_page' => 1,
		'post_status'    => 'any',
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => 'question_pro_id',
				'value'   => $question_pro_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			),
		),
	);

	$question_pro_query = new WP_Query( $question_pro_args );
	if ( ( is_a( $question_pro_query, 'WP_Query' ) ) && ( property_exists( $question_pro_query, 'posts' ) ) ) {
		if ( ( ! empty( $question_pro_query->posts ) ) && ( isset( $question_pro_query->posts[0] ) ) ) {
			return $question_pro_query->posts[0];
		}
	}
}

/**
 * Action hook called when a Question (sfwd-question) is moved to trash or untrashed.
 *
 * @since 2.6.0
 *
 * @param string $new_status New post_status value.
 * @param string $old_status Old post_status value.
 * @param Object $post WP_Post object instance.
 */
function learndash_transition_quiz_question_post_status( $new_status, $old_status, $post ) {
	global $wpdb;

	if ( $new_status !== $old_status ) {
		if ( ( ! empty( $post ) ) && ( is_a( $post, 'WP_Post' ) ) && ( in_array( $post->post_type, array( 'sfwd-question' ) ) ) === true ) {

			$sql_str = "SELECT meta_value FROM " . $wpdb->postmeta . " WHERE post_id = " . $post->ID . " AND (meta_key = 'quiz_id' OR meta_key LIKE 'ld_quiz_%')";
			$quiz_ids = $wpdb->get_col( $sql_str );
			if ( ! empty( $quiz_ids ) ) {
				$quiz_ids = array_unique( $quiz_ids );
				foreach ( $quiz_ids as $quiz_id ) {
					learndash_set_quiz_questions_dirty( $quiz_id );
				}
			}
		}
	}
}
add_action( 'transition_post_status', 'learndash_transition_quiz_question_post_status', 10, 3 );


/**
 * Interface function to set the Quiz 'dirty' flag for questions.
 * This 'dirty' flag is used to trigger the Quiz logic to relod the questions
 * via queries instead of using the stored questions post meta. This generally
 * means something changed with the questions.
 *
 * @since 2.6.0
 * @param integer $quiz_id Quiz ID to change dirty flag.
 */
function learndash_set_quiz_questions_dirty( $quiz_id = 0 ) {
	if ( ! empty( $quiz_id ) ) {
		$quiz_questions_object = LDLMS_Factory_Post::quiz_questions( absint( $quiz_id ) );
		if ( is_a( $quiz_questions_object, 'LDLMS_Quiz_Questions' ) ) {
			$quiz_questions_object->set_questions_dirty();
		}
	}
}

/**
 * For a given Question post gather all the quiz posts and set each as
 * questions dirty.
 *
 * @since 2.6.0
 * @param integer $question_post_id Question Post ID.
 */
function learndash_set_question_quizzes_dirty( $question_post_id = 0 ) {
	$question_post_id = absint( $question_post_id );
	if ( ! empty( $question_post_id ) ) {
		$question_quiz_ids = learndash_get_quizzes_for_question( $question_post_id, true );
		if ( ! empty( $question_quiz_ids ) ) {
			foreach ( $question_quiz_ids as $question_quiz_id => $quiz_title ) {
				learndash_set_quiz_questions_dirty( $question_quiz_id );
			}
		}
	}
}

/**
 * Adds a WPProQuiz question to mirror a Question post (sfwd-question).
 *
 * @since 2.6.0
 * @param integer $question_pro_id Post ID of Question (sfwd-question).
 * @param array   $post_data Post Data containing post_title and post_content.
 * @return integer new question pro id.
 */
function learndash_update_pro_question( $question_pro_id = 0, $post_data = array() ) {
	$question_pro_id = absint( $question_pro_id );

	$question_mapper = new WpProQuiz_Model_QuestionMapper();

	if ( isset( $post_data['action'] ) ) {
		switch ( $post_data['action'] ) {
			case 'editpost':
				$proquiz_controller_question = new WpProQuiz_Controller_Question();
				$question_model = $proquiz_controller_question->getPostQuestionModel( 0, $question_pro_id );
				break;

			case 'new_step':
				$proquiz_controller_question = new WpProQuiz_Controller_Question();
				$question_model = $proquiz_controller_question->getPostQuestionModel( 0, $question_pro_id );
				break;

			case 'edit_title':
				$question_model = $question_mapper->fetchById( absint( $question_pro_id ) );
				break;

			default:
				break;

		}
	}

	if ( ( isset( $question_model ) ) && ( is_a( $question_model, 'WpProQuiz_Model_Question' ) ) ) {
		if ( ( isset( $post_data['post_type'] ) ) && ( $post_data['post_type'] === learndash_get_post_type_slug( 'question' ) ) ) {
			if ( isset( $post_data['post_title'] ) ) {
				$question_model->setTitle( $post_data['post_title'] );
			}
			if ( isset( $post_data['post_content'] ) ) {
				$question_model->setQuestion( $post_data['post_content'] );
			}

			if ( ( isset( $post_data['post_ID'] ) ) && ( ! empty( $post_data['post_ID'] ) ) ) {
				$quiz_post_id = learndash_get_setting( $post_data['post_ID'], 'quiz' );
				if ( ! empty( $quiz_post_id ) ) {
					$quiz_post_id = absint( $quiz_post_id );
					$quiz_pro_id = learndash_get_setting( $quiz_post_id, 'quiz_pro' );
					if ( ! empty( $quiz_pro_id ) ) {
						$question_model->setQuizId( $quiz_pro_id );
					}
				}
			}
		}

		$question = $question_mapper->save( $question_model, true );

		learndash_update_question_template( $question, $post_data );

		// After the save we check the question ID in case WPProQuiz changed it.
		$question_pro_id = $question->getId();
		return $question_pro_id;
	}
}

/**
 * Handle the Question Save Template logic.
 *
 * @since 2.6.0
 * @param object $question WpProQuiz_Model_Question instance.
 * @param array  $post_data $_POST data to process the templated related fields.
 * @return mixed on success WpProQuiz_Model_Template instance.
 */
function learndash_update_question_template( $question = null, $post_data = array() ) {
	if ( ( ! empty( $post_data ) ) && ( ! empty( $question ) ) ) {
		$template_mapper = new WpProQuiz_Model_TemplateMapper();
		if ( ( isset( $post_data['templateName'] ) ) && ( ! empty( $post_data['templateName'] ) ) ) {
			$template = new WpProQuiz_Model_Template();
			$template->setType( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUESTION );
			$template->setName( trim( $post_data['templateName'] ) );
		} else if ( ( isset( $post_data['templateSaveList'] ) ) && ( ! empty( $post_data['templateSaveList'] ) ) ) {
			$template = $template_mapper->fetchById( absint( $post_data['templateSaveList'] ), false );
		}

		if ( ( isset( $template ) ) && ( is_a( $template, 'WpProQuiz_Model_Template' ) ) ) {
			$template->setData( array(
				'question' => $question,
			) );

			return $template_mapper->save( $template );
		}
	}
}

/**
 * Gets an array of Quiz IDS where the question is used.
 *
 * @since 2.6.0
 * @param integer $question_post_id Question Post ID.
 * @param boolean $return_flat_array Default is false and will return primary and secondary sub-array sets.
 * @return array of Quiz post IDs.
 */
function learndash_get_quizzes_for_question( $question_post_id = 0, $return_flat_array = false ) {
	global $wpdb;

	$quiz_ids = array();

	if ( true !== $return_flat_array ) {
		$course_ids['primary'] = array();
		$course_ids['secondary'] = array();
	}

	if ( ! empty( $question_post_id ) ) {
		$sql_str = $wpdb->prepare( "SELECT postmeta.meta_value as quiz_id, posts.post_title as quiz_title FROM " . $wpdb->postmeta . " AS postmeta
				INNER JOIN " . $wpdb->posts . " AS posts ON postmeta.meta_value = posts.ID WHERE postmeta.post_id = " . $question_post_id . " AND postmeta.meta_key LIKE %s ORDER BY quiz_title ASC", 'quiz_id' );
		$quiz_ids_primary = $wpdb->get_results( $sql_str );
		if ( ! empty( $quiz_ids_primary ) ) {
			foreach ( $quiz_ids_primary as $quiz_set ) {
				if ( true === $return_flat_array ) {
					$quiz_ids[ $quiz_set->quiz_id ] = $quiz_set->quiz_title;
				} else {
					$quiz_ids['primary'][ $quiz_set->quiz_id ] = $quiz_set->quiz_title;
				}
			}
		}

		$sql_str = "SELECT postmeta.meta_value as quiz_id, posts.post_title as quiz_title FROM " . $wpdb->postmeta . " AS postmeta
			INNER JOIN " . $wpdb->posts . " AS posts ON postmeta.meta_value = posts.ID WHERE postmeta.post_id = " . $question_post_id . " AND postmeta.meta_key LIKE 'ld_quiz_%' ORDER BY quiz_title ASC" ;
		$quiz_ids_secondary = $wpdb->get_results( $sql_str );
		if ( ! empty( $quiz_ids_secondary ) ) {
			foreach ( $quiz_ids_secondary as $quiz_set ) {
				if ( true === $return_flat_array ) {
					if ( ! isset( $quiz_ids[ $quiz_set->quiz_id ] ) ) {
						$quiz_ids[ $quiz_set->quiz_id ] = $quiz_set->quiz_title;
					}
				} else {
					if ( ( ! isset( $quiz_ids['primary'][ $quiz_set->quiz_id ] ) ) && ( ! isset( $quiz_ids['secondary'][ $quiz_set->quiz_id ] ) ) ) {
						$quiz_ids['secondary'][ $quiz_set->quiz_id ] = $quiz_set->quiz_title;
					}
				}
			}
		}

		return $quiz_ids;
	}
}

/**
 * Determine the Quiz ID based on the global post being viewed.
 *
 * @since 2.6.0
 * @param integer $id Post ID being viewed.
 * @return integer The found quiz ID or false.
 */
function learndash_get_quiz_id( $id = null ) {
	global $post;

	if ( is_object( $id ) && $id->ID ) {
		$p = $id;
		$id = $p->ID;
	} elseif ( is_numeric( $id ) ) {
		$p = get_post( $id );
	}

	if ( empty( $id ) ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			//return false;
		} else {
			if ( is_admin() ) {
				global $parent_file, $post_type, $pagenow;
				if ( ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) || ( ! in_array( $post_type, array( 'sfwd-question' ) ) ) ) {
					return false;
				}

			} else if ( ! is_single() || is_home() ) {
				return false;
			}
		}

		if ( ( $post ) && ( $post instanceof WP_Post ) ) {
			$id = $post->ID;
			$p = $post;
		} else {
			return false;
		}
	}

	if ( empty( $p->ID ) ) {
		return 0;
	}

	if ( learndash_get_post_type_slug( 'quiz' ) === $p->post_type ) {
		return $p->ID;
	}

	if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
		return absint( $_GET['quiz_id'] );
	} elseif ( ( isset( $_GET['quiz'] ) ) && ( ! empty( $_GET['quiz'] ) ) ) {
		return absint( $_GET['quiz'] );
	} elseif ( ( isset( $_POST['quiz_id'] ) ) && ( ! empty( $_POST['quiz_id'] ) ) ) {
		return absint( $_POST['quiz_id'] );
	} else if ( ( isset( $_POST['quiz'] ) ) && ( ! empty( $_POST['quiz'] ) ) ) {
		return intval( $_POST['quiz'] );
	} else if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
		if ( learndash_get_post_type_slug( 'quiz' ) === get_post_type( intval( $_GET['post'] ) ) ) {
			return intval( $_GET['post'] );
		}
	}

	return (int) get_post_meta( $id, 'quiz_id', true );
}


/**
 * Add content to quiz navigation meta box for admin
 *
 * @since 2.6.0
 */
function learndash_quiz_navigation_admin_box_content() {
	global $typenow;

	$quiz_id = 0;
	$current_post = false;

	if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
		$quiz_id = learndash_get_quiz_id( absint( $_GET['post'] ) );
		$current_post = get_post( intval( $_GET['post'] ) );
	}

	if ( ( empty( $quiz_id ) ) && ( isset( $_GET['quiz_id'] ) ) ) {
		$quiz_id = absint( $_GET['quiz_id'] );
	}

	if ( ! empty( $quiz_id ) ) {

		$instance = array();
		$instance['show_widget_wrapper'] = true;
		$instance['quiz_id'] = $quiz_id;
		$instance['current_question_id'] = 0;
		$instance['current_type'] = $typenow;

		$question_query_args = array();
		$question_query_args['pagination'] = 'true';
		$question_query_args['paged'] = 1;

		if ( ( is_a( $current_post, 'WP_Post' ) ) && ( in_array( $current_post->post_type, array( 'sfwd-quiz', 'sfwd-question' ) ) ) ) {
			if ( in_array( $current_post->post_type, array( 'sfwd-question' ) ) ) {
				$instance['current_question_id'] = $current_post->ID;
			}
		}

		learndash_quiz_navigation_admin( $quiz_id, $instance, $question_query_args );

		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) == 'yes' ) {
			learndash_quiz_switcher_admin( $quiz_id );
		}
	} else {
		echo sprintf(
			// translators: placeholders: Questions.
			esc_html_x( 'No associated %s', 'placeholder: Questions', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'questions' )
		);
		echo sprintf(
			'<div class="quiz_navigation_app"></div>',
			esc_attr( $quiz_id )
		);
	}
}


/**
 * Utility function to get the questions associated with a Quiz
 *
 * @since 2.6.0
 * @param integer $quiz_id The Quiz Post ID.
 * @return array of quiz question (sfwd-question) post ids.
 */
function learndash_get_quiz_questions( $quiz_id = 0 ) {
	if ( ! empty( $quiz_id ) ) {
		$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( absint( $quiz_id ) );
		if ( $ld_quiz_questions_object ) {
			$ld_quiz_questions = $ld_quiz_questions_object->get_questions();
			return $ld_quiz_questions;
		}
	}
}

/**
 * Outputs quiz navigation admin template for widget
 *
 * @since 2.6.0
 *
 * @param integer $quiz_id Quiz Post ID.
 * @param array   $instance Widget instance array.
 * @param array   $question_query_args array of query options for pagnination etc.
 *
 * @return string quiz navigation output
 */
function learndash_quiz_navigation_admin( $quiz_id = 0, $instance = array(), $question_query_args = array() ) {
	if ( empty( $quiz_id ) ) {
		return;
	}

	$quiz = get_post( absint( $quiz_id ) );
	if ( ( ! $quiz ) || ( ! is_a( $quiz, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'quiz' ) !== $quiz->post_type ) ) {
		return;
	}

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		$user_id = 0;
	}

	$instance['nonce'] = wp_create_nonce( 'ld_quiz_navigation_admin_pager_nonce_' . $quiz->ID . '_' . get_current_user_id() );

	$quiz_navigation_admin_pager = array();
	global $quiz_navigation_admin_pager;

	$question_start_idx = 0;

	$question_list = learndash_get_quiz_questions( $quiz_id );
	if ( ! empty( $question_list ) ) {
		//$course_lessons_per_page = learndash_get_course_lessons_per_page( $quiz_id );
		$quiz_questions_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'question_num' );
		if ( ( $quiz_questions_per_page > 0 ) && ( count( $question_list ) > $quiz_questions_per_page ) ) {
			$quiz_navigation_admin_pager['per_page'] = absint( $quiz_questions_per_page );
			$quiz_navigation_admin_pager['total_items'] = count( $question_list );

			$questions_page_chunks = array_chunk( $question_list, $quiz_navigation_admin_pager['per_page'], true );
			$quiz_navigation_admin_pager['total_pages'] = count( $questions_page_chunks );

			$quiz_navigation_admin_pager['paged'] = 1;
			if ( ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
				$quiz_navigation_admin_pager['paged'] = absint( $_POST['paged'] );
			} else {
				foreach( $questions_page_chunks as $paged_idx => $paged_set ) {
					if ( isset( $paged_set[ $instance['current_question_id'] ] ) ) {
						$quiz_navigation_admin_pager['paged'] = $paged_idx + 1;
						break;
					}
				}
			}

			$chunks_paged = $quiz_navigation_admin_pager['paged'] - 1;
			if ( isset( $questions_page_chunks[ $chunks_paged ] ) ) {
				$question_list = $questions_page_chunks[ $chunks_paged ];
			} else {
				$question_list = $questions_page_chunks[0];
			}
		}
	} else {
		echo sprintf(
			// translators: placeholders: Questions.
			esc_html_x( 'No associated %s', 'placeholder: Questions', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'questions' )
		);
	}

	SFWD_LMS::get_template(
		'quiz_navigation_admin',
		array(
			'user_id'            => $user_id,
			'quiz_id'            => $quiz_id,
			'widget'             => $instance,
			'questions_list'     => $question_list,
		),
		true
	);
}

/**
 * Show the Quiz Switcher within the Quiz Questions Admin metabox.
 *
 * @since 2.6.0
 * @param integer $quiz_id Quiz Post ID.
 */
function learndash_quiz_switcher_admin( $quiz_id ) {
	$template_file = SFWD_LMS::get_template(
		'quiz_navigation_switcher_admin',
		array(),
		null,
		true
	);
	
	if ( ! empty( $template_file ) ) {
		include $template_file;
	}
}

/**
 * Quiz Questions Navigation AJAX Pager handler function
 *
 * @since 2.5.4
 */
function learndash_wp_ajax_ld_quiz_navigation_admin_pager() {
	$reply_data = array();

	if ( ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
		$paged = intval( $_POST['paged'] );
	} else {
		$paged = 1;
	}

	if ( ( isset( $_POST['widget_data'] ) ) && ( ! empty( $_POST['widget_data'] ) ) ) {
		$widget_data = $_POST['widget_data'];
	} else {
		$widget_data = array();
	}

	if ( ( isset( $widget_data['quiz_id'] ) ) && ( ! empty( $widget_data['quiz_id'] ) ) ) {
		$quiz_id = intval( $widget_data['quiz_id'] );
	} else {
		$quiz_id = 0;
	}

	if ( ( ! empty( $quiz_id ) ) && ( ! empty( $widget_data ) ) ) {
		if ( ( isset( $_POST['widget_data']['nonce'] ) ) && ( ! empty( $_POST['widget_data']['nonce'] ) ) && ( wp_verify_nonce( $_POST['widget_data']['nonce'], 'ld_quiz_navigation_admin_pager_nonce_' . $quiz_id . '_' . get_current_user_id() ) ) ) {
			$questions_query_args = array();
			//$course_lessons_per_page = learndash_get_course_lessons_per_page( $course_id );
			//if ( $course_lessons_per_page > 0 ) {		
				$questions_query_args['pagination'] = 'true';
				$questions_query_args['paged'] = $paged;
			//}
			$widget_data['show_widget_wrapper'] = false;
			$level = ob_get_level();
			ob_start();
			learndash_quiz_navigation_admin( $quiz_id, $widget_data, $questions_query_args );
			$reply_data['content'] = learndash_ob_get_clean( $level );
		}
	}

	echo json_encode( $reply_data );
	die();
}
add_action( 'wp_ajax_ld_quiz_navigation_admin_pager', 'learndash_wp_ajax_ld_quiz_navigation_admin_pager' );


/**
 * This function will copy the WPProQuiz Question Category to the LearnDadh Question taxonomy
 *
 * @since 2.6.0
 * @param integer $question_post_id WP_Post Question ID.
 * @param integer $question_pro_id WpProQuiz_Model_Question object or ID.
 */
function learndash_proquiz_sync_question_fields( $question_post_id = 0, $question_pro_id = 0 ) {

	if ( ( empty( $question_post_id ) ) || ( empty( $question_pro_id ) ) ) {
		return;
	}

	if ( is_a( $question_pro_id, 'WpProQuiz_Model_Question' ) ) {
		$question_pro = $question_pro_id;
	} else {
		$question_pro_mapper = new WpProQuiz_Model_QuestionMapper();
		$question_pro = $question_pro_mapper->fetch( absint( $question_pro_id ) );
	}

	if ( is_a( $question_pro, 'WpProQuiz_Model_Question' ) ) {

		update_post_meta( $question_post_id, 'question_points', intval( $question_pro->getPoints() ) );
		update_post_meta( $question_post_id, 'question_type', $question_pro->getAnswerType() );
		update_post_meta( $question_post_id, 'question_pro_id', intval( $question_pro->getId() ) );
		update_post_meta( $question_post_id, 'question_pro_category', intval( $question_pro->getCategoryId() ) );

		// Not sure why this is here.
		/*
		$update_post = array(
			'ID'           => $question_post_id,
			'post_title'   => $question_pro->getTitle(),
			'post_content' => $question_pro->getQuestion(),
			'menu_order'   => absint( $question_pro->getSort() ),
		);
		wp_update_post( $update_post );
		*/

		/*
		$quiz_post_ids = learndash_get_quiz_post_ids( $question_pro->getQuizId() );
		if ( ! empty( $quiz_post_ids ) ) {
			foreach ( $quiz_post_ids as $idx => $quiz_post_id ) {
				if ( 0 === $idx ) {
					learndash_update_setting( $question_post_id, 'quiz', absint( $quiz_post_id ) );
				}
				//if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) === 'yes' ) {
					add_post_meta( $question_post_id, 'ld_quiz_id', intval( $quiz_post_id ), true );
				//}
			}
		}
		*/
	}
}

/**
 * This function will copy the WPProQuiz Question Category to the LearnDadh Question taxonomy
 *
 * @since 2.6.0
 * @param integer $question_post_id WP_Post Question ID.
 * @param integer $question_pro_id WpProQuiz_Model_Question object or ID.
 * @return object WP_Term object.
 */
function learndash_proquiz_sync_question_category( $question_post_id = 0, $question_pro_id = 0 ) {

	if ( ( empty( $question_post_id ) ) || ( empty( $question_pro_id ) ) ) {
		return;
	}

	if ( is_a( $question_pro_id, 'WpProQuiz_Model_Question' ) ) {
		$question_pro = $question_pro_id;
	} else {
		$question_pro_mapper = new WpProQuiz_Model_QuestionMapper();
		$question_pro = $question_pro_mapper->fetch( absint( $question_pro_id ) );
	}

	if ( is_a( $question_pro, 'WpProQuiz_Model_Question' ) ) {

		// Sync the Question category with the LD Question Category.
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Questions_Taxonomies', 'ld_question_category' ) == 'yes' ) {
			$question_pro_category_id = $question_pro->getCategoryId();
			$question_pro_category_name = $question_pro->getCategoryName();
			if ( ( ! empty( $question_pro_category_id ) ) && ( ! empty( $question_pro_category_name ) ) ) {
				$category_query_args = array(
					'taxonomy'   => array( 'ld_question_category' ),
					'hide_empty' => false,
					'name'       => $question_pro_category_name,
				);
				$category_terms = get_terms( $category_query_args );
				if ( ! is_wp_error( $category_terms ) ) {
					if ( ! empty( $category_terms ) ) {
						foreach ( $category_terms as $category_term ) {
							wp_set_object_terms( $question_post_id, $category_term->term_id, 'ld_question_category' );
						}
						return $category_terms;

					} else {
						$new_term = wp_insert_term( $question_pro_category_name, 'ld_question_category' );
						if ( isset( $new_term['term_id'] ) ) {
							add_term_meta( absint( $new_term['term_id'] ), 'category_pro_id', $question_pro_category_id );
							wp_set_object_terms( $question_post_id, intval( $new_term['term_id'] ), 'ld_question_category' );

							return $new_term;
						}
					}
				}
			}
		}
	}
}

/**
 * Utility function to return all the quiz posts IDs based on the quiz pro id.
 * This is similar to the function learndash_get_quiz_id_by_pro_quiz_id() but returns
 * an array instead of a single post ID.
 *
 * @since 2.6.0
 * @param integer $quiz_pro_id ID of WPProQuiz Quiz.
 * @return array of quiz post IDs.
 */
function learndash_get_quiz_post_ids( $quiz_pro_id = 0 ) {
	static $quiz_post_ids = array();
	$quiz_pro_id = absint( $quiz_pro_id );
	if ( ! empty( $quiz_pro_id ) ) {
		if ( ! isset( $quiz_post_ids[ $quiz_pro_id ] ) ) {
			$quiz_post_ids[ $quiz_pro_id ] = array();

			if ( ! empty( $quiz_pro_id ) ) {
				$quiz_query_args = array(
					'post_type'      => learndash_get_post_type_slug( 'quiz' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'meta_query'     => array(
						array(
							'key'     => 'quiz_pro_id',
							'value'   => absint( $quiz_pro_id ),
							'compare' => '=',
						),
					),
				);

				$quiz_query = new WP_Query( $quiz_query_args );
				if ( ( $quiz_query instanceof WP_Query ) && ( property_exists( $quiz_query, 'posts' ) ) ) {
					$quiz_post_ids[ $quiz_pro_id ] = array_merge( $quiz_post_ids[ $quiz_pro_id ], $quiz_query->posts );
				}
			}
		}

		return $quiz_post_ids[ $quiz_pro_id ];
	}
}

/**
 * This function retreives the WPProQuiz Question row column by field.
 *
 * @since 2.6.0
 * @param integer $question_pro_id WPProQuiz Question ID.
 * @param mixed   $fields Array or string of fields to return.
 * @return array of field values.
 */
function leandash_get_question_pro_fields( $question_pro_id = 0, $fields = null ) {
	$values = array();

	if ( ( ! empty( $question_pro_id ) ) && ( ! empty( $fields ) ) ) {
		if ( is_string( $fields ) ) {
			$fields = explode( ',', $fields );
		}
		if ( is_array( $fields ) ) {
			$fields = array_map( 'trim', $fields );
		}

		$question_mapper = new WpProQuiz_Model_QuestionMapper();
		$question_pro = $question_mapper->fetch( $question_pro_id );

		foreach ( $fields as $field ) {
			$function = 'get' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $field ) ) );
			if ( method_exists( $question_pro, $function ) ) {
				$values[ $field ] = $question_pro->$function();
			} else {
				$values[ $field ] = null;
			}
		}

		return $values;
	}

	return $values;
}

/**
 * This function retreives the WPProQuiz Quiz row column by field.
 *
 * @since 2.6.0
 * @param integer $question_pro_id WPProQuiz Question ID.
 * @param mixed   $fields Array or string of fields to return.
 * @return array of field values.
 */
function leandash_get_quiz_pro_fields( $quiz_pro_id = 0, $fields = null ) {
	$values = array();

	if ( ( ! empty( $quiz_pro_id ) ) && ( ! empty( $fields ) ) ) {
		if ( is_string( $fields ) ) {
			$fields = explode( ',', $fields );
		}
		if ( is_array( $fields ) ) {
			$fields = array_map( 'trim', $fields );
		}

		$quiz_mapper = new WpProQuiz_Model_QuizMapper();
		$quiz_pro = $quiz_mapper->fetch( $quiz_pro_id );

		foreach ( $fields as $field ) {
			$function = 'get' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $field ) ) );
			if ( method_exists( $quiz_pro, $function ) ) {
				$values[ $field ] = $quiz_pro->$function();
			} else {
				$values[ $field ] = null;
			}
		}

		return $values;
	}

	return $values;
}

/**
 * This function accepts a list of Quiz posts. It is assumed quiz posts
 * all share the same ProQuiz Quiz ID. This function will determine which
 * is the 'primary' quiz post. If one is not found the first in the array
 * will be set as the primary.
 *
 * @since 2.6.0
 * @param integer $quiz_pro_id ProQuiz Quiz ID.
 * @param boolean $set_first If true will take first quiz post fount and use as primary.
 * @return integer Primary Quiz Post ID.
 */
function learndash_get_quiz_primary_shared( $quiz_pro_id = 0, $set_first = true ) {
	static $quiz_primary_post_ids = array();

	$quiz_pro_id = absint( $quiz_pro_id );
	if ( ! empty( $quiz_pro_id ) ) {
		if ( ( ! isset( $quiz_primary_post_ids[ $quiz_pro_id ] ) ) || ( empty( $quiz_primary_post_ids[ $quiz_pro_id ] ) ) ) {
			$quiz_primary_post_ids[ $quiz_pro_id ] = 0;
			$quiz_post_ids = learndash_get_quiz_post_ids( $quiz_pro_id );
			if ( ! empty( $quiz_post_ids ) ) {

				$quiz_query_args = array(
					'post_type'      => learndash_get_post_type_slug( 'quiz' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'post__in'       => $quiz_post_ids,
					'meta_query'     => array(
						array(
							'key'     => 'quiz_pro_primary_' . $quiz_pro_id,
							'compare' => 'EXISTS',
						),
					),
				);

				$quiz_query = new WP_Query( $quiz_query_args );
				if ( ( is_a( $quiz_query, 'WP_Query' ) ) && ( property_exists( $quiz_query, 'posts' ) ) && ( ! empty( $quiz_query->posts ) ) ) {
					$quiz_primary_post_ids[ $quiz_pro_id ] = $quiz_query->posts[0];
					if ( count( $quiz_query->posts ) > 1 ) {
						foreach ( $quiz_query->posts as $quiz_post_idx => $quiz_post_id ) {
							if ( 0 !== $quiz_post_idx ) {
								delete_post_meta( $quiz_post_id, 'quiz_pro_primary_' . $quiz_pro_id );
							}
						}
					}
				} else {
					if ( true === $set_first ) {
						$quiz_primary_post_ids[ $quiz_pro_id ] = $quiz_post_ids[0];
						update_post_meta( $quiz_primary_post_ids[ $quiz_pro_id ], 'quiz_pro_primary_' . $quiz_pro_id, $quiz_pro_id );
					}
				}
			}
		}

		return $quiz_primary_post_ids[ $quiz_pro_id ];
	}
}


function learndash_quiz_result_message_sort( $messages = array() ) {
	$sorted = array();
	if ( ( isset( $messages ) ) && ( ! empty( $messages ) ) ) {
		$activ_bypass = false;
		if ( ! isset( $messages['activ'] ) ) {
			$activ_bypass = true;
		}

		for ( $i = 0; $i < LEARNDASH_QUIZ_RESULT_MESSAGE_MAX; $i++ ) {
			
			if ( true === $activ_bypass ) {
				$activ = 1;
			} else {
				$activ = null;
				if ( isset( $messages['activ'][$i] ) ) {
					$activ = absint( $messages['activ'][$i] );
				}
			}

			$prozent = null;
			if ( isset( $messages['prozent'][$i] ) ) {
				$prozent = (float)str_replace(',', '.', $messages['prozent'][$i] );
			}

			$text = null;
			if ( isset( $messages['text'][$i] ) ) {
				$text = $messages['text'][$i];
				if ( ! empty( $text ) ) {
					$text = wp_check_invalid_utf8( $text );
					if ( ! empty( $text ) ) {
						$text = sanitize_post_field( 'post_content', $text, 0, 'display' );
						$text = stripslashes( $text );
					}
				} else {
					$activ = null;
				}
			}

			if ( ( ! is_null( $activ ) ) && ( ! empty( $activ ) ) && ( ! is_null( $prozent ) ) && ( ! is_null( $text ) ) ) {
				if ( ! isset( $sorted[ $prozent ] ) ) {
					$sorted[ $prozent ] = array(
						'prozent' => $prozent,
						'activ' => $activ,
						'text' => $text,
					);
				}
			}
		}
	}

	if ( ! isset( $sorted[0] ) ) {
		$sorted[0] = array(
			'prozent' => 0,
			'activ' => 1,
			'text' => '',
		);
	}

	$result = array();
	if ( ! empty( $sorted ) ) {
		ksort( $sorted );
		
		foreach ( $sorted as $item ) {
			$result['text'][] = $item['text'];
			$result['prozent'][] = $item['prozent'];
			$result['activ'][] = $item['activ'];
		}
	}
	
	return $result;
}