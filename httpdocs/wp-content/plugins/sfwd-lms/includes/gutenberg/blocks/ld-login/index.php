<?php
/**
 * Handles all server side logic for the ld-login Gutenberg Block. This block is functionally the same
 * as the learndash_login shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_LearnDash_Login' ) ) ) {
	/**
	 * Class for handling LearnDash Login Block
	 */
	class LearnDash_Gutenberg_Block_LearnDash_Login extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'learndash_login';
			$this->block_slug = 'ld-login';
			$this->block_attributes = array(
				'login_url' => array(
					'type' => 'string',
				),
				'login_label' => array(
					'type' => 'string',
				),
				'login_placement' => array(
					'type' => 'string',
				),
				'login_button' => array(
					'type' => 'string',
				),

				'logout_url' => array(
					'type' => 'string',
				),
				'logout_label' => array(
					'type' => 'string',
				),
				'logout_placement' => array(
					'type' => 'string',
				),
				'logout_button' => array(
					'type' => 'string',
				),
				'preview_show' => array(
					'type' => 'boolean',
				),
				'preview_action' => array(
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
			if ( is_user_logged_in() ) {

				$shortcode_params_str = '';
				foreach ( $attributes as $key => $val ) {
					if ( ( empty( $key ) ) || ( is_null( $val ) ) ) {
						continue;
					}

					if ( 'preview_show' === $key ) {
						continue;
					} else if ( 'preview_show' === $key ) {
						if ( empty( $val ) ) {
							continue;
						}
						if ( ( isset( $attributes['preview_show'] ) ) && ( true === $attributes['preview_show'] ) ) {
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
new LearnDash_Gutenberg_Block_LearnDash_Login();
