<?php
/**
 * Handles all server side logic for the ld-course-complete Gutenberg Block. This block is functionally the same
 * as the ld_course_complete shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Course_Complete' ) ) ) {
	/**
	 * Class for handling LearnDash Course Complete Block
	 */
	class LearnDash_Gutenberg_Block_Course_Complete extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'course_complete';
			$this->block_slug = 'ld-course-complete';
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

			$this->self_closing = false;

			$this->init();
		}
	}
}
new LearnDash_Gutenberg_Block_Course_Complete();
