<?php
/**
 * LearnDash Settings Section for Assignments Custom Post Type Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Assignments_CPT' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Assignments_CPT extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {

			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-assignment_page_assignments-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'assignments-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_assignments_cpt';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_assignments_cpt';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'cpt_options';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Assignment Custom Post Type Options', 'learndash' );

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = esc_html__( 'Control the LearnDash Assignment Custom Post Type Options.', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$_INITIALIZE = false;
			if ( false === $this->setting_option_values ) {
				$_INITIALIZE                 = true;
				$this->setting_option_values = array(
					'exclude_from_search' => 'yes',
					'publicly_queryable'  => 'yes',
					'comment_status'      => 'yes',
				);
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'exclude_from_search' => array(
					'name'      => 'exclude_from_search',
					'type'      => 'checkbox',
					'label'     => esc_html__( 'Exclude From Search', 'learndash' ),
					'help_text' => esc_html__( 'Exclude From Search', 'learndash' ),
					'value'     => isset( $this->setting_option_values['exclude_from_search'] ) ? $this->setting_option_values['exclude_from_search'] : '',
					'options'   => array(
						'yes' => esc_html__( 'Exclude', 'learndash' ),
					),
				),
				'publicly_queryable'  => array(
					'name'      => 'publicly_queryable',
					'type'      => 'checkbox',
					'label'     => esc_html__( 'Publicly Viewable', 'learndash' ),
					'help_text' => esc_html__( 'Controls access to view single Assignments on the front-end.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['publicly_queryable'] ) ? $this->setting_option_values['publicly_queryable'] : '',
					'options'   => array(
						'yes' => esc_html__( 'Public', 'learndash' ),
					),
				),
				'comment_status'      => array(
					'name'      => 'comment_status',
					'type'      => 'checkbox',
					'label'     => esc_html__( 'Comments enabled', 'learndash' ),
					'help_text' => esc_html__( 'Controls if comments are enabled.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['comment_status'] ) ? $this->setting_option_values['comment_status'] : '',
					'options'   => array(
						'yes' => esc_html__( 'Enabled', 'learndash' ),
					),
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Assignments_CPT::add_section_instance();
	}
);
