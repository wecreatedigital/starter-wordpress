<?php
/**
 * LearnDash Settings Metabox for Quiz Progess Settings.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Quiz_Progress_Settings' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Quiz_Progress_Settings extends LearnDash_Settings_Metabox {

		protected $quiz_edit = null;
		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-quiz-progress-settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Progression and Restriction Settings', 'learndash' );

			$this->settings_section_description = sprintf(
				// translators: placeholder: quiz.
				esc_html_x( 'Controls the requirement for accessing and completing the %s', 'placeholder: quiz', 'learndash' ),
				learndash_get_custom_label_lower( 'quiz' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'retry_restrictions'       => 'retry_restrictions',
				'repeats'                  => 'repeats',
				'quizRunOnce'              => 'quizRunOnce',
				'quizRunOnceType'          => 'quizRunOnceType',
				'quizRunOnceCookie'        => 'quizRunOnceCookie',

				'passingpercentage'        => 'passingpercentage',

				'certificate'              => 'certificate',
				'threshold'                => 'threshold',

				'quiz_time_limit_enabled'  => 'quiz_time_limit_enabled',
				'timeLimit'                => 'timeLimit',
				'forcingQuestionSolve'     => 'forcingQuestionSolve',
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
			$_POST['quizRunOnce']       = $settings_values['quizRunOnce'];
			$_POST['quizRunOnceType']   = $settings_values['quizRunOnceType'];
			$_POST['quizRunOnceCookie'] = $settings_values['quizRunOnceCookie'];

			$_POST['forcingQuestionSolve'] = $settings_values['forcingQuestionSolve'];
			$_POST['timeLimit']            = $settings_values['timeLimit'];
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$this->quiz_edit = $this->init_quiz_edit( $this->_post );
			
			if ( true === $this->settings_values_loaded ) {

				if ( ( isset( $this->setting_option_values['passingpercentage'] ) ) && ( '' !== $this->setting_option_values['passingpercentage'] ) ) {
					$this->setting_option_values['passingpercentage'] = floatval( $this->setting_option_values['passingpercentage'] );
				} else {
					$this->setting_option_values['passingpercentage'] = '80';
				}
				if ( ( isset( $this->setting_option_values['threshold'] ) ) && ( '' !== $this->setting_option_values['threshold'] ) ) {
					$this->setting_option_values['threshold'] = floatval( $this->setting_option_values['threshold'] ) * 100;
				} else {
					$this->setting_option_values['threshold'] = '80';
				}

				if ( $this->quiz_edit['quiz'] ) {
					$this->setting_option_values['timeLimit'] = $this->quiz_edit['quiz']->getTimeLimit();

					$this->setting_option_values['forcingQuestionSolve'] = $this->quiz_edit['quiz']->isForcingQuestionSolve();
					if ( true === $this->setting_option_values['forcingQuestionSolve'] ) {
						$this->setting_option_values['forcingQuestionSolve'] = 'on';
					}

					$this->setting_option_values['quizRunOnceType']   = '';
					$this->setting_option_values['quizRunOnceCookie'] = '';

					if ( ( isset( $this->setting_option_values['repeats'] ) ) && ( '' !== $this->setting_option_values['repeats'] ) ) {
						$this->setting_option_values['quizRunOnceType']   = $this->quiz_edit['quiz']->getQuizRunOnceType();
						$this->setting_option_values['quizRunOnceCookie'] = $this->quiz_edit['quiz']->isQuizRunOnceCookie();
					} else {
						$this->setting_option_values['repeats'] = '';
						if ( $this->quiz_edit['quiz']->isQuizRunOnce() ) {
							$this->setting_option_values['repeats'] = '0';
							$this->setting_option_values['quizRunOnceType']   = $this->quiz_edit['quiz']->getQuizRunOnceType();
							$this->setting_option_values['quizRunOnceCookie'] = $this->quiz_edit['quiz']->isQuizRunOnceCookie();
						}
					}

					if ( true === $this->setting_option_values['quizRunOnceCookie'] ) {
						$this->setting_option_values['quizRunOnceCookie'] = 'on';
					}

					if ( ( isset( $this->setting_option_values['repeats'] ) ) && ( '' !== $this->setting_option_values['repeats'] ) ) {
						$this->setting_option_values['retry_restrictions'] = 'on';
					} else {
						$this->setting_option_values['retry_restrictions'] = '';
						$this->setting_option_values['repeats']            = '0';
						$this->setting_option_values['quizRunOnce']        = false;
						$this->setting_option_values['quizRunOnceType']    = '';
						$this->setting_option_values['quizRunOnceCookie']  = '';
					}

					if ( ! isset( $this->setting_option_values['quiz_time_limit_enabled'] ) ) {
						$this->setting_option_values['quiz_time_limit_enabled'] = '';
						if ( ( isset( $this->setting_option_values['timeLimit'] ) ) && ( ! empty( $this->setting_option_values['timeLimit'] ) ) ) {
							$this->setting_option_values['quiz_time_limit_enabled'] = 'on';
						}
					}
				}
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

			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
				$select_cert_options_default = array(
					'-1' => esc_html__( 'Search or select a certificate…', 'learndash' ),
				);
			} else {
				$select_cert_options_default = array(
					'' => esc_html__( 'Select Certificate', 'learndash' ),
				);
			}
			$select_cert_options = $sfwd_lms->select_a_certificate();
			if ( ( is_array( $select_cert_options ) ) && ( ! empty( $select_cert_options ) ) ) {
				$select_cert_options = $select_cert_options_default + $select_cert_options;
			} else {
				$select_cert_options = $select_cert_options_default;
			}
/*
			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
				$select_quiz_options_default = array(
					'' => sprintf(
						// translators: placeholder: quiz.
						esc_html_x( 'Search or select a %s…', 'placeholder: quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' )
					),
				);
			} else {
				$select_quiz_options_default = array(
					'' => sprintf(
						// translators: placeholder: quiz.
						esc_html_x( 'Select a %s…', 'placeholder: quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' )
					),
				);
			}
			$select_quiz_options = $sfwd_lms->select_a_quiz();
			if ( ( is_array( $select_quiz_options ) ) && ( ! empty( $select_quiz_options ) ) ) {
				$select_quiz_options = $select_quiz_options_default + $select_quiz_options;
			} else {
				$select_quiz_options = $select_quiz_options_default;
			}
*/
			/*
			$this->setting_option_fields = array(
                'quizRunOnceType' => array(
					'name' => 'quizRunOnceType',
					'label_none' => true,
                    'type' => 'select',
					'default' => '1',
					'value' => $this->setting_option_values['quizRunOnceType'],
					'input_label' => esc_html__( 'users', 'learndash' ),
					'options' => array(
						'1' => esc_html__( 'All users', 'learndash' ),
						'2' => esc_html__( 'Registered users only', 'learndash' ),
						'3' => esc_html__( 'Anonymous user only', 'learndash' ),
					),
				),
				'quizRunOnceCookie' => array(
					'name' => 'quizRunOnceCookie',
					'label_none' => true,
					'type' => 'checkbox-switch',
					'options' => array(
						'on' => esc_html__( 'Use a cookie to restrict ALL users, including anonymous visitors', 'learndash' ),
					),
					'value' => $this->setting_option_values['quizRunOnceCookie'],
					'default' => '',
				),
				'quiz_reset_cookies' => array(
					'name' => 'quiz_reset_cookies',
					'type' => 'custom',
					'html' => '<div style="margin-top: 15px;"><input class="button-secondary" type="button" name="resetQuizLock" value="'. esc_html__('Reset the user identification', 'learndash') .'"><span id="resetLockMsg" style="display:none; background-color: rgb(255, 255, 173); border: 1px solid rgb(143, 143, 143); padding: 4px; margin-left: 5px; ">'. esc_html__('User identification has been reset.', 'learndash') .'</span><p class="description"></p></div>',
					'label_none' => true,
					'input_full' => true,
				)
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['retry_restrictions_options_once_fields'] = $this->setting_option_fields;
			*/

			$this->setting_option_fields = array(
				'passingpercentage'       => array(
					'name'        => 'passingpercentage',
					'label'       => esc_html__( 'Passing Score', 'learndash' ),
					'type'        => 'number',
					'value'       => $this->setting_option_values['passingpercentage'],
					'default'     => '80',
					'placeholder' => 'e.g. 80',
					'class'       => '-small',
					'input_label' => '%',
					'attrs'       => array(
						'min'  => '0',
						'max'  => '100',
						//'step' => '0.01',
					),
				),
				'certificate'             => array(
					'name'                => 'certificate',
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( ' %s Certificate', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'type'                => 'select',
					'value'               => $this->setting_option_values['certificate'],
					'options'             => $select_cert_options,
					'child_section_state' => ( ( ! empty( $this->setting_option_values['certificate'] ) ) && ( '-1' !== $this->setting_option_values['certificate'] ) ) ? 'open' : 'closed',
				),
				'threshold'               => array(
					'name'           => 'threshold',
					'label'          => esc_html__( 'Certificate Awarded for', 'learndash' ),
					'type'           => 'number',
					'default'        => '80',
					'placeholder'    => 'e.g. 80',
					'class'          => '-small',
					'help_text'      => esc_html__( 'Set the score needed to receive a certificate. This can be different from the "Passing Score".', 'learndash' ),
					'input_label'    => esc_html__( '% score', 'learndash' ),
					'attrs'          => array(
						'min'  => '0',
						'max'  => '100',
						//'step' => '0.01',
					),
					'value'          => $this->setting_option_values['threshold'],
					'class'          => '-small',
					'parent_setting' => 'certificate',
				),

				'retry_restrictions'      => array(
					'name'                => 'retry_restrictions',
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Restrict %s Retakes', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'on' => '',
					),
					'value'               => $this->setting_option_values['retry_restrictions'],
					'default'             => '',
					'child_section_state' => ( 'on' === $this->setting_option_values['retry_restrictions'] ) ? 'open' : 'closed',
				),
				'repeats'                 => array(
					'name'              => 'repeats',
					'label'             => esc_html__( 'Number of Retries Allowed', 'learndash' ),
					'help_text'         => esc_html__( 'You must input a whole number value or leave blank to default to 0.', 'learndash' ),
					'type'              => 'number',
					'class'             => '-small',
					'default'           => '',
					'value'             => $this->setting_option_values['repeats'],
					//'value_allow_blank' => true,
					//'value_abs'         => true,
					'attrs'       => array(
						'step' => 1,
						'min'  => 0,
						'can_empty' => true,
						'can_decimal' => false
					),
					'parent_setting'    => 'retry_restrictions',
				),
				'quizRunOnceType'         => array(
					'name'           => 'quizRunOnceType',
					'label'          => esc_html__( 'Retries Applicable to', 'learndash' ),
					'type'           => 'select',
					'default'        => '1',
					'value'          => $this->setting_option_values['quizRunOnceType'],
					'options'        => array(
						'1' => esc_html__( 'All users', 'learndash' ),
						'2' => esc_html__( 'Registered users only', 'learndash' ),
						'3' => esc_html__( 'Anonymous user only', 'learndash' ),
					),
					'parent_setting' => 'retry_restrictions',
				),
				'quizRunOnceCookie'       => array(
					'name'           => 'quizRunOnceCookie',
					'label'          => '',
					'type'           => 'checkbox',
					'options'        => array(
						'on' => esc_html__( 'Use a cookie to restrict ALL users, including anonymous visitors', 'learndash' ),
					),
					'value'          => $this->setting_option_values['quizRunOnceCookie'],
					'default'        => '',
					'parent_setting' => 'retry_restrictions',
				),

				'quiz_reset_cookies'      => array(
					'name'           => 'quiz_reset_cookies',
					'type'           => 'custom',
					'html'           => '<div><input class="button-secondary" type="button" name="resetQuizLock" value="' . esc_html__( 'Reset the user identification', 'learndash' ) . '"><span id="resetLockMsg" style="display:none; background-color: rgb(255, 255, 173); border: 1px solid rgb(143, 143, 143); padding: 4px; margin-left: 5px; ">' . esc_html__( 'User identification has been reset.', 'learndash' ) . '</span><p class="description"></p></div>',
					'label'          => '',
					'parent_setting' => 'retry_restrictions',
				),

				'forcingQuestionSolve'    => array(
					'name'    => 'forcingQuestionSolve',
					'label'   => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Completion', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'type'    => 'checkbox',
					'options' => array(
						'on' => sprintf(
							// translators: placeholder: Questions.
							esc_html_x( 'All %s required to complete', 'placeholder: Questions', 'learndash' ),
							learndash_get_custom_label( 'questions' )
						),
					),
					'value'   => $this->setting_option_values['forcingQuestionSolve'],
					'default' => 'on',
				),
				'quiz_time_limit_enabled' => array(
					'name'                => 'quiz_time_limit_enabled',
					'label'               => esc_html__( 'Time Limit', 'learndash' ),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'on' => '',
					),
					'value'               => $this->setting_option_values['quiz_time_limit_enabled'],
					'default'             => '',
					'child_section_state' => ( 'on' === $this->setting_option_values['quiz_time_limit_enabled'] ) ? 'open' : 'closed',

				),
				'timeLimit'               => array(
					'name'           => 'timeLimit',
					'label'          => esc_html__( 'Automatically Submit After', 'learndash' ),
					'type'           => 'timer-entry',
					'class'          => 'small-text',
					'placeholder'    => esc_html__( 'e.g. 0', 'learndash' ),
					'default'        => '',
					'value'          => $this->setting_option_values['timeLimit'],
					'parent_setting' => 'quiz_time_limit_enabled',
				),

			);
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

				if ( ! isset( $settings_values['certificate'] ) ) {
					$settings_values['certificate'] = '';
				}

				if ( ! isset( $settings_values['threshold'] ) ) {
					$settings_values['threshold'] = '';
				}

				if ( '-1' === $settings_values['certificate'] ) {
					$settings_values['certificate'] = '';
				}

				if ( ! empty( $settings_values['certificate'] ) ) {
					$settings_values['threshold']   = floatval( $settings_values['threshold'] ) / 100;
				} else {
					$settings_values['threshold']   = '';
					$settings_values['certificate'] = '';
				}

				// Clear out the time limit is the time limit enabled is not set.
				if ( ! isset( $settings_values['quiz_time_limit_enabled'] ) ) {
					$settings_values['quiz_time_limit_enabled'] = '';
				}
				if ( ! isset( $settings_values['timeLimit'] ) ) {
					$settings_values['timeLimit'] = '';
				}

				if ( 'on' === $settings_values['quiz_time_limit_enabled'] ) {
					if ( empty( $settings_values['timeLimit'] ) ) {
						$settings_values['quiz_time_limit_enabled'] = '';
					}
				}

				if ( ! empty( $settings_values['timeLimit'] ) ) {
					if ( 'on' !== $settings_values['quiz_time_limit_enabled'] ) {
						$settings_values['timeLimit'] = 0;
					}
				}

				if ( 'on' === $settings_values['forcingQuestionSolve'] ) {
					$settings_values['forcingQuestionSolve'] = true;
				} else {
					$settings_values['forcingQuestionSolve'] = false;
				}

				if ( ! isset( $settings_values['retry_restrictions'] ) ) {
					$settings_values['retry_restrictions'] = '';
				}

				if ( ! isset( $settings_values['repeats'] ) ) {
					$settings_values['repeats'] = '';
				}

				if ( ( 'on' !== $settings_values['retry_restrictions'] ) || ( '' === $settings_values['repeats'] ) ) {
					$settings_values['repeats']            = '';
					$settings_values['retry_restrictions'] = '';
					$settings_values['quizRunOnce']        = false;
					$settings_values['quizRunOnceType']    = '';
					$settings_values['quizRunOnceCookie']  = '';
				} else {
					$settings_values['quizRunOnce']        = true;
					if ( ( isset( $settings_values['quizRunOnceCookie'] ) ) && ( 'on' === $settings_values['quizRunOnceCookie'] ) ) {
						$settings_values['quizRunOnceCookie'] = true;
					}
				}
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'quiz' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Quiz_Progress_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Quiz_Progress_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Quiz_Progress_Settings'] = LearnDash_Settings_Metabox_Quiz_Progress_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
