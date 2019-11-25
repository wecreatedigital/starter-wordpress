<?php
/**
 * Handles all server side logic for the ld-course-expire-status Gutenberg Block. This block is functionally the same
 * as the ld_course_expire_status shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Course_Expire_Status' ) ) ) {
	/**
	 * Class for handling LearnDash Course Expire Status Block
	 */
	class LearnDash_Gutenberg_Block_Course_Expire_Status extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'ld_course_expire_status';
			$this->block_slug = 'ld-course-expire-status';
			$this->block_attributes = array(
				'course_id' => array(
					'type' => 'string',
				),
				'user_id' => array(
					'type' => 'string',
				),
				'label_before' => array(
					'type' => 'string',
				),
				'label_after' => array(
					'type' => 'string',
				),
				'autop' => array(
					'type' => 'boolean',
				),
				'preview_show' => array(
					'type' => 'boolean',
				),
				'preview_course_id' => array(
					'type' => 'string',
				),
				'preview_user_id' => array(
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
		 * Initialize the hooks.
		 */
		/*
		public function init() {
			if ( function_exists( 'register_block_type' ) ) {
				parent::init();
				add_action( 'init', array( $this, 'register_blocks' ) );
				add_filter( 'learndash_block_markers_shortcode_atts', array( $this, 'learndash_block_markers_shortcode_atts_filter' ), 30, 4 );
			}
		}
		*/
		
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
					$attributes['preview_user_id'] = $this->get_example_user_id();
					$attributes['preview_show'] = 1;
					unset( $attributes['example_show'] );
				}

				if ( ( isset( $attributes['preview_show'] ) ) && ( ! empty( $attributes['preview_show'] ) ) ) {
					if ( ( isset( $attributes['preview_course_id'] ) ) && ( ! empty( $attributes['preview_course_id'] ) ) ) {
						$attributes['course_id'] = absint( $attributes['preview_course_id'] );
						unset( $attributes['preview_course_id'] );
					}
					if ( ( isset( $attributes['preview_user_id'] ) ) && ( ! empty( $attributes['preview_user_id'] ) ) ) {
						$attributes['user_id'] = absint( $attributes['preview_user_id'] );
						unset( $attributes['preview_user_id'] );
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

					if ( 'preview_show' === $key ) {
						continue;
					} else if ( 'preview_user_id' === $key ) {
						if ( ( ! isset( $attributes['user_id'] ) ) && ( 'preview_user_id' === $key ) && ( '' !== $val ) ) {
							if ( learndash_is_admin_user( get_current_user_id() ) ) {
								// If admin user they can preview any user_id.
							} else if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
								// If group leader user we ensure the preview user_id is within their group(s).
								if ( ! learndash_is_group_leader_of_user( get_current_user_id(), $val ) ) {
									continue;
								}
							} else {
								// If neither admin or group leader then we don't see the user_id for the shortcode.
								continue;
							}
							$key = str_replace( 'preview_', '', $key );
							$val = intval( $val );
						}
					} else if ( empty( $val ) ) {
						continue;
					}

					$shortcode_params_str .= ' ' . $key . '="' . esc_attr( $val ) . '"';
				}

				$shortcode_params_str = '[' . $this->shortcode_slug . $shortcode_params_str . ']';
				$shortcode_out = do_shortcode( $shortcode_params_str );

				return $this->render_block_wrap( $shortcode_out );
			}
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
new LearnDash_Gutenberg_Block_Course_Expire_Status();
