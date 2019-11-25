<?php
/**
 * Utility class to contain all the custom post typee used within LearnDash.
 *
 * @since 2.6.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Post_Types' ) ) {
	/**
	 * Class to create the instance.
	 */
	class LDLMS_Post_Types {

		/**
		 * Collection of all post types.
		 *
		 * @var array $post_types.
		 */
		private static $post_types = array(
			'course'      => 'sfwd-courses',
			'lesson'      => 'sfwd-lessons',
			'topic'       => 'sfwd-topic',
			'quiz'        => 'sfwd-quiz',
			'question'    => 'sfwd-question',
			'transaction' => 'sfwd-transactions',
			'group'       => 'groups',
			'assignment'  => 'sfwd-assignment',
			'essay'       => 'sfwd-essays',
			'certificate' => 'sfwd-certificates',
		);

		/**
		 * Collection of all post types sections.
		 *
		 * @var array $post_type_sections.
		 */
		private static $post_type_sections = array(
			'all'            => array(
				'course',
				'lesson',
				'topic',
				'quiz',
				'question',
				'transaction',
				'group',
				'assignment',
				'essay',
				'certificate',
			),
			'course'         => array(
				'course',
				'lesson',
				'topic',
				'quiz',
			),
			'course_steps'   => array(
				'lesson',
				'topic',
				'quiz',
			),
			'quiz'           => array(
				'quiz',
				'question',
			),
			'quiz_questions' => array(
				'question',
			),
		);

		/**
		 * Public constructor for class
		 *
		 * @since 2.6.0
		 */
		public function __construct() {
		}

		/**
		 * Public Initialize function for class
		 *
		 * @since 2.6.0
		 */
		public static function init() {
			/**
			 * We really only need to build the full table names once. So
			 * we use a static flag to control the processing.
			 */
			static $init_called = false;

			if ( true !== $init_called ) {
				$init_called = true;

				/**
				 * Fitler the list of custom database tables.
				 *
				 * @since 2.6.0
				 */
				self::$post_types = apply_filters( 'learndash_custom_post_types', self::$post_types );
			}
		}

		/**
		 * Get an array of all custom tables.
		 *
		 * @since 2.6.0
		 *
		 * @param string $post_type_section Which group of post_types to return. Default is all.
		 * @return array of post type slugs.
		 */
		public static function get_post_types( $post_type_section = 'all' ) {
			$post_types_return = array();
			if ( ( ! empty( $post_type_section ) ) && ( isset( self::$post_type_sections[ $post_type_section ] ) ) ) {
				$post_type_keys = self::$post_type_sections[ $post_type_section ];
				if ( ! empty( $post_type_keys ) ) {
					foreach ( $post_type_keys as $post_type_key ) {
						$post_types_return[] = self::$post_types[ $post_type_key ];
					}
				}
			}
			return $post_types_return;
		}

		/**
		 * Utility function to return the post type slug. This is to prevent hard-coding
		 * of the slug throughout the code files.
		 *
		 * @since 2.6.0
		 *
		 * @param string $post_type_key Internal key used to identify the post_type.
		 * @return string post type slug if found.
		 */
		public static function get_post_type_slug( $post_type_key = '' ) {

			if ( isset( self::$post_types[ $post_type_key ] ) ) {
				return self::$post_types[ $post_type_key ];
			}
		}

		/**
		 * Utility function to return the post type key. This is to prevent hard-coding
		 * of the key throughout the code files.
		 *
		 * @since 2.6.0
		 *
		 * @param string $post_type_slug Internal slug used to identify the post_type.
		 * @return string post type key if found.
		 */
		public static function get_post_type_key( $post_type_slug = '' ) {

			if ( ( ! empty( self::$post_types ) ) && ( ! empty( $post_type_slug ) ) ) {
				foreach ( self::$post_types as $_key => $_slug ) {
					if ( $post_type_slug === $_slug ) {
						return $_key;
					}
				}
			}
		}

		// End of functions.
	}
}

// These are the base table names WITHOUT the $wpdb->prefix.
global $learndash_post_types;
$learndash_post_types = LDLMS_Post_Types::get_post_types();

function learndash_get_post_types( $post_section_key = 'all' ) {
	return LDLMS_Post_Types::get_post_types( $post_section_key );
}

function learndash_get_post_type_slug( $post_type_key = '' ) {
	if ( ! empty( $post_type_key ) ) {
		return LDLMS_Post_Types::get_post_type_slug( $post_type_key );
	}
}

function learndash_get_post_type_key( $post_type_slug = '' ) {
	if ( ! empty( $post_type_slug ) ) {
		return LDLMS_Post_Types::get_post_type_key( $post_type_slug );
	}
}