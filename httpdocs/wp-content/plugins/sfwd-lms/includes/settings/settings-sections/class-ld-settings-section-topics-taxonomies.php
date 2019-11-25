<?php
/**
 * LearnDash Settings Section for Topics Taxonomies Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Topics_Taxonomies' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Topics_Taxonomies extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-topic_page_topics-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'topics-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_topics_taxonomies';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_topics_taxonomies';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'taxonomies';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Topic.
				esc_html_x( '%s Taxonomies', 'placeholder: Topic', 'learndash' ),
				learndash_get_custom_label( 'topic' )
			);

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: topics.
				esc_html_x( 'Control which Taxonomies can be used with the LearnDash %s.', 'placeholder: topics', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'topics' )
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$_init = false;
			if ( false === $this->setting_option_values ) {
				$__init                      = true;
				$this->setting_option_values = array(
					'ld_topic_category' => 'yes',
					'ld_topic_tag'      => 'yes',
					'wp_post_category'  => '',
					'wp_post_tag'       => '',
				);

				// If this is a new install we want to turn off WP Post Category/Tag.
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php';
				$this->ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();

				$ld_prior_version = $this->ld_admin_data_upgrades->get_data_settings( 'prior_version' );
				if ( 'new' === $ld_prior_version ) {
					$this->setting_option_values['wp_post_category'] = '';
					$this->setting_option_values['wp_post_tag']      = '';
				}
			}

			$this->setting_option_values = wp_parse_args(
				$this->setting_option_values,
				array(
					'ld_topic_category' => '',
					'ld_topic_tag'      => '',
					'wp_post_category'  => '',
					'wp_post_tag'       => '',
				)
			);
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'ld_topic_category' => array(
					'name'    => 'ld_topic_category',
					'type'    => 'checkbox-switch',
					'label'   => sprintf(
						// translators: placeholder: Topic.
						esc_html_x( '%s Categories', 'placeholder: Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'value'   => $this->setting_option_values['ld_topic_category'],
					'options' => array(
						''    => '',
						'yes' => sprintf(
							// translators: placeholder: Topic.
							esc_html_x( 'Manage %s Categories via the Actions dropdown', 'placeholder: Topic', 'learndash' ),
							learndash_get_custom_label( 'topic' )
						),
					),
				),
				'ld_topic_tag'      => array(
					'name'    => 'ld_topic_tag',
					'type'    => 'checkbox-switch',
					'label'   => sprintf(
						// translators: placeholder: Topic.
						esc_html_x( '%s Tags', 'placeholder: Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'value'   => $this->setting_option_values['ld_topic_tag'],
					'options' => array(
						''    => '',
						'yes' => sprintf(
							// translators: placeholder: Topic.
							esc_html_x( 'Manage %s Tags via the Actions dropdown', 'placeholder: Topic', 'learndash' ),
							learndash_get_custom_label( 'topic' )
						),
					),
				),
				'wp_post_category'  => array(
					'name'    => 'wp_post_category',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'WP Post Categories', 'learndash' ),
					'value'   => $this->setting_option_values['wp_post_category'],
					'options' => array(
						''    => '',
						'yes' => esc_html__( 'Manage WP Categories via the Actions dropdown', 'learndash' ),
					),
				),
				'wp_post_tag'       => array(
					'name'    => 'wp_post_tag',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'WP Post Tags', 'learndash' ),
					'value'   => $this->setting_option_values['wp_post_tag'],
					'options' => array(
						''    => '',
						'yes' => esc_html__( 'Manage WP Tags via the Actions dropdown', 'learndash' ),
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
		LearnDash_Settings_Topics_Taxonomies::add_section_instance();
	}
);
