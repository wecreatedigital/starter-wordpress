<?php
/**
 * LearnDash Settings Section Quiz Template Mangement.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Quizzes_Template_Management' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Quizzes_Template_Management extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz_page_quizzes-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'quizzes-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_quizzes_template_management';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_quizzes_template_management';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'quizzes_template_management';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Template Management', 'placeholder: Quiz', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);

			parent::__construct();

			// Hook to handle the AJAX delete/update actions.
			add_action( 'wp_ajax_' . $this->setting_field_prefix, array( $this, 'ajax_action' ) );
		}

		/**
		 * Load the field settings values
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$this->setting_option_values = array(
				'quiz_templates' => array(
					'' => __( 'Select a template', 'learndash' ),
				),
			);

			if ( ( is_admin() ) && ( isset( $_GET['page'] ) ) && ( 'quizzes-options' === $_GET['page'] ) ) {
				$template_mapper = new WpProQuiz_Model_TemplateMapper();
				$quiz_templates  = $template_mapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false );
				if ( ( ! empty( $quiz_templates ) ) && ( is_array( $quiz_templates ) ) ) {
					foreach ( $quiz_templates as $template_quiz ) {
						$template_name = $template_quiz->getName();
						$template_id   = $template_quiz->getTemplateId();

						if ( ! empty( $template_name ) ) {
							$this->setting_option_values['quiz_templates'][ $template_id ] = esc_html( $template_name );
						}
					}
				}
			}
		}

		/**
		 * Load the field settings fields
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array(
				'quiz_template' => array(
					'name'      => 'quiz_template',
					'type'      => 'select-edit-delete',
					'label'     => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s templates', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'help_text' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Manage %s templates. Select a template then update the title or delete.', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'     => '',
					'options'   => $this->setting_option_values['quiz_templates'],
					'buttons'   => array(
						'delete' => esc_html__( 'Delete', 'learndash' ),
						'update' => esc_html__( 'Update', 'learndash' ),
					),
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * This function handles the AJAX actions from the browser.
		 *
		 * @since 2.5.9
		 */
		public function ajax_action() {
			$reply_data = array( 'status' => false );

			if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) {
				if ( ( isset( $_POST['field_nonce'] ) ) && ( ! empty( $_POST['field_nonce'] ) ) && ( isset( $_POST['field_key'] ) ) && ( ! empty( $_POST['field_key'] ) ) && ( wp_verify_nonce( esc_attr( $_POST['field_nonce'] ), $_POST['field_key'] ) ) ) {

					if ( isset( $_POST['field_action'] ) ) {
						if ( 'update' === $_POST['field_action'] ) {
							if ( ( isset( $_POST['field_value'] ) ) && ( ! empty( $_POST['field_value'] ) ) && ( isset( $_POST['field_text'] ) ) && ( ! empty( $_POST['field_text'] ) ) ) {
								$template_id       = intval( $_POST['field_value'] );
								$template_new_name = esc_attr( $_POST['field_text'] );

								$template_mapper = new WpProQuiz_Model_TemplateMapper();
								$template        = $template_mapper->fetchById( $template_id );
								if ( ( $template ) && ( is_a( $template, 'WpProQuiz_Model_Template' ) ) ) {
									$template_current_name = $template->getName();
									if ( $template_current_name !== $template_new_name ) {
										$update_ret = $template_mapper->updateName( $template_id, $template_new_name );
										if ( $update_ret ) {
											$reply_data['status']  = true;
											$reply_data['message'] = '<span style="color: green" >' . __( 'Template updated.', 'learndash' ) . '</span>';
										}
									}
								}
							}
						} elseif ( 'delete' === $_POST['field_action'] ) {
							if ( ( isset( $_POST['field_value'] ) ) && ( ! empty( $_POST['field_value'] ) ) ) {
								$template_id = intval( $_POST['field_value'] );

								$template_mapper = new WpProQuiz_Model_TemplateMapper();
								$template        = $template_mapper->fetchById( $template_id );
								if ( ( $template ) && ( is_a( $template, 'WpProQuiz_Model_Template' ) ) ) {
									$update_ret = $template_mapper->delete( $template_id );
									if ( $update_ret ) {
										$reply_data['status']  = true;
										$reply_data['message'] = '<span style="color: green" >' . __( 'Template deleted.', 'learndash' ) . '</span>';
									}
								}
							}
						}
					}
				}
			}

			if ( ! empty( $reply_data ) ) {
				echo wp_json_encode( $reply_data );
			}

			wp_die(); // This is required to terminate immediately and return a proper response.

		}

		// End of functions.
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Quizzes_Template_Management::add_section_instance();
	}
);
