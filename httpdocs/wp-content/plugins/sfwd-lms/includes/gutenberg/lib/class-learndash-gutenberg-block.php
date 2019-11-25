<?php
/**
 * Base class for all LearnDash Gutenberg Blocks.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ! class_exists( 'LearnDash_Gutenberg_Block' ) ) {
	/**
	 * Abstract Parent class to hold common functions used by specific LearnDash Blocks.
	 */
	class LearnDash_Gutenberg_Block {

		protected $block_base = 'learndash';
		protected $shortcode_slug;
		protected $block_slug;
		protected $block_attributes;
		protected $self_closing;

		public function __construct() {
		}

		/**
		 * Initialize the hooks.
		 */
		public function init() {
			if ( function_exists( 'register_block_type' ) ) {
				add_action( 'init', array( $this, 'register_blocks' ) );
				add_filter( 'learndash_block_markers_shortcode_atts', array( $this, 'learndash_block_markers_shortcode_atts_filter' ), 30, 4 );

				/**
				 * Filter on the 'the_content' hook from WP. This needs to be at a priority
				 * before the 'run_shortcode' function which runs at a priority of 8.
				 */
				add_filter( 'the_content', array( $this, 'the_content_filter' ), 5 );
				add_filter( 'learndash_convert_block_markers_to_shortcode_content', array( $this, 'convert_block_markers_to_shortcode_content_filter' ), 30, 4 );
			}
		}

		/**
		 * Register Block for Gutenberg
		 */
		public function register_blocks() {
			register_block_type(
				$this->block_base . '/' . $this->block_slug,
				array(
					'render_callback' => array( $this, 'render_block' ),
					'attributes'      => $this->block_attributes,
				)
			);
		}

		/**
		 * Hook into 'the_content' WP filter and parse out our block. We want to convert eh Gutenber Block notation to a normal LD shortcode.
		 * Called at high priority BEFORE do_shortcode() and do_blocks().
		 *
		 * @since 2.5.9
		 *
		 * @param string $content The post content containg all the inline HTML and blocks.
		 * @return string $content.
		 */
		public function the_content_filter( $content = '' ) {
			if ( ( is_admin() ) && ( ( isset( $_REQUEST['post'] ) ) && ( ! empty( $_REQUEST['post'] ) ) ) && ( ( isset( $_REQUEST['action'] ) ) && ( 'edit' === $_REQUEST['action'] ) ) ) {
				return $content;
			}

			if ( ! empty( $content ) ) {
				$content = $this->convert_block_markers_to_shortcode( $content, $this->block_slug, $this->shortcode_slug, $this->self_closing );
			}
			return $content;
		}

		/**
		 * Render Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content. This is called from within the admin edit post type page via an
		 * AJAX-type call to the server.
		 *
		 * Each sub-subclassed instance should provide its own version of this function.
		 *
		 * @since 2.5.9
		 *
		 * @param array $attributes Shortcode attrbutes.
		 * @return void The output is echoed.
		 */
		public function render_block( $attributes = array() ) {
			return;
			wp_die();
		}

		/**
		 * Add wrapper content around content to be returned to server.
		 *
		 * @since 2.5.9
		 *
		 * @param string $content Content text to be wrapper.
		 * @param boolean $with_inner Flag to control inclusion of inner block div element.
		 *
		 * @return string wrapped content.
		 */
		public function render_block_wrap( $content = '', $with_inner = true ) {
			$return_content  = '';
			$return_content .= '<!-- ' . $this->block_slug . ' gutenberg block begin -->';

			if ( true === $with_inner ) {
				$return_content .= '<div className="learndash-block-inner">';
			}

			$return_content .= $content;

			if ( true === $with_inner ) {
				$return_content .= '</div>';
			}

			$return_content .= '<!-- ' . $this->block_slug . ' gutenberg block end -->';

			return $return_content;
		}


		/**
		 * Utility function to parse the WP Block content looking for specific token patterns.
		 *
		 * @since 2.6
		 *
		 * @param string  $content Full page/post content to be searched.
		 * @param string  $block_slug This is the block token pattern to search for. Ex: ld-user-meta, ld-visitor, ld-profile.
		 * @param string  $shortcode_slug This is the actual shortcode token to be used.
		 * @param boolean $self_closing true if not an innerblock.
		 * @return string $content
		 */
		public function convert_block_markers_to_shortcode( $content = '', $block_slug = '', $shortcode_slug = '', $self_closing = false ) {
			if ( ( ! empty( $content ) ) && ( ! empty( $block_slug ) ) && ( ! empty( $shortcode_slug ) ) ) {
				$pattern_atts_array = array();
				if ( true === $self_closing ) {
					preg_match_all( '#<!--\s+wp:' . $this->block_base . '/' . $block_slug . '(.*?) /-->#is', $content, $ar );
					if ( ( isset( $ar[0] ) ) && ( is_array( $ar[0] ) ) && ( ! empty( $ar[0] ) ) ) {
						if ( ( isset( $ar[1] ) ) && ( is_array( $ar[1] ) ) && ( ! empty( $ar[1] ) ) ) {
							foreach ( $ar[1] as $pattern_key => $pattern_atts_json ) {
								$replacement_text = '[' . $shortcode_slug;

								if ( ! empty( $pattern_atts_json ) ) {
									$pattern_atts_array = (array) json_decode( $pattern_atts_json );
									$pattern_atts_array = apply_filters( 'learndash_block_markers_shortcode_atts', $pattern_atts_array, $shortcode_slug, $block_slug, $content );
									if ( ( is_array( $pattern_atts_array ) ) && ( ! empty( $pattern_atts_array ) ) ) {
										$shortcode_atts = '';
										foreach ( $pattern_atts_array as $attr_key => $attr_value ) {
											if ( 'meta' === $attr_key ) {
												continue;
											}

											if ( '' !== $attr_value ) {
												if ( ! empty( $shortcode_atts ) ) {
													$shortcode_atts .= ' ';
												}

												$shortcode_atts .= $attr_key . '="' . $attr_value . '"';
											}
										}

										if ( ! empty( $shortcode_atts ) ) {
											$replacement_text .= ' ' . $shortcode_atts;
										}
									}
								}

								// If we have built a replacement text then replace it in the main $content.
								if ( ! empty( $replacement_text ) ) {
									$replacement_text .= ']';
									$content           = str_replace( $ar[0][ $pattern_key ], $replacement_text, $content );
									$content           = apply_filters( 'learndash_convert_block_markers_to_shortcode_content', $content, $pattern_atts_array, $shortcode_slug, $block_slug );
								}
							}
						}
					}
				} else {
					/**
					 * A non-self closing WP block will look like the following for the ld-student. The
					 * patter will have an outer wrapper of the block whihc will be converted into a shortcode
					 * wrapper like [ld_student]<content here>[/ld_student]
					 *
					 * <!-- wp:learndash/ld-student {"course_id":"109"} -->
					 * <!-- wp:paragraph -->
					 * <p>This is the inner content. </p>
					 * <!-- /wp:paragraph -->
					 * <!-- /wp:learndash/ld-student -->
					 */
					preg_match_all( '#<!--\s+wp:' . $this->block_base . '/' . $block_slug . '(.*?)-->(.*?)<!--\s+/wp:' . $this->block_base . '/' . $block_slug . '\s+-->#is', $content, $ar );
					if ( ( isset( $ar[0] ) ) && ( is_array( $ar[0] ) ) && ( ! empty( $ar[0] ) ) ) {
						if ( ( isset( $ar[1] ) ) && ( is_array( $ar[1] ) ) && ( ! empty( $ar[1] ) ) ) {
							foreach ( $ar[1] as $pattern_key => $pattern_atts_json ) {
								$pattern_atts_json = trim( $pattern_atts_json );

								// Ensure the inner content is not empty.
								if ( ( isset( $ar[2][ $pattern_key ] ) ) && ( ! empty( $ar[2][ $pattern_key ] ) ) ) {
									$replacement_text = '[' . $shortcode_slug;

									if ( ! empty( $pattern_atts_json ) ) {
										$pattern_atts_array = (array) json_decode( $pattern_atts_json );
										$pattern_atts_array = apply_filters( 'learndash_block_markers_shortcode_atts', $pattern_atts_array, $shortcode_slug, $block_slug, $content );
										$shortcode_atts = '';
										if ( ( is_array( $pattern_atts_array ) ) && ( ! empty( $pattern_atts_array ) ) ) {
											foreach ( $pattern_atts_array as $attr_key => $attr_value ) {
												if ( 'meta' === $attr_key ) {
													continue;
												}

												if ( '' !== $attr_value ) {
													if ( ! empty( $shortcode_atts ) ) {
														$shortcode_atts .= ' ';
													}
													$shortcode_atts .= $attr_key . '="' . $attr_value . '"';
												}
											}
										}
										if ( ! empty( $shortcode_atts ) ) {
											$replacement_text .= ' ' . $shortcode_atts;
										}
									}
									$replacement_text .= ']' . $ar[2][ $pattern_key ] . '[/' . $shortcode_slug . ']';

									// If we have built a replacement text then replace it in the main $content.
									if ( ! empty( $replacement_text ) ) {
										$content = str_replace( $ar[0][ $pattern_key ], $replacement_text, $content );
										$content = apply_filters( 'learndash_convert_block_markers_to_shortcode_content', $content, $pattern_atts_array, $shortcode_slug, $block_slug );
									}
								}
							}
						}
					}
				}
			}

			return $content;
		}

		/**
		 * Called from the LD function learndash_convert_block_markers_shortcode() when parsing the block content.
		 * Each sub-subclassed instance should provide its own version of this function.
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
			return $attributes;
		}

		/**
		 * Called from the LD function convert_block_markers_to_shortcode() when parsing the block content.
		 * This function allows hooking into the converted content.
		 *
		 * @since 2.6.4
		 *
		 * @param string $content This is the orignal full content being parsed.
		 * @param array  $attributes The array of attributes parse from the block content.
		 * @param string $shortcode_slug This will match the related LD shortcode ld_profile, ld_course_list, etc.
		 * @param string $block_slug This is the block token being processed. Normally same as the shortcode but underscore replaced with dash.
		 *
		 * @return string $content.
		 */
		public function convert_block_markers_to_shortcode_content_filter( $content = '', $attributes = array(), $shortcode_slug = '', $block_slug = '' ) {
			return $content;
		}

		/**
		 * Common function used by the ld_course_list, ld_lesson_list, ld_topic_list,
		 * and ld_quiz_list called from the render_block short/block processing function.
		 * Converts the array of atrributes to a normalized shortcode parameter string.
		 *
		 * @since 2.6.4
		 * @param array $attributes Array of block attributes.
		 * @return string.
		 */
		protected function prepare_course_list_atts_to_param( $attributes = array() ) {
			$shortcode_params_str = '';

			foreach ( $attributes as $key => $val ) {
				if ( ( empty( $key ) ) || ( is_null( $val ) ) ) {
					continue;
				}

				if ( 'preview_show' === $key ) {
					continue;
				} elseif ( 'preview_user_id' === $key ) {
					if ( ( ! isset( $attributes['user_id'] ) ) && ( 'preview_user_id' === $key ) && ( '' !== $val ) ) {
						if ( learndash_is_admin_user( get_current_user_id() ) ) {
							// If admin user they can preview any user_id.
						} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
							// If group leader user we ensure the preview user_id is within their group(s).
							if ( ! learndash_is_group_leader_of_user( get_current_user_id(), $val ) ) {
								continue;
							}
						} else {
							// If neither admin or group leader then we don't see the user_id for the shortcode.
							continue;
						}
						$key = str_replace( 'preview_', '', $key );
						$val = absint( $val );
					}
				} elseif ( 'per_page' === $key ) {
					if ( '' === $val ) {
						continue;
					}
					$key = 'num';
					$val = (int) $val;

				} elseif ( ( 'show_content' === $key ) || ( 'show_thumbnail' === $key ) || ( 'course_grid' === $key ) || ( 'progress_bar' === $key ) ) {
					if ( ( 1 === $val ) || ( true === $val ) || ( 'true' === $val ) ) {
						$val = 'true';
					} else {
						$val = 'false';
					}
				} elseif ( 'col' === $key ) {
					if ( defined( 'LEARNDASH_COURSE_GRID_FILE' ) ) {
						$val = absint( $val );
						if ( $val < 1 ) {
							$val = 3;
						}
					} else {
						continue;
					}
				} elseif ( empty( $val ) ) {
					continue;
				}

				if ( ! empty( $shortcode_params_str ) ) {
					$shortcode_params_str .= ' ';
				}
				$shortcode_params_str .= $key . '="' . esc_attr( $val ) . '"';
			}

			return $shortcode_params_str;
		}

		/**
		 * Get example user ID. This is used as part of WP 5.3 Gutenberg Block Example / Preview.
		 *
		 * @since 3.1
		 * @return integer $user_id User ID.
		 */
		function get_example_user_id() {
			$user_id = 0;
			$user_id = apply_filters( 'learndash_gutenberg_block_example_id', $user_id, 'user_id', 'user', $this->block_slug );
			$user_id = absint( $user_id );
			if ( ! empty( $user_id ) ) {
				$user = get_user_by( 'ID', $user_id );
				if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
					$user_id = 0;
				}
			}

			if ( empty( $user_id ) ) {
				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
				}
			}

			return $user_id;
		}

		/**
		 * Get example post ID. This is used as part of WP 5.3 Gutenberg Block Example / Preview.
		 *
		 * @since 3.1
		 * @param string $post_type Post Type Slug to retreive.
		 * @return integer $post_id Post ID.
		 */
		function get_example_post_id( $post_type = '' ) {
			$post_id = 0;
			$post_id = apply_filters( 'learndash_gutenberg_block_example_id', $post_id, 'post_id', $post_type, $this->block_slug );
			$post_id = absint( $post_id );
			if ( ! empty( $post_id ) ) {
				$_post = get_post( $post_id );
				if ( ( ! $_post ) || ( ! is_a( $_post, 'WP_Post' ) ) ) {
					$course_id = 0;
				}
			}

			if ( empty( $post_id ) ) {
				$post_id = learndash_get_single_post( $post_type );
			}

			return $post_id;
		}

		// End of functions.
	}
}
