<?php
/**
 * LearnDash Binary Selector Class.
 *
 * @package LearnDash
 * @subpackage Admin Settings
 */

if ( ! class_exists( 'Learndash_Binary_Selector' ) ) {
	/**
	 * Class for LearnDash Binary Selector.
	 */
	class Learndash_Binary_Selector {

		/**
		 * Selector args used.
		 *
		 * @var array $args Array of arguments used by the selector
		 */
		protected $args = array();

		/**
		 * Selector default settings.
		 *
		 * @var array $defaults Array of default settings for the selector
		 */
		private $defaults = array();

		/**
		 * Stores the class as a var. This is so when we send the class back over AJAX we know how to recreate it.
		 *
		 * @var Class $selector_class Class reference to selector.
		 */
		protected $selector_class;

		/**
		 * Nonce for the AJAX calls.
		 *
		 * @var string $selector_nonce Nonce value.
		 */
		protected $selector_nonce;

		/**
		 * Element data passed to DOM.
		 *
		 * @var array $element_data Array of data passed to DOM
		 */
		protected $element_data = array();

		/**
		 * Container for the query result items.
		 *
		 * @var array $element_items Array of query result items to process.
		 */
		protected $element_items = array();

		/**
		 * Container for the queries.
		 *
		 * @var array $element_queries Array of Queries.
		 */
		protected $element_queries = array();

		/**
		 * Public constructor for class.
		 *
		 * @param array $args Array of selector args used to initialize instance.
		 */
		public function __construct( $args = array() ) {

			$this->defaults = array(
				'html_title'         => '',
				'html_id'            => '',
				'html_name'          => '',
				'html_class'         => '',
				'selected_ids'       => array(),
				'included_ids'       => array(),
				'max_height'         => '250px',
				'min_height'         => '250px',
				'lazy_load'          => false,
				'search_label_left'  => esc_html__( 'Search:', 'learndash' ),
				'search_label_right' => esc_html__( 'Search:', 'learndash' ),
				'is_search'          => false,
				'is_pager'           => false,
			);

			$this->args = wp_parse_args( $args, $this->defaults );

			$this->args['html_slug'] = sanitize_title_with_dashes( $this->args['html_id'] );

			// We want to conver this to an array.
			if ( ( ! empty( $this->args['selected_ids'] ) ) && ( is_string( $this->args['selected_ids'] ) ) ) {
				$this->args['selected_ids'] = explode( ',', $this->args['selected_ids'] );
			} elseif ( ( empty( $this->args['selected_ids'] ) ) && ( is_string( $this->args['selected_ids'] ) ) ) {
				$this->args['selected_ids'] = array();
			}

			// If for some reason the 'include' element is passed in we convert it to our 'included_ids'.
			if ( ( isset( $this->args['include'] ) ) && ( ! empty( $this->args['include'] ) ) && ( empty( $this->args['included_ids'] ) ) ) {
				$this->args['included_ids'] = $this->args['include'];
				unset( $this->args['include'] );
			}
			if ( ( ! empty( $this->args['included_ids'] ) ) && ( is_string( $this->args['included_ids'] ) ) ) {
				$this->args['included_ids'] = explode( ',', $this->args['included_ids'] );
			}

			// Let the outside world override some settings.
			$this->args = apply_filters( 'learndash_binary_selector_args', $this->args, $this->selector_class );

			$this->element_items['left']  = array();
			$this->element_items['right'] = array();

			$this->element_queries['left']  = array();
			$this->element_queries['right'] = array();
		}

		/**
		 * Show function for selector.
		 */
		public function show() {
			$this->query_selection_section_items( 'left' );
			$this->query_selection_section_items( 'right' );

			// If we don't have items for the left (All items) then something is wrong. Abort.
			if ( ( empty( $this->element_items['left'] ) ) && ( empty( $this->element_items['right'] ) ) ) {
				return;
			}

			// Before we add our data element we remove all the unneeded keys. Just to keep it small.
			$element_data = $this->element_data;
			foreach ( $this->defaults as $key => $val ) {
				if ( isset( $element_data['query_vars'][ $key ] ) ) {
					unset( $element_data['query_vars'][ $key ] );
				}
			}

			// Aware of the PHP post number vars limit we convert the inlcude and exclude arrays to json so they are sent back as strings.
			if ( ( isset( $element_data['query_vars']['include'] ) ) && ( ! empty( $element_data['query_vars']['include'] ) ) ) {
				$element_data['query_vars']['include'] = wp_json_encode( $element_data['query_vars']['include'], JSON_FORCE_OBJECT );
			}

			if ( ( isset( $element_data['query_vars']['exclude'] ) ) && ( ! empty( $element_data['query_vars']['exclude'] ) ) ) {
				$element_data['query_vars']['exclude'] = wp_json_encode( $element_data['query_vars']['exclude'], JSON_FORCE_OBJECT );
			}

			?>
			<div id="<?php echo esc_attr( $this->args['html_id'] ); ?>" class="<?php echo esc_attr( $this->args['html_class'] ); ?> learndash-binary-selector" data="<?php echo htmlspecialchars( wp_json_encode( $element_data ) ); ?>">
				<input type="hidden" class="learndash-binary-selector-form-element" name="<?php echo esc_attr( $this->args['html_name'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $this->args['selected_ids'], JSON_FORCE_OBJECT ) ); ?>"/>
				<input type="hidden" name="<?php echo esc_attr( $this->args['html_id'] ); ?>-nonce" value="<?php echo wp_create_nonce( $this->args['html_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( $this->args['html_id'] ); ?>-changed" class="learndash-binary-selector-form-changed" value="" />
				
				<?php $this->show_selections_title(); ?>
				<table class="learndash-binary-selector-table">
				<tr>
					<?php
						$this->show_selections_section( 'left' );
						$this->show_selections_section_controls();
						$this->show_selections_section( 'right' );
					?>
				</tr>
			</table>
				<?php
				if ( ( isset( $this->args['max_height'] ) ) && ( ! empty( $this->args['max_height'] ) ) ) {
					?>
					<style>
					.learndash-binary-selector .learndash-binary-selector-section .learndash-binary-selector-items {
						max-height: <?php echo esc_attr( $this->args['max_height'] ); ?>;
						overflow-y:scroll;
					}
					</style>
					<?php
				}
				?>
				<?php
				if ( ( isset( $this->args['min_height'] ) ) && ( ! empty( $this->args['min_height'] ) ) ) {
					?>
					<style>
					.learndash-binary-selector .learndash-binary-selector-section .learndash-binary-selector-items {
						min-height: <?php echo esc_attr( $this->args['min_height'] ); ?>;
					}
					</style>
					<?php
				}
				?>
			</div>
			<?php
		}

		/**
		 * Show Selections Title.
		 *
		 * This is the title shown above the binary selector widget.
		 */
		protected function show_selections_title() {
			if ( ! empty( $this->args['html_title'] ) ) {
				echo $this->args['html_title'];
			}
		}

		/**
		 * Show Selector Controls.
		 *
		 * Shows the Add/Remove buttons which lives betweeen the left/right side selectors.
		 */
		protected function show_selections_section_controls() {
			?>
			<td class="learndash-binary-selector-section learndash-binary-selector-section-middle">
				<a href="#" class="learndash-binary-selector-button-add">
				<?php if ( is_rtl() ) { ?>
					<img src="<?php echo LEARNDASH_LMS_PLUGIN_URL . 'assets/images/arrow_left.png'; ?>" />
				<?php } else { ?>
					<img src="<?php echo LEARNDASH_LMS_PLUGIN_URL . 'assets/images/arrow_right.png'; ?>" />
				<?php } ?>
				</a><br>
				
				<a href="#" class="learndash-binary-selector-button-remove">
				<?php if ( is_rtl() ) { ?>
					<img src="<?php echo LEARNDASH_LMS_PLUGIN_URL . 'assets/images/arrow_right.png'; ?>" />
				<?php } else { ?>
					<img src="<?php echo LEARNDASH_LMS_PLUGIN_URL . 'assets/images/arrow_left.png'; ?>" />
				<?php } ?>				
				</a>
			</td>
			<?php
		}

		/**
		 * Show Selector section.
		 *
		 * Shows the left/right selector sections.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function show_selections_section( $position = '' ) {
			$position = esc_attr( $position );
			if ( ( 'left' === $position ) || ( 'right' === $position ) ) {
				?>
				<td class="learndash-binary-selector-section learndash-binary-selector-section-<?php echo esc_attr( $position ); ?>">
					<input placeholder="<?php echo esc_attr( $this->get_search_label( $position ) ); ?>" type="text" id="learndash-binary-selector-search-<?php echo esc_attr( $this->args['html_slug'] ); ?>-<?php echo esc_attr( $position ); ?>" class="learndash-binary-selector-search learndash-binary-selector-search-<?php echo esc_attr( $position ); ?>" />

					<select multiple="multiple" class="learndash-binary-selector-items learndash-binary-selector-items-<?php echo esc_attr( $position ); ?>">
						<?php $this->show_selections_section_items( $position ); ?>
					</select>

					<ul class="learndash-binary-selector-pager learndash-binary-selector-pager-<?php echo esc_attr( $position ); ?>">
						<?php $this->show_selections_section_pager( $position ); ?>
					</ul>
				</td>
				<?php
			}
		}

		/**
		 * Show selector section items.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function show_selections_section_items( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				echo $this->build_options_html( $position );
			}
		}

		/**
		 * Show selector section legend.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function show_selections_section_legend( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				if ( 'left' === $position ) {
					?>
					<span class="items-loaded-count" style="display:none"> /</span> <span class="items-total-count"></span>
					<?php
				} elseif ( 'right' === $position ) {
					?>
					<span class="items-loaded-count" style="display:none"> /</span> <span class="items-total-count"></span>
					<?php
				}
			}
		}

		/**
		 * Show selector section pager.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function show_selections_section_pager( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				?>
				<li class="learndash-binary-selector-pager-prev"><a class="learndash-binary-selector-pager-prev" style="display:none;" href="#"><?php esc_html_e( '&lsaquo; prev', 'learndash' ); ?></a></li>
				<li class="learndash-binary-selector-pager-info" style="display:none;"><?php echo sprintf(
					// translators: placeholder: Page X of Y.
					esc_html_x( 'Page %s of %s', 'placeholder: Page X of Y', 'learndash' ),
					'<span class="current_page"></span>', '<span class="total_pages"></span>' 
				); ?></li>
				<li class="learndash-binary-selector-pager-next"><a class="learndash-binary-selector-pager-next" style="display:none;" href="#"><?php esc_html_e( 'next  &rsaquo;', 'learndash' ); ?></a></li>
				<?php
			}
		}

		/**
		 * Get selector section search label.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function get_search_label( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				if ( isset( $this->args[ 'search_label_' . $position ] ) ) {
					return $this->args[ 'search_label_' . $position ];
				} elseif ( isset( $this->args['search_label'] ) ) {
					return $this->args['search_label'];
				} else {
					return esc_html__( 'Search', 'learndash' );
				}
			}
		}

		/**
		 * Get selector section pager data.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function get_pager_data( $position = '' ) {
		}

		/**
		 * Get selector section items.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function query_selection_section_items( $position = '' ) {
		}

		/**
		 * Process selector section query.
		 *
		 * @param array  $query_args Array of query args.
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function process_query( $query_args = array(), $position = '' ) {
		}

		/**
		 * Load selector section page AJAX.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function load_pager_ajax( $position = '' ) {
			$reply_data['html_options'] = '';

			if ( $this->is_valid_position( $position ) ) {
				$this->query_selection_section_items( $position );
				$reply_data                 = $this->element_data[ $position ];
				$reply_data['html_options'] = $this->build_options_html( $position );
			}
			return $reply_data;
		}

		/**
		 * Load selector section search AJAX.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function load_search_ajax( $position = '' ) {
			$reply_data['html_options'] = '';

			if ( $this->is_valid_position( $position ) ) {
				$this->args['is_search'] = true;

				$this->query_selection_section_items( $position );
				if ( isset( $this->element_data[ $position ] ) ) {
					$reply_data                 = $this->element_data[ $position ];
					$reply_data['html_options'] = $this->build_options_html( $position );
				}
			}
			return $reply_data;
		}

		/**
		 * Get selector section nonce.
		 */
		protected function get_nonce_data() {
			return wp_create_nonce( $this->selector_class . '-' . $this->args['html_id'] );
		}

		/**
		 * Validate selector section nonce.
		 *
		 * @param string $nonce Nonce value to validate.
		 */
		public function validate_nonce_data( $nonce = '' ) {
			if ( ! empty( $nonce ) ) {
				return wp_verify_nonce( $nonce, $this->selector_class . '-' . $this->args['html_id'] );
			}
		}

		/**
		 * Utility function to check and validate the $postition
		 * variable. It should be only 'left' or 'right'.
		 *
		 * @since 2.6.0
		 *
		 * @param string $position Should have value 'left' or 'right'.
		 * @return true if valid.
		 */
		public function is_valid_position( $position = '' ) {
			if ( ! empty( $position ) ) {
				$position = esc_attr( $position );
				if ( ( 'left' === $position ) || ( 'right' === $position ) ) {
					return true;
				}
			}
			return false;
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Users' ) ) && ( class_exists( 'Learndash_Binary_Selector' ) ) ) {
	/**
	 * Class for LearnDash Binary Selector or Users.
	 */
	class Learndash_Binary_Selector_Users extends Learndash_Binary_Selector {
		/**
		 * Public constructor for class.
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			// Set up the defaut query args for the Users.
			$defaults = array(
				'paged'         => 1,
				'number'        => get_option( 'posts_per_page' ),
				//'search_number' => get_option( 'posts_per_page' ),
				'fields'        => array( 'ID', 'display_name', 'user_login' ),
				'orderby'       => 'display_name',
				'order'         => 'ASC',
				'search'        => '',
			);

			if ( ( ! isset( $args['number'] ) ) && ( isset( $args['per_page'] ) ) && ( ! empty( $args['per_page'] ) ) ) {
				$args['number'] = $args['per_page'];
			}

			$args = wp_parse_args( $args, $defaults );

			parent::__construct( $args );

			if ( ( isset( $this->args['included_ids'] ) ) && ( ! empty( $this->args['included_ids'] ) ) ) {
				$this->query_args['include'] = $this->args['included_ids'];
			}

			if ( ( isset( $this->args['excluded_ids'] ) ) && ( ! empty( $this->args['excluded_ids'] ) ) ) {
				$this->query_args['exclude'] = $this->args['excluded_ids'];
			}
		}

		/**
		 * Get selector section pager data.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function get_pager_data( $position = '' ) {
			$pager = array();

			if ( $this->is_valid_position( $position ) ) {
				if ( isset( $this->element_queries[ $position ] ) ) {
					if ( isset( $this->element_queries[ $position ]->query_vars['paged'] ) ) {
						$pager['current_page'] = intval( $this->element_queries[ $position ]->query_vars['paged'] );
					} else {
						$pager['current_page'] = 0;
					}

					if ( isset( $this->element_queries[ $position ]->query_vars['number'] ) ) {
						$pager['per_page'] = intval( $this->element_queries[ $position ]->query_vars['number'] );
					} else {
						$pager['per_page'] = 0;
					}

					if ( isset( $this->element_queries[ $position ]->total_users ) ) {
						$pager['total_items'] = intval( $this->element_queries[ $position ]->total_users );
					} else {
						$pager['total_items'] = 0;
					}

					if ( ( ! empty( $pager['per_page'] ) ) && ( ! empty( $pager['total_items'] ) ) ) {
						$pager['total_pages'] = ceil( intval( $this->element_queries[ $position ]->total_users ) / intval( $this->element_queries[ $position ]->query_vars['number'] ) );
					} else {
						$pager['total_pages'] = 0;
					}
				}
			}

			return $pager;
		}

		/**
		 * Build selector section options HTML.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function build_options_html( $position = '' ) {
			$options_html = '';

			if ( $this->is_valid_position( $position ) ) {
				if ( ! empty( $this->element_items[ $position ] ) ) {
					foreach ( $this->element_items[ $position ] as $user ) {
						$user_name = apply_filters( 'learndash_binary_selector_item', $user->display_name . ' (' . $user->user_login . ')', $user, $position, $this->selector_class );
						if ( ! empty( $user_name ) ) {
							$user_name = strip_tags( $user_name );
						} else {
							$user_name = $user->display_name . ' (' . $user->user_login . ')';
						}

						$disabled_class = '';
						$disabled_state = '';

						if ( ( is_array( $this->args['selected_ids'] ) ) && ( ! empty( $this->args['selected_ids'] ) ) ) {
							if ( in_array( $user->ID, $this->args['selected_ids'] ) ) {
								$disabled_class = 'learndash-binary-selector-item-disabled';
								if ( 'left' === $position ) {
									$disabled_state = ' disabled="disabled" ';
								}
							}
						}
						$options_html .= '<option class="learndash-binary-selector-item ' . $disabled_class . '" ' . $disabled_state . ' value="' . $user->ID . '" data-value="' . $user->ID . '">' . $user_name . '</option>';
					}
				}
			}

			return $options_html;
		}

		/** Query selector section items.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function query_selection_section_items( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				if ( 'left' === $position ) {
					if ( ! empty( $this->args['included_ids'] ) ) {
						$this->args['include'] = $this->args['included_ids'];
					}

					if ( ( isset( $this->args['excluded_ids'] ) ) && ( ! empty( $this->args['excluded_ids'] ) ) ) {
						$this->args['exclude'] = $this->args['excluded_ids'];
					}

					if ( true === $this->args['is_search'] ) {
						if ( ( isset( $this->args['selected_ids'] ) ) && ( ! empty( $this->args['selected_ids'] ) ) ) {
							if ( ! isset( $this->args['exclude'] ) ) {
								$this->args['exclude'] = array();
							}
							$this->args['exclude'] = array_merge( $this->args['exclude'], $this->args['selected_ids'] );
						}
					}
				} elseif ( 'right' === $position ) {
					if ( ! empty( $this->args['selected_ids'] ) ) {
						$this->args['include'] = $this->args['selected_ids'];
					} else {
						$this->args['include'] = array( 0 );
					}

					if ( ( isset( $this->args['excluded_ids'] ) ) && ( ! empty( $this->args['excluded_ids'] ) ) ) {
						$this->args['exclude'] = $this->args['excluded_ids'];
					}
				}
				$this->process_query( $this->args, $position );

				if ( isset( $this->args['include'] ) ) {
					unset( $this->args['include'] );
				}
			}
		}

		/**
		 * Process selector section query.
		 *
		 * @param array  $query_args Array of query args.
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function process_query( $query_args = array(), $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				$query = new WP_User_Query( $query_args );
				$items = $query->get_results();
				if ( ! empty( $items ) ) {

					$this->element_queries[ $position ] = $query;
					$this->element_items[ $position ]   = $items;

					// We only need to store one reference to the query as the left and right will share this. Plus
					// the query on the right side may/will have the 'include' elements and we store this as 'selected_ids' key.
					if ( 'left' === $position ) {
						$this->element_data['query_vars'] = $query_args;
					}

					$this->element_data['selector_class'] = $this->selector_class;
					$this->element_data['selector_nonce'] = $this->get_nonce_data();

					$this->element_data[ $position ]['position'] = $position;
					$this->element_data[ $position ]['pager']    = $this->get_pager_data( $position );
				}
			}
		}

		/**
		 * Load selector section search AJAX.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function load_search_ajax( $position = '' ) {

			$reply_data = array();

			if ( $this->is_valid_position( $position ) ) {
				if ( ( isset( $this->args['search'] ) ) && ( ! empty( $this->args['search'] ) ) ) {

					// For user searching Users we must include the beginning and ending '*' for wildcard matches.
					$this->args['search'] = '*' . $this->args['search'] . '*';

					// Now call the parent function to perform the actual search.
					$reply_data = parent::load_search_ajax( $position );
				}
			}

			return $reply_data;
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Course_Users' ) ) && ( class_exists( 'Learndash_Binary_Selector_Users' ) ) ) {
	/**
	 * Class for LearnDash Binary Selector Course Users.
	 */
	class Learndash_Binary_Selector_Course_Users extends Learndash_Binary_Selector_Users {

		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'course_id'          => 0,
				'html_title'         => '<h3>' .
				// translators: placeholder: Course.
				esc_html_x( '%s Users', 'Course Users Label', 'learndash' ) . '</h3>',
				'html_title'         => '<h3>' .
				// translators: placeholder: Course.
				sprintf( esc_html_x( '%s Users', 'Course Users label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</h3>',
				'html_id'            => 'learndash_course_users',
				'html_class'         => 'learndash_course_users',
				'html_name'          => 'learndash_course_users',
				'search_label_left'  => sprintf(
					// translators: placeholder: Course.
					esc_html_x( 'Search All %s Users', 'Search All Course Users', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'course' )
				),
				'search_label_right' => sprintf(
					// translators: placeholder: Course.
					esc_html_x( 'Search Assigned %s Users', 'Search Assigned Course Users', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'course' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['course_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['course_id'] . ']';

			parent::__construct( $args );
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Group_Users' ) ) && ( class_exists( 'Learndash_Binary_Selector_Users' ) ) ) {
	/**
	 * Class for LearnDash Binary Selector Group Users.
	 */
	class Learndash_Binary_Selector_Group_Users extends Learndash_Binary_Selector_Users {
		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'group_id'           => 0,
				'html_title'         => '<h3>' . esc_html__( 'Group Users', 'learndash' ) . '</h3>',
				'html_id'            => 'learndash_group_users',
				'html_class'         => 'learndash_group_users',
				'html_name'          => 'learndash_group_users',
				'search_label_left'  => esc_html__( 'Search All Group Users', 'learndash' ),
				'search_label_right' => esc_html__( 'Search Assigned Group Users', 'learndash' ),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['group_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['group_id'] . ']';

			parent::__construct( $args );
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Group_Leaders' ) ) && ( class_exists( 'Learndash_Binary_Selector_Users' ) ) ) {
	/**
	 * Class for LearnDash Binary Selector Group Leaders.
	 */
	class Learndash_Binary_Selector_Group_Leaders extends Learndash_Binary_Selector_Users {
		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'group_id'           => 0,
				'html_title'         => '<h3>' . esc_html__( 'Group Leaders', 'learndash' ) . '</h3>',
				'html_id'            => 'learndash_group_leaders',
				'html_class'         => 'learndash_group_leaders',
				'html_name'          => 'learndash_group_leaders',
				'search_label_left'  => esc_html__( 'Search All Group Leaders', 'learndash' ),
				'search_label_right' => esc_html__( 'Search Assigned Group Leaders', 'learndash' ),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['group_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['group_id'] . ']';

			if ( ( ! isset( $args['included_ids'] ) ) || ( empty( $args['included_ids'] ) ) ) {
				$args['role__in'] = array( 'group_leader', 'administrator' );
			}

			parent::__construct( $args );
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Posts' ) ) && ( class_exists( 'Learndash_Binary_Selector' ) ) ) {
	/**
	 * Class for LearnDash Binary Selector Posts.
	 */
	class Learndash_Binary_Selector_Posts extends Learndash_Binary_Selector {
		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			// Set up the defaut query args for the Users.
			$defaults = array(
				'paged'                 => 1,
				'post_status'           => array( 'publish' ),
				'posts_per_page'        => get_option( 'posts_per_page' ),
				//'search_posts_per_page' => get_option( 'posts_per_page' ),
				'orderby'               => 'title',
				'order'                 => 'ASC',
				'ignore_sticky_posts'   => true,
				'search'                => '',
			);

			if ( ( ! isset( $args['posts_per_page'] ) ) && ( isset( $args['number'] ) ) && ( ! empty( $args['number'] ) ) ) {
				$args['posts_per_page'] = $args['number'];
			}

			$args = wp_parse_args( $args, $defaults );

			parent::__construct( $args );

			if ( ( isset( $this->args['included_ids'] ) ) && ( ! empty( $this->args['included_ids'] ) ) ) {
				$this->query_args['include'] = $this->args['included_ids'];
			}
		}

		/**
		 * Get selector section items.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function query_selection_section_items( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				if ( 'left' === $position ) {
					if ( ! empty( $this->args['included_ids'] ) ) {
						$this->args['post__in'] = $this->args['included_ids'];
					}

					if ( true === $this->args['is_search'] ) {
						if ( ( isset( $this->args['selected_ids'] ) ) && ( ! empty( $this->args['selected_ids'] ) ) ) {
							if ( ! isset( $this->args['post__not_in'] ) ) {
								$this->args['post__not_in'] = array();
							}
							$this->args['post__not_in'] = array_merge( $this->args['post__not_in'], $this->args['selected_ids'] );
						}
					}
				} else if ( 'right' === $position ) {
					if ( ! empty( $this->args['selected_ids'] ) ) {
						$this->args['post__in'] = $this->args['selected_ids'];
					} else {
						$this->args['post__in'] = array( 0 );
					}
				}

				$this->process_query( $this->args, $position );
				if ( isset( $this->args['post__in'] ) ) {
					unset( $this->args['post__in'] );
				}
			}
		}

		/**
		 * Process selector section query.
		 *
		 * @param array  $query_args Array of query args.
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function process_query( $query_args = array(), $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				$query = new WP_Query( $query_args );
				if ( ( isset( $query->posts ) ) && ( ! empty( $query->posts ) ) ) {

					$this->element_queries[ $position ] = $query;

					if ( 'left' === $position ) {
						$this->element_data['query_vars'] = $query_args;
					}

					$this->element_items[ $position ] = $query->posts;

					$this->element_data['selector_class'] = $this->selector_class;
					$this->element_data['selector_nonce'] = $this->get_nonce_data();

					$this->element_data[ $position ]['position'] = $position;
					$this->element_data[ $position ]['pager']    = $this->get_pager_data( $position );
				}
			}
		}

		/**
		 * Get selector section pager data.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function get_pager_data( $position = '' ) {
			$pager = array();
			if ( $this->is_valid_position( $position ) ) {
				if ( isset( $this->element_queries[ $position ] ) ) {

					if ( isset( $this->element_queries[ $position ]->query_vars['paged'] ) ) {
						$pager['current_page'] = intval( $this->element_queries[ $position ]->query_vars['paged'] );
					} else {
						$pager['current_page'] = 0;
					}

					if ( isset( $this->element_queries[ $position ]->query_vars['posts_per_page'] ) ) {
						$pager['per_page'] = intval( $this->element_queries[ $position ]->query_vars['posts_per_page'] );
					} else {
						$pager['per_page'] = 0;
					}

					if ( isset( $this->element_queries[ $position ]->found_posts ) ) {
						$pager['total_items'] = intval( $this->element_queries[ $position ]->found_posts );
					} else {
						$pager['total_items'] = 0;
					}

					if ( ( ! empty( $pager['per_page'] ) ) && ( ! empty( $pager['total_items'] ) ) ) {
						$pager['total_pages'] = ceil( intval( $pager['total_items'] ) / intval( $pager['per_page'] ) );
					} else {
						$pager['total_pages'] = 0;
					}
				}
			}
			return $pager;
		}

		/**
		 * Build selector section options HTML.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function build_options_html( $position = '' ) {
			$options_html = '';
			if ( $this->is_valid_position( $position ) ) {
				if ( ! empty( $this->element_items[ $position ] ) ) {
					foreach ( $this->element_items[ $position ] as $post ) {
						$disabled_class = '';
						$disabled_state = '';

						$item_title = apply_filters( 'learndash_binary_selector_item', $post->post_title, $post, $position, $this->selector_class );
						if ( ! empty( $item_title ) ) {
							$item_title = strip_tags( $item_title );
						} else {
							$item_title = $post->post_title;
						}

						if ( ( is_array( $this->args['selected_ids'] ) ) && ( ! empty( $this->args['selected_ids'] ) ) ) {
							if ( in_array( $post->ID, $this->args['selected_ids'] ) ) {
								$disabled_class = 'learndash-binary-selector-item-disabled';
								if ( 'left' == $position ) {
									$disabled_state = ' disabled="disabled" ';
								}
							}
						}
						$options_html .= '<option class="learndash-binary-selector-item ' . $disabled_class . '" ' . $disabled_state . ' value="' . $post->ID . '" data-value="' . $post->ID . '">' . $item_title . '</option>';
					}
				}
			}

			return $options_html;
		}

		/**
		 * Load selector section search AJAX.
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function load_search_ajax( $position = '' ) {

			$reply_data = array();

			if ( $this->is_valid_position( $position ) ) {
				if ( ( ! isset( $this->args['s'] ) ) && ( isset( $this->args['search'] ) ) ) {
					$this->args['s'] = $this->args['search'];
					unset( $this->args['search'] );
				}

				if ( ( isset( $this->args['s'] ) ) && ( ! empty( $this->args['s'] ) ) ) {
					$this->args['s'] = '"' . $this->args['s'] . '"';

					add_filter( 'posts_search', array( $this, 'search_filter_by_title' ), 10, 2 );
					$reply_data = parent::load_search_ajax( $position );
					remove_filter( 'posts_search', array( $this, 'search_filter_by_title' ), 10, 2 );
				}
			}
			return $reply_data;
		}

		/**
		 * Search filter by Title.
		 *
		 * @param string   $search Search pattern.
		 * @param WP_Query $wp_query WP_Query object.
		 */
		public function search_filter_by_title( $search = '', WP_Query $wp_query ) {
			if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
				global $wpdb;

				$q = $wp_query->query_vars;
				$n = ! empty( $q['exact'] ) ? '' : '%';

				$search = array();

				foreach ( (array) $q['search_terms'] as $term ) {
					$search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $n . $wpdb->esc_like( $term ) . $n );
				}

				if ( ! is_user_logged_in() ) {
					$search[] = "$wpdb->posts.post_password = ''";
				}

				$search = ' AND ' . implode( ' AND ', $search );
			}
			return $search;
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Group_Courses' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 * Class for LearnDash binary Selector Group Courses.
	 */
	class Learndash_Binary_Selector_Group_Courses extends Learndash_Binary_Selector_Posts {

		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'group_id'           => 0,
				'post_type'          => 'sfwd-courses',
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholder: courses.
					esc_html_x( 'Group %s', 'Group Courses label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				) . '</h3>',
				'html_id'            => 'learndash_group_courses',
				'html_class'         => 'learndash_group_courses',
				'html_name'          => 'learndash_group_courses',
				'search_label_left'  => sprintf(
					// translators: placeholder: courses.
					esc_html_x( 'Search All Group %s', 'Search All Group Courses Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
				'search_label_right' => sprintf(
					// translators: placeholder: courses.
					esc_html_x( 'Search Assigned Group %s', 'Search Assigned Group Courses Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['group_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['group_id'] . ']';

			parent::__construct( $args );
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Course_Groups' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 * Class for LearnDash binary Selector Course Groups
	 */
	class Learndash_Binary_Selector_Course_Groups extends Learndash_Binary_Selector_Posts {
		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'course_id'          => 0,
				'post_type'          => 'groups',
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholder: Course.
					esc_html_x( 'Groups Using %s', 'Groups Using Course Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'course' )
				) . '</h3>',
				'html_id'            => 'learndash_course_groups',
				'html_class'         => 'learndash_course_groups',
				'html_name'          => 'learndash_course_groups',
				'search_label_left'  => esc_html__( 'Search All Groups', 'learndash' ),
				'search_label_right' => sprintf(
					// translators: placeholder: Course.
					esc_html_x( 'Search %s Groups', 'Search Course Groups Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'course' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['course_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['course_id'] . ']';

			parent::__construct( $args );
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_User_Courses' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 * Class for LearnDash binary Selector User Courses.
	 */
	class Learndash_Binary_Selector_User_Courses extends Learndash_Binary_Selector_Posts {
		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'user_id'            => 0,
				'post_type'          => 'sfwd-courses',
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholder: Courses.
					esc_html_x( 'User Enrolled in %s', 'User Enrolled in Courses Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				) . '</h3>',
				'html_id'            => 'learndash_user_courses',
				'html_class'         => 'learndash_user_courses',
				'html_name'          => 'learndash_user_courses',
				'search_label_left'  => sprintf(
					// translators: placeholder: Courses.
					esc_html_x( 'Search All %s', 'Search All Courses Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
				'search_label_right' => sprintf(
					// translators: placeholder: Courses.
					esc_html_x( 'Search Enrolled %s', 'Search Enrolled Courses Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['user_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['user_id'] . ']';

			parent::__construct( $args );
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_User_Groups' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 * Class for LearnDash binary Selector User Groups.
	 */
	class Learndash_Binary_Selector_User_Groups extends Learndash_Binary_Selector_Posts {
		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'user_id'            => 0,
				'post_type'          => 'groups',
				'html_title'         => '<h3>' . esc_html__( 'User Enrolled in Groups', 'learndash' ) . '</h3>',
				'html_id'            => 'learndash_user_groups',
				'html_class'         => 'learndash_user_groups',
				'html_name'          => 'learndash_user_groups',
				'search_label_left'  => esc_html__( 'Search All Groups', 'learndash' ),
				'search_label_right' => esc_html__( 'Search Enrolled Groups', 'learndash' ),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['user_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['user_id'] . ']';

			parent::__construct( $args );
		}
	}
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Leader_Groups' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 * Class for LearnDash binary Selector Leader Groups.
	 */
	class Learndash_Binary_Selector_Leader_Groups extends Learndash_Binary_Selector_Posts {
		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'user_id'            => 0,
				'post_type'          => 'groups',
				'html_title'         => '<h3>' . esc_html__( 'Leader of Groups', 'learndash' ) . '</h3>',
				'html_id'            => 'learndash_leader_groups',
				'html_class'         => 'learndash_leader_groups',
				'html_name'          => 'learndash_leader_groups',
				'search_label_left'  => esc_html__( 'Search All Groups', 'learndash' ),
				'search_label_right' => esc_html__( 'Search Leader Groups', 'learndash' ),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id'] = $args['html_id'] . '-' . $args['user_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['user_id'] . ']';

			parent::__construct( $args );
		}
	}
}

/**
 * Handler function for AJAX pager.
 */
function learndash_binary_selector_pager_ajax() {

	$reply_data = array( 'status' => false );

	if ( ( isset( $_POST['query_data'] ) ) && ( ! empty( $_POST['query_data'] ) ) ) {
		if ( ( isset( $_POST['query_data']['query_vars'] ) ) && ( ! empty( $_POST['query_data']['query_vars'] ) ) ) {

			$args = $_POST['query_data']['query_vars'];

			if ( ( isset( $args['include'] ) ) && ( ! empty( $args['include'] ) ) ) {
				if ( learndash_is_valid_JSON( stripslashes( $args['include'] ) ) ) {
					$args['include'] = (array)json_decode( stripslashes( $args['include'] ) );
				}
			}

			if ( ( isset( $args['exclude'] ) ) && ( ! empty( $args['exclude'] ) ) ) {
				if ( learndash_is_valid_JSON( stripslashes( $args['exclude'] ) ) ) {
					$args['exclude'] = (array)json_decode( stripslashes( $args['exclude'] ) );
				}
			}

			if ( ( isset( $_POST['query_data']['selected_ids'] ) ) && ( ! empty( $_POST['query_data']['selected_ids'] ) ) ) {
				$args['selected_ids'] = (array) json_decode( stripslashes( $_POST['query_data']['selected_ids'] ) );
			}

			// Set our reference flag so other functions know we are running pager.
			$args['is_pager'] = true;

			if ( ( isset( $_POST['query_data']['selector_class'] ) ) && ( class_exists( $_POST['query_data']['selector_class'] ) ) && ( is_subclass_of( $_POST['query_data']['selector_class'], 'Learndash_Binary_Selector' ) ) ) {

				$selector = new $_POST['query_data']['selector_class']( $args );

				if ( ( isset( $_POST['query_data']['selector_nonce'] ) ) && ( ! empty( $_POST['query_data']['selector_nonce'] ) ) ) {
					if ( $selector->validate_nonce_data( $_POST['query_data']['selector_nonce'] ) ) {
						if ( ( isset( $_POST['query_data']['position'] ) ) && ( ! empty( $_POST['query_data']['position'] ) ) ) {
							if ( ( isset( $_POST['query_data']['query_vars']['search'] ) ) && ( ! empty( $_POST['query_data']['query_vars']['search'] ) ) ) {
								//$selector->is_search = true;
								$reply_data = $selector->load_search_ajax( esc_attr( $_POST['query_data']['position'] ) );
							} else {
								$reply_data = $selector->load_pager_ajax( esc_attr( $_POST['query_data']['position'] ) );
							}
						}
					}
				}
			}
		}
	}

	if ( ! empty( $reply_data ) ) {
		echo json_encode( $reply_data );
	}

	wp_die(); // this is required to terminate immediately and return a proper response.
}

add_action( 'wp_ajax_learndash_binary_selector_pager', 'learndash_binary_selector_pager_ajax' );
