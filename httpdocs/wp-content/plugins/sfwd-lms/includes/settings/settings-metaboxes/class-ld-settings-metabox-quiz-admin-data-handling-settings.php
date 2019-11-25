<?php
/**
 * LearnDash Settings Metabox for Quiz Admin & Data Handling Settings.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Quiz_Admin_Data_Handling_Settings' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Quiz_Admin_Data_Handling_Settings extends LearnDash_Settings_Metabox {

		protected $quiz_edit = null;
		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-quiz-admin-data-handling-settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Administrative and Data Handling Settings', 'learndash' );

			$this->settings_section_description = esc_html__( 'Controls data handling options, notifications and templates.', 'learndash' );

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'associated_settings_enabled' => 'associated_settings_enabled',
				'toplistDataShowIn_enabled'   => 'toplistDataShowIn_enabled',
				'statisticsIpLock_enabled'    => 'statisticsIpLock_enabled',

				'associated_settings'         => 'quiz_pro',

				'formActivated'               => 'formActivated',
				'formShowPosition'            => 'formShowPosition',

				'toplistDataAddPermissions'   => 'toplistDataAddPermissions',
				'toplistDataAddMultiple'      => 'toplistDataAddMultiple',
				'toplistDataAddBlock'         => 'toplistDataAddBlock',
				'toplistDataAddAutomatic'     => 'toplistDataAddAutomatic',
				'toplistDataShowLimit'        => 'toplistDataShowLimit',
				'toplistDataSort'             => 'toplistDataSort',
				'toplistActivated'            => 'toplistActivated',
				'toplistDataShowIn'           => 'toplistDataShowIn',
				'toplistDataCaptcha'          => 'toplistDataCaptcha',

				'statisticsOn'                => 'statisticsOn',
				'viewProfileStatistics'       => 'viewProfileStatistics',
				'statisticsIpLock'            => 'statisticsIpLock',

				'email_enabled'               => 'email_enabled',
				'email_enabled_admin'         => 'email_enabled_admin',
				'emailNotification'           => 'emailNotification',
				'userEmailNotification'       => 'userEmailNotification',

				'timeLimitCookie_enabled'     => 'timeLimitCookie_enabled',
				'timeLimitCookie'             => 'timeLimitCookie',

				'templates_enabled'           => 'templates_enabled',
				//'templateLoadId'              => 'templateLoadId',
				//'templateSaveList'            => 'templateSaveList',
				//'templateName'                => 'templateName',
			);

			parent::__construct();
		}

		/**
		 * Used to save the settings fields back to the global $_POST object so
		 * the WPProQuiz normal form processing can take place.
		 *
		 * @since 3.0
		 * @param object $pro_quiz_edit WpProQuiz_Controller_Quiz instance (not used).
		 * @param array $settings_values Array of settings fields.
		 */
		public function save_fields_to_post( $pro_quiz_edit, $settings_values = array() ) {
			$_POST['formActivated']             = $settings_values['formActivated'];
			$_POST['formShowPosition']          = $settings_values['formShowPosition'];
			$_POST['toplistActivated']          = $settings_values['toplistActivated'];
			$_POST['toplistDataAddPermissions'] = $settings_values['toplistDataAddPermissions'];
			$_POST['toplistDataAddMultiple']    = $settings_values['toplistDataAddMultiple'];
			$_POST['toplistDataAddBlock']       = $settings_values['toplistDataAddBlock'];
			$_POST['toplistDataAddAutomatic']   = $settings_values['toplistDataAddAutomatic'];
			$_POST['toplistDataShowLimit']      = $settings_values['toplistDataShowLimit'];
			$_POST['toplistDataSort']           = $settings_values['toplistDataSort'];
			$_POST['toplistDataShowIn']         = $settings_values['toplistDataShowIn'];
			$_POST['toplistDataCaptcha']        = $settings_values['toplistDataCaptcha'];
			$_POST['statisticsOn']              = $settings_values['statisticsOn'];
			$_POST['viewProfileStatistics']     = $settings_values['viewProfileStatistics'];
			$_POST['statisticsIpLock']          = $settings_values['statisticsIpLock'];
			$_POST['emailNotification']         = $settings_values['emailNotification'];
			$_POST['userEmailNotification']     = $settings_values['userEmailNotification'];
			$_POST['timeLimitCookie']           = $settings_values['timeLimitCookie'];

//			$_POST['templateLoadId']            = $settings_values['templateLoadId'];
//			$_POST['templateSaveList']          = $settings_values['templateSaveList'];
//			$_POST['templateName']              = $settings_values['templateName'];
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			global $pagenow;

			parent::load_settings_values();

			$this->quiz_edit = $this->init_quiz_edit( $this->_post );

			if ( true === $this->settings_values_loaded ) {
				if ( $this->quiz_edit['quiz'] ) {
					$this->setting_option_values['formActivated'] = $this->quiz_edit['quiz']->isFormActivated();
					if ( true === $this->setting_option_values['formActivated'] ) {
						$this->setting_option_values['formActivated'] = 'on';
					} else {
						$this->setting_option_values['formActivated'] = '';
					}

					//$form_mapper                                        = new WpProQuiz_Model_FormMapper();
					//$this->setting_option_values['custom_fields_forms'] = $form_mapper->fetch( $this->quiz_edit['quiz']->getId() );

					if ( $this->quiz_edit['forms'] ) {
						$this->setting_option_values['custom_fields_forms'] = $this->quiz_edit['forms'];
					} else {
						$this->setting_option_values['custom_fields_forms'] = array();
					}

					if ( empty( $this->setting_option_values['custom_fields_forms'] ) ) {
						$this->setting_option_values['formActivated'] = '';
					} else {
						$this->setting_option_values['formActivated'] = 'on';
					}

					$this->setting_option_values['formShowPosition'] = $this->quiz_edit['quiz']->getFormShowPosition();
					if ( WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_START === $this->setting_option_values['formShowPosition'] ) {
						$this->setting_option_values['formShowPosition'] = WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_START;
					} else {
						$this->setting_option_values['formShowPosition'] = WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_END;
					}

					$this->setting_option_values['toplistActivated'] = $this->quiz_edit['quiz']->isToplistActivated();
					if ( true === $this->setting_option_values['toplistActivated'] ) {
						$this->setting_option_values['toplistActivated'] = 'on';
					} else {
						$this->setting_option_values['toplistActivated'] = '';
					}

					$this->setting_option_values['toplistDataAddPermissions'] = $this->quiz_edit['quiz']->getToplistDataAddPermissions();
					$this->setting_option_values['toplistDataAddMultiple']    = $this->quiz_edit['quiz']->isToplistDataAddMultiple();
					if ( true === $this->setting_option_values['toplistDataAddMultiple'] ) {
						$this->setting_option_values['toplistDataAddMultiple'] = 'on';
					}

					$this->setting_option_values['toplistDataAddBlock']     = absint( $this->quiz_edit['quiz']->getToplistDataAddBlock() );
					$this->setting_option_values['toplistDataAddAutomatic'] = $this->quiz_edit['quiz']->isToplistDataAddAutomatic();
					if ( true === $this->setting_option_values['toplistDataAddAutomatic'] ) {
						$this->setting_option_values['toplistDataAddAutomatic'] = 'on';
					} else {
						$this->setting_option_values['toplistDataAddAutomatic'] = '';
					}

					$this->setting_option_values['toplistDataShowLimit'] = $this->quiz_edit['quiz']->getToplistDataShowLimit();
					$this->setting_option_values['toplistDataSort']      = $this->quiz_edit['quiz']->getToplistDataSort();
					$this->setting_option_values['toplistDataShowIn']    = $this->quiz_edit['quiz']->getToplistDataShowIn();
					if ( absint( $this->setting_option_values['toplistDataShowIn'] ) > 0 ) {
						$this->setting_option_values['toplistDataShowIn_enabled'] = 'on';
					} else {
						$this->setting_option_values['toplistDataShowIn_enabled'] = '';
					}

					if ( class_exists( 'ReallySimpleCaptcha' ) ) {
						$this->setting_option_values['toplistDataCaptcha'] = $this->quiz_edit['quiz']->isToplistDataCaptcha();
						if ( true === $this->setting_option_values['toplistDataCaptcha'] ) {
							$this->setting_option_values['toplistDataCaptcha'] = 'on';
						} else {
							$this->setting_option_values['toplistDataCaptcha'] = '';
						}
					} else {
						$this->setting_option_values['toplistDataCaptcha'] = '';
					}

					if ( 'on' !== $this->setting_option_values['toplistActivated'] ) {
						$this->setting_option_values['toplistDataAddPermissions'] = '';
						$this->setting_option_values['toplistDataAddMultiple']    = '';
						$this->setting_option_values['toplistDataAddBlock']       = 0;
						$this->setting_option_values['toplistDataAddAutomatic']   = '';
						$this->setting_option_values['toplistDataShowLimit']      = '';
						$this->setting_option_values['toplistDataSort']           = '';
						$this->setting_option_values['toplistDataShowIn']         = '';
						$this->setting_option_values['toplistDataShowIn_enabled'] = '';
						$this->setting_option_values['toplistDataCaptcha']        = '';
					}

					$this->setting_option_values['statisticsOn'] = $this->quiz_edit['quiz']->isStatisticsOn();
					if ( true === $this->setting_option_values['statisticsOn'] ) {
						$this->setting_option_values['statisticsOn'] = 'on';
					} else {
						$this->setting_option_values['statisticsOn'] = '';
					}

					if ( isset( $this->quiz_edit['quiz_postmeta']['viewProfileStatistics'] ) ) {
						$this->setting_option_values['viewProfileStatistics'] = $this->quiz_edit['quiz_postmeta']['viewProfileStatistics'];
					} else {
						if ( 'post-new.php' === $pagenow ) {
							$this->setting_option_values['viewProfileStatistics'] = true;
						} else {
							$this->setting_option_values['viewProfileStatistics'] = $this->quiz_edit['quiz']->getViewProfileStatistics();
						}
					}
					if ( true === $this->setting_option_values['viewProfileStatistics'] ) {
						$this->setting_option_values['viewProfileStatistics'] = 'on';
					} else {
						$this->setting_option_values['viewProfileStatistics'] = '';
					}

					$this->setting_option_values['statisticsIpLock'] = $this->quiz_edit['quiz']->getStatisticsIpLock();
					if ( ! empty( $this->setting_option_values['statisticsIpLock'] ) ) {
						$this->setting_option_values['statisticsIpLock']         = round( $this->setting_option_values['statisticsIpLock'] / 60 );
						$this->setting_option_values['statisticsIpLock_enabled'] = 'on';
					} else {
						$this->setting_option_values['statisticsIpLock_enabled'] = '';
					}

					$this->setting_option_values['emailNotification'] = $this->quiz_edit['quiz']->getEmailNotification();
					if ( ( $this->setting_option_values['emailNotification'] == WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_ALL ) || ( $this->setting_option_values['emailNotification'] == WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_REG_USER ) ) {
						$this->setting_option_values['email_enabled_admin'] = 'on';
					} else {
						$this->setting_option_values['email_enabled_admin'] = '';
					}

					$this->setting_option_values['userEmailNotification'] = $this->quiz_edit['quiz']->isUserEmailNotification();
					if ( true === $this->setting_option_values['userEmailNotification'] ) {
						$this->setting_option_values['userEmailNotification'] = 'on';
					} else {
						$this->setting_option_values['userEmailNotification'] = '';
					}

					if ( ( 'on' !== $this->setting_option_values['userEmailNotification'] ) && ( 'on' !== $this->setting_option_values['email_enabled_admin'] ) ) {
						$this->setting_option_values['email_enabled'] = '';
					} else {
						$this->setting_option_values['email_enabled'] = 'on';
					}

					if ( isset( $this->quiz_edit['quiz_postmeta']['timeLimitCookie'] ) ) {
						$this->setting_option_values['timeLimitCookie'] = $this->quiz_edit['quiz_postmeta']['timeLimitCookie'];
					} else {
						$this->setting_option_values['timeLimitCookie'] = $this->quiz_edit['quiz']->getTimeLimitCookie();
					}
					if ( ! empty( $this->setting_option_values['timeLimitCookie'] ) ) {
						$this->setting_option_values['timeLimitCookie_enabled'] = 'on';
					} else {
						$this->setting_option_values['timeLimitCookie_enabled'] = '';
					}

					$this->setting_option_values['associated_settings'] = learndash_get_setting( get_the_ID(), 'quiz_pro' );

					if ( ! isset( $this->setting_option_values['advanced_settings'] ) ) {
						$this->setting_option_values['advanced_settings'] = '';
					}

					if ( 'on' === $this->setting_option_values['timeLimitCookie_enabled'] ) {
						$this->setting_option_values['advanced_settings'] = 'on';
					}
				}
			}

			$this->setting_option_values['templateLoadId'] = '';
			if ( ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) {
				$this->setting_option_values['templateLoadId'] = absint( $_GET['templateLoadId'] );
			}
			if ( ! empty( $this->setting_option_values['templateLoadId'] ) ) {
				$this->setting_option_values['templates_enabled'] = 'on';
			} else {
				$this->setting_option_values['templates_enabled'] = '';
			}

			foreach ( $this->settings_fields_map as $_internal => $_external ) {
				if ( ! isset( $this->setting_option_values[ $_internal ] ) ) {
					$this->setting_option_values[ $_internal ] = '';
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$pro_quiz_options         = LD_QuizPro::get_quiz_list();
			$pro_quiz_options_default = array(
				'-1' => esc_html__( 'Select a ProQuiz association', 'learndash' ),
				'new' => esc_html__( 'New ProQuiz association', 'learndash' ),
			);
			$pro_quiz_options         = $pro_quiz_options_default + $pro_quiz_options;

			$this->setting_option_fields = array(
				'toplistDataAddBlock' => array(
					'name'        => 'toplistDataAddBlock',
					'label'       => esc_html__( 'Re-apply after', 'learndash' ),
					'label_full'  => true,
					'input_full'  => true,
					'type'        => 'number',
					'value'       => $this->setting_option_values['toplistDataAddBlock'],
					'input_label' => esc_html__( 'minutes', 'learndash' ),
					'default'     => '1',
					'attrs'       => array(
						'step' => 1,
						'min'  => 0,
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['leaderboard_add_multiple_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'toplistDataShowIn' => array(
					'name'       => 'toplistDataShowIn',
					'label_none' => true,
					'type'       => 'radio',
					'value'      => $this->setting_option_values['toplistDataShowIn'],
					'default'    => '1',
					'options'    => array(
						'1' => esc_html__( 'Below the result text', 'learndash' ),
						'2' => esc_html__( 'In a button', 'learndash' ),
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['leaderboard_result_display_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'statisticsIpLock' => array(
					'name'              => 'statisticsIpLock',
					'label_full'        => true,
					'label'             => esc_html__( 'IP-lock time limit', 'learndash' ),
					'label_description' => esc_html__( 'Protect the statistics from spam. Results will only be saved every X minutes.', 'learndash' ),
					'input_full'        => true,
					'type'              => 'number',
					'class'             => '-small',
					'value'             => $this->setting_option_values['statisticsIpLock'],
					'input_label'       => esc_html__( 'minutes', 'learndash' ),
					'default'           => '0',
					'attrs'       => array(
						'step' => 1,
						'min'  => 0,
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['quiz_statistics_ip_lock_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'emailNotification' => array(
					'name'              => 'emailNotification',
					'label'             => esc_html__( 'Email trigger', 'learndash' ),
					'label_full'        => true,
					'label_description' => sprintf(
						// translators: placeholder: quiz.
						esc_html_x( 'The admin will receive an email notification when the following users have taken the %s.', 'placeholder: quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' )
					),
					'type'              => 'select',
					'value'             => $this->setting_option_values['emailNotification'],
					'input_full'        => true,
					'default'           => WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_REG_USER,
					'options'           => array(
						WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_ALL => esc_html__( 'All users', 'learndash' ),
						WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_REG_USER => esc_html__( 'Registered users only', 'learndash' ),
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['emailNotification_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'timeLimitCookie' => array(
					'name'              => 'timeLimitCookie',
					'label_full'        => true,
					'label'             => esc_html__( 'Cookie time limit', 'learndash' ),
					'label_description' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Save the user’s answers into a browser cookie until the %s is submitted', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'input_full'        => true,
					'type'              => 'number',
					'class'             => '-small',
					'value'             => $this->setting_option_values['timeLimitCookie'],
					'input_label'       => esc_html__( 'seconds', 'learndash' ),
					'default'           => '',
					'attrs'       => array(
						'step' => 1,
						'min'  => 0,
					),

				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['timeLimitCookie_enabled_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'associated_settings' => array(
					'name'              => 'associated_settings',
					'type'              => 'select',
					'label_full'        => true,
					'label'             => esc_html__( 'Associated Quiz Database Table', 'learndash' ),
					'label_description' => wp_kses_post( 'This will change the database association.<br /><strong>We do not recommend editing this</strong> unless for a specific purpose.', 'learndash' ),
					'input_full'        => true,
					'value'             => $this->setting_option_values['associated_settings'],
					'parent_setting'    => 'associated_settings_enabled',
					'default'           => '',
					'options'           => $pro_quiz_options,
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['associated_settings_enabled_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(

				'formActivated'               => array(
					'name'                => 'formActivated',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Custom Fields', 'learndash' ),
					'value'               => $this->setting_option_values['formActivated'],
					'default'             => '',
					'help_text'           => sprintf(
						// translators: placeholder: quiz, Quiz
						esc_html_x( 'Enable this option to gather data from your users before or after the %1$s. All data is stored in the %2$s Statistics.', 'placeholder: quiz, Quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label( 'quiz' )
					),
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['formActivated'] ) ? 'open' : 'closed',
				),

				'custom_fields_forms'         => array(
					'name'           => 'custom_fields_forms',
					'type'           => 'quiz-custom-fields',
					'label_none'     => true,
					'input_full'     => true,
					'value'          => $this->setting_option_values['custom_fields_forms'],
					'parent_setting' => 'formActivated',
				),
				'formShowPosition'            => array(
					'name'           => 'formShowPosition',
					'type'           => 'radio',
					'label'          => esc_html__( 'Display Position', 'learndash' ),
					'value'          => $this->setting_option_values['formShowPosition'],
					'parent_setting' => 'formActivated',
					'default'        => WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_START,
					'options'        => array(
						WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_START => sprintf(
							// translators: placeholder: quiz.
							esc_html_x( 'On the %s startpage', 'placeholder: quiz.', 'learndash' ),
							learndash_get_custom_label_lower( 'quiz' )
						),
						WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_END => sprintf(
							// translators: placeholder: quiz, quiz.
							esc_html_x( 'At the end of the %1$s (before the %2$s result)', 'placeholder: quiz, quiz', 'learndash' ),
							learndash_get_custom_label_lower( 'quiz' ),
							learndash_get_custom_label_lower( 'quiz' )
						),
					),
				),

				'toplistActivated'            => array(
					'name'                => 'toplistActivated',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Leaderboard', 'learndash' ),
					'value'               => $this->setting_option_values['toplistActivated'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['toplistActivated'] ) ? 'open' : 'closed',
				),

				'toplistDataAddPermissions'   => array(
					'name'           => 'toplistDataAddPermissions',
					'type'           => 'select',
					'label'          => esc_html__( 'Who can apply?', 'learndash' ),
					'value'          => $this->setting_option_values['toplistDataAddPermissions'],
					'options'        => array(
						'1' => esc_html__( 'All user', 'learndash' ),
						'2' => esc_html__( 'Registered users only', 'learndash' ),
						'3' => esc_html__( 'Anonymous users only', 'learndash' ),
					),
					'parent_setting' => 'toplistActivated',
				),

				'toplistDataAddMultiple'      => array(
					'name'                => 'toplistDataAddMultiple',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Multiple Applications per user', 'learndash' ),
					'value'               => $this->setting_option_values['toplistDataAddMultiple'],
					'parent_setting'      => 'toplistActivated',
					'default'             => '',
					'options'             => array(
						''   => '',
						'on' => esc_html__( 'Users can apply more than once to the leaderboard', 'learndash' ),
					),
					'inline_fields'       => array(
						'leaderboard_add_multiple_min' => $this->settings_sub_option_fields['leaderboard_add_multiple_fields'],
					),
					'inner_section_state' => ( 'on' === $this->setting_option_values['toplistDataAddMultiple'] ) ? 'open' : 'closed',
				),

				'toplistDataAddAutomatic'     => array(
					'name'           => 'toplistDataAddAutomatic',
					'type'           => 'checkbox',
					'label'          => esc_html__( 'Automatic user entry', 'learndash' ),
					'value'          => $this->setting_option_values['toplistDataAddAutomatic'],
					'parent_setting' => 'toplistActivated',
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
				),

				'toplistDataShowLimit'        => array(
					'name'           => 'toplistDataShowLimit',
					'label'          => esc_html__( 'Number of displayed entries', 'learndash' ),
					'type'           => 'number',
					'class'          => '-small',
					'parent_setting' => 'toplistActivated',
					'value'          => $this->setting_option_values['toplistDataShowLimit'],
					'default'        => '10',
					'attrs'          => array(
						'step' => 1,
						'min'  => 0,
					),
				),

				'toplistDataSort'             => array(
					'name'           => 'toplistDataSort',
					'type'           => 'select',
					'label'          => esc_html__( 'Sort list by?', 'learndash' ),
					'value'          => $this->setting_option_values['toplistDataSort'],
					'parent_setting' => 'toplistActivated',
					'default'        => '1',
					'options'        => array(
						'1' => esc_html__( 'Best user', 'learndash' ),
						'2' => esc_html__( 'Newest entry', 'learndash' ),
						'3' => esc_html__( 'Oldest entry', 'learndash' ),
					),
				),
				'toplistDataShowIn_enabled'   => array(
					'name'                => 'toplistDataShowIn_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Display on %s results page', 'placeholder: Quiz.', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'value'               => $this->setting_option_values['toplistDataShowIn_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'parent_setting'      => 'toplistActivated',
					'inline_fields'       => array(
						'leaderboard_result_display_fields' => $this->settings_sub_option_fields['leaderboard_result_display_fields'],
					),
					'inner_section_state' => ( 'on' === $this->setting_option_values['toplistDataShowIn_enabled'] ) ? 'open' : 'closed',
				),

				'toplistDataCaptcha'          => array(
					'name'           => 'toplistDataCaptcha',
					'type'           => 'checkbox',
					'label'          => esc_html__( 'Really Simple CAPTCHA', 'learndash' ),
					'help_text'      => sprintf(
						// translators: placeholder: links to Real Simple CAPTCHA.
						esc_html_x( 'This option requires additional plugin: %s', 'placeholder: links to Real Simple CAPTCHA', 'learndash' ),
						'<br /><a href="http://wordpress.org/extend/plugins/really-simple-captcha/" target="_blank">Really Simple CAPTCHA</a>'
					),
					'value'          => $this->setting_option_values['toplistDataCaptcha'],
					'parent_setting' => 'toplistActivated',
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
				),

				'statisticsOn'                => array(
					'name'                => 'statisticsOn',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Statistics', 'placeholder: Quiz.', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'value'               => $this->setting_option_values['statisticsOn'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['statisticsOn'] ) ? 'open' : 'closed',
				),
				'viewProfileStatistics'       => array(
					'name'           => 'viewProfileStatistics',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Front-end Profile Display', 'learndash' ),
					'value'          => $this->setting_option_values['viewProfileStatistics'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'statisticsOn',
				),
				'statisticsIpLock_enabled'    => array(
					'name'                => 'statisticsIpLock_enabled',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Statistics IP-lock', 'learndash' ),
					'value'               => $this->setting_option_values['statisticsIpLock_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'parent_setting'      => 'statisticsOn',
					'inline_fields'       => array(
						'quiz_statistics_ip_lock_fields' => $this->settings_sub_option_fields['quiz_statistics_ip_lock_fields'],
					),
					'inner_section_state' => ( 'on' === $this->setting_option_values['statisticsIpLock_enabled'] ) ? 'open' : 'closed',
				),

				'email_enabled'               => array(
					'name'                => 'email_enabled',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Email Notifications', 'learndash' ),
					'value'               => $this->setting_option_values['email_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['email_enabled'] ) ? 'open' : 'closed',
				),

				'email_enabled_admin'         => array(
					'name'                => 'email_enabled_admin',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Admin', 'learndash' ),
					'value'               => $this->setting_option_values['email_enabled_admin'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'inline_fields'       => array(
						'emailNotification_fields' => $this->settings_sub_option_fields['emailNotification_fields'],
					),
					'inner_section_state' => ( 'on' === $this->setting_option_values['email_enabled_admin'] ) ? 'open' : 'closed',
					'parent_setting'      => 'email_enabled',
				),
				'userEmailNotification'       => array(
					'name'           => 'userEmailNotification',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'User', 'learndash' ),
					'value'          => $this->setting_option_values['userEmailNotification'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'email_enabled',
				),

				'templates_enabled'           => array(
					'name'                => 'templates_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Quiz
						esc_html_x( '%s Templates', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'value'               => $this->setting_option_values['templates_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['templates_enabled'] ) ? 'open' : 'closed',
				),
				'templateLoadId'              => array(
					'name'           => 'templateLoadId',
					'type'           => 'quiz-templates-load',
					'label'          => esc_html__( 'Use Template', 'learndash' ),
					'value'          => $this->setting_option_values['templateLoadId'],
					'default'        => '',
					'template_type'  => WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ,
					'parent_setting' => 'templates_enabled',
				),
				'templateSaveList'              => array(
					'name'           => 'templateSaveList',
					'type'           => 'quiz-templates-save',
					'label'          => esc_html__( 'Save as Template', 'learndash' ),
					//'value'          => $this->setting_option_values['templates_load'],
					//'default'        => '',
					'template_type'  => WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ,
					'parent_setting' => 'templates_enabled',
				),

				'advanced_settings'           => array(
					'name'                => 'advanced_settings',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Advanced Settings', 'learndash' ),
					'value'               => $this->setting_option_values['advanced_settings'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['advanced_settings'] ) ? 'open' : 'closed',
				),
				'timeLimitCookie_enabled'     => array(
					'name'                => 'timeLimitCookie_enabled',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Browser Cookie Answer Protection', 'learndash' ),
					'value'               => $this->setting_option_values['timeLimitCookie_enabled'],
					'help_text'           => sprintf(
						// translators: placeholder: quizzes
						esc_html_x( 'Browser cookies have limited memory. This may not work with large %s.', 'placeholder: quizzes', 'learndash' ),
						learndash_get_custom_label_lower( 'quizzes' )
					),
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'inline_fields'       => array(
						'timeLimitCookie_enabled_fields' => $this->settings_sub_option_fields['timeLimitCookie_enabled_fields'],
					),
					'inner_section_state' => ( 'on' === $this->setting_option_values['timeLimitCookie_enabled'] ) ? 'open' : 'closed',
					'parent_setting'      => 'advanced_settings',
				),

				'associated_settings_enabled' => array(
					'name'                => 'associated_settings_enabled',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Associated Settings', 'learndash' ),
					'value'               => '',
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'inline_fields'       => array(
						'associated_settings_enabled_fields' => $this->settings_sub_option_fields['associated_settings_enabled_fields'],
					),
					'inner_section_state' => 'closed',
					'parent_setting'      => 'advanced_settings',
				),

			);

			// If the Real Simple CAPTCHA is not installed thenclear and disable the checkbox.
			if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
				if ( isset( $this->setting_option_fields['toplistDataCaptcha'] ) ) {
					$this->setting_option_fields['toplistDataCaptcha']['value'] = '';
					$this->setting_option_fields['toplistDataCaptcha']['attrs'] = array(
						'disabled' => 'disabled',
					);
				}
			}

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Filter settings values for metabox before save to database.
		 *
		 * @param array $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( $settings_metabox_key === $this->settings_metabox_key ) ) {

				if ( ( isset( $settings_values['formActivated'] ) ) && ( 'on' === $settings_values['formActivated'] ) ) {
					$settings_values['formActivated'] = true;
				} else {
					$settings_values['formActivated'] = false;
				}

				if ( ( isset( $settings_values['toplistActivated'] ) ) && ( 'on' === $settings_values['toplistActivated'] ) ) {
					$settings_values['toplistActivated'] = true;
				} else {
					$settings_values['toplistActivated'] = false;
				}

				if ( ( isset( $settings_values['toplistDataAddMultiple'] ) ) && ( 'on' === $settings_values['toplistDataAddMultiple'] ) ) {
					$settings_values['toplistDataAddMultiple'] = true;
				} else {
					$settings_values['toplistDataAddMultiple'] = false;
				}

				if ( ( isset( $settings_values['toplistDataAddAutomatic'] ) ) && ( 'on' === $settings_values['toplistDataAddAutomatic'] ) ) {
					$settings_values['toplistDataAddAutomatic'] = true;
				} else {
					$settings_values['toplistDataAddAutomatic'] = false;
				}

				if ( ( isset( $settings_values['toplistDataShowIn_enabled'] ) ) && ( 'on' === $settings_values['toplistDataShowIn_enabled'] ) ) {
					if ( isset( $settings_values['toplistDataShowIn'] ) ) {
						$settings_values['toplistDataShowIn'] = absint( $settings_values['toplistDataShowIn'] );
						if ( empty( $settings_values['toplistDataShowIn'] ) ) {
							$settings_values['toplistDataShowIn'] = 0;
						}
					} else {
						$settings_values['toplistDataShowIn'] = 0;
					}
				} else {
					$settings_values['toplistDataShowIn'] = 0;
				}

				if ( ( isset( $settings_values['toplistDataCaptcha'] ) ) && ( 'on' === $settings_values['toplistDataCaptcha'] ) ) {
					$settings_values['toplistDataCaptcha'] = true;
				} else {
					$settings_values['toplistDataCaptcha'] = false;
				}

				if ( true !== $settings_values['toplistActivated'] ) {
					$settings_values['toplistDataAddMultiple'] = false;
					$settings_values['toplistDataAddAutomatic'] = false;
					$settings_values['toplistDataShowIn'] = 0;
					$settings_values['toplistDataCaptcha'] = false;
				}

				if ( ( isset( $settings_values['statisticsOn'] ) ) && ( 'on' === $settings_values['statisticsOn'] ) ) {
					$settings_values['statisticsOn'] = true;
				} else {
					$settings_values['statisticsOn'] = false;
				}

				if ( ( isset( $settings_values['viewProfileStatistics'] ) ) && ( 'on' === $settings_values['viewProfileStatistics'] ) ) {
					$settings_values['viewProfileStatistics'] = true;
				} else {
					$settings_values['viewProfileStatistics'] = false;
				}

				if ( ( isset( $settings_values['statisticsIpLock_enabled'] ) ) && ( 'on' === $settings_values['statisticsIpLock_enabled'] ) ) {
					if ( isset( $settings_values['statisticsIpLock'] ) ) {
						$settings_values['statisticsIpLock'] = absint( $settings_values['statisticsIpLock'] ) * 60;
						if ( empty( $settings_values['statisticsIpLock'] ) ) {
							$settings_values['statisticsIpLock'] = 0;
						}
					} else {
						$settings_values['statisticsIpLock'] = 0;
					}
				} else {
					$this->setting_option_values['statisticsIpLock'] = 0;
				}

				// Main Email notification switch.
				if ( ( isset( $settings_values['email_enabled'] ) ) && ( 'on' === $settings_values['email_enabled'] ) ) {
					if ( ( isset( $settings_values['email_enabled_admin'] ) ) && ( 'on' === $settings_values['email_enabled_admin'] ) ) {

						if ( ( isset( $settings_values['emailNotification'] ) ) && ( ( $settings_values['emailNotification'] == WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_ALL ) || ( $settings_values['emailNotification'] == WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_REG_USER ) ) ) {
							$settings_values['emailNotification'] = $settings_values['emailNotification'];
						} else {
							$settings_values['emailNotification'] = WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_NONE;
						}
					} else {
						$settings_values['emailNotification'] = WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_NONE;
					}

					if ( ( isset( $settings_values['userEmailNotification'] ) ) && ( 'on' === $settings_values['userEmailNotification'] ) ) {
						$settings_values['userEmailNotification'] = true;
					} else {
						$settings_values['userEmailNotification'] = false;
					}
				} else {
					$settings_values['emailNotification']     = WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_NONE;
					$settings_values['userEmailNotification'] = false;
				}

				if ( ( isset( $settings_values['templates_enabled'] ) ) && ( 'on' === $settings_values['templates_enabled'] ) ) {

					if ( '-1' === $_POST['templateSaveList'] ) {
						$_POST['templateSaveList'] = '';
					}

					if ( "0" === $_POST['templateSaveList'] ) {
						if ( empty( $_POST['templateName'] ) ) {
							$_POST['templateSaveList'] = '';
						}
					} else if ( '' !== $_POST['templateSaveList'] ) {
						$_POST['templateName'] = '';	
					}
				} else {
					$_POST['templateSaveList'] = '';
					$_POST['templateName'] = '';
				}

				if ( ( isset( $settings_values['timeLimitCookie_enabled'] ) ) && ( 'on' === $settings_values['timeLimitCookie_enabled'] ) ) {
					if ( ( isset( $settings_values['timeLimitCookie'] ) ) && ( ! empty( $settings_values['timeLimitCookie'] ) ) ) {
						$timeLimit_cookie = absint( $settings_values['timeLimitCookie'] );
						if ( ! empty( $timeLimit_cookie ) ) {
							$settings_values['timeLimitCookie'] = $timeLimit_cookie;
						} else {
							$settings_values['timeLimitCookie'] = '';
						}
					}
				} else {
					$settings_values['timeLimitCookie'] = '';
				}

				if ( '-1' === $settings_values['quiz_pro'] ) {
					$settings_values['quiz_pro'] = '';
				}

				/**
				 * We set the value from the settings if empty to prevent the ProQuiz logic from
				 * assigning a new pro_quiz ID.
				 */
				if ( empty( $settings_values['quiz_pro'] ) ) {
					$settings_values['quiz_pro'] = learndash_get_setting( get_the_ID(), 'quiz_pro' );
				}

				if ( 'new' === $settings_values['quiz_pro'] ) {
					$settings_values['quiz_pro'] = '';
				}
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'quiz' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Quiz_Admin_Data_Handling_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Quiz_Admin_Data_Handling_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Quiz_Admin_Data_Handling_Settings'] = LearnDash_Settings_Metabox_Quiz_Admin_Data_Handling_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
