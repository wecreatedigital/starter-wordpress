<?php
/**
 * Handles all server side logic for the ld-student Gutenberg Block. This block is functionally the same
 * as the [student] shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Student' ) ) ) {
	/**
	 * Class for handling LearnDash LearnDash_Gutenberg_Block_Student Block
	 */
	class LearnDash_Gutenberg_Block_Student extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'student';
			$this->block_slug = 'ld-student';
			$this->self_closing = false;

			$this->block_attributes = array(
				'course_id' => array(
					'type' => 'string',
				),
				'user_id' => array(
					'type' => 'string',
				),
				'autop' => array(
					'type' => 'boolean',
				),
			);
			
			$this->init();
		}
	}
}
new LearnDash_Gutenberg_Block_Student();
