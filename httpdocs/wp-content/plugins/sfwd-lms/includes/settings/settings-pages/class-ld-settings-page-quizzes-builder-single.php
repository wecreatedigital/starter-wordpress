<?php
/**
 * LearnDash Settings Page Quiz Builder Single.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Quiz_Builder_Single' ) ) ) {
	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Quiz_Builder_Single extends LearnDash_Settings_Page {
		/**
		 * Private variable to contain the update status.
		 *
		 * @var boolean $update_success
		 */
		private $update_success = false;

		/**
		 * Priority for tab
		 *
		 * @var integer $settings_tab_priority
		 */
		protected $settings_tab_priority = 8;

		/**
		 * Public constructor for class
		 */
		public function __construct() {

			$this->parent_menu_page_url = 'edit.php?post_type=sfwd-quiz';
			$this->menu_page_capability = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id     = 'quizzes-builder';
			$this->settings_page_title  = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Builder', 'placeholder: Quiz', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);

			$this->settings_tab_title = $this->settings_page_title;

			add_action( 'load-sfwd-quiz_page_quizzes-builder', array( $this, 'on_load' ) );
			add_filter( 'post_row_actions', array( $this, 'learndash_quiz_row_actions' ), 20, 2 );
			add_filter( 'learndash_admin_tab_sets', array( $this, 'admin_tab_sets' ), 15, 2 );
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );

			parent::__construct();
		}

		/**
		 * Action function called after title is displayed.
		 *
		 * @since 2.4.0
		 *
		 * @param string $settings_screen_id Current screen ID.
		 */
		public function settings_page_after_title( $settings_screen_id = '' ) {
			if ( $this->settings_screen_id == $settings_screen_id ) {
				if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
					$quiz_id   = intval( $_GET['quiz_id'] );
					$quiz_post = get_post( $quiz_id );
					if ( ( $quiz_post ) && ( is_a( $quiz_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $quiz_post->post_type ) ) {
						?>
						<div id="course-builder-title-box">
							<h2 class="course-title"><?php echo $quiz_post->post_title; ?></h2>
							<p class="course-links">
								<strong><?php esc_html_e( 'Permalink:', 'learndash' ); ?></strong> <a href="<?php echo get_permalink( $quiz_id ); ?>"><?php echo get_permalink( $quiz_id ); ?></a><br />
								<strong><?php esc_html_e( 'Edit:', 'learndash' ); ?></strong> <a href="<?php echo get_edit_post_link( $quiz_id ); ?>"><?php echo get_edit_post_link( $quiz_id ); ?></a>
							</p>
						</div>
						<?php
					}
				}
			}
		}

		/**
		 * Override the settings form as we are not really handling settings to be sent through options.php
		 *
		 * @since 2.4.0
		 *
		 * @param boolean $start Start.
		 *
		 * @return boolean $start
		 */
		public function get_admin_page_form( $start = true ) {
			if ( true === $start ) {
				return apply_filters( 'learndash_admin_page_form', '<form id="learndash-settings-page-form" method="post">', $start );
			} else {
				return apply_filters( 'learndash_admin_page_form', '</form>', $start );
			}
		}

		/**
		 * Action hook when settings screen is being shown.
		 */
		public function on_load() {
			if ( is_admin() ) {
				// If the Course Builder screen is being shown...
				$current_screen = get_current_screen();
				if ( 'sfwd-quiz_page_quizzes-builder' === $current_screen->id ) {
					// ...but the 'course_id' query parameters is not found...
					if ( ( ! isset( $_GET['quiz_id'] ) ) || ( empty( $_GET['quiz_id'] ) ) ) {
						// ...then redirect back to the courses listin screen.
						$quizzes_list_url = add_query_arg( 'post_type', 'sfwd-quiz', admin_url( 'edit.php' ) );
						wp_safe_redirect( $quizzes_list_url );
					} else {
						$this->cb = Learndash_Admin_Metabox_Quiz_Builder::add_instance( 'Learndash_Admin_Metabox_Quiz_Builder' );
						$this->save_cb_metabox();
						$this->cb->builder_on_load();
					}
				}
			}
		}

		/**
		 * Called when metabox is being saved.
		 */
		public function save_cb_metabox() {
			if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
				$quiz_id = intval( $_GET['quiz_id'] );

				$quiz_post = get_post( $quiz_id );
				if ( ( $quiz_post ) && ( is_a( $quiz_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $quiz_post->post_type ) ) {
					$this->update_success = $this->cb->save_course_builder( $quiz_id, $quiz_post, true );
				}
			}
		}

		/**
		 * Function to show admin notices on settings page.
		 */
		public function admin_notice() {
			if ( true === $this->update_success ) {
				?>
				<div class="notice notice-success is-dismissible"> 
					<p><strong><?php esc_html_e( 'Settings saved.', 'learndash' ); ?></strong></p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'learndash' ); ?></span>
					</button>
				</div>
				<?php
			}
		}

		/**
		 * Add Quiz Builder link to Quizzes row action array.
		 *
		 * @since 2.5.0
		 *
		 * @param array   $row_actions Existing Row actions for quiz.
		 * @param WP_Post $quiz_post Quiz Post object for current row.
		 *
		 * @return array $row_actions
		 */
		public function learndash_quiz_row_actions( $row_actions = array(), $quiz_post = null ) {
			global $typenow, $pagenow;

			if ( ( 'edit.php' === $pagenow ) && ( learndash_get_post_type_slug( 'quiz' ) === $typenow ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
				if ( ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) ) && ( ! isset( $row_actions['ld-quiz-builder'] ) ) ) {
					if ( apply_filters( 'learndash_show_quiz_builder_row_actions', true, $quiz_post ) === true ) {
						$quiz_label = sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Use %s Builder', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						);

						$row_actions['ld-quiz-builder'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							add_query_arg(
								array(
									'currentTab' => 'learndash_quiz_builder',
								),
								get_edit_post_link( $quiz_post->ID )
							),
							esc_attr( $quiz_label ),
							esc_html__( 'Builder', 'learndash' )
						);
					}
				}
			}

			return $row_actions;
		}

		/**
		 * Filter the LearnDash admin menu (tabs). We remove the 'Course Builder' tab until needed.
		 *
		 * @since 2.5.0
		 *
		 * @param array  $admin_menu_set Current Menu set array.
		 * @param string $admin_menu_key Current menu key.
		 *
		 * @return array $admin_menu_set
		 */
		public function admin_tab_sets( $admin_menu_set = array(), $admin_menu_key = '' ) {

			if ( 'edit.php?post_type=' . learndash_get_post_type_slug( 'quiz' ) === $admin_menu_key ) {
				if ( ( ! isset( $_GET['quiz_id'] ) ) || ( empty( $_GET['quiz_id'] ) ) ) {
					// If we don't have the 'course_id' URL parameter then we remove the tab.
					foreach ( $admin_menu_set as $menu_idx => $menu_item ) {
						if ( 'sfwd-quiz_page_quizzes-builder' === $menu_item['id'] ) {
							unset( $admin_menu_set[ $menu_idx ] );
							break;
						}
					}
				} else {
					// Else of we do have the 'quiz_id' URL parameter we include this in the tab URL.
					foreach ( $admin_menu_set as $menu_idx => &$menu_item ) {
						if ( 'sfwd-quiz_page_quizzes-builder' === $menu_item['id'] ) {
							$menu_item['link'] = add_query_arg( 'quiz_id', intval( $_GET['quiz_id'] ), $menu_item['link'] );
							break;
						}
					}
				}
			}

			return $admin_menu_set;
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Quiz_Builder_Single::add_page_instance();
	}
);
