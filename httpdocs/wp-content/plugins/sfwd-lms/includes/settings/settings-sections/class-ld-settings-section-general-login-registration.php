<?php
/**
 * LearnDash Settings Section for Login Registration Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_General_Login_Registration' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_General_Login_Registration extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_settings';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_login_registration';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_login_registration';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_login_registration';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Login / Registration Settings', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['login_logo'] ) ) {
				$this->setting_option_values['login_logo'] = 0;
			}

			if ( ! isset( $this->setting_option_values['login_logo2'] ) ) {
				$this->setting_option_values['login_logo2'] = 0;
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'login_logo'  => array(
					'name'              => 'login_logo',
					'type'              => 'media-upload',
					'label'             => esc_html__( 'Login Logo', 'learndash' ),
					/*
					'help_text' => sprintf(
						// translators: placeholder: Default per page number.
						esc_html_x( 'Default per page controls all shortcodes and widget. Default is %d. Set to zero for no pagination.', 'placeholder: Default per page', 'learndash' ),
						LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE
					),
					*/
					'value'             => $this->setting_option_values['login_logo'],
					'validate_callback' => array( $this, 'validate_section_field_media_upload' ),
					'validate_args'     => array(
						'allow_empty' => 1,
					),
				),
				'login_logo2' => array(
					'name'              => 'login_logo2',
					'type'              => 'media-upload',
					'label'             => esc_html__( 'Login Logo 2', 'learndash' ),
					'help_text'         => sprintf(
						// translators: placeholder: Default per page number.
						esc_html_x( 'Default per page controls all shortcodes and widget. Default is %d. Set to zero for no pagination.', 'placeholder: Default per page', 'learndash' ),
						LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE
					),
					'value'             => $this->setting_option_values['login_logo2'],
					'validate_callback' => array( $this, 'validate_section_field_media_upload' ),
					'validate_args'     => array(
						'allow_empty' => 1,
					),
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Validate settings field.
		 *
		 * @param string $val Value to be validated.
		 * @param string $key settings fields key.
		 * @param array  $args Settings field args array.
		 *
		 * @return integer $val.
		 */
		public function validate_section_field_media_upload( $val, $key, $args = array() ) {
			// Get the digits only.
			$val = absint( $val );
			if ( ( isset( $args['field']['validate_args']['allow_empty'] ) ) && ( true === $args['field']['validate_args']['allow_empty'] ) && ( empty( $val ) ) ) {
				$val = '';
			}
			return $val;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_General_Login_Registration::add_section_instance();
	}
);
