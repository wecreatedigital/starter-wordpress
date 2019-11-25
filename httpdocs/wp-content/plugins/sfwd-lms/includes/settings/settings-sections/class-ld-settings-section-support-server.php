<?php
/**
 * LearnDash Settings Section for Support Server Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_Server' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Support_Server extends LearnDash_Settings_Section {

		/**
		 * Settings set array for this section.
		 *
		 * @var array $settings_set Array of settings used by this section.
		 */
		protected $settings_set = array();

		/**
		 * PHP ini settings array.
		 *
		 * @var array $php_ini_settings Array of PHP settings to check.
		 */
		private $php_ini_settings = array( 'max_execution_time', 'max_input_time', 'max_input_vars', 'post_max_size', 'max_file_uploads', 'upload_max_filesize' );

		/**
		 * PHP extensions array.
		 *
		 * @var array $php_extensions Array of PHP extensions to check.
		 */
		private $php_extensions = array( 'mbstring' );

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_support';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'server_settings';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_server_settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Server Settings', 'learndash' );

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			$ABSPATH_tmp = str_replace( '\\', '/', ABSPATH );

			/************************************************************************************************
			 * Server Settings.
			 ************************************************************************************************/
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {
				$this->settings_set = array();

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

				$php_version                            = phpversion();
				$this->settings_set['settings']['phpversion'] = array(
					'label'      => 'PHP Version',
					'label_html' => esc_html__( 'PHP Version', 'learndash' ),
					'value'      => $php_version,
				);

				$version_compare = version_compare( '7.0', $php_version, '>' );
				$color           = 'green';
				if ( -1 == $version_compare ) {
					$color = 'red';
				}
				$this->settings_set['settings']['phpversion']['value_html'] = '<span style="color: ' . $color . '">' . $php_version . '</span>';
				if ( -1 == $version_compare ) {
					$this->settings_set['settings']['phpversion']['value_html'] .= ' - <a href="https://wordpress.org/about/requirements/" target="_blank">' . esc_html__( 'WordPress Minimum Requirements', 'learndash' ) . '</a>';
				}

				if ( defined( 'PHP_OS' ) ) {
					$this->settings_set['settings']['PHP_OS'] = array(
						'label'      => 'PHP OS',
						'label_html' => esc_html__( 'PHP OS', 'learndash' ),
						'value'      => PHP_OS,
					);
				}

				if ( defined( 'PHP_OS_FAMILY' ) ) {
					$this->settings_set['settings']['PHP_OS_FAMILY'] = array(
						'label'      => 'PHP OS Family',
						'label_html' => esc_html__( 'PHP OS Family', 'learndash' ),
						'value'      => PHP_OS_FAMILY,
					);
				}

				if ( true == $wpdb->is_mysql ) {
					global $required_mysql_version;

					$mysql_version = $wpdb->db_version();

					$this->settings_set['settings']['mysql_version'] = array(
						'label'      => 'MySQL version',
						'label_html' => esc_html__( 'MySQL version', 'learndash' ),
						'value'      => $mysql_version,
					);

					$version_compare = version_compare( $required_mysql_version, $mysql_version, '>' );
					$color           = 'green';
					if ( -1 == $version_compare ) {
						$color = 'red';
					}

					$this->settings_set['settings']['mysql_version']['value_html'] = '<span style="color: ' . $color . '">' . $mysql_version . '</span>';
					if ( -1 == $version_compare ) {
						$this->settings_set['settings']['mysql_version']['value_html'] .= ' - <a href="https://wordpress.org/about/requirements/" target="_blank">' . esc_html__( 'WordPress Minimum Requirements', 'learndash' ) . '</a>';
					}
				}

				$this->php_ini_settings = apply_filters( 'learndash_support_php_ini_settings', $this->php_ini_settings );
				if ( ! empty( $this->php_ini_settings ) ) {
					sort( $this->php_ini_settings );
					$this->php_ini_settings = array_unique( $this->php_ini_settings );

					foreach ( $this->php_ini_settings as $ini_key ) {
						$this->settings_set['settings'][ $ini_key ] = array(
							'label' => $ini_key,
							'value' => ini_get( $ini_key ),
						);
					}

					$this->settings_set['settings']['curl'] = array(
						'label' => 'curl',
					);

					if ( ! extension_loaded( 'curl' ) ) {
						$this->settings_set['settings']['curl']['value']      = 'No';
						$this->settings_set['settings']['curl']['value_html'] = '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>';

					} else {
						$this->settings_set['settings']['curl']['value']      = 'Yes<br />';
						$this->settings_set['settings']['curl']['value_html'] = '<span style="color: green">' . esc_html__( 'Yes', 'learndash' ) . '</span><br />';

						$version = curl_version();

						$this->settings_set['settings']['curl']['value']      .= 'Version: ' . $version['version'] . '<br />';
						$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Version', 'learndash' ) . ': ' . $version['version'] . '<br />';

						$this->settings_set['settings']['curl']['value']      .= 'SSL Version: ' . $version['ssl_version'] . '<br />';
						$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'SSL Version', 'learndash' ) . ': ' . $version['ssl_version'] . '<br />';

						$this->settings_set['settings']['curl']['value']      .= 'Libz Version: ' . $version['libz_version'] . '<br />';
						$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Libz Version', 'learndash' ) . ': ' . $version['libz_version'] . '<br />';

						$this->settings_set['settings']['curl']['value']      .= 'Protocols: ' . join( ', ', $version['protocols'] ) . '<br />';
						$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Protocols', 'learndash' ) . ': ' . join( ', ', $version['protocols'] ) . '<br />';

						if ( isset( $_GET['ld_debug'] ) ) {
							$paypal_email         = get_option( 'learndash_settings_paypal' );
							$ca_certificates_path = ini_get( 'curl.cainfo' );

							if ( ! $ca_certificates_path ) {
								if ( isset( $paypal_email['paypal_email'] ) && ! empty( $paypal_email['paypal_email'] ) ) {
									$this->settings_set['settings']['curl']['value']      .= 'Path to the CA certificates not set. Please add it to curl.cainfo in the php.ini file. Otherwise, PayPal may not work. (X)<br />';
									$this->settings_set['settings']['curl']['value_html'] .= '<span style="color: red">' . esc_html__( 'Path to the CA certificates not set. Please add it to curl.cainfo in the php.ini file. Otherwise, PayPal may not work.', 'learndash' ) . '</span><br />';
								}

								if ( isset( $paypal_email['paypal_email'] ) && empty( $paypal_email['paypal_email'] ) ) {
									$this->settings_set['settings']['curl']['value']      .= 'Path to the CA certificates not set. (X)<br />';
									$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Path to the CA certificates not set.', 'learndash' ) . '</span><br />';
								}
							} else {
								$this->settings_set['settings']['curl']['value']      .= 'Path to the CA certificates: ' . $ca_certificates_path . '<br />';
								$this->settings_set['settings']['curl']['value_html'] .= esc_html__( 'Path to the CA certificates', 'learndash' ) . ': ' . $ca_certificates_path . '</span><br />';
							}
						}
					}
				}

				$this->php_extensions = apply_filters( 'learndash_support_php_extensions', $this->php_extensions );
				if ( ! empty( $this->php_extensions ) ) {
					sort( $this->php_extensions );
					$this->php_extensions = array_unique( $this->php_extensions );

					foreach ( $this->php_extensions as $ini_key ) {
						$this->settings_set['settings'][ $ini_key ] = array(
							'label'      => $ini_key,
							'value'      => extension_loaded( $ini_key ) ? 'Yes' : 'No (X)',
							'value_html' => extension_loaded( $ini_key ) ? esc_html__( 'Yes', 'learndash' ) : '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>',
						);
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
		LearnDash_Settings_Section_Support_Server::add_section_instance();
	}
);
