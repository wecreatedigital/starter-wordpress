<?php
/**
 * SFWD_LMS
 *
 * @since 2.1.0
 *
 * @package LearnDash
 */

if ( ! class_exists( 'SFWD_LMS' ) ) {

	/**
	 * Class to create the SFWD_LMS instance.
	 */
	class SFWD_LMS extends Semper_Fi_Module  {

		public $post_types = array();
		public $cache_key = '';
		public $quiz_json = '';
		public $count = null;

		private $post_args = array();
		private $ALL_PLUGUNS_CALLED = false;
		private $learndash_legacy_plugins_paths = array(
			'sfwd-lms/sfwd_lms.php'
		);

		/**
		 * Set up properties and hooks for this class 
		 */
		public function __construct() {
			self::$instance =& $this;
			$this->file = __FILE__;
			$this->name = 'LMS';
			$this->plugin_name = 'SFWD LMS';
			$this->name = 'LMS Options';
			$this->prefix = 'sfwd_lms_';
			$this->parent_option = 'sfwd_lms_options';
			parent::__construct();

			register_activation_hook( $this->plugin_path['basename'], array( $this, 'activate' ) );
			register_deactivation_hook( $this->plugin_path['basename'], array( $this, 'deactivate' ) );

			add_action( 'init', array( $this, 'trigger_actions' ), 1 );
			add_action( 'init', array( $this, 'add_post_types' ), 2 );

			// WPMU (Multisite) actions when a new blog is added/deleted.
			add_action( 'wpmu_new_blog', array( $this, 'wpmu_new_blog' ) );
			add_action( 'delete_blog', array( $this, 'delete_blog' ), 10, 2 );

			//add_action( 'plugins_loaded', array( $this, 'add_post_types' ), 1 );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'parse_request', array( $this, 'parse_ipn_request' ) );
			add_action( 'generate_rewrite_rules', array( $this, 'paypal_rewrite_rules' ) );
			add_filter( 'sfwd_cpt_loop', array( $this, 'cpt_loop_filter' ) );
			add_filter( 'edit_term_count', array( $this, 'tax_term_count' ), 10, 3 );
			add_action( 'init', array( $this, 'add_tag_init' ) );
			add_action( 'plugins_loaded', array( $this, 'i18nize') );
			add_shortcode( 'usermeta', array( $this, 'usermeta_shortcode' ) );

			add_filter( 'all_plugins', array( $this, 'all_plugins_proc' ) );
			add_action( 'pre_current_active_plugins', array( $this, 'pre_current_active_plugins_proc' ) );
			add_filter( 'option_active_plugins', array( $this, 'option_active_plugins_proc' ) );
			add_filter( 'site_option_active_sitewide_plugins', array( $this, 'site_option_active_sitewide_plugins_proc' ) );
			add_filter( 'pre_update_option_active_plugins', array( $this, 'pre_update_option_active_plugins' ) );
			add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'pre_update_site_option_active_sitewide_plugins' ) );

			add_action( 'after_setup_theme', array( $this, 'load_template_functions' ) );

			add_filter( 'category_row_actions', array( $this, 'ld_course_category_row_actions' ), 10, 2 );
			add_filter( 'post_tag_row_actions', array( $this, 'ld_course_category_row_actions' ), 10, 2 );

			add_action( 'shutdown', array( $this, 'wp_shutdown' ), 0 );

			if ( is_admin() ) {
				require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-groups-edit.php' );
				$this->ld_admin_groups_edit = new Learndash_Admin_Groups_Edit();

				//require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-settings-support-panel.php' );
				//$this->ld_admin_settings_support_panel = new Learndash_Admin_Settings_Support_Panel();

				require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-groups-users-list.php' );
				$this->ld_admin_groups_users_list = new Learndash_Admin_Groups_Users_list();

				require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php' );
				$this->ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();

				require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-settings-data-reports.php' );
				$this->ld_admin_settings_data_reports = new Learndash_Admin_Settings_Data_Reports();

				require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-user-profile-edit.php' );
				$this->ld_admin_user_profile_edit = new Learndash_Admin_User_Profile_Edit();

				//require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-course-edit.php' );
				//$this->ld_admin_course_edit = new Learndash_Admin_Course_Edit();

				//require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-quiz-edit.php' );
				//$this->ld_admin_quiz_edit = new Learndash_Admin_Quiz_Edit();

				//require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-question-edit.php' );
				//$this->ld_admin_question_edit = new Learndash_Admin_Question_Edit();

				//require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-question-listing.php' );
				//$this->ld_admin_question_listing = new Learndash_Admin_Question_Listing();
				require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-posts-edit.php' );
				require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-posts-listing.php' );

				/**
				 * WP-admin pionters functions
				 */
				require_once ( LEARNDASH_LMS_PLUGIN_DIR . '/includes/admin/class-learndash-admin-pointers.php' );
			}

			add_action( 'wp_ajax_select_a_lesson', array( $this, 'select_a_lesson_ajax' ) );
			add_action( 'wp_ajax_select_a_lesson_or_topic', array( $this, 'select_a_lesson_or_topic_ajax' ) );
			add_action( 'wp_ajax_select_a_quiz', array( $this, 'select_a_quiz_ajax' ) );
		}

		function trigger_actions() {
			$this->upgrade_plugin();
			
			if ( is_admin() ) {
				if ( ( is_multisite() ) && ( !is_network_admin() ) ) {
					if ( isset( $_GET['learndash_activate'] ) ) {
						$this->activate();
					}
				}
				do_action('learndash_admin_init');
			}
			
			do_action('learndash_init');

			do_action('learndash_settings_sections_fields_init');
			do_action('learndash_settings_sections_init');
			//do_action('learndash_themes_init');
			
			if ( is_admin() ) {
				do_action('learndash_settings_pages_init');
			}
		}

		/**
		 * Called when new Multisite blog is created 
		 * this is used to trigger the activate logic 
		 *
		 * @since 2.5.5
		 */
		function wpmu_new_blog( $blog_id = 0 ) {
			if ( !empty( $blog_id ) ) {
				switch_to_blog( $blog_id );
				$this->activate();
				restore_current_blog();
			} 
		}

		/**
		 * Called when Multisite blog is deleted 
		 * this is used to remove any custom DB tables. 
		 *
		 * @since 2.5.5
		 */
		function delete_blog( $blog_id = 0, $drop_tables = false ) {
			if ( ( !empty( $blog_id ) ) && ( $drop_tables === true ) ) {
				switch_to_blog( $blog_id );
				learndash_delete_all_data();
				restore_current_blog();
			} 
		}


		function get_post_args_section( $section = '', $sub_section = '' ) {
			if ( ( !empty( $section ) ) && ( isset( $this->post_args[$section] ) ) )
				if ( ( !empty( $sub_section ) ) && ( isset( $this->post_args[$section][$sub_section] ) ) )
					return $this->post_args[$section][$sub_section];
				else
					return $this->post_args[$section];
		}

		function wp_shutdown() {
			// If we are activating LD then we wait to flush the rewrite on the next page load because the $this->post_args is not setup yet
			if ( defined( 'LEARNDASH_ACTIVATED' ) && LEARNDASH_ACTIVATED ) {
				return;
			}
			
			if ( defined( 'LEARNDASH_SETTINGS_UPDATING' ) && LEARNDASH_SETTINGS_UPDATING ) {
				return;
			}

			// check if we triggered the rewrite flush
			$sfwd_lms_rewrite_flush_transient = get_option( 'sfwd_lms_rewrite_flush' );

			if ( $sfwd_lms_rewrite_flush_transient ) {
				
				delete_option( 'sfwd_lms_rewrite_flush' );
				
				$ld_rewrite_post_types = array(
					'sfwd-courses'	=>	'courses',
					'sfwd-lessons'	=>	'lessons',
					'sfwd-topic'	=>	'topics',
					'sfwd-quiz'		=>	'quizzes'
				);

				// First, we update the $post_args array item with the new permalink slug. 
				foreach ( $ld_rewrite_post_types as $cpt_key => $custom_label_key ) {
					if ( isset( $this->post_args[$cpt_key] ) ) {
						$this->post_args[$cpt_key]['slug_name'] = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', $custom_label_key );
						$this->post_args[$cpt_key]['cpt_options']['has_archive'] = learndash_post_type_has_archive( $cpt_key );
					}	
				}
				

				// Second, we allow external filters. This is the same filter used when the post types are registered.
				$this->post_args = apply_filters( 'learndash_post_args', $this->post_args );

				// Last we need to update the registered post type. 
				foreach ( $ld_rewrite_post_types as $cpt_key => $custom_label_key ) {
					$post_type_object = get_post_type_object( $cpt_key );
					if ( $post_type_object instanceof WP_Post_Type ) {
						$post_type_object->rewrite['slug'] = $this->post_args[$cpt_key]['slug_name'];
						$post_type_object->has_archive     = $this->post_args[$cpt_key]['cpt_options']['has_archive'];
						register_post_type( $cpt_key, $post_type_object );
					}
				}
				
				flush_rewrite_rules();
			}
		}

		/**
		 * Load functions used for templates
		 *
		 * @since 2.1.0
		 */
		function load_template_functions() {
			$this->init_ld_templates_dir();
			$template_file = $this->get_template( 'learndash_template_functions', array(), false, true );
			if ( ( ! empty( $template_file ) ) && ( file_exists( $template_file ) ) && ( is_file( $template_file ) ) ) {
				include_once( $template_file );
			}

			// Add support for generic name functions.php file in our template directory.
			$template_functions_file = LEARNDASH_TEMPLATES_DIR;
			$template_functions_file = trailingslashit( $template_functions_file ) . 'functions.php';
			if ( file_exists( $template_functions_file ) ) {
				include_once( $template_functions_file );
			}
		}

		/**
		 * Register Courses, Lessons, Quiz CPT's and set up their admin columns on post list view
		 */
		function add_tag_init()	{
			
			// LearnDash v2.4 - This tag init logic and taxonomy association has been moved to the add_post_types() functions
			// where each of the custom post types can have their own relatve taxonimies.  
			//$tag_args = array( 'taxonomies' => array( 'post_tag', 'category' ) );
			//register_post_type( 'sfwd-courses', $tag_args ); //Tag arguments for $post_type='sfwd-courses'
			//register_post_type( 'sfwd-lessons', $tag_args ); //Tag arguments for $post_type='sfwd-courses'
			//register_post_type( 'sfwd-quiz', $tag_args ); //Tag arguments for $post_type='sfwd-courses'

			add_filter( 'manage_edit-sfwd-lessons_columns', 'add_course_data_columns' );
			add_filter( 'manage_edit-sfwd-quiz_columns', 'add_shortcode_data_columns' );
			add_filter( 'manage_edit-sfwd-quiz_columns', 'add_course_data_columns' );
			add_filter( 'manage_edit-sfwd-topic_columns', 'add_lesson_data_columns' );
			add_filter( 'manage_edit-sfwd-assignment_columns', 'add_course_data_columns' );
			add_filter( 'manage_edit-sfwd-assignment_columns', 'add_assignment_data_columns' );
			add_filter( 'manage_edit-sfwd-essays_columns', 'add_essays_data_columns' );
			add_filter( 'manage_edit-sfwd-transactions_columns', 'add_course_data_columns' );
			

			//add_filter( 'manage_edit-sfwd-quiz_columns', 'remove_tags_column' );
			//add_filter( 'manage_edit-sfwd-quiz_columns', 'remove_categories_column' );

			add_action( 'manage_sfwd-lessons_posts_custom_column', 'manage_asigned_course_columns', 10, 3 );
			add_action( 'manage_sfwd-quiz_posts_custom_column', 'manage_asigned_course_columns', 10, 3 );
			add_action( 'manage_sfwd-topic_posts_custom_column', 'manage_asigned_course_columns', 10, 3 );

			add_action( 'manage_sfwd-assignment_posts_custom_column', 'manage_asigned_course_columns', 10, 3 );
			add_action( 'manage_sfwd-assignment_posts_custom_column', 'manage_asigned_assignment_columns', 10, 3 );

			add_action( 'manage_sfwd-transactions_posts_custom_column', 'manage_asigned_course_columns', 10, 3 );

			add_action( 'restrict_manage_posts', 'restrict_listings_by_course' );
			
			add_filter( 'parse_query', 'course_table_filter' );
		}



		/**
		 * Loads the plugin's translated strings
		 *
		 * @since 2.1.0
		 */
		function i18nize() {
			
			if ( ( defined( 'LD_LANG_DIR' ) ) && ( LD_LANG_DIR ) ) {
				load_plugin_textdomain( LEARNDASH_LMS_TEXT_DOMAIN, false, LD_LANG_DIR );
			} else {
				load_plugin_textdomain( LEARNDASH_LMS_TEXT_DOMAIN, false, dirname( plugin_basename( dirname( __FILE__ ) ) ) . '/languages' );
			}
		}



		/**
		 * Update count of posts with a term
		 * 
		 * Callback for add_filter 'edit_term_count'
		 * There is no apply_filters or php call to execute this function
		 *
		 * @todo  consider for deprecation, other docblock tags removed
		 *
		 * @since 2.1.0
		 */
		function tax_term_count( $columns, $id, $tax ) {
			if ( empty( $tax ) || ( $tax != 'courses' ) ) { 
				return $columns;
			}

			if ( ! empty( $_GET ) && ! empty( $_GET['post_type'] ) ) {
				$post_type = $_GET['post_type'];
				$wpq = array(		
					'tax_query' => array( 
						array( 
							'taxonomy' => $tax, 
							'field' => 'id', 
							'terms' => $id 
						)
					),
					'post_type' => $post_type,
					'post_status' => 'publish',
					'posts_per_page' => -1
				);
				$q = new WP_Query( $wpq );
				$this->count = $q->found_posts;
				add_filter( 'number_format_i18n', array( $this, 'column_term_number' ) );
			}

			return $columns;			
		}


		/**
		 * Set column term number
		 * 
		 * This function is called by the 'tax_term_count' method and is no longer being ran
		 * See tax_term_count()
		 *
		 * @todo  consider for deprecation, other docblock tags removed
		 *
		 * @since 2.1.0
		 */
		function column_term_number( $number ) {
			remove_filter( 'number_format_i18n', array( $this, 'column_term_number' ) );
			if ( $this->count !== null ) {
				$number = $this->count;
				$this->count = null;
			}
			return $number;
		}



		/**
		 * [usermeta] shortcode
		 * 
		 * This shortcode takes a parameter named field, which is the name of the user meta data field to be displayed.
		 * Example: [usermeta field="display_name"] would display the user's Display Name.
		 *
		 * @since 2.1.0
		 * 
		 * @param  array 	$attr    shortcode attributes
		 * @param  string 	$content content of shortcode
		 * @return string          	 output of shortcode
		 */
		function usermeta_shortcode( $attr, $content = '' ) {
			global $learndash_shortcode_used;
			$learndash_shortcode_used = true;
			
			// We clear out content because there is no reason to retain it. 
			$content = '';
			
			$attr = shortcode_atts( 
				array( 
					'field' 	=>	'', 
					'user_id' 	=> 	get_current_user_id()
				), 
				$attr 
			);
			
			/**
			 * Added logic to allow admin and group_leader to view certificate from other users. Should proably be somewhere else
			 * @since 2.3
			 */
			//$post_type = '';
			//if ( get_query_var( 'post_type' ) ) {
			//	$post_type = get_query_var( 'post_type' );
			//	if ( $post_type == 'sfwd-certificates' ) {
			//		if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) 
			//		  && ( ( isset( $_GET['user'] ) ) && (!empty( $_GET['user'] ) ) ) ) {
			//			$attr['user_id'] = intval( $_GET['user'] );
			//		}
			//	}
			//}
			
			if ( ( !empty( $attr['user_id'] ) ) && ( !empty( $attr['field'] ) ) ) {
			
				if ( ( learndash_is_admin_user() ) || ( $attr['user_id'] == get_current_user_id() ) ) {
					$usermeta_available_fields = array( $attr['field'] => $attr['field'] );
				} else {
					$usermeta_available_fields = learndash_get_usermeta_shortcode_available_fields( $attr );
				}
					
				if ( !is_array( $usermeta_available_fields ) ) 
					$usermeta_available_fields = array( $usermeta_available_fields );
					
				if ( array_key_exists( $attr['field'], $usermeta_available_fields ) === true ) {
					$value = '';

					// First check the userdata fields
					$userdata = get_userdata( intval( $attr['user_id'] ) );
					if ( ( ( $userdata ) && ( $userdata instanceof WP_User ) ) ) {
						$value = $userdata->{$attr['field']};
					}

					/**
					 * Added logic to allow formatting of value before returning
					 * @since 2.4
					 */
					$content = apply_filters( 'learndash_usermeta_shortcode_field_value_display', $value, $attr, $usermeta_available_fields );
				} 
			}
			
			return $content;
		}


		/**
		 * Callback for add_filter 'sfwd_cpt_loop'
		 * There is no apply_filters or php call to execute this function
		 *
		 * @since 2.1.0
		 * 
		 * @todo  consider for deprecation, other docblock tags removed
		 */
		function cpt_loop_filter( $content ) {
			global $post;
			if ( $post->post_type == 'sfwd-quiz' ) {
				$meta = get_post_meta( $post->ID, '_sfwd-quiz' );
				if ( is_array( $meta ) && ! empty( $meta ) ) {
					$meta = $meta[0];
					if ( is_array( $meta ) && ( ! empty( $meta['sfwd-quiz_lesson'] ) ) ) {
						$content = '';
					}
				}
			}
			return $content;
		}

		public function upgrade_plugin( ) {
			require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php' );
			$this->ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();

			$ld_current_version = $this->ld_admin_data_upgrades->get_data_settings( 'current_version' );
			$ld_prior_version = $this->ld_admin_data_upgrades->get_data_settings( 'prior_version' );
			$db_version = $this->ld_admin_data_upgrades->get_data_settings( 'db_version' );

			if ( empty( $ld_prior_version ) ) {
				// If we have a prior 'db_version' then we know there was a prior LD install.
				if ( ! empty( $db_version ) ) {
					if ( ! empty( $ld_current_version ) ) {
						$ld_prior_version = $ld_current_version;
					} else {
						$ld_prior_version = '0.0.0.0';
					}
				} 
				// Else we have a new install
				else {
					$ld_prior_version = 'new';
				}

				$this->ld_admin_data_upgrades->set_data_settings( 'prior_version', $ld_prior_version );

				// As this is a new install we want to set the prior data run on the Courses and Quizzes.
				$data_upgrade_courses = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_User_Meta_Courses' );
				if ( $data_upgrade_courses ) {
					$data_upgrade_courses->set_last_run_info();
				}

				$data_upgrade_quizzes = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_User_Meta_Quizzes' );
				if ( $data_upgrade_quizzes ) {
					$data_upgrade_quizzes->set_last_run_info();
				}

				$data_upgrade_quiz_questions = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Quiz_Questions' );
				if ( $data_upgrade_quiz_questions ) {
					$data_upgrade_quiz_questions->set_last_run_info();
				}
			}

			$_TRIGGER_ACTIVATE = false;
			if ( ( empty( $ld_current_version ) ) || ( version_compare( LEARNDASH_VERSION, $ld_current_version, '>' ) ) ) {
				$_TRIGGER_ACTIVATE = true;

				/**
				 * Remove legacy option item
				 *
				 * @since 2.5.7
				 */
				delete_option( 'ld-repositories' );

				// Before we update the current version we use it to set the prior version.
				if ( ! empty( $ld_current_version ) ) {
					$this->ld_admin_data_upgrades->set_data_settings( 'prior_version', $ld_current_version );
				}
				$this->ld_admin_data_upgrades->set_data_settings( 'current_version', LEARNDASH_VERSION );
			}

			if ( $_TRIGGER_ACTIVATE == true )
				$this->activate();
		}

		/**
		 * Fire on plugin activation
		 * 
		 * Currently sets 'sfwd_lms_rewrite_flush' to true
		 *
		 * @todo   consider if needed, transient is not being used anywhere else in LearnDash
		 * 
		 * @since 2.1.0
		 */
		public function activate() {
			learndash_setup_rewrite_flush();

			if ( ! defined( 'LEARNDASH_ACTIVATED' ) ) {
				define( 'LEARNDASH_ACTIVATED', true );
			}

			require_once( LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php' );
			$this->ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();

			$ld_current_version = $this->ld_admin_data_upgrades->get_data_settings( 'current_version' );
			$ld_prior_version = $this->ld_admin_data_upgrades->get_data_settings( 'prior_version' );
			$db_version = $this->ld_admin_data_upgrades->get_data_settings( 'db_version' );

			if ( empty( $ld_prior_version ) ) {
				// If we have a prior 'db_version' then we know there was a prior LD install.
				if ( ! empty( $db_version ) ) {
					if ( ! empty( $ld_current_version ) ) {
						$ld_prior_version = $ld_current_version;
					} else {
						$ld_prior_version = '0.0.0.0';
					}
				} else {
					// Else we have a new install.
					$ld_prior_version = 'new';
				}

				$this->ld_admin_data_upgrades->set_data_settings( 'prior_version', $ld_prior_version );

				// As this is a new install we want to set the prior data run on the Courses and Quizzes.
				$data_upgrade_courses = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_User_Meta_Courses' );
				if ( $data_upgrade_courses ) {
					$data_upgrade_courses->set_last_run_info();
				}

				$data_upgrade_quizzes = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_User_Meta_Quizzes' );
				if ( $data_upgrade_quizzes ) {
					$data_upgrade_quizzes->set_last_run_info();
				}

				$data_upgrade_course_access_list = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Course_Access_List_Convert' );
				if ( $data_upgrade_course_access_list ) {
					$data_upgrade_course_access_list->set_last_run_info();
				}

				$data_upgrade_quiz_questions = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Quiz_Questions' );
				if ( $data_upgrade_quiz_questions ) {
					$data_upgrade_quiz_questions->set_last_run_info();
					LearnDash_Settings_Quizzes_Management_Display::add_section_instance();
					LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Quizzes_Management_Display', 'shared_questions', 'yes' );
				}
			}

			if ( ( empty( $ld_current_version ) ) || ( version_compare( LEARNDASH_VERSION, $ld_current_version, '>' ) ) ) {

				// Before we update the current version we use it to set the prior version
				if ( ! empty( $ld_current_version ) ) {
					$this->ld_admin_data_upgrades->set_data_settings( 'prior_version', $ld_current_version );
				}

				$this->ld_admin_data_upgrades->set_data_settings( 'current_version', LEARNDASH_VERSION );
			}

			$ld_admin_settings_data_upgrades_db = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_User_Activity_DB_Table' );
			$ld_admin_settings_data_upgrades_db->upgrade_data_settings();

			$this->ld_admin_data_upgrades->set_data_settings( 'translations_installed', false );

			delete_option( 'ld-repositories' );

			/**
			 * Ensure we call WPProQuiz activate functions
			 * @since 2.4.6.1
			 */
			WpProQuiz_Helper_Upgrade::upgrade();

			/**
			 * If the prior version is not empty we check if there are existing questions. If
			 * none found we set the questions data upgrade to completed.
			 */
			if ( ! empty( $ld_prior_version ) ) {
				global $wpdb;

				$data_upgrade_quiz_questions = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Quiz_Questions' );
				if ( $data_upgrade_quiz_questions ) {
					$questions_data_settings = $data_upgrade_quiz_questions->get_data_settings( 'pro-quiz-questions' );

					$question_proquiz_count = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT id FROM ' . LDLMS_DB::get_table_name( 'quiz_question' ) . ' LIMIT %d', 1
						)
					);

					$question_post_count = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type=%s LIMIT %d', learndash_get_post_type_slug( 'question' ), 1
						)
					);

					if ( ( empty( $question_proquiz_count ) ) && ( empty( $question_post_count ) ) ) {
						$data_upgrade_quiz_questions->set_last_run_info();
					} else if ( ( ! empty( $question_proquiz_count ) ) && ( empty( $question_post_count ) ) ) {
						$data_upgrade_quiz_questions->set_data_settings( 'pro-quiz-questions', false );
					} else if ( ( ! empty( $question_proquiz_count ) ) && ( ! empty( $question_post_count ) ) ) {
						if ( false === $questions_data_settings ) {
							$data_upgrade_quiz_questions->set_last_run_info();
						}
					}
				}
			}

			/** 
			 * Secure the Assignments & Essay uploads directory from browseing
			 *
			 * @since 2.5.5
			 */
			$wp_upload_dir = wp_upload_dir();
			$wp_upload_base_dir = str_replace( '\\', '/', $wp_upload_dir['basedir'] );

			$ld_dirs = array( 'assignments', 'essays' );
			foreach( array( 'assignments', 'essays' ) as $ld_dir ) {

				$_dir = trailingslashit( $wp_upload_base_dir ) . $ld_dir;
				if ( ! file_exists( $_dir ) ) {
					if ( is_writable( dirname( $_dir ) ) ) {
						wp_mkdir_p( $_dir );
					}
				}

				if ( file_exists( $_dir ) ) {
					$_index = trailingslashit( $_dir ) . 'index.php';
					if ( ! file_exists( $_index ) ) {
						file_put_contents( $_index , '//LearnDash is THE Best LMS' );
					}
				}
			}

			do_action( 'learndash_activated' );
		}

		function deactivate() {
			do_action( 'learndash_deactivated' );
		}

		/**
		 * Add 'sfwd-lms' to query vars
		 * Fired on filter 'query_vars'
		 * 
		 * @since 2.1.0
		 * 
		 * @param  array  	$vars  query vars
		 * @return array 	$vars  query vars
		 */
		function add_query_vars( $vars ) {
			//$courses_options = learndash_get_option( 'sfwd-courses' );
			//if ((isset($courses_options['paypal_email'])) && (!empty($courses_options['paypal_email']))) {
			//	$vars = array_merge( array( 'sfwd-lms' ), $vars );
			//}
			
			
			$paypal_email = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_PayPal', 'paypal_email' );
			if ( !empty( $paypal_email ) ) {
				$vars = array_merge( array( 'sfwd-lms' ), $vars );
			}
			return $vars;
		}



		/**
		 * Include PayPal IPN if request is for PayPal IPN
		 * Fired on action 'parse_request'
		 * 
		 * @since 2.1.0
		 * 
		 * @param  object 	$wp  wp query
		 */
		function parse_ipn_request( $wp ) {
			//$courses_options = learndash_get_option( 'sfwd-courses' );
			//if ((isset($courses_options['paypal_email'])) && (!empty($courses_options['paypal_email']))) {
			
			//	if ( array_key_exists( 'sfwd-lms', $wp->query_vars )
			//			&& $wp->query_vars['sfwd-lms'] == 'paypal' ) {
				
					/**
					 * include PayPal IPN
					 */
			//		require_once( 'vendor/paypal/ipn.php' );
			//	}
			//}
			
			$paypal_email = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_PayPal', 'paypal_email' );
			if ( !empty( $paypal_email ) ) {
				if ( ( array_key_exists( 'sfwd-lms', $wp->query_vars ) ) && ( $wp->query_vars['sfwd-lms'] == 'paypal' ) ) {
						/**
						 * include PayPal IPN
						 */
						//require_once( __DIR__ . '/vendor/paypal/ipn.php' );
						require_once( LEARNDASH_LMS_LIBRARY_DIR . '/paypal/ipn.php' );
				}
			}
		}



		/**
		 * Adds paypal to already generated rewrite rules
		 * Fired on action 'generate_rewrite_rules'
		 *
		 * @since 2.1.0
		 * 
		 * @param  object  $wp_rewrite
		 */
		function paypal_rewrite_rules( $wp_rewrite ) {
			
			//$courses_options = learndash_get_option( 'sfwd-courses' );
			//if ((isset($courses_options['paypal_email'])) && (!empty($courses_options['paypal_email']))) {
			//	$wp_rewrite->rules = array_merge( array( 'sfwd-lms/paypal' => 'index.php?sfwd-lms=paypal' ), $wp_rewrite->rules );
			//}

			$paypal_email = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_PayPal', 'paypal_email' );
			if ( !empty( $paypal_email ) ) {
				$wp_rewrite->rules = array_merge( array( 'sfwd-lms/paypal' => 'index.php?sfwd-lms=paypal' ), $wp_rewrite->rules );
			}
		}

		/**
		 * Sets up CPT's and creates a 'new SFWD_CPT_Instance()' of each
		 * 
		 * @since 2.1.0
		 */
		function add_post_types() {
			$post = 0;

			if ( is_admin() && ! empty( $_GET ) && ( isset( $_GET['post'] ) ) ) {
				$post_id = $_GET['post'];
			}

			if ( ! empty( $post_id ) ) {
				$this->quiz_json = get_post_meta( $post_id, '_quizdata', true );
				if ( ! empty( $this->quiz_json ) ) {
					$this->quiz_json = $this->quiz_json['workingJson'];
				}
			}

			$options = get_option( 'sfwd_cpt_options' );

			$level1 = $level2 = $level3 = $level4 = $level5 = '';

			if ( ! empty( $options['modules'] ) ) {
				$options = $options['modules'];
				if ( ! empty( $options['sfwd-quiz_options'] ) ) {
					$options = $options['sfwd-quiz_options'];
					foreach ( array( 'level1', 'level2', 'level3', 'level4', 'level5' ) as $level ) {
						$$level = '';
						if ( ! empty( $options["sfwd-quiz_{$level}"] ) ) {
							$$level = $options["sfwd-quiz_{$level}"];
						}
					}
				}
			}

			if ( empty( $this->quiz_json ) ) { 
				$this->quiz_json = '{"info":{"name":"","main":"","results":"","level1":"' . $level1 . '","level2":"' . $level2 . '","level3":"' . $level3 . '","level4":"' . $level4 . '","level5":"' . $level5 . '"}}';
			}
			
			$posts_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
			if ( empty( $posts_per_page ) ) { 
				$posts_per_page = get_option( 'posts_per_page' );
				if ( empty( $posts_per_page ) ) { 
					$posts_per_page = 5;
				}
			}

			$course_capabilities = array(
				'read_post' => 'read_course',
				'publish_posts' => 'publish_courses',
				'edit_posts' => 'edit_courses',
				'edit_others_posts' => 'edit_others_courses',
				'delete_posts' => 'delete_courses',
				'delete_others_posts' => 'delete_others_courses',
				'read_private_posts' => 'read_private_courses',
				'edit_private_posts' => 'edit_private_courses',
				'delete_private_posts' => 'delete_private_courses',
				'delete_post' => 'delete_course',
				'edit_published_posts' => 'edit_published_courses',
				'delete_published_posts' => 'delete_published_courses',
			);

			if ( is_admin() ) {
				$admin_role = get_role( 'administrator' );
				if ( ( $admin_role ) && ( $admin_role instanceof WP_Role ) ) {
					if ( ! $admin_role->has_cap( 'delete_private_courses' ) ) {
						foreach ( $course_capabilities as $key => $cap ) {
							if ( ! $admin_role->has_cap( $cap ) ) {
								$admin_role->add_cap( $cap );
							}
						}
					}
					if ( ! $admin_role->has_cap( 'enroll_users' ) ) {
						$admin_role->add_cap( 'enroll_users' );
					}
				}
			}

			$lcl_topic  = LearnDash_Custom_Label::get_label( 'topic' );
			$lcl_topics = LearnDash_Custom_Label::get_label( 'topics' );

			$lesson_topic_labels = array(
				'name' 					=> 	$lcl_topics,
				'singular_name' 		=> 	$lcl_topic,
				'add_new' 				=> 	esc_html_x( 'Add New', 'Add New Topic Label', 'learndash' ),
				'add_new_item' 			=> 	sprintf( esc_html_x( 'Add New %s', 'Add New Topic Label', 'learndash' ), $lcl_topic ),
				'edit_item' 			=> 	sprintf( esc_html_x( 'Edit %s', 'Edit Topic Label', 'learndash' ), $lcl_topic ),
				'new_item' 				=> 	sprintf( esc_html_x( 'New %s', 'New Topic Label', 'learndash' ), $lcl_topic ),
				'all_items' 			=> 	$lcl_topics,
				'view_item' 			=> 	sprintf( esc_html_x( 'View %s', 'View Topic Label', 'learndash' ), $lcl_topic ),
				'search_items' 			=> 	sprintf( esc_html_x( 'Search %s', 'Search Topic Label', 'learndash' ), $lcl_topics ),
				'not_found' 			=> 	sprintf( esc_html_x( 'No %s found', 'No Topic found Label', 'learndash' ), $lcl_topics ),
				'not_found_in_trash' 	=> 	sprintf( esc_html_x( 'No %s found in Trash', 'No Topic found in Trash', 'learndash' ), $lcl_topics ),
				'parent_item_colon' 	=> 	'',
				'menu_name' 			=> 	$lcl_topics,
				'item_published'		=>	sprintf( esc_html_x( '%s Published', 'Topic Published', 'learndash' ), $lcl_topic ),
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'Topic Published Privately', 'learndash' ), $lcl_topic ),
				'item_reverted_to_draft' => sprintf( esc_html_x( '%s Reverted to Draft', 'Topic Reverted to Draft', 'learndash' ), $lcl_topic ),
				'item_scheduled'		=>	sprintf( esc_html_x( '%s Scheduled', 'Topic Scheduled', 'learndash' ), $lcl_topic ),
				'item_updated'			=>	sprintf( esc_html_x( '%s Updated', 'Topic Updated', 'learndash' ), $lcl_topic ),
			);

			$lcl_quiz    = LearnDash_Custom_Label::get_label( 'quiz' );
			$lcl_quizzes = LearnDash_Custom_Label::get_label( 'quizzes' );

			$quiz_labels = array(
				'name' 					=> 	$lcl_quizzes,
				'singular_name' 		=> 	$lcl_quiz,
				'add_new' 				=> 	esc_html_x( 'Add New', 'Add New Quiz Label', 'learndash' ),
				'add_new_item' 			=> 	sprintf( esc_html_x( 'Add New %s', 'Add New Quiz Label', 'learndash' ), $lcl_quiz ),
				'edit_item' 			=> 	sprintf( esc_html_x( 'Edit %s', 'Edit Quiz Label', 'learndash' ), $lcl_quiz ),
				'new_item' 				=> 	sprintf( esc_html_x( 'New %s', 'New Quiz Label', 'learndash' ), $lcl_quiz ),
				'all_items' 			=> 	$lcl_quizzes,
				'view_item' 			=> 	sprintf( esc_html_x( 'View %s', 'View Quiz Label', 'learndash' ), $lcl_quiz ),
				'search_items' 			=> 	sprintf( esc_html_x( 'Search %s', 'Search Quiz Label', 'learndash' ), $lcl_quizzes ),
				'not_found' 			=> 	sprintf( esc_html_x( 'No %s found', 'No Quiz found Label', 'learndash' ), $lcl_quizzes ),
				'not_found_in_trash' 	=> 	sprintf( esc_html_x( 'No %s found in Trash', 'No Quiz found in Trash Label', 'learndash' ), $lcl_quizzes ),
				'parent_item_colon' 	=> 	'',
				'menu_name'				=>	$lcl_quizzes,
				'item_published'		=>	sprintf( esc_html_x( '%s Published', 'Quiz Published', 'learndash' ), $lcl_quiz ),
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'Quiz Published Privately', 'learndash' ), $lcl_quiz ),
				'item_reverted_to_draft' => sprintf( esc_html_x( '%s Reverted to Draft', 'Quiz Reverted to Draft', 'learndash' ), $lcl_quiz ),
				'item_scheduled'		=>	sprintf( esc_html_x( '%s Scheduled', 'Quiz Scheduled', 'learndash' ), $lcl_quiz ),
				'item_updated'			=>	sprintf( esc_html_x( '%s Updated', 'Quiz Updated', 'learndash' ), $lcl_quiz ),
			);

			$lcl_question  = LearnDash_Custom_Label::get_label( 'question' );
			$lcl_questions = LearnDash_Custom_Label::get_label( 'questions' );

			$question_labels = array(
				'name' 					=> 	$lcl_questions,
				'singular_name' 		=> 	$lcl_question,
				'add_new' 				=> 	esc_html_x( 'Add New', 'Add New Question Label', 'learndash' ),
				'add_new_item' 			=> 	sprintf( esc_html_x( 'Add New %s', 'Add New Question Label', 'learndash' ), $lcl_question ),
				'edit_item' 			=> 	sprintf( esc_html_x( 'Edit %s', 'Edit Question Label', 'learndash' ), $lcl_question ),
				'new_item' 				=> 	sprintf( esc_html_x( 'New %s', 'New Question Label', 'learndash' ), $lcl_question ),
				'all_items' 			=> 	$lcl_questions,
				'view_item' 			=> 	sprintf( esc_html_x( 'View %s', 'View Question Label', 'learndash' ), $lcl_question ),
				'search_items' 			=> 	sprintf( esc_html_x( 'Search %s', 'Search Question Label', 'learndash' ), $lcl_questions ),
				'not_found' 			=> 	sprintf( esc_html_x( 'No %s found', 'No Question found Label', 'learndash' ), $lcl_questions ),
				'not_found_in_trash' 	=> 	sprintf( esc_html_x( 'No %s found in Trash', 'No Question found in Trash Label', 'learndash' ), $lcl_questions ),
				'parent_item_colon' 	=> 	'',
				'menu_name'				=>	$lcl_questions,
				'item_published'		=>	sprintf( esc_html_x( '%s Published', 'Question Published', 'learndash' ), $lcl_question ),
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'Question Published Privately', 'learndash' ), $lcl_question ),
				'item_reverted_to_draft' => sprintf( esc_html_x( '%s Reverted to Draft', 'Question Reverted to Draft', 'learndash' ), $lcl_question ),
				'item_scheduled'		=>	sprintf( esc_html_x( '%s Scheduled', 'Question Scheduled', 'learndash' ), $lcl_question ),
				'item_updated'			=>	sprintf( esc_html_x( '%s Updated', 'Question Updated', 'learndash' ), $lcl_question ),
			);

			$lcl_lesson  = LearnDash_Custom_Label::get_label( 'lesson' );
			$lcl_lessons = LearnDash_Custom_Label::get_label( 'lessons' );

			$lesson_labels = array(
				'name' 					=> 	$lcl_lessons,
				'singular_name' 		=> 	$lcl_lesson,
				'add_new' 				=> 	esc_html_x( 'Add New', 'Add New Lesson Label', 'learndash' ),
				'add_new_item' 			=> 	sprintf( esc_html_x( 'Add New %s', 'Add New Lesson Label', 'learndash' ), $lcl_lesson ),
				'edit_item' 			=> 	sprintf( esc_html_x( 'Edit %s', 'Edit Lesson Label', 'learndash' ), $lcl_lesson ),
				'new_item' 				=> 	sprintf( esc_html_x( 'New %s', 'New Lesson Label', 'learndash' ), $lcl_lesson ),
				'all_items' 			=> 	$lcl_lessons,
				'view_item' 			=> 	sprintf( esc_html_x( 'View %s', 'View Lesson Label', 'learndash' ), $lcl_lesson ),
				'search_items' 			=> 	sprintf( esc_html_x( 'Search %s', 'Search Lesson Label', 'learndash' ), $lcl_lessons ),
				'not_found' 			=> 	sprintf( esc_html_x( 'No %s found', 'No Lesson found Label', 'learndash' ), $lcl_lessons ),
				'not_found_in_trash' 	=> 	sprintf( esc_html_x( 'No %s found in Trash', 'No Lesson found in Trash Label', 'learndash' ), $lcl_lessons ),
				'parent_item_colon' 	=> 	'',
				'menu_name'				=>	$lcl_lessons,
				'item_published'		=>	sprintf( esc_html_x( '%s Published', 'Lesson Published', 'learndash' ), $lcl_lesson ),
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'Lesson Published Privately', 'learndash' ), $lcl_lesson ),
				'item_reverted_to_draft' => sprintf( esc_html_x( '%s Reverted to Draft', 'Lesson Reverted to Draft', 'learndash' ), $lcl_lesson ),
				'item_scheduled'		=>	sprintf( esc_html_x( '%s Scheduled', 'Lesson Scheduled', 'learndash' ), $lcl_lesson ),
				'item_updated'			=>	sprintf( esc_html_x( '%s Updated', 'Lesson Updated', 'learndash' ), $lcl_lesson ),
			);

			$lcl_course  = LearnDash_Custom_Label::get_label( 'course' );
			$lcl_courses = LearnDash_Custom_Label::get_label( 'courses' );

			$course_labels = array(
				'name' 					=> 	$lcl_courses,
				'singular_name' 		=> 	$lcl_course,
				'add_new' 				=> 	esc_html_x( 'Add New', 'Add New Course Label', 'learndash' ),
				'add_new_item' 			=> 	sprintf( esc_html_x( 'Add New %s', 'Add New Course Label', 'learndash' ), $lcl_course ),
				'edit_item' 			=> 	sprintf( esc_html_x( 'Edit %s', 'Edit Course Label', 'learndash' ), $lcl_course ),
				'new_item' 				=> 	sprintf( esc_html_x( 'New %s', 'New Course Label', 'learndash' ), $lcl_course ),
				'all_items' 			=> 	$lcl_courses,
				'view_item' 			=> 	sprintf( esc_html_x( 'View %s', 'View Course Label', 'learndash' ), $lcl_course ),
				'search_items' 			=> 	sprintf( esc_html_x( 'Search %s', 'Search Courses Label', 'learndash' ), $lcl_courses ),
				'not_found' 			=> 	sprintf( esc_html_x( 'No %s found', 'No Courses found Label', 'learndash' ), $lcl_courses ),
				'not_found_in_trash' 	=> 	sprintf( esc_html_x( 'No %s found in Trash', 'No Courses found in Trash Label', 'learndash' ), $lcl_courses ),
				'parent_item_colon' 	=> 	'',
				'menu_name'				=>	$lcl_courses,
				'item_published'		=>	sprintf( esc_html_x( '%s Published', 'Course Published', 'learndash' ), $lcl_course ),
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'Course Published Privately', 'learndash' ), $lcl_course ),
				'item_reverted_to_draft' => sprintf( esc_html_x( '%s Reverted to Draft', 'Course Reverted to Draft', 'learndash' ), $lcl_course ),
				'item_scheduled'		=>	sprintf( esc_html_x( '%s Scheduled', 'Course Scheduled', 'learndash' ), $lcl_course ),
				'item_updated'			=>	sprintf( esc_html_x( '%s Updated', 'Course Updated', 'learndash' ), $lcl_course ),
			);

			$course_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Taxonomies', 'wp_post_category' ) == 'yes') {
				$course_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Taxonomies', 'wp_post_tag' ) == 'yes') {
				$course_taxonomies['post_tag'] = 'post_tag';
			}
								
			$learndash_settings_permalinks_taxonomies = get_option( 'learndash_settings_permalinks_taxonomies' );
			if ( !is_array( $learndash_settings_permalinks_taxonomies ) ) $learndash_settings_permalinks_taxonomies = array();
			$learndash_settings_permalinks_taxonomies = wp_parse_args(
				$learndash_settings_permalinks_taxonomies, 
				array(
					'ld_course_category' 	=>	'course-category',
					'ld_course_tag' 		=>	'course-tag',
					'ld_lesson_category' 	=> 	'lesson-category',
					'ld_lesson_tag' 		=> 	'lesson-tag',
					'ld_topic_category'		=> 	'topic-category',
					'ld_topic_tag' 			=> 	'topic-tag',
					'ld_quiz_category'		=>	'quiz-category',
					'ld_quiz_tag'			=>	'quiz-tag',
					'ld_question_category'	=>	'question-category',
					'ld_question_tag'		=>	'question-tag'
				)
			);
							

			//$learndash_settings_permalinks_cpt = get_option( 'learndash_settings_permalinks' );
			/*
			if ( !is_array( $learndash_settings_permalinks_cpt ) ) $learndash_settings_permalinks_cpt = array();
			$learndash_settings_permalinks_cpt = wp_parse_args(
				$learndash_settings_permalinks_cpt, 
				array(
					'ld_course_category' 	=>	'course-category',
					'ld_course_tag' 		=>	'course-tag',
					'ld_lesson_category' 	=> 	'lesson-category',
					'ld_lesson_tag' 		=> 	'lesson-tag',
					'ld_topic_category'		=> 	'topic-category',
					'ld_topic_tag' 			=> 	'topic-tag'
				)
			);
			*/
			
							
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Taxonomies', 'ld_course_category' ) == 'yes' ) {				
				$course_taxonomies['ld_course_category'] =	array(
					'public'			=> true,
					'hierarchical'		=> true,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-courses' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-courses' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_course_category'],
					//'rest_controller_class' => 'LD_REST_Terms_Course_Category_Controller_V1',
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_course_category'] ),
					'capabilities' 		=> array(
						'manage_terms' 		=> 'manage_categories',
						'edit_terms'   		=> 'edit_categories',
						'delete_terms' 		=> 'delete_categories',
						'assign_terms' 		=> 'assign_categories',
					),
					
					'labels'           => 	array(
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Course', 'learndash' ), $lcl_course ),
					),
				);
			}
			
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Taxonomies', 'ld_course_tag' ) == 'yes') {
				$course_taxonomies['ld_course_tag'] = array(
					'public'			=> true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-courses' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-courses' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_course_tag'],
					//'rest_controller_class' => 'LD_REST_Terms_Course_Tag_Controller_V1',
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_course_tag'] ),
					'labels'            => array(
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Course', 'learndash' ), $lcl_course ),
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Course', 'learndash' ), $lcl_course ),
					),
				);
			}

			$lesson_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Lessons_Taxonomies', 'wp_post_category' ) == 'yes') {
				$lesson_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Lessons_Taxonomies', 'wp_post_tag' ) == 'yes') {
				$lesson_taxonomies['post_tag'] = 'post_tag';
			}
				
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Lessons_Taxonomies', 'ld_lesson_category' ) == 'yes') {
				$lesson_taxonomies['ld_lesson_category'] =	array(
					'public'			=> true,
					'hierarchical'		=> true,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-lessons' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-lessons' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_lesson_category'],
					//'rest_controller_class' => 'LD_REST_Terms_Lesson_Category_Controller_V1',
					'rewrite'           => 	array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_lesson_category'] ),
					'capabilities' 		=> 	array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'labels'            => 	array(
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
					),
				);
			}
			
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Lessons_Taxonomies', 'ld_lesson_tag' ) == 'yes') {
				$lesson_taxonomies['ld_lesson_tag'] = array(
					'public'			=> true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-lessons' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-lessons' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_lesson_tag'],
					//'rest_controller_class' => 'LD_REST_Terms_Lesson_Tag_Controller_V1',
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_lesson_tag'] ),
					'labels'            => array(
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
					),
				);
			}
				
			$topic_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Topics_Taxonomies', 'wp_post_category' ) == 'yes') {
				$topic_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Topics_Taxonomies', 'wp_post_tag' ) == 'yes') {
				$topic_taxonomies['post_tag'] = 'post_tag';
			}
				
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Topics_Taxonomies', 'ld_topic_category' ) == 'yes') {
				$topic_taxonomies['ld_topic_category'] =	array(
					'public'			=> true,
					'hierarchical'		=> true,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-topic' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-topic' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_topic_category'],
					//'rest_controller_class' => 'LD_REST_Terms_Topic_Category_Controller_V1',
					'rewrite'           => 	array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_topic_category'] ),
					'capabilities' 		=> 	array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'labels'            => 	array(
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
					),
				);
			}
			
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Topics_Taxonomies', 'ld_topic_tag' ) == 'yes' ) {
				$topic_taxonomies['ld_topic_tag'] = array(
					'public'			=> true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-topic' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-topic' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_topic_tag'],
					//'rest_controller_class' => 'LD_REST_Terms_Topic_Tag_Controller_V1',
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_topic_tag'] ),
					'labels'            => array(
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
					),
				);
			}
			
			$quiz_taxonomies = array();				
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Quizzes_Taxonomies', 'ld_quiz_category' ) == 'yes') {
				$quiz_taxonomies['ld_quiz_category'] =	array(
					'public'			=> true,
					'hierarchical'		=> true,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-quiz' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-quiz' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_topic_category'],
					//'rest_controller_class' => 'LD_REST_Terms_Topic_Category_Controller_V1',
					'rewrite'           => 	array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_quiz_category'] ),
					'capabilities' 		=> 	array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'labels'            => 	array(
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
					),
				);
			}
			
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Quizzes_Taxonomies', 'ld_quiz_tag' ) == 'yes' ) {
				$quiz_taxonomies['ld_quiz_tag'] = array(
					'public'			=> true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-quiz' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-quiz' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_topic_tag'],
					//'rest_controller_class' => 'LD_REST_Terms_Topic_Tag_Controller_V1',
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_quiz_tag'] ),
					'labels'            => array(
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
					),
				);
			}
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Quizzes_Taxonomies', 'wp_post_category' ) == 'yes') {
				$quiz_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Quizzes_Taxonomies', 'wp_post_tag' ) == 'yes') {
				$quiz_taxonomies['post_tag'] = 'post_tag';
			}


			$question_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Questions_Taxonomies', 'ld_question_category' ) == 'yes') {
				$question_taxonomies['ld_question_category'] =	array(
					'public'			=> false,
					'hierarchical'		=> true,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-question' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-question' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_topic_category'],
					//'rest_controller_class' => 'LD_REST_Terms_Topic_Category_Controller_V1',
					'rewrite'           => 	array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_question_category'] ),
					'capabilities' 		=> 	array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'labels'            => 	array(
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Question', 'learndash' ), $lcl_question ),
					),
				);
			}
			
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Questions_Taxonomies', 'ld_question_tag' ) == 'yes' ) {
				$question_taxonomies['ld_question_tag'] = array(
					'public'			=> false,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'		=> true,	
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'		=> LearnDash_REST_API::enabled( 'sfwd-question' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-question' ),
					//'rest_base' 		=> $learndash_settings_permalinks_taxonomies['ld_topic_tag'],
					//'rest_controller_class' => 'LD_REST_Terms_Topic_Tag_Controller_V1',
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_question_tag'] ),
					'labels'            => array(
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Question', 'learndash' ), $lcl_question ),
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Question', 'learndash' ), $lcl_question ),
					),
				);
			}
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Questions_Taxonomies', 'wp_post_category' ) == 'yes') {
				$question_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Questions_Taxonomies', 'wp_post_tag' ) == 'yes') {
				$question_taxonomies['post_tag'] = 'post_tag';
			}

			$course_lessons_options_labels = array(
				//'orderby' 	=> 	LearnDash_Settings_Section_Lessons_Display_Order::get_setting_select_option_label( 'orderby' ),
				'orderby' 	=> 	LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'orderby' ),
				//'order' 	=>	LearnDash_Settings_Section_Lessons_Display_Order::get_setting_select_option_label( 'order' )
				'order' 	=>	LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'order' ),
			);
			
			$this->post_args = array(
				'sfwd-courses' => array(
					'plugin_name' => LearnDash_Custom_Label::get_label( 'course' ),
					'slug_name' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'courses' ), 
					'post_type' => 'sfwd-courses',
					'template_redirect' => true,
					'taxonomies' => $course_taxonomies,
					'cpt_options' => array( 
						'has_archive' => learndash_post_type_has_archive( 'sfwd-courses' ),
						'hierarchical' => false, 
						'supports' => array_merge(
							array( 'title', 'editor', 'author', 'page-attributes' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_CPT', 'supports' )
						),
						'labels' => $course_labels,
						'capability_type' => 'course',
						'exclude_from_search' => (LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_CPT', 'include_in_search' ) !== 'yes' ) ? true : false, 
						'capabilities' => $course_capabilities,
						'map_meta_cap' => true,
						'show_in_rest' => LearnDash_REST_API::enabled( 'sfwd-courses' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-courses' ),
						//'rest_base' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'courses' ),
						'rest_controller_class' => LearnDash_REST_API::get_controller( 'sfwd-courses' ),
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					), 
					'fields' => array( 
						'course_materials' => array(
							'name' => sprintf( esc_html_x( '%s Materials', 'Course Materials Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'textarea',
							'help_text' => sprintf( esc_html_x( 'Options for %s materials', 'Options for course materials', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args' => array(
								'schema' => array(
									'type' => 'html'
								) 
							)
						),
						'course_price_type' => array(
							'name' => sprintf( esc_html_x( '%s Price Type', 'Course Price Type Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'select',
							'initial_options' => array(	
								'open' => esc_html__( 'Open', 'learndash' ),
								'closed' => esc_html__( 'Closed', 'learndash' ),
								'free' => esc_html__( 'Free', 'learndash' ),
								'paynow' => esc_html__( 'Buy Now', 'learndash' ),
								'subscribe'	=> esc_html__( 'Recurring', 'learndash' ),
							),
							'default' => 'open',
							'help_text' => esc_html__( 'Is it open to all, free join, one time purchase, or a recurring subscription?', 'learndash' ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args' => array(
								'schema' => array(
									'type' => 'string',
									'default' => 'open',
									'enum' => array(
										'open',
										'closed',
										'free',
										'buynow',
										'subscribe',
									),
								)
							)
						),
						'custom_button_label' => array(
							'name' => esc_html__( 'Custom Button Label', 'learndash' ),
							'type' => 'text',
							'placeholder'	=> esc_html__( 'Optional', 'learndash' ),
							'show_in_rest' => LearnDash_REST_API::enabled()
						),
						'custom_button_url' => array(
							'name' => esc_html__( 'Custom Button URL', 'learndash' ),
							'type' => 'text',
							'placeholder'	=> esc_html__( 'Optional', 'learndash' ),
							'help_text' => sprintf( esc_html_x( 'Entering a URL in this field will enable the "%s" button. The button will not display if this field is left empty. Relative URL beginning with a slash is acceptable.', 'placeholders: "Take This Course" button label', 'learndash' ), LearnDash_Custom_Label::get_label( 'button_take_this_course' )),
							'show_in_rest' => LearnDash_REST_API::enabled()
						),
						'course_price' => array(
							'name' => sprintf( esc_html_x( '%s Price', 'Course Price Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'text',
							'help_text' => sprintf( esc_html_x( 'Enter %s price here. Leave empty if the %s is free.', 'Enter course price here. Leave empty if the course is free.', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_price_billing_cycle' => array(
							'name' => esc_html__( 'Billing Cycle', 'learndash' ),
							'type' => 'html',
							'default' => $this->learndash_course_price_billing_cycle_html(),
							'help_text' => esc_html__( 'Billing Cycle for the recurring payments in case of a subscription.', 'learndash' ),
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
						'course_access_list' => array(
							'name' => sprintf( esc_html_x( '%s Access List', 'Course Access List Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'textarea',
							'help_text' => esc_html__( 'This field is auto-populated with the UserIDs of those who have access to this course.', 'learndash' ),
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
						'course_lesson_orderby' => array(
							'name' => sprintf( esc_html_x( 'Sort %s By', 'Sort Lesson By Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type' => 'select',
							'initial_options' => array(	
								''		=> esc_html__( 'Use Default', 'learndash' ) . ' ( '. $course_lessons_options_labels['orderby'] .' )',
								'title'	=> esc_html__( 'Title', 'learndash' ),
								'date'	=> esc_html__( 'Date', 'learndash' ),
								'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
							),
							'default' => '',
							'help_text' => sprintf( esc_html_x( 'Choose the sort order of %1$s in this %2$s.', 'Choose the sort order of lessons in this course.', 'learndash' ), learndash_get_custom_label_lower('lessons'), learndash_get_custom_label_lower('course') ),
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
						'course_lesson_order' => array(
							'name' => sprintf( esc_html_x( 'Sort %s Direction', 'Sort Lesson Direction Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type' => 'select',
							'initial_options' => array(	
								''		=> esc_html__( 'Use Default', 'learndash' )  . ' ( '. $course_lessons_options_labels['order'] .' )',
								'ASC'	=> esc_html__( 'Ascending', 'learndash' ),
								'DESC'	=> esc_html__( 'Descending', 'learndash' ),
							),
							'default' => '',
							'help_text' => sprintf( esc_html_x( 'Choose the sort order of %1$s in this %2$s.', 'Choose the sort order of lessons in this course.', 'learndash' ), learndash_get_custom_label_lower('lessons'), learndash_get_custom_label_lower('course' ) ),
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
						
						'course_lesson_per_page' => array(
							'name' => sprintf( esc_html_x( '%s Per Page', 'placeholder: Lessons', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ) ),
							'type' => 'select',
							'initial_options' => array(	
								''			=> esc_html__( 'Use Default', 'learndash' ) . ' ( '. LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Lessons_Display_Order', 'posts_per_page' ) . ' )',
								'CUSTOM'	=> esc_html__( 'Custom', 'learndash' ),
							),
							'default' => '',
							'help_text' => sprintf( esc_html_x( 'Choose the per page of %s in this %s.', 'Choose the per page of lessons in this course.', 'learndash' ), learndash_get_custom_label_lower('lessons'), learndash_get_custom_label_lower('course')),
							'show_in_rest' => false,
						),
						'course_lesson_per_page_custom' => array( 
							'name' => sprintf( esc_html_x( 'Custom %s Per Page', 'Custom lessons per page', 'learndash' ), LearnDash_Custom_Label::get_label('lessons') ), 
							'type' => 'number', 
							'min' => '0',
							'help_text' => sprintf( esc_html_x( 'Enter %s per page value. Set to zero for no paging', 'Enter lesson per page value. Set to zero for no paging', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'default' => 0,
							'show_in_rest' => false,
						),
						
						'course_prerequisite_enabled' => array(
							'name' => sprintf( esc_html_x( 'Enable %s Prerequisites', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'checkbox',
							'checked_value' => 'on',
							'help_text' => esc_html__( 'Leave this field unchecked if prerequisite not used.', 'learndash' ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args' => array(
								'schema' => array(
									'type' => 'boolean',
									'default' => false,
								)
							)
						),
						'course_prerequisite' => array( 
							'name' => sprintf( esc_html_x( '%s Prerequisites', 'Course prerequisites Label', 'learndash' ),LearnDash_Custom_Label::get_label( 'course' ) ), 
							'type' => 'multiselect', 
							'help_text' => sprintf( esc_html_x( 'Select one or more %1$s as prerequisites to view this %2$s', 'Select one or more course as prerequisites to view this course', 'learndash' ), learndash_get_custom_label_lower('course'), learndash_get_custom_label_lower('course') ), 
							'lazy_load'	=>	true,
							'initial_options' => '', 
							'default' => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args' => array(
								'schema' => array(
									'default' => array(),
									'type' => 'array',
								)
							)
						),
						'course_prerequisite_compare' => array(
							'name' => sprintf( esc_html_x( '%s Prerequisites Compare', 'Course Prerequisites Compare Label', 'learndash' ),LearnDash_Custom_Label::get_label( 'course' ) ), 
							'type' => 'select',
							'initial_options' => array(	
								'ANY'	=> esc_html__( 'ANY (default) - The student must complete at least one of the prerequisites', 'learndash' ),
								'ALL'	=> esc_html__( 'ALL - The student must complete all the prerequisites', 'learndash' ),
							),
							'default' => 'ANY',
							'help_text' => sprintf( esc_html_x( 'Select how to compare the selected prerequisite %s.', 'pleaceholder: Course', 'learndash' ),learndash_get_custom_label_lower('course') ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_points_enabled' => array(
							'name' => sprintf( esc_html_x( 'Enable %s Points', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'checkbox',
							'help_text' => esc_html__( 'Leave this field unchecked if points not used.', 'learndash' ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args' => array(
								'schema' => array(
									'type' => 'boolean',
								)
							)
						),
						'course_points' => array(
							'name' => sprintf( esc_html_x( '%s Points', 'Course Points', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'number',
							'step' => 'any',
							'min' => '0',
							'help_text' => sprintf( esc_html_x( 'Enter the number of points a user will receive for this %s.', 'placeholder: course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_points_access' => array(
							'name' => sprintf( esc_html_x( '%s Points Access', 'Course Points Access', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'number',
							'step' => 'any',
							'min' => '0',
							'help_text' => sprintf( esc_html_x( 'Enter the number of points a user must have to access this %s.', 'placeholder: course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_disable_lesson_progression' => array(
							'name' => sprintf( esc_html_x( 'Disable %s Progression', 'Disable Lesson Progression Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type' => 'checkbox',
							'default' => 0,
							'help_text' => sprintf( esc_html_x( 'Disable the feature that allows attempting %s only in allowed order.', 'Disable the feature that allows attempting lessons only in allowed order.', 'learndash' ), learndash_get_custom_label_lower('lessons') ), 
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'expire_access' => array(
							'name' => esc_html__( 'Expire Access', 'learndash' ),
							'type' => 'checkbox',
							'help_text' => esc_html__( 'Leave this field unchecked if access never expires.', 'learndash' ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'expire_access_days' => array(
							'name' => esc_html__( 'Expire Access After (days)', 'learndash' ),
							'type' => 'number',
							'min' => '0',
							'help_text' => sprintf( esc_html_x( 'Enter the number of days a user has access to this %s.', 'Enter the number of days a user has access to this course.', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'expire_access_delete_progress' => array(
							'name' => sprintf( esc_html_x( 'Delete %1$s and %2$s Data After Expiration', 'Delete Course and Quiz Data After Expiration Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'type' => 'checkbox',
							'help_text' => sprintf( esc_html_x( "Select this option if you want the user's %s progress to be deleted when their access expires.", 'placeholder: course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_disable_content_table' => array(
							'name' => sprintf( esc_html_x( 'Hide %s Content Table', 'Hide Course Content Table Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type' => 'checkbox',
							'default' => 0,
							'help_text' => sprintf( esc_html_x( 'Hide %s Content table when user is not enrolled.', 'Hide Course Content table when user is not enrolled.', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), 
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
						
						'certificate' => array( 
							'name' => esc_html__( 'Associated Certificate', 'learndash' ), 
							'type' => 'select', 
							'help_text' => sprintf( esc_html_x( 'Select a certificate to be awarded upon %s completion (optional).', 'Select a certificate to be awarded upon course completion (optional).', 'learndash' ), learndash_get_custom_label_lower('course') ), 
							'default' => '',
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
					),
				),
				'sfwd-lessons' => array(
					'plugin_name' => LearnDash_Custom_Label::get_label( 'lesson' ),
					'slug_name' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'lessons' ),
					'post_type' => 'sfwd-lessons',
					'template_redirect' => true,
					 'taxonomies' => $lesson_taxonomies,
					'cpt_options' => array( 
						'has_archive' => learndash_post_type_has_archive( 'sfwd-lessons' ),
						'supports' => array_merge(
							array( 'title', 'editor', 'author', 'page-attributes' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_CPT', 'supports' )
						),
						'labels' => $lesson_labels , 
						'capability_type' => 'course', 
						'exclude_from_search' => (LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_CPT', 'include_in_search' ) !== 'yes' ) ? true : false,
						'capabilities' => $course_capabilities, 
						'map_meta_cap' => true,
						'show_in_rest' => LearnDash_REST_API::enabled( 'sfwd-lessons' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-lessons' ),
						//'rest_base' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'lessons' ),
						'rest_controller_class' => LearnDash_REST_API::get_controller( 'sfwd-lessons' ),
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
					'fields' => array(
						'lesson_materials' => array(
							'name' => sprintf( esc_html_x( '%s Materials', 'Lesson Materials Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type' => 'textarea',
							'help_text' => sprintf( esc_html_x( 'Options for %s materials', 'Options for lesson materials', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args' => array(
								'schema' => array(
									'type' => 'html'
								) 
							)
						),
						'course' => array( 
							'name' => sprintf( esc_html_x( 'Associated %s', 'Associated Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), 
							'type' => 'select', 
							'lazy_load'	=> true,
							'help_text' => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'Associate this lesson with a course.', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'default' => '' , 
							'required' => true,
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
						'forced_lesson_time' => array( 
							'name' => sprintf( esc_html_x( 'Forced %s Timer', 'Forced Lesson Timer Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ), 
							'type' => 'text', 
							'help_text' => sprintf( esc_html_x( 'Minimum time a user has to spend on %s page before it can be marked complete. Examples: 40 (for 40 seconds), 20s, 45sec, 2m 30s, 2min 30sec, 1h 5m 10s, 1hr 5min 10sec', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ), 
							'default' => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_upload' => array( 
							'name' => esc_html__( 'Upload Assignment', 'learndash' ), 
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this if you want to make it mandatory to upload assignment', 'learndash' ), 
							'default' => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'auto_approve_assignment' => array( 
							'name' => esc_html__( 'Auto Approve Assignment', 'learndash' ), 
							'type' => 'checkbox',
							'help_text' => esc_html__( 'Check this if you want to auto-approve the uploaded assignment', 'learndash' ), 
							'default' => 'on',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_count' => array( 
							'name' => esc_html__( 'Limit number of uploaded files', 'learndash' ), 
							'type' => 'number', 
							'placeholder' => esc_html__('Default is 1', 'learndash' ),
							'help_text' => esc_html__( 'Enter the maximum number of assignment uploads allowed. Default is 1. Use 0 to unlimited.', 'learndash' ), 
							'default' => '1',
							'class' => 'small-text',
							'min' => '1',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_deletion_enabled' => array(
							'name' => esc_html__( 'Allow Student to Delete own Assignment(s)', 'learndash' ),
							'type' => 'checkbox',
							'help_text' => esc_html__( 'Allow Student to Delete own Assignment(s)', 'learndash' ),
							'default' => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						
						'lesson_assignment_points_enabled' => array(
							'name' => esc_html__( 'Award Points for Assignment', 'learndash' ),
							'type' => 'checkbox',
							'help_text' => esc_html__( 'Allow this assignment to be assigned points when it is approved.', 'learndash' ),
							'default' => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_points_amount' => array(
							'name' => esc_html__( 'Set Number of Points for Assignment', 'learndash' ),
							'type' => 'number',
							'min' => 0,
							'help_text' => esc_html__( 'Assign the max amount of points someone can earn for this assignment.', 'learndash' ),
							'default' => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_extensions' => array( 
							'name' => esc_html__( 'Allowed File Extensions', 'learndash' ), 
							'type' => 'text', 
							'placeholder' => esc_html__('Example: pdf, xls, zip', 'learndash' ),
							'help_text' => esc_html__( 'Enter comma-separated list of allowed file extensions: pdf, xls, zip or leave blank for any.', 'learndash' ), 
							'default' => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_size' => array( 
							'name' => esc_html__( 'Allowed File Size', 'learndash' ), 
							'type' => 'text', 
							'placeholder' => sprintf( esc_html_x('Maximum upload file size: %s', 'placeholder: PHP file upload size', 'learndash'), ini_get('upload_max_filesize') ),
							'help_text' => sprintf( esc_html_x( 'Enter maximim file upload size. Example: 100KB, 2M, 2MB, 1G. Maximum upload file size: %s', 'placeholder: PHP file upload size', 'learndash' ),  ini_get('upload_max_filesize') ),
							'default' => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						
						'sample_lesson' => array( 
							'name' => sprintf( esc_html_x( 'Sample %s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ), 
							'type' => 'checkbox', 
							'help_text' => sprintf( esc_html_x( 'Check this if you want this %1$s and all its %2$s to be available for free.', 'Check this if you want this lesson and all its topics to be available for free.', 'learndash' ), learndash_get_custom_label_lower('lesson'), learndash_get_custom_label_lower('topics') ),
							'default' => 0,
						),
						'visible_after' => array( 
							'name' => sprintf( esc_html_x( 'Make %s visible X Days After Sign-up', 'Make Lesson Visible X Days After Sign-up', 'learndash' ), LearnDash_Custom_Label::get_label('lesson') ), 
							'type' => 'number', 
							'class' => 'small-text',
							'min' => '0',
							'help_text' => sprintf( esc_html_x( 'Make %s visible ____ days after sign-up', 'Make lesson visible ____ days after sign-up', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'default' => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'visible_after_specific_date' => array( 
							'name' => sprintf( esc_html_x( 'Make %s Visible on Specific Date', 'Make Lesson Visible on Specific Date', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type' => 'wp_date_selector', 
							'class' => 'learndash-datepicker-field',
							'help_text' => sprintf( esc_html_x( 'Set the date that you would like this %s to become available.', 'Set the date that you would like this lesson to become available.','learndash' ), learndash_get_custom_label_lower('lesson') ), 
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
					),
					/* 	The Lesson default_options section has been depricated and replace with 
						the new Settings Section LearnDash_Settings_Section_Lessons_Display_Order class
					*/
					/*
					'default_options' => array(
						'orderby' => array(
							'name' => esc_html__( 'Sort By', 'learndash' ),
							'type' => 'select',
							'initial_options' => array(	
								''		=> esc_html__( 'Select a choice...', 'learndash' ),
								'title'	=> esc_html__( 'Title', 'learndash' ),
								'date'	=> esc_html__( 'Date', 'learndash' ),
								'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
							),
							'default' => 'date',
							'help_text' => esc_html__( 'Choose the sort order.', 'learndash' ),
						),
						'order' => array(
							'name' => esc_html__( 'Sort Direction', 'learndash' ),
							'type' => 'select',
							'initial_options' => array(	
								''		=> esc_html__( 'Select a choice...', 'learndash' ),
								'ASC'	=> esc_html__( 'Ascending', 'learndash' ),
								'DESC'	=> esc_html__( 'Descending', 'learndash' ),
							),
							'default' => 'DESC',
							'help_text' => esc_html__( 'Choose the sort order.', 'learndash' ),
						),
						'posts_per_page' => array(
							'name' => esc_html__( 'Posts Per Page', 'learndash' ),
							'type' => 'text',
							'help_text' => esc_html__( 'Enter the number of posts to display per page.', 'learndash' ),
							'default' => $posts_per_page,
						),
					)
					*/
				),
				'sfwd-topic' => array(
					'plugin_name' => sprintf( esc_html_x( '%1$s %2$s', 'Lesson Topic Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
					'slug_name' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'topics' ),
					'post_type' => 'sfwd-topic',
					'template_redirect' => true,
					'taxonomies' => $topic_taxonomies, //array( 'courses' => esc_html__( 'Manage Course Associations', 'learndash' ) ),
					'cpt_options' => array( 
						'supports' => array_merge(
							array( 'title', 'editor', 'author', 'page-attributes' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_CPT', 'supports' )
						),
						'has_archive' => learndash_post_type_has_archive( 'sfwd-topic' ),
						'labels' => $lesson_topic_labels, 
						'capability_type' => 'course', 
						'exclude_from_search' => (LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Topics_CPT', 'include_in_search' ) !== 'yes' ) ? true : false, 
						'capabilities' => $course_capabilities, 
						'map_meta_cap' => true,
						'show_in_rest' => LearnDash_REST_API::enabled( 'sfwd-topic' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-topic' ),
						//'rest_base' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'topics' ),
						'rest_controller_class' => LearnDash_REST_API::get_controller( 'sfwd-topic' ),
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Topic.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'fields' => array(
						'topic_materials' => array(
							'name' => sprintf( esc_html_x( '%s Materials', 'Topic Materials Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
							'type' => 'textarea',
							'help_text' => sprintf( esc_html_x( 'Options for %s materials', 'Options for topic materials', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args' => array(
								'schema' => array(
									'type' => 'html'
								) 
							)
						),
						
						'course' => array( 
							'name' => sprintf( esc_html_x( 'Associated %s', 'Associated Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), 
							'type' => 'select', 
							'lazy_load'	=> true,
							'help_text' => sprintf( esc_html_x( 'Associate this %s with a %s.', 'placeholders: topic, course', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ), LearnDash_Custom_Label::get_label( 'course' ) ), 
							'default' => '', 
							//'initial_options' => $this->select_a_course( 'sfwd-topic' ),	// Move to topic_display_settings
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
						'lesson' => array( 
							'name' => sprintf( esc_html_x( 'Associated %s', 'Associated Lesson Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ), 
							'type' => 'select', 
							'lazy_load'	=> true,
							'help_text' => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'Associate this topic with a lesson.', 'learndash' ), LearnDash_Custom_Label::get_label('topic'), LearnDash_Custom_Label::get_label('lesson') ),
							'default' => '' , 
							//'initial_options' => $this->select_a_lesson(), // // Move to topic_display_settings
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),
						'forced_lesson_time' => array( 
							'name' => sprintf( esc_html_x( 'Forced %s Timer', 'Forced Topic Timer Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ), 
							'type' => 'text', 
							'help_text' => sprintf( esc_html_x( 'Minimum time a user has to spend on %s page before it can be marked complete. Examples: 40 (for 40 seconds), 20s, 45sec, 2m 30s, 2min 30sec, 1h 5m 10s, 1hr 5min 10sec', 'Minimum time a user has to spend on Topic page Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ), 
							'default' => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_upload' => array( 
							'name' => esc_html__( 'Upload Assignment', 'learndash' ), 
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this if you want to make it mandatory to upload assignment', 'learndash' ), 
							'default' => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'auto_approve_assignment' => array( 
							'name' => esc_html__( 'Auto Approve Assignment', 'learndash' ), 
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this if you want to auto-approve the uploaded assignment', 'learndash' ), 
							'default' => 'on',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_count' => array( 
							'name' => esc_html__( 'Limit number of uploaded files', 'learndash' ), 
							'type' => 'number', 
							'placeholder' => esc_html__('Default is 1', 'learndash' ),
							'help_text' => esc_html__( 'Enter the maximum number of assignment uploads allowed. Default is 1. Use 0 to unlimited.', 'learndash' ), 
							'default' => '1',
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'class' => 'small-text',
							'min' => '1',
						),
						'lesson_assignment_deletion_enabled' => array(
							'name' => esc_html__( 'Allow Student to Delete own Assignment(s)', 'learndash' ),
							'type' => 'checkbox',
							'help_text' => esc_html__( 'Allow Student to Delete own Assignment(s)', 'learndash' ),
							'default' => 0,
						),
						
						'lesson_assignment_points_enabled' => array(
							'name' => esc_html__( 'Award Points for Assignment', 'learndash' ),
							'type' => 'checkbox',
							'help_text' => esc_html__( 'Allow this assignment to be assigned points when it is approved.', 'learndash' ),
							'default' => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_points_amount' => array(
							'name' => esc_html__( 'Set Number of Points for Assignment', 'learndash' ),
							'type' => 'number',
							'min' => 0,
							'help_text' => esc_html__( 'Assign the max amount of points someone can earn for this assignment.', 'learndash' ),
							'default' => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						
						'assignment_upload_limit_extensions' => array( 
							'name' => esc_html__( 'Allowed File Extensions', 'learndash' ), 
							'type' => 'text', 
							'placeholder' => esc_html__('Example: pdf,xls,zip', 'learndash' ),
							'help_text' => esc_html__( 'Enter comma-separated list of allowed file extensions: pdf,xls,zip or leave blank for any.', 'learndash' ), 
							'default' => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_size' => array( 
							'name' => esc_html__( 'Allowed File Size', 'learndash' ), 
							'type' => 'text', 
							'placeholder' => sprintf( esc_html_x('Maximum upload file size: %s', 'placeholder: PHP file upload size', 'learndash'), ini_get('upload_max_filesize') ),
							'help_text' => sprintf( esc_html_x( 'Enter maximim file upload size. Example: 100KB, 2M, 2MB, 1G. Maximum upload file size: %s', 'placeholder: PHP file upload size', 'learndash' ),  ini_get('upload_max_filesize') ),
							'default' => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),

						// 'visible_after' => array( 
						// 	'name' => esc_html__( 'Make lesson visible X days after sign-up', 'learndash' ), 
						// 	'type' => 'text', 
						// 	'help_text' => esc_html__( 'Make lesson visible ____ days after sign-up', 'learndash' ), 
						// 	'default' => 0,
						// ),
					),
					'default_options' => array(
						'orderby' => array(
							'name' => esc_html__( 'Sort By', 'learndash' ),
							'type' => 'select',
							'initial_options' => array(	''		=> esc_html__( 'Select a choice...', 'learndash' ),
								'title'	=> esc_html__( 'Title', 'learndash' ),
								'date'	=> esc_html__( 'Date', 'learndash' ),
								'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
							),
							'default' => 'date',
							'help_text' => esc_html__( 'Choose the sort order.', 'learndash' ),
							),
						'order' => array(
							'name' => esc_html__( 'Sort Direction', 'learndash' ),
							'type' => 'select',
							'initial_options' => array(	''		=> esc_html__( 'Select a choice...', 'learndash' ),
									'ASC'	=> esc_html__( 'Ascending', 'learndash' ),
									'DESC'	=> esc_html__( 'Descending', 'learndash' ),
							),
							'default' => 'DESC',
							'help_text' => esc_html__( 'Choose the sort order.', 'learndash' ),
						),
					),
				),
				'sfwd-quiz' => array(
					'plugin_name' => LearnDash_Custom_Label::get_label( 'quiz' ),
					'slug_name' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'quizzes' ),
					'post_type' => 'sfwd-quiz',
					'template_redirect' => true,
					 'taxonomies' => $quiz_taxonomies
					 /*array( 
						 'category' => 'category', 
						 'post_tag' =>'post_tag' 
					 )
					 */,
					'cpt_options' => array(	
						'has_archive' => learndash_post_type_has_archive( 'sfwd-quiz' ),
						'hierarchical' => false, 
						'supports' => array_merge(
							array( 'title', 'editor', 'author', 'page-attributes' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_CPT', 'supports' )
						),
						'labels' => $quiz_labels, 
						'capability_type' => 'course', 
						'exclude_from_search' => (LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Quizzes_CPT', 'include_in_search' ) !== 'yes' ) ? true : false, 
						'capabilities' => $course_capabilities, 
						'map_meta_cap' => true,
						'show_in_rest' => LearnDash_REST_API::enabled( 'sfwd-quiz' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-quiz' ),
						//'rest_base' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'quizzes' ),
						'rest_controller_class' => LearnDash_REST_API::get_controller( 'sfwd-quiz' ),
						
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'fields' => array(
						'quiz_materials' => array(
							'name' => sprintf( esc_html_x( '%s Materials', 'Quiz Materials Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'type' => 'textarea',
							'help_text' => sprintf( esc_html_x( 'Options for %s materials', 'Options for quiz materials', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args' => array(
								'schema' => array(
									'type' => 'html'
								) 
							)
						),
						
						'repeats' => array( 
							'name' => esc_html__( 'Repeats', 'learndash' ), 
							'type' => 'text', 
							'help_text' => sprintf( esc_html_x( 'Number of repeats allowed for %s. Blank = unlimited attempts. 0 = 1 attempt, 1 = 2 attempts, etc.', 'Number of repeats allowed for quiz', 'learndash' ), learndash_get_custom_label_lower('quiz') ),
							'default' => '',
						),
						'threshold' => array( 
							'name' => esc_html__( 'Certificate Threshold', 'learndash' ), 
							'type' => 'text', 
							'help_text' => esc_html__( 'Minimum score required to award a certificate, between 0 and 1 where 1 = 100%.', 'learndash' ), 
							'default' => '0.8',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'passingpercentage' => array( 
							'name' => esc_html__( 'Passing Percentage', 'learndash' ), 
							'type' => 'text', 
							'help_text' => sprintf( esc_html_x( 'Passing percentage required to pass the %s (number only). e.g. 80 for 80%%.', 'Passing percentage required to pass the quiz (number only). e.g. 80 for 80%.', 'learndash' ), learndash_get_custom_label_lower('quiz') ),
							'default' => '80',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course' => array( 
							'name' => sprintf( esc_html_x( 'Associated %s', 'Associated Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), 
							'type' => 'select', 
							'lazy_load'	=> true,
							'help_text' => sprintf( esc_html_x( 'Associate this %s with a %s.', 'Associate this %s with a course.', 'learndash' ), LearnDash_Custom_Label::get_label('quiz'), LearnDash_Custom_Label::get_label('course') ),
							'default' => '', 
							//'initial_options' => $this->select_a_course( 'sfwd-quiz' ), // Move to quiz_display_settings
						),
						'lesson' => array( 
							'name' => sprintf( esc_html_x( 'Associated %s', 'Associated Lesson Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ), 
							'type' => 'select', 
							'help_text' => sprintf( esc_html_x( 'Associate this %s with a %s.', 'Associate this quiz with a lesson.', 'learndash' ), LearnDash_Custom_Label::get_label('quiz'), LearnDash_Custom_Label::get_label('lesson') ),
							'default' => '', 
						),
						'certificate' => array( 
							'name' => esc_html__( 'Associated Certificate', 'learndash' ), 
							'type' => 'select', 
							'help_text' => sprintf( esc_html_x( 'Optionally associate a %s with a certificate.', 'Optionally associate a quiz with a certificate.', 'learndash' ), learndash_get_custom_label_lower('quiz') ),
							'default' => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'quiz_pro' => array( 
							'name' => esc_html__( 'Associated Settings', 'learndash' ), 
							'type' => 'select', 
							'help_text' => sprintf( esc_html_x( 'If you imported a %s, use this field to select it. Otherwise, create new settings below. After saving or publishing, you will be able to add questions.', 'If you imported a quiz, use this field to select it. Otherwise, create new settings below. After saving or publishing, you will be able to add questions.', 'learndash' ), learndash_get_custom_label_lower('quiz') )  . '<a style="display:none" id="advanced_quiz_preview" class="wpProQuiz_prview" href="#">'.esc_html__( 'Preview', 'learndash' ).'</a>',
							//'initial_options' => ( array( 0 => esc_html__( '-- Select Settings --', 'learndash' ) ) + LD_QuizPro::get_quiz_list() ), // Move to quiz_display_settings
							'default' => '',
						),
					),
					/*
					'metaboxes' => array(
						'learndash_quiz_advanced' => array(
							'title' => sprintf(
								// translators: placeholder: Quiz.
								esc_html_x( 'LearnDash %s Advanced Settings', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) 
							),
							'fields' => array(
								'course_prerequisite_enabled' => array(
									'name' => sprintf(
										// translators: placeholder: Course.
										esc_html_x( 'Enable %s Prerequisites', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' )
									),
									'type' => 'checkbox',
									'checked_value' => 'on',
									'help_text' => esc_html__( 'Leave this field unchecked if prerequisite not used.', 'learndash' ),
									'show_in_rest' => LearnDash_REST_API::enabled(),
									'rest_args' => array(
										'schema' => array(
											'type' => 'boolean',
											'default' => false,
										),
									)
								),
							),
						),
					),
					*/
					'default_options' => array()
				),
				'sfwd-question' => array(
					'plugin_name' => LearnDash_Custom_Label::get_label( 'question' ),
					'slug_name' => 'sfwd-question', //LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'questions' ),
					'post_type' => 'sfwd-question',
					'template_redirect' => false,
					'taxonomies' => $question_taxonomies,
					'cpt_options' => array(	
						'public' => false,
						'hierarchical' => false, 
						'supports' => array( 'title', 'thumbnail', 'editor' , 'author', 'revisions', 'page-attributes' ), 
						'labels' => $question_labels, 
						'capability_type' => 'course', 
						'exclude_from_search' => true, 
						'show_in_nav_menus' => false,
						'capabilities' => $course_capabilities, 
						'map_meta_cap' => true,
						'show_in_rest' => true, //LearnDash_REST_API::enabled( 'sfwd-question' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-question' ),
						//'rest_base' => LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'quizzes' ),
						'rest_controller_class' => LearnDash_REST_API::get_controller( 'sfwd-question' ),
						
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Question
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'Question' )
					),
					'fields' => array(
						'quiz' => array( 
							'name' => sprintf( esc_html_x( 'Associated %s', 'Associated Quiz Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ), 
							'type' => 'select', 
							'lazy_load'	=> true,
							'help_text' => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'Associate this question with a quiz.', 'learndash' ), LearnDash_Custom_Label::get_label( 'question' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'default' => '' , 
							'required' => true,
							'show_in_rest' => false, //LearnDash_REST_API::enabled(),
						),

					),
					'default_options' => array()
				),				
			);

			$cert_defaults = array(
				'shortcode_options' => array(
					'name' => 'Shortcode Options',
					'type' => 'html',
					'default' => '',
					'save' => false,
					'label' => 'none',
				),
			);

			$lcl_certificates = 'Certificates';
			$lcl_certificate = 'Certificate';
			$certificates_labels = array(
				'name' 					=> 	$lcl_certificates,
				'singular_name' 		=> 	$lcl_certificate,
				'add_new' 				=> 	esc_html_x( 'Add New', 'Add New Certificate Label', 'learndash' ),
				'add_new_item' 			=> 	sprintf( esc_html_x( 'Add New %s', 'Add New Certificate Label', 'learndash' ), $lcl_certificate ),
				'edit_item' 			=> 	sprintf( esc_html_x( 'Edit %s', 'Edit Certificate Label', 'learndash' ), $lcl_certificate ),
				'new_item' 				=> 	sprintf( esc_html_x( 'New %s', 'New Certificate Label', 'learndash' ), $lcl_certificate ),
				'all_items' 			=> 	$lcl_certificates,
				'view_item' 			=> 	sprintf( esc_html_x( 'View %s', 'View Certificate Label', 'learndash' ), $lcl_certificate ),
				'search_items' 			=> 	sprintf( esc_html_x( 'Search %s', 'Search Certificate Label', 'learndash' ), $lcl_certificate ),
				'not_found' 			=> 	sprintf( esc_html_x( 'No %s found', 'No Certificate found Label', 'learndash' ), $lcl_certificate ),
				'not_found_in_trash' 	=> 	sprintf( esc_html_x( 'No %s found in Trash', 'No Certificates found in Trash Label', 'learndash' ), $lcl_certificates ),
				'parent_item_colon' 	=> 	'',
				'menu_name'				=>	$lcl_certificates,
				'item_published'		=>	sprintf( esc_html_x( '%s Published', 'Certificate Published', 'learndash' ), $lcl_certificate ),
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'Certificate Published Privately', 'learndash' ), $lcl_certificate ),
				'item_reverted_to_draft' => sprintf( esc_html_x( '%s Reverted to Draft', 'Certificate Reverted to Draft', 'learndash' ), $lcl_certificate ),
				'item_scheduled'		=>	sprintf( esc_html_x( '%s Scheduled', 'Certificate Scheduled', 'learndash' ), $lcl_certificate ),
				'item_updated'			=>	sprintf( esc_html_x( '%s Updated', 'Certificate Updated', 'learndash' ), $lcl_certificate ),
			);

			$this->post_args['sfwd-certificates'] = array(
				'plugin_name' => esc_html__( 'Certificates', 'learndash' ),
				'slug_name' => 'certificates',
				'post_type' => 'sfwd-certificates',
				'template_redirect' => false,
				'fields' => array(),
				'options_page_title' => esc_html__( 'LearnDash Certificates Options', 'learndash' ),
				'default_options' => $cert_defaults,
				'cpt_options' => array( 
					'labels' => $certificates_labels,
					'exclude_from_search' => true, 
					'has_archive' => false, 
					'hierarchical' => false, 
					'supports' => array( 'title', 'editor', 'thumbnail' , 'author',  'revisions'), 
					'show_in_nav_menus' => false,
					'capability_type' => 'course', 
					'capabilities' => $course_capabilities, 
					'map_meta_cap' => true,
					'show_in_rest' 			=> false, //LearnDash_REST_API::enabled(),
					//'rest_base' 			=> LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'courses' ),
					//'rest_controller_class' => 'LD_REST_Posts_Certificates_Controller_V1'
				)
			);

			if ( learndash_is_admin_user( ) ) {
				$this->post_args['sfwd-transactions'] = array(
					'plugin_name' => esc_html__( 'Transactions', 'learndash' ),
					'slug_name' => 'transactions',
					'post_type' => 'sfwd-transactions',
					'template_redirect' => false,
					'options_page_title' => esc_html__( 'LearnDash Transactions Options', 'learndash' ),
					'cpt_options' => array( 
						'supports' => array ( 'title', 'custom-fields' ), 
						'exclude_from_search' => true, 
						'publicly_queryable' => false, 
						'show_in_nav_menus' => false, 
						'show_in_admin_bar' => false,
						//'show_in_menu'	=> 'edit.php?post_type=sfwd-courses'
					),
					'fields' => array(),
					'default_options' => array( 
						null => array( 
							'type' => 'html', 
							'save' => false, 
							'default' => esc_html__( 'Click the Export button below to export the transaction list.', 'learndash' ),
						)
					)
				);

				add_action( 'admin_init', array( $this, 'trans_export_init' ) );
			}
			
			// Added in v2.5.4 to hide the lesson, topic and quiz post type from nav menu when shared steps enabed. 
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$this->post_args['sfwd-lessons']['cpt_options']['show_in_nav_menus'] = false;
				$this->post_args['sfwd-topic']['cpt_options']['show_in_nav_menus'] = false;
				$this->post_args['sfwd-quiz']['cpt_options']['show_in_nav_menus'] = false;
			}
			
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				if ( isset( $this->post_args['sfwd-courses']['fields']['course_lesson_orderby'] ) ) 
					unset( $this->post_args['sfwd-courses']['fields']['course_lesson_orderby'] );
				if ( isset( $this->post_args['sfwd-courses']['fields']['course_lesson_order'] ) ) 
					unset( $this->post_args['sfwd-courses']['fields']['course_lesson_order'] );
			}

			// Remove the filter to prevent Course Grid from adding a 'Short Description' field to the legacy metabox.
			// See CG-118
			remove_filter( 'learndash_post_args', 'learndash_course_grid_post_args' );

			/**
			 * Filter $post_args used to create the custom post types and everything
			 * associated with them.
			 * 
			 * @since 2.1.0
			 * 
			 * @param  array  $post_args       
			 */
			$this->post_args = apply_filters( 'learndash_post_args', $this->post_args );

			add_action( 'admin_init', array( $this, 'quiz_export_init' ) );
			add_action( 'admin_init', array( $this, 'course_export_init' ) );
			
			//add_action( 'show_user_profile', array( $this, 'show_course_info' ) );
			//add_action( 'edit_user_profile', array( $this, 'show_course_info' ) );

			foreach ( $this->post_args as $p ) {				
				$this->post_types[ $p['post_type'] ] = new SFWD_CPT_Instance( $p );
			}

			//add_action( 'publish_sfwd-courses', array( $this, 'add_course_tax_entry' ), 10, 2 );
			add_action( 'init', array( $this, 'tax_registration' ), 11 );
			
			//$sfwd_quiz = $this->post_types['sfwd-quiz'];
			//$quiz_prefix = $sfwd_quiz->get_prefix();
			//add_filter( "{$quiz_prefix}display_settings", array( $this, 'quiz_display_settings' ), 10, 3 );
			
			$sfwd_question = $this->post_types['sfwd-question'];
			$question_prefix = $sfwd_question->get_prefix();
			add_filter( "{$question_prefix}display_settings", array( $this, 'question_display_settings' ), 10, 3 );
			
			//$sfwd_courses = $this->post_types['sfwd-courses'];
			//$courses_prefix = $sfwd_courses->get_prefix();
			//add_filter( "{$courses_prefix}display_settings", array( $this, 'course_display_settings' ), 10, 3 );

			//$sfwd_lessons = $this->post_types['sfwd-lessons'];
			//$lessons_prefix = $sfwd_lessons->get_prefix();
			//add_filter( "{$lessons_prefix}display_settings", array( $this, 'lesson_display_settings' ), 10, 3 );

			//$sfwd_topics = $this->post_types['sfwd-topic'];
			//$topics_prefix = $sfwd_topics->get_prefix();
			//add_filter( "{$topics_prefix}display_settings", array( $this, 'topic_display_settings' ), 10, 3 );
		}

		/**
		 * Returns output of users course information for bottom of profile
		 *
		 * @since 2.1.0
		 * 
		 * @param  int 		$user_id 	user id
		 * @return string          		output of course information
		 */
		static function get_course_info( $user_id, $atts = array() ) {
			
			$atts_defaults = apply_filters( 
				'learndash_ld_course_list_shortcode_defaults', 
				array(
					'return' => false, // Set to true to return the array data nstead of calling the template for output. 
					// This function essentially produces the output of three sections. Registered Courses, 
					// Course Progress and Quiz Attempts. This parameters lets us control which section to 
					// return or all.  
					'type' => array('registered','course','quiz' ), 
				
					// Defaults
					'num' => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ),
					'orderby' => 'title',
					'order' => 'ASC',
					//'course_ids' => null,
					//'quiz_ids' => null,
					'group_id' => null,
					
					// Registered Courses 
					'registered_num' => false, 
					'registered_show_thumbnail' => 'true',
					'registered_orderby' => 'title',
					'registered_order' => 'ASC',

					// Course Progress
					'progress_num' => false, 
					'progress_orderby' => 'title',
					'progress_order' => 'ASC', 
				
					// Quizzes
					'quiz_num' => false, 
					'quiz_orderby' => 'taken',
					'quiz_order' => 'DESC', 
				)
			);

			$atts = shortcode_atts( $atts_defaults, $atts );

			if ( !empty( $atts['type'] ) ) {
				if ( is_string( $atts['type'] ) ) {
					$atts['type'] = explode(',', $atts['type'] );
				}
				$atts['type'] = array_map( 'trim', $atts['type'] );
			}
			
			if ( !empty( $atts['group_id'] ) ) {
				$atts['course_ids'] = learndash_group_enrolled_courses( $atts['group_id'] );
				$atts['quiz_ids'] = learndash_get_group_course_quiz_ids( $atts['group_id'] );
			} else {
				$atts['course_ids'] = null;
				$atts['quiz_ids'] = null;
			}
			
			if ( !is_null( $atts['course_ids'] ) ) {
				if ( is_string( $atts['course_ids'] ) ) {
					$atts['course_ids'] = explode(',', $atts['course_ids'] );
				}
				$atts['course_ids'] = array_map( 'trim', $atts['course_ids'] );
			}

			if ( !is_null( $atts['quiz_ids'] ) ) {
				if ( is_string( $atts['quiz_ids'] ) ) {
					$atts['quiz_ids'] = explode(',', $atts['quiz_ids'] );
				}
				$atts['quiz_ids'] = array_map( 'trim', $atts['quiz_ids'] );
			}
			
			
			if ( !is_null( $atts['course_ids'] ) ) {
				$courses_registered_all = $atts['course_ids'];
			} else {
				$courses_registered_all = ld_get_mycourses( $user_id );
			}
			
			$courses_registered = array();
			$courses_registered_pager = array();
			if ( in_array( 'registered', $atts['type'] ) ) {
			
				if ( empty( $atts['registered_show_thumbnail'] ) ) {
					$atts['registered_show_thumbnail'] = $atts_defaults['registered_show_thumbnail'];
				}
							
				if ( !empty( $courses_registered_all ) ) {
					if ( $atts['registered_num'] === false )
						$atts['registered_num'] = intval( $atts_defaults['num'] );
					else
						$atts['registered_num'] = intval( $atts['registered_num'] );
				
					if ( ( !isset( $atts['registered_orderby'] ) ) || ( empty( $atts['registered_orderby'] ) ) )
						$atts['registered_orderby'] = $atts_defaults['registered_orderby'];

					if ( ( !isset( $atts['registered_order'] ) ) || ( empty( $atts['registered_order'] ) ) )
						$atts['registered_order'] = $atts_defaults['registered_order'];
				
					$courses_registered_query_args = array(
						'post_type'			=>	'sfwd-courses',
						'fields'			=>	'ids',
						'orderby'			=>	$atts['registered_orderby'],
						'order'				=>	$atts['registered_order'],
						'post__in'			=>	$courses_registered_all
					);
				
					$courses_registered_per_page = apply_filters( 'learndash_course_info_per_page', intval( $atts['registered_num'] ), 'registered', $user_id, $atts );
					if ( intval( $courses_registered_per_page ) > 0 ) {
						$courses_registered_query_args['posts_per_page'] = intval( $courses_registered_per_page );
						$courses_registered_query_args['paged'] = apply_filters('learndash_course_info_paged', 1, 'registered' );
					} else {
						$courses_registered_query_args['nopaging'] = true;
					}
				
					$courses_registered_query_args = apply_filters( 'learndash_course_info_query_args', $courses_registered_query_args, 'registered', $user_id, $atts );
					if ( !empty( $courses_registered_query_args ) ) {
						$course_registered_query = new WP_Query( $courses_registered_query_args );
						if ( ( isset( $course_registered_query->posts ) ) && ( !empty( $course_registered_query->posts ) ) ) {
							$courses_registered = $course_registered_query->posts;
							
							if ( isset( $course_registered_query->query_vars['paged'] ) )
								$courses_registered_pager['paged'] = $course_registered_query->query_vars['paged'];
							else 
								$courses_registered_pager['paged'] = $courses_registered_query_args['paged'];
						
							$courses_registered_pager['total_items'] = $course_registered_query->found_posts;
							$courses_registered_pager['total_pages'] = $course_registered_query->max_num_pages;
						} else {
							$courses_registered = array();
						}
					} else {
						$courses_registered = array();
					}
				}
			}

			$course_progress = array();
			$course_progress_pager = array();
			
			if ( in_array( 'course', $atts['type'] ) ) {

				if ( !is_null( $atts['course_ids'] ) ) {
					$course_progress_ids = $atts['course_ids'];
				} else {
					$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );
					$course_progress = empty( $usermeta ) ? array() : $usermeta;
					$course_progress_ids = array_merge( $courses_registered_all, array_keys( $course_progress ) );
				}
				
				// The course_info_shortcode.php template is driven be the $courses_registered array. 
				// We want to make sure we show ALL the courses from both the $courses_registered and 
				// the course_progress. Also we want to run through WP_Query so we can ensure they still 
				// exist as valid posts AND we want to sort these alphs by title
				//$courses_registered = array_merge( $courses_registered, array_keys( $course_progress ) );
				if ( !empty( $course_progress_ids ) ) {

					if ( $atts['progress_num'] === false )
						$atts['progress_num'] = intval( $atts_defaults['num'] );
					else
						$atts['progress_num'] = intval( $atts['progress_num'] );
				
					if ( ( !isset( $atts['progress_orderby'] ) ) || ( empty( $atts['progress_orderby'] ) ) )
						$atts['progress_orderby'] = $atts_defaults['progress_orderby'];
					
					if ( ( !isset( $atts['progress_order'] ) ) || ( empty( $atts['progress_order'] ) ) )
						$atts['progress_order'] = $atts_defaults['progress_order'];
					
					$course_progress_query_args = array(
						'post_type'			=>	'sfwd-courses',
						'fields'			=>	'ids',
						'orderby'			=>	$atts['progress_orderby'],
						'order'				=>	$atts['progress_order'],
						'post__in'			=>	$course_progress_ids
					);
				
					$courses_per_page = apply_filters( 'learndash_course_info_per_page', intval( $atts['progress_num'] ), 'courses', $user_id, $atts );
					if ( intval( $courses_per_page ) > 0 ) {
						$course_progress_query_args['posts_per_page'] = intval( $courses_per_page );
						$course_progress_query_args['paged'] = apply_filters('learndash_course_info_paged', 1, 'courses' );
					} else {
						$course_progress_query_args['nopaging'] = true;
					}
				
					$course_progress_query_args = apply_filters( 'learndash_course_info_query_args', $course_progress_query_args, 'courses', $user_id, $atts );
				
					if ( !empty( $course_progress_query_args ) ) {
						$course_progress_query = new WP_Query( $course_progress_query_args );
						if ( ( isset( $course_progress_query->posts ) ) && ( !empty( $course_progress_query->posts ) ) ) {
							$course_p = $course_progress;
							$course_progress = array();
							foreach( $course_progress_query->posts as $course_id ) {
								if ( isset( $course_p[$course_id] ) ) {
									$course_progress[$course_id] = $course_p[$course_id];
								} else {
									$course_progress[$course_id] = array();
								}
							}
							
							$course_progress_pager = array();
							if ( isset( $course_progress_query->query_vars['paged'] ) )
								$course_progress_pager['paged'] = $course_progress_query->query_vars['paged'];
							else 
								$course_progress_pager['paged'] = $course_progress_query_args['paged'];
							
							$course_progress_pager['total_items'] = $course_progress_query->found_posts;
							$course_progress_pager['total_pages'] = $course_progress_query->max_num_pages;
						}
					} else {
						$course_progress = array();
						$course_progress_pager = array();
					}
				}
			}

			$quizzes = array();
			$quizzes_pager = array();
			if ( in_array( 'quiz', $atts['type'] ) ) {
			
				$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );
				$quizzes = empty( $usermeta ) ? false : $usermeta;
			
				// We need to re-query the quiz (posts). This is partly to validate the listing. We don't
				// want to pass old or outdated quiz items to externals. 
				if ( !empty( $quizzes ) ) {
					
					if ( $atts['quiz_num'] === false )
						$atts['quiz_num'] = intval( $atts_defaults['num'] );
					else
						$atts['quiz_num'] = intval( $atts['quiz_num'] );
				
					if ( ( !isset( $atts['quiz_orderby'] ) ) || ( empty( $atts['quiz_orderby'] ) ) )
						$atts['quiz_orderby'] = $atts_defaults['quiz_orderby'];

					if ( ( !isset( $atts['quiz_order'] ) ) || ( empty( $atts['quiz_order'] ) ) )
						$atts['quiz_order'] = $atts_defaults['quiz_order'];

					if ( !is_null( $atts['quiz_ids'] ) ) {
						$quiz_ids = $atts['quiz_ids'];
					} else {
						$quiz_ids = wp_list_pluck( $quizzes, 'quiz' );
					}

					$quiz_total_query_args = array(
						'post_type'			=>	'sfwd-quiz',
						'fields'			=>	'ids',
						'orderby'			=>	'title', //$atts['quiz_orderby'],
						'order'				=>	'ASC', //$atts['quiz_order'],
						'nopaging'			=>	true,
						'post__in'			=>	$quiz_ids
					);
				
					if ( $quiz_total_query_args['orderby'] == 'taken' ) {
						$quiz_total_query_args['orderby'] = 'title';
					}
				
					$quiz_query = new WP_Query( $quiz_total_query_args );
					if ( ( $quiz_query ) && ( !is_wp_error( $quiz_query ) ) && is_a( $quiz_query, 'WP_Query' ) ) {
						if ( ( property_exists( $quiz_query, 'posts' ) ) && ( !empty( $quiz_query->posts ) ) ) {
							$quizzes_tmp = array();
							foreach( $quiz_query->posts as $post_idx => $quiz_id ) {
								foreach( $quizzes as $quiz_idx => $quiz_attempt ) {
									if ( $quiz_attempt['quiz'] == $quiz_id ) {
										if ( $atts['quiz_orderby'] == 'taken' ) {
											$quiz_key = $quiz_attempt['time'] .'-'. $quiz_attempt['quiz']; 
										} else if ( $atts['quiz_orderby'] == 'title' ) {
											$quiz_key = $post_idx .'-'. $quiz_attempt['time']; 
										} else if ( $atts['quiz_orderby'] == 'id' ) {
											$quiz_key = $quiz_attempt['quiz'] .'-'. $quiz_attempt['time'];
										}
										if ( !empty( $quiz_key ) ) {
											$quizzes_tmp[$quiz_key] = $quiz_attempt;
											unset( $quizzes[$quiz_idx] ); 
										}
									}
								}
							}
							
							$quizzes = $quizzes_tmp;

							if ( $atts['quiz_order'] == 'DESC' ) 
								krsort( $quizzes );
							else
								ksort( $quizzes );
								

							$quizzes_per_page = apply_filters( 'learndash_quiz_info_per_page', $atts['quiz_num'], 'quizzes', $user_id );
							if ( $quizzes_per_page > 0 ) {
							
								$quizzes_pager['paged'] = apply_filters('learndash_quiz_info_paged', 1 );
								$quizzes_pager['total_items'] = count( $quizzes );
								$quizzes_pager['total_pages'] = ceil( count( $quizzes ) / $quizzes_per_page );
							
								$quizzes = array_slice ( $quizzes, ( $quizzes_pager['paged'] * $quizzes_per_page ) - $quizzes_per_page, $quizzes_per_page, false );
							}
						}
					}
				}
			}

			/**
			 * Filter Courses and Quizzes is showing the Group Admin > Report page
			 * IF we are viewing the group_admin_page we want to filter the Courses and Quizzes listing
			 * to only include those items related to the Group	
			 * 
			 * @since 2.3
			 */
			global $pagenow;
			if ( ( !empty( $pagenow ) ) && ( $pagenow == 'admin.php' ) ) {
				if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] == 'group_admin_page' ) ) {
					if ( ( isset( $_GET['group_id'] ) ) && ( !empty( $_GET['group_id'] ) ) ) {
						$group_id = intval( $_GET['group_id'] );
						/*
						$group_courses = learndash_group_enrolled_courses( $group_id );
						if ( empty( $group_courses ) ) {
							$group_courses = array();
						}

						if ( empty( $courses_registered ) ) {
							$courses_registered = array();
						}
						$courses_registered = array_intersect( $group_courses, $courses_registered );

						if ( empty( $course_progress ) ) {
							$course_progress = array();
						}
						foreach( $course_progress as $course_id => $course_details ) {
							if ( !in_array( $course_id, $group_courses ) ) 
								unset( $course_progress[$course_id] );
						}
						
						$group_quizzes = learndash_get_group_course_quiz_ids( $group_id );
						if ( empty( $group_quizzes ) ) {
							$group_quizzes = array();
						}

						if ( empty( $quizzes ) ) {
							$quizzes = array();
						}
						
						foreach( $quizzes as $quiz_idx => $quiz_details ) {
							if ( !in_array( $quiz_details['quiz'], $group_quizzes ) ) 
								unset( $quizzes[$quiz_idx] );
						}
						*/
						if ( ( isset( $_GET['user_id'] ) ) && ( !empty( $_GET['user_id'] ) ) ) {
							$user_id = intval( $_GET['user_id'] );
							
							if ( learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) {
								if (learndash_is_user_in_group( intval( $_GET['user_id'] ), intval( $_GET['group_id'] ) )) {
									if ( isset( $_POST['learndash_course_points'] ) ) {
										update_user_meta($user_id, 'course_points', intval( $_POST['learndash_course_points'] ) );
									}
								}
							}
						}
						
					}
				}
			}

			if ( !empty( $atts['return'] ) ) {
				return array(
					'user_id' => $user_id,
					'courses_registered' => $courses_registered,
					'courses_registered_pager' => $courses_registered_pager,
					'course_progress' => $course_progress,
					'course_progress_pager' => $course_progress_pager,
					'quizzes' => $quizzes,
					'quizzes_pager' => $quizzes_pager
				);
			} else {
				
				if ( is_admin() ) {
					if ( !empty( $pagenow ) ) {
						if ( ( $pagenow == 'profile.php' ) || ( $pagenow == 'user-edit.php' ) ) { 
							$atts['pagenow'] = $pagenow;
							$atts['pagenow_nonce'] = wp_create_nonce( $pagenow .'-'. $user_id );
						} else if ( ( $pagenow == 'admin.php' ) && ( isset( $_GET['page'] ) ) && ( $_GET['page'] == 'group_admin_page' ) ) {
							$atts['pagenow'] = esc_attr( $_GET['page'] );
						
							if ( ( isset( $_GET['group_id'] ) ) && ( !empty( $_GET['group_id'] ) ) ) {
								$atts['group_id'] = intval( $_GET['group_id'] );
							} else {
								$atts['group_id'] = 0;
							}
							$atts['pagenow_nonce'] = wp_create_nonce( esc_attr( $_GET['page'] ) .'-'. $atts['group_id'] .'-'. $user_id );
						} else {
							$atts['pagenow'] = 'learndash';
							$atts['pagenow_nonce'] = wp_create_nonce( $atts['pagenow'] .'-'. $user_id );
						}
					} 
				} else {
					$atts['pagenow'] = 'learndash';
					$atts['pagenow_nonce'] = wp_create_nonce( $atts['pagenow'] .'-'. $user_id );
				} 
				$atts['user_id'] = $user_id;
				
				unset( $atts['course_ids'] );
				unset( $atts['quiz_ids'] );
				
				return SFWD_LMS::get_template('course_info_shortcode', array(
						'user_id' => $user_id,
						'courses_registered' => $courses_registered,
						'courses_registered_pager' => $courses_registered_pager,
						'course_progress' => $course_progress,
						'course_progress_pager' => $course_progress_pager,
						'quizzes' => $quizzes,
						'quizzes_pager' => $quizzes_pager,
						'shortcode_atts' => $atts
					)
				); 
			} 
		}



		/**
		 * Updates course price billy cycle on save
		 * Fires on action 'save_post'
		 *
		 * @since 2.1.0
		 * 
		 * @param  int 		 $post_id 	 
		 */
		function learndash_course_price_billing_cycle_save( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( empty( $post_id ) || empty( $_POST['post_type'] ) ) {
				return '';
			}

			// Check permissions
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}

			if ( isset( $_POST['course_price_billing_p3'] ) ) {
				update_post_meta( $post_id, 'course_price_billing_p3', $_POST['course_price_billing_p3'] );
			}

			if ( isset( $_POST['course_price_billing_t3'] ) ) {
				update_post_meta( $post_id, 'course_price_billing_t3', $_POST['course_price_billing_t3'] );
			}
		}



		/**
		 * Billing Cycle field html output for courses
		 * 
		 * @since 2.1.0
		 * 
		 * @return string
		 */
		function learndash_course_price_billing_cycle_html() {
			global $pagenow;
			add_action( 'save_post', array( $this, 'learndash_course_price_billing_cycle_save' ) );

			if ( $pagenow == 'post.php' && ! empty( $_GET['post'] ) ) {
				$post_id = $_GET['post'];
				$post = get_post( $post_id );

				if ( ( ! is_a( $post, 'WP_Post' ) ) || ( $post->post_type != 'sfwd-courses' ) ) {
					return;
				}

				$course_price_billing_p3 = get_post_meta( $post_id, 'course_price_billing_p3',  true );
				$course_price_billing_t3 = get_post_meta( $post_id, 'course_price_billing_t3',  true );
				$settings = learndash_get_setting( $post_id );

				if ( ! is_array( $settings ) ) {
					$settings = array();
				}
				
				if ( ! isset( $settings['course_price_type'] ) ) {
					$settings['course_price_type'] = 'open';
				}
				if ( ! empty( $settings ) && $settings['course_price_type'] == 'paynow' && empty( $settings['course_price'] ) ) {
					if ( empty( $settings['course_join'] ) ) {
						learndash_update_setting( $post_id, 'course_price_type', 'open' );
					} else {
						learndash_update_setting( $post_id, 'course_price_type', 'free' );
					}
				}

			} else {

				if ( $pagenow == 'post-new.php' && ! empty( $_GET['post_type'] ) && $_GET['post_type'] == 'sfwd-courses' ) {
					$post_id = 0;
					$course_price_billing_p3 = $course_price_billing_t3 = '';
				} else {
					return;
				}

			}
			

			$selected_D = $selected_W = $selected_M = $selected_Y = '';
			${'selected_'.$course_price_billing_t3} = 'selected="selected"';
			return '<input name="course_price_billing_p3" type="number" value="'.$course_price_billing_p3.'" class="small-text" /> 
					<select class="select_course_price_billing_p3" name="course_price_billing_t3">
						<option value="D" '.$selected_D.'>'.esc_html__( 'day(s)', 'learndash' ).'</option>
						<option value="W" '.$selected_W.'>'.esc_html__( 'week(s)', 'learndash' ).'</option>
						<option value="M" '.$selected_M.'>'.esc_html__( 'month(s)', 'learndash' ).'</option>
						<option value="Y" '.$selected_Y.'>'.esc_html__( 'year(s)', 'learndash' ).'</option>
					</select>';
		}

		static function course_progress_data( $course_id = null ) {
			set_time_limit( 0 );
			global $wpdb;

			$current_user = wp_get_current_user();
			if ( ( !learndash_is_admin_user( $current_user->ID ) ) && ( !learndash_is_group_leader_user( $current_user->ID ) ) ) {
				return;
			}
			
			$group_id = 0;
			if ( isset( $_GET['group_id'] ) ) {
				$group_id = $_GET['group_id'];
			}

			if ( learndash_is_group_leader_user( $current_user->ID ) ) {

				$users_group_ids = learndash_get_administrators_group_ids( $current_user->ID );
				if ( ! count( $users_group_ids ) ) {
					return array();
				}
				
				if ( !empty( $group_id ) ) {
					if ( ! in_array( $group_id, $users_group_ids ) ) {
						return;
					}
					$users_group_ids = array( $group_id );
				} 

				$all_user_ids = array();
				// First get the user_ids for each group...
				foreach($users_group_ids as $users_group_id) {
					$user_ids = learndash_get_groups_user_ids( $users_group_id );
					if (!empty($user_ids)) {
						if (!empty($all_user_ids)) {
							$all_user_ids = array_merge($all_user_ids, $user_ids);
						} else {
							$all_user_ids = $user_ids;
						}
					}
				}
				
				// Then once we have all the groups user_id run a last query for the complete user ids
				if (!empty($all_user_ids)) {
					$user_query_args = array(
						'include' 	=> 	$all_user_ids,
						'orderby' 	=>	'display_name',
						'order'	 	=>	'ASC',
					);
	
					$user_query = new WP_User_Query( $user_query_args );
	
					if ( isset( $user_query->results ) ) {
						$users = $user_query->results;
					}
				}
				
			} else if ( learndash_is_admin_user( $current_user->ID ) ) {
				if ( ! empty( $group_id ) ) {
					$users = learndash_get_groups_users( $group_id );
				} else {
					$users = get_users( 'orderby=display_name&order=ASC' );
				}

			} else {
				return array();
			}
			
			if ( empty( $users ) ) return array();

			$course_access_list = array();

			$course_progress_data = array();
			set_time_limit( 0 );

			$quiz_titles = array();
			$lessons = array();

			if ( ! empty( $course_id ) ) {
				$courses = array( get_post( $course_id ) );
			} elseif ( ! empty( $group_id ) ){
				$courses = learndash_group_enrolled_courses( $group_id );
				$courses = array_map( 'intval', $courses );
				$courses = ld_course_list( array( 'post__in' => $courses, 'array' => true ) );
			} else {
				$courses = ld_course_list( array( 'array' => true ) );
			}

			if ( ! empty( $users ) ) {

				foreach ( $users as $u ) {

					$user_id = $u->ID;
					$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );
					if ( ! empty( $usermeta ) ) {
						$usermeta = maybe_unserialize( $usermeta );
					}

					if ( ! empty( $courses[0] ) ) {

						foreach ( $courses as $course ) {
							$c = $course->ID;

							if ( empty( $course->post_title) || ! sfwd_lms_has_access( $c, $user_id ) ) {
								continue;
							}

							$cv = ! empty( $usermeta[ $c] ) ? $usermeta[ $c ] : array( 'completed' => '', 'total' => '' );

							$course_completed_meta = get_user_meta( $user_id, 'course_completed_'.$course->ID, true );
							( empty( $course_completed_meta ) ) ? $course_completed_date = '' : $course_completed_date = date_i18n( 'F j, Y H:i:s', $course_completed_meta );

							$row = array( 'user_id' => $user_id,
								'name' => $u->display_name,
								'email' => $u->user_email,
								'course_id' => $c,
								'course_title' => $course->post_title,
								'total_steps' => $cv['total'],
								'completed_steps' => $cv['completed'], 
								'course_completed' => ( ! empty( $cv['total'] ) && $cv['completed'] >= $cv['total'] ) ? 'YES' : 'NO' , 
								'course_completed_on' => $course_completed_date
							);

							$i = 1;
							if ( ! empty( $cv['lessons'] ) ) {
								foreach ( $cv['lessons'] as $lesson_id => $completed ) {
									if ( ! empty( $completed ) ) {
										if ( empty( $lessons[ $lesson_id ] ) ) {
											$lesson = $lessons[ $lesson_id ] = get_post( $lesson_id );
										}
										else {
											$lesson = $lessons[ $lesson_id ];
										}

										$row['lesson_completed_'.$i] = $lesson->post_title;
										$i++;
									}
								}
							}

							$course_progress_data[] = $row;

						} // end foreach

					} // end if 

				} // end foreach

			} else {
				$course_progress_data[] = array( 
					'user_id' => $user_id, 
					'name' => $u->display_name, 
					'email' => $u->user_email, 
					'status' => esc_html__( 'No attempts', 'learndash' ),
				);
			}

			 /**
			 * Filter course progress data to be displayed
			 * 
			 * @since 2.1.0
			 * 
			 * @param  array  $course_progress_data
			 */
			$course_progress_data = apply_filters( 'course_progress_data', $course_progress_data, $users, @$group_id );

			return $course_progress_data;
		}



		/**
		 * Exports course progress data to CSV file
		 *
		 * @since 2.1.0
		 */
		function course_export_init() {
			if ( ( ! defined( 'LEARNDASH_DEBUG' ) ) || ( LEARNDASH_DEBUG !== true ) ) {
				error_reporting( 0 );
			}

			if ( ! empty( $_REQUEST['courses_export_submit'] ) && ! empty( $_REQUEST['nonce-sfwd'] ) ) {
				set_time_limit( 0 );
				
				$default_tz = get_option( 'timezone_string' );
				if ( !empty( $default_tz ) )
					date_default_timezone_set( $default_tz );

				$nonce = $_REQUEST['nonce-sfwd'];

				if ( ! wp_verify_nonce( $nonce, 'sfwd-nonce' ) ) { 
					die( esc_html__( 'Security Check - If you receive this in error, log out and back in to WordPress', 'learndash' ) );
				}

				$content = SFWD_LMS::course_progress_data();

				if ( empty( $content ) ) {
					$content[] = array( 'status' => esc_html__( 'No attempts', 'learndash' ) );
				}

				/**
				 * include parseCSV to write csv file
				 */
				//require_once( dirname( __FILE__ ) . '/vendor/parsecsv.lib.php' );
				require_once( LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php' );

				$csv = new lmsParseCSV();
				$csv->file = 'courses.csv';
				$csv->output_filename = 'courses.csv';
				$csv = apply_filters('learndash_csv_object', $csv, 'courses' );
				 /**
				 * Filter the content will print onto the exported CSV
				 * 
				 * @since 2.1.0
				 * 
				 * @param  array  $content
				 */
				$content = apply_filters( 'course_export_data', $content );

				$csv->output( 'courses.csv', $content, array_keys( reset( $content ) ) );
				die();
			}
		}



		/**
		 * Course Export Button submit data
		 * 
		 * apply_filters ran in display_settings_page() in sfwd_module_class.php
		 *
		 * @todo  currently no add_filter using this callback
		 *        consider for deprecation or implement add_filter
		 *
		 * @since 2.1.0
		 * 
		 * @param  array $submit
		 * @return array $submit
		 */
		function courses_filter_submit( $submit ) {
			$submit['courses_export_submit'] = array( 
				'type' => 'submit',
				'class' => 'button-primary',
				'value' => sprintf( esc_html_x( 'Export User %s Data &raquo;', 'Export User Course Data Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) 
			);
			return $submit;
		}



		/**
		 * Export quiz data to CSV
		 * 
		 * @since 2.1.0
		 */
		function quiz_export_init() {
			if ( ( ! defined( 'LEARNDASH_DEBUG' ) ) || ( LEARNDASH_DEBUG !== true ) ) {
				error_reporting( 0 );
			}
			
			global $wpdb;
			$current_user = wp_get_current_user();

			if ( ( !learndash_is_admin_user( $current_user->ID ) ) && ( !learndash_is_group_leader_user( $current_user->ID ) ) )  {
				return;
			}
			// Why are these 3 lines here??
			$sfwd_quiz = $this->post_types['sfwd-quiz'];
			$quiz_prefix = $sfwd_quiz->get_prefix();
			add_filter( $quiz_prefix . 'submit_options', array( $this, 'quiz_filter_submit' ) );

			if ( ! empty( $_REQUEST['quiz_export_submit'] ) && ! empty( $_REQUEST['nonce-sfwd'] ) ) {
				$timezone_string = get_option( 'timezone_string' );
				if ( !empty( $timezone_string ) )
					date_default_timezone_set( $timezone_string );

				if ( ! wp_verify_nonce( $_REQUEST['nonce-sfwd'], 'sfwd-nonce' ) ) { 
					die ( esc_html__( 'Security Check - If you receive this in error, log out and back in to WordPress', 'learndash' ) );
				}

				/**
				 * include parseCSV to write csv file
				 */
				//require_once( __DIR__ . '/vendor/parsecsv.lib.php' );
				require_once( LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php' );

				$content = array();
				set_time_limit( 0 );
				//Need ability to export quiz results for group to CSV

				if ( isset( $_GET['group_id'] ) ) {
					$group_id = $_GET['group_id'];
				}

				if ( learndash_is_group_leader_user( $current_user->ID ) ) {

					$users_group_ids = learndash_get_administrators_group_ids( $current_user->ID );
					if ( ! count( $users_group_ids ) ) {
						return array();
					}

					if ( isset( $group_id ) ) {
						if ( ! in_array( $group_id, $users_group_ids ) ) {
							return;
						}
						$users_group_ids = array( $group_id );
					} 
					
					$all_user_ids = array();
					// First get the user_ids for each group...
					foreach($users_group_ids as $users_group_id) {
						$user_ids = learndash_get_groups_user_ids( $users_group_id );
						if (!empty($user_ids)) {
							if (!empty($all_user_ids)) {
								$all_user_ids = array_merge($all_user_ids, $user_ids);
							} else {
								$all_user_ids = $user_ids;
							}
						}
					}
				
					// Then once we have all the groups user_id run a last query for the complete user ids
					if (!empty($all_user_ids)) {
						$user_query_args = array(
							'include' => $all_user_ids,
							'orderby' => 'display_name',
							'order'	 =>	'ASC',
							'meta_query' => array(
								array(
									'key'     	=> 	'_sfwd-quizzes',
									'compare' 	=> 	'EXISTS',
								),
							)
						);
						
						$user_query = new WP_User_Query( $user_query_args );
	
						if ( isset( $user_query->results ) ) {
							$users = $user_query->results;
						} 
					}
				} else if ( learndash_is_admin_user( $current_user->ID ) ) {
					if ( ! empty( $group_id ) ) {
						$user_ids = learndash_get_groups_user_ids( $group_id );
						if (!empty($user_ids)) {
							$user_query_args = array(
								'include' => $user_ids,
								'orderby' => 'display_name',
								'order'	 =>	'ASC',
								'meta_query' => array(
									array(
										'key'     	=> 	'_sfwd-quizzes',
										'compare' 	=> 	'EXISTS',
									),
								)
							);
		
							$user_query = new WP_User_Query( $user_query_args );
							if (isset($user_query->results)) {
								$users = $user_query->results;
							} else {
								$users = array();
							}
						}
						
					}
					else {
						
						$user_query_args = array(
							'orderby' => 'display_name',
							'order'	 =>	'ASC',
							'meta_query' => array(
								array(
									'key'     	=> 	'_sfwd-quizzes',
									'compare' 	=> 	'EXISTS',
								),
							)
						);
	
						$user_query = new WP_User_Query( $user_query_args );
						if (isset($user_query->results)) {
							$users = $user_query->results;
						} else {
							$users = array();
						}
					}

				} else {
					return array();
				}
				
				$quiz_titles = array();

				if ( ! empty( $users ) ) {

					foreach ( $users as $u ) {

						$user_id = $u->ID;
						$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );

						if ( ! empty( $usermeta ) ) {

							foreach ( $usermeta as $k => $v ) {

								if ( ! empty( $group_id ) ) {
									$course_id = learndash_get_course_id( intval( $v['quiz'] ) );
									if ( ! learndash_group_has_course( $group_id, $course_id ) ) {
										continue;
									}
								}								

								if ( empty( $quiz_titles[ $v['quiz']] ) ) {

									if ( ! empty( $v['quiz'] ) ) {
										$quiz = get_post( $v['quiz'] );

										if ( empty( $quiz) ) {
											continue;
										}

										$quiz_titles[ $v['quiz']] = $quiz->post_title;

									} else if ( ! empty( $v['pro_quizid'] ) ) {

										$quiz = get_post( $v['pro_quizid'] );

										if ( empty( $quiz) ) {
											continue;
										}

										$quiz_titles[ $v['quiz']] = $quiz->post_title;

									} else {
										$quiz_titles[ $v['quiz']] = '';
									}
								}

								// After LD v2.2.1.2 we made a changes to the quiz user meta 'count' value output. Up to that point if the quiz showed only partial 
								// questions, like 5 of 10 total then the value of $v[count] would be 10 instead of only the shown count 5. 
								// After LD v2.2.1.2 we added a new field 'question_show_count' to hold the number of questions shown to the user during 
								// the quiz. 
								// But on legacy quiz user meta we needed a way to pull that information fron the quiz...

								if ( !isset( $v['question_show_count'] ) ) {
									$v['question_show_count'] = $v['count'];

									// ...If we have the statistics ref ID then we can pull the number of questions from there. 
									if ( ( isset( $v['statistic_ref_id'] ) ) && ( !empty( $v['statistic_ref_id'] ) ) ) {
										global $wpdb;
										
										$sql_str = $wpdb->prepare(" SELECT count(*) as count FROM ". LDLMS_DB::get_table_name( 'quiz_statistic' ) . " WHERE statistic_ref_id = %d",  $v['statistic_ref_id'] );
										$count = $wpdb->get_var( $sql_str );
										if ( !$count ) $count = 0;
										$v['question_show_count'] = intval( $count );
									} else {
										// .. or if the statistics is not enabled for this quiz then we get the question show count from the 
										// quiz data. Note there is a potential hole in the logic here. If this quiz setting changes then existing 
										// quiz user meta reports will also be effected. 
										$pro_quiz_id = get_post_meta( $v['quiz'], 'quiz_pro_id', true );
										if ( !empty( $pro_quiz_id ) ) {
											$quizMapper = new WpProQuiz_Model_QuizMapper();
											$quiz       = $quizMapper->fetch( $pro_quiz_id );
									
											if ( ( $quiz->isShowMaxQuestion() ) && ( $quiz->getShowMaxQuestionValue() > 0 ) ) {
												$v['question_show_count'] = $quiz->getShowMaxQuestionValue();
											}
										}
									}
								}

								$content[] = array( 
									'user_id' 		=>	$user_id,
									'name' 			=>	$u->display_name,
									'email' 		=>	$u->user_email,
									'quiz_id' 		=>	$v['quiz'],
									'quiz_title' 	=> 	$quiz_titles[ $v['quiz'] ],
									'rank' 			=> 	$v['rank'],
									'score' 		=> 	$v['score'],
									'total' 		=> 	$v['question_show_count'],
									'date' 			=> 	date_i18n( DATE_RSS, $v['time'] ) ,
								);
							}

						} else {

							//	$content[] = array( 'user_id' => $user_id, 'name' => $u->display_name, 'email' => $u->user_email, 'status' => esc_html__( 'No attempts', 'learndash' ) );
							$content[] = array( 
								'user_id' => $user_id,
								'name' => $u->display_name,
								'email' => $u->user_email,
								'quiz_id' => esc_html__( 'No attempts',
								'learndash' ),
								'quiz_title' => '',
								'rank' => '',
								'score' => '',
								'total' => '',
								'date' => '' 
							 );

						} // end if

					} // end foreach 

				} // end if

				if ( empty( $content ) ) {
					$content[] = array( 'status' => esc_html__( 'No attempts', 'learndash' ) );
				}

				 /**
				 * Filter quiz data that will print to CSV
				 * 
				 * @since 2.1.0
				 * 
				 * @param  array  $content
				 */
				$content = apply_filters( 'quiz_export_data', $content, $users, @$group_id );

				$csv = new lmsParseCSV();
				$csv->file = 'quizzes.csv';
				$csv->output_filename = 'quizzes.csv';
				$csv = apply_filters('learndash_csv_object', $csv, 'quizzes' );
				
				$csv->output('quizzes.csv', $content, array_keys( reset( $content ) ) );
				die();

			}
		}



		/**
		 * Quiz Export Button submit data
		 * 
		 * Filter callback for $quiz_prefix . 'submit_options'
		 * apply_filters ran in display_settings_page() in sfwd_module_class.php
		 * 
		 * @since 2.1.0
		 * 
		 * @param  array $submit
		 * @return array
		 */
		function quiz_filter_submit( $submit ) {			
			$submit['quiz_export_submit'] = array( 
				'type' => 'submit',
				'class' => 'button-primary',
				'value' => sprintf( esc_html_x( 'Export %s Data &raquo;', 'Export Quiz Data Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ) 
			);
			return $submit;
		}



		/**
		 * Export transcations to CSV file
		 * 
		 * Not currently being used in plugin
		 *
		 * @todo consider for deprecation or implement in plugin
		 *
		 * @since 2.1.0
		 */
		function trans_export_init() {
			$sfwd_trans = $this->post_types['sfwd-transactions'];
			$trans_prefix = $sfwd_trans->get_prefix();
			add_filter( $trans_prefix . 'submit_options', array( $this, 'trans_filter_submit' ) );

			if ( ! empty( $_REQUEST['export_submit'] ) && ! empty( $_REQUEST['nonce-sfwd'] ) ) {
				$nonce = $_REQUEST['nonce-sfwd'];

				if ( ! wp_verify_nonce( $nonce, 'sfwd-nonce' ) ) { 
					die ( esc_html__( 'Security Check - If you receive this in error, log out and back in to WordPress', 'learndash' ) );
				}

				/**
				 * Include parseCSV to write csv file
				 */
				//require_once( __DIR__ . '/vendor/parsecsv.lib.php' );
				require_once( LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php' );

				$content = array();
				set_time_limit( 0 );

				$locations = query_posts( 
					array( 
						'post_status' => 'publish', 
						'post_type' => 'sfwd-transactions', 
						'posts_per_page' => -1 
					) 
				);

				foreach ( $locations as $key => $location ) {
					$location_data = get_post_custom( $location->ID );
					foreach ( $location_data as $k => $v ) {
						if ( $k[0] == '_' ) {
							unset( $location_data[ $k ] );
						}
						else {
							$location_data[ $k] = $v[0];
						}
					}
					$content[] = $location_data;
				}

				if ( ! empty( $content ) ) {
					$csv = new lmsParseCSV();

					$this->csv->file = 'transactions.csv';
					$this->csv->output_filename = 'transactions.csv';
					$csv = apply_filters('learndash_csv_object', $csv, 'transactions' );
					
					$csv->output( true, 'transactions.csv', $content, array_keys( reset( $content ) ) );
				}

				die();
			}
		}



		/**
		 * Transaction Export Button submit data
		 *
		 * Filter callback for $trans_prefix . 'submit_options'
		 * apply_filters ran in display_settings_page() in sfwd_module_class.php
		 * 
		 * @since 2.1.0
		 * 
		 * @param  array $submit
		 * @return array
		 */
		function trans_filter_submit( $submit ) {
			unset( $submit['Submit'] );
			unset( $submit['Submit_Default'] );

			$submit['export_submit'] = array( 
				'type' => 'submit',
				'class' => 'button-primary',
				'value' => esc_html__( 'Export &raquo;', 'learndash' ) 
			);

			return $submit;
		}



		/**
		 * Set up quiz display settings
		 * 
		 * Filter callback for '{$quiz_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.1.0
		 * 
		 * @param  array  $settings        quiz settings
		 * @param  string $location        where these settings are being displayed
		 * @param  array  $current_options current options stored for a given location
		 * @return array                   quiz settings
		 */
		function quiz_display_settings( $settings, $location, $current_options ) {
			global $sfwd_lms;
			$sfwd_quiz = $sfwd_lms->post_types['sfwd-quiz'];
			$quiz_prefix = $sfwd_quiz->get_prefix();
			
			$prefix_len = strlen( $quiz_prefix );
			$quiz_options = $sfwd_quiz->get_current_options();

			if ( $location == null ) {

				foreach ( $quiz_options as $k => $v ) {
					if ( strpos( $k, $quiz_prefix ) === 0 ) {
						$quiz_options[ substr( $k, $prefix_len ) ] = $v;
						unset( $quiz_options[ $k ] );
					}
				}

				foreach ( array( 'level1', 'level2', 'level3', 'level4', 'level5' ) as $level ) {
					$quiz['info'][ $level ] = $quiz_options[ $level ];
				}

				$quiz['info']['name'] = $quiz['info']['main'] = $quiz['info']['results'] = '';
				$quiz_json = json_encode( $quiz );
				$settings['sfwd-quiz_quiz']['default'] = '<div class="quizFormWrapper"></div><script type="text/javascript">var quizJSON = ' . $quiz_json . ';</script>';
				
				if ( $location == null ) { 
					unset( $settings["{$quiz_prefix}quiz"] );
				}

				if ( ! empty( $settings["{$quiz_prefix}certificate_post"] ) ) {
					$posts = get_posts( array( 'post_type' => 'sfwd-certificates' , 'numberposts' => -1 ) );
					$post_array = array( '0' => esc_html__( '-- Select a Certificate --', 'learndash' ) );

					if ( ! empty( $posts ) ) {
						foreach ( $posts as $p ) {
							$post_array[ $p->ID ] = $p->post_title;
						}
					}

					$settings["{$quiz_prefix}certificate_post"]['initial_options'] = $post_array;
				}

			} else {

				global $pagenow;
				if (($pagenow == 'post.php') || ($pagenow == 'post-new.php')) {
					$current_screen = get_current_screen();
					if ($current_screen->post_type == 'sfwd-quiz') {

						if ( ( isset( $settings["{$quiz_prefix}course"] ) ) && ( ! empty( $settings["{$quiz_prefix}course"] ) ) ) {

							if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {

								$_settings = $settings["{$quiz_prefix}course"];
							
								$query_options = array( 
									'post_type' 		=> 	'sfwd-courses', 
									'post_status' 		=> 	'any',  
									'posts_per_page' 	=> 	-1,
									'exclude'			=>	get_the_id(),
									'orderby'			=>	'title',
									'order'				=>	'ASC'	
								);

								$lazy_load = apply_filters('learndash_element_lazy_load_admin', true);
								if (($lazy_load == true) && (isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
									$query_options['paged'] 			= 	1;
									$query_options['posts_per_page'] 	= 	apply_filters('learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, "{$quiz_prefix}course");
								}
							
							    /**
							    * Filter course prerequisites
							    * 
							    * @since 2.1.0
							    * 
							    * @param  array  $options 
							    */
							   $query_options = apply_filters( 'learndash_quiz_cours_post_options', $query_options, $_settings );
							
							   $query_posts = new WP_Query( $query_options );

							   $post_array = array( '0' => sprintf( esc_html_x( '-- Select a %s --', 'Select a Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) );
						   
							   if ( ! empty( $query_posts->posts ) ) {
								   if ( count( $query_posts->posts ) >= $query_posts->found_posts ) {
									   // If the number of returned posts is equal or greater then found_posts then no need to run lazy load
									   $_settings['lazy_load'] = false;
								   }
	
								   foreach ( $query_posts->posts as $p ) {
									   if ( $p->ID == get_the_id() ){
										   //Skip for current post id as current course can not be prerequities of itself
									   } else { 
										   $post_array[ $p->ID ] = $p->post_title;
									   }
								   }
							   } else {
								   // If we don't have any items then override the lazy load flag
								   $_settings['lazy_load'] = false;
							   }
							   $settings["{$quiz_prefix}course"]['initial_options'] = $post_array;
						   
							   if ((isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
								   $lazy_load_data = array();
								   $lazy_load_data['query_vars'] 	= 	$query_options;
								   $lazy_load_data['query_type']	= 	'WP_Query';
								   $lazy_load_data['value']		=	$_settings['value'];
								   $settings["{$quiz_prefix}course"]['lazy_load_data'] = $lazy_load_data;
							   }
						   } else {
						   		unset( $settings["{$quiz_prefix}course"] );
						   }
					   }
						

						if ( ( isset( $settings["{$quiz_prefix}lesson"] ) ) && ( ! empty( $settings["{$quiz_prefix}lesson"] ) ) ) {
							if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {
							
								$post_array = array( '0' => sprintf( esc_html_x( '-- Select a %s or %s --', 'Select a Lesson or Topic Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'topic' ) ) );
								$course_id = get_post_meta(get_the_id(), 'course_id', true );
							
								$lessons_array = $this->select_a_lesson_or_topic( $course_id );
								if ( !empty( $lessons_array ) ) {
									// We can't merge because it will destroy the array indexs. 
									//$post_array = array_merge( $post_array, $lessons_array );
									foreach( $lessons_array as $lesson_id => $lesson_title ) {
										$post_array[$lesson_id] = $lesson_title;
									}
								}
								$settings["{$quiz_prefix}lesson"]['initial_options'] = $post_array;
							} else {
								unset( $settings["{$quiz_prefix}lesson"] );
							}
						}

						if ( ! empty( $settings["{$quiz_prefix}certificate"] ) ) {					
							$posts = get_posts( array( 'post_type' => 'sfwd-certificates'  , 'numberposts' => -1 ) );
							$post_array = array( '0' => esc_html__( '-- Select a Certificate --', 'learndash' ) );

							if ( ! empty( $posts ) ) {
								foreach ( $posts as $p ) {
									$post_array[ $p->ID ] = $p->post_title;
								}
							}

							$settings["{$quiz_prefix}certificate"]['initial_options'] = $post_array;
						}
				
						if ( ! empty( $settings["{$quiz_prefix}quiz_pro"] ) ) {
							$settings["{$quiz_prefix}quiz_pro"]['initial_options'] = array( 0 => esc_html__( '-- Select Settings --', 'learndash' ) ) + LD_QuizPro::get_quiz_list();
						}
					}
				}
			}

			return $settings;
		}

		/**
		 * Set up question display settings
		 * 
		 * Filter callback for '{$question_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.1.0
		 * 
		 * @param  array  $settings        quiz settings
		 * @param  string $location        where these settings are being displayed
		 * @param  array  $current_options current options stored for a given location
		 * @return array                   quiz settings
		 */
		function question_display_settings( $settings, $location, $current_options ) {
			global $sfwd_lms;
			$sfwd_question = $sfwd_lms->post_types['sfwd-question'];
			$question_prefix = $sfwd_question->get_prefix();
			
			$prefix_len = strlen( $question_prefix );
			$question_options = $sfwd_question->get_current_options();

			if ( $location == null ) {
			} else {

				global $pagenow;
				if ( ( $pagenow == 'post.php' ) || ( $pagenow == 'post-new.php' ) ) {
					$current_screen = get_current_screen();
					if ($current_screen->post_type == 'sfwd-question') {

						if ( ( isset( $settings["{$question_prefix}quiz"] ) ) && ( ! empty( $settings["{$question_prefix}quiz"] ) ) ) {

							$_settings = $settings["{$question_prefix}quiz"];
							
							$query_options = array( 
								'post_type' 		=> 	'sfwd-quiz', 
								'post_status' 		=> 	'any',  
								'posts_per_page' 	=> 	-1,
								'exclude'			=>	get_the_id(),
								'orderby'			=>	'title',
								'order'				=>	'ASC'	
							);

							$lazy_load = apply_filters('learndash_element_lazy_load_admin', true);
							if (($lazy_load == true) && (isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
								$query_options['paged'] 			= 	1;
								$query_options['posts_per_page'] 	= 	apply_filters('learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, "{$question_prefix}quiz");
							}
							
							/**
							* Filter course prerequisites
							* 
							* @since 2.1.0
							* 
							* @param  array  $options 
							*/
							$query_options = apply_filters( 'learndash_question_quiz_post_options', $query_options, $_settings );
							
							$query_posts = new WP_Query( $query_options );

							$post_array = array( '0' => sprintf( esc_html_x( '-- Select a %s --', 'Select a Quiz Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quiz' ) ) );
						   
							if ( ! empty( $query_posts->posts ) ) {
								if ( count( $query_posts->posts ) >= $query_posts->found_posts ) {
									// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
									$_settings['lazy_load'] = false;
								}

								foreach ( $query_posts->posts as $p ) {
									if ( $p->ID == get_the_id() ){
										//Skip for current post id as current course can not be prerequities of itself
									} else { 
										$post_array[ $p->ID ] = $p->post_title;
									}
								}
							} else {
								// If we don't have any items then override the lazy load flag
								$_settings['lazy_load'] = false;
							}
							$settings["{$question_prefix}quiz"]['initial_options'] = $post_array;
						
							if ((isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
								$lazy_load_data = array();
								$lazy_load_data['query_vars'] 	= 	$query_options;
								$lazy_load_data['query_type']	= 	'WP_Query';
								$lazy_load_data['value']		=	( isset( $_settings['value'] ) ) ? $_settings['value'] : '';
								$settings["{$question_prefix}quiz"]['lazy_load_data'] = $lazy_load_data;
							}
						}
					}
				}
			}

			return $settings;
		}

		function select_a_course( $current_post_type = null ) {

			$opt = array( 
				'post_type' => 'sfwd-courses',
				'post_status' => 'any',
				'numberposts' => -1,
				'orderby' => 'title',
				'order' => 'ASC',
			);

			$posts = get_posts( $opt );
			$post_array = array();

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $p ) {
					$post_array[ $p->ID ] = $p->post_title;
				}
			}

			return $post_array;
		}



		function select_a_certificate( $current_post_type = null ) {

			$opt = array( 
				'post_type' => 'sfwd-certificates',
				'post_status' => 'any',
				'numberposts' => -1,
				'orderby' => 'title',
				'order' => 'ASC',
			);

			$posts = get_posts( $opt );
			$post_array = array();

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $p ) {
					$post_array[ $p->ID ] = $p->post_title;
				}
			}

			return $post_array;
		}


		/**
		 * Retrieves lessons or topics for a course to populate dropdown on edit screen
		 * 
		 * Ajax action callback for wp_ajax_select_a_lesson_or_topic
		 *
		 * @since 2.1.0
		 */
		function select_a_lesson_or_topic_ajax() {
			$data = array();
			$data['opt'] = array();
			
			if ( ( isset( $_POST['ld_selector_nonce'] ) ) && ( ! empty( $_POST['ld_selector_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_selector_nonce'], learndash_get_post_type_slug( 'lesson' ) ) ) ) { 
				
				if ( ( isset( $_POST['ld_selector_default'] ) ) && ( ! empty( $_POST['ld_selector_default'] ) ) ) {
					$ld_selector_default = true; //esc_attr( $_POST['ld_selector_default'] );
				} else {
					$ld_selector_default = false;
				}
				$post_array = $this->select_a_lesson_or_topic( @$_REQUEST['course_id'], true, $ld_selector_default );
				if ( !empty( $post_array ) ) {
					$i = 0;
					foreach ( $post_array as $key => $value ) {
						$opt[ $i ]['key'] = $key;
						$opt[ $i ]['value'] = $value;
						$i++;
					}
					$data['opt'] = $opt;
				}
			}

			echo json_encode( $data );
			exit;
		}



		/**
		 * Makes wp_query to retrieve lessons or topics for a course
		 *
		 * @since 2.1.0
		 * 
		 * @param  int 		$course_id 
		 * @return array 	array of lessons or topics
		 */
		function select_a_lesson_or_topic( $course_id = null, $include_topics = true, $include_default = true ) {
			if ( ! is_admin() ) {
				return array();
			}
			$post_array = array();
			
			if ( !is_null( $course_id ) ) {
				if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					$lesson_ids = learndash_course_get_children_of_step( $course_id, $course_id, 'sfwd-lessons' );
					if ( !empty( $lesson_ids ) ) {
						foreach( $lesson_ids as $lesson_id ) {
							$post_array[ $lesson_id ] = get_the_title( $lesson_id );
							if ( $include_topics ) {
								$topic_ids = learndash_course_get_children_of_step( $course_id, $lesson_id, 'sfwd-topic' );
								if ( !empty( $topic_ids ) ) {
									foreach( $topic_ids as $topic_id ) {
										$post_array[ $topic_id ] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . get_the_title( $topic_id );
									}
								}
							}
						}
					}
				} else {
					$lessons_options = sfwd_lms_get_post_options( 'sfwd-lessons' );
					$course_lessons_args = learndash_get_course_lessons_order( $course_id );
					$orderby             = isset( $course_lessons_args['orderby'] ) ? $course_lessons_args['orderby'] : $lessons_options['orderby'];
					$order               = isset( $course_lessons_args['order'] ) ? $course_lessons_args['order'] : $lessons_options['order'];
					$opt = array( 
						'post_type'   => 'sfwd-lessons',
						'post_status' => 'any',  'numberposts' => -1,
						'orderby'     => $orderby,
						'order'       => $order,
					);

					if ( empty( $course_id ) ) {
						$course_id = learndash_get_course_id( @$_GET['post'] );
					}

					if ( ! empty( $course_id ) ) {
						$opt['meta_key'] = 'course_id';
						$opt['meta_value'] = $course_id;
					}

					$posts = get_posts( $opt );
					
					if ( true === $include_default ) {
						if ( $include_topics == true ) {
							if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
								$post_array = array( 
									'-1' => sprintf(
										// translators: placeholder: Lesson, Topic Labels.
										esc_html_x( 'Search or select a %1$s or %2$s…', 'placeholder: Lesson, Topic Labels', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'lesson' ),
										LearnDash_Custom_Label::get_label( 'topic' ) 
									) 
								);
							} else {
								$post_array = array( 
									'0' => sprintf(
										// translators: placeholder: Lesson, Topic Labels.
										esc_html_x( 'Select a %1$s or %2$s', 'placeholder: Lesson, Topic Labels', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'lesson' ),
										LearnDash_Custom_Label::get_label( 'topic' ) 
									) 
								);
							}
						} else {
							if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
								$post_array = array( 
									'-1' => sprintf(
										// translators: placeholder: Lesson Label.
										esc_html_x( 'Search or select a %s…', 'placeholder: Lesson Label', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'lesson' )
									) 
								);
							} else {
								$post_array = array( 
									'0' => sprintf(
										// translators: placeholder: Lesson Label.
										esc_html_x( 'Select a %s', 'placeholder: Lesson Labels', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'lesson' )
									) 
								);
							}
						}
					}
					
					if ( ! empty( $posts ) ) {
						foreach ( $posts as $p ){
							$lesson_post_title = strip_tags( $p->post_title );
							if ( empty( $lesson_post_title ) ) {
								$lesson_post_title = $p->ID . ' - /' . $p->post_name; 
							}
							$post_array[ $p->ID ] = $lesson_post_title;
							if ( $include_topics == true ) {
								$topics_array = learndash_get_topic_list( $p->ID, $course_id );
								if ( ! empty( $topics_array ) ) {
									foreach ( $topics_array as $topic ) {
										$topic_post_title = strip_tags( $topic->post_title );
										if ( empty( $topic_post_title ) ) {
											$topic_post_title = $topic->ID . ' - /' . $topic->post_name; 
										}
										$post_array[ $topic->ID ] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $topic_post_title;
									}
								}
							}
						}
					}					
				}
			}
			return $post_array;
		}


		/**
		 * Retrieves lessons for a course to populate dropdown on edit screen
		 * 
		 * Ajax action callback for wp_ajax_select_a_lesson
		 *
		 * @since 2.1.0
		 */
		function select_a_lesson_ajax() {
			//$post_array = $this->select_a_lesson( @$_REQUEST['course_id'] );
			//$post_array = $this->select_a_lesson_or_topic( @$_REQUEST['course_id'], false );
			
			$data = array();
			$data['opt'] = array();
			
			if ( ( isset( $_POST['ld_selector_nonce'] ) ) && ( ! empty( $_POST['ld_selector_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_selector_nonce'], 'sfwd-lessons' ) ) ) { 
				if ( ( isset( $_POST['ld_selector_default'] ) ) && ( ! empty( $_POST['ld_selector_default'] ) ) ) {
					$ld_selector_default = true; //esc_attr( $_POST['ld_selector_default'] );
				} else {
					$ld_selector_default = false;
				}
				$post_array = $this->select_a_lesson_or_topic( @$_REQUEST['course_id'], false, $ld_selector_default );
				if ( !empty( $post_array ) ) {
					$i = 0;
					foreach ( $post_array as $key => $value ) {
						$opt[ $i ]['key'] = $key;
						$opt[ $i ]['value'] = $value;
						$i++;
					}
					$data['opt'] = $opt;
				}
			}

			echo json_encode( $data );
			exit;
		}



		/**
		 * Makes wp_query to retrieve lessons a course
		 *
		 * @since 2.1.0
		 * 
		 * @param  int 		$course_id 
		 * @return array 	array of lessons
		 */
		function select_a_lesson( $course_id = null ) {			
			if ( ! is_admin() ) {
				return array();
			}

			if ( ! empty( $_REQUEST['ld_action'] ) || ! empty( $_GET['post'] ) && is_array( $_GET['post'] ) ) {
				return array();
			}

			$opt = array( 
				'post_type' => 'sfwd-lessons', 
				'post_status' => 'any',  
				'numberposts' => -1 , 
				'orderby' => learndash_get_option( 'sfwd-lessons', 'orderby' ), 
				'order' => learndash_get_option( 'sfwd-lessons', 'order' ),
			);

			if ( empty( $course_id ) ) {
				if ( empty( $_GET['post'] ) ) {
					$course_id = learndash_get_course_id();
				} else {
					$course_id = learndash_get_course_id( $_GET['post'] );
				}
			}

			if ( ! empty( $course_id ) ) {
				$opt['meta_key'] = 'course_id';
				$opt['meta_value'] = $course_id;
			}

			$posts = get_posts( $opt );
			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
				$post_array = array( 
					'-1' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Search or select a %s', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					) 
				);
			} else {
				$post_array = array( 
					'0' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Select a %s', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					) 
				);
			}

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $p ) {
					$post_array[ $p->ID ] = $p->post_title;
				}
			}

			return $post_array;
		}


		/**
		 * Retrieves quizzes for a course to populate dropdown on edit screen
		 * 
		 * Ajax action callback for wp_ajax_select_a_lesson
		 *
		 * @since 2.5.0
		 */
		function select_a_quiz_ajax() {
			$data = array();
			$data['opt'] = array();
			
			if ( ( isset( $_POST['ld_selector_nonce'] ) ) && ( ! empty( $_POST['ld_selector_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_selector_nonce'], 'sfwd-quiz' ) ) ) { 
				$post_array = $this->select_a_quiz( @$_REQUEST['course_id'], @$_REQUEST['lesson_id'] );
				if ( !empty( $post_array ) ) {
					$i = 0;
					foreach ( $post_array as $key => $value ) {
						$opt[ $i ]['key'] = $key;
						$opt[ $i ]['value'] = $value;
						$i++;
					}
					$data['opt'] = $opt;
				}
			}
			echo json_encode( $data );
			exit;
		}
		
		/**
		 * Makes wp_query to retrieve quizzes a course
		 *
		 * @since 2.5.0
		 * 
		 * @param  int 		$course_id 
		 * @return array 	array of lessons
		 */
		function select_a_quiz( $course_id = 0, $lesson_topic_id = 0 ) {
			
			$post_array = array();
		
			if ( !empty( $course_id ) ) {
				if ( !empty( $lesson_topic_id ) ) {
					$quiz_ids = learndash_course_get_children_of_step( $course_id, $lesson_topic_id, 'sfwd-quiz' );
				} else {
					$quiz_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-quiz' );
				}
				if ( !empty( $quiz_ids ) ) {
					foreach( $quiz_ids as $quiz_id ) {
						$post_array[ $quiz_id ] = get_the_title( $quiz_id );
					}
				}
			} else {
				$opt = array( 
					'post_type' => 'sfwd-quiz', 
					'post_status' => 'any',  
					'numberposts' => -1 , 
					'orderby' => 'title', 
					'order' => 'ASC',
				);

				$posts = get_posts( $opt );
				$post_array = array();

				if ( ! empty( $posts ) ) {
					foreach ( $posts as $p ) {
						$post_array[ $p->ID ] = $p->post_title;
					}
				}
			}
			return $post_array;
		}
		
		
		/**
		 * Set up course display settings
		 * 
		 * Filter callback for '{$courses_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.1.0
		 * 
		 * @param  array  $settings        quiz settings
		 * @return array                   quiz settings
		 */
		function course_display_settings( $settings ) {

			global $sfwd_lms;
			$sfwd_courses = $sfwd_lms->post_types['sfwd-courses'];
			$courses_prefix = $sfwd_courses->get_prefix();

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				if ( isset( $settings['sfwd-courses_course_lesson_orderby'] ) ) unset( $settings['sfwd-courses_course_lesson_orderby'] );
				if ( isset( $settings['sfwd-courses_course_lesson_order'] ) ) unset( $settings['sfwd-courses_course_lesson_order'] );
			}
			
			if ( ! empty( $settings["{$courses_prefix}course_prerequisite"] ) ) {
				
				$_settings = $settings["{$courses_prefix}course_prerequisite"];
				
				$query_options = array( 
					'post_type' 		=> 	'sfwd-courses', 
					'post_status' 		=> 	'any',  
					'posts_per_page' 	=> 	-1,
					'exclude'			=>	get_the_id(),
					'orderby'			=>	'title',
					'order'				=>	'ASC'	
				);

				$lazy_load = apply_filters('learndash_element_lazy_load_admin', true);
				if (($lazy_load == true) && (isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
					$query_options['paged'] 			= 	1;
					$query_options['posts_per_page'] 	= 	apply_filters('learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, "{$courses_prefix}course_prerequisite");
				}

				 /**
				 * Filter course prerequisites
				 * 
				 * @since 2.1.0
				 * 
				 * @param  array  $options 
				 */
				$query_options = apply_filters( 'learndash_course_prerequisite_post_options', $query_options, $_settings );

				$query_posts = new WP_Query( $query_options );

				$post_array = array( '0' => sprintf( esc_html_x( '-- Select a %s --', 'Select a Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) );

				if ( ! empty( $query_posts->posts ) ) {
					if ( count( $query_posts->posts ) >= $query_posts->found_posts ) {
						// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
						$_settings['lazy_load'] = false;
					}
					
					foreach ( $query_posts->posts as $p ) {
						if ( $p->ID == get_the_id() ){
							//Skip for current post id as current course can not be prerequities of itself
						} else { 
							$post_array[ $p->ID ] = $p->post_title;
						}
					}
				} else {
					// If we don't have any items then override the lazy load flag
					$_settings['lazy_load'] = false;
				}

				$settings["{$courses_prefix}course_prerequisite"]['initial_options'] = $post_array;
				
				if ((isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
					$lazy_load_data = array();
					$lazy_load_data['query_vars'] 	= 	$query_options;
					$lazy_load_data['query_type']	= 	'WP_Query';
					$lazy_load_data['value']		=	$_settings['value'];
					$settings["{$courses_prefix}course_prerequisite"]['lazy_load_data'] = $lazy_load_data;
				}
			}

			if ( ! empty( $settings["{$courses_prefix}certificate"] ) ) {
				$posts = get_posts( array( 'post_type' => 'sfwd-certificates'  , 'numberposts' => -1) );
				$post_array = array( '0' => esc_html__( '-- Select a Certificate --', 'learndash' ) );

				if ( ! empty( $posts ) ) {
					foreach ( $posts as $p ) {
						$post_array[ $p->ID ] = $p->post_title;
					}
				}

				$settings["{$courses_prefix}certificate"]['initial_options'] = $post_array;
			}

			return $settings;

		}

		
		/**
		 * Set up lesson display settings
		 * 
		 * Filter callback for '{$lessons_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.2.0.2
		 * 
		 * @param  array  $settings        lesson settings
		 * @return array                   lesson settings
		 */
		function lesson_display_settings( $settings ) {

			global $sfwd_lms;
			$sfwd_lessons = $sfwd_lms->post_types['sfwd-lessons'];
			$lessons_prefix = $sfwd_lessons->get_prefix();

			if ( ( isset( $settings["{$lessons_prefix}course"] ) ) && ( ! empty( $settings["{$lessons_prefix}course"] ) ) ) {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {
					$_settings = $settings["{$lessons_prefix}course"];
				
					$query_options = array( 
						'post_type' 		=> 	'sfwd-courses', 
						'post_status' 		=> 	'any',  
						'posts_per_page' 	=> 	-1,
						'exclude'			=>	get_the_id(),
						'orderby'			=>	'title',
						'order'				=>	'ASC'	
					);

					$lazy_load = apply_filters('learndash_element_lazy_load_admin', true);
					if (($lazy_load == true) && (isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
						$query_options['paged'] 			= 	1;
						$query_options['posts_per_page'] 	= 	apply_filters('learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, "{$lessons_prefix}course");
					}
				

					 /**
					 * Filter course prerequisites
					 * 
					 * @since 2.1.0
					 * 
					 * @param  array  $options 
					 */
					$query_options = apply_filters( 'learndash_lesson_course_post_options', $query_options, $_settings );

	 				$query_posts = new WP_Query( $query_options );
				
	 				$post_array = array( '0' => sprintf( esc_html_x( '-- Select a %s --', 'Select a Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) );

	 				if ( ! empty( $query_posts->posts ) ) {
	 					if ( count( $query_posts->posts ) >= $query_posts->found_posts ) {
	 						// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
	 						$_settings['lazy_load'] = false;
	 					}
					
	 					foreach ( $query_posts->posts as $p ) {
	 						if ( $p->ID == get_the_id() ){
	 							//Skip for current post id as current course can not be prerequities of itself
	 						} else { 
	 							$post_array[ $p->ID ] = $p->post_title;
	 						}
	 					}
	 				} else {
	 					// If we don't have any items then override the lazy load flag
	 					$_settings['lazy_load'] = false;
	 				}

	 				if ((isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
	 					$lazy_load_data = array();
	 					$lazy_load_data['query_vars'] 	= 	$query_options;
	 					$lazy_load_data['query_type']	= 	'WP_Query';
	 					$lazy_load_data['value']		=	$_settings['value'];
	 					$settings["{$lessons_prefix}course"]['lazy_load_data'] = $lazy_load_data;
	 				}

					$settings["{$lessons_prefix}course"]['initial_options'] = $post_array;
				} else {
					unset( $settings["{$lessons_prefix}course"] );
				}
			}
			return $settings;
		}


		/**
		 * Set up topic display settings
		 * 
		 * Filter callback for '{$topics_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.2.0.2
		 * 
		 * @param  array  $settings        topic settings
		 * @return array                   topic settings
		 */
		function topic_display_settings( $settings ) {
			global $sfwd_lms;
			$sfwd_topics = $sfwd_lms->post_types['sfwd-topic'];
			$topics_prefix = $sfwd_topics->get_prefix();

			if ( ( isset( $settings["{$topics_prefix}course"] ) ) && ( ! empty( $settings["{$topics_prefix}course"] ) ) ) {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {
				
					$_settings = $settings["{$topics_prefix}course"];
				
					$query_options = array( 
						'post_type' 		=> 	'sfwd-courses', 
						'post_status' 		=> 	'any',  
						'posts_per_page' 	=> 	-1,
						'exclude'			=>	get_the_id(),
						'orderby'			=>	'title',
						'order'				=>	'ASC'	
					);

					$lazy_load = apply_filters('learndash_element_lazy_load_admin', true);
					if (($lazy_load == true) && (isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
						$query_options['paged'] 			= 	1;
						$query_options['posts_per_page'] 	= 	apply_filters('learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, "{$topics_prefix}course");
					}

					 /**
					 * Filter course prerequisites
					 * 
					 * @since 2.2.0.2
					 * 
					 * @param  array  $options 
					 */
					$query_options = apply_filters( 'learndash_topic_course_post_options', $query_options, $_settings );

	 				$query_posts = new WP_Query( $query_options );
				
	 				$post_array = array( '0' => sprintf( esc_html_x( '-- Select a %s --', 'Select a Course Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) );

	 				if ( ! empty( $query_posts->posts ) ) {
	 					if ( count( $query_posts->posts ) >= $query_posts->found_posts ) {
	 						// If the number of returned posts is equal or greater then found_posts then no need to run lazy load
	 						$_settings['lazy_load'] = false;
	 					}
					
	 					foreach ( $query_posts->posts as $p ) {
	 						if ( $p->ID == get_the_id() ){
	 							//Skip for current post id as current course can not be prerequities of itself
	 						} else { 
	 							$post_array[ $p->ID ] = $p->post_title;
	 						}
	 					}
	 				} else {
	 					// If we don't have any items then override the lazy load flag
	 					$_settings['lazy_load'] = false;
	 				}
 				
					if ((isset($_settings['lazy_load'])) && ($_settings['lazy_load'] == true)) {
	 					$lazy_load_data = array();
	 					$lazy_load_data['query_vars'] 	= 	$query_options;
	 					$lazy_load_data['query_type']	= 	'WP_Query';
	 					$lazy_load_data['value']		=	$_settings['value'];
	 					$settings["{$topics_prefix}course"]['lazy_load_data'] = $lazy_load_data;
	 				}

					$settings["{$topics_prefix}course"]['initial_options'] = $post_array;
				} else {
					unset( $settings["{$topics_prefix}course"] );
				}
			}
		
			
			if ( ( isset( $settings["{$topics_prefix}lesson"] ) ) && ( ! empty( $settings["{$topics_prefix}lesson"] ) ) ) {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {

	 				$post_array = array( '0' => sprintf( esc_html_x( '-- Select a %s --', 'Select a Lesson Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ) );
					$course_id = learndash_get_course_id( get_the_id() );
					if ( !empty( $course_id ) ) {
						$lessons_items = $this->select_a_lesson_or_topic( intval( $course_id ), false );
						if ( !empty( $lessons_items ) ) {
							if ( isset( $lessons_items[0] ) ) unset( $lessons_items[0] );
							$post_array = $lessons_items;
						}
					}

					$settings["{$topics_prefix}lesson"]['initial_options'] = $post_array;
				} else {
					unset( $settings["{$topics_prefix}lesson"] );
				}
			}
			return $settings;
		}



		/**
		 * Insert course name as a term on course publish
		 * 
		 * Action callback for 'publish_sfwd-courses' (wp core filter action)
		 *
		 * @todo  consider for deprecation, action is commented 
		 *
		 * @since 2.1.0
		 * 
		 * @param int 		$post_id
		 * @param object 	$post
		 */
		function add_course_tax_entry( $post_id, $post ) {
			$term = get_term_by( 'slug', $post->post_name, 'courses' );
			$term_id = isset( $term->term_id ) ? $term->term_id : 0;

			if ( ! $term_id ) {
				$term = wp_insert_term( $post->post_title, 'courses', array( 'slug' => $post->post_name ) );
				$term_id = $term['term_id'];
			}

			wp_set_object_terms( (int)$post_id, (int)$term_id, 'courses', true );
		}



		/**
		 * Register taxonomies for each custom post type
		 * 
		 * Action callback for 'init'
		 *
		 * @since 2.1.0
		 */
		function tax_registration() {

			/**
			 * Filter that gathers taxonomies that need to be registered
			 * add_filters are currently added during the add_post_type() method in swfd_cpt.php
			 *
			 * @since 2.1.0
			 * 
			 * @param  array
			 */
			$taxes = apply_filters( 'sfwd_cpt_register_tax', array() );

			/**
			 * The expected return form of the array is:
			 *	array(
			 *		'tax_slug1' => 	array(
			 *							'post_types' => array('sfwd-courses', 'sfwd-lessons'),
			 * 							'tax_args' => array() // See register_taxonomy() third parameter for valid args options
			 *						),
			 *		'tax_slug2' => 	array(
			 *							'post_types' => array('sfwd-lessons'),
			 * 							'tax_args' => array() 
			 *						),
			 *	)
			 */

			if ( ! empty( $taxes ) ) {
				foreach( $taxes as $tax_slug => $tax_options ) {
					if ( !taxonomy_exists( $tax_slug ) ) {
						if ( ( isset( $tax_options['post_types'] ) ) && ( !empty( $tax_options['post_types'] ) ) ) {
							if ( ( isset( $tax_options['tax_args'] ) ) && ( !empty( $tax_options['tax_args'] ) ) ) {

								// Via the LD post type setup when the 'taxonomies' option is defined we can associate other taxonomies
								// with our custom post types by setting the tax slug and value as the same
								if ( $tax_slug !== $tax_options['tax_args']['rewrite']['slug'] ) {
									$tax_options = apply_filters( 'learndash_taxonomy_args', $tax_options, $tax_slug );
									if ( !empty( $tax_options ) ) {
										register_taxonomy( $tax_slug, $tax_options['post_types'], $tax_options['tax_args'] );
									}
								}
							}
						}
					} else {
					
						// If the taxonomy already exists we only need to then assocated the post_types 
						if ( ( isset( $tax_options['post_types'] ) ) && ( !empty( $tax_options['post_types'] ) ) ) {
							foreach( $tax_options['post_types'] as $post_type ) {
								register_taxonomy_for_object_type( $tax_slug, $post_type );
							}
						}
					}
				}



/*
				foreach ( $taxes as $k => $v ) {

					if ( ! empty( $v ) ) {

						foreach ( $v as $tax ) {

							if ( ! is_array( $tax[0] ) ) { 
								$tax[0] = array( $tax[0] );
							}

							$post_types = array_merge( $post_types, $tax[0] );

							if ( empty( $tax_options ) ) {
								$tax_options = $tax[1];
							} else {
								foreach ( $tax[1] as $l => $w ) {
									$tax_options[ $l] = $w;
								}
							}

						} // end foreach

					} // endif

				}// end foreach
*/
//				register_taxonomy( $k, $post_types, $tax_options );				
			} // endif

		}

		static function get_template_paths( $filename = '' ) {
			$template_filenames = array();
			$template_paths = array();

			$active_template_key = LearnDash_Theme_Register::get_active_theme_key();
			$file_pathinfo = pathinfo( $filename );
			
			if ( ! isset( $file_pathinfo['dirname'] ) ) {
				$file_pathinfo['dirname'] = '';
			} else if ( ! empty( $file_pathinfo['dirname'] ) ) {
				if ( '.' === $file_pathinfo['dirname'] ) {
					$file_pathinfo['dirname'] = '';
				} else {
					$file_pathinfo['dirname'] .= '/';
				}
			} 

			if ( ! isset( $file_pathinfo['filename'] ) ) {
				$file_pathinfo['filename'] = '';
			}

			if ( ! isset( $file_pathinfo['extension'] ) ) {
				$file_pathinfo['extension'] = '';
			} 

			if ( in_array( $file_pathinfo['extension'], array( 'js', 'css' ), true ) ) {
				if ( ( defined( 'LEARNDASH_SCRIPT_DEBUG' ) ) && ( LEARNDASH_SCRIPT_DEBUG == true ) ) {
					$template_filenames[] = $file_pathinfo['dirname'] . $file_pathinfo['filename'] . '.' . $file_pathinfo['extension'];
				}

				$template_filenames[] = $file_pathinfo['dirname'] . $file_pathinfo['filename'] . '.min.' . $file_pathinfo['extension'];
			} else {
				$template_filenames[] = $file_pathinfo['dirname'] . $file_pathinfo['filename'] . '.' . $file_pathinfo['extension'];
			}

			$template_paths['theme'] = array();
			foreach( $template_filenames as $template_filename ) {
				$template_paths['theme'][] = 'learndash/' . $active_template_key . '/' . $template_filename;
			}

			if ( 'legacy' === $active_template_key ) {
				foreach( $template_filenames as $template_filename ) {
					$template_paths['theme'][] = 'learndash/' . $template_filename;
				}

				foreach( $template_filenames as $template_filename ) {
					$template_paths['theme'][] = $template_filename;
				}
			}

			$template_paths['templates'] = array();
			if ( defined( 'LEARNDASH_TEMPLATES_DIR' ) ) {
				$template_dir = trailingslashit( LEARNDASH_TEMPLATES_DIR );
				foreach( $template_filenames as $template_filename ) {
					$template_paths['templates'][] = $template_dir . $active_template_key . '/' . $template_filename;
				}
				if ( 'learndash_template_functions.php' === $file_pathinfo['filename'] ) {
					$template_paths['templates'][] = $template_dir . $active_template_key . '/' . 'functions.php';
				}
				if ( 'legacy' === $active_template_key ) {
					foreach( $template_filenames as $template_filename ) {
						$template_paths['templates'][] = $template_dir . $template_filename;
					}
					if ( 'learndash_template_functions.php' === $file_pathinfo['filename'] ) {
						$template_paths['templates'][] = $template_dir . 'functions.php';
					}
				}
			}
			
			$active_template_dir = LearnDash_Theme_Register::get_active_theme_template_dir();
			if ( ! empty( $active_template_dir ) ) {
				foreach( $template_filenames as $template_filename ) {
					$template_paths['templates'][] = $active_template_dir . '/' . $template_filename;
				}
			}

			if ( LEARNDASH_LEGACY_THEME !== $active_template_key ) {
				$legacy_theme_instance = LearnDash_Theme_Register::get_theme_instance( LEARNDASH_LEGACY_THEME );
				$legacy_theme_dir = $legacy_theme_instance->get_theme_template_dir();
				if ( ! empty( $legacy_theme_dir ) ) {
					foreach( $template_filenames as $template_filename ) {
						$template_paths['templates'][] = $legacy_theme_dir . '/' . $template_filename;
					}
				}
			}
			return $template_paths;
		}

		/**
		 * Get LearnDash template and pass data to be used in template
		 *
		 * Checks to see if user has a 'learndash' directory in their current theme
		 * and uses the template if it exists.
		 *
		 * @since 2.1.0
		 * 
		 * @param  string  	$name             template name
		 * @param  array  	$args             data for template
		 * @param  boolean 	$echo             echo or return
		 * @param  boolean 	return_file_path  return just file path instead of output
		 */
		static function get_template( $name, $args, $echo = false, $return_file_path = false ) {			
			$template_paths = array();

			$file_pathinfo = pathinfo( $name );
			//error_log('file_pathinfo<pre>'. print_r($file_pathinfo, true) .'</pre>');

			$template_filename = $name;
			if ( ( ! isset( $file_pathinfo['extension'] ) ) || ( empty( $file_pathinfo['extension'] ) ) ) {
				$template_filename .= '.php';
			} 
			
			/**
			 * Allow override of template filename.
			 * @since 3.0.
			 */
			$template_filename = apply_filters( 'learndash_template_filename', $template_filename, $name, $args, $echo, $return_file_path );

			if ( empty( $template_filename ) ) {
				return;
			}

			$file_pathinfo = pathinfo( $template_filename );
			if ( ( ! isset( $file_pathinfo['extension'] ) ) || ( empty( $file_pathinfo['extension'] ) ) ) {
				$template_filename .= '.php';
			} 

			$template_paths = self::get_template_paths( $template_filename );

			$filepath = '';
			if ( ( isset( $template_paths['theme'] ) ) && ( ! empty( $template_paths['theme'] ) ) ) {
				$filepath = locate_template( $template_paths['theme'] );
			}
						
			if ( empty( $filepath ) ) {
				if ( ( isset( $template_paths['templates'] ) ) && ( ! empty( $template_paths['templates'] ) ) ) {
					foreach ( $template_paths['templates'] as $template ) {
						if ( file_exists( $template ) ) {
							$filepath = $template;
							break;
						}
					}
				}
			}

			/**
			 * Filter filepath for learndash template being called
			 * 
			 * @since 2.1.0
			 * @since 3.0.3 - Allow override of empty or other checks. 
			 * 
			 * @param  string  $filepath
			 */
			$filepath = apply_filters( 'learndash_template', $filepath, $name, $args, $echo, $return_file_path );
			if ( ! $filepath ) {
				return false;
			}

			if ( $return_file_path ) {
				return $filepath;
			}

			// Added check to ensure external hooks don't return empty or non-accessible filenames. 
			if ( ( !empty( $filepath ) ) && ( file_exists( $filepath ) ) && ( is_file( $filepath ) ) ) {
				$args = apply_filters( 'ld_template_args_' . $name, $args, $filepath, $echo );
				if ( ( ! empty( $args ) ) && ( is_array( $args ) ) ) {
					extract( $args );
				}
				$level = ob_get_level();
				ob_start();
				include( $filepath );
				$contents = learndash_ob_get_clean( $level );

				if ( ! $echo ) {
					return $contents;
				}

				echo $contents;
			}
		}
		
		/**
		 * Called from the 'all_plugins' filter. This is called from the Plugins listing screen and will let us
		 * set out internal flag 'ALL_PLUGUNS_CALLED' so we know when (and when not) to add the legacy pluin paths
		 *
		 * @since 2.3.0.3
		 * 
		 * @param array $all_plugings The array of plugins to be displayed on the Plugins listing
		 * @return array $all_plugings
		 */
		function all_plugins_proc( $all_plugins ) {
			$this->ALL_PLUGUNS_CALLED = true;
			return $all_plugins;
		}

		/**
		 * Called from the 'pre_current_active_plugins' action. This is called after the Plugins listing checks for
		 * valid plugins. The will let us unset our internal flag 'ALL_PLUGINS_CALLED'.
		 *
		 * @since 2.3.0.3
		 * @param none
		 * @return none
		 */
		function pre_current_active_plugins_proc() {
			$this->ALL_PLUGUNS_CALLED = false;
		}

		/**
		 * This is called from the get_options() function for the option 'active_plugins'. Using this filter 
		 * we can append our legacy plugins directories allowing other plugins to check via is_plugin_active()
		 * This will protect that connection of LD is installed in a non-standard plugin directory.
		 *
		 * @since 2.3.0.3
		 *
		 * @param array $active_plugins An array of the current active plugins.
		 * @return array $active_plugins
		 */
		function option_active_plugins_proc( $active_plugins ) {
			global $pagenow;

			if (!empty( $active_plugins ) ) {
				if ( ( 'plugins.php' != $pagenow ) || ( $this->ALL_PLUGUNS_CALLED != true ) ) {
					// Just a double check the current_plugin_dir is in the active plugins array.
					if ( in_array( LEARNDASH_LMS_PLUGIN_KEY, $active_plugins ) ) {
						if ( ( !empty( $this->learndash_legacy_plugins_paths ) ) && ( is_array( $this->learndash_legacy_plugins_paths ) ) ) {
							foreach( $this->learndash_legacy_plugins_paths as $learndash_legacy_plugins_path ) {
								if ( $learndash_legacy_plugins_path != LEARNDASH_LMS_PLUGIN_KEY ) {
									if ( !in_array( $learndash_legacy_plugins_path, $active_plugins ) ) {
										$active_plugins[] = $learndash_legacy_plugins_path;
									}
								}
							}
						}
					}
				}
			}
			return $active_plugins;
		}

		/**
		 * This is called from the update_options() function for the option 'active_plugins'. Using this filter 
		 * we can remove our legacy plugins directories we added via the option_active_plugins_proc filter. 
		 *
		 * @since 2.3.0.3
		 *
		 * @param array $active_plugins An array of the current active plugins.
		 * @return array $active_plugins
		 */
		function pre_update_option_active_plugins( $active_plugins ) {
			if ( !empty( $active_plugins ) ) {
				if ( ( !empty( $this->learndash_legacy_plugins_paths ) ) && ( is_array( $this->learndash_legacy_plugins_paths ) ) ) {
					foreach( $this->learndash_legacy_plugins_paths as $learndash_legacy_plugins_path ) {
						if ( $learndash_legacy_plugins_path != LEARNDASH_LMS_PLUGIN_KEY ) {
							if ( ( $key = array_search( $learndash_legacy_plugins_path, $active_plugins ) ) !== false) {
								unset( $active_plugins[$key] );
							}
						}
					}
				}
			}
			return $active_plugins;
		}
			
		function site_option_active_sitewide_plugins_proc( $active_plugins ) {	
			global $pagenow;

			if (!empty( $active_plugins ) ) {
	
				if ( ( 'plugins.php' != $pagenow ) || ( $this->ALL_PLUGUNS_CALLED != true ) ) {

					// Just a double check the current_plugin_dir is in the active plugins array.
					if ( isset( $active_plugins[LEARNDASH_LMS_PLUGIN_KEY] ) ) {
						if ( ( !empty( $this->learndash_legacy_plugins_paths ) ) && ( is_array( $this->learndash_legacy_plugins_paths ) ) ) {
							foreach( $this->learndash_legacy_plugins_paths as $learndash_legacy_plugins_path ) {
								if ( $learndash_legacy_plugins_path != LEARNDASH_LMS_PLUGIN_KEY ) {
									if ( !isset( $active_plugins[$learndash_legacy_plugins_path] ) )  {
										$active_plugins[$learndash_legacy_plugins_path] = $active_plugins[LEARNDASH_LMS_PLUGIN_KEY];
									}
								}
							}
						}
					}
				}
			}

			return $active_plugins;
		}			

		function pre_update_site_option_active_sitewide_plugins( $active_plugins ) {
			if ( !empty( $active_plugins ) ) {
				if ( ( !empty( $this->learndash_legacy_plugins_paths ) ) && ( is_array( $this->learndash_legacy_plugins_paths ) ) ) {
					foreach( $this->learndash_legacy_plugins_paths as $learndash_legacy_plugins_path ) {
						if ( $learndash_legacy_plugins_path != LEARNDASH_LMS_PLUGIN_KEY ) {
							if ( isset( $active_plugins[$learndash_legacy_plugins_path] ) ) {
								unset( $active_plugins[$learndash_legacy_plugins_path] );
							}
						}
					}
				}
			}

			return $active_plugins;
		}

		
		/**
		 * Add support for alternate templates directory. 
		 * Normally LD will load template files from the active theme directory
		 * or if not found via the plugin templates directory. We now support 
		 * a nuetral directory wp-content/uploads/learndash/templates/
		 * 
		 * If the site uses a functions.php it will be loaded from that directory
		 * This is the recommended place to add actions/filters to prevent theme updates
		 * from erasing them. 
		 *
		 * @since 2.4
		 * 
		 * @param  none
		 * @return none
		 */
		function init_ld_templates_dir() {
			if ( ! defined('LEARNDASH_TEMPLATES_DIR' ) ) {
				$wp_upload_dir = wp_upload_dir();
				$ld_templates_dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'learndash/templates/';
				define( 'LEARNDASH_TEMPLATES_DIR', $ld_templates_dir );

				if ( ! file_exists( $ld_templates_dir ) ) {
					if ( wp_mkdir_p( $ld_templates_dir ) !== false ) {
						// To prevent security browsing add an index.php file.
						file_put_contents( trailingslashit( $ld_templates_dir ) .'index.php', '// nothing to see here');
					}
				}
			}
			
			// Piggy back to this logic and cleanup the reports directory
			if ( ( is_admin() ) && (( !defined( 'DOING_AJAX' ) ) || ( DOING_AJAX !== true )) ) {

				$wp_upload_dir = wp_upload_dir();
				$ld_reports_dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'learndash/';
			
				if ( file_exists( $ld_reports_dir ) ) {
					$filenames = array();

					$filenames_csv = glob( $ld_reports_dir ."*.csv");
					if (( is_array( $filenames_csv ) ) && ( !empty( $filenames_csv ) ))
						$filenames = array_merge($filenames, $filenames_csv );

					$filenames_csv = glob( $ld_reports_dir ."/reports/*.csv" );
					if (( is_array( $filenames_csv ) ) && ( !empty( $filenames_csv ) ))
						$filenames = array_merge($filenames, $filenames_csv );

					if ( !empty( $filenames ) ) {
						foreach ( $filenames as $filename ) {
							if ( filemtime( $filename ) < ( time() - 60 * 60) ) {
								$file = basename( $filename );

								if ( substr( $file, 0, strlen( 'learndash_reports_user_courses_' )) == 'learndash_reports_user_courses_' ) {
									$transient_hash = str_replace( array( 'learndash_reports_user_courses_', '.csv' ), '', $file );
						
									$options_key = 'learndash_reports_user_courses_' . $transient_hash;
									delete_option( $options_key );

									$options_key = '_transient_user-courses_' . $transient_hash;
									delete_option( $options_key );

									$options_key = '_transient_timeout_user-courses_' . $transient_hash;
									delete_option( $options_key );

									@unlink( $filename );

								} else if ( substr( $file, 0, strlen( 'learndash_reports_user_quizzes' )) == 'learndash_reports_user_quizzes' ) {
									$transient_hash = str_replace( array( 'learndash_reports_user_quizzes', '.csv' ), '', $file );
						
									$options_key = 'learndash_reports_user_quizzes_' . $transient_hash;
									delete_option( $options_key );

									$options_key = '_transient_user-quizzes_' . $transient_hash;
									delete_option( $options_key );

									$options_key = '_transient_timeout_user-quizzes_' . $transient_hash;
									delete_option( $options_key );

									@unlink( $filename );
								}
							}
						}
					}
				}
			}
		}

		// If on the Course, Lessons, Topics section we display the WP Post Categories or Post Tags. We want to hide the row action 'view' links. 
		function ld_course_category_row_actions( $actions, $tag ) {
			global $learndash_post_types;
			global $pagenow, $taxnow;

			if ( ( $pagenow == 'edit-tags.php' ) && ( ( $taxnow == 'category' ) || ( $taxnow == 'post_tag' ) ) ) {
				if ( in_array( get_current_screen()->post_type, $learndash_post_types ) !== false ) {
					if ( isset( $actions['view'] ) ) {
						//unset( $actions['view'] );
						$current_href_old = get_term_link( $tag );
						$current_href_new = add_query_arg( 'post_type', get_current_screen()->post_type, $current_href_old );
						$actions['view'] = str_replace( $current_href_old, $current_href_new, $actions['view'] );
					}
				}
			}

			return $actions;
		}

		/**
		 * Function to dynamically control the 'the_content' filtering for this post_type instance.
		 * This is needed for example when using the 'the_content' filters manually and do not want the
		 * normal filters recursively applied.
		 *
		 * @since 2.5.9
		 *
		 * @param boolean $filter_check True if the_content filter is to be enabled.
		 * @param array   $post_types Limit change to specific instance post types. default is all. 
		 */
		public static function content_filter_control( $filter_check = true, $post_types = array() ) {
			
			if ( empty( $post_types ) ) {
				$post_types = array_keys( SFWD_CPT_Instance::$instances );
			}
			foreach( SFWD_CPT_Instance::$instances as $post_type => $instance ) {
				if ( in_array( $post_type, $post_types ) ) {
					$instance->content_filter_control( $filter_check );
				}
			}
		}

		// End of functions.
	}
}

global $sfwd_lms;
$sfwd_lms = new SFWD_LMS();
