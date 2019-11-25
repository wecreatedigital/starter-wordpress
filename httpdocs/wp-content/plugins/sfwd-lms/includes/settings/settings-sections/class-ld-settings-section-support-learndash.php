<?php
/**
 * LearnDash Settings Section for Support LearnDash Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_LearnDash' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Support_LearnDash extends LearnDash_Settings_Section {

		/**
		 * Settings set array for this section.
		 *
		 * @var array $settings_set Array of settings used by this section.
		 */

		protected $settings_set = array();

		/**
		 * Translations MO files array.
		 *
		 * @var array $mo_files Array of translation MO files.
		 */
		private $mo_files = array();

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_support';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'ld_settings';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_ld_settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'LearnDash Settings', 'learndash' );

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			$ABSPATH_tmp = str_replace( '\\', '/', ABSPATH );

			/************************************************************************************************
			 * LearnDash Settings
			 ************************************************************************************************/
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {

				$this->settings_set = array();

				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);

				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Setting', 'learndash' ),
						'text'  => 'Setting',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Value', 'learndash' ),
						'text'  => 'Value',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['settings'] = array();

				$element = Learndash_Admin_Data_Upgrades::get_instance();

				$ld_license_info = get_option( 'nss_plugin_info_sfwd_lms' );

				if ( ( $ld_license_info ) && ( property_exists( $ld_license_info, 'new_version' ) ) && ( ! empty( $ld_license_info->new_version ) ) ) {
					if ( version_compare( LEARNDASH_VERSION, $ld_license_info->new_version, '<' ) ) {
						$LEARNDASH_VERSION_value_html = '<span style="color: red">' . LEARNDASH_VERSION . '</span> ' .
						sprintf(
							// translators: placeholder: version number.
							esc_html_x( 'A newer version of LearnDash (%s) is available.', 'placeholder: version number', 'learndash' ),
							$ld_license_info->new_version
						) . ' <a href="' . admin_url( 'plugins.php?plugin_status=upgrade' ) . '">' . esc_html__( 'Please upgrade.', 'learndash' ) . '</a>';
						$LEARNDASH_VERSION_value = LEARNDASH_VERSION . ' - (X)';

					} else {
						$LEARNDASH_VERSION_value_html = '<span style="color: green">' . LEARNDASH_VERSION . '</span>';
						$LEARNDASH_VERSION_value      = LEARNDASH_VERSION;
					}
				} else {
					$LEARNDASH_VERSION_value      = LEARNDASH_VERSION;
					$LEARNDASH_VERSION_value_html = LEARNDASH_VERSION;
				}

				$ld_prior_version = $element->get_data_settings( 'prior_version' );
				if ( ( ! empty( $ld_prior_version ) ) && ( LEARNDASH_VERSION != $ld_prior_version ) ) {
					$LEARNDASH_VERSION_value      .= sprintf( ' (upgraded from %s)', $ld_prior_version );
					$LEARNDASH_VERSION_value_html .= sprintf(
						// translators: placeholder: prior LearnDash version.
						esc_html_x( ' (upgraded from %s)', 'placeholder: prior LearnDash version', 'learndash' ),
						$ld_prior_version
					);
				}

				$this->settings_set['settings']['LEARNDASH_VERSION'] = array(
					'label'      => 'Learndash Version',
					'label_html' => esc_html__( 'Learndash Version', 'learndash' ),
					'value'      => $LEARNDASH_VERSION_value,
					'value_html' => $LEARNDASH_VERSION_value_html,
				);

				$ld_license_valid = get_option( 'nss_plugin_remote_license_sfwd_lms' );
				$ld_license_check = get_option( 'nss_plugin_check_sfwd_lms' );

				if ( ( isset( $ld_license_valid['value'] ) ) && ( '1' === $ld_license_valid['value'] ) ) {
					$license_value_html = '<span style="color: green">' . esc_html__( 'Yes', 'learndash' ) . '</span>';
					$license_value      = 'Yes';
					if ( ! empty( $ld_license_check ) ) {
						$license_value_html .= ' (' . sprintf(
							// translators: placeholder: date.
							esc_html_x( 'last check: %s', 'placeholder: date', 'learndash' ),
							learndash_adjust_date_time_display( $ld_license_check )
						) . ')';
						$license_value .= ' (last check: ' . learndash_adjust_date_time_display( $ld_license_check ) . ')';
					}
				} else {
					$license_value_html = '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>';
					$license_value      = 'No (X)';
				}
				$this->settings_set['settings']['LEARNDASH_license'] = array(
					'label'      => 'LearnDash License Valid',
					'label_html' => esc_html__( 'LearnDash License Valid', 'learndash' ),
					'value'      => $license_value,
					'value_html' => $license_value_html,
				);

				$this->settings_set['settings']['LEARNDASH_SETTINGS_DB_VERSION'] = array(
					'label'      => 'DB Version',
					'label_html' => esc_html__( 'DB Version', 'learndash' ),
					'value'      => LEARNDASH_SETTINGS_DB_VERSION,
				);

				$data_settings_courses = $element->get_data_settings( 'user-meta-courses' );
				if ( ( ! empty( $data_settings_courses ) ) && ( ! empty( $data_settings_courses ) ) ) {
					if ( version_compare( $data_settings_courses['version'], LEARNDASH_SETTINGS_DB_VERSION, '<' ) ) {
						$color      = 'red';
						$color_text = ' (X)';
					} else {
						$color      = 'green';
						$color_text = '';
					}
					$data_upgrade_courses_value      = $data_settings_courses['version'] . $color_text;
					$data_upgrade_courses_value_html = '<span style="color: ' . $color . '">' . $data_settings_courses['version'] . '</span>';

					if ( 'red' == $color ) {
						$data_upgrade_courses_value_html .= ' <a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'Please run the Data Upgrade.', 'learndash' ) . '</a>';
					} elseif ( ( isset( $data_settings_courses['last_run'] ) ) && ( ! empty( $data_settings_courses['last_run'] ) ) ) {
						$data_upgrade_courses_value      .= ' (' . learndash_adjust_date_time_display( $data_settings_courses['last_run'] ) . ')';
						$data_upgrade_courses_value_html .= ' (' . sprintf(
							// translators: placeholder: datetime.
							esc_html_x( 'last run %s', 'placeholder: datetime', 'learndash' ),
							learndash_adjust_date_time_display( $data_settings_courses['last_run'] )
						) . ')';
					}
				} else {
					$data_upgrade_courses_value      = '';
					$data_upgrade_courses_value_html = '';
				}

				$this->settings_set['settings']['Data Upgrade Courses'] = array(
					'label'      => 'Data Upgrade Courses',
					'label_html' => esc_html__( 'Data Upgrade Courses', 'learndash' ),
					'value'      => $data_upgrade_courses_value,
					'value_html' => $data_upgrade_courses_value_html,
				);

				$data_settings_quizzes = $element->get_data_settings( 'user-meta-quizzes' );
				if ( ( ! empty( $data_settings_quizzes ) ) && ( ! empty( $data_settings_quizzes ) ) ) {
					if ( version_compare( $data_settings_quizzes['version'], LEARNDASH_SETTINGS_DB_VERSION, '<' ) ) {
						$color      = 'red';
						$color_text = ' (X)';
					} else {
						$color      = 'green';
						$color_text = '';
					}
					$data_upgrade_quizzes_value      = $data_settings_quizzes['version'] . $color_text;
					$data_upgrade_quizzes_value_html = '<span style="color: ' . $color . '">' . $data_settings_quizzes['version'] . '</span>';
					if ( 'red' == $color ) {
						$data_upgrade_quizzes_value_html .= ' <a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'Please run the Data Upgrade.', 'learndash' );
					} elseif ( ( isset( $data_settings_quizzes['last_run'] ) ) && ( ! empty( $data_settings_quizzes['last_run'] ) ) ) {
						$data_upgrade_quizzes_value      .= ' (' . learndash_adjust_date_time_display( $data_settings_quizzes['last_run'] ) . ')';
						$data_upgrade_quizzes_value_html .= ' (' . sprintf(
							// translators: placeholder: datetime.
							esc_html_x( 'last run %s', 'placeholder: datetime', 'learndash' ),
							learndash_adjust_date_time_display( $data_settings_quizzes['last_run'] )
						) . ')';
					}
				} else {
					$data_upgrade_quizzes_value      = '';
					$data_upgrade_quizzes_value_html = '';
				}

				$this->settings_set['settings']['Data Upgrade Quizzes'] = array(
					'label'      => 'Data Upgrade Quizzes',
					'label_html' => esc_html__( 'Data Upgrade Quizzes', 'learndash' ),
					'value'      => $data_upgrade_quizzes_value,
					'value_html' => $data_upgrade_quizzes_value_html,
				);

				$data_pro_quiz_questions = $element->get_data_settings( 'pro-quiz-questions' );
				if ( ( ! empty( $data_pro_quiz_questions ) ) && ( ! empty( $data_pro_quiz_questions ) ) ) {
					if ( version_compare( $data_pro_quiz_questions['version'], LEARNDASH_SETTINGS_DB_VERSION, '<' ) ) {
						$color      = 'red';
						$color_text = ' (X)';
					} else {
						$color      = 'green';
						$color_text = '';
					}
					$data_pro_quiz_questions_value = $data_pro_quiz_questions['version'] . $color_text;
					$data_pro_quiz_questions_html  = '<span style="color: ' . $color . '">' . $data_pro_quiz_questions['version'] . '</span>';
					if ( 'red' == $color ) {
						$data_pro_quiz_questions_html .= ' <a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'Please run the Data Upgrade.', 'learndash' );
					} elseif ( ( isset( $data_pro_quiz_questions['last_run'] ) ) && ( ! empty( $data_pro_quiz_questions['last_run'] ) ) ) {
						$data_pro_quiz_questions_value .= ' (' . learndash_adjust_date_time_display( $data_pro_quiz_questions['last_run'] ) . ')';
						$data_pro_quiz_questions_html  .= ' (' . sprintf(
							// translators: placeholder: datetime.
							esc_html_x( 'last run %s', 'placeholder: datetime', 'learndash' ),
							learndash_adjust_date_time_display( $data_pro_quiz_questions['last_run'] )
						) . ')';
					}
				} else {
					$data_pro_quiz_questions_value = '';
					$data_pro_quiz_questions_html  = '';
				}

				$this->settings_set['settings']['Data ProQuiz Questions'] = array(
					'label'      => 'Data ProQuiz Questions',
					'label_html' => esc_html__( 'Data Upgrade ProQuiz Questions', 'learndash' ),
					'value'      => $data_pro_quiz_questions_value,
					'value_html' => $data_pro_quiz_questions_html,
				);

								$data_course_access_lists = $element->get_data_settings( 'course-access-lists-convert' );
				if ( ( ! empty( $data_course_access_lists ) ) && ( ! empty( $data_course_access_lists ) ) ) {
					if ( version_compare( $data_course_access_lists['version'], LEARNDASH_SETTINGS_DB_VERSION, '<' ) ) {
						$color      = 'red';
						$color_text = ' (X)';
					} else {
						$color      = 'green';
						$color_text = '';
					}
					$data_course_access_lists_value = $data_course_access_lists['version'] . $color_text;
					$data_course_access_lists_html  = '<span style="color: ' . $color . '">' . $data_course_access_lists['version'] . '</span>';
					if ( 'red' == $color ) {
						$data_course_access_lists_html .= ' <a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'Please run the Data Upgrade.', 'learndash' );
					} elseif ( ( isset( $data_course_access_lists['last_run'] ) ) && ( ! empty( $data_course_access_lists['last_run'] ) ) ) {
						$data_course_access_lists_value .= ' (' . learndash_adjust_date_time_display( $data_course_access_lists['last_run'] ) . ')';
						$data_course_access_lists_html  .= ' (' . sprintf(
							// translators: placeholder: datetime.
							esc_html_x( 'last run %s', 'placeholder: datetime', 'learndash' ),
							learndash_adjust_date_time_display( $data_course_access_lists['last_run'] )
						) . ')';
					}
				} else {
					$data_course_access_lists_value = '';
					$data_course_access_lists_html  = '';
				}

				$this->settings_set['settings']['Data Course Access Lists Convert'] = array(
					'label'      => 'Data Course Access Lists Convert',
					'label_html' => esc_html__( 'Data Upgrade Course Access Lists Convert', 'learndash' ),
					'value'      => $data_course_access_lists_value,
					'value_html' => $data_course_access_lists_html,
				);

				$courses_count                                   = wp_count_posts( 'sfwd-courses' );
				$this->settings_set['settings']['courses_count'] = array(
					'label'      => 'Courses Count',
					'label_html' => esc_html__( 'Courses Count', 'learndash' ),
					'value'      => $courses_count->publish,
				);

				$lessons_count                                   = wp_count_posts( 'sfwd-lessons' );
				$this->settings_set['settings']['lessons_count'] = array(
					'label'      => 'Lessons Count',
					'label_html' => esc_html__( 'Lessons Count', 'learndash' ),
					'value'      => $lessons_count->publish,
				);

				$topics_count                                   = wp_count_posts( 'sfwd-topic' );
				$this->settings_set['settings']['topics_count'] = array(
					'label'      => 'Topics Count',
					'label_html' => esc_html__( 'Topics Count', 'learndash' ),
					'value'      => $topics_count->publish,
				);

				$quizzes_count                                   = wp_count_posts( 'sfwd-quiz' );
				$this->settings_set['settings']['quizzes_count'] = array(
					'label'      => 'Quizzes Count',
					'label_html' => esc_html__( 'Quizzes Count', 'learndash' ),
					'value'      => $quizzes_count->publish,
				);

				$this->settings_set['settings']['active_theme'] = array(
					'label'      => 'Active LD Theme',
					'label_html' => esc_html__( 'Active LD Theme', 'learndash' ),
					'value'      => LearnDash_Theme_Register::get_active_theme_name(),
				);

				$this->settings_set['settings']['courses_autoenroll_admin_users'] = array(
					'label'      => 'Courses Auto-enroll',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Auto-enroll', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);
				$this->settings_set['settings']['bypass_course_limits_admin_users'] = array(
					'label'      => 'Bypass Course limits',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Bypass %s limits', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['reports_include_admin_users'] = array(
					'label'      => 'Include in Reports',
					'label_html' => esc_html__( 'Include in Reports', 'learndash' ),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'reports_include_admin_users' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'reports_include_admin_users' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['course_builder'] = array(
					'label'      => 'Course Builder Interface',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Builder Interface', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['course_shared_steps'] = array(
					'label'      => 'Shared Course Steps',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Shared %s Steps', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['nested_urls'] = array(
					'label'      => 'Nested URLs',
					'label_html' => esc_html__( 'Nested URLs', 'learndash' ),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['courses_permalink_slug'] = array(
					'label'      => 'Courses Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( '%s Permalink slug', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' ),
				);
				$this->settings_set['settings']['lessons_permalink_slug'] = array(
					'label'      => 'Lessons Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( '%s Permalink slug', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'lessons' ),
				);
				$this->settings_set['settings']['topics_permalink_slug'] = array(
					'label'      => 'Topics Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Topics.
						esc_html_x( '%s Permalink slug', 'placeholder: Topics', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topics' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'topics' ),
				);
				$this->settings_set['settings']['quizzes_permalink_slug'] = array(
					'label'      => 'Quizzes Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( '%s Permalink slug', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quizzes' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'quizzes' ),
				);

				$this->settings_set['settings']['quiz_builder'] = array(
					'label'      => 'Quiz Builder Interface',
					'label_html' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Builder Interface', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['quiz_shared_questions'] = array(
					'label'      => 'Quiz Shared Questions',
					'label_html' => sprintf(
						// translators: placeholder: Quiz, Questions.
						esc_html_x( '%1$s Shared %2$s', 'placeholder: Quiz, Questions', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' ),
						LearnDash_Custom_Label::get_label( 'questions' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$learndash_settings_permalinks_taxonomies = get_option( 'learndash_settings_permalinks_taxonomies' );
				if ( ! is_array( $learndash_settings_permalinks_taxonomies ) ) {
					$learndash_settings_permalinks_taxonomies = array();
				}
				$learndash_settings_permalinks_taxonomies = wp_parse_args(
					$learndash_settings_permalinks_taxonomies,
					array(
						'ld_course_category' => 'course-category',
						'ld_course_tag'      => 'course-tag',
						'ld_lesson_category' => 'lesson-category',
						'ld_lesson_tag'      => 'lesson-tag',
						'ld_topic_category'  => 'topic-category',
						'ld_topic_tag'       => 'topic-tag',
					)
				);

				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'ld_course_category' ) == 'yes' ) {
					$courses_taxonomies = $sfwd_lms->get_post_args_section( 'sfwd-courses', 'taxonomies' );
					if ( ( isset( $courses_taxonomies['ld_course_category'] ) ) && ( $courses_taxonomies['ld_course_category']['public'] == true ) ) {
						$this->settings_set['settings']['ld_course_category'] = array(
							'label'      => 'Courses Category base',
							'label_html' => sprintf(
								// translators: placeholder: Course.
								esc_html_x( '%s Category base', 'placeholder: Course', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'course' )
							),
							'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_course_category'],
						);
					}

					if ( ( isset( $courses_taxonomies['ld_course_tag'] ) ) && ( true == $courses_taxonomies['ld_course_tag']['public'] ) ) {
						$this->settings_set['settings']['ld_course_tag'] = array(
							'label'      => 'Courses Tag',
							'label_html' => sprintf(
								// translators: placeholder: Course.
								esc_html_x( '%s Tag base', 'placeholder: Course', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'course' )
							),
							'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_course_tag'],
						);
					}
				}

				if ( 'yes' == LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_Taxonomies', 'ld_lesson_category' ) ) {
					$lessons_taxonomies = $sfwd_lms->get_post_args_section( 'sfwd-lessons', 'taxonomies' );
					if ( ( isset( $lessons_taxonomies['ld_lesson_category'] ) ) && ( $lessons_taxonomies['ld_lesson_category']['public'] == true ) ) {
						$this->settings_set['settings']['ld_lesson_category'] = array(
							'label'      => 'Lesson Category base',
							'label_html' => sprintf(
								// translators: placeholder: Lesson.
								esc_html_x( '%s Category base', 'placeholder: Lesson', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'lesson' )
							),
							'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_lesson_category'],
						);
					}

					if ( ( isset( $lessons_taxonomies['ld_lesson_tag'] ) ) && ( true == $lessons_taxonomies['ld_lesson_tag']['public'] ) ) {
						$this->settings_set['settings']['ld_lesson_tag'] = array(
							'label'      => 'Lessons Tag',
							'label_html' => sprintf(
								// translators: placeholder: Lesson.
								esc_html_x( '%s Tag base', 'placeholder: Lesson', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'lesson' )
							),
							'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_lesson_tag'],
						);
					}
				}

				if ( 'yes' == LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_Taxonomies', 'ld_topic_category' ) ) {
					$topics_taxonomies = $sfwd_lms->get_post_args_section( 'sfwd-topic', 'taxonomies' );
					if ( ( isset( $topics_taxonomies['ld_topic_category'] ) ) && ( true == $topics_taxonomies['ld_topic_category']['public'] ) ) {
						$this->settings_set['settings']['ld_topic_category'] = array(
							'label'      => 'Topics Category base',
							'label_html' => sprintf(
								// translators: placeholder: Topic.
								esc_html_x( '%s Category base', 'placeholder: Topic', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'topic' )
							),
							'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_topic_category'],
						);
					}

					if ( ( isset( $topics_taxonomies['ld_topic_tag'] ) ) && ( $topics_taxonomies['ld_topic_tag']['public'] == true ) ) {
						$this->settings_set['settings']['ld_topic_tag'] = array(
							'label'      => 'Topics Tag',
							'label_html' => sprintf(
								// translators: placeholder: Topic.
								esc_html_x( '%s Tag base', 'placeholder: Topic', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'topic' )
							),
							'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_topic_tag'],
						);
					}
				}

				// LD Assignment upload path.
				$upload_dir      = wp_upload_dir();
				$upload_dir_base = str_replace( '\\', '/', $upload_dir['basedir'] );
				$upload_url_base = $upload_dir['baseurl'];

				$assignment_upload_dir_path                              = $upload_dir_base . '/assignments';
				$assignment_upload_dir_path_r                            = str_replace( $ABSPATH_tmp, '', $assignment_upload_dir_path );
				$this->settings_set['settings']['Assignment Upload Dir'] = array(
					'label'      => 'Assignment Upload Dir',
					'label_html' => esc_html__( 'Assignment Upload Dir', 'learndash' ),
					'value'      => $assignment_upload_dir_path_r,
				);

				$color = 'green';

				if ( ! file_exists( $assignment_upload_dir_path ) ) {
					$color = 'red';
					$this->settings_set['settings']['Assignment Upload Dir']['value_html']  = '<span style="color: ' . $color . '">' . $assignment_upload_dir_path_r . '</span>';
					$this->settings_set['settings']['Assignment Upload Dir']['value_html'] .= ' - ' . esc_html__( 'Directory does not exists', 'learndash' );

					$this->settings_set['settings']['Assignment Upload Dir']['value'] .= ' - (X) Directory does not exists';

				} elseif ( ! is_writable( $assignment_upload_dir_path ) ) {
					$color = 'red';
					$this->settings_set['settings']['Assignment Upload Dir']['value_html']  = '<span style="color: ' . $color . '">' . $assignment_upload_dir_path_r . '</span>';
					$this->settings_set['settings']['Assignment Upload Dir']['value_html'] .= ' - ' . esc_html__( 'Directory not writable', 'learndash' );

					$this->settings_set['settings']['Assignment Upload Dir']['value'] .= ' - (X) Directory not writable';

				} else {
					$this->settings_set['settings']['Assignment Upload Dir']['value_html'] = '<span style="color: ' . $color . '">' . $assignment_upload_dir_path_r . '</span>';
				}

				$essay_upload_dir_path                              = $upload_dir_base . '/essays';
				$essay_upload_dir_path_r                            = str_replace( $ABSPATH_tmp, '', $essay_upload_dir_path );
				$this->settings_set['settings']['Essay Upload Dir'] = array(
					'label'      => 'Essay Upload Dir',
					'label_html' => esc_html__( 'Essay Upload Dir', 'learndash' ),
					'value'      => $essay_upload_dir_path_r,
				);

				$color = 'green';

				if ( ! file_exists( $essay_upload_dir_path ) ) {
					$color = 'red';
					$this->settings_set['settings']['Essay Upload Dir']['value_html']  = '<span style="color: ' . $color . '">' . $essay_upload_dir_path_r . '</span>';
					$this->settings_set['settings']['Essay Upload Dir']['value_html'] .= ' - ' . esc_html__( 'Directory does not exists', 'learndash' );

					$this->settings_set['settings']['Essay Upload Dir']['value'] .= ' - (X) Directory does not exists';

				} elseif ( ! is_writable( $essay_upload_dir_path ) ) {
					$color = 'red';
					$this->settings_set['settings']['Essay Upload Dir']['value_html']  = '<span style="color: ' . $color . '">' . $essay_upload_dir_path_r . '</span>';
					$this->settings_set['settings']['Essay Upload Dir']['value_html'] .= ' - ' . esc_html__( 'Directory not writable', 'learndash' );

					$this->settings_set['settings']['Essay Upload Dir']['value'] .= ' - (X) Directory not writable';

				} else {
					$this->settings_set['settings']['Essay Upload Dir']['value_html'] = '<span style="color: ' . $color . '">' . $essay_upload_dir_path_r . '</span>';
				}

				foreach ( apply_filters( 'learndash_support_ld_defines', array( 'LEARNDASH_LMS_PLUGIN_DIR', 'LEARNDASH_LMS_PLUGIN_URL', 'LEARNDASH_SCRIPT_DEBUG', 'LEARNDASH_SCRIPT_VERSION_TOKEN', 'LEARNDASH_GUTENBERG', 'LEARNDASH_ADMIN_CAPABILITY_CHECK', 'LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK', 'LEARNDASH_COURSE_BUILDER', 'LEARNDASH_QUIZ_BUILDER', 'LEARNDASH_LESSON_VIDEO', 'LEARNDASH_ADDONS_UPDATER', 'LEARNDASH_QUIZ_PREREQUISITE_ALT', 'LEARNDASH_LMS_DEFAULT_QUESTION_POINTS', 'LEARNDASH_LMS_DEFAULT_ANSWER_POINTS', 'LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE', 'LEARNDASH_REST_API_ENABLED', 'LEARNDASH_TRANSIENTS_DISABLED' ) ) as $defined_item ) {
					$defined_value = ( defined( $defined_item ) ) ? constant( $defined_item ) : '';
					if ( 'LEARNDASH_LMS_PLUGIN_DIR' == $defined_item ) {
						$defined_value = str_replace( $ABSPATH_tmp, '', $defined_value );
					}

					$this->settings_set['settings'][ $defined_item ] = array(
						'label'      => $defined_item,
						'label_html' => $defined_item,
						'value'      => $defined_value,
					);
				}

				$ld_translation_files = '';
				if ( ! empty( $this->mo_files ) ) {

					foreach ( $this->mo_files as $domain => $mo_files ) {
						$mo_files_output = '';
						foreach ( $mo_files as $mo_file ) {
							if ( file_exists( $mo_file ) ) {
								if ( ! empty( $mo_files_output ) ) {
									$mo_files_output .= ', ';
								}
								$mo_files_output .= str_replace( ABSPATH, '', $mo_file );
								$mo_files_output .= ' <em>' . learndash_adjust_date_time_display( filectime( $mo_file ) ) . '</em>';
							}
						}
						if ( ! empty( $mo_files_output ) ) {
							$ld_translation_files .= '<strong>' . $domain . '</strong> - ' . $mo_files_output . '<br />';
						}
					}
				}

				$this->settings_set['settings']['Translation Files'] = array(
					'label'      => 'Translation Files',
					'label_html' => esc_html__( 'Translation Files', 'learndash' ),
					'value'      => $ld_translation_files,
				);

				$support_sections[ $this->setting_option_key ] = apply_filters( 'learndash_support_section', $this->settings_set, $this->setting_option_key );
			}

			return $support_sections;
		}

		public function show_support_section( $settings_section_key = '', $settings_screen_id = '' ) {
			if ( $settings_section_key === $this->settings_section_key ) {
				$support_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash_Settings_Page_Support' );
				if ( $support_page_instance ) {
					$support_page_instance->show_support_section( $this->setting_option_key );
				}
			}
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Support_LearnDash::add_section_instance();
	}
);
