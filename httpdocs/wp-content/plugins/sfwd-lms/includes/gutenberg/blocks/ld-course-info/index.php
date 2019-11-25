<?php
/**
 * Handles all server side logic for the ld-course-info Gutenberg Block. This block is functionally the same
 * as the ld_course_info shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Course_Info' ) ) ) {
	/**
	 * Class for handling LearnDash Course Info Block
	 */
	class LearnDash_Gutenberg_Block_Course_Info extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'ld_course_info';
			$this->block_slug = 'ld-course-info';
			$this->block_attributes = array(
				'user_id' => array(
					'type' => 'string',
				),
				'registered_show' => array(
					'type' => 'boolean',
				),
				'registered_show_thumbnail' => array(
					'type' => 'boolean',
				),
				'registered_num' => array(
					'type' => 'string',
				),
				'registered_order' => array(
					'type' => 'string',
				),
				'registered_orderby' => array(
					'type' => 'string',
				),
				'progress_show' => array(
					'type' => 'boolean',
				),
				'progress_num' => array(
					'type' => 'string',
				),
				'progress_order' => array(
					'type' => 'string',
				),
				'progress_orderby' => array(
					'type' => 'string',
				),
				'quiz_show' => array(
					'type' => 'boolean',
				),
				'quiz_num' => array(
					'type' => 'string',
				),
				'quiz_order' => array(
					'type' => 'string',
				),
				'quiz_orderby' => array(
					'type' => 'string',
				),
				'preview_show' => array(
					'type' => 'boolean',
				),
				'preview_user_id' => array(
					'type' => 'string',
				),
				'example_show' => array(
					'type' => 'boolean',
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
					$attributes['preview_user_id'] = $this->get_example_user_id();
					$attributes['preview_show'] = 1;
					unset( $attributes['example_show'] );
				}

				$shortcode_params_str = '';
				$types = array();
				if ( isset( $attributes['registered_show'] ) ) {
					if ( true === $attributes['registered_show'] ) {
						$types[] = 'registered';
					}
					unset( $attributes['registered_show'] );
				}

				if ( isset( $attributes['progress_show'] ) ) {
					if ( true === $attributes['progress_show'] ) {
						$types[] = 'course';
					}
					unset( $attributes['progress_show'] );
				}
				if ( isset( $attributes['quiz_show'] ) ) {
					if ( true === $attributes['quiz_show'] ) {
						$types[] = 'quiz';
					}
					unset( $attributes['quiz_show'] );
				}
				if ( ! empty( $types ) ) {
					if ( ! empty( $shortcode_params_str ) ) {
						$shortcode_params_str .= ' ';
					}
					$shortcode_params_str .= 'type="' . implode( ',', $types ) . '"';

					foreach( $attributes as $key => $val ) {
						if ( ( empty( $key ) ) || ( is_null( $val ) ) ) {
							continue;
						}

						if ( substr( $key, 0, strlen( 'preview_' ) ) == 'preview_' ) {
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
						}

						if ( ! empty( $shortcode_params_str ) ) {
							$shortcode_params_str .= ' ';
						}
						$shortcode_params_str .= $key . '="' . esc_attr( $val ) . '"';
					}

					$shortcode_params_str = '[' . $this->shortcode_slug . ' ' . $shortcode_params_str . ']';
					$shortcode_out = do_shortcode( $shortcode_params_str );

					// This is mainly to protect against emty returns with the Gutenberg ServerSideRender function.
					return $this->render_block_wrap( $shortcode_out );
				} else {
					return $this->render_block_wrap( '<span class="learndash-block-error-message">' . __( "Please enable one or more 'Show' options within the Block Settings.", 'learndash' ) . '</span>' );
				}
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

				if ( ! isset( $attributes['type'] ) ) {
					$types_array = array( 'registered', 'course', 'quiz' );
					if ( isset( $attributes['registered_show'] ) ) {
						if ( false === $attributes['registered_show'] ) {
							$types_array = array_diff( $types_array, array( 'registered' ) );
						}
						unset( $attributes['registered_show'] );
					}
					if ( isset( $attributes['progress_show'] ) ) {
						if ( false === $attributes['progress_show'] ) {
							$types_array = array_diff( $types_array, array( 'course' ) );
						}
						unset( $attributes['progress_show'] );
					}
					if ( isset( $attributes['quiz_show'] ) ) {
						if ( false === $attributes['quiz_show'] ) {
							$types_array = array_diff( $types_array, array( 'quiz' ) );
						}
						unset( $attributes['quiz_show'] );
					}
					if ( ! empty( $types_array ) ) {
						$attributes['type'] = implode( ',', $types_array );
					}
				}
			}
			return $attributes;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Course_Info();
