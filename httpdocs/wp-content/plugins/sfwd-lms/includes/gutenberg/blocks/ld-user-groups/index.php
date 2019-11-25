<?php
/**
 * Handles all server side logic for the ld-user-groups Gutenberg Block. This block is functionally the same
 * as the user_groups shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_User_Groups' ) ) ) {
	/**
	 * Class for handling LearnDash User Groups Block
	 */
	class LearnDash_Gutenberg_Block_User_Groups extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {

			$this->shortcode_slug = 'user_groups';
			$this->block_slug = 'ld-user-groups';
			$this->block_attributes = array(
				'user_id' => array(
					'type' => 'string',
				),
				'per_page' => array(
					'type' => 'integer',
				),
				'order' => array(
					'type' => 'string',
				),
				'orderby' => array(
					'type' => 'string',
				),
				'preview_show' => array(
					'type' => 'boolean',
				),
				'preview_user_id' => array(
					'type' => 'string',
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
			}
			return $attributes;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_User_Groups();
