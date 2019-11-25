<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LearnDash Import LearnDash Topic
 *
 * This file contains functions to handle import of the LearnDash Topic
 *
 * @package LearnDash
 * @subpackage LearnDash
 * @since 1.0.0
 */

if ( ( !class_exists( 'LearnDash_Import_Topic' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	class LearnDash_Import_Topic  extends LearnDash_Import_Post {
		private $version			= '1.0';
		
		protected $dest_post_type 	= 'sfwd-topic';
		protected $source_post_type = 'sfwd-topic';
		
		protected $dest_taxonomy	= 'ld_topic_tag';

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