<?php
/**
 * Registers widget for displaying a list of lessons for a course and tracks lesson progress.
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\CPT
 */

add_action( 'widgets_init', 'learndash_register_cpt_lesson_widget' );

function learndash_register_cpt_lesson_widget() {
	register_widget( 'Lesson_Widget' );
}

/**
 * Adds widget for displaying lessons
 */
class Lesson_Widget extends WP_Widget {

	protected $post_type = 'sfwd-lessons';
	protected $post_name = 'Lesson';
	protected $post_args;

	/**
	 * Set up post arguments for widget
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		$args = array();

		$args['description'] = sprintf( esc_html_x('Displays a list of %1$s for a %2$s and tracks %3$s progress.', 'Displays a list of lessons for a course and tracks lesson progress.', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons'), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'lesson' ));

		if ( empty( $this->post_args) ) {
			$this->post_args = array( 'post_type' => $this->post_type, 'numberposts' => -1, 'order' => 'DESC', 'orderby' => 'date' );
		}

		parent::__construct( "{$this->post_type}-widget", $this->post_name, $args );
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

		extract( $args, EXTR_SKIP );

		/* Before Widget content */
		$buf = $before_widget;

		/**
		 * Filter widget title
		 *
		 * @param string
		 */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		if ( ! empty( $title) ) {
			$buf .= $before_title . $title . $after_title;
		}

		$buf .= '<ul>';

		/* Display Widget Data */
		$course_id = learndash_get_course_id();

		if ( empty( $course_id ) || ! is_single() ) {
			return '';
		}

		$course_lessons_list          = $this->course_lessons_list( $course_id );
		$stripped_course_lessons_list = strip_tags( $course_lessons_list );

		if ( empty( $stripped_course_lessons_list ) ) {
			return '';
		}

		$buf .= $course_lessons_list;

		/* After Widget content */
		$buf .= '</ul>' . $after_widget;

		echo $buf;

		$learndash_shortcode_used = true;

	}



	/**
	 * Sets up course lesson list HTML
	 *
	 * @since 2.1.0
	 *
	 * @param  int 		$course_id 	course id
	 * @return string   $html       output
	 */
	function course_lessons_list( $course_id ) {
		$course = get_post( $course_id );

		if ( empty( $course->ID) || $course_id != $course->ID ) {
			return '';
		}

		$html                  = '';
		$course_lesson_orderby = learndash_get_setting( $course_id, 'course_lesson_orderby' );
		$course_lesson_order   = learndash_get_setting( $course_id, 'course_lesson_order' );
		$lessons               = sfwd_lms_get_post_options( 'sfwd-lessons' );
		$orderby               = ( empty( $course_lesson_orderby)) ? $lessons['orderby'] : $course_lesson_orderby;
		$order                 = ( empty( $course_lesson_order)) ? $lessons['order'] : $course_lesson_order;
		$post__in				= '';
		$meta_key				= 'course_id';
		$meta_value				= $course_id;
		
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$course_lessons = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
			if ( !empty( $course_lessons ) ) {
				$order = '';
				$orderby ='post__in';
				$post__in = implode(',', $course_lessons );
				$meta_key = '';
				$meta_value = '';
			}
		}
		
		$shortcode = '[sfwd-lessons meta_key="' . $meta_key . '" meta_value="'. $meta_value .'" order="'. $order .'" orderby="'. $orderby .'" post__in="'. $post__in .'" posts_per_page="'. $lessons['posts_per_page'] .'" wrapper="li"]';
		
		$lessons               = wptexturize( do_shortcode(  $shortcode ) );
		
		$html .= $lessons;
		return $html;
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
	public function update( $new_instance, $old_instance ) {
		/* Updates widget title value */
		$instance = $old_instance;
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
	public function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance['title'] );
		} else {
			$title = $this->post_name;
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' );?>"><?php esc_html_e( 'Title:', 'learndash' );?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' );?>" name="<?php echo $this->get_field_name( 'title' );?>" type="text" value="<?php echo $title;?>" />
		</p>
		<?php
	}
}



add_action( 'widgets_init', 'learndash_register_cpt_course_widget' );

function learndash_register_cpt_course_widget() {
	register_widget( 'Course_Widget' );
}

/**
 * Adds widget for displaying courses
 */
class Course_Widget extends WP_Widget {

	protected $post_type = 'sfwd-courses';
	protected $post_name = 'Course';
	protected $post_args;

	/**
	 * Set up post arguments for widget
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		$args = array();

		$this->post_name = LearnDash_Custom_Label::get_label( 'course' );

		if ( empty( $args['description'] ) ) {
			$args['description'] = sprintf( esc_html_x( 'Displays a list of %s', 'placeholder: Course', 'learndash' ), $this->post_name );
		}

		if ( empty( $this->post_args) ) {
			$this->post_args = array( 'post_type' => $this->post_type, 'numberposts' => -1, 'order' => 'DESC', 'orderby' => 'date' );
		}

		parent::__construct( "{$this->post_type}-widget", $this->post_name, $args );
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
		
		extract( $args, EXTR_SKIP );

		/* Before Widget content */
		$buf = $before_widget;

		/**
		 * Filter widget title
		 *
		 * @param string
		 */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		if ( ! empty( $title) ) {
			$buf .= $before_title . $title . $after_title;
		}

		$buf .= '<ul>';

		/* Display Widget Data */
		$args = $this->post_args;

		$args['posts_per_page'] = $args['numberposts'];
		$args['wrapper']        = 'li';
		global $shortcode_tags, $post;

		if ( ! empty( $shortcode_tags[ $this->post_type ] ) ) {
			$buf .= call_user_func( $shortcode_tags[ $this->post_type ], $args, null, $this->post_type );
		}

		/* After Widget content */
		$buf .= '</ul>' . $after_widget;

		echo $buf;

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
	public function update( $new_instance, $old_instance ) {
		/* Updates widget title value */
		$instance = $old_instance;
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
	public function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance['title'] );
		} else {
			$title = $this->post_name;
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' );?>"><?php esc_html_e( 'Title:', 'learndash' );?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' );?>" name="<?php echo $this->get_field_name( 'title' );?>" type="text" value="<?php echo $title;?>" />
		</p>
		<?php
	}
}



add_action( 'widgets_init', 'learndash_register_cpt_quiz_widget' );

function learndash_register_cpt_quiz_widget() {
	register_widget( 'Quiz_Widget' );
}

/**
 * Adds widget for displaying quizzes
 */
class Quiz_Widget extends WP_Widget {

	protected $post_type = 'sfwd-quiz';
	protected $post_name = 'Quiz';
	protected $post_args;

	/**
	 * Set up post arguments for widget
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		$args = array();
		
		$this->post_name = LearnDash_Custom_Label::get_label( 'quiz' );
		
		if ( empty( $args['description'] ) ) {
			$args['description'] = sprintf( esc_html_x( 'Displays a list of %s', 'placeholder: Quiz', 'learndash' ), $this->post_name );
		}

		if ( empty( $this->post_args) ) {
			$this->post_args = array( 'post_type' => $this->post_type, 'numberposts' => -1, 'order' => 'DESC', 'orderby' => 'date' );
		}

		parent::__construct( "{$this->post_type}-widget", $this->post_name, $args );
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

		extract( $args, EXTR_SKIP );

		/* Before Widget content */
		$buf = $before_widget;

		/**
		 * Filter widget title
		 *
		 * @param string
		 */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		if ( ! empty( $title) ) {
			$buf .= $before_title . $title . $after_title;
		}

		$buf .= '<ul>';

		/* Display Widget Data */
		$args = $this->post_args;

		$args['posts_per_page'] = $args['numberposts'];
		$args['wrapper']        = 'li';
		global $shortcode_tags, $post;

		if ( ! empty( $shortcode_tags[ $this->post_type ] ) ) {
			$buf .= call_user_func( $shortcode_tags[ $this->post_type ], $args, null, $this->post_type );
		}

		/* After Widget content */
		$buf .= '</ul>' . $after_widget;

		echo $buf;

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
	public function update( $new_instance, $old_instance ) {
		/* Updates widget title value */
		$instance = $old_instance;
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
	public function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance['title'] );
		} else {
			$title = $this->post_name;
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' );?>"><?php esc_html_e( 'Title:', 'learndash' );?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' );?>" name="<?php echo $this->get_field_name( 'title' );?>" type="text" value="<?php echo $title;?>" />
		</p>
		<?php
	}
}



add_action( 'widgets_init', 'learndash_register_cpt_transactions_widget' );

function learndash_register_cpt_transactions_widget() {
	register_widget( 'Transactions_Widget' );
}

/**
 * Adds widget for displaying transactions
 */
class Transactions_Widget extends WP_Widget {

	protected $post_type = 'sfwd-transactions';
	protected $post_name = 'Transactions';
	protected $post_args;

	/**
	 * Set up post arguments for widget
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		$args = array();

		if ( empty( $args['description'] ) ) {
			$args['description'] = sprintf( esc_html_x( 'Displays a list of %s', 'placeholder: Transactions', 'learndash' ), $this->post_name );
		}

		if ( empty( $this->post_args) ) {
			$this->post_args = array( 'post_type' => $this->post_type, 'numberposts' => -1, 'order' => 'DESC', 'orderby' => 'date' );
		}

		parent::__construct( "{$this->post_type}-widget", $this->post_name, $args );
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

		extract( $args, EXTR_SKIP );

		/* Before Widget content */
		$buf = $before_widget;

		/**
		 * Filter widget title
		 *
		 * @param string
		 */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		if ( ! empty( $title) ) {
			$buf .= $before_title . $title . $after_title;
		}

		$buf .= '<ul>';

		/* Display Widget Data */
		$args = $this->post_args;

		$args['posts_per_page'] = $args['numberposts'];
		$args['wrapper']        = 'li';
		global $shortcode_tags, $post;

		if ( ! empty( $shortcode_tags[ $this->post_type ] ) ) {
			$buf .= call_user_func( $shortcode_tags[ $this->post_type ], $args, null, $this->post_type );
		}

		/* After Widget content */
		$buf .= '</ul>' . $after_widget;

		echo $buf;

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
	public function update( $new_instance, $old_instance ) {
		/* Updates widget title value */
		$instance = $old_instance;
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
	public function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance['title'] );
		} else {
			$title = $this->post_name;
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' );?>"><?php esc_html_e( 'Title:', 'learndash' );?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' );?>" name="<?php echo $this->get_field_name( 'title' );?>" type="text" value="<?php echo $title;?>" />
		</p>
		<?php
	}
}



add_action( 'widgets_init', 'learndash_register_cpt_certificates_widget' );

function learndash_register_cpt_certificates_widget() {
	register_widget( 'Certificates_Widget' );
}

/**
 * Adds widget for displaying certificates
 */
class Certificates_Widget extends WP_Widget {

	protected $post_type = 'sfwd-certificates';
	protected $post_name = 'Certificates';
	protected $post_args;

	/**
	 * Set up post arguments for widget
	 *
	 * @since 2.1.0
	 *
	 */
	public function __construct() {
		$args = array();

		if ( empty( $args['description'] ) ) {
			$args['description'] = sprintf( esc_html_x( 'Displays a list of %s', 'placeholder: Certificates', 'learndash' ), $this->post_name );
		}

		if ( empty( $this->post_args) ) {
			$this->post_args = array( 'post_type' => $this->post_type, 'numberposts' => -1, 'order' => 'DESC', 'orderby' => 'date' );
		}

		parent::__construct( "{$this->post_type}-widget", $this->post_name, $args );
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

		extract( $args, EXTR_SKIP );

		/* Before Widget content */
		$buf = $before_widget;

		/**
		 * Filter widget title
		 *
		 * @param string
		 */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		if ( ! empty( $title) ) {
			$buf .= $before_title . $title . $after_title;
		}

		$buf .= '<ul>';

		/* Display Widget Data */
		$args = $this->post_args;

		$args['posts_per_page'] = $args['numberposts'];
		$args['wrapper']        = 'li';
		global $shortcode_tags, $post;

		if ( ! empty( $shortcode_tags[ $this->post_type ] ) ) {
			$buf .= call_user_func( $shortcode_tags[ $this->post_type ], $args, null, $this->post_type );
		}

		/* After Widget content */
		$buf .= '</ul>' . $after_widget;

		echo $buf;

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
	public function update( $new_instance, $old_instance ) {
		/* Updates widget title value */
		$instance = $old_instance;
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
	public function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance['title'] );
		} else {
			$title = $this->post_name;
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' );?>"><?php esc_html_e( 'Title:', 'learndash' );?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' );?>" name="<?php echo $this->get_field_name( 'title' );?>" type="text" value="<?php echo $title;?>" />
		</p>
		<?php
	}
}