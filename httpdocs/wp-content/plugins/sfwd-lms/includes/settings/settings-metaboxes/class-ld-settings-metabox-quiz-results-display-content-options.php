<?php
/**
 * LearnDash Settings Metabox for Quiz Results Page Display & Content Options.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Quiz_Results_Options' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Quiz_Results_Options extends LearnDash_Settings_Metabox {

		protected $quiz_edit = null;
		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-quiz-results-options';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Results Page Display', 'learndash' );

			$this->settings_section_description = esc_html__( 'Controls how the results page will look', 'learndash' );

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'resultGradeEnabled'        => 'resultGradeEnabled',
				'resultText'                => 'resultText',
				'resultTextGrade'           => 'resultTextGrade',

				'btnRestartQuizHidden'      => 'btnRestartQuizHidden',
				'showAverageResult'         => 'showAverageResult',
				'showCategoryScore'         => 'showCategoryScore',
				'hideResultPoints'          => 'hideResultPoints',
				'hideResultCorrectQuestion' => 'hideResultCorrectQuestion',
				'hideResultQuizTime'        => 'hideResultQuizTime',

				'hideAnswerMessageBox'      => 'hideAnswerMessageBox',
				'disabledAnswerMark'        => 'disabledAnswerMark',
				'btnViewQuestionHidden'     => 'btnViewQuestionHidden',

			);

			parent::__construct();
		}


		public function save_fields_to_post( $pro_quiz_edit, $settings_values = array() ) {

			$_POST['resultGradeEnabled'] = $settings_values['resultGradeEnabled'];

			$_POST['btnRestartQuizHidden']      = $settings_values['btnRestartQuizHidden'];
			$_POST['showAverageResult']         = $settings_values['showAverageResult'];
			$_POST['showCategoryScore']         = $settings_values['showCategoryScore'];
			$_POST['hideResultPoints']          = $settings_values['hideResultPoints'];
			$_POST['hideResultCorrectQuestion'] = $settings_values['hideResultCorrectQuestion'];
			$_POST['hideResultQuizTime']        = $settings_values['hideResultQuizTime'];
			$_POST['hideAnswerMessageBox']      = $settings_values['hideAnswerMessageBox'];
			$_POST['disabledAnswerMark']        = $settings_values['disabledAnswerMark'];
			$_POST['btnViewQuestionHidden']     = $settings_values['btnViewQuestionHidden'];
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$this->quiz_edit = $this->init_quiz_edit( $this->_post );

			if ( true === $this->settings_values_loaded ) {

				if ( $this->quiz_edit['quiz'] ) {
					$this->setting_option_values['resultGradeEnabled'] = $this->quiz_edit['quiz']->isResultGradeEnabled();
					if ( true === $this->setting_option_values['resultGradeEnabled'] ) {
						$this->setting_option_values['resultGradeEnabled'] = true;
					} else {
						$this->setting_option_values['resultGradeEnabled'] = '';
					}
					// Always enabled.
					//$this->setting_option_values['resultGradeEnabled'] = true;

					$this->setting_option_values['btnRestartQuizHidden'] = $this->quiz_edit['quiz']->isBtnRestartQuizHidden();
					if ( true !== $this->setting_option_values['btnRestartQuizHidden'] ) {
						$this->setting_option_values['btnRestartQuizHidden'] = 'on';
					} else {
						$this->setting_option_values['btnRestartQuizHidden'] = '';
					}

					$this->setting_option_values['showAverageResult'] = $this->quiz_edit['quiz']->isShowAverageResult();
					if ( true === $this->setting_option_values['showAverageResult'] ) {
						$this->setting_option_values['showAverageResult'] = 'on';
					} else {
						$this->setting_option_values['showAverageResult'] = '';
					}

					$this->setting_option_values['showCategoryScore'] = $this->quiz_edit['quiz']->isShowCategoryScore();
					if ( true === $this->setting_option_values['showCategoryScore'] ) {
						$this->setting_option_values['showCategoryScore'] = 'on';
					} else {
						$this->setting_option_values['showCategoryScore'] = '';
					}

					$this->setting_option_values['hideResultPoints'] = $this->quiz_edit['quiz']->isHideResultPoints();
					if ( true !== $this->setting_option_values['hideResultPoints'] ) {
						$this->setting_option_values['hideResultPoints'] = 'on';
					} else {
						$this->setting_option_values['hideResultPoints'] = '';
					}

					$this->setting_option_values['hideResultCorrectQuestion'] = $this->quiz_edit['quiz']->isHideResultCorrectQuestion();
					if ( true !== $this->setting_option_values['hideResultCorrectQuestion'] ) {
						$this->setting_option_values['hideResultCorrectQuestion'] = 'on';
					} else {
						$this->setting_option_values['hideResultCorrectQuestion'] = '';
					}

					$this->setting_option_values['hideResultQuizTime'] = $this->quiz_edit['quiz']->isHideResultQuizTime();
					if ( true !== $this->setting_option_values['hideResultQuizTime'] ) {
						$this->setting_option_values['hideResultQuizTime'] = 'on';
					} else {
						$this->setting_option_values['hideResultQuizTime'] = '';
					}

					if ( ( 'on' === $this->setting_option_values['showAverageResult'] )
					|| ( 'on' === $this->setting_option_values['showCategoryScore'] )
					|| ( 'on' === $this->setting_option_values['hideResultPoints'] )
					|| ( 'on' === $this->setting_option_values['hideResultCorrectQuestion'] )
					|| ( 'on' === $this->setting_option_values['hideResultQuizTime'] )
					) {
						$this->setting_option_values['custom_result_data_display'] = 'on';
					} else {
						$this->setting_option_values['custom_result_data_display'] = '';
					}

					$this->setting_option_values['hideAnswerMessageBox'] = $this->quiz_edit['quiz']->isHideAnswerMessageBox();
					if ( true !== $this->setting_option_values['hideAnswerMessageBox'] ) {
						$this->setting_option_values['hideAnswerMessageBox'] = 'on';
					} else {
						$this->setting_option_values['hideAnswerMessageBox'] = '';
					}

					$this->setting_option_values['disabledAnswerMark'] = $this->quiz_edit['quiz']->isDisabledAnswerMark();
					if ( true !== $this->setting_option_values['disabledAnswerMark'] ) {
						$this->setting_option_values['disabledAnswerMark'] = 'on';
					} else {
						$this->setting_option_values['disabledAnswerMark'] = '';
					}

					$this->setting_option_values['btnViewQuestionHidden'] = $this->quiz_edit['quiz']->isBtnViewQuestionHidden();
					if ( true !== $this->setting_option_values['btnViewQuestionHidden'] ) {
						$this->setting_option_values['btnViewQuestionHidden'] = 'on';
					} else {
						$this->setting_option_values['btnViewQuestionHidden'] = '';
					}

					if ( ( 'on' === $this->setting_option_values['hideAnswerMessageBox'] )
					|| ( 'on' === $this->setting_option_values['disabledAnswerMark'] )
					|| ( 'on' === $this->setting_option_values['btnViewQuestionHidden'] )
					) {
						$this->setting_option_values['custom_answer_feedback'] = 'on';
					} else {
						$this->setting_option_values['custom_answer_feedback'] = '';
					}

					$this->setting_option_values['resultTextGrade'] = array();
					$this->setting_option_values['resultText']      = $this->quiz_edit['quiz']->getResultText();
					if ( ( '' === $this->setting_option_values['resultText'] ) || ( isset ( $this->setting_option_values['resultText']['text'][0] ) ) && ( ! empty( $this->setting_option_values['resultText']['text'][0] ) ) ) {
						$this->setting_option_values['resultGradeEnabled'] = 'on';
						if ( is_array( $this->setting_option_values['resultText'] ) ) {
							$this->setting_option_values['resultTextGrade'] = $this->setting_option_values['resultText'];
						} else {
							$this->setting_option_values['resultTextGrade']['text'][0]    = $this->setting_option_values['resultText'];
							$this->setting_option_values['resultTextGrade']['prozent'][0] = '0';
							$this->setting_option_values['resultTextGrade']['activ'][0]   = '1';
						}
					} else {
						$this->setting_option_values['resultGradeEnabled'] = '';
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

			$this->setting_option_fields = array(
				'resultGradeEnabled'       => array(
					'name'                => 'resultGradeEnabled',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Result Message(s)', 'learndash' ),
					'value'               => $this->setting_option_values['resultGradeEnabled'],
					'default'             => '',
					'help_text'           => esc_html__( "When enabled, the first message will be diplayed to ALL users. To customize the message based on earned score, add new Graduation Levels and set the 'From' field to the desired grade.", 'learndash' ),
					'options'             => array(
						''   => '',
						'on' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'The message below is displayed on the %s results page.', 'placeholder: Quiz', 'learndash' ),
							learndash_get_custom_label( 'Quiz' )
						),
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['resultGradeEnabled'] ) ? 'open' : 'closed',
				),

				'resultText'                 => array(
					'name'           => 'resultText',
					'type'           => 'custom',
					'label_none'     => true,
					'input_full'     => true,
					'parent_setting' => 'resultGradeEnabled',
					'html'           => $this->get_custom_result_messages(),
				),

				'btnRestartQuizHidden'       => array(
					'name'    => 'btnRestartQuizHidden',
					'type'    => 'checkbox-switch',
					'label'   => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Restart %s button', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'Quiz' )
					),
					'value'   => $this->setting_option_values['btnRestartQuizHidden'],
					'default' => 'on',
					'options' => array(
						'on' => '',
					),
				),

				'custom_result_data_display' => array(
					'name'                => 'custom_result_data_display',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Custom Results Display', 'learndash' ),
					'value'               => $this->setting_option_values['custom_result_data_display'],
					'default'             => 'on',
					'options'             => array(
						''   => '',
						'on' => esc_html__( 'Enable the items you wish to display on the Result Page', 'learndash' ),
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['custom_result_data_display'] ) ? 'open' : 'closed',
				),

				'showAverageResult'          => array(
					'name'           => 'showAverageResult',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Average Score', 'learndash' ),
					'help_text'      => sprintf(
						// translators: placeholder: quiz.
						esc_html_x( 'Display the average score of all users who took the %s', 'placeholder: quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' )
					),
					'value'          => $this->setting_option_values['showAverageResult'],
					'default'        => 'on',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_result_data_display',
				),

				'showCategoryScore'          => array(
					'name'           => 'showCategoryScore',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Category Score', 'learndash' ),
					'help_text'      => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'Display the score achieved for each %s Category', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'value'          => $this->setting_option_values['showCategoryScore'],
					'default'        => 'on',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_result_data_display',
				),

				'hideResultPoints'           => array(
					'name'           => 'hideResultPoints',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Overall Score', 'learndash' ),
					'parent_setting' => 'custom_result_data_display',
					'value'          => $this->setting_option_values['hideResultPoints'],
					'default'        => 'on',
					'options'        => array(
						'on' => '',
						''   => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x( 'The achieved %s score is NOT be displayed on the Results page', 'placeholder: Quiz', 'learndash' ),
							learndash_get_custom_label( 'quiz' )
						),
					),
				),
				'hideResultCorrectQuestion'  => array(
					'name'           => 'hideResultCorrectQuestion',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'No. of Correct Answers', 'learndash' ),
					'parent_setting' => 'custom_result_data_display',
					'value'          => $this->setting_option_values['hideResultCorrectQuestion'],
					'default'        => 'on',
					'options'        => array(
						'on' => '',
						''   => sprintf(
							// translators: placeholder: Questions.
							esc_html_x( 'The number of correctly answered %s is NOT displayed on the Results page.', 'placeholder: Questions', 'learndash' ),
							learndash_get_custom_label( 'questions' )
						),
					),
				),

				'hideResultQuizTime'         => array(
					'name'           => 'hideResultQuizTime',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Time Spent', 'learndash' ),
					'parent_setting' => 'custom_result_data_display',
					'value'          => $this->setting_option_values['hideResultQuizTime'],
					'default'        => 'on',
					'options'        => array(
						'on' => '',
					),
				),

				'custom_answer_feedback'     => array(
					'name'                => 'custom_answer_feedback',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Custom Answer Feedback', 'learndash' ),
					'help_text'           => sprintf(
						// translators: placeholder: questions.
						esc_html_x( 'Select which data users should be able to view when reviewing their submitted %s.', 'placeholder: questions', 'learndash' ),
						learndash_get_custom_label_lower( 'questions' )
					),
					'value'               => $this->setting_option_values['custom_answer_feedback'],
					'default'             => 'on',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['custom_answer_feedback'] ) ? 'open' : 'closed',
				),

				'hideAnswerMessageBox'       => array(
					'name'           => 'hideAnswerMessageBox',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Correct / Incorrect Messages', 'learndash' ),
					'value'          => $this->setting_option_values['hideAnswerMessageBox'],
					'default'        => 'on',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_answer_feedback',
				),
				'disabledAnswerMark'         => array(
					'name'           => 'disabledAnswerMark',
					'type'           => 'checkbox-switch',
					'label'          => esc_html__( 'Correct / Incorrect Answer Marks', 'learndash' ),
					'value'          => $this->setting_option_values['disabledAnswerMark'],
					'default'        => 'on',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_answer_feedback',
				),
				'btnViewQuestionHidden'      => array(
					'name'           => 'btnViewQuestionHidden',
					'type'           => 'checkbox-switch',
					'label'          => sprintf(
						// translators: placeholder: Questions.
						esc_html_x( 'View %s Button', 'placeholder: Questions', 'learndash' ),
						learndash_get_custom_label( 'questions' )
					),
					'value'          => $this->setting_option_values['btnViewQuestionHidden'],
					'default'        => 'on',
					'options'        => array(
						'on' => '',
					),
					'parent_setting' => 'custom_answer_feedback',
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

				if ( ( isset( $settings_values['resultGradeEnabled'] ) ) && ( 'on' === $settings_values['resultGradeEnabled'] ) ) {
					$settings_values['resultGradeEnabled'] = true;
				} else {
					$settings_values['resultGradeEnabled'] = false;
				}

				//if ( isset( $_POST['resultTextGrade'] ) ) {
				//	if ( ( isset( $_POST['resultTextGrade']['text'][0] ) ) && ( ! empty( $_POST['resultTextGrade']['text'][0] ) ) ) {
				//		$settings_values['btnRestartQuizHidden'] = true;
				//	}
				//}

				if ( ( ! isset( $settings_values['btnRestartQuizHidden'] ) ) || ( 'on' === $settings_values['btnRestartQuizHidden'] ) ) {
					$settings_values['btnRestartQuizHidden'] = false;
				} else {
					$settings_values['btnRestartQuizHidden'] = true;
				}

				if ( ! isset( $settings_values['showAverageResult'] ) ) {
					$settings_values['showAverageResult'] = '';
				}

				if ( ! isset( $settings_values['showCategoryScore'] ) ) {
					$settings_values['showCategoryScore'] = '';
				}

				if ( ( ! isset( $settings_values['hideResultPoints'] ) ) || ( 'on' === $settings_values['hideResultPoints'] ) ) {
					$settings_values['hideResultPoints'] = false;
				} else {
					$settings_values['hideResultPoints'] = true;
				}

				if ( ( ! isset( $settings_values['hideResultCorrectQuestion'] ) ) || ( 'on' === $settings_values['hideResultCorrectQuestion'] ) ) {
					$settings_values['hideResultCorrectQuestion'] = false;
				} else {
					$settings_values['hideResultCorrectQuestion'] = true;
				}

				if ( ( ! isset( $settings_values['hideResultQuizTime'] ) ) || ( 'on' === $settings_values['hideResultQuizTime'] ) ) {
					$settings_values['hideResultQuizTime'] = false;
				} else {
					$settings_values['hideResultQuizTime'] = true;
				}

				if ( ( ! isset( $settings_values['hideAnswerMessageBox'] ) ) || ( 'on' === $settings_values['hideAnswerMessageBox'] ) ) {
					$settings_values['hideAnswerMessageBox'] = false;
				} else {
					$settings_values['hideAnswerMessageBox'] = true;
				}

				if ( ( ! isset( $settings_values['disabledAnswerMark'] ) ) || ( 'on' === $settings_values['disabledAnswerMark'] ) ) {
					$settings_values['disabledAnswerMark'] = false;
				} else {
					$settings_values['disabledAnswerMark'] = true;
				}

				if ( ( ! isset( $settings_values['btnViewQuestionHidden'] ) ) || ( 'on' === $settings_values['btnViewQuestionHidden'] ) ) {
					$settings_values['btnViewQuestionHidden'] = false;
				} else {
					$settings_values['btnViewQuestionHidden'] = true;
				}

				if ( ! isset( $settings_values['custom_answer_feedback'] ) ) {
					$settings_values['custom_answer_feedback'] = '';
				}
				if ( ! isset( $settings_values['custom_result_data_display'] ) ) {
					$settings_values['custom_result_data_display'] = '';
				}
			}

			return $settings_values;
		}

		public function get_custom_result_messages() {
			$result_text = $this->setting_option_values['resultText'];
			$html       = '';
			$level      = ob_get_level();
			ob_start();
			?>
			<div  id="learndash-quiz-resultList">
				<ul id="resultList">
				<?php
					$message_prozent_zero_found = false;
				for ( $i = 0; $i < LEARNDASH_QUIZ_RESULT_MESSAGE_MAX; $i++ ) {
					$message_text_value    = '';
					$message_prozent_value = 0;
					$message_activ_value   = 0;

					$message_show_style      = '';
					$message_editor_style    = '';
					$message_input_disabled  = '';
					$message_delete_enabled  = true;
					$message_arrow_direction = 'down';
					$message_editor_style    = 'display:none;';

					if ( isset( $result_text['text'][ $i ] ) ) {
						$message_text_value = $result_text['text'][ $i ];
					}

					if ( isset( $result_text['prozent'][ $i ] ) ) {
						$message_prozent_value = absint( $result_text['prozent'][ $i ] );
					}

					if ( isset( $result_text['activ'][ $i ] ) ) {
						$message_activ_value = absint( $result_text['activ'][ $i ] );
					}

					if ( true !== $message_prozent_zero_found ) {
						$message_input_disabled     = ' readonly ';
						$message_prozent_zero_found = true;
					} else {
						$message_input_disabled = '';
					}

					if ( empty( $message_activ_value ) ) {
						$message_show_style      = ' display:none;';
						$message_arrow_direction = 'up';
						$message_editor_style    = '';
						$message_prozent_value    = '1';
					} elseif ( 0 === $i ) {
						$message_delete_enabled  = false;
						$message_arrow_direction = 'up';
						$message_editor_style    = '';
					}

					?>
					<li style="<?php echo $message_show_style; ?>">
						<div class="resultHeader">
							<input type="hidden" value="<?php echo $message_activ_value; ?>" name="resultTextGrade[activ][]">
							<?php
								echo sprintf(
									// translators: placeholder: input form field.
									esc_html_x( 'From %s %% score, display this message:', 'placeholder: input form field', 'learndash' ),
									'<input type="number" ' . $message_input_disabled . ' name="resultTextGrade[prozent][]" min="1" max="100" step="1" class="-small small-text" value="' . $message_prozent_value . '">'
								);
							?>
							<div class="expand-arrow expand-arrow-<?php echo esc_attr( $message_arrow_direction ); ?>">
								<svg width="11" height="8" viewBox="0 0 14 8" xmlns="http://www.w3.org/2000/svg"><path d="M1 1l6 6 6-6" stroke="#0073aa" stroke-width="2" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round"></path></svg>
								</div>
								<?php if ( true === $message_delete_enabled ) { ?>
									<input type="button" value="<?php esc_html_e( 'Delete graduation', 'learndash' ); ?>" class="deleteResult">
								<?php } ?>
								<div style="clear: right;"></div>
							</div>
							<div class="resultEditor" style="<?php echo $message_editor_style; ?>">
							<?php
							wp_editor(
								$message_text_value,
								'resultText_' . $i,
								array(
									'textarea_rows' => 3,
									'textarea_name' => 'resultTextGrade[text][]',
								)
							);
							?>
							</div>
						</li>
						<?php
				}
				?>
				</ul>

				<input type="button" class="addResult" name="addResult" id="addResult" value="<?php esc_html_e( 'Add graduation', 'learndash' ); ?>">
			</div>
			<?php
			$html .= learndash_ob_get_clean( $level );

			return $html;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'quiz' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Quiz_Results_Options'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Quiz_Results_Options' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Quiz_Results_Options'] = LearnDash_Settings_Metabox_Quiz_Results_Options::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
