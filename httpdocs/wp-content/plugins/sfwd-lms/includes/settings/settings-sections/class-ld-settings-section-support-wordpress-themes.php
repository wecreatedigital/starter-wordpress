<?php
/**
 * LearnDash Settings Section for Support WordPress Themes Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_WordPress_Themes' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Support_WordPress_Themes extends LearnDash_Settings_Section {

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
			$this->setting_option_key = 'wp_active_theme';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_wp_active_theme';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'WordPress Active Theme', 'learndash' );

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			$ABSPATH_tmp = str_replace( '\\', '/', ABSPATH );

			/************************************************************************************************
			 * WordPress Active Theme.
			 ************************************************************************************************/
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {
				$this->settings_set           = array();
				
				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);

				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Theme', 'learndash' ),
						'text'  => 'Theme',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Details', 'learndash' ),
						'text'  => 'Details',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['settings'] = array();

				$current_theme = wp_get_theme();

				if ( $current_theme->exists() ) {
					$theme_stylesheet = $current_theme->get_stylesheet();

					$themes_update = get_site_transient( 'update_themes' );

					$theme_value      = 'Version: ' . $current_theme->get( 'Version' );
					$theme_value_html = esc_html__( 'Version', 'learndash' ) . ': ' . $current_theme->get( 'Version' );

					if ( isset( $themes_update->response[ $theme_stylesheet ] ) ) {
						if ( version_compare( $current_theme->get( 'Version' ), $themes_update->response[ $theme_stylesheet ]['new_version'], '<' ) ) {
							$theme_value      .= ' Update available: ' . $themes_update->response[ $theme_stylesheet ]['new_version'] . ' (X)';
							$theme_value_html .= ' <span style="color:red;">' . esc_html__( 'Update available', 'learndash' ) . ': ' . $themes_update->response[ $theme_stylesheet ]['new_version'] . '</span>';
						}
					}

					$theme_value      .= ' Path: ' . $current_theme->get( 'ThemeURI' );
					$theme_value_html .= '<br />' . esc_html__( 'Path', 'learndash' ) . ': ' . $current_theme->get( 'ThemeURI' );

					$this->settings_set['settings']['active_theme'] = array(
						'label'      => $current_theme->get( 'Name' ),
						'value'      => $theme_value,
						'value_html' => $theme_value_html,
					);
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
		LearnDash_Settings_Section_Support_WordPress_Themes::add_section_instance();
	}
);
