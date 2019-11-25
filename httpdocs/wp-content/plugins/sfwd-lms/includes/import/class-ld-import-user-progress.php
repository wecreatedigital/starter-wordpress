<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LearnDash Import CPT
 *
 * This file contains functions to handle import of the LearnDash CPT Course
 *
 * @package LearnDash
 * @subpackage LearnDash
 * @since 1.0.0
 */

if ( ( !class_exists( 'LearnDash_Import_User_Progress' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	class LearnDash_Import_User_Progress extends LearnDash_Import_Post {
		private $version			= '1.0';
		
		protected $dest_post_type 	= 'sfwd-courses';
		protected $source_post_type = 'sfwd-courses';
		
		protected $dest_taxonomy	= 'ld_course_category';
		
	    function __construct( ) {
			parent::__construct(); 
		}

		function duplicate_post( $source_post_id = 0, $force_copy = false ) {
			$new_post = parent::duplicate_post( $source_post_id, $force_copy );
			
			return $new_post;
		}

		function duplicate_post_tax_term( $source_term, $create_parents = false ) {
			$new_term = parent::duplicate_post_tax_term( $source_term, $create_parents );
			
			return $new_term;
		}

		// Prerequisite only support by Courses. (well and quizzes)
		// This function also enables course prerequisite
		function set_post_prerequisite( $dest_post_id = 0, $prereq_post_id = 0 ) {
			if ( ( !empty( $dest_post_id )) && ( !empty( $prereq_post_id ) ) ) {
				$this->set_course_prerequisite_enabled( $dest_post_id, true );
				
				$prerequisite_posts = learndash_get_course_prerequisite( $dest_post_id );
				$prerequisite_posts[] = $prereq_post_id;
				$this->set_course_prerequisite( $dest_post_id, $prerequisite_posts );
			}
		}

		function set_course_prerequisite_enabled( $course_id, $enabled = true ) {
			if ( $enabled === true ) 
				$enabled = 'on';
	
			if ( $enabled != 'on' )
				$enabled = '';
	
			return learndash_update_setting( $course_id, 'course_prerequisite_enabled', $enabled );
		}
		
		function set_course_prerequisite( $course_id = 0, $course_prerequisites = array() ) {
			if ( !empty( $course_id ) ) {
				if ( ( !empty( $course_prerequisites ) ) && ( is_array( $course_prerequisites ) ) ) {
					$course_prerequisites = array_unique( $course_prerequisites );
				}
		
				return learndash_update_setting( $course_id, 'course_prerequisite', (array)$course_prerequisites );
			}
		}
			
		// End of functions		
	}
}