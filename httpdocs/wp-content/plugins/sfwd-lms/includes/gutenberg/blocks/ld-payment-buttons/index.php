<?php
/**
 * Handles all server side logic for the ld-payment-buttons Gutenberg Block. This block is functionally the same
 * as the learndash_payment_buttons shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Payment_Buttons' ) ) ) {
	/**
	 * Class for handling LearnDash Payment Buttons Block
	 */
	class LearnDash_Gutenberg_Payment_Buttons extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {

			$this->shortcode_slug = 'learndash_payment_buttons';
			$this->block_slug = 'ld-payment-buttons';
			$this->block_attributes = array(
				'course_id' => array(
					'type' => 'string',
				),
				'preview_show' => array(
					'type' => 'boolean',
				),
				'preview_course_id' => array(
					'type' => 'string',
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

			/** Here we don't render the button via the shortcode. We can't due to the CSS/JS needed to be loaded
			 * like for Stripe. So we just show a button and let it go at that.
			 */
			$attributes_meta = array();
			if ( isset( $attributes['meta'] ) ) {
				$attributes_meta = $attributes['meta'];
				unset( $attributes['meta'] );
			}

			if ( ( isset( $attributes['preview_show'] ) ) && ( ! empty( $attributes['preview_show'] ) ) ) {
				if ( ( isset( $attributes['preview_course_id'] ) ) && ( ! empty( $attributes['preview_course_id'] ) ) ) {
					$attributes['course_id'] = absint( $attributes['preview_course_id'] );
					unset( $attributes['preview_course_id'] );
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
			}

			$course_post = get_post( (int) $attributes['course_id'] );
			if ( ( ! is_a( $course_post, 'WP_Post' ) ) || ( 'sfwd-courses' !== $course_post->post_type ) ) {
				return $this->render_block_wrap( '<span class="learndash-block-error-message">' . sprintf(
					// translators: placeholder: Course.
					_x( 'Invalid %1$s ID.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' )
				) . '</span>' );
			}

			$course_price_type = learndash_get_setting( $course_post, 'course_price_type' );
			if ( ( ! empty( $course_price_type ) ) && ( in_array( $course_price_type, array( 'free', 'paynow', 'subscribe' ) ) ) ) {
				$button_text = LearnDash_Custom_Label::get_label( 'button_take_this_course' );
				$shortcode_out = '<a class="btn-join" href="#" id="btn-join">' . $button_text . '</a>';

				return $this->render_block_wrap( $shortcode_out );
			} else {
				return $this->render_block_wrap( '<span class="learndash-block-error-message">' . sprintf(
					// translators: placeholder: Course.
					esc_html_x( '%s Price Type must be Free, PayNow or Subscribe.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' )
				) . '</span>' );
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
			}
			return $attributes;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Payment_Buttons();
