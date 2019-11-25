<?php
/**
 * LearnDash Course Builder Metabox Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ! class_exists( 'Learndash_Admin_Builder' ) ) {
	/**
	 * Class for LearnDash Admin Builder.
	 */
	class Learndash_Admin_Builder {

		protected $builder_post_type;
		protected $builder_prefix      = 'learndash_builder';
		protected $builder_assets      = array();
		protected $selector_post_types = array();
		//protected $ld_course_steps_object = null;

		/**
		 * Static array of section instances.
		 *
		 * @var array $_instances
		 */
		protected static $_instances = array();

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			if ( ! isset( $this->builder_assets[ $this->builder_post_type ] ) ) {
				$this->builder_assets[ $this->builder_post_type ] = array(
					'post_data' => array(),
					'messages'  => array(),
				);
			}

			add_action( 'admin_footer', array( $this, 'builder_admin_footer' ), 1 );

			if ( ! empty( $course_id ) ) {
				$this->builder_post_id = intval( $course_id );
			}
		}

				/**
		 * Add instance to static tracking array
		 *
		 * @since 2.4.0
		 */
		final public static function add_instance() {
			$called_class = get_called_class();
			if ( is_subclass_of( $called_class, __CLASS__ ) ) {
				if ( ! isset( self::$_instances[ $called_class ] ) ) {
					self::$_instances[ $called_class ] = new $called_class();
					if ( is_a( self::$_instances[ $called_class ], $called_class ) ) {
						return self::$_instances[ $called_class ];
					}
				} else {
					return self::$_instances[ $called_class ];
				}
			}
		}

		/**
		 * Get the current instance of this class or new.
		 *
		 * @since 2.3
		 *
		 * @param string $called_class Class for instance.
		 * @return object instance of class.
		 */
		public static function get_instance( $called_class = '' ) {
			if ( ! empty( $called_class ) ) {
				if ( isset( self::$_instances[ $called_class ] ) ) {
					return self::$_instances[ $called_class ];
				} else {
					self::add_instance();
					if ( ( isset( self::$_instances[ $called_class ] ) ) && ( is_a( self::$_instances[ $called_class ], $called_class ) ) ) {
						return self::$_instances[ $called_class ];
					}
				}
			} else {
				if ( null === self::$instance ) {
					self::$instance = new static();
				}

				return self::$instance;
			}
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 */
		public function builder_on_load() {
		}

		/**
		 * Call via the WordPress admin_footer action hook.
		 */
		public function builder_admin_footer() {
			global $post;

			wp_enqueue_style(
				'learndash-new-builder-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/builder/dist/builder' . leardash_min_builder_asset() . '.css',
				array( 'wp-editor' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash-new-builder-style', 'rtl', 'replace' );

			wp_enqueue_script(
				'learndash-new-builder-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/builder/dist/builder' . leardash_min_builder_asset() . '.js',
				array( 'wp-i18n', 'learndash-new-header-script', 'wp-data' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);

			// Make sure some metaboxes can't be toggled off
			wp_enqueue_script(
				'learndash-force-metaboxes',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/builder/dist/metaboxes' . leardash_min_builder_asset() . '.js',
				array( 'wp-data', 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);

			$metaboxes = array();

			if ( ( function_exists( 'use_block_editor_for_post' ) ) && ( use_block_editor_for_post( $post ) ) ) {
				$this->builder_assets[ $this->builder_post_type ]['post_data']['builder_editor'] = 'block';
				$metaboxes['editor'] = 'block';

			} else {
				$this->builder_assets[ $this->builder_post_type ]['post_data']['builder_editor'] = 'classic';
				$metaboxes['editor'] = 'classic';
			}

			$this->builder_assets[ $this->builder_post_type ]['post_data']['builder_class']      = get_called_class();
			$this->builder_assets[ $this->builder_post_type ]['post_data']['builder_post_id']    = $this->builder_post_id;
			$this->builder_assets[ $this->builder_post_type ]['post_data']['builder_post_title'] = get_the_title( $this->builder_post_id );
			$this->builder_assets[ $this->builder_post_type ]['post_data']['builder_post_type']  = $this->builder_post_type;

			wp_localize_script( 'learndash-new-builder-script', 'learndash_builder_assets', $this->builder_assets );
			wp_localize_script( 'learndash-force-metaboxes', 'learndash_builder_metaboxes', $metaboxes );

		}

		/**
		 * Prints content for Course Builder meta box for admin
		 * This function is called from other add_meta_box functions
		 *
		 * @since 2.5
		 *
		 * @param object $post WP_Post.
		 * @return string meta box HTML output.
		 */
		public function show_builder_box( $post ) {
			// Use nonce for verification.
			wp_nonce_field( $this->builder_prefix . '_' . $this->builder_post_type . '_' . $this->builder_post_id . '_nonce', $this->builder_prefix . '_nonce' );
			?>
			<div id="learndash_builder_box_wrap" class="learndash_builder_box_wrap" data-ld-course-id="<?php echo intval( $this->builder_post_id ); ?>" data-ld-typenow="<?php echo esc_attr( $post->post_type ); ?>">
				<input type="hidden" id="<?php echo esc_attr( $this->builder_prefix ); ?>_data" name="<?php echo esc_attr( $this->builder_prefix ); ?>[<?php echo esc_attr( $this->builder_post_type ); ?>][<?php echo intval( $this->builder_post_id ); ?>]" value="" />
				<div class="learndash_selectors">
					<div class="learndash-header-right">
						<span class="ld-show-all"><?php esc_html_e( 'Expand All', 'learndash' ); ?></span>
						<span class="ld-divide-all">|</span>
						<span class="ld-hide-all"><?php esc_html_e( 'Collapse All', 'learndash' ); ?></span>
					</div>
					<?php $this->show_builder_selectors(); ?>
				</div>
				<div class="learndash_builder_items">
					<?php $this->show_builder_headers(); ?>
					<?php echo $this->build_course_steps_html(); ?>
				</div>
				<br style="clear:both;"/>
			</div>
			<?php
		}

		public function show_builder_headers() {
			$this->show_builder_header_left();
			$this->show_builder_header_right();
		}

		public function show_builder_header_left() {
			?>
			<div class="learndash-header-left">
				<?php echo $this->get_build_items_count(); ?>
			</div>
			<?php
		}

		public function show_builder_header_right() {
			?>
			<div class="learndash-header-right">
				<span class="ld-show-all"><?php esc_html_e( 'Expand All', 'learndash' ); ?></span>
				<span class="ld-divide-all">|</span>
				<span class="ld-hide-all"><?php esc_html_e( 'Collapse All', 'learndash' ); ?></span>
			</div>
			<?php
		}

		/**
		 * Utility function to get the label for Post Type.
		 *
		 * @since 2.5.0
		 *
		 * @param string  $post_type Post Type slug.
		 * @param boolean $singular True if singular label needed. False for plural.
		 * @return string.
		 */
		public function get_label_for_post_type( $post_type = '', $singular = true ) {
			return '';
		}

		/**
		 * Show the Course Box Selectors - Left side sections. There will be one selector per post type.
		 */
		public function show_builder_selectors() {
			$builder_post_type_label = $this->get_label_for_post_type( $this->builder_post_type );

			foreach ( $this->selector_post_types as $selector_post_type ) {
				$post_type_object = get_post_type_object( $selector_post_type );
				if ( is_a( $post_type_object, 'WP_Post_Type' ) ) {

					$this->builder_assets[ $this->builder_post_type ]['messages'][ 'confirm_remove_' . $selector_post_type ] = sprintf(
						// translators: 'placeholders: will be post type labels like Course, Lesson, Topic'.
						esc_html_x( 'Are you sure you want to remove this %1$s from the %2$s? (This will also remove all sub-items)', 'placeholders: will be post type labels like Course, Lesson, Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( $this->get_label_for_post_type( $selector_post_type ) ),
						LearnDash_Custom_Label::get_label( $builder_post_type_label )
					);

					$this->builder_assets[ $this->builder_post_type ]['messages'][ 'confirm_trash_' . $selector_post_type ] = sprintf(
						// translators: placeholder: will be post type label like Course, Lesson, Topic.
						esc_html_x( 'Are you sure you want to move this %s to Trash?', 'placeholder: will be post type label like Course, Lesson, Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( $this->get_label_for_post_type( $selector_post_type ) )
					);

					$post_type_query_args = $this->build_selector_query(
						array(
							'post_type' => $selector_post_type,
						)
					);

					if ( ! empty( $post_type_query_args ) ) {
						$post_type_query          = new WP_Query( $post_type_query_args );
						$selector_post_type_steps = $this->get_selector_selected_steps( $selector_post_type );
						$selector_post_type_steps = htmlspecialchars( json_encode( $selector_post_type_steps ) );
						?>
						<div class="learndash-selector-container learndash-selector-container-<?php echo esc_attr( $selector_post_type ); ?>" data-ld-type="<?php echo esc_attr( $selector_post_type ); ?>" data-ld-selected="<?php echo esc_attr( $selector_post_type_steps ); ?>">
							<h3 class="learndash-selector-header"><span class="learndash-selector-title"><?php echo learndash_get_custom_label( $this->get_label_for_post_type( $selector_post_type, false ) ); ?></span><span class="ld-course-builder-action ld-course-builder-action-show-hide ld-course-builder-action-show dashicons" title="<?php esc_html_e( 'Expand/Collape Section', 'learndash' ); ?>"></span><span class="ld-course-builder-action ld-course-builder-action-add dashicons" title="<?php esc_html_e( 'New', 'learndash' ); ?>"><img src="<?php echo admin_url( 'images/wpspin_light-2x.gif' ); ?>" alt="" /></span></h3>
							<div class="learndash-selector-post-listing">
								<?php
									$row_single = $this->build_selector_row_single( null, $selector_post_type );
								if ( ! empty( $row_single ) ) {
									?>
										<ul class="learndash-row-placeholder" style="display:none"><?php echo $row_single; ?></ul>
										<?php
								}
								?>
								<div class="learndash-selector-pager">
									<p class="pager-info">
										<?php echo $this->build_selector_pages_buttons( $post_type_query ); ?>
									</p>
								</div>
								<div class="learndash-selector-search"><input type="text" placeholder="<?php esc_html_e( 'Search...', 'learndash' ); ?>" /></div>
								<ul id="learndash-selector-post-listing-<?php echo $selector_post_type; ?>" class="learndash-selector-post-listing dropfalse">
																					<?php
																					if ( $post_type_query->have_posts() ) {
																						echo $this->build_selector_rows( $post_type_query );
																					}
								?>
								</ul>
							</div>
						</div>
						<?php
					}
				}
			}
		}

		/**
		 * Common function to show Selector pager buttons.
		 *
		 * @since 2.5.0
		 * @param object $post_type_query WP_Query instance.
		 * @return string Button(s) HTML.
		 */
		public function build_selector_pages_buttons( $post_type_query ) {
			$pager_buttons = '';

			if ( $post_type_query instanceof WP_Query ) {
				$first_page = 1;

				$current_page = intval( $post_type_query->query['paged'] );
				$last_page    = intval( $post_type_query->max_num_pages );
				if ( empty( $last_page ) ) {
					$last_page = 1;
				}

				if ( $current_page <= 1 ) {
					$prev_page     = 1;
					$prev_disabled = ' disabled="disabled" ';
				} else {
					$prev_page     = $current_page - 1;
					$prev_disabled = '';
				}

				if ( $current_page >= $last_page ) {
					$next_page     = $last_page;
					$next_disabled = ' disabled="disabled" ';
				} else {
					$next_page     = $current_page + 1;
					$next_disabled = '';
				}

				$pager_buttons .= '<button ' . $prev_disabled . ' class="button button-simple first" data-page="' . $first_page . '" title="' . esc_attr__( 'First Page', 'learndash' ) . '">&laquo;</button>';
				$pager_buttons .= '<button ' . $prev_disabled . ' class="button button-simple prev" data-page="' . $prev_page . '" title="' . esc_attr__( 'Previous Page', 'learndash' ) . '">&lsaquo;</button>';
				$pager_buttons .= '<span><span class="pagedisplay"><span class="current_page">' . $current_page . '</span> / <span class="total_pages">' . $last_page . '</span></span></span>';
				$pager_buttons .= '<button ' . $next_disabled . ' class="button button-simple next" data-page="' . $next_page . '" title="' . esc_attr__( 'Next Page', 'learndash' ) . '">&rsaquo;</button>';
				$pager_buttons .= '<button ' . $next_disabled . ' class="button button-simple last" data-page="' . $last_page . '" title="' . esc_attr__( 'Last Page', 'learndash' ) . '" >&raquo;</button>';
			}

			return $pager_buttons;
		}

		/**
		 * Show selector rows.
		 *
		 * @since 2.5.0
		 * @param object $post_type_query WP_Query instance.
		 */
		protected function build_selector_rows( $post_type_query ) {
			$selector_rows = '';

			if ( $post_type_query instanceof WP_Query ) {
				$selector_post_type        = $post_type_query->query['post_type'];
				$selector_post_type_object = get_post_type_object( $selector_post_type );

				$selector_label = $selector_post_type_object->label;
				$selector_slug  = $this->get_label_for_post_type( $selector_post_type );

				foreach ( $post_type_query->posts as $p ) {
					$selector_rows .= $this->build_selector_row_single( $p, $selector_post_type );
				}
			}

			return $selector_rows;
		}

		/**
		 * Build Course Steps HTML.
		 */
		public function build_course_steps_html() {
			$steps_html = '';

			$course_steps = $this->ld_course_steps_object->get_steps();

			if ( ! empty( $course_steps ) ) {
				$steps_html .= $this->process_course_steps( $course_steps );
			}
			return $steps_html;
		}
		// End of functions.
	}
}

if ( ( defined( 'LEARNDASH_COURSE_BUILDER' ) ) && ( LEARNDASH_COURSE_BUILDER === true ) ) {
	require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/admin/classes-builders/class-learndash-admin-course-builder-metabox.php';
}
if ( ( defined( 'LEARNDASH_QUIZ_BUILDER' ) ) && ( LEARNDASH_QUIZ_BUILDER === true ) ) {
	require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/admin/classes-builders/class-learndash-admin-quiz-builder-metabox.php';
}

add_action(
	'wp_ajax_learndash_builder_selector_pager',
	function() {
		if ( ! current_user_can( 'read' ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}

		// @codingStandardsIgnoreStart
		if ( ( ! isset( $_POST['builder_data'] ) ) || ( empty( $_POST['builder_data'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_data = $_POST['builder_data'];

		if ( ( ! isset( $_POST['builder_query_args'] ) ) || ( empty( $_POST['builder_query_args'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_query_args = $_POST['builder_query_args'];
		// @codingStandardsIgnoreEnd

		$builder_data = learndash_verify_builder_data( $builder_data );

		$builder_instance = $builder_data['builder_class']::add_instance();
		if ( is_a( $builder_instance, $builder_data['builder_class'] ) ) {
			$builder_instance->builder_init( $builder_data['builder_post_id'] );
			$builder_instance->learndash_builder_selector_pager( $builder_query_args );
		}
		echo json_encode( array() );
		wp_die();

		/*
		if ( ( isset( $_POST['builder_data'] ) ) && ( ! empty( $_POST['builder_data'] ) ) ) {
			if ( ( isset( $_POST['builder_data']['builder_class'] ) ) && ( ! empty( $_POST['builder_data']['builder_class'] ) ) ) {
				$builder_class    = esc_attr( $_POST['builder_data']['builder_class'] );
				$builder_instance = $builder_class::add_instance();
				if ( is_a( $builder_instance, $builder_class ) ) {
					if ( ( isset( $_POST['builder_data']['builder_post_id'] ) ) && ( ! empty( $_POST['builder_data']['builder_post_id'] ) ) ) {
						$builder_instance->builder_init( $_POST['builder_data']['builder_post_id'] );
						if ( ( isset( $_POST['builder_query_args'] ) ) && ( ! empty( $_POST['builder_query_args'] ) ) ) {
							$builder_instance->learndash_builder_selector_pager( $_POST['builder_query_args'] );
						}
					}
				}
			}
		}
		echo json_encode( array() );

		wp_die();
		*/
	}
);

add_action(
	'wp_ajax_learndash_builder_selector_search',
	function() {
		if ( ! current_user_can( 'read' ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}

		// @codingStandardsIgnoreStart
		if ( ( ! isset( $_POST['builder_data'] ) ) || ( empty( $_POST['builder_data'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_data = $_POST['builder_data'];

		if ( ( ! isset( $_POST['builder_query_args'] ) ) || ( empty( $_POST['builder_query_args'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_query_args = $_POST['builder_query_args'];
		// @codingStandardsIgnoreEnd

		$builder_data = learndash_verify_builder_data( $builder_data );

		$builder_instance = $builder_data['builder_class']::add_instance();
		if ( is_a( $builder_instance, $builder_data['builder_class'] ) ) {
			$builder_instance->builder_init( $builder_data['builder_post_id'] );
			$builder_instance->learndash_builder_selector_search( $builder_query_args );
		}
		echo json_encode( array() );
		wp_die();

		/*
		if ( ( isset( $_POST['builder_data'] ) ) && ( ! empty( $_POST['builder_data'] ) ) ) {
			if ( ( isset( $_POST['builder_data']['builder_class'] ) ) && ( ! empty( $_POST['builder_data']['builder_class'] ) ) ) {
				$builder_class    = esc_attr( $_POST['builder_data']['builder_class'] );
				$builder_instance = $builder_class::add_instance();
				if ( is_a( $builder_instance, $builder_class ) ) {
					if ( ( isset( $_POST['builder_data']['builder_post_id'] ) ) && ( ! empty( $_POST['builder_data']['builder_post_id'] ) ) ) {
						$builder_instance->builder_init( $_POST['builder_data']['builder_post_id'] );

						if ( ( isset( $_POST['builder_query_args'] ) ) && ( ! empty( $_POST['builder_query_args'] ) ) ) {
							$builder_instance->learndash_builder_selector_search( $_POST['builder_query_args'] );
						}
					}
				}
			}
		}
		echo json_encode( array() );

		wp_die();
		*/
	}
);

add_action(
	'wp_ajax_learndash_builder_selector_step_trash',
	function() {

		if ( ! current_user_can( 'edit_courses' ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}

		// @codingStandardsIgnoreStart
		if ( ( ! isset( $_POST['builder_data'] ) ) || ( empty( $_POST['builder_data'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_data = $_POST['builder_data'];

		if ( ( ! isset( $_POST['builder_query_args'] ) ) || ( empty( $_POST['builder_query_args'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_query_args = $_POST['builder_query_args'];
		// @codingStandardsIgnoreEnd

		$builder_data = learndash_verify_builder_data( $builder_data );

		$builder_instance = $builder_data['builder_class']::add_instance();
		if ( is_a( $builder_instance, $builder_data['builder_class'] ) ) {
			$builder_instance->builder_init( $builder_data['builder_post_id'] );
			$builder_instance->learndash_builder_selector_step_trash( $builder_query_args );
		}
		echo json_encode( array() );
		wp_die();

		/*
		if ( ( isset( $_POST['builder_data'] ) ) && ( ! empty( $_POST['builder_data'] ) ) ) {
			if ( ( isset( $_POST['builder_data']['builder_class'] ) ) && ( ! empty( $_POST['builder_data']['builder_class'] ) ) ) {
				$builder_class    = esc_attr( $_POST['builder_data']['builder_class'] );
				$builder_instance = $builder_class::add_instance();
				if ( is_a( $builder_instance, $builder_class ) ) {
					if ( ( isset( $_POST['builder_data']['builder_post_id'] ) ) && ( ! empty( $_POST['builder_data']['builder_post_id'] ) ) ) {
						$builder_instance->builder_init( $_POST['builder_data']['builder_post_id'] );

						if ( ( isset( $_POST['builder_query_args'] ) ) && ( ! empty( $_POST['builder_query_args'] ) ) ) {
							$builder_instance->learndash_builder_selector_step_trash( $_POST['builder_query_args'] );
						}
					}
				}
			}
		}
		echo json_encode( array() );

		wp_die();
		*/
	}
);

add_action(
	'wp_ajax_learndash_builder_selector_step_new',
	function() {

		if ( ! current_user_can( 'edit_courses' ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}

		// @codingStandardsIgnoreStart
		if ( ( ! isset( $_POST['builder_data'] ) ) || ( empty( $_POST['builder_data'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_data = $_POST['builder_data'];

		if ( ( ! isset( $_POST['builder_query_args'] ) ) || ( empty( $_POST['builder_query_args'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_query_args = $_POST['builder_query_args'];
		// @codingStandardsIgnoreEnd

		$builder_data = learndash_verify_builder_data( $builder_data );

		$builder_instance = $builder_data['builder_class']::add_instance();
		if ( is_a( $builder_instance, $builder_data['builder_class'] ) ) {
			$builder_instance->builder_init( $builder_data['builder_post_id'] );
			$builder_instance->learndash_builder_selector_step_new( $builder_query_args );
		}
		echo json_encode( array() );
		wp_die();

		/*
		if ( ( isset( $_POST['builder_data'] ) ) && ( ! empty( $_POST['builder_data'] ) ) ) {
			if ( ( isset( $_POST['builder_data']['builder_class'] ) ) && ( ! empty( $_POST['builder_data']['builder_class'] ) ) ) {
				$builder_class    = esc_attr( $_POST['builder_data']['builder_class'] );
				$builder_instance = $builder_class::add_instance();
				if ( is_a( $builder_instance, $builder_class ) ) {
					if ( ( isset( $_POST['builder_data']['builder_post_id'] ) ) && ( ! empty( $_POST['builder_data']['builder_post_id'] ) ) ) {
						$builder_instance->builder_init( $_POST['builder_data']['builder_post_id'] );

						if ( ( isset( $_POST['builder_query_args'] ) ) && ( ! empty( $_POST['builder_query_args'] ) ) ) {
							$builder_instance->learndash_builder_selector_step_new( $_POST['builder_query_args'] );
						}
					}
				}
			}
		}
		echo json_encode( array() );

		wp_die();
		*/
	}
);

add_action( 'wp_ajax_learndash_builder_selector_step_title', function() {
		if ( ! current_user_can( 'edit_courses' ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}

		// @codingStandardsIgnoreStart
		if ( ( ! isset( $_POST['builder_data'] ) ) || ( empty( $_POST['builder_data'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_data = $_POST['builder_data'];

		if ( ( ! isset( $_POST['builder_query_args'] ) ) || ( empty( $_POST['builder_query_args'] ) ) ) {
			echo wp_json_encode( array() );
			wp_die();
		}
		$builder_query_args = $_POST['builder_query_args'];
		// @codingStandardsIgnoreEnd

		$builder_data = learndash_verify_builder_data( $builder_data );

		$builder_instance = $builder_data['builder_class']::add_instance();
		if ( is_a( $builder_instance, $builder_data['builder_class'] ) ) {
			$builder_instance->builder_init( $builder_data['builder_post_id'] );
			$builder_instance->learndash_builder_selector_step_title( $builder_query_args );
		}
		echo json_encode( array() );

		wp_die();

		/*
		if ( ( isset( $_POST['builder_data'] ) ) && ( ! empty( $_POST['builder_data'] ) ) ) {
			if ( ( isset( $_POST['builder_data']['builder_class'] ) ) && ( ! empty( $_POST['builder_data']['builder_class'] ) ) ) {
				$builder_class = esc_attr( $_POST['builder_data']['builder_class'] );
				$builder_instance = $builder_class::add_instance();
				if ( is_a( $builder_instance, $builder_class ) ) {
					if ( ( isset( $_POST['builder_data']['builder_post_id'] ) ) && ( ! empty( $_POST['builder_data']['builder_post_id'] ) ) ) {
						$builder_instance->builder_init( $_POST['builder_data']['builder_post_id'] );

						if ( ( isset( $_POST['builder_query_args'] ) ) && ( ! empty( $_POST['builder_query_args'] ) ) ) {
							$builder_instance->learndash_builder_selector_step_title( $_POST['builder_query_args'] );
						}
					}
				}
			}
		}
		echo json_encode( array() );

		wp_die();
		*/
	}
);

function learndash_verify_builder_data( $builder_data = array() ) {
	if ( empty( $builder_data ) ) {
		echo wp_json_encode( array() );
		wp_die();
	}

	if ( ( ! isset( $builder_data['builder_class'] ) ) || ( empty( $builder_data['builder_class'] ) ) ) {
		echo wp_json_encode( array() );
		wp_die();
	}
	$builder_data['builder_class'] = esc_attr( $builder_data['builder_class'] );

	if ( ! is_subclass_of( $builder_data['builder_class'], 'Learndash_Admin_Builder' ) ) {
		echo wp_json_encode( array() );
		wp_die();
	}

	if ( ( ! isset( $builder_data['builder_post_type'] ) ) || ( empty( $builder_data['builder_post_type'] ) ) ) {
		echo wp_json_encode( array() );
		wp_die();
	}
	$builder_data['builder_post_type'] = esc_attr( $builder_data['builder_post_type'] );

	if ( ( ! isset( $builder_data['builder_post_id'] ) ) || ( empty( $builder_data['builder_post_id'] ) ) ) {
		echo wp_json_encode( array() );
		wp_die();
	}
	$builder_data['builder_post_id'] = absint( $builder_data['builder_post_id'] );

	if ( ( ! isset( $builder_data['builder_nonce'] ) ) || ( empty( $builder_data['builder_nonce'] ) ) ) {
		echo wp_json_encode( array() );
		wp_die();
	}

	//See nonce field build out in show_builder_box() of this file.
	//wp_nonce_field( $this->builder_prefix . '_' . $this->builder_post_type . '_' . $this->builder_post_id . '_nonce', $this->builder_prefix . '_nonce' );
	$nonce_field_value = 'learndash_builder_' . $builder_data['builder_post_type'] . '_' . $builder_data['builder_post_id'] . '_nonce';
	if ( ! wp_verify_nonce( $builder_data['builder_nonce'], $nonce_field_value ) ) {
		echo wp_json_encode( array() );
		wp_die();
	}

	return $builder_data;
}
