<?php
/**
 * LearnDash Settings Page Course Builder Single.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Course_Builder_Single' ) ) ) {
	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Course_Builder_Single extends LearnDash_Settings_Page {
		/**
		 * Private variable to contain the update status.
		 *
		 * @var boolean $update_success
		 */
		private $update_success = false;

		/**
		 * Public constructor for class
		 */
		public function __construct() {

			$this->parent_menu_page_url = 'edit.php?post_type=sfwd-courses';
			$this->menu_page_capability = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id     = 'courses-builder';
			$this->settings_page_title  = sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Builder', 'placeholder: Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);

			$this->settings_tab_title = $this->settings_page_title;

			add_action( 'load-sfwd-courses_page_courses-builder', array( $this, 'on_load' ) );
			add_filter( 'post_row_actions', array( $this, 'learndash_course_row_actions' ), 20, 2 );
			add_filter( 'learndash_admin_tab_sets', array( $this, 'admin_tab_sets' ), 15, 2 );
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
			parent::__construct();
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
				if ( 'sfwd-courses_page_courses-builder' == $current_screen->id ) {
					// ...but the 'course_id' query parameters is not found...
					if ( ( ! isset( $_GET['course_id'] ) ) || ( empty( $_GET['course_id'] ) ) ) {
						// ...then redirect back to the courses listin screen.
						$courses_list_url = add_query_arg( 'post_type', 'sfwd-courses', admin_url( 'edit.php' ) );
						wp_redirect( $courses_list_url );
					} else {
						$this->cb = Learndash_Admin_Metabox_Course_Builder::add_instance( 'Learndash_Admin_Metabox_Course_Builder' );
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
			if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
				$course_id = intval( $_GET['course_id'] );

				$course_post = get_post( $course_id );
				if ( ( ! empty( $_POST['action'] ) && 'update' === wp_unslash( $_POST['action'] ) ) &&
					( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) &&
					( 'sfwd-courses' == $course_post->post_type ) ) {
					$this->cb->save_course_builder( $course_id, $course_post, true );
					$this->update_success = true;
				}
			}
			return;

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
		 * Add Course Builder link to Courses row action array.
		 *
		 * @since 2.5.0
		 *
		 * @param array   $row_actions Existing Row actions for course.
		 * @param WP_Post $course_post Course Post object for current row.
		 *
		 * @return array $row_actions
		 */
		public function learndash_course_row_actions( $row_actions = array(), $course_post = null ) {
			global $typenow, $pagenow;

			if ( ( 'edit.php' === $pagenow ) && ( 'sfwd-courses' === $typenow ) && ( is_a( $course_post, 'WP_Post' ) ) ) {
				if ( ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) == 'yes' ) && ( ! isset( $row_actions['ld-course-builder'] ) ) ) {
					if ( apply_filters( 'learndash_show_course_builder_row_actions', true, $course_post ) === true ) {
						$course_label = sprintf(
							// translators: placeholder: Course.
							esc_html_x( 'Use %s Builder', 'placeholder: Course', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'course' )
						);

						$row_actions['ld-course-builder'] = sprintf(
							'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
							add_query_arg(
								array(
									'currentTab' => 'learndash_course_builder',
								),
								get_edit_post_link( $course_post->ID )
							),
							esc_attr( $course_label ),
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

			if ( 'edit.php?post_type=sfwd-courses' == $admin_menu_key ) {
				if ( ( ! isset( $_GET['course_id'] ) ) || ( empty( $_GET['course_id'] ) ) ) {
					// If we don't have the 'course_id' URL parameter then we remove the tab.
					foreach ( $admin_menu_set as $menu_idx => $menu_item ) {
						if ( 'sfwd-courses_page_courses-builder' === $menu_item['id'] ) {
							unset( $admin_menu_set[ $menu_idx ] );
							break;
						}
					}
				} else {
					// Else of we do have the 'course_id' URL parameter we include this in the tab URL.
					foreach ( $admin_menu_set as $menu_idx => &$menu_item ) {
						if ( 'sfwd-courses_page_courses-builder' === $menu_item['id'] ) {
							$menu_item['link'] = add_query_arg( 'course_id', intval( $_GET['course_id'] ), $menu_item['link'] );
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
		LearnDash_Settings_Page_Course_Builder_Single::add_page_instance();
	}
);
