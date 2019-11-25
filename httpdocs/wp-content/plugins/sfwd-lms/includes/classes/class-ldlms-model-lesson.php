<?php
if ( !class_exists( 'LDLMS_Model_Lesson' ) ) {
	class LDLMS_Model_Lesson extends LDLMS_Model_Post {

		private static $post_type = 'sfwd-lessons';
		
		function __construct( $lesson_id = 0 ) {
			$this->load( $lesson_id );
		}

		static function get_post_type() {
			return self::$post_type;
		}

		function load( $lesson_id ) {
			if ( !empty( $lesson_id ) ) {
				$this->lesson_id = intval( $lesson_id );
				//$this->init();
			}
		}
		
		static function get_settings() {
			return sfwd_lms_get_post_options( self::$post_type );
		}
		
		function load_steps( $query_args = array() ) {
			
			$default_query_args = array(
				'post_type'		=>	$this->get_post_type(),
			);

			$this->last_query_args = wp_parse_args( $query_args, $default_query_args );
			
			//error_log('query_args<pre>'. print_r($this->last_query_args, true) .'</pre>');
			$this->last_query = new WP_Query( $this->last_query_args );
			//error_log('last_query<pre>'. print_r($this->last_query, true) .'</pre>');
			if ( ( $this->last_query instanceof WP_Query ) && ( property_exists( $this->last_query, 'posts' ) ) ) {
				$lessons_ids = $this->last_query->posts;
			}
			
			return $lessons_ids;
		}
		
		
	}
}