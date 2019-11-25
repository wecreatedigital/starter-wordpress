<?php
/**
 * Handles all server side logic for the ld-course-list Gutenberg Block. This block is functionally the same
 * as the ld_course_list shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Course_List' ) ) ) {
	/**
	 * Class for handling LearnDash Course List Block
	 */
	class LearnDash_Gutenberg_Block_Course_List extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_course_list';
			$this->block_slug       = 'ld-course-list';
			$this->block_attributes = array(
				'orderby'                 => array(
					'type' => 'string',
				),
				'order'                   => array(
					'type' => 'string',
				),
				'per_page'                => array(
					'type' => 'string',
				),
				'mycourses'               => array(
					'type' => 'string',
				),
				'show_content'            => array(
					'type' => 'boolean',
				),
				'show_thumbnail'          => array(
					'type' => 'boolean',
				),
				'course_category_name'    => array(
					'type' => 'string',
				),
				'course_cat'              => array(
					'type' => 'string',
				),
				'course_categoryselector' => array(
					'type' => 'boolean',
				),
				'course_tag'              => array(
					'type' => 'string',
				),
				'course_tag_id'           => array(
					'type' => 'string',
				),
				'category_name'           => array(
					'type' => 'string',
				),
				'cat'                     => array(
					'type' => 'string',
				),
				'categoryselector'        => array(
					'type' => 'boolean',
				),
				'tag'                     => array(
					'type' => 'string',
				),
				'tag_id'                  => array(
					'type' => 'string',
				),
				'preview_show'            => array(
					'type' => 'boolean',
				),
				'preview_user_id'         => array(
					'type' => 'string',
				),
				'course_grid'             => array(
					'type' => 'boolean',
				),
				'progress_bar'            => array(
					'type' => 'boolean',
				),
				'col'                     => array(
					'type' => 'string',
				),
				'example_show' => array(
					'type' => 'boolean',
				),
			);
			$this->self_closing     = true;

			$this->init();
		}

		/**
		 * Render Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content. In the case of this function the rendered output will be for the
		 * [ld_profile] shortcode.
		 *
		 * @since 2.5.9
		 *
		 * @param array $attributes Shortcode attrbutes.
		 * @return none The output is echoed.
		 */
		public function render_block( $attributes = array() ) {

			if ( is_user_logged_in() ) {

				$attributes           = apply_filters( 'learndash_block_markers_shortcode_atts', $attributes, $this->shortcode_slug, $this->block_slug, '' );
				$shortcode_params_str = $this->prepare_course_list_atts_to_param( $attributes );
				$shortcode_params_str = '[' . $this->shortcode_slug . ' ' . $shortcode_params_str . ']';
				$shortcode_out        = do_shortcode( $shortcode_params_str );

				// This is mainly to protect against emty returns with the Gutenberg ServerSideRender function.
				return $this->render_block_wrap( $shortcode_out );
			}
			wp_die();
		}

		/**
		 * Called from the LD function learndash_convert_block_markers_shortcode() when parsing the block content.
		 *
		 * @since 2.5.9
		 *
		 * @param array  $attributes The array of attributes parse from the block content.
		 * @param string $shortcode_slug This will match the related LD shortcode ld_profile, ld_course_list, etc.
		 * @param string $block_slug This is the block token being processed. Normally same as the shortcode but underscore replaced with dash.
		 * @param string $content This is the orignal full content being parsed.
		 *
		 * @return array $attributes.
		 */
		public function learndash_block_markers_shortcode_atts_filter( $attributes = array(), $shortcode_slug = '', $block_slug = '', $content = '' ) {
			if ( $shortcode_slug === $this->shortcode_slug ) {
				if ( isset( $attributes['preview_show'] ) ) {
					unset( $attributes['preview_show'] );
				}

				if ( isset( $attributes['preview_user_id'] ) ) {
					unset( $attributes['preview_user_id'] );
				}

				if ( isset( $attributes['per_page'] ) ) {
					if ( ! isset( $attributes['num'] ) ) {
						$attributes['num'] = $attributes['per_page'];
						unset( $attributes['per_page'] );
					}
				}

				if ( ( ! isset( $attributes['course_grid'] ) ) || ( true === $attributes['course_grid'] ) ) {
					$attributes['course_grid'] = 'true';
				}

				if ( ( isset( $attributes['course_categoryselector'] ) ) && ( true === $attributes['course_categoryselector'] ) ) {
					$attributes['course_categoryselector'] = 'true';
				}

				if ( ( isset( $attributes['categoryselector'] ) ) && ( true === $attributes['categoryselector'] ) ) {
					$attributes['categoryselector'] = 'true';
				}

				/**
				 * Not the best place to make this call this but we need to load the
				 * Course Grid resources.
				 */
				if ( 'true' === $attributes['course_grid'] ) {
					learndash_enqueue_course_grid_scripts();
				}

				if ( ( isset( $attributes['progress_bar'] ) ) && ( true === $attributes['progress_bar'] ) ) {
					$attributes['progress_bar'] = 'true';
				}
			}

			return $attributes;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Course_List();
