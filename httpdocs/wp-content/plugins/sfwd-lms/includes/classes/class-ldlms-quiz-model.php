<?php
if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Quiz' ) ) ) {
	class LDLMS_Model_Quiz extends LDLMS_Model_Post {

		private static $post_type = 'sfwd-quiz';
		
		function __construct( $quiz_id = 0 ) {
			$this->load( $quiz_id );
		}

		// Endof functions.
	}
}