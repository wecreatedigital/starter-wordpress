<?php
if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Topic' ) ) ) {
	class LDLMS_Model_Topic extends LDLMS_Model_Post {

		private static $post_type = 'sfwd-topic';
		
		function __construct( $topic_id = 0 ) {
			$this->load( $topic_id );
		}

		// Endof functions.
	}
}