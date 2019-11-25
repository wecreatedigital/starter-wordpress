<?php
/**
 * LearnDash Settings Section for Question Taxonomies Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Questions_Taxonomies' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Questions_Taxonomies extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {

			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-question_page_questions-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'questions-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_questions_taxonomies';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_questions_taxonomies';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'taxonomies';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Taxonomies', 'placeholder: Question', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'question' )
			);

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: Quiz, Questions.
				esc_html_x( 'Control which taxonomies can be used with the LearnDash %1$s %2$s.', 'placeholder: Quiz, Questions', 'learndash' ),
				learndash_get_custom_label( 'quiz' ),
				learndash_get_custom_label_lower( 'questions' )
			);

			parent::__construct();

			// Hook to handle the AJAX delete/update actions.
			add_action( 'wp_ajax_' . $this->setting_field_prefix, array( $this, 'ajax_action' ) );
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$_init = false;
			if ( false === $this->setting_option_values ) {
				$_init                       = true;
				$this->setting_option_values = array(
					'proquiz_question_category' => 'yes',
					'ld_question_category'      => 'no',
					'ld_question_tag'           => 'no',
					'wp_post_category'          => 'no',
					'wp_post_tag'               => 'no',
				);

				// If this is a new install we want to turn off WP Post Category/Tag.
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php';
				$this->ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();

				$ld_prior_version = $this->ld_admin_data_upgrades->get_data_settings( 'prior_version' );
				if ( 'new' === $ld_prior_version ) {
					$this->setting_option_values['wp_post_category'] = 'no';
					$this->setting_option_values['wp_post_tag']      = 'no';
				}
			}

			$this->setting_option_values = wp_parse_args(
				$this->setting_option_values,
				array(
					'proquiz_question_category' => 'yes',
					'ld_question_category'      => 'no',
					'ld_question_tag'           => 'no',
					'wp_post_category'          => 'no',
					'wp_post_tag'               => 'no',
					'question_category'         => array(
						'' => esc_html__( 'Select a category', 'learndash' ),
					),
				)
			);

			if ( ( is_admin() ) && ( isset( $_GET['page'] ) ) && ( 'questions-options' === $_GET['page'] ) ) {
				$category_mapper     = new WpProQuiz_Model_CategoryMapper();
				$question_categories = $category_mapper->fetchAll();
				if ( ( ! empty( $question_categories ) ) && ( is_array( $question_categories ) ) ) {
					foreach ( $question_categories as $question_category ) {
						$category_name = $question_category->getCategoryName();
						$category_id   = $question_category->getCategoryId();

						if ( ! empty( $category_name ) ) {
							$this->setting_option_values['question_category'][ $category_id ] = esc_html( $category_name );
						}
					}
				}
			}

		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				/*
				'ld_question_category' => array(
					'name' => 'ld_question_category',
					'type' => 'checkbox',
					'label' => sprintf(
						// translators: placeholder: Question.
                        esc_html_x( 'LearnDash %s Categories', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'help_text' => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'Enable the builtin LearnDash %s Categories', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'value' => $this->setting_option_values['ld_question_category'],
					'options' => array(
						'yes' => esc_html__( 'Yes', 'learndash' ),
					),
				),
				*/
				/*
				'ld_question_tag' => array(
					'name' => 'ld_question_tag',
					'type' => 'checkbox',
					'label' => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'LearnDash %s Tags', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'help_text' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Enable the builtin LearnDash %s Tags', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'value' => $this->setting_option_values['ld_question_tag'],
					'options' => array(
						'yes' => esc_html__( 'Yes', 'learndash' ),
					),
				),
				*/
				/*
				'wp_post_category' => array(
					'name' => 'wp_post_category',
					'type' => 'checkbox',
					'label' => esc_html__( 'WordPress Post Categories', 'learndash' ),
					'help_text' => esc_html__( 'Enable the builtin WordPress Post Categories', 'learndash' ),
					'value' => $this->setting_option_values['wp_post_category'],
					'options' => array(
						'yes' => esc_html__( 'Yes', 'learndash' ),
					),
				),
				*/
				/*
				'wp_post_tag' => array(
					'name' => 'wp_post_tag',
					'type' => 'checkbox',
					'label' => esc_html__( 'WordPress Post Tags', 'learndash' ),
					'help_text' => esc_html__( 'Enable the builtin WordPress Post Tags', 'learndash' ),
					'value' => $this->setting_option_values['wp_post_tag'],
					'options' => array(
						'yes' => esc_html__( 'Yes', 'learndash' ),
					),
				),
				*/
				'proquiz_question_category' => array(
					'name'                => 'proquiz_question_category',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Categories', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'value'               => $this->setting_option_values['proquiz_question_category'],
					'options'             => array(
						'yes' => esc_html__( 'Yes', 'learndash' ),
					),
					'options'             => array(
						'yes' => array(
							'tooltip' => sprintf(
								// translators: placeholder: Question.
								esc_html_x( '%s categories cannot be disabled.', 'placeholder: Question', 'learndash' ),
								learndash_get_custom_label( 'question' )
							),
						),
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['proquiz_question_category'] ) ? 'open' : 'closed',
					'attrs'               => array(
						'disabled' => 'disabled',
					),
				),
				'_question_category'        => array(
					'name'           => 'question_category',
					'type'           => 'select-edit-delete',
					'label'          => esc_html__( 'Category management', 'learndash' ),
					'help_text'      => esc_html__( 'Select a category to update or delete the title.', 'learndash' ),
					'value'          => '',
					'options'        => $this->setting_option_values['question_category'],
					'buttons'        => array(
						'delete' => esc_html__( 'Delete', 'learndash' ),
						'update' => esc_html__( 'Update', 'learndash' ),
					),
					'parent_setting' => 'proquiz_question_category',
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
								$category_id       = intval( $_POST['field_value'] );
								$category_new_name = esc_attr( $_POST['field_text'] );

								$category_mapper = new WpProQuiz_Model_CategoryMapper();
								$category        = $category_mapper->fetchById( $category_id );
								if ( ( $category ) && ( is_a( $category, 'WpProQuiz_Model_Category' ) ) ) {
									$category_current_name = $category->getCategoryName();
									if ( $category_current_name !== $category_new_name ) {
										$update_ret = $category_mapper->updateCatgoryName( $category_id, $category_new_name );
										if ( $update_ret ) {
											$reply_data['status']  = true;
											$reply_data['message'] = '<span style="color: green" >' . __( 'Category updated.', 'learndash' ) . '</span>';
										}
									}
								}
							}
						} elseif ( 'delete' === $_POST['field_action'] ) {
							if ( ( isset( $_POST['field_value'] ) ) && ( ! empty( $_POST['field_value'] ) ) ) {
								$category_id = intval( $_POST['field_value'] );

								$category_mapper = new WpProQuiz_Model_CategoryMapper();
								$category        = $category_mapper->fetchById( $category_id );
								if ( ( $category ) && ( is_a( $category, 'WpProQuiz_Model_Category' ) ) ) {
									$update_ret = $category_mapper->delete( $category_id );
									if ( $update_ret ) {
										$reply_data['status']  = true;
										$reply_data['message'] = '<span style="color: green" >' . __( 'Category deleted.', 'learndash' ) . '</span>';
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
		LearnDash_Settings_Questions_Taxonomies::add_section_instance();
	}
);
