<?php
/**
 * LearnDash Settings Section for Support Templates Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_Templates' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Support_Templates extends LearnDash_Settings_Section {

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
			$this->setting_option_key = 'ld_templates';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_ld_templates';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Templates', 'learndash' );

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			$ABSPATH_tmp = str_replace( '\\', '/', ABSPATH );

			/************************************************************************************************
			 * Learndash Templates.
			 ************************************************************************************************/

			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {
				$this->load_templates();

				$this->settings_set           = array();
				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);

				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Template Name', 'learndash' ),
						'text'  => 'Template Name',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Template Path', 'learndash' ),
						'text'  => 'Template Path',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['desc'] = '';

				$this->settings_set['desc'] .= '<p><strong>' . esc_html__( 'Current Active LD Theme', 'learndash' ) . '</strong>: ' . LearnDash_Theme_Register::get_active_theme_name() . '</p>';

				$template_paths = SFWD_LMS::get_template_paths( 'xxx.php' );

				$theme_root = get_theme_root();
				$theme_root = str_replace( '\\', '/', $theme_root );

				$this->settings_set['desc'] .= '<p>' . esc_html__( 'The following is the search order paths for override templates, relative to site root:', 'learndash' );

				$this->settings_set['desc'] .= '<ol>';

				if ( ( isset( $template_paths['theme'] ) ) && ( ! empty( $template_paths['theme'] ) ) ) {
					foreach ( $template_paths['theme'] as $theme_path ) {
						$theme_path = dirname( $theme_path );
						if ( '.' === $theme_path ) {
							$theme_path = '';
						} else {
							$theme_path = '/' . $theme_path;
						}
						$this->settings_set['desc'] .= '<li>' . str_replace( $ABSPATH_tmp, '/', $theme_root ) . '/' . esc_html__( '<PARENT or CHILD THEME>', 'learndash' ) . $theme_path . '</li>';
					}
				}

				if ( ( isset( $template_paths['templates'] ) ) && ( ! empty( $template_paths['templates'] ) ) ) {
					foreach ( $template_paths['templates'] as $theme_path ) {
						$theme_path = dirname( $theme_path );
						if ( '.' === $theme_path ) {
							$theme_path = '';
						}
						$this->settings_set['desc'] .= '<li>' . str_replace( $ABSPATH_tmp, '/', $theme_path ) . '</li>';
					}
				}

				$this->settings_set['desc'] .= '</ol></p>';

				$this->settings_set['settings'] = array();

				$ABSPATH_tmp                  = str_replace( '\\', '/', ABSPATH );
				$LEARNDASH_LMS_PLUGIN_DIR_tmp = str_replace( '\\', '/', LEARNDASH_LMS_PLUGIN_DIR );

				if ( ! empty( $this->template_array ) ) {
					foreach ( $this->template_array as $template_filename => $template_path ) {
						if ( ! empty( $template_path ) ) {
							$template_path = str_replace( '\\', '/', $template_path );

							$this->settings_set['settings'][ $template_filename ] = array(
								'label' => $template_filename,
							);

							if ( strncmp( $template_path, $LEARNDASH_LMS_PLUGIN_DIR_tmp, strlen( $LEARNDASH_LMS_PLUGIN_DIR_tmp ) ) != 0 ) {
								$this->settings_set['settings'][ $template_filename ]['value_html'] = '<span style="color: red;">' . str_replace( $ABSPATH_tmp, '', $template_path ) . '</span>';
								$this->settings_set['settings'][ $template_filename ]['value']      = str_replace( $ABSPATH_tmp, '', $template_path ) . ' (X)';
							} else {
								$this->settings_set['settings'][ $template_filename ]['value_html'] = str_replace( $ABSPATH_tmp, '', $template_path );
								$this->settings_set['settings'][ $template_filename ]['value']      = str_replace( $ABSPATH_tmp, '', $template_path );
							}
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

				/**
		 * Load template files in preparation for processing.
		 *
		 * @since 2.3
		 */
		public function load_templates() {
			$this->template_array = array();

			$ABSPATH_tmp = str_replace( '\\', '/', ABSPATH );
			$LEARNDASH_LMS_PLUGIN_DIR_tmp = str_replace( '\\', '/', LEARNDASH_LMS_PLUGIN_DIR );

			$active_theme_instance = LearnDash_Theme_Register::get_active_theme_instance();
			if ( is_a( $active_theme_instance, 'LearnDash_Theme_Register' ) ) {
				$active_theme_dir = $active_theme_instance->get_theme_template_dir();
				$template_files = learndash_scandir_recursive( $active_theme_dir );
				if ( ! empty( $template_files ) ) {
					foreach ( $template_files as $idx => $template_file ) {
						$template_file = str_replace( '\\', '/', $template_file );
						$file_pathinfo = pathinfo( $template_file );
						if ( ( ! isset( $file_pathinfo['extension'] ) ) || ( empty( $file_pathinfo['extension'] ) ) ) {
							continue;
						}

						if ( ( ! isset( $file_pathinfo['filename'] ) ) || ( empty( $file_pathinfo['filename'] ) ) ) {
							continue;
						}

						if ( ! in_array( $file_pathinfo['extension'], array( 'php', 'css', 'js' ) ) ) {
							continue;
						}

						if ( '_' === $file_pathinfo['filename'][0] ) {
							continue;
						}

						if ( false !== strpos( $file_pathinfo['filename'], '.min.' ) ) {
							continue;
						}

						if ( ! in_array( $template_file, $this->template_array ) ) {
							$template_filename = str_replace( $active_theme_dir . '/', '', $template_file );
							$template_path = SFWD_LMS::get_template( $template_filename, null, null, true );
							if ( ! empty( $template_path ) ) {
								$this->template_array[ $template_filename ] = $template_path;
							}
						}
					}
				}
			}
			
			if ( LearnDash_Theme_Register::get_active_theme_key() !== LEARNDASH_LEGACY_THEME ) {
				$legacy_theme_instance = LearnDash_Theme_Register::get_theme_instance( LEARNDASH_LEGACY_THEME );
				if ( is_a( $active_theme_instance, 'LearnDash_Theme_Register' ) ) {
					$legacy_theme_dir = $legacy_theme_instance->get_theme_template_dir();
					if ( ! empty( $legacy_theme_dir ) ) {
						$template_files = learndash_scandir_recursive( $legacy_theme_dir );
						if ( ! empty( $template_files ) ) {
							foreach ( $template_files as $idx => $template_file ) {
								$template_file = str_replace( '\\', '/', $template_file );
								//$template_file = str_replace( $ABSPATH_tmp, '', $template_file );
								$file_pathinfo = pathinfo( $template_file );
								if ( ( ! isset( $file_pathinfo['extension'] ) ) || ( empty( $file_pathinfo['extension'] ) ) ) {
									continue;
								}

								if ( ( ! isset( $file_pathinfo['filename'] ) ) || ( empty( $file_pathinfo['filename'] ) ) ) {
									continue;
								}

								if ( ! in_array( $file_pathinfo['extension'], array( 'php', 'css', 'js' ) ) ) {
									continue;
								}

								if ( '_' === $file_pathinfo['filename'][0] ) {
									continue;
								}

								if ( false !== strpos( $file_pathinfo['filename'], '.min.' ) ) {
									continue;
								}

								$template_filename = str_replace( $legacy_theme_dir . '/', '', $template_file );
								if ( ! isset( $this->template_array[ $template_filename ] ) ) {
									$template_path = SFWD_LMS::get_template( $template_filename, null, null, true );
									if ( ! empty( $template_path ) ) {
										$this->template_array[ $template_filename ] = $template_path;
									}
								}
							}
						}
					}
				}
			}

			if ( ! empty( $this->template_array ) ) {
				ksort( $this->template_array );

				// We want to reorder the 
				$templates_grouped = array(
					'override' => array(),
				);

				$active_theme_dir = '';
				$legacy_theme_dir = '';
				$active_theme_instance = LearnDash_Theme_Register::get_active_theme_instance();
				if ( is_a( $active_theme_instance, 'LearnDash_Theme_Register' ) ) {
					$templates_grouped['active'] = array();
					$active_theme_dir = $active_theme_instance->get_theme_template_dir();
				} 

				if ( LearnDash_Theme_Register::get_active_theme_key() !== LEARNDASH_LEGACY_THEME ) {
					$legacy_theme_instance = LearnDash_Theme_Register::get_theme_instance( LEARNDASH_LEGACY_THEME );
					if ( is_a( $active_theme_instance, 'LearnDash_Theme_Register' ) ) {
						$templates_grouped['legacy'] = array();
						$legacy_theme_dir = $legacy_theme_instance->get_theme_template_dir();
					}
				}

				foreach ( $this->template_array as $template_filename => $template_path ) {
					if ( strncmp( $template_path, $LEARNDASH_LMS_PLUGIN_DIR_tmp, strlen( $LEARNDASH_LMS_PLUGIN_DIR_tmp ) ) != 0 ) {
						$templates_grouped['override'][ $template_filename ] = $template_path;
					} else if ( ( ! empty( $active_theme_dir ) ) && ( strncmp( $template_path, $active_theme_dir, strlen( $active_theme_dir ) ) == 0 ) ) {
						$templates_grouped['active'][ $template_filename ] = $template_path;
					} else if ( ( ! empty( $legacy_theme_dir ) ) && ( strncmp( $template_path, $legacy_theme_dir, strlen( $legacy_theme_dir ) ) == 0 ) ) {
						$templates_grouped['legacy'][ $template_filename ] = $template_path;
					}
				}

				$this->template_array = array();
				foreach( $templates_grouped as $template_section => $template_array ) {
					if ( ! empty( $template_array ) ) {
						$this->template_array = array_merge( $this->template_array, $template_array );
					}
				}
			}
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Support_Templates::add_section_instance();
	}
);
