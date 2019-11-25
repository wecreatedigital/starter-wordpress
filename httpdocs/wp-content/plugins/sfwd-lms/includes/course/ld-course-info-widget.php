<?php
/**
 * Course info and navigation widgets
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Widgets
 */


// This filter will parse the text of the widget for shortcodes.
add_filter( 'widget_text', 'do_shortcode' );
add_filter( 'widget_text_content', 'do_shortcode' );
add_filter( 'widget_custom_html', 'do_shortcode' );
add_filter( 'widget_custom_html_content', 'do_shortcode' );

class LearnDash_Course_Info_Widget extends WP_Widget {

	/**
	 * Setup Course Info Widget
	 */
	function __construct() {
		$widget_ops = array( 
			'classname' => 'widget_ldcourseinfo', 
			'description' => sprintf( esc_html_x( 'LearnDash - %s attempt and score information of users. Visible only to users logged in.', 'placeholders: course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) )
		);
		$control_ops = array(); //'width' => 400, 'height' => 350);
		parent::__construct( 'ldcourseinfo', sprintf( esc_html_x( '%s Information', 'Course Information', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), $widget_ops, $control_ops );
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

		 /**
		 * Filter widget title
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string
		 */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance );
		
		if ( empty( $user_id ) ) {
			$current_user = wp_get_current_user();
			if ( empty( $current_user->ID ) ) {
				return;
			}
			
			$user_id = $current_user->ID;
		}
		
		$courseinfo = learndash_course_info( $user_id, $instance );
		
		if ( empty( $courseinfo ) ) {
			return;
		}
		
		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}
		
		echo $courseinfo;
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
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		$instance['registered_show_thumbnail'] = esc_attr( $new_instance['registered_show_thumbnail'] );
		if ( $new_instance['registered_num'] != '' )
			$instance['registered_num'] = intval( $new_instance['registered_num'] );
		else 
			$instance['registered_num'] = false;
		
		$instance['registered_orderby'] = esc_attr( $new_instance['registered_orderby'] );
		$instance['registered_order'] = esc_attr( $new_instance['registered_order'] );

		if ( $new_instance['progress_num'] != '' )
			$instance['progress_num'] = intval( $new_instance['progress_num'] );
		else 
			$instance['progress_num'] = false;
		
		$instance['progress_orderby'] = esc_attr( $new_instance['progress_orderby'] );
		$instance['progress_order'] = esc_attr( $new_instance['progress_order'] );

		if ( $new_instance['quiz_num'] != '' )
			$instance['quiz_num'] = intval( $new_instance['quiz_num'] );
		else 
			$instance['quiz_num'] = false;
		
		$instance['quiz_orderby'] = esc_attr( $new_instance['quiz_orderby'] );
		$instance['quiz_order'] = esc_attr( $new_instance['quiz_order'] );
		
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
		$instance = wp_parse_args( 
			(array) $instance, 
			array( 
				'title' => '',
				'registered_show_thumbnail' => '',
				'registered_num' => false,
				'registered_orderby' => '',
				'registered_order' => '',

				'progress_num' => false,
				'progress_orderby' => '',
				'progress_order' => '',

				'quiz_num' => false,
				'quiz_orderby' => '',
				'quiz_order' => '',
			) 
		);
		
		$title = strip_tags( $instance['title'] );

		$registered_show_thumbnail = esc_attr( $instance['registered_show_thumbnail'] );

		if ( $instance['registered_num'] != '' )
			$registered_num = abs(intval( $instance['registered_num'] ));
		else
			$registered_num = '';
		
		$registered_orderby = esc_attr( $instance['registered_orderby'] );
		$registered_order = esc_attr( $instance['registered_order'] );
		
		if ( $instance['registered_num'] != '' )
			$progress_num = abs(intval( $instance['progress_num'] ));
		else
			$progress_num = '';
		
		$progress_orderby = esc_attr( $instance['progress_orderby'] );
		$progress_order = esc_attr( $instance['progress_order'] );

		if ( $instance['quiz_num'] != '' )
			$quiz_num = abs( intval( $instance['quiz_num'] ));
		else 
			$quiz_num = '';
		
		$quiz_orderby = esc_attr( $instance['quiz_orderby'] );
		$quiz_order = esc_attr( $instance['quiz_order'] );

		//$text = format_to_edit($instance['text']);
		
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'learndash' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>


			<p>
				<label for="<?php echo $this->get_field_id( 'registered_show_thumbnail' ); ?>"><?php echo esc_html__( 'Registered show thumbnail:', 'learndash' ) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'registered_show_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'registered_show_thumbnail' ); ?>">
					<option value="" <?php selected( $registered_show_thumbnail, '' ); ?>><?php echo esc_html__('Yes (default)', 'learndash') ?></option>
					<option value="false" <?php selected( $registered_show_thumbnail, 'false' ); ?>><?php echo esc_html__('No', 'learndash') ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'registered_num' ); ?>"><?php echo esc_html__( 'Registered per page:', 'learndash' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'registered_num' ); ?>" name="<?php echo $this->get_field_name( 'registered_num' ); ?>" type="number" min="0" value="<?php echo $registered_num; ?>" />
				<span class="description"><?php printf( esc_html_x( 'Default is %d. Set to zero for no pagination.', 'placeholders: default per page', 'learndash' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ) ) ?></span>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'registered_orderby' ); ?>"><?php echo esc_html__( 'Registered order by:', 'learndash' ); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'registered_orderby' ); ?>" name="<?php echo $this->get_field_name( 'registered_orderby' ); ?>">
					<option value="" <?php selected( $registered_orderby, '' ); ?>><?php echo esc_html__('Title (default) - Order by post title', 'learndash') ?></option>
					<option value="id" <?php selected( $registered_orderby, 'id' ); ?>><?php echo esc_html__('ID - Order by post id', 'learndash') ?></option>
					<option value="date" <?php selected( $registered_orderby, 'date' ); ?>><?php echo esc_html__('Date - Order by post date', 'learndash') ?></option>
					<option value="menu_order" <?php selected( $registered_orderby, 'menuorder' ); ?>><?php echo esc_html__('Menu - Order by Page Order Value', 'learndash') ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'registered_order' ); ?>"><?php echo esc_html__( 'Registered order:', 'learndash' ); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'registered_order' ); ?>" name="<?php echo $this->get_field_name( 'registered_order' ); ?>">
					<option value="" <?php selected( $registered_order, '' ); ?>><?php echo esc_html__('ASC (default) - lowest to highest values', 'learndash') ?></option>
					<option value="DESC" <?php selected( $registered_order, 'DESC' ); ?>><?php echo esc_html__('DESC - highest to lowest values', 'learndash') ?></option>
				</select>
			</p>
			

			<p>
				<label for="<?php echo $this->get_field_id( 'progress_num' ); ?>"><?php echo sprintf( esc_html_x( '%s progress per page:', 'placeholder: course', 'learndash' ) , LearnDash_Custom_Label::get_label( 'course' ) ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'progress_num' ); ?>" name="<?php echo $this->get_field_name( 'progress_num' ); ?>" type="number"  min="0" value="<?php echo $progress_num; ?>" />
				<span class="description"><?php printf( esc_html_x( 'Default is %d. Set to zero for no pagination.', 'placeholders: default per page', 'learndash' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'progress_num' ) ) ?></span>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'progress_orderby' ); ?>"><?php echo esc_html__( 'Progress order by:', 'learndash' ); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'progress_orderby' ); ?>" name="<?php echo $this->get_field_name( 'progress_orderby' ); ?>">
					<option value="" <?php selected( $progress_orderby, '' ); ?>><?php echo esc_html__('Title (default) - Order by post title', 'learndash') ?></option>
					<option value="id" <?php selected( $progress_orderby, 'id' ); ?>><?php echo esc_html__('ID - Order by post id', 'learndash') ?></option>
					<option value="date" <?php selected( $progress_orderby, 'date' ); ?>><?php echo esc_html__('Date - Order by post date', 'learndash') ?></option>
					<option value="menu_order" <?php selected( $progress_orderby, 'menu_order' ); ?>><?php echo esc_html__('Menu - Order by Page Order Value', 'learndash') ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'progress_order' ); ?>"><?php echo esc_html__( 'Progress order:', 'learndash' ); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'progress_order' ); ?>" name="<?php echo $this->get_field_name( 'progress_order' ); ?>">
					<option value="" <?php selected( $progress_order, '' ); ?>><?php echo esc_html__('ASC (default) - lowest to highest values', 'learndash') ?></option>
					<option value="DESC" <?php selected( $progress_order, 'DESC' ); ?>><?php echo esc_html__('DESC - highest to lowest values', 'learndash') ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'quiz_num' ); ?>"><?php echo sprintf( esc_html_x( '%s per page:', 'placeholder: quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quizzes' ) ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'quiz_num' ); ?>" name="<?php echo $this->get_field_name( 'quiz_num' ); ?>" type="number"  min="0" value="<?php echo $quiz_num; ?>" />
				<span class="description"><?php printf( esc_html_x( 'Default is %d. Set to zero for no pagination.', 'placeholders: default per page', 'learndash' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'quiz_num' ) ) ?></span>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'quiz_orderby' ); ?>"><?php echo sprintf( esc_html_x( '%s order by:', 'placeholder: quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quizzes' ) ); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'quiz_orderby' ); ?>" name="<?php echo $this->get_field_name( 'quiz_orderby' ); ?>">
					<option value="" <?php selected( $quiz_orderby, '' ); ?>><?php echo esc_html__( 'Date Taken (default) - Order by date taken', 'learndash' ); ?></option>
					<option value="title" <?php selected( $quiz_orderby, 'title' ); ?>><?php echo esc_html__('Title - Order by post title', 'learndash') ?></option>
					<option value="id" <?php selected( $quiz_orderby, 'id' ); ?>><?php echo esc_html__('ID - Order by post id', 'learndash') ?></option>
					<option value="date" <?php selected( $quiz_orderby, 'date' ); ?>><?php echo esc_html__('Date - Order by post date', 'learndash') ?></option>
					<option value="menu_order" <?php selected( $quiz_orderby, 'menu_order' ); ?>><?php echo esc_html__('Menu - Order by Page Order Value', 'learndash') ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'quiz_order' ); ?>"><?php echo sprintf( esc_html_x( '%s order:', 'placeholder: quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quizzes' ) ); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'quiz_order' ); ?>" name="<?php echo $this->get_field_name( 'quiz_order' ); ?>">
					<option value="" <?php selected( $quiz_order, '' ); ?>><?php echo esc_html__('DESC (default) - highest to lowest values', 'learndash') ?></option>
					<option value="ASC" <?php selected( $quiz_order, 'ASC' ); ?>><?php echo esc_html__('ASC - lowest to highest values', 'learndash') ?></option>
				</select>
			</p>
		<?php
	}
}

add_action( 'widgets_init', function() {
	return register_widget("LearnDash_Course_Info_Widget");
});



class LearnDash_Course_Navigation_Widget extends WP_Widget {
	
	/**
	 * Setup Course Navigation Widget
	 */
	function __construct() {
		$widget_ops = array(
			'classname' => 'widget_ldcoursenavigation', 
			'description' => sprintf( esc_html_x( 'LearnDash - %s Navigation. Shows lessons and topics on the current course.', 'LearnDash - Course Navigation. Shows lessons and topics on the current course.', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) )
		);
		$control_ops = array(); //'width' => 400, 'height' => 350);
		parent::__construct( 'widget_ldcoursenavigation', sprintf( esc_html_x( '%s Navigation', 'Course Navigation Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), $widget_ops, $control_ops );
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
	public function widget( $args, $instance ) {
		global $learndash_shortcode_used;

		//global $post;
		$post = get_post( get_the_id() );

		if ( ( ! is_a( $post, 'WP_Post' ) ) || ( empty( $post->ID ) ) || ( ! is_single() ) ) {
			return;
		}

		$course_id = learndash_get_course_id( $post->ID );
		if ( empty( $course_id ) ) {
			return;
		}

		//$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
		// If the course price type is not 'open' and the user is not logged in then abort.
		//if ( ( 'open' !== $course_price_type ) && ( ! is_user_logged_in() ) ) {
		//	return;
		//}

		$instance['show_widget_wrapper'] = true;
		$instance['current_lesson_id'] = 0;
		$instance['current_step_id'] = 0;

		$lesson_query_args = array();
		$course_lessons_per_page = learndash_get_course_lessons_per_page( $course_id );
		if ( $course_lessons_per_page > 0 ) {
			if ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) {

				$instance['current_step_id'] = $post->ID;
				if ( 'sfwd-lessons' === $post->post_type ) {
					$instance['current_lesson_id'] = $post->ID;
				} else if ( in_array( $post->post_type, array( 'sfwd-topic', 'sfwd-quiz') ) ) {
					$instance['current_lesson_id'] = learndash_course_get_single_parent_step( $course_id, $post->ID, 'sfwd-lessons' );
				}

				if ( ! empty( $instance['current_lesson_id'] ) ) {
					$course_lesson_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
					if ( ! empty( $course_lesson_ids ) ) {
						$course_lessons_paged = array_chunk( $course_lesson_ids, $course_lessons_per_page, true );
						$lessons_paged = 0;
						foreach ( $course_lessons_paged as $paged => $paged_set ) {
							if ( in_array( $instance['current_lesson_id'], $paged_set ) ) {
								$lessons_paged = $paged + 1;
								break;
							}
						}

						if ( ! empty( $lessons_paged ) ) {
							$lesson_query_args['pagination'] = 'true';
							$lesson_query_args['paged'] = $lessons_paged;
						}
					}
				} else if ( in_array( $post->post_type, array( 'sfwd-quiz' ) ) ) {
					// If here we have a global Quiz. So we set the pager to the max number
					$course_lesson_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
					if ( ! empty( $course_lesson_ids ) ) {
						$course_lessons_paged = array_chunk( $course_lesson_ids, $course_lessons_per_page, true );
						$lesson_query_args['paged'] = count( $course_lessons_paged );
					}
				}
			}
		} else {
			if ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) {

				$instance['current_step_id'] = $post->ID;
				if ( 'sfwd-lessons' === $post->post_type ) {
					$instance['current_lesson_id'] = $post->ID;
				} else if ( in_array( $post->post_type, array( 'sfwd-topic', 'sfwd-quiz') ) ) {
					$instance['current_lesson_id'] = learndash_course_get_single_parent_step( $course_id, $post->ID, 'sfwd-lessons' );
				}
			}
		}

		extract( $args );

		/**
		 * Filter widget title
		 *
		 * @since 2.1.0
		 *
		 * @param  string
		 */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance );

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		learndash_course_navigation( $course_id, $instance, $lesson_query_args );

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
		$instance = $old_instance;
		
		$instance['title'] 					= 	strip_tags( $new_instance['title'] );		
		
		$instance['show_lesson_quizzes']	= 	isset( $new_instance['show_lesson_quizzes'] ) ? (bool) $new_instance['show_lesson_quizzes'] : false;
		$instance['show_topic_quizzes'] 	= 	isset( $new_instance['show_topic_quizzes'] ) ? (bool) $new_instance['show_topic_quizzes'] : false;
		$instance['show_course_quizzes'] 	= 	isset( $new_instance['show_course_quizzes'] ) ? (bool) $new_instance['show_course_quizzes'] : false;

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
		$title = strip_tags( $instance['title'] );
		$show_lesson_quizzes 	= isset( $instance['show_lesson_quizzes'] ) ? (bool) $instance['show_lesson_quizzes'] : false;
		$show_topic_quizzes 	= isset( $instance['show_topic_quizzes'] ) ? (bool) $instance['show_topic_quizzes'] : false;
		$show_course_quizzes 	= isset( $instance['show_course_quizzes'] ) ? (bool) $instance['show_course_quizzes'] : false;

		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'learndash' ); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<p>
				<input class="checkbox" type="checkbox" <?php checked( $show_course_quizzes ); ?> id="<?php echo $this->get_field_id( 'show_course_quizzes' ); ?>" name="<?php echo $this->get_field_name( 'show_course_quizzes' ); ?>" />
				<label for="<?php echo $this->get_field_id( 'show_course_quizzes' ); ?>"><?php echo sprintf( esc_html_x( 'Show %1$s %2$s?', 'Show Course Quizzes?', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ); ?></label>
			</p>
			<p>
				<input class="checkbox" type="checkbox" <?php checked( $show_lesson_quizzes ); ?> id="<?php echo $this->get_field_id( 'show_lesson_quizzes' ); ?>" name="<?php echo $this->get_field_name( 'show_lesson_quizzes' ); ?>" />
				<label for="<?php echo $this->get_field_id( 'show_lesson_quizzes' ); ?>"><?php echo sprintf( esc_html_x( 'Show %1$s %2$s?', 'Show Lesson Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ); ?></label>
			</p>
			<p>
				<input class="checkbox" type="checkbox" <?php checked( $show_topic_quizzes ); ?> id="<?php echo $this->get_field_id( 'show_topic_quizzes' ); ?>" name="<?php echo $this->get_field_name( 'show_topic_quizzes' ); ?>" />
				<label for="<?php echo $this->get_field_id( 'show_topic_quizzes' ); ?>"><?php echo sprintf( esc_html_x( 'Show %1$s %2$s?', 'Show Topic Quizzes?', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ); ?></label>
			</p>
<?php /* ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'lessons_per_page' ); ?>"><?php echo sprintf( esc_html_x( '%s per page', 'placeholder: Lessons', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ) ); ?></label>
				<input type="text" id="<?php echo $this->get_field_id( 'lessons_per_page' ); ?>" name="<?php echo $this->get_field_name( 'lessons_per_page' ); ?>" value="<?php echo  $lessons_per_page; ?>" />
			</p>
<?php */ ?>
		<?php
	}

}

add_action( 'widgets_init', function( ) {
	return register_widget("LearnDash_Course_Navigation_Widget");
} );



/**
 * Outputs course navigation template for widget
 * 
 * @since 2.1.0
 * 
 * @param int 		$course_id  course id
 * @param widget_instance array of widgert settings
 * @param lesson_query_args array of query options for pagnination etc.
 * 
 * @return string 			 	course navigation output
 */
function learndash_course_navigation( $course_id, $widget_instance = array(), $lesson_query_args = array() ) {
	$course = get_post( $course_id );
	
	if ( empty( $course->ID ) || $course_id != $course->ID ) {
		return;
	}
	
	if ( empty( $course->ID ) || $course->post_type != 'sfwd-courses' ) {
		return;
	}
	
	if ( is_user_logged_in() )
		$user_id = get_current_user_id();
	else
		$user_id = 0;
		
	$course_navigation_widget_pager = array();
	global $course_navigation_widget_pager;
	
	add_action( 'learndash_course_lessons_list_pager', function( $query_result = null ) {
		global $course_navigation_widget_pager;

		$course_navigation_widget_pager['paged'] = 1;

		if ( ( isset( $query_result->query_vars['paged'] ) ) && ( $query_result->query_vars['paged'] > 1 ) )
			$course_navigation_widget_pager['paged'] = $query_result->query_vars['paged'];
		
		$course_navigation_widget_pager['total_items'] = $query_result->found_posts;
		$course_navigation_widget_pager['total_pages'] = $query_result->max_num_pages;
	} );
	
	$lessons = learndash_get_course_lessons_list( $course, $user_id, $lesson_query_args );
	
	$template_file = SFWD_LMS::get_template( 
		'course_navigation_widget', 
		array(
			'course_id' => $course_id, 
			'course' => $course, 
			'lessons' => $lessons,
			'widget' => $widget_instance
		), 
		null, 
		true 
	);

	if ( ! empty( $template_file ) ) {
		include( $template_file );
	}
}



/**
 * Outputs course navigation admin template for widget
 * 
 * @since 2.1.0
 * 
 * @param  int 		$course_id  course id
 * @param widget_instance array of widgert settings
 * @param lesson_query_args array of query options for pagnination etc.
 *
 * @return string 			 	course navigation output
 */
function learndash_course_navigation_admin( $course_id, $instance = array(), $lesson_query_args = array() ) {
	$course = get_post( $course_id );
	
	if ( empty( $course->ID ) || $course_id != $course->ID ) {
		return;
	}
	
	$course = get_post( $course_id );

	if ( empty( $course->ID ) || $course->post_type != 'sfwd-courses' ) {
		return;
	}
	
	if ( is_user_logged_in() )
		$user_id = get_current_user_id();
	else
		$user_id = 0;
			
	$course_navigation_admin_pager = array();
	global $course_navigation_admin_pager;
	
	add_action( 'learndash_course_lessons_list_pager', function( $query_result = null ) {
		global $course_navigation_admin_pager;

		$course_navigation_admin_pager['paged'] = 1;

		if ( ( isset( $query_result->query_vars['paged'] ) ) && ( $query_result->query_vars['paged'] > 1 ) )
			$course_navigation_admin_pager['paged'] = $query_result->query_vars['paged'];
		
		$course_navigation_admin_pager['total_items'] = $query_result->found_posts;
		$course_navigation_admin_pager['total_pages'] = $query_result->max_num_pages;
	} );
	
	$lessons = learndash_get_course_lessons_list( $course, $user_id, $lesson_query_args );
	$quizzes = learndash_get_course_quiz_list( $course_id, $user_id ); 
	
	 SFWD_LMS::get_template( 
		'course_navigation_admin', 
		array( 
			'user_id' => $user_id,
			'course_id' => $course_id, 
			'course' => $course, 
			'lessons' => $lessons, 
			'course_quiz_list' => $quizzes,
			'widget' => $instance
		),
		true 
	);
}

function learndash_course_switcher_admin( $course_id ) {
	$template_file = SFWD_LMS::get_template( 
		'course_navigation_switcher_admin', 
		array(), 
		null, 
		true 
	);

	if ( ! empty( $template_file ) ) {
		include( $template_file );
	}
}


/**
 * Register course navigation meta box for admin
 * 
 * @since 2.1.0
 */
/*
function learndash_course_navigation_admin_box() {
	$post_types = array('sfwd-courses', 'sfwd-lessons', 'sfwd-quiz', 'sfwd-topic');

	foreach( $post_types as $post_type ) {
		add_meta_box( 'learndash_course_navigation_admin_meta', esc_html__( 'Associated Content', 'learndash' ), 'learndash_course_navigation_admin_box_content', $post_type, 'side', 'high' );
	}
}

add_action( 'add_meta_boxes', 'learndash_course_navigation_admin_box' );
*/

/**
 * Hook to add the needed style and script files needed to handle pager
 *
 * @since 2.5.4
 */
function learndash_course_step_edit_init() {
	global $learndash_assets_loaded;
	
	$screen = get_current_screen();
	if ( ( $screen->base == 'post') && ( in_array( $screen->post_type, array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
		
		$filepath = SFWD_LMS::get_template( 'learndash_pager.css', null, null, true );
		if ( !empty( $filepath ) ) {
			wp_enqueue_style( 'learndash_pager_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
			wp_style_add_data( 'learndash_pager_css', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_pager_css'] = __FUNCTION__;
		} 

		$filepath = SFWD_LMS::get_template( 'learndash_pager.js', null, null, true );
		if ( !empty( $filepath ) ) {
			wp_enqueue_script( 'learndash_pager_js', learndash_template_url_from_path( $filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
			$learndash_assets_loaded['scripts']['learndash_pager_js'] = __FUNCTION__;
		}
	}	
}
add_action( 'load-post.php', 'learndash_course_step_edit_init' );
add_action( 'load-post-new.php', 'learndash_course_step_edit_init' );


/**
 * Add content to course navigation meta box for admi
 * 
 * @since 2.1.0
 */
function learndash_course_navigation_admin_box_content() {
	if ( ( isset($_GET['post'] ) ) && ( !empty( $_GET['post'] ) ) ) {
		$course_id = learndash_get_course_id( intval( $_GET['post'] ) );
			
		if ( !empty( $course_id ) ) {
			
			$instance = array();
			$instance['show_widget_wrapper'] = true;
			$instance['course_id'] = $course_id;
			$instance['current_lesson_id'] = 0;
			$instance['current_step_id'] = 0;
			
			$lesson_query_args = array();
			$lesson_query_args['pagination'] = 'true';
			$lesson_query_args['paged'] = 1;

			//if ( $course_id != intval( $_GET['post'] ) )
			//	$widget_instance['current_step_id'] = intval( $_GET['post'] );
			//else 
			//	$widget_instance['current_step_id'] = 0;
			
			//$current_post_type = get_post_type( $_GET['post'] );
			$current_post = get_post( intval( $_GET['post'] ) );
			if ( ( is_a( $current_post, 'WP_Post' ) ) && ( is_user_logged_in() ) && ( in_array( $current_post->post_type, array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
			
				$course_lessons_per_page = learndash_get_course_lessons_per_page( $course_id );
				if ( $course_lessons_per_page > 0 ) {
					if (  in_array( $current_post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) {

						$instance['current_step_id'] = $current_post->ID;
						if ( $current_post->post_type == 'sfwd-lessons' ) {
							$instance['current_lesson_id'] = $instance['current_step_id'];
						} else if ( in_array( $current_post->post_type, array('sfwd-topic', 'sfwd-quiz') ) ) {
							$instance['current_lesson_id'] = learndash_course_get_single_parent_step( $course_id, $instance['current_step_id'], 'sfwd-lessons' );
						}

						if ( !empty( $instance['current_lesson_id'] ) ) {
							$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
							$course_lesson_ids = $ld_course_steps_object->get_children_steps( $course_id, 'sfwd-lessons' );
	
							if ( !empty( $course_lesson_ids ) ) {
								$course_lessons_paged = array_chunk( $course_lesson_ids, $course_lessons_per_page, true );
								$lessons_paged = 0;
								foreach( $course_lessons_paged as $paged => $paged_set ) {
									if ( in_array( $instance['current_lesson_id'], $paged_set ) ) {
										$lessons_paged = $paged + 1;
										break;
									}
								}
	
								if ( !empty( $lessons_paged ) ) {
									$lesson_query_args['pagination'] = 'true';
									$lesson_query_args['paged'] = $lessons_paged;
								}
							}
						} else if ( in_array( $current_post->post_type, array( 'sfwd-quiz') ) ) {
							// If here we have a global Quiz. So we set the pager to the max number
							$course_lesson_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
							if ( !empty( $course_lesson_ids ) ) {
								$course_lessons_paged = array_chunk( $course_lesson_ids, $course_lessons_per_page, true );
								$lesson_query_args['paged'] = count( $course_lessons_paged );
							}
						}
							
					}
				} else {
					$lesson_query_args['pagination'] = 'false';
					
					if ( in_array( $current_post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) {
						$instance['current_step_id'] = $current_post->ID;
						if ( $current_post->post_type == 'sfwd-lessons' ) {
							$instance['current_lesson_id'] = $current_post->ID;
						} else if ( in_array( $current_post->post_type, array('sfwd-topic', 'sfwd-quiz') ) ) {
							$instance['current_lesson_id'] = learndash_course_get_single_parent_step( $course_id, $current_post->ID, 'sfwd-lessons' );
						}
					}
				}
			}
			
			learndash_course_navigation_admin( $course_id, $instance, $lesson_query_args );
		} else {
			echo sprintf(
				// translators: placeholders: Course.
				esc_html_x( 'No associated %s', 'placeholder: Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);
		}

		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			learndash_course_switcher_admin( $course_id );
		}
	}
}


/**
 * Get course info html output for user (helper function)
 * 
 * @since 2.1.0
 * 
 * @param  int 		$user_id 
 * @return string 	course info output
 */
function learndash_course_info( $user_id, $atts = array() ) {
	return SFWD_LMS::get_course_info( $user_id, $atts );
}



/**
 * Shortcode get course info html output for user (helper function)
 * 
 * @since 2.1.0
 * 
 * @param  array 	$atts 	shortcode attributes
 * @return string 	course info output
 */
function learndash_course_info_shortcode( $atts = array() ) {
	
	global $learndash_shortcode_used;
	
	if ( ( isset( $atts['user_id'] ) ) && ( !empty( $atts['user_id'] ) ) ) {
		$user_id = intval( $atts['user_id'] );
		unset( $atts['user_id'] );
	} else {
		$current_user = wp_get_current_user();
		
		if ( empty( $current_user->ID ) ) {
			return;
		}
		
		$user_id = $current_user->ID;
	}

	$learndash_shortcode_used = true;
	
	return SFWD_LMS::get_course_info( $user_id, $atts );
}

add_shortcode( 'ld_course_info', 'learndash_course_info_shortcode' );



function learndash_user_course_points_shortcode( $atts, $content = '' ) {
	global $learndash_shortcode_used;
	
	$defaults = array(
		'user_id'	=>	get_current_user_id(),
		'context'	=>	'ld_user_course_points'
	);
	$atts = wp_parse_args( $atts, $defaults );

	if ( !isset( $atts['user_id'] ) )
		return;

	$learndash_shortcode_used = true;

	$user_couse_points = learndash_get_user_course_points( $atts['user_id'] );

	$content = SFWD_LMS::get_template( 
		'learndash_course_points_user_message', 
		array(
			'user_course_points'	=>	$user_couse_points,
			'user_id'				=>	$atts['user_id'],
			'shortcode_atts'		=>	$atts,
		), false
	);
	return $content;
}
add_shortcode( 'ld_user_course_points', 'learndash_user_course_points_shortcode' );


/**
 * Shortcoude output profile for user
 * 
 * @since 2.1.0
 * 
 * @param  array 	$atts 	shortcode attributes
 * @return string 	output profile for user
 */
function learndash_profile( $atts ) {
	global $learndash_shortcode_used;
	
	// Add check to ensure user it logged in
	if ( !is_user_logged_in() ) return '';
	
	$defaults = array(
		'user_id'				=>	get_current_user_id(),
		'per_page'				=>	false,
		'order' 				=> 'DESC', 
		'orderby' 				=> 'ID', 
		'course_points_user' 	=> 'yes',
		'expand_all'			=> false,
		'profile_link'			=> 'yes',
		'show_header'           => 'yes',
		'show_quizzes'			=> 'yes',
		'show_search'           => 'yes',
		'search'                => '',
	);
	$atts = wp_parse_args( $atts, $defaults );

	if ( ( strtolower($atts['expand_all'] ) == 'yes' ) || ( $atts['expand_all'] == 'true' ) || ( $atts['expand_all'] == '1' ))
		$atts['expand_all'] = true;
	else
		$atts['expand_all'] = false;

	
	if ( ( strtolower($atts['show_header'] ) == 'yes' ) || ( $atts['show_header'] == 'true' ) || ( $atts['show_header'] == '1' ))
		$atts['show_header'] = 'yes';
	else
		$atts['show_header'] = false;

	if ( ( strtolower($atts['show_search'] ) == 'yes' ) || ( $atts['show_search'] == 'true' ) || ( $atts['show_search'] == '1' ))
		$atts['show_search'] = 'yes';
	else
		$atts['show_search'] = false;

	if ( ( strtolower($atts['course_points_user'] ) == 'yes' ) || ( $atts['course_points_user'] == 'true' ) || ( $atts['course_points_user'] == '1' ))
		$atts['course_points_user'] = 'yes';
	else
		$atts['course_points_user'] = false;

		if ( $atts['per_page'] === false ) {
		$atts['per_page'] = $atts['quiz_num'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
	} else {
		$atts['per_page'] = intval( $atts['per_page'] );
	}

	if ( $atts['per_page'] > 0 ) {
		$atts['paged'] = 1;
	} else {
		unset( $atts['paged'] );
		$atts['nopaging'] = true;
	}

	if ( ( strtolower( $atts['profile_link'] ) == 'yes' ) || ( $atts['profile_link'] == 'true' ) || ( $atts['profile_link'] == '1' ) )
		$atts['profile_link'] = true;
	else
		$atts['profile_link'] = false;


	if ( ( strtolower( $atts['show_quizzes'] ) == 'yes' ) || ( $atts['show_quizzes'] == 'true' ) || ( $atts['show_quizzes'] == '1' ) )
		$atts['show_quizzes'] = true;
	else
		$atts['show_quizzes'] = false;
	

	if ( ( isset( $_GET['ld-profile-search'] ) ) && ( ! empty( $_GET['ld-profile-search'] ) ) ) {
		$atts['search'] = esc_attr( $_GET['ld-profile-search'] );
	}
	
	$atts = apply_filters('learndash_profile_shortcode_atts', $atts );

	if ( isset( $atts['search'] ) ) {
		$atts['s'] = $atts['search'];
		unset( $atts['search'] );
	}

	if ( empty( $atts['user_id'] ) ) return;

	$current_user = get_user_by( 'id', $atts['user_id'] );
	$user_courses = ld_get_mycourses( $atts['user_id'], $atts );

	$usermeta = get_user_meta( $atts['user_id'], '_sfwd-quizzes', true );
	$quiz_attempts_meta = empty( $usermeta ) ? false : $usermeta;
	$quiz_attempts = array();

	if ( ! empty( $quiz_attempts_meta ) ) {

		foreach ( $quiz_attempts_meta as $quiz_attempt ) {
			$c = learndash_certificate_details( $quiz_attempt['quiz'], $atts['user_id'] );
			$quiz_attempt['post'] = get_post( $quiz_attempt['quiz'] );
			$quiz_attempt['percentage'] = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );
			
			if ( $atts['user_id'] == get_current_user_id() && ! empty( $c['certificateLink'] ) && ( ( isset( $quiz_attempt['percentage'] ) && $quiz_attempt['percentage'] >= $c['certificate_threshold'] * 100 ) ) ) {
				$quiz_attempt['certificate'] = $c;
			}

			if ( !isset( $quiz_attempt['course'] ) )
				$quiz_attempt['course'] = learndash_get_course_id( $quiz_attempt['quiz'] );
			$course_id = intval( $quiz_attempt['course'] );

			$quiz_attempts[$course_id][] = $quiz_attempt;
		}
	}
	
	$profile_pager = array();
	
	if ( ( isset( $atts['per_page'] ) ) && ( intval( $atts['per_page'] ) > 0 ) ) {
		$atts['per_page'] = intval( $atts['per_page'] );
			
		if ( ( isset( $_GET['ld-profile-page'] ) ) && ( !empty( $_GET['ld-profile-page'] ) ) ) {
			$profile_pager['paged'] = intval( $_GET['ld-profile-page'] );
		} else {
			$profile_pager['paged'] = 1;
		}
		
		$profile_pager['total_items'] = count( $user_courses );
		$profile_pager['total_pages'] = ceil( count( $user_courses ) / $atts['per_page'] );
		
		$user_courses = array_slice ( $user_courses, ( $profile_pager['paged'] * $atts['per_page'] ) - $atts['per_page'], $atts['per_page'], false );
	}
	
	$learndash_shortcode_used = true;
	
	return SFWD_LMS::get_template( 
		'profile', 
		array(
			'user_id' 			=> 	$atts['user_id'], 
			'quiz_attempts' 	=> 	$quiz_attempts, 
			'current_user' 		=> 	$current_user, 
			'user_courses' 		=> 	$user_courses,
			'shortcode_atts'	=>	$atts,
			'profile_pager'		=>	$profile_pager
		) 
	);
}

add_shortcode( 'ld_profile', 'learndash_profile' );

function wp_ajax_ld_course_registered_pager() {
	if ( !is_user_logged_in() ) return '';
	if ( ! current_user_can( 'read' ) ) return '';
	
	add_filter('learndash_course_info_paged', function( $paged = 1, $context = '' ) {
		if ( ( $context == 'registered' ) && ( isset( $_POST['paged'] ) ) && ( !empty( $_POST['paged'] ) ) ) {
			$paged = intval( $_POST['paged'] );
		}
		
		// Always return $paged
		return $paged;
	}, 10, 2 );

	$reply_data = array();

	if ( isset( $_POST['shortcode_atts'] ) )
		$shortcode_atts = $_POST['shortcode_atts'];
	else
		$shortcode_atts = array();
	
	$user_id = get_current_user_id();
	if ( learndash_is_group_leader_user() ) {
		if ( ( isset( $shortcode_atts['user_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
			if ( learndash_is_group_leader_of_user( $user_id, $shortcode_atts['user_id'] ) ) {
				$user_id = intval( $shortcode_atts['user_id'] );
			}
		}
	} else if ( learndash_is_admin_user() ) {	
		if ( ( isset( $shortcode_atts['user_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
			$user_id = intval( $shortcode_atts['user_id'] );
		} 
	}
	
	$shortcode_atts['return'] = true;
	$shortcode_atts['type'] = 'registered';
	
	// Setup the pager filter. 
	if ( !learndash_ajax_pager_verify_atts( $user_id, $shortcode_atts ) ) {
		return '';
	}

	$user_progress = SFWD_LMS::get_course_info( $user_id, $shortcode_atts );

	if ( ( isset( $user_progress['courses_registered'] ) ) && ( !empty( $user_progress['courses_registered'] ) ) ) {
		$courses_registered = $user_progress['courses_registered'];
		
		$level = ob_get_level();
		ob_start();
		
		$template_file = SFWD_LMS::get_template(
			'course_registered_rows',
			null,
			null, 
			true 
		); 
		if ( ! empty( $template_file ) ) {
			include $template_file;
		}
		$reply_data['content'] = learndash_ob_get_clean( $level );
	}

	if ( isset( $user_progress['courses_registered_pager'] ) ) {
		$reply_data['pager'] = SFWD_LMS::get_template( 
			'learndash_pager.php', 
			array(
				'pager_results' => $user_progress['courses_registered_pager'], 
				'pager_context' => 'course_info_registered'
			) 
		);
	}
	
	echo json_encode( $reply_data );
	die();
}
add_action( 'wp_ajax_ld_course_registered_pager', 'wp_ajax_ld_course_registered_pager' );

function wp_ajax_ld_course_progress_pager() {
	if ( !is_user_logged_in() ) return '';

	add_filter('learndash_course_info_paged', function( $paged = 1, $context = '' ) {
		if ( ( $context == 'courses' ) && ( isset( $_POST['paged'] ) ) && ( !empty( $_POST['paged'] ) ) ) {
			$paged = intval( $_POST['paged'] );
		}
		
		// Always return $paged
		return $paged;
	}, 10, 2 );

	$reply_data = array();

	if ( isset( $_POST['shortcode_atts'] ) )
		$shortcode_atts = $_POST['shortcode_atts'];
	else
		$shortcode_atts = array();
	
	$user_id = get_current_user_id();
	if ( ( isset( $shortcode_atts['user_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
		$shortcode_atts['user_id'] = absint( $shortcode_atts['user_id'] );
		if ( $user_id !== $shortcode_atts['user_id'] ) {
			if ( ( learndash_is_group_leader_user() ) && ( learndash_is_group_leader_of_user( $user_id, $shortcode_atts['user_id'] ) ) ) {
				$user_id = intval( $shortcode_atts['user_id'] );
			} else if ( learndash_is_admin_user() ) {	
				$user_id = intval( $shortcode_atts['user_id'] );
			} 
		}
	}

	$shortcode_atts['return'] = true;
	$shortcode_atts['type'] = 'course';
	
	// Setup the pager filter. 
	if ( !learndash_ajax_pager_verify_atts( $user_id, $shortcode_atts) ) {
		return '';
	}
	
	$user_progress = SFWD_LMS::get_course_info( $user_id, $shortcode_atts );

	if ( ( isset( $user_progress['course_progress'] ) ) && ( !empty( $user_progress['course_progress'] ) ) ) {
		$courses_registered = $user_progress['courses_registered'];
		$course_progress = $user_progress['course_progress'];
		
		$level = ob_get_level();
		ob_start();
		
		$template_file = SFWD_LMS::get_template(
			'course_progress_rows',
			null,
			null, 
			true 
		);

		if ( ! empty( $template_file ) ) {
			include $template_file;
		}
		$reply_data['content'] = learndash_ob_get_clean( $level );
	}

	if ( isset( $user_progress['course_progress_pager'] ) ) {
		$reply_data['pager'] = SFWD_LMS::get_template( 
			'learndash_pager.php', 
			array(
				'pager_results' => $user_progress['course_progress_pager'], 
				'pager_context' => 'course_info_courses'
			) 
		);
	}
	
	echo json_encode( $reply_data );
	die();
}
add_action( 'wp_ajax_ld_course_progress_pager', 'wp_ajax_ld_course_progress_pager' );
add_action( 'wp_ajax_nopriv_ld_course_progress_pager', 'wp_ajax_ld_course_progress_pager' );

function wp_ajax_ld_quiz_progress_pager() {
	
	if ( ! is_user_logged_in() ) return '';
	if ( ! current_user_can( 'read' ) ) return '';
	
	add_filter('learndash_quiz_info_paged', function( $paged = 1 ) {
		if ( ( isset( $_POST['paged'] ) ) && ( !empty( $_POST['paged'] ) ) ) {
			$paged = intval( $_POST['paged'] );
		}
		return $paged;
	});

	if ( isset( $_POST['shortcode_atts'] ) )
		$shortcode_atts = $_POST['shortcode_atts'];
	else
		$shortcode_atts = array();

	$user_id = get_current_user_id();
	if ( ( isset( $shortcode_atts['user_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
		$shortcode_atts['user_id'] = absint( $shortcode_atts['user_id'] );
		if ( $user_id !== $shortcode_atts['user_id'] ) {
			if ( ( learndash_is_group_leader_user() ) && ( learndash_is_group_leader_of_user( $user_id, $shortcode_atts['user_id'] ) ) ) {
				$user_id = intval( $shortcode_atts['user_id'] );
			} else if ( learndash_is_admin_user() ) {	
				$user_id = intval( $shortcode_atts['user_id'] );
			} 
		}
	}

	$shortcode_atts['return'] = true;
	$shortcode_atts['type'] = 'quiz';
	
	// Setup the pager filter. 
	if ( !learndash_ajax_pager_verify_atts( $user_id, $shortcode_atts ) ) {
		return '';
	}

	$reply_data = array();

	$user_progress = SFWD_LMS::get_course_info( $user_id, $shortcode_atts );

	if ( ( isset( $user_progress['quizzes'] ) ) && ( !empty( $user_progress['quizzes'] ) ) ) {
		$quizzes = $user_progress['quizzes'];
		
		$level = ob_get_level();
		ob_start();

		$template_file = SFWD_LMS::get_template(
			'quiz_progress_rows',
			null,
			null, 
			true 
		); 

		if ( ! empty( $template_file ) ) {
			include $template_file;
		}

		$reply_data['content'] = learndash_ob_get_clean( $level );
	}

	if ( isset( $user_progress['quizzes_pager'] ) ) {
		$reply_data['pager'] = SFWD_LMS::get_template( 
			'learndash_pager.php', 
			array(
			'pager_results' => $user_progress['quizzes_pager'], 
			'pager_context' => 'course_info_quizzes'
			) 
		);
	}
	
	echo json_encode( $reply_data );
	die();
}
	
add_action( 'wp_ajax_ld_quiz_progress_pager', 'wp_ajax_ld_quiz_progress_pager' );


/**
 * Course Navigation AJAX Pager handler function
 *
 * @since 2.5.4
 */
function wp_ajax_ld_course_navigation_pager() {
	$reply_data = array();
	
	if ( ( isset( $_POST['paged'] ) ) && ( !empty( $_POST['paged'] ) ) ) {
		$paged = intval( $_POST['paged'] );
	} else {
		$paged = 1;
	}

	if ( ( isset( $_POST['widget_data']['course_id'] ) ) && ( !empty( $_POST['widget_data']['course_id'] ) ) ) {
		$course_id = intval( $_POST['widget_data']['course_id'] );
	} else {
		$course_id = 0;
	}
		
	if ( ( isset( $_POST['widget_data']['widget_instance'] ) ) && ( !empty( $_POST['widget_data']['widget_instance'] ) ) ) {
		$widget_instance = $_POST['widget_data']['widget_instance'];
	} else {
		$widget_instance = array();
	}
	
	if ( ( !empty( $course_id ) ) && ( !empty( $widget_instance ) ) ) {
		
		$lesson_query_args = array();
		$course_lessons_per_page = learndash_get_course_lessons_per_page( $course_id );
		if ( $course_lessons_per_page > 0 ) {		
			$lesson_query_args['pagination'] = 'true';
			$lesson_query_args['paged'] = $paged;
		}
		$widget_instance['show_widget_wrapper'] = false;
		
		$level = ob_get_level();
		ob_start();
		learndash_course_navigation( $course_id, $widget_instance, $lesson_query_args );
		$reply_data['content'] = learndash_ob_get_clean( $level );		
	}
	
	echo json_encode( $reply_data );
	die();
}
	
add_action( 'wp_ajax_ld_course_navigation_pager', 'wp_ajax_ld_course_navigation_pager' );
add_action( 'wp_ajax_nopriv_ld_course_navigation_pager', 'wp_ajax_ld_course_navigation_pager' );


/**
 * Course Navigation AJAX Pager handler function
 *
 * @since 2.5.4
 */
function wp_ajax_ld_course_navigation_admin_pager() {
	$reply_data = array();
	
	if ( ( isset( $_POST['paged'] ) ) && ( !empty( $_POST['paged'] ) ) ) {
		$paged = intval( $_POST['paged'] );
	} else {
		$paged = 1;
	}

	if ( ( isset( $_POST['widget_data'] ) ) && ( !empty( $_POST['widget_data'] ) ) ) {
		$widget_data = $_POST['widget_data'];
	} else {
		$widget_data = array();
	}

	if ( ( isset( $widget_data['course_id'] ) ) && ( !empty( $widget_data['course_id'] ) ) ) {
		$course_id = intval( $widget_data['course_id'] );
	} else {
		$course_id = 0;
	}
		
	if ( ( !empty( $course_id ) ) && ( !empty( $widget_data ) ) ) {
		
		if ( ( isset( $_POST['widget_data']['nonce'] ) ) && ( ! empty( $_POST['widget_data']['nonce'] ) ) && ( wp_verify_nonce( $_POST['widget_data']['nonce'], 'ld_course_navigation_admin_pager_nonce_' . $course_id . '_' . get_current_user_id() ) ) ) {

			$lesson_query_args = array();
			//$course_lessons_per_page = learndash_get_course_lessons_per_page( $course_id );
			//if ( $course_lessons_per_page > 0 ) {		
				$lesson_query_args['pagination'] = 'true';
				$lesson_query_args['paged'] = $paged;
			//}
			$widget_data['show_widget_wrapper'] = false;
			
			$level = ob_get_level();
			ob_start();
			learndash_course_navigation_admin( $course_id, $widget_data, $lesson_query_args );
			$reply_data['content'] = learndash_ob_get_clean( $level );
		}
	}
	
	echo json_encode( $reply_data );
	die();
}
	
add_action( 'wp_ajax_ld_course_navigation_admin_pager', 'wp_ajax_ld_course_navigation_admin_pager' );


function learndash_ajax_pager_verify_atts( $user_id, $shortcode_atts ) {
	$use_filter = false;
	
	if ( ( !empty( $user_id ) ) && ( isset( $shortcode_atts['pagenow'] ) ) ) {
		if ( ( isset( $shortcode_atts['pagenow_nonce'] ) ) && ( !empty( $shortcode_atts['pagenow_nonce'] ) ) ) {
			if ( ( $shortcode_atts['pagenow'] == 'profile.php' ) || ( $shortcode_atts['pagenow'] == 'user-edit.php' ) ) {
				if ( wp_verify_nonce( $shortcode_atts['pagenow_nonce'], $shortcode_atts['pagenow'] .'-'. $user_id ) ) {
					$use_filter = true;
				}
			} else if ( $shortcode_atts['pagenow'] == 'group_admin_page' ) {
				if ( ( isset( $shortcode_atts['group_id'] ) ) && ( intval( $shortcode_atts['group_id'] ) ) ) {
					if ( wp_verify_nonce( $shortcode_atts['pagenow_nonce'], $shortcode_atts['pagenow'] .'-'. intval( $shortcode_atts['group_id'] ).'-'. $user_id ) ) {
						$use_filter = true;
					}
				} 
			} else if ( $shortcode_atts['pagenow'] == 'learndash' ) {
				if ( wp_verify_nonce( $shortcode_atts['pagenow_nonce'], $shortcode_atts['pagenow'] .'-'. $user_id ) ) {
					// Hard return here because we don't want to set $user_filter to true as that will trigger the 
					// logic below to show the admin only details link. 
					return true;
				}
			}
		}
	
		if ( $use_filter == true ) {
			// The following filter is called during the template output. Normally if the admin is viewing profile.php
			// We show the edit options. but via AJAX we don't know from where the user is viewing. It may be a front-end 
			// page etc. So as part of the shortcode atts we store the pagenow and a nonce we then verify within the logic below.
			add_filter( 'learndash_show_user_course_complete_options', function( $show_admin_options, $user_id = 0 ) {
				if ( current_user_can( 'edit_users' ) )  {
					$show_admin_options = true;
				} 

				return $show_admin_options;
			}, 1, 2 );
		}
	}
		
	return $use_filter;
}