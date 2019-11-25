<?php
/**
 * Handles all server side logic for the ld-group Gutenberg Block. This block is functionally the same
 * as the ld_course_complete shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Group' ) ) ) {
	/**
	 * Class for handling LearnDash Group Block
	 */
	class LearnDash_Gutenberg_Block_Group extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'ld_group';
			$this->block_slug = 'ld-group';
			$this->self_closing = false;

			$this->block_attributes = array(
				'group_id' => array(
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
new LearnDash_Gutenberg_Block_Group();
