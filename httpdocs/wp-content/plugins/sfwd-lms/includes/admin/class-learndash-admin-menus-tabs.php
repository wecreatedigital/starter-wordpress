<?php
/**
 * LearnDash Settings Admin Menus and Tabs class.
 *
 * @package LearnDash
 */

if ( ! class_exists( 'Learndash_Admin_Menus_Tabs' ) ) {
	/**
	 * Class to create the settings section.
	 */
	class Learndash_Admin_Menus_Tabs {

		/**
		 * Holder variable for instances of this class.
		 *
		 * @var object $instance Instance of this class oabject.
		 */
		private static $instance;

		protected $admin_tab_sets    = array();
		public $admin_tab_priorities = array(
			'private'  => 0,
			'high'     => 10,
			'normal'   => 20,
			'taxonomy' => 30,
			'misc'     => 100,
		);

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// We first add this hook so we are calling 'admin_menu' early.
			add_action( 'admin_menu', array( $this, 'learndash_admin_menu_early' ), 0 );

			/**
			 * Then within the 'wp_loaded' handler we add another hook into
			 * 'admin_menu' to be in the last-est position where we add all
			 * the misc menu items.
			 */
			add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 1000 );

			//add_action( 'all_admin_notices', array( $this, 'learndash_admin_tabs' ), 20 );
			add_action( 'in_admin_header', array( $this, 'learndash_admin_tabs' ), 20 );
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new static();
			}

			return self::$instance;
		}

		/**
		 * We hook into the 'wp_loaded' action which comes just before the
		 * 'admin_menu' action. The reason for this we want to add a special
		 * 'admin_menu' and ensure it is the last action taken on the menu.
		 */
		public function wp_loaded() {

			global $wp_filter;

			/***********************************************************************
			admin_menu
			************************************************************************/
			// Set a default priority.

			$top_priority = 100;
			if ( defined( 'LEARNDASH_SUBMENU_SETTINGS_PRIORITY' ) ) {
				$top_priority = intval( LEARNDASH_SUBMENU_SETTINGS_PRIORITY );
			}

			$top_priority = apply_filters( 'learndash_submenu_settings_priority', $top_priority );

			add_action( 'admin_menu', array( $this, 'learndash_admin_menu_last' ), $top_priority );
		}

		public function learndash_menu_args( $menu_args = array() ) {
			if ( ( is_array( $menu_args['admin_tabs'] ) ) && ( ! empty( $menu_args['admin_tabs'] ) ) ) {
				foreach ( $menu_args['admin_tabs'] as &$admin_tab_item ) {

					// Similar to the logic from admin_menu above.
					// We need to convert the 'edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses'
					// menu_links to 'admin.php?page=learndash_lms_settings' so all the LearnDash > Settings tabs connect
					// to that menu instead.
					if ( 'edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses' === $admin_tab_item['menu_link'] ) {
						$admin_tab_item['menu_link'] = 'admin.php?page=learndash_lms_settings';
					}
				}
			}

			$menu_args['admin_tabs_on_page']['admin_page_learndash_lms_settings'] = $menu_args['admin_tabs_on_page']['sfwd-courses_page_sfwd-lms_sfwd_lms_post_type_sfwd-courses'];

			$menu_args['admin_tabs_on_page']['sfwd-courses_page_sfwd-lms_sfwd_lms_post_type_sfwd-courses'] = $menu_args['admin_tabs_on_page']['edit-sfwd-courses'];

			return $menu_args;
		}

		public function learndash_admin_menu_tabs( $menu_args = array() ) {
			$menu_item_tabs = array();

			// Now we take the current page id and collect all the tab items. This is the newer
			// form of the tab logic instead of them being global.
			$current_page_id = $menu_args['current_page_id'];
			if ( isset( $menu_args['admin_tabs_on_page'][ $current_page_id ] ) ) {
				$menu_link = '';

				foreach ( $menu_args['admin_tabs_on_page'][ $current_page_id ] as $admin_tabs_on_page_id ) {
					if ( isset( $menu_args['admin_tabs'][ $admin_tabs_on_page_id ] ) ) {
						if ( empty( $menu_link ) ) {
							$menu_link = $menu_args['admin_tabs'][ $admin_tabs_on_page_id ]['menu_link'];
						}

						$menu_item_tabs[ $admin_tabs_on_page_id ] = $menu_args['admin_tabs'][ $admin_tabs_on_page_id ];
					}
				}

				foreach ( $menu_args['admin_tabs'] as $admin_tab_id => $admin_tab ) {
					if ( $admin_tab['menu_link'] == $menu_link ) {
						if ( ! isset( $menu_item_tabs[ $admin_tab_id ] ) ) {
							$menu_item_tabs[ $admin_tab_id ] = $admin_tab;
						}
					}
				}
			}

			return $menu_item_tabs;
		}


		public function add_admin_tab_set( $menu_slug, $menu_item ) {
			global $learndash_post_types, $learndash_taxonomies;

			$url_parts = wp_parse_url( $menu_slug );
			if ( ( isset( $url_parts['path'] ) ) && ( 'edit.php' === $url_parts['path'] ) && ( isset( $url_parts['query'] ) ) && ( ! empty( $url_parts['query'] ) ) ) {
				$menu_query_args = array();
				parse_str( $url_parts['query'], $menu_query_args );
				if ( ( isset( $menu_query_args['post_type'] ) ) && ( in_array( $menu_query_args['post_type'], $learndash_post_types, true ) ) ) {
					if ( ! isset( $admin_tab_sets[ $menu_slug ] ) ) {
						$admin_tab_sets[ $menu_slug ] = array();
					}

					foreach ( $menu_item as $menu_item_section ) {
						$url_parts = wp_parse_url( html_entity_decode( $menu_item_section[2] ) );
						if ( ( isset( $url_parts['query'] ) ) && ( ! empty( $url_parts['query'] ) ) ) {
							parse_str( $url_parts['query'], $link_params );
						} else {
							$link_params = array(
								'post_type' => $menu_query_args['post_type'],
								'taxonomy'  => '',
							);
						}

						// Edit - We add in the 1 position
						if ( substr( $menu_item_section[2], 0, strlen( 'edit.php?' ) ) == 'edit.php?' ) {
							$all_title = $menu_item_section[0];
							if ( ( isset( $link_params['post_type'] ) ) && ( ! empty( $link_params['post_type'] ) ) ) {
								$post_type_object = get_post_type_object( $link_params['post_type'] );
								if ( $post_type_object ) {
									$all_title = sprintf(
										// translators: placeholder: Post Type Plural Name.
										esc_html_x( 'All  %s', 'placeholder: Post Type Plural Name', 'learndash' ),
										$post_type_object->labels->name
									);
								}
							}

							$this->admin_tab_sets[ $menu_slug ][1] = array(
								'id'   => 'edit-' . $link_params['post_type'],
								'name' => $all_title,
								'cap'  => $menu_item_section[1],
								'link' => $menu_item_section[2],
							);
						} elseif ( 'edit-tags.php?' === substr( $menu_item_section[2], 0, strlen( 'edit-tags.php?' ) ) ) {
							$menu_priority = 50;
							if ( 'sfwd-quiz' === $menu_query_args['post_type'] ) {
								$menu_priority = 23;
							} elseif ( ( isset( $link_params['taxonomy'] ) ) && ( ! empty( $link_params['taxonomy'] ) ) ) {
								if ( in_array( $link_params['taxonomy'], $learndash_taxonomies ) ) {
									$menu_priority = 40;
								}
							}

							$this->add_admin_tab_item(
								$menu_slug,
								array(
									'id'   => 'edit-' . $link_params['taxonomy'],
									'name' => $menu_item_section[0],
									'cap'  => $menu_item_section[1],
									'link' => $menu_item_section[2],
								),
								$menu_priority
							);
						}
					}
				}
			}
		}

		public function add_admin_tab_item( $menu_slug, $menu_item, $menu_priority = 20 ) {

			if ( ! isset( $this->admin_tab_sets[ $menu_slug ] ) ) {
				$this->admin_tab_sets[ $menu_slug ] = array();
			} else {
				ksort( $this->admin_tab_sets[ $menu_slug ] );
			}

			if ( ! isset( $menu_item['cap'] ) ) {
				$menu_item['cap'] = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			}

			while ( true ) {
				if ( ! isset( $this->admin_tab_sets[ $menu_slug ][ $menu_priority ] ) ) {
					$this->admin_tab_sets[ $menu_slug ][ $menu_priority ] = $menu_item;
					break;
				}
				$menu_priority++;
			}
		}


		/* The purpose of this early function is to setup the main 'learndash-lms' menu page. Then
		 * re-position the various custom post type submenu items to be found under it.
		*/
		public function learndash_admin_menu_early() {
			if ( ! is_admin() ) {
				return;
			}

			global $submenu, $menu;

			$add_submenu = array();

			if ( current_user_can( 'edit_courses' ) ) {
				if ( isset( $submenu['edit.php?post_type=sfwd-courses'] ) ) {
					$add_submenu['sfwd-courses'] = array(
						'name'  => LearnDash_Custom_Label::get_label( 'courses' ),
						'cap'   => 'edit_courses',
						'link'  => 'edit.php?post_type=sfwd-courses',
						'class' => 'submenu-ldlms-courses',
					);
					$this->add_admin_tab_set( 'edit.php?post_type=sfwd-courses', $submenu['edit.php?post_type=sfwd-courses'] );
				}

				if ( isset( $submenu['edit.php?post_type=sfwd-lessons'] ) ) {
					$add_submenu['sfwd-lessons'] = array(
						'name'  => LearnDash_Custom_Label::get_label( 'lessons' ),
						'cap'   => 'edit_courses',
						'link'  => 'edit.php?post_type=sfwd-lessons',
						'class' => 'submenu-ldlms-lessons',
					);
					$this->add_admin_tab_set( 'edit.php?post_type=sfwd-lessons', $submenu['edit.php?post_type=sfwd-lessons'] );
				}

				if ( isset( $submenu['edit.php?post_type=sfwd-topic'] ) ) {
					$add_submenu['sfwd-topic'] = array(
						'name'  => LearnDash_Custom_Label::get_label( 'topics' ),
						'cap'   => 'edit_courses',
						'link'  => 'edit.php?post_type=sfwd-topic',
						'class' => 'submenu-ldlms-topics',
					);
					$this->add_admin_tab_set( 'edit.php?post_type=sfwd-topic', $submenu['edit.php?post_type=sfwd-topic'] );
				}

				if ( isset( $submenu['edit.php?post_type=sfwd-quiz'] ) ) {
					$add_submenu['sfwd-quiz'] = array(
						'name'  => LearnDash_Custom_Label::get_label( 'quizzes' ),
						'cap'   => 'edit_courses',
						'link'  => 'edit.php?post_type=sfwd-quiz',
						'class' => 'submenu-ldlms-quizzes',
					);
					$this->add_admin_tab_set( 'edit.php?post_type=sfwd-quiz', $submenu['edit.php?post_type=sfwd-quiz'] );
				}

				if ( ( true === is_data_upgrade_quiz_questions_updated() ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ) {
					if ( isset( $submenu[ 'edit.php?post_type=' . learndash_get_post_type_slug( 'question' ) ] ) ) {
						$add_submenu['sfwd-question'] = array(
							'name'  => LearnDash_Custom_Label::get_label( 'questions' ),
							'cap'   => 'edit_courses',
							'link'  => add_query_arg(
								'post_type',
								learndash_get_post_type_slug( 'question' ),
								'edit.php'
							),
							'class' => 'submenu-ldlms-questions',
						);

						if ( isset( $_GET['quiz_id'] ) ) {
							$quiz_id = absint( $_GET['quiz_id'] );
							if ( ! empty( $quiz_id ) ) {
								foreach ( $submenu[ 'edit.php?post_type=' . learndash_get_post_type_slug( 'question' ) ] as &$link ) {
									$link[2] = add_query_arg( 'quiz_id', $quiz_id, $link[2] );
								}
							}
						}

						$this->add_admin_tab_set(
							add_query_arg(
								'post_type',
								learndash_get_post_type_slug( 'question' ),
								'edit.php'
							),
							$submenu[ 'edit.php?post_type=' . learndash_get_post_type_slug( 'question' ) ]
						);
					}
				}

				if ( isset( $submenu['edit.php?post_type=sfwd-certificates'] ) ) {
					$add_submenu['sfwd-certificates'] = array(
						'name'  => esc_html_x( 'Certificates', 'Certificates Menu Label', 'learndash' ),
						'cap'   => 'edit_courses',
						'link'  => 'edit.php?post_type=sfwd-certificates',
						'class' => 'submenu-ldlms-certificates',
					);
					$this->add_admin_tab_set( 'edit.php?post_type=sfwd-certificates', $submenu['edit.php?post_type=sfwd-certificates'] );
				}
			}

			if ( current_user_can( 'edit_assignments' ) ) {
				if ( isset( $submenu['edit.php?post_type=sfwd-assignment'] ) ) {
					$add_submenu['sfwd-assignment'] = array(
						'name'  => esc_html_x( 'Assignments', 'Assignments Menu Label', 'learndash' ),
						'cap'   => 'edit_assignments',
						'link'  => 'edit.php?post_type=sfwd-assignment',
						'class' => 'submenu-ldlms-assignments',
					);
					$this->add_admin_tab_set( 'edit.php?post_type=sfwd-assignment', $submenu['edit.php?post_type=sfwd-assignment'] );
				}
			}

			if ( current_user_can( 'edit_groups' ) ) {
				if ( isset( $submenu['edit.php?post_type=groups'] ) ) {
					$add_submenu['groups'] = array(
						'name'  => esc_html_x( 'Groups', 'Groups Menu Label', 'learndash' ),
						'cap'   => 'edit_groups',
						'link'  => 'edit.php?post_type=groups',
						'class' => 'submenu-ldlms-groups',
					);
					$this->add_admin_tab_set( 'edit.php?post_type=groups', $submenu['edit.php?post_type=groups'] );
				}
			}

			if ( learndash_is_group_leader_user() ) {
				$add_submenu['sfwd-essays'] = array(
					'name'  => esc_html_x( 'Submitted Essays', 'Submitted Essays Menu Label', 'learndash' ),
					'cap'   => 'group_leader',
					'link'  => 'edit.php?post_type=sfwd-essays',
					'class' => 'submenu-ldlms-essays',
				);
			}

			/**
			 * Filter submenu array before it is registered.
			 *
			 * @since 2.1.0
			 *
			 * @param  array  $add_submenu
			 */
			$add_submenu = apply_filters( 'learndash_submenu', $add_submenu );

			if ( ! empty( $add_submenu ) ) {

				$menu_position = 2;
				if ( defined( 'LEARNDASH_MENU_POSITION' ) ) {
					$menu_position = intval( LEARNDASH_MENU_POSITION );
				}

				$menu_position = apply_filters( 'learndash-menu-position', $menu_position );

				$menu_icon = '<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
									 viewBox="0 0 58 46.6" style="enable-background:new 0 0 58 46.6;" xml:space="preserve">
								<path fill="#fff" style="opacity:.45" d="M51,9.7V2.6C51,1.2,49.8,0,48.4,0H2.6C1.2,0,0,1.2,0,2.6v7.1L51,9.7z M12.8,4.6c0.4-0.4,1.2-0.4,1.6,0
									c0.2,0.2,0.4,0.5,0.4,0.8S14.7,6,14.5,6.2c-0.2,0.2-0.5,0.4-0.8,0.4s-0.6-0.1-0.8-0.4c-0.2-0.2-0.4-0.5-0.4-0.8
									C12.5,5.1,12.7,4.8,12.8,4.6z M8.6,4.6c0.4-0.4,1.2-0.4,1.6,0c0.2,0.2,0.4,0.5,0.4,0.8S10.4,6,10.2,6.2C10,6.4,9.7,6.5,9.4,6.5
									c-0.3,0-0.6-0.1-0.8-0.4C8.3,6,8.2,5.7,8.2,5.4S8.3,4.8,8.6,4.6z M4.3,4.6c0.4-0.4,1.2-0.4,1.6,0c0.2,0.2,0.4,0.5,0.4,0.8
									S6.1,6,5.9,6.2C5.7,6.4,5.4,6.5,5.1,6.5S4.5,6.4,4.3,6.2C4,6,3.9,5.7,3.9,5.4C3.9,5.1,4,4.8,4.3,4.6z"/>
								<path fill="#fff" style="opacity:.45" d="M30,34c0-8.6,7-15.5,15.5-15.5c1.9,0,3.7,0.4,5.4,1v-6.7H0v28.9c0,1.5,1.2,2.6,2.6,2.6H34C31.5,41.7,30,38,30,34z"/>
								<path fill="#fff" style="opacity:.45" d="M45.5,21.5C38.6,21.5,33,27.1,33,34s5.6,12.5,12.5,12.5C52.4,46.6,58,41,58,34S52.4,21.5,45.5,21.5z M52.3,30.7l-7.2,8.8h0
									c-0.3,0.4-0.8,0.6-1.3,0.6c-0.5,0-0.9-0.2-1.2-0.5l0,0l-3.9-4.2l0,0c-0.3-0.3-0.4-0.7-0.4-1.1c0-0.9,0.7-1.7,1.7-1.7
									c0.5,0,0.9,0.2,1.2,0.5l0,0l2.6,2.8l6-7.3l0,0C50,28.2,50.5,28,51,28c0.9,0,1.7,0.7,1.7,1.7C52.7,30,52.5,30.4,52.3,30.7L52.3,30.7z
									"/>
								</svg>';

				add_menu_page(
					esc_html__( 'LearnDash LMS', 'learndash' ),
					esc_html__( 'LearnDash LMS', 'learndash' ),
					'read',
					'learndash-lms',
					null,
					'data:image/svg+xml;base64,' . base64_encode( $menu_icon ),
					$menu_position
				);

				$location = 0;

				foreach ( $add_submenu as $key => $add_submenu_item ) {
					if ( current_user_can( $add_submenu_item['cap'] ) ) {
						$_tmp_menu_item = array( $add_submenu_item['name'], $add_submenu_item['cap'], $add_submenu_item['link'] );
						if ( ( isset( $add_submenu_item['class'] ) ) && ( ! empty( $add_submenu_item['class'] ) ) ) {
							$_tmp_menu_item[4] = $add_submenu_item['class'];
						}
						$submenu['learndash-lms'][ $location++ ] = $_tmp_menu_item;
					}
				}

				/**
				 * Action added to trigger add-ons when LD menu and submenu items have been added to the system.
				 * This works better than trying to fiddle with priority on WP 'admin_menu' hook.
				*
				* @since 2.4.0
				*
				* @param  string LD menu parent slug 'learndash-lms'.
				*/
				do_action( 'learndash_admin_menu', 'learndash-lms' );
			}

			global $learndash_post_types;
			foreach ( $learndash_post_types as $ld_post_type ) {
				$menu_slug = 'edit.php?post_type=' . $ld_post_type;
				if ( isset( $submenu[ $menu_slug ] ) ) {
					remove_menu_page( $menu_slug );
				}
			}
		}

		public function learndash_admin_menu_last() {
			global $submenu, $menu, $_wp_real_parent_file, $_wp_submenu_nopriv, $_registered_pages, $_parent_pages;
			$_parent_file = get_admin_page_parent();
			$add_submenu  = array();

			if ( ( isset( $submenu['learndash-lms-non-existant'] ) ) && ( ! empty( $submenu['learndash-lms-non-existant'] ) ) ) {
				foreach ( $submenu['learndash-lms-non-existant'] as $submenu_idx => $submenu_item ) {
					if ( isset( $_parent_pages[ $submenu_item[2] ] ) ) {
						$_parent_pages[ $submenu_item[2] ] = 'admin.php?page=learndash_lms_settings';

						$submenu['admin.php?page=learndash_lms_settings'][] = $submenu_item;
					}
				}
			}

			/**
			 * Allow add-ons and other LD core menus to be added to the bottom of the sub-menu.
			 *
			 * @since 2.5.6
			 */
			$add_submenu = apply_filters( 'learndash_submenu_last', $add_submenu );

			$add_submenu['settings'] = array(
				'name' => esc_html_x( 'Settings', 'Settings Menu Label', 'learndash' ),
				'cap'  => LEARNDASH_ADMIN_CAPABILITY_CHECK,
				'link' => 'admin.php?page=learndash_lms_settings',
			);

			foreach ( $add_submenu as $key => $add_submenu_item ) {
				if ( current_user_can( $add_submenu_item['cap'] ) ) {
					$submenu['learndash-lms'][] = array( $add_submenu_item['name'], $add_submenu_item['cap'], $add_submenu_item['link'] );
				}
			}
		}

		/**
		 * Set up admin tabs for each admin menu page under LearnDash
		 *
		 * @since 2.1.0
		 */
		public function learndash_admin_tabs() {
			if ( ! is_admin() ) {
				return;
			}
			global $submenu, $menu, $parent_file;
			global $learndash_current_page_link;
			$learndash_current_page_link = '';

			$current_screen  = get_current_screen();
			$current_page_id = $current_screen->id;

			//$current_screen_parent_file = $current_screen->parent_file;
			if ( $parent_file ) {
				$current_screen_parent_file = $parent_file;
			} else {
				$current_screen_parent_file = $current_screen->parent_file;
			}

			if ( 'learndash-lms' === $current_screen_parent_file ) {
				if ( 'learndash-lms_page_learndash-lms-reports' === $current_screen->id ) {
					$current_screen_parent_file = 'admin.php?page=learndash-lms-reports';
				}
				/**
				 * The above IF should work. However what we are seeing in LEARNDASH-3661 is
				 * due to the translation of 'LearnDash LMS' the screen ID gets changed by WP
				 * to something like 'lms-learndash_page_learndash-lms-reports' in the French
				 * or something entirely different in other languages. So we add a secondary
				 * check on the 'page' query string param.
				 * @since 3.0.7
				 */
				else if ( ( isset( $_GET['page'] ) ) && ( 'learndash-lms-reports' === $_GET['page'] ) ) {
					$current_screen_parent_file = 'admin.php?page=learndash-lms-reports';
				}

				// See LEARNDASH-581:
				// In a normal case when viewing the LearnDash > Courses > All Courses tab the screen ID is set to 'edit-sfwd-courses' and the parent_file is set ''edit.php?post_type=sfwd-courses'.
				// However when the Admin Menu Editor plugin is installed it somehow sets the parent_file to 'learndash-lms'. So below we need to change the value back. Note this is just for the
				// listing URL. The Add New and other tabs are not effected.
				if ( 'edit-sfwd-courses' === $current_screen->id ) {
					$current_screen_parent_file = 'edit.php?post_type=sfwd-courses';
				}

				if ( 'edit-sfwd-lessons' === $current_screen->id ) {
					$current_screen_parent_file = 'edit.php?post_type=sfwd-lessons';
				}

				if ( 'edit-sfwd-topic' === $current_screen->id ) {
					$current_screen_parent_file = 'edit.php?post_type=sfwd-topic';
				}

				if ( 'edit-sfwd-quiz' === $current_screen->id ) {
					$current_screen_parent_file = 'edit.php?post_type=sfwd-quiz';
				}

				if ( 'edit-sfwd-question' === $current_screen->id ) {
					$current_screen_parent_file = 'edit.php?post_type=sfwd-question';
				}

				if ( 'edit-sfwd-certificates' === $current_screen->id ) {
					$current_screen_parent_file = 'edit.php?post_type=sfwd-certificates';
				}

				if ( 'edit-groups' === $current_screen->id ) {
					$current_screen_parent_file = 'edit.php?post_type=groups';
				}

				if ( 'edit-sfwd-assignment' === $current_screen->id ) {
					$current_screen_parent_file = 'edit.php?post_type=sfwd-assignment';
				}

				if ( learndash_is_group_leader_user() ) {
					if ( 'edit-sfwd-essays' === $current_screen->id ) {
						$current_screen_parent_file = 'edit.php?post_type=sfwd-essays';
					}
				}
			}

			if ( ( 'edit.php?post_type=sfwd-quiz' === $current_screen_parent_file ) || ( 'edit.php?post_type=sfwd-essays' === $current_screen_parent_file ) ) {
				$post_id = ! empty( $_GET['post_id'] ) ? $_GET['post_id'] : ( empty( $_GET['post'] ) ? 0 : $_GET['post'] );

				if ( ! empty( $_GET['module'] ) ) {
					$current_page_id = $current_page_id . '_' . esc_attr( $_GET['module'] );
				} elseif ( ! empty( $post_id ) ) {
					$current_page_id = $current_page_id . '_edit';
				}

				if ( learndash_is_admin_user() ) {
					$menu_user_cap = LEARNDASH_ADMIN_CAPABILITY_CHECK;
					$menu_parent   = 'edit.php?post_type=sfwd-quiz';
				} elseif ( learndash_is_group_leader_user() ) {
					$menu_user_cap = LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK;
					$menu_parent   = 'learndash-lms';
				}
				$this->add_admin_tab_item(
					'edit.php?post_type=sfwd-quiz',
					array(
						'link'             => 'edit.php?post_type=sfwd-essays',
						'name'             => esc_html_x( 'Submitted Essays', 'Quiz Submitted Essays Tab Label', 'learndash' ),
						'id'               => 'edit-sfwd-essays',
						'cap'              => $menu_user_cap,
						'parent_menu_link' => $menu_parent,
					),
					$this->admin_tab_priorities['normal']
				);
			}

			// Somewhat of a kludge. The essays are shown within the quiz post type menu section. So we can't just use
			// the default logic. But we can (below) copy the quiz tab items to a new tab set for essays.
			if ( 'edit.php?post_type=sfwd-essays' === $current_screen_parent_file ) {
				if ( 'admin.php?page=learndash_lms_settings' !== $current_screen_parent_file ) {
					do_action( 'learndash_admin_tabs_set', $current_screen_parent_file, $this );
				}

				$post_id = ! empty( $_GET['post_id'] ) ? $_GET['post_id'] : ( empty( $_GET['post'] ) ? 0 : $_GET['post'] );
				if ( ! empty( $post_id ) ) {
					$current_page_id = 'edit-sfwd-essays'; //. $current_page_id;
				}

				$this->admin_tab_sets['edit.php?post_type=sfwd-essays'] = array();

				foreach ( $this->admin_tab_sets['edit.php?post_type=sfwd-quiz'] as $menu_key => $menu_item ) {
					$this->admin_tab_sets['edit.php?post_type=sfwd-essays'][ $menu_key ] = $menu_item;
				}
			}

			if ( 'edit.php?post_type=sfwd-quiz' === $current_screen_parent_file ) {

				if ( ( empty( $post_id ) ) && ( ! empty( $_GET['quiz_id'] ) ) && ( 'admin_page_ldAdvQuiz' === $current_page_id ) ) {
					$post_id = learndash_get_quiz_id_by_pro_quiz_id( $_GET['quiz_id'] );
				}

				if ( ! empty( $post_id ) ) {
					$quiz_id = learndash_get_setting( $post_id, 'quiz_pro', true );
					if ( ! empty( $quiz_id ) ) {

						$this->add_admin_tab_item(
							$current_screen->parent_file,
							array(
								'link' => 'post.php?post=' . $post_id . '&action=edit',
								'name' => sprintf(
									// translators: placeholder: Edit Quiz Label.
									esc_html_x( 'Edit %s', 'Edit Quiz Label', 'learndash' ),
									LearnDash_Custom_Label::get_label( 'quiz' )
								),
								'id'   => 'sfwd-quiz_edit',
							),
							$this->admin_tab_priorities['misc']
						);

						if ( ( true === is_data_upgrade_quiz_questions_updated() ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ) {
							$question_tab_url = add_query_arg(
								array(
									'post_type' => learndash_get_post_type_slug( 'question' ),
									'quiz_id'   => $post_id,
								),
								'edit.php'
							);
						} else {
							$question_tab_url = add_query_arg(
								array(
									'page'    => 'ldAdvQuiz',
									'module'  => 'question',
									'quiz_id' => $quiz_id,
									'post_id' => $post_id,
								),
								'admin.php'
							);
						}

						$this->add_admin_tab_item(
							$current_screen->parent_file,
							array(
								'link' => 'admin.php?page=ldAdvQuiz&module=statistics&id=' . $quiz_id . '&post_id=' . $post_id,
								'name' => esc_html_x( 'Statistics', 'Quiz Statistics Tab Label', 'learndash' ),
								'id'   => 'sfwd-quiz_page_ldAdvQuiz_statistics',
							),
							$this->admin_tab_priorities['misc']
						);

						$this->add_admin_tab_item(
							$current_screen->parent_file,
							array(
								'link' => 'admin.php?page=ldAdvQuiz&module=toplist&id=' . $quiz_id . '&post_id=' . $post_id,
								'name' => esc_html_x( 'Leaderboard', 'Quiz Leaderboard Tab Label', 'learndash' ),
								'id'   => 'sfwd-quiz_page_ldAdvQuiz_toplist',
							),
							$this->admin_tab_priorities['misc']
						);
					}
				}
			}

			if ( ( 'admin.php?page=learndash-lms-reports' === $current_screen_parent_file ) || ( 'edit.php?post_type=sfwd-transactions' === $current_screen_parent_file ) ) {

				$this->add_admin_tab_item(
					$current_screen_parent_file,
					array(
						'id'   => 'learndash-lms_page_learndash-lms-reports',
						'name' => esc_html_x( 'Reports', 'Reports Menu Label', 'learndash' ),
						'link' => 'admin.php?page=learndash-lms-reports',
						'cap'  => LEARNDASH_ADMIN_CAPABILITY_CHECK,
					),
					$this->admin_tab_priorities['high']
				);

				$this->add_admin_tab_item(
					$current_screen_parent_file,
					array(
						'id'               => 'edit-sfwd-transactions',
						'name'             => esc_html_x( 'Transactions', 'Transactions Tab Label', 'learndash' ),
						'link'             => 'edit.php?post_type=sfwd-transactions',
						'parent_menu_link' => 'admin.php?page=learndash-lms-reports',
					),
					$this->admin_tab_priorities['high']
				);

				if ( 'edit.php?post_type=sfwd-transactions' === $current_screen_parent_file ) {
					$post_id = ! empty( $_GET['post_id'] ) ? $_GET['post_id'] : ( empty( $_GET['post'] ) ? 0 : $_GET['post'] );
					if ( ! empty( $post_id ) ) {
						$current_page_id = 'edit-sfwd-transactions';
					}
				}
			}

			if ( 'edit.php?post_type=groups' === $current_screen_parent_file ) {

				if ( learndash_is_admin_user() ) {
					$user_group_ids = learndash_get_administrators_group_ids( get_current_user_id(), true );
					if ( ! empty( $user_group_ids ) ) {

						$this->add_admin_tab_item(
							$current_screen_parent_file,
							array(
								'id'   => 'groups_page_group_admin_page',
								'name' => esc_html__( 'Group Administration', 'learndash' ),
								'link' => 'admin.php?page=group_admin_page',
								'cap'  => LEARNDASH_ADMIN_CAPABILITY_CHECK,
							),
							$this->admin_tab_priorities['high']
						);
					}
				}
			}

			if ( 'learndash-lms_page_group_admin_page' === $current_screen->id ) {

				$this->add_admin_tab_item(
					$current_screen_parent_file,
					array(
						'id'   => 'learndash-lms_page_group_admin_page',
						'name' => esc_html__( 'Group Administration', 'learndash' ),
						'link' => 'admin.php?page=group_admin_page',
						'cap'  => LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK,
					),
					$this->admin_tab_priorities['high']
				);
			}

			$admin_tabs_legacy = apply_filters( 'learndash_admin_tabs', array() );
			foreach ( $admin_tabs_legacy as $tab_idx => $tab_item ) {
				if ( empty( $tab_item ) ) {
					unset( $admin_tabs_legacy[ $tab_idx ] );
				} else {
					if ( 'edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses' === $admin_tabs_legacy[ $tab_idx ]['menu_link'] ) {
						$admin_tabs_legacy[ $tab_idx ]['menu_link'] = 'admin.php?page=learndash_lms_settings';
					}
				}
			}

			if ( 'learndash-lms-non-existant' === $current_screen_parent_file ) {
				$menu_link = '';
				foreach ( $admin_tabs_legacy as $tab_idx => $tab_item ) {
					if ( $tab_item['id'] === $current_page_id ) {
						$current_screen_parent_file = $tab_item['menu_link'];
						break;
					}
				}
			}

			if ( 'admin.php?page=learndash_lms_settings' === $current_screen_parent_file ) {

				$this->add_admin_tab_item(
					'admin.php?page=learndash_lms_settings',
					array(
						'link' => 'admin.php?page=nss_plugin_license-sfwd_lms-settings',
						'name' => esc_html_x( 'LMS License', 'LMS License Tab Label', 'learndash' ),
						'id'   => 'admin_page_nss_plugin_license-sfwd_lms-settings',
					),
					50
				);

				do_action( 'learndash_admin_tabs_set', $current_screen_parent_file, $this );

				// Here we add the legacy tabs to the end of the existing tabs.
				if ( ! empty( $admin_tabs_legacy ) ) {
					foreach ( $admin_tabs_legacy as $tab_idx => $tab_item ) {
						if ( $tab_item['menu_link'] === $current_screen_parent_file ) {
							$this->add_admin_tab_item(
								$current_screen_parent_file,
								$tab_item,
								80
							);
						}
					}
				}
			}

			if ( ( 'edit.php?post_type=sfwd-essays' !== $current_screen_parent_file ) && ( 'admin.php?page=learndash_lms_settings' !== $current_screen_parent_file ) ) {
				do_action( 'learndash_admin_tabs_set', $current_screen_parent_file, $this );
			}

			$admin_tabs_on_page_legacy = array();
			$admin_tabs_on_page_legacy['sfwd-courses_page_sfwd-lms_sfwd_lms_post_type_sfwd-courses'] = array();

			$admin_tabs_on_page_legacy = apply_filters( 'learndash_admin_tabs_on_page', $admin_tabs_on_page_legacy, array(), $current_page_id );
			foreach ( $admin_tabs_on_page_legacy as $tab_idx => $tab_set ) {
				if ( empty( $tab_set ) ) {
					unset( $admin_tabs_on_page_legacy[ $tab_idx ] );
				}
			}

			if ( isset( $admin_tabs_on_page_legacy[ $current_page_id ] ) ) {
				$admin_tabs_on_page_legacy_set = $admin_tabs_on_page_legacy[ $current_page_id ];
				if ( ( ! empty( $admin_tabs_on_page_legacy_set ) ) && ( is_array( $admin_tabs_on_page_legacy_set ) ) ) {
					foreach ( $admin_tabs_on_page_legacy_set as $admin_tab_idx ) {
						if ( isset( $admin_tabs_legacy[ $admin_tab_idx ] ) ) {
							$admin_tab_item             = $admin_tabs_legacy[ $admin_tab_idx ];
							$current_screen_parent_file = $admin_tab_item['menu_link'];
							$this->add_admin_tab_item(
								$admin_tab_item['menu_link'],
								$admin_tab_item,
								80
							);
							unset( $admin_tabs_legacy[ $admin_tab_idx ] );
						}
						unset( $admin_tabs_on_page_legacy_set[ $admin_tab_idx ] );
					}
				}
			}

			// Get tabs data to new tabs system.
			$this->show_admin_tabs( $current_screen_parent_file, $current_page_id );
		}

		/**
		 * Get admin tabs data to new tabs system.
		 *
		 * @param string $menu_tab_key    The menu tab key.
		 * @param string $current_page_id The current page id.
		 * @return array
		 */
		public function get_admin_tabs( $menu_tab_key = '', $current_page_id = '' ) {

			if ( isset( $this->admin_tab_sets[ $menu_tab_key ] ) ) {

				if ( ! empty( $this->admin_tab_sets[ $menu_tab_key ] ) ) {

					ksort( $this->admin_tab_sets[ $menu_tab_key ] );

					/**
					 * Filter for current admin tab set.
					 *
					 * @since 3.0
					 */

					$this->admin_tab_sets[ $menu_tab_key ] = apply_filters( 'learndash_admin_tab_sets', $this->admin_tab_sets[ $menu_tab_key ], $menu_tab_key, $current_page_id );

					if ( ! empty( $this->admin_tab_sets[ $menu_tab_key ] ) ) {
						global $learndash_current_page_link;
						if ( ( isset( $admin_tab_item['parent_menu_link'] ) ) && ( ! empty( $admin_tab_item['parent_menu_link'] ) ) ) {
							$learndash_current_page_link = trim( $admin_tab_item['parent_menu_link'] );
						} else {
							$learndash_current_page_link = $menu_tab_key;
						}
						add_action( 'admin_footer', 'learndash_select_menu' );

						return $this->admin_tab_sets[ $menu_tab_key ];
					}
				}
			}

			return [];
		}

		public function show_admin_tabs( $menu_tab_key = '', $current_page_id = '' ) {

			/**
			 * Control if admin tabs should be displayed.
			 *
			 * @param array $flag Defines if tabs should be displayed.
			 */
			if ( isset( $this->admin_tab_sets[ $menu_tab_key ] ) ) {

				if ( ! empty( $this->admin_tab_sets[ $menu_tab_key ] ) ) {

					ksort( $this->admin_tab_sets[ $menu_tab_key ] );

					/**
					 * Filter for current admin tab set
					 * @since 2.5
					 */
					$this->admin_tab_sets[ $menu_tab_key ] = apply_filters( 'learndash_admin_tab_sets', $this->admin_tab_sets[ $menu_tab_key ], $menu_tab_key, $current_page_id );
					if ( ! empty( $this->admin_tab_sets[ $menu_tab_key ] ) ) {
						global $learndash_current_page_link;
						if ( ( isset( $admin_tab_item['parent_menu_link'] ) ) && ( ! empty( $admin_tab_item['parent_menu_link'] ) ) ) {
							$learndash_current_page_link = trim( $admin_tab_item['parent_menu_link'] );
						} else {
							if ( 'edit.php?post_type=sfwd-essays' === $menu_tab_key ) {
								if ( true === learndash_is_group_leader_user() ) {
									// Nothing here.
								} else {
									$learndash_current_page_link = 'edit.php?post_type=sfwd-quiz';
								}
							} elseif ( 'edit.php?post_type=sfwd-transactions' === $menu_tab_key ) {
								$learndash_current_page_link = 'admin.php?page=learndash-lms-reports';
							} else {
								$learndash_current_page_link = $menu_tab_key;
							}
						}
						add_action( 'admin_footer', 'learndash_select_menu' );

						if ( ( defined( 'LEARNDASH_SETTINGS_HEADER_PANEL' ) ) && ( true === apply_filters( 'learndash_settings_header_panel', LEARNDASH_SETTINGS_HEADER_PANEL ) ) ) {
							$this->admin_header_panel( $menu_tab_key );

						} else {
							echo '<h1 class="nav-tab-wrapper">';

							$post_id = ! empty( $_GET['post_id'] ) ? $_GET['post_id'] : ( empty( $_GET['post'] ) ? 0 : $_GET['post'] );

							foreach ( $this->admin_tab_sets[ $menu_tab_key ] as $admin_tab_item ) {
								if ( ! isset( $admin_tab_item['id'] ) ) {
									$admin_tab_item['id'] = '';
								}

								if ( ! empty( $admin_tab_item['id'] ) ) {

									if ( $admin_tab_item['id'] == $current_page_id ) {
										$class = 'nav-tab nav-tab-active';

										global $learndash_current_page_link;
										if ( ( isset( $admin_tab_item['parent_menu_link'] ) ) && ( ! empty( $admin_tab_item['parent_menu_link'] ) ) ) {
											$learndash_current_page_link = trim( $admin_tab_item['parent_menu_link'] );
										} else {
											$learndash_current_page_link = $menu_tab_key;
										}

										add_action( 'admin_footer', 'learndash_select_menu' );

									} else {
										$class = 'nav-tab';
									}

									$target = ! empty( $admin_tab_item['target'] ) ? 'target="' . $admin_tab_item['target'] . '"' : '';

									$url = '';
									if ( ( isset( $admin_tab_item['external_link'] ) ) && ( ! empty( $admin_tab_item['external_link'] ) ) ) {
										$url = $admin_tab_item['external_link'];
									} elseif ( ( isset( $admin_tab_item['link'] ) ) && ( ! empty( $admin_tab_item['link'] ) ) ) {
										$url = $admin_tab_item['link'];

									} else {
										if ( false !== ( $pos = strpos( $admin_tab_item['id'], 'learndash-lms_page_' ) ) ) {
											$url_page = str_replace( 'learndash-lms_page_', '', $admin_tab_item['id'] );
											$url      = add_query_arg( array( 'page' => $url_page ), 'admin.php' );
										}
									}

									if ( ! empty( $url ) ) {
										echo '<a href="' . $url . '" class="' . $class . ' nav-tab-' . $admin_tab_item['id'] . '"  ' . $target . '>' . $admin_tab_item['name'] . '</a>';
									}
								}
							}
							echo '</h1>';
						}
					}
				}
			}
		}

		/**
		 * Show the new Admin header panel
		 *
		 * @since 3.0
		 * @param string $menu_tab_key Current tab key to show.
		 */
		protected function admin_header_panel( $menu_tab_key = '' ) {
			global $pagenow, $post, $typenow;
			global $learndash_assets_loaded;
			global $learndash_metaboxes;

			if ( ( empty( $menu_tab_key ) ) || ( ! isset( $this->admin_tab_sets[ $menu_tab_key ] ) ) || ( empty( $this->admin_tab_sets[ $menu_tab_key ] ) ) ) {
				return;
			}

			$screen = get_current_screen();

			$header_data = array(
				'tabs'           => array(),
				'currentTab'     => $screen->id,
				'editing'        => 1,
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'adminurl'       => admin_url( 'edit.php' ),
				'quizImportUrl'  => admin_url( 'admin.php?page=ldAdvQuiz' ),
				'postadminurl'   => admin_url( 'post.php' ),
				'back_to_title'  => '',
				'back_to_url'    => '',
				'error_messages' => array(
					'builder' => esc_html__( 'There was an unexpected error while loading. Please try refreshing the page. If the error continues, contact LearnDash support.', 'learndash' ),
					'header'  => esc_html__( 'There was an unexpected error while loading. Please try refreshing the page. If the error continues, contact LearnDash support.', 'learndash' ),
				),
				'labels'         => array(
					'section-heading'     => esc_html__( 'Section Heading', 'learndash' ),
					'section-headings'    => esc_html__( 'Section Headings', 'learndash' ),
					'answer'              => esc_html__( 'answer', 'learndash' ),
					'answers'             => esc_html__( 'answers', 'learndash' ),
					'course'              => \LearnDash_Custom_Label::get_label( 'course' ),
					'courses'             => \LearnDash_Custom_Label::get_label( 'courses' ),
					'lesson'              => \LearnDash_Custom_Label::get_label( 'lesson' ),
					'lessons'             => \LearnDash_Custom_Label::get_label( 'lessons' ),
					'topic'               => \LearnDash_Custom_Label::get_label( 'topic' ),
					'topics'              => \LearnDash_Custom_Label::get_label( 'topics' ),
					'quiz'                => \LearnDash_Custom_Label::get_label( 'quiz' ),
					'quizzes'             => \LearnDash_Custom_Label::get_label( 'quizzes' ),
					'question'            => \LearnDash_Custom_Label::get_label( 'question' ),
					'questions'           => \LearnDash_Custom_Label::get_label( 'questions' ),
					'sfwd-course'         => \LearnDash_Custom_Label::get_label( 'course' ),
					'sfwd-courses'        => \LearnDash_Custom_Label::get_label( 'courses' ),
					'sfwd-lesson'         => \LearnDash_Custom_Label::get_label( 'lesson' ),
					'sfwd-lessons'        => \LearnDash_Custom_Label::get_label( 'lessons' ),
					'sfwd-topic'          => \LearnDash_Custom_Label::get_label( 'topic' ),
					'sfwd-topics'         => \LearnDash_Custom_Label::get_label( 'topics' ),
					'sfwd-quiz'           => \LearnDash_Custom_Label::get_label( 'quiz' ),
					'sfwd-quizzes'        => \LearnDash_Custom_Label::get_label( 'quizzes' ),
					'sfwd-courses'        => \LearnDash_Custom_Label::get_label( 'courses' ),
					'sfwd-question'       => \LearnDash_Custom_Label::get_label( 'question' ),
					'start-adding-lesson' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Start by adding a %s.', 'placeholder: Lesson', 'learndash' ),
						\LearnDash_Custom_Label::get_label( 'lesson' )
					),
				),
				'sfwdMap'        => array(
					'lesson'   => 'sfwd-lessons',
					'topic'    => 'sfwd-topic',
					'quiz'     => 'sfwd-quiz',
					'question' => 'sfwd-question',
				),
				'rest'           => array(
					'namespace' => LEARNDASH_REST_API_NAMESPACE . '/v1',
					'base'      => array(
						'lessons'  => \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'sfwd-lessons' ),
						'topic'    => \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'sfwd-topic' ),
						'quiz'     => \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'sfwd-quiz' ),
						'question' => 'sfwd-questions',
					),
					'root'      => esc_url_raw( rest_url() ),
					'nonce'     => wp_create_nonce( 'wp_rest' ),
				),
				'post_data'      => array(
					'builder_post_id'    => 0,
					'builder_post_title' => '',
					'builder_post_type'  => '',
				),
				'posts_per_page' => 0,
				'lessons'        => array(),
				'topics'         => array(),
				'quizzes'        => array(),
				'questions'      => array(),
				'i18n'           => array(
					'back_to'                            => esc_html_x( 'Back to', 'Link back to the post type overview', 'learndash' ),
					'actions'                            => esc_html_x( 'Actions', 'Builder actions dropdown', 'learndash' ),
					'expand'                             => esc_html_x( 'Expand All', 'Builder elements', 'learndash' ),
					'collapse'                           => esc_html_x( 'Collapse All', 'Builder elements', 'learndash' ),
					'error'                              => esc_html__( 'An error occurred while submitting your request. Please try again.', 'learndash' ),
					'cancel'                             => esc_html__( 'Cancel', 'learndash' ),
					'edit'                               => esc_html__( 'Edit', 'learndash' ),
					'remove'                             => esc_html__( 'Remove', 'learndash' ),
					'save'                               => esc_html__( 'Save', 'learndash' ),
					'settings'                           => esc_html__( 'Settings', 'learndash' ),
					'edit_question'                      => esc_html__( 'Click here to edit the question', 'learndash' ),
					'correct_answer_message'             => esc_html__( 'Message for correct answer - optional', 'learndash' ),
					'different_incorrect_answer_message' => esc_html__( 'Use different message for incorrect answer', 'learndash' ),
					'same_answer_message'                => esc_html__( 'Currently same message is displayed as above.', 'learndash' ),
					'incorrect_answer_message'           => esc_html__( 'Message for incorrect answer - optional', 'learndash' ),
					'solution_hint'                      => esc_html__( 'Solution hint', 'learndash' ),
					'points'                             => esc_html__( 'points', 'learndash' ),
					'edit_answer'                        => esc_html__( 'Click here to edit the answer', 'learndash' ),
					'update_answer'                      => esc_html__( 'Update Answer', 'learndash' ),
					'answer_missing'                     => esc_html__( 'Answer is missing', 'learndash' ),
					'allow_html'                         => esc_html__( 'Allow HTML', 'learndash' ),
					'correct'                            => esc_html__( 'Correct', 'learndash' ),
					'correct_1st'                        => wp_kses_post( _x( '1<sup>st</sup>', 'First sort answer correct', 'learndash' ) ),
					'correct_2nd'                        => wp_kses_post( _x( '2<sup>nd</sup>', 'Second sort answer correct', 'learndash' ) ),
					'correct_3rd'                        => wp_kses_post( _x( '3<sup>rd</sup>', 'Third sort answer correct', 'learndash' ) ),
					'correct_nth'                        => wp_kses_post( _x( '<sup>th</sup>', 'nth sort answer correct', 'learndash' ) ),
					'answer_updated'                     => esc_html__( 'Answer updated', 'learndash' ),
					'edit_answer_settings'               => esc_html__( 'Edit answer settings', 'learndash' ),
					'answer'                             => esc_html__( 'Answer:', 'learndash' ),
					'edit_matrix'                        => esc_html__( 'Click here to edit the matrix', 'learndash' ),
					'new_element_labels'                 => array(
						'question'        => sprintf(
							/* translators: placeholders: Question */
							esc_html_x( 'New %1$s', 'placeholder: Question', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'quiz'            => sprintf(
							/* translators: placeholders: Quiz */
							esc_html_x( 'New %1$s', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'topic'           => sprintf(
							/* translators: placeholders: Topic */
							esc_html_x( 'New %1$s', 'placeholder: Topic', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topic' )
						),
						'lesson'          => sprintf(
							/* translators: placeholders: Lesson */
							esc_html_x( 'New %1$s', 'placeholder: Lesson', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lesson' )
						),
						'answer'          => esc_html__( 'New answer', 'learndash' ),
						'section-heading' => esc_html__( 'New Section Heading', 'learndash' ),
					),
					'enter_title'                        => esc_html_x( 'Enter a title', 'Title for the new course, lesson, quiz', 'learndash' ),
					'enter_answer'                       => esc_html_x( 'Enter an answer', 'Answer for a question', 'learndash' ),
					'please_wait'                        => esc_html_x( 'Please wait...', 'Please wait while the form is loading', 'learndash' ),
					'add_element'                        => esc_html_x( 'Add', 'Add lesson, topic, quiz...', 'learndash' ),
					'add_element_labels'                 => array(
						'question'        => sprintf(
							/* translators: placeholders: Question */
							esc_html_x( 'Add %1$s', 'placeholder: Question', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'question' )
						),
						'questions'       => sprintf(
							/* translators: placeholders: Question */
							esc_html_x( 'Add %1$s', 'placeholder: Questions', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'questions' )
						),
						'quiz'            => sprintf(
							/* translators: placeholders: Quiz */
							esc_html_x( 'Add %1$s', 'placeholder: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'topic'           => sprintf(
							/* translators: placeholders: Topic */
							esc_html_x( 'Add %1$s', 'placeholder: Topic', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topic' )
						),
						'lesson'          => sprintf(
							/* translators: placeholders: Lesson */
							esc_html_x( 'Add %1$s', 'placeholder: Lesson', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lesson' )
						),
						'answer'          => esc_html__( 'Add answer', 'learndash' ),
						'section-heading' => esc_html__( 'Add Section Heading', 'learndash' ),
					),
					'move_up'                            => esc_html_x( 'Move up', 'Move the current element up in the builder interface', 'learndash' ),
					'question_empty'                     => sprintf(
						/* translators: placeholders: question */
						esc_html_x( 'The %s is empty.', 'Warning when no question was entered', 'learndash' ),
						learndash_get_custom_label_lower( 'question' )
					),
					'move_down'                          => esc_html_x( 'Move down', 'Move the current element down in the builder interface', 'learndash' ),
					'rename'                             => esc_html_x( 'Rename', 'Rename the current element in the builder interface', 'learndash' ),
					'search_element_labels'              => array(
						'lesson'  => sprintf(
							/* translators: placeholders: lessons */
							esc_html_x( 'Search %1$s', 'placeholders: lessons', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lessons' )
						),
						'quiz'  => sprintf(
							/* translators: placeholders: quizzes */
							esc_html_x( 'Search %1$s', 'placeholders: quizzes', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quizzes' )
						),
						'topic'  => sprintf(
							/* translators: placeholders: topics */
							esc_html_x( 'Search %1$s', 'placeholders: topics', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topics' )
						),
						'question'  => sprintf(
							/* translators: placeholders: questions */
							esc_html_x( 'Search %1$s', 'placeholders: questions', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'questions' )
						),
					),
					'recent'                             => esc_html_x( 'Recent', 'List of recent lessons, topics, quizzes or questions', 'learndash' ),
					'view_all'                           => esc_html_x( 'View all', 'Lesson, Topic, Quiz or Question posts', 'learndash' ),
					'start_adding_element_labels'        => array(
						'lesson' => sprintf(
							/* translators: placeholders: Lesson*/
							esc_html_x( 'Start adding your first %1$s', 'placeholders: Lesson', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lesson' )
						),
						'quiz'   => sprintf(
							/* translators: placeholders: Quiz*/
							esc_html_x( 'Start adding your first %1$s', 'placeholders: Quiz', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'topic'  => sprintf(
							/* translators: placeholders: Topic*/
							esc_html_x( 'Start adding your first %1$s', 'placeholders: Topic', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topic' )
						),
						'question'  => sprintf(
							/* translators: placeholders: Question*/
							esc_html_x( 'Start adding your first %1$s', 'placeholders: Question', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'question' )
						),
					),
					'all_elements_added_labels'          => array(
						'lesson' => sprintf(
							/* translators: placeholders: Lessons*/
							esc_html_x( 'All available %1$s have been added.', 'placeholders: Lessons', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lessons' )
						),
						'quiz' => sprintf(
							/* translators: placeholders: Quizzes */
							esc_html_x( 'All available %1$s have been added.', 'placeholders: Quizzes', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quizzes' )
						),
						'topic'  => sprintf(
							/* translators: placeholders: Topics */
							esc_html_x( 'All available %1$s have been added.', 'placeholders: Topics', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topics' )
						),
						'question'  => sprintf(
							/* translators: placeholders: Questions */
							esc_html_x( 'All available %1$s have been added.', 'placeholders: Questions', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'questions' )
						),
					),
					'start_adding'                       => esc_html_x( 'Start adding your first', 'Lesson, Topic, Quiz or Question', 'learndash' ),
					'refresh'                            => esc_html_x( 'Refresh', 'Builder - Refresh list of  Lessons, Topics, Quizzes or Questions', 'learndash' ),
					'load_more'                          => esc_html_x( 'Load More', 'Builder - Load more Lessons, Topics, Quizzes or Questions', 'learndash' ),
					'add_selected'                       => esc_html_x( 'Add Selected', 'Builder - Add selected Lessons, Topics, Quizzes or Questions', 'learndash' ),
					'undo'                               => esc_html_x( 'Undo', 'Undo action in the builder', 'learndash' ),
					'criterion'                          => esc_html_x( 'Criterion', 'Matrix answer Criteroion', 'learndash' ),
					'sort_element'                       => esc_html_x( 'Sort element', 'Sort matrix answer element', 'learndash' ),
					'question_settings'                  => esc_html_x( 'Settings', 'Question settings. Placeholder in JavaScript', 'learndash' ),
					'select_option'                      => esc_html_x( 'Select', 'Select an option', 'learndash' ),
					'nothing_found'                      => esc_html_x( 'Nothing matches your search', 'No matching Lesson, Topic, Quiz or Question found', 'learndash' ),
					'drop_lessons'                       => sprintf(
						/* translators: placeholders: Lessons */
						esc_html_x( 'Drop %1$s here', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'drop_question'                      => sprintf(
						/* translators: placeholders: Question */
						esc_html_x( 'Drop %1$s here', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'drop_quizzes'                       => sprintf(
						/* translators: placeholders: Quizzes */
						esc_html_x( 'Drop %1$s here', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quizzes' )
					),
					'drop_quizzes_topics'                => sprintf(
						/* translators: placeholders: %1$s: Topics, %2$s: Quizzes */
						esc_html_x( 'Drop %1$s or %2$s here', 'placeholder: %1$s: Topics, %2$s: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topics' ),
						LearnDash_Custom_Label::get_label( 'quizzes' )
					),
					'step'                               => esc_html_x( 'step', 'singular - Amount of steps in a course or quiz', 'learndash' ),
					'steps'                              => esc_html_x( 'steps', 'plural - Amount of steps in a course or quiz', 'learndash' ),
					'in_this'                            => esc_html_x( 'in this', 'Amount of steps in this course or quiz', 'learndash' ),
					'final_quiz'                         => esc_html_x( 'Final', 'Builder - Final quiz. Placeholder in JavaScript', 'learndash' ),
					'quiz_no_questions'                  => sprintf(
						// translators: placeholders: %1$s: Quiz, %2$s:   Questions
						esc_html_x( 'This %1$s has no %2$s yet', 'This quiz has no questions.', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' ),
						LearnDash_Custom_Label::get_label( 'questions' )
					),
					'question_empty_edit'                => sprintf(
						/* translators: placeholders: question */
						esc_html_x( 'The %s is empty, click here to edit it.', 'Warning when no question was entered', 'learndash' ),
						learndash_get_custom_label_lower( 'question' )
					),
					'unsaved_chages'                     => esc_html( 'You have unsaved changes. If you proceed, they will be lost.', 'learndash' ),
					'manage_questions_builder'           => sprintf(
						/* translators: placeholders: Questions */
						esc_html_x( 'Manage %1$s in builder', 'Manage Questions in builder', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'questions' )
					),
					'total_points'                       => esc_html_x( 'TOTAL:', 'Total points', 'learndash' ),
					'no_content'                         => esc_html_x( 'has no content yet.', 'Displayed when the post type, e.g. course, has no content', 'learndash' ),
					'add_content'                        => esc_html_x( 'Add a new', 'Content type, e.g. lesson', 'learndash' ),
					'add_from_sidebar'                   => esc_html_x( 'or add an existing one from the sidebar', 'Content type, e.g. lesson', 'learndash' ),
					'essay_answer_format'                => esc_html_x( 'Answer format', 'Type of essay answer', 'learndash' ),
					'essay_text_answer'                  => esc_html_x( 'Text entry', 'Submit essay answer in a text box', 'learndash' ),
					'essay_file_upload_answer'           => esc_html_x( 'File upload', 'Submit essay answer as an upload', 'learndash' ),
					'essay_after_submission'             => esc_html_x( 'What should happen on quiz submission?', 'What grading options should be used after essay submission', 'learndash' ),
					'essay_not_graded_no_points'         => esc_html_x( 'Not Graded, No Points Awarded', 'Essay answer grading option', 'learndash' ),
					'essay_not_graded_full_points'       => esc_html_x( 'Not Graded, Full Points Awarded', 'Essay answer grading option', 'learndash' ),
					'essay_graded_full_points'           => esc_html_x( 'Graded, Full Points Awarded', 'Essay answer grading option', 'learndash' ),
					'essay_not_set'                      => esc_html_x( 'Not set', 'Essay answer grading option has not been set', 'learndash' ),
				),
			);

			$action_menu = array();

			$screen_post_type = '';
			if ( ! empty( $typenow ) ) {
				$screen_post_type = $typenow;
			} else {
				$menu_tab_parts = wp_parse_url( $menu_tab_key );
				if ( ( isset( $menu_tab_parts['query'] ) ) && ( ! empty( $menu_tab_parts['query'] ) ) ) {
					parse_str( $menu_tab_parts['query'], $menu_tab_url_parts );
					if ( ( isset( $menu_tab_url_parts['post_type'] ) ) && ( ! empty( $menu_tab_url_parts['post_type'] ) ) ) {
						$screen_post_type = $menu_tab_url_parts['post_type'];
					}
				}
			}

			if ( ! empty( $screen_post_type ) ) {
				$screen_post_type_object = get_post_type_object( $screen_post_type );
			}

			$header_data['post_data']['builder_post_id'] = get_the_ID();
			if ( ! empty( $header_data['post_data']['builder_post_id'] ) ) {
				$header_data['post_data']['builder_post_title'] = get_the_title( $header_data['post_data']['builder_post_id'] );
			}

			$header_data['post_data']['builder_post_type'] = $screen_post_type;

			$logic_control = '';

			if ( ( isset( $_GET['page'] ) ) && ( strtolower( $_GET['page'] ) === strtolower( 'ldAdvQuiz' ) ) ) {
				$logic_control = 'post';
			} elseif ( 'sfwd-courses_page_courses-builder' === $screen->id ) {

				$header_data['currentTab'] = 'learndash_course_builder';
				$header_data['tabs']       = [];

				$header_data['back_to_title'] = learndash_get_label_course_step_back( learndash_get_post_type_slug( 'course' ), true );
				$header_data['back_to_url'] = admin_url( 'edit.php?post_type=sfwd-courses' );

				if ( isset( $_GET['course_id'] ) ) {
					$header_data['tabs'][] = [
						'id'         => 'post-body-content',
						'name'       => learndash_get_label_course_step_page( learndash_get_post_type_slug( 'course' ) ),
						'link'       => get_edit_post_link( absint( $_GET['course_id'] ) ),
						'isExternal' => 'true',
					];
				}

				if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) ) {
					$header_data['tabs'][] = [
						'id'        => 'learndash_course_builder',
						'name'      => esc_html__( 'Builder', 'learndash' ),
						'metaboxes' => [ 'learndash_courses_builder_courses_builder' ],
					];
				}

				if ( isset( $_GET['course_id'] ) ) {
					$header_data['tabs'][] = [
						'id'         => 'sfwd-courses',
						'name'       => esc_html__( 'Settings', 'learndash' ),
						'link'       => get_edit_post_link( absint( $_GET['course_id'] ) ) . '&currentTab=sfwd-courses',
						'isExternal' => 'true',
					];
				}
			} elseif ( in_array( $pagenow, array( 'edit.php', 'edit-tags.php', 'admin.php', 'options-general.php' ) ) ) {
				$logic_control = 'archive';
			} elseif ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
				$logic_control = 'post';
			}

			if ( 'archive' === $logic_control ) {
				if ( learndash_is_admin_user() ) {
					$header_data['back_to_title'] = esc_html__( 'Overview', 'learndash' );
					$header_data['back_to_url']   = admin_url( 'admin.php?page=learndash_lms_overview' );
				} else {
					$header_data['back_to_title'] = '';
					$header_data['back_to_url']   = '';
				}

				if ( 'admin.php?page=learndash_lms_settings' === $screen->parent_file ) {
					$header_data['post_data']['builder_post_title'] = esc_html__( 'Settings', 'learndash' );
				}

				if ( learndash_get_post_type_slug( 'essay' ) === $screen_post_type ) {
					if ( learndash_is_group_leader_user() ) {
						$header_data['post_data']['builder_post_title'] = sprintf(
							// translators: placeholder: Essays.
							esc_html_x( '%s', 'placeholder: Essays', 'learndash' ),
							$screen_post_type_object->labels->name
						);
					} else {
						$header_data['post_data']['builder_post_title'] = sprintf(
							// translators: placeholder: Quizzes.
							esc_html_x( '%s', 'placeholder: Quizzes', 'learndash' ),
							learndash_get_custom_label( 'quizzes' )
						);
					}
				} elseif ( ( isset( $screen_post_type_object ) ) && ( is_a( $screen_post_type_object, 'WP_Post_Type' ) ) ) {
					$header_data['post_data']['builder_post_title'] = sprintf(
						// translators: placeholder: Post Type Plural Name.
						esc_html_x( '%s', 'placeholder: Post Type Plural Name', 'learndash' ),
						$screen_post_type_object->labels->name
					);
				}

				if ( learndash_get_post_type_slug( 'quiz' ) === $screen_post_type ) {
					$action_menu[] = array(
						'title'      => esc_html_x( 'Import/Export', 'Quiz Import/Export Tab Label', 'learndash' ),
						'link'       => 'admin.php?page=ldAdvQuiz',
						'isExternal' => 'false',
					);
				}

				if ( ( 'groups_page_group_admin_page' === $screen->id ) || ( 'learndash-lms_page_group_admin_page' === $screen->id ) ) {
					if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) {
						if ( ( isset( $_GET['user_id'] ) ) && ( ! empty( $_GET['user_id'] ) ) ) {
							$user = get_user_by( 'id', absint( $_GET['user_id'] ) );
							if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
								if ( ! empty( $user->display_name ) ) {
									$user_name = $user->display_name;
								} else {
									$user_name = $user->first_name . ' ' . $user->last_name;
								}
								$header_data['post_data']['builder_post_title'] = $user_name;
								$header_data['back_to_title']                   = get_the_title( absint( $_GET['group_id'] ) );
								$header_data['back_to_url']                     = add_query_arg(
									array(
										'group_id' => absint( $_GET['group_id'] ),
										'page'     => 'group_admin_page',
									),
									admin_url( 'admin.php' )
								);
							}
						} else {
							$header_data['post_data']['builder_post_title'] = get_the_title( absint( $_GET['group_id'] ) );
							$header_data['back_to_title']                   = esc_html__( 'Group Administration', 'learndash' );
							$header_data['back_to_url']                     = add_query_arg(
								array(
									'page' => 'group_admin_page',
								),
								admin_url( 'admin.php' )
							);
						}
					} else {
						$header_data['post_data']['builder_post_title'] = esc_html__( 'Group Administration', 'learndash' );
					}
				}

				foreach ( $this->admin_tab_sets[ $menu_tab_key ] as $menu_item ) {
					if ( ( isset( $menu_item['link'] ) ) && ( ! empty( $menu_item['link'] ) ) ) {
						$link_parts = wp_parse_url( $menu_item['link'] );
						if ( ( ! isset( $menu_item['cap'] ) ) || ( ! current_user_can( $menu_item['cap'] ) ) ) {
							continue;
						}

						if ( ( isset( $learndash_metaboxes[ $screen->id ] ) ) && ( ! empty( $learndash_metaboxes[ $screen->id ] ) ) ) {
							$metaboxes = array_keys( $learndash_metaboxes[ $screen->id ] );
						} else {
							$metaboxes = [];
						}

						if ( ( isset( $link_parts['path'] ) ) && ( ! empty( $link_parts['path'] ) ) ) {
							if ( 'edit.php' === $link_parts['path'] ) {

								$header_data['tabs'][] = array(
									'id'         => $menu_item['id'],
									'name'       => $menu_item['name'],
									'link'       => admin_url( $menu_item['link'] ),
									'isExternal' => 'true',
									'actions'    => array(),
									'metaboxes'  => $metaboxes,
								);
							} elseif ( ( 'admin.php' === $link_parts['path'] ) || ( 'options-general.php' === $link_parts['path'] ) ) {

								$header_data['tabs'][] = array(
									'id'         => $menu_item['id'],
									'name'       => $menu_item['name'],
									'link'       => admin_url( $menu_item['link'] ),
									'isExternal' => 'true',
									'actions'    => array(),
									'metaboxes'  => $metaboxes,
								);
							} elseif ( 'edit-tags.php' === $link_parts['path'] ) {
								$action_menu[] = array(
									'title'      => $menu_item['name'],
									'link'       => $menu_item['link'],
									'isExternal' => 'false',
									'metaboxes'  => $metaboxes,
								);
							}
						}
					}
				}

				if ( ( 'learndash-lms_page_learndash-lms-reports' === $screen->id ) || ( ( isset( $_GET['page'] ) ) && ( 'learndash-lms-reports' === $_GET['page'] ) ) ) {
					if ( isset( $header_data['tabs'][0] ) ) {
						$header_data['currentTab'] = $header_data['tabs'][0]['id'];
					}
				}
			} elseif ( 'post' === $logic_control ) {
				$header_data['back_to_title'] = esc_html__( 'Back', 'learndash' );
				$header_data['back_to_url']   = admin_url( 'edit.php?post_type=' . $screen_post_type );

				if ( ( isset( $_GET['currentTab'] ) ) && ( ! empty( $_GET['currentTab'] ) ) ) {
					$header_data['currentTab'] = esc_attr( $_GET['currentTab'] );
				} else {
					$header_data['currentTab'] = 'post-body-content';
				}

				$header_data['post_data']['builder_post_id'] = get_the_ID();
				if ( ! $header_data['post_data']['builder_post_id'] ) {
					if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
						$post_id = absint( $_GET['post'] );
						if ( get_post_type( $post_id ) === learndash_get_post_type_slug( 'quiz' ) ) {
							$header_data['post_data']['builder_post_id'] = $post_id;
						}
					} else {
						if ( ( isset( $_GET['post_id'] ) ) && ( ! empty( $_GET['post_id'] ) ) ) {
							$post_id = absint( $_GET['post_id'] );
							if ( get_post_type( $post_id ) === learndash_get_post_type_slug( 'quiz' ) ) {
								$header_data['post_data']['builder_post_id'] = $post_id;
							}
						}
					}
				}

				$header_data['post_data']['builder_post_title'] = '';
				if ( ! empty( $header_data['post_data']['builder_post_id'] ) ) {
					$header_data['post_data']['builder_post_title'] = get_the_title( $header_data['post_data']['builder_post_id'] );
				}

				$header_data['post_data']['builder_post_type'] = $screen_post_type;
				$header_data['back_to_title'] = learndash_get_label_course_step_back( $screen_post_type, true );
				$header_data['tabs'] = array(
					array(
						'id'      => 'post-body-content',
						'name'    => learndash_get_label_course_step_page( $screen_post_type ),
						'actions' => array(),
					),
				);

				if ( ( isset( $_GET['page'] ) ) && ( 'ldAdvQuiz' === $_GET['page'] ) ) {
					if ( ( isset( $_GET['post_id'] ) ) && ( ! empty( $_GET['post_id'] ) ) ) {
						if ( ( isset( $_GET['module'] ) ) && ( 'question' === $_GET['module'] ) ) {
							if ( ( isset( $_GET['action'] ) ) && ( 'addEdit' === $_GET['action'] ) ) {
								$header_data['currentTab']  = $screen->id;
								$header_data['back_to_title'] = learndash_get_label_course_step_back( learndash_get_post_type_slug( 'question' ), true );
								$header_data['back_to_url'] = add_query_arg(
									array(
										'page'    => 'ldAdvQuiz',
										'module'  => 'question',
										'quiz_id' => $_GET['quiz_id'],
										'post_id' => $_GET['post_id'],
									),
									'admin.php'
								);

								$header_data['currentTab']  = $screen->id;

								$header_data['tabs'] = array(
									array(
										'id'      => $screen->id,
										'name'    => learndash_get_label_course_step_page( learndash_get_post_type_slug( 'question' ) ),
										'actions' => array(),
									),
								);
							} else {
								$header_data['back_to_title'] = learndash_get_label_course_step_back( learndash_get_post_type_slug( 'quiz' ), true );
								$header_data['back_to_url'] = admin_url( 'edit.php?post_type=' . learndash_get_post_type_slug( 'quiz' ) );
								$header_data['currentTab']  = $screen->id;

								$header_data['tabs'] = array(
									array(
										'id'      => $screen->id,
										'name'    => sprintf(
											// translators: placeholder: Questions.
											esc_html_x( '%s', 'placeholder: Questions', 'learndash' ),
											learndash_get_custom_label( 'questions' )
										),
										'actions' => array(),
									),
								);
							}

						} else {
							$header_data['back_to_title'] = learndash_get_label_course_step_page( learndash_get_post_type_slug( 'quiz' ) );
							$header_data['back_to_url'] = get_edit_post_link( absint( $_GET['post_id'] ) );
							$header_data['currentTab']  = $screen->id;
						}
					} else {
						// Quiz Import.Export page.
						$header_data['currentTab'] = 'import-export';
						$header_data['tabs'] = array(
							array(
								'id'      => $header_data['currentTab'],
								'name'    => 'Import/Export Page',
								'link'    => admin_url( 'admin.php?page=ldAdvQuiz' ),
								'isExternal' => 'true',
								'actions' => array(),
							),
				);
					}

					if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {

						$action_menu = array_merge(
							$action_menu,
							array(
								array(
									'title'      => sprintf(
										// translators: placeholders: Quiz, Questions.
										esc_html_x( 'Reprocess %1$s %2$s', 'placeholders: Quiz, Questions', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'Quiz' ),
										LearnDash_Custom_Label::get_label( 'Questions' )
									),
									'link'       => add_query_arg( 'quiz_id', absint( $_GET['post'] ), admin_url( 'admin.php?page=learndash_data_upgrades' ) ),
									'isExternal' => 'true',
								)
							)
						);

						if ( current_user_can('wpProQuiz_export') ) {

							$action_menu = array_merge(
								$action_menu,
								array(
									array(
										'title'      => sprintf(
											// translators: placeholder: Quiz.
											esc_html_x( 'Export %s', 'placeholder: Quiz', 'learndash' ),
											LearnDash_Custom_Label::get_label( 'quiz' )
										),
										'link'       => add_query_arg(
											array(
												'page'       => 'ldAdvQuiz',
												'quiz_id'    => absint( $_GET['post'] ),
											),
											admin_url( 'admin.php' )
										),
										'isExternal' => 'true',
									)
								)
							);
						}

						$action_menu = array_merge(
							$action_menu,
							array(
								array(
									'title'      => esc_html__( 'Statistics', 'learndash' ),
									'link'       => add_query_arg(
										array(
											'module'     => 'statistics',
											'currentTab' => 'statistics',
										),
										$this->get_quiz_base_url()
									),
									'isExternal' => 'false',
								),
								array(
									'title'      => esc_html__( 'Leaderboard', 'learndash' ),
									'link'       => add_query_arg(
										array(
											'module'     => 'toplist',
											'currentTab' => 'leaderboard',
										),
										$this->get_quiz_base_url()
									),
									'isExternal' => 'false',
								),
							)
						);
					}

					if ( ( isset( $_GET['module'] ) ) && ( 'statistics' === $_GET['module'] ) ) {
						$header_data['tabs'] = array(
							array(
								'id'      => $screen->id,
								'name'    => esc_html__( 'Statistics', 'learndash' ),
								'actions' => $action_menu,
							),
						);
					} elseif ( ( isset( $_GET['module'] ) ) && ( 'toplist' === $_GET['module'] ) ) {
						$header_data['tabs'] = array(
							array(
								'id'      => $screen->id,
								'name'    => esc_html__( 'Leaderboard', 'learndash' ),
								'actions' => $action_menu,
							),
						);
					}
				} elseif ( learndash_get_post_type_slug( 'course' ) === $screen_post_type ) {
					if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) ) {
						$header_data['tabs'] = array_merge(
							$header_data['tabs'],
							array(
								array(
									'id'   => 'learndash_course_builder',
									'name' => esc_html__( 'Builder', 'learndash' ),
								),
							)
						);
					}

					if ( current_user_can( 'edit_groups' ) ) {
						if ( true === apply_filters( 'learndash_show_metabox_course_groups', true ) ) {
							$header_data['tabs'] = array_merge(
								$header_data['tabs'],
								array(
									array(
										'id'                  => $screen_post_type . '-settings',
										'name'                => esc_html__( 'Settings', 'learndash' ),
										'metaboxes'           => [ 'sfwd-courses', 'learndash-course-display-content-settings', 'learndash-course-access-settings', 'learndash-course-navigation-settings', 'learndash-course-users-settings', 'learndash-course-grid-meta-box' ],
										'showDocumentSidebar' => 'false',
									),
									array(
										'id'                  => 'learndash_course_groups',
										'name'                => esc_html__( 'Groups', 'learndash' ),
										'metaboxes'           => [ 'learndash_course_groups' ],
										'showDocumentSidebar' => 'false',
									),
								)
							);
						}
					}

				} elseif ( learndash_get_post_type_slug( 'quiz' ) === $screen_post_type ) {

					if ( ( true === is_data_upgrade_quiz_questions_updated() ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ) {
						$header_data['tabs'] = array_merge(
							$header_data['tabs'],
							array(
								array(
									'id'   => 'learndash_quiz_builder',
									'name' => esc_html__( 'Builder', 'learndash' ),
								),
							)
						);
					}

					$header_data['tabs'] = array_merge(
						$header_data['tabs'],
						array(
							array(
								'id'                  => $screen_post_type . '-settings',
								'name'                => esc_html__( 'Settings', 'learndash' ),
								'metaboxes'           => [ $screen_post_type, 'learndash-quiz-access-settings', 'learndash-quiz-progress-settings', 'learndash-quiz-display-content-settings', 'learndash-quiz-results-options', 'learndash-quiz-admin-data-handling-settings', 'learndash-course-grid-meta-box' ],
								'showDocumentSidebar' => 'false',
							),
						)
					);

					if ( ( true !== is_data_upgrade_quiz_questions_updated() ) || ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) !== 'yes' ) ) {
						$pro_quiz_id = learndash_get_setting( get_the_ID(), 'quiz_pro', true );
						if ( ! empty( $pro_quiz_id ) ) {
							$header_data['tabs'] = array_merge(
								$header_data['tabs'],
								array(
									array(
										'id'   => 'learndash_quiz_questions',
										'name' => esc_html__( 'Questions', 'learndash' ),
										'link' => add_query_arg(
											array(
												'page'    => 'ldAdvQuiz',
												'module'  => 'question',
												'quiz_id' => $pro_quiz_id,
												'post_id' => absint( $_GET['post'] ),
											),
											admin_url( 'admin.php' )
										),
										'isExternal' => 'true',
									),
								)
							);
						}
					}

					if ( apply_filters( 'learndash_settings_metaboxes_legacy_quiz', LEARNDASH_SETTINGS_METABOXES_LEGACY_QUIZ, $screen_post_type ) ) {
						$header_data['tabs'] = array_merge(
							$header_data['tabs'],
							array(
								array(
									'id'                  => 'learndash_quiz_advanced_aggregated',
									'name'                => esc_html__( 'Advanced Settings', 'learndash' ),
									'metaboxes'           => [ 'learndash_quiz_advanced_aggregated' ],
									'showDocumentSidebar' => 'false',
								),
							)
						);
					}

					$action_menu = array_merge(
						$action_menu,
						array(
							array(
								'title'      => sprintf(
									// translators: placeholders: Quiz, Questions.
									esc_html_x( 'Reprocess %1$s %2$s', 'placeholders: Quiz, Questions', 'learndash' ),
									LearnDash_Custom_Label::get_label( 'Quiz' ),
									LearnDash_Custom_Label::get_label( 'Questions' )
								),
								'link'       => add_query_arg( 'quiz_id', $post->ID, admin_url( 'admin.php?page=learndash_data_upgrades' ) ),
								'isExternal' => 'true',
							),
						)
					);

					if ( current_user_can('wpProQuiz_export') ) {
						$action_menu = array_merge(
							$action_menu,
							array(
								array(
									'title'      => sprintf(
										// translators: placeholder: Quiz.
										esc_html_x( 'Export %s', 'placeholder: Quiz', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'quiz' )
									),
									'link'       => add_query_arg(
										array(
											'page'       => 'ldAdvQuiz',
											'quiz_id'    => $post->ID,
										),
										admin_url( 'admin.php' )
									),
									'isExternal' => 'true',
								)
							)
						);
					}

					$action_menu = array_merge(
						$action_menu,
						array(
							array(
								'title'      => esc_html__( 'Statistics', 'learndash' ),
								'link'       => add_query_arg(
									array(
										'module'     => 'statistics',
										'currentTab' => 'statistics',
									),
									$this->get_quiz_base_url()
								),
								'isExternal' => 'false',
							),
							array(
								'title'      => esc_html__( 'Leaderboard', 'learndash' ),
								'link'       => add_query_arg(
									array(
										'module'     => 'toplist',
										'currentTab' => 'leaderboard',
									),
									$this->get_quiz_base_url()
								),
								'isExternal' => 'false',
							),
						)
					);

				} elseif ( in_array(
					$screen_post_type,
					array(
						learndash_get_post_type_slug( 'lesson' ),
						learndash_get_post_type_slug( 'topic' ),
						learndash_get_post_type_slug( 'question' ),
					),
					true
				) ) {
					$post_settings_metaboxes = array();

					switch ( $screen_post_type ) {
						case learndash_get_post_type_slug( 'lesson' ):

							$post_settings_metaboxes = array_merge(
								$post_settings_metaboxes,
								[
									$screen_post_type,
									'learndash-lesson-display-content-settings',
									'learndash-lesson-access-settings',
									'learndash-course-grid-meta-box',
								]
							);
							break;

						case learndash_get_post_type_slug( 'topic' ):
							$post_settings_metaboxes = array_merge(
								$post_settings_metaboxes,
								[
									$screen_post_type,
									'learndash-topic-display-content-settings',
									'learndash-topic-access-settings',
									'learndash-course-grid-meta-box',
								]
							);
							break;

						case learndash_get_post_type_slug( 'question' ):
							if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) !== 'yes' ) {
								$post_settings_metaboxes = array_merge(
									$post_settings_metaboxes,
									[
										$screen_post_type,
									]
								);
							}
							break;

					}

					if ( ! empty( $post_settings_metaboxes ) ) {
						$header_data['tabs'] = array_merge(
							$header_data['tabs'],
							array(
								array(
									'id'                  => $screen_post_type . '-settings',
									'name'                => esc_html__( 'Settings', 'learndash' ),
									'metaboxes'           => $post_settings_metaboxes,
									'showDocumentSidebar' => 'false',
								),
							)
						);
					}
				}
			}

			$action_menu = apply_filters( 'learndash_header_action_menu', $action_menu, $menu_tab_key, $screen_post_type, $header_data['tabs'] );
			if ( ! empty( $action_menu ) ) {
				if ( ! empty( $header_data['tabs'] ) ) {
					foreach ( $header_data['tabs'] as &$header_menu_item ) {
						$header_menu_item['actions'] = $action_menu;
					}
				}
			}
			$header_data['tabs'] = apply_filters( 'learndash_header_tab_menu', $header_data['tabs'], $menu_tab_key, $screen_post_type );

			if ( 'sfwd-courses' === $screen_post_type ) {
				$header_data['posts_per_page'] = \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'per_page' );
			} elseif ( 'sfwd-quiz' === $screen_post_type ) {
				$header_data['posts_per_page'] = \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'per_page' );
			} else {
				$header_data['posts_per_page'] = get_option( 'posts_per_page' );
			}

			// Load the MO file translations into wp.i18n script hook.
			learndash_load_inline_script_locale_data();

			/**
			 * Localize dynamic data to LearnDashData global at front-end.
			 *
			 * @param array $data Default global data.
			 */
			$learndash_data = apply_filters(
				'learndash_header_data',
				$header_data,
				$menu_tab_key,
				$this->admin_tab_sets[ $menu_tab_key ]
			);

			if ( ! empty( $learndash_data ) ) {
				echo '<div id="sfwd-header"></div>';

				if ( ( ! empty( $screen_post_type ) ) && ( in_array( $screen_post_type, LDLMS_Post_Types::get_post_types(), true ) ) && ( 'edit-' . $screen_post_type === $screen->id ) ) {
					if ( learndash_get_total_post_count( $screen_post_type ) === 0 ) {

						// If there's an onboarding page, we render it.
						if ( file_exists( LEARNDASH_LMS_PLUGIN_DIR . "/includes/admin/onboarding-templates/onboarding-{$screen_post_type}.php" ) ) {
							include_once LEARNDASH_LMS_PLUGIN_DIR . "/includes/admin/onboarding-templates/onboarding-{$screen_post_type}.php";
						}
					}
				}

				if ( ! isset( $learndash_assets_loaded['styles']['learndash-new-header-style'] ) ) {
					wp_enqueue_style(
						'learndash-new-header-style',
						LEARNDASH_LMS_PLUGIN_URL . 'assets/js/builder/dist/header' . leardash_min_builder_asset() . '.css',
						array(),
						LEARNDASH_SCRIPT_VERSION_TOKEN
					);
					wp_style_add_data( 'learndash-new-header-style', 'rtl', 'replace' );
					$learndash_assets_loaded['styles']['learndash-new-header-style'] = __FUNCTION__;
				}

				$css_lesson_label     = \LearnDash_Custom_Label::get_label( 'lesson' )[0];
				$css_topic_label      = \LearnDash_Custom_Label::get_label( 'topic' )[0];
				$css_quiz_label       = \LearnDash_Custom_Label::get_label( 'quiz' )[0];
				$css_question_label   = \LearnDash_Custom_Label::get_label( 'question' )[0];
				$learndash_custom_css = "
				.learndash_navigation_lesson_topics_list .lesson > a:before,
				#sfwd-course-lessons h2:before {
					content: '{$css_lesson_label}';
				}
				.learndash_navigation_lesson_topics_list .topic_item > a > span:before,
				#sfwd-course-topics h2:before {
					content: '{$css_topic_label}';
				}
				.learndash_navigation_lesson_topics_list .quiz_list_item .lesson > a:before,
				#sfwd-course-quizzes h2:before {
					content: '{$css_quiz_label}';
				}
				#sfwd-quiz-questions h2:before,
				.ld-question-overview-widget-item:before {
					content: '{$css_question_label}';
				}
				";
				wp_add_inline_style( 'learndash-new-header-style', $learndash_custom_css );

				if ( ! isset( $learndash_assets_loaded['scripts']['learndash-new-header-script'] ) ) {
					wp_enqueue_script(
						'learndash-new-header-script',
						LEARNDASH_LMS_PLUGIN_URL . 'assets/js/builder/dist/header' . leardash_min_builder_asset() . '.js',
						array( 'wp-i18n' ),
						LEARNDASH_SCRIPT_VERSION_TOKEN,
						true
					);
					$learndash_assets_loaded['scripts']['learndash-new-header-script'] = __FUNCTION__;

					wp_localize_script( 'learndash-new-header-script', 'LearnDashData', $learndash_data );
				}
			}
		}

		public function get_quiz_base_url() {
			$quiz_post_id = get_the_ID();
			if ( ! $quiz_post_id ) {
				if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
					$post_id = absint( $_GET['post'] );
					if ( get_post_type( $post_id ) === learndash_get_post_type_slug( 'quiz' ) ) {
						$quiz_post_id = $post_id;
					}
				} else {
					if ( ( isset( $_GET['post_id'] ) ) && ( ! empty( $_GET['post_id'] ) ) ) {
						$post_id = absint( $_GET['post_id'] );
						if ( get_post_type( $post_id ) === learndash_get_post_type_slug( 'quiz' ) ) {
							$header_data['post_data']['builder_post_id'] = $post_id;
						}
					}
				}
			}

			$quiz_id = 0;
			if ( ! empty( $quiz_post_id ) ) {
				$quiz_id = learndash_get_setting( $quiz_post_id, 'quiz_pro' );
			}

			$url_params = array(
				'page'    => 'ldAdvQuiz',
				'id'      => $quiz_id,
				'post_id' => $quiz_post_id,
				'post'    => $quiz_post_id,
			);

			return add_query_arg( $url_params, admin_url( 'admin.php' ) );
		}
		// End of methods
	}
}

$ld_admin_menus_tabs = Learndash_Admin_Menus_Tabs::get_instance();

function learndash_add_admin_tab_item( $menu_slug, $menu_item, $menu_priority ) {
	Learndash_Admin_Menus_Tabs::get_instance()->add_admin_tab_item( $menu_slug, $menu_item, $menu_priority );
}

/**
 * Get current admin tabs set.
 *
 * @return array
 */
function learndash_get_current_tabs_set() {
	return Learndash_Admin_Menus_Tabs::get_instance()->learndash_admin_tabs();
}
