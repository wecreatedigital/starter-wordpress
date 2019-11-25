<?php
/**
 * LearnDash Settings Section Quiz Email Settings.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Quizzes_Email' ) ) ) {
	/**
	 * Class to create the Quiz Email Section.
	 */
	class LearnDash_Settings_Quizzes_Email extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz_page_quizzes-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'quizzes-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_quizzes_email';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_quizzes_email';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'quizzes_email';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Email Settings', 'placeholder: Quiz', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);

			$this->settings_section_description = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( 'Control the %s email notification options', 'placeholder: Quiz', 'learndash' ),
				learndash_get_custom_label_lower( 'quiz' )
			);

			// Define the depreacted Class and Fields
			$this->settings_deprecated = array(
				'LearnDash_Settings_Quizzes_Admin_Email' => array(
					'option_key' => 'learndash_settings_quizzes_admin_email',
					'fields'     => array(
						'mail_to'         => 'admin_mail_to',
						'mail_from_name'  => 'admin_mail_from_name',
						'mail_from_email' => 'admin_mail_from_email',
						'mail_subject'    => 'admin_mail_subject',
						'mail_html'       => 'admin_mail_html',
						'mail_message'    => 'admin_mail_message',
					),
				),
				'LearnDash_Settings_Quizzes_User_Email'  => array(
					'option_key' => 'learndash_settings_quizzes_user_email',
					'fields'     => array(
						'mail_from_name'  => 'user_mail_from_name',
						'mail_from_email' => 'user_mail_from_email',
						'mail_subject'    => 'user_mail_subject',
						'mail_html'       => 'user_mail_html',
						'mail_message'    => 'user_mail_message',
					),
				),
			);

			add_filter( 'learndash_settings_row_outside_after', array( $this, 'learndash_settings_row_outside_after' ), 10, 2 );
			add_filter( 'learndash_settings_row_outside_before', array( $this, 'learndash_settings_row_outside_before' ), 30, 2 );

			parent::__construct();
		}

		/**
		 * Load the field settings values
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			// If the settings set as a whole is empty then we set a default.
			if ( empty( $this->setting_option_values ) ) {
				// If the settings set as a whole is empty then we set a default.
				if ( false === $this->setting_option_values ) {
					$this->transition_deprecated_settings();
				}
			}

			if ( ! isset( $this->setting_option_values['admin_mail_from_name'] ) ) {
				$this->setting_option_values['admin_mail_from_name'] = '';
			}

			if ( ! isset( $this->setting_option_values['admin_mail_from_email'] ) ) {
				$this->setting_option_values['admin_mail_from_email'] = '';
			}

			if ( ! isset( $this->setting_option_values['admin_mail_to'] ) ) {
				$this->setting_option_values['admin_mail_to'] = '';
			}

			if ( ! isset( $this->setting_option_values['admin_mail_subject'] ) ) {
				$this->setting_option_values['admin_mail_subject'] = '';
			}

			if ( ! isset( $this->setting_option_values['admin_mail_message'] ) ) {
				$this->setting_option_values['admin_mail_message'] = '';
			}

			if ( ! isset( $this->setting_option_values['admin_mail_html'] ) ) {
				$this->setting_option_values['admin_mail_html'] = '';
			}
		}

		/**
		 * Load the field settings fields
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array(
				'admin_mail_from_name'  => array(
					'name'      => 'admin_mail_from_name',
					'type'      => 'text',
					'label'     => esc_html__( 'From Name', 'learndash' ),
					'help_text' => esc_html__( 'This is the name of the sender. If not provided will default to the system email name.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['admin_mail_from_name'] ) ? $this->setting_option_values['admin_mail_from_name'] : '',
				),
				'admin_mail_from_email' => array(
					'name'      => 'admin_mail_from_email',
					'type'      => 'email',
					'label'     => esc_html__( 'From Email', 'learndash' ),
					'help_text' => sprintf(
						// translators: placeholder: admin email.
						esc_html_x( 'This is the email address of the sender. If not provided the admin email %s will be used.', 'placeholder: admin email', 'learndash' ),
						'(<strong>' . get_option( 'admin_email' ) . '</strong>)'
					),
					'value'     => isset( $this->setting_option_values['admin_mail_from_email'] ) ? $this->setting_option_values['admin_mail_from_email'] : '',
				),
				'admin_mail_to'         => array(
					'name'      => 'admin_mail_to',
					'type'      => 'text',
					'label'     => esc_html__( 'Mail To', 'learndash' ),
					'help_text' => esc_html__( 'Separate multiple email addresses with a comma, e.g. wp@test.com, test@test.com.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['admin_mail_to'] ) ? $this->setting_option_values['admin_mail_to'] : '',
				),
				'admin_mail_subject'    => array(
					'name'  => 'admin_mail_subject',
					'type'  => 'text',
					'label' => esc_html__( 'Subject', 'learndash' ),
					'value' => isset( $this->setting_option_values['admin_mail_subject'] ) ? $this->setting_option_values['admin_mail_subject'] : '',
				),
				'admin_mail_message'    => array(
					'name'              => 'admin_mail_message',
					'type'              => 'wpeditor',
					'label'             => esc_html__( 'Message', 'learndash' ),
					'value'             => isset( $this->setting_option_values['admin_mail_message'] ) ? stripslashes( $this->setting_option_values['admin_mail_message'] ) : '',
					'editor_args'       => array(
						'textarea_name' => $this->setting_option_key . '[admin_mail_message]',
						'textarea_rows' => 5,
						'editor_class'  => 'learndash_mail_message ' . $this->setting_option_key . '_admin_mail_message',
					),
					'label_description' => '<div>
						<h4>' . esc_html__( 'Supported variables', 'learndash' ) . ':</h4>
						<ul>
							<li><span>$userId</span> - ' . esc_html__( 'User-ID', 'learndash' ) . '</li>
							<li><span>$username</span> - ' . esc_html__( 'Username', 'learndash' ) . '</li>
							<li><span>$quizname</span> - ' . esc_html__( 'Quiz-Name', 'learndash' ) . '</li>
							<li><span>$result</span> - ' . esc_html__( 'Result in percent', 'learndash' ) . '</li>
							<li><span>$points</span> - ' . esc_html__( 'Reached points', 'learndash' ) . '</li>
							<li><span>$ip</span> - ' . esc_html__( 'IP-address of the user', 'learndash' ) . '</li>
							<li><span>$categories</span> - ' . esc_html__( 'Category-Overview', 'learndash' ) . '</li>
						</ul>	
					</div>',
				),
				'admin_mail_html'       => array(
					'name'    => 'admin_mail_html',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'Allow HTML', 'learndash' ),
					'value'   => isset( $this->setting_option_values['admin_mail_html'] ) ? $this->setting_option_values['admin_mail_html'] : '',
					'options' => array(
						'yes' => '',
					),
				),

				'user_mail_from_name'   => array(
					'name'      => 'user_mail_from_name',
					'type'      => 'text',
					'label'     => esc_html__( 'From Name', 'learndash' ),
					'help_text' => esc_html__( 'This is the name of the sender. If not provided will default to the system email name.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['admin_mail_from_name'] ) ? $this->setting_option_values['admin_mail_from_name'] : '',
				),
				'user_mail_from_email'  => array(
					'name'      => 'user_mail_from_email',
					'type'      => 'email',
					'label'     => esc_html__( 'From Email', 'learndash' ),
					'help_text' => sprintf(
						// translators: placeholder: admin email.
						esc_html_x( 'This is the email address of the sender. If not provided the admin email %s will be used.', 'placeholder: admin email', 'learndash' ),
						'(<strong>' . get_option( 'admin_email' ) . '</strong>)'
					),
					'value'     => isset( $this->setting_option_values['user_mail_from_email'] ) ? $this->setting_option_values['user_mail_from_email'] : '',
				),
				'user_mail_subject'     => array(
					'name'  => 'user_mail_subject',
					'type'  => 'text',
					'label' => esc_html__( 'Subject', 'learndash' ),
					'value' => isset( $this->setting_option_values['user_mail_subject'] ) ? $this->setting_option_values['user_mail_subject'] : '',
				),
				'user_mail_message'     => array(
					'name'              => 'user_mail_message',
					'type'              => 'wpeditor',
					'label'             => esc_html__( 'Message', 'learndash' ),
					'value'             => isset( $this->setting_option_values['user_mail_message'] ) ? stripslashes( $this->setting_option_values['user_mail_message'] ) : '',
					'editor_args'       => array(
						'textarea_name' => $this->setting_option_key . '[user_mail_message]',
						'textarea_rows' => 5,
						'editor_class'  => 'learndash_mail_message ' . $this->setting_option_key . '_user_mail_message',
					),
					'label_description' => '<div>
						<h4>' . esc_html__( 'Supported variables', 'learndash' ) . ':</h4>
						<ul>
							<li><span>$userId</span> - ' . esc_html__( 'User-ID', 'learndash' ) . '</li>
							<li><span>$username</span> - ' . esc_html__( 'Username', 'learndash' ) . '</li>
							<li><span>$quizname</span> - ' . esc_html__( 'Quiz-Name', 'learndash' ) . '</li>
							<li><span>$result</span> - ' . esc_html__( 'Result in percent', 'learndash' ) . '</li>
							<li><span>$points</span> - ' . esc_html__( 'Reached points', 'learndash' ) . '</li>
							<li><span>$ip</span> - ' . esc_html__( 'IP-address of the user', 'learndash' ) . '</li>
							<li><span>$categories</span> - ' . esc_html__( 'Category-Overview', 'learndash' ) . '</li>
						</ul>	
					</div>',
				),
				'user_mail_html'        => array(
					'name'    => 'user_mail_html',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'Allow HTML', 'learndash' ),
					'value'   => isset( $this->setting_option_values['user_mail_html'] ) ? $this->setting_option_values['user_mail_html'] : '',
					'options' => array(
						'yes' => '',
					),
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Hook into action after the fieldset is output. This allows adding custom content like JS/CSS.
		 *
		 * @since 2.5.9
		 *
		 * @param string $html This is the field output which will be send to the screen.
		 * @param array  $field_args Array of field args used to build the field HTML.
		 *
		 * @return string $html.
		 */
		public function learndash_settings_row_outside_after( $html = '', $field_args = array() ) {
			/**
			 * Here we hook into the bottom of the field HTML output and add some inline JS to handle the
			 * change event on the radio buttons. This is really just to update the 'custom' input field
			 * display.
			 */
			if ( ( isset( $field_args['setting_option_key'] ) ) && ( $this->setting_option_key === $field_args['setting_option_key'] ) ) {
				if ( ( isset( $field_args['name'] ) ) && ( 'admin_mail_html' === $field_args['name'] ) ) {
					$html .= '<div class="ld-divider"></div>';
				}
			}
			return $html;
		}

		/**
		 * Add Header and description on email sections.
		 */
		public function learndash_settings_row_outside_before( $content = '', $field_args = array() ) {
			if ( ( isset( $field_args['name'] ) ) && ( in_array( $field_args['name'], array( 'admin_mail_from_name', 'user_mail_from_name' ) ) ) ) {
				if ( 'admin_mail_from_name' === $field_args['name'] ) {
					$content .= '<div class="ld-settings-email-header-wrapper">';

					$content .= '<div class="ld-settings-email-header">';
					$content .= esc_html__( 'ADMIN NOTIFICATIONS', 'learndash' );
					$content .= '</div>';

					$content .= '<div class="ld-settings-email-description">';
					$content .= sprintf(
						// translators: placeholder: quiz, quiz.
						esc_html_x( 'Manage the email content that will be sent out to the admin, group leader or other supervisors when a user completes a %1$s. You must enable "Admin Notification" on a per %2$s basis.', 'placeholder: quiz, quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label_lower( 'quiz' )
					);
					$content .= '</div>';

					$content .= '</div>';
				}

				if ( 'user_mail_from_name' === $field_args['name'] ) {
					$content .= '<div class="ld-settings-email-header-wrapper">';

					$content .= '<div class="ld-settings-email-header">';
					$content .= esc_html__( 'USER NOTIFICATIONS', 'learndash' );
					$content .= '</div>';

					$content .= '<div class="ld-settings-email-description">';
					$content .= sprintf(
						// translators: placeholder: quiz, quiz.
						esc_html_x( 'Manage the email content that will be sent out to the user when a %1$s is completed. You must enable "User Notification" on a per %2$s basis.', 'placeholder: quiz, quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label_lower( 'quiz' )
					);
					$content .= '</div>';

					$content .= '</div>';
				}
			}
			return $content;
		}

		// End of functions.
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Quizzes_Email::add_section_instance();
	}
);
