<?php
/**
 * LearnDash Settings Metabox for Quiz Display and Content Options.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Quiz_Display_Content' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Quiz_Display_Content extends LearnDash_Settings_Metabox {

		/**
		 * Variable to hold the number of questions.
		 * @var integer $questions_count
		 */
		var $questions_count = 0;
		
		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-quiz-display-content-settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Display and Content Options', 'learndash' );

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: quiz.
				esc_html_x( 'Controls how the %s will look and what will be displayed', 'placeholder: quiz', 'learndash' ),
				learndash_get_custom_label_lower( 'quiz' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'quiz_materials_enabled'              => 'quiz_materials_enabled',
				'quiz_materials'                      => 'quiz_materials',
				'custom_sorting'                      => 'custom_sorting',
				'autostart'                           => 'autostart',
				'showReviewQuestion'                  => 'showReviewQuestion',
				'quizSummaryHide'                     => 'quizSummaryHide',
				'skipQuestionDisabled'                => 'skipQuestionDisabled',
				'sortCategories'                      => 'sortCategories',
				'questionRandom'                      => 'questionRandom',
				'showMaxQuestion'                     => 'showMaxQuestion',
				'showMaxQuestionValue'                => 'showMaxQuestionValue',
				'showPoints'                          => 'showPoints',
				'showCategory'                        => 'showCategory',
				'hideQuestionPositionOverview'        => 'hideQuestionPositionOverview',
				'hideQuestionNumbering'               => 'hideQuestionNumbering',
				'numberedAnswer'                      => 'numberedAnswer',
				'answerRandom'                        => 'answerRandom',
				'quizModus'                           => 'quizModus',
				'quizModus_multiple_questionsPerPage' => 'quizModus_multiple_questionsPerPage',
				'quizModus_single_back_button'        => 'quizModus_single_back_button',
				'quizModus_single_feedback'           => 'quizModus_single_feedback',
				'titleHidden'                         => 'titleHidden',
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
			$_POST['autostart']                    = $settings_values['autostart'];
			$_POST['showReviewQuestion']           = $settings_values['showReviewQuestion'];
			$_POST['quizSummaryHide']              = $settings_values['quizSummaryHide'];
			$_POST['skipQuestionDisabled']         = $settings_values['skipQuestionDisabled'];
			$_POST['sortCategories']               = $settings_values['sortCategories'];
			$_POST['questionRandom']               = $settings_values['questionRandom'];
			$_POST['showMaxQuestion']              = $settings_values['showMaxQuestion'];
			$_POST['showMaxQuestionValue']         = $settings_values['showMaxQuestionValue'];
			$_POST['answerRandom']                 = $settings_values['answerRandom'];
			$_POST['showPoints']                   = $settings_values['showPoints'];
			$_POST['showCategory']                 = $settings_values['showCategory'];
			$_POST['hideQuestionPositionOverview'] = $settings_values['hideQuestionPositionOverview'];
			$_POST['hideQuestionNumbering']        = $settings_values['hideQuestionNumbering'];
			$_POST['numberedAnswer']               = $settings_values['numberedAnswer'];
			$_POST['quizModus']                    = $settings_values['quizModus'];
			$_POST['questionsPerPage']             = $settings_values['quizModus_multiple_questionsPerPage'];

			$_POST['titleHidden'] = $settings_values['titleHidden'];
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$this->quiz_edit = $this->init_quiz_edit( $this->_post );

			$this->ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( $this->_post->ID );

			if ( true === $this->settings_values_loaded ) {

				$questionMapper = new WpProQuiz_Model_QuestionMapper();
				$questions = $questionMapper->fetchAll( $this->quiz_edit['quiz'] );
				if ( ( is_array( $questions ) ) && ( ! empty( $questions ) ) ) {
					$this->questions_count = count( $questions );
				}

				if ( ! isset( $this->setting_option_values['quiz_materials'] ) ) {
					$this->setting_option_values['quiz_materials'] = '';
				}
				if ( ! empty( $this->setting_option_values['quiz_materials'] ) ) {
					$this->setting_option_values['quiz_materials_enabled'] = 'on';
				} else {
					$this->setting_option_values['quiz_materials_enabled'] = '';
				}

				if ( $this->quiz_edit['quiz'] ) {

					$this->setting_option_values['autostart'] = $this->quiz_edit['quiz']->isAutostart();
					if ( true === $this->setting_option_values['autostart'] ) {
						$this->setting_option_values['autostart'] = 'on';
					} else {
						$this->setting_option_values['autostart'] = '';
					}

					$this->setting_option_values['showReviewQuestion'] = $this->quiz_edit['quiz']->isShowReviewQuestion();
					if ( true === $this->setting_option_values['showReviewQuestion'] ) {
						$this->setting_option_values['showReviewQuestion'] = 'on';
					} else {
						$this->setting_option_values['showReviewQuestion'] = '';
					}

					$this->setting_option_values['quizSummaryHide'] = $this->quiz_edit['quiz']->isQuizSummaryHide();
					if ( true === $this->setting_option_values['quizSummaryHide'] ) {
						$this->setting_option_values['quizSummaryHide'] = '';
					} else {
						$this->setting_option_values['quizSummaryHide'] = 'on';
					}

					$this->setting_option_values['skipQuestionDisabled'] = $this->quiz_edit['quiz']->isSkipQuestionDisabled();
					if ( true === $this->setting_option_values['skipQuestionDisabled'] ) {
						$this->setting_option_values['skipQuestionDisabled'] = '';
					} else {
						$this->setting_option_values['skipQuestionDisabled'] = 'on';
					}

					$this->setting_option_values['sortCategories'] = $this->quiz_edit['quiz']->isSortCategories();
					if ( true === $this->setting_option_values['sortCategories'] ) {
						$this->setting_option_values['sortCategories'] = 'on';
					} else {
						$this->setting_option_values['sortCategories'] = '';
					}

					$this->setting_option_values['questionRandom'] = $this->quiz_edit['quiz']->isQuestionRandom();
					if ( true === $this->setting_option_values['questionRandom'] ) {
						$this->setting_option_values['questionRandom'] = 'on';
					} else {
						$this->setting_option_values['questionRandom'] = '';
					}

					$this->setting_option_values['showMaxQuestion'] = $this->quiz_edit['quiz']->isShowMaxQuestion();
					if ( true === $this->setting_option_values['showMaxQuestion'] ) {
						$this->setting_option_values['showMaxQuestion'] = 'on';
					} else {
						$this->setting_option_values['showMaxQuestion'] = '';
					}

					$this->setting_option_values['showMaxQuestionValue'] = $this->quiz_edit['quiz']->getShowMaxQuestionValue();
					if ( ! empty( $this->setting_option_values['showMaxQuestionValue'] ) ) {
						$this->setting_option_values['showMaxQuestionValue'] = absint( $this->setting_option_values['showMaxQuestionValue'] );
					} else {
						$this->setting_option_values['showMaxQuestionValue'] = '';
					}

					if ( absint( $this->setting_option_values['showMaxQuestionValue'] ) > $this->questions_count ) {
						$this->setting_option_values['showMaxQuestionValue'] = $this->questions_count;
					}

					if ( 'on' === $this->setting_option_values['questionRandom'] ) {
						if ( 'on' !== $this->setting_option_values['showMaxQuestion'] ) {
							$this->setting_option_values['showMaxQuestionValue'] = 0;
						}
					} else {
						$this->setting_option_values['showMaxQuestion']      = '';
						$this->setting_option_values['showMaxQuestionValue'] = 0;
					}

					if ( ( 'on' === $this->setting_option_values['sortCategories'] ) || ( 'on' === $this->setting_option_values['questionRandom'] ) ) {
						$this->setting_option_values['custom_sorting'] = 'on';
					} else {
						$this->setting_option_values['custom_sorting'] = '';
					}

					$this->setting_option_values['showPoints'] = $this->quiz_edit['quiz']->isShowPoints();
					if ( true === $this->quiz_edit['quiz']->isShowPoints() ) {
						$this->setting_option_values['showPoints'] = 'on';
					} else {
						$this->setting_option_values['showPoints'] = '';
					}

					$this->setting_option_values['showCategory'] = $this->quiz_edit['quiz']->isShowCategory();
					if ( true === $this->setting_option_values['showCategory'] ) {
						$this->setting_option_values['showCategory'] = 'on';
					} else {
						$this->setting_option_values['showCategory'] = '';
					}

					$this->setting_option_values['hideQuestionPositionOverview'] = $this->quiz_edit['quiz']->isHideQuestionPositionOverview();
					if ( true !== $this->setting_option_values['hideQuestionPositionOverview'] ) {
						$this->setting_option_values['hideQuestionPositionOverview'] = 'on';
					} else {
						$this->setting_option_values['hideQuestionPositionOverview'] = '';
					}

					$this->setting_option_values['hideQuestionNumbering'] = $this->quiz_edit['quiz']->isHideQuestionNumbering();
					if ( true !== $this->setting_option_values['hideQuestionNumbering'] ) {
						$this->setting_option_values['hideQuestionNumbering'] = 'on';
					} else {
						$this->setting_option_values['hideQuestionNumbering'] = '';
					}

					$this->setting_option_values['numberedAnswer'] = $this->quiz_edit['quiz']->isNumberedAnswer();
					if ( true === $this->setting_option_values['numberedAnswer'] ) {
						$this->setting_option_values['numberedAnswer'] = 'on';
					} else {
						$this->setting_option_values['numberedAnswer'] = '';
					}

					$this->setting_option_values['answerRandom'] = $this->quiz_edit['quiz']->isAnswerRandom();
					if ( true === $this->setting_option_values['answerRandom'] ) {
						$this->setting_option_values['answerRandom'] = 'on';
					} else {
						$this->setting_option_values['answerRandom'] = '';
					}

					$this->setting_option_values['quizModus']                           = '';
					$this->setting_option_values['quizModus_single_feedback']           = '';
					$this->setting_option_values['quizModus_single_back_button']        = '';
					$this->setting_option_values['quizModus_multiple_questionsPerPage'] = 0;

					$this->setting_option_values['quizModus'] = (int) $this->quiz_edit['quiz']->getQuizModus();
					if ( 0 === $this->setting_option_values['quizModus'] ) {
						$this->setting_option_values['quizModus']                 = 'single';
						$this->setting_option_values['quizModus_single_feedback'] = 'end';
					} elseif ( 1 === $this->setting_option_values['quizModus'] ) {
						$this->setting_option_values['quizModus']                    = 'single';
						$this->setting_option_values['quizModus_single_feedback']    = 'end';
						$this->setting_option_values['quizModus_single_back_button'] = 'on';
					} elseif ( 2 === $this->setting_option_values['quizModus'] ) {
						$this->setting_option_values['quizModus']                 = 'single';
						$this->setting_option_values['quizModus_single_feedback'] = 'each';
					} elseif ( 3 === $this->setting_option_values['quizModus'] ) {
						$this->setting_option_values['quizModus']                           = 'multiple';
						$this->setting_option_values['quizModus_multiple_questionsPerPage'] = (int) $this->quiz_edit['quiz']->getQuestionsPerPage();
					}

					$this->setting_option_values['titleHidden'] = $this->quiz_edit['quiz']->isTitleHidden();
					if ( true !== $this->setting_option_values['titleHidden'] ) {
						$this->setting_option_values['titleHidden'] = 'on';
					} else {
						$this->setting_option_values['titleHidden'] = '';
					}
				}

				if ( ( 'on' === $this->setting_option_values['showPoints'] ) || ( 'on' === $this->setting_option_values['showCategory'] ) || ( 'on' === $this->setting_option_values['hideQuestionPositionOverview'] ) || ( 'on' === $this->setting_option_values['hideQuestionNumbering'] ) || ( 'on' === $this->setting_option_values['numberedAnswer'] ) ) {
					$this->setting_option_values['custom_question_elements'] = 'on';
				} else {
					$this->setting_option_values['custom_question_elements'] = '';
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

			//apply_filters( $this->settings_screen_id . '_display_settings', $this->settings_fields_legacy, $this->settings_screen_id, $this->settings_values_legacy );

			$this->setting_option_fields = array(
				'quizModus_single_back_button' => array(
					'name'       => 'quizModus_single_back_button',
					'label_none' => true,
					'input_full' => true,
					'type'       => 'checkbox',
					'value'      => $this->setting_option_values['quizModus_single_back_button'],
					'default'    => '',
					'options'    => array(
						'on' => esc_html__( 'Display Back button', 'learndash' ),
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['quizModus_single_back_button_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'quizModus_single_feedback' => array(
					'name'       => 'quizModus_single_feedback',
					'label_none' => true,
					'input_full' => true,
					'type'       => 'radio',
					'value'      => $this->setting_option_values['quizModus_single_feedback'],
					'default'    => 'end',
					'options'    => array(
						'end'  => array(
							'label'               => esc_html__( 'Display results at the end only', 'learndash' ),
							'inline_fields'       => array(
								'quizModus_single' => $this->settings_sub_option_fields['quizModus_single_back_button_fields'],
							),
							'inner_section_state' => ( 'end' === $this->setting_option_values['quizModus_single_feedback'] ) ? 'open' : 'closed',
						),
						'each' => array(
							'label' => esc_html__( 'Display results after each submitted answer', 'learndash' ),
						),
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['quizModus_single_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'quizModus_multiple_questionsPerPage' => array(
					'name'        => 'quizModus_multiple_questionsPerPage',
					'type'        => 'number',
					'class'       => 'small-text',
					'label_none'  => true,
					'input_full'  => true,
					'input_label' => sprintf(
						// translators: placeholder: questions.
						esc_html_x( '%s per page (0 = all)', 'placeholder: questions', 'learndash' ),
						learndash_get_custom_label_lower( 'questions' )
					),
					'attrs'       => array(
						'step' => 1,
						'min'  => 0,
					),
					'value'       => $this->setting_option_values['quizModus_multiple_questionsPerPage'],
					'default'     => 0,
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['quizModus_multiple_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'showMaxQuestionValue' => array(
					'name'        => 'showMaxQuestionValue',
					'type'        => 'number',
					'class'       => 'small-text',
					'label_none'  => true,
					'input_full'  => true,
					'input_label' => sprintf(
						// translators: placeholder: questions.
						esc_html_x( 'out of %1$d %2$s.', 'placeholder: count of questions, questions label.', 'learndash' ),
						$this->questions_count,
						learndash_get_custom_label_lower( 'questions' )
					),
					'attrs'       => array(
						'step' => 1,
						'min'  => 0,
						'max'  => $this->questions_count,
					),
					'value'       => $this->setting_option_values['showMaxQuestionValue'],
					'default'     => 0,
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['showMaxQuestionValue_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'quiz_materials_enabled'       => array(
					'name'                => 'quiz_materials_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Materials', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'help_text'           => sprintf(
						// translators: placeholder: quiz, quiz.
						esc_html_x( 'List and display support materials for the %1$s. This is visible to any user having access to the %2$s.', 'placeholder: quiz, quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label_lower( 'quiz' )
					),
					'value'               => $this->setting_option_values['quiz_materials_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Any content added below is displayed on the %s page', 'placeholder: Quiz', 'learndash' ),
							learndash_get_custom_label( 'quiz' )
						),
						''   => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['quiz_materials_enabled'] ) ? 'open' : 'closed',
				),
				'quiz_materials'               => array(
					'name'           => 'quiz_materials',
					'type'           => 'wpeditor',
					'parent_setting' => 'quiz_materials_enabled',
					'value'          => $this->setting_option_values['quiz_materials'],
					'default'        => '',
					'placeholder'    => esc_html__( 'Add a list of needed documents or URLs. This field supports HTML.', 'learndash' ),
					'editor_args' => array(
						'textarea_name' => $this->settings_metabox_key . '[quiz_materials]',
						'textarea_rows' => 3,
					),

				),
				'autostart'                    => array(
					'name'    => 'autostart',
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Autostart', 'learndash' ),
					'value'   => $this->setting_option_values['autostart'],
					'default' => '',
					'options' => array(
						'on' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Start automatically, without the "Start %s" button', 'placeholder: Quiz', 'learndash' ),
							learndash_get_custom_label( 'quiz' )
						),
					),
				),

				'quizModus'                    => array(
					'name'    => 'quizModus',
					'label'   => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Display', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'type'    => 'select',
					'default' => 'single',
					'value'   => $this->setting_option_values['quizModus'],
					'options' => array(
						'single'   => array(
							'label'               => sprintf(
								// translators: placeholder: question.
								esc_html_x( 'One %s at a time', 'placeholder: question', 'learndash' ),
								learndash_get_custom_label_lower( 'question' )
							),
							'inline_fields'       => array(
								'quizModus_single' => $this->settings_sub_option_fields['quizModus_single_fields'],
							),
							'inner_section_state' => ( 'single' === $this->setting_option_values['quizModus'] ) ? 'open' : 'closed',
						),
						'multiple' => array(
							'label'               => sprintf(
								// translators: placeholder: questions.
								esc_html_x( 'All %s at once (or paginated)', 'placeholder: questions', 'learndash' ),
								learndash_get_custom_label_lower( 'questions' )
							),
							'inline_fields'       => array(
								'quizModus_multiple' => $this->settings_sub_option_fields['quizModus_multiple_fields'],
							),
							'inner_section_state' => ( 'multiple' === $this->setting_option_values['quizModus'] ) ? 'open' : 'closed',
						),

					),
				),
				'showReviewQuestion'           => array(
					'name'                => 'showReviewQuestion',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Overview Table', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'value'               => $this->setting_option_values['showReviewQuestion'],
					'default'             => '',
					'options'             => array(
						''   => '',
						'on' => sprintf(
							// translators: placeholder: Quiz, Questions.
							esc_html_x( 'An overview table will be shown for all %s.', 'placeholder: Questions', 'learndash' ),
							learndash_get_custom_label_lower( 'questions' )
						),
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['showReviewQuestion'] ) ? 'open' : 'closed',
				),
				'quizSummaryHide'              => array(
					'name'           => 'quizSummaryHide',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Summary', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'value'          => $this->setting_option_values['quizSummaryHide'],
					'default'        => '',
					'options'        => array(
						''   => '',
						'on' => esc_html__( 'Display a summary table before submission', 'learndash' ),
					),
					'parent_setting' => 'showReviewQuestion',
				),
				'skipQuestionDisabled'         => array(
					'name'           => 'skipQuestionDisabled',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'Skip %s', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'value'          => $this->setting_option_values['skipQuestionDisabled'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'showReviewQuestion',
				),

				'custom_sorting'               => array(
					'name'                => 'custom_sorting',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'Custom %s Ordering', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'value'               => $this->setting_option_values['custom_sorting'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['custom_sorting'] ) ? 'open' : 'closed',
				),
				'sortCategories'               => array(
					'name'           => 'sortCategories',
					'type'           => 'checkbox',
					'label'          => esc_html__( 'Sort by Category', 'learndash' ),
					'value'          => $this->setting_option_values['sortCategories'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_sorting',
				),
				'questionRandom'               => array(
					'name'                => 'questionRandom',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Randomize Order', 'learndash' ),
					'value'               => $this->setting_option_values['questionRandom'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'parent_setting'      => 'custom_sorting',
					'child_section_state' => ( 'on' === $this->setting_option_values['questionRandom'] ) ? 'open' : 'closed',
				),

				'showMaxQuestion'              => array(
					'name'           => 'showMaxQuestion',
					'label'          => '',
					'type'           => 'radio',
					'value'          => $this->setting_option_values['showMaxQuestion'],
					'default'        => '',
					'options'        => array(
						''   => array(
							'label' => sprintf(
								// translators: placeholder: questions.
								esc_html_x( 'Display all %s', 'placeholder: questions', 'learndash' ),
								learndash_get_custom_label_lower( 'questions' )
							),
						),
						'on' => array(
							'label'               => sprintf(
								// translators: placeholder: questions.
								esc_html_x( 'Display subset of %s', 'placeholder: questions', 'learndash' ),
								learndash_get_custom_label_lower( 'questions' )
							),
							'inline_fields'       => array(
								'showMaxQuestionValue_fields' => $this->settings_sub_option_fields['showMaxQuestionValue_fields'],
							),
							'inner_section_state' => ( 'on' === $this->setting_option_values['showMaxQuestion'] ) ? 'open' : 'closed',

						),
					),
					'parent_setting' => 'questionRandom',
				),
				'custom_question_elements'     => array(
					'name'                => 'custom_question_elements',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'Additional %s Options', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'value'               => $this->setting_option_values['custom_question_elements'],
					'default'             => '',
					'options'             => array(
						''   => '',
						'on' => sprintf(
							// translators: placeholder: Question.
							esc_html_x( 'Any enabled elements below will be displayed in each %s', 'placeholder: Question', 'learndash' ),
							learndash_get_custom_label( 'question' )
						),
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['custom_question_elements'] ) ? 'open' : 'closed',
				),

				'showPoints'                   => array(
					'name'           => 'showPoints',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Point Value', 'learndash' ),
					'value'          => $this->setting_option_values['showPoints'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_question_elements',
				),
				'showCategory'                 => array(
					'name'           => 'showCategory',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Category', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'value'          => $this->setting_option_values['showCategory'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_question_elements',
				),
				'hideQuestionPositionOverview' => array(
					'name'           => 'hideQuestionPositionOverview',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Position', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'value'          => $this->setting_option_values['hideQuestionPositionOverview'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_question_elements',
				),

				'hideQuestionNumbering'        => array(
					'name'           => 'hideQuestionNumbering',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Numbering', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'value'          => $this->setting_option_values['hideQuestionNumbering'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_question_elements',
				),
				'numberedAnswer'               => array(
					'name'           => 'numberedAnswer',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Number Answers', 'learndash' ),
					'value'          => $this->setting_option_values['numberedAnswer'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_question_elements',
				),
				'answerRandom'                 => array(
					'name'           => 'answerRandom',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Randomize Answers', 'learndash' ),
					'help_text'      => sprintf(
						// translators: placeholder: question.
						esc_html_x( 'Answer display will be randomized within any given %s.', 'placeholder: question.', 'learndash' ),
						learndash_get_custom_label_lower( 'question' )
					),
					'value'          => $this->setting_option_values['answerRandom'],
					'default'        => '',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_question_elements',
				),

				'titleHidden'                  => array(
					'name'      => 'titleHidden',
					'type'      => 'checkbox-switch',
					'label'     => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Title', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'value'     => $this->setting_option_values['titleHidden'],
					'default'   => '',
					'help_text' => sprintf(
						// translators: placeholder: quiz, Quiz, Quizzes.
						esc_html_x( 'A second %1$s title will be displayed on the %2$s Post. This option is recommended if displaying %3$s via Shortcode.', 'placeholder: quiz, Quiz, Quizzes.', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label( 'quiz' ),
						learndash_get_custom_label( 'quizzes' )
					),
					'options'   => array(
						''   => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'Only the %s Post title is shown', 'placeholder: Quiz', 'learndash' ),
							learndash_get_custom_label( 'quiz' )
						),
						'on' => sprintf(
							// translators: placeholder: Quiz, Quiz, quiz.
							esc_html_x( 'The %1$s Title is displayed in addition to the %2$s Post title. Recommended for %3$s shortcode usage.', 'placeholder: Quiz, Quiz, quiz', 'learndash' ),
							learndash_get_custom_label( 'quiz' ),
							learndash_get_custom_label( 'quiz' ),
							learndash_get_custom_label_lower( 'quiz' )
						),
					),
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

				if ( ( 'on' !== $settings_values['quiz_materials_enabled'] ) || ( empty( $settings_values['quiz_materials'] ) ) ) {
					$settings_values['quiz_materials_enabled'] = '';
					$settings_values['quiz_materials']         = '';
				}

				if ( ( isset( $settings_values['autostart'] ) ) && ( 'on' === $settings_values['autostart'] ) ) {
					$settings_values['autostart'] = true;
				} else {
					$settings_values['autostart'] = false;
				}

				if ( ( isset( $settings_values['showReviewQuestion'] ) ) && ( 'on' === $settings_values['showReviewQuestion'] ) ) {
					$settings_values['showReviewQuestion'] = true;
				} else {
					$settings_values['showReviewQuestion'] = false;
				}

				if ( ( isset( $settings_values['quizSummaryHide'] ) ) && ( 'on' === $settings_values['quizSummaryHide'] ) ) {
					$settings_values['quizSummaryHide'] = false;
				} else {
					$settings_values['quizSummaryHide'] = true;
				}

				if ( ( isset( $settings_values['skipQuestionDisabled'] ) ) && ( 'on' === $settings_values['skipQuestionDisabled'] ) ) {
					$settings_values['skipQuestionDisabled'] = false;
				} else {
					$settings_values['skipQuestionDisabled'] = true;
				}

				if ( ( isset( $settings_values['sortCategories'] ) ) && ( 'on' === $settings_values['sortCategories'] ) ) {
					$settings_values['sortCategories'] = true;
				} else {
					$settings_values['sortCategories'] = false;
				}

				if ( ( isset( $settings_values['questionRandom'] ) ) && ( 'on' === $settings_values['questionRandom'] ) ) {
					$settings_values['questionRandom'] = true;
				} else {
					$settings_values['questionRandom'] = false;
				}

				if ( ( isset( $settings_values['answerRandom'] ) ) && ( 'on' === $settings_values['answerRandom'] ) ) {
					$settings_values['answerRandom'] = true;
				} else {
					$settings_values['answerRandom'] = false;
				}

				if ( ( isset( $settings_values['showMaxQuestion'] ) ) && ( 'on' === $settings_values['showMaxQuestion'] ) ) {
					$settings_values['showMaxQuestion'] = true;
				} else {
					$settings_values['showMaxQuestion'] = false;
				}

				if ( ( isset( $settings_values['showMaxQuestionValue'] ) ) && ( ! empty( $settings_values['showMaxQuestionValue'] ) ) ) {
					$settings_values['showMaxQuestionValue'] = absint( $settings_values['showMaxQuestionValue'] );
					if ( empty( $settings_values['showMaxQuestionValue'] ) ) {
						$settings_values['showMaxQuestionValue'] = '';
					}
				} else {
					$settings_values['showMaxQuestion'] = '';
				}

				//if ( ( isset( $settings_values['answerRandom'] ) ) && ( 'on' === $settings_values['answerRandom'] ) ) {
				//	$settings_values['answerRandom'] = true;
				//} else {
				//	$settings_values['answerRandom'] = false;
				//}

				if ( ( isset( $settings_values['showPoints'] ) ) && ( 'on' === $settings_values['showPoints'] ) ) {
					$settings_values['showPoints'] = true;
				} else {
					$settings_values['showPoints'] = false;
				}

				if ( ( isset( $settings_values['showCategory'] ) ) && ( 'on' === $settings_values['showCategory'] ) ) {
					$settings_values['showCategory'] = true;
				} else {
					$settings_values['showCategory'] = false;
				}

				if ( ( isset( $settings_values['hideQuestionPositionOverview'] ) ) && ( 'on' === $settings_values['hideQuestionPositionOverview'] ) ) {
					$settings_values['hideQuestionPositionOverview'] = false;
				} else {
					$settings_values['hideQuestionPositionOverview'] = true;
				}

				if ( ( isset( $settings_values['hideQuestionNumbering'] ) ) && ( 'on' === $settings_values['hideQuestionNumbering'] ) ) {
					$settings_values['hideQuestionNumbering'] = false;
				} else {
					$settings_values['hideQuestionNumbering'] = true;
				}

				if ( ( isset( $settings_values['numberedAnswer'] ) ) && ( 'on' === $settings_values['numberedAnswer'] ) ) {
					$settings_values['numberedAnswer'] = true;
				} else {
					$settings_values['numberedAnswer'] = false;
				}

				if ( ( isset( $settings_values['titleHidden'] ) ) && ( 'on' === $settings_values['titleHidden'] ) ) {
					$settings_values['titleHidden'] = false;
				} else {
					$settings_values['titleHidden'] = true;
				}

				if ( ( isset( $settings_values['quizModus'] ) ) && ( ! empty( $settings_values['quizModus'] ) ) ) {
					if ( 'single' === $settings_values['quizModus'] ) {
						$settings_values['quizModus_multiple_questionsPerPage'] = 0;
						$settings_values['quizModus']                           = 0;

						if ( 'on' === $settings_values['quizModus_single_back_button'] ) {
							$settings_values['quizModus'] = 1;
						}

						if ( 'each' === $settings_values['quizModus_single_feedback'] ) {
							$settings_values['quizModus'] = 2;
						}
					} elseif ( 'multiple' === $settings_values['quizModus'] ) {
						$settings_values['quizModus'] = 3;

						if ( isset( $settings_values['quizModus_multiple_questionsPerPage'] ) ) {
							$settings_values['quizModus_multiple_questionsPerPage'] = absint( $settings_values['quizModus_multiple_questionsPerPage'] );
						}
					}
				}

				if ( ( isset( $settings_values['custom_sorting'] ) ) && ( 'on' === $settings_values['custom_sorting'] ) ) {
					if ( ( isset( $settings_values['questionRandom'] ) ) && ( true === $settings_values['questionRandom'] ) ) {
						if ( ( isset( $settings_values['showMaxQuestion'] ) ) && ( true === $settings_values['showMaxQuestion'] ) ) {
							if ( ( isset( $settings_values['showMaxQuestionValue'] ) ) && ( ! empty( $settings_values['showMaxQuestionValue'] ) ) ) {
								$settings_values['showMaxQuestionValue'] = absint( $settings_values['showMaxQuestionValue'] );
							} else {
								$settings_values['showMaxQuestion']      = '';
								$settings_values['showMaxQuestionValue'] = 0;
							}
						} else {
							$settings_values['showMaxQuestion'] = '';
						}
					} else {
						$settings_values['questionRandom'] = '';
					}
				} else {
					$settings_values['custom_sorting']       = '';
					$settings_values['questionRandom']       = '';
					$settings_values['showMaxQuestion']      = '';
					$settings_values['showMaxQuestionValue'] = '';
				}
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'quiz' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Quiz_Display_Content'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Quiz_Display_Content' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Quiz_Display_Content'] = LearnDash_Settings_Metabox_Quiz_Display_Content::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
