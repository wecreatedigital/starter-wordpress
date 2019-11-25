<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LearnDash Import LearnDash Lesson
 *
 * This file contains functions to handle import of the LearnDash CPT Lesson
 *
 * @package LearnDash
 * @subpackage LearnDash
 * @since 1.0.0
 */

if ( ( !class_exists( 'LearnDash_Import_Lesson' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	class LearnDash_Import_Lesson  extends LearnDash_Import_Post {
		private $version			= '1.0';
		
		protected $dest_post_type 	= 'sfwd-lessons';
		protected $source_post_type = 'sfwd-lessons';
		
		protected $dest_taxonomy	= 'ld_lesson_tag';

	    function __construct( ) {
			parent::__construct(); 
		}

		function duplicate_post( $source_post_id = 0, $force_copy = false ) {
			$new_post = parent::duplicate_post( $source_post_id, $force_copy );
			
			return $new_post;
		}

		function duplicate_post_tax_term( $source_term, $create_parents = false ) {
			$new_term = parent::duplicate_post( $source_term, $create_parents );
			
			return $new_term;
		}

		// End of functions
	}
}