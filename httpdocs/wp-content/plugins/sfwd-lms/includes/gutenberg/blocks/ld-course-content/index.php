<?php
/**
 * Handles all server side logic for the ld-course-content Gutenberg Block. This block is functionally the same
 * as the course_content shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Course_Content' ) ) ) {
	/**
	 * Class for handling LearnDash Course Content Block
	 */
	class LearnDash_Gutenberg_Block_Course_Content extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'course_content';
			$this->block_slug = 'ld-course-content';
			$this->block_attributes = array(
				'course_id' => array(
					'type' => 'string',
				),
				'per_page' => array(
					'type' => 'string',
				),
				'preview_show' => array(
					'type' => 'boolean',
				),
				'preview_course_id' => array(
					'type' => 'string',
				),
				'example_show' => array(
					'type' => 'boolean',
				),
				'meta' => array(
					'type' => 'object',
				),
			);
			$this->self_closing = true;

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

			$attributes_meta = array();
			if ( isset( $attributes['meta'] ) ) {
				$attributes_meta = $attributes['meta'];
				unset( $attributes['meta'] );
			}

			if ( is_user_logged_in() ) {
				if ( ( isset( $attributes['example_show'] ) ) && ( ! empty( $attributes['example_show'] ) ) ) {
					$attributes['preview_course_id'] = $this->get_example_post_id( learndash_get_post_type_slug( 'course' ) );
					$attributes['preview_show'] = 1;
					unset( $attributes['example_show'] );
				}

				if ( ( isset( $attributes['preview_show'] ) ) && ( ! empty( $attributes['preview_show'] ) ) ) {
					if ( ( isset( $attributes['preview_course_id'] ) ) && ( ! empty( $attributes['preview_course_id'] ) ) ) {
						$attributes['course_id'] = absint( $attributes['preview_course_id'] );
					}
				}

				if ( ( ! isset( $attributes['course_id'] ) ) || ( empty( $attributes['course_id'] ) ) ) {
					if ( ( ! isset( $attributes_meta['course_id'] ) ) || ( empty( $attributes_meta['course_id'] ) ) ) {
						return $this->render_block_wrap( '<span class="learndash-block-error-message">' . sprintf(
							// translators: placeholder: Course, Course.
							_x( '%1$s ID is required when not used within a %2$s.', 'placeholder: Course, Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'course' )
						) . '</span>' );
					} else {
						$attributes['course_id'] = (int) $attributes_meta['course_id'];
					}
				} else {
					$course_post = get_post( (int) $attributes['course_id'] );
					if ( ( ! is_a( $course_post, 'WP_Post' ) ) || ( 'sfwd-courses' !== $course_post->post_type ) ) {
						return $this->render_block_wrap( '<span class="learndash-block-error-message">' . sprintf(
							// translators: placeholder: Course.
							_x( 'Invalid %1$s ID.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' )
						) . '</span>' );
					}
				}

				$shortcode_params_str = '';
				foreach ( $attributes as $key => $val ) {
					if ( ( empty( $key ) ) || ( is_null( $val ) ) ) {
						continue;
					}

					if ( 'per_page' === $key ) {
						$key = 'num';
					}

					if ( ! empty( $shortcode_params_str ) ) {
						$shortcode_params_str .= ' ';
					}
					$shortcode_params_str .= $key . '="' . esc_attr( $val ) . '"';
				}

				$shortcode_params_str = '[' . $this->shortcode_slug . ' ' . $shortcode_params_str . ']';
				$shortcode_out = do_shortcode( $shortcode_params_str );

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
					}
				}
			}
			return $attributes;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Course_Content();
