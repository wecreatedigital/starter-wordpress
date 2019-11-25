<?php
/**
 * LearnDash Factory Post Class.
 * This is a factory class used to instansiate course and quiz related data.
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	 exit;
}

require_once( LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/abstract-ldlms-model.php' );
require_once( LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/abstract-ldlms-model-post.php' );
require_once( LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/class-ldlms-model-course.php' );
require_once( LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/class-ldlms-model-lesson.php' );
require_once( LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/class-ldlms-course-steps.php' );
require_once( LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/class-ldlms-quiz-questions.php' );

//require_once( LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/class-ldlms-topic-model.php' );
//require_once( LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/class-ldlms-quiz-model.php' );


if ( ! class_exists( 'LDLMS_Factory_Post' ) ) {
	/**
	 * Class for LearnDash Factory Post.
	 */
	class LDLMS_Factory_Post {

		/**
		 * Static array of object instances.
		 *
		 * @var array $instances.
		 */
		private static $instances = array();

		/**
		 * Get a Course.
		 *
		 * @param integer $course Either course_id integer or WP_Post instance.
		 * @param boolean $bypass To force reload of instance.
		 *
		 * @return new instance of LDLMS_Model_Course
		 */
		public static function course( $course = null, $bypass = false ) {
			if ( ! empty( $course ) ) {
				$course_id = 0;

				$model = 'LDLMS_Model_Course';

				if ( is_numeric( $course ) ) {
					$course_id = absint( $course );

				} elseif ( ( $course instanceof WP_Post ) && ( isset( $course->ID ) ) ) {
					$course_id = absint( $course->ID );
				}

				if ( ! empty( $course_id ) ) {
					if ( ! isset( self::$instances[ $model ] ) ) {
						self::$instances[ $model ] = array();
					}

					if ( ( isset( self::$instances[ $model ][ $course_id ] ) ) && ( false === $bypass ) ) {
						return self::$instances[ $model ][ $course_id ];
					} else {
						try {
							self::$instances[ $model ][ $course_id ] = new $model( $course_id );
							return self::$instances[ $model ][ $course_id ];
						} catch ( LDLMS_Exception_NotFound $e ) {
							return null;
						}
					}
				}
			}
		}

		/**
		 * Get a Course Steps.
		 *
		 * @param mixed   $course Either course_id integer or WP_Post instance.
		 * @param boolean $bypass To force reload of instance.
		 *
		 * @return new instance of LDLMS_Model_Course
		 */
		public static function course_steps( $course = null, $bypass = false ) {
			if ( ! empty( $course ) ) {
				$course_id = 0;

				$model = 'LDLMS_Course_Steps';

				if ( is_numeric( $course ) ) {
					$course_id = absint( $course );

				} elseif ( ( $course instanceof WP_Post ) && ( isset( $course->ID ) ) ) {
					$course_id = absint( $course->ID );
				}

				if ( ! empty( $course_id ) ) {
					if ( ! isset( self::$instances[ $model ] ) )
						self::$instances[ $model ] = array();

					if ( ( isset( self::$instances[ $model ][ $course_id ] ) ) && ( false === $bypass ) ) {
						return self::$instances[ $model ][ $course_id ];
					} else {
						try {
							self::$instances[ $model ][ $course_id ] = new $model( $course_id );
							return self::$instances[ $model ][ $course_id ];
						} catch ( LDLMS_Exception_NotFound $e ) {
							return null;
						}
					}
				}
			}
		}

		/**
		 * Get a Lesson.
		 *
		 * @param mixed $course Either course_id integer or WP_Post instance.
		 * @param mixed $lesson Either lesson_id integer or WP_Post instance.
		 *
		 * @return new instance of LDLMS_Model_Course.
		 */
		public static function get_course_lessons( $course = null, $lesson = null ) {	
			if ( ! empty( $course ) ) {
				$course = self::get_course( $course );
				if ( $course ) {
					$lesson_id = 0;

					if ( is_numeric( $lesson ) ) {
						$lesson_id = absint( $lesson );

					} elseif ( ( $lesson instanceof WP_Post ) && ( isset( $lesson->ID ) ) ) {
						$lesson_id = absint( $lesson->ID );
					}

					$course_lesson = $course->get_lesson( $lesson_id );

					return $course_lesson;
				}
			}
		}

		/**
		 * Get a Quiz Questions.
		 *
		 * @param mixed   $quiz Either quiz_id integer or WP_Post instance.
		 * @param boolean $bypass To force reload of instance.
		 *
		 * @return new instance of LDLMS_Model_Course
		 */
		public static function quiz_questions( $quiz = null, $bypass = false ) {
			if ( ! empty( $quiz ) ) {
				$quiz_id = 0;

				$model = 'LDLMS_Quiz_Questions';

				if ( is_numeric( $quiz ) ) {
					$quiz_id = absint( $quiz );

				} elseif ( ( $quiz instanceof WP_Post ) && ( isset( $quiz->ID ) ) ) {
					$quiz_id = absint( $quiz->ID );
				}

				if ( ! empty( $quiz_id ) ) {
					if ( ! isset( self::$instances[ $model ] ) ) {
						self::$instances[ $model ] = array();
					}

					if ( ( isset( self::$instances[ $model ][ $quiz_id ] ) ) && ( false === $bypass ) ) {
						return self::$instances[ $model ][ $quiz_id ];
					} else {
						try {
							self::$instances[ $model ][ $quiz_id ] = new $model( $quiz_id );
							return self::$instances[ $model ][ $quiz_id ];
						} catch ( LDLMS_Exception_NotFound $e ) {
							return null;
						}
					}
				}
			}
		}
	}
}
