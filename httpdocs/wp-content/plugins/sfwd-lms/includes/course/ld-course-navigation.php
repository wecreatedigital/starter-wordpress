<?php
/**
 * Function that help the user navigate through the course
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Navigation
 */


/**
 * Generate previous post link for lesson or topic
 *
 * @since 2.1.0
 * 
 * @param  string  $prevlink
 * @param  boolean $url      return a url instead of HTML link
 * @return string            previous post link output
 */
function learndash_previous_post_link( $prevlink='', $url = false, $post = null ) {
if ( empty( $post) ) {
		global $post;
	}

	if ( empty( $post) ) {
		return $prevlink;
	}

	if ( $post->post_type == 'sfwd-lessons' ) {
		$link_name = learndash_get_label_course_step_previous( learndash_get_post_type_slug ( 'lesson' ) );
		$posts = learndash_get_lesson_list( null, array( 'num' => 0 ) );
	} else if ( $post->post_type == 'sfwd-topic' ) {
		$link_name = learndash_get_label_course_step_previous( learndash_get_post_type_slug ( 'topic' ) );
		
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$course_id = learndash_get_course_id( $post );
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post, 'lesson' );
		}
		$posts = learndash_get_topic_list( $lesson_id );
	} else {
		return $prevlink;
	}

	foreach ( $posts as $k => $p ) {
		if ( $p instanceof WP_Post ) {
			if ( $p->ID == $post->ID ) {
				$found_at = $k;
				break;
			}
		}
	}

	if ( isset( $found_at) && ! empty( $posts[ $found_at -1] ) ) {
		if ( 'id' === $url ) {
			return $posts[ $found_at -1]->ID;
		} else if ( $url ) {
			return get_permalink( $posts[ $found_at -1]->ID );
		} else {
			$permalink = get_permalink( $posts[ $found_at + 1]->ID );
			if ( is_rtl() ) {
				$link_name_with_arrow = $link_name;
			} else {
				$link_name_with_arrow = '<span class="meta-nav">&larr;</span> ' . $link_name;
			}

			$link = '<a href="'.$permalink.'" class="prev-link" rel="prev">' . $link_name_with_arrow . '</a>';

			 /**
			 * Filter previous post link output
			 * 
			 * @since 2.1.0
			 * 
			 * @param  string  $link 
			 */
			return apply_filters( 'learndash_previous_post_link', $link, $permalink, $link_name, $post );
		}	

	} else {
		return $prevlink;
	}
}



/**
 * Generate next post link for lesson or topic
 *
 * @since 2.1.0
 * 
 * @param  string  $prevlink
 * @param  boolean $url      return a url instead of HTML link.
 *                           Added 3.1 'id' will return next step post ID.
 * @param  object  $post     WP_Post object
 * @return string            next post link output
 */
function learndash_next_post_link( $prevlink='', $url = false, $post = null ) {
	if ( empty( $post) ) {
		global $post;
	}

	if ( empty( $post) ) {
		return $prevlink;
	}

	if ( $post->post_type == 'sfwd-lessons' ) {
		$link_name = learndash_get_label_course_step_next( learndash_get_post_type_slug( 'lesson' ) );
		$course_id = learndash_get_course_id( $post );
		$posts = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );
	} else if ( $post->post_type == 'sfwd-topic' ) {
		$link_name = learndash_get_label_course_step_next( learndash_get_post_type_slug( 'topic' ) );

		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$course_id = learndash_get_course_id( $post->ID );
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post, 'lesson' );
		}
		$posts = learndash_get_topic_list( $lesson_id );
	} else {
		return $prevlink;
	}

	foreach ( $posts as $k => $p ) {
		
		if ( $p instanceof WP_Post ) {
			if ( $p->ID == $post->ID ) {
				$found_at = $k;
				break;
			}
		}
	}

	if ( isset( $found_at) && ! empty( $posts[ $found_at + 1] ) ) {
		if ( 'id' === $url ) {
			return $posts[ $found_at + 1]->ID;
		} else if ( $url ) {
			return get_permalink( $posts[ $found_at + 1]->ID );
		} else {
			$permalink = get_permalink( $posts[ $found_at + 1]->ID );
			if ( is_rtl() ) {
				$link_name_with_arrow = $link_name ;
			} else {
				$link_name_with_arrow = $link_name . ' <span class="meta-nav">&rarr;</span>';
			}

			$link = '<a href="'.$permalink.'" class="next-link" rel="next">' . $link_name_with_arrow.'</a>';

			 /**
			 * Filter next post link output
			 * 
			 * @since 2.1.0
			 * 
			 * @param  string  $link 
			 */
			return apply_filters( 'learndash_next_post_link', $link, $permalink, $link_name, $post );
		}
//	} else if ( $post->post_type == 'sfwd-topic' ) {
//		if ( ( isset( $lesson_id ) ) && ( ! empty( $lesson_id ) ) ) {
//			$lesson_post = get_post( $lesson_id );
//			if ( ( is_a( $lesson_post, 'WP_Post' ) ) && ( $lesson_post->post_type === learndash_get_post_type_slug( 'lesson' ) ) ) {
//				return learndash_next_post_link( $prevlink, $url, $lesson_post );
//			}
//		}
	} else {
		return $prevlink;
	}
}



/**
 * Don't show previous/next link in certain situations
 *
 * @since 2.1.0
 * 
 * @param  string $prevlink
 * @return string
 */
function learndash_clear_prev_next_links( $prevlink='' ){
	global $post;

	if ( ! is_singular() || empty( $post->post_type) || ( $post->post_type != 'sfwd-lessons' && $post->post_type != 'sfwd-quiz' && $post->post_type != 'sfwd-courses' && $post->post_type != 'sfwd-topic' && $post->post_type != 'sfwd-assignment') ) {
		return $prevlink;
	} else {
		return '';
	}
}

add_filter( 'previous_post_link', 'learndash_clear_prev_next_links', 1, 2 );
add_filter( 'next_post_link', 'learndash_clear_prev_next_links', 1, 2 );



/**
 * Output quiz continue link
 *
 * @since  x.x.
 * 
 * @param  int 		$id 	quiz id
 * @return string   output of link
 */
function learndash_quiz_continue_link( $id ) {
	global $status, $pageQuizzes;

	$course_id = learndash_get_course_id( $id );
	if ( ( !empty( $course_id ) ) && ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) ) {	
		$lesson_id = learndash_course_get_single_parent_step( $course_id, $id );
		if ( empty( $lesson_id ) ) {
			$url = get_permalink( $course_id );
			$url = add_query_arg(
				array( 
					'quiz_type' 	=> 'global',
					'quiz_redirect' => 1,
					'course_id'		=> $course_id,
					'quiz_id'		=> $id
				), 
				$url 
			);
			
		} else {
			$url = get_permalink( $lesson_id );
			$url = add_query_arg(
				array( 
					'quiz_type' 	=> 'lesson',
					'quiz_redirect' => 1,
					'lesson_id'		=> $lesson_id,
					'quiz_id'		=> $id
				), 
				$url 
			);
		}

		if ( ( isset( $url ) ) && ( !empty( $url ) ) ) {
			$returnLink = '<a id="quiz_continue_link" href="'. $url .'">' . esc_html( LearnDash_Custom_Label::get_label( 'button_click_here_to_continue' ) ) . '</a>';
		}
	} else {
		$quizmeta = get_post_meta( $id, '_sfwd-quiz' , true );

		if ( ! empty( $quizmeta['sfwd-quiz_lesson'] ) ) {
			$return_id = $quiz_lesson = $quizmeta['sfwd-quiz_lesson'];
		}

		if ( empty( $quiz_lesson) ) {
			$return_id = $course_id = learndash_get_course_id( $id );
			$url = get_permalink( $return_id );
			$url .= strpos( 'a'.$url, '?' )? '&':'?';
			$url .= 'quiz_type=global&quiz_redirect=1&course_id='.$course_id.'&quiz_id='.$id;
			$returnLink = '<a id="quiz_continue_link" href="'.$url.'">' . esc_html( LearnDash_Custom_Label::get_label( 'button_click_here_to_continue' ) ) . '</a>';
		} else	{
			$url = get_permalink( $return_id );
			$url .= strpos( 'a'.$url, '?' )? '&':'?';
			$url .= 'quiz_type=lesson&quiz_redirect=1&lesson_id='.$return_id.'&quiz_id='.$id;
			$returnLink = '<a id="quiz_continue_link" href="'.$url.'">' . esc_html( LearnDash_Custom_Label::get_label( 'button_click_here_to_continue' ) ) . '</a>';
		}
	}
	
	// Why are we checking the WordPress version? Shouldn't this be checking the LD version??
	$version = get_bloginfo( 'version' );
	
	if ( $version >= '1.5.1' ) {

		 /**
		 * Filter output of quiz continue link
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string  $returnLink
		 */
		return apply_filters( 'learndash_quiz_continue_link', $returnLink, $url );

	} else {

		 /**
		 * Filter output of quiz continue link
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string  $returnLink
		 */
		return apply_filters( 'learndash_quiz_continue_link', $returnLink );

	}
}

function learndash_quiz_continue_link_OLD( $id ) {
	global $status, $pageQuizzes;

	$quizmeta = get_post_meta( $id, '_sfwd-quiz' , true );

	if ( ! empty( $quizmeta['sfwd-quiz_lesson'] ) ) {
		$return_id = $quiz_lesson = $quizmeta['sfwd-quiz_lesson'];
	}

	if ( empty( $quiz_lesson) ) {
		$return_id = $course_id = learndash_get_course_id( $id );
		$url = get_permalink( $return_id );
		$url .= strpos( 'a'.$url, '?' )? '&':'?';
		$url .= 'quiz_type=global&quiz_redirect=1&course_id='.$course_id.'&quiz_id='.$id;
		$returnLink = '<a id="quiz_continue_link" href="'.$url.'">' . esc_html( LearnDash_Custom_Label::get_label( 'button_click_here_to_continue' ) ) . '</a>';
	} else	{
		$url = get_permalink( $return_id );
		$url .= strpos( 'a'.$url, '?' )? '&':'?';
		$url .= 'quiz_type=lesson&quiz_redirect=1&lesson_id='.$return_id.'&quiz_id='.$id;
		$returnLink = '<a id="quiz_continue_link" href="'.$url.'">' . esc_html( LearnDash_Custom_Label::get_label( 'button_click_here_to_continue' ) ) . '</a>';
	}

	// Why are we checking the WordPress version? Shouldn't this be checking the LD version??
	$version = get_bloginfo( 'version' );
	
	if ( $version >= '1.5.1' ) {

		 /**
		 * Filter output of quiz continue link
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string  $returnLink
		 */
		return apply_filters( 'learndash_quiz_continue_link', $returnLink, $url );

	} else {

		 /**
		 * Filter output of quiz continue link
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string  $returnLink
		 */
		return apply_filters( 'learndash_quiz_continue_link', $returnLink );

	}
}



/**
 * Output LearnDash topic dots
 * Indicates name of topic and whether it's been completed
 * 
 * @since 2.1.0
 * 
 * @param  int 		$lesson_id 
 * @param  boolean 	$show_text 
 * @param  string  	$type      	dots|list
 * @param  int  	$user_id   
 * @return string              	output
 */
function learndash_topic_dots( $lesson_id, $show_text = false, $type = 'dots', $user_id = null, $course_id = null ) {
	if ( empty( $lesson_id ) ) {
		return '';
	}

	$topics = learndash_get_topic_list( $lesson_id, $course_id );
	if ( empty( $topics[0]->ID ) ) {
		return '';
	}

	$topics_progress = learndash_get_course_progress( $user_id, $topics[0]->ID, $course_id );

	if ( ! empty( $topics_progress['posts'][0] ) ) {
		$topics = $topics_progress['posts'];
	}

	if ( $type == 'array' ) {
		return $topics;
	}

	$html = "<div id='learndash_topic_dots-".$lesson_id. "' class='learndash_topic_dots type-".$type."'>";

	if ( ! empty( $show_text) ) {
		$html .= '<strong>'.$show_text.'</strong>';
	}

	switch ( $type ) {
		case 'list':
			$html .= '<ul>';
			$sn = 0;

			foreach ( $topics as $topic ) {
				$sn++;

				if ( $topic->completed ) {
					$completed = 'topic-completed';
				} else {
					$completed = 'topic-notcompleted';
				}

				 /**
				 * Filter output of list topic dots
				 * 
				 * @since 2.1.0
				 * 
				 * @param  string
				 */
				$html .= apply_filters( 'learndash_topic_dots_item', "<li><a class='".$completed."' href='".get_permalink( $topic->ID )."'  title='".$topic->post_title."'><span>".$topic->post_title.'</span></a></li>', $topic, $completed, $type, $sn );
			}

			$html .= '</ul>';
			break;

		case 'dots':

		default:
			$sn = 0;

			foreach ( $topics as $topic ) {
				$sn++;

				if ( $topic->completed ) {
					$completed = 'topic-completed';
				}
				else {
					$completed = 'topic-notcompleted';
				}

				 /**
				 * Filter output of topic dots
				 * 
				 * @since 2.1.0
				 * 
				 * @param  string
				 */
				$html .= apply_filters( 'learndash_topic_dots_item', '<a class="'.$completed.'" href="'.get_permalink( $topic->ID ).'"><SPAN TITLE="'.$topic->post_title.'"></SPAN></a>', $topic, $completed, $type, $sn );
			}

			break;
	}

	$html .= '</div>';

	return $html;
}



/**
 * Get lesson list for course
 *
 * @since 2.1.0
 * 
 * @param  int 	 $id 	id of resource
 * @return array 		list of lessons
 */
function learndash_get_lesson_list( $id = null, $atts = array() ) {
	global $post;

	if ( empty( $id ) ) {
		if ( $post instanceof WP_Post ) {
			$id = $post->ID;
		}
	}

	$course_id = learndash_get_course_id( $id );

	if ( empty( $course_id ) ) {
		return array();
	}

	global $wpdb;

	$lessons = sfwd_lms_get_post_options( 'sfwd-lessons' );
	//$course_options = get_post_meta( $course_id, '_sfwd-courses', true );
	//$course_orderby = @$course_options['sfwd-courses_course_lesson_orderby'];
	//$course_order = @$course_options['sfwd-courses_course_lesson_order'];	
	//$orderby = ( empty( $course_orderby) ) ? $lessons['orderby'] : $course_orderby;
	//$order = ( empty( $course_order) ) ? $lessons['order'] : $course_order;

	$course_lessons_args = learndash_get_course_lessons_order( $course_id );
	$orderby = ( isset( $course_lessons_args[ 'orderby' ] ) ) ? $course_lessons_args[ 'orderby' ] : 'title';
	$order = ( isset( $course_lessons_args[ 'order' ] ) ) ? $course_lessons_args[ 'order' ] : 'ASC';

	switch ( $orderby ) {
		case 'title': $orderby = 'title'; break;
		case 'date': $orderby = 'date'; break;
	}

	$lessons_args = array(
		'array' => true,
		'course_id' => $course_id,
		'post_type' => 'sfwd-lessons',
		'meta_key' => 'course_id', 
		'meta_value' => $course_id,
		'orderby' => $orderby, 
		'order' => $order,
	);
	
	$lessons_args = array_merge( $lessons_args, $atts );
	
	if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
		$ld_course_steps_object->load_steps();
		$course_steps = $ld_course_steps_object->get_steps('t');

		if ( ( isset( $course_steps[$lessons_args['post_type']] ) ) && ( !empty( $course_steps[$lessons_args['post_type']] ) ) ) {
			$lessons_args['post__in'] = $course_steps[$lessons_args['post_type']];
			$lessons_args['orderby'] = 'post__in';

			unset($lessons_args['order']);
			unset($lessons_args['meta_key']);
			unset($lessons_args['meta_value']);
 		} else {
 			return array();
 		}
	}
	
	/**
	 * Filter for lessons list args
	 *
	 * @since 2.5.7
	 */
	$lessons_args = apply_filters( 'learndash_get_lesson_list_args', $lessons_args, $id, $course_id );
	if ( !empty( $lessons_args ) ) {
		return ld_lesson_list( $lessons_args );
	}
}



/**
 * Get topics list for a lesson
 *
 * @since 2.1.0
 *
 * @param  int   $for_lesson_id
 * @return array topics list
 */
function learndash_get_topic_list( $for_lesson_id = null, $course_id = null ) {
	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $for_lesson_id );
	}

	if ( ( ! empty( $for_lesson_id ) ) && ( ! empty( $course_id ) ) ) {
		$transient_key = 'learndash_lesson_topics_' . $course_id . '_' . $for_lesson_id;
	} elseif ( ! empty( $for_lesson_id ) ) {
		$transient_key = 'learndash_lesson_topics_' . $for_lesson_id;
	} else {
		$transient_key = 'learndash_lesson_topics_all';
	}

	$topics_array = LDLMS_Transients::get( $transient_key );

	if ( false === $topics_array ) {

		if ( ! empty( $for_lesson_id ) ) {

			$lessons_options = sfwd_lms_get_post_options( 'sfwd-lessons' );
			$orderby         = $lessons_options['orderby'];
			$order           = $lessons_options['order'];

			if ( ! empty( $course_id ) ) {
				$course_lessons_args = learndash_get_course_lessons_order( $course_id );
				$orderby             = isset( $course_lessons_args['orderby'] ) ? $course_lessons_args['orderby'] : 'title';
				$order               = isset( $course_lessons_args['order'] ) ? $course_lessons_args['order'] : 'ASC';
			}
		} else {
			$orderby = 'name';
			$order   = 'ASC';
		}

		$topics_query_args = array(
			'post_type'   => 'sfwd-topic',
			'numberposts' => -1,
			'orderby'     => $orderby,
			'order'       => $order,
		);

		if ( ! empty( $for_lesson_id ) ) {
			$topics_query_args['meta_key']     = 'lesson_id';
			$topics_query_args['meta_value']   = $for_lesson_id;
			$topics_query_args['meta_compare'] = '=';
		}

		if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
			if ( ! empty( $course_id ) ) {

				$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
				$ld_course_steps_object->load_steps();
				$steps = $ld_course_steps_object->get_steps();

				if ( ( isset( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] ) ) && ( ! empty( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] ) ) ) {
					$topic_ids = array_keys( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] );
					$topics_query_args['include'] = $topic_ids;
					$topics_query_args['orderby'] = 'post__in';

					unset( $topics_query_args['order'] );
					unset( $topics_query_args['meta_key'] );
					unset( $topics_query_args['meta_value'] );
					unset( $topics_query_args['meta_compare'] );
				} else {
					return array();
				}
			}
		}

		$topics = get_posts( $topics_query_args );

		if ( ! empty( $topics ) ) {
			if ( empty( $for_lesson_id ) ) {
				$topics_array = array();

				foreach ( $topics as $topic ) {
					if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
						$course_id = learndash_get_course_id( $topic->ID );
						$lesson_id = learndash_course_get_single_parent_step( $course_id, $topic->ID );
					} else {
						$lesson_id = learndash_get_setting( $topic, 'lesson' );
					}

					if ( ! empty( $lesson_id ) ) {
						// Need to clear out the post_content before transient storage.
						$topic->post_content = 'EMPTY';
						$topics_array[ $lesson_id ][] = $topic;
					}
				}
				LDLMS_Transients::set( $transient_key, $topics_array, MINUTE_IN_SECONDS );
				return $topics_array;
			} else {
				LDLMS_Transients::set( $transient_key, $topics, MINUTE_IN_SECONDS );
				return $topics;
			}
		}
	} else {
		return $topics_array;
	}
}

/**
 * Get quiz list for resource
 *
 * @since 2.1.0
 * 
 * @param  int $id 	id of resource (topic, lesson, etc)
 * @return array    list of quizzes
 */
function learndash_get_global_quiz_list( $id = null ){
	global $post;

	if ( empty( $id ) ) {
		if ( ! empty( $post->ID ) ) {
			$id = $post->ID;
		} else {
			return array();
		}
	}

	//COURSEIDCHANGE
	$course_id = learndash_get_course_id( $id );
	if (!empty($course_id)) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$quiz_ids = learndash_course_get_children_of_step( $course_id, $course_id, 'sfwd-quiz' );
			if ( !empty( $quiz_ids ) ) {
				return get_posts( array( 
					'post_type' => 'sfwd-quiz', 
					'posts_per_page' => -1, 
					'include' => $quiz_ids,
					'orderby' => 'post__in', 
					'order' => 'ASC'
				));
				
			}
		} else {
			$transient_key = "learndash_quiz_course_". $course_id;
			$quizzes_new = LDLMS_Transients::get( $transient_key );
			if ( $quizzes_new === false ) {

				$course_settings = learndash_get_setting( $course_id );
				$lessons_options = learndash_get_option( 'sfwd-lessons' );
				$orderby = ( empty( $course_settings['course_lesson_orderby'] ) ) ? @$lessons_options['orderby'] : $course_settings['course_lesson_orderby'];
				$order = ( empty( $course_settings['course_lesson_order'] ) ) ? @$lessons_options['order'] : $course_settings['course_lesson_order'];

				$quizzes = get_posts( array( 
					'post_type' => 'sfwd-quiz', 
					'posts_per_page' => -1, 
					'meta_key' => 'course_id', 
					'meta_value' => $course_id, 
					'meta_compare' => '=', 
					'orderby' => $orderby, 
					'order' => $order
				));

				$quizzes_new = array();

				foreach ( $quizzes as $k => $quiz ) {
					$quiz_lesson = learndash_get_setting( $quiz, 'lesson' );
					if ( empty( $quiz_lesson) ) {
						$quizzes_new[] = $quizzes[ $k ];
					}
				}
			
				LDLMS_Transients::set( $transient_key, $quizzes_new, MINUTE_IN_SECONDS );
			} 
			return $quizzes_new;
		}
	}
}

function learndash_get_global_quiz_list_OLD( $id = null ){
	global $post;

	if ( empty( $id ) ) {
		if ( ! empty( $post->ID ) ) {
			$id = $post->ID;
		} else {
			return array();
		}
	}

	//COURSEIDCHANGE
	$course_id = learndash_get_course_id( $id );
	if (!empty($course_id)) {

		$transient_key = "learndash_quiz_course_". $course_id;
		$quizzes_new = LDLMS_Transients::get( $transient_key );
		if ( $quizzes_new === false ) {

			$course_settings = learndash_get_setting( $course_id );
			$lessons_options = learndash_get_option( 'sfwd-lessons' );
			$orderby = ( empty( $course_settings['course_lesson_orderby'] ) ) ? @$lessons_options['orderby'] : $course_settings['course_lesson_orderby'];
			$order = ( empty( $course_settings['course_lesson_order'] ) ) ? @$lessons_options['order'] : $course_settings['course_lesson_order'];

			$quizzes = get_posts( array( 
				'post_type' => 'sfwd-quiz', 
				'posts_per_page' => -1, 
				'meta_key' => 'course_id', 
				'meta_value' => $course_id, 
				'meta_compare' => '=', 
				'orderby' => $orderby, 
				'order' => $order
			));

			$quizzes_new = array();

			foreach ( $quizzes as $k => $quiz ) {
				if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					$course_id = learndash_get_course_id( $quiz->ID );
					$quiz_lesson = learndash_course_get_single_parent_step( $course_id, $quiz->ID );
				} else {
					$quiz_lesson = learndash_get_setting( $quiz, 'lesson' );
				}
				
				if ( empty( $quiz_lesson) ) {
					$quizzes_new[] = $quizzes[ $k ];
				}
			}
			
			LDLMS_Transients::set( $transient_key, $quizzes_new, MINUTE_IN_SECONDS );
		} 
		return $quizzes_new;
	}
}



/**
 * Get lesson list output for course
 *
 * @since 2.1.0
 * 
 * @param  int|obj $course course id or course WP_Post
 * @return string          html output of lesson list for course
 */
/**
 * Get lesson list output for course
 *
 * @since 2.1.0
 * 
 * @param  int|obj $course course id or course WP_Post
 * @return string          html output of lesson list for course
 */
function learndash_get_course_lessons_list( $course = null, $user_id = null, $lessons_args = array() ) {
	if ( empty( $course ) ) {
		$course_id = learndash_get_course_id();
	}

	if ( is_numeric( $course ) ) {
		$course_id = $course;
		$course = get_post( $course_id );
	}

	if ( empty( $course->ID ) ) {
		return array();
	}

	$course_settings = learndash_get_setting( $course );
	$lessons_options = learndash_get_option( 'sfwd-lessons' );

	$orderby = ( empty( $course_settings['course_lesson_orderby'] ) ) ? @$lessons_options['orderby'] : $course_settings['course_lesson_orderby'];
	$order = ( empty( $course_settings['course_lesson_order'] ) ) ? @$lessons_options['order'] : $course_settings['course_lesson_order'];
	
	$lesson_query_pagination = 'true';
	if ( ( isset( $lessons_args['num'] ) ) && ( $lessons_args['num'] !== false ) ) {
		if ( intval( $lessons_args['num'] ) == 0 ) {
			$lesson_query_pagination = '';
			$posts_per_page = -1;
		} else {
			$posts_per_page = intval( $lessons_args['num'] );
		}
	} else {
		$posts_per_page = learndash_get_course_lessons_per_page( $course->ID );
		if ( empty( $posts_per_page ) ) {
			$posts_per_page = -1;
			$lesson_query_pagination = '';
		}
	}

	$lesson_paged = 1;
	if ( isset( $lessons_args['paged'] ) ) {
		$lesson_paged = intval( $lessons_args['paged'] );
	} else if ( isset( $_GET['ld-lesson-page'] ) ) {
		$lesson_paged = intval( $_GET['ld-lesson-page'] );
	}

	if ( empty( $lesson_paged ) ) {
		$lesson_paged = 1;
	}

	$opt = array(
		'post_type' => 'sfwd-lessons',
		'meta_key'	=> 'course_id',
		'meta_value' => $course->ID,
		'order' => $order,
		'orderby' => $orderby,
		'posts_per_page' => $posts_per_page,
		'paged' => $lesson_paged,
		'pagination' => $lesson_query_pagination,
		'pager_context' => 'course_lessons',
		'return' => 'array',
		'user_id' => $user_id,
		'course_id' => $course->ID,
	);
	$opt = wp_parse_args( $lessons_args, $opt );

	if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {	
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course->ID );
		
		$lesson_ids = $ld_course_steps_object->get_children_steps( $course->ID, $opt['post_type'] );
		//error_log('lesson_ids<pre>'. print_r($lesson_ids, true) .'</pre>');
		
		if ( !empty( $lesson_ids ) ) {
			$opt['include'] = implode( ",", $lesson_ids );
			$opt['orderby'] = 'post__in';
			$opt['course_id'] = $course->ID;

			unset($opt['order']);
			unset($opt['meta_key']);
			unset($opt['meta_value']);
		} else {
			return array();
		}
	}
		
	$lessons = SFWD_CPT::loop_shortcode( $opt );
	return $lessons;
}

/**
 * Get quiz list output for course
 *
 * @since 2.1.0
 * 
 * @param  int|obj $course course id or course WP_Post
 * @return string          html output of quiz list for course
 */
function learndash_get_course_quiz_list( $course = null, $user_id = null ) {
	if ( empty( $course ) ) {
		$course_id = learndash_get_course_id();
		$course = get_post( $course_id );
	}

	if ( is_numeric( $course ) ) {
		$course_id = $course;
		$course = get_post( $course_id );
	}

	if ( empty( $course->ID ) ) {
		return array();
	}

	$course_settings = learndash_get_setting( $course );
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	$orderby = ( empty( $course_settings['course_lesson_orderby'] ) ) ? @$lessons_options['orderby'] : $course_settings['course_lesson_orderby'];
	$order = ( empty( $course_settings['course_lesson_order'] ) ) ? @$lessons_options['order'] : $course_settings['course_lesson_order'];
	$opt = array(
		'post_type' => 'sfwd-quiz',
		'meta_key'	=> 'course_id',
		'meta_value' => $course->ID,
		'order' => $order,
		'orderby' => $orderby,
		//'posts_per_page' => empty( $lessons_options['posts_per_page'] ) ? -1 : $lessons_options['posts_per_page'],
		'posts_per_page' => -1,
		'user_id' => $user_id,
		'return' => 'array',
		'user_id' => $user_id
	);

	if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {	
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course->ID );
		
		$lesson_ids = $ld_course_steps_object->get_children_steps( $course->ID, $opt['post_type'] );
		//error_log('lesson_ids<pre>'. print_r($lesson_ids, true) .'</pre>');
		
		if ( !empty( $lesson_ids ) ) {
			$opt['include'] = implode( ",", $lesson_ids );
			$opt['orderby'] = 'post__in';
			$opt['course_id'] = $course->ID;

			unset($opt['order']);
			unset($opt['meta_key']);
			unset($opt['meta_value']);
		} else {
			return array();
		}
	}
	$quizzes = SFWD_CPT::loop_shortcode( $opt );
	return $quizzes;
}



/**
 * Get lesson list output for quiz
 *
 * @since 2.1.0
 * 
 * @param  int|obj $quiz quiz id or quiz WP_Post
 * @return string          html output of lesson list for quiz
 */
function learndash_get_lesson_quiz_list( $lesson, $user_id = null, $course_id = null ) {
	if ( is_numeric( $lesson ) ) {
		$lesson_id = $lesson;
		$lesson = get_post( $lesson_id );
	}

	if ( empty( $lesson->ID ) ) {
		return array();
	}

	if (empty( $course_id ))
		$course_id = learndash_get_course_id( $lesson );

	$course_settings = learndash_get_setting( $course_id );
	$lessons_options = learndash_get_option( 'sfwd-lessons' );
	$orderby = ( empty( $course_settings['course_lesson_orderby'] ) ) ? @$lessons_options['orderby'] : $course_settings['course_lesson_orderby'];
	$order = ( empty( $course_settings['course_lesson_order'] ) ) ? @$lessons_options['order'] : $course_settings['course_lesson_order'];
	$opt = array(
		'post_type' => 'sfwd-quiz',
		'meta_key'	=> 'lesson_id',
		'meta_value' => $lesson->ID,
		'order' => $order,
		'orderby' => $orderby,
		//'posts_per_page' => empty( $lessons_options['posts_per_page'] ) ? -1 : $lessons_options['posts_per_page'],
		'posts_per_page' => -1,
		'user_id' => $user_id,
		'return' => 'array',
		'user_id' => $user_id,
		'course_id' => $course_id
	);


	if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {	
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
		if ( $ld_course_steps_object ) {
			$quiz_ids = $ld_course_steps_object->get_children_steps( $lesson->ID, $opt['post_type'] );
			if ( !empty( $quiz_ids ) ) {
				$opt['include'] = implode( ",", $quiz_ids );
				$opt['orderby'] = 'post__in';
				//$opt['course_id'] = $course_id;

				unset($opt['order']);
				unset($opt['meta_key']);
				unset($opt['meta_value']);
			} else {
				return array();
			}
		}
	}

	$quizzes = SFWD_CPT::loop_shortcode( $opt );
	return $quizzes;
}