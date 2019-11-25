<?php
/**
 * LearnDash Settings Section Quiz Admin Email.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Quizzes_Admin_Email' ) ) ) {
	/**
	 * Class to create the Quiz Admin Email Section.
	 */
	class LearnDash_Settings_Quizzes_Admin_Email extends LearnDash_Settings_Section {

		/**
		 * Legacy WPProQuiz options key.
		 *
		 * @var string $legacy_options_key Value for WPProQuiz options key.
		 */
		private $legacy_options_key = 'wpProQuiz_emailSettings';

		/**
		 * This array provides a trision from the legacy WPProQuiz fields (right values )
		 * into the locally used field names ( left keys ).
		 *
		 * @var array $transition_settings Array contain local and WPProQuiz keys.
		 */
		private $legacy_transition_settings = array(
			'mail_to'         => 'to',
			'mail_from_name'  => 'from_name',
			'mail_from_email' => 'from',
			'mail_subject'    => 'subject',
			'mail_html'       => 'html',
			'mail_message'    => 'message',
		);

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz_page_quizzes-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'quizzes-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_quizzes_admin_email';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_quizzes_admin_email';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'quizzes_admin_email';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Admin Email Settings', 'placeholder: Quiz', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);

			parent::__construct();

			add_filter( 'learndash_settings_field_html_after', array( $this, 'learndash_settings_field_html_after' ), 10, 2 );
		}

		/**
		 * Load the field settings values
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( false === $this->setting_option_values ) {
				$this->setting_option_values = array();

				$wpproquiz_values = get_option( $this->legacy_options_key, array() );
				foreach ( $this->legacy_transition_settings as $local_key => $legacy_key ) {
					if ( isset( $wpproquiz_values[ $legacy_key ] ) ) {
						if ( 'html' === $legacy_key ) {
							if ( true === $wpproquiz_values[ $legacy_key ] ) {
								$wpproquiz_values[ $legacy_key ] = 'yes';
							}
						}
						$this->setting_option_values[ $local_key ] = $wpproquiz_values[ $legacy_key ];
					}
				}
			}
		}

		/**
		 * Load the field settings fields
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array(
				'mail_to'         => array(
					'name'      => 'mail_to',
					'type'      => 'text',
					'label'     => esc_html__( 'Mail To', 'learndash' ),
					'help_text' => esc_html__( 'Separate multiple email addresses with a comma, e.g. wp@test.com, test@test.com.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['mail_to'] ) ? $this->setting_option_values['mail_to'] : '',
				),
				'mail_from_name'  => array(
					'name'      => 'mail_from_name',
					'type'      => 'text',
					'label'     => esc_html__( 'From Name', 'learndash' ),
					'help_text' => esc_html__( 'This is the email name of the sender. If not provided will default to the system email name.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['mail_from_name'] ) ? $this->setting_option_values['mail_from_name'] : '',
				),
				'mail_from_email' => array(
					'name'      => 'mail_from_email',
					'type'      => 'email',
					'label'     => esc_html__( 'From Email', 'learndash' ),
					'help_text' => sprintf(
						wp_kses_post(
							// translators: placeholder: admin email.
							_x( 'This is the email address of the sender. If not provided the admin email <strong>(%s)</strong> will be used.', 'placeholder: admin email', 'learndash' )
						),
						get_option( 'admin_email' )
					),
					'value'     => isset( $this->setting_option_values['mail_from_email'] ) ? $this->setting_option_values['mail_from_email'] : '',
				),
				'mail_subject'    => array(
					'name'      => 'mail_subject',
					'type'      => 'text',
					'label'     => esc_html__( 'Mail Subject', 'learndash' ),
					'help_text' => esc_html__( 'The email subject the admin will see.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['mail_subject'] ) ? $this->setting_option_values['mail_subject'] : '',
				),
				'mail_html'       => array(
					'name'      => 'mail_html',
					'type'      => 'checkbox',
					'label'     => esc_html__( 'Use HTML?', 'learndash' ),
					'help_text' => esc_html__( 'Send email as HTML format.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['mail_html'] ) ? $this->setting_option_values['mail_html'] : '',
					'options'   => array(
						'yes' => esc_html__( 'Yes', 'learndash' ),
					),
				),
				'mail_message'    => array(
					'name'        => 'mail_message',
					'type'        => 'wpeditor',
					'label'       => esc_html__( 'Message', 'learndash' ),
					'value'       => isset( $this->setting_option_values['mail_message'] ) ? stripslashes( $this->setting_option_values['mail_message'] ) : '',
					'editor_args' => array(
						'textarea_name' => $this->setting_option_key . '[mail_message]',
						'textarea_rows' => 5,
						'editor_class'  => 'learndash_mail_message ' . $this->setting_option_key . '_mail_message',
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
		public function learndash_settings_field_html_after( $html = '', $field_args = array() ) {
			/**
			 * Here we hook into the bottom of the field HTML output and add some inline JS to handle the
			 * change event on the radio buttons. This is really just to update the 'custom' input field
			 * display.
			 */
			if ( ( isset( $field_args['setting_option_key'] ) ) && ( $this->setting_option_key === $field_args['setting_option_key'] ) ) {
				if ( ( isset( $field_args['name'] ) ) && ( 'mail_message' === $field_args['name'] ) ) {
					$html .= '<div>
								<h4>' . esc_html__( 'Allowed variables', 'learndash' ) . ':</h4>
								<ul>
									<li><span>$userId</span> - ' . esc_html__( 'User-ID', 'learndash' ) . '</li>
									<li><span>$username</span> - ' . esc_html__( 'Username', 'learndash' ) . '</li>
									<li><span>$quizname</span> - ' . esc_html__( 'Quiz-Name', 'learndash' ) . '</li>
									<li><span>$result</span> - ' . esc_html__( 'Result in percent', 'learndash' ) . '</li>
									<li><span>$points</span> - ' . esc_html__( 'Reached points', 'learndash' ) . '</li>
									<li><span>$ip</span> - ' . esc_html__( 'IP-address of the user', 'learndash' ) . '</li>
									<li><span>$categories</span> - ' . esc_html__( 'Category-Overview', 'learndash' ) . '</li>
								</ul>	
							</div>';
				}
			}
			return $html;
		}

		/**
		 * Custom save function because we need to update the WPProQuiz settings with the saved value.
		 */
		public function save_settings_fields() {
			if ( isset( $_POST[ $this->setting_option_key ] ) ) {
				$settings_values = array();

				foreach ( $this->legacy_transition_settings as $local_key => $legacy_key ) {
					$settings_values[ $legacy_key ] = '';
					if ( isset( $_POST[ $this->setting_option_key ][ $local_key ] ) ) {
						$settings_values[ $legacy_key ] = $_POST[ $this->setting_option_key ][ $local_key ];
					}
				}
				//error_log('_POST<pre>'. print_r($_POST, true) .'</pre>');
				//error_log('settings_values<pre>'. print_r($settings_values, true) .'</pre>');

				update_option( $this->legacy_options_key, $settings_values );
			}
		}

		// End of functions.
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Quizzes_Admin_Email::add_section_instance();
	}
);
