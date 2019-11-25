<?php
/**
 * Handles all server side logic for the ld-course-notstarted Gutenberg Block. This block is functionally the same
 * as the [course_notstarted] shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Course_Not_Started' ) ) ) {
	/**
	 * Class for handling LearnDash Course Not Started Block
	 */
	class LearnDash_Gutenberg_Block_Course_Not_Started extends LearnDash_Gutenberg_Block {

				/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'course_notstarted';
			$this->block_slug = 'ld-course-notstarted';
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
new LearnDash_Gutenberg_Block_Course_Not_Started();
