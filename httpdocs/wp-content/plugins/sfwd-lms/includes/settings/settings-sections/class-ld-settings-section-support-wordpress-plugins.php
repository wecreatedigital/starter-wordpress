<?php
/**
 * LearnDash Settings Section for Support WordPress Plugins Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_WordPress_Plugins' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Support_WordPress_Plugins extends LearnDash_Settings_Section {

		/**
		 * Settings set array for this section.
		 *
		 * @var array $settings_set Array of settings used by this section.
		 */
		protected $settings_set = array();

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_support';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'wp_active_plugins';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_wp_active_plugins';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'WordPress Active Plugins', 'learndash' );

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			$ABSPATH_tmp = str_replace( '\\', '/', ABSPATH );


			/************************************************************************************************
			 * WordPress Active Plugins.
			 ************************************************************************************************/
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {
				$this->settings_set           = array();
				
				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);

				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Plugin', 'learndash' ),
						'text'  => 'Plugin',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Details', 'learndash' ),
						'text'  => 'Details',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['settings'] = array();

				$current_plugins = get_site_transient( 'update_plugins' );

				$all_plugins = get_plugins();

				if ( ! empty( $all_plugins ) ) {
					foreach ( $all_plugins as $plugin_key => $plugin_data ) {
						if ( is_plugin_active( $plugin_key ) ) {

							$plugin_value      = 'Version: ' . $plugin_data['Version'];
							$plugin_value_html = esc_html__( 'Version', 'learndash' ) . ': ' . $plugin_data['Version'];

							if ( isset( $current_plugins->response[ $plugin_key ] ) ) {
								if ( version_compare( $plugin_data['Version'], $current_plugins->response[ $plugin_key ]->new_version, '<' ) ) {
									$plugin_value      .= ' Update available: ' . $current_plugins->response[ $plugin_key ]->new_version . ' (X)';
									$plugin_value_html .= ' <span style="color:red;">' . esc_html__( 'Update available', 'learndash' ) . ': ' . $current_plugins->response[ $plugin_key ]->new_version . '</span>';
								}
							}

							$plugin_value      .= ' Path: ' . $plugin_data['PluginURI'];
							$plugin_value_html .= '<br />' . esc_html__( 'Path', 'learndash' ) . ': ' . $plugin_data['PluginURI'];

							$this->settings_set['settings'][ $plugin_key ] = array(
								'label'      => $plugin_data['Name'],
								'value'      => $plugin_value,
								'value_html' => $plugin_value_html,
							);
						}
					}
				}

				$support_sections[ $this->setting_option_key ] = apply_filters( 'learndash_support_section', $this->settings_set, $this->setting_option_key );
			}

			return $support_sections;
		}

		public function show_support_section( $settings_section_key = '', $settings_screen_id = '' ) {
			if ( $settings_section_key === $this->settings_section_key ) {
				$support_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash_Settings_Page_Support' );
				if ( $support_page_instance ) {
					$support_page_instance->show_support_section( $this->setting_option_key );
				}
			}
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Support_WordPress_Plugins::add_section_instance();
	}
);
