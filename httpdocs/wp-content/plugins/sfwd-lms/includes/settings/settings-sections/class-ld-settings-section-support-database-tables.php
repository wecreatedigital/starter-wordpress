<?php
/**
 * LearnDash Settings Section for Support Database Tables Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_Database_Tables' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Support_Database_Tables extends LearnDash_Settings_Section {

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
			$this->setting_option_key = 'ld_database_tables';

			// This is the HTML form field prefix used.
			//$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_ld_database_tables';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Database Tables', 'learndash' );

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			$ABSPATH_tmp = str_replace( '\\', '/', ABSPATH );

			/************************************************************************************************
			 * Learndash Database Tables
			 ************************************************************************************************/
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {

				$this->settings_set           = array();
				
				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);
				
				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Table Name', 'learndash' ),
						'text'  => 'Table Name',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Present', 'learndash' ),
						'text'  => 'Present',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['desc'] = '<p>' . esc_html__( 'When the LearnDash plugin or related add-ons are activated they will create the following tables. If the tables are not present try reactivating the plugin. If the table still do not show check the DB_USER defined in your wp-config.php and ensure it has the proper permissions to create tables. Check with your host for help.', 'learndash' ) . '</p>';
				$grants               = learndash_get_db_user_grants();
				if ( ! empty( $grants ) ) {
					if ( ( array_search( 'ALL PRIVILEGES', $grants ) === false ) && ( array_search( 'CREATE', $grants ) === false ) ) {
						$this->settings_set['desc'] .= '<p style="color: red">' . esc_html__( 'The DB_USER defined in your wp-config.php does not have CREATE permission.', 'learndash' ) . '</p>';
					}
				}

				$this->settings_set['settings'] = array();

				$this->db_tables = LDLMS_DB::get_tables();
				$this->db_tables = apply_filters( 'learndash_support_db_tables', $this->db_tables );
				if ( ! empty( $this->db_tables ) ) {
					sort( $this->db_tables );
					$this->db_tables = array_unique( $this->db_tables );

					foreach ( $this->db_tables as $db_table ) {
						$this->settings_set['settings'][ $db_table ] = array(
							'label' => $db_table,
						);

						if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $db_table . "'" ) == $db_table ) {
							if ( true === apply_filters( 'learndash_support_db_tables_rows', true ) ) {
								$table_rows = $wpdb->get_var( "SELECT table_rows from information_schema.tables WHERE table_schema = '" . DB_NAME . "' AND table_name = '" . $db_table . "'" );
								$rows_str = ' - rows(' . $table_rows . ')';
							} else {
								$rows_str = '';
							}

							$this->settings_set['settings'][ $db_table ]['value']      = 'Yes' . $rows_str;
							$this->settings_set['settings'][ $db_table ]['value_html'] = '<span style="color: green">' . esc_html__( 'Yes', 'learndash' ) . '</span>' . $rows_str;
						} else {
							$this->settings_set['settings'][ $db_table ]['value']      = 'No' . ' - (X)';
							$this->settings_set['settings'][ $db_table ]['value_html'] = '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>';
						}
					}
				}
				$this->system_info['ld_database_tables'] = apply_filters( 'learndash_support_section', $this->settings_set, 'ld_database_tables' );
					
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
		LearnDash_Settings_Section_Support_Database_Tables::add_section_instance();
	}
);
