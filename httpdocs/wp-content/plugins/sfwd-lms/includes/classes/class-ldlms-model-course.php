<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ( ! class_exists( 'LDLMS_Model_Course' ) ) && ( class_exists( 'LDLMS_Model_Post' ) ) ) {
	class LDLMS_Model_Course extends LDLMS_Model_Post {

		private static $post_type = 'sfwd-courses';
		private $steps_object = null;

		function __construct( $course_id = 0 ) {
			if ( ! empty( $course_id ) ) {
				$course_id = absint( $course_id );
				if ( ! $this->initialize( $course_id ) )
					throw new LDLMS_Exception_NotFound();
			} else {
				throw new LDLMS_Exception_NotFound();
			}
		}

		private function initialize( $course_id ) {
			if ( ! empty( $course_id ) ) {
				$course = get_post( $course_id );
				if ( ( $course instanceof WP_Post ) && ( $course->post_type === LDLMS_Model_Course::$post_type ) ) {
					$this->id = $course_id;
					$this->post = $course;

					$this->load_settings();
					return true;
				} else {
					return false;
				}
			}
		}

		public function load_settings() {
			if ( !empty( $this->post ) ) {
				$settings = learndash_get_setting( $this->post );
				if ( ! is_array( $settings ) ) {
					if ( ! empty( $settings ) ) {
						LDLMS_Model_Course::$settings = array( $settings );
					} else {
						LDLMS_Model_Course::$settings = array();
					}
				}

				$lesson_settings = LDLMS_Model_Lesson::get_settings();

				// We can't do a sinple merge because the keys are different. So hopefuly we can remember to update this with the logic for each mis-matching key.
				if ( ( isset( $lesson_settings['order'] ) ) && ( ! empty( $lesson_settings['order'] ) ) ) {
					LDLMS_Model_Course::$settings['course_lesson_order'] = $lesson_settings['order'];
				}

				if ( ( isset( $lesson_settings['orderby'] ) ) && ( ! empty( $lesson_settings['orderby'] ) ) ) {
					LDLMS_Model_Course::$settings['course_lesson_orderby'] = $lesson_settings['orderby'];
				}

				if ( ( isset( $lesson_settings['posts_per_page'] ) ) && ( ! empty( $lesson_settings['posts_per_page'] ) ) ) {
					LDLMS_Model_Course::$settings['course_lesson_per_page'] = $lesson_settings['posts_per_page'];
				}
			}
		}

		public static function get_setting( $setting_key = '' ) {
			if ( ( ! empty( $setting_key ) ) && ( isset( self::$settings[ $setting_key ] ) ) ) {
				return self::$settings[ $setting_key ];
			} else {
				return self::$settings;
			}
		}

		public static function get_post_type() {
			return self::$post_type;
		}
	}
}
