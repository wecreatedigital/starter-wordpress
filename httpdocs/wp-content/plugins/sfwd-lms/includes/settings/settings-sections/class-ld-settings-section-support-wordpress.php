<?php
/**
 * LearnDash Settings Section for Support WordPress Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_WordPress' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Support_WordPress extends LearnDash_Settings_Section {

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
			$this->setting_option_key = 'wp_settings';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_wp_settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'WordPress Settings', 'learndash' );

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			$ABSPATH_tmp = str_replace( '\\', '/', ABSPATH );

			/************************************************************************************************
			 * WordPress Settings.
			 ************************************************************************************************/
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {
				$this->settings_set           = array();
				
				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);

				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Setting', 'learndash' ),
						'text'  => 'Setting',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Value', 'learndash' ),
						'text'  => 'Value',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['settings'] = array();

				$this->settings_set['settings']['wp_version'] = array(
					'label'      => 'WordPress Version',
					'label_html' => esc_html__( 'WordPress Version', 'learndash' ),
					'value'      => $wp_version,
				);

				$this->settings_set['settings']['home'] = array(
					'label'      => 'WordPress Home URL',
					'label_html' => esc_html__( 'WordPress Home URL', 'learndash' ),
					'value'      => get_option( 'home' ),
				);

				$this->settings_set['settings']['siteurl'] = array(
					'label'      => 'WordPress Site URL',
					'label_html' => esc_html__( 'WordPress Site URL', 'learndash' ),
					'value'      => get_option( 'siteurl' ),
				);

				$this->settings_set['settings']['is_multisite'] = array(
					'label'      => 'Is Multisite',
					'label_html' => esc_html__( 'Is Multisite', 'learndash' ),
					'value'      => is_multisite() ? 'Yes' : 'No',
					'value_html' => is_multisite() ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['Site Language'] = array(
					'label'      => 'Site Language',
					'label_html' => esc_html__( 'Site Language', 'learndash' ),
					'value'      => get_locale(),
				);

				if ( $wp_rewrite->using_permalinks() ) {
					$value_html = '<span style="color: green">' . esc_html__( 'Yes', 'learndash' ) . '</span>';
					$value      = 'Yes';
				} else {
					$value_html = '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>';
					$value      = 'No (X)';
				}
				$this->settings_set['settings']['using_permalinks'] = array(
					'label'      => 'Using Permalinks',
					'label_html' => esc_html__( 'Using Permalinks', 'learndash' ),
					'value_html' => $value_html,
					'value'      => $value,
				);

				$this->settings_set['settings']['Object Cache'] = array(
					'label'      => 'Object Cache',
					'label_html' => esc_html__( 'Object Cache', 'learndash' ),
					'value'      => wp_using_ext_object_cache() ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				foreach ( apply_filters( 'learndash_support_wp_defines', array( 'DISABLE_WP_CRON', 'WP_DEBUG', 'WP_DEBUG_DISPLAY', 'SCRIPT_DEBUG', 'WP_DEBUG_DISPLAY', 'WP_DEBUG_LOG', 'WP_PLUGIN_DIR', 'WP_AUTO_UPDATE_CORE', 'WP_MAX_MEMORY_LIMIT', 'WP_MEMORY_LIMIT', 'DB_CHARSET', 'DB_COLLATE' ) ) as $defined_item ) {

					$defined_value      = ( defined( $defined_item ) ) ? constant( $defined_item ) : '';
					$defined_value_html = $defined_value;
					if ( 'WP_PLUGIN_DIR' == $defined_item ) {
						$defined_value = str_replace( $ABSPATH_tmp, '', $defined_value );
					} elseif ( 'WP_MEMORY_LIMIT' == $defined_item ) {
						if ( learndash_return_bytes_from_shorthand( $defined_value ) < learndash_return_bytes_from_shorthand( '100M' ) ) {
							$defined_value     .= ' - (X) Recommended at least 100M memory.';
							$defined_value_html = '<span style="color: red;">' . $defined_value_html . '</span> - <a target="_blank" href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP">' . esc_html__( 'Recommended at least 100M memory.', 'learndash' ) . '</a>';
						} else {
							$defined_value_html = '<span style="color: green;">' . $defined_value_html . '</span>';
						}
					} elseif ( 'WP_MAX_MEMORY_LIMIT' == $defined_item ) {
						if ( learndash_return_bytes_from_shorthand( $defined_value ) < learndash_return_bytes_from_shorthand( '256M' ) ) {
							$defined_value     .= ' - (X) Recommended at least 256M memory.';
							$defined_value_html = '<span style="color: red;">' . $defined_value_html . '</span> - <a target="_blank" href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP">' . esc_html__( 'Recommended at least 256M memory.', 'learndash' ) . '</a>';
						} else {
							$defined_value_html = '<span style="color: green;">' . $defined_value_html . '</span>';
						}
					}

					$this->settings_set['settings'][ $defined_item ] = array(
						'label'      => $defined_item,
						'label_html' => $defined_item,
						'value'      => $defined_value,
						'value_html' => $defined_value_html,
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
		LearnDash_Settings_Section_Support_WordPress::add_section_instance();
	}
);
