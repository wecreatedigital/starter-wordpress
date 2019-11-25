<?php
/**
 * Shortcodes for displaying Quiz and Course info
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Shortcodes
 */



/**
 * Shortcode that displays the requested quiz information
 * 
 * @since 2.1.0
 * 
 * @param  array $attr shortcode attributes
 * @return string      shortcode output
 */
function learndash_quizinfo( $attr ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	$shortcode_atts = shortcode_atts(
		array(
			'show'     => '', //[score], [count], [pass], [rank], [timestamp], [pro_quizid], [points], [total_points], [percentage], [timespent]
			'user_id'  => '',
			'quiz'     => '',
			'time'     => '',
			'field_id' => '',
			'format'   => 'F j, Y, g:i a',
		), 
		$attr 
	);

	extract( $shortcode_atts );

	$time    = ( empty( $time ) && isset( $_REQUEST['time'] ) ) ? $_REQUEST['time'] : $time;
	$show    = ( empty( $show ) && isset( $_REQUEST['show'] ) ) ? $_REQUEST['show'] : $show;	
	$quiz    = ( empty( $quiz ) && isset( $_REQUEST['quiz'] ) ) ? $_REQUEST['quiz'] : $quiz;
	$user_id = ( empty( $user_id ) && isset( $_REQUEST['user_id'] ) ) ? $_REQUEST['user_id'] : $user_id;
	$course_id = ( empty( $course_id ) && isset( $_REQUEST['course_id'] ) ) ? $_REQUEST['course_id'] : null;
	$field_id = ( empty( $field_id ) && isset( $_REQUEST['field_id'] ) ) ? $_REQUEST['field_id'] : $field_id;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
		
		/**
		 * Added logic to allow admin and group_leader to view certificate from other users. 
		 * @since 2.3
		 */
		$post_type = '';
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		}

		if ( $post_type == 'sfwd-certificates' ) {
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && (!empty( $_GET['user'] ) ) ) ) {
				$user_id = intval( $_GET['user'] );
			}
		}
	}

	if ( empty( $quiz) || empty( $user_id ) || empty( $show) ) {
		return '';
	}

	$quizinfo = get_user_meta( $user_id, '_sfwd-quizzes', true );

	$selected_quizinfo = '';
	$selected_quizinfo2 = '';
	
	foreach ( $quizinfo as $quiz_i ) {

		if ( isset( $quiz_i['time'] ) && $quiz_i['time'] == $time && $quiz_i['quiz'] == $quiz ) {
			$selected_quizinfo = $quiz_i;
			break;
		}

		if ( $quiz_i['quiz'] == $quiz ) {
			$selected_quizinfo2 = $quiz_i;
		}
	}

	$selected_quizinfo = empty( $selected_quizinfo ) ? $selected_quizinfo2 : $selected_quizinfo;

	switch ( $show ) {
		case 'timestamp':
			date_default_timezone_set( get_option( 'timezone_string' ) );
			$selected_quizinfo['timestamp'] = date_i18n( $format, $selected_quizinfo['time'] );
			break;

		case 'percentage':		
			if ( empty( $selected_quizinfo['percentage'] ) ) {
				$selected_quizinfo['percentage'] = empty( $selected_quizinfo['count'] ) ? 0 : $selected_quizinfo['score'] * 100 / $selected_quizinfo['count'];
			}

			break;

		case 'pass':
			$selected_quizinfo['pass'] = ! empty( $selected_quizinfo['pass'] ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' );
			break;

		case 'quiz_title':
			$quiz_post = get_post( $quiz );

			if ( ! empty( $quiz_post->post_title) ) {
				$selected_quizinfo['quiz_title'] = $quiz_post->post_title;
			}

			break;

		case 'course_title':
			if ( ( isset( $selected_quizinfo['course'] ) ) && ( !empty( $selected_quizinfo['course'] ) ) ) {
				$course_id = intval( $selected_quizinfo['course'] );
			} else {
				$course_id = learndash_get_setting( $quiz, 'course' );
			}
			if ( !empty( $course_id ) ) {
				$course = get_post( $course_id );
				if ( ( is_a( $course, 'WP_Post' ) ) && ( ! empty( $course->post_title) ) ) {
					$selected_quizinfo['course_title'] = $course->post_title;
				}
			}

			break;

		case 'timespent':
			$selected_quizinfo['timespent'] = isset( $selected_quizinfo['timespent'] ) ? learndash_seconds_to_time( $selected_quizinfo['timespent'] ) : '';
			break;


		case 'field':
			if ( ! empty( $field_id ) ) {
				if ( ( isset( $selected_quizinfo['pro_quizid'] ) ) && ( ! empty( $selected_quizinfo['pro_quizid'] ) ) ) {
					$formMapper = new WpProQuiz_Model_FormMapper();
					$quiz_form_elements = $formMapper->fetch( $selected_quizinfo['pro_quizid'] );
					if ( ! empty( $quiz_form_elements ) ) {
						foreach( $quiz_form_elements as $quiz_form_element ) {
							if ( absint( $field_id ) == absint( $quiz_form_element->getFormId() ) ) {
								$selected_quizinfo[ $show ] = '';

								if ( ( isset( $selected_quizinfo['statistic_ref_id'] ) ) && ( ! empty( $selected_quizinfo['statistic_ref_id'] ) ) ) {
									$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
									$statisticRefData = $statisticRefMapper->fetchAllByRef( $selected_quizinfo['statistic_ref_id'] );
									if ( ( $statisticRefData ) && ( is_a( $statisticRefData, 'WpProQuiz_Model_StatisticRefModel' ) ) ) {
										$form_data = $statisticRefData->getFormData();
										//$selected_quizinfo[ $show ] = $form_data[ $field_id ];
										if ( isset( $form_data[ $field_id ] ) ) {
											$selected_quizinfo[ $show ] = $quiz_form_element->getValue( $form_data[ $field_id ] );
										} 
									}
								}
								break;
							}
						}
					}
				}
			}
			break;
			
	}

	if ( isset( $selected_quizinfo[ $show ] ) ) {
		return apply_filters( 'learndash_quizinfo', $selected_quizinfo[ $show ], $shortcode_atts );
	} else {
		return apply_filters( 'learndash_quizinfo', '', $shortcode_atts );
	}
}

add_shortcode( 'quizinfo', 'learndash_quizinfo' );



/**
 * Shortcode that displays the requested course information
 *
 * @since 2.1.0
 *
 * @param array $attr shortcode attributes.
 *
 * @return string shortcode output
 */
function learndash_courseinfo( $attr ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts = shortcode_atts(
		array(
			'show'      => 'course_title',
			'user_id'   => '',
			'course_id' => '',
			'format'    => 'F j, Y, g:i a',
			'decimals'  => 2,
		),
		$attr
	);

	$shortcode_atts['course_id'] = ! empty( $shortcode_atts['course_id'] ) ? $shortcode_atts['course_id'] : '';
	if ( '' === $shortcode_atts['course_id'] ) {
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			$shortcode_atts['course_id'] = intval( $_GET['course_id'] );
		} else {
			$shortcode_atts['course_id'] = learndash_get_course_id();
		}
	}

	$shortcode_atts['user_id']   = ! empty( $shortcode_atts['user_id'] ) ? $shortcode_atts['user_id'] : '';
	if ( '' === $shortcode_atts['user_id'] ) {
		if ( ( isset( $_GET['user_id'] ) ) && ( ! empty( $_GET['user_id'] ) ) ) {
			$shortcode_atts['user_id'] = intval( $_GET['user_id'] );
		}
	}

	if ( empty( $shortcode_atts['user_id'] ) ) {
		$shortcode_atts['user_id'] = get_current_user_id();

		/**
		 * Added logic to allow admin and group_leader to view certificate from other users.
		 *
		 * @since 2.3
		 */
		$post_type = '';
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		}

		if ( 'sfwd-certificates' == $post_type ) {
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) {
				$shortcode_atts['user_id'] = intval( $_GET['user'] );
			}
		}
	}

	if ( empty( $shortcode_atts['course_id'] ) || empty( $shortcode_atts['user_id'] ) ) {
		return apply_filters( 'learndash_courseinfo', '', $shortcode_atts );
	}

	$shortcode_atts['show'] = strtolower( $shortcode_atts['show'] );

	switch ( $shortcode_atts['show'] ) {
		case 'course_title':
			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$course_title = $course->post_title;
			} else {
				$course_title = '';
			}
			return apply_filters( 'learndash_courseinfo', $course_title, $shortcode_atts );
			break;

		case 'cumulative_score':
		case 'cumulative_points':
		case 'cumulative_total_points':
		case 'cumulative_percentage':
		case 'cumulative_timespent':
		case 'cumulative_count':
			$field    = str_replace( 'cumulative_', '', $shortcode_atts['show'] );
			$quizdata = get_user_meta( $shortcode_atts['user_id'], '_sfwd-quizzes', true );
			$quizzes = learndash_course_get_steps_by_type( intval( $shortcode_atts['course_id'] ), 'sfwd-quiz' );
			if ( empty( $quizzes ) ) {
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			$scores = array();

			if ( ( ! empty( $quizdata ) ) && ( is_array( $quizdata ) ) ) {
				foreach ( $quizdata as $data ) {
					if ( ( in_array( $data['quiz'], $quizzes ) ) ) {
						if ( ( ! isset( $data['course'] ) ) || ( intval( $data['course'] ) == intval( $shortcode_atts['course_id'] ) ) ) {
							if ( empty( $scores[ $data['quiz'] ] ) || $scores[ $data['quiz'] ] < $data[ $field ] ) {
								$scores[ $data['quiz'] ] = $data[ $field ];
							}
						}
					}
				}
			}

			if ( empty( $scores ) || ! count( $scores ) ) {
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			$sum = 0;

			foreach ( $scores as $score ) {
				$sum += $score;
			}

			$return = number_format( $sum / count( $scores ), $shortcode_atts['decimals'] );

			if ( 'timespent' == $field ) {
				return apply_filters( 'learndash_courseinfo', learndash_seconds_to_time( $return ), $shortcode_atts );
			} else {
				return apply_filters( 'learndash_courseinfo', $return, $shortcode_atts );
			}
			break;

		case 'aggregate_percentage':
		case 'aggregate_score':
		case 'aggregate_points':
		case 'aggregate_total_points':
		case 'aggregate_timespent':
		case 'aggregate_count':
			$field    = substr_replace( $shortcode_atts['show'], '', 0, 10 );
			$quizdata = get_user_meta( $shortcode_atts['user_id'], '_sfwd-quizzes', true );
			$quizzes = learndash_course_get_steps_by_type( intval( $shortcode_atts['course_id'] ), 'sfwd-quiz' );
			if ( empty( $quizzes ) ) {
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			$scores = array();

			if ( ( ! empty( $quizdata ) ) && ( is_array( $quizdata ) ) ) {
				foreach ( $quizdata as $data ) {
					if ( in_array( $data['quiz'], $quizzes ) ) {
						if ( ( empty( $scores[ $data['quiz'] ] ) || $scores[ $data['quiz'] ] < $data[ $field ] ) ) {
							if ( ( ! isset( $data['course'] ) ) || ( intval( $data['course'] ) == intval( $shortcode_atts['course_id'] ) ) ) {
								$scores[ $data['quiz'] ] = $data[ $field ];
							}
						}
					}
				}
			}

			if ( empty( $scores ) || ! count( $scores ) ) {
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			$sum = 0;

			foreach ( $scores as $score ) {
				$sum += $score;
			}

			$return = number_format( $sum, $shortcode_atts['decimals'] );

			if ( 'timespent' == $field ) {
				return apply_filters( 'learndash_courseinfo', learndash_seconds_to_time( $return ), $shortcode_atts['show'] );
			} else {
				return apply_filters( 'learndash_courseinfo', $return, $shortcode_atts );
			}

		case 'completed_on':
			$completed_on = get_user_meta( $shortcode_atts['user_id'], 'course_completed_' . $shortcode_atts['course_id'], true );

			if ( empty( $completed_on ) ) {
				$completed_on = learndash_user_get_course_completed_date( $shortcode_atts['user_id'], $shortcode_atts['course_id'] );
				if ( empty( $completed_on ) ) {
					return apply_filters( 'learndash_courseinfo', '-', $shortcode_atts );
				}
			}

			return apply_filters( 'learndash_courseinfo', learndash_adjust_date_time_display( $completed_on, $shortcode_atts['format'] ), $shortcode_atts );
			break;

		case 'course_points':
			$course_points = learndash_get_course_points( $shortcode_atts['course_id'], $shortcode_atts['decimals'] );
			$course_points = number_format( $course_points, $shortcode_atts['decimals'] );
			return apply_filters( 'learndash_courseinfo', $course_points, $shortcode_atts );

			break;

		case 'user_course_points':
			$user_course_points = learndash_get_user_course_points( $shortcode_atts['user_id'] );
			$user_course_points = number_format( $user_course_points, $shortcode_atts['decimals'] );
			return apply_filters( 'learndash_courseinfo', $user_course_points, $shortcode_atts );

			break;

		default:
			return apply_filters( 'learndash_courseinfo', '', $shortcode_atts );
	}
}

add_shortcode( 'courseinfo', 'learndash_courseinfo' );
