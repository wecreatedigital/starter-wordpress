<?php
/**
 * Handles all server side logic for the ld-visitor Gutenberg Block. This block is functionally the same
 * as the [visitor] shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Visitor' ) ) ) {
	/**
	 * Class for handling LearnDash Visitor Block
	 */
	class LearnDash_Gutenberg_Block_Visitor extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'visitor';
			$this->block_slug = 'ld-visitor';
			$this->self_closing = false;

			$this->block_attributes = array(
				'course_id' => array(
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
new LearnDash_Gutenberg_Block_Visitor();